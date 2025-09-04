<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Manual extends Model
{
    use HasFactory;

    protected $fillable = [
        'titulo',
        'modulo',
        'descripcion',
        'contenido',
        'version',
        'activo',
        'orden',
        'icono'
    ];

    protected $casts = [
        'activo' => 'boolean',
        'orden' => 'integer'
    ];

    // Scope para obtener solo manuales activos
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    // Scope para ordenar por orden
    public function scopeOrdenados($query)
    {
        return $query->orderBy('orden', 'asc')->orderBy('titulo', 'asc');
    }

    // Scope para filtrar por módulo
    public function scopePorModulo($query, $modulo)
    {
        return $query->where('modulo', $modulo);
    }
}
