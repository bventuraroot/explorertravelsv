<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\ConnectionException;
use App\Models\Ambiente;

class FirmadorTestController extends Controller
{
    /**
     * Obtener la URL del firmador desde la base de datos
     */
    private function getFirmadorUrl(): string
    {
        try {
            // Obtener el ambiente activo (puedes ajustar esto según tu lógica)
            $ambiente = Ambiente::where('cod', '01')->first(); // Modo producción por defecto

            if ($ambiente && $ambiente->url_firmador) {
                return $ambiente->url_firmador;
            }

            // Fallback a la URL por defecto
            return 'http://147.93.176.3:8113/firmardocumento/';

        } catch (\Exception $e) {
            Log::error('Error al obtener URL del firmador: ' . $e->getMessage());
            return 'http://147.93.176.3:8113/firmardocumento/';
        }
    }

    /**
     * Mostrar la vista de prueba del firmador
     */
    public function index()
    {
        $firmadorUrl = $this->getFirmadorUrl();
        return view('firmador.test', compact('firmadorUrl'));
    }

    /**
     * Probar conexión básica al firmador
     */
    public function testConnection(Request $request)
    {
        try {
            $url = $this->getFirmadorUrl();
            $timeout = $request->get('timeout', 30);

            Log::info('Iniciando prueba de conexión al firmador', [
                'url' => $url,
                'timeout' => $timeout
            ]);

            // Prueba 1: Conexión básica
            $startTime = microtime(true);
            $response = Http::timeout($timeout)->get($url);
            $endTime = microtime(true);
            $responseTime = round(($endTime - $startTime) * 1000, 2);

            $result = [
                'success' => true,
                'status_code' => $response->status(),
                'response_time_ms' => $responseTime,
                'url' => $url,
                'message' => 'Conexión exitosa al firmador'
            ];

            Log::info('Prueba de conexión exitosa', $result);

        } catch (ConnectionException $e) {
            $result = [
                'success' => false,
                'error_type' => 'ConnectionException',
                'message' => 'Error de conexión: ' . $e->getMessage(),
                'url' => $url ?? 'http://147.93.176.3:8113/firmardocumento/',
                'details' => [
                    'code' => $e->getCode(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ];

            Log::error('Error de conexión al firmador', $result);

        } catch (\Exception $e) {
            $result = [
                'success' => false,
                'error_type' => get_class($e),
                'message' => 'Error inesperado: ' . $e->getMessage(),
                'url' => $url ?? 'http://147.93.176.3:8113/firmardocumento/',
                'details' => [
                    'code' => $e->getCode(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ];

            Log::error('Error inesperado al probar firmador', $result);
        }

        return response()->json($result);
    }

    /**
     * Probar firma de documento de prueba
     */
    public function testFirma(Request $request)
    {
        try {
            $url = $this->getFirmadorUrl();
            $timeout = $request->get('timeout', 30);

            // Datos de prueba para firma
            $testData = [
                'documento' => '<?xml version="1.0" encoding="UTF-8"?><test>Documento de prueba</test>',
                'certificado' => 'certificado_prueba',
                'password' => 'password_prueba'
            ];

            Log::info('Iniciando prueba de firma', [
                'url' => $url,
                'timeout' => $timeout,
                'data_size' => strlen(json_encode($testData))
            ]);

            $startTime = microtime(true);
            $response = Http::timeout($timeout)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ])
                ->post($url, $testData);
            $endTime = microtime(true);
            $responseTime = round(($endTime - $startTime) * 1000, 2);

            $result = [
                'success' => true,
                'status_code' => $response->status(),
                'response_time_ms' => $responseTime,
                'url' => $url,
                'response_body' => $response->body(),
                'message' => 'Prueba de firma completada'
            ];

            Log::info('Prueba de firma exitosa', $result);

        } catch (ConnectionException $e) {
            $result = [
                'success' => false,
                'error_type' => 'ConnectionException',
                'message' => 'Error de conexión en firma: ' . $e->getMessage(),
                'url' => $url ?? 'http://147.93.176.3:8113/firmardocumento/',
                'details' => [
                    'code' => $e->getCode(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ];

            Log::error('Error de conexión en prueba de firma', $result);

        } catch (\Exception $e) {
            $result = [
                'success' => false,
                'error_type' => get_class($e),
                'message' => 'Error inesperado en firma: ' . $e->getMessage(),
                'url' => $url ?? 'http://147.93.176.3:8113/firmardocumento/',
                'details' => [
                    'code' => $e->getCode(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ];

            Log::error('Error inesperado en prueba de firma', $result);
        }

        return response()->json($result);
    }

    /**
     * Probar conectividad de red
     */
    public function testNetwork()
    {
        $tests = [];
        $firmadorUrl = $this->getFirmadorUrl();

        // Extraer host y puerto de la URL
        $parsedUrl = parse_url($firmadorUrl);
        $host = $parsedUrl['host'] ?? '147.93.176.3';
        $port = $parsedUrl['port'] ?? 8113;

        // Test 1: DNS Resolution
        try {
            $ip = gethostbyname($host);
            $tests['dns'] = [
                'success' => true,
                'host' => $host,
                'resolved_ip' => $ip,
                'message' => 'Resolución DNS exitosa'
            ];
        } catch (\Exception $e) {
            $tests['dns'] = [
                'success' => false,
                'message' => 'Error en resolución DNS: ' . $e->getMessage()
            ];
        }

        // Test 2: Port connectivity
        try {
            $connection = @fsockopen($host, $port, $errno, $errstr, 5);

            if ($connection) {
                fclose($connection);
                $tests['port'] = [
                    'success' => true,
                    'host' => $host,
                    'port' => $port,
                    'message' => 'Puerto accesible'
                ];
            } else {
                $tests['port'] = [
                    'success' => false,
                    'host' => $host,
                    'port' => $port,
                    'error_code' => $errno,
                    'error_message' => $errstr,
                    'message' => 'Puerto no accesible'
                ];
            }
        } catch (\Exception $e) {
            $tests['port'] = [
                'success' => false,
                'message' => 'Error al probar puerto: ' . $e->getMessage()
            ];
        }

        // Test 3: HTTP connectivity
        try {
            $url = $firmadorUrl;
            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'method' => 'GET'
                ]
            ]);

            $response = @file_get_contents($url, false, $context);

            if ($response !== false) {
                $tests['http'] = [
                    'success' => true,
                    'url' => $url,
                    'response_length' => strlen($response),
                    'message' => 'Conexión HTTP exitosa'
                ];
            } else {
                $tests['http'] = [
                    'success' => false,
                    'url' => $url,
                    'message' => 'No se pudo conectar via HTTP'
                ];
            }
        } catch (\Exception $e) {
            $tests['http'] = [
                'success' => false,
                'message' => 'Error en conexión HTTP: ' . $e->getMessage()
            ];
        }

        return response()->json([
            'success' => true,
            'tests' => $tests,
            'summary' => [
                'total_tests' => count($tests),
                'passed_tests' => count(array_filter($tests, fn($test) => $test['success'])),
                'failed_tests' => count(array_filter($tests, fn($test) => !$test['success']))
            ]
        ]);
    }

    /**
     * Obtener información de ambientes disponibles
     */
    public function getAmbientes()
    {
        try {
            $ambientes = Ambiente::all();
            $currentUrl = $this->getFirmadorUrl();

            return response()->json([
                'success' => true,
                'ambientes' => $ambientes,
                'current_url' => $currentUrl
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener ambientes: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener información del servidor
     */
    public function serverInfo()
    {
        $info = [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'server_name' => $_SERVER['SERVER_NAME'] ?? 'Unknown',
            'server_addr' => $_SERVER['SERVER_ADDR'] ?? 'Unknown',
            'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
            'http_user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'curl_version' => curl_version()['version'] ?? 'Unknown',
            'allow_url_fopen' => ini_get('allow_url_fopen') ? 'Enabled' : 'Disabled',
            'max_execution_time' => ini_get('max_execution_time'),
            'memory_limit' => ini_get('memory_limit'),
            'timezone' => date_default_timezone_get(),
            'current_time' => now()->toDateTimeString()
        ];

        return response()->json([
            'success' => true,
            'server_info' => $info
        ]);
    }
}
