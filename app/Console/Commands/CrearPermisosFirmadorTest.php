<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CrearPermisosFirmadorTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firmador:crear-permisos {--rol=admin : Rol al que asignar los permisos}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crear permisos para el módulo de prueba del firmador';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔧 Creando permisos para el módulo de prueba del firmador...');

        $permisos = [
            'firmador.test' => 'Acceder a prueba de conectividad del firmador',
            'firmador.test-connection' => 'Ejecutar prueba de conexión básica',
            'firmador.test-firma' => 'Ejecutar prueba de firma',
            'firmador.test-network' => 'Ejecutar diagnóstico de red',
            'firmador.server-info' => 'Ver información del servidor',
            'firmador.ambientes' => 'Ver información de ambientes disponibles'
        ];

        $permisosCreados = [];
        $permisosExistentes = [];

        foreach ($permisos as $permiso => $descripcion) {
            try {
                $permisoModel = Permission::firstOrCreate([
                    'name' => $permiso
                ], [
                    'guard_name' => 'web'
                ]);

                if ($permisoModel->wasRecentlyCreated) {
                    $permisosCreados[] = $permiso;
                    $this->info("✅ Permiso creado: {$permiso}");
                } else {
                    $permisosExistentes[] = $permiso;
                    $this->line("ℹ️  Permiso ya existe: {$permiso}");
                }

            } catch (\Exception $e) {
                $this->error("❌ Error al crear permiso {$permiso}: " . $e->getMessage());
            }
        }

        // Asignar permisos al rol especificado
        $rolNombre = $this->option('rol');
        $rol = Role::where('name', $rolNombre)->first();

        if ($rol) {
            $this->info("🔗 Asignando permisos al rol: {$rolNombre}");

            foreach ($permisos as $permiso => $descripcion) {
                if (!$rol->hasPermissionTo($permiso)) {
                    $rol->givePermissionTo($permiso);
                    $this->info("✅ Permiso {$permiso} asignado al rol {$rolNombre}");
                } else {
                    $this->line("ℹ️  El rol {$rolNombre} ya tiene el permiso {$permiso}");
                }
            }
        } else {
            $this->warn("⚠️  El rol '{$rolNombre}' no existe. Los permisos no fueron asignados.");
        }

        // Resumen
        $this->newLine();
        $this->info('📊 Resumen:');
        $this->line("   • Permisos creados: " . count($permisosCreados));
        $this->line("   • Permisos existentes: " . count($permisosExistentes));
        $this->line("   • Total de permisos: " . count($permisos));

        if (!empty($permisosCreados)) {
            $this->info('✅ Permisos creados exitosamente:');
            foreach ($permisosCreados as $permiso) {
                $this->line("   • {$permiso}");
            }
        }

        $this->newLine();
        $this->info('🎯 Para usar el módulo:');
        $this->line('   1. Ir a Administracion DTE → Prueba de Conectividad Firmador');
        $this->line('   2. Ejecutar las pruebas de conectividad');
        $this->line('   3. Revisar los resultados para diagnosticar problemas');

        return 0;
    }
}
