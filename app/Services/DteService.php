<?php

namespace App\Services;

use App\Models\Dte;
use App\Models\DteError;
use App\Models\Contingencia;
use App\Models\Sale;
use App\Models\Company;
use App\Mail\EnviarCorreo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Exception;

class DteService
{
    protected $errorHandler;

    public function __construct()
    {
        $this->errorHandler = new ElectronicInvoiceErrorHandler();
    }

    /**
     * Procesar cola de DTE con gestión de errores
     */
    public function procesarCola(int $limite = 10): array
    {
        $resultados = [
            'procesados' => 0,
            'exitosos' => 0,
            'errores' => 0,
            'contingencias_creadas' => 0,
            'correos_enviados' => 0,
            'detalles' => []
        ];

        $dtesEnCola = Dte::where('codEstado', '01')
            ->whereNull('idContingencia')
            ->limit($limite)
            ->get();

        foreach ($dtesEnCola as $dte) {
            try {
                $resultado = $this->procesarDte($dte);

                if ($resultado['exitoso']) {
                    $resultados['exitosos']++;

                    // Enviar correo si es ambiente de producción
                    if ($resultado['ambiente'] === '01') {
                        $this->enviarCorreoAutomatico($dte);
                        $resultados['correos_enviados']++;
                    }
                } else {
                    $resultados['errores']++;

                    // Usar el manejador de errores
                    $this->errorHandler->handleDocumentError($dte, $resultado['error_data']);

                    // Crear contingencia si es necesario
                    if ($resultado['necesita_contingencia']) {
                        $this->crearContingenciaAutomatica($dte, $resultado['error']);
                        $resultados['contingencias_creadas']++;
                    }
                }

                $resultados['procesados']++;
                $resultados['detalles'][] = $resultado;

            } catch (Exception $e) {
                Log::error('Error procesando DTE', [
                    'dte_id' => $dte->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                $this->errorHandler->handleDocumentError($dte, [], $e);
                $resultados['errores']++;
            }
        }

        return $resultados;
    }

    public function procesarDte(Dte $dte): array
    {
        $resultado = ['dte_id' => $dte->id, 'exitoso' => false, 'error' => null, 'necesita_contingencia' => false, 'tipo_error' => null];
        try {
            $validacion = $this->validarDte($dte);
            if (!$validacion['valido']) {
                $this->registrarError($dte, 'validacion', 'VALIDATION_ERROR', $validacion['error']);
                return array_merge($resultado, ['error' => $validacion['error'], 'tipo_error' => 'validacion']);
            }
            $comprobante = $this->obtenerComprobante($dte);
            if (empty($comprobante)) {
                $error = 'No se pudo obtener el comprobante';
                $this->registrarError($dte, 'datos', 'DATA_ERROR', $error);
                return array_merge($resultado, ['error' => $error, 'tipo_error' => 'datos']);
            }
            $dteJson = $this->generarDteJson($dte, $comprobante);
            if (empty($dteJson)) {
                $error = 'Error generando JSON del DTE';
                $this->registrarError($dte, 'datos', 'JSON_ERROR', $error);
                return array_merge($resultado, ['error' => $error, 'tipo_error' => 'datos']);
            }
            $dteFirmado = $this->firmarDte($dte, $dteJson);
            if (!$dteFirmado['exitoso']) {
                $this->registrarError($dte, 'firma', 'SIGN_ERROR', $dteFirmado['error']);
                return array_merge($resultado, ['error' => $dteFirmado['error'], 'tipo_error' => 'firma']);
            }
            $respuestaHacienda = $this->enviarAHacienda($dte, $dteFirmado['dte']);
            if ($respuestaHacienda['exitoso']) {
                $dte->marcarComoEnviado($respuestaHacienda['datos']);
                $resultado['exitoso'] = true;
            } else {
                $dte->marcarComoRechazado($respuestaHacienda['datos']);
                $this->registrarError($dte, 'hacienda', 'HACIENDA_ERROR', $respuestaHacienda['error']);
                $resultado = array_merge($resultado, [
                    'error' => $respuestaHacienda['error'],
                    'tipo_error' => 'hacienda',
                    'necesita_contingencia' => $dte->necesitaContingencia()
                ]);
            }
        } catch (Exception $e) {
            $this->registrarError($dte, 'sistema', 'SYSTEM_ERROR', $e->getMessage());
            $resultado = array_merge($resultado, ['error' => $e->getMessage(), 'tipo_error' => 'sistema']);
        }
        return $resultado;
    }

    private function validarDte(Dte $dte): array
    {
        $errores = [];
        if (!$dte->company_id) $errores[] = 'Empresa no especificada';
        if (!$dte->tipoDte) $errores[] = 'Tipo de documento no especificado';
        if ($dte->idContingencia) {
            $contingencia = Contingencia::find($dte->idContingencia);
            if ($contingencia && method_exists($contingencia, 'isVigente') && $contingencia->isVigente()) {
                $errores[] = 'Documento en contingencia vigente';
            }
        }
        return ['valido' => empty($errores), 'error' => implode('; ', $errores)];
    }

    private function obtenerComprobante(Dte $dte): array
    {
        $sale = Sale::with(['client', 'company', 'details.product'])->find($dte->sale_id);
        if (!$sale) return [];
        return [
            'encabezado' => [
                'empresa' => $sale->company,
                'cliente' => $sale->client,
                'documento' => $sale
            ],
            'detalle' => $sale->details->map(function($detail){
                return [
                    'producto' => $detail->product,
                    'cantidad' => $detail->amountp,
                    'precio' => $detail->pricesale,
                    'total' => $detail->pricesale * $detail->amountp
                ];
            })->toArray()
        ];
    }

    private function generarDteJson(Dte $dte, array $comprobante): array
    {
        return [
            'identificacion' => [
                'version' => $dte->versionJson,
                'ambiente' => $dte->ambiente_id,
                'tipoDte' => $dte->tipoDte,
                'numeroControl' => $dte->id_doc,
                'codigoGeneracion' => $dte->codigoGeneracion
            ],
            'emisor' => $comprobante['encabezado']['empresa'],
            'receptor' => $comprobante['encabezado']['cliente'],
            'documento' => $comprobante['encabezado']['documento'],
            'detalle' => $comprobante['detalle']
        ];
    }

    private function firmarDte(Dte $dte, array $dteJson): array
    {
        try {
            $empresa = Company::find($dte->company_id);
            // Obtener URL del firmador desde ambientes asociados al config de la empresa (como RomaCopies)
            $ambiente = DB::table('config as c')
                ->leftJoin('ambientes as a', 'c.ambiente', '=', 'a.id')
                ->where('c.company_id', $empresa->id)
                ->select('a.url_firmador')
                ->first();

            $datosFirma = [
                'nit' => $empresa->nit ?? '',
                'activo' => true,
                'passwordPri' => $empresa->passwordPri ?? '',
                'dteJson' => $dteJson
            ];
            $firmadorUrl = $ambiente->url_firmador ?? null;
            if (!$firmadorUrl) {
                return ['exitoso' => false, 'error' => 'URL de firmador no configurada'];
            }
            $response = Http::timeout(30)->post($firmadorUrl, $datosFirma);
            if ($response->successful()) {
                return ['exitoso' => true, 'dte' => $response->json()];
            }
            return ['exitoso' => false, 'error' => 'Error en firma: ' . $response->body()];
        } catch (Exception $e) {
            return ['exitoso' => false, 'error' => 'Error de conexión con firmador: ' . $e->getMessage()];
        }
    }

    private function enviarAHacienda(Dte $dte, array $dteFirmado): array
    {
        try {
            // Obtener URL de envío desde ambientes asociados al config de la empresa (como RomaCopies)
            $ambiente = DB::table('config as c')
                ->leftJoin('ambientes as a', 'c.ambiente', '=', 'a.id')
                ->where('c.company_id', $dte->company_id)
                ->select('a.url_envio')
                ->first();

            $datosEnvio = [
                'ambiente' => $dte->ambiente_id,
                'dteJson' => $dteFirmado
            ];
            $haciendaUrl = $ambiente->url_envio ?? null;
            if (!$haciendaUrl) {
                return ['exitoso' => false, 'datos' => [], 'error' => 'URL de Hacienda no configurada'];
            }
            $response = Http::timeout(60)->post($haciendaUrl, $datosEnvio);
            if ($response->successful()) {
                $respuesta = $response->json();
                if (($respuesta['codEstado'] ?? null) === '02') {
                    return ['exitoso' => true, 'datos' => $respuesta];
                }
                return ['exitoso' => false, 'datos' => $respuesta, 'error' => $respuesta['descripcionMsg'] ?? 'Error desconocido'];
            }
            return ['exitoso' => false, 'datos' => [], 'error' => 'Error de conexión con Hacienda: ' . $response->status()];
        } catch (Exception $e) {
            return ['exitoso' => false, 'datos' => [], 'error' => 'Error de red: ' . $e->getMessage()];
        }
    }

    private function registrarError(Dte $dte, string $tipo, string $codigo, string $descripcion): void
    {
        DteError::crearError($dte->id, $tipo, $codigo, $descripcion, ['dte_id' => $dte->id, 'sale_id' => $dte->sale_id]);
    }

    public function crearContingenciaAutomatica(Dte $dte, string $motivo): ?Contingencia
    {
        if (!class_exists(Contingencia::class)) return null;
        $contingencia = Contingencia::create([
            'empresa_id' => $dte->company_id,
            'nombre' => 'Contingencia Automática - ' . now()->format('Y-m-d H:i'),
            'descripcion' => 'Contingencia creada automáticamente por errores en DTE',
            'motivo' => $motivo,
            'fecha_inicio' => now(),
            'fecha_fin' => now()->addDays(2),
            'estado' => method_exists(Contingencia::class, 'ESTADO_PENDIENTE') ? Contingencia::ESTADO_PENDIENTE : 0,
            'tipo_contingencia' => method_exists(Contingencia::class, 'TIPO_TECNICA') ? Contingencia::TIPO_TECNICA : 0,
            'documentos_afectados' => 1,
            'created_by' => 1
        ]);
        $dte->update(['idContingencia' => $contingencia->id]);
        return $contingencia;
    }

    public function reintentarDte(Dte $dte): array
    {
        $errores = $dte->errors()->noResueltos()->get();
        if ($errores->isEmpty()) return ['exitoso' => false, 'error' => 'No hay errores para reintentar'];
        if (!$dte->puedeReintentar()) return ['exitoso' => false, 'error' => 'DTE no puede ser reintentado'];
        return $this->procesarDte($dte);
    }

    public function procesarReintentosAutomaticos(): array
    {
        $resultados = ['procesados' => 0, 'exitosos' => 0, 'errores' => 0];
        $dtesParaReintento = Dte::paraReintento()->get();
        foreach ($dtesParaReintento as $dte) {
            $resultado = $this->reintentarDte($dte);
            $resultados['procesados']++;
            $resultado['exitoso'] ? $resultados['exitosos']++ : $resultados['errores']++;
        }
        return $resultados;
    }

    public function obtenerEstadisticas(int $empresaId = null): array
    {
        $query = Dte::query();
        if ($empresaId) $query->where('company_id', $empresaId);
        $total = $query->count();
        $enCola = (clone $query)->enCola()->count();
        $enviados = (clone $query)->enviados()->count();
        $rechazados = (clone $query)->rechazados()->count();
        $enRevision = (clone $query)->enRevision()->count();
        return [
            'total' => $total,
            'en_cola' => $enCola,
            'enviados' => $enviados,
            'rechazados' => $rechazados,
            'en_revision' => $enRevision,
            'porcentaje_exito' => $total > 0 ? round(($enviados / $total) * 100, 2) : 0,
            'pendientes_reintento' => Dte::paraReintento()->count(),
            'necesitan_contingencia' => Dte::necesitanContingencia()->count()
        ];
    }

    /**
     * Enviar correo automático después de DTE exitoso
     */
    private function enviarCorreoAutomatico(Dte $dte): void
    {
        try {
            // Obtener datos de la venta
            $sale = Sale::with(['client', 'company'])
                ->find($dte->sale_id);

            if (!$sale || !$sale->client->email) {
                Log::warning('No se puede enviar correo: venta no encontrada o cliente sin email', [
                    'dte_id' => $dte->id,
                    'sale_id' => $dte->sale_id
                ]);
                return;
            }

            // Obtener JSON del DTE
            $jsonDte = json_decode($dte->jsonDte ?? '{}', true);
            if (empty($jsonDte)) {
                Log::warning('No se puede enviar correo: JSON del DTE vacío', [
                    'dte_id' => $dte->id
                ]);
                return;
            }

            // Preparar datos para el correo
            $data = [
                'nombre' => $sale->client->name,
                'json' => (object) $jsonDte
            ];

            // Crear y enviar correo
            $asunto = "Comprobante de Venta No." . $jsonDte['identificacion']['numeroControl'] .
                     ' de Proveedor: ' . $jsonDte['emisor']['nombre'];

            $correo = new EnviarCorreo($data);
            $correo->subject($asunto);

            Mail::to($sale->client->email)->send($correo);

            Log::info('Correo enviado exitosamente', [
                'dte_id' => $dte->id,
                'email' => $sale->client->email,
                'numero_control' => $jsonDte['identificacion']['numeroControl']
            ]);

        } catch (Exception $e) {
            Log::error('Error enviando correo automático', [
                'dte_id' => $dte->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Obtener estadísticas de errores
     */
    public function obtenerEstadisticasErrores(int $empresaId = null): array
    {
        $query = DteError::query();

        if ($empresaId) {
            $query->whereHas('dte', function($q) use ($empresaId) {
                $q->where('company_id', $empresaId);
            });
        }

        $total = $query->count();
        $noResueltos = $query->clone()->noResueltos()->count();
        $criticos = $query->clone()->criticos()->count();
        $porTipo = $query->clone()->selectRaw('tipo_error, count(*) as total')
            ->groupBy('tipo_error')
            ->pluck('total', 'tipo_error');

        return [
            'total_errores' => $total,
            'no_resueltos' => $noResueltos,
            'criticos' => $criticos,
            'por_tipo' => $porTipo,
            'porcentaje_resueltos' => $total > 0 ? round((($total - $noResueltos) / $total) * 100, 2) : 0
        ];
    }
}


