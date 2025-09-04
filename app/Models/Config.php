<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Config extends Model
{
    use HasFactory;
    protected $table = "config";

    protected $fillable = [
        'company_id',
        'version',
        'ambiente',
        'typeModel',
        'typeTransmission',
        'typeContingencia',
        'versionJson',
        'passPrivateKey',
        'passkeyPublic',
        'passMH',
        'codeCountry',
        'nameCountry',
        'dte_emission_enabled',
        'dte_emission_notes'
    ];

    protected $casts = [
        'dte_emission_enabled' => 'boolean'
    ];

    /**
     * Verificar si la emisión de DTE está habilitada para una empresa
     */
    public static function isDteEmissionEnabled(int $companyId): bool
    {
        $config = self::where('company_id', $companyId)->first();
        return $config ? $config->dte_emission_enabled : true; // Por defecto habilitado
    }

    /**
     * Obtener configuración de DTE para una empresa
     */
    public static function getDteConfig(int $companyId): ?self
    {
        return self::where('company_id', $companyId)->first();
    }
}
