<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ActualizarUrlsFirmador extends Command
{
    protected $signature = 'firmador:actualizar-urls {--url-produccion=} {--url-test=}';
    protected $description = 'Actualizar URLs del firmador para ambientes producción y test';

    public function handle(): int
    {
        $this->info('Actualizando URLs del firmador...');
        $urlProduccion = $this->option('url-produccion');
        $urlTest = $this->option('url-test');

        if (!$urlProduccion && !$urlTest) {
            $this->error('Debe proporcionar al menos una opción: --url-produccion o --url-test');
            return self::FAILURE;
        }

        $ambientes = DB::table('ambientes')->get();
        foreach ($ambientes as $ambiente) {
            $nuevaUrl = null;
            if ($ambiente->cod === '01' && $urlProduccion) {
                $nuevaUrl = $urlProduccion;
            } elseif ($ambiente->cod !== '01' && $urlTest) {
                $nuevaUrl = $urlTest;
            }
            if ($nuevaUrl) {
                DB::table('ambientes')->where('id', $ambiente->id)->update(['url_firmador' => $nuevaUrl]);
                $this->line(" - Ambiente {$ambiente->cod}: {$nuevaUrl}");
            }
        }

        $this->info('Completado.');
        return self::SUCCESS;
    }
}


