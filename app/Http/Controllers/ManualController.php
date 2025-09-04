<?php

namespace App\Http\Controllers;

use App\Services\ManualService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ManualController extends Controller
{
    protected $manualService;

    public function __construct(ManualService $manualService)
    {
        $this->manualService = $manualService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $id_user = auth()->user()->id;

        // Consultar el rol del usuario (asumiendo que el rol de admin tiene role_id = 1)
        $rolQuery = "SELECT a.role_id FROM model_has_roles a WHERE a.model_id = ?";
        $rolResult = DB::select($rolQuery, [$id_user]);
        $isAdmin = !empty($rolResult) && $rolResult[0]->role_id == 1;

        $manuals = $this->manualService->getAllManuals();
        $modulos = $this->manualService->getAvailableModules();

        return view('manuals.index', compact('manuals', 'modulos', 'isAdmin'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $modulos = $this->manualService->getAvailableModules();
        return view('manuals.create', compact('modulos'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'titulo' => 'required|string|max:255',
            'modulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'contenido' => 'required|string',
            'version' => 'required|string|max:50',
            'orden' => 'required|integer|min:0',
            'icono' => 'nullable|string|max:100'
        ]);

        // Crear archivo Markdown
        $filename = $this->generateFilename($request->titulo);
        $modulePath = resource_path('manuals/' . $request->modulo);

        if (!file_exists($modulePath)) {
            mkdir($modulePath, 0755, true);
        }

        $filePath = $modulePath . '/' . $filename . '.md';

        // Crear contenido con front matter
        $content = $this->generateMarkdownContent($request->all());

        file_put_contents($filePath, $content);

        return redirect()->route('manuals.index')
            ->with('success', 'Manual creado exitosamente.');
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $id (formato: modulo_filename)
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        list($module, $filename) = explode('_', $id, 2);
        $manual = $this->manualService->getManual($module, $filename);

        if (!$manual) {
            abort(404, 'Manual no encontrado');
        }

        return view('manuals.show', compact('manual'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  string  $id (formato: modulo_filename)
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        list($module, $filename) = explode('_', $id, 2);
        $manual = $this->manualService->getManual($module, $filename);

        if (!$manual) {
            abort(404, 'Manual no encontrado');
        }

        $modulos = $this->manualService->getAvailableModules();
        return view('manuals.edit', compact('manual', 'modulos'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id (formato: modulo_filename)
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'titulo' => 'required|string|max:255',
            'modulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'contenido' => 'required|string',
            'version' => 'required|string|max:50',
            'orden' => 'required|integer|min:0',
            'icono' => 'nullable|string|max:100'
        ]);

        list($oldModule, $oldFilename) = explode('_', $id, 2);
        $oldFilePath = resource_path('manuals/' . $oldModule . '/' . $oldFilename . '.md');

        // Si cambió el módulo o el título, crear nuevo archivo
        if ($oldModule !== $request->modulo || $oldFilename !== $this->generateFilename($request->titulo)) {
            $newFilename = $this->generateFilename($request->titulo);
            $newModulePath = resource_path('manuals/' . $request->modulo);

            if (!file_exists($newModulePath)) {
                mkdir($newModulePath, 0755, true);
            }

            $newFilePath = $newModulePath . '/' . $newFilename . '.md';

            // Crear nuevo archivo
            $content = $this->generateMarkdownContent($request->all());
            file_put_contents($newFilePath, $content);

            // Eliminar archivo anterior si existe
            if (file_exists($oldFilePath)) {
                unlink($oldFilePath);
            }
        } else {
            // Actualizar archivo existente
            $content = $this->generateMarkdownContent($request->all());
            file_put_contents($oldFilePath, $content);
        }

        return redirect()->route('manuals.index')
            ->with('success', 'Manual actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $id (formato: modulo_filename)
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        list($module, $filename) = explode('_', $id, 2);
        $filePath = resource_path('manuals/' . $module . '/' . $filename . '.md');

        if (file_exists($filePath)) {
            unlink($filePath);
        }

        return redirect()->route('manuals.index')
            ->with('success', 'Manual eliminado exitosamente.');
    }

    /**
     * Display manuals by module
     *
     * @param  string  $modulo
     * @return \Illuminate\Http\Response
     */
    public function porModulo($modulo)
    {
        $manuals = $this->manualService->getManualsByModule($modulo);
        $modulos = $this->manualService->getAvailableModules();
        $nombreModulo = $modulos[$modulo] ?? $modulo;

        return view('manuals.por-modulo', compact('manuals', 'modulo', 'nombreModulo'));
    }

    /**
     * Generar nombre de archivo desde el título
     */
    protected function generateFilename($title)
    {
        $filename = strtolower($title);
        $filename = preg_replace('/[^a-z0-9\s-]/', '', $filename);
        $filename = preg_replace('/[\s-]+/', '-', $filename);
        $filename = trim($filename, '-');

        return $filename;
    }

    /**
     * Generar contenido Markdown con front matter
     */
    protected function generateMarkdownContent($data)
    {
        $frontMatter = "---\n";
        $frontMatter .= "titulo: \"{$data['titulo']}\"\n";
        $frontMatter .= "modulo: \"{$data['modulo']}\"\n";
        $frontMatter .= "descripcion: \"{$data['descripcion']}\"\n";
        $frontMatter .= "version: \"{$data['version']}\"\n";
        $frontMatter .= "activo: " . ($data['activo'] ? 'true' : 'false') . "\n";
        $frontMatter .= "orden: {$data['orden']}\n";
        $frontMatter .= "icono: \"{$data['icono']}\"\n";
        $frontMatter .= "---\n\n";

        return $frontMatter . $data['contenido'];
    }

    /**
     * Formatear tamaño de archivo
     */
    public static function formatFileSize($bytes)
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }

    /**
     * Parsear Markdown a HTML
     */
    public function parseMarkdown($content)
    {
        $converter = new \League\CommonMark\CommonMarkConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);

        return $converter->convert($content)->getContent();
    }
}
