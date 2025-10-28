<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use ZipArchive;

class BackupController extends Controller
{
    private $backupPath = 'backups';

    public function __construct()
    {
        // Crear directorio de backups si no existe
        if (!Storage::disk('local')->exists($this->backupPath)) {
            Storage::disk('local')->makeDirectory($this->backupPath);
        }
    }

    /**
     * Mostrar la página principal de backups
     */
    public function index()
    {
        $backups = $this->getBackupsList();
        $stats = $this->getBackupStats($backups);

        return view('backups.index', compact('backups', 'stats'));
    }

    /**
     * Crear un nuevo backup
     */
    public function create(Request $request)
    {
        try {
            $compress = $request->input('compress', true);
            $dbName = env('DB_DATABASE');
            $dbUser = env('DB_USERNAME');
            $dbPass = env('DB_PASSWORD');
            $dbHost = env('DB_HOST');

            $timestamp = date('Y-m-d_H-i-s');
            $filename = "backup_{$dbName}_{$timestamp}";
            $sqlFile = storage_path("app/{$this->backupPath}/{$filename}.sql");

            // Crear el comando mysqldump
            $command = sprintf(
                'mysqldump --user=%s --password=%s --host=%s %s > %s',
                escapeshellarg($dbUser),
                escapeshellarg($dbPass),
                escapeshellarg($dbHost),
                escapeshellarg($dbName),
                escapeshellarg($sqlFile)
            );

            // Ejecutar el backup
            exec($command, $output, $returnVar);

            if ($returnVar !== 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al crear el backup'
                ], 500);
            }

            // Comprimir si se solicita
            if ($compress) {
                $zipFile = storage_path("app/{$this->backupPath}/{$filename}.sql.gz");

                // Comprimir usando gzip
                $command = sprintf(
                    'gzip -c %s > %s',
                    escapeshellarg($sqlFile),
                    escapeshellarg($zipFile)
                );

                exec($command);

                // Eliminar el archivo SQL sin comprimir
                if (file_exists($zipFile)) {
                    unlink($sqlFile);
                    $finalFile = "{$filename}.sql.gz";
                } else {
                    $finalFile = "{$filename}.sql";
                }
            } else {
                $finalFile = "{$filename}.sql";
            }

            return response()->json([
                'success' => true,
                'message' => 'Backup creado exitosamente',
                'filename' => $finalFile
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Descargar un backup
     */
    public function download($filename)
    {
        try {
            $filePath = storage_path("app/{$this->backupPath}/{$filename}");

            if (!file_exists($filePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Archivo no encontrado'
                ], 404);
            }

            return response()->download($filePath);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al descargar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar un backup
     */
    public function delete($filename)
    {
        try {
            $filePath = "{$this->backupPath}/{$filename}";

            if (!Storage::disk('local')->exists($filePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Archivo no encontrado'
                ], 404);
            }

            Storage::disk('local')->delete($filePath);

            return response()->json([
                'success' => true,
                'message' => 'Backup eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Limpiar backups antiguos
     */
    public function cleanOld(Request $request)
    {
        try {
            $keep = $request->input('keep', 3);
            $backups = $this->getBackupsList();

            // Ordenar por fecha descendente
            usort($backups, function($a, $b) {
                return $b['timestamp'] <=> $a['timestamp'];
            });

            $deleted = 0;
            $toDelete = array_slice($backups, $keep);

            foreach ($toDelete as $backup) {
                Storage::disk('local')->delete("{$this->backupPath}/{$backup['filename']}");
                $deleted++;
            }

            return response()->json([
                'success' => true,
                'message' => "Se eliminaron {$deleted} backup(s) antiguo(s)",
                'deleted' => $deleted
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al limpiar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar la lista de backups
     */
    public function refresh()
    {
        try {
            $backups = $this->getBackupsList();
            $stats = $this->getBackupStats($backups);

            return response()->json([
                'success' => true,
                'backups' => $backups,
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener lista de backups
     */
    private function getBackupsList()
    {
        $files = Storage::disk('local')->files($this->backupPath);
        $backups = [];

        foreach ($files as $file) {
            $fullPath = storage_path("app/{$file}");
            $filename = basename($file);

            // Ignorar archivos que no sean .sql o .sql.gz
            if (!str_ends_with($filename, '.sql') && !str_ends_with($filename, '.sql.gz')) {
                continue;
            }

            $size = filesize($fullPath);
            $timestamp = filemtime($fullPath);
            $compressed = str_ends_with($filename, '.gz');

            // Extraer el nombre de la base de datos del nombre del archivo
            $parts = explode('_', $filename);
            $dbName = isset($parts[1]) ? $parts[1] : env('DB_DATABASE');

            $backups[] = [
                'filename' => $filename,
                'database' => $dbName,
                'size' => $size,
                'size_formatted' => $this->formatBytes($size),
                'timestamp' => $timestamp,
                'date' => date('Y-m-d H:i:s', $timestamp),
                'date_relative' => $this->getRelativeTime($timestamp),
                'compressed' => $compressed,
                'status' => $compressed ? 'Comprimido' : 'Normal'
            ];
        }

        // Ordenar por fecha descendente
        usort($backups, function($a, $b) {
            return $b['timestamp'] <=> $a['timestamp'];
        });

        return $backups;
    }

    /**
     * Obtener estadísticas de backups
     */
    private function getBackupStats($backups)
    {
        $totalSize = 0;
        $compressed = 0;
        $lastBackup = null;

        foreach ($backups as $backup) {
            $totalSize += $backup['size'];
            if ($backup['compressed']) {
                $compressed++;
            }
        }

        if (count($backups) > 0) {
            $lastBackup = $backups[0];
        }

        return [
            'total' => count($backups),
            'total_size' => $totalSize,
            'total_size_formatted' => $this->formatBytes($totalSize),
            'compressed' => $compressed,
            'last_backup' => $lastBackup ? $lastBackup['date'] : null,
            'last_backup_formatted' => $lastBackup ? date('d/m/Y', $lastBackup['timestamp']) : 'N/A'
        ];
    }

    /**
     * Formatear bytes a unidades legibles
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Obtener tiempo relativo
     */
    private function getRelativeTime($timestamp)
    {
        $diff = time() - $timestamp;

        if ($diff < 60) {
            return 'hace ' . $diff . ' segundos';
        } elseif ($diff < 3600) {
            return 'hace ' . floor($diff / 60) . ' minutos';
        } elseif ($diff < 86400) {
            return 'hace ' . floor($diff / 3600) . ' horas';
        } else {
            return 'hace ' . floor($diff / 86400) . ' días';
        }
    }
}

