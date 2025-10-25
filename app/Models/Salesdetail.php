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
        'detainedP',
        'detained13',
        'renta',
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
        'renta' => 'decimal:8',
        'priceunit' => 'decimal:8',
        'pricesale' => 'decimal:8',
        'nosujeta' => 'decimal:8',
        'exempt' => 'decimal:8',
        'detained' => 'decimal:8',
        'detainedP' => 'decimal:8',
        'detained13' => 'decimal:8',
    ];

    /**
     * Relación con el producto
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Relación con la venta
     */
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}
