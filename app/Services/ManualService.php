<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;

class ManualService
{
    protected $manualsPath;
    protected $converter;

    public function __construct()
    {
        $this->manualsPath = resource_path('manuals');
        $this->converter = $this->createMarkdownConverter();
    }

    protected function createMarkdownConverter()
    {
        $environment = new Environment([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);

        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new GithubFlavoredMarkdownExtension());

        return new MarkdownConverter($environment);
    }

    /**
     * Obtener todos los manuales organizados por módulo
     */
    public function getAllManuals()
    {
        $manuals = [];

        if (!File::exists($this->manualsPath)) {
            return $manuals;
        }

        // Escanear archivos .md directamente en la carpeta principal
        $files = File::files($this->manualsPath);
        foreach ($files as $file) {
            if ($file->getExtension() === 'md') {
                $manual = $this->parseManualFile($file, 'General');
                if ($manual) {
                    $manuals['General'][] = $manual;
                }
            }
        }

        // Escanear subcarpetas (módulos) dinámicamente
        $directories = File::directories($this->manualsPath);
        foreach ($directories as $directory) {
            $moduleName = basename($directory);
            $moduleManuals = $this->getManualsByModule($moduleName);
            if (!empty($moduleManuals)) {
                $manuals[$moduleName] = $moduleManuals;
            }
        }

        return $manuals;
    }

    /**
     * Obtener manuales de un módulo específico
     */
    public function getManualsByModule($module)
    {
        $modulePath = $this->manualsPath . '/' . $module;

        if (!File::exists($modulePath)) {
            return [];
        }

        $files = File::files($modulePath);
        $manuals = [];

        foreach ($files as $file) {
            if ($file->getExtension() === 'md') {
                $manual = $this->parseManualFile($file, $module);
                if ($manual) {
                    $manuals[] = $manual;
                }
            }
        }

        // Ordenar por orden y luego por título
        usort($manuals, function ($a, $b) {
            if ($a['orden'] == $b['orden']) {
                return strcmp($a['titulo'], $b['titulo']);
            }
            return $a['orden'] - $b['orden'];
        });

        return $manuals;
    }

    /**
     * Obtener un manual específico
     */
    public function getManual($module, $filename)
    {
        $filePath = $this->manualsPath . '/' . $module . '/' . $filename . '.md';

        if (!File::exists($filePath)) {
            return null;
        }

        return $this->parseManualFile(new \SplFileInfo($filePath), $module);
    }

    /**
     * Obtener módulos disponibles (detectados automáticamente)
     */
    public function getAvailableModules()
    {
        $modules = [];

        if (!File::exists($this->manualsPath)) {
            return $modules;
        }

        // Obtener todas las carpetas en el directorio de manuales
        $directories = File::directories($this->manualsPath);

        foreach ($directories as $directory) {
            $moduleKey = basename($directory);
            $moduleName = $this->formatModuleName($moduleKey);
            $modules[$moduleKey] = $moduleName;
        }

        // Agregar módulo General si hay archivos .md en la raíz
        $rootFiles = File::files($this->manualsPath);
        $hasRootFiles = false;
        foreach ($rootFiles as $file) {
            if ($file->getExtension() === 'md') {
                $hasRootFiles = true;
                break;
            }
        }

        if ($hasRootFiles) {
            $modules['General'] = 'General';
        }

        return $modules;
    }

    /**
     * Formatear nombre del módulo para mostrar
     */
    protected function formatModuleName($moduleKey)
    {
        // Reemplazar guiones bajos y guiones con espacios
        $formatted = str_replace(['_', '-'], ' ', $moduleKey);

        // Capitalizar cada palabra
        $formatted = ucwords($formatted);

        // Casos especiales
        $specialCases = [
            'Administracion' => 'Administración',
            'Produccion' => 'Producción',
            'Creditos' => 'Créditos',
            'AdministracionDTE' => 'Administración DTE'
        ];

        return $specialCases[$moduleKey] ?? $formatted;
    }

    /**
     * Parsear archivo de manual
     */
    protected function parseManualFile($file, $module)
    {
        $content = File::get($file->getPathname());
        $filename = pathinfo($file->getFilename(), PATHINFO_FILENAME);

        // Extraer metadatos del front matter (YAML)
        $frontMatter = $this->extractFrontMatter($content);
        $markdownContent = $this->removeFrontMatter($content);

        // Convertir Markdown a HTML
        $htmlContent = $this->converter->convert($markdownContent)->getContent();

        return [
            'id' => $module . '_' . $filename,
            'titulo' => $frontMatter['titulo'] ?? $this->formatTitle($filename),
            'modulo' => $module,
            'descripcion' => $frontMatter['descripcion'] ?? '',
            'contenido' => $htmlContent,
            'contenido_markdown' => $markdownContent,
            'version' => $frontMatter['version'] ?? '1.0',
            'activo' => $frontMatter['activo'] ?? true,
            'orden' => $frontMatter['orden'] ?? 0,
            'icono' => $frontMatter['icono'] ?? 'file-text',
            'filename' => $filename,
            'updated_at' => date('Y-m-d H:i:s', $file->getMTime()),
            'created_at' => date('Y-m-d H:i:s', $file->getCTime()),
        ];
    }

    /**
     * Extraer front matter del archivo
     */
    protected function extractFrontMatter($content)
    {
        if (preg_match('/^---\s*\n(.*?)\n---\s*\n(.*)$/s', $content, $matches)) {
            $yaml = $matches[1];
            $frontMatter = [];

            // Parsear YAML básico
            $lines = explode("\n", $yaml);
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || strpos($line, '#') === 0) {
                    continue;
                }

                if (strpos($line, ':') !== false) {
                    list($key, $value) = explode(':', $line, 2);
                    $key = trim($key);
                    $value = trim($value);

                    // Remover comillas
                    $value = trim($value, '"\'');

                    // Convertir booleanos
                    if ($value === 'true') {
                        $value = true;
                    } elseif ($value === 'false') {
                        $value = false;
                    }

                    // Convertir números
                    if (is_numeric($value)) {
                        $value = (int) $value;
                    }

                    $frontMatter[$key] = $value;
                }
            }

            return $frontMatter;
        }

        return [];
    }

    /**
     * Remover front matter del contenido
     */
    protected function removeFrontMatter($content)
    {
        if (preg_match('/^---\s*\n.*?\n---\s*\n(.*)$/s', $content, $matches)) {
            return $matches[1];
        }

        return $content;
    }

    /**
     * Formatear título desde el nombre del archivo
     */
    protected function formatTitle($filename)
    {
        return ucwords(str_replace(['-', '_'], ' ', $filename));
    }

    /**
     * Obtener lista de archivos de manuales
     */
    public function getManualFiles()
    {
        $files = [];

        if (!File::exists($this->manualsPath)) {
            return $files;
        }

        // Escanear archivos en la raíz
        $rootFiles = File::files($this->manualsPath);
        foreach ($rootFiles as $file) {
            if ($file->getExtension() === 'md') {
                $files[] = [
                    'module' => 'General',
                    'module_name' => 'General',
                    'filename' => pathinfo($file->getFilename(), PATHINFO_FILENAME),
                    'path' => $file->getPathname(),
                    'size' => $file->getSize(),
                    'modified' => $file->getMTime(),
                ];
            }
        }

        // Escanear archivos en subcarpetas
        $directories = File::directories($this->manualsPath);
        foreach ($directories as $directory) {
            $moduleKey = basename($directory);
            $moduleName = $this->formatModuleName($moduleKey);

            $moduleFiles = File::files($directory);
            foreach ($moduleFiles as $file) {
                if ($file->getExtension() === 'md') {
                    $files[] = [
                        'module' => $moduleKey,
                        'module_name' => $moduleName,
                        'filename' => pathinfo($file->getFilename(), PATHINFO_FILENAME),
                        'path' => $file->getPathname(),
                        'size' => $file->getSize(),
                        'modified' => $file->getMTime(),
                    ];
                }
            }
        }

        return $files;
    }
}
