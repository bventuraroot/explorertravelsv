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
        'updated_by',
        'documentos_afectados'
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

    /**
     * Accessor para nombre (alias de motivoContingencia)
     */
    public function getNombreAttribute()
    {
        return $this->motivoContingencia;
    }

    /**
     * Accessor para fecha_inicio (alias de fInicio)
     */
    public function getFechaInicioAttribute()
    {
        return $this->fInicio;
    }

    /**
     * Accessor para fecha_fin (alias de fFin)
     */
    public function getFechaFinAttribute()
    {
        return $this->fFin;
    }

    /**
     * Accessor para tipo_texto
     */
    public function getTipoTextoAttribute()
    {
        $tipos = [
            1 => 'No disponibilidad de sistema del MH',
            2 => 'No disponibilidad de sistema del emisor',
            3 => 'Falla en el suministro de servicio de Internet del Emisor',
            4 => 'Falla en el suministro de servicio de energia eléctrica del emisor que impida la transmisión de los DTE',
            5 => 'Otro'
        ];

        $tipoTexto = $tipos[$this->tipoContingencia ?? 0] ?? 'Desconocido';
        return '<span class="badge bg-label-primary">' . $tipoTexto . '</span>';
    }
}
