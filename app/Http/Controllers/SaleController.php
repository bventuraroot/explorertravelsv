<?php

namespace App\Http\Controllers;

use App\Models\Ambiente;
use App\Models\Client;
use App\Models\Company;
use App\Models\Dte;
use App\Models\Sale;
use App\Models\Config;
use App\Models\Salesdetail;
use DateTime;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use App\Mail\EnviarCorreo;
use App\Models\Correlativo;
use Illuminate\Http\JsonResponse;

class SaleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $id_user = auth()->user()->id;
        // Consultar el rol del usuario (asumiendo que el rol de admin tiene role_id = 1 y contabilidad role_id = 2)
        $rolQuery = "SELECT a.role_id FROM model_has_roles a WHERE a.model_id = ?";
        $rolResult = DB::select($rolQuery, [$id_user]);
        $isAdmin = !empty($rolResult) && ($rolResult[0]->role_id == 1 || $rolResult[0]->role_id == 2);

        $sales = Sale::join('typedocuments', 'typedocuments.id', '=', 'sales.typedocument_id')
            ->join('clients', 'clients.id', '=', 'sales.client_id')
            ->join('companies', 'companies.id', '=', 'sales.company_id')
            ->leftjoin('dte', 'dte.sale_id', '=', 'sales.id')
            ->where('sales.typesale', '<>', '3')
            ->select(
                'sales.*',
                'typedocuments.description AS document_name',
                'clients.firstname',
                'clients.firstlastname',
                'clients.name_contribuyente as nameClient',
                'clients.tpersona',
                'clients.email as mailClient',
                'companies.name AS company_name',
                'dte.tipoDte',
                'dte.estadoHacienda',
                'dte.id_doc',
                'dte.company_name',
                DB::raw('(SELECT dee.descriptionMessage FROM dte dee WHERE dee.id_doc_Ref2=sales.id) AS relatedSale'),
                DB::raw('(SELECT COUNT(*) FROM sales nc INNER JOIN typedocuments tdnc ON nc.typedocument_id = tdnc.id WHERE nc.doc_related = sales.id AND tdnc.type = "NCR" AND nc.state = 1) AS tiene_nota_credito'),
                DB::raw('(SELECT COUNT(*) FROM sales nd INNER JOIN typedocuments tdnd ON nd.typedocument_id = tdnd.id WHERE nd.doc_related = sales.id AND tdnd.type = "NDB" AND nd.state = 1) AS tiene_nota_debito'),
                DB::raw('(SELECT GROUP_CONCAT(CONCAT(dte_nc.id_doc, " (", nc.date, ")") SEPARATOR ", ") FROM sales nc INNER JOIN typedocuments tdnc ON nc.typedocument_id = tdnc.id INNER JOIN dte dte_nc ON dte_nc.sale_id = nc.id WHERE nc.doc_related = sales.id AND tdnc.type = "NCR" AND nc.state = 1) AS notas_credito'),
                DB::raw('(SELECT GROUP_CONCAT(CONCAT(dte_nd.id_doc, " (", nd.date, ")") SEPARATOR ", ") FROM sales nd INNER JOIN typedocuments tdnd ON nd.typedocument_id = tdnd.id INNER JOIN dte dte_nd ON dte_nd.sale_id = nd.id WHERE nd.doc_related = sales.id AND tdnd.type = "NDB" AND nd.state = 1) AS notas_debito'),
                DB::raw('CASE
                    WHEN sales.totalamount IS NULL OR sales.totalamount = 0 THEN
                        COALESCE((SELECT SUM(sd.nosujeta + sd.exempt + sd.pricesale + sd.detained13 - sd.renta - sd.detained)
                                 FROM salesdetails sd WHERE sd.sale_id = sales.id), 0)
                    ELSE sales.totalamount
                END AS calculated_total'));

        // Si no es admin, solo muestra los clientes ingresados por él
        if (!$isAdmin) {
            $sales->where('sales.user_id', $id_user);
        }

        // Aplicar filtros de fecha
        if ($request->filled('fecha_desde') && $request->filled('fecha_hasta')) {
            // Si se proporcionan ambas fechas, usar el rango especificado
            $sales->where('sales.date', '>=', $request->fecha_desde)
                  ->where('sales.date', '<=', $request->fecha_hasta);
        } elseif ($request->filled('fecha_desde')) {
            // Si solo se proporciona fecha desde, mostrar desde esa fecha hasta hoy
            $sales->where('sales.date', '>=', $request->fecha_desde)
                  ->where('sales.date', '<=', date('Y-m-d'));
        } elseif ($request->filled('fecha_hasta')) {
            // Si solo se proporciona fecha hasta, mostrar los últimos 7 días hasta esa fecha
            $fechaDesde = date('Y-m-d', strtotime($request->fecha_hasta . ' -7 days'));
            $sales->where('sales.date', '>=', $fechaDesde)
                  ->where('sales.date', '<=', $request->fecha_hasta);
        } else {
            // Si no se proporcionan fechas, mostrar solo los últimos 7 días por defecto
            $fechaHasta = date('Y-m-d');
            $fechaDesde = date('Y-m-d', strtotime('-7 days'));
            $sales->where('sales.date', '>=', $fechaDesde)
                  ->where('sales.date', '<=', $fechaHasta);
        }

        if ($request->filled('tipo_documento')) {
            $sales->where('sales.typedocument_id', $request->tipo_documento);
        }

        if ($request->filled('correlativo')) {
            $correlativo = $request->correlativo;
            $sales->where(function($query) use ($correlativo) {
                $query->where('sales.id', 'LIKE', "%{$correlativo}%")
                      ->orWhere('dte.id_doc', 'LIKE', "%{$correlativo}%");
            });
        }

        if ($request->filled('cliente_id')) {
            $sales->where('sales.client_id', $request->cliente_id);
        }

        // Obtener los datos para los filtros
        $tiposDocumento = DB::table('typedocuments')
            ->select('id', 'description')
            ->orderBy('description')
            ->get();

        $clientes = DB::table('clients')
            ->select('id', 'name_contribuyente', 'comercial_name', 'firstname', 'firstlastname', 'tpersona')
            ->orderBy('name_contribuyente')
            ->get();

        // Obtener la primera empresa (la que siempre se usa)
        $empresaPrincipal = DB::table('companies')
            ->select('id', 'name')
            ->orderBy('id')
            ->first();

        // Obtener las ventas filtradas ordenadas por fecha descendente
        $sales = $sales->orderBy('sales.date', 'desc')->get();

        return view('sales.index', compact('sales', 'tiposDocumento', 'clientes', 'empresaPrincipal'));
    }

    public function impdoc($corr)
    {
        return view('sales.impdoc', array("corr" => $corr));
    }

    /**
     * Nuevo endpoint POST para agregar detalle de venta de forma segura.
     * Acepta los mismos campos que el GET anterior y delega a savefactemp().
     */
    public function savefactempPost(Request $request)
    {
        $validated = $request->validate([
            'idsale' => 'required',
            'clientid' => 'required',
            'productid' => 'required',
            'cantidad' => 'required|numeric',
            'price' => 'required|numeric',
            'pricenosujeta' => 'required|numeric',
            'priceexenta' => 'required|numeric',
            'pricegravada' => 'required|numeric',
            'ivarete13' => 'required|numeric',
            'renta' => 'required|numeric',
            'ivarete' => 'required|numeric',
            'acuenta' => 'nullable|string',
            'fpago' => 'required',
            'fee' => 'nullable|numeric',
            'reserva' => 'nullable|string',
            'ruta' => 'nullable|string',
            'destino' => 'nullable|string',
            'linea' => 'nullable|string',
            'canal' => 'nullable|string',
            'description' => 'nullable|string',
            'tipoVenta' => 'nullable|string',
        ]);

        // Normalizar valores nulos
        $acuenta = $request->input('acuenta', '');
        $fee = $request->input('fee', 0);
        $reserva = $request->input('reserva', '');
        $ruta = $request->input('ruta', '');
        $destino = $request->input('destino', '');
        $linea = $request->input('linea', '');
        $canal = $request->input('canal', '');
        $description = $request->input('description', '');
        $tipoVenta = $request->input('tipoVenta', 'gravada');

        return $this->savefactemp(
            $request->idsale,
            $request->clientid,
            $request->productid,
            $request->cantidad,
            $request->price,
            $request->pricenosujeta,
            $request->priceexenta,
            $request->pricegravada,
            $request->ivarete13,
            $request->renta,
            $request->ivarete,
            $acuenta,
            $request->fpago,
            $fee,
            $reserva,
            $ruta,
            $destino,
            $linea,
            $canal,
            $description,
            $tipoVenta
        );
    }

    public function savefactemp($idsale, $clientid, $productid, $cantidad, $price, $pricenosujeta, $priceexenta, $pricegravada, $ivarete13, $renta, $ivarete, $acuenta, $fpago, $fee, $reserva, $ruta, $destino, $linea, $canal, $description, $tipoVenta = 'gravada')
    {
        // Limpiar parámetros que vienen como 'SIN_VALOR'
        $acuenta = ($acuenta === 'SIN_VALOR') ? '' : $acuenta;
        $reserva = ($reserva === 'SIN_VALOR') ? '' : $reserva;
        $ruta = ($ruta === 'SIN_VALOR') ? '' : $ruta;
        $destino = ($destino === 'SIN_VALOR') ? '' : $destino;
        $linea = ($linea === 'SIN_VALOR') ? '' : $linea;
        $canal = ($canal === 'SIN_VALOR') ? '' : $canal;
        $description = ($description === 'SIN_VALOR') ? '' : $description;

        // Log para debug
        Log::info('savefactemp llamado', [
            'tipoVenta' => $tipoVenta,
            'price' => $price,
            'pricenosujeta' => $pricenosujeta,
            'priceexenta' => $priceexenta,
            'pricegravada' => $pricegravada
        ]);

        DB::beginTransaction();

        try {
            $id_user = auth()->user()->id;
            $sale = Sale::find($idsale);
            $sale->client_id = $clientid;
            $sale->acuenta = $acuenta;
            $sale->waytopay = $fpago;
            $sale->save();
            // Lógica basada en el tipo de venta (como en Roma Copies)
            if ($tipoVenta === 'gravada') {
                // Venta gravada: calcular IVA normalmente
                $ivafac = round($pricegravada - ($pricegravada / 1.13), 2);
                $pricegravadafac = round($pricegravada / 1.13, 3);

                if ($pricegravada != "0.00") {
                    $priceunitariofac = round($pricegravadafac / $cantidad, 3);
                } else {
                    $priceunitariofac = round($price, 3);
                }

                if ($sale->typedocument_id == '8') {
                    // Sujeto excluido: precio con IVA, pero IVA = 0
                    $priceunitariofac = $price;
                    $pricegravadafac = $pricegravada;
                    $ivafac = 0.00;
                } elseif ($sale->typedocument_id == '3') {
                    // Crédito fiscal: el precio ya viene sin IVA, guardarlo tal cual
                    $priceunitariofac = round($price, 8); // Precio unitario sin IVA tal cual
                    $pricegravadafac = round($price * $cantidad, 8); // Subtotal sin IVA
                    $ivafac = round($pricegravadafac * 0.13, 8); // IVA = 13% del subtotal sin IVA
                }
            } elseif ($tipoVenta === 'exenta') {
                // Venta exenta: mantener precio unitario original, no va en gravadas
                $priceunitariofac = round($price, 3); // Precio unitario sin modificar
                $pricegravadafac = 0.00; // No va en gravadas
                $ivafac = 0.00; // No genera IVA
            } elseif ($tipoVenta === 'nosujeta' || $tipoVenta === 'no_sujeta') {
                // Venta no sujeta: mantener precio unitario original, no va en gravadas
                $priceunitariofac = round($price, 3); // Precio unitario sin modificar
                $pricegravadafac = 0.00; // No va en gravadas
                $ivafac = 0.00; // No genera IVA
            } else {
                // Por defecto, tratar como gravada
                $ivafac = round($pricegravada - ($pricegravada / 1.13), 2);
                $pricegravadafac = round($pricegravada / 1.13, 3);

                if ($pricegravada != "0.00") {
                    $priceunitariofac = round($pricegravadafac / $cantidad, 3);
                } else {
                    $priceunitariofac = round($price + $fee, 3);
                }

                if ($sale->typedocument_id == '8') {
                    // Sujeto excluido: precio con IVA, pero IVA = 0
                    $priceunitariofac = $price + $fee;
                    $pricegravadafac = $pricegravada;
                    $ivafac = 0.00;
                } elseif ($sale->typedocument_id == '3') {
                    // Crédito fiscal: el precio ya viene sin IVA, guardarlo tal cual
                    $priceunitariofac = round($price, 8); // Precio unitario sin IVA tal cual
                    $pricegravadafac = round($price * $cantidad, 8); // Subtotal sin IVA
                    $ivafac = round($pricegravadafac * 0.13, 8); // IVA = 13% del subtotal sin IVA
                }
            }
            //iva al fee - solo para ventas gravadas
            if ($tipoVenta === 'gravada') {
                $feesiniva = round($fee / 1.13, 8);
                $ivafee = round($fee - $feesiniva, 8);

                // Para crédito fiscal, recalcular el IVA incluyendo el fee
                if ($sale->typedocument_id == '3') {
                    // En CCF, el priceunit debe incluir el fee SIN IVA
                    $priceunitariofac = round(($price + $feesiniva), 8);
                    // Subtotal (gravadas) es unitario (sin IVA) incluyendo fee × cantidad
                    $pricegravadafac = round($priceunitariofac * $cantidad, 8);
                    // IVA del total sin IVA
                    $ivafac = round($pricegravadafac * 0.13, 8); // IVA = 13% del subtotal (producto + fee) sin IVA
                }
            } else {
                // Para ventas exentas/no sujetas, el fee no tiene IVA
                $feesiniva = round($fee, 8);
                $ivafee = 0.00;
            }

            $saledetails = new Salesdetail();
            $saledetails->sale_id = $idsale;
            $saledetails->product_id = $productid;
            $saledetails->amountp = $cantidad;
            // Guardar con precisión 8 (BD 12,8)
            $saledetails->priceunit = round($priceunitariofac, 8);
            $saledetails->pricesale = round($pricegravadafac, 8);

            // Asignar valores según el tipo de venta (como en Roma Copies)
            if ($tipoVenta === 'gravada') {
                $saledetails->nosujeta = 0.00;
                $saledetails->exempt = 0.00;
                $saledetails->detained13 = round($ivafac, 8);
            } elseif ($tipoVenta === 'exenta') {
                $saledetails->nosujeta = 0.00;
                $saledetails->exempt = $priceexenta; // El precio total ya incluye IVA
                $saledetails->detained13 = 0.00;
            } elseif ($tipoVenta === 'nosujeta' || $tipoVenta === 'no_sujeta') {
                $saledetails->nosujeta = $pricenosujeta; // El precio total ya incluye IVA
                $saledetails->exempt = 0.00;
                $saledetails->detained13 = 0.00;
            } else {
                // Por defecto, usar valores originales
                $saledetails->nosujeta = $pricenosujeta;
                $saledetails->exempt = $priceexenta;
                $saledetails->detained13 = round($ivafac, 2);
            }

            $saledetails->detained = $ivarete;
            $saledetails->renta = ($sale->typedocument_id != '8') ? round(0.00, 2) : round($renta * $cantidad, 2);
            $saledetails->fee = $feesiniva;
            $saledetails->feeiva = $ivafee;
            $saledetails->reserva = $reserva;
            $saledetails->ruta = $ruta;
            $saledetails->destino = $destino;
            $saledetails->linea = $linea;
            $saledetails->canal = $canal;
            $saledetails->user_id = $id_user;
            $saledetails->description = $description;


            $saledetails->save();


            DB::commit();
            return response()->json(array(
                "res" => "1",
                "idsaledetail" => $saledetails['id']
            ), 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'No se pudo procesar la venta', 'message' => $e->getMessage()], 500);
        }
    }

    public function newcorrsale($idempresa, $iduser, $iddoc): JsonResponse
    {
        DB::beginTransaction();

        try {
            // Obtener la última venta sin detalles y no usada recientemente
            $lastSale = DB::table('sales')
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('salesdetails')
                        ->whereRaw('sales.id = salesdetails.sale_id');
                })
                ->where('created_at', '<', now()->subMinutes(5)) // Solo ventas inactivas por más de 5 min
                ->lockForUpdate() // Bloquea para evitar que otro usuario lo use al mismo tiempo
                ->orderByDesc('id')
                ->first();
            //dd($lastSale);
            if ($lastSale) {
                // Si la última venta no tiene detalles y está inactiva, la reutilizamos
                $newId = $lastSale->id;
                DB::table('sales')->where('id', $lastSale->id)->delete();
            } else {
                // Si la última venta tiene detalles o está en uso, crear una nueva con auto-incremento
                $newId = null;
            }

            // Crear la nueva venta
            $corr = new Sale();
            $corr->id = $newId; // Si es null, Laravel usará auto-increment
            $corr->company_id = $idempresa;
            $corr->typedocument_id = $iddoc;
            $corr->user_id = $iduser;
            $corr->date = now();
            $corr->state = 1;
            $corr->typesale = 2;
            $corr->save();

            DB::commit();

            return response()->json(['sale_id' => $corr->id], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'No se pudo procesar la venta', 'message' => $e->getMessage()], 500);
        }
    }


    public function destroysaledetail($idsaledetail)
    {
        $saledetails = Salesdetail::find(base64_decode($idsaledetail));
        $saledetails->delete();
        return response()->json(array(
            "res" => "1"
        ));
    }

    public function getdatadocbycorr($corr)
    {
        $saleId = base64_decode($corr);

        // Intentar con join de iva primero
        $saledetails = Sale::join('companies', 'companies.id', '=', 'sales.company_id')
            ->join('iva', 'iva.company_id', '=', 'companies.id')
            ->leftJoin('clients', 'clients.id', '=', 'sales.client_id')
            ->select(
                'sales.*',
                'companies.*',
                'clients.id AS client_id',
                'clients.firstname AS client_firstname',
                'clients.secondname AS client_secondname',
                'clients.comercial_name AS comercial_name',
                'clients.tipoContribuyente AS client_contribuyente',
                'iva.valor AS iva',
                'iva.valor_entre AS iva_entre'
            )
            ->where('sales.id', '=', $saleId)
            ->get();

        // Si no hay resultados, intentar sin el join de iva
        if ($saledetails->count() == 0) {
            $saledetails = Sale::join('companies', 'companies.id', '=', 'sales.company_id')
                ->leftJoin('clients', 'clients.id', '=', 'sales.client_id')
                ->select(
                    'sales.*',
                    'companies.*',
                    'clients.id AS client_id',
                    'clients.firstname AS client_firstname',
                    'clients.secondname AS client_secondname',
                    'clients.comercial_name AS comercial_name',
                    'clients.tipoContribuyente AS client_contribuyente',
                    DB::raw('0.13 AS iva'),
                    DB::raw('0.13 AS iva_entre')
                )
                ->where('sales.id', '=', $saleId)
                ->get();
        }

        return response()->json($saledetails);
    }

    public function getdatadocbycorr2($corr)
    {
        $saledetails = Sale::join('companies', 'companies.id', '=', 'sales.company_id')
            ->join('addresses', 'addresses.id', '=', 'companies.address_id')
            ->join('countries', 'countries.id', '=', 'addresses.country_id')
            ->join('departments', 'departments.id', '=', 'addresses.department_id')
            ->join('municipalities', 'municipalities.id', '=', 'addresses.municipality_id')
            ->join('phones', 'phones.id', '=', 'companies.phone_id')
            ->join('clients', 'clients.id', '=', 'sales.client_id')
            ->join('typedocuments', 'typedocuments.id', '=', 'sales.typedocument_id')
            ->select(
                'sales.*',
                'companies.*',
                'companies.ncr AS NCR',
                'companies.nit AS NIT',
                'countries.name AS country_name',
                'departments.name AS department_name',
                'municipalities.name AS municipality_name',
                'addresses.reference AS address',
                'phones.*',
                'typedocuments.description AS document_name',
                'clients.id AS client_id',
                'clients.firstname AS client_firstname',
                'clients.secondname AS client_secondname',
                'clients.tipoContribuyente AS client_contribuyente',
                'sales.id AS corr',
                'clients.tpersona',
                'clients.name_contribuyente'
            )
            ->where('sales.id', '=', base64_decode($corr))
            ->get();
        return response()->json($saledetails);
    }

    public function createdocument($corr, $amount)
    {
        DB::beginTransaction();
        try {
            $amount = substr($amount, 1);
            $salesave = Sale::find(base64_decode($corr));
            $salesave->totalamount = $amount;
            $salesave->typesale = 1; // finalizar venta como en RomaCopies
            //buscar el correlativo actual
            $newCorr = Correlativo::join('typedocuments as tdoc', 'tdoc.type', '=', 'docs.id_tipo_doc')
                ->where('tdoc.id', '=', $salesave->typedocument_id)
                ->where('docs.id_empresa', '=', $salesave->company_id)
                ->select('docs.actual', 'docs.id')
                ->get();
            $salesave->nu_doc = $newCorr[0]->actual;
            $salesave->save();

            $idempresa = $salesave->company_id;
            $createdby = $salesave->user_id;
            //$company = Company::find($idempresa);
            //$config = Config::where('company_id', $idempresa)->first();
            //detalle factura
            $detailsbd = Salesdetail::where('sale_id', '=', base64_decode($corr))
                ->select(
                    DB::raw('SUM(nosujeta) nosujeta,
            SUM(exempt) exentas,
            SUM(CASE WHEN nosujeta > 0 OR exempt > 0 THEN 0 ELSE pricesale END) gravadas,
            SUM(nosujeta+exempt+pricesale) subtotalventas,
            0 descnosujeta,
            0 descexenta,
            0 desgravada,
            0 porcedesc,
            0 totaldesc,
            NULL tributos,
            SUM(nosujeta+exempt+pricesale) subtotal,
            SUM(detained) ivarete,
            0 ivarete,
            SUM(renta) rentarete,
            NULL pagos,
            SUM(CASE WHEN nosujeta > 0 OR exempt > 0 THEN 0 ELSE detained13 END) iva')
                )
                ->get();
            //detalle de montos de la factura
            $totalPagar = ($detailsbd[0]->nosujeta + $detailsbd[0]->exentas + $detailsbd[0]->gravadas + $detailsbd[0]->iva - ($detailsbd[0]->rentarete + $detailsbd[0]->ivarete));
            $totales = [
                "totalNoSuj" => (float)$detailsbd[0]->nosujeta,
                "totalExenta" => (float)$detailsbd[0]->exentas,
                "totalGravada" => (float)$detailsbd[0]->gravadas,
                "subTotalVentas" => round((float)($detailsbd[0]->subtotalventas), 8),
                "descuNoSuj" => $detailsbd[0]->descnosujeta,
                "descuExenta" => $detailsbd[0]->descexenta,
                "descuGravada" => $detailsbd[0]->desgravada,
                "porcentajeDescuento" => 0.00,
                "totalDescu" => $detailsbd[0]->totaldesc,
                "tributos" =>  null,
                "subTotal" => round((float)($detailsbd[0]->subtotal), 8),
                "ivaPerci1" => 0.00,
                "ivaRete1" => 0.00,
                "reteRenta" => round((float)$detailsbd[0]->rentarete, 8),
                "montoTotalOperacion" => round((float)($detailsbd[0]->subtotal), 8),
                //(float)$encabezado["montoTotalOperacion"],
                "totalNoGravado" => (float)0,
                "totalPagar" => (float)$totalPagar,
                "totalLetras" => numtoletras($totalPagar),
                "saldoFavor" => 0.00,
                "condicionOperacion" => $salesave->waytopay,
                "pagos" => null,
                "totalIva" => (float)$detailsbd[0]->iva
            ];
            //detalle del comprobante como url de firmador y mh etc
            $querydocumento = "SELECT
        a.id id_doc,
        b.`type` id_tipo_doc,
        docs.serie serie,
        docs.inicial inicial,
        docs.final final,
        docs.actual actual,
        docs.estado estado,
        a.company_id id_empresa,
        a.user_id hechopor,
        a.created_at fechacreacion,
        b.description NombreDocumento,
        c.name NombreUsuario,
        c.nit docUser,
        b.codemh tipodocumento,
        b.versionjson versionJson,
        e.url_credencial,
        e.url_envio,
        e.url_invalidacion,
        e.url_contingencia,
        e.url_firmador,
        d.typeTransmission tipogeneracion,
        e.cod ambiente,
        a.updated_at,
        1 aparece_ventas
        FROM sales a
        INNER JOIN typedocuments b ON a.typedocument_id = b.id
        INNER JOIN docs ON b.id = (SELECT t.id FROM typedocuments t WHERE t.type = docs.id_tipo_doc)
        INNER JOIN users c ON a.user_id = c.id
        LEFT JOIN config d ON a.company_id = d.company_id
        LEFT JOIN ambientes e ON d.ambiente = e.id
        WHERE a.id = " . base64_decode($corr) . "";
            $documento = DB::select(DB::raw($querydocumento));

            $queryproducto = "SELECT
        c.id id_producto,
        CASE
        WHEN b.description IS NOT NULL AND b.description != '' THEN b.description
        WHEN c.id = 9 THEN CONCAT(c.name, ' ', b.reserva, ' ', b.ruta)
        ELSE c.name
        END AS descripcion,
        b.amountp cantidad,
        (b.priceunit) precio_unitario,
        0 descuento,
        0 no_imponible,
        (b.pricesale+b.nosujeta+b.exempt) subtotal,
        b.pricesale gravadas,
        b.nosujeta no_sujetas,
        b.exempt exentas,
        b.detained13 iva,
        0 porcentaje_descuento,
        b.detained13 iva_calculado,
        b.renta renta_retenida,
        1 tipo_item,
        59 uniMedida
        FROM sales a
        INNER JOIN salesdetails b ON b.sale_id=a.id
        INNER JOIN products c ON b.product_id=c.id
        WHERE a.id=" . base64_decode($corr) . "";
            $producto = DB::select(DB::raw($queryproducto));
            $detalle = $producto;
            //data del emisor
            $queryemisor = "SELECT
        a.nit,
        CAST(REPLACE(REPLACE(a.ncr, '-', ''), ' ', '') AS UNSIGNED) AS ncr,
        a.name nombre,
        c.code codActividad,
        c.name descActividad,
        a.name nombreComercial,
        a.tipoEstablecimiento,
        f.code departamento,
        g.code municipio,
        d.reference direccion,
        e.phone telefono,
        NULL codEstableMH,
        NULL codEstable,
        NULL codPuntoVentaMH,
        NULL codPuntoVenta,
        a.email correo,
        b.passkeyPublic clavePublicaMH,
        b.passPrivateKey clavePrivadaMH,
        b.passMH claveApiMH
        FROM companies a
        LEFT JOIN config b ON a.id=b.company_id
        INNER JOIN economicactivities c ON a.economicactivity_id=c.id
        INNER JOIN addresses d ON a.address_id=d.id
        INNER JOIN phones e ON a.phone_id=e.id
        INNER JOIN departments f ON d.department_id=f.id
        INNER JOIN municipalities g ON d.municipality_id=g.id
        WHERE a.id=$idempresa";
            $emisor = DB::select(DB::raw($queryemisor));

            $querycliente = "SELECT
        a.id idcliente,
        IF(a.nit = '00000000-0', NULL, a.nit) as nit,
        IF(a.ncr = 'N/A' or a.ncr = '0' or a.ncr is null, NULL, CAST(REPLACE(REPLACE(a.ncr, '-', ''), ' ', '') AS UNSIGNED)) AS ncr,
        CASE
            WHEN a.tpersona = 'N' THEN CONCAT_WS(' ', a.firstname, a.secondname, a.firstlastname, a.secondlastname)
            WHEN a.tpersona = 'J' THEN COALESCE(a.name_contribuyente, '')
        END AS nombre,
        IF(b.code = 0, NULL, b.code) AS codActividad,
        IF(b.code = 0, NULL, b.name) AS descActividad,
        CASE
            WHEN a.tpersona = 'N' THEN CONCAT_WS(' ', a.firstname, a.secondname, a.firstlastname, a.secondlastname)
            WHEN a.tpersona = 'J' THEN COALESCE(a.name_contribuyente, '')
        END AS nombreComercial,
        a.email correo,
        f.code departamento,
        g.code municipio,
        c.reference direccion,
        p.phone telefono,
        1 id_tipo_contribuyente,
        a.tipoContribuyente id_clasificacion_tributaria,
        0 siempre_retiene,
        '36' tipoDocumento,
        a.nit numDocumento,
        '36'tipoDocumentoCliente,
        d.code codPais,
        d.name nombrePais,
        0 siempre_retiene_renta,
        a.extranjero,
        a.pasaporte,
        a.tpersona
    FROM clients a
    INNER JOIN economicactivities b ON a.economicactivity_id=b.id
    INNER JOIN addresses c ON a.address_id=c.id
    INNER JOIN phones p ON a.phone_id=p.id
    INNER JOIN countries d ON c.country_id=d.id
    LEFT JOIN departments f ON c.department_id=f.id
    LEFT JOIN municipalities g ON c.municipality_id=g.id
    WHERE a.id = $salesave->client_id";
            $cliente = DB::select(DB::raw($querycliente));

            // Validaciones previas a encolar DTE (alineadas a RomaCopies y requisitos de Explorer)
            $erroresValidacion = [];
            if (empty($emisor) || empty($emisor[0]->nit) || empty($emisor[0]->clavePrivadaMH)) {
                $erroresValidacion[] = 'Datos del emisor incompletos (NIT/clave privada MH)';
            }
            if (empty($cliente)) {
                $erroresValidacion[] = 'Datos del cliente no encontrados';
            }
            if ($salesave->client_id === null) {
                $erroresValidacion[] = 'Factura sin cliente asociado';
            }
            if (empty($detalle)) {
                $erroresValidacion[] = 'Factura sin detalle de productos';
            }
            if ($totalPagar <= 0) {
                $erroresValidacion[] = 'Total a pagar debe ser mayor a 0';
            }
            if (!empty($erroresValidacion)) {
                DB::rollBack();
                return response()->json([
                    'error' => 'VALIDATION_ERROR',
                    'message' => implode('; ', $erroresValidacion)
                ], 422);
            }

            $comprobante = [
                "emisor"    => $emisor,
                "documento" => $documento,
                "detalle"   => $detalle,
                "totales"   => $totales,
                "cliente"   => $cliente
            ];
            // Verificar si la emisión de DTE está habilitada para esta empresa
            if (Config::isDteEmissionEnabled($idempresa)) {
                $contingencia = [];
                $respuesta_hacienda = [];
                if ($documento[0]->tipogeneracion == 1) {
                    $contingencia = 1;
                    if ($contingencia) {
                        $respuesta_hacienda = $this->Enviar_Hacienda($comprobante, "01");
                        //dd($respuesta_hacienda);
                        if ($respuesta_hacienda["codEstado"] == "03") {
                            // CREAR DTE CON ESTADO RECHAZADO Y REGISTRAR ERROR
                            $dtecreate = $this->crearDteConError($documento, $emisor, $respuesta_hacienda, $comprobante, $salesave, $createdby);
                            // REGISTRAR ERROR EN LA TABLA dte_errors
                            $this->registrarErrorDte($dtecreate, 'hacienda', 'HACIENDA_REJECTED', $respuesta_hacienda["descripcionMsg"] ?? 'Documento rechazado por Hacienda', [
                                'codigoMsg' => $respuesta_hacienda["codigoMsg"] ?? null,
                                'observacionesMsg' => $respuesta_hacienda["observacionesMsg"] ?? null,
                                'sale_id' => base64_decode($corr)
                            ]);

                            return json_encode($respuesta_hacienda);
                        }
                        $comprobante["json"] = $respuesta_hacienda;
                    }
                }
                //dd($respuesta_hacienda);
                //create respuesta de MH
                $dtecreate = new Dte();
                $dtecreate->versionJson = $documento[0]->versionJson;
                $dtecreate->ambiente_id = $documento[0]->ambiente;
                $dtecreate->tipoDte = $documento[0]->tipodocumento;
                $dtecreate->tipoModelo = $documento[0]->tipogeneracion;
                $dtecreate->tipoTransmision = 1;
                $dtecreate->tipoContingencia = "null";
                $dtecreate->idContingencia = "null";
                $dtecreate->nameTable = 'Sales';
                $dtecreate->company_id = $idempresa;
                $dtecreate->company_name = $emisor[0]->nombreComercial;
                $dtecreate->id_doc = $respuesta_hacienda["identificacion"]["numeroControl"];
                $dtecreate->codTransaction = "01";
                $dtecreate->desTransaction = "Emision";
                $dtecreate->type_document = $documento[0]->tipodocumento;
                $dtecreate->id_doc_Ref1 = "null";
                $dtecreate->id_doc_Ref2 = "null";
                $dtecreate->type_invalidacion = "null";
                $dtecreate->codEstado = $respuesta_hacienda["codEstado"];
                $dtecreate->Estado = $respuesta_hacienda["estado"];
                $dtecreate->codigoGeneracion = $respuesta_hacienda["codigoGeneracion"];
                $dtecreate->selloRecibido = $respuesta_hacienda["selloRecibido"];
                $dtecreate->fhRecibido = $respuesta_hacienda["fhRecibido"];
                $dtecreate->estadoHacienda = $respuesta_hacienda["estadoHacienda"];
                $dtecreate->json = json_encode($comprobante);
                $dtecreate->nSends = $respuesta_hacienda["nuEnvios"];
                $dtecreate->codeMessage = $respuesta_hacienda["codigoMsg"];
                $dtecreate->claMessage = $respuesta_hacienda["clasificaMsg"];
                $dtecreate->descriptionMessage = $respuesta_hacienda["descripcionMsg"];
                $dtecreate->detailsMessage = $respuesta_hacienda["observacionesMsg"];
                $dtecreate->sale_id = base64_decode($corr);
                $dtecreate->created_by = $documento[0]->NombreUsuario;
                $dtecreate->save();

                // Envío automático de correo después de guardar el DTE
                //$this->enviarCorreoAutomatico(base64_decode($corr), $dtecreate);
            } else {
                // DTE deshabilitado - solo guardar la venta sin emisión
                Log::info("DTE deshabilitado para empresa ID: {$idempresa}. Venta guardada sin emisión DTE.");

                // Envío automático de correo para ventas sin DTE
                //$this->enviarCorreoAutomatico(base64_decode($corr), null);

            }

            // update correlativo como en RomaCopies
            $updateCorr = Correlativo::find($newCorr[0]->id);
            $updateCorr->actual = ($updateCorr->actual + 1);
            $updateCorr->save();
            $salesave = Sale::find(base64_decode($corr));
            $salesave->json = json_encode($comprobante);
            $salesave->save();
            $exit = 1;
            DB::commit();
            return response()->json(array(
               "res" => $exit
            ));
        } catch (\Exception $e) {
            DB::rollBack();

            // REGISTRAR ERROR EN LA TABLA dte_errors SI EXISTE UN DTE
            if (isset($dtecreate) && $dtecreate->id) {
                $this->registrarErrorDte($dtecreate, 'sistema', 'SYSTEM_ERROR', $e->getMessage(), [
                    'sale_id' => base64_decode($corr),
                    'exception' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }

            return response()->json(['error' => 'No se pudo procesar el documento', 'message' => $e->getMessage()], 500);
        }
    }

    public function getdetailsdoc($corr)
    {
        $saledetails = Salesdetail::leftJoin('products', 'products.id', '=', 'salesdetails.product_id')
            ->select(
                'salesdetails.*',
                DB::raw('CASE
                    WHEN salesdetails.reserva IS NOT NULL AND salesdetails.ruta IS NOT NULL
                    THEN CONCAT(products.name, " - ", salesdetails.reserva, " - ", salesdetails.ruta)
                    WHEN salesdetails.reserva IS NOT NULL
                    THEN CONCAT(products.name, " - ", salesdetails.reserva)
                    ELSE products.name
                END as product_name')
            )
            ->where('sale_id', '=', base64_decode($corr))
            ->get();


        return response()->json($saledetails);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('sales.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Sale  $Sale
     * @return \Illuminate\Http\Response
     */
    public function show(Sale $Sale)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Sale  $Sale
     * @return \Illuminate\Http\Response
     */
    public function edit(Sale $Sale)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Sale  $Sale
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Sale $Sale)
    {
        //
    }

    public function ncr($id_sale)
    {
        // La nota de crédito SOLO puede venir del formulario
        if (!request()->isMethod('post') || !request()->has('productos')) {
            return redirect()->back()
                ->with('error', 'Acceso no autorizado. La nota de crédito debe crearse desde el formulario.');
        }

        // Validar que el ID de venta sea válido
        if (!$id_sale || !is_numeric($id_sale)) {
            return redirect()->back()
                ->with('error', 'ID de venta inválido.');
        }

        DB::beginTransaction();
        try {
            $request = request();

            // Obtener la venta original
            $saleOriginal = Sale::where('id', $id_sale)
                ->where('typesale', 1)
                ->where('state', 1)
                ->firstOrFail();
            $idempresa = $saleOriginal->company_id;
            $createdby = $saleOriginal->user_id;

            // Verificar modificaciones, calcular total y crear detalles
            $hayModificaciones = false;
            $totalAmount = 0;
            $productosOriginales = $saleOriginal->salesdetails->keyBy('product_id');
            $detallesModificados = [];

            foreach ($request->productos as $productoData) {
                if (!isset($productoData['incluir']) || !$productoData['incluir']) {
                    continue;
                }

                // Validar datos del producto (para NCR solo requerimos product_id y cantidad a disminuir)
                if (!isset($productoData['product_id']) || !isset($productoData['cantidad'])) {
                    DB::rollBack();
                    return redirect()->back()
                        ->with('error', 'Datos de producto incompletos. Se requiere producto y cantidad a disminuir.');
                }

                $productoOriginal = $productosOriginales->get($productoData['product_id']);
                if (!$productoOriginal) {
                    DB::rollBack();
                    return redirect()->back()
                        ->with('error', 'Producto no encontrado en la venta original.');
                }

                $cantidadOriginal = $productoOriginal->amountp;
                $precioOriginal = $productoOriginal->priceunit;
                $cantidadDisminuir = isset($productoData['cantidad']) ? (float)$productoData['cantidad'] : 0; // cantidad a disminuir
                $precioNuevo = isset($productoData['precio']) ? (float)$productoData['precio'] : $precioOriginal; // posible nuevo precio (para descuento de precio)

                // Validar entradas
                if (!is_numeric($cantidadDisminuir) || $cantidadDisminuir < 0) {
                    DB::rollBack();
                    return redirect()->back()
                        ->with('error', 'Cantidad a disminuir inválida para el producto.');
                }

                if (!is_numeric($precioNuevo) || $precioNuevo < 0) {
                    DB::rollBack();
                    return redirect()->back()
                        ->with('error', 'Precio inválido para el producto.');
                }

                // Validar que no exceda la cantidad original
                if ($cantidadDisminuir > $cantidadOriginal) {
                    DB::rollBack();
                    return redirect()->back()
                        ->with('error', 'La cantidad a disminuir no puede ser mayor a la cantidad del documento original.');
                }

                // Para NCR: el precio no puede aumentar (solo igual o menor)
                if ($precioNuevo > $precioOriginal) {
                    DB::rollBack();
                    return redirect()->back()
                        ->with('error', 'En una Nota de Crédito el precio no puede ser mayor al precio original.');
                }

                // Calcular diferencias
                $diferenciaCantidad = $cantidadDisminuir; // unidades a restar al original
                $diferenciaPrecio = max(0, $precioOriginal - $precioNuevo); // descuento unitario
                $cantidadBasePrecio = max(0, $cantidadOriginal - $cantidadDisminuir); // unidades restantes a las que aplica descuento de precio

                // Si no hay cambios en cantidad ni precio, saltar
                if ($diferenciaCantidad == 0 && $diferenciaPrecio == 0) {
                    continue;
                }

                $hayModificaciones = true;

                // Calcular subtotal diferencia total
                $subtotalDiferencia = ($diferenciaCantidad * $precioOriginal) + ($cantidadBasePrecio * $diferenciaPrecio);

                $tipoVenta = $productoData['tipo_venta'] ?? 'gravada';

                // Calcular total según el tipo de venta
                if ($tipoVenta === 'gravada') {
                    $totalAmount += $subtotalDiferencia + ($subtotalDiferencia * 0.13);
                } else {
                    // Para exenta y no_sujeta, solo se suma el subtotal sin IVA
                    $totalAmount += $subtotalDiferencia;
                }

                // Preparar datos del detalle para crear después
                $detallesModificados[] = [
                    'productoData' => $productoData,
                    'productoOriginal' => $productoOriginal,
                    'cantidadOriginal' => $cantidadOriginal,
                    'precioOriginal' => $precioOriginal,
                    'cantidadDisminuir' => $diferenciaCantidad,
                    'diferenciaPrecio' => $diferenciaPrecio,
                    'cantidadBasePrecio' => $cantidadBasePrecio,
                    'tipoVenta' => $tipoVenta
                ];
            }

            if (!$hayModificaciones) {
                DB::rollBack();
                return redirect()->back()
                    ->with('error', 'No se detectaron modificaciones en los productos. No se puede crear una nota de crédito sin cambios.');
            }
            // Crear la nota de crédito con solo las modificaciones
            $nfactura = new Sale();
            $nfactura->client_id = $saleOriginal->client_id;
            $nfactura->company_id = $saleOriginal->company_id;
            $nfactura->doc_related = $id_sale; // ID de la venta original
            $nfactura->typesale = 1; // Venta confirmada
            $nfactura->date = now();
            $nfactura->user_id = Auth::id();
            $nfactura->waytopay = $saleOriginal->waytopay ?? 1;
            $nfactura->state = 1; // Activa/Confirmada
            $nfactura->state_credit = 0;
            $nfactura->motivo = $request->motivo ?? 'Modificación de productos';
            $nfactura->acuenta = $saleOriginal->acuenta ?? 0;

            // Obtener el typedocument_id para notas de crédito (tipo NCR)
            $typedocumentNCR = \App\Models\Typedocument::where('type', 'NCR')
                ->where('company_id', $saleOriginal->company_id)
                ->first();
            if (!$typedocumentNCR) {
                DB::rollBack();
                return redirect()->back()
                    ->with('error', 'No se encontró configuración de tipo de documento NCR para esta empresa.');
            }

            $nfactura->typedocument_id = $typedocumentNCR->id;

            // Obtener y asignar el número de documento del correlativo
            $newCorr = Correlativo::join('typedocuments as tdoc', 'tdoc.type', '=', 'docs.id_tipo_doc')
                ->where('tdoc.id', '=', $typedocumentNCR->id)
                ->where('docs.id_empresa', '=', $nfactura->company_id)
                ->select('docs.actual', 'docs.id')
                ->first();

            if (!$newCorr) {
                DB::rollBack();
                return redirect()->back()
                    ->with('error', 'No se encontró correlativo para el tipo de documento NCR.');
            }

            $nfactura->nu_doc = $newCorr->actual;
            $nfactura->totalamount = $totalAmount;
            $nfactura->save();

            // Actualizar correlativo después de guardar la nota de crédito
            DB::table('docs')->where('id', $newCorr->id)->increment('actual');

            // Crear detalles usando los datos ya preparados
            foreach ($detallesModificados as $detalleData) {
                $productoData = $detalleData['productoData'];
                $productoOriginal = $detalleData['productoOriginal'];
                $cantidadOriginal = $detalleData['cantidadOriginal'];
                $precioOriginal = $detalleData['precioOriginal'];
                $cantidadDisminuir = $detalleData['cantidadDisminuir'];
                $diferenciaPrecio = $detalleData['diferenciaPrecio'];
                $cantidadBasePrecio = $detalleData['cantidadBasePrecio'];
                $tipoVenta = $detalleData['tipoVenta'];

                // Crear UNA sola línea por producto modificado
                $cantidadNC = $cantidadDisminuir; // cantidad a disminuir del formulario
                $precioNC = $precioOriginal; // precio original
                $subtotal = $cantidadNC * $precioNC;

                $detalle = new Salesdetail();
                $detalle->sale_id = $nfactura->id;
                $detalle->product_id = $productoData['product_id'];
                $detalle->amountp = $cantidadNC;
                $detalle->priceunit = $precioNC;
                $detalle->description = $productoOriginal->description;
                $detalle->renta = 0; // Campo requerido
                $detalle->fee = 0; // Campo requerido
                $detalle->feeiva = 0; // Campo requerido
                $detalle->reserva = 0; // Campo requerido
                $detalle->ruta = $productoOriginal->ruta ?? null;
                $detalle->destino = $productoOriginal->destino ?? null;
                $detalle->linea = $productoOriginal->linea ?? null;
                $detalle->canal = $productoOriginal->canal ?? null;
                $detalle->user_id = Auth::id();

                if ($tipoVenta === 'gravada') {
                    $detalle->pricesale = $subtotal;
                    $detalle->detained13 = $subtotal * 0.13;
                    $detalle->detained = null; // Campo nullable
                    $detalle->exempt = 0;
                    $detalle->nosujeta = 0;
                } elseif ($tipoVenta === 'exenta') {
                    $detalle->pricesale = 0;
                    $detalle->detained13 = 0;
                    $detalle->detained = null; // Campo nullable
                    $detalle->exempt = $subtotal;
                    $detalle->nosujeta = 0;
                } elseif ($tipoVenta === 'no_sujeta') {
                    $detalle->pricesale = 0;
                    $detalle->detained13 = 0;
                    $detalle->detained = null; // Campo nullable
                    $detalle->exempt = 0;
                    $detalle->nosujeta = $subtotal;
                } else {
                    // Por defecto, tratar como gravada
                    $detalle->pricesale = $subtotal;
                    $detalle->detained13 = $subtotal * 0.13;
                    $detalle->detained = null; // Campo nullable
                    $detalle->exempt = 0;
                    $detalle->nosujeta = 0;
                }
                $detalle->save();
            }
            // Verificar si DTE está habilitado para esta empresa

            if (!Config::isDteEmissionEnabled($idempresa)) {
                DB::commit();
                if (request()->ajax()) {
                    return response('0');
                }
                return redirect()->route('credit-notes.index')
                    ->with('success', 'Nota de crédito creada exitosamente. DTE deshabilitado para esta empresa.');
            }

            // Obtener información básica de la venta original
        $qfactura = "SELECT
                        s.id id_factura,
                        s.totalamount total_venta,
                        s.company_id id_empresa,
                        s.client_id id_cliente,
                        s.user_id id_usuario,
                        clie.nit,
                        clie.email email_cliente,
                        clie.tpersona tipo_personeria,
                        CASE
                                WHEN clie.tpersona = 'N' THEN CONCAT_WS(' ', clie.firstname, clie.secondname, clie.firstlastname, clie.secondlastname)
                                WHEN clie.tpersona = 'J' THEN COALESCE(clie.name_contribuyente, '')
                            END AS nombre_cliente,
                        dte.json,
                        dte.tipoModelo,
                        dte.fhRecibido,
                        dte.codigoGeneracion,
                        dte.selloRecibido,
                        dte.tipoDte
                        FROM sales s
                        INNER JOIN clients clie ON s.client_id=clie.id
                        LEFT JOIN dte ON dte.sale_id=s.id
                        WHERE s.id = $id_sale";
        $factura = DB::select(DB::raw($qfactura));
            // Obtener información del tipo de documento NCR
        $qdoc = "SELECT
                a.id id_doc,
                a.`type` id_tipo_doc,
                docs.serie serie,
                docs.inicial inicial,
                docs.final final,
                docs.actual actual,
                docs.estado estado,
                a.company_id id_empresa,
                    " . Auth::id() . " hechopor,
                    NOW() fechacreacion,
                a.description NombreDocumento,
                    '" . Auth::user()->name . "' NombreUsuario,
                    '" . (Auth::user()->nit ?? '00000000-0') . "' docUser,
                a.codemh tipodocumento,
                a.versionjson versionJson,
                e.url_credencial,
                e.url_envio,
                e.url_invalidacion,
                e.url_contingencia,
                e.url_firmador,
                d.typeTransmission tipogeneracion,
                e.cod ambiente,
                    NOW() updated_at,
                1 aparece_ventas
                FROM typedocuments a
                INNER JOIN docs ON a.id = (SELECT t.id FROM typedocuments t WHERE t.type = docs.id_tipo_doc)
                INNER JOIN config d ON a.company_id=d.company_id
                INNER JOIN ambientes e ON d.ambiente=e.id
                    WHERE a.`type`= 'NCR' AND a.company_id = $idempresa";
        $doc = DB::select(DB::raw($qdoc));
            // Obtener detalles de la nota de crédito (solo las modificaciones)
            $detalle = $this->construirDetalleNotaCredito($nfactura->id);
        $versionJson = $doc[0]->versionJson;
        $ambiente = $doc[0]->ambiente;
        $tipoDte = $doc[0]->tipodocumento;
        $numero = $doc[0]->actual;

            // Obtener totales de la nota de crédito
            $totalesNC = $this->calcularTotalesNotaCredito($nfactura->id);

            // Construir array $totales con la estructura correcta
            $totales = [
                "totalNoSuj" => $totalesNC['nosujetas'],
                "totalExenta" => $totalesNC['exentas'],
                "totalGravada" => $totalesNC['gravadas'],
                "subTotalVentas" => $totalesNC['subtotal'],
                "totalIva" => $totalesNC['iva'],
                "totalPagar" => $totalesNC['total'],
                "totalLetras" => numtoletras($totalesNC['total']),
                "condicionOperacion" => $nfactura->waytopay ?? '01',
                "descuNoSuj" => 0,
                "descuExenta" => 0,
                "descuGravada" => 0,
                "totalDescu" => 0,
                "ivaRete1" => 0,
                "reteRenta" => 0,
                "saldoFavor" => 0
            ];

            // Construir documento fiscal para nota de crédito
            $dteOriginal = $saleOriginal->dte;

        $documento[0] = [
                "tipodocumento"             => $doc[0]->tipodocumento,
                "nu_doc"                    => $numero,
                "tipo_establecimiento"      => "1",
                "version"                   => $doc[0]->versionJson,
                "ambiente"                  => $doc[0]->ambiente,
                "tipoDteOriginal"           => $dteOriginal->tipoDte ?? '01',
                "tipoGeneracionOriginal"    => $dteOriginal->tipoModelo ?? 1,
                "codigoGeneracionOriginal"  => $dteOriginal->codigoGeneracion ?? '',
                "selloRecibidoOriginal"     => $dteOriginal->selloRecibido ?? '',
                "numeroOriginal"            => $dteOriginal->codigoGeneracion ?? '',
                "fecEmiOriginal"            => $dteOriginal ? date('Y-m-d', strtotime($dteOriginal->fhRecibido)) : date('Y-m-d'),
                "total_iva"                 => $totalesNC['iva'],
            "tipoDocumento"             => "",
                "numDocumento"              => $factura[0]->nit ?? '',
                "nombre"                    => $factura[0]->nombre_cliente ?? '',
            "versionjson"               => $doc[0]->versionJson,
                "id_empresa"                => $saleOriginal->company_id,
                "url_credencial"            => $doc[0]->url_credencial,
                "url_envio"                 => $doc[0]->url_envio,
                "url_firmador"              => $doc[0]->url_firmador,
            "nuEnvio"                   => 1,
                "condiciones"               => "1",
                "total_venta"               => $totalesNC['total'],
                "tot_gravado"               => $totalesNC['gravadas'],
                "tot_nosujeto"              => $totalesNC['nosujetas'],
                "tot_exento"                => $totalesNC['exentas'],
                "subTotalVentas"            => $totalesNC['subtotal'],
            "descuNoSuj"                => 0.00,
            "descuExenta"               => 0.00,
            "descuGravada"              => 0.00,
            "totalDescu"                => 0.00,
                "subTotal"                  => $totalesNC['subtotal'],
            "ivaPerci1"                 => 0.00,
                "ivaRete1"                  => 0.00,
            "reteRenta"                 => 0.00,
                //"total_letras"              => numeroletras($totalesNC['total']),
                "total_letras"              => numtoletras($totalesNC['total']),
                "totalPagar"                => $totalesNC['total'],
                "NombreUsuario"             => Auth::user()->name,
                "docUser"                   => Auth::user()->nit ?? ''
            ];
            // Obtener datos del cliente
        $qcliente = "SELECT
                                a.id id_cliente,
                            CASE
                                WHEN a.tpersona = 'N' THEN CONCAT_WS(' ', a.firstname, a.secondname, a.firstlastname, a.secondlastname)
                                WHEN a.tpersona = 'J' THEN COALESCE(a.name_contribuyente, '')
                            END AS nombre_cliente,
                                p.phone telefono_cliente,
                                a.email email_cliente,
                                c.reference direccion_cliente,
                                1 status_cliente,
                                a.created_at date_added,
                                CAST(REPLACE(REPLACE(a.ncr, '-', ''), ' ', '') AS UNSIGNED) AS ncr,
                            a.nit,
                            a.tpersona tipo_personeria,
                            g.code municipio,
                            f.code departamento,
                            a.company_id id_empresa,
                            NULL hechopor,
                            a.tipoContribuyente id_clasificacion_tributaria,
                            CASE
                                WHEN a.tipoContribuyente = 'GRA' THEN 'GRANDES CONTRIBUYENTES'
                                WHEN a.tipoContribuyente = 'MED' THEN 'MEDIANOS CONTRIBUYENTES'
                                WHEN a.tipoContribuyente = 'PEQU'  THEN 'PEQUEÑOS CONTRIBUYENTES'
                                WHEN a.tipoContribuyente = 'OTR'  THEN 'OTROS CONTRIBUYENTES'
                            END AS descripcion,
                            0 siempre_retiene,
                            1 id_tipo_contribuyente,
                            b.id giro,
                            b.code codActividad,
                            b.name descActividad,
                            a.comercial_name nombre_comercial
                        FROM clients a
                        INNER JOIN economicactivities b ON a.economicactivity_id=b.id
                        INNER JOIN addresses c ON a.address_id=c.id
                        INNER JOIN phones p ON a.phone_id=p.id
                        INNER JOIN countries d ON c.country_id=d.id
                        INNER JOIN departments f ON c.department_id=f.id
                        INNER JOIN municipalities g ON c.municipality_id=g.id
                        WHERE a.id = " . $factura[0]->id_cliente . "";
        $cliente = DB::select(DB::raw($qcliente));

            // Obtener datos del emisor (empresa)
        $queryemisor = "SELECT
                        a.nit,
                        CAST(REPLACE(REPLACE(a.ncr, '-', ''), ' ', '') AS UNSIGNED) AS ncr,
                        a.name nombre,
                        c.code codActividad,
                        c.name descActividad,
                        a.name nombreComercial,
                        a.tipoEstablecimiento,
                        f.code departamento,
                        g.code municipio,
                        d.reference direccion,
                        e.phone telefono,
                        NULL codEstableMH,
                        NULL codEstable,
                        NULL codPuntoVentaMH,
                        NULL codPuntoVenta,
                        a.email correo,
                        b.passkeyPublic clavePublicaMH,
                        b.passPrivateKey clavePrivadaMH,
                        b.passMH claveApiMH
                        FROM companies a
                        INNER JOIN config b ON a.id=b.company_id
                        INNER JOIN economicactivities c ON a.economicactivity_id=c.id
                        INNER JOIN addresses d ON a.address_id=d.id
                        INNER JOIN phones e ON a.phone_id=e.id
                        INNER JOIN departments f ON d.department_id=f.id
                        INNER JOIN municipalities g ON d.municipality_id=g.id
                            WHERE a.id=" . $saleOriginal->company_id . "";
        $emisor = DB::select(DB::raw($queryemisor));

            // Construir comprobante para envío a Hacienda
        $comprobante = [
            "emisor" => $emisor,
            "documento" => $documento,
            "detalle" => $detalle,
            "totales" => $totales,
            "cliente" => $cliente
        ];

            // Enviar a Hacienda
        $respuesta = $this->Enviar_Hacienda($comprobante, "05");
        //dd($respuesta);
        if ($respuesta["codEstado"] == "03") {
                // CREAR DTE CON ESTADO RECHAZADO Y REGISTRAR ERROR
                $dtecreate = $this->crearDteConError($doc, $emisor, $respuesta, $comprobante, $nfactura, $createdby);
                // REGISTRAR ERROR EN LA TABLA dte_errors
                $this->registrarErrorDte($dtecreate, 'hacienda', 'HACIENDA_REJECTED', $respuesta["descripcionMsg"] ?? 'Documento rechazado por Hacienda', [
                    'codigoMsg' => $respuesta["codigoMsg"] ?? null,
                    'observacionesMsg' => $respuesta["observacionesMsg"] ?? null,
                    'sale_id' => $nfactura->id
                ]);

                // Guardar JSON con información de rechazo en la tabla sales
                $comprobante["json"] = $respuesta;
                $nfactura->json = json_encode($comprobante);
                $nfactura->save();

                DB::rollBack();
                return redirect()->back()
                    ->with('error', 'Error al enviar a Hacienda: ' . ($respuesta["descripcionMsg"] ?? 'Documento rechazado'));
            }
            //dd($respuesta);
        $comprobante["json"] = $respuesta;
            // Crear registro DTE
            $dte = new \App\Models\Dte();
            $dte->versionJson = $doc[0]->versionJson;
            $dte->ambiente_id = $doc[0]->ambiente;
            $dte->tipoDte = $doc[0]->tipodocumento;
            $dte->tipoModelo = $doc[0]->tipogeneracion;
            $dte->tipoTransmision = 1;
            $dte->tipoContingencia = "null";
            $dte->idContingencia = "null";
            $dte->nameTable = 'Sales';
            $dte->company_id = $nfactura->company_id;
            $dte->company_name = $emisor[0]->nombreComercial;
            $dte->id_doc = $respuesta["identificacion"]["numeroControl"];
            $dte->codTransaction = "01";
            $dte->desTransaction = "Emision";
            $dte->type_document = $doc[0]->tipodocumento;
            $dte->id_doc_Ref1 = "null";
            $dte->id_doc_Ref2 = "null";
            $dte->type_invalidacion = "null";
            $dte->codEstado = $respuesta["codEstado"];
            $dte->Estado = $respuesta["estado"];
            $dte->codigoGeneracion = $respuesta["codigoGeneracion"];
            $dte->selloRecibido = $respuesta["selloRecibido"];
            $dte->fhRecibido = $respuesta["fhRecibido"];
            $dte->estadoHacienda = $respuesta["estadoHacienda"];
            $dte->json = json_encode($comprobante);
            $dte->nSends = $respuesta["nuEnvios"];
            $dte->codeMessage = $respuesta["codigoMsg"];
            $dte->claMessage = $respuesta["clasificaMsg"];
            $dte->descriptionMessage = $respuesta["descripcionMsg"];
            $dte->detailsMessage = $respuesta["observacionesMsg"];
            $dte->sale_id = $nfactura->id;
            $dte->created_by = $doc[0]->NombreUsuario;
            $dte->save();

            $nfactura->codigoGeneracion = $respuesta["codigoGeneracion"];

            // Agregar el codigoGeneracion al JSON antes de guardarlo
            //$comprobante["json"] = $respuesta;
            $nfactura->json = json_encode($comprobante);
            $nfactura->save();

            // El correlativo ya fue actualizado arriba

            DB::commit();
            if (request()->ajax()) {
                return response('1');
            }
            return redirect()->route('sale.index')
                ->with('success', 'Nota de crédito creada y enviada a Hacienda exitosamente.');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error creando nota de crédito: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            // Para debugging temporal, mostrar el error completo
            if (config('app.debug')) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Error al procesar la nota de crédito: ' . $e->getMessage() . ' en línea ' . $e->getLine());
            }

            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al procesar la nota de crédito. Revisa los logs para más detalles.');
        }
    }

    /**
     * Calcular totales de la nota de crédito
     */
    private function calcularTotalesNotaCredito($notaCreditoId): array
    {
        $totales = Salesdetail::where('sale_id', $notaCreditoId)
            ->selectRaw('
                SUM(pricesale) as gravadas,
                SUM(exempt) as exentas,
                SUM(nosujeta) as nosujetas,
                SUM(detained13) as iva,
                SUM(pricesale + exempt + nosujeta) as subtotal
            ')
            ->first();

        $total = $totales->subtotal + $totales->iva;

        return [
            'gravadas' => (float)$totales->gravadas,
            'exentas' => (float)$totales->exentas,
            'nosujetas' => (float)$totales->nosujetas,
            'iva' => (float)$totales->iva,
            'subtotal' => (float)$totales->subtotal,
            'total' => (float)$total
        ];
    }

    /**
     * Construir detalle fiscal para la nota de crédito
     */
    private function construirDetalleNotaCredito($notaCreditoId): array
    {
        $queryDetalle = "SELECT
                        *,
                        det.id id_factura_det,
                        det.sale_id id_factura,
                        det.product_id id_producto,
                        CASE
                            WHEN det.description IS NOT NULL AND det.description != '' THEN det.description
                            ELSE pro.description
                        END AS descripcion,
                        det.amountp cantidad,
                        det.priceunit precio_unitario,
                        det.nosujeta no_sujetas,
                        det.exempt exentas,
                        det.pricesale gravadas,
                        det.detained13 iva,
                        0.00 no_imponible,
                        sa.company_id id_empresa,
                        CASE
                                WHEN pro.`type` = 'tercero' THEN 'T'
                                WHEN pro.`type` = 'directo' THEN 'D'
                            END AS tipo_producto,
                        0.00 porcentaje_descuento,
                        0.00 descuento,
                        det.created_at,
                        det.updated_at
                        FROM salesdetails det
                        INNER JOIN sales sa ON det.sale_id=sa.id
                        INNER JOIN products pro ON det.product_id=pro.id
                        WHERE det.sale_id = $notaCreditoId";

        return DB::select(DB::raw($queryDetalle));
    }

    /**
     * Crear nota de débito
     *
     * @param  int|null  $id_sale
     * @return \Illuminate\Http\Response
     */
    public function ndb($id_sale = null)
    {
        // Si no se pasa id_sale como parámetro de ruta, intentar obtenerlo de la consulta
        if ($id_sale === null) {
            $id_sale = request('sale_id');
        }

        // Si el id_sale viene codificado en base64, decodificarlo
        if ($id_sale && !is_numeric($id_sale)) {
            $id_sale = base64_decode($id_sale);
        }

        // Por ahora, redirigir con un mensaje informativo ya que las notas de débito no están implementadas
        if (!request()->ajax() && !request()->expectsJson()) {
            return redirect()->route('sale.index')->with('info', 'La funcionalidad de notas de débito está en desarrollo');
        }

        return response()->json(array(
            "res" => 0,
            "message" => "La funcionalidad de notas de débito está en desarrollo"
        ));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Sale  $Sale
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $idFactura = base64_decode($id);
            $anular = Sale::find($idFactura);

            if (!$anular) {
                return response()->json([
                    'success' => false,
                    'message' => 'Venta no encontrada',
                    'res' => 0
                ], 404);
            }

            $anular->state = 0;
            $anular->typesale = 0;

            $queryinvalidacion = "SELECT
        b.tipoModelo,
        b.type_document,
        b.sale_id numero_factura,
        b.id_doc,
        b.tipoDte,
        am.cod ambiente,
        comp.tipoEstablecimiento,
        b.codigoGeneracion,
        b.selloRecibido,
        b.fhRecibido,
        (SELECT SUM(det.detained13) FROM salesdetails det WHERE det.sale_id=a.id) iva,
        clie.nit,
        CASE
                WHEN clie.tpersona = 'N' THEN CONCAT_WS(' ', clie.firstname, clie.secondname, clie.firstlastname, clie.secondlastname)
                WHEN clie.tpersona = 'J' THEN COALESCE(clie.name_contribuyente, '')
            END AS anombrede,
        a.company_id id_empresa,
        a.client_id id_cliente,
        am.url_credencial,
        am.url_invalidacion,
        am.url_firmador
        FROM sales a
        INNER JOIN clients clie ON a.client_id=clie.id
        INNER JOIN companies comp ON a.company_id=comp.id
        INNER JOIN dte b ON b.sale_id=a.id
        LEFT JOIN ambientes am ON CONCAT('0',b.ambiente_id)=am.cod
        WHERE a.id = $idFactura";
            $invalidacion = DB::select(DB::raw($queryinvalidacion));
            $queryemisor = "SELECT
        a.nit,
        CAST(REPLACE(REPLACE(a.ncr, '-', ''), ' ', '') AS UNSIGNED) AS ncr,
        a.name nombre,
        c.code codActividad,
        c.name descActividad,
        a.name nombreComercial,
        a.tipoEstablecimiento,
        f.code departamento,
        g.code municipio,
        d.reference direccion,
        e.phone telefono,
        NULL codEstableMH,
        NULL codEstable,
        NULL codPuntoVentaMH,
        NULL codPuntoVenta,
        a.email correo,
        b.passkeyPublic clavePublicaMH,
        b.passPrivateKey clavePrivadaMH,
        b.passMH claveApiMH
        FROM companies a
        INNER JOIN config b ON a.id=b.company_id
        INNER JOIN economicactivities c ON a.economicactivity_id=c.id
        INNER JOIN addresses d ON a.address_id=d.id
        INNER JOIN phones e ON a.phone_id=e.id
        INNER JOIN departments f ON d.department_id=f.id
        INNER JOIN municipalities g ON d.municipality_id=g.id
        WHERE a.id=$anular->company_id";
            $emisor = DB::select(DB::raw($queryemisor));
            $queryproducto = "SELECT
        c.id id_producto,
        CASE
            WHEN b.description IS NOT NULL AND b.description != '' THEN b.description
            ELSE c.description
        END AS descripcion,
        b.amountp cantidad,
        b.priceunit precio_unitario,
        0 descuento,
        0 no_imponible,
        (b.pricesale+b.nosujeta+b.exempt) subtotal,
        b.pricesale gravadas,
        b.nosujeta no_sujetas,
        b.exempt exentas,
        b.detained13 iva,
        0 porcentaje_descuento,
        b.detained13 iva_calculado,
        0 renta_retenida,
        1 tipo_item,
        59 uniMedida
        FROM sales a
        INNER JOIN salesdetails b ON b.sale_id=a.id
        INNER JOIN products c ON b.product_id=c.id
        WHERE a.id=$idFactura";
            $producto = DB::select(DB::raw($queryproducto));
            $detalle = $producto;
            $detailsbd = Salesdetail::where('sale_id', '=', $idFactura)
                ->select(
                    DB::raw('SUM(nosujeta) nosujeta,
            SUM(exempt) exentas,
            SUM(CASE WHEN nosujeta > 0 OR exempt > 0 THEN 0 ELSE pricesale END) gravadas,
            SUM(nosujeta+exempt+pricesale) subtotalventas,
            0 descnosujeta,
            0 descexenta,
            0 desgravada,
            0 porcedesc,
            0 totaldesc,
            NULL tributos,
            SUM(nosujeta+exempt+pricesale) subtotal,
            SUM(detained) ivarete,
            0 ivarete,
            0 rentarete,
            NULL pagos,
            SUM(CASE WHEN nosujeta > 0 OR exempt > 0 THEN 0 ELSE detained13 END) iva')
                )
                ->get();

            $totalPagar = ($detailsbd[0]->nosujeta + $detailsbd[0]->exentas + $detailsbd[0]->gravadas + $detailsbd[0]->iva - $detailsbd[0]->ivarete);
            $totales = [
                "totalNoSuj" => (float)$detailsbd[0]->nosujeta,
                "totalExenta" => (float)$detailsbd[0]->exentas,
                "totalGravada" => (float)$detailsbd[0]->gravadas,
                "subTotalVentas" => round((float)($detailsbd[0]->subtotalventas), 8),
                "descuNoSuj" => $detailsbd[0]->descnosujeta,
                "descuExenta" => $detailsbd[0]->descexenta,
                "descuGravada" => $detailsbd[0]->desgravada,
                "porcentajeDescuento" => 0.00,
                "totalDescu" => $detailsbd[0]->totaldesc,
                "tributos" =>  null,
                "subTotal" => round((float)($detailsbd[0]->subtotal), 8),
                "ivaPerci1" => 0.00,
                "ivaRete1" => 0.00,
                "reteRenta" => round((float)$detailsbd[0]->rentarete, 8),
                "montoTotalOperacion" => round((float)($detailsbd[0]->subtotal), 8),
                "totalNoGravado" => (float)0,
                "totalPagar" => (float)$totalPagar,
                "totalLetras" => numtoletras($totalPagar),
                "saldoFavor" => 0.00,
                "condicionOperacion" => $anular->waytopay,
                "pagos" => null,
                "totalIva" => (float)$detailsbd[0]->iva
            ];
            dd($totales);
            $querycliente = "SELECT
        a.id idcliente,
        a.nit,
        CAST(REPLACE(REPLACE(a.ncr, '-', ''), ' ', '') AS UNSIGNED) AS ncr,
        CASE
            WHEN a.tpersona = 'N' THEN CONCAT_WS(' ', a.firstname, a.secondname, a.firstlastname, a.secondlastname)
            WHEN a.tpersona = 'J' THEN COALESCE(a.name_contribuyente, '')
        END AS nombre,
        b.code codActividad,
        b.name descActividad,
        CASE
            WHEN a.tpersona = 'N' THEN CONCAT_WS(' ', a.firstname, a.secondname, a.firstlastname, a.secondlastname)
            WHEN a.tpersona = 'J' THEN COALESCE(a.comercial_name, '')
        END AS nombreComercial,
        a.email correo,
        f.code departamento,
        g.code municipio,
        c.reference direccion,
        p.phone telefono,
        1 id_tipo_contribuyente,
        a.tipoContribuyente id_clasificacion_tributaria,
        0 siempre_retiene,
        36 tipoDocumento,
        a.nit numDocumento,
        36 tipoDocumentoCliente,
        d.code codPais,
        d.name nombrePais,
        0 siempre_retiene_renta
    FROM clients a
    INNER JOIN economicactivities b ON a.economicactivity_id=b.id
    INNER JOIN addresses c ON a.address_id=c.id
    INNER JOIN phones p ON a.phone_id=p.id
    INNER JOIN countries d ON c.country_id=d.id
    INNER JOIN departments f ON c.department_id=f.id
    INNER JOIN municipalities g ON c.municipality_id=g.id
    WHERE a.id = $anular->client_id";
            $cliente = DB::select(DB::raw($querycliente));

            $documento[0] = [
                "tipodocumento"         => 99,
                "nu_doc"                => $invalidacion[0]->numero_factura,
                "tipoDteOriginal"       => $invalidacion[0]->tipoDte,
                "tipo_establecimiento"  => $invalidacion[0]->tipoEstablecimiento,
                "version"               => 2,
                "ambiente"              => $invalidacion[0]->ambiente,
                "id_doc"                => $invalidacion[0]->id_doc,
                "fecAnulado"            => date('Y-m-d'),
                "horAnulado"            => date("H:i:s"),
                "codigoGeneracionOriginal" => $invalidacion[0]->codigoGeneracion,
                "selloRecibidoOriginal"     => $invalidacion[0]->selloRecibido,
                "fecEmiOriginal"            => date('Y-m-d', strtotime($invalidacion[0]->fhRecibido)),
                "total_iva"                 => $invalidacion[0]->iva,
                "tipoDocumento"             => $invalidacion[0]->type_document,
                "numDocumento"              => $invalidacion[0]->nit,
                "nombre"                    => $invalidacion[0]->anombrede,
                "versionjson"               => 2,
                "id_empresa"                => $invalidacion[0]->id_empresa,
                "url_credencial"            => $invalidacion[0]->url_credencial,
                "url_envio"                 => $invalidacion[0]->url_invalidacion,
                "url_firmador"              => $invalidacion[0]->url_firmador,
                "nuEnvio"                   => 1
            ];
            $comprobante = [
                "emisor"    => $emisor,
                "documento" => $documento,
                "detalle"   => $detalle,
                "totales"   => $totales,
                "cliente"   => $cliente
            ];

            $respuesta = $this->Enviar_Hacienda($comprobante, "02");
            if ($respuesta["codEstado"] == "03") {
                return response()->json([
                    'success' => false,
                    'message' => $respuesta['descripcionMsg'],
                    'code' => $respuesta["codEstado"]
                ], 400);
            }
            $comprobante["json"] = $respuesta;

            $dtecreate = new Dte();
            $dtecreate->versionJson = $documento[0]["versionjson"];
            $dtecreate->ambiente_id = $documento[0]["ambiente"];
            $dtecreate->tipoDte = $documento[0]["tipoDocumento"];
            $dtecreate->tipoModelo = 2;
            $dtecreate->tipoTransmision = $documento[0]["tipoDocumento"];
            $dtecreate->tipoContingencia = "null";
            $dtecreate->idContingencia = "null";
            $dtecreate->nameTable = 'Sales';
            $dtecreate->company_id = $anular->company_id;
            $dtecreate->company_name = $emisor[0]->nombreComercial;
            $dtecreate->id_doc = $documento[0]["id_doc"];
            $dtecreate->codTransaction = "02";
            $dtecreate->desTransaction = "Invalidacion";
            $dtecreate->type_document = $documento[0]["tipoDocumento"];
            $dtecreate->id_doc_Ref1 = $documento[0]["id_doc"];
            $dtecreate->id_doc_Ref2 = "null";
            $dtecreate->type_invalidacion = "1";
            $dtecreate->codEstado = $respuesta["codEstado"];
            $dtecreate->Estado = $respuesta["estado"];
            $dtecreate->codigoGeneracion = $respuesta["codigoGeneracion"];
            $dtecreate->selloRecibido = $respuesta["selloRecibido"];
            $dtecreate->fhRecibido = $respuesta["fhRecibido"];
            $dtecreate->estadoHacienda = $respuesta["estadoHacienda"];
            $dtecreate->json = json_encode($comprobante);
            $dtecreate->nSends = $respuesta["nuEnvios"];
            $dtecreate->codeMessage = $respuesta["codigoMsg"];
            $dtecreate->claMessage = $respuesta["clasificaMsg"];
            $dtecreate->descriptionMessage = $respuesta["descripcionMsg"];
            $dtecreate->detailsMessage = $respuesta["observacionesMsg"];
            $dtecreate->sale_id = $idFactura;
            $dtecreate->created_by = $documento[0]["nombre"];
            $dtecreate->save();
            $anular->save();

            if ($dtecreate && $anular) {
                return response()->json([
                    'success' => true,
                    'message' => 'Documento invalidado correctamente',
                    'res' => 1
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al invalidar el documento',
                    'res' => 0
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor: ' . $e->getMessage(),
                'res' => 0
            ], 500);
        }
    }

    public function Enviar_Hacienda($comprobante, $codTransaccion = "01")
    {
        //$codTransaccion ='01';
        date_default_timezone_set('America/El_Salvador');
        ini_set('max_execution_time', '300');
        $respuesta = [];
        $comprobante_electronico = [];
        //return $comprobante_electronico;
        try {
            $comprobante_electronico = convertir_json($comprobante, $codTransaccion);
            //dd($comprobante_electronico);
        } catch (\Exception $e) {
            throw $e;
        }
        //return $comprobante_electronico;
        if ($codTransaccion == "02" || $codTransaccion == "05") {
            $tipo_documento = $comprobante["documento"][0]["tipodocumento"];
        } else {
            $tipo_documento = $comprobante["documento"][0]->tipodocumento;
        }
        //$tipo_documento = $comprobante["documento"][0]->tipodocumento;
        //dd($comprobante);
        if ($codTransaccion == "02" || $codTransaccion == "05") {
            $version = $comprobante["documento"][0]["version"];
        } else {
            $version = $comprobante["documento"][0]->versionJson;
        }
        //$version = $comprobante["documento"][0]->versionJson;
        if ($codTransaccion == '01' || $codTransaccion == "05") {
            $numero_control = $comprobante_electronico["identificacion"]["numeroControl"];
        } else {
            $numero_control = 'Anulacion o Contingencia';
        }
        $empresa = $comprobante["documento"][0];
        $id_empresa = ($codTransaccion == "02" || $codTransaccion == "05" ? $comprobante["documento"][0]["id_empresa"] : $empresa->id_empresa);
        $ambiente = ($codTransaccion == "02" || $codTransaccion == "05" ? $comprobante["documento"][0]["ambiente"] : $empresa->ambiente);
        $emisor = $comprobante["emisor"];
        $url_credencial = ($codTransaccion == "02" || $codTransaccion == "05" ? $comprobante["documento"][0]["url_credencial"] : $empresa->url_credencial);
        $url_envio = ($codTransaccion == "02" || $codTransaccion == "05" ? $comprobante["documento"][0]["url_envio"] : $empresa->url_envio);
        $url_firmador = ($codTransaccion == "02" || $codTransaccion == "05" ? $comprobante["documento"][0]["url_firmador"] : $empresa->url_firmador);
        //dd(str_replace('-','',$emisor[0]->nit));
        $firma_electronica = [
            "nit" => str_replace('-', '', $emisor[0]->nit),
            "activo" => true,
            "passwordPri" => $emisor[0]->clavePrivadaMH,
            "dteJson" => $comprobante_electronico
        ];
        //dd($firma_electronica);
        //return json_encode($firma_electronica);
        //dd(json_encode($firma_electronica));
        //dd($url_firmador);
        try {
            $response = Http::accept('application/json')->post($url_firmador, $firma_electronica);
        } catch (\Throwable $th) {
            $error = [
                "mensaje" => "Error en Firma de Documento",
                "error" => $th
            ];
            return  json_encode($error);
        }
        //return "aqui llego";
        //return $response;
        $objResponse = json_decode($response, true);
        //dd($objResponse);
        //return json_last_error_msg();
        $objResponse = (array)$objResponse;
        $comprobante_encriptado = $objResponse["body"];
        $validacion_usuario = [
            "user"  => str_replace('-', '', $emisor[0]->nit),
            "pwd"   => $emisor[0]->claveApiMH
        ];

        //dd($validacion_usuario);
        //dd($this->getTokenMH($id_empresa, $validacion_usuario, $url_credencial, $url_credencial));
        if ($this->getTokenMH($id_empresa, $validacion_usuario, $url_credencial, $url_credencial) == "OK") {
            // return 'paso validacion';
            $token = Session::get($id_empresa);

            // Debugging para el token
            Log::info('Token obtenido para envío', [
                'id_empresa' => $id_empresa,
                'token_length' => strlen($token),
                'token_preview' => substr($token, 0, 20) . '...',
                'url_envio' => $url_envio
            ]);

            //dd(["token" => $token, "url_envio" => $url_envio, "id_empresa" => $id_empresa]);

            //$ambiente = $comprobante["documento"][0]->ambiente;
            //dd($documento[0]);
            //return ["token" => $token];
            //dd($codTransaccion);
            if ($codTransaccion == "01" || $codTransaccion == "05") {
                $comprobante_enviar = [
                    "ambiente"      => $ambiente,
                    "idEnvio"       => 1, //intval($comprobante["nuEnvio"]),
                    "version"       => intval($version),
                    "tipoDte"       => $tipo_documento,
                    "documento"     => $comprobante_encriptado
                ];
            } else {
                $comprobante_enviar = [
                    "ambiente"      => $ambiente,
                    "idEnvio"       => intval($empresa["nuEnvio"]),
                    "version"       => intval($version),
                    "documento"     => $comprobante_encriptado
                ];
            }

            //dd($comprobante_enviar);
            //dd($url_envio);
            try {
                $response_enviado = Http::withToken($token)->post($url_envio, $comprobante_enviar);

                // Si recibe 401 Unauthorized, regenerar token e intentar de nuevo
                if ($response_enviado->status() == 401) {
                    Log::warning('Token no autorizado (401), esperando 2 segundos antes del segundo intento...');

                    // Esperar 2 segundos antes del segundo intento
                    sleep(3);

                    Log::warning('Regenerando token y reintentando envío 2 vez...');

                    // Limpiar sesión para forzar nueva autenticación
                    Session::forget($id_empresa);
                    Session::forget($id_empresa . '_fecha');

                    // Regenerar token
                    $tokenResult = $this->getNewTokenMH($id_empresa, $validacion_usuario, $url_credencial);
                    //dd("Token Result",$tokenResult, Session::get($id_empresa), Session::get($id_empresa . '_fecha'));

                    if ($tokenResult == 'OK') {
                        $token = Session::get($id_empresa);
                        Log::info('Nuevo token generado, reintentando envío 2 vez...');

                        // Intentar de nuevo con el nuevo token
                        $response_enviado = Http::withToken($token)->post($url_envio, $comprobante_enviar);

                        // Si el segundo intento también falla con 401, intentar una tercera vez
                        if($response_enviado->status() == 401){
                            Log::warning('Token no autorizado (401), esperando 3 segundos antes del tercer intento...');

                            // Esperar 3 segundos antes del tercer intento
                            sleep(3);

                            Log::warning('Regenerando token y reintentando envío 3 vez...');

                            // Limpiar sesión nuevamente
                            Session::forget($id_empresa);
                            Session::forget($id_empresa . '_fecha');

                            // Regenerar token por tercera vez
                            $tokenResult = $this->getNewTokenMH($id_empresa, $validacion_usuario, $url_credencial);

                            if($tokenResult == 'OK'){
                                $token = Session::get($id_empresa);
                                Log::info('Tercer token generado, reintentando envío 3 vez...');

                                // Tercer intento
                                $response_enviado = Http::withToken($token)->post($url_envio, $comprobante_enviar);

                                Log::info('Tercer intento - Respuesta del envío a MH', [
                                    'status_code' => $response_enviado->status(),
                                    'response_body' => $response_enviado->body()
                                ]);
                            } else {
                                Log::error('Error al regenerar token 3 vez: ' . $tokenResult);
                            }
                        } else {
                            Log::info('Segundo intento exitoso - Respuesta del envío a MH', [
                                'status_code' => $response_enviado->status(),
                                'response_body' => $response_enviado->body()
                            ]);
                        }
                    } else {
                        Log::error('Error al regenerar token 1 vez: ' . $tokenResult);
                    }
                } else {
                    // Debugging después del envío exitoso
                    Log::info('Primer intento exitoso - Respuesta del envío a MH', [
                        'status_code' => $response_enviado->status(),
                        'response_body' => $response_enviado->body()
                    ]);
                }

                //dd($response_enviado);
            } catch (\Throwable $th) {
                //return 'entro aqui';
                $error  = [
                    "mensaje" => "Error con Servicios de Hacienda",
                    "erro" => $th
                ];
                return json_encode($error);
            }
        } else {
            $response_enviado = $this->getTokenMH($id_empresa, $url_credencial, $url_credencial);
        }

        //dd($comprobante);

        //return json_encode($comprobante);
        //dd($response_enviado);
        $objEnviado = json_decode($response_enviado);
        //dd($objEnviado);
        if (isset($objEnviado->estado)) {
            $estado_envio = $objEnviado->estado;
            $dateString = $objEnviado->fhProcesamiento;
            $myDateTime = DateTime::createFromFormat('d/m/Y H:i:s', $dateString);
            $newDateString = $myDateTime->format('Y-m-d H:i:s');
            //$prueba = gettype($objEnviado->observaciones);
            //dd($objEnviado->observaciones);
            $observaciones = implode("<br>", $objEnviado->observaciones);
            if ($estado_envio == "PROCESADO") {
                $respuesta = [
                    "codEstado"         => "02",
                    "estado"            => "Enviado",
                    "codigoGeneracion"  => $objEnviado->codigoGeneracion,
                    "fhRecibido"        => $newDateString,
                    "selloRecibido"     => $objEnviado->selloRecibido,
                    "estadoHacienda"    => $objEnviado->estado,
                    "nuEnvios"          => 1,
                    "clasificaMsg"      => $objEnviado->clasificaMsg,
                    "codigoMsg"         =>  $objEnviado->codigoMsg,
                    "descripcionMsg"    => $objEnviado->descripcionMsg,
                    "observacionesMsg"  => $observaciones,

                ];
                $comprobante_electronico["selloRecibido"] = $objEnviado->selloRecibido;
                if ($codTransaccion == '01' || $codTransaccion == '05') {
                    if ($tipo_documento == '14') {
                        $respuesta["receptor"] = $comprobante_electronico["sujetoExcluido"];
                    } else {
                        $respuesta["receptor"] = $comprobante_electronico["receptor"];
                    }

                    $respuesta["identificacion"]    = $comprobante_electronico["identificacion"];
                    $respuesta["json_enviado"]      = $comprobante_electronico;
                }

                // $this->envia_correo($comprobante);

            } else {
                $respuesta = [
                    "codEstado" =>  "03",
                    "estado" =>  "Rechazado",
                    "descripcionMsg" =>  $objEnviado->descripcionMsg,
                    "observacionesMsg" =>  $observaciones,
                    "nuEnvios" =>  1
                ];
            }
        } else {
            return var_dump($objEnviado);
        }

        return $respuesta;
    }

    public function getTokenMH($id_empresa, $credenciales, $url_seguridad)
    {
        //dd('entra a gettoken');
        if (!Session::has($id_empresa)) {

            //dd('No encuentra la variable');
            //return ["mensaje" => "llama  getnewtokemh"];
            $respuesta =  $this->getNewTokenMH($id_empresa, $credenciales, $url_seguridad);
        } else {
            $now = new Datetime('now');
            $expira = DateTime::createFromFormat('Y-m-d H:i:s', Session::get($id_empresa . '_fecha'));
            $respuesta = 'OK';
            if ($now > $expira) {
                // dd($expira);
                $respuesta = $this->getNewTokenMH($id_empresa, $credenciales, $url_seguridad);
            }
        }
        //dd(Session::get($id_empresa));
        // return ["mensaje" => "pasa la autorizacion OK estoy en get"];
        if ($respuesta == 'OK') {
            return 'OK';
        } else {
            return $respuesta;
        }
    }

    public function getNewTokenMH($id_empresa, $credenciales, $url_seguridad)
    {


        $response_usuario = Http::asForm()->post($url_seguridad, $credenciales);

        // Debugging para la autenticación
        Log::info('Respuesta de autenticación MH', [
            'status_code' => $response_usuario->status(),
            'response_body' => $response_usuario->body(),
            'url_seguridad' => $url_seguridad,
            'credenciales_user' => $credenciales['user'] ?? 'NO_DEFINIDO'
        ]);

        //dd(["mensaje" => $response_usuario, 'credenciales' => $credenciales]);
        $objValidacion = json_decode($response_usuario, true);

        //dd($objValidacion);
        //return ["mensaje" => "pasa la autorizacion"];
        if ($objValidacion["status"] != 'OK') {
            // return ["mensaje" => "no pasa la autorizacion OK"];
            return $objValidacion["status"];
        } else {
            //dd($objValidacion);
            //return ["mensaje" => "pasa la autorizacion OK"];
            Session::put($id_empresa, str_replace('Bearer ', '', $objValidacion["body"]["token"]));
            $fecha_expira = date("Y-m-d H:i:S", strtotime('+24 hours'));
            Session::put($id_empresa . '_fecha', $fecha_expira);
            return 'OK';
        }
    }

    public function envia_correo(Request $request)
    {
        $id_factura = $request->id_factura;
        $nombre = $request->nombre;
        $numero = $request->numero;
        $comprobante = Sale::join('dte', 'dte.sale_id', '=', 'sales.id')
            ->join('companies', 'companies.id', '=', 'sales.company_id')
            ->join('addresses', 'addresses.id', '=', 'companies.address_id')
            ->join('countries', 'countries.id', '=', 'addresses.country_id')
            ->join('departments', 'departments.id', '=', 'addresses.department_id')
            ->join('municipalities', 'municipalities.id', '=', 'addresses.municipality_id')
            ->join('clients as cli', 'cli.id', '=', 'sales.client_id')
            ->join('addresses as add', 'add.id', '=', 'cli.address_id')
            ->join('countries as cou', 'cou.id', '=', 'add.country_id')
            ->join('departments as dep', 'dep.id', '=', 'add.department_id')
            ->join('municipalities as muni', 'muni.id', '=', 'add.municipality_id')
            ->select(
                'sales.*',
                'dte.json as JsonDTE',
                'dte.codigoGeneracion',
                'countries.name as PaisE',
                'departments.name as DepartamentoE',
                'municipalities.name as MunicipioE',
                'cou.name as PaisR',
                'dep.name as DepartamentoR',
                'muni.name as MunicipioR'
            )
            ->where('sales.id', '=', $id_factura)
            ->get();
        //dd($comprobante);
        $email = $request->email;
        //$email ="briandagoberto20@hotmail.com";
        $pdf = $this->genera_pdf($id_factura);
        $json_root = json_decode($comprobante[0]->JsonDTE);
        $json_enviado = $json_root->json->json_enviado;
        $json = json_encode($json_enviado, JSON_PRETTY_PRINT);
        $archivos = [
            $comprobante[0]->codigoGeneracion . '.pdf' => $pdf->output(),
            $comprobante[0]->codigoGeneracion . '.json' => $json
        ];
        $data = ["nombre" => $json_enviado->receptor->nombre, "numero" => $numero,  "json" => $json_enviado];
        $asunto = "Comprobante de Venta No." . $data["json"]->identificacion->numeroControl . ' de Proveedor: ' . $data["json"]->emisor->nombre;
        $correo = new EnviarCorreo($data);
        $correo->subject($asunto);
        foreach ($archivos as $nombreArchivo => $rutaArchivo) {
            $correo->attachData($rutaArchivo, $nombreArchivo);
        }

        Mail::to($email)->send($correo);
    }

    public function genera_pdf($id)
    {
        $factura = Sale::leftjoin('dte', 'dte.sale_id', '=', 'sales.id')
            ->join('companies', 'companies.id', '=', 'sales.company_id')
            ->join('addresses', 'addresses.id', '=', 'companies.address_id')
            ->join('countries', 'countries.id', '=', 'addresses.country_id')
            ->join('departments', 'departments.id', '=', 'addresses.department_id')
            ->join('municipalities', 'municipalities.id', '=', 'addresses.municipality_id')
            ->join('clients as cli', 'cli.id', '=', 'sales.client_id')
            ->join('addresses as add', 'add.id', '=', 'cli.address_id')
            ->join('countries as cou', 'cou.id', '=', 'add.country_id')
            ->join('departments as dep', 'dep.id', '=', 'add.department_id')
            ->join('municipalities as muni', 'muni.id', '=', 'add.municipality_id')
            ->select(
                'dte.json',
                'countries.name as PaisE',
                'departments.name as DepartamentoE',
                'municipalities.name as MunicipioE',
                'cou.name as PaisR',
                'dep.name as DepartamentoR',
                'muni.name as MunicipioR',
                'typedoc.codemh'
            )
            ->where('sales.id', '=', $id)
            ->get();
        //dd($factura);
        $comprobante = json_decode($factura, true);
        //dd(json_decode($comprobante[0]["json"]));
        $data = json_decode($comprobante[0]["json"], true);
        //print_r($data);
        //dd($data);
        $tipo_comprobante = $data["documento"][0]["tipodocumento"];
        //dd($tipo_comprobante);
        switch ($tipo_comprobante) {
            case '03': //CRF
                $rptComprobante = 'pdf.crf';
                break;
            case '01': //FAC
                $rptComprobante = 'pdf.fac';
                break;
            case '11':  //FEX
                $rptComprobante = 'pdf.fex';
                break;
            case '05':
                $rptComprobante = 'pdf.ncr';
                break;
            case '06':
                $rptComprobante = 'pdf.ndb';
                break;

            default:
                # code...
                break;
        }
        @$fecha = $data["json"]["fhRecibido"];
        @$qr = base64_encode(codigoQR($data["documento"][0]["ambiente"], $data["json"]["codigoGeneracion"], $fecha));
        //return  '<img src="data:image/png;base64,'.$qr .'">';
        $data["codTransaccion"] = "01";
        $data["PaisE"] = $factura[0]['PaisE'];
        $data["DepartamentoE"] = $factura[0]['DepartamentoE'];
        $data["MunicipioE"] = $factura[0]['MunicipioE'];
        $data["PaisR"] = $factura[0]['PaisR'];
        $data["DepartamentoR"] = $factura[0]['DepartamentoR'];
        $data["MunicipioR"] = $factura[0]['MunicipioR'];
        $data["qr"] = $qr;
        $tamaño = "Letter";
        $orientacion = "Portrait";
        $pdf = app('dompdf.wrapper');
        $pdf->set_option('isHtml5ParserEnabled', true);
        $pdf->set_option('isRemoteEnabled', true);
        //dd(asset('/temp'));
        // $pdf->set_option('tempDir', asset('/temp'));
        //dd($data);
        $pdf->loadHtml(ob_get_clean());
        $pdf->setPaper($tamaño, $orientacion);
        $pdf->getDomPDF()->set_option("enable_php", true);
        $pdf->loadView($rptComprobante, $data);
        //dd($pdf);
        return $pdf;
    }
    public function genera_pdflocal($id)
    {
        $factura = Sale::leftjoin('dte', 'dte.sale_id', '=', 'sales.id')
            ->join('companies', 'companies.id', '=', 'sales.company_id')
            ->join('addresses', 'addresses.id', '=', 'companies.address_id')
            ->join('countries', 'countries.id', '=', 'addresses.country_id')
            ->join('departments', 'departments.id', '=', 'addresses.department_id')
            ->join('municipalities', 'municipalities.id', '=', 'addresses.municipality_id')
            ->join('clients as cli', 'cli.id', '=', 'sales.client_id')
            ->join('addresses as add', 'add.id', '=', 'cli.address_id')
            ->join('countries as cou', 'cou.id', '=', 'add.country_id')
            ->join('departments as dep', 'dep.id', '=', 'add.department_id')
            ->join('municipalities as muni', 'muni.id', '=', 'add.municipality_id')
            ->join('typedocuments as typedoc', 'typedoc.id', '=', 'sales.typedocument_id')
            ->select(
                'sales.*',
                'dte.json',
                'sales.json as jsonlocal',
                'countries.name as PaisE',
                'departments.name as DepartamentoE',
                'municipalities.name as MunicipioE',
                'cou.name as PaisR',
                'dep.name as DepartamentoR',
                'muni.name as MunicipioR',
                'typedoc.codemh'
            )
            ->where('sales.id', '=', $id)
            ->get();
        //dd($factura);
        $comprobante = json_decode($factura, true);
        //dd(json_decode($comprobante[0]["json"]));
        $data = json_decode($comprobante[0]["jsonlocal"], true);
        //dd($data);

        //print_r($data);
        //dd($data);
        //$tipo_comprobante = $data["documento"][0]["tipodocumento"];
        $tipo_comprobante = $comprobante[0]['codemh'];
        switch ($tipo_comprobante) {
            case '03': //CRF
                $rptComprobante = 'pdf.crflocal';
                break;
            case '01': //FAC
                $rptComprobante = 'pdf.faclocal';
                break;
            case '11':  //FEX
                $rptComprobante = 'pdf.fex';
                break;
            case '05':
                $rptComprobante = 'pdf.ncr';
                break;
            case '06':
                $rptComprobante = 'pdf.ndb';
                break;

            default:
                # code...
                break;
        }
        //$fecha = $data["json"]["fhRecibido"];
        //dd($data);
        $fecha = $data['documento'][0]['fechacreacion'];
        @$qr = base64_encode(codigoQR($data["documento"][0]["ambiente"], $data["json"]["codigoGeneracion"], $fecha));
        //return  '<img src="data:image/png;base64,'.$qr .'">';
        $data["codTransaccion"] = "01";
        $data["PaisE"] = $factura[0]['PaisE'];
        $data["DepartamentoE"] = $factura[0]['DepartamentoE'];
        $data["MunicipioE"] = $factura[0]['MunicipioE'];
        $data["PaisR"] = $factura[0]['PaisR'];
        $data["DepartamentoR"] = $factura[0]['DepartamentoR'];
        $data["MunicipioR"] = $factura[0]['MunicipioR'];
        $data["qr"] = $qr;
        $tamaño = "Letter";
        $orientacion = "Portrait";
        $pdf = app('dompdf.wrapper');
        $pdf->set_option('isHtml5ParserEnabled', true);
        $pdf->set_option('isRemoteEnabled', true);
        //dd(asset('/temp'));
        // $pdf->set_option('tempDir', asset('/temp'));
        //dd($data);
        $pdf->loadHtml(ob_get_clean());
        $pdf->setPaper($tamaño, $orientacion);
        $pdf->getDomPDF()->set_option("enable_php", true);
        $pdf->loadView($rptComprobante, $data);
        //dd($pdf);
        return $pdf;
    }
    public function print($id)
    {
        //$pdf = $this->genera_pdf($id);
        $pdf = $this->genera_pdflocal($id);
        return $pdf->stream('comprobante.pdf');
    }

    /**
     * Enviar correo automático al cliente con el comprobante.
     * - Si hay DTE, adjunta PDF oficial y JSON enviado
     * - Si no hay DTE, adjunta PDF local
     */
    private function enviarCorreoAutomaticoVenta(int $saleId, ?Dte $dte = null): void
    {
        // Obtener venta y correo del cliente
        $venta = Sale::join('clients', 'clients.id', '=', 'sales.client_id')
            ->join('companies', 'companies.id', '=', 'sales.company_id')
            ->select('sales.*', 'clients.email as client_email', 'clients.tpersona', 'clients.firstname', 'clients.secondname', 'clients.firstlastname', 'clients.secondlastname', 'clients.comercial_name', 'clients.name_contribuyente', 'companies.name as company_name')
            ->where('sales.id', $saleId)
            ->first();

        if (!$venta || empty($venta->client_email)) {
            return; // sin correo, no se envía
        }

        // Construir nombre del cliente
        $nombreCliente = $venta->tpersona === 'N'
            ? trim(($venta->firstname ?: '') . ' ' . ($venta->secondname ?: '') . ' ' . ($venta->firstlastname ?: '') . ' ' . ($venta->secondlastname ?: ''))
            : ($venta->comercial_name ?: $venta->name_contribuyente ?: 'Cliente');

        $numero = $venta->nu_doc ?: ('#' . $venta->id);
        $email = $venta->client_email;

        if ($dte && $dte->json) {
            // Armar PDF oficial y JSON
            $pdf = $this->genera_pdf($saleId);
            $jsonRoot = json_decode($dte->json);
            $jsonEnviado = $jsonRoot->json->json_enviado ?? null;
            $jsonPretty = $jsonEnviado ? json_encode($jsonEnviado, JSON_PRETTY_PRINT) : json_encode($jsonRoot, JSON_PRETTY_PRINT);

            $dataCorreo = [
                'nombre' => $nombreCliente,
                'numero' => $numero,
                'json' => $jsonEnviado ?: $jsonRoot
            ];

            $correo = new EnviarCorreo($dataCorreo);
            $asunto = 'Comprobante de Venta No. ' . $numero . ' - ' . $venta->company_name;
            $correo->subject($asunto);
            $correo->attachData($pdf->output(), ($dte->codigoGeneracion ?: ('venta_' . $saleId)) . '.pdf');
            $correo->attachData($jsonPretty, ($dte->codigoGeneracion ?: ('venta_' . $saleId)) . '.json');
            Mail::to($email)->send($correo);
            return;
        }

        // Sin DTE: PDF local
        $pdf = $this->genera_pdflocal($saleId);
        $dataCorreo = [
            'nombre' => $nombreCliente,
            'numero' => $numero,
            'json' => null
        ];
        $correo = new EnviarCorreo($dataCorreo);
        $asunto = 'Comprobante de Venta No. ' . $numero . ' - ' . $venta->company_name;
        $correo->subject($asunto);
        $correo->attachData($pdf->output(), 'venta_' . $saleId . '.pdf');
        Mail::to($email)->send($correo);
    }

    /**
     * Crear DTE con estado rechazado cuando hay error
     */
    private function crearDteConError($documento, $emisor, $respuesta_hacienda, $comprobante, $salesave, $createdby)
    {
        $dtecreate = new Dte();
        $dtecreate->versionJson = $documento[0]->versionJson;
        $dtecreate->ambiente_id = $documento[0]->ambiente;
        $dtecreate->tipoDte = $documento[0]->tipodocumento;
        $dtecreate->tipoModelo = $documento[0]->tipogeneracion;
        $dtecreate->tipoTransmision = 1;
        $dtecreate->tipoContingencia = "null";
        $dtecreate->idContingencia = "null";
        $dtecreate->nameTable = 'Sales';
        $dtecreate->company_id = $salesave->company_id;
        $dtecreate->company_name = $emisor[0]->nombreComercial;
        $dtecreate->id_doc = $respuesta_hacienda["identificacion"]["numeroControl"] ?? 'ERROR-' . time();
        $dtecreate->codTransaction = "01";
        $dtecreate->desTransaction = "Emision";
        $dtecreate->type_document = $documento[0]->tipodocumento;
        $dtecreate->id_doc_Ref1 = "null";
        $dtecreate->id_doc_Ref2 = "null";
        $dtecreate->type_invalidacion = "null";
        $dtecreate->codEstado = "03"; // Rechazado
        $dtecreate->Estado = "Rechazado";
        $dtecreate->codigoGeneracion = null;
        $dtecreate->selloRecibido = null;
        $dtecreate->fhRecibido = null;
        $dtecreate->estadoHacienda = null;
        $dtecreate->json = json_encode($comprobante);
        $dtecreate->nSends = 1;
        $dtecreate->codeMessage = $respuesta_hacienda["codigoMsg"] ?? null;
        $dtecreate->claMessage = $respuesta_hacienda["clasificaMsg"] ?? null;
        $dtecreate->descriptionMessage = $respuesta_hacienda["descripcionMsg"] ?? null;
        $dtecreate->detailsMessage = $respuesta_hacienda["observacionesMsg"] ?? null;
        $dtecreate->sale_id = $salesave->id;
        $dtecreate->created_by = $createdby;
        $dtecreate->save();

        return $dtecreate;
    }

    /**
     * Registrar error en la tabla dte_errors
     */
    private function registrarErrorDte($dte, $tipo, $codigo, $descripcion, $detalles = [])
    {
        try {
            // Obtener el JSON completo del DTE
            $jsonCompleto = null;
            if ($dte && isset($dte->json)) {
                $jsonCompleto = $dte->json;
            }

            \App\Models\DteError::crearError(
                $dte->id,
                $tipo,
                $codigo,
                $descripcion,
                $detalles,
                debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5),
                $jsonCompleto
            );
        } catch (\Exception $e) {
            Log::error('❌ Error registrando error DTE', [
                'dte_id' => $dte ? $dte->id : 'null',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    public function destinos()
    {
        $destinos = DB::table('aeropuertos')->get();
        return response()->json($destinos, 200);
    }

    public function linea()
    {
        $lineas = DB::table('aerolineas')->get();
        return response()->json($lineas, 200);
    }
}
