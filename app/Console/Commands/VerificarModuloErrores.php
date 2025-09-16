<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;

class VerificarModuloErrores extends Command
{
    protected $signature = 'dte:verificar-errores-modulo';
    protected $description = 'Verifica que el módulo de errores DTE esté funcionando correctamente';

    public function handle()
    {
        $this->info('🔍 Verificando módulo de errores DTE...');

        // Verificar rutas
        $this->info('📋 Verificando rutas...');
        $rutasEsperadas = [
            'dte.error-show' => 'dte-admin/error-show/{id}',
            'dte.show' => 'dte-admin/dte-show/{id}',
            'dte.errores' => 'dte-admin/errores',
            'dte.dashboard' => 'dte-admin/dashboard'
        ];

        foreach ($rutasEsperadas as $nombre => $uri) {
            try {
                $ruta = Route::getRoutes()->getByName($nombre);
                if ($ruta) {
                    $this->line("✅ {$nombre}: {$ruta->uri()}");
                } else {
                    $this->error("❌ {$nombre}: No encontrada");
                }
            } catch (\Exception $e) {
                $this->error("❌ {$nombre}: Error - {$e->getMessage()}");
            }
        }

        // Verificar generación de rutas
        $this->info('🔗 Verificando generación de rutas...');
        try {
            $url = route('dte.error-show', ['id' => 1]);
            $this->line("✅ dte.error-show: {$url}");
        } catch (\Exception $e) {
            $this->error("❌ dte.error-show: {$e->getMessage()}");
        }

        try {
            $url = route('dte.show', ['id' => 1]);
            $this->line("✅ dte.show: {$url}");
        } catch (\Exception $e) {
            $this->error("❌ dte.show: {$e->getMessage()}");
        }

        // Verificar vistas
        $this->info('👁️ Verificando vistas...');
        $vistas = [
            'dte.errores' => 'resources/views/dte/errores.blade.php',
            'dte.error-show' => 'resources/views/dte/error-show.blade.php'
        ];

        foreach ($vistas as $nombre => $archivo) {
            if (file_exists(base_path($archivo))) {
                $this->line("✅ {$nombre}: {$archivo}");
            } else {
                $this->error("❌ {$nombre}: {$archivo} no encontrado");
            }
        }

        // Verificar controlador
        $this->info('🎮 Verificando controlador...');
        if (class_exists('App\Http\Controllers\DteAdminController')) {
            $this->line("✅ DteAdminController: Existe");

            $metodos = ['errores', 'showError', 'showDte', 'resolverError'];
            foreach ($metodos as $metodo) {
                if (method_exists('App\Http\Controllers\DteAdminController', $metodo)) {
                    $this->line("✅ DteAdminController@{$metodo}: Existe");
                } else {
                    $this->error("❌ DteAdminController@{$metodo}: No encontrado");
                }
            }
        } else {
            $this->error("❌ DteAdminController: No encontrado");
        }

        $this->info('🎉 Verificación completada!');

        return 0;
    }
}
