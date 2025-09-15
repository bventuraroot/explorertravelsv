<?php

namespace App\Console\Commands;

use App\Services\DteService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcesarColaDte extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dte:procesar-cola {--limite=10 : Número máximo de DTE a procesar} {--verbose : Mostrar información detallada}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Procesa la cola de DTE con manejo de errores y envío de correos automático';

    protected $dteService;

    /**
     * Create a new command instance.
     */
    public function __construct(DteService $dteService)
    {
        parent::__construct();
        $this->dteService = $dteService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $limite = $this->option('limite');
        $verbose = $this->option('verbose');

        $this->info("🚀 Iniciando procesamiento de cola DTE...");
        $this->info("📊 Límite: {$limite} documentos");

        try {
            $resultados = $this->dteService->procesarCola($limite);

            // Mostrar resultados
            $this->mostrarResultados($resultados, $verbose);

            // Mostrar estadísticas adicionales
            if ($verbose) {
                $this->mostrarEstadisticas();
            }

            $this->info("✅ Procesamiento completado exitosamente");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Error durante el procesamiento: " . $e->getMessage());
            Log::error('Error en comando ProcesarColaDte', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Mostrar resultados del procesamiento
     */
    private function mostrarResultados(array $resultados, bool $verbose): void
    {
        $this->newLine();
        $this->info("📈 RESULTADOS DEL PROCESAMIENTO:");
        $this->newLine();

        $this->line("📋 Documentos procesados: <fg=cyan>{$resultados['procesados']}</>");
        $this->line("✅ Exitosos: <fg=green>{$resultados['exitosos']}</>");
        $this->line("❌ Con errores: <fg=red>{$resultados['errores']}</>");
        $this->line("📧 Correos enviados: <fg=blue>{$resultados['correos_enviados']}</>");
        $this->line("🚨 Contingencias creadas: <fg=yellow>{$resultados['contingencias_creadas']}</>");

        // Mostrar porcentaje de éxito
        if ($resultados['procesados'] > 0) {
            $porcentajeExito = round(($resultados['exitosos'] / $resultados['procesados']) * 100, 2);
            $color = $porcentajeExito >= 80 ? 'green' : ($porcentajeExito >= 60 ? 'yellow' : 'red');
            $this->line("📊 Porcentaje de éxito: <fg={$color}>{$porcentajeExito}%</>");
        }

        // Mostrar detalles si es verbose
        if ($verbose && !empty($resultados['detalles'])) {
            $this->newLine();
            $this->info("🔍 DETALLES POR DOCUMENTO:");
            $this->newLine();

            $headers = ['ID', 'Estado', 'Tipo Error', 'Observaciones'];
            $rows = [];

            foreach ($resultados['detalles'] as $detalle) {
                $estado = $detalle['exitoso'] ? '✅ Exitoso' : '❌ Error';
                $tipoError = $detalle['tipo_error'] ?? 'N/A';
                $observaciones = $detalle['error'] ?? 'Sin observaciones';

                $rows[] = [
                    $detalle['dte_id'],
                    $estado,
                    $tipoError,
                    $observaciones
                ];
            }

            $this->table($headers, $rows);
        }
    }

    /**
     * Mostrar estadísticas del sistema
     */
    private function mostrarEstadisticas(): void
    {
        $this->newLine();
        $this->info("📊 ESTADÍSTICAS DEL SISTEMA:");
        $this->newLine();

        try {
            $estadisticas = $this->dteService->obtenerEstadisticas();
            $estadisticasErrores = $this->dteService->obtenerEstadisticasErrores();

            $this->line("📋 Total DTE: <fg=cyan>{$estadisticas['total']}</>");
            $this->line("⏳ En cola: <fg=yellow>{$estadisticas['en_cola']}</>");
            $this->line("✅ Enviados: <fg=green>{$estadisticas['enviados']}</>");
            $this->line("❌ Rechazados: <fg=red>{$estadisticas['rechazados']}</>");
            $this->line("🔍 En revisión: <fg=blue>{$estadisticas['en_revision']}</>");
            $this->line("📊 % Éxito general: <fg=cyan>{$estadisticas['porcentaje_exito']}%</>");

            $this->newLine();
            $this->line("🚨 Total errores: <fg=red>{$estadisticasErrores['total_errores']}</>");
            $this->line("⚠️  No resueltos: <fg=yellow>{$estadisticasErrores['no_resueltos']}</>");
            $this->line("🔥 Críticos: <fg=red>{$estadisticasErrores['criticos']}</>");

        } catch (\Exception $e) {
            $this->warn("⚠️  No se pudieron obtener las estadísticas: " . $e->getMessage());
        }
    }
}
