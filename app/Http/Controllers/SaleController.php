<?php

namespace App\Http\Controllers;

use App\Models\Ambiente;
use App\Models\Client;
use App\Models\Company;
use App\Models\Dte;
use App\Models\Sale;
use App\Models\Config;
use App\Models\Salesdetail;
use App\Models\Provider;
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
    private const N8N_WEBHOOK_URL = 'https://n8nvsystem.demosconsoftsv.website/webhook-test/invoice/send';
    private const N8N_JWT_SECRET = '!Pizza2025/*';
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

        // Subconsulta: último DTE de emisión (no invalidación) por venta
        $dteEmisionSub = DB::table('dte')
            ->select(
                'dte.sale_id',
                'dte.tipoDte',
                'dte.estadoHacienda',
                'dte.id_doc',
                'dte.company_name',
                'dte.codigoGeneracion',
                'dte.selloRecibido',
                'dte.fhRecibido'
            )
            ->whereIn('dte.codTransaction', ['01','05','06'])
            ->whereRaw('dte.id = (SELECT MAX(d2.id) FROM dte d2 WHERE d2.sale_id = dte.sale_id AND d2.codTransaction IN ("01","05","06"))');

        $sales = Sale::join('typedocuments', 'typedocuments.id', '=', 'sales.typedocument_id')
            ->join('clients', 'clients.id', '=', 'sales.client_id')
            ->leftJoin('phones', 'clients.phone_id', '=', 'phones.id')
            ->join('companies', 'companies.id', '=', 'sales.company_id')
            ->leftJoinSub($dteEmisionSub, 'dte_emis', function ($join) {
                $join->on('dte_emis.sale_id', '=', 'sales.id');
            })
            ->where('sales.typesale', '<>', '3')
            ->whereNull('sales.parent_sale_id') // Solo mostrar padres y ventas simples, NO hijos
            ->select(
                'sales.*',
                'typedocuments.description AS document_name',
                'clients.firstname',
                'clients.firstlastname',
                'clients.name_contribuyente as nameClient',
                'clients.tpersona',
                'clients.email as mailClient',
                DB::raw('phones.phone as client_phone'),
                'companies.name AS company_name',
                'dte_emis.tipoDte',
                'dte_emis.estadoHacienda',
                'dte_emis.id_doc',
                'dte_emis.company_name',
                DB::raw('dte_emis.codigoGeneracion as codigoGeneracion'),
                DB::raw('dte_emis.selloRecibido as selloRecibido'),
                DB::raw('dte_emis.fhRecibido as fhRecibido'),
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
                END AS calculated_total'),
                DB::raw('(SELECT COUNT(*) FROM sales children WHERE children.parent_sale_id = sales.id) AS children_count'));

        // Si no es admin, solo muestra los clientes ingresados por él
        if (!$isAdmin) {
            $sales->where('sales.user_id', $id_user);
        }

        // Aplicar filtros de fecha con validación
        if ($request->filled('fecha_desde') && $request->filled('fecha_hasta')) {
            // Validar formato de fechas
            $fechaDesde = $request->fecha_desde;
            $fechaHasta = $request->fecha_hasta;

            // Validar que las fechas tengan formato correcto
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaDesde) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaHasta)) {
                // Validar que fecha_desde no sea mayor que fecha_hasta
                if ($fechaDesde <= $fechaHasta) {
                    $sales->where('sales.date', '>=', $fechaDesde)
                          ->where('sales.date', '<=', $fechaHasta);
                }
            }
        } elseif ($request->filled('fecha_desde') && preg_match('/^\d{4}-\d{2}-\d{2}$/', $request->fecha_desde)) {
            // Si solo se proporciona fecha desde, mostrar desde esa fecha hasta hoy
            $sales->where('sales.date', '>=', $request->fecha_desde)
                  ->where('sales.date', '<=', date('Y-m-d'));
        } elseif ($request->filled('fecha_hasta') && preg_match('/^\d{4}-\d{2}-\d{2}$/', $request->fecha_hasta)) {
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

        if ($request->filled('tipo_documento') && $request->tipo_documento != '') {
            $sales->where('sales.typedocument_id', $request->tipo_documento);
        }

        if ($request->filled('correlativo') && trim($request->correlativo) != '') {
            $correlativo = trim($request->correlativo);
            $sales->where(function($query) use ($correlativo) {
                $query->where('sales.id', 'LIKE', "%{$correlativo}%")
                      ->orWhere('dte_emis.id_doc', 'LIKE', "%{$correlativo}%");
            });
        }

        if ($request->filled('cliente_id') && $request->cliente_id != '') {
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

        // Obtener las ventas filtradas ordenadas por fecha y hora de creación descendente
        $sales = $sales->orderBy('sales.date', 'desc')
                       ->orderBy('sales.created_at', 'desc')
                       ->get();

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
            'line_provider_id' => 'nullable|numeric|exists:providers,id',
            'fpago' => 'required',
            'fee' => 'nullable|numeric',
            'reserva' => 'nullable|string',
            'ruta' => 'nullable|string',
            'destino' => 'nullable|string',
            'linea' => 'nullable|string',
            'canal' => 'nullable|string',
            'description' => 'nullable|string',
            'tipoVenta' => 'nullable|string',
            // Campos adicionales para CLQ
            'clq_tipo_documento' => 'nullable|string|max:10',
            'clq_tipo_generacion' => 'nullable|string|max:2',
            'clq_numero_documento' => 'nullable|string|max:100',
            'clq_fecha_generacion' => 'nullable|date',
            'clq_observaciones' => 'nullable|string',
            'clq_total_gravadas' => 'nullable|numeric',
            'clq_total_exentas' => 'nullable|numeric',
            'clq_total_no_sujetas' => 'nullable|numeric',
        ]);

        // Normalizar valores nulos
        $acuenta = $request->input('acuenta', '');
        $line_provider_id = $request->input('line_provider_id', null);
        $fee = $request->input('fee', 0);
        $reserva = $request->input('reserva', '');
        $ruta = $request->input('ruta', '');
        $destino = $request->input('destino', '');
        $linea = $request->input('linea', '');
        $canal = $request->input('canal', '');
        $description = $request->input('description', '');
        $tipoVenta = $request->input('tipoVenta', 'gravada');
        $retencion_agente = $request->input('retencion_agente', 0);
        $detainedP = $request->input('detainedP', 0);

        // Campos adicionales CLQ
        $clq_tipo_documento = $request->input('clq_tipo_documento', '');
        $clq_tipo_generacion = $request->input('clq_tipo_generacion', '');
        $clq_numero_documento = $request->input('clq_numero_documento', '');
        $clq_fecha_generacion = $request->input('clq_fecha_generacion', null);
        $clq_observaciones = $request->input('clq_observaciones', '');
        $clq_total_gravadas = $request->input('clq_total_gravadas', 0);
        $clq_total_exentas = $request->input('clq_total_exentas', 0);
        $clq_total_no_sujetas = $request->input('clq_total_no_sujetas', 0);

        $result = $this->savefactemp(
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
            $tipoVenta,
            $retencion_agente,
            $detainedP,
            $clq_tipo_documento,
            $clq_tipo_generacion,
            $clq_numero_documento,
            $clq_fecha_generacion,
            $clq_observaciones,
            $clq_total_gravadas,
            $clq_total_exentas,
            $clq_total_no_sujetas
        );

        // Si tenemos line_provider_id, actualizar el salesdetail recién creado
        if ($line_provider_id && $result->status() == 200) {
            $responseData = json_decode($result->getContent(), true);
            if (isset($responseData['idsaledetail'])) {
                $salesdetail = Salesdetail::find($responseData['idsaledetail']);
                if ($salesdetail) {
                    $salesdetail->line_provider_id = $line_provider_id;
                    $salesdetail->save();
                }
            }
        }

        return $result;
    }

    /**
     * Actualiza metadatos simples de la venta (cliente y/o fecha) de forma segura.
     */
    public function updateMeta(Request $request)
    {
        $request->validate([
            'sale_id' => 'required|numeric|exists:sales,id',
            'client_id' => 'nullable|numeric|exists:clients,id',
            'date' => 'nullable|date|before_or_equal:today',
        ]);

        $sale = Sale::find($request->sale_id);

        // Permitir cambiar cliente en borrador o antes de confirmar
        if ($request->filled('client_id')) {
            $sale->client_id = $request->client_id;
        }

        // Permitir ajustar fecha antes de crear DTE (solo en typesale borrador 2)
        if ($request->filled('date') && (string)$sale->typesale !== '1') {
            $sale->date = $request->date;
        }

        $sale->save();

        return response()->json(['ok' => true]);
    }

    public function savefactemp($idsale, $clientid, $productid, $cantidad, $price, $pricenosujeta, $priceexenta, $pricegravada, $ivarete13, $renta, $ivarete, $acuenta, $fpago, $fee, $reserva, $ruta, $destino, $linea, $canal, $description, $tipoVenta = 'gravada', $retencion_agente = 0, $detainedP = 0, $clq_tipo_documento = '', $clq_tipo_generacion = '', $clq_numero_documento = '', $clq_fecha_generacion = null, $clq_observaciones = '', $clq_total_gravadas = 0, $clq_total_exentas = 0, $clq_total_no_sujetas = 0)
    {
        // Limpiar parámetros que vienen como 'SIN_VALOR'
        $acuenta = ($acuenta === 'SIN_VALOR') ? '' : $acuenta;
        $reserva = ($reserva === 'SIN_VALOR') ? '' : $reserva;
        $ruta = ($ruta === 'SIN_VALOR') ? '' : $ruta;
        $destino = ($destino === 'SIN_VALOR') ? '' : $destino;
        $linea = ($linea === 'SIN_VALOR') ? '' : $linea;
        $canal = ($canal === 'SIN_VALOR') ? '' : $canal;
        $description = ($description === 'SIN_VALOR') ? '' : $description;


        DB::beginTransaction();

        try {
            $id_user = auth()->user()->id;
            $sale = Sale::find($idsale);
            $sale->client_id = $clientid;
            $sale->acuenta = $acuenta;
            $sale->waytopay = $fpago;
            $sale->retencion_agente = $retencion_agente;
            $sale->save();
            // Lógica basada en el tipo de venta (como en Roma Copies)
            if ($tipoVenta === 'gravada') {
                // Venta gravada: calcular IVA normalmente
                $ivafac = round($pricegravada - ($pricegravada / 1.13), 8);
                $pricegravadafac = round($pricegravada / 1.13, 8);

                if ($pricegravada != "0.00") {
                    $priceunitariofac = round($pricegravadafac / $cantidad, 8);
                } else {
                    $priceunitariofac = round($price, 8);
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
                // Venta exenta: priceunit incluye precio + fee (sin IVA)
                $feesiniva = round($fee / 1.13, 8);
                $priceunitariofac = round($price + $feesiniva, 8);
                $pricegravadafac = 0.00; // No va en gravadas (el fee va en pricesale abajo)
                $ivafac = 0.00; // No genera IVA del producto
            } elseif ($tipoVenta === 'nosujeta' || $tipoVenta === 'no_sujeta') {
                // Venta no sujeta: priceunit incluye precio + fee (sin IVA)
                $feesiniva = round($fee / 1.13, 8);
                $priceunitariofac = round($price + $feesiniva, 8);
                $pricegravadafac = 0.00; // No va en gravadas (el fee va en pricesale abajo)
                $ivafac = 0.00; // No genera IVA del producto
            } else {
                // Por defecto, tratar como gravada
                $ivafac = round($pricegravada - ($pricegravada / 1.13), 8);
                $pricegravadafac = round($pricegravada / 1.13, 8);

                if ($pricegravada != "0.00") {
                    $priceunitariofac = round($pricegravadafac / $cantidad, 8);
                } else {
                    $priceunitariofac = round($price + $fee, 8);
                }

                if ($sale->typedocument_id == '8') {
                    // Sujeto excluido: precio con IVA, pero IVA = 0
                    $priceunitariofac = $price + $fee;
                    $pricegravadafac = $pricegravada;
                    $ivafac = 0.00;
                } elseif ($sale->typedocument_id == '3' || $sale->typedocument_id == '2') {
                    // Crédito fiscal y Comprobante de Liquidación: el precio ya viene sin IVA, guardarlo tal cual
                    $priceunitariofac = round($price, 8); // Precio unitario sin IVA tal cual
                    $pricegravadafac = round($price * $cantidad, 8); // Subtotal sin IVA
                    $ivafac = round($pricegravadafac * 0.13, 8); // IVA = 13% del subtotal sin IVA
                }
            }
            // IVA al fee - EL FEE SIEMPRE LLEVA IVA
            if ($fee > 0) {
                $feesiniva = round($fee / 1.13, 8);
                $ivafee = round($fee - $feesiniva, 8);
            } else {
                $feesiniva = 0.00;
                $ivafee = 0.00;
            }

            // Para ventas gravadas (Crédito Fiscal, Comprobante de Liquidación y Factura), incluir fee en priceunit y pricesale
            if ($tipoVenta === 'gravada') {
                if ($sale->typedocument_id == '3' || $sale->typedocument_id == '2') {
                    // Crédito Fiscal y Comprobante de Liquidación: precio ya sin IVA + fee sin IVA
                    $priceunitariofac = round(($price + $feesiniva), 8);
                    $pricegravadafac = round($priceunitariofac * $cantidad, 8);
                    // IVA del total sin IVA (precio + fee)
                    $ivafac = round($pricegravadafac * 0.13, 8);
                } else {
                    // Factura: precio con IVA + fee con IVA
                    $preciounitariosiniva = round($price / 1.13, 8);
                    $priceunitariofac = round(($preciounitariosiniva + $feesiniva), 8);
                    $pricegravadafac = round(($preciounitariosiniva + $feesiniva) * $cantidad, 8);
                    // IVA del total sin IVA (precio + fee)
                    $ivafac = round($pricegravadafac * 0.13, 8);
                }
            }

            $saledetails = new Salesdetail();
            $saledetails->sale_id = $idsale;
            $saledetails->product_id = $productid;
            $saledetails->amountp = $cantidad;

            // IMPORTANTE: Para CLQ (typedocument_id == '2'), usar los totales directamente del documento original
            // NOTA: Los valores se guardan siempre como positivos en la BD. La conversión a negativos se hace en el helper clq() al generar el JSON
            if ($sale->typedocument_id == '2' && (abs($clq_total_gravadas) > 0 || abs($clq_total_exentas) > 0 || abs($clq_total_no_sujetas) > 0)) {
                // Usar valores absolutos para asegurar que siempre guardamos valores positivos
                $clq_total_gravadas_abs = abs($clq_total_gravadas);
                $clq_total_exentas_abs = abs($clq_total_exentas);
                $clq_total_no_sujetas_abs = abs($clq_total_no_sujetas);

                // Para CLQ, usar los totales del documento original directamente (siempre positivos en BD)
                $saledetails->pricesale = round($clq_total_gravadas_abs, 8);
                $saledetails->exempt = round($clq_total_exentas_abs, 8);
                $saledetails->nosujeta = round($clq_total_no_sujetas_abs, 8);

                // Calcular IVA solo de las gravadas (13% de gravadas)
                $saledetails->detained13 = round($clq_total_gravadas_abs * 0.13, 8);

                // Calcular priceunit como promedio (total / cantidad)
                $total_sin_iva = $clq_total_gravadas_abs + $clq_total_exentas_abs + $clq_total_no_sujetas_abs;
                if ($cantidad > 0) {
                    $saledetails->priceunit = round($total_sin_iva / $cantidad, 8);
                } else {
                    $saledetails->priceunit = round($total_sin_iva, 8);
                }
            } else {
                // Lógica normal para otros documentos
                // Guardar con precisión 8 (BD 12,8)
                $saledetails->priceunit = round($priceunitariofac, 8);
                $saledetails->pricesale = round($pricegravadafac, 8);

                // Asignar valores según el tipo de venta
                if ($tipoVenta === 'gravada') {
                    $saledetails->nosujeta = 0.00;
                    $saledetails->exempt = 0.00;
                    $saledetails->detained13 = round($ivafac, 8);

                } elseif ($tipoVenta === 'exenta') {
                    $saledetails->nosujeta = 0.00;
                    $saledetails->exempt = $priceexenta;
                    // Si hay fee, va en pricesale (gravadas)
                    if ($fee > 0) {
                        $saledetails->pricesale = round($feesiniva * $cantidad, 8); // Fee sin IVA como gravadas
                        $saledetails->detained13 = round($ivafee * $cantidad, 8); // IVA del fee
                    } else {
                        $saledetails->pricesale = 0.00;
                        $saledetails->detained13 = 0.00;
                    }

                } elseif ($tipoVenta === 'nosujeta' || $tipoVenta === 'no_sujeta') {
                    $saledetails->nosujeta = $pricenosujeta;
                    $saledetails->exempt = 0.00;
                    // Si hay fee, va en pricesale (gravadas)
                    if ($fee > 0) {
                        $saledetails->pricesale = round($feesiniva * $cantidad, 8); // Fee sin IVA como gravadas
                        $saledetails->detained13 = round($ivafee * $cantidad, 8); // IVA del fee
                    } else {
                        $saledetails->pricesale = 0.00;
                        $saledetails->detained13 = 0.00;
                    }

                } else {
                    // Por defecto, usar valores originales
                    $saledetails->nosujeta = $pricenosujeta;
                    $saledetails->exempt = $priceexenta;
                    $saledetails->detained13 = round($ivafac, 8);
                }
            }

            $saledetails->detained = round($ivarete, 8);
            $saledetails->detainedP = 0;
            $saledetails->renta = ($sale->typedocument_id != '8') ? round(0.00, 8) : round($renta * $cantidad, 8);
            // fee y feeiva se guardan para reportes internos, NO se usan para generar DTE
            $saledetails->fee = round($feesiniva * $cantidad, 8);
            $saledetails->feeiva = round($ivafee * $cantidad, 8);
            $saledetails->reserva = $reserva;
            $saledetails->ruta = $ruta;
            $saledetails->destino = $destino;
            $saledetails->linea = $linea;
            $saledetails->canal = $canal;
            $saledetails->user_id = $id_user;
            $saledetails->description = $description;

            // Campos adicionales para Comprobante de Liquidación (CLQ)
            if ($sale->typedocument_id == '2') {
                $saledetails->clq_tipo_documento = $clq_tipo_documento;
                $saledetails->clq_tipo_generacion = $clq_tipo_generacion;
                $saledetails->clq_numero_documento = $clq_numero_documento;
                $saledetails->clq_fecha_generacion = $clq_fecha_generacion;
                $saledetails->clq_observaciones = $clq_observaciones;
            }

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
        $saleId = base64_decode($corr);
        $sale = Sale::with('salesdetails')->find($saleId);

        // Verificar si tiene múltiples proveedores Y NO es hijo (evitar recursión)
        // Si parent_sale_id es null, NO es hijo
        if (is_null($sale->parent_sale_id) && $this->hasMultipleProviders($sale)) {
            return $this->processMultiProviderSale($sale, $amount);
        }

        // Flujo normal para venta simple O venta hija
        DB::beginTransaction();
        try {
            $amount = substr($amount, 1);
            $salesave = Sale::find($saleId);
            $salesave->totalamount = $amount;
            $salesave->typesale = 1; // finalizar venta como en RomaCopies
            //buscar el correlativo actual
            $newCorr = Correlativo::join('typedocuments as tdoc', 'tdoc.type', '=', 'docs.id_tipo_doc')
                ->where('tdoc.id', '=', $salesave->typedocument_id)
                ->where('docs.id_empresa', '=', $salesave->company_id)
                ->select('docs.actual', 'docs.id')
                ->get();
                if(empty($newCorr)){
                    return response()->json(['error' => 'No se encontró el correlativo', 'message' => 'No se encontró el correlativo'], 404);
                }
            $salesave->nu_doc = $newCorr[0]->actual;
            // Asignar provider_id desde el primer detalle de venta si existe
            if (!empty($sale->salesdetails) && isset($sale->salesdetails[0]) && !empty($sale->salesdetails[0]->line_provider_id)) {
                $salesave->provider_id = $sale->salesdetails[0]->line_provider_id;
            }
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
            SUM(pricesale) gravadas,
            SUM(nosujeta+exempt+pricesale) subtotalventas,
            0 descnosujeta,
            0 descexenta,
            0 desgravada,
            0 porcedesc,
            0 totaldesc,
            NULL tributos,
            SUM(nosujeta+exempt+pricesale) subtotal,
            SUM(detained) ivarete,
            SUM(renta) rentarete,
            NULL pagos,
            SUM(detained13) iva')
                )
                ->get();
            //detalle de montos de la factura
            // El ivarete ya incluye el 1% de retención del agente si aplica
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
                "ivaRete1" => round((float)$detailsbd[0]->ivarete, 8),
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

            //informacion del producto
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
        b.fee fee,
        1 tipo_item,
        59 uniMedida,
        b.clq_tipo_documento,
        b.clq_tipo_generacion,
        b.clq_numero_documento,
        b.clq_fecha_generacion,
        b.clq_observaciones,
        b.line_provider_id,
        d.razonsocial provider_name,
        d.nit provider_nit
        FROM sales a
        INNER JOIN salesdetails b ON b.sale_id=a.id
        INNER JOIN products c ON b.product_id=c.id
        LEFT JOIN providers d ON b.line_provider_id=d.id
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

                // Envío automático de correo tras confirmación exitosa en Hacienda (solo producción)
                // No enviar por cada hijo: sendConsolidatedEmail enviará un solo correo al final del flujo multi‑proveedor
                $codEstado = $dtecreate->codEstado ?? null;
                $ambienteId = $dtecreate->ambiente_id ?? null;
                $esAceptado = in_array((string)$codEstado, ['02', '2'], true);
                $esProduccion = in_array((string)$ambienteId, ['01', '1', '0', '00'], true);

                if ($esAceptado && $esProduccion) {
                    $saleIdCorreo = (int) base64_decode($corr);
                    $saleParaCorreo = Sale::find($saleIdCorreo);
                    // Verificar si es hijo: si parent_sale_id es null, NO es hijo
                    if (!$saleParaCorreo || is_null($saleParaCorreo->parent_sale_id)) {
                        try {
                            $this->enviarCorreoAutomaticoVenta($saleIdCorreo, $dtecreate);
                            Log::info('Correo automático enviado tras confirmación exitosa de Hacienda', [
                                'dte_id' => $dtecreate->id,
                                'sale_id' => $dtecreate->sale_id,
                                'codigo_generacion' => $dtecreate->codigoGeneracion
                            ]);
                        } catch (\Exception $e) {
                            Log::error('Error enviando correo automático tras confirmación de Hacienda', [
                                'dte_id' => $dtecreate->id,
                                'sale_id' => $dtecreate->sale_id,
                                'error' => $e->getMessage()
                            ]);
                        }
                    } else {
                        Log::info('Correo automático omitido: venta hija (enviará sendConsolidatedEmail)', [
                            'sale_id' => $saleIdCorreo,
                            'parent_sale_id' => $saleParaCorreo->parent_sale_id
                        ]);
                    }
                } else {
                    Log::info('Correo automático no enviado: condición Hacienda/ambiente', [
                        'sale_id' => base64_decode($corr),
                        'codEstado' => $codEstado,
                        'ambiente_id' => $ambienteId,
                        'esAceptado' => $esAceptado,
                        'esProduccion' => $esProduccion
                    ]);
                }
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

            // Recargar la venta para obtener datos actualizados (incluyendo el DTE recién guardado)
            $salesave = Sale::find(base64_decode($corr));
            $salesave->json = json_encode($comprobante);

            // IMPORTANTE: Actualizar state = 1 cuando el DTE es confirmado por Hacienda
            // Esto aplica tanto para ventas simples como para ventas hijas
            // codEstado = '02' significa "Aceptado" y estadoHacienda = 'PROCESADO'
            if (isset($dtecreate) &&
                (($dtecreate->codEstado ?? null) === '02' ||
                 ($dtecreate->estadoHacienda ?? null) === 'PROCESADO')) {
                $salesave->state = 1; // Confirmar la venta
                Log::info('State actualizado a 1 tras confirmación de Hacienda', [
                    'sale_id' => $salesave->id,
                    'codEstado' => $dtecreate->codEstado,
                    'estadoHacienda' => $dtecreate->estadoHacienda,
                    'is_child' => !is_null($salesave->parent_sale_id),
                    'parent_sale_id' => $salesave->parent_sale_id
                ]);
            }

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
            ->leftJoin('providers', 'providers.id', '=', 'salesdetails.line_provider_id')
            ->select(
                'salesdetails.*',
                DB::raw('CASE
                    WHEN salesdetails.reserva IS NOT NULL AND salesdetails.ruta IS NOT NULL
                    THEN CONCAT(products.name, " - ", salesdetails.reserva, " - ", salesdetails.ruta)
                    WHEN salesdetails.reserva IS NOT NULL
                    THEN CONCAT(products.name, " - ", salesdetails.reserva)
                    ELSE products.name
                END as product_name'),
                'providers.razonsocial as provider_name',
                'providers.nit as provider_nit'
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
     * Buscar documentos (Facturas/CCF) para autorellenar campos de CLQ
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function searchDocumentsForCLQ(Request $request)
    {
        try {
            \Log::info('Búsqueda CLQ iniciada', $request->all());

            // Subconsulta para obtener el último DTE de emisión (no invalidación) por venta
            $dteEmisionSub = DB::table('dte')
                ->select(
                    'dte.sale_id',
                    'dte.codigoGeneracion',
                    'dte.id_doc',
                    'dte.codTransaction'
                )
                ->whereIn('dte.codTransaction', ['01','05','06'])
                ->whereRaw('dte.id = (SELECT MAX(d2.id) FROM dte d2 WHERE d2.sale_id = dte.sale_id AND d2.codTransaction IN ("01","05","06"))');

            // Subconsulta para verificar si existe un DTE de invalidación
            $dteInvalidacionSub = DB::table('dte')
                ->select('dte.sale_id')
                ->where('dte.codTransaction', '02')
                ->whereRaw('dte.sale_id = sales.id');

            $query = Sale::leftJoin('clients', 'sales.client_id', '=', 'clients.id')
                ->leftJoin('typedocuments', 'sales.typedocument_id', '=', 'typedocuments.id')
                ->leftJoinSub($dteEmisionSub, 'dte_emis', function ($join) {
                    $join->on('dte_emis.sale_id', '=', 'sales.id');
                })
                ->leftJoin('providers', 'sales.provider_id', '=', 'providers.id')
                ->select(
                    'sales.id',
                    'sales.date',
                    'sales.totalamount',
                    'sales.typedocument_id',
                    'sales.company_id',
                    'sales.provider_id',
                    'sales.parent_sale_id',
                    'sales.is_parent',
                    'sales.state',
                    'providers.razonsocial as proveedor_nombre',
                    'typedocuments.description as tipo_documento',
                    'typedocuments.codemh as codigo_mh',
                    'typedocuments.type as tipo_doc_code',
                    'dte_emis.codigoGeneracion',
                    'dte_emis.id_doc as numero_control',
                    DB::raw("CASE
                        WHEN clients.tpersona = 'J' THEN clients.comercial_name
                        WHEN clients.tpersona = 'N' THEN CONCAT_WS(' ', clients.firstname, clients.firstlastname)
                        ELSE 'Sin cliente'
                    END AS cliente_nombre"),
                    DB::raw("EXISTS(SELECT 1 FROM dte WHERE dte.sale_id = sales.id AND dte.codTransaction = '02') as es_invalidado")
                )
                // Incluir ventas confirmadas (state = 1) Y invalidadas (state = 0) de terceros
                // Las invalidadas deben tener al menos un DTE de emisión para poder ser incluidas en CLQ
                ->where(function($q) {
                    $q->where('sales.state', 1) // Confirmadas
                      ->orWhere(function($q2) {
                          // Invalidadas pero que tengan DTE de emisión y sean de terceros
                          $q2->where('sales.state', 0)
                             ->whereExists(function($subquery) {
                                 $subquery->select(DB::raw(1))
                                     ->from('dte')
                                     ->whereColumn('dte.sale_id', 'sales.id')
                                     ->whereIn('dte.codTransaction', ['01', '05', '06']);
                             })
                             ->whereNotNull('sales.provider_id'); // Asegurar que es de terceros
                      });
                })
                // Solo documentos con tercero (venta a cuenta de)
                // IMPORTANTE: Solo ventas que tienen sales.provider_id
                ->whereNotNull('sales.provider_id')
                // IMPORTANTE: El provider_id de sales debe coincidir con line_provider_id de salesdetails
                // Verificar que existe al menos un salesdetail con line_provider_id = sales.provider_id
                ->whereExists(function($subquery) {
                    $subquery->select(DB::raw(1))
                        ->from('salesdetails')
                        ->whereColumn('salesdetails.sale_id', 'sales.id')
                        ->whereColumn('salesdetails.line_provider_id', 'sales.provider_id');
                })
                // Excluir CLQ (typedocument_id = 2) de los resultados
                ->where('sales.typedocument_id', '!=', '2');

            // Primero filtrar por empresa (OBLIGATORIO)
            if ($request->filled('company_id')) {
                $query->where('sales.company_id', $request->company_id);
                \Log::info('Filtrando por empresa: ' . $request->company_id);
            } else {
                \Log::warning('No se proporcionó company_id');
                return response()->json([
                    'success' => false,
                    'message' => 'No se proporcionó la empresa',
                    'data' => []
                ]);
            }

            // NUEVO: Filtrar por cliente (proveedor/tercero de la venta original)
            // El cliente del CLQ debe ser el proveedor de la factura/CCF original
            // Comparar por NIT en lugar de ID
            // IMPORTANTE: El provider_id de la venta debe coincidir con el cliente del CLQ (por NIT)
            if ($request->filled('client_id')) {
                // Obtener el NIT del cliente seleccionado
                $clienteCLQ = \App\Models\Client::find($request->client_id);
                if ($clienteCLQ && $clienteCLQ->nit) {
                    $nitCliente = str_replace('-', '', $clienteCLQ->nit);
                    // Comparar NIT del proveedor con NIT del cliente del CLQ
                    // El provider_id de sales debe tener un NIT que coincida con el cliente del CLQ
                    $query->whereRaw('REPLACE(providers.nit, "-", "") = ?', [$nitCliente]);
                    \Log::info('Filtrando por NIT del proveedor = NIT del cliente CLQ', [
                        'client_id' => $request->client_id,
                        'nit_cliente' => $nitCliente,
                        'nota' => 'El sales.provider_id debe coincidir con line_provider_id y el NIT del provider debe coincidir con el cliente del CLQ'
                    ]);
                } else {
                    \Log::warning('Cliente del CLQ no encontrado o sin NIT', ['client_id' => $request->client_id]);
                }
            }

            // NUEVO: Excluir documentos que ya tienen CLQ (liquidados)
            // Un documento está liquidado si existe un CLQ que lo referencia por codigoGeneracion
            // IMPORTANTE: Los documentos invalidados también pueden estar liquidados, así que verificamos ambos
            $query->where(function($q) {
                // Si no tiene codigoGeneracion, no puede estar liquidado (mostrarlo)
                $q->whereNull('dte_emis.codigoGeneracion')
                  // Si tiene codigoGeneracion, verificar que NO esté en ningún CLQ activo
                  ->orWhereNotExists(function($subquery) {
                      $subquery->select(DB::raw(1))
                          ->from('salesdetails as clq_sd')
                          ->join('sales as clq_sales', 'clq_sales.id', '=', 'clq_sd.sale_id')
                          ->whereColumn('clq_sd.clq_numero_documento', 'dte_emis.codigoGeneracion')
                          ->where('clq_sales.typedocument_id', '2') // Solo CLQ
                          ->where('clq_sales.state', '!=', 0); // Excluir CLQ anulados
                  });
            });

            // Filtrar por tipo de documento si se especifica
            if ($request->filled('tipo_documento')) {
                $query->where('sales.typedocument_id', $request->tipo_documento);
            } else {
                // Por defecto, solo Facturas (6) y CCF (3)
                $query->whereIn('sales.typedocument_id', [3, 6]);
            }

            // Filtrar por número de documento
            if ($request->filled('numero_doc')) {
                $query->where('sales.id', 'LIKE', '%' . $request->numero_doc . '%');
            }

            // Filtrar por rango de fechas
            if ($request->filled('fecha_desde')) {
                $query->whereDate('sales.date', '>=', $request->fecha_desde);
            }

            if ($request->filled('fecha_hasta')) {
                $query->whereDate('sales.date', '<=', $request->fecha_hasta);
            }

            // Filtrar por cliente
            if ($request->filled('cliente')) {
                $query->where(function($q) use ($request) {
                    $q->where('clients.firstname', 'LIKE', '%' . $request->cliente . '%')
                      ->orWhere('clients.firstlastname', 'LIKE', '%' . $request->cliente . '%')
                      ->orWhere('clients.comercial_name', 'LIKE', '%' . $request->cliente . '%');
                });
            }

            // Log de la query SQL para debugging
            $sql = $query->toSql();
            $bindings = $query->getBindings();
            \Log::info('Query SQL CLQ:', ['sql' => $sql, 'bindings' => $bindings]);

            // Ordenar por fecha descendente (más recientes primero)
            // Aumentar límite a 1000 para mostrar más resultados
            $documentos = $query->orderBy('sales.date', 'desc')
                ->orderBy('sales.id', 'desc')
                ->limit(1000)
                ->get();

            \Log::info('Documentos CLQ encontrados', [
                'count' => $documentos->count(),
                'ids' => $documentos->pluck('id')->toArray(),
                'provider_ids' => $documentos->pluck('provider_id')->toArray(),
                'nota' => 'Solo ventas con sales.provider_id que coincide con line_provider_id de salesdetails'
            ]);

            // Si no hay resultados, hacer diagnóstico detallado
            if ($documentos->count() === 0 && $request->filled('company_id')) {
                // Incluir tanto confirmadas como invalidadas en el diagnóstico
                $totalVentas = Sale::where('sales.company_id', $request->company_id)
                    ->whereIn('sales.state', [0, 1]) // Confirmadas e invalidadas
                    ->whereIn('sales.typedocument_id', [3, 6])
                    ->count();

                // Ventas con tercero (provider_id) - incluir todas las ventas hijas
                // IMPORTANTE: Solo buscar en ventas hijas (is_parent = 0) que tienen provider_id
                // Las ventas padre no tienen provider_id, solo las hijas lo tienen
                // Incluir tanto confirmadas como invalidadas
                $ventasConTercero = Sale::where('sales.company_id', $request->company_id)
                    ->whereIn('sales.state', [0, 1]) // Confirmadas e invalidadas
                    ->whereIn('sales.typedocument_id', [3, 6])
                    ->whereNotNull('sales.provider_id')
                    ->where('sales.typedocument_id', '!=', '2') // Excluir CLQ
                    ->count();

                // Log detallado de ventas con tercero (confirmadas e invalidadas)
                $ventasConTerceroDetalle = Sale::where('sales.company_id', $request->company_id)
                    ->whereIn('sales.state', [0, 1]) // Confirmadas e invalidadas
                    ->whereIn('sales.typedocument_id', [3, 6])
                    ->whereNotNull('sales.provider_id')
                    ->where('sales.typedocument_id', '!=', '2')
                    ->select('sales.id', 'sales.provider_id', 'sales.typedocument_id', 'sales.date', 'sales.is_parent', 'sales.parent_sale_id', 'sales.state')
                    ->get();

                \Log::info('Ventas con tercero encontradas', [
                    'count' => $ventasConTercero,
                    'detalle' => $ventasConTerceroDetalle->toArray()
                ]);

                // También verificar ventas que tienen line_provider_id en salesdetails pero no provider_id en sales
                // Esto puede pasar si la venta aún no se ha separado en hijas
                // Incluir tanto confirmadas como invalidadas
                $ventasConLineProvider = DB::table('sales')
                    ->join('salesdetails', 'sales.id', '=', 'salesdetails.sale_id')
                    ->where('sales.company_id', $request->company_id)
                    ->whereIn('sales.state', [0, 1]) // Confirmadas e invalidadas
                    ->whereIn('sales.typedocument_id', [3, 6])
                    ->where('sales.typedocument_id', '!=', '2')
                    ->whereNotNull('salesdetails.line_provider_id')
                    ->whereNull('sales.provider_id')
                    ->distinct()
                    ->count('sales.id');

                \Log::info('Ventas con line_provider_id pero sin provider_id (pendientes de separar)', [
                    'count' => $ventasConLineProvider
                ]);

                // Ventas con el tercero específico (comparando por NIT)
                $ventasConTerceroEspecifico = 0;
                $ventasConTerceroEspecificoDetalle = collect();
                if ($request->filled('client_id')) {
                    $clienteCLQ = \App\Models\Client::find($request->client_id);
                    if ($clienteCLQ && $clienteCLQ->nit) {
                        $nitCliente = str_replace('-', '', $clienteCLQ->nit);
                        // Incluir tanto confirmadas como invalidadas
                        $ventasConTerceroEspecifico = DB::table('sales')
                            ->join('providers', 'sales.provider_id', '=', 'providers.id')
                            ->where('sales.company_id', $request->company_id)
                            ->whereIn('sales.state', [0, 1]) // Confirmadas e invalidadas
                            ->whereIn('sales.typedocument_id', [3, 6])
                            ->whereNotNull('sales.provider_id')
                            ->where('sales.typedocument_id', '!=', '2') // Excluir CLQ
                            ->whereRaw('REPLACE(providers.nit, "-", "") = ?', [$nitCliente])
                            ->count();

                        // Obtener detalles de estas ventas (confirmadas e invalidadas)
                        $ventasConTerceroEspecificoDetalle = DB::table('sales')
                            ->join('providers', 'sales.provider_id', '=', 'providers.id')
                            ->where('sales.company_id', $request->company_id)
                            ->whereIn('sales.state', [0, 1]) // Confirmadas e invalidadas
                            ->whereIn('sales.typedocument_id', [3, 6])
                            ->whereNotNull('sales.provider_id')
                            ->where('sales.typedocument_id', '!=', '2')
                            ->whereRaw('REPLACE(providers.nit, "-", "") = ?', [$nitCliente])
                            ->select('sales.id', 'sales.provider_id', 'sales.typedocument_id', 'sales.date', 'sales.is_parent', 'sales.parent_sale_id', 'sales.state', 'providers.nit as provider_nit')
                            ->get();

                        \Log::info('Ventas con tercero específico (NIT)', [
                            'nit_buscado' => $nitCliente,
                            'count' => $ventasConTerceroEspecifico,
                            'detalle' => $ventasConTerceroEspecificoDetalle->toArray()
                        ]);
                    }
                }

                $nitBuscado = null;
                if ($request->filled('client_id')) {
                    $clienteCLQ = \App\Models\Client::find($request->client_id);
                    if ($clienteCLQ && $clienteCLQ->nit) {
                        $nitBuscado = str_replace('-', '', $clienteCLQ->nit);
                    }
                }

                \Log::info('Diagnóstico CLQ', [
                    'total_ventas' => $totalVentas,
                    'ventas_con_tercero' => $ventasConTercero,
                    'ventas_con_tercero_especifico' => $ventasConTerceroEspecifico,
                    'client_id' => $request->client_id,
                    'nit_buscado' => $nitBuscado
                ]);

                return response()->json([
                    'success' => true,
                    'data' => [],
                    'count' => 0,
                    'debug' => [
                        'total_ventas_empresa' => $totalVentas,
                        'ventas_con_tercero' => $ventasConTercero,
                        'ventas_con_tercero_especifico' => $ventasConTerceroEspecifico,
                        'nit_buscado' => $nitBuscado ?? null,
                        'client_id' => $request->client_id ?? null,
                        'filtros_aplicados' => $request->except('_token'),
                        'ventas_detalle' => $ventasConTerceroEspecificoDetalle->map(function($v) {
                            return [
                                'id' => $v->id,
                                'provider_id' => $v->provider_id,
                                'provider_nit' => $v->provider_nit,
                                'is_parent' => $v->is_parent,
                                'parent_sale_id' => $v->parent_sale_id,
                                'date' => $v->date
                            ];
                        })->toArray(),
                        'ventas_pendientes_separar' => $ventasConLineProvider ?? 0,
                        'mensaje' => $ventasConTerceroEspecifico > 0
                            ? 'Hay ' . $ventasConTerceroEspecifico . ' venta(s) con ese tercero pero ya están liquidadas o no cumplen otros filtros'
                            : ($ventasConTercero > 0
                                ? 'Hay ' . $ventasConTercero . ' venta(s) con terceros pero no del proveedor seleccionado (NIT buscado: ' . ($nitBuscado ?? 'N/A') . ')'
                                : ($ventasConLineProvider > 0
                                    ? 'Hay ' . $ventasConLineProvider . ' venta(s) con productos a terceros pero aún no se han separado. Confirma la venta primero.'
                                    : 'No hay ventas a terceros confirmadas en esta empresa'))
                    ]
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $documentos,
                'count' => $documentos->count(),
                'filtros' => $request->except('_token')
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en searchDocumentsForCLQ: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Error al buscar documentos: ' . $e->getMessage(),
                'data' => [],
                'error_details' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    /**
     * Obtener detalles de una venta para autorellenar CLQ
     * Devuelve solo los totales/montos, no los productos individuales
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getSaleDetailsForCLQ($id)
    {
        try {
            // Incluir tanto ventas confirmadas (state = 1) como invalidadas (state = 0)
            // Las invalidadas deben tener al menos un DTE de emisión para poder ser incluidas en CLQ
            $sale = Sale::with(['salesdetails'])
                ->where('id', $id)
                ->where(function($q) {
                    $q->where('state', 1) // Confirmadas
                      ->orWhere(function($q2) {
                          // Invalidadas pero que tengan DTE de emisión
                          $q2->where('state', 0)
                             ->whereExists(function($subquery) {
                                 $subquery->select(DB::raw(1))
                                     ->from('dte')
                                     ->whereColumn('dte.sale_id', 'sales.id')
                                     ->whereIn('dte.codTransaction', ['01', '05', '06']);
                             });
                      });
                })
                ->first();

            if (!$sale) {
                return response()->json([
                    'success' => false,
                    'message' => 'Venta no encontrada o no cumple los requisitos para CLQ',
                    'data' => []
                ], 404);
            }

            // Calcular totales de todos los detalles
            $total_nosujeta = $sale->salesdetails->sum('nosujeta') ?? 0;
            $total_exenta = $sale->salesdetails->sum('exempt') ?? 0;
            $total_gravada = $sale->salesdetails->sum('pricesale') ?? 0;
            $total_iva = $sale->salesdetails->sum('detained13') ?? 0;
            $total_fee = $sale->salesdetails->sum('fee') ?? 0;
            $total_feeiva = $sale->salesdetails->sum('feeiva') ?? 0;

            // Determinar tipo de venta predominante
            $tipo_venta = 'gravada';
            if ($total_nosujeta > 0 && $total_nosujeta >= $total_exenta && $total_nosujeta >= $total_gravada) {
                $tipo_venta = 'nosujeta';
            } elseif ($total_exenta > 0 && $total_exenta >= $total_gravada) {
                $tipo_venta = 'exenta';
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'totales' => [
                        'nosujeta' => $total_nosujeta,
                        'exenta' => $total_exenta,
                        'gravada' => $total_gravada,
                        'iva' => $total_iva,
                        'fee' => $total_fee,
                        'feeiva' => $total_feeiva,
                        'total_general' => $sale->totalamount ?? 0,
                    ],
                    'tipo_venta' => $tipo_venta,
                    'cantidad' => 1 // Siempre cantidad 1 para liquidación
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en getSaleDetailsForCLQ: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener detalles: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Determinar tipo de venta basado en los montos del detalle
     */
    private function determinarTipoVenta($detail)
    {
        if (($detail->nosujeta ?? 0) > 0) {
            return 'nosujeta';
        } elseif (($detail->exempt ?? 0) > 0) {
            return 'exenta';
        } else {
            return 'gravada';
        }
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
            SUM(pricesale) gravadas,
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
            SUM(detained13) iva')
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
    WHERE a.id = $anular->client_id";
            $cliente = DB::select(DB::raw($querycliente));
            // Determinar tipo y número de documento del cliente
            $tipoDocumentoCliente = '36';
            $numDocumentoCliente = null;
            if (!empty($cliente)) {
                $cli = $cliente[0];
                if (isset($cli->extranjero) && intval($cli->extranjero) === 1) {
                    $tipoDocumentoCliente = '03';
                    $numDocumentoCliente = isset($cli->pasaporte) ? str_replace('-', '', $cli->pasaporte) : null;
                } else if (!empty($cli->nit)) {
                    $tipoDocumentoCliente = '36';
                    $numDocumentoCliente = str_replace('-', '', $cli->nit);
                }
            }

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
                "tipoDocumento"             => $tipoDocumentoCliente,
                "numDocumento"              => $numDocumentoCliente,
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
                Log::warning('Invalidación rechazada por MH', [
                    'sale_id' => $idFactura,
                    'respuesta' => $respuesta
                ]);
                return response()->json([
                    'success' => false,
                    'message' => $respuesta['descripcionMsg'] ?? 'Documento rechazado por Hacienda',
                    'code' => $respuesta["codEstado"] ?? null,
                    'descripcionMsg' => $respuesta['descripcionMsg'] ?? null,
                    'codigoMsg' => $respuesta['codigoMsg'] ?? null,
                    'clasificaMsg' => $respuesta['clasificaMsg'] ?? null,
                    'observacionesMsg' => $respuesta['observacionesMsg'] ?? null,
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
                Log::error('Falló guardado de invalidación', [
                    'sale_id' => $idFactura,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Error al invalidar el documento',
                    'res' => 0
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Excepción en invalidación', [
                'sale_id' => $idFactura ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
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
        $email = $request->email;

        // Verificar si existe DTE para esta venta
        $dte = \App\Models\Dte::where('sale_id', $id_factura)->first();

        // Debug: Log para verificar qué se está encontrando
        Log::info('Debug envia_correo', [
            'id_factura' => $id_factura,
            'dte_encontrado' => $dte ? 'Sí' : 'No',
            'dte_json' => $dte ? ($dte->json ? 'Sí' : 'No') : 'N/A',
            'dte_codigoGeneracion' => $dte ? $dte->codigoGeneracion : 'N/A'
        ]);

        if ($dte && $dte->json) {
            // Si hay DTE, usar PDF oficial y JSON enviado
            $pdf = $this->genera_pdf($id_factura);
            $json_root = json_decode($dte->json, true); // Decodificar como array
            $json_enviado = $json_root['json']['json_enviado'] ?? $json_root;
            $json = $this->limpiarJsonParaCorreo($json_enviado);

            // Debug: Log para verificar el JSON
            Log::info('Debug JSON en envia_correo', [
                'json_root_keys' => $json_root ? array_keys($json_root) : 'No keys',
                'json_enviado_keys' => $json_enviado ? array_keys($json_enviado) : 'No keys',
                'codigoGeneracion_en_json' => $json_enviado['identificacion']['codigoGeneracion'] ?? 'No encontrado'
            ]);

            // Obtener nombre de archivo basado en código de generación
            $nombreArchivo = $this->obtenerNombreArchivo($dte, $json_enviado);

            // Debug: Log para verificar el nombre del archivo
            Log::info('Debug nombre archivo', [
                'nombreArchivo_final' => $nombreArchivo,
                'dte_codigoGeneracion' => $dte->codigoGeneracion
            ]);

            $archivos = [
                $nombreArchivo . '.pdf' => $pdf->output(),
                $nombreArchivo . '.json' => $json
            ];

            $data = [
                "nombre" => $json_enviado['receptor']['nombre'] ?? $nombre,
                "numero" => $numero,
                "json" => $json_enviado // Ya es un array
            ];

            // CORRECCIÓN: Asegurar que el JSON sea un array, no string
            if (is_string($data["json"])) {
                $data["json"] = json_decode($data["json"], true);
                Log::info('JSON convertido de string a array en envia_correo');
            }

            // Debug: Log para verificar qué se está enviando al correo
            Log::info('Debug data para correo', [
                'json_tipo' => gettype($data["json"]),
                'json_keys' => is_array($data["json"]) ? array_keys($data["json"]) : 'No es array',
                'identificacion_existe' => isset($data["json"]["identificacion"]) ? 'Sí' : 'No'
            ]);

            $asunto = "Comprobante de Venta No." . ($json_enviado['identificacion']['numeroControl'] ?? $numero) . ' de Proveedor: ' . ($json_enviado['emisor']['nombre'] ?? 'Empresa');
        } else {
            // Si no hay DTE, usar PDF local
            $pdf = $this->genera_pdflocal($id_factura);

            $archivos = [
                'venta_' . $id_factura . '.pdf' => $pdf->output()
            ];

            $data = [
                "nombre" => $nombre,
                "numero" => $numero,
                "json" => null
            ];

            $asunto = "Comprobante de Venta No." . $numero;
        }

        $correo = new EnviarCorreo($data);
        $correo->subject($asunto);
        foreach ($archivos as $nombreArchivo => $rutaArchivo) {
            $correo->attachData($rutaArchivo, $nombreArchivo);
        }

        Mail::to($email)->send($correo);
    }

    /**
     * Envía correo electrónico con factura PDF (para uso offline - sin JSON de Hacienda)
     * Esta función verifica si hay DTE y redirige a la función apropiada
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function enviar_correo_offline(Request $request)
    {
        try {
            // Validar datos requeridos
            $request->validate([
                'id_factura' => 'required|integer|exists:sales,id',
                'email' => 'required|email',
                'nombre_cliente' => 'nullable|string',
            ]);

            $id_factura = $request->id_factura;
            $email = $request->email;
            $nombre_cliente = $request->nombre_cliente;

            // Verificar si es una venta padre
            $sale = Sale::find($id_factura);
            if ($sale && $sale->is_parent) {
                // Si es padre, enviar correo con todos los hijos
                return $this->enviar_correo_padre($request, $sale);
            }

            // Verificar si existe DTE para esta venta
            $dte = \App\Models\Dte::where('sale_id', $id_factura)->first();

            if ($dte && $dte->json) {
                // Si existe DTE, usar el método con DTE (PDF + JSON)
                return $this->enviar_correo_con_dte($request);
            } else {
                // Si no existe DTE, usar el método offline (solo PDF)
                return $this->enviar_correo_sin_dte($request);
            }

        } catch (\Exception $e) {
            Log::error("Error en enviar_correo_offline para venta ID: {$request->id_factura} - " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar el correo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Envía correo con DTE (PDF + JSON)
     */
    private function enviar_correo_con_dte(Request $request)
    {
        try {
            $id_factura = $request->id_factura;
            $email = $request->email;
            $nombre_cliente = $request->nombre_cliente;

            // Obtener datos de la venta con DTE
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
                    'sales.json',
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

            if ($comprobante->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró información de la venta con DTE'
                ], 404);
            }

            // Generar PDF oficial
            $pdf = $this->genera_pdf($id_factura);

            // Usar sales.json que contiene la respuesta de Hacienda
            $sale_json = $comprobante[0]->json;
            $json_root = json_decode($sale_json, true);

            // Verificar que se decodificó correctamente
            if (!is_array($json_root)) {
                $json_error = json_last_error_msg();
                Log::error('Error: Sale JSON no se pudo decodificar como array', [
                    'sale_json' => $sale_json,
                    'json_error' => $json_error
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Error al procesar el JSON de la venta: ' . $json_error
                ], 500);
            }

            // Obtener json_enviado de la respuesta de Hacienda
            $json_enviado = $json_root['json']['json_enviado'] ?? $json_root;

            $json = $this->limpiarJsonParaCorreo($json_enviado);

            // Obtener nombre de archivo basado en sello de recepción
            $dte = \App\Models\Dte::where('sale_id', $id_factura)->first();
            $nombreArchivo = $this->obtenerNombreArchivo($dte, $json_enviado);

            $archivos = [
                $nombreArchivo . '.pdf' => $pdf->output(),
                $nombreArchivo . '.json' => $json
            ];

            $data = [
                "nombre" => $json_enviado['receptor']['nombre'] ?? $nombre_cliente,
                "numero" => $request->numero ?? $comprobante[0]->nu_doc,
                "json" => $json_enviado // Ya es un array
            ];

            // CORRECCIÓN: Asegurar que el JSON sea un array, no string
            if (is_string($data["json"])) {
                $data["json"] = json_decode($data["json"], true);
                Log::info('JSON convertido de string a array en enviar_correo_con_dte');
            }

            // Debug: Log para verificar qué se está enviando al correo
            Log::info('Debug data para correo en enviar_correo_con_dte', [
                'json_tipo' => gettype($data["json"]),
                'json_keys' => is_array($data["json"]) ? array_keys($data["json"]) : 'No es array',
                'identificacion_existe' => isset($data["json"]["identificacion"]) ? 'Sí' : 'No'
            ]);

            $asunto = "Comprobante de Venta No." . ($json_enviado['identificacion']['numeroControl'] ?? $data["numero"]) . ' de Proveedor: ' . ($json_enviado['emisor']['nombre'] ?? 'Empresa');

            $correo = new EnviarCorreo($data);
            $correo->subject($asunto);
            foreach ($archivos as $nombreArchivo => $rutaArchivo) {
                $correo->attachData($rutaArchivo, $nombreArchivo);
            }

            Mail::to($email)->send($correo);

            return response()->json([
                'success' => true,
                'message' => 'Correo enviado exitosamente con PDF y JSON',
                'data' => [
                    'numero_factura' => $data["numero"],
                    'email' => $email,
                    'archivos' => array_keys($archivos)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error("Error en enviar_correo_con_dte para venta ID: {$request->id_factura} - " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar el correo con DTE: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Envía correo sin DTE (solo PDF local)
     */
    private function enviar_correo_sin_dte(Request $request)
    {
        try {
            $id_factura = $request->id_factura;
            $email = $request->email;
            $nombre_cliente = $request->nombre_cliente;

            // Obtener datos básicos de la venta
            $venta = Sale::join('companies', 'companies.id', '=', 'sales.company_id')
                ->select('sales.*', 'companies.name as company_name')
                ->where('sales.id', $id_factura)
                ->first();

            if (!$venta) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró información de la venta'
                ], 404);
            }

            // Generar PDF local
            $pdf = $this->genera_pdflocal($id_factura);

            $data = [
                "nombre" => $nombre_cliente ?: 'Cliente',
                "numero" => $request->numero ?? $venta->nu_doc,
                "json" => null
            ];

            $asunto = "Comprobante de Venta No." . $data["numero"] . ' - ' . $venta->company_name;

            $correo = new EnviarCorreo($data);
            $correo->subject($asunto);
            $correo->attachData($pdf->output(), 'venta_' . $id_factura . '.pdf');

            Mail::to($email)->send($correo);

            return response()->json([
                'success' => true,
                'message' => 'Correo enviado exitosamente con PDF local',
                'data' => [
                    'numero_factura' => $data["numero"],
                    'email' => $email,
                    'archivos' => ['venta_' . $id_factura . '.pdf']
                ]
            ]);

        } catch (\Exception $e) {
            Log::error("Error en enviar_correo_sin_dte para venta ID: {$request->id_factura} - " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar el correo sin DTE: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Envía correo con todos los DTEs de una venta padre
     */
    private function enviar_correo_padre(Request $request, Sale $parentSale)
    {
        try {
            $email = $request->email;
            $nombre_cliente = $request->nombre_cliente;

            // Obtener todos los hijos con DTE
            $childSales = Sale::with(['dte', 'client'])
                ->where('parent_sale_id', $parentSale->id)
                ->whereHas('dte', function($query) {
                    $query->whereNotNull('json');
                })
                ->get();

            if ($childSales->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay DTEs emitidos para enviar. Primero debe emitir los DTEs de los hijos.'
                ], 400);
            }

            // Preparar datos del correo
            $client = $parentSale->client;
            $nombre = $nombre_cliente ?: ($client ? ($client->tpersona == 'J'
                ? $client->name_contribuyente
                : $client->firstname . ' ' . $client->firstlastname)
                : 'Cliente');

            // Preparar archivos adjuntos
            $archivos = [];
            $archivosNombres = [];

            foreach ($childSales as $childSale) {
                $dte = $childSale->dte;
                if (!$dte) {
                    continue; // Saltar si no tiene DTE
                }

                try {
                    // Generar PDF del hijo
                    $pdf = $this->genera_pdf($childSale->id);

                    // Obtener JSON desde sales.json (como en enviar_correo_con_dte)
                    $sale_json = $childSale->json;
                    if (!$sale_json) {
                        Log::warning("No hay JSON en sales para hijo ID: {$childSale->id}");
                        continue;
                    }

                    $json_root = json_decode($sale_json, true);

                    if (!is_array($json_root)) {
                        Log::warning("JSON inválido para hijo ID: {$childSale->id}");
                        continue;
                    }

                    // Obtener json_enviado de la respuesta de Hacienda
                    $json_enviado = $json_root['json']['json_enviado'] ?? $json_root;
                    $json = $this->limpiarJsonParaCorreo($json_enviado);

                    // Obtener nombre de archivo
                    $nombreArchivo = $this->obtenerNombreArchivo($dte, $json_enviado);

                    // Agregar PDF
                    $archivos[$nombreArchivo . '.pdf'] = $pdf->output();
                    $archivosNombres[] = $nombreArchivo . '.pdf';

                    // Agregar JSON
                    $archivos[$nombreArchivo . '.json'] = $json;
                    $archivosNombres[] = $nombreArchivo . '.json';

                } catch (\Exception $e) {
                    Log::error("Error generando PDF/JSON para hijo ID: {$childSale->id} - " . $e->getMessage());
                    // Continuar con los demás hijos aunque uno falle
                    continue;
                }
            }

            if (empty($archivos)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudieron generar los archivos para enviar'
                ], 500);
            }

            // Preparar data para el correo (venta padre: no es local, tiene DTEs en los hijos)
            $data = [
                "nombre" => $nombre,
                "numero" => $request->numero ?? $parentSale->id,
                "json" => null, // No hay un JSON único para venta padre
                "es_venta_padre" => true,
                "documentos_count" => count($childSales)
            ];

            $asunto = "Documentos Tributarios - Venta #" . $parentSale->id . " (" . count($childSales) . " documento" . (count($childSales) > 1 ? 's' : '') . ")";

            // Crear y enviar correo
            $correo = new EnviarCorreo($data);
            $correo->subject($asunto);

            foreach ($archivos as $nombreArchivo => $contenido) {
                $correo->attachData($contenido, $nombreArchivo);
            }

            Mail::to($email)->send($correo);

            return response()->json([
                'success' => true,
                'message' => 'Correo enviado exitosamente con ' . count($childSales) . ' documento(s)',
                'data' => [
                    'numero_factura' => $data["numero"],
                    'email' => $email,
                    'archivos' => $archivosNombres,
                    'documentos_enviados' => count($childSales)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error("Error en enviar_correo_padre para venta ID: {$request->id_factura} - " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar el correo: ' . $e->getMessage()
            ], 500);
        }
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
            ->join('typedocuments as typedoc', 'typedoc.id', '=', 'sales.typedocument_id')
            ->select(
                'dte.json as jsondte',
                'dte.selloRecibido as dte_selloRecibido',
                'dte.codigoGeneracion as dte_codigoGeneracion',
                'dte.fhRecibido as dte_fhRecibido',
                'sales.json as json',
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
        dd($factura);
        $comprobante = json_decode($factura, true);
        // Tomar sales.json y priorizar json.json_enviado (con robustez si vienen strings anidados)
        $salesJsonRaw = $comprobante[0]["json"] ?? '{}';
        $salesJson = is_string($salesJsonRaw) ? json_decode($salesJsonRaw, true) : (is_array($salesJsonRaw) ? $salesJsonRaw : []);
        if (isset($salesJson["json"]) && is_string($salesJson["json"])) {
            $maybe = json_decode($salesJson["json"], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($maybe)) {
                $salesJson["json"] = $maybe;
            }
        }
        $json = [];
        // PRIORIDAD 1: Intentar obtener json desde salesJson
        if (isset($salesJson["json"]["json_enviado"])) {
            $json = is_string($salesJson["json"]["json_enviado"]) ? json_decode($salesJson["json"]["json_enviado"], true) : $salesJson["json"]["json_enviado"];
        } elseif (isset($salesJson["json"])) {
            // Verificar si salesJson["json"] es directamente json_enviado o tiene estructura anidada
            if (isset($salesJson["json"]["identificacion"]) || isset($salesJson["json"]["emisor"]) || isset($salesJson["json"]["receptor"])) {
                // Es directamente json_enviado
                $json = $salesJson["json"];
            } elseif (isset($salesJson["json"]["json_enviado"])) {
                $json = is_string($salesJson["json"]["json_enviado"]) ? json_decode($salesJson["json"]["json_enviado"], true) : $salesJson["json"]["json_enviado"];
            } else {
                $json = $salesJson["json"];
            }
        }

        // Si salesJson está vacío pero tenemos DTE, intentar obtener json_enviado desde dte.json
        // CRÍTICO: Esto es necesario porque cuando se envía el correo automático, sales.json puede estar vacío
        // pero dte.json tiene todos los datos necesarios
        if (empty($salesJson) && isset($comprobante[0]["jsondte"])) {
            $dteJsonRaw = $comprobante[0]["jsondte"];

            // Log para debugging del contenido RAW de jsondte
            Log::info('genera_pdf: dteJsonRaw extraído', [
                'sale_id' => $id,
                'tipo' => gettype($dteJsonRaw),
                'es_string' => is_string($dteJsonRaw),
                'es_array' => is_array($dteJsonRaw),
                'es_object' => is_object($dteJsonRaw),
                'preview' => is_string($dteJsonRaw) ? substr($dteJsonRaw, 0, 200) : (is_array($dteJsonRaw) ? 'ARRAY' : (is_object($dteJsonRaw) ? 'OBJECT:'.get_class($dteJsonRaw) : 'UNKNOWN'))
            ]);

            // Decodificar según el tipo
            if (is_string($dteJsonRaw)) {
                // Primer intento de decodificación
                $dteJson = json_decode($dteJsonRaw, true);

                // CRÍTICO: Si el resultado es TODAVÍA un string, significa que está doblemente codificado
                // Necesitamos decodificar una segunda vez
                if (is_string($dteJson)) {
                    Log::info('genera_pdf: JSON doblemente codificado, decodificando segunda vez', [
                        'sale_id' => $id,
                        'preview_segunda' => substr($dteJson, 0, 200)
                    ]);
                    $dteJson = json_decode($dteJson, true);
                }
            } elseif (is_array($dteJsonRaw)) {
                $dteJson = $dteJsonRaw;
            } elseif (is_object($dteJsonRaw)) {
                // Convertir objeto a array
                $dteJson = json_decode(json_encode($dteJsonRaw), true);
            } else {
                $dteJson = [];
            }

            Log::info('genera_pdf: dteJson decodificado', [
                'sale_id' => $id,
                'dteJson_tipo' => gettype($dteJson),
                'dteJson_keys' => is_array($dteJson) ? array_keys($dteJson) : 'No es array',
                'tiene_json' => isset($dteJson["json"]),
                'json_type' => isset($dteJson["json"]) ? gettype($dteJson["json"]) : 'N/A'
            ]);

            // PRIORIDAD 1: Extraer json_enviado desde dte.json PRIMERO
            // porque es la estructura completa que necesitamos
            if (isset($dteJson["json"])) {
                $dteJsonInner = $dteJson["json"];

                // Si es string, decodificar
                if (is_string($dteJsonInner)) {
                    $dteJsonInner = json_decode($dteJsonInner, true);
                }

                // Buscar json_enviado en diferentes estructuras posibles
                if (isset($dteJsonInner["json_enviado"])) {
                    // json_enviado está anidado
                    $jsonEnviado = is_string($dteJsonInner["json_enviado"]) ? json_decode($dteJsonInner["json_enviado"], true) : $dteJsonInner["json_enviado"];
                    if (is_array($jsonEnviado) && !empty($jsonEnviado)) {
                        $json = $jsonEnviado;
                        Log::info('genera_pdf: json_enviado extraído desde dte.json (anidado)', [
                            'sale_id' => $id,
                            'json_keys' => array_keys($json),
                            'tiene_emisor' => isset($json["emisor"]),
                            'tiene_receptor' => isset($json["receptor"]),
                            'tiene_cuerpoDocumento' => isset($json["cuerpoDocumento"]),
                            'tiene_resumen' => isset($json["resumen"])
                        ]);
                    }
                } elseif (is_array($dteJsonInner) && (isset($dteJsonInner["identificacion"]) || isset($dteJsonInner["emisor"]) || isset($dteJsonInner["receptor"]))) {
                    // Es directamente json_enviado
                    $json = $dteJsonInner;
                    Log::info('genera_pdf: json_enviado extraído desde dte.json (directo)', [
                        'sale_id' => $id,
                        'json_keys' => array_keys($json),
                        'tiene_emisor' => isset($json["emisor"]),
                        'tiene_receptor' => isset($json["receptor"]),
                        'tiene_cuerpoDocumento' => isset($json["cuerpoDocumento"]),
                        'tiene_resumen' => isset($json["resumen"])
                    ]);
                }
            }

            // PRIORIDAD 2: Si dte.json tiene la estructura completa (emisor, cliente, etc.), usarla como salesJson
            // Esto es para compatibilidad con documentos antiguos
            if (isset($dteJson["emisor"]) || isset($dteJson["cliente"]) || isset($dteJson["detalle"])) {
                $salesJson = $dteJson;
                Log::info('genera_pdf: salesJson reemplazado desde dte.json', [
                    'sale_id' => $id,
                    'dteJson_keys' => is_array($dteJson) ? array_keys($dteJson) : 'No es array',
                    'tiene_emisor' => isset($dteJson["emisor"]),
                    'tiene_cliente' => isset($dteJson["cliente"]),
                    'tiene_detalle' => isset($dteJson["detalle"])
                ]);
            }
        }

        // DEBUG: Log detallado de la estructura de salesJson para identificar el problema
        Log::info('genera_pdf: Estructura de salesJson', [
            'sale_id' => $id,
            'salesJson_keys' => is_array($salesJson) ? array_keys($salesJson) : 'No es array',
            'tiene_emisor' => isset($salesJson["emisor"]),
            'tiene_cliente' => isset($salesJson["cliente"]),
            'tiene_detalle' => isset($salesJson["detalle"]),
            'tiene_totales' => isset($salesJson["totales"]),
            'tiene_documento' => isset($salesJson["documento"]),
            'tiene_json' => isset($salesJson["json"]),
            'emisor_type' => isset($salesJson["emisor"]) ? gettype($salesJson["emisor"]) : 'N/A',
            'cliente_type' => isset($salesJson["cliente"]) ? gettype($salesJson["cliente"]) : 'N/A',
            'detalle_type' => isset($salesJson["detalle"]) ? gettype($salesJson["detalle"]) : 'N/A',
            'detalle_count' => (isset($salesJson["detalle"]) && is_array($salesJson["detalle"])) ? count($salesJson["detalle"]) : 'N/A',
            'json_keys' => (isset($json) && is_array($json)) ? array_keys($json) : 'No es array',
            'json_has_emisor' => isset($json["emisor"]),
            'json_has_receptor' => isset($json["receptor"]),
            'tiene_jsondte' => isset($comprobante[0]["jsondte"])
        ]);

        // Agregar selloRecibido, codigoGeneracion y fhRecibido desde el DTE si no están en el JSON
        // Esto asegura que la vista siempre tenga acceso a estos campos
        if (!isset($json["selloRecibido"]) && isset($comprobante[0]["dte_selloRecibido"])) {
            $json["selloRecibido"] = $comprobante[0]["dte_selloRecibido"];
        }
        if (!isset($json["codigoGeneracion"]) && isset($comprobante[0]["dte_codigoGeneracion"])) {
            $json["codigoGeneracion"] = $comprobante[0]["dte_codigoGeneracion"];
        }
        if (!isset($json["fhRecibido"]) && isset($comprobante[0]["dte_fhRecibido"])) {
            $json["fhRecibido"] = $comprobante[0]["dte_fhRecibido"];
        }

        // Asegurar que receptor esté disponible en el JSON (la vista lo usa directamente)
        // Nota: El receptor se puede construir desde $data["cliente"] más adelante si no existe
        if (!isset($json["receptor"]) || !is_array($json["receptor"])) {
            // Se construirá desde la BD más adelante si es necesario
            $json["receptor"] = [
                "nombre" => "",
                "tipoDocumento" => "",
                "numDocumento" => "",
                "correo" => "",
                "descActividad" => ""
            ];
        } else {
            // Asegurar que receptor tenga todos los campos necesarios
            if (!isset($json["receptor"]["descActividad"])) {
                $json["receptor"]["descActividad"] = "";
            }
        }

        // Documento meta si existe en sales.json
        $documento = $salesJson["documento"] ?? [];

        // Asegurar que documento sea un array y tenga al menos un elemento
        if (!is_array($documento) || empty($documento) || !isset($documento[0])) {
            $documento = [[
                "versionjson" => "",
                "versionJson" => "",
                "version" => "",
                "actual" => "",
                "ambiente" => "",
                "tipodocumento" => "",
                "fechacreacion" => date('Y-m-d')
            ]];
        }
        //print_r($data);
        //dd($data);
        //$tipo_comprobante = $data["documento"][0]["tipodocumento"];
        $tipo_comprobante = $comprobante[0]['codemh'];
        //dd($tipo_comprobante);
        switch ($tipo_comprobante) {
            case '03': //CRF
                $rptComprobante = 'pdf.crf';
                break;
            case '01': //FAC
                $rptComprobante = 'pdf.fac';
                break;
            case '08': //CLQ - Comprobante de Liquidación
                $rptComprobante = 'pdf.clq';
                break;
            case '14': // FSE - Sujeto Excluido
                $rptComprobante = 'pdf.fse';
                break;
            case '11':  //FEX
                $rptComprobante = 'pdf.fex';
                break;
            case '05': // NCR - usar plantilla oficial de NCR
                $rptComprobante = 'pdf.ncr';
                break;
            case '06': // NDB - usar plantilla oficial de NDB
                $rptComprobante = 'pdf.ndb';
                break;

            default:
                # code...
                break;
        }
        // Inicializar $data básico para todas las plantillas
        // IMPORTANTE: Inicializar todos los campos que las vistas pueden necesitar
        $data = [
            "json" => $json,
            "documento" => $documento,
            "emisor" => [],
            "cliente" => [],
            "detalle" => [],
            "totales" => [],
        ];

        // Para FAC, CRF, CLQ, FSE, NCR y NDB, alinear estructura (emisor/cliente/detalle/totales del tope de sales.json)
        // Misma lógica que funcionaba antes, pero con validaciones adicionales
        if (in_array($tipo_comprobante, ['01', '03', '08', '14', '05', '06'])) {
            // PRIORIDAD 1: Usar datos desde sales.json (como funcionaba originalmente)
            // CRÍTICO: Convertir TODOS los objetos stdClass a arrays asociativos
            if (isset($salesJson["emisor"])) {
                // Convertir objetos a arrays si es necesario (los objetos stdClass de DB::select)
                // Usar json_decode(json_encode()) para convertir recursivamente todos los objetos
                $emisorConvertido = json_decode(json_encode($salesJson["emisor"]), true);
                if (is_array($emisorConvertido)) {
                    $data["emisor"] = $emisorConvertido;
                } elseif (is_object($salesJson["emisor"])) {
                    $data["emisor"] = json_decode(json_encode($salesJson["emisor"]), true);
                } elseif (is_array($salesJson["emisor"])) {
                    $data["emisor"] = $salesJson["emisor"]; // ya viene como arreglo [ {...} ]
                }
                // Asegurar que sea array indexado y tenga campos necesarios
                if (isset($data["emisor"]) && is_array($data["emisor"])) {
                    // Si no es array indexado, convertirlo
                    if (!isset($data["emisor"][0]) || !is_numeric(key($data["emisor"]))) {
                        $data["emisor"] = [$data["emisor"]];
                    }
                    // Asegurar campos necesarios
                    foreach ($data["emisor"] as $index => $emisorItem) {
                        if (is_array($emisorItem)) {
                            if (!isset($emisorItem["descActividad"])) {
                                $data["emisor"][$index]["descActividad"] = "";
                            }
                            // Asegurar que tanto nrc como ncr estén disponibles
                            if (!isset($emisorItem["nrc"]) && isset($emisorItem["ncr"])) {
                                $data["emisor"][$index]["nrc"] = $emisorItem["ncr"];
                            }
                            if (!isset($emisorItem["ncr"]) && isset($emisorItem["nrc"])) {
                                $data["emisor"][$index]["ncr"] = $emisorItem["nrc"];
                            }
                            // Si no tiene ninguno, inicializar vacío
                            if (!isset($emisorItem["nrc"])) {
                                $data["emisor"][$index]["nrc"] = "";
                            }
                            if (!isset($emisorItem["ncr"])) {
                                $data["emisor"][$index]["ncr"] = "";
                            }
                            if (isset($emisorItem["direccion"]) && is_string($emisorItem["direccion"])) {
                                $data["emisor"][$index]["direccion"] = ["complemento" => $emisorItem["direccion"]];
                            }
                        }
                    }
                }
            }

            if (isset($salesJson["cliente"])) {
                // Convertir objetos a arrays si es necesario (CRÍTICO: usar json_decode/json_encode para conversión recursiva)
                $clienteConvertido = json_decode(json_encode($salesJson["cliente"]), true);
                if (is_array($clienteConvertido)) {
                    $data["cliente"] = $clienteConvertido;
                } elseif (is_object($salesJson["cliente"])) {
                    $data["cliente"] = json_decode(json_encode($salesJson["cliente"]), true);
                } elseif (is_array($salesJson["cliente"])) {
                    $data["cliente"] = $salesJson["cliente"]; // ya viene como arreglo [ {...} ]
                }
                // Asegurar que sea array indexado
                if (isset($data["cliente"]) && is_array($data["cliente"])) {
                    if (!isset($data["cliente"][0]) || !is_numeric(key($data["cliente"]))) {
                        $data["cliente"] = [$data["cliente"]];
                    }
                }
            }

            if (isset($salesJson["detalle"])) {
                // CRÍTICO: Convertir objetos a arrays recursivamente
                $detalleConvertido = json_decode(json_encode($salesJson["detalle"]), true);
                if (is_array($detalleConvertido)) {
                    $data["detalle"] = $detalleConvertido;
                } elseif (is_object($salesJson["detalle"])) {
                    $data["detalle"] = json_decode(json_encode($salesJson["detalle"]), true);
                } elseif (is_array($salesJson["detalle"])) {
                    $data["detalle"] = $salesJson["detalle"]; // arreglo de items
                }
            }

            if (isset($salesJson["totales"])) {
                // CRÍTICO: Convertir objetos a arrays recursivamente
                $totalesConvertido = json_decode(json_encode($salesJson["totales"]), true);
                if (is_array($totalesConvertido)) {
                    $data["totales"] = $totalesConvertido;
                } elseif (is_object($salesJson["totales"])) {
                    $data["totales"] = json_decode(json_encode($salesJson["totales"]), true);
                } elseif (is_array($salesJson["totales"])) {
                    $data["totales"] = $salesJson["totales"]; // objeto asociativo
                }
            }

            // PRIORIDAD 2: Si tenemos json_enviado, usar su estructura (como funcionaba originalmente)
            // IMPORTANTE: Si no hay datos en salesJson, usar json_enviado como fuente principal
            // Esto es crítico porque cuando se envía el correo automático, puede que salesJson no tenga la estructura completa
            // NOTA: Para CLQ, el detalle puede estar vacío si no hay documentos relacionados, así que no lo incluimos en la verificación
            $emisorVacio = empty($data["emisor"]) ||
                          (isset($data["emisor"][0]) && empty($data["emisor"][0]["nombre"]));
            $clienteVacio = empty($data["cliente"]) ||
                           (isset($data["cliente"][0]) && empty($data["cliente"][0]["nombre"]));
            // CRÍTICO: Si salesJson está vacío, SIEMPRE usar json_enviado como fuente principal
            $usarJsonEnviado = empty($salesJson) || $emisorVacio || $clienteVacio ||
                              (empty($data["detalle"]) && $tipo_comprobante != '08'); // CLQ puede tener detalle vacío

            // Log para debugging
            if (empty($salesJson) || $usarJsonEnviado) {
                Log::info('genera_pdf: Usando json_enviado como fuente principal', [
                    'sale_id' => $id,
                    'salesJson_vacio' => empty($salesJson) ? 'Sí' : 'No',
                    'emisor_vacio' => $emisorVacio ? 'Sí' : 'No',
                    'cliente_vacio' => $clienteVacio ? 'Sí' : 'No',
                    'detalle_vacio' => empty($data["detalle"]) ? 'Sí' : 'No',
                    'json_has_emisor' => isset($json["emisor"]),
                    'json_has_receptor' => isset($json["receptor"]),
                    'json_has_cuerpoDocumento' => isset($json["cuerpoDocumento"]),
                    'json_has_resumen' => isset($json["resumen"])
                ]);
            }

            // Si necesitamos usar json_enviado, extraer todos los datos disponibles
            if ($usarJsonEnviado) {
                // Extraer emisor desde json_enviado
                if (isset($json["emisor"]) && is_array($json["emisor"])) {
                    $emisorData = $json["emisor"];
                    if (!isset($emisorData["descActividad"])) {
                        $emisorData["descActividad"] = "";
                    }
                    // Asegurar que tanto nrc como ncr estén disponibles
                    if (!isset($emisorData["nrc"]) && isset($emisorData["ncr"])) {
                        $emisorData["nrc"] = $emisorData["ncr"];
                    }
                    if (!isset($emisorData["ncr"]) && isset($emisorData["nrc"])) {
                        $emisorData["ncr"] = $emisorData["nrc"];
                    }
                    // Si no tiene ninguno, inicializar vacío
                    if (!isset($emisorData["nrc"])) {
                        $emisorData["nrc"] = "";
                    }
                    if (!isset($emisorData["ncr"])) {
                        $emisorData["ncr"] = "";
                    }
                    $data["emisor"] = [$emisorData]; // convertir a arreglo como espera el template
                }

                // Extraer cliente/receptor desde json_enviado
                if (isset($json["receptor"]) && is_array($json["receptor"])) {
                    $receptorData = $json["receptor"];
                    // Asegurar campos necesarios
                    if (!isset($receptorData["descActividad"])) {
                        $receptorData["descActividad"] = "";
                    }
                    if (!isset($receptorData["nrc"]) && isset($receptorData["ncr"])) {
                        $receptorData["nrc"] = $receptorData["ncr"];
                    }
                    if (!isset($receptorData["ncr"]) && isset($receptorData["nrc"])) {
                        $receptorData["ncr"] = $receptorData["nrc"];
                    }
                    if (!isset($receptorData["nrc"])) {
                        $receptorData["nrc"] = "";
                    }
                    if (!isset($receptorData["ncr"])) {
                        $receptorData["ncr"] = "";
                    }
                    $data["cliente"] = [$receptorData]; // convertir a arreglo como espera el template
                }
            } else {
                // Si ya tenemos datos pero json_enviado tiene emisor/receptor, usarlos como complemento
                if (isset($json["emisor"]) && is_array($json["emisor"]) && $emisorVacio) {
                    $emisorData = $json["emisor"];
                    if (!isset($emisorData["descActividad"])) {
                        $emisorData["descActividad"] = "";
                    }
                    if (!isset($emisorData["nrc"]) && isset($emisorData["ncr"])) {
                        $emisorData["nrc"] = $emisorData["ncr"];
                    }
                    if (!isset($emisorData["ncr"]) && isset($emisorData["nrc"])) {
                        $emisorData["ncr"] = $emisorData["nrc"];
                    }
                    if (!isset($emisorData["nrc"])) {
                        $emisorData["nrc"] = "";
                    }
                    if (!isset($emisorData["ncr"])) {
                        $emisorData["ncr"] = "";
                    }
                    $data["emisor"] = [$emisorData];
                }

                if (isset($json["receptor"]) && is_array($json["receptor"]) && $clienteVacio) {
                    $receptorData = $json["receptor"];
                    if (!isset($receptorData["descActividad"])) {
                        $receptorData["descActividad"] = "";
                    }
                    if (!isset($receptorData["nrc"]) && isset($receptorData["ncr"])) {
                        $receptorData["nrc"] = $receptorData["ncr"];
                    }
                    if (!isset($receptorData["ncr"]) && isset($receptorData["nrc"])) {
                        $receptorData["ncr"] = $receptorData["nrc"];
                    }
                    if (!isset($receptorData["nrc"])) {
                        $receptorData["nrc"] = "";
                    }
                    if (!isset($receptorData["ncr"])) {
                        $receptorData["ncr"] = "";
                    }
                    $data["cliente"] = [$receptorData];
                }
            }

            // Mapear detalle y totales desde json_enviado si están disponibles o si no hay datos
            // NOTA: Para CLQ, el detalle puede venir como "documentosRelacionados" en lugar de "cuerpoDocumento"
            // CRÍTICO: Si salesJson está vacío, SIEMPRE intentar extraer desde json_enviado
            if ($usarJsonEnviado || empty($salesJson) || (isset($json["cuerpoDocumento"]) && is_array($json["cuerpoDocumento"]))) {
                if (isset($json["cuerpoDocumento"]) && is_array($json["cuerpoDocumento"]) && !empty($json["cuerpoDocumento"])) {
                    $data["detalle"] = $json["cuerpoDocumento"];
                    Log::info('genera_pdf: detalle extraído desde json_enviado.cuerpoDocumento', [
                        'sale_id' => $id,
                        'detalle_count' => count($data["detalle"])
                    ]);
                }
            }
            // Para CLQ, también verificar documentosRelacionados
            if ($tipo_comprobante == '08' && (empty($data["detalle"]) || $usarJsonEnviado || empty($salesJson))) {
                if (isset($json["documentosRelacionados"]) && is_array($json["documentosRelacionados"]) && !empty($json["documentosRelacionados"])) {
                    $data["detalle"] = $json["documentosRelacionados"];
                    Log::info('genera_pdf: detalle extraído desde json_enviado.documentosRelacionados (CLQ)', [
                        'sale_id' => $id,
                        'detalle_count' => count($data["detalle"])
                    ]);
                }
            }

            // CRÍTICO: Si salesJson está vacío, SIEMPRE intentar extraer totales desde json_enviado
            if ($usarJsonEnviado || empty($salesJson) || (isset($json["resumen"]) && is_array($json["resumen"]))) {
                if (isset($json["resumen"]) && is_array($json["resumen"]) && !empty($json["resumen"])) {
                    $data["totales"] = $json["resumen"];
                    Log::info('genera_pdf: totales extraídos desde json_enviado.resumen', [
                        'sale_id' => $id,
                        'totales_keys' => is_array($data["totales"]) ? array_keys($data["totales"]) : 'No es array'
                    ]);
                }
            }

            // Asegurar que emisor siempre tenga al menos un elemento para evitar errores en la vista
            if (!isset($data["emisor"]) || !is_array($data["emisor"]) || empty($data["emisor"]) || !isset($data["emisor"][0]) || !is_array($data["emisor"][0])) {
                // Último recurso: construir emisor básico vacío
                $data["emisor"] = [[
                    "nombre" => "",
                    "nit" => "",
                    "nrc" => "",
                    "ncr" => "",
                    "descActividad" => "",
                    "direccion" => "",
                    "telefono" => "",
                    "correo" => "",
                    "nombreComercial" => ""
                ]];
            }

            // Asegurar que cliente siempre tenga al menos un elemento (la vista lo usa como $cliente[0])
            if (!isset($data["cliente"]) || !is_array($data["cliente"]) || empty($data["cliente"]) || !isset($data["cliente"][0]) || !is_array($data["cliente"][0])) {
                // Último recurso: construir cliente básico vacío
                $data["cliente"] = [[
                    "nombre" => "",
                    "tipoDocumento" => "",
                    "numDocumento" => "",
                    "correo" => ""
                ]];
            }

            // Asegurar que receptor en JSON tenga los mismos datos que cliente
            if (!isset($json["receptor"]) || !is_array($json["receptor"]) || empty($json["receptor"]["nombre"])) {
                if (isset($data["cliente"][0]) && !empty($data["cliente"][0]["nombre"])) {
                    $json["receptor"] = [
                        "nombre" => $data["cliente"][0]["nombre"] ?? "",
                        "tipoDocumento" => $data["cliente"][0]["tipoDocumento"] ?? "",
                        "numDocumento" => $data["cliente"][0]["numDocumento"] ?? "",
                        "correo" => $data["cliente"][0]["correo"] ?? "",
                        "nit" => $data["cliente"][0]["nit"] ?? "",
                        "nrc" => $data["cliente"][0]["nrc"] ?? ($data["cliente"][0]["ncr"] ?? ""),
                        "ncr" => $data["cliente"][0]["ncr"] ?? ($data["cliente"][0]["nrc"] ?? ""),
                        "descActividad" => $data["cliente"][0]["descActividad"] ?? ""
                    ];
                }
            } else {
                // Asegurar que receptor existente tenga nrc y ncr definidos
                if (!isset($json["receptor"]["nrc"]) && isset($json["receptor"]["ncr"])) {
                    $json["receptor"]["nrc"] = $json["receptor"]["ncr"];
                }
                if (!isset($json["receptor"]["ncr"]) && isset($json["receptor"]["nrc"])) {
                    $json["receptor"]["ncr"] = $json["receptor"]["nrc"];
                }
                // Si no tiene ninguno, inicializar vacío
                if (!isset($json["receptor"]["nrc"])) {
                    $json["receptor"]["nrc"] = "";
                }
                if (!isset($json["receptor"]["ncr"])) {
                    $json["receptor"]["ncr"] = "";
                }
            }
        }

        // Asegurar que documento siempre tenga al menos un elemento (fuera del if para todos los tipos)
        if (!is_array($documento) || empty($documento) || !isset($documento[0])) {
            $documento = [[
                "versionjson" => "",
                "versionJson" => "",
                "version" => "",
                "actual" => "",
                "ambiente" => "",
                "tipodocumento" => "",
                "fechacreacion" => date('Y-m-d')
            ]];
        }

        // Actualizar documento en $data
        $data["documento"] = $documento;

        // Actualizar JSON en $data con receptor actualizado (si se construyó desde BD)
        $data["json"] = $json;

        // CRÍTICO: Log final para verificar qué se está pasando a la vista
        // Esto ayuda a identificar por qué el PDF puede estar vacío
        Log::info('genera_pdf: Estado final de $data antes de pasar a la vista', [
            'sale_id' => $id,
            'tipo_comprobante' => $tipo_comprobante,
            'emisor_count' => isset($data["emisor"]) && is_array($data["emisor"]) ? count($data["emisor"]) : 0,
            'emisor_has_nombre' => isset($data["emisor"][0]["nombre"]) && !empty($data["emisor"][0]["nombre"]),
            'emisor_has_nit' => isset($data["emisor"][0]["nit"]) && !empty($data["emisor"][0]["nit"]),
            'emisor_has_nrc' => isset($data["emisor"][0]["nrc"]) && !empty($data["emisor"][0]["nrc"]),
            'cliente_count' => isset($data["cliente"]) && is_array($data["cliente"]) ? count($data["cliente"]) : 0,
            'cliente_has_nombre' => isset($data["cliente"][0]["nombre"]) && !empty($data["cliente"][0]["nombre"]),
            'detalle_count' => isset($data["detalle"]) && is_array($data["detalle"]) ? count($data["detalle"]) : 0,
            'totales_has_totalPagar' => isset($data["totales"]["totalPagar"]) && !empty($data["totales"]["totalPagar"]),
            'json_has_emisor' => isset($json["emisor"]),
            'json_has_receptor' => isset($json["receptor"]),
            'json_has_cuerpoDocumento' => isset($json["cuerpoDocumento"]),
            'json_has_resumen' => isset($json["resumen"]),
            'salesJson_vacio' => empty($salesJson) ? 'Sí' : 'No'
        ]);

        // Asegurar que detalle y totales siempre estén definidos (incluso si están vacíos)
        // Esto previene errores de "Undefined variable" en las vistas
        if (!isset($data["detalle"]) || !is_array($data["detalle"])) {
            $data["detalle"] = [];
        }
        if (!isset($data["totales"]) || !is_array($data["totales"])) {
            $data["totales"] = [];
        }
        if (!isset($data["emisor"]) || !is_array($data["emisor"])) {
            $data["emisor"] = [];
        }
        if (!isset($data["cliente"]) || !is_array($data["cliente"])) {
            $data["cliente"] = [];
        }

        @$fecha = $json["fhRecibido"] ?? null;
        @$qr = base64_encode(codigoQR(($documento[0]["ambiente"] ?? ($json["identificacion"]["ambiente"] ?? null)), ($json["codigoGeneracion"] ?? ($json["identificacion"]["codigoGeneracion"] ?? null)), $fecha));
        //return  '<img src="data:image/png;base64,'.$qr .'">';
        $data["codTransaccion"] = "01";
        $data["PaisE"] = $factura[0]['PaisE'];
        $data["DepartamentoE"] = $factura[0]['DepartamentoE'];
        $data["MunicipioE"] = $factura[0]['MunicipioE'];
        $data["PaisR"] = $factura[0]['PaisR'];
        $data["DepartamentoR"] = $factura[0]['DepartamentoR'];
        $data["MunicipioR"] = $factura[0]['MunicipioR'];
        $data["qr"] = $qr;

        // VERIFICACIÓN FINAL: Asegurar que todas las variables que las vistas necesitan estén definidas
        // Esto es crítico para evitar errores de "Undefined variable" en las vistas
        $data["detalle"] = $data["detalle"] ?? [];
        $data["totales"] = $data["totales"] ?? [];
        $data["emisor"] = $data["emisor"] ?? [];
        $data["cliente"] = $data["cliente"] ?? [];
        $data["json"] = $data["json"] ?? [];
        $data["documento"] = $data["documento"] ?? [];

        // Debug: Log para verificar que detalle esté definido antes de pasar a la vista
        Log::info('genera_pdf: Verificación final antes de loadView', [
            'sale_id' => $id,
            'tipo_comprobante' => $tipo_comprobante,
            'detalle_isset' => isset($data["detalle"]),
            'detalle_is_array' => is_array($data["detalle"] ?? null),
            'detalle_count' => is_array($data["detalle"] ?? null) ? count($data["detalle"]) : 'N/A',
            'emisor_isset' => isset($data["emisor"]),
            'emisor_count' => (isset($data["emisor"]) && is_array($data["emisor"])) ? count($data["emisor"]) : 'N/A',
            'emisor_has_nombre' => (isset($data["emisor"][0]) && is_array($data["emisor"][0])) ? !empty($data["emisor"][0]["nombre"]) : false,
            'cliente_isset' => isset($data["cliente"]),
            'cliente_count' => (isset($data["cliente"]) && is_array($data["cliente"])) ? count($data["cliente"]) : 'N/A',
            'cliente_has_nombre' => (isset($data["cliente"][0]) && is_array($data["cliente"][0])) ? !empty($data["cliente"][0]["nombre"]) : false,
            'totales_isset' => isset($data["totales"]),
            'data_keys' => array_keys($data),
            'rptComprobante' => $rptComprobante,
            'json_has_emisor' => isset($json["emisor"]),
            'json_has_receptor' => isset($json["receptor"]),
            'json_has_cuerpoDocumento' => isset($json["cuerpoDocumento"]),
            'json_has_resumen' => isset($json["resumen"]),
            'salesJson_tiene_emisor' => isset($salesJson["emisor"]),
            'salesJson_tiene_cliente' => isset($salesJson["cliente"]),
            'salesJson_tiene_detalle' => isset($salesJson["detalle"]),
            'salesJson_tiene_totales' => isset($salesJson["totales"])
        ]);

        $tamaño = "Letter";
        $orientacion = "Portrait";
        $pdf = app('dompdf.wrapper');
        $pdf->set_option('isHtml5ParserEnabled', true);
        $pdf->set_option('isRemoteEnabled', true);
        //dd(asset('/temp'));
        // $pdf->set_option('tempDir', asset('/temp'));
        //dd($data);
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

        // Mapear datos para compatibilidad con templates
        $data["emisor"] = $data["emisor"] ?? [];
        $data["cliente"] = $data["cliente"] ?? [];
        $data["detalle"] = $data["detalle"] ?? [];
        $data["totales"] = $data["totales"] ?? [];
        $data["documento"] = $data["documento"] ?? [];
        $data["json"] = $data["json"] ?? [];
        switch ($tipo_comprobante) {
            case '03': //CRF
                $rptComprobante = 'pdf.crflocal';
                break;
            case '01': //FAC
                $rptComprobante = 'pdf.faclocal';
                break;
            case '08': //CLQ - Comprobante de Liquidación (local)
                $rptComprobante = 'pdf.clqlocal';
                break;
            case '14': // FSE - Sujeto Excluido (local)
                $rptComprobante = 'pdf.fse';
                break;
            case '11':  //FEX
                $rptComprobante = 'pdf.fex';
                break;
            case '05': // NCR local - usar plantilla local de NCR
                $rptComprobante = 'pdf.ncrlocal';
                break;
            case '06': // NDB local - usar plantilla oficial de NDB (misma estructura)
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

        // Variables adicionales para compatibilidad con templates
        $data["MunicipioE"] = $factura[0]['MunicipioE'];
        $data["DepartamentoE"] = $factura[0]['DepartamentoE'];
        $data["MunicipioR"] = $factura[0]['MunicipioR'];
        $data["DepartamentoR"] = $factura[0]['DepartamentoR'];

        $tamaño = "Letter";
        $orientacion = "Portrait";
        $pdf = app('dompdf.wrapper');
        $pdf->set_option('isHtml5ParserEnabled', true);
        $pdf->set_option('isRemoteEnabled', true);
        //dd(asset('/temp'));
        // $pdf->set_option('tempDir', asset('/temp'));
        //dd($data);
        $pdf->setPaper($tamaño, $orientacion);
        $pdf->getDomPDF()->set_option("enable_php", true);
        $pdf->loadView($rptComprobante, $data);
        //dd($pdf);
        return $pdf;
    }
    public function print($id)
    {
        // Verificar si existe DTE para esta venta
        $dte = \App\Models\Dte::where('sale_id', $id)->first();

        if ($dte && $dte->json) {
            // Si hay DTE, usar PDF oficial
            $pdf = $this->genera_pdf($id);
        } else {
            // Si no hay DTE, usar PDF local
            $pdf = $this->genera_pdflocal($id);
        }

        return $pdf->stream('comprobante.pdf');
    }

    public function download($id)
    {
        // Verificar si existe DTE para esta venta
        $dte = \App\Models\Dte::where('sale_id', $id)->first();

        if ($dte && $dte->json) {
            $pdf = $this->genera_pdf($id);
        } else {
            $pdf = $this->genera_pdflocal($id);
        }

        $filename = 'comprobante_' . $id . '.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename, [
            'Content-Type' => 'application/pdf'
        ]);
    }

    /**
     * Firma un JWT HS256 y reenvía el payload al webhook de n8n.
     */
    public function sendToN8n(Request $request): JsonResponse
    {
        $payload = [
            'iss' => 'explorertravelsv',
            'iat' => time(),
            'exp' => time() + 300
        ];

        $jwt = $this->createHs256Jwt($payload, self::N8N_JWT_SECRET);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $jwt,
                'Content-Type' => 'application/json'
            ])
            ->timeout(20)
            ->withOptions(['verify' => false])
            ->post(self::N8N_WEBHOOK_URL, $request->all());

            return response()->json([
                'success' => $response->successful(),
                'status' => $response->status(),
                'data' => $this->safeJson($response->body())
            ], $response->status());
        } catch (\Throwable $e) {
            Log::error('Error enviando a n8n', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error enviando a n8n',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function createHs256Jwt(array $payload, string $secret): string
    {
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $segments = [
            $this->base64UrlEncode(json_encode($header, JSON_UNESCAPED_SLASHES)),
            $this->base64UrlEncode(json_encode($payload, JSON_UNESCAPED_SLASHES))
        ];
        $signingInput = implode('.', $segments);
        $signature = hash_hmac('sha256', $signingInput, $secret, true);
        $segments[] = $this->base64UrlEncode($signature);
        return implode('.', $segments);
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function safeJson(?string $raw)
    {
        if ($raw === null) return null;
        try {
            return json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            return ['raw' => $raw];
        }
    }

    /**
     * Limpiar JSON para envío por correo (quitar caracteres de escape)
     */
    private function limpiarJsonParaCorreo($jsonData): string
    {
        if (is_string($jsonData)) {
            $jsonData = json_decode($jsonData, true);
        }

        // Codificar con formato bonito y sin caracteres de escape
        $jsonLimpio = json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return $jsonLimpio;
    }

    /**
     * Obtener nombre de archivo basado en código de generación
     */
    private function obtenerNombreArchivo($dte, $jsonEnviado = null): string
    {
        // Priorizar código de generación como estaba antes
        if ($dte->codigoGeneracion) {
            return $dte->codigoGeneracion;
        }

        // Si hay json_enviado, usar su código de generación
        if ($jsonEnviado && isset($jsonEnviado['identificacion']['codigoGeneracion'])) {
            return $jsonEnviado['identificacion']['codigoGeneracion'];
        }

        // Fallback a número de control
        if ($jsonEnviado && isset($jsonEnviado['identificacion']['numeroControl'])) {
            return $jsonEnviado['identificacion']['numeroControl'];
        }

        // Último fallback
        return 'venta_' . $dte->sale_id;
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

        if (!$venta) {
            Log::warning('enviarCorreoAutomaticoVenta: venta no encontrada', ['sale_id' => $saleId]);
            return;
        }
        if (empty(trim((string)($venta->client_email ?? '')))) {
            Log::warning('enviarCorreoAutomaticoVenta: cliente sin email', [
                'sale_id' => $saleId,
                'client_id' => $venta->client_id
            ]);
            return;
        }

        // Construir nombre del cliente
        $nombreCliente = $venta->tpersona === 'N'
            ? trim(($venta->firstname ?: '') . ' ' . ($venta->secondname ?: '') . ' ' . ($venta->firstlastname ?: '') . ' ' . ($venta->secondlastname ?: ''))
            : ($venta->comercial_name ?: $venta->name_contribuyente ?: 'Cliente');

        $numero = $venta->nu_doc ?: ('#' . $venta->id);
        $email = $venta->client_email;

        // Usar la misma lógica que envia_correo (desde sales.index)
        // SIEMPRE buscar el DTE desde la base de datos para asegurar datos actualizados
        // Ignorar el objeto $dte pasado como parámetro para garantizar consistencia
        // Esto asegura que siempre tengamos los datos más recientes guardados en la BD
        $dte = \App\Models\Dte::where('sale_id', $saleId)->latest('id')->first();

        // Debug: Log para verificar qué se está encontrando
        Log::info('Debug enviarCorreoAutomaticoVenta', [
            'id_factura' => $saleId,
            'dte_encontrado' => $dte ? 'Sí' : 'No',
            'dte_json' => $dte ? ($dte->json ? 'Sí' : 'No') : 'N/A',
            'dte_codigoGeneracion' => $dte ? $dte->codigoGeneracion : 'N/A',
            'dte_selloRecibido' => $dte ? ($dte->selloRecibido ? 'Sí' : 'No') : 'N/A',
            'dte_fhRecibido' => $dte ? ($dte->fhRecibido ? 'Sí' : 'No') : 'N/A'
        ]);

        if ($dte && $dte->json) {
            // Si hay DTE, usar PDF oficial y JSON enviado (misma lógica que envia_correo)
            $pdf = $this->genera_pdf($saleId);
            $json_root = json_decode($dte->json, true); // Decodificar como array
            $json_enviado = $json_root['json']['json_enviado'] ?? $json_root;
            $json = $this->limpiarJsonParaCorreo($json_enviado);

            // Debug: Log para verificar el JSON
            Log::info('Debug JSON en enviarCorreoAutomaticoVenta', [
                'json_root_keys' => $json_root ? array_keys($json_root) : 'No keys',
                'json_enviado_keys' => $json_enviado ? array_keys($json_enviado) : 'No keys',
                'codigoGeneracion_en_json' => $json_enviado['identificacion']['codigoGeneracion'] ?? 'No encontrado'
            ]);

            // Obtener nombre de archivo basado en código de generación
            $nombreArchivo = $this->obtenerNombreArchivo($dte, $json_enviado);

            // Debug: Log para verificar el nombre del archivo
            Log::info('Debug nombre archivo en enviarCorreoAutomaticoVenta', [
                'nombreArchivo_final' => $nombreArchivo,
                'dte_codigoGeneracion' => $dte->codigoGeneracion
            ]);

            $archivos = [
                $nombreArchivo . '.pdf' => $pdf->output(),
                $nombreArchivo . '.json' => $json
            ];

            $data = [
                "nombre" => $json_enviado['receptor']['nombre'] ?? $nombreCliente,
                "numero" => $numero,
                "json" => $json_enviado // Ya es un array
            ];

            // CORRECCIÓN: Asegurar que el JSON sea un array, no string
            if (is_string($data["json"])) {
                $data["json"] = json_decode($data["json"], true);
                Log::info('JSON convertido de string a array en enviarCorreoAutomaticoVenta');
            }

            // Debug: Log para verificar qué se está enviando al correo
            Log::info('Debug data para correo en enviarCorreoAutomaticoVenta', [
                'json_tipo' => gettype($data["json"]),
                'json_keys' => is_array($data["json"]) ? array_keys($data["json"]) : 'No es array',
                'identificacion_existe' => isset($data["json"]["identificacion"]) ? 'Sí' : 'No'
            ]);

            $asunto = "Comprobante de Venta No." . ($json_enviado['identificacion']['numeroControl'] ?? $numero) . ' de Proveedor: ' . ($json_enviado['emisor']['nombre'] ?? 'Empresa');
        } else {
            // Si no hay DTE, usar PDF local (misma lógica que envia_correo)
            $pdf = $this->genera_pdflocal($saleId);

            $archivos = [
                'venta_' . $saleId . '.pdf' => $pdf->output()
            ];

            $data = [
                "nombre" => $nombreCliente,
                "numero" => $numero,
                "json" => null
            ];

            $asunto = "Comprobante de Venta No." . $numero;
        }

        $correo = new EnviarCorreo($data);
        $correo->subject($asunto);
        foreach ($archivos as $nombreArchivo => $rutaArchivo) {
            $correo->attachData($rutaArchivo, $nombreArchivo);
        }

        Log::info('enviarCorreoAutomaticoVenta: enviando correo', ['sale_id' => $saleId, 'email' => $email, 'tiene_dte' => ($dte && $dte->json) ? 'Sí' : 'No']);
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

    /**
     * Obtiene las ventas hijas de una venta padre
     */
    public function getChildSales($parent_id)
    {
        $parentSale = Sale::find($parent_id);

        $childSales = Sale::with(['dte', 'salesdetails.lineProvider', 'typedocument'])
            ->where('parent_sale_id', $parent_id)
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($child) use ($parentSale) {
                // Buscar DTE de invalidación (codTransaction = "02")
                $dteInvalidacion = \App\Models\Dte::where('sale_id', $child->id)
                    ->where('codTransaction', '02')
                    ->latest()
                    ->first();

                return [
                    'id' => $child->id,
                    'date' => $child->date->format('d/m/Y'),
                    'created_at' => $child->created_at ? $child->created_at->format('H:i') : null,
                    'totalamount' => $child->totalamount,
                    'state' => $child->state,
                    'acuenta' => $child->acuenta,
                    'provider' => $child->salesdetails->first()->lineProvider ?? null,
                    'document_type' => $child->typedocument ? $child->typedocument->description : 'N/A',
                    'dte' => $child->dte ? [
                        'id' => $child->dte->id,
                        'codigoGeneracion' => $child->dte->codigoGeneracion,
                        'estadoHacienda' => $child->dte->estadoHacienda,
                        'id_doc' => $child->dte->id_doc,
                        'selloRecibido' => $child->dte->selloRecibido,
                        'fhRecibido' => $child->dte->fhRecibido
                    ] : null,
                    'dte_invalidacion' => $dteInvalidacion ? [
                        'id' => $dteInvalidacion->id,
                        'codigoGeneracion' => $dteInvalidacion->codigoGeneracion,
                        'estadoHacienda' => $dteInvalidacion->estadoHacienda,
                        'fhRecibido' => $dteInvalidacion->fhRecibido
                    ] : null,
                    'correlativo_dte' => $child->dte ? $child->dte->id_doc : null,
                    'has_dte' => $child->dte ? true : false,
                    'is_success' => $child->dte && $child->dte->estadoHacienda === 'PROCESADO',
                    'can_reemit' => !$child->dte || $child->dte->estadoHacienda !== 'PROCESADO',
                    // Información del padre
                    'parent_client' => $parentSale ? $parentSale->client : null,
                    'parent_client_name' => $parentSale && $parentSale->client
                        ? ($parentSale->client->tpersona == 'J'
                            ? $parentSale->client->name_contribuyente
                            : $parentSale->client->firstname . ' ' . $parentSale->client->firstlastname)
                        : '-',
                    'parent_waytopay' => $parentSale ? $parentSale->waytopay : null,
                    'parent_waytopay_text' => $parentSale ? ($parentSale->waytopay == 1 ? 'CONTADO' : ($parentSale->waytopay == 2 ? 'CRÉDITO' : 'OTRO')) : '-'
                ];
            });

        return response()->json($childSales);
    }

    /**
     * Reemite un DTE de una venta hija que falló
     */
    public function reemitChild($id)
    {
        try {
            $childSale = Sale::with('salesdetails')->find($id);

            // Verificar si es hijo: si parent_sale_id es null, NO es hijo
            if (!$childSale || is_null($childSale->parent_sale_id)) {
                return redirect()->route('sale.index')
                    ->with('error', 'Venta no encontrada o no es una venta hija');
            }

            // Calcular monto
            $total = 0;
            foreach ($childSale->salesdetails as $detail) {
                $total += $detail->pricesale + $detail->detained13 - $detail->detained - $detail->renta + $detail->nosujeta + $detail->exempt;
            }

            $amount = '$' . number_format($total, 2, '.', '');
            $corrEncoded = base64_encode($childSale->id);

            // Intentar emitir nuevamente
            $response = $this->createdocument($corrEncoded, $amount);
            $responseData = json_decode($response->getContent(), true);

            if (isset($responseData['res']) && $responseData['res'] == 1) {
                return redirect()->route('sale.index')
                    ->with('success', 'DTE reemitido correctamente');
            } else {
                return redirect()->route('sale.index')
                    ->with('error', 'Error al reemitir DTE: ' . ($responseData['message'] ?? 'Error desconocido'));
            }
        } catch (\Exception $e) {
            Log::error('Error reemitiendo DTE hijo: ' . $e->getMessage());
            return redirect()->route('sale.index')
                ->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Verifica si una venta tiene múltiples proveedores en sus detalles
     */
    private function hasMultipleProviders($sale)
    {
        $providers = Salesdetail::where('sale_id', $sale->id)
            ->whereNotNull('line_provider_id')
            ->distinct()
            ->pluck('line_provider_id')
            ->toArray();

        // Si tiene al menos un proveedor Y además tiene líneas sin proveedor
        $hasNoProvider = Salesdetail::where('sale_id', $sale->id)
            ->whereNull('line_provider_id')
            ->exists();

        // Hay múltiples grupos si:
        // 1. Hay más de un proveedor diferente, O
        // 2. Hay al menos un proveedor Y hay líneas sin proveedor
        return count($providers) > 1 || (count($providers) >= 1 && $hasNoProvider);
    }

    /**
     * Procesa una venta con múltiples proveedores (padre con hijos)
     */
    private function processMultiProviderSale($parentSale, $amount)
    {
        DB::beginTransaction();
        try {
            $amount = substr($amount, 1);

            // Agrupar detalles por proveedor ANTES de guardar para determinar provider_id
            $groups = $this->groupDetailsByProvider($parentSale);

            // Si todos los productos son de terceros y hay un solo proveedor, establecer provider_id en la venta padre
            // Esto es necesario para que el CLQ pueda encontrar el documento cuando solo hay un proveedor
            $providerIds = array_filter(array_column($groups, 'provider_id'), function($id) {
                return $id !== null;
            });

            // Si hay exactamente un proveedor (todos los productos son del mismo tercero), establecer provider_id
            if (count($providerIds) === 1 && count($groups) === 1) {
                $parentSale->provider_id = reset($providerIds);
                Log::info('Estableciendo provider_id en venta padre', [
                    'sale_id' => $parentSale->id,
                    'provider_id' => $parentSale->provider_id,
                    'provider_name' => $groups[array_key_first($groups)]['provider_name'] ?? 'N/A'
                ]);
            } elseif (count($providerIds) === 0) {
                // Si no hay proveedores (todos son productos propios), asegurar que provider_id sea null
                $parentSale->provider_id = null;
            }
            // Si hay múltiples proveedores, provider_id queda null (es una venta padre con múltiples terceros)

            // Marcar como padre
            $parentSale->is_parent = 1;
            $parentSale->totalamount = $amount;
            $parentSale->typesale = 1; // Finalizar

            // Log antes de guardar para verificar provider_id
            Log::info('Guardando venta padre', [
                'sale_id' => $parentSale->id,
                'provider_id' => $parentSale->provider_id,
                'is_parent' => $parentSale->is_parent,
                'total_grupos' => count($groups),
                'provider_ids' => $providerIds
            ]);

            $parentSale->save();

            $childResults = [];
            $allSuccess = true;

            foreach ($groups as $key => $group) {
                try {
                    // Crear venta hija
                    $childSale = $this->createChildSale($parentSale, $group);

                    // Calcular monto del hijo
                    $childAmount = $this->calculateChildAmount($group['details']);

                    // Emitir DTE para el hijo (usar la función actual createdocument)
                    $dteResponse = $this->emitDTEForChild($childSale, $childAmount);

                    if ($dteResponse['success']) {
                        $childResults[] = [
                            'sale_id' => $childSale->id,
                            'success' => true,
                            'provider' => $group['provider_name'] ?? 'Servicio Propio',
                            'provider_id' => $group['provider_id'] ?? null,
                            'amount' => str_replace('$', '', $childAmount),
                            'dte_id' => $dteResponse['dte_id'] ?? null,
                            'codigo_generacion' => $dteResponse['codigo_generacion'] ?? null
                        ];
                    } else {
                        $allSuccess = false;
                        $childResults[] = [
                            'sale_id' => $childSale->id,
                            'success' => false,
                            'error' => $dteResponse['error'] ?? 'Error desconocido',
                            'provider' => $group['provider_name'] ?? 'Servicio Propio',
                            'provider_id' => $group['provider_id'] ?? null,
                            'amount' => str_replace('$', '', $childAmount)
                        ];
                    }
                } catch (\Exception $e) {
                    $allSuccess = false;
                    $childResults[] = [
                        'sale_id' => null,
                        'success' => false,
                        'error' => $e->getMessage(),
                        'provider' => $group['provider_name'] ?? 'Desconocido',
                        'provider_id' => $group['provider_id'] ?? null
                    ];
                    Log::error('Error creando hijo: ' . $e->getMessage());
                }
            }

            DB::commit();

            // Si todos los DTEs se emitieron bien, enviar un solo correo consolidado al cliente
            if ($allSuccess) {
                $this->sendConsolidatedEmail($parentSale);
            }

            return response()->json([
                'res' => $allSuccess ? 1 : 0,
                'type' => 'multi_provider',
                'parent_sale_id' => $parentSale->id,
                'all_success' => $allSuccess,
                'children' => $childResults,
                'message' => $allSuccess
                    ? 'Todos los DTEs se emitieron correctamente. Revisa tu correo.'
                    : 'Algunos DTEs fallaron. Puedes reemitirlos desde el listado de ventas.'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error en processMultiProviderSale: ' . $e->getMessage());
            return response()->json([
                'res' => 0,
                'error' => 'Error al procesar venta múltiple',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Agrupa los detalles de venta por proveedor
     */
    private function groupDetailsByProvider($sale)
    {
        $details = Salesdetail::where('sale_id', $sale->id)->get();
        $groups = [];

        foreach ($details as $detail) {
            $key = $detail->line_provider_id ?? 'no_provider';

            if (!isset($groups[$key])) {
                $provider = null;
                $providerId = $detail->line_provider_id; // Obtener directamente de line_provider_id

                if ($providerId) {
                    $provider = Provider::find($providerId);
                }

                $groups[$key] = [
                    'provider_id' => $providerId, // Guardar el line_provider_id directamente
                    'provider_name' => $provider ? $provider->razonsocial : 'Servicio Propio',
                    'details' => []
                ];

                // Log para debugging
                Log::info('Grupo creado', [
                    'key' => $key,
                    'provider_id' => $providerId,
                    'provider_name' => $groups[$key]['provider_name']
                ]);
            }

            $groups[$key]['details'][] = $detail;
        }

        return $groups;
    }

    /**
     * Crea una venta hija a partir del padre
     */
    private function createChildSale($parentSale, $group)
    {
        $childSale = new Sale();
        $childSale->parent_sale_id = $parentSale->id;
        $childSale->is_parent = 0;
        $childSale->client_id = $parentSale->client_id;
        $childSale->company_id = $parentSale->company_id;
        $childSale->typedocument_id = $parentSale->typedocument_id;
        $childSale->user_id = $parentSale->user_id;
        $childSale->date = $parentSale->date;
        $childSale->waytopay = $parentSale->waytopay;
        $childSale->typesale = 2; // Borrador inicialmente
        $childSale->acuenta = $group['provider_name'];

        // IMPORTANTE: Guardar el provider_id desde line_provider_id del primer detalle del grupo
        // Esto es necesario para que el CLQ pueda encontrar el documento por provider_id
        // El provider_id debe ser el mismo que line_provider_id de salesdetails
        $firstDetail = !empty($group['details']) ? $group['details'][0] : null;

        // Obtener provider_id del grupo o del primer detalle
        $providerId = $group['provider_id'] ?? ($firstDetail ? $firstDetail->line_provider_id : null);

        // Asignar provider_id directamente (puede ser null para "Servicio Propio")
        $childSale->provider_id = $providerId;

        // Log para debugging
        Log::info('Creando venta hija', [
            'parent_id' => $parentSale->id,
            'provider_id_asignado' => $childSale->provider_id,
            'provider_name' => $group['provider_name'] ?? 'N/A',
            'group_provider_id' => $group['provider_id'] ?? 'N/A',
            'first_detail_line_provider_id' => $firstDetail ? $firstDetail->line_provider_id : 'N/A',
            'details_count' => count($group['details'] ?? [])
        ]);

        // Calcular total
        $total = 0;
        foreach ($group['details'] as $detail) {
            $total += $detail->pricesale + $detail->detained13 - $detail->detained - $detail->renta + $detail->nosujeta + $detail->exempt;
        }
        $childSale->totalamount = round($total, 2);
        $childSale->save();

        // Verificar que se guardó correctamente
        $savedChild = Sale::find($childSale->id);
        Log::info('Venta hija guardada - Verificación provider_id', [
            'child_id' => $childSale->id,
            'provider_id_guardado' => $savedChild->provider_id ?? 'NULL',
            'provider_id_esperado' => $childSale->provider_id ?? 'NULL',
            'coincide' => ($savedChild->provider_id == $childSale->provider_id) ? 'SÍ' : 'NO'
        ]);

        if ($savedChild && $savedChild->provider_id != $childSale->provider_id) {
            Log::error('ERROR: provider_id no se guardó correctamente', [
                'child_id' => $childSale->id,
                'expected' => $childSale->provider_id,
                'actual' => $savedChild->provider_id
            ]);
        }

        // Copiar detalles
        foreach ($group['details'] as $detail) {
            $newDetail = $detail->replicate();
            $newDetail->sale_id = $childSale->id;
            $newDetail->save();
        }

        return $childSale;
    }

    /**
     * Calcula el monto total de un grupo de detalles
     */
    private function calculateChildAmount($details)
    {
        $total = 0;
        foreach ($details as $detail) {
            $total += $detail->pricesale + $detail->detained13 - $detail->detained - $detail->renta + $detail->nosujeta + $detail->exempt;
        }
        return '$' . number_format($total, 2, '.', '');
    }

    /**
     * Emite DTE para una venta hija usando la lógica existente
     */
    private function emitDTEForChild($childSale, $amount)
    {
        try {
            $corrEncoded = base64_encode($childSale->id);

            // Llamar a la función actual de emisión de DTE
            // (El código de emisión ya existe en createdocument, se reutiliza)
            $response = $this->createdocument($corrEncoded, $amount);

            // Manejar tanto Response como string
            if ($response instanceof \Illuminate\Http\JsonResponse || $response instanceof \Illuminate\Http\Response) {
                $responseData = json_decode($response->getContent(), true);
            } else {
                // Si es string, decodificarlo directamente
                $responseData = is_string($response) ? json_decode($response, true) : $response;
            }

            // Validar que se pudo decodificar el JSON
            if ($responseData === null && json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Error al decodificar respuesta de createdocument: ' . json_last_error_msg());
            }

            // Obtener información del DTE creado
            $dte = Dte::where('sale_id', $childSale->id)->latest()->first();

            // Obtener rutas de PDF y JSON desde la respuesta o desde el DTE
            $pdfPath = $responseData['pdf_path'] ?? null;
            $jsonPath = $responseData['json_path'] ?? null;

            // Si no están en la respuesta, intentar obtenerlas del DTE
            if (!$pdfPath && $dte) {
                // La ruta del PDF generalmente se genera en genera_pdf
                // Por ahora, usamos null si no está disponible
            }

            return [
                'success' => isset($responseData['res']) && $responseData['res'] == 1,
                'data' => $responseData,
                'pdf' => $pdfPath,
                'json' => $jsonPath,
                'dte_id' => $dte ? $dte->id : null,
                'codigo_generacion' => $dte ? $dte->codigoGeneracion : null,
                'estado_hacienda' => $dte ? $dte->estadoHacienda : null,
                'error' => $responseData['message'] ?? ($responseData['error'] ?? null)
            ];
        } catch (\Exception $e) {
            Log::error('Error emitiendo DTE hijo: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Envía UN correo con todos los PDFs y JSONs de las ventas hijas.
     * Se invoca al completar el flujo de presentación a Hacienda (processMultiProviderSale).
     * Reutiliza la lógica de enviar_correo_padre: genera PDFs y JSONs desde los hijos con DTE.
     */
    private function sendConsolidatedEmail(Sale $parentSale)
    {
        try {
            $client = $parentSale->client;

            if (!$client || !$client->email) {
                Log::warning('Cliente sin email, no se envió correo consolidado para venta padre #' . $parentSale->id);
                return;
            }

            // Obtener todos los hijos con DTE (misma lógica que enviar_correo_padre)
            $childSales = Sale::with(['dte'])
                ->where('parent_sale_id', $parentSale->id)
                ->whereHas('dte', function ($query) {
                    $query->whereNotNull('json');
                })
                ->get();

            if ($childSales->isEmpty()) {
                Log::warning('No hay hijos con DTE para correo consolidado, venta padre #' . $parentSale->id);
                return;
            }

            $nombre = $client->tpersona == 'J'
                ? ($client->name_contribuyente ?? 'Cliente')
                : trim(($client->firstname ?? '') . ' ' . ($client->firstlastname ?? ''));
            if (empty(trim($nombre ?? ''))) {
                $nombre = 'Cliente';
            }

            $archivos = [];
            $archivosNombres = [];

            foreach ($childSales as $childSale) {
                $dte = $childSale->dte;
                if (!$dte) {
                    continue;
                }

                try {
                    $pdf = $this->genera_pdf($childSale->id);

                    $sale_json = $childSale->json;
                    if (!$sale_json) {
                        Log::warning("No hay JSON en sales para hijo ID: {$childSale->id}");
                        continue;
                    }

                    $json_root = json_decode($sale_json, true);
                    if (!is_array($json_root)) {
                        Log::warning("JSON inválido para hijo ID: {$childSale->id}");
                        continue;
                    }

                    $json_enviado = $json_root['json']['json_enviado'] ?? $json_root;
                    $json = $this->limpiarJsonParaCorreo($json_enviado);
                    $nombreArchivo = $this->obtenerNombreArchivo($dte, $json_enviado);

                    $archivos[$nombreArchivo . '.pdf'] = $pdf->output();
                    $archivosNombres[] = $nombreArchivo . '.pdf';

                    $archivos[$nombreArchivo . '.json'] = $json;
                    $archivosNombres[] = $nombreArchivo . '.json';
                } catch (\Exception $e) {
                    Log::error("Error generando PDF/JSON para hijo ID: {$childSale->id} en sendConsolidatedEmail: " . $e->getMessage());
                    continue;
                }
            }

            if (empty($archivos)) {
                Log::warning('No se pudieron generar archivos para correo consolidado, venta padre #' . $parentSale->id);
                return;
            }

            $data = [
                'nombre' => $nombre,
                'numero' => $parentSale->id,
                'json' => null,
                'es_venta_padre' => true,
                'documentos_count' => count($childSales),
            ];

            $asunto = 'Documentos Tributarios - Venta #' . $parentSale->id . ' (' . count($childSales) . ' documento' . (count($childSales) > 1 ? 's' : '') . ')';

            $correo = new EnviarCorreo($data);
            $correo->subject($asunto);

            foreach ($archivos as $nombreArchivo => $contenido) {
                $correo->attachData($contenido, $nombreArchivo);
            }

            Mail::to($client->email)->send($correo);

            Log::info('Correo consolidado enviado tras presentación a Hacienda', [
                'parent_sale_id' => $parentSale->id,
                'email' => $client->email,
                'documentos' => count($childSales),
                'archivos' => $archivosNombres,
            ]);
        } catch (\Exception $e) {
            Log::error('Error enviando correo consolidado: ' . $e->getMessage());
        }
    }
}
