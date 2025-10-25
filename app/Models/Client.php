<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'firstname',
        'secondname',
        'firtslastname',
        'secondlastname',
        'comercial_name',
        'tel1',
        'tel2',
        'email',
        'address',
        'giro',
        'nit',
        'tpersona',
        'legal',
        'birthday',
        'empresa',
        'companyselected',
        'contribuyente',
        'tipoContribuyente',
        'agente_retencion',
        'country',
        'departament',
        'municipio',
        'acteconomica',
        'ncr',
        'pasaporte',
        'name_contribuyente',
        'extranjero',
        'company_id',
        'address_id',
        'phone_id',
        'economicactivity_id',
        'user_id',
        'user_id_update'
    ];

    /**
     * Reglas de validación para prevenir duplicados
     */
    public static function getValidationRules($clientId = null, $companyId = null)
    {
        $rules = [
            'nit' => 'required|string|max:20',
            'tpersona' => 'required|in:N,J,E',
            'company_id' => 'required|exists:companies,id',
        ];

        if ($clientId) {
            // Para edición, excluir el cliente actual
            $rules['nit'] .= '|unique:clients,nit,' . $clientId . ',id,tpersona,' . request('tpersona') . ',company_id,' . $companyId;
            $rules['ncr'] = 'nullable|string|max:20|unique:clients,ncr,' . $clientId . ',id,tpersona,J,company_id,' . $companyId;
            $rules['pasaporte'] = 'nullable|string|max:20|unique:clients,pasaporte,' . $clientId . ',id,tpersona,E,company_id,' . $companyId;
        } else {
            // Para creación
            $rules['nit'] .= '|unique:clients,nit,NULL,id,tpersona,' . request('tpersona') . ',company_id,' . $companyId;
            $rules['ncr'] = 'nullable|string|max:20|unique:clients,ncr,NULL,id,tpersona,J,company_id,' . $companyId;
            $rules['pasaporte'] = 'nullable|string|max:20|unique:clients,pasaporte,NULL,id,tpersona,E,company_id,' . $companyId;
        }

        return $rules;
    }

    /**
     * Scope para buscar por DUI/NIT
     */
    public function scopeByDocument($query, $document, $tpersona, $companyId)
    {
        return $query->where('nit', $document)
                    ->where('tpersona', $tpersona)
                    ->where('company_id', $companyId);
    }

    /**
     * Scope para buscar por NCR
     */
    public function scopeByNcr($query, $ncr, $companyId)
    {
        return $query->where('ncr', $ncr)
                    ->where('tpersona', 'J')
                    ->where('company_id', $companyId);
    }

    /**
     * Scope para buscar por pasaporte
     */
    public function scopeByPasaporte($query, $pasaporte, $companyId)
    {
        return $query->where('pasaporte', $pasaporte)
                    ->where('tpersona', 'E')
                    ->where('company_id', $companyId);
    }
}
