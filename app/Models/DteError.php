<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DteError extends Model
{
    use HasFactory;

    protected $table = 'dte_errors';

    protected $fillable = [
        'dte_id',
        'tipo_error',
        'codigo_error',
        'descripcion',
        'detalles',
        'trace',
        'resuelto',
        'solucion',
        'resolved_by',
        'resolved_at',
        'intentos_realizados',
        'max_intentos',
    ];

    protected $casts = [
        'detalles' => 'array',
        'trace' => 'array',
        'resuelto' => 'boolean',
        'resolved_at' => 'datetime',
        'intentos_realizados' => 'integer',
        'max_intentos' => 'integer',
    ];

    public function dte(): BelongsTo
    {
        return $this->belongsTo(Dte::class);
    }

    public static function crearError(int $dteId, string $tipo, string $codigo, string $descripcion, array $detalles = [], array $trace = []): self
    {
        return self::create([
            'dte_id' => $dteId,
            'tipo_error' => $tipo,
            'codigo_error' => $codigo,
            'descripcion' => $descripcion,
            'detalles' => $detalles,
            'trace' => $trace,
            'resuelto' => false,
        ]);
    }

    public function scopeNoResueltos($query)
    {
        return $query->where('resuelto', false);
    }

    public function scopeCriticos($query)
    {
        return $query->whereIn('tipo_error', ['sistema', 'hacienda']);
    }

    public function getTipoTextoAttribute(): string
    {
        return strtoupper($this->tipo_error);
    }

    public function getTiempoTranscurridoAttribute(): string
    {
        return $this->created_at ? $this->created_at->diffForHumans() : '';
    }

    public function marcarResuelto(string $solucion, int $userId): bool
    {
        $this->resuelto = true;
        $this->solucion = $solucion;
        $this->resolved_by = $userId;
        $this->resolved_at = now();
        return $this->save();
    }

    public function incrementarIntento(): bool
    {
        $this->intentos_realizados++;
        return $this->save();
    }

    public function puedeReintentar(): bool
    {
        return $this->intentos_realizados < $this->max_intentos && !$this->resuelto;
    }

    public function getEstadoBadgeAttribute(): string
    {
        if ($this->resuelto) {
            return '<span class="badge bg-success">Resuelto</span>';
        }

        if ($this->intentos_realizados >= $this->max_intentos) {
            return '<span class="badge bg-danger">Agotado</span>';
        }

        return '<span class="badge bg-warning">Pendiente</span>';
    }

    public function getTipoBadgeAttribute(): string
    {
        $badgeClass = match($this->tipo_error) {
            'hacienda' => 'bg-warning',
            'sistema' => 'bg-secondary',
            'validacion' => 'bg-info',
            'autenticacion', 'firma' => 'bg-danger',
            default => 'bg-secondary'
        };

        return '<span class="badge ' . $badgeClass . '">' . ucfirst($this->tipo_error) . '</span>';
    }
}


