<?php

namespace App\Http\Controllers;

use App\Models\Dte;
use App\Models\DteError;
use App\Models\Contingencia;
use App\Models\Company;
use App\Services\DteService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Exception;

class DteAdminController extends Controller
{
    protected $dteService;

    public function __construct(DteService $dteService)
    {
        $this->dteService = $dteService;

        // Middleware de permisos
        $this->middleware('permission:dte.dashboard')->only(['dashboard', 'estadisticas']);
        $this->middleware('permission:dte.procesar')->only(['procesarCola', 'procesarReintentos']);
        $this->middleware('permission:dte.errores')->only(['errores', 'resolverError']);
        $this->middleware('permission:dte.contingencias')->only(['contingencias', 'crearContingencia']);
        $this->middleware('permission:dte.reintentar')->only(['reintentarDte']);
    }

    /**
     * Dashboard principal de DTE
     */
    public function dashboard(Request $request): View
    {
        $empresaId = $request->get('empresa_id', auth()->user()->company_id ?? null);
        $estadisticas = $this->dteService->obtenerEstadisticas($empresaId);
        $erroresCriticos = $this->dteService->obtenerErroresCriticos();
        $empresas = Company::select('id', 'name')->orderBy('name')->get();

        // Obtener últimos DTE procesados
        $ultimosDte = Dte::with(['company', 'sale'])
            ->when($empresaId, function($query) use ($empresaId) {
                return $query->where('company_id', $empresaId);
            })
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Obtener contingencias activas
        $contingenciasActivas = Contingencia::with(['empresa', 'creador'])
            ->when($empresaId, function($query) use ($empresaId) {
                return $query->where('empresa_id', $empresaId);
            })
            ->activas()
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('dte.dashboard', compact(
            'estadisticas',
            'erroresCriticos',
            'empresas',
            'empresaId',
            'ultimosDte',
            'contingenciasActivas'
        ));
    }

    /**
     * Mostrar estadísticas detalladas
     */
    public function estadisticas(Request $request): JsonResponse
    {
        $empresaId = $request->get('empresa_id');
        $estadisticas = $this->dteService->obtenerEstadisticas($empresaId);

        return response()->json($estadisticas);
    }

    /**
     * Procesar cola de DTE
     */
    public function procesarCola(Request $request): JsonResponse
    {
        try {
            $limite = $request->get('limite', 10);
            $resultados = $this->dteService->procesarCola($limite);

            return response()->json([
                'success' => true,
                'message' => "Procesados {$resultados['procesados']} DTE. " .
                            "Exitosos: {$resultados['exitosos']}, " .
                            "Errores: {$resultados['errores']}, " .
                            "Contingencias creadas: {$resultados['contingencias_creadas']}",
                'data' => $resultados
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar cola: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Procesar reintentos automáticos
     */
    public function procesarReintentos(Request $request): JsonResponse
    {
        try {
            $resultados = $this->dteService->procesarReintentosAutomaticos();

            return response()->json([
                'success' => true,
                'message' => "Reintentos procesados: {$resultados['reintentos_procesados']}",
                'data' => $resultados
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar reintentos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reintentar DTE específico
     */
    public function reintentarDte(Request $request, int $dteId): JsonResponse
    {
        try {
            $dte = Dte::findOrFail($dteId);
            $resultado = $this->dteService->reintentarDte($dte);

            if ($resultado['exitoso']) {
                return response()->json([
                    'success' => true,
                    'message' => 'DTE procesado exitosamente'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Error reintentando DTE: ' . $resultado['error']
                ], 400);
            }

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar lista de errores
     */
    public function errores(Request $request): View
    {
        $filtros = $request->only(['tipo', 'empresa_id', 'resuelto']);

        $query = DteError::with(['dte.company', 'resueltoPor']);

        if (isset($filtros['tipo']) && $filtros['tipo']) {
            $query->porTipo($filtros['tipo']);
        }

        if (isset($filtros['empresa_id']) && $filtros['empresa_id']) {
            $query->whereHas('dte', function($q) use ($filtros) {
                $q->where('company_id', $filtros['empresa_id']);
            });
        }

        if (isset($filtros['resuelto'])) {
            if ($filtros['resuelto'] === '1') {
                $query->where('resuelto', true);
            } else {
                $query->noResueltos();
            }
        } else {
            $query->noResueltos(); // Por defecto mostrar solo no resueltos
        }

        $errores = $query->orderBy('created_at', 'desc')->paginate(20);
        $empresas = Company::select('id', 'name')->orderBy('name')->get();

        // Calcular estadísticas
        $estadisticas = [
            'total' => DteError::count(),
            'no_resueltos' => DteError::where('resuelto', false)->count(),
            'resueltos' => DteError::where('resuelto', true)->count(),
            'criticos' => DteError::whereIn('tipo_error', ['autenticacion', 'firma', 'hacienda'])
                ->where('resuelto', false)->count()
        ];

        return view('dte.errores', compact('errores', 'empresas', 'filtros', 'estadisticas'));
    }

    /**
     * Resolver error manualmente
     */
    public function resolverError(Request $request, int $errorId): JsonResponse
    {
        $request->validate([
            'solucion' => 'required|string|max:500'
        ]);

        try {
            $resuelto = $this->dteService->resolverError(
                $errorId,
                $request->solucion,
                auth()->id()
            );

            if ($resuelto) {
                return response()->json([
                    'success' => true,
                    'message' => 'Error resuelto correctamente'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo resolver el error'
                ], 400);
            }

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error resolviendo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Gestión de contingencias DTE
     */
    public function contingencias(Request $request): View
    {
        $filtros = $request->only(['estado', 'empresa_id', 'tipo']);

        $query = Contingencia::with(['company'])
            ->orderBy('created_at', 'desc');

        // Aplicar filtros
        if (!empty($filtros['estado'])) {
            switch ($filtros['estado']) {
                case 'pendiente':
                    $query->where('codEstado', '01');
                    break;
                case 'aprobada':
                    $query->where('codEstado', '02');
                    break;
                case 'activa':
                    $query->where('activa', true);
                    break;
                case 'finalizada':
                    $query->where('fecha_fin', '<', now());
                    break;
                case 'cancelada':
                    $query->where('codEstado', '03');
                    break;
            }
        }

        if (!empty($filtros['empresa_id'])) {
            $query->where('company_id', $filtros['empresa_id']);
        }

        if (!empty($filtros['tipo'])) {
            $query->where('tipoContingencia', $filtros['tipo']);
        }

        $contingencias = $query->paginate(20);
        $empresas = Company::select('id', 'name')->orderBy('name')->get();

        return view('dte.contingencias', compact('contingencias', 'empresas', 'filtros'));
    }

    /**
     * Crear nueva contingencia
     */
    public function crearContingencia(Request $request): RedirectResponse
    {
        $request->validate([
            'empresa_id' => 'required|exists:companies,id',
            'tipo_contingencia' => 'required|string',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'required|string',
            'motivo' => 'required|string',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after:fecha_inicio',
            'resolucion_mh' => 'nullable|string',
            'dte_ids' => 'nullable|array',
            'dte_ids.*' => 'exists:dte,id'
        ]);

        try {
            DB::beginTransaction();

            $contingencia = Contingencia::create([
                'idEmpresa' => $request->empresa_id,
                'company_id' => $request->empresa_id,
                'idTienda' => 1,
                'codInterno' => 'CONT-' . time(),
                'nombre' => $request->nombre,
                'versionJson' => '1.0',
                'ambiente' => '00',
                'codEstado' => '01', // Pendiente
                'activa' => false,
                'estado' => 'Pendiente',
                'codigoGeneracion' => 'CONT-' . time(),
                'fechaCreacion' => now()->format('Y-m-d'),
                'horaCreacion' => now()->format('H:i:s'),
                'fInicio' => $request->fecha_inicio,
                'fecha_inicio' => $request->fecha_inicio,
                'fFin' => $request->fecha_fin,
                'fecha_fin' => $request->fecha_fin,
                'hInicio' => '00:00:00',
                'hFin' => '23:59:59',
                'tipoContingencia' => $request->tipo_contingencia,
                'motivoContingencia' => $request->motivo,
                'nombreResponsable' => auth()->user()->name,
                'tipoDocResponsable' => '13',
                'nuDocResponsable' => '12345678-9',
                'documentos_afectados' => count($request->dte_ids ?? []),
                'created_by' => auth()->user()->name,
                'updated_by' => auth()->user()->name
            ]);

            // Asociar DTEs si se proporcionaron
            if ($request->dte_ids) {
                Dte::whereIn('id', $request->dte_ids)
                    ->update(['idContingencia' => $contingencia->id]);
            }

            DB::commit();

            return redirect()->route('dte.contingencias')
                ->with('success', 'Contingencia creada exitosamente');

        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al crear contingencia: ' . $e->getMessage());
        }
    }

    /**
     * Aprobar contingencia
     */
    public function aprobarContingencia(Request $request, int $id): JsonResponse
    {
        try {
            $contingencia = Contingencia::findOrFail($id);

            $contingencia->update([
                'codEstado' => '02', // Aprobada
                'estado' => 'Aprobada',
                'updated_by' => auth()->user()->name
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Contingencia aprobada exitosamente'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al aprobar contingencia: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Activar contingencia
     */
    public function activarContingencia(Request $request, int $id): JsonResponse
    {
        try {
            $contingencia = Contingencia::findOrFail($id);

            $contingencia->update([
                'activa' => true,
                'codEstado' => '02', // Activa
                'estado' => 'Activa',
                'updated_by' => auth()->user()->name
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Contingencia activada exitosamente'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al activar contingencia: ' . $e->getMessage()
            ], 500);
        }
    }


    /**
     * Obtener DTEs para contingencia
     */
    public function getDtesParaContingencia(Request $request): JsonResponse
    {
        $empresaId = $request->get('empresa_id');

        if (!$empresaId) {
            return response()->json([]);
        }

        $dtes = Dte::where('company_id', $empresaId)
            ->where('codEstado', '03') // Rechazados
            ->whereNull('idContingencia')
            ->select('id', 'id_doc', 'tipoDte', 'created_at')
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get()
            ->map(function($dte) {
                return [
                    'id' => $dte->id,
                    'numero_control' => $dte->id_doc,
                    'cliente' => 'Cliente N/A', // Se puede mejorar con relación
                    'tipo_documento' => $dte->tipoDte,
                    'fecha' => $dte->created_at->format('d/m/Y')
                ];
            });

        return response()->json($dtes);
    }

    /**
     * Mostrar detalles de un DTE
     */
    public function showDte(int $id): View
    {
        $dte = Dte::with(['company', 'sale.client', 'errors.resueltoPor'])
            ->findOrFail($id);

        return view('dte.show', compact('dte'));
    }

    /**
     * Mostrar detalles de un error DTE
     */
    public function showError(int $id): View
    {
        $error = DteError::with(['dte.company', 'dte.sale.client', 'resueltoPor'])
            ->findOrFail($id);

        return view('dte.error-show', compact('error'));
    }

    /**
     * Obtener estadísticas en tiempo real
     */
    public function estadisticasTiempoReal(): JsonResponse
    {
        $empresaId = auth()->user()->company_id ?? null;
        $estadisticas = $this->dteService->obtenerEstadisticas($empresaId);
        $erroresCriticos = $this->dteService->obtenerErroresCriticos();

        return response()->json([
            'estadisticas' => $estadisticas,
            'errores_criticos' => $erroresCriticos['total'],
            'timestamp' => now()->toISOString()
        ]);
    }
}
