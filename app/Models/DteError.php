<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class DteError extends Model
{
    use HasFactory;

    protected $table = "dte_errors";

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
        'max_intentos'
    ];

    protected $casts = [
        'detalles' => 'array',
        'trace' => 'array',
        'intentos_realizados' => 'integer',
        'max_intentos' => 'integer',
        'resuelto' => 'boolean',
        'resolved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Tipos de error
    const TIPO_VALIDACION = 'validacion';
    const TIPO_NETWORK = 'network';
    const TIPO_AUTENTICACION = 'autenticacion';
    const TIPO_FIRMA = 'firma';
    const TIPO_HACIENDA = 'hacienda';
    const TIPO_SISTEMA = 'sistema';
    const TIPO_DATOS = 'datos';

    // Estados de resolución
    const RESUELTO_MANUAL = 'manual';
    const RESUELTO_AUTOMATICO = 'automatico';
    const RESUELTO_CONTINGENCIA = 'contingencia';

    /**
     * Relación con el DTE
     */
    public function dte(): BelongsTo
    {
        return $this->belongsTo(Dte::class);
    }

    /**
     * Relación con el usuario que resolvió el error
     */
    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * Alias para la relación resolvedBy
     */
    public function resueltoPor(): BelongsTo
    {
        return $this->resolvedBy();
    }

    /**
     * Scope para errores no resueltos
     */
    public function scopeNoResueltos($query)
    {
        return $query->where('resuelto', false);
    }

    /**
     * Scope para errores críticos
     */
    public function scopeCriticos($query)
    {
        return $query->whereIn('tipo_error', [
            self::TIPO_AUTENTICACION,
            self::TIPO_FIRMA,
            self::TIPO_HACIENDA
        ]);
    }

    /**
     * Scope para errores que pueden reintentarse
     */
    public function scopeReintentables($query)
    {
        return $query->where('resuelto', false)
                    ->where('intentos_realizados', '<', 'max_intentos');
    }

    /**
     * Scope para filtrar por tipo de error
     */
    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo_error', $tipo);
    }


    /**
     * Verificar si el error puede reintentarse
     */
    public function puedeReintentar(): bool
    {
        return !$this->resuelto &&
               $this->intentos_realizados < $this->max_intentos;
    }

    /**
     * Verificar si el error es crítico
     */
    public function isCritico(): bool
    {
        return in_array($this->tipo_error, [
            self::TIPO_AUTENTICACION,
            self::TIPO_FIRMA,
            self::TIPO_HACIENDA
        ]);
    }

    /**
     * Verificar si el error necesita intervención manual
     */
    public function necesitaIntervencionManual(): bool
    {
        return $this->isCritico() ||
               $this->intentos_realizados >= $this->max_intentos ||
               $this->tipo_error === self::TIPO_DATOS;
    }

    /**
     * Obtener el tipo de error como texto
     */
    public function getTipoTextoAttribute(): string
    {
        return match($this->tipo_error) {
            self::TIPO_VALIDACION => 'Validación',
            self::TIPO_NETWORK => 'Red',
            self::TIPO_AUTENTICACION => 'Autenticación',
            self::TIPO_FIRMA => 'Firma Digital',
            self::TIPO_HACIENDA => 'Hacienda',
            self::TIPO_SISTEMA => 'Sistema',
            self::TIPO_DATOS => 'Datos',
            default => 'Desconocido'
        };
    }

    /**
     * Obtener el tipo con clase CSS para badges
     */
    public function getTipoBadgeAttribute(): string
    {
        $color = match($this->tipo_error) {
            self::TIPO_VALIDACION => 'warning',
            self::TIPO_NETWORK => 'info',
            self::TIPO_AUTENTICACION => 'danger',
            self::TIPO_FIRMA => 'danger',
            self::TIPO_HACIENDA => 'danger',
            self::TIPO_SISTEMA => 'secondary',
            self::TIPO_DATOS => 'warning',
            default => 'dark'
        };

        return '<span class="badge bg-' . $color . '">' . $this->tipo_texto . '</span>';
    }

    /**
     * Obtener el estado de resolución como texto
     */
    public function getEstadoResolucionAttribute(): string
    {
        if ($this->resuelto) {
            return 'Resuelto';
        }

        if ($this->necesitaIntervencionManual()) {
            return 'Necesita Intervención';
        }

        if ($this->puedeReintentar()) {
            return 'Pendiente de Reintento';
        }

        return 'En Análisis';
    }

    /**
     * Obtener el estado con clase CSS para badges
     */
    public function getEstadoBadgeAttribute(): string
    {
        if ($this->resuelto) {
            return '<span class="badge bg-success">Resuelto</span>';
        }

        if ($this->necesitaIntervencionManual()) {
            return '<span class="badge bg-danger">Crítico</span>';
        }

        if ($this->puedeReintentar()) {
            return '<span class="badge bg-warning">Pendiente</span>';
        }

        return '<span class="badge bg-info">En Análisis</span>';
    }

    /**
     * Obtener tiempo transcurrido desde el error
     */
    public function getTiempoTranscurridoAttribute(): string
    {
        $diff = $this->created_at->diff(now());

        if ($diff->days > 0) {
            return $diff->days . ' día(s)';
        }

        if ($diff->h > 0) {
            return $diff->h . ' hora(s)';
        }

        if ($diff->i > 0) {
            return $diff->i . ' minuto(s)';
        }

        return 'Recién ocurrido';
    }

    /**
     * Obtener tiempo restante para próximo reintento
     */
    public function getTiempoRestanteReintentoAttribute(): ?string
    {
        if ($this->resuelto) {
            return null;
        }

        return 'Listo para reintento';
    }

    /**
     * Incrementar intentos de resolución
     */
    public function incrementarIntentos(): void
    {
        $this->increment('intentos_realizados');
        $this->save();
    }

    /**
     * Marcar como resuelto
     */
    public function marcarResuelto(string $solucion, int $userId = null): bool
    {
        return $this->update([
            'resuelto' => true,
            'resolved_by' => $userId,
            'resolved_at' => now(),
            'solucion' => $solucion
        ]);
    }

    /**
     * Obtener estadísticas del error
     */
    public function getEstadisticas(): array
    {
        return [
            'intentos_restantes' => $this->max_intentos - $this->intentos_realizados,
            'porcentaje_intentos' => ($this->intentos_realizados / $this->max_intentos) * 100,
            'tiempo_espera' => $this->tiempo_restante_reintento,
            'es_critico' => $this->isCritico(),
            'necesita_intervencion' => $this->necesitaIntervencionManual()
        ];
    }

    /**
     * Crear error automáticamente
     */
    public static function crearError(
        int $dteId,
        string $tipo,
        string $codigo,
        string $descripcion,
        array $detalles = [],
        array $stackTrace = [],
        string $jsonCompleto = null
    ): self {
        $data = [
            'dte_id' => $dteId,
            'tipo_error' => $tipo,
            'codigo_error' => $codigo,
            'descripcion' => $descripcion,
            'detalles' => $detalles,
            'trace' => $stackTrace,
            'intentos_realizados' => 0,
            'max_intentos' => self::getMaxIntentosPorTipo($tipo),
            'resuelto' => false
        ];

        return self::create($data);
    }

    /**
     * Obtener máximo intentos por tipo de error
     */
    private static function getMaxIntentosPorTipo(string $tipo): int
    {
        return match($tipo) {
            self::TIPO_NETWORK => 5,
            self::TIPO_VALIDACION => 3,
            self::TIPO_AUTENTICACION => 1,
            self::TIPO_FIRMA => 1,
            self::TIPO_HACIENDA => 2,
            self::TIPO_SISTEMA => 3,
            self::TIPO_DATOS => 1,
            default => 3
        };
    }
}
