<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ManualPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Crear permisos para el módulo de manuales
        $permissions = [
            'manuals.index',
            'manuals.create',
            'manuals.show',
            'manuals.edit',
            'manuals.update',
            'manuals.destroy'
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web'
            ]);
        }

        // Asignar permisos al rol de administrador
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo($permissions);
            $this->command->info('Permisos de manuales asignados al rol admin');
        } else {
            $this->command->warn('Rol admin no encontrado');
        }

        // Asignar permisos al rol de usuario (solo lectura)
        $userRole = Role::where('name', 'user')->first();
        if ($userRole) {
            $userRole->givePermissionTo(['manuals.index', 'manuals.show']);
            $this->command->info('Permisos de lectura de manuales asignados al rol user');
        }

        $this->command->info('Permisos de manuales creados exitosamente');
    }
}
