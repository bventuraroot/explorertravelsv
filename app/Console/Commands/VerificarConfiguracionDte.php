<?php

namespace App\Console\Commands;

use App\Models\Config;
use App\Models\Company;
use Illuminate\Console\Command;

class VerificarConfiguracionDte extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dte:verificar-configuracion {--empresa= : ID de empresa específica a verificar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verificar configuración de emisión DTE por empresa';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🔧 Verificando configuración de emisión DTE...');
        $this->newLine();

        $empresaId = $this->option('empresa');

        if ($empresaId) {
            $this->verificarEmpresaEspecifica($empresaId);
        } else {
            $this->verificarTodasLasEmpresas();
        }

        return 0;
    }

    /**
     * Verificar una empresa específica
     */
    private function verificarEmpresaEspecifica($empresaId)
    {
        $empresa = Company::find($empresaId);

        if (!$empresa) {
            $this->error("❌ Empresa con ID {$empresaId} no encontrada");
            return;
        }

        $config = Config::where('company_id', $empresaId)->first();

        $this->info("📊 Configuración para: {$empresa->name}");
        $this->line('─' . str_repeat('─', 50));

        if ($config) {
            $this->line("✅ Configuración encontrada");
            $this->line("📋 Detalles:");
            $this->table(
                ['Campo', 'Valor'],
                [
                    ['ID', $config->id],
                    ['Empresa', $empresa->name],
                    ['Versión', $config->version],
                    ['Ambiente', $config->ambiente == 1 ? 'Desarrollo' : 'Producción'],
                    ['Versión JSON', $config->versionJson],
                    ['Emisión DTE', $config->dte_emission_enabled ? '✅ Habilitado' : '❌ Deshabilitado'],
                    ['Notas', $config->dte_emission_notes ?: 'Sin notas'],
                    ['Última actualización', $config->updated_at->format('d/m/Y H:i:s')]
                ]
            );
        } else {
            $this->line("⚠️  Sin configuración específica (usará valor por defecto)");
            $this->line("📋 Estado por defecto:");
            $this->table(
                ['Campo', 'Valor'],
                [
                    ['Empresa', $empresa->name],
                    ['Emisión DTE', '✅ Habilitado (por defecto)'],
                    ['Notas', 'Sin configuración específica']
                ]
            );
        }
    }

    /**
     * Verificar todas las empresas
     */
    private function verificarTodasLasEmpresas()
    {
        $empresas = Company::all();
        $configs = Config::all();

        $this->info("📊 Configuración de emisión DTE por empresa:");
        $this->newLine();

        $headers = ['ID', 'Empresa', 'Configuración', 'Estado DTE', 'Notas'];
        $rows = [];

        $habilitadas = 0;
        $deshabilitadas = 0;

        foreach ($empresas as $empresa) {
            $config = $configs->where('company_id', $empresa->id)->first();

            if ($config) {
                $estado = $config->dte_emission_enabled ? '✅ Habilitado' : '❌ Deshabilitado';
                $configuracion = 'Configurada';
                $notas = $config->dte_emission_notes ?: 'Sin notas';

                if ($config->dte_emission_enabled) {
                    $habilitadas++;
                } else {
                    $deshabilitadas++;
                }
            } else {
                $estado = '✅ Habilitado';
                $configuracion = 'Por defecto';
                $notas = 'Sin configuración';
                $habilitadas++;
            }

            $rows[] = [
                $empresa->id,
                $empresa->name,
                $configuracion,
                $estado,
                $notas
            ];
        }

        $this->table($headers, $rows);
        $this->newLine();

        $this->info("📈 Resumen:");
        $this->line("   • Empresas con DTE habilitado: {$habilitadas}");
        $this->line("   • Empresas con DTE deshabilitado: {$deshabilitadas}");
        $this->line("   • Total de empresas: " . $empresas->count());
    }
}
