<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CrearPermisosFirmadorTest extends Command
{
    protected $signature = 'firmador:crear-permisos {--rol=admin}';
    protected $description = 'Crear permisos para el módulo de prueba del firmador y asignarlos a un rol';

    public function handle(): int
    {
        $rolNombre = $this->option('rol');
        $this->info('Creando permisos del módulo firmador...');

        $permisos = [
            'firmador.test' => 'Acceder a prueba de conectividad del firmador',
            'firmador.test-connection' => 'Ejecutar prueba de conexión básica',
            'firmador.test-firma' => 'Ejecutar prueba de firma',
            'firmador.server-info' => 'Ver información del servidor',
        ];

        foreach ($permisos as $name => $desc) {
            Permission::firstOrCreate(['name' => $name], ['guard_name' => 'web']);
        }

        $role = Role::where('name', $rolNombre)->first();
        if ($role) {
            $role->givePermissionTo(array_keys($permisos));
            $this->info("Permisos asignados al rol {$rolNombre}");
        } else {
            $this->warn("Rol {$rolNombre} no encontrado. Asigne manualmente los permisos si es necesario.");
        }

        return self::SUCCESS;
    }
}


