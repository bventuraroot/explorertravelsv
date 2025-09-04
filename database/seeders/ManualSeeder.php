<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Manual;

class ManualSeeder extends Seeder
{
    public function run()
    {
        $manuals = [
            [
                'titulo' => 'Guía de Administración de Usuarios',
                'modulo' => 'Administracion',
                'descripcion' => 'Aprende a gestionar usuarios, roles y permisos en el sistema',
                'contenido' => '<h1>Administración de Usuarios</h1><p>El módulo de administración te permite gestionar usuarios, roles y permisos del sistema de manera eficiente.</p>',
                'version' => '1.0',
                'activo' => true,
                'orden' => 1,
                'icono' => 'users'
            ],
            [
                'titulo' => 'Gestión de Clientes',
                'modulo' => 'Clientes',
                'descripcion' => 'Cómo crear, editar y gestionar la información de clientes',
                'contenido' => '<h1>Gestión de Clientes</h1><p>Para agregar un nuevo cliente al sistema, ve al módulo de Clientes y haz clic en Nuevo Cliente.</p>',
                'version' => '1.0',
                'activo' => true,
                'orden' => 1,
                'icono' => 'user-plus'
            ],
            [
                'titulo' => 'Proceso de Ventas',
                'modulo' => 'Ventas',
                'descripcion' => 'Guía completa para realizar ventas y generar facturas',
                'contenido' => '<h1>Proceso de Ventas</h1><p>Para crear una nueva venta, accede al módulo de Ventas y sigue el proceso paso a paso.</p>',
                'version' => '1.0',
                'activo' => true,
                'orden' => 1,
                'icono' => 'dollar'
            ]
        ];

        foreach ($manuals as $manual) {
            Manual::create($manual);
        }
    }
}
