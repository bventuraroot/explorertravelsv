<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contingencia extends Model
{
    use HasFactory;

    protected $table = "contingencias";

    protected $fillable = [
        'idEmpresa',
        'codInterno',
        'versionJson',
        'ambiente',
        'codEstado',
        'estado',
        'tipoContingencia',
        'motivoContingencia',
        'nombreResponsable',
        'tipoDocResponsable',
        'nuDocResponsable',
        'fechaCreacion',
        'horaCreacion',
        'fInicio',
        'fFin',
        'hInicio',
        'hFin',
        'codigoGeneracion',
        'selloRecibido',
        'fhRecibido',
        'codEstadoHacienda',
        'estadoHacienda',
        'codigoMsg',
        'clasificaMsg',
        'descripcionMsg',
        'observacionesMsg',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'fechaCreacion' => 'datetime',
        'fInicio' => 'date',
        'fFin' => 'date',
        'fhRecibido' => 'datetime',
        'horaCreacion' => 'datetime:H:i:s',
        'hInicio' => 'datetime:H:i:s',
        'hFin' => 'datetime:H:i:s'
    ];

    /**
     * Relación con la empresa
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'idEmpresa');
    }

    /**
     * Relación con el usuario que la creó
     */
    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relación con el usuario que la actualizó
     */
    public function actualizador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Alias para la relación company
     */
    public function empresa(): BelongsTo
    {
        return $this->company();
    }

    /**
     * Relación con el usuario que la aprobó
     */
    public function aprobador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprobado_por');
    }

    /**
     * Scope para contingencias activas
     */
    public function scopeActivas($query)
    {
        return $query->where('activa', true)
                    ->where('fecha_fin', '>=', now());
    }

    /**
     * Scope para filtrar por empresa
     */
    public function scopePorEmpresa($query, $empresaId)
    {
        return $query->where('empresa_id', $empresaId);
    }
}
