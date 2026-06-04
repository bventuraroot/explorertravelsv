<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailPurchaseImport extends Model
{
    use HasFactory;

    protected $fillable = [
        'email_uid',
        'email_subject',
        'email_from',
        'email_date',
        'filename',
        'pdf_path',
        'dte_codigo_generacion',
        'dte_numero_control',
        'dte_sello_recepcion',
        'dte_tipo_dte',
        'dte_tipo_nombre',
        'status',
        'company_id',
        'purchase_id',
        'items_total',
        'items_mapped',
        'error_message',
        'validation_errors',
        'raw_json',
        'processed_at'
    ];

    protected $casts = [
        'email_date' => 'datetime',
        'validation_errors' => 'array',
        'processed_at' => 'datetime'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class, 'purchase_id');
    }

    // Accessors
    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            'processed' => 'Procesado',
            'error' => 'Error',
            'skipped' => 'Omitido',
            'pending' => 'Pendiente',
            default => 'Desconocido'
        };
    }

    public function getEmisorNombreAttribute()
    {
        if (!$this->raw_json) return null;
        try {
            $dte = json_decode($this->raw_json, true);
            $root = isset($dte['dte']) ? $dte['dte'] : (isset($dte['DTE']) ? $dte['DTE'] : $dte);
            return $root['emisor']['nombre'] ?? $root['emisor']['nombreComercial'] ?? null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function getTotalDteAttribute()
    {
        if (!$this->raw_json) return null;
        try {
            $dte = json_decode($this->raw_json, true);
            $root = isset($dte['dte']) ? $dte['dte'] : (isset($dte['DTE']) ? $dte['DTE'] : $dte);
            $resumen = $root['resumen'] ?? [];
            return (float) ($resumen['montoTotalOperacion'] ?? $resumen['totalPagar'] ?? 0);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
