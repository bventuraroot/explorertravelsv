<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Client;

class ClientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $clientId = $this->route('client') ? $this->route('client')->id : null;

        return [
            'firstname' => 'required_if:tpersona,N|string|max:255',
            'firstlastname' => 'required_if:tpersona,N|string|max:255',
            'comercial_name' => 'required_if:tpersona,J|string|max:255',
            'name_contribuyente' => 'required_if:tpersona,J|string|max:255',
            'email' => 'nullable|email|max:255',
            'tpersona' => 'required|in:N,J,E',
            'nit' => [
                'required',
                'string',
                'max:20',
                function ($attribute, $value, $fail) use ($clientId) {
                    // Validar duplicados según tipo de persona
                    $tpersona = $this->input('tpersona');

                    if ($tpersona === 'N') {
                        // Para personas naturales, validar DUI (nit)
                        $query = Client::where('nit', $value)
                            ->where('tpersona', 'N')
                            ->where('company_id', $this->input('companyselected'));

                        if ($clientId) {
                            $query->where('id', '!=', $clientId);
                        }

                        if ($query->exists()) {
                            $fail('El DUI ya está registrado para otra persona natural en esta empresa.');
                        }
                    } elseif ($tpersona === 'J') {
                        // Para personas jurídicas, validar NIT
                        $query = Client::where('nit', $value)
                            ->where('tpersona', 'J')
                            ->where('company_id', $this->input('companyselected'));

                        if ($clientId) {
                            $query->where('id', '!=', $clientId);
                        }

                        if ($query->exists()) {
                            $fail('El NIT ya está registrado para otra persona jurídica en esta empresa.');
                        }
                    }
                }
            ],
            'ncr' => [
                'nullable',
                'string',
                'max:20',
                function ($attribute, $value, $fail) use ($clientId) {
                    // Solo validar NCR si es persona jurídica y el valor no es N/A
                    if ($this->input('tpersona') === 'J' && $value && $value !== 'N/A') {
                        $query = Client::where('ncr', $value)
                            ->where('tpersona', 'J')
                            ->where('company_id', $this->input('companyselected'));

                        if ($clientId) {
                            $query->where('id', '!=', $clientId);
                        }

                        if ($query->exists()) {
                            $fail('El NCR ya está registrado para otra persona jurídica en esta empresa.');
                        }
                    }
                }
            ],
            'pasaporte' => [
                'nullable',
                'string',
                'max:20',
                function ($attribute, $value, $fail) use ($clientId) {
                    // Solo validar pasaporte si es extranjero y el valor no está vacío
                    if ($this->input('tpersona') === 'E' && $value) {
                        $query = Client::where('pasaporte', $value)
                            ->where('tpersona', 'E')
                            ->where('company_id', $this->input('companyselected'));

                        if ($clientId) {
                            $query->where('id', '!=', $clientId);
                        }

                        if ($query->exists()) {
                            $fail('El pasaporte ya está registrado para otro extranjero en esta empresa.');
                        }
                    }
                }
            ],
            'companyselected' => 'required|exists:companies,id',
            'economicactivity_id' => 'required|exists:economicactivities,id',
            'country' => 'required|exists:countries,id',
            'departament' => 'required|exists:departments,id',
            'municipio' => 'required|exists:municipalities,id',
            'address' => 'required|string|max:500',
            'tel1' => 'required|string|max:20',
            'tel2' => 'nullable|string|max:20',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'firstname.required_if' => 'El nombre es requerido para personas naturales.',
            'firstlastname.required_if' => 'El apellido es requerido para personas naturales.',
            'comercial_name.required_if' => 'El nombre comercial es requerido para personas jurídicas.',
            'name_contribuyente.required_if' => 'El nombre del contribuyente es requerido para personas jurídicas.',
            'tpersona.required' => 'El tipo de persona es requerido.',
            'tpersona.in' => 'El tipo de persona debe ser Natural, Jurídica o Extranjero.',
            'nit.required' => 'El DUI/NIT es requerido.',
            'companyselected.required' => 'La empresa es requerida.',
            'companyselected.exists' => 'La empresa seleccionada no existe.',
            'economicactivity_id.required' => 'La actividad económica es requerida.',
            'economicactivity_id.exists' => 'La actividad económica seleccionada no existe.',
            'country.required' => 'El país es requerido.',
            'country.exists' => 'El país seleccionado no existe.',
            'departament.required' => 'El departamento es requerido.',
            'departament.exists' => 'El departamento seleccionado no existe.',
            'municipio.required' => 'El municipio es requerido.',
            'municipio.exists' => 'El municipio seleccionado no existe.',
            'address.required' => 'La dirección es requerida.',
            'tel1.required' => 'El teléfono principal es requerido.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'firstname' => 'nombre',
            'firstlastname' => 'apellido',
            'comercial_name' => 'nombre comercial',
            'name_contribuyente' => 'nombre del contribuyente',
            'tpersona' => 'tipo de persona',
            'nit' => 'DUI/NIT',
            'ncr' => 'NCR',
            'pasaporte' => 'pasaporte',
            'companyselected' => 'empresa',
            'economicactivity_id' => 'actividad económica',
            'country' => 'país',
            'departament' => 'departamento',
            'municipio' => 'municipio',
            'address' => 'dirección',
            'tel1' => 'teléfono principal',
            'tel2' => 'teléfono secundario',
        ];
    }
}
