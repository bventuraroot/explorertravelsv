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
    ];

    protected $casts = [
        'detalles' => 'array',
        'trace' => 'array',
        'resuelto' => 'boolean',
        'resolved_at' => 'datetime',
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
}


