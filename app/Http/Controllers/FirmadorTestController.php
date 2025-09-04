<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FirmadorTestController extends Controller
{
    private function getFirmadorUrl(): string
    {
        try {
            $empresaId = auth()->user()->company_id ?? null;
            if ($empresaId) {
                $ambiente = DB::table('config as c')
                    ->leftJoin('ambientes as a', 'c.ambiente', '=', 'a.id')
                    ->where('c.company_id', $empresaId)
                    ->select('a.url_firmador')
                    ->first();
                if ($ambiente && $ambiente->url_firmador) {
                    return $ambiente->url_firmador;
                }
            }
        } catch (\Throwable $e) {
            Log::error('Error al obtener URL del firmador: ' . $e->getMessage());
        }
        return '';
    }

    public function index()
    {
        $firmadorUrl = $this->getFirmadorUrl();
        return view('firmador.test', compact('firmadorUrl'));
    }

    public function testConnection()
    {
        try {
            $url = $this->getFirmadorUrl();
            Log::info('Prueba de conexión al firmador', ['url' => $url]);
            $response = Http::timeout(10)->get($url);
            return response()->json([
                'ok' => $response->successful(),
                'status' => $response->status(),
                'message' => $response->successful() ? 'Conexión exitosa al firmador' : 'Error al conectar con firmador'
            ], $response->successful() ? 200 : 500);
        } catch (\Throwable $e) {
            Log::error('Error de conexión al firmador', ['error' => $e->getMessage()]);
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function testFirma(Request $request)
    {
        try {
            $url = $this->getFirmadorUrl();
            $payload = [
                'nit' => $request->input('nit', '00000000-0'),
                'activo' => true,
                'passwordPri' => $request->input('passwordPri', ''),
                'dteJson' => $request->input('dteJson', ['ping' => 'pong'])
            ];
            $response = Http::timeout(20)->post($url, $payload);
            return response()->json([
                'ok' => $response->successful(),
                'status' => $response->status(),
                'body' => $response->json()
            ], $response->successful() ? 200 : 500);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function serverInfo()
    {
        $url = $this->getFirmadorUrl();
        $parsed = parse_url($url);
        return response()->json([
            'current_url' => $url,
            'host' => $parsed['host'] ?? null,
            'scheme' => $parsed['scheme'] ?? null,
            'port' => $parsed['port'] ?? null,
        ]);
    }
}


