<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use HasFactory;

    // Tipos DTE que RESTAN del inventario (Nota de Crédito de Proveedor)
    public const TIPOS_DTE_RESTA_INVENTARIO = ['05'];

    // Tipos DTE que SUMAN al inventario (Factura, CCF, ND Proveedor, FSE)
    public const TIPOS_DTE_SUMA_INVENTARIO = ['01', '03', '06', '14'];

    protected $fillable = [
        'document_id',
        'document_tipo_dte',
        'related_purchase_id',
        'provider_id',
        'company_id',
        'number',
        'codigo_generacion',
        'sello_recepcion',
        'date',
        'exenta',
        'gravada',
        'iva',
        'contrns',
        'fovial',
        'iretenido',
        'otros',
        'total',
        'fingreso',
        'periodo',
        'user_id',
        'import_id'
    ];

    protected $casts = [
        'date'                => 'date',
        'fingreso'            => 'date',
        'related_purchase_id' => 'integer',
    ];

    public function details()
    {
        return $this->hasMany(PurchaseDetail::class);
    }

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Relación con la compra original que esta NC/ND está afectando
     */
    public function relatedPurchase()
    {
        return $this->belongsTo(Purchase::class, 'related_purchase_id');
    }

    /**
     * Relación inversa: NC/ND que afectan esta compra
     */
    public function creditDebitNotes()
    {
        return $this->hasMany(Purchase::class, 'related_purchase_id');
    }

    /**
     * ¿Este documento RESTA del inventario? (Nota de Crédito de Proveedor)
     */
    public function isInventorySubtract(): bool
    {
        return in_array($this->document_tipo_dte, self::TIPOS_DTE_RESTA_INVENTARIO);
    }

    /**
     * ¿Este documento SUMA al inventario? (Factura, CCF, ND, FSE)
     */
    public function isInventoryAdd(): bool
    {
        if (!$this->document_tipo_dte) return true;
        return in_array($this->document_tipo_dte, self::TIPOS_DTE_SUMA_INVENTARIO);
    }
}
