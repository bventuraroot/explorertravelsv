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
            ->get()
            ->filter(function($dte) {
                return $dte->id !== null && $dte->id !== '';
            });

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

        // Consulta simplificada para debug
        $query = DteError::with(['dte.company', 'resueltoPor']);

        // Aplicar filtros solo si se especifican
        if (isset($filtros['tipo']) && $filtros['tipo'] && $filtros['tipo'] !== 'todos') {
            $query->porTipo($filtros['tipo']);
        }

        if (isset($filtros['empresa_id']) && $filtros['empresa_id'] && $filtros['empresa_id'] !== 'todas') {
            $query->whereHas('dte', function($q) use ($filtros) {
                $q->where('company_id', $filtros['empresa_id']);
            });
        }

        if (isset($filtros['resuelto']) && $filtros['resuelto'] !== 'todos') {
            if ($filtros['resuelto'] === '1') {
                $query->where('resuelto', true);
            } elseif ($filtros['resuelto'] === '0') {
                $query->noResueltos();
            }
        } else {
            // Por defecto mostrar solo no resueltos
            $query->noResueltos();
        }

        $errores = $query->orderBy('created_at', 'desc')->paginate(20);
        $empresas = Company::select('id', 'name')->orderBy('name')->get();

        // Debug: Log de información
        \Log::info('DTE Errores Debug', [
            'filtros' => $filtros,
            'total_errores' => $errores->total(),
            'errores_count' => $errores->count(),
            'query_sql' => $query->toSql(),
            'query_bindings' => $query->getBindings()
        ]);

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
            'tipo_contingencia' => 'required',
            'motivo' => 'required|string',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after:fecha_inicio',
            'resolucion_mh' => 'nullable|string',
            'dte_ids' => 'required|array',
            'dte_ids.*' => 'string' // IDs de ventas con prefijo SALE-
        ]);

        try {
            DB::beginTransaction();

            // Fechas y horas al estilo Roma Copies
            $fechaCreacion = now();
            $fInicio = \Carbon\Carbon::parse($request->fecha_inicio);
            $fFin = \Carbon\Carbon::parse($request->fecha_fin);

            $contingencia = Contingencia::create([
                'idEmpresa' => $request->empresa_id,
                'versionJson' => '3',
                'ambiente' => '00',
                'codEstado' => '01',
                'estado' => 'En Cola',
                'tipoContingencia' => $request->tipo_contingencia,
                'motivoContingencia' => $request->motivo,
                'nombreResponsable' => auth()->user()->name ?? 'Sistema',
                'tipoDocResponsable' => '13',
                'nuDocResponsable' => '00000000-0',
                'fechaCreacion' => $fechaCreacion->format('Y-m-d H:i:s'),
                'fInicio' => $fInicio->format('Y-m-d'),
                'fFin' => $fFin->format('Y-m-d'),
                'horaCreacion' => $fechaCreacion->format('H:i:s'),
                'hInicio' => $fInicio->format('H:i:s'),
                'hFin' => $fFin->format('H:i:s'),
                'codigoGeneracion' => strtoupper(\Str::uuid()->toString())
            ]);

            // Asociar SOLO ventas sin DTE seleccionadas (prefijo SALE-)
            $saleIds = collect($request->dte_ids)
                ->filter(fn($v) => is_string($v) && str_starts_with($v, 'SALE-'))
                ->map(fn($v) => intval(str_replace('SALE-', '', $v)))
                ->filter()->values();

            if ($saleIds->isEmpty()) {
                DB::rollBack();
                return redirect()->back()->withInput()->with('danger', 'Debe seleccionar al menos una venta en borrador');
            }

            // Marcar ventas con la contingencia y generar codigoGeneracion si falta
            \DB::table('sales')->whereIn('id', $saleIds)->update(['id_contingencia' => $contingencia->id]);
            $salesToCode = \DB::table('sales')->whereIn('id', $saleIds)->whereNull('codigoGeneracion')->pluck('id');
            foreach ($salesToCode as $sid) {
                \DB::table('sales')->where('id', $sid)->update(['codigoGeneracion' => strtoupper(\Str::uuid()->toString())]);
            }

            // documentos_afectados
            \DB::table('contingencias')->where('id', $contingencia->id)->update(['documentos_afectados' => $saleIds->count()]);

            DB::commit();

            return redirect()->route('dte.contingencias')
                ->with('success', 'Contingencia creada exitosamente');

        } catch (Exception $e) {
            DB::rollBack();
            \Log::error('Error creando contingencia', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
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

        // Solo ventas sin DTE (borradores) de esta empresa
        $ventasSinDte = \DB::table('sales as a')
            ->leftJoin('dte as b', 'b.sale_id', '=', 'a.id')
            ->leftJoin('typedocuments as t', 't.id', '=', 'a.typedocument_id')
            ->where('a.company_id', $empresaId)
            ->whereNull('b.sale_id')
            ->select(
                'a.id',
                'a.created_at',
                't.type as tipo_documento'
            )
            ->orderBy('a.created_at', 'desc')
            ->limit(300)
            ->get()
            ->map(function($row){
                return [
                    'id' => 'SALE-' . $row->id,
                    'numero_control' => $row->id,
                    'cliente' => 'N/A',
                    'tipo_documento' => $row->tipo_documento ?? 'N/A',
                    'estado' => 'Sin DTE (Borrador)',
                    'fecha' => optional($row->created_at)->format('d/m/Y')
                ];
            });

        return response()->json($ventasSinDte->values());
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
