<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class BackupPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Crear permisos para el módulo de backups
        $permissions = [
            'backups.index',
            'backups.create',
            'backups.download',
            'backups.delete',
            'backups.clean',
            'backups.refresh'
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web'
            ]);
        }

        // Asignar permisos al rol de administrador
        $adminRole = Role::where('name', 'Admin')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo($permissions);
            $this->command->info('Permisos de backups asignados al rol Admin');
        } else {
            $this->command->warn('Rol Admin no encontrado');
        }

        // Asignar permisos básicos al rol de Contabilidad (solo lectura y descarga)
        $contabilidadRole = Role::where('name', 'Contabilidad')->first();
        if ($contabilidadRole) {
            $contabilidadRole->givePermissionTo(['backups.index', 'backups.download']);
            $this->command->info('Permisos de lectura de backups asignados al rol Contabilidad');
        }

        $this->command->info('Permisos de backups creados exitosamente');
    }
}
