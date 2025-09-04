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

    public function marcarComoEnviado(array $datos): bool
    {
        $this->codEstado = $datos['codEstado'] ?? '02';
        $this->Estado = $datos['estado'] ?? 'Enviado';
        $this->codigoGeneracion = $datos['codigoGeneracion'] ?? $this->codigoGeneracion;
        $this->selloRecibido = $datos['selloRecibido'] ?? $this->selloRecibido;
        $this->fhRecibido = $datos['fhRecibido'] ?? now();
        $this->estadoHacienda = $datos['estadoHacienda'] ?? $this->estadoHacienda;
        $this->nSends = 1;
        $this->codeMessage = $datos['codigoMsg'] ?? null;
        $this->claMessage = $datos['clasificaMsg'] ?? null;
        $this->descriptionMessage = $datos['descripcionMsg'] ?? null;
        $this->detailsMessage = $datos['observacionesMsg'] ?? null;
        $this->json = isset($this->json['json_enviado']) ? $this->json : array_merge($this->json ?? [], ['json_enviado' => $datos['json_enviado'] ?? []]);
        return $this->save();
    }

    public function marcarComoRechazado(array $datos): bool
    {
        $this->codEstado = '03';
        $this->Estado = 'Rechazado';
        $this->codeMessage = $datos['codigoMsg'] ?? null;
        $this->claMessage = $datos['clasificaMsg'] ?? null;
        $this->descriptionMessage = $datos['descripcionMsg'] ?? null;
        $this->detailsMessage = $datos['observacionesMsg'] ?? null;
        $this->nSends = ($this->nSends ?? 0) + 1;
        return $this->save();
    }

    public function necesitaContingencia(): bool
    {
        return $this->codEstado === '03' && empty($this->idContingencia);
    }

    public function puedeReintentar(): bool
    {
        return $this->codEstado === '03' && ($this->nSends ?? 0) < 3;
    }
}
