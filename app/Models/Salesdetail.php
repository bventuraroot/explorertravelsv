<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Salesdetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'product_id',
        'amountp',
        'priceunit',
        'pricesale',
        'nosujeta',
        'exempt',
        'detained',
        'detained13',
        'fee',
        'feeiva',
        'reserva',
        'ruta',
        'destino',
        'linea',
        'canal',
        'user_id',
        'description'
    ];

    protected $casts = [
        'fee' => 'decimal:8',
        'feeiva' => 'decimal:8',
        'priceunit' => 'decimal:8',
        'pricesale' => 'decimal:8',
        'nosujeta' => 'decimal:8',
        'exempt' => 'decimal:8',
        'detained' => 'decimal:8',
        'detained13' => 'decimal:8',
    ];
}
