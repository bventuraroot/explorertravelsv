<?php

namespace App\Http\Controllers;

use App\Models\EmailPurchaseImport;
use App\Models\Company;
use App\Models\Provider;
use App\Services\EmailPurchaseService;
use App\Services\PurchaseImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;

class EmailPurchaseController extends Controller
{
    public function __construct(
        private EmailPurchaseService $emailService,
        private PurchaseImportService $importService
    ) {}

    public function index(Request $request): View
    {
        $query = EmailPurchaseImport::with(['company', 'purchase.provider'])
            ->orderBy('email_date', 'desc')
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            if ($request->status !== 'all') {
                $query->where('status', $request->status);
            }
        } else {
            $query->whereNull('purchase_id'); // Por defecto ocultar procesados
        }

        if ($request->filled('date_from')) $query->whereDate('email_date', '>=', $request->date_from);
        if ($request->filled('date_to'))   $query->whereDate('email_date', '<=', $request->date_to);

        return view('email-purchases.index', [
            'imports' => $query->paginate(20)->withQueryString(),
            'stats'   => [
                'total'     => EmailPurchaseImport::count(),
                'processed' => EmailPurchaseImport::where('status', 'processed')->count(),
                'errors'    => EmailPurchaseImport::where('status', 'error')->count(),
                'skipped'   => EmailPurchaseImport::where('status', 'skipped')->count(),
                'pending'   => EmailPurchaseImport::where('status', 'pending')->count(),
            ]
        ]);
    }

    public function run(Request $request): JsonResponse
    {
        try {
            $limit  = (int) $request->input('limit', 0);
            $userId = auth()->id() ?? 1;

            $summary = $this->emailService->run($limit, $userId);

            return response()->json([
                'success' => true,
                'summary' => $summary,
                'message' => "Completado: {$summary['processed']} procesados, {$summary['errors']} errores, {$summary['skipped']} omitidos.",
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('[EmailPurchase] Error en ejecución web: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Error de sincronización: ' . $e->getMessage()
            ], 200);
        }
    }

    /**
     * Obtener logs de sincronización recientes.
     */
    public function getLogs(): JsonResponse
    {
        try {
            $logPath = storage_path('logs/laravel.log');
            if (!file_exists($logPath)) {
                return response()->json([
                    'success' => true,
                    'logs' => 'No hay logs registrados en este servidor de Laravel.'
                ]);
            }

            $file = new \SplFileObject($logPath, 'r');
            $file->seek(PHP_INT_MAX);
            $totalLines = $file->key();
            
            // Leer las últimas 250 líneas
            $startLine = max(0, $totalLines - 250);
            $file->seek($startLine);

            $lines = [];
            while (!$file->eof()) {
                $line = $file->fgets();
                if ($line !== false) {
                    $lines[] = trim($line);
                }
            }

            // Filtrar las líneas asociadas a [EmailPurchase]
            $filteredLines = [];
            foreach ($lines as $line) {
                if (str_contains($line, '[EmailPurchase]') || str_contains($line, 'EmailPurchaseService')) {
                    $filteredLines[] = $line;
                }
            }

            // Si está muy vacío (tal vez porque no hay logs específicos aún), mostraremos las últimas 60 líneas completas para dar contexto general
            if (count($filteredLines) < 5) {
                $filteredLines = array_slice($lines, -60);
            }

            return response()->json([
                'success' => true,
                'logs' => implode("\n", $filteredLines)
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al leer logs: ' . $e->getMessage()
            ]);
        }
    }

    public function show($id): View
    {
        $import = EmailPurchaseImport::with([
            'company', 'purchase.provider'
        ])->findOrFail($id);

        $parsedHeader = null;
        if (!$import->purchase && $import->raw_json && $import->company) {
            try {
                $dte = json_decode($import->raw_json, true);
                $parsed = app(\App\Services\DtePurchaseParser::class)->parse($dte, $import->company, auth()->id() ?? 1);
                $parsedHeader = (object) $parsed['header'];
            } catch (\Throwable $e) {}
        }

        return view('email-purchases.show', [
            'import'       => $import,
            'companies'    => Company::orderBy('name')->get(['id', 'name']),
            'providers'    => Provider::orderBy('razonsocial')->get(['id', 'razonsocial', 'nit', 'ncr']),
            'parsedHeader' => $parsedHeader
        ]);
    }

    public function confirm(Request $request, $id): JsonResponse
    {
        $request->validate([
            'provider_id' => 'required|integer|exists:providers,id',
            'company_id'  => 'required|integer|exists:companies,id',
        ]);

        $import = EmailPurchaseImport::findOrFail($id);
        try {
            $userId = auth()->id() ?? 1;
            $this->importService->confirmPurchaseManual($import, $request->provider_id, $request->company_id, $userId);
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getDetails($id): JsonResponse
    {
        try {
            $import = EmailPurchaseImport::with(['company'])->findOrFail($id);
            $dte = json_decode($import->raw_json, true);
            $root = isset($dte['dte']) ? $dte['dte'] : (isset($dte['DTE']) ? $dte['DTE'] : $dte);

            // Resolver emisor/proveedor
            $providerId = null;
            try {
                $providerId = app(\App\Services\DtePurchaseParser::class)->resolveProvider($root['emisor'] ?? []);
            } catch (\Throwable $e) {}

            return response()->json([
                'success' => true,
                'data' => [
                    'id'                => $import->id,
                    'number'            => $import->dte_numero_control,
                    'date'              => $root['identificacion']['fecEmi'] ?? date('Y-m-d'),
                    'period'            => date('m', strtotime($root['identificacion']['fecEmi'] ?? date('Y-m-d'))),
                    'company_id'        => $import->company_id,
                    'provider_id'       => $providerId,
                    'codigo_generacion' => $import->dte_codigo_generacion,
                    'sello_recepcion'   => $import->dte_sello_recepcion,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function showPdf($id)
    {
        $import = EmailPurchaseImport::findOrFail($id);
        if (!$import->pdf_path) abort(404);

        $absolutePath = Storage::disk('public')->path($import->pdf_path);
        if (!file_exists($absolutePath)) abort(404);

        return response()->file($absolutePath, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . basename($absolutePath) . '"'
        ]);
    }
}
