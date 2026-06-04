<?php

namespace App\Console\Commands;

use App\Services\EmailPurchaseService;
use Illuminate\Console\Command;

class ImportEmailPurchases extends Command
{
    protected $signature = 'email:import-purchases {--limit=10 : Límite de correos a leer}';
    protected $description = 'Revisa la cuenta de correo IMAP configurada, busca adjuntos JSON de Hacienda e importa DTEs de compras';

    public function handle(EmailPurchaseService $service)
    {
        $limit = (int) $this->option('limit');
        $this->info("Iniciando revisión de correos DTE (Límite: {$limit})...");

        try {
            $summary = $service->run($limit, 1); // User ID 1 por defecto

            $this->info("✓ Procesamiento completado.");
            $this->line("- Leídos/Procesados: " . ($summary['processed'] ?? 0));
            $this->line("- Fallidos con error: " . ($summary['errors'] ?? 0));
            $this->line("- Omitidos/Saltados:  " . ($summary['skipped'] ?? 0));

        } catch (\Exception $e) {
            $this->error("Ocurrió un error general: " . $e->getMessage());
        }
    }
}
