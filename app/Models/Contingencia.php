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
        'company_id',
        'idTienda',
        'codInterno',
        'nombre',
        'versionJson',
        'ambiente',
        'codEstado',
        'activa',
        'estado',
        'codigoGeneracion',
        'fechaCreacion',
        'horaCreacion',
        'fInicio',
        'fecha_inicio',
        'fFin',
        'fecha_fin',
        'hInicio',
        'hFin',
        'tipoContingencia',
        'motivoContingencia',
        'nombreResponsable',
        'tipoDocResponsable',
        'nuDocResponsable',
        'selloRecibido',
        'fhRecibido',
        'codEstadoHacienda',
        'estadoHacienda',
        'codigoMsg',
        'clasificaMsg',
        'descripcionMsg',
        'observacionesMsg',
        'documentos_afectados',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'activa' => 'boolean',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'fechaCreacion' => 'date',
        'fInicio' => 'date',
        'fFin' => 'date',
        'fhRecibido' => 'datetime',
        'documentos_afectados' => 'integer'
    ];

    /**
     * Relación con la empresa
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
