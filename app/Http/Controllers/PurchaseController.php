<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $purchase = Purchase::join("typedocuments", "typedocuments.id", "=", "purchases.document_id")
        ->join("providers", "providers.id", "=", "purchases.provider_id")
        ->join("companies", "companies.id", "=", "purchases.company_id")
        ->leftJoin("email_purchase_imports", "email_purchase_imports.id", "=", "purchases.import_id")
        ->select("purchases.id AS idpurchase",
            "typedocuments.description AS namedoc",
            "purchases.number",
            "purchases.date",
            "purchases.exenta",
            "purchases.gravada",
            "purchases.iva",
            "purchases.otros",
            "purchases.total",
            "purchases.import_id",
            "email_purchase_imports.pdf_path AS pdf_path",
            "providers.razonsocial AS name_provider")
        ->get();
        return view('purchases.index', array(
            "purchases" => $purchase
        ));
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
        $tipoDte = \DB::table('typedocuments')->where('id', $request->document)->value('codemh');
        
        // Validar que NC/ND tengan compra relacionada
        if (in_array($tipoDte, ['05', '06']) && !$request->related_purchase_id) {
            return redirect()->back()
                ->with('error', 'Las Notas de Crédito y Débito de Proveedor deben indicar la Compra Original que afectan (campo ID Compra Relacionada).')
                ->withInput();
        }

        $purchase = new Purchase();
        $purchase->document_id = $request->document;
        $purchase->document_tipo_dte   = $tipoDte;
        $purchase->related_purchase_id = $request->related_purchase_id ?: null;
        $purchase->provider_id = $request->provider;
        $purchase->company_id = $request->company;
        $purchase->number = $request->number;
        $daterequest = strtotime($request->date);
        $new_date = date('Y-m-d', $daterequest);
        $purchase->date = $new_date;
        $purchase->exenta = $request->exenta;
        $purchase->gravada = $request->gravada;
        $purchase->iva = $request->iva;
        $purchase->contrns = $request->contrans;
        $purchase->fovial = $request->fovial;
        $purchase->iretenido = $request->iretenido;
        $purchase->otros = $request->others;
        $purchase->total = $request->total;
        $purchase->fingreso = date('Y-m-d');
        $purchase->periodo = $request->period;
        $purchase->user_id = $request->iduser;
        $purchase->codigo_generacion = $request->codigo_generacion ?: null;
        $purchase->sello_recepcion = $request->sello_recepcion ?: null;
        $purchase->save();
        return redirect()->route('purchase.index');
    }

    public function getpurchaseid($id){
        $purchase = Purchase::find(base64_decode($id));
        return response()->json($purchase);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Purchase  $purchase
     * @return \Illuminate\Http\Response
     */
    public function show(Purchase $purchase)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Purchase  $purchase
     * @return \Illuminate\Http\Response
     */
    public function edit(Purchase $purchase)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Purchase  $purchase
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Purchase $purchase)
    {
        $tipoDteEdit = \DB::table('typedocuments')->where('id', $request->documentedit)->value('codemh');
        
        // Validar que NC/ND tengan compra relacionada
        if (in_array($tipoDteEdit, ['05', '06']) && !$request->related_purchase_idedit) {
            return redirect()->back()
                ->with('error', 'Las Notas de Crédito y Débito de Proveedor deben indicar la Compra Original que afectan (campo ID Compra Relacionada).')
                ->withInput();
        }

        $purchase = Purchase::findOrFail($request->idedit);
        $purchase->document_id = $request->documentedit;
        $purchase->document_tipo_dte   = $tipoDteEdit;
        $purchase->related_purchase_id = $request->related_purchase_idedit ?: null;
        $purchase->provider_id = $request->provideredit;
        $purchase->company_id = $request->companyedit;
        $purchase->number = $request->numberedit;
        $daterequest = strtotime($request->dateedit);
        $new_date = date('Y-m-d', $daterequest);
        $purchase->date = $new_date;
        $purchase->exenta = $request->exentaedit;
        $purchase->gravada = $request->gravadaedit;
        $purchase->iva = $request->ivaedit;
        $purchase->contrns = $request->contransedit;
        $purchase->fovial = $request->fovialedit;
        $purchase->iretenido = $request->iretenidoedit;
        $purchase->otros = $request->othersedit;
        $purchase->total = $request->totaledit;
        $purchase->periodo = $request->periodedit;
        $purchase->codigo_generacion = $request->codigo_generacionedit ?: null;
        $purchase->sello_recepcion = $request->sello_recepcionedit ?: null;
        $purchase->save();
        return redirect()->route('purchase.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Purchase  $purchase
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
         //dd($id);
         $purchase = Purchase::find(base64_decode($id));
         if ($purchase) {
             if ($purchase->import_id) {
                 $import = \App\Models\EmailPurchaseImport::find($purchase->import_id);
                 if ($import) {
                     $import->update([
                         'purchase_id' => null,
                         'status'      => 'pending'
                     ]);
                 }
             }
             $purchase->delete();
         }
         return response()->json(array(
             "res" => "1"
         ));
    }
}
