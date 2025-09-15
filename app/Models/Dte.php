<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dte extends Model
{
    use HasFactory;
    protected $table = "dte";

    protected $fillable = [
        'versionJson',
        'ambiente_id',
        'tipoDte',
        'tipoModelo',
        'tipoTransmision',
        'tipoContingencia',
        'idContingencia',
        'nameTable',
        'company_id',
        'company_name',
        'id_doc',
        'codTransaction',
        'desTransaction',
        'type_document',
        'id_doc_Ref1',
        'id_doc_Ref2',
        'type_invalidacion',
        'codEstado',
        'Estado',
        'codigoGeneracion',
        'selloRecibido',
        'fhRecibido',
        'estadoHacienda',
        'json',
        'nSends',
        'codeMessage',
        'claMessage',
        'descriptionMessage',
        'detailsMessage',
        'sale_id',
        'created_by',
    ];

    protected $casts = [
        'json' => 'array',
        'fhRecibido' => 'datetime',
    ];

    public function errors(): HasMany
    {
        return $this->hasMany(DteError::class, 'dte_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }

    public function contingencia(): BelongsTo
    {
        return $this->belongsTo(Contingencia::class, 'idContingencia');
    }

    public function scopeEnCola($query)
    {
        return $query->where('codEstado', '01');
    }

    public function scopeEnviados($query)
    {
        return $query->where('codEstado', '02');
    }

    public function scopeRechazados($query)
    {
        return $query->where('codEstado', '03');
    }

    public function scopeEnRevision($query)
    {
        return $query->where('codEstado', '10');
    }

    public function scopeParaReintento($query)
    {
        return $query->where('codEstado', '03')->where('nSends', '<', 3);
    }

    public function scopeNecesitanContingencia($query)
    {
        return $query->whereNull('idContingencia')->where('codEstado', '03');
    }

    /**
     * Marcar DTE como enviado exitosamente
     */
    public function marcarComoEnviado(array $datosRespuesta): void
    {
        $this->update([
            'codEstado' => '02',
            'Estado' => 'Enviado',
            'estadoHacienda' => $datosRespuesta['estado'] ?? null,
            'selloRecibido' => $datosRespuesta['selloRecibido'] ?? null,
            'fhRecibido' => $datosRespuesta['fhRecibido'] ?? now(),
            'descripcionMsg' => $datosRespuesta['descripcionMsg'] ?? 'Enviado exitosamente',
            'observacionesMsg' => $datosRespuesta['observacionesMsg'] ?? null,
            'nSends' => ($this->nSends ?? 0) + 1
        ]);
    }

    /**
     * Marcar DTE como rechazado
     */
    public function marcarComoRechazado(array $datosError): void
    {
        $this->update([
            'codEstado' => '03',
            'Estado' => 'Rechazado',
            'descripcionMsg' => $datosError['descripcionMsg'] ?? 'Error en procesamiento',
            'observacionesMsg' => $datosError['observacionesMsg'] ?? null,
            'nSends' => ($this->nSends ?? 0) + 1
        ]);
    }

    /**
     * Marcar DTE para revisión
     */
    public function marcarEnRevision(string $motivo): void
    {
        $this->update([
            'codEstado' => '10',
            'Estado' => 'En Revisión',
            'observacionesMsg' => $motivo
        ]);
    }

    /**
     * Verificar si el DTE puede reintentarse
     */
    public function puedeReintentar(): bool
    {
        return $this->codEstado === '01' &&
               ($this->nSends ?? 0) < 3 &&
               !$this->idContingencia;
    }

    /**
     * Verificar si el DTE necesita contingencia
     */
    public function necesitaContingencia(): bool
    {
        return $this->codEstado === '03' &&
               ($this->nSends ?? 0) >= 3 &&
               !$this->idContingencia;
    }

    /**
     * Incrementar correlativo de envíos
     */
    public function incrementarCorrelativo(): void
    {
        $this->increment('nSends');
    }

    /**
     * Obtener el ambiente como string
     */
    public function getAmbienteAttribute(): string
    {
        return $this->ambiente_id === '01' ? 'Producción' : 'Pruebas';
    }

    /**
     * Verificar si es ambiente de producción
     */
    public function esProduccion(): bool
    {
        return $this->ambiente_id === '01';
    }

    /**
     * Obtener estado como texto legible
     */
    public function getEstadoTextoAttribute(): string
    {
        return match($this->codEstado) {
            '01' => 'En Cola',
            '02' => 'Enviado',
            '03' => 'Rechazado',
            '10' => 'En Revisión',
            default => 'Desconocido'
        };
    }

    /**
     * Obtener clase CSS para el estado
     */
    public function getEstadoClaseAttribute(): string
    {
        return match($this->codEstado) {
            '01' => 'warning',
            '02' => 'success',
            '03' => 'danger',
            '10' => 'info',
            default => 'secondary'
        };
    }
}
