<?php

namespace App\Services;

use App\Models\EmailPurchaseImport;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Webklex\PHPIMAP\ClientManager;

class EmailPurchaseService
{
    public function __construct(
        private DteValidator $validator,
        private DtePurchaseParser $parser,
        private PurchaseImportService $importService
    ) {}

    public function run(int $limit = 20, int $userId = 1): array
    {
        $summary = ['total' => 0, 'processed' => 0, 'errors' => 0, 'skipped' => 0];
        Log::info('[EmailPurchase] Iniciando ejecución del lector IMAP. Límite: ' . $limit . ', Usuario ID: ' . $userId);

        try {
            $client = $this->buildClient();
            Log::info('[EmailPurchase] Conectando al servidor IMAP...');
            $client->connect();
            Log::info('[EmailPurchase] Conexión IMAP establecida con éxito.');

            $folderName = config('email_purchase.mailbox', 'INBOX');
            $folder   = $client->getFolder($folderName);
            Log::info("[EmailPurchase] Accediendo a la carpeta de correo: {$folderName}");
            
            // Obtener el lote ordenado por fecha descendente (más nuevos primero)
            $query = $folder->query()->all();
            $query->setFetchOrder('desc');

            $sinceDays = (int) config('email_purchase.since_days', 30);
            if ($sinceDays > 0) {
                $query->since(now()->subDays($sinceDays));
                Log::info("[EmailPurchase] Filtrando correos desde hace {$sinceDays} días.");
            } else {
                Log::info("[EmailPurchase] Buscando todos los correos en la carpeta sin filtro de fecha.");
            }

            if ($limit > 0) {
                $query->limit($limit);
            }
            $messages = $query->get();
            Log::info('[EmailPurchase] Cantidad de correos obtenidos del servidor IMAP: ' . count($messages));

            foreach ($messages as $message) {
                try {
                    $result = $this->processMessage($message, $userId);
                    $summary[$result]++;
                    $summary['total']++;
                } catch (\Throwable $e) {
                    Log::error('[EmailPurchase] Error inesperado en mensaje: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
                    $summary['errors']++;
                    $summary['total']++;
                }
            }

            $client->disconnect();
            Log::info('[EmailPurchase] Desconexión de servidor IMAP completada de forma segura.');
        } catch (\Throwable $e) {
            Log::error('[EmailPurchase] Fallo de conexión IMAP o error crítico: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            throw $e;
        }

        return $summary;
    }

    private function processMessage($message, int $userId): string
    {
        $emailUid     = (string) $message->getUid();
        $emailSubject = $this->decodeSubject($message->getSubject());
        $emailFrom    = (string) ($message->getFrom()[0]->mail ?? '');
        $emailDate    = $message->getDate() ? $message->getDate()->first() : null;

        Log::info("[EmailPurchase] Procesando correo. UID: {$emailUid} | De: {$emailFrom} | Fecha: " . ($emailDate ? $emailDate->toDateTimeString() : 'Desconocida') . " | Asunto: '{$emailSubject}'");

        // Evitar procesar correos duplicados por ID de Mensaje, excepto si terminaron en error o fueron omitidos
        $existingImports = EmailPurchaseImport::where('email_uid', $emailUid)
            ->orWhere('email_uid', 'like', $emailUid . '_%')
            ->get();

        if ($existingImports->isNotEmpty()) {
            $hasValidImport = $existingImports->contains(function ($imp) {
                return in_array($imp->status, ['processed', 'partial', 'pending']);
            });

            if ($hasValidImport) {
                Log::info("[EmailPurchase] Correo UID {$emailUid} ya procesado previamente. Omitiendo conexión.");
                return 'skipped';
            }

            // Si todos los intentos anteriores fallaron o fueron omitidos, los eliminamos para re-procesar de cero
            Log::info("[EmailPurchase] Correo UID {$emailUid} tiene intentos anteriores fallidos o skip. Eliminando registros anteriores para re-procesar.");
            foreach ($existingImports as $imp) {
                if ($imp->pdf_path) {
                    Storage::disk('public')->delete($imp->pdf_path);
                }
                $imp->items()->delete();
                $imp->delete();
            }
        }

        // Asegurar que el cuerpo del mensaje y su estructura de adjuntos estén completamente parseados y descargados
        try {
            Log::info("[EmailPurchase] Descargando y parseando estructura de cuerpo para el correo UID {$emailUid}...");
            $message->parseBody();
        } catch (\Throwable $e) {
            Log::warning("[EmailPurchase] Advertencia al parsear el cuerpo del correo UID {$emailUid}: " . $e->getMessage());
        }

        // Obtener los adjuntos pre-parseados directamente por la librería de forma robusta
        $allParts = $message->getAttachments();
        Log::info("[EmailPurchase] Correo UID {$emailUid} - Adjuntos encontrados por la librería: " . count($allParts));
        
        $jsonAttachments = [];
        $pdfAttachment   = null;

        foreach ($allParts as $part) {
            $filename = (string) $part->getName();
            $contentType = strtolower((string) $part->getContentType());
            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            Log::debug("[EmailPurchase] Analizando parte. Nombre: '{$filename}' | MIME: {$contentType} | Ext: {$extension}");

            // Validar si es JSON
            if ($extension === 'json' || 
                $contentType === 'application/json' || 
                $contentType === 'text/json' ||
                (!empty($filename) && str_contains(strtolower($filename), '.json'))) {
                Log::info("[EmailPurchase] Encontrado adjunto JSON: '{$filename}' | MIME: {$contentType}");
                $jsonAttachments[] = $part;
            }

            // Validar si es PDF
            if (!$pdfAttachment && (
                $extension === 'pdf' || 
                $contentType === 'application/pdf' || 
                (!empty($filename) && str_contains(strtolower($filename), '.pdf'))
            )) {
                Log::info("[EmailPurchase] Encontrado adjunto PDF: '{$filename}' | MIME: {$contentType}");
                $pdfAttachment = $part;
            }

            // Validar si es ZIP para buscar JSON/PDF dentro
            if ($extension === 'zip' || 
                $contentType === 'application/zip' || 
                $contentType === 'application/x-zip-compressed' ||
                (!empty($filename) && str_contains(strtolower($filename), '.zip'))) {
                Log::info("[EmailPurchase] Encontrado archivo ZIP adjunto: '{$filename}'. Inspeccionando su contenido...");
                try {
                    $zipContent = $part->getContent();
                    if (!empty($zipContent)) {
                        $tempFile = tempnam(sys_get_temp_dir(), 'dte_zip_');
                        file_put_contents($tempFile, $zipContent);

                        $extractedFiles = [];
                        if (class_exists(\ZipArchive::class)) {
                            $zip = new \ZipArchive();
                            if ($zip->open($tempFile) === true) {
                                Log::info("[EmailPurchase] ZIP abierto con éxito (ZipArchive). Archivos contenidos: " . $zip->numFiles);
                                for ($i = 0; $i < $zip->numFiles; $i++) {
                                    $extractedFiles[] = [
                                        'name'    => $zip->getNameIndex($i),
                                        'content' => $zip->getFromIndex($i)
                                    ];
                                }
                                $zip->close();
                            } else {
                                Log::error("[EmailPurchase] No se pudo abrir el archivo ZIP con ZipArchive.");
                            }
                        } else {
                            Log::info("[EmailPurchase] ZipArchive no disponible. Usando fallback comando 'unzip'...");
                            $output = [];
                            $returnVar = 0;
                            @exec("unzip -Z -1 " . escapeshellarg($tempFile) . " 2>&1", $output, $returnVar);
                            if ($returnVar === 0 && !empty($output)) {
                                foreach ($output as $line) {
                                    $filenameInZip = trim($line);
                                    if (empty($filenameInZip)) continue;
                                    $fileContent = shell_exec("unzip -p " . escapeshellarg($tempFile) . " " . escapeshellarg($filenameInZip));
                                    $extractedFiles[] = [
                                        'name'    => $filenameInZip,
                                        'content' => $fileContent
                                    ];
                                }
                            } else {
                                Log::error("[EmailPurchase] Fallback 'unzip' falló o no retornó archivos.");
                            }
                        }

                        foreach ($extractedFiles as $file) {
                            $zipFilename = $file['name'];
                            $zipExtension = strtolower(pathinfo($zipFilename, PATHINFO_EXTENSION));
                            $zipContentDecoded = $file['content'];

                            if ($zipExtension === 'json' || str_contains(strtolower($zipFilename), '.json')) {
                                Log::info("[EmailPurchase] Encontrado JSON dentro del ZIP: '{$zipFilename}'");
                                $jsonAttachments[] = new class($zipFilename, $zipContentDecoded) {
                                    public function __construct(private $name, private $content) {}
                                    public function getName() { return $this->name; }
                                    public function getContent() { return $this->content; }
                                    public function getContentType() { return 'application/json'; }
                                };
                            }

                            if (!$pdfAttachment && ($zipExtension === 'pdf' || str_contains(strtolower($zipFilename), '.pdf'))) {
                                Log::info("[EmailPurchase] Encontrado PDF dentro del ZIP: '{$zipFilename}'");
                                $pdfAttachment = new class($zipFilename, $zipContentDecoded) {
                                    public function __construct(private $name, private $content) {}
                                    public function getName() { return $this->name; }
                                    public function getContent() { return $this->content; }
                                    public function getContentType() { return 'application/pdf'; }
                                };
                            }
                        }
                        unlink($tempFile);
                    }
                } catch (\Throwable $e) {
                    Log::error('[EmailPurchase] Error abriendo archivo ZIP adjunto: ' . $e->getMessage());
                }
            }
        }

        if (empty($jsonAttachments)) {
            Log::info("[EmailPurchase] Correo UID {$emailUid} - No se encontraron adjuntos JSON DTE. Guardando registro omitido.");
            $this->saveSkipped($emailUid, $emailSubject, $emailFrom, $emailDate, null, 'Sin adjuntos JSON DTE (se revisaron todas las partes del correo).');
            return 'skipped';
        }

        // Buscar factura física original en PDF si viene adjunta
        $pdfPath = null;
        if ($pdfAttachment) {
            try {
                $pdfNameRaw = (string) $pdfAttachment->getName() ?: 'factura.pdf';
                $pdfFilename = uniqid('dte_') . '_' . preg_replace('/[^a-zA-Z0-9_.-]/', '_', $pdfNameRaw);
                Storage::disk('public')->put('dte_pdfs/' . $pdfFilename, $pdfAttachment->getContent());
                $pdfPath = 'dte_pdfs/' . $pdfFilename;
                Log::info("[EmailPurchase] PDF original guardado en disco: '{$pdfPath}'");
            } catch (\Throwable $e) {
                Log::error('[EmailPurchase] Error guardando PDF adjunto: ' . $e->getMessage());
            }
        }

        $result = 'skipped';

        foreach ($jsonAttachments as $attachment) {
            $filename = (string) $attachment->getName();
            $content  = (string) $attachment->getContent();
            Log::info("[EmailPurchase] Procesando adjunto JSON: '{$filename}'...");

            // Decodificación robusta con detección automática de codificación
            $dte = $this->decodeJsonContent($content, $filename);

            if ($dte === null) {
                Log::error("[EmailPurchase] Error decodificando contenido JSON para '{$filename}': " . json_last_error_msg());
                $this->saveError($emailUid, $emailSubject, $emailFrom, $emailDate, $filename, null,
                    'JSON Inválido o encoding no compatible: ' . json_last_error_msg());
                $result = 'errors';
                continue;
            }

            // Normalización
            if (is_array($dte)) {
                if (isset($dte['dte']) && is_array($dte['dte'])) {
                    $dte = $dte['dte'];
                } elseif (isset($dte['DTE']) && is_array($dte['DTE'])) {
                    $dte = $dte['DTE'];
                } elseif (isset($dte['Dte']) && is_array($dte['Dte'])) {
                    $dte = $dte['Dte'];
                }
            }

            // Registrar borrador inicial en BD
            $import = EmailPurchaseImport::create([
                'email_uid'     => $emailUid . '_' . $filename,
                'email_subject' => $emailSubject,
                'email_from'    => $emailFrom,
                'email_date'    => $emailDate,
                'filename'      => $filename,
                'pdf_path'      => $pdfPath,
                'status'        => 'pending',
                'raw_json'      => json_encode(
                    $this->stripSignature($dte),
                    JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
                ),
            ]);

            try {
                // Validar DTE
                $validation = $this->validator->validate($dte, $import->email_uid);

                if (! $validation['valid']) {
                    $status = $validation['should_skip'] ? 'skipped' : 'error';
                    $import->update([
                        'status'            => $status,
                        'error_message'     => implode(' | ', $validation['errors']),
                        'validation_errors' => $validation['errors'],
                        'processed_at'      => now(),
                    ]);
                    $result = $validation['should_skip'] ? 'skipped' : 'errors';
                    continue;
                }

                $company = $validation['company'];
                $selloMH = $dte['respuestaMH']['selloRecibido']
                    ?? $dte['responseMH']['selloRecibido']
                    ?? $dte['selloRecibido']
                    ?? null;

                $import->update([
                    'dte_codigo_generacion' => $dte['identificacion']['codigoGeneracion'] ?? null,
                    'dte_numero_control'    => $dte['identificacion']['numeroControl'] ?? null,
                    'dte_sello_recepcion'   => $selloMH,
                    'dte_tipo_dte'          => $dte['identificacion']['tipoDte'] ?? null,
                    'dte_tipo_nombre'       => $this->getTipoNombre($dte['identificacion']['tipoDte'] ?? ''),
                    'company_id'            => $company->id,
                ]);

                // Parsear información del DTE
                $parsed = $this->parser->parse($dte, $company, $userId);

                // Persistir
                $this->importService->process($import, $parsed);

                $result = $import->fresh()->status === 'processed' ? 'processed' : 'errors';

            } catch (\Throwable $e) {
                Log::error("[EmailPurchase] Error en {$filename}: " . $e->getMessage());
                $import->update([
                    'status'        => 'error',
                    'error_message' => $e->getMessage(),
                    'processed_at'  => now(),
                ]);
                $result = 'errors';
            }
        }

        return $result;
    }

    private function decodeJsonContent(string $content, string $filename): ?array
    {
        // Limpiar espacios en blanco y cualquier BOM (Byte Order Mark) al principio del string
        $content = trim($content);
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content); // UTF-8 BOM
        $content = preg_replace('/^\xFE\xFF/', '', $content);     // UTF-16 BE BOM
        $content = preg_replace('/^\xFF\xFE/', '', $content);     // UTF-16 LE BOM

        // Intento 1: UTF-8 directo
        $dte = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE) return $dte;

        // Intento 2: Detección y re-codificación
        $encodings = ['ISO-8859-1', 'Windows-1252', 'UTF-16', 'UTF-16LE', 'UTF-16BE'];
        $detected  = mb_detect_encoding($content, $encodings, true);

        if ($detected && $detected !== 'UTF-8') {
            $converted = mb_convert_encoding($content, 'UTF-8', $detected);
            $converted = preg_replace('/^\xEF\xBB\xBF/', '', $converted); // Limpiar BOM si surgió en la conversión
            $dte       = json_decode($converted, true);
            if (json_last_error() === JSON_ERROR_NONE) return $dte;
        }

        // Intento 3: Conversión forzada
        $converted = mb_convert_encoding($content, 'UTF-8', 'ISO-8859-1');
        $converted = preg_replace('/^\xEF\xBB\xBF/', '', $converted); // Limpiar BOM si surgió en la conversión
        $dte       = json_decode($converted, true);
        if (json_last_error() === JSON_ERROR_NONE) return $dte;

        return null;
    }

    private function decodeSubject($subject): string
    {
        if ($subject === null) return '';
        $raw = (string) $subject;
        if (str_contains($raw, '=?')) {
            $decoded = mb_decode_mimeheader($raw);
            return $decoded ?: $raw;
        }
        return $raw;
    }

    private function buildClient(): \Webklex\PHPIMAP\Client
    {
        $host          = config('email_purchase.imap.host');
        $port          = (int) config('email_purchase.imap.port', 993);
        $protocol      = 'imap';
        $encryption    = config('email_purchase.imap.encryption', 'ssl');
        $validate_cert = (bool) config('email_purchase.imap.validate_cert', false);
        $username      = config('email_purchase.imap.username');
        $password      = config('email_purchase.imap.password');

        // Enmascarar credenciales para seguridad en logs
        $maskedUser = 'No configurado';
        if (!empty($username)) {
            $atPos = strpos($username, '@');
            if ($atPos !== false) {
                $maskedUser = substr($username, 0, min(3, $atPos)) . '***' . substr($username, $atPos);
            } else {
                $maskedUser = substr($username, 0, min(3, strlen($username))) . '***';
            }
        }
        $passLen = strlen((string)$password);
        $maskedPass = $passLen > 0 ? "Configurada (longitud: {$passLen})" : 'No configurada';

        Log::info(sprintf(
            '[EmailPurchase] Generando cliente IMAP. Host: "%s" | Puerto: %d | Protocolo: "%s" | Encriptación: "%s" | Validar Cert: %s | Usuario: "%s" | Contraseña: %s',
            $host,
            $port,
            $protocol,
            $encryption,
            $validate_cert ? 'true' : 'false',
            $maskedUser,
            $maskedPass
        ));

        if (empty($host)) {
            Log::warning('[EmailPurchase] El host IMAP está vacío. Por favor, verifica tu configuración en el archivo .env.');
        }

        $cm = new ClientManager([
            'options' => [
                'delimiter'        => '/',
                'fetch'            => \Webklex\PHPIMAP\IMAP::FT_UID,
                'fetch_body'       => true,
                'fetch_attachment' => true,
                'fetch_flags'      => true,
            ]
        ]);
        return $cm->make([
            'driver'         => 'socket',
            'host'           => $host,
            'port'           => $port,
            'protocol'       => $protocol,
            'encryption'     => $encryption,
            'validate_cert'  => $validate_cert,
            'username'       => $username,
            'password'       => $password,
            'authentication' => 'auto',
        ]);
    }

    private function saveSkipped($uid, $subject, $from, $date, $filename, $reason): void
    {
        EmailPurchaseImport::create([
            'email_uid' => $uid . ($filename ? '_' . $filename : ''),
            'email_subject' => $subject, 'email_from' => $from,
            'email_date' => $date, 'filename' => $filename, 'status' => 'skipped',
            'error_message' => $reason, 'processed_at' => now(),
        ]);
    }

    private function saveError($uid, $subject, $from, $date, $filename, $rawJson, $message): void
    {
        EmailPurchaseImport::create([
            'email_uid' => $uid . ($filename ? '_' . $filename : ''),
            'email_subject' => $subject, 'email_from' => $from,
            'email_date' => $date, 'filename' => $filename, 'status' => 'error',
            'error_message' => $message, 'raw_json' => $rawJson, 'processed_at' => now(),
        ]);
    }

    private function stripSignature(array $dte): array
    {
        unset($dte['firmaElectronica'], $dte['firma']);
        return $dte;
    }

    private function getTipoNombre(string $tipoDte): string
    {
        $nombres = [
            '01' => 'Factura',
            '03' => 'Comprobante de Crédito Fiscal',
            '04' => 'Nota de Remisión',
            '05' => 'Nota de Crédito',
            '06' => 'Nota de Débito',
            '07' => 'Comprobante de Retención',
            '14' => 'Factura de Sujeto Excluido',
        ];
        return $nombres[$tipoDte] ?? "Tipo {$tipoDte}";
    }
}
