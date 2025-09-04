<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CreateManual extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'manual:create {title} {module} {--description=} {--version=1.0}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crear un nuevo manual en el sistema';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $title = $this->argument('title');
        $module = $this->argument('module');
        $description = $this->option('description') ?: "Manual de {$title}";
        $version = $this->option('version');

        // Crear nombre de archivo desde el título
        $filename = $this->generateFilename($title);

        // Crear directorio del módulo si no existe
        $modulePath = resource_path("manuals/{$module}");
        if (!File::exists($modulePath)) {
            File::makeDirectory($modulePath, 0755, true);
            $this->info("📁 Directorio del módulo '{$module}' creado");
        }

        // Crear contenido del manual
        $content = $this->generateManualContent($title, $module, $description, $version);

        // Guardar archivo
        $filePath = "{$modulePath}/{$filename}.md";
        File::put($filePath, $content);

        $this->info("✅ Manual creado exitosamente:");
        $this->line("   📄 Archivo: {$filename}.md");
        $this->line("   📂 Módulo: {$module}");
        $this->line("   📍 Ruta: {$filePath}");

        $this->info("🎉 El manual será detectado automáticamente por el sistema!");

        return 0;
    }

    /**
     * Generar nombre de archivo desde el título
     */
    protected function generateFilename($title)
    {
        // Convertir a minúsculas y reemplazar espacios con guiones
        $filename = strtolower($title);
        $filename = preg_replace('/[^a-z0-9\s-]/', '', $filename);
        $filename = preg_replace('/\s+/', '-', $filename);
        $filename = trim($filename, '-');

        return $filename;
    }

    /**
     * Generar contenido del manual
     */
    protected function generateManualContent($title, $module, $description, $version)
    {
        $content = "---\n";
        $content .= "titulo: \"{$title}\"\n";
        $content .= "modulo: \"{$module}\"\n";
        $content .= "descripcion: \"{$description}\"\n";
        $content .= "version: \"{$version}\"\n";
        $content .= "activo: true\n";
        $content .= "orden: 1\n";
        $content .= "icono: \"book\"\n";
        $content .= "---\n\n";
        $content .= "# {$title}\n\n";
        $content .= "## Descripción\n\n";
        $content .= "{$description}\n\n";
        $content .= "## Contenido\n\n";
        $content .= "Aquí puedes agregar el contenido de tu manual usando Markdown.\n\n";
        $content .= "### Ejemplos de formato\n\n";
        $content .= "- **Texto en negrita**\n";
        $content .= "- *Texto en cursiva*\n";
        $content .= "- `Código inline`\n\n";
        $content .= "```\n";
        $content .= "Bloque de código\n";
        $content .= "```\n\n";
        $content .= "## Secciones adicionales\n\n";
        $content .= "Puedes agregar tantas secciones como necesites.\n\n";
        $content .= "## Contacto\n\n";
        $content .= "Para más información, contacta al administrador del sistema.\n";

        return $content;
    }
}
