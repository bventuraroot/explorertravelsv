<?php

namespace App\Http\Controllers;

use App\Services\DteService;
use Illuminate\Http\Request;

class DteAdminController extends Controller
{
    protected $service;

    public function __construct(DteService $service)
    {
        $this->service = $service;
        $this->middleware('auth');
    }

    public function estadisticas(Request $request)
    {
        $empresaId = $request->get('empresa_id');
        $stats = $this->service->obtenerEstadisticas($empresaId);
        return view('dte.estadisticas', compact('stats', 'empresaId'));
    }

    public function procesarCola()
    {
        $resultado = $this->service->procesarCola(10);
        return back()->with('success', 'Cola procesada: ' . json_encode($resultado));
    }

    public function procesarReintentos()
    {
        $resultado = $this->service->procesarReintentosAutomaticos();
        return back()->with('success', 'Reintentos procesados: ' . json_encode($resultado));
    }
}


