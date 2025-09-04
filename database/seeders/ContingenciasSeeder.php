<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ContingenciasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Obtener la primera empresa disponible
        $empresa = DB::table('companies')->first();

        if (!$empresa) {
            $this->command->info('No hay empresas disponibles para crear contingencias');
            return;
        }

        $contingencias = [
            [
                'idEmpresa' => $empresa->id,
                'company_id' => $empresa->id,
                'idTienda' => 1,
                'codInterno' => 'CONT-001',
                'nombre' => 'Contingencia SII No Disponible',
                'versionJson' => '1.0',
                'ambiente' => '00',
                'codEstado' => '02',
                'activa' => true,
                'estado' => 'Activa',
                'codigoGeneracion' => 'CONT-' . time(),
                'fechaCreacion' => now()->format('Y-m-d'),
                'horaCreacion' => now()->format('H:i:s'),
                'fInicio' => now()->format('Y-m-d'),
                'fecha_inicio' => now()->format('Y-m-d'),
                'fFin' => now()->addDays(7)->format('Y-m-d'),
                'fecha_fin' => now()->addDays(7)->format('Y-m-d'),
                'hInicio' => '00:00:00',
                'hFin' => '23:59:59',
                'tipoContingencia' => '01',
                'motivoContingencia' => 'SII no disponible por mantenimiento programado',
                'nombreResponsable' => 'Administrador del Sistema',
                'tipoDocResponsable' => '13',
                'nuDocResponsable' => '12345678-9',
                'documentos_afectados' => 15,
                'created_at' => now(),
                'updated_at' => now(),
                'created_by' => 'seeder',
                'updated_by' => 'seeder'
            ],
            [
                'idEmpresa' => $empresa->id,
                'company_id' => $empresa->id,
                'idTienda' => 1,
                'codInterno' => 'CONT-002',
                'nombre' => 'Contingencia Certificado Expirado',
                'versionJson' => '1.0',
                'ambiente' => '00',
                'codEstado' => '01',
                'activa' => true,
                'estado' => 'En Proceso',
                'codigoGeneracion' => 'CONT-' . (time() + 1),
                'fechaCreacion' => now()->format('Y-m-d'),
                'horaCreacion' => now()->format('H:i:s'),
                'fInicio' => now()->format('Y-m-d'),
                'fecha_inicio' => now()->format('Y-m-d'),
                'fFin' => now()->addDays(3)->format('Y-m-d'),
                'fecha_fin' => now()->addDays(3)->format('Y-m-d'),
                'hInicio' => '00:00:00',
                'hFin' => '23:59:59',
                'tipoContingencia' => '02',
                'motivoContingencia' => 'Certificado digital expirado, renovación en proceso',
                'nombreResponsable' => 'Administrador del Sistema',
                'tipoDocResponsable' => '13',
                'nuDocResponsable' => '12345678-9',
                'documentos_afectados' => 8,
                'created_at' => now(),
                'updated_at' => now(),
                'created_by' => 'seeder',
                'updated_by' => 'seeder'
            ],
            [
                'idEmpresa' => $empresa->id,
                'company_id' => $empresa->id,
                'idTienda' => 1,
                'codInterno' => 'CONT-003',
                'nombre' => 'Contingencia Error de Conectividad',
                'versionJson' => '1.0',
                'ambiente' => '00',
                'codEstado' => '02',
                'activa' => true,
                'estado' => 'Activa',
                'codigoGeneracion' => 'CONT-' . (time() + 2),
                'fechaCreacion' => now()->format('Y-m-d'),
                'horaCreacion' => now()->format('H:i:s'),
                'fInicio' => now()->subDays(1)->format('Y-m-d'),
                'fecha_inicio' => now()->subDays(1)->format('Y-m-d'),
                'fFin' => now()->addDays(2)->format('Y-m-d'),
                'fecha_fin' => now()->addDays(2)->format('Y-m-d'),
                'hInicio' => '00:00:00',
                'hFin' => '23:59:59',
                'tipoContingencia' => '03',
                'motivoContingencia' => 'Problemas de conectividad con el SII',
                'nombreResponsable' => 'Administrador del Sistema',
                'tipoDocResponsable' => '13',
                'nuDocResponsable' => '12345678-9',
                'documentos_afectados' => 23,
                'created_at' => now(),
                'updated_at' => now(),
                'created_by' => 'seeder',
                'updated_by' => 'seeder'
            ]
        ];

        foreach ($contingencias as $contingencia) {
            DB::table('contingencias')->insert($contingencia);
        }

        $this->command->info('Contingencias de ejemplo creadas exitosamente');
    }
}
