<?php

namespace App\Http\Controllers;

use App\Models\Config;
use Illuminate\Http\Request;

class ConfigController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $configs = Config::join('companies AS co', 'config.company_id', '=', 'co.id')
        ->select('config.*','co.name AS name_company')
        ->get();

        return view('dtemh.config',array(
            'configs'=> $configs
        ));
    }

    public function getconfigid($id){
        $config = Config::join('companies AS co', 'config.company_id', '=', 'co.id')
        ->select('config.*','co.name AS name_company')
        ->where('config.id', '=', base64_decode($id))
        ->get();
        return response()->json($config);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            // Validar campos requeridos
            $request->validate([
                'company' => 'required|exists:companies,id',
                'version' => 'required|string',
                'typemodel' => 'required|string',
                'typetransmission' => 'required|string',
                'typecontingencia' => 'required|string',
                'passprivatekey' => 'required|string',
                'passpublickey' => 'required|string',
                'passmh' => 'required|string',
            ], [
                'company.required' => 'Debe seleccionar una empresa',
                'company.exists' => 'La empresa seleccionada no existe',
                'version.required' => 'El campo Versión es requerido',
                'typemodel.required' => 'El campo Tipo Modelo es requerido',
                'typetransmission.required' => 'El campo Tipo Transmisión es requerido',
                'typecontingencia.required' => 'El campo Tipo Contingencia es requerido',
                'passprivatekey.required' => 'El campo Contraseña Llave Privada es requerido',
                'passpublickey.required' => 'El campo Contraseña Llave Pública es requerido',
                'passmh.required' => 'El campo Contraseña MH es requerido',
            ]);

            // Verificar si ya existe una configuración para esta empresa
            $existingConfig = Config::where('company_id', $request->company)->first();
            if ($existingConfig) {
                return redirect()->route('config.index')->with('error', 'Ya existe una configuración para esta empresa');
            }

            $config = new Config();
            $config->company_id = $request->company;
            $config->version = $request->version;
            $config->ambiente = $request->ambiente;
            $config->typeModel = $request->typemodel;
            $config->typeTransmission = $request->typetransmission;
            $config->typeContingencia = $request->typecontingencia;
            $config->versionJson = $request->versionjson;
            $config->passPrivateKey = $request->passprivatekey;
            $config->passkeyPublic = $request->passpublickey;
            $config->passMH = $request->passmh;
            $config->codeCountry = "9300";
            $config->nameCountry = "EL SALVADOR";
            $config->dte_emission_enabled = $request->has('dte_emission_enabled');
            $config->dte_emission_notes = $request->dte_emission_notes;

            $config->save();

            return redirect()->route('config.index')->with('success', 'Configuración creada correctamente');
        } catch (\Exception $e) {
            \Log::error('Error al crear configuración: ' . $e->getMessage());
            return redirect()->route('config.index')->with('error', 'Error al crear la configuración: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Config  $config
     * @return \Illuminate\Http\Response
     */
    public function show(Config $config)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Config  $config
     * @return \Illuminate\Http\Response
     */
    public function edit(Config $config)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Config  $config
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        try {
            // Validar campos requeridos
            $request->validate([
                'idedit' => 'required|exists:config,id',
                'companyedit' => 'required|exists:companies,id',
                'versionedit' => 'required|string',
                'typemodeledit' => 'required|string',
                'typetransmissionedit' => 'required|string',
                'typecontingenciaedit' => 'required|string',
                'passprivatekeyedit' => 'required|string',
                'passpublickeyedit' => 'required|string',
                'passmhedit' => 'required|string',
            ], [
                'idedit.required' => 'ID de configuración requerido',
                'idedit.exists' => 'La configuración no existe',
                'companyedit.required' => 'Debe seleccionar una empresa',
                'companyedit.exists' => 'La empresa seleccionada no existe',
                'versionedit.required' => 'El campo Versión es requerido',
                'typemodeledit.required' => 'El campo Tipo Modelo es requerido',
                'typetransmissionedit.required' => 'El campo Tipo Transmisión es requerido',
                'typecontingenciaedit.required' => 'El campo Tipo Contingencia es requerido',
                'passprivatekeyedit.required' => 'El campo Contraseña Llave Privada es requerido',
                'passpublickeyedit.required' => 'El campo Contraseña Llave Pública es requerido',
                'passmhedit.required' => 'El campo Contraseña MH es requerido',
            ]);

            $config = Config::find($request->idedit);
            if (!$config) {
                return redirect()->route('config.index')->with('error', 'Configuración no encontrada');
            }

            $config->company_id = $request->companyedit;
            $config->version = $request->versionedit;
            $config->ambiente = $request->ambienteedit;
            $config->typeModel = $request->typemodeledit;
            $config->typeTransmission = $request->typetransmissionedit;
            $config->typeContingencia = $request->typecontingenciaedit;
            $config->versionJson = $request->versionjsonedit;
            $config->passPrivateKey = $request->passprivatekeyedit;
            $config->passkeyPublic = $request->passpublickeyedit;
            $config->passMH = $request->passmhedit;
            $config->dte_emission_enabled = $request->has('dte_emission_enabled_edit');
            $config->dte_emission_notes = $request->dte_emission_notes_edit;

            $config->save();

            return redirect()->route('config.index')->with('success', 'Configuración actualizada correctamente');
        } catch (\Exception $e) {
            \Log::error('Error al actualizar configuración: ' . $e->getMessage());
            return redirect()->route('config.index')->with('error', 'Error al actualizar la configuración: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Config  $config
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $config = Config::find(base64_decode($id));
        $config->delete();
        return response()->json(array(
             "res" => "1"
         ));
    }
}
