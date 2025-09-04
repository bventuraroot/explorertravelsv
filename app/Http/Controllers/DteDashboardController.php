<?php

namespace App\Http\Controllers;

use App\Models\Dte;
use App\Models\Company;
use App\Models\DteError;
use App\Models\Contingencia;
use App\Services\DteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DteDashboardController extends Controller
{
    protected $dteService;

    public function __construct(DteService $dteService)
    {
        $this->dteService = $dteService;
        $this->middleware('auth');
        $this->middleware('permission:dte.dashboard');
    }

    /**
     * Mostrar el dashboard principal de DTE
     */
    public function index(Request $request)
    {
        $empresaId = $request->get('empresa_id');

        // Obtener estadísticas
        $estadisticas = $this->obtenerEstadisticas($empresaId);

        // Obtener errores críticos
        $erroresCriticos = $this->obtenerErroresCriticos($empresaId);

        // Obtener últimos DTE procesados
        $ultimosDte = $this->obtenerUltimosDte($empresaId);

        // Obtener contingencias activas
        $contingenciasActivas = $this->obtenerContingenciasActivas($empresaId);

        // Obtener empresas para el filtro
        $empresas = Company::all();

        return view('dte.dashboard', compact(
            'estadisticas',
            'erroresCriticos',
            'ultimosDte',
            'contingenciasActivas',
            'empresas',
            'empresaId'
        ));
    }

    /**
     * Obtener estadísticas de DTE
     */
    public function obtenerEstadisticas($empresaId = null)
    {
        $query = Dte::query();

        if ($empresaId) {
            $query->where('company_id', $empresaId);
        }

        $total = $query->count();
        $enCola = $query->clone()->enCola()->count();
        $enviados = $query->clone()->enviados()->count();
        $rechazados = $query->clone()->rechazados()->count();
        $enRevision = $query->clone()->enRevision()->count();
        $pendientesReintento = $query->clone()->paraReintento()->count();
        $necesitanContingencia = $query->clone()->necesitanContingencia()->count();

        $porcentajeExito = $total > 0 ? round(($enviados / $total) * 100, 2) : 0;

        return [
            'total' => $total,
            'en_cola' => $enCola,
            'enviados' => $enviados,
            'rechazados' => $rechazados,
            'en_revision' => $enRevision,
            'pendientes_reintento' => $pendientesReintento,
            'necesitan_contingencia' => $necesitanContingencia,
            'porcentaje_exito' => $porcentajeExito
        ];
    }

    /**
     * Obtener errores críticos
     */
    protected function obtenerErroresCriticos($empresaId = null)
    {
        $query = DteError::query()
            ->where('tipo_error', 'CRITICO')
            ->where('resuelto', false);

        if ($empresaId) {
            $query->whereHas('dte', function($q) use ($empresaId) {
                $q->where('company_id', $empresaId);
            });
        }

        return [
            'total' => $query->count(),
            'errores' => $query->with('dte')->limit(10)->get()
        ];
    }

    /**
     * Obtener últimos DTE procesados
     */
    protected function obtenerUltimosDte($empresaId = null)
    {
        $query = Dte::with('company')
            ->orderBy('created_at', 'desc')
            ->limit(20);

        if ($empresaId) {
            $query->where('company_id', $empresaId);
        }

        return $query->get()->map(function($dte) {
            $dte->estado_color = $this->obtenerColorEstado($dte->codEstado);
            $dte->estado_texto = $this->obtenerTextoEstado($dte->codEstado);
            return $dte;
        });
    }

    /**
     * Obtener contingencias activas
     */
    public function obtenerContingenciasActivas($empresaId = null)
    {
        $query = Contingencia::with('company')
            ->where('activa', true)
            ->where('fecha_fin', '>=', now());

        if ($empresaId) {
            $query->where('company_id', $empresaId);
        }

        return $query->get()->map(function($contingencia) {
            $contingencia->tipo_texto = $this->obtenerTipoContingencia($contingencia->tipoContingencia);
            $contingencia->estado_badge = $this->generarBadgeEstado($contingencia->codEstado);
            return $contingencia;
        });
    }

    /**
     * Obtener color del estado
     */
    protected function obtenerColorEstado($codEstado)
    {
        return match($codEstado) {
            '01' => 'warning',  // En cola
            '02' => 'success',  // Enviado
            '03' => 'danger',   // Rechazado
            '10' => 'info',     // En revisión
            default => 'secondary'
        };
    }

    /**
     * Obtener texto del estado
     */
    protected function obtenerTextoEstado($codEstado)
    {
        return match($codEstado) {
            '01' => 'En Cola',
            '02' => 'Enviado',
            '03' => 'Rechazado',
            '10' => 'En Revisión',
            default => 'Desconocido'
        };
    }

    /**
     * Obtener tipo de contingencia
     */
    protected function obtenerTipoContingencia($tipo)
    {
        return match($tipo) {
            '01' => 'SII No Disponible',
            '02' => 'Certificado Expirado',
            '03' => 'Error de Conectividad',
            '04' => 'Mantenimiento Programado',
            '05' => 'Falla de Sistema',
            default => 'Otro (' . $tipo . ')'
        };
    }

    /**
     * Generar badge de estado
     */
    protected function generarBadgeEstado($codEstado)
    {
        return match($codEstado) {
            '01' => '<span class="badge bg-warning">En Proceso</span>',
            '02' => '<span class="badge bg-success">Activa</span>',
            '03' => '<span class="badge bg-danger">Rechazada</span>',
            '10' => '<span class="badge bg-info">En Revisión</span>',
            default => '<span class="badge bg-secondary">Desconocido</span>'
        };
    }

    /**
     * Procesar cola de DTE
     */
    public function procesarCola(Request $request)
    {
        $limite = $request->get('limite', 10);
        $resultado = $this->dteService->procesarCola($limite);

        return response()->json([
            'success' => true,
            'message' => "Procesados: {$resultado['procesados']}, Exitosos: {$resultado['exitosos']}, Errores: {$resultado['errores']}",
            'data' => $resultado
        ]);
    }

    /**
     * Procesar reintentos
     */
    public function procesarReintentos(Request $request)
    {
        $resultado = $this->dteService->procesarReintentosAutomaticos();

        return response()->json([
            'success' => true,
            'message' => "Reintentos procesados: {$resultado['reintentos_procesados']}",
            'data' => $resultado
        ]);
    }

    /**
     * Obtener estadísticas en tiempo real
     */
    public function estadisticasTiempoReal(Request $request)
    {
        $empresaId = $request->get('empresa_id');
        $estadisticas = $this->obtenerEstadisticas($empresaId);
        $erroresCriticos = $this->obtenerErroresCriticos($empresaId);

        return response()->json([
            'estadisticas' => $estadisticas,
            'errores_criticos' => $erroresCriticos['total']
        ]);
    }

    /**
     * Mostrar detalles de un DTE
     */
    public function show($id)
    {
        $dte = Dte::with(['company', 'errors'])->findOrFail($id);

        return view('dte.show', compact('dte'));
    }
}
