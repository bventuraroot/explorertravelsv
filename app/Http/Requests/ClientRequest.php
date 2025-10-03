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

        // Debug: Log de los datos recibidos
        \Log::info('ClientRequest validation', [
            'all_data' => $this->all(),
            'tpersona' => $this->input('tpersona'),
            'tpersonaedit' => $this->input('tpersonaedit'),
            'country' => $this->input('country'),
            'countryedit' => $this->input('countryedit'),
            'address' => $this->input('address'),
            'addressedit' => $this->input('addressedit'),
            'tel1' => $this->input('tel1'),
            'tel1edit' => $this->input('tel1edit')
        ]);

        return [
            'firstname' => 'required_if:tpersona,N|nullable|string|max:255',
            'firstnameedit' => 'required_if:tpersonaedit,N|nullable|string|max:255',
            'firstlastname' => 'required_if:tpersona,N|nullable|string|max:255',
            'firstlastnameedit' => 'required_if:tpersonaedit,N|nullable|string|max:255',
            'comercial_name' => 'required_if:tpersona,J|nullable|string|max:255',
            'comercial_nameedit' => 'required_if:tpersonaedit,J|nullable|string|max:255',
            'name_contribuyente' => 'required_if:tpersona,J|nullable|string|max:255',
            'name_contribuyenteedit' => 'required_if:tpersonaedit,J|nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'emailedit' => 'nullable|email|max:255',
            'tpersona' => 'required_without:tpersonaedit|in:N,J',
            'tpersonaedit' => 'required_without:tpersona|in:N,J',
            'nit' => [
                'nullable',
                'string',
                'max:20',
                function ($attribute, $value, $fail) use ($clientId) {
                    // Validar duplicados según tipo de persona
                    $tpersona = $this->input('tpersona');
                    $extranjero = $this->input('extranjero') === 'on' ? '1' : ($this->input('extranjeroedit') === 'on' ? '1' : '0');

                    // Validar que el DUI sea requerido para personas naturales NO extranjeras
                    if ($tpersona === 'N' && $extranjero === '0' && (empty($value) || $value === '')) {
                        $fail('El DUI es requerido para personas naturales no extranjeras.');
                    }

                    // Determinar qué campo de empresa usar
                    $companyId = $this->input('companyselected') ?: $this->input('companyselectededit');

                    if ($tpersona === 'N' && $extranjero === '0' && $value) {
                        // Para personas naturales no extranjeras, validar DUI (nit)
                        $query = Client::where('nit', $value)
                            ->where('tpersona', 'N')
                            ->where('extranjero', '0')
                            ->where('company_id', $companyId);

                        if ($clientId) {
                            $query->where('id', '!=', $clientId);
                        }

                        if ($query->exists()) {
                            $fail('El DUI ya está registrado para otra persona natural en esta empresa.');
                        }
                    } elseif ($tpersona === 'J' && $value) {
                        // Para personas jurídicas, validar NIT
                        $query = Client::where('nit', $value)
                            ->where('tpersona', 'J')
                            ->where('company_id', $companyId);

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
                'required_if:tpersona,J',
                'nullable',
                'string',
                'max:20',
                function ($attribute, $value, $fail) use ($clientId) {
                    // Solo validar NCR si es persona jurídica y el valor no es N/A
                    if ($this->input('tpersona') === 'J' && $value && $value !== 'N/A') {
                        // Determinar qué campo de empresa usar
                        $companyId = $this->input('companyselected') ?: $this->input('companyselectededit');

                        $query = Client::where('ncr', $value)
                            ->where('tpersona', 'J')
                            ->where('company_id', $companyId);

                        if ($clientId) {
                            $query->where('id', '!=', $clientId);
                        }

                        if ($query->exists()) {
                            $fail('El NCR ya está registrado para otra persona jurídica en esta empresa.');
                        }
                    }
                }
            ],
            'nitedit' => [
                'nullable',
                'string',
                'max:20',
                function ($attribute, $value, $fail) use ($clientId) {
                    // Validar duplicados según tipo de persona
                    $tpersona = $this->input('tpersonaedit');
                    $extranjero = $this->input('extranjero') === 'on' ? '1' : ($this->input('extranjeroedit') === 'on' ? '1' : '0');

                    // Validar que el DUI sea requerido para personas naturales NO extranjeras
                    if ($tpersona === 'N' && $extranjero === '0' && (empty($value) || $value === '')) {
                        $fail('El DUI es requerido para personas naturales no extranjeras.');
                    }

                    // Determinar qué campo de empresa usar
                    $companyId = $this->input('companyselected') ?: $this->input('companyselectededit');

                    if ($tpersona === 'N' && $extranjero === '0' && $value) {
                        // Para personas naturales no extranjeras, validar DUI (nit)
                        $query = Client::where('nit', $value)
                            ->where('tpersona', 'N')
                            ->where('extranjero', '0')
                            ->where('company_id', $companyId);

                        if ($clientId) {
                            $query->where('id', '!=', $clientId);
                        }

                        if ($query->exists()) {
                            $fail('El DUI ya está registrado para otra persona natural en esta empresa.');
                        }
                    } elseif ($tpersona === 'J' && $value) {
                        // Para personas jurídicas, validar NIT
                        $query = Client::where('nit', $value)
                            ->where('tpersona', 'J')
                            ->where('company_id', $companyId);

                        if ($clientId) {
                            $query->where('id', '!=', $clientId);
                        }

                        if ($query->exists()) {
                            $fail('El NIT ya está registrado para otra persona jurídica en esta empresa.');
                        }
                    }
                }
            ],
            'ncredit' => [
                'required_if:tpersonaedit,J',
                'nullable',
                'string',
                'max:20',
                function ($attribute, $value, $fail) use ($clientId) {
                    // Solo validar NCR si es persona jurídica y el valor no es N/A
                    if ($this->input('tpersonaedit') === 'J' && $value && $value !== 'N/A') {
                        // Determinar qué campo de empresa usar
                        $companyId = $this->input('companyselected') ?: $this->input('companyselectededit');

                        $query = Client::where('ncr', $value)
                            ->where('tpersona', 'J')
                            ->where('company_id', $companyId);

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
                'required_if:extranjero,on',
                'required_if:extranjeroedit,on',
                'nullable',
                'string',
                'max:20',
                function ($attribute, $value, $fail) use ($clientId) {
                    // Solo validar pasaporte si es extranjero y el valor no está vacío
                    $extranjero = $this->input('extranjero') === 'on' ? '1' : ($this->input('extranjeroedit') === 'on' ? '1' : '0');
                    if ($extranjero === '1' && $value) {
                        // Determinar qué campo de empresa usar
                        $companyId = $this->input('companyselected') ?: $this->input('companyselectededit');

                        $query = Client::where('pasaporte', $value)
                            ->where('extranjero', '1')
                            ->where('company_id', $companyId);

                        if ($clientId) {
                            $query->where('id', '!=', $clientId);
                        }

                        if ($query->exists()) {
                            $fail('El pasaporte ya está registrado para otro extranjero en esta empresa.');
                        }
                    }
                }
            ],
            'pasaporteedit' => [
                'required_if:extranjeroedit,on',
                'nullable',
                'string',
                'max:20',
                function ($attribute, $value, $fail) use ($clientId) {
                    // Solo validar pasaporte si es extranjero y el valor no está vacío
                    $extranjero = $this->input('extranjero') === 'on' ? '1' : ($this->input('extranjeroedit') === 'on' ? '1' : '0');
                    if ($extranjero === '1' && $value) {
                        // Determinar qué campo de empresa usar
                        $companyId = $this->input('companyselected') ?: $this->input('companyselectededit');

                        $query = Client::where('pasaporte', $value)
                            ->where('extranjero', '1')
                            ->where('company_id', $companyId);

                        if ($clientId) {
                            $query->where('id', '!=', $clientId);
                        }

                        if ($query->exists()) {
                            $fail('El pasaporte ya está registrado para otro extranjero en esta empresa.');
                        }
                    }
                }
            ],
            'companyselected' => 'required_without:companyselectededit|nullable|exists:companies,id',
            'companyselectededit' => 'required_without:companyselected|nullable|exists:companies,id',
            'economicactivity_id' => 'nullable|exists:economicactivities,id',
            'country' => 'required_without:countryedit|exists:countries,id',
            'countryedit' => 'required_without:country|exists:countries,id',
            'departament' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if ($value && $value !== '0' && $value !== '') {
                        if (!\App\Models\Department::where('id', $value)->exists()) {
                            $fail('El departamento seleccionado no existe.');
                        }
                    }
                }
            ],
            'departamentedit' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if ($value && $value !== '0' && $value !== '') {
                        if (!\App\Models\Department::where('id', $value)->exists()) {
                            $fail('El departamento seleccionado no existe.');
                        }
                    }
                }
            ],
            'municipio' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if ($value && $value !== '0' && $value !== '') {
                        if (!\App\Models\Municipality::where('id', $value)->exists()) {
                            $fail('El municipio seleccionado no existe.');
                        }
                    }
                }
            ],
            'municipioedit' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if ($value && $value !== '0' && $value !== '') {
                        if (!\App\Models\Municipality::where('id', $value)->exists()) {
                            $fail('El municipio seleccionado no existe.');
                        }
                    }
                }
            ],
            'address' => 'required_without:addressedit|string|max:500',
            'addressedit' => 'required_without:address|string|max:500',
            'tel1' => 'required_without:tel1edit|string|max:20',
            'tel1edit' => 'required_without:tel1|string|max:20',
            'tel2' => 'nullable|string|max:20',
            'tel2edit' => 'nullable|string|max:20',
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
            'tpersona.in' => 'El tipo de persona debe ser Natural o Jurídica.',
            'nit.required_if' => 'El DUI es requerido para personas naturales.',
            'ncr.required_if' => 'El NCR es requerido para personas jurídicas.',
            'pasaporte.required_if' => 'El pasaporte es requerido para extranjeros.',
            'companyselected.required_without' => 'La empresa es requerida.',
            'companyselected.exists' => 'La empresa seleccionada no existe.',
            'companyselectededit.required_without' => 'La empresa es requerida.',
            'companyselectededit.exists' => 'La empresa seleccionada no existe.',
            'economicactivity_id.exists' => 'La actividad económica seleccionada no existe.',
            'country.required' => 'El país es requerido.',
            'country.exists' => 'El país seleccionado no existe.',
            'departament.exists' => 'El departamento seleccionado no existe.',
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
            'nit' => 'DUI',
            'ncr' => 'NCR',
            'pasaporte' => 'pasaporte',
            'companyselected' => 'empresa',
            'companyselectededit' => 'empresa',
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
