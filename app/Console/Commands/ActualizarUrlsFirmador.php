<?php

namespace App\Console\Commands;

use App\Models\Ambiente;
use Illuminate\Console\Command;

class ActualizarUrlsFirmador extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firmador:actualizar-urls {--url-produccion= : URL del firmador para producción} {--url-test= : URL del firmador para test}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualizar URLs del firmador en la base de datos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔧 Actualizando URLs del firmador...');

        $urlProduccion = $this->option('url-produccion') ?: 'http://147.93.176.3:8113/firmardocumento/';
        $urlTest = $this->option('url-test') ?: 'http://localhost:8113/firmardocumento/';

        try {
            // Actualizar ambiente de producción (código 01)
            $ambienteProduccion = Ambiente::where('cod', '01')->first();
            if ($ambienteProduccion) {
                $ambienteProduccion->url_firmador = $urlProduccion;
                $ambienteProduccion->save();
                $this->info("✅ Ambiente producción (01) actualizado: {$urlProduccion}");
            } else {
                $this->warn("⚠️  No se encontró el ambiente de producción (código 01)");
            }

            // Actualizar ambiente de test (código 00)
            $ambienteTest = Ambiente::where('cod', '00')->first();
            if ($ambienteTest) {
                $ambienteTest->url_firmador = $urlTest;
                $ambienteTest->save();
                $this->info("✅ Ambiente test (00) actualizado: {$urlTest}");
            } else {
                $this->warn("⚠️  No se encontró el ambiente de test (código 00)");
            }

            // Mostrar resumen
            $this->newLine();
            $this->info('📊 Resumen de URLs actualizadas:');

            $ambientes = Ambiente::all();
            foreach ($ambientes as $ambiente) {
                $this->line("   • {$ambiente->cod} ({$ambiente->description}): {$ambiente->url_firmador}");
            }

            $this->newLine();
            $this->info('🎯 Para probar las nuevas URLs:');
            $this->line('   1. Ir a Administracion DTE → Prueba de Conectividad Firmador');
            $this->line('   2. Ejecutar las pruebas de conectividad');
            $this->line('   3. Verificar que las URLs se muestran correctamente');

        } catch (\Exception $e) {
            $this->error('❌ Error al actualizar URLs: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
