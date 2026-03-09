<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Purchase;
use App\Models\Report;
use App\Models\Sale;
use App\Models\Salesdetail;
use App\Models\Dte;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    public function sales(){
        return view('reports.sales');
    }

    public function purchases(){
        return view('reports.purchases');
    }

    public function reportsales($company, $year, $period){
        $sales_r = Sale::join('typedocuments', 'typedocuments.id', '=', 'sales.typedocument_id')
        ->join('clients', 'clients.id', '=', 'sales.client_id')
        ->join('companies', 'companies.id', '=', 'sales.company_id')
        ->select('sales.*',
        'typedocuments.description AS document_name',
        'clients.firstname',
        'clients.firstlastname',
        'clients.comercial_name',
        'clients.tpersona',
        'companies.name AS company_name')
        ->where("companies.id", "=", $company)
        ->whereRaw('YEAR(sales.date) = ?', [$year])
        ->whereRaw('MONTH(sales.date) = ?', [$period])
        ->get();
        $sales_r1['data'] = $sales_r;
        return response()->json($sales_r1);
    }
    public function reportpurchases($company, $year, $period){
        $purchases_r = Purchase::join('typedocuments', 'typedocuments.id', '=', 'purchases.document_id')
        ->join('providers', 'providers.id', '=', 'purchases.provider_id')
        ->join('companies', 'companies.id', '=', 'purchases.company_id')
        ->select('purchases.*',
        'typedocuments.description AS document_name',
        'providers.razonsocial AS nameprovider',
        'companies.name AS company_name')
        ->where("companies.id", "=", $company)
        ->whereRaw('YEAR(purchases.datedoc) = ?', [$year])
        ->whereRaw('MONTH(purchases.datedoc) = ?', [$period])
        ->get();
        $purchases_r1['data'] = $purchases_r;
        return response()->json($purchases_r1);
    }

    public function contribuyentes(){
            return view('reports.contribuyentes');
    }
    public function reportyear(){
            return view('reports.reportyear');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function contribusearch(Request $request){
        $Company = Company::find($request['company']);
        $dteEmisionSub = DB::table('dte')
            ->select('dte.sale_id', 'dte.id_doc', 'dte.codigoGeneracion', 'dte.selloRecibido', 'dte.fhRecibido', 'dte.json', 'dte.codEstado')
            ->whereIn('dte.codTransaction', ['01', '05', '06'])
            ->whereRaw('dte.id = (SELECT MAX(d2.id) FROM dte d2 WHERE d2.sale_id = dte.sale_id AND d2.codTransaction IN ("01","05","06"))');
        $sales = Sale::leftjoin('clients', 'sales.client_id', '=', 'clients.id')
        ->leftJoinSub($dteEmisionSub, 'dte_emis', 'dte_emis.sale_id', '=', 'sales.id')
        ->select('*','sales.id AS correlativo',
        'clients.ncr AS ncrC',
        'dte_emis.id_doc AS numeroControl',
        'dte_emis.codigoGeneracion AS codigoGeneracion',
        'dte_emis.selloRecibido AS selloRecibido')
        ->selectRaw("(SELECT dte_anul.id_doc FROM dte dte_anul WHERE dte_anul.sale_id = sales.id AND dte_anul.codTransaction = '02' LIMIT 1) AS numeroControl_anulacion")
        ->selectRaw("(SELECT dte_anul.codigoGeneracion FROM dte dte_anul WHERE dte_anul.sale_id = sales.id AND dte_anul.codTransaction = '02' LIMIT 1) AS codigoGeneracion_anulacion")
        ->selectRaw("(SELECT dte_anul.selloRecibido FROM dte dte_anul WHERE dte_anul.sale_id = sales.id AND dte_anul.codTransaction = '02' LIMIT 1) AS selloRecibido_anulacion")
        ->selectRaw("CASE
            WHEN dte_emis.fhRecibido IS NOT NULL
            THEN DATE_FORMAT(dte_emis.fhRecibido, '%d/%m/%Y')
            WHEN dte_emis.json IS NOT NULL AND JSON_EXTRACT(dte_emis.json, '$.identificacion.fecEmi') IS NOT NULL
            THEN DATE_FORMAT(STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(dte_emis.json, '$.identificacion.fecEmi')), '%Y-%m-%d'), '%d/%m/%Y')
            ELSE DATE_FORMAT(sales.date, '%d/%m/%Y')
        END AS dateF ")
        ->selectRaw("CASE
            WHEN clients.tpersona = 'J' THEN clients.comercial_name
            WHEN clients.tpersona = 'N' THEN CONCAT_WS(' ', clients.firstname, clients.secondname, clients.firstlastname, clients.secondlastname)
            ELSE ''
        END AS nombre_completo")
        ->selectRaw("(SELECT SUM(sde.exempt) FROM salesdetails AS sde WHERE sde.sale_id=sales.id) AS exenta")
        ->selectRaw("(SELECT
            COALESCE(SUM(sdg.pricesale), 0) -
            (SELECT
                COALESCE(SUM(CASE
                    WHEN sd.pricesale > 0 AND (sd.exempt = 0 OR sd.exempt IS NULL) AND (sd.nosujeta = 0 OR sd.nosujeta IS NULL) THEN sd.fee
                    ELSE 0
                END), 0) +
                COALESCE(SUM(CASE
                    WHEN p.name IN ('Cargo administrativo', 'CXS') AND sd.pricesale > 0 AND (sd.exempt = 0 OR sd.exempt IS NULL) AND (sd.nosujeta = 0 OR sd.nosujeta IS NULL) THEN
                        (sd.pricesale + sd.nosujeta + sd.exempt)
                    ELSE 0
                END), 0)
            FROM salesdetails AS sd
            LEFT JOIN products AS p ON p.id = sd.product_id
            WHERE sd.sale_id = sales.id)
        FROM salesdetails AS sdg WHERE sdg.sale_id=sales.id) AS gravada")
        ->selectRaw("(SELECT SUM(sdn.nosujeta) FROM salesdetails AS sdn WHERE sdn.sale_id=sales.id) AS nosujeta")
        ->selectRaw("(SELECT
            COALESCE(SUM(sdi.detained13), 0) -
            (SELECT
                COALESCE(SUM(CASE
                    WHEN sd.pricesale > 0 AND (sd.exempt = 0 OR sd.exempt IS NULL) AND (sd.nosujeta = 0 OR sd.nosujeta IS NULL) THEN sd.feeiva
                    ELSE 0
                END), 0) +
                COALESCE(SUM(CASE
                    WHEN p.name IN ('Cargo administrativo', 'CXS') AND sd.pricesale > 0 AND (sd.exempt = 0 OR sd.exempt IS NULL) AND (sd.nosujeta = 0 OR sd.nosujeta IS NULL) THEN
                        (sd.detained13)
                    ELSE 0
                END), 0)
            FROM salesdetails AS sd
            LEFT JOIN products AS p ON p.id = sd.product_id
            WHERE sd.sale_id = sales.id)
        FROM salesdetails AS sdi WHERE sdi.sale_id=sales.id) AS iva")
        ->selectRaw("(SELECT
            COALESCE(SUM(CASE
                WHEN sd.pricesale > 0 AND (sd.exempt = 0 OR sd.exempt IS NULL) AND (sd.nosujeta = 0 OR sd.nosujeta IS NULL) THEN sd.fee
                ELSE 0
            END), 0) +
            COALESCE(SUM(CASE
                WHEN p.name IN ('Cargo administrativo', 'CXS') AND sd.pricesale > 0 AND (sd.exempt = 0 OR sd.exempt IS NULL) AND (sd.nosujeta = 0 OR sd.nosujeta IS NULL) THEN
                    (sd.pricesale + sd.nosujeta + sd.exempt)
                ELSE 0
            END), 0)
        FROM salesdetails AS sd
        LEFT JOIN products AS p ON p.id = sd.product_id
        WHERE sd.sale_id = sales.id) AS fee")
        ->selectRaw("(SELECT
            COALESCE(SUM(CASE
                WHEN sd.pricesale > 0 AND (sd.exempt = 0 OR sd.exempt IS NULL) AND (sd.nosujeta = 0 OR sd.nosujeta IS NULL) THEN sd.feeiva
                ELSE 0
            END), 0) +
            COALESCE(SUM(CASE
                WHEN p.name IN ('Cargo administrativo', 'CXS') AND sd.pricesale > 0 AND (sd.exempt = 0 OR sd.exempt IS NULL) AND (sd.nosujeta = 0 OR sd.nosujeta IS NULL) THEN
                    (sd.detained13)
                ELSE 0
            END), 0)
        FROM salesdetails AS sd
        LEFT JOIN products AS p ON p.id = sd.product_id
        WHERE sd.sale_id = sales.id) AS ivafee")
        // Marcar si la venta es propia o a terceros
        ->selectRaw("CASE
            WHEN sales.provider_id IS NOT NULL OR EXISTS (
                SELECT 1 FROM salesdetails sdter
                WHERE sdter.sale_id = sales.id
                  AND sdter.line_provider_id IS NOT NULL
            ) THEN 'TERCEROS'
            ELSE 'PROPIA'
        END AS tipo_venta")
        // Excluir ventas padre del libro; solo mostrar ventas hijas o ventas sin relación
        ->where(function($q) {
            $q->whereNull('sales.is_parent')
              ->orWhere('sales.is_parent', 0);
        })
        ->where('sales.typedocument_id', "=", "3")
        ->whereRaw('YEAR(sales.date)=?', $request['year'])
        ->whereRaw('MONTH(sales.date)=?', $request['period'])
        ->WhereRaw('DAY(sales.date) BETWEEN "01" AND "31"')
        ->where('sales.company_id', '=', $request['company'])
        ->where('dte_emis.codEstado', '=', '02')
        ->orderBy('sales.id')
        ->get();
        return view('reports.contribuyentes', array(
            "heading" => $Company,
            "yearB" => $request['year'],
            "period" => $request['period'],
            "sales" => $sales
        ));
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function yearsearch(Request $request){
        $Company = Company::find($request['company']);
        $sales = Sale::join('salesdetails','salesdetails.sale_id', '=','sales.id')
        ->join('iva','iva.company_id', '=', 'sales.company_id')
        ->selectRaw("SUM((CASE sales.typedocument_id WHEN 3 THEN
        CASE WHEN salesdetails.exempt<>'0' THEN ROUND((salesdetails.exempt),2) WHEN salesdetails.exempt='0' THEN ROUND(((salesdetails.pricesale)/1.13),2) END
        WHEN 6 THEN
        CASE WHEN salesdetails.exempt<>'0' THEN ROUND((salesdetails.exempt),2) WHEN salesdetails.exempt='0' THEN  ROUND((salesdetails.pricesale)/1.13,2) END END)) as GRAVADAS")
        ->selectRaw("SUM((CASE sales.typedocument_id WHEN 3 THEN
        CASE WHEN salesdetails.exempt<>'0' THEN 0 WHEN salesdetails.exempt='0' THEN ROUND(salesdetails.pricesale-salesdetails.pricesale/1.13,2) END
        WHEN 6 THEN CASE WHEN salesdetails.exempt<>'0' THEN 0 WHEN salesdetails.exempt='0' THEN ROUND((salesdetails.pricesale-(salesdetails.pricesale)/1.13),2) END END)) DEBITO")
        ->selectRaw("SUM((CASE sales.typedocument_id WHEN 3 THEN
        CASE WHEN salesdetails.exempt<>'0' THEN ROUND(salesdetails.exempt,2) WHEN salesdetails.exempt='0' THEN ROUND(salesdetails.pricesale/1.13+(salesdetails.pricesale-(salesdetails.pricesale)/1.13),2) END
         WHEN 6 THEN
         CASE WHEN salesdetails.exempt<>'0' THEN ROUND(salesdetails.exempt,2) WHEN salesdetails.exempt='0' THEN ROUND(salesdetails.pricesale/1.13+(salesdetails.pricesale-(salesdetails.pricesale)/1.13),2) END END)) TOTALV")
        ->selectRaw("YEAR(sales.date) as yearsale")
        ->selectRaw("MONTH(sales.date) as monthsale")
        ->where('sales.company_id', '=', $request['company'])
        ->where('sales.state', '<>', 0)
        ->groupBy([
            'yearsale',
            'monthsale'
        ])
        ->orderBy('monthsale', 'asc')
         ->get();
         $purchases = Purchase::selectRaw("YEAR(purchases.date) as yearpuchase")
         ->selectRaw("SUM(purchases.gravada+purchases.exenta) INTERNASPU")
         ->selectRaw("SUM(purchases.iva) CREDITOPU")
         ->selectRaw("SUM(purchases.gravada+purchases.iva+purchases.exenta) TOTALC")
         ->selectRaw("MONTH(purchases.date) as monthpurchase")
         ->groupBy([
            'yearpuchase',
            'monthpurchase'
        ])
        ->orderBy('monthpurchase', 'asc')
         ->get();
        return view('reports.reportyear', array(
            "heading" => $Company,
            "yearB" => $request['year'],
            "purchases" => $purchases,
            "sales" => $sales
        ));
    }

    public function consumidor(){
        return view('reports.consumidor');
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     */
    public function consumidorsearch(Request $request){
        $Company = Company::find($request['company']);
        $dteEmisionSubCons = DB::table('dte')
            ->select('dte.sale_id', 'dte.id_doc', 'dte.codigoGeneracion', 'dte.selloRecibido', 'dte.fhRecibido', 'dte.json', 'dte.codEstado')
            ->whereIn('dte.codTransaction', ['01', '05', '06'])
            ->whereRaw('dte.id = (SELECT MAX(d2.id) FROM dte d2 WHERE d2.sale_id = dte.sale_id AND d2.codTransaction IN ("01","05","06"))');
        $sales = Sale::leftjoin('clients', 'sales.client_id', '=', 'clients.id')
        ->leftJoinSub($dteEmisionSubCons, 'dte_emis', 'dte_emis.sale_id', '=', 'sales.id')
        ->select('*','sales.id AS correlativo',
        'clients.ncr AS ncrC',
        'dte_emis.id_doc AS numeroControl',
        'dte_emis.codigoGeneracion AS codigoGeneracion',
        'dte_emis.selloRecibido AS selloRecibido')
        ->selectRaw("(SELECT dte_anul.id_doc FROM dte dte_anul WHERE dte_anul.sale_id = sales.id AND dte_anul.codTransaction = '02' LIMIT 1) AS numeroControl_anulacion")
        ->selectRaw("(SELECT dte_anul.codigoGeneracion FROM dte dte_anul WHERE dte_anul.sale_id = sales.id AND dte_anul.codTransaction = '02' LIMIT 1) AS codigoGeneracion_anulacion")
        ->selectRaw("(SELECT dte_anul.selloRecibido FROM dte dte_anul WHERE dte_anul.sale_id = sales.id AND dte_anul.codTransaction = '02' LIMIT 1) AS selloRecibido_anulacion")
        ->selectRaw("CASE
            WHEN dte_emis.fhRecibido IS NOT NULL
            THEN DATE_FORMAT(dte_emis.fhRecibido, '%d/%m/%Y')
            WHEN dte_emis.json IS NOT NULL AND JSON_EXTRACT(dte_emis.json, '$.identificacion.fecEmi') IS NOT NULL
            THEN DATE_FORMAT(STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(dte_emis.json, '$.identificacion.fecEmi')), '%Y-%m-%d'), '%d/%m/%Y')
            ELSE DATE_FORMAT(sales.date, '%d/%m/%Y')
        END AS dateF ")
        ->selectRaw("CASE
            WHEN clients.tpersona = 'J' THEN clients.comercial_name
            WHEN clients.tpersona = 'N' THEN CONCAT_WS(' ', clients.firstname, clients.secondname, clients.firstlastname, clients.secondlastname)
            ELSE ''
        END AS nombre_completo")
        ->selectRaw("(SELECT SUM(sde.exempt) FROM salesdetails AS sde WHERE sde.sale_id=sales.id) AS exenta")
        ->selectRaw("(SELECT
            COALESCE(SUM(sdg.pricesale), 0) -
            (SELECT
                COALESCE(SUM(CASE
                    WHEN sd.pricesale > 0 AND (sd.exempt = 0 OR sd.exempt IS NULL) AND (sd.nosujeta = 0 OR sd.nosujeta IS NULL) THEN sd.fee
                    ELSE 0
                END), 0) +
                COALESCE(SUM(CASE
                    WHEN p.name IN ('Cargo administrativo', 'CXS') AND sd.pricesale > 0 AND (sd.exempt = 0 OR sd.exempt IS NULL) AND (sd.nosujeta = 0 OR sd.nosujeta IS NULL) THEN
                        (sd.pricesale + sd.nosujeta + sd.exempt)
                    ELSE 0
                END), 0)
            FROM salesdetails AS sd
            LEFT JOIN products AS p ON p.id = sd.product_id
            WHERE sd.sale_id = sales.id)
        FROM salesdetails AS sdg WHERE sdg.sale_id=sales.id) AS gravada")
        ->selectRaw("(SELECT SUM(sdn.nosujeta) FROM salesdetails AS sdn WHERE sdn.sale_id=sales.id) AS nosujeta")
        ->selectRaw("(SELECT
            COALESCE(SUM(sdi.detained13), 0) -
            (SELECT
                COALESCE(SUM(CASE
                    WHEN sd.pricesale > 0 AND (sd.exempt = 0 OR sd.exempt IS NULL) AND (sd.nosujeta = 0 OR sd.nosujeta IS NULL) THEN sd.feeiva
                    ELSE 0
                END), 0) +
                COALESCE(SUM(CASE
                    WHEN p.name IN ('Cargo administrativo', 'CXS') AND sd.pricesale > 0 AND (sd.exempt = 0 OR sd.exempt IS NULL) AND (sd.nosujeta = 0 OR sd.nosujeta IS NULL) THEN
                        (sd.detained13)
                    ELSE 0
                END), 0)
            FROM salesdetails AS sd
            LEFT JOIN products AS p ON p.id = sd.product_id
            WHERE sd.sale_id = sales.id)
        FROM salesdetails AS sdi WHERE sdi.sale_id=sales.id) AS iva")
        ->selectRaw("(SELECT
            COALESCE(SUM(CASE
                WHEN sd.pricesale > 0 AND (sd.exempt = 0 OR sd.exempt IS NULL) AND (sd.nosujeta = 0 OR sd.nosujeta IS NULL) THEN sd.fee
                ELSE 0
            END), 0) +
            COALESCE(SUM(CASE
                WHEN p.name IN ('Cargo administrativo', 'CXS') AND sd.pricesale > 0 AND (sd.exempt = 0 OR sd.exempt IS NULL) AND (sd.nosujeta = 0 OR sd.nosujeta IS NULL) THEN
                    (sd.pricesale + sd.nosujeta + sd.exempt)
                ELSE 0
            END), 0)
        FROM salesdetails AS sd
        LEFT JOIN products AS p ON p.id = sd.product_id
        WHERE sd.sale_id = sales.id) AS fee")
        ->selectRaw("(SELECT
            COALESCE(SUM(CASE
                WHEN sd.pricesale > 0 AND (sd.exempt = 0 OR sd.exempt IS NULL) AND (sd.nosujeta = 0 OR sd.nosujeta IS NULL) THEN sd.feeiva
                ELSE 0
            END), 0) +
            COALESCE(SUM(CASE
                WHEN p.name IN ('Cargo administrativo', 'CXS') AND sd.pricesale > 0 AND (sd.exempt = 0 OR sd.exempt IS NULL) AND (sd.nosujeta = 0 OR sd.nosujeta IS NULL) THEN
                    (sd.detained13)
                ELSE 0
            END), 0)
        FROM salesdetails AS sd
        LEFT JOIN products AS p ON p.id = sd.product_id
        WHERE sd.sale_id = sales.id) AS ivafee")
        ->selectRaw("(SELECT SUM(sdret.detained) FROM salesdetails AS sdret WHERE sdret.sale_id=sales.id) AS iva_retenido")
        ->selectRaw("(SELECT SUM(sdper.detainedP) FROM salesdetails AS sdper WHERE sdper.sale_id=sales.id) AS iva_percibido")
        // Marcar si la venta es propia o a terceros
        ->selectRaw("CASE
            WHEN sales.provider_id IS NOT NULL OR EXISTS (
                SELECT 1 FROM salesdetails sdter
                WHERE sdter.sale_id = sales.id
                  AND sdter.line_provider_id IS NOT NULL
            ) THEN 'TERCEROS'
            ELSE 'PROPIA'
        END AS tipo_venta")
        // Excluir ventas padre del libro; solo mostrar ventas hijas o ventas sin relación
        ->where(function($q) {
            $q->whereNull('sales.is_parent')
              ->orWhere('sales.is_parent', 0);
        })
        ->where('sales.typedocument_id', "=", "6")
        ->whereRaw('(clients.tpersona = "N" OR clients.tpersona = "J")' )
        ->whereRaw('YEAR(sales.date)=?', $request['year'])
        ->whereRaw('MONTH(sales.date)=?', $request['period'])
        ->WhereRaw('DAY(sales.date) BETWEEN "01" AND "31"')
        ->where('sales.company_id', '=', $request['company'])
        ->where(function($query) {
            $query->whereNull('dte_emis.codEstado')
                  ->orWhere('dte_emis.codEstado', '=', '02');
        })
        ->orderBy('sales.id')
        ->get();
        return view('reports.consumidor', array(
            "heading" => $Company,
            "yearB" => $request['year'],
            "period" => $request['period'],
            "sales" => $sales
        ));
    }

    public function bookpurchases(){
        return view('reports.bookpurchases');
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     */
    public function comprassearch(Request $request){
        $Company = Company::find($request['company']);

        $purchases = Purchase::join('providers AS pro', 'pro.id', '=', 'purchases.provider_id')
        ->select('*')
        ->selectRaw("DATE_FORMAT(purchases.date, '%d/%m/%Y') AS dateF")
        ->whereRaw('YEAR(purchases.date)=?', $request['year'])
        ->whereRaw('MONTH(purchases.date)=?', $request['period'])
        ->WhereRaw('DAY(purchases.date) BETWEEN "01" AND "31"')
        ->where('purchases.company_id', '=', $request['company'])
        ->orderByRaw('MONTH(purchases.date)')
        ->orderBy('purchases.date')
        ->get();
        return view('reports.bookpurchases', array(
            "heading" => $Company,
            "yearB" => $request['year'],
            "period" => $request['period'],
            "purchases" => $purchases
        ));
    }
    public function directas(){

        return view('reports.directas');

    }

    /**
     * Mostrar vista de reporte de control de IVA y pago a cuenta
     *
     * @return \Illuminate\Http\Response
     */
    public function ivacontrol(){
        return view('reports.ivacontrol');
    }

    /**
     * Procesar búsqueda de control de IVA y pago a cuenta
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function ivacontrolsearch(Request $request){
        $Company = Company::find($request['company']);

        // Obtener totales de ventas del mes (solo facturas con DTE emitido)
        $salesData = Sale::join('salesdetails','salesdetails.sale_id', '=','sales.id')
            ->leftJoin('dte', 'dte.sale_id', '=', 'sales.id')
            ->selectRaw("SUM(salesdetails.detained13) as debito_fiscal")
            ->selectRaw("SUM(salesdetails.pricesale) as ventas_gravadas")
            ->selectRaw("SUM(salesdetails.exempt) as ventas_exentas")
            ->selectRaw("SUM(salesdetails.nosujeta) as ventas_nosujetas")
            ->whereRaw('YEAR(sales.date)=?', $request['year'])
            ->whereRaw('MONTH(sales.date)=?', $request['period'])
            ->where('sales.company_id', '=', $request['company'])
            ->where('sales.state', '<>', 0)
            // Solo incluir DTE que fueron enviados exitosamente (estado "Enviado")
            ->where('dte.codEstado', '=', '02')
            ->first();

        // Obtener totales de compras del mes
        $purchasesData = Purchase::selectRaw("SUM(purchases.iva) as credito_fiscal")
            ->selectRaw("SUM(purchases.gravada) as compras_gravadas")
            ->selectRaw("SUM(purchases.exenta) as compras_exentas")
            ->whereRaw('YEAR(purchases.date)=?', $request['year'])
            ->whereRaw('MONTH(purchases.date)=?', $request['period'])
            ->where('purchases.company_id', '=', $request['company'])
            ->first();

        // Calcular IVA a pagar
        $debito_fiscal = $salesData->debito_fiscal ?? 0;
        $credito_fiscal = $purchasesData->credito_fiscal ?? 0;
        $iva_a_pagar = $debito_fiscal - $credito_fiscal;

        // Calcular pago a cuenta (1% de ventas gravadas)
        $ventas_gravadas = $salesData->ventas_gravadas ?? 0;
        $pago_a_cuenta = $ventas_gravadas * 0.01;

        return view('reports.ivacontrol', array(
            "heading" => $Company,
            "yearB" => $request['year'],
            "period" => $request['period'],
            "debito_fiscal" => $debito_fiscal,
            "credito_fiscal" => $credito_fiscal,
            "iva_a_pagar" => $iva_a_pagar,
            "ventas_gravadas" => $ventas_gravadas,
            "ventas_exentas" => $salesData->ventas_exentas ?? 0,
            "ventas_nosujetas" => $salesData->ventas_nosujetas ?? 0,
            "compras_gravadas" => $purchasesData->compras_gravadas ?? 0,
            "compras_exentas" => $purchasesData->compras_exentas ?? 0,
            "pago_a_cuenta" => $pago_a_cuenta,
            "total_a_pagar" => $iva_a_pagar + $pago_a_cuenta
        ));
    }

    /**
     * Exportar reporte de contribuyentes a Excel
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function contribuyentesExcel(Request $request){
        $Company = Company::find($request['company']);
        $dteEmisionSubExc = DB::table('dte')
            ->select('dte.sale_id', 'dte.id_doc', 'dte.codigoGeneracion', 'dte.selloRecibido', 'dte.fhRecibido', 'dte.json', 'dte.codEstado')
            ->whereIn('dte.codTransaction', ['01', '05', '06'])
            ->whereRaw('dte.id = (SELECT MAX(d2.id) FROM dte d2 WHERE d2.sale_id = dte.sale_id AND d2.codTransaction IN ("01","05","06"))');
        $sales = Sale::leftjoin('clients', 'sales.client_id', '=', 'clients.id')
        ->leftJoinSub($dteEmisionSubExc, 'dte_emis', 'dte_emis.sale_id', '=', 'sales.id')
        ->select('*','sales.id AS correlativo',
        'clients.ncr AS ncrC',
        'dte_emis.id_doc AS numeroControl',
        'dte_emis.codigoGeneracion AS codigoGeneracion',
        'dte_emis.selloRecibido AS selloRecibido')
        ->selectRaw("(SELECT dte_anul.id_doc FROM dte dte_anul WHERE dte_anul.sale_id = sales.id AND dte_anul.codTransaction = '02' LIMIT 1) AS numeroControl_anulacion")
        ->selectRaw("(SELECT dte_anul.codigoGeneracion FROM dte dte_anul WHERE dte_anul.sale_id = sales.id AND dte_anul.codTransaction = '02' LIMIT 1) AS codigoGeneracion_anulacion")
        ->selectRaw("(SELECT dte_anul.selloRecibido FROM dte dte_anul WHERE dte_anul.sale_id = sales.id AND dte_anul.codTransaction = '02' LIMIT 1) AS selloRecibido_anulacion")
        ->selectRaw("CASE
            WHEN dte_emis.fhRecibido IS NOT NULL
            THEN DATE_FORMAT(dte_emis.fhRecibido, '%d/%m/%Y')
            WHEN dte_emis.json IS NOT NULL AND JSON_EXTRACT(dte_emis.json, '$.identificacion.fecEmi') IS NOT NULL
            THEN DATE_FORMAT(STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(dte_emis.json, '$.identificacion.fecEmi')), '%Y-%m-%d'), '%d/%m/%Y')
            ELSE DATE_FORMAT(sales.date, '%d/%m/%Y')
        END AS dateF ")
        ->selectRaw("CASE
            WHEN clients.tpersona = 'J' THEN clients.comercial_name
            WHEN clients.tpersona = 'N' THEN CONCAT_WS(' ', clients.firstname, clients.secondname, clients.firstlastname, clients.secondlastname)
            ELSE ''
        END AS nombre_completo")
        ->selectRaw("(SELECT SUM(sde.exempt) FROM salesdetails AS sde WHERE sde.sale_id=sales.id) AS exenta")
        ->selectRaw("(SELECT
            COALESCE(SUM(sdg.pricesale), 0) -
            (SELECT
                COALESCE(SUM(CASE
                    WHEN sd.pricesale > 0 AND (sd.exempt = 0 OR sd.exempt IS NULL) AND (sd.nosujeta = 0 OR sd.nosujeta IS NULL) THEN sd.fee
                    ELSE 0
                END), 0) +
                COALESCE(SUM(CASE
                    WHEN p.name IN ('Cargo administrativo', 'CXS') AND sd.pricesale > 0 AND (sd.exempt = 0 OR sd.exempt IS NULL) AND (sd.nosujeta = 0 OR sd.nosujeta IS NULL) THEN
                        (sd.pricesale + sd.nosujeta + sd.exempt)
                    ELSE 0
                END), 0)
            FROM salesdetails AS sd
            LEFT JOIN products AS p ON p.id = sd.product_id
            WHERE sd.sale_id = sales.id)
        FROM salesdetails AS sdg WHERE sdg.sale_id=sales.id) AS gravada")
        ->selectRaw("(SELECT SUM(sdn.nosujeta) FROM salesdetails AS sdn WHERE sdn.sale_id=sales.id) AS nosujeta")
        ->selectRaw("(SELECT
            COALESCE(SUM(sdi.detained13), 0) -
            (SELECT
                COALESCE(SUM(CASE
                    WHEN sd.pricesale > 0 AND (sd.exempt = 0 OR sd.exempt IS NULL) AND (sd.nosujeta = 0 OR sd.nosujeta IS NULL) THEN sd.feeiva
                    ELSE 0
                END), 0) +
                COALESCE(SUM(CASE
                    WHEN p.name IN ('Cargo administrativo', 'CXS') AND sd.pricesale > 0 AND (sd.exempt = 0 OR sd.exempt IS NULL) AND (sd.nosujeta = 0 OR sd.nosujeta IS NULL) THEN
                        (sd.detained13)
                    ELSE 0
                END), 0)
            FROM salesdetails AS sd
            LEFT JOIN products AS p ON p.id = sd.product_id
            WHERE sd.sale_id = sales.id)
        FROM salesdetails AS sdi WHERE sdi.sale_id=sales.id) AS iva")
        ->selectRaw("(SELECT
            COALESCE(SUM(CASE
                WHEN sd.pricesale > 0 AND (sd.exempt = 0 OR sd.exempt IS NULL) AND (sd.nosujeta = 0 OR sd.nosujeta IS NULL) THEN sd.fee
                ELSE 0
            END), 0) +
            COALESCE(SUM(CASE
                WHEN p.name IN ('Cargo administrativo', 'CXS') AND sd.pricesale > 0 AND (sd.exempt = 0 OR sd.exempt IS NULL) AND (sd.nosujeta = 0 OR sd.nosujeta IS NULL) THEN
                    (sd.pricesale + sd.nosujeta + sd.exempt)
                ELSE 0
            END), 0)
        FROM salesdetails AS sd
        LEFT JOIN products AS p ON p.id = sd.product_id
        WHERE sd.sale_id = sales.id) AS fee")
        ->selectRaw("(SELECT
            COALESCE(SUM(CASE
                WHEN sd.pricesale > 0 AND (sd.exempt = 0 OR sd.exempt IS NULL) AND (sd.nosujeta = 0 OR sd.nosujeta IS NULL) THEN sd.feeiva
                ELSE 0
            END), 0) +
            COALESCE(SUM(CASE
                WHEN p.name IN ('Cargo administrativo', 'CXS') AND sd.pricesale > 0 AND (sd.exempt = 0 OR sd.exempt IS NULL) AND (sd.nosujeta = 0 OR sd.nosujeta IS NULL) THEN
                    (sd.detained13)
                ELSE 0
            END), 0)
        FROM salesdetails AS sd
        LEFT JOIN products AS p ON p.id = sd.product_id
        WHERE sd.sale_id = sales.id) AS ivafee")
        // Marcar si la venta es propia o a terceros
        ->selectRaw("CASE
            WHEN sales.provider_id IS NOT NULL OR EXISTS (
                SELECT 1 FROM salesdetails sdter
                WHERE sdter.sale_id = sales.id
                  AND sdter.line_provider_id IS NOT NULL
            ) THEN 'TERCEROS'
            ELSE 'PROPIA'
        END AS tipo_venta")
        // Excluir ventas padre del libro; solo mostrar ventas hijas o sin relación
        ->where(function($q) {
            $q->whereNull('sales.is_parent')
              ->orWhere('sales.is_parent', 0);
        })
        ->where('sales.typedocument_id', "=", "3")
        ->whereRaw('YEAR(sales.date)=?', $request['year'])
        ->whereRaw('MONTH(sales.date)=?', $request['period'])
        ->WhereRaw('DAY(sales.date) BETWEEN "01" AND "31"')
        ->where('sales.company_id', '=', $request['company'])
        ->where('dte_emis.codEstado', '=', '02')
        ->orderBy('sales.id')
        ->get();

        $mesesDelAno = array(
            "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio",
            "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"
        );
        $mesesDelAnoMayuscula = array_map('strtoupper', $mesesDelAno);

        // Construir HTML para Excel
        $html = '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
        $html .= '<head>';
        $html .= '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
        $html .= '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet>';
        $html .= '<x:Name>Ventas Contribuyentes</x:Name>';
        $html .= '<x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet>';
        $html .= '</x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->';
        $html .= '</head>';
        $html .= '<body>';
        $html .= '<table border="1">';

        // Encabezado
        $html .= '<tr><th colspan="22" style="text-align:center; font-weight:bold;">LIBRO DE VENTAS CONTRIBUYENTES (Valores expresados en USD)</th></tr>';
        $html .= '<tr><td colspan="22" style="text-align:center;">';
        $html .= '<b>Nombre del Contribuyente:</b> ' . $Company['name'] . ' ';
        $html .= '<b>N.R.C.:</b> ' . $Company['nrc'] . ' ';
        $html .= '<b>NIT:</b> ' . $Company['nit'] . ' ';
        $html .= '<b>MES:</b> ' . $mesesDelAnoMayuscula[(int)$request['period']-1] . ' ';
        $html .= '<b>AÑO:</b> ' . $request['year'];
        $html .= '</td></tr>';

        // Encabezados de columna
        $html .= '<tr>';
        $html .= '<th>NUM. CORR.</th>';
        $html .= '<th>Fecha Emisión</th>';
        $html .= '<th>No. Doc.</th>';
        $html .= '<th>Nombre del Cliente</th>';
        $html .= '<th>NRC</th>';
        $html .= '<th>Exentas</th>';
        $html .= '<th>Internas Gravadas</th>';
        $html .= '<th>Debito Fiscal</th>';
        $html .= '<th>FEE</th>';
        $html .= '<th>IVA FEE</th>';
        $html .= '<th>No Sujetas</th>';
        $html .= '<th>Exentas (Terceros)</th>';
        $html .= '<th>Internas Gravadas (Terceros)</th>';
        $html .= '<th>Debito Fiscal (Terceros)</th>';
        $html .= '<th>IVA Percibido</th>';
        $html .= '<th>TOTAL</th>';
        $html .= '<th>NÚMERO CONTROL DTE</th>';
        $html .= '<th>CÓDIGO GENERACIÓN</th>';
        $html .= '<th>SELLO RECEPCIÓN</th>';
        $html .= '<th>Nº CONTROL ANULACIÓN</th>';
        $html .= '<th>CÓD. GEN. ANULACIÓN</th>';
        $html .= '<th>SELLO ANULACIÓN</th>';
        $html .= '</tr>';

        // Datos
        $total_ex = 0;
        $total_gv = 0;
        $total_gv2 = 0;
        $total_iva = 0;
        $total_iva2 = 0;
        $total_ns = 0;
        $tot_final = 0;
        $total_iva2P = 0;
        $tot_fee = 0;
        $tot_ivafee = 0;
        $i = 1;

        foreach ($sales as $sale) {
            $html .= '<tr>';
            $html .= '<td>' . $i . '</td>';
            $html .= '<td>' . $sale['dateF'] . '</td>';
            $html .= '<td>' . ($sale['correlativo'] ?? '-') . '</td>';

            // Nombre del cliente completo
            if($sale['typesale']=='0'){
                $html .= '<td style="color: red; font-weight: bold;">ANULADO</td>';
            } else {
                $html .= '<td>' . strtoupper($sale['nombre_completo'] ?? '') . '</td>';
            }

            $html .= '<td>' . preg_replace('/[^0-9]/', '', $sale['ncrC'] ?? '') . '</td>';

            if($sale['typesale']=='0'){
                $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">0.00</td>';
                $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">0.00</td>';
                $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">0.00</td>';
                $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">0.00</td>';
                $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">0.00</td>';
                $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">0.00</td>';
            } else {
                $fee = $sale['fee'] ?? 0;
                $ivafee = $sale['ivafee'] ?? 0;
                $esTercero = isset($sale['tipo_venta']) && $sale['tipo_venta'] === 'TERCEROS';

                // Ventas propias
                if ($esTercero) {
                    $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">0.00</td>';
                    $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">0.00</td>';
                    $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">0.00</td>';
                } else {
                    $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($sale['exenta'], 2, '.', '') . '</td>';
                    $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($sale['gravada'], 2, '.', '') . '</td>';
                    $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($sale['iva'], 2, '.', '') . '</td>';

                    $total_ex += $sale['exenta'];
                    $total_gv += $sale['gravada'];
                    $total_iva += $sale['iva'];
                }

                $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($fee, 2, '.', '') . '</td>';
                $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($ivafee, 2, '.', '') . '</td>';
                $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($sale['nosujeta'], 2, '.', '') . '</td>';
                $tot_fee += $fee;
                $tot_ivafee += $ivafee;
                $total_ns += $sale['nosujeta'];
            }

            // Columnas de terceros
            if ($sale['typesale']=='0') {
                $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">0.00</td>';
                $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">0.00</td>';
                $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">0.00</td>';
                $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">0.00</td>';
            } else {
                $esTercero = isset($sale['tipo_venta']) && $sale['tipo_venta'] === 'TERCEROS';
                if ($esTercero) {
                    $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($sale['exenta'], 2, '.', '') . '</td>';
                    $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($sale['gravada'], 2, '.', '') . '</td>';
                    $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($sale['iva'], 2, '.', '') . '</td>';
                    $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($sale['iva_percibido'] ?? 0, 2, '.', '') . '</td>';
                    $total_gv2 += $sale['gravada'];
                    $total_iva2 += $sale['iva'];
                    $total_iva2P += ($sale['iva_percibido'] ?? 0);
                } else {
                    $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">0.00</td>';
                    $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">0.00</td>';
                    $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">0.00</td>';
                    $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">0.00</td>';
                }
            }

            if($sale['typesale']=='0'){
                $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">0.00</td>';
            } else {
                $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($sale['totalamount'], 2, '.', '') . '</td>';
                $tot_final += $sale['totalamount'];
            }

            $html .= '<td>' . ($sale['numeroControl'] ?? '-') . '</td>';
            $html .= '<td>' . ($sale['codigoGeneracion'] ?? '-') . '</td>';
            $html .= '<td>' . ($sale['selloRecibido'] ?? '-') . '</td>';
            $html .= '<td>' . (($sale['typesale'] ?? '') == '0' ? ($sale['numeroControl_anulacion'] ?? '-') : '-') . '</td>';
            $html .= '<td>' . (($sale['typesale'] ?? '') == '0' ? ($sale['codigoGeneracion_anulacion'] ?? '-') : '-') . '</td>';
            $html .= '<td>' . (($sale['typesale'] ?? '') == '0' ? ($sale['selloRecibido_anulacion'] ?? '-') : '-') . '</td>';
            $html .= '</tr>';
            $i++;
        }

        // Totales
        $html .= '<tr style="font-weight:bold;">';
        $html .= '<td colspan="5" style="text-align:right;">TOTALES DEL MES</td>';
        $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($total_ex, 2, '.', '') . '</td>';
        $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($total_gv, 2, '.', '') . '</td>';
        $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($total_iva, 2, '.', '') . '</td>';
        $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($tot_fee, 2, '.', '') . '</td>';
        $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($tot_ivafee, 2, '.', '') . '</td>';
        $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($total_ns, 2, '.', '') . '</td>';
        $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">0.00</td>';
        $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">0.00</td>';
        $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">0.00</td>';
        $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">0.00</td>';
        $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($tot_final, 2, '.', '') . '</td>';
        $html .= '<td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td>';
        $html .= '</tr>';

        $html .= '</table>';
        $html .= '</body></html>';

        // Enviar headers para descarga
        $filename = 'Ventas_Contribuyentes_' . $mesesDelAno[(int)$request['period']-1] . '_' . $request['year'] . '.xls';

        return response($html)
            ->header('Content-Type', 'application/vnd.ms-excel; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * Exportar reporte de consumidor a Excel
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function consumidorExcel(Request $request){
        $Company = Company::find($request['company']);
        $dteEmisionSubExcCons = DB::table('dte')
            ->select('dte.sale_id', 'dte.id_doc', 'dte.codigoGeneracion', 'dte.selloRecibido', 'dte.fhRecibido', 'dte.json', 'dte.codEstado')
            ->whereIn('dte.codTransaction', ['01', '05', '06'])
            ->whereRaw('dte.id = (SELECT MAX(d2.id) FROM dte d2 WHERE d2.sale_id = dte.sale_id AND d2.codTransaction IN ("01","05","06"))');
        $sales = Sale::leftjoin('clients', 'sales.client_id', '=', 'clients.id')
        ->leftJoinSub($dteEmisionSubExcCons, 'dte_emis', 'dte_emis.sale_id', '=', 'sales.id')
        ->select('*','sales.id AS correlativo',
        'clients.ncr AS ncrC',
        'dte_emis.id_doc AS numeroControl',
        'dte_emis.codigoGeneracion AS codigoGeneracion',
        'dte_emis.selloRecibido AS selloRecibido')
        ->selectRaw("(SELECT dte_anul.id_doc FROM dte dte_anul WHERE dte_anul.sale_id = sales.id AND dte_anul.codTransaction = '02' LIMIT 1) AS numeroControl_anulacion")
        ->selectRaw("(SELECT dte_anul.codigoGeneracion FROM dte dte_anul WHERE dte_anul.sale_id = sales.id AND dte_anul.codTransaction = '02' LIMIT 1) AS codigoGeneracion_anulacion")
        ->selectRaw("(SELECT dte_anul.selloRecibido FROM dte dte_anul WHERE dte_anul.sale_id = sales.id AND dte_anul.codTransaction = '02' LIMIT 1) AS selloRecibido_anulacion")
        ->selectRaw("CASE
            WHEN dte_emis.fhRecibido IS NOT NULL
            THEN DATE_FORMAT(dte_emis.fhRecibido, '%d/%m/%Y')
            WHEN dte_emis.json IS NOT NULL AND JSON_EXTRACT(dte_emis.json, '$.identificacion.fecEmi') IS NOT NULL
            THEN DATE_FORMAT(STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(dte_emis.json, '$.identificacion.fecEmi')), '%Y-%m-%d'), '%d/%m/%Y')
            ELSE DATE_FORMAT(sales.date, '%d/%m/%Y')
        END AS dateF ")
        ->selectRaw("CASE
            WHEN clients.tpersona = 'J' THEN clients.comercial_name
            WHEN clients.tpersona = 'N' THEN CONCAT_WS(' ', clients.firstname, clients.secondname, clients.firstlastname, clients.secondlastname)
            ELSE ''
        END AS nombre_completo")
        ->selectRaw("(SELECT SUM(sde.exempt) FROM salesdetails AS sde WHERE sde.sale_id=sales.id) AS exenta")
        ->selectRaw("(SELECT
            COALESCE(SUM(sdg.pricesale), 0) -
            (SELECT
                COALESCE(SUM(CASE
                    WHEN sd.pricesale > 0 AND (sd.exempt = 0 OR sd.exempt IS NULL) AND (sd.nosujeta = 0 OR sd.nosujeta IS NULL) THEN sd.fee
                    ELSE 0
                END), 0) +
                COALESCE(SUM(CASE
                    WHEN p.name IN ('Cargo administrativo', 'CXS') AND sd.pricesale > 0 AND (sd.exempt = 0 OR sd.exempt IS NULL) AND (sd.nosujeta = 0 OR sd.nosujeta IS NULL) THEN
                        (sd.pricesale + sd.nosujeta + sd.exempt)
                    ELSE 0
                END), 0)
            FROM salesdetails AS sd
            LEFT JOIN products AS p ON p.id = sd.product_id
            WHERE sd.sale_id = sales.id)
        FROM salesdetails AS sdg WHERE sdg.sale_id=sales.id) AS gravada")
        ->selectRaw("(SELECT SUM(sdn.nosujeta) FROM salesdetails AS sdn WHERE sdn.sale_id=sales.id) AS nosujeta")
        ->selectRaw("(SELECT
            COALESCE(SUM(sdi.detained13), 0) -
            (SELECT
                COALESCE(SUM(CASE
                    WHEN sd.pricesale > 0 AND (sd.exempt = 0 OR sd.exempt IS NULL) AND (sd.nosujeta = 0 OR sd.nosujeta IS NULL) THEN sd.feeiva
                    ELSE 0
                END), 0) +
                COALESCE(SUM(CASE
                    WHEN p.name IN ('Cargo administrativo', 'CXS') AND sd.pricesale > 0 AND (sd.exempt = 0 OR sd.exempt IS NULL) AND (sd.nosujeta = 0 OR sd.nosujeta IS NULL) THEN
                        (sd.detained13)
                    ELSE 0
                END), 0)
            FROM salesdetails AS sd
            LEFT JOIN products AS p ON p.id = sd.product_id
            WHERE sd.sale_id = sales.id)
        FROM salesdetails AS sdi WHERE sdi.sale_id=sales.id) AS iva")
        ->selectRaw("(SELECT
            COALESCE(SUM(CASE
                WHEN sd.pricesale > 0 AND (sd.exempt = 0 OR sd.exempt IS NULL) AND (sd.nosujeta = 0 OR sd.nosujeta IS NULL) THEN sd.fee
                ELSE 0
            END), 0) +
            COALESCE(SUM(CASE
                WHEN p.name IN ('Cargo administrativo', 'CXS') AND sd.pricesale > 0 AND (sd.exempt = 0 OR sd.exempt IS NULL) AND (sd.nosujeta = 0 OR sd.nosujeta IS NULL) THEN
                    (sd.pricesale + sd.nosujeta + sd.exempt)
                ELSE 0
            END), 0)
        FROM salesdetails AS sd
        LEFT JOIN products AS p ON p.id = sd.product_id
        WHERE sd.sale_id = sales.id) AS fee")
        ->selectRaw("(SELECT
            COALESCE(SUM(CASE
                WHEN sd.pricesale > 0 AND (sd.exempt = 0 OR sd.exempt IS NULL) AND (sd.nosujeta = 0 OR sd.nosujeta IS NULL) THEN sd.feeiva
                ELSE 0
            END), 0) +
            COALESCE(SUM(CASE
                WHEN p.name IN ('Cargo administrativo', 'CXS') AND sd.pricesale > 0 AND (sd.exempt = 0 OR sd.exempt IS NULL) AND (sd.nosujeta = 0 OR sd.nosujeta IS NULL) THEN
                    (sd.detained13)
                ELSE 0
            END), 0)
        FROM salesdetails AS sd
        LEFT JOIN products AS p ON p.id = sd.product_id
        WHERE sd.sale_id = sales.id) AS ivafee")
        ->selectRaw("(SELECT SUM(sdret.detained) FROM salesdetails AS sdret WHERE sdret.sale_id=sales.id) AS iva_retenido")
        ->selectRaw("(SELECT SUM(sdper.detainedP) FROM salesdetails AS sdper WHERE sdper.sale_id=sales.id) AS iva_percibido")
        ->where('sales.typedocument_id', "=", "6")
        ->whereRaw('(clients.tpersona = "N" OR clients.tpersona = "J")' )
        ->whereRaw('YEAR(sales.date)=?', $request['year'])
        ->whereRaw('MONTH(sales.date)=?', $request['period'])
        ->WhereRaw('DAY(sales.date) BETWEEN "01" AND "31"')
        ->where('sales.company_id', '=', $request['company'])
        ->where(function($query) {
            $query->whereNull('dte_emis.codEstado')
                  ->orWhere('dte_emis.codEstado', '=', '02');
        })
        ->orderBy('sales.id')
        ->get();

        $mesesDelAno = array(
            "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio",
            "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"
        );
        $mesesDelAnoMayuscula = array_map('strtoupper', $mesesDelAno);

        // Construir HTML para Excel
        $html = '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
        $html .= '<head>';
        $html .= '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
        $html .= '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet>';
        $html .= '<x:Name>Ventas Consumidor</x:Name>';
        $html .= '<x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet>';
        $html .= '</x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->';
        $html .= '</head>';
        $html .= '<body>';
        $html .= '<table border="1">';

        // Encabezado
        $html .= '<tr><th colspan="19" style="text-align:center; font-weight:bold;">LIBRO DE VENTAS CONSUMIDOR</th></tr>';
        $html .= '<tr><td colspan="19" style="text-align:center;">';
        $html .= '<b>Nombre del Contribuyente:</b> ' . $Company['name'] . ' ';
        $html .= '<b>N.R.C.:</b> ' . $Company['nrc'] . ' ';
        $html .= '<b>MES:</b> ' . $mesesDelAnoMayuscula[(int)$request['period']-1] . ' ';
        $html .= '<b>Año:</b> ' . $request['year'] . ' (Valores expresados en Dólares Estadounidenses)';
        $html .= '</td></tr>';

        // Encabezados de columna
        $html .= '<tr>';
        $html .= '<th>Corr.</th>';
        $html .= '<th>FECHA</th>';
        $html .= '<th>No. Doc.</th>';
        $html .= '<th>CLIENTE</th>';
        $html .= '<th>EXENTAS</th>';
        $html .= '<th>NO SUJETAS</th>';
        $html .= '<th>INTERNAS GRAVADAS</th>';
        $html .= '<th>DEBITO FISCAL</th>';
        $html .= '<th>FEE</th>';
        $html .= '<th>IVA FEE</th>';
        $html .= '<th>IVA RETENIDO</th>';
        $html .= '<th>IVA PERCIBIDO</th>';
        $html .= '<th>VENTA TOTAL</th>';
        $html .= '<th>NÚMERO CONTROL DTE</th>';
        $html .= '<th>CÓDIGO GENERACIÓN</th>';
        $html .= '<th>SELLO RECEPCIÓN</th>';
        $html .= '<th>Nº CONTROL ANULACIÓN</th>';
        $html .= '<th>CÓD. GEN. ANULACIÓN</th>';
        $html .= '<th>SELLO ANULACIÓN</th>';
        $html .= '</tr>';

        // Datos
        $tot_exentas = 0;
        $tot_int_grav = 0;
        $tot_debfiscal = 0;
        $tot_nosujetas = 0;
        $tot_final = 0;
        $tot_fee = 0;
        $tot_ivafee = 0;
        $tot_iva_retenido = 0;
        $tot_iva_percibido = 0;
        $i = 1;

        foreach ($sales as $sale) {
            $html .= '<tr>';
            $html .= '<td>' . $i . '</td>';
            $html .= '<td>' . $sale['dateF'] . '</td>';
            $html .= '<td>' . ($sale['correlativo'] ?? '-') . '</td>';

            // Nombre del cliente completo
            if($sale['typesale']=='0'){
                $html .= '<td style="color: red; font-weight: bold;">ANULADO</td>';
            } else {
                $html .= '<td>' . strtoupper($sale['nombre_completo'] ?? '') . '</td>';
            }

            if($sale['typesale']=='0'){
                $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">0.00</td>';
                $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">0.00</td>';
                $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">0.00</td>';
                $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">0.00</td>';
                $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">0.00</td>';
                $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">0.00</td>';
                $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">0.00</td>';
                $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">0.00</td>';
                $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">0.00</td>';
            } else {
                $fee = $sale['fee'] ?? 0;
                $ivafee = $sale['ivafee'] ?? 0;
                $iva_retenido = $sale['iva_retenido'] ?? 0;
                $iva_percibido = $sale['iva_percibido'] ?? 0;

                $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($sale['exenta'], 2, '.', '') . '</td>';
                $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($sale['nosujeta'], 2, '.', '') . '</td>';
                $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($sale['gravada'], 2, '.', '') . '</td>';
                $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($sale['iva'], 2, '.', '') . '</td>';
                $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($fee, 2, '.', '') . '</td>';
                $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($ivafee, 2, '.', '') . '</td>';
                $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($iva_retenido, 2, '.', '') . '</td>';
                $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($iva_percibido, 2, '.', '') . '</td>';
                $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($sale['totalamount'], 2, '.', '') . '</td>';

                $tot_exentas += $sale['exenta'];
                $tot_nosujetas += $sale['nosujeta'];
                $tot_int_grav += $sale['gravada'];
                $tot_debfiscal += $sale['iva'];
                $tot_fee += $fee;
                $tot_ivafee += $ivafee;
                $tot_iva_retenido += $iva_retenido;
                $tot_iva_percibido += $iva_percibido;
                $tot_final += $sale['totalamount'];
            }

            $html .= '<td>' . ($sale['numeroControl'] ?? '-') . '</td>';
            $html .= '<td>' . ($sale['codigoGeneracion'] ?? '-') . '</td>';
            $html .= '<td>' . ($sale['selloRecibido'] ?? '-') . '</td>';
            $html .= '<td>' . (($sale['typesale'] ?? '') == '0' ? ($sale['numeroControl_anulacion'] ?? '-') : '-') . '</td>';
            $html .= '<td>' . (($sale['typesale'] ?? '') == '0' ? ($sale['codigoGeneracion_anulacion'] ?? '-') : '-') . '</td>';
            $html .= '<td>' . (($sale['typesale'] ?? '') == '0' ? ($sale['selloRecibido_anulacion'] ?? '-') : '-') . '</td>';
            $html .= '</tr>';
            $i++;
        }

        // Totales
        $html .= '<tr style="font-weight:bold;">';
        $html .= '<td colspan="4" style="text-align:right;">TOTALES DEL MES</td>';
        $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($tot_exentas, 2, '.', '') . '</td>';
        $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($tot_nosujetas, 2, '.', '') . '</td>';
        $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($tot_int_grav, 2, '.', '') . '</td>';
        $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($tot_debfiscal, 2, '.', '') . '</td>';
        $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($tot_fee, 2, '.', '') . '</td>';
        $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($tot_ivafee, 2, '.', '') . '</td>';
        $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($tot_iva_retenido, 2, '.', '') . '</td>';
        $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($tot_iva_percibido, 2, '.', '') . '</td>';
        $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($tot_final, 2, '.', '') . '</td>';
        $html .= '<td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td>';
        $html .= '</tr>';

        // Liquidación del débito fiscal
        $html .= '<tr><td colspan="19" style="text-align:center; font-weight:bold;"><br>LIQUIDACION DEL DEBITO FISCAL EN VENTAS DIRECTAS</td></tr>';

        $html .= '<tr>';
        $html .= '<td colspan="6" style="text-align:right; font-weight:bold;">GRAVADAS, NO SUJETAS, EXENTAS, SIN IVA</td>';
        $html .= '<td colspan="2" style="text-align:right; mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($tot_int_grav + $tot_exentas + $tot_nosujetas, 2, '.', '') . '</td>';
        $html .= '<td colspan="11"></td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<td colspan="3">VENTAS EXENTAS</td>';
        $html .= '<td style="text-align:right; mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($tot_exentas, 2, '.', '') . '</td>';
        $html .= '<td style="text-align:right;">13 %</td>';
        $html .= '<td style="text-align:right; mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($tot_debfiscal, 2, '.', '') . '</td>';
        $html .= '<td colspan="12"></td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<td colspan="3">VENTAS NO SUJETAS</td>';
        $html .= '<td style="text-align:right; mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($tot_nosujetas, 2, '.', '') . '</td>';
        $html .= '<td style="text-align:right;">0 %</td>';
        $html .= '<td style="text-align:right; mso-number-format:\'\#\,\#\#0\.00\';">0.00</td>';
        $html .= '<td colspan="12"></td>';
        $html .= '</tr>';

        $html .= '<tr style="font-weight:bold;">';
        $html .= '<td colspan="3">VENTA LOCALES GRAVADAS</td>';
        $html .= '<td style="text-align:right; mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($tot_int_grav, 2, '.', '') . '</td>';
        $html .= '<td style="text-align:right;">TOTAL</td>';
        $html .= '<td style="text-align:right; mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($tot_int_grav + $tot_debfiscal, 2, '.', '') . '</td>';
        $html .= '<td colspan="12"></td>';
        $html .= '</tr>';

        $html .= '</table>';
        $html .= '</body></html>';

        // Enviar headers para descarga
        $filename = 'Ventas_Consumidor_' . $mesesDelAno[(int)$request['period']-1] . '_' . $request['year'] . '.xls';

        return response($html)
            ->header('Content-Type', 'application/vnd.ms-excel; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * Exportar reporte de consumidor a PDF (libro tipo tabla)
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function consumidorPdf(Request $request){
        $Company = Company::find($request['company']);
        $sales = Sale::leftjoin('clients', 'sales.client_id', '=', 'clients.id')
        ->leftJoin('dte', 'dte.sale_id', '=', 'sales.id')
        ->select('*','sales.id AS correlativo',
        'clients.ncr AS ncrC',
        'dte.id_doc AS numeroControl',
        'dte.codigoGeneracion AS codigoGeneracion',
        'dte.selloRecibido AS selloRecibido')
        ->selectRaw("CASE
            WHEN dte.fhRecibido IS NOT NULL
            THEN DATE_FORMAT(dte.fhRecibido, '%d/%m/%Y')
            WHEN dte.json IS NOT NULL AND JSON_EXTRACT(dte.json, '$.identificacion.fecEmi') IS NOT NULL
            THEN DATE_FORMAT(STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(dte.json, '$.identificacion.fecEmi')), '%Y-%m-%d'), '%d/%m/%Y')
            ELSE DATE_FORMAT(sales.date, '%d/%m/%Y')
        END AS dateF ")
        ->selectRaw('(SELECT SUM(sde.exempt) FROM salesdetails AS sde WHERE sde.sale_id=sales.id) AS exenta')
        ->selectRaw('(SELECT SUM(sdg.pricesale) FROM salesdetails AS sdg WHERE sdg.sale_id=sales.id) AS gravada')
        ->selectRaw('(SELECT SUM(sdn.nosujeta) FROM salesdetails AS sdn WHERE sdn.sale_id=sales.id) AS nosujeta')
        ->selectRaw('(SELECT SUM(sdi.detained13) FROM salesdetails AS sdi WHERE sdi.sale_id=sales.id) AS iva')
        // Marcar si la venta es propia o a terceros
        ->selectRaw("CASE
            WHEN sales.provider_id IS NOT NULL OR EXISTS (
                SELECT 1 FROM salesdetails sdter
                WHERE sdter.sale_id = sales.id
                  AND sdter.line_provider_id IS NOT NULL
            ) THEN 'TERCEROS'
            ELSE 'PROPIA'
        END AS tipo_venta")
        // Excluir ventas padre del libro; solo mostrar ventas hijas o ventas sin relación
        ->where(function($q) {
            $q->whereNull('sales.is_parent')
              ->orWhere('sales.is_parent', 0);
        })
        ->where('sales.typedocument_id', '=', '6')
        ->whereRaw('(clients.tpersona = "N" OR clients.tpersona = "J")' )
        ->whereRaw('YEAR(sales.date)=?', $request['year'])
        ->whereRaw('MONTH(sales.date)=?', $request['period'])
        ->WhereRaw('DAY(sales.date) BETWEEN "01" AND "31"')
        ->where('sales.company_id', '=', $request['company'])
        ->where(function($query) {
            $query->whereNull('dte.codEstado')
                  ->orWhere('dte.codEstado', '=', '02');
        })
        ->orderBy('sales.id')
        ->get();

        $mesesDelAno = array(
            'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
            'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
        );

        $pdf = app('dompdf.wrapper');
        $pdf->set_option('isHtml5ParserEnabled', true);
        $pdf->set_option('isRemoteEnabled', true);
        $pdf->setPaper('letter', 'portrait');
        $pdf->getDomPDF()->set_option('enable_php', true);
        $pdf->loadView('pdf.documentos.ventas_consumidor_tabla_pdf', [
            'heading' => $Company,
            'yearB' => $request['year'],
            'period' => $request['period'],
            'sales' => $sales,
            'mesNombre' => $mesesDelAno[(int)$request['period']-1]
        ]);

        $filename = 'Ventas_Consumidor_' . $mesesDelAno[(int)$request['period']-1] . '_' . $request['year'] . '.pdf';
        return $pdf->download($filename);
    }

    /**
     * Concatenar PDFs de cada documento de venta del reporte de consumidor
     * y devolver un único PDF combinado
     */
    public function consumidorMergePdf(Request $request){
        $Company = Company::find($request['company']);
        $sales = Sale::leftjoin('clients', 'sales.client_id', '=', 'clients.id')
        ->leftJoin('dte', 'dte.sale_id', '=', 'sales.id')
        ->select('*','sales.id AS correlativo')
        // Excluir ventas padre del merge; solo ventas hijas o sin relación
        ->where(function($q) {
            $q->whereNull('sales.is_parent')
              ->orWhere('sales.is_parent', 0);
        })
        ->where('sales.typedocument_id', '=', '6')
        ->whereRaw('(clients.tpersona = "N" OR clients.tpersona = "J")' )
        ->whereRaw('YEAR(sales.date)=?', $request['year'])
        ->whereRaw('MONTH(sales.date)=?', $request['period'])
        ->WhereRaw('DAY(sales.date) BETWEEN "01" AND "31"')
        ->where('sales.company_id', '=', $request['company'])
        ->where(function($query) {
            $query->whereNull('dte.codEstado')
                  ->orWhere('dte.codEstado', '=', '02');
        })
        ->orderBy('sales.id')
        ->get();

        // Verificar librería de merge
        $mergerClass = '\\iio\\libmergepdf\\Merger';
        if (!class_exists($mergerClass)) {
            return response('Falta la dependencia iio/libmergepdf. Instala con: composer require iio/libmergepdf', 500)
                ->header('Content-Type', 'text/plain');
        }

        $merger = new $mergerClass();

        // Generar cada PDF y añadirlo al merger
        $saleController = app(SaleController::class);
        foreach ($sales as $sale) {
            // Omitir documentos anulados
            if (isset($sale['typesale']) && $sale['typesale'] === '0') {
                continue;
            }
            $saleId = $sale['correlativo'];

            try {
                // Usar misma lógica que SaleController::download
                $dte = Dte::where('sale_id', $saleId)->first();
                if ($dte && $dte->json) {
                    // PDF oficial DTE (incluye diseños actualizados, terceros, etc.)
                    $pdf = $saleController->genera_pdf($saleId);
                } else {
                    // PDF local
                    $pdf = $saleController->genera_pdflocal($saleId);
                }

                $merger->addRaw($pdf->output());
            } catch (\Throwable $e) {
                // Si un documento falla, se omite y continúa con los demás
                continue;
            }
        }

        $mesesDelAno = array(
            'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
            'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
        );
        $filename = 'Ventas_Consumidor_Documentos_' . $mesesDelAno[(int)$request['period']-1] . '_' . $request['year'] . '.pdf';

        try {
            $combined = $merger->merge();
        } catch (\Throwable $e) {
            return response('Error al unir PDFs: ' . $e->getMessage(), 500)
                ->header('Content-Type', 'text/plain');
        }

        return response()->streamDownload(function () use ($combined) {
            echo $combined;
        }, $filename, [
            'Content-Type' => 'application/pdf'
        ]);
    }

    /**
     * Concatenar PDFs de cada documento de venta del reporte de contribuyentes (typedocument 3)
     * y devolver un único PDF combinado
     */
    public function contribuyentesMergePdf(Request $request){
        $Company = Company::find($request['company']);
        $sales = Sale::leftjoin('clients', 'sales.client_id', '=', 'clients.id')
        ->leftJoin('dte', 'dte.sale_id', '=', 'sales.id')
        ->select('*','sales.id AS correlativo')
        // Excluir ventas padre del merge; solo ventas hijas o sin relación
        ->where(function($q) {
            $q->whereNull('sales.is_parent')
              ->orWhere('sales.is_parent', 0);
        })
        ->where('sales.typedocument_id', '=', '3')
        ->whereRaw('YEAR(sales.date)=?', $request['year'])
        ->whereRaw('MONTH(sales.date)=?', $request['period'])
        ->WhereRaw('DAY(sales.date) BETWEEN "01" AND "31"')
        ->where('sales.company_id', '=', $request['company'])
        // Solo incluir DTE enviados exitosamente
        ->where('dte.codEstado', '=', '02')
        ->orderBy('sales.id')
        ->get();

        $mergerClass = '\\iio\\libmergepdf\\Merger';
        if (!class_exists($mergerClass)) {
            return response('Falta la dependencia iio/libmergepdf. Instala con: composer require iio/libmergepdf', 500)
                ->header('Content-Type', 'text/plain');
        }
        $merger = new $mergerClass();

        $saleController = app(SaleController::class);
        foreach ($sales as $sale) {
            if (isset($sale['typesale']) && $sale['typesale'] === '0') {
                continue;
            }
            $saleId = $sale['correlativo'];
            try {
                // Usar misma lógica que SaleController::download
                $dte = Dte::where('sale_id', $saleId)->first();
                if ($dte && $dte->json) {
                    $pdf = $saleController->genera_pdf($saleId);
                } else {
                    $pdf = $saleController->genera_pdflocal($saleId);
                }

                $merger->addRaw($pdf->output());
            } catch (\Throwable $e) {
                continue;
            }
        }

        $mesesDelAno = array(
            'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
            'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
        );
        $filename = 'Ventas_Contribuyentes_Documentos_' . $mesesDelAno[(int)$request['period']-1] . '_' . $request['year'] . '.pdf';

        try {
            $combined = $merger->merge();
        } catch (\Throwable $e) {
            return response('Error al unir PDFs: ' . $e->getMessage(), 500)
                ->header('Content-Type', 'text/plain');
        }

        return response()->streamDownload(function () use ($combined) {
            echo $combined;
        }, $filename, [
            'Content-Type' => 'application/pdf'
        ]);
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
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Report  $report
     * @return \Illuminate\Http\Response
     */
    public function show(Report $report)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Report  $report
     * @return \Illuminate\Http\Response
     */
    public function edit(Report $report)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Report  $report
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Report $report)
    {
        //
    }

    /**
     * Mostrar vista de reporte de liquidaciones
     *
     * @return \Illuminate\Http\Response
     */
    public function liquidacion(){
        return view('reports.liquidacion');
    }

    /**
     * Procesar búsqueda de liquidaciones
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function liquidacionsearch(Request $request){
        $Company = Company::find($request['company']);
        // Subconsulta: un solo DTE de emisión por venta (evita duplicados cuando existe DTE de anulación)
        $dteEmisionSub = DB::table('dte')
            ->select('dte.sale_id', 'dte.id_doc', 'dte.codigoGeneracion', 'dte.selloRecibido', 'dte.fhRecibido', 'dte.json', 'dte.codEstado')
            ->whereIn('dte.codTransaction', ['01', '05', '06'])
            ->whereRaw('dte.id = (SELECT MAX(d2.id) FROM dte d2 WHERE d2.sale_id = dte.sale_id AND d2.codTransaction IN ("01","05","06"))');
        $sales = Sale::leftjoin('clients', 'sales.client_id', '=', 'clients.id')
        ->leftJoinSub($dteEmisionSub, 'dte_emis', 'dte_emis.sale_id', '=', 'sales.id')
        ->select('*','sales.id AS correlativo',
        'clients.ncr AS ncrC',
        'dte_emis.id_doc AS numeroControl',
        'dte_emis.codigoGeneracion AS codigoGeneracion',
        'dte_emis.selloRecibido AS selloRecibido')
        ->selectRaw("(SELECT dte_anul.id_doc FROM dte dte_anul WHERE dte_anul.sale_id = sales.id AND dte_anul.codTransaction = '02' LIMIT 1) AS numeroControl_anulacion")
        ->selectRaw("(SELECT dte_anul.codigoGeneracion FROM dte dte_anul WHERE dte_anul.sale_id = sales.id AND dte_anul.codTransaction = '02' LIMIT 1) AS codigoGeneracion_anulacion")
        ->selectRaw("(SELECT dte_anul.selloRecibido FROM dte dte_anul WHERE dte_anul.sale_id = sales.id AND dte_anul.codTransaction = '02' LIMIT 1) AS selloRecibido_anulacion")
        ->selectRaw("CASE
            WHEN dte_emis.fhRecibido IS NOT NULL
            THEN DATE_FORMAT(dte_emis.fhRecibido, '%d/%m/%Y')
            WHEN dte_emis.json IS NOT NULL AND JSON_EXTRACT(dte_emis.json, '$.identificacion.fecEmi') IS NOT NULL
            THEN DATE_FORMAT(STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(dte_emis.json, '$.identificacion.fecEmi')), '%Y-%m-%d'), '%d/%m/%Y')
            ELSE DATE_FORMAT(sales.date, '%d/%m/%Y')
        END AS dateF ")
        ->selectRaw("CASE
            WHEN clients.tpersona = 'J' THEN clients.comercial_name
            WHEN clients.tpersona = 'N' THEN CONCAT_WS(' ', clients.firstname, clients.secondname, clients.firstlastname, clients.secondlastname)
            ELSE ''
        END AS nombre_completo")
        ->selectRaw("(SELECT SUM(sde.exempt) FROM salesdetails AS sde WHERE sde.sale_id=sales.id) AS exenta")
        ->selectRaw("(SELECT
            COALESCE(SUM(sdg.pricesale), 0) -
            (SELECT
                COALESCE(SUM(CASE
                    WHEN sd.pricesale > 0 AND (sd.exempt = 0 OR sd.exempt IS NULL) AND (sd.nosujeta = 0 OR sd.nosujeta IS NULL) THEN sd.fee
                    ELSE 0
                END), 0) +
                COALESCE(SUM(CASE
                    WHEN p.name IN ('Cargo administrativo', 'CXS') AND sd.pricesale > 0 AND (sd.exempt = 0 OR sd.exempt IS NULL) AND (sd.nosujeta = 0 OR sd.nosujeta IS NULL) THEN
                        (sd.pricesale + sd.nosujeta + sd.exempt)
                    ELSE 0
                END), 0)
            FROM salesdetails AS sd
            LEFT JOIN products AS p ON p.id = sd.product_id
            WHERE sd.sale_id = sales.id)
        FROM salesdetails AS sdg WHERE sdg.sale_id=sales.id) AS gravada")
        ->selectRaw("(SELECT SUM(sdn.nosujeta) FROM salesdetails AS sdn WHERE sdn.sale_id=sales.id) AS nosujeta")
        ->selectRaw("(SELECT
            COALESCE(SUM(sdi.detained13), 0) -
            (SELECT
                COALESCE(SUM(CASE
                    WHEN sd.pricesale > 0 AND (sd.exempt = 0 OR sd.exempt IS NULL) AND (sd.nosujeta = 0 OR sd.nosujeta IS NULL) THEN sd.feeiva
                    ELSE 0
                END), 0) +
                COALESCE(SUM(CASE
                    WHEN p.name IN ('Cargo administrativo', 'CXS') AND sd.pricesale > 0 AND (sd.exempt = 0 OR sd.exempt IS NULL) AND (sd.nosujeta = 0 OR sd.nosujeta IS NULL) THEN
                        (sd.detained13)
                    ELSE 0
                END), 0)
            FROM salesdetails AS sd
            LEFT JOIN products AS p ON p.id = sd.product_id
            WHERE sd.sale_id = sales.id)
        FROM salesdetails AS sdi WHERE sdi.sale_id=sales.id) AS iva")
        ->selectRaw("(SELECT SUM(sdret.detained) FROM salesdetails AS sdret WHERE sdret.sale_id=sales.id) AS iva_retenido")
        ->selectRaw("(SELECT SUM(sdper.detainedP) FROM salesdetails AS sdper WHERE sdper.sale_id=sales.id) AS iva_percibido")
        // Exportación: suma de items donde clq_tipo_documento='11' (FEX - Factura Exportación)
        ->selectRaw("COALESCE((SELECT SUM(COALESCE(sdex.pricesale,0) + COALESCE(sdex.nosujeta,0) + COALESCE(sdex.exempt,0))
            FROM salesdetails AS sdex WHERE sdex.sale_id=sales.id AND sdex.clq_tipo_documento='11'), 0) AS exportacion")
        // Marcar si la venta es propia o a terceros
        ->selectRaw("CASE
            WHEN sales.provider_id IS NOT NULL OR EXISTS (
                SELECT 1 FROM salesdetails sdter
                WHERE sdter.sale_id = sales.id
                  AND sdter.line_provider_id IS NOT NULL
            ) THEN 'TERCEROS'
            ELSE 'PROPIA'
        END AS tipo_venta")
        ->where('sales.typedocument_id', "=", "2")
        ->whereRaw('YEAR(sales.date)=?', $request['year'])
        ->whereRaw('MONTH(sales.date)=?', $request['period'])
        ->WhereRaw('DAY(sales.date) BETWEEN "01" AND "31"')
        ->where('sales.company_id', '=', $request['company'])
        // Solo incluir ventas con DTE de emisión enviado o sin DTE (no filtrar por dte para no duplicar)
        ->where(function($query) {
            $query->whereNull('dte_emis.codEstado')
                  ->orWhere('dte_emis.codEstado', '=', '02');
        })
        ->orderBy('sales.id')
        ->get();
        return view('reports.liquidacion', array(
            "heading" => $Company,
            "yearB" => $request['year'],
            "period" => $request['period'],
            "sales" => $sales
        ));
    }

    /**
     * Exportar reporte de liquidaciones a Excel
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function liquidacionExcel(Request $request){
        $Company = Company::find($request['company']);
        $dteEmisionSubExcel = DB::table('dte')
            ->select('dte.sale_id', 'dte.id_doc', 'dte.codigoGeneracion', 'dte.selloRecibido', 'dte.fhRecibido', 'dte.json', 'dte.codEstado')
            ->whereIn('dte.codTransaction', ['01', '05', '06'])
            ->whereRaw('dte.id = (SELECT MAX(d2.id) FROM dte d2 WHERE d2.sale_id = dte.sale_id AND d2.codTransaction IN ("01","05","06"))');
        $sales = Sale::leftjoin('clients', 'sales.client_id', '=', 'clients.id')
        ->leftJoinSub($dteEmisionSubExcel, 'dte_emis', 'dte_emis.sale_id', '=', 'sales.id')
        ->select('*','sales.id AS correlativo',
        'clients.ncr AS ncrC',
        'dte_emis.id_doc AS numeroControl',
        'dte_emis.codigoGeneracion AS codigoGeneracion',
        'dte_emis.selloRecibido AS selloRecibido')
        ->selectRaw("(SELECT dte_anul.id_doc FROM dte dte_anul WHERE dte_anul.sale_id = sales.id AND dte_anul.codTransaction = '02' LIMIT 1) AS numeroControl_anulacion")
        ->selectRaw("(SELECT dte_anul.codigoGeneracion FROM dte dte_anul WHERE dte_anul.sale_id = sales.id AND dte_anul.codTransaction = '02' LIMIT 1) AS codigoGeneracion_anulacion")
        ->selectRaw("(SELECT dte_anul.selloRecibido FROM dte dte_anul WHERE dte_anul.sale_id = sales.id AND dte_anul.codTransaction = '02' LIMIT 1) AS selloRecibido_anulacion")
        ->selectRaw("CASE
            WHEN dte_emis.fhRecibido IS NOT NULL
            THEN DATE_FORMAT(dte_emis.fhRecibido, '%d/%m/%Y')
            WHEN dte_emis.json IS NOT NULL AND JSON_EXTRACT(dte_emis.json, '$.identificacion.fecEmi') IS NOT NULL
            THEN DATE_FORMAT(STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(dte_emis.json, '$.identificacion.fecEmi')), '%Y-%m-%d'), '%d/%m/%Y')
            ELSE DATE_FORMAT(sales.date, '%d/%m/%Y')
        END AS dateF ")
        ->selectRaw("CASE
            WHEN clients.tpersona = 'J' THEN clients.comercial_name
            WHEN clients.tpersona = 'N' THEN CONCAT_WS(' ', clients.firstname, clients.secondname, clients.firstlastname, clients.secondlastname)
            ELSE ''
        END AS nombre_completo")
        ->selectRaw("(SELECT SUM(sde.exempt) FROM salesdetails AS sde WHERE sde.sale_id=sales.id) AS exenta")
        ->selectRaw("(SELECT
            COALESCE(SUM(sdg.pricesale), 0) -
            (SELECT
                COALESCE(SUM(CASE
                    WHEN sd.pricesale > 0 AND (sd.exempt = 0 OR sd.exempt IS NULL) AND (sd.nosujeta = 0 OR sd.nosujeta IS NULL) THEN sd.fee
                    ELSE 0
                END), 0) +
                COALESCE(SUM(CASE
                    WHEN p.name IN ('Cargo administrativo', 'CXS') AND sd.pricesale > 0 AND (sd.exempt = 0 OR sd.exempt IS NULL) AND (sd.nosujeta = 0 OR sd.nosujeta IS NULL) THEN
                        (sd.pricesale + sd.nosujeta + sd.exempt)
                    ELSE 0
                END), 0)
            FROM salesdetails AS sd
            LEFT JOIN products AS p ON p.id = sd.product_id
            WHERE sd.sale_id = sales.id)
        FROM salesdetails AS sdg WHERE sdg.sale_id=sales.id) AS gravada")
        ->selectRaw("(SELECT SUM(sdn.nosujeta) FROM salesdetails AS sdn WHERE sdn.sale_id=sales.id) AS nosujeta")
        ->selectRaw("(SELECT
            COALESCE(SUM(sdi.detained13), 0) -
            (SELECT
                COALESCE(SUM(CASE
                    WHEN sd.pricesale > 0 AND (sd.exempt = 0 OR sd.exempt IS NULL) AND (sd.nosujeta = 0 OR sd.nosujeta IS NULL) THEN sd.feeiva
                    ELSE 0
                END), 0) +
                COALESCE(SUM(CASE
                    WHEN p.name IN ('Cargo administrativo', 'CXS') AND sd.pricesale > 0 AND (sd.exempt = 0 OR sd.exempt IS NULL) AND (sd.nosujeta = 0 OR sd.nosujeta IS NULL) THEN
                        (sd.detained13)
                    ELSE 0
                END), 0)
            FROM salesdetails AS sd
            LEFT JOIN products AS p ON p.id = sd.product_id
            WHERE sd.sale_id = sales.id)
        FROM salesdetails AS sdi WHERE sdi.sale_id=sales.id) AS iva")
        ->selectRaw("(SELECT SUM(sdret.detained) FROM salesdetails AS sdret WHERE sdret.sale_id=sales.id) AS iva_retenido")
        ->selectRaw("(SELECT SUM(sdper.detainedP) FROM salesdetails AS sdper WHERE sdper.sale_id=sales.id) AS iva_percibido")
        // Exportación: suma de items donde clq_tipo_documento='11' (FEX - Factura Exportación)
        ->selectRaw("COALESCE((SELECT SUM(COALESCE(sdex.pricesale,0) + COALESCE(sdex.nosujeta,0) + COALESCE(sdex.exempt,0))
            FROM salesdetails AS sdex WHERE sdex.sale_id=sales.id AND sdex.clq_tipo_documento='11'), 0) AS exportacion")
        // Marcar si la venta es propia o a terceros
        ->selectRaw("CASE
            WHEN sales.provider_id IS NOT NULL OR EXISTS (
                SELECT 1 FROM salesdetails sdter
                WHERE sdter.sale_id = sales.id
                  AND sdter.line_provider_id IS NOT NULL
            ) THEN 'TERCEROS'
            ELSE 'PROPIA'
        END AS tipo_venta")
        ->where('sales.typedocument_id', "=", "2")
        ->whereRaw('YEAR(sales.date)=?', $request['year'])
        ->whereRaw('MONTH(sales.date)=?', $request['period'])
        ->WhereRaw('DAY(sales.date) BETWEEN "01" AND "31"')
        ->where('sales.company_id', '=', $request['company'])
        ->where(function($query) {
            $query->whereNull('dte_emis.codEstado')
                  ->orWhere('dte_emis.codEstado', '=', '02');
        })
        ->orderBy('sales.id')
        ->get();

        $mesesDelAno = array(
            "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio",
            "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"
        );
        $mesesDelAnoMayuscula = array_map('strtoupper', $mesesDelAno);

        // Construir HTML para Excel
        $html = '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
        $html .= '<head>';
        $html .= '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
        $html .= '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet>';
        $html .= '<x:Name>Liquidaciones</x:Name>';
        $html .= '<x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet>';
        $html .= '</x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->';
        $html .= '</head>';
        $html .= '<body>';
        $html .= '<table border="1">';

        // Encabezado
        $html .= '<tr><th colspan="21" style="text-align:center; font-weight:bold;">LIBRO DE COMPROBANTES DE LIQUIDACIÓN</th></tr>';
        $html .= '<tr><td colspan="21" style="text-align:center;">';
        $html .= '<b>Nombre del Contribuyente:</b> ' . $Company['name'] . ' ';
        $html .= '<b>N.R.C.:</b> ' . $Company['nrc'] . ' ';
        $html .= '<b>NIT:</b> ' . $Company['nit'] . ' ';
        $html .= '<b>MES:</b> ' . $mesesDelAnoMayuscula[(int)$request['period']-1] . ' ';
        $html .= '<b>AÑO:</b> ' . $request['year'];
        $html .= '</td></tr>';

        // Encabezados de columna
        $html .= '<tr>';
        $html .= '<th>NUM. CORR.</th>';
        $html .= '<th>Fecha Emisión</th>';
        $html .= '<th>No. Doc.</th>';
        $html .= '<th>Nombre del Cliente</th>';
        $html .= '<th>NRC</th>';
        $html .= '<th>TIPO VENTA</th>';
        $html .= '<th>Exentas</th>';
        $html .= '<th>No Sujetas</th>';
        $html .= '<th>Internas Gravadas</th>';
        $html .= '<th>Exportación</th>';
        $html .= '<th>Debito Fiscal</th>';
        $html .= '<th>IVA Retenido</th>';
        $html .= '<th>IVA Percibido</th>';
        $html .= '<th>TOTAL</th>';
        $html .= '<th>NÚMERO CONTROL DTE</th>';
        $html .= '<th>CÓDIGO GENERACIÓN</th>';
        $html .= '<th>SELLO RECEPCIÓN</th>';
        $html .= '<th>Nº CONTROL ANULACIÓN</th>';
        $html .= '<th>CÓD. GEN. ANULACIÓN</th>';
        $html .= '<th>SELLO ANULACIÓN</th>';
        $html .= '</tr>';

        // Datos
        $total_ex = 0;
        $total_gv = 0;
        $total_iva = 0;
        $total_ns = 0;
        $tot_exportacion = 0;
        $tot_final = 0;
        $tot_iva_retenido = 0;
        $tot_iva_percibido = 0;
        $i = 1;

        foreach ($sales as $sale) {
            $html .= '<tr>';
            $html .= '<td>' . $i . '</td>';
            $html .= '<td>' . $sale['dateF'] . '</td>';
            $html .= '<td>' . ($sale['correlativo'] ?? '-') . '</td>';

            if($sale['typesale']=='0'){
                $html .= '<td style="color: red; font-weight: bold;">ANULADO</td>';
            } else {
                $html .= '<td>' . strtoupper($sale['nombre_completo'] ?? '') . '</td>';
            }

            $html .= '<td>' . preg_replace('/[^0-9]/', '', $sale['ncrC'] ?? '') . '</td>';

            if($sale['typesale']=='0'){
                $html .= '<td>-</td>';
                $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">0.00</td>';
                $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">0.00</td>';
                $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">0.00</td>';
                $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">0.00</td>';
                $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">0.00</td>';
                $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">0.00</td>';
                $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">0.00</td>';
                $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">0.00</td>';
                $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">0.00</td>';
            } else {
                $html .= '<td>' . ($sale['tipo_venta'] ?? 'PROPIA') . '</td>';
                $iva_retenido = $sale['iva_retenido'] ?? 0;
                $iva_percibido = $sale['iva_percibido'] ?? 0;

                $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($sale['exenta'], 2, '.', '') . '</td>';
                $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($sale['nosujeta'], 2, '.', '') . '</td>';
                $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($sale['gravada'], 2, '.', '') . '</td>';
                $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($sale['exportacion'] ?? 0, 2, '.', '') . '</td>';
                $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($sale['iva'], 2, '.', '') . '</td>';
                $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($iva_retenido, 2, '.', '') . '</td>';
                $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($iva_percibido, 2, '.', '') . '</td>';
                $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($sale['totalamount'], 2, '.', '') . '</td>';

                $total_ex += $sale['exenta'];
                $total_ns += $sale['nosujeta'];
                $total_gv += $sale['gravada'];
                $tot_exportacion += $sale['exportacion'] ?? 0;
                $total_iva += $sale['iva'];
                $tot_iva_retenido += $iva_retenido;
                $tot_iva_percibido += $iva_percibido;
                $tot_final += $sale['totalamount'];
            }

            $html .= '<td>' . ($sale['numeroControl'] ?? '-') . '</td>';
            $html .= '<td>' . ($sale['codigoGeneracion'] ?? '-') . '</td>';
            $html .= '<td>' . ($sale['selloRecibido'] ?? '-') . '</td>';
            $html .= '<td>' . (($sale['typesale'] ?? '') == '0' ? ($sale['numeroControl_anulacion'] ?? '-') : '-') . '</td>';
            $html .= '<td>' . (($sale['typesale'] ?? '') == '0' ? ($sale['codigoGeneracion_anulacion'] ?? '-') : '-') . '</td>';
            $html .= '<td>' . (($sale['typesale'] ?? '') == '0' ? ($sale['selloRecibido_anulacion'] ?? '-') : '-') . '</td>';
            $html .= '</tr>';
            $i++;
        }

        // Totales
        $html .= '<tr style="font-weight:bold;">';
        $html .= '<td colspan="6" style="text-align:right;">TOTALES DEL MES</td>';
        $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($total_ex, 2, '.', '') . '</td>';
        $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($total_ns, 2, '.', '') . '</td>';
        $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($total_gv, 2, '.', '') . '</td>';
        $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($tot_exportacion, 2, '.', '') . '</td>';
        $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($total_iva, 2, '.', '') . '</td>';
        $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($tot_iva_retenido, 2, '.', '') . '</td>';
        $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($tot_iva_percibido, 2, '.', '') . '</td>';
        $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($tot_final, 2, '.', '') . '</td>';
        $html .= '<td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td>';
        $html .= '</tr>';

        $html .= '</table>';
        $html .= '</body></html>';

        // Enviar headers para descarga
        $filename = 'Liquidaciones_' . $mesesDelAno[(int)$request['period']-1] . '_' . $request['year'] . '.xls';

        return response($html)
            ->header('Content-Type', 'application/vnd.ms-excel; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * Concatenar PDFs de cada comprobante de liquidación del reporte
     * y devolver un único PDF combinado
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function liquidacionMergePdf(Request $request){
        $Company = Company::find($request['company']);
        $dteEmisionSubMerge = DB::table('dte')
            ->select('dte.sale_id', 'dte.codEstado')
            ->whereIn('dte.codTransaction', ['01', '05', '06'])
            ->whereRaw('dte.id = (SELECT MAX(d2.id) FROM dte d2 WHERE d2.sale_id = dte.sale_id AND d2.codTransaction IN ("01","05","06"))');
        $sales = Sale::leftjoin('clients', 'sales.client_id', '=', 'clients.id')
        ->leftJoinSub($dteEmisionSubMerge, 'dte_emis', 'dte_emis.sale_id', '=', 'sales.id')
        ->select('*','sales.id AS correlativo')
        ->where('sales.typedocument_id', '=', '2')
        ->whereRaw('YEAR(sales.date)=?', $request['year'])
        ->whereRaw('MONTH(sales.date)=?', $request['period'])
        ->WhereRaw('DAY(sales.date) BETWEEN "01" AND "31"')
        ->where('sales.company_id', '=', $request['company'])
        ->where(function($query) {
            $query->whereNull('dte_emis.codEstado')
                  ->orWhere('dte_emis.codEstado', '=', '02');
        })
        ->orderBy('sales.id')
        ->get();

        $mergerClass = '\\iio\\libmergepdf\\Merger';
        if (!class_exists($mergerClass)) {
            return response('Falta la dependencia iio/libmergepdf. Instala con: composer require iio/libmergepdf', 500)
                ->header('Content-Type', 'text/plain');
        }
        $merger = new $mergerClass();

        $saleController = app(SaleController::class);
        foreach ($sales as $sale) {
            if (isset($sale['typesale']) && $sale['typesale'] === '0') {
                continue;
            }
            $saleId = $sale['correlativo'];
            try {
                $dte = Dte::where('sale_id', $saleId)->first();
                if ($dte && $dte->json) {
                    $pdf = $saleController->genera_pdf($saleId);
                } else {
                    $pdf = $saleController->genera_pdflocal($saleId);
                }
                $merger->addRaw($pdf->output());
            } catch (\Throwable $e) {
                continue;
            }
        }

        $mesesDelAno = array(
            'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
            'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
        );
        $filename = 'Comprobantes_Liquidacion_Documentos_' . $mesesDelAno[(int)$request['period']-1] . '_' . $request['year'] . '.pdf';

        try {
            $combined = $merger->merge();
        } catch (\Throwable $e) {
            return response('Error al unir PDFs: ' . $e->getMessage(), 500)
                ->header('Content-Type', 'text/plain');
        }

        return response()->streamDownload(function () use ($combined) {
            echo $combined;
        }, $filename, [
            'Content-Type' => 'application/pdf'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Report  $report
     * @return \Illuminate\Http\Response
     */
    public function destroy(Report $report)
    {
        //
    }

    /**
     * Reporte de Ventas a Terceros
     * Vista principal con filtros
     */
    public function ventasTerceros()
    {
        return view('reports.ventas-terceros');
    }

    /**
     * Búsqueda de Ventas a Terceros con filtros
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function ventasTercerosSearch(Request $request)
    {
        $Company = Company::find($request['company']);

        // Subconsulta DTE de emisión de la factura (evita duplicados por reenvíos)
        $dteFacSub = DB::table('dte')
            ->select('dte.sale_id', 'dte.id_doc', 'dte.codigoGeneracion', 'dte.selloRecibido', 'dte.estadoHacienda', 'dte.codEstado')
            ->whereIn('dte.codTransaction', ['01', '05', '06'])
            ->whereRaw('dte.id = (SELECT MAX(d2.id) FROM dte d2 WHERE d2.sale_id = dte.sale_id AND d2.codTransaction IN ("01","05","06"))');

        // Subconsulta DTE del CLQ (evita duplicados)
        $dteClqFacSub = DB::table('dte')
            ->select('dte.sale_id', 'dte.id_doc', 'dte.codEstado')
            ->whereIn('dte.codTransaction', ['01', '05', '06'])
            ->whereRaw('dte.id = (SELECT MAX(d2.id) FROM dte d2 WHERE d2.sale_id = dte.sale_id AND d2.codTransaction IN ("01","05","06"))');

        // Subconsulta: por cada factura, obtener el CLQ ACTIVO (no anulado) más reciente que la liquida
        // Esto evita duplicados cuando un CLQ se anula y se crea uno nuevo para las mismas facturas
        $sdClqActivoSub = DB::table('salesdetails as sd_inner')
            ->select('sd_inner.clq_numero_documento', DB::raw('MAX(sd_inner.sale_id) AS clq_sale_id'))
            ->join('sales as s_inner', 's_inner.id', '=', 'sd_inner.sale_id')
            ->where('s_inner.typedocument_id', '=', 2)
            ->where('s_inner.state', '!=', 0)
            ->where('s_inner.typesale', '!=', '0') // Solo CLQs activos (excluye anulados)
            ->whereNotNull('sd_inner.clq_numero_documento')
            ->groupBy('sd_inner.clq_numero_documento');

        // Query base: ventas con provider_id (ventas a terceros)
        $query = Sale::leftJoin('clients', 'sales.client_id', '=', 'clients.id')
            ->leftJoin('providers', 'sales.provider_id', '=', 'providers.id')
            ->leftJoinSub($dteFacSub, 'dte', 'dte.sale_id', '=', 'sales.id')
            ->leftJoin('typedocuments', 'sales.typedocument_id', '=', 'typedocuments.id')
            // Subconsulta: un único CLQ activo por factura (resuelve duplicados al anular y recrear CLQ)
            ->leftJoinSub($sdClqActivoSub, 'sd_clq', 'sd_clq.clq_numero_documento', '=', 'dte.codigoGeneracion')
            ->leftJoin('sales as clq', 'clq.id', '=', 'sd_clq.clq_sale_id')
            ->leftJoinSub($dteClqFacSub, 'dte_clq', 'dte_clq.sale_id', '=', 'clq.id')
            ->select(
                'sales.id AS sale_id',
                'sales.date',
                'sales.totalamount',
                'sales.state',
                'sales.parent_sale_id',
                'sales.is_parent',
                'typedocuments.description AS tipo_documento',
                'dte.id_doc AS numero_control',
                'dte.codigoGeneracion',
                'dte.selloRecibido',
                'dte.estadoHacienda',
                'providers.razonsocial AS proveedor_nombre',
                'providers.nit AS proveedor_nit'
            )
            ->selectRaw("CASE
                WHEN clients.tpersona = 'J' THEN clients.comercial_name
                WHEN clients.tpersona = 'N' THEN CONCAT_WS(' ', clients.firstname, clients.secondname, clients.firstlastname, clients.secondlastname)
                ELSE 'N/A'
            END AS cliente_nombre")
            ->selectRaw("DATE_FORMAT(sales.date, '%d/%m/%Y') AS fecha_formato")
            // Estado de liquidación: solo "Liquidado" si el CLQ activo tiene DTE confirmado
            ->selectRaw("CASE
                WHEN clq.id IS NOT NULL AND dte_clq.codEstado = '02' THEN 'Liquidado'
                ELSE 'Pendiente'
            END AS estado_liquidacion")
            ->selectRaw("clq.id AS clq_id")
            ->selectRaw("dte_clq.id_doc AS clq_numero_control")
            ->where('sales.company_id', $request['company'])
            ->whereNotNull('sales.provider_id') // Solo ventas con tercero
            ->where('sales.state', '=', 1) // Solo confirmadas
            ->where('sales.typedocument_id', '!=', 2); // Excluir CLQ (solo mostrar facturas/CCF)

        // Filtro por rango de fechas
        if ($request->filled('fecha_ini') && $request->filled('fecha_fin')) {
            $query->whereBetween('sales.date', [$request['fecha_ini'], $request['fecha_fin']]);
        }

        // Filtro por proveedor
        if ($request->filled('provider_id')) {
            $query->where('sales.provider_id', $request['provider_id']);
        }

        // Filtro por cliente
        if ($request->filled('client_id')) {
            $query->where('sales.client_id', $request['client_id']);
        }

        // Filtro por estado de liquidación
        if ($request->filled('estado_liquidacion')) {
            if ($request['estado_liquidacion'] === 'liquidado') {
                $query->whereNotNull('clq.id')->where('dte_clq.codEstado', '=', '02');
            } elseif ($request['estado_liquidacion'] === 'pendiente') {
                $query->where(function ($q) {
                    $q->whereNull('clq.id')
                      ->orWhere('dte_clq.codEstado', '!=', '02');
                });
            }
        }

        // Filtro por tipo de documento
        if ($request->filled('typedocument_id')) {
            $query->where('sales.typedocument_id', $request['typedocument_id']);
        }

        $sales = $query->orderBy('sales.date', 'desc')
            ->orderBy('sales.id', 'desc')
            ->get();

        // Asegurar que la empresa tenga todos los campos necesarios
        $companyData = [
            'id' => $Company->id ?? null,
            'name' => $Company->name ?? '-',
            'nit' => $Company->nit ?? '-',
            'ncr' => !empty($Company->ncr) ? $Company->ncr : '-'
        ];

        return response()->json([
            'company' => $companyData,
            'data' => $sales
        ]);
    }

    /**
     * Exportar reporte de Ventas a Terceros a Excel
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function ventasTercerosExcel(Request $request)
    {
        $Company = Company::find($request['company']);

        if (!$Company) {
            return response()->json(['error' => 'Empresa no encontrada'], 404);
        }

        // Subconsulta DTE de la factura (evita duplicados por reenvíos/contingencias)
        $dteFacExcelSub = DB::table('dte')
            ->select('dte.sale_id', 'dte.id_doc', 'dte.codigoGeneracion', 'dte.selloRecibido', 'dte.estadoHacienda', 'dte.codEstado')
            ->whereIn('dte.codTransaction', ['01', '05', '06'])
            ->whereRaw('dte.id = (SELECT MAX(d2.id) FROM dte d2 WHERE d2.sale_id = dte.sale_id AND d2.codTransaction IN ("01","05","06"))');

        // Subconsulta DTE del CLQ (evita duplicados)
        $dteClqExcelSub = DB::table('dte')
            ->select('dte.sale_id', 'dte.id_doc', 'dte.codEstado')
            ->whereIn('dte.codTransaction', ['01', '05', '06'])
            ->whereRaw('dte.id = (SELECT MAX(d2.id) FROM dte d2 WHERE d2.sale_id = dte.sale_id AND d2.codTransaction IN ("01","05","06"))');

        // Subconsulta: CLQ ACTIVO más reciente por factura (resuelve duplicados al anular y recrear CLQ)
        $sdClqActivoExcelSub = DB::table('salesdetails as sd_inner')
            ->select('sd_inner.clq_numero_documento', DB::raw('MAX(sd_inner.sale_id) AS clq_sale_id'))
            ->join('sales as s_inner', 's_inner.id', '=', 'sd_inner.sale_id')
            ->where('s_inner.typedocument_id', '=', 2)
            ->where('s_inner.state', '!=', 0)
            ->where('s_inner.typesale', '!=', '0') // Solo CLQs activos (excluye anulados)
            ->whereNotNull('sd_inner.clq_numero_documento')
            ->groupBy('sd_inner.clq_numero_documento');

        // Query base: ventas con provider_id (ventas a terceros) - misma lógica que ventasTercerosSearch
        $query = Sale::leftJoin('clients', 'sales.client_id', '=', 'clients.id')
            ->leftJoin('providers', 'sales.provider_id', '=', 'providers.id')
            ->leftJoinSub($dteFacExcelSub, 'dte', 'dte.sale_id', '=', 'sales.id')
            ->leftJoin('typedocuments', 'sales.typedocument_id', '=', 'typedocuments.id')
            ->leftJoinSub($sdClqActivoExcelSub, 'sd_clq', 'sd_clq.clq_numero_documento', '=', 'dte.codigoGeneracion')
            ->leftJoin('sales as clq', 'clq.id', '=', 'sd_clq.clq_sale_id')
            ->leftJoinSub($dteClqExcelSub, 'dte_clq', 'dte_clq.sale_id', '=', 'clq.id')
            ->select(
                'sales.id AS sale_id',
                'sales.date',
                'sales.totalamount',
                'typedocuments.description AS tipo_documento',
                'dte.id_doc AS numero_control',
                'dte.codigoGeneracion',
                'dte.selloRecibido',
                'providers.razonsocial AS proveedor_nombre',
                'providers.nit AS proveedor_nit'
            )
            ->selectRaw("CASE
                WHEN clients.tpersona = 'J' THEN clients.comercial_name
                WHEN clients.tpersona = 'N' THEN CONCAT_WS(' ', clients.firstname, clients.secondname, clients.firstlastname, clients.secondlastname)
                ELSE 'N/A'
            END AS cliente_nombre")
            ->selectRaw("DATE_FORMAT(sales.date, '%d/%m/%Y') AS fecha_formato")
            ->selectRaw("CASE
                WHEN clq.id IS NOT NULL AND dte_clq.codEstado = '02' THEN 'Liquidado'
                ELSE 'Pendiente'
            END AS estado_liquidacion")
            ->selectRaw("clq.id AS clq_id")
            ->selectRaw("dte_clq.id_doc AS clq_numero_control")
            ->where('sales.company_id', $request['company'])
            ->whereNotNull('sales.provider_id')
            ->where('sales.state', '=', 1)
            ->where('sales.typedocument_id', '!=', 2);

        // Aplicar los mismos filtros que en ventasTercerosSearch
        if ($request->filled('fecha_ini') && $request->filled('fecha_fin')) {
            $query->whereBetween('sales.date', [$request['fecha_ini'], $request['fecha_fin']]);
        }

        if ($request->filled('provider_id')) {
            $query->where('sales.provider_id', $request['provider_id']);
        }

        if ($request->filled('client_id')) {
            $query->where('sales.client_id', $request['client_id']);
        }

        if ($request->filled('estado_liquidacion')) {
            if ($request['estado_liquidacion'] === 'liquidado') {
                $query->whereNotNull('clq.id')->where('dte_clq.codEstado', '=', '02');
            } elseif ($request['estado_liquidacion'] === 'pendiente') {
                $query->where(function ($q) {
                    $q->whereNull('clq.id')
                      ->orWhere('dte_clq.codEstado', '!=', '02');
                });
            }
        }

        if ($request->filled('typedocument_id')) {
            $query->where('sales.typedocument_id', $request['typedocument_id']);
        }

        $sales = $query->orderBy('sales.date', 'desc')
            ->orderBy('sales.id', 'desc')
            ->get();

        // Construir HTML para Excel
        $html = '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
        $html .= '<head>';
        $html .= '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
        $html .= '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet>';
        $html .= '<x:Name>Ventas a Terceros</x:Name>';
        $html .= '<x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet>';
        $html .= '</x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->';
        $html .= '</head>';
        $html .= '<body>';
        $html .= '<table border="1">';

        // Encabezado
        $html .= '<tr><th colspan="12" style="text-align:center; font-weight:bold; font-size:14px;">REPORTE DE VENTAS A TERCEROS</th></tr>';
        $html .= '<tr><td colspan="12" style="text-align:center;">';
        $html .= '<b>Nombre del Contribuyente:</b> ' . $Company->name . ' ';
        $html .= '<b>NIT:</b> ' . $Company->nit . ' ';
        $html .= '<b>NRC:</b> ' . $Company->nrc;
        if ($request->filled('fecha_ini') && $request->filled('fecha_fin')) {
            $html .= ' <b>Período:</b> ' . date('d/m/Y', strtotime($request['fecha_ini'])) . ' - ' . date('d/m/Y', strtotime($request['fecha_fin']));
        }
        $html .= '</td></tr>';
        $html .= '<tr></tr>'; // Fila vacía

        // Encabezados de columna
        $html .= '<tr style="background-color:#4472C4; color:#FFFFFF; font-weight:bold;">';
        $html .= '<th>#</th>';
        $html .= '<th>Fecha</th>';
        $html .= '<th>Cliente</th>';
        $html .= '<th>Proveedor (Tercero)</th>';
        $html .= '<th>NIT Proveedor</th>';
        $html .= '<th>Tipo Documento</th>';
        $html .= '<th>N° Control</th>';
        $html .= '<th>Código Generación</th>';
        $html .= '<th>Sello Recepción</th>';
        $html .= '<th>Monto</th>';
        $html .= '<th>Estado Liquidación</th>';
        $html .= '<th>CLQ N° Control</th>';
        $html .= '</tr>';

        // Datos
        $totalPendiente = 0;
        $totalLiquidado = 0;
        $countPendiente = 0;
        $countLiquidado = 0;
        $i = 1;

        foreach ($sales as $sale) {
            $estado = $sale->estado_liquidacion === 'Liquidado' ? 'Liquidado' : 'Pendiente';

            if ($estado === 'Pendiente') {
                $totalPendiente += floatval($sale->totalamount);
                $countPendiente++;
            } else {
                $totalLiquidado += floatval($sale->totalamount);
                $countLiquidado++;
            }

            $html .= '<tr>';
            $html .= '<td>' . $i . '</td>';
            $html .= '<td>' . ($sale->fecha_formato ?? '-') . '</td>';
            $html .= '<td>' . ($sale->cliente_nombre ?? 'N/A') . '</td>';
            $html .= '<td>' . ($sale->proveedor_nombre ?? 'N/A') . '</td>';
            $html .= '<td>' . ($sale->proveedor_nit ?? '-') . '</td>';
            $html .= '<td>' . ($sale->tipo_documento ?? '-') . '</td>';
            $html .= '<td>' . ($sale->numero_control ?? '-') . '</td>';
            $html .= '<td>' . ($sale->codigoGeneracion ?? '-') . '</td>';
            $html .= '<td>' . ($sale->selloRecibido ?? '-') . '</td>';
            $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format(floatval($sale->totalamount), 2, '.', '') . '</td>';
            $html .= '<td>' . $estado . '</td>';
            $html .= '<td>' . ($sale->clq_numero_control ?? '-') . '</td>';
            $html .= '</tr>';
            $i++;
        }

        // Totales
        $html .= '<tr style="font-weight:bold; background-color:#E7E6E6;">';
        $html .= '<td colspan="9" style="text-align:right;">TOTALES</td>';
        $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($totalPendiente + $totalLiquidado, 2, '.', '') . '</td>';
        $html .= '<td colspan="2">-</td>';
        $html .= '</tr>';

        // Resumen
        $html .= '<tr></tr>';
        $html .= '<tr style="font-weight:bold;">';
        $html .= '<td colspan="9" style="text-align:right;">Pendientes de Liquidar:</td>';
        $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($totalPendiente, 2, '.', '') . '</td>';
        $html .= '<td>' . $countPendiente . ' ventas</td>';
        $html .= '<td>-</td>';
        $html .= '</tr>';

        $html .= '<tr style="font-weight:bold;">';
        $html .= '<td colspan="9" style="text-align:right;">Liquidadas:</td>';
        $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($totalLiquidado, 2, '.', '') . '</td>';
        $html .= '<td>' . $countLiquidado . ' ventas</td>';
        $html .= '<td>-</td>';
        $html .= '</tr>';

        $html .= '</table>';
        $html .= '</body></html>';

        // Generar nombre de archivo
        $fechaIni = $request->filled('fecha_ini') ? date('Y-m-d', strtotime($request['fecha_ini'])) : date('Y-m-d');
        $fechaFin = $request->filled('fecha_fin') ? date('Y-m-d', strtotime($request['fecha_fin'])) : date('Y-m-d');
        $filename = 'Ventas_Terceros_' . $fechaIni . '_' . $fechaFin . '.xls';

        return response($html)
            ->header('Content-Type', 'application/vnd.ms-excel; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // REPORTE: FACTURAS TERCEROS (MANDANTE) CON CLQ RELACIONADO
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Vista del reporte Facturas Terceros (Mandante)
     */
    public function facturasTerceros()
    {
        return view('reports.facturas-terceros');
    }

    /**
     * Buscar y mostrar el reporte Facturas Terceros (Mandante)
     */
    public function facturasTercerosSearch(Request $request)
    {
        $Company  = Company::find($request['company']);
        $mesesDelAno = ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
                        'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

        // Subconsulta: un único DTE de emisión por factura (evita duplicados por reenvíos)
        $dteFacturaSub = DB::table('dte')
            ->select('dte.sale_id', 'dte.id_doc', 'dte.codigoGeneracion', 'dte.selloRecibido', 'dte.codEstado')
            ->whereIn('dte.codTransaction', ['01', '05', '06'])
            ->whereRaw('dte.id = (SELECT MAX(d2.id) FROM dte d2 WHERE d2.sale_id = dte.sale_id AND d2.codTransaction IN ("01","05","06"))');

        // Subconsulta: un único DTE de emisión por CLQ (evita duplicados)
        $dteCLQSub = DB::table('dte')
            ->select('dte.sale_id', 'dte.id_doc', 'dte.codigoGeneracion', 'dte.codEstado')
            ->whereIn('dte.codTransaction', ['01', '05', '06'])
            ->whereRaw('dte.id = (SELECT MAX(d2.id) FROM dte d2 WHERE d2.sale_id = dte.sale_id AND d2.codTransaction IN ("01","05","06"))');

        // Subconsulta: CLQ ACTIVO más reciente por factura (resuelve duplicados al anular y recrear CLQ)
        $sdClqSub = DB::table('salesdetails as sd_inner')
            ->select('sd_inner.clq_numero_documento', DB::raw('MAX(sd_inner.sale_id) AS clq_sale_id'))
            ->join('sales as s_inner', 's_inner.id', '=', 'sd_inner.sale_id')
            ->where('s_inner.typedocument_id', '=', 2)
            ->where('s_inner.state', '!=', 0)
            ->where('s_inner.typesale', '!=', '0') // Solo CLQs activos (excluye anulados)
            ->whereNotNull('sd_inner.clq_numero_documento')
            ->groupBy('sd_inner.clq_numero_documento');

        $sales = Sale::leftJoin('clients', 'sales.client_id', '=', 'clients.id')
            ->leftJoin('providers', 'sales.provider_id', '=', 'providers.id')
            ->leftJoinSub($dteFacturaSub, 'dte_fact', 'dte_fact.sale_id', '=', 'sales.id')
            ->leftJoin('typedocuments', 'sales.typedocument_id', '=', 'typedocuments.id')
            ->leftJoinSub($sdClqSub, 'sd_clq', 'sd_clq.clq_numero_documento', '=', 'dte_fact.codigoGeneracion')
            ->leftJoin('sales as clq', 'clq.id', '=', 'sd_clq.clq_sale_id')
            ->leftJoinSub($dteCLQSub, 'dte_clq', 'dte_clq.sale_id', '=', 'clq.id')
            ->select(
                'sales.id AS sale_id',
                'sales.totalamount',
                'providers.razonsocial AS mandante_nombre',
                'providers.nit AS mandante_nit',
                'providers.ncr AS mandante_ncr',
                'typedocuments.description AS tipo_documento_desc',
                'typedocuments.codemh AS tipo_documento_cod',
                'dte_fact.id_doc AS numero_control',
                'dte_fact.codigoGeneracion AS codigo_generacion',
                'dte_fact.selloRecibido AS sello_recibido',
                'dte_clq.id_doc AS clq_numero_control',
                'dte_clq.codigoGeneracion AS clq_codigo_generacion'
            )
            ->selectRaw("DATE_FORMAT(sales.date, '%d/%m/%Y') AS fecha_emision")
            ->selectRaw("DATE_FORMAT(clq.date, '%d/%m/%Y') AS clq_fecha")
            ->selectRaw("(SELECT COALESCE(SUM(sd.detained13),0) FROM salesdetails sd WHERE sd.sale_id = sales.id) AS iva_operacion")
            ->selectRaw("CASE
                WHEN clq.id IS NOT NULL AND dte_clq.codEstado = '02' THEN 'Liquidado'
                ELSE 'Pendiente'
            END AS estado_liquidacion")
            ->where('sales.company_id', '=', $request['company'])
            ->whereNotNull('sales.provider_id')
            ->where('sales.state', '=', 1)
            ->where('sales.typesale', '!=', '0')  // Solo facturas activas (no anuladas)
            ->where('sales.typedocument_id', '!=', 2)
            ->whereRaw('YEAR(sales.date) = ?', [$request['year']])
            ->whereRaw('MONTH(sales.date) = ?', [$request['period']])
            ->where(function ($q) {
                $q->whereNull('sales.is_parent')->orWhere('sales.is_parent', 0);
            })
            ->where('dte_fact.codEstado', '=', '02') // Solo DTE confirmado
            ->orderBy('sales.date', 'asc')
            ->orderBy('sales.id', 'asc')
            ->get();

        $yearB   = $request['year'];
        $period  = $request['period'];
        $heading = $Company;

        return view('reports.facturas-terceros', compact('sales','heading','yearB','period','mesesDelAno'));
    }

    /**
     * Exportar reporte Facturas Terceros (Mandante) a Excel
     */
    public function facturasTercerosExcel(Request $request)
    {
        $Company = Company::find($request['company']);
        $mesesDelAno = ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
                        'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
        $mesesMayus  = array_map('strtoupper', $mesesDelAno);

        // Subconsulta: un único DTE de emisión por factura (evita duplicados por reenvíos)
        $dteFacturaSubExc = DB::table('dte')
            ->select('dte.sale_id', 'dte.id_doc', 'dte.codigoGeneracion', 'dte.selloRecibido', 'dte.codEstado')
            ->whereIn('dte.codTransaction', ['01', '05', '06'])
            ->whereRaw('dte.id = (SELECT MAX(d2.id) FROM dte d2 WHERE d2.sale_id = dte.sale_id AND d2.codTransaction IN ("01","05","06"))');

        // Subconsulta: un único DTE de emisión por CLQ (evita duplicados)
        $dteCLQSubExc = DB::table('dte')
            ->select('dte.sale_id', 'dte.id_doc', 'dte.codigoGeneracion', 'dte.codEstado')
            ->whereIn('dte.codTransaction', ['01', '05', '06'])
            ->whereRaw('dte.id = (SELECT MAX(d2.id) FROM dte d2 WHERE d2.sale_id = dte.sale_id AND d2.codTransaction IN ("01","05","06"))');

        // Subconsulta: CLQ ACTIVO más reciente por factura (resuelve duplicados al anular y recrear CLQ)
        $sdClqSubExc = DB::table('salesdetails as sd_inner')
            ->select('sd_inner.clq_numero_documento', DB::raw('MAX(sd_inner.sale_id) AS clq_sale_id'))
            ->join('sales as s_inner', 's_inner.id', '=', 'sd_inner.sale_id')
            ->where('s_inner.typedocument_id', '=', 2)
            ->where('s_inner.state', '!=', 0)
            ->where('s_inner.typesale', '!=', '0') // Solo CLQs activos (excluye anulados)
            ->whereNotNull('sd_inner.clq_numero_documento')
            ->groupBy('sd_inner.clq_numero_documento');

        $sales = Sale::leftJoin('clients', 'sales.client_id', '=', 'clients.id')
            ->leftJoin('providers', 'sales.provider_id', '=', 'providers.id')
            ->leftJoinSub($dteFacturaSubExc, 'dte_fact', 'dte_fact.sale_id', '=', 'sales.id')
            ->leftJoin('typedocuments', 'sales.typedocument_id', '=', 'typedocuments.id')
            ->leftJoinSub($sdClqSubExc, 'sd_clq', 'sd_clq.clq_numero_documento', '=', 'dte_fact.codigoGeneracion')
            ->leftJoin('sales as clq', 'clq.id', '=', 'sd_clq.clq_sale_id')
            ->leftJoinSub($dteCLQSubExc, 'dte_clq', 'dte_clq.sale_id', '=', 'clq.id')
            ->select(
                'sales.id AS sale_id',
                'sales.totalamount',
                'providers.razonsocial AS mandante_nombre',
                'providers.nit AS mandante_nit',
                'providers.ncr AS mandante_ncr',
                'typedocuments.description AS tipo_documento_desc',
                'typedocuments.codemh AS tipo_documento_cod',
                'dte_fact.id_doc AS numero_control',
                'dte_fact.codigoGeneracion AS codigo_generacion',
                'dte_fact.selloRecibido AS sello_recibido',
                'dte_clq.id_doc AS clq_numero_control',
                'dte_clq.codigoGeneracion AS clq_codigo_generacion'
            )
            ->selectRaw("DATE_FORMAT(sales.date, '%d/%m/%Y') AS fecha_emision")
            ->selectRaw("DATE_FORMAT(clq.date, '%d/%m/%Y') AS clq_fecha")
            ->selectRaw("(SELECT COALESCE(SUM(sd.detained13),0) FROM salesdetails sd WHERE sd.sale_id = sales.id) AS iva_operacion")
            ->selectRaw("CASE
                WHEN clq.id IS NOT NULL AND dte_clq.codEstado = '02' THEN 'Liquidado'
                ELSE 'Pendiente'
            END AS estado_liquidacion")
            ->where('sales.company_id', '=', $request['company'])
            ->whereNotNull('sales.provider_id')
            ->where('sales.state', '=', 1)
            ->where('sales.typesale', '!=', '0')  // Solo facturas activas (no anuladas)
            ->where('sales.typedocument_id', '!=', 2)
            ->whereRaw('YEAR(sales.date) = ?', [$request['year']])
            ->whereRaw('MONTH(sales.date) = ?', [$request['period']])
            ->where(function ($q) {
                $q->whereNull('sales.is_parent')->orWhere('sales.is_parent', 0);
            })
            ->where('dte_fact.codEstado', '=', '02') // Solo DTE confirmado
            ->orderBy('sales.date', 'asc')
            ->orderBy('sales.id', 'asc')
            ->get();

        // Construir HTML para Excel
        $html  = '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
        $html .= '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
        $html .= '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet>';
        $html .= '<x:Name>Facturas Terceros</x:Name>';
        $html .= '<x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions>';
        $html .= '</x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--></head>';
        $html .= '<body><table border="1">';

        // Encabezado del reporte
        $html .= '<tr><th colspan="14" style="text-align:center; font-weight:bold; background:#1e3a5f; color:#fff;">FACTURAS TERCEROS - MANDANTE/MANDATARIO</th></tr>';
        $html .= '<tr><td colspan="14" style="text-align:center;">';
        $html .= '<b>Empresa (Mandatario):</b> ' . ($Company['name'] ?? '') . '&nbsp;&nbsp;';
        $html .= '<b>NIT:</b> ' . ($Company['nit'] ?? '') . '&nbsp;&nbsp;';
        $html .= '<b>MES:</b> ' . $mesesMayus[(int)$request['period'] - 1] . '&nbsp;&nbsp;';
        $html .= '<b>AÑO:</b> ' . $request['year'];
        $html .= '</td></tr>';

        // Fila de agrupación de encabezados
        $html .= '<tr style="background:#c9daf8; font-weight:bold; text-align:center;">';
        $html .= '<th rowspan="2">N°</th>';
        $html .= '<th rowspan="2">NIT MANDANTE</th>';
        $html .= '<th rowspan="2">NRC MANDANTE</th>';
        $html .= '<th rowspan="2">NOMBRE / RAZÓN SOCIAL MANDANTE</th>';
        $html .= '<th rowspan="2">FECHA EMISIÓN</th>';
        $html .= '<th rowspan="2">TIPO DOC.</th>';
        $html .= '<th rowspan="2">SERIE</th>';
        $html .= '<th rowspan="2">Nº RESOLUCIÓN (CONTROL DTE)</th>';
        $html .= '<th rowspan="2">Nº DOCUMENTO (CÓD. GENERACIÓN)</th>';
        $html .= '<th rowspan="2">IVA DE LA OPERACIÓN</th>';
        $html .= '<th colspan="3" style="background:#a4c2f4;">COMPROBANTE DE LIQUIDACIÓN</th>';
        $html .= '<th rowspan="2">ESTADO</th>';
        $html .= '</tr>';
        $html .= '<tr style="background:#a4c2f4; font-weight:bold; text-align:center;">';
        $html .= '<th>RESOLUCIÓN CLQ (CONTROL)</th>';
        $html .= '<th>Nº COMPROBANTE (CÓD. GEN.)</th>';
        $html .= '<th>FECHA CLQ</th>';
        $html .= '</tr>';

        $i = 1;
        $tot_iva = 0;

        foreach ($sales as $sale) {
            $nrcLimpio = preg_replace('/[^0-9]/', '', $sale->mandante_ncr ?? '');
            $tipoCod   = $sale->tipo_documento_cod ? $sale->tipo_documento_cod . ' - ' . ($sale->tipo_documento_desc ?? '') : ($sale->tipo_documento_desc ?? '-');
            $estado    = $sale->estado_liquidacion ?? 'Pendiente';

            $colorEstado = '';
            if ($estado === 'CLQ Anulado') {
                $colorEstado = 'color:red; font-weight:bold;';
            } elseif ($estado === 'Pendiente') {
                $colorEstado = 'color:#b45309; font-weight:bold;';
            }

            $html .= '<tr>';
            $html .= '<td>' . $i . '</td>';
            $html .= '<td>' . ($sale->mandante_nit ?? '-') . '</td>';
            $html .= '<td>' . $nrcLimpio . '</td>';
            $html .= '<td>' . ($sale->mandante_nombre ?? '-') . '</td>';
            $html .= '<td>' . ($sale->fecha_emision ?? '-') . '</td>';
            $html .= '<td>' . $tipoCod . '</td>';
            $html .= '<td>-</td>';
            $html .= '<td>' . ($sale->numero_control ?? '-') . '</td>';
            $html .= '<td>' . ($sale->codigo_generacion ?? '-') . '</td>';
            $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format(floatval($sale->iva_operacion ?? 0), 2, '.', '') . '</td>';
            $html .= '<td>' . ($sale->clq_numero_control ?? '-') . '</td>';
            $html .= '<td>' . ($sale->clq_codigo_generacion ?? '-') . '</td>';
            $html .= '<td>' . ($sale->clq_fecha ?? '-') . '</td>';
            $html .= '<td style="' . $colorEstado . '">' . $estado . '</td>';
            $html .= '</tr>';

            $tot_iva += floatval($sale->iva_operacion ?? 0);
            $i++;
        }

        // Totales
        $html .= '<tr style="font-weight:bold; background:#c9daf8;">';
        $html .= '<td colspan="9" style="text-align:right;">TOTALES DEL MES</td>';
        $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($tot_iva, 2, '.', '') . '</td>';
        $html .= '<td colspan="4"></td>';
        $html .= '</tr>';

        $html .= '</table></body></html>';

        $filename = 'Facturas_Terceros_' . $mesesDelAno[(int)$request['period'] - 1] . '_' . $request['year'] . '.xls';

        return response($html)
            ->header('Content-Type', 'application/vnd.ms-excel; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // REPORTE: DETALLE DE FACTURAS POR COMPROBANTE DE LIQUIDACIÓN (CLQ)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Mostrar la vista del reporte de detalle CLQ
     */
    public function clqDetalle()
    {
        return view('reports.clq-detalle');
    }

    /**
     * Procesar búsqueda y devolver la vista con los datos
     */
    public function clqDetalleSearch(Request $request)
    {
        $Company = Company::find($request['company']);

        $mesesDelAno = ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
                        'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

        // Subconsulta: un solo DTE de emisión por venta
        $dteEmisionSub = DB::table('dte')
            ->select('dte.sale_id','dte.id_doc','dte.codigoGeneracion','dte.selloRecibido','dte.fhRecibido','dte.json','dte.codEstado')
            ->whereIn('dte.codTransaction', ['01','05','06'])
            ->whereRaw('dte.id = (SELECT MAX(d2.id) FROM dte d2 WHERE d2.sale_id = dte.sale_id AND d2.codTransaction IN ("01","05","06"))');

        $sales = Sale::leftJoin('clients', 'sales.client_id', '=', 'clients.id')
            ->leftJoinSub($dteEmisionSub, 'dte_emis', 'dte_emis.sale_id', '=', 'sales.id')
            ->select(
                'sales.id AS correlativo',
                'sales.typesale',
                'sales.totalamount',
                'clients.ncr AS ncrC',
                'dte_emis.id_doc AS numeroControl',
                'dte_emis.codigoGeneracion AS codigoGeneracion',
                'dte_emis.selloRecibido AS selloRecibido'
            )
            ->selectRaw("CASE
                WHEN dte_emis.fhRecibido IS NOT NULL
                THEN DATE_FORMAT(dte_emis.fhRecibido, '%d/%m/%Y')
                WHEN dte_emis.json IS NOT NULL AND JSON_EXTRACT(dte_emis.json, '$.identificacion.fecEmi') IS NOT NULL
                THEN DATE_FORMAT(STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(dte_emis.json, '$.identificacion.fecEmi')), '%Y-%m-%d'), '%d/%m/%Y')
                ELSE DATE_FORMAT(sales.date, '%d/%m/%Y')
            END AS dateF")
            ->selectRaw("CASE
                WHEN clients.tpersona = 'J' THEN clients.comercial_name
                WHEN clients.tpersona = 'N' THEN CONCAT_WS(' ', clients.firstname, clients.secondname, clients.firstlastname, clients.secondlastname)
                ELSE ''
            END AS nombre_completo")
            ->where('sales.typedocument_id', '=', '2')
            ->where(function ($q) {
                $q->whereNull('sales.is_parent')->orWhere('sales.is_parent', 0);
            })
            ->whereRaw('YEAR(sales.date) = ?', [$request['year']])
            ->whereRaw('MONTH(sales.date) = ?', [$request['period']])
            ->where('sales.company_id', '=', $request['company'])
            ->orderBy('sales.id')
            ->get();

        // Para cada CLQ, cargar sus facturas relacionadas (salesdetails con clq_numero_documento)
        foreach ($sales as $sale) {
            $sale->facturas = DB::table('salesdetails')
                ->leftJoin('providers', 'salesdetails.line_provider_id', '=', 'providers.id')
                ->where('salesdetails.sale_id', $sale->correlativo)
                ->whereNotNull('salesdetails.clq_numero_documento')
                ->select(
                    'salesdetails.clq_tipo_documento',
                    'salesdetails.clq_tipo_generacion',
                    'salesdetails.clq_numero_documento',
                    'salesdetails.clq_fecha_generacion',
                    'salesdetails.clq_observaciones',
                    'salesdetails.pricesale',
                    'salesdetails.exempt',
                    'salesdetails.nosujeta',
                    'salesdetails.detained13',
                    'providers.razonsocial AS provider_name',
                    'providers.nit AS provider_nit'
                )
                ->get();
        }

        $yearB   = $request['year'];
        $period  = $request['period'];
        $heading = $Company;

        return view('reports.clq-detalle', compact('sales','heading','yearB','period','mesesDelAno'));
    }

    /**
     * Exportar el reporte de detalle CLQ a Excel
     */
    public function clqDetalleExcel(Request $request)
    {
        $Company = Company::find($request['company']);

        $mesesDelAno = ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
                        'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
        $mesesMayus  = array_map('strtoupper', $mesesDelAno);

        $tiposDoc = [
            '01' => 'FCF', '03' => 'CCF', '04' => 'Nota Remisión',
            '05' => 'Nota Crédito', '06' => 'Nota Débito',
            '11' => 'FEX', '14' => 'FSE',
        ];

        $dteEmisionSub = DB::table('dte')
            ->select('dte.sale_id','dte.id_doc','dte.codigoGeneracion','dte.selloRecibido','dte.fhRecibido','dte.json','dte.codEstado')
            ->whereIn('dte.codTransaction', ['01','05','06'])
            ->whereRaw('dte.id = (SELECT MAX(d2.id) FROM dte d2 WHERE d2.sale_id = dte.sale_id AND d2.codTransaction IN ("01","05","06"))');

        $sales = Sale::leftJoin('clients', 'sales.client_id', '=', 'clients.id')
            ->leftJoinSub($dteEmisionSub, 'dte_emis', 'dte_emis.sale_id', '=', 'sales.id')
            ->select(
                'sales.id AS correlativo',
                'sales.typesale',
                'sales.totalamount',
                'clients.ncr AS ncrC',
                'dte_emis.id_doc AS numeroControl',
                'dte_emis.codigoGeneracion AS codigoGeneracion',
                'dte_emis.selloRecibido AS selloRecibido'
            )
            ->selectRaw("CASE
                WHEN dte_emis.fhRecibido IS NOT NULL
                THEN DATE_FORMAT(dte_emis.fhRecibido, '%d/%m/%Y')
                WHEN dte_emis.json IS NOT NULL AND JSON_EXTRACT(dte_emis.json, '$.identificacion.fecEmi') IS NOT NULL
                THEN DATE_FORMAT(STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(dte_emis.json, '$.identificacion.fecEmi')), '%Y-%m-%d'), '%d/%m/%Y')
                ELSE DATE_FORMAT(sales.date, '%d/%m/%Y')
            END AS dateF")
            ->selectRaw("CASE
                WHEN clients.tpersona = 'J' THEN clients.comercial_name
                WHEN clients.tpersona = 'N' THEN CONCAT_WS(' ', clients.firstname, clients.secondname, clients.firstlastname, clients.secondlastname)
                ELSE ''
            END AS nombre_completo")
            ->where('sales.typedocument_id', '=', '2')
            ->where(function ($q) {
                $q->whereNull('sales.is_parent')->orWhere('sales.is_parent', 0);
            })
            ->whereRaw('YEAR(sales.date) = ?', [$request['year']])
            ->whereRaw('MONTH(sales.date) = ?', [$request['period']])
            ->where('sales.company_id', '=', $request['company'])
            ->orderBy('sales.id')
            ->get();

        foreach ($sales as $sale) {
            $sale->facturas = DB::table('salesdetails')
                ->leftJoin('providers', 'salesdetails.line_provider_id', '=', 'providers.id')
                ->where('salesdetails.sale_id', $sale->correlativo)
                ->whereNotNull('salesdetails.clq_numero_documento')
                ->select(
                    'salesdetails.clq_tipo_documento',
                    'salesdetails.clq_tipo_generacion',
                    'salesdetails.clq_numero_documento',
                    'salesdetails.clq_fecha_generacion',
                    'salesdetails.clq_observaciones',
                    'salesdetails.pricesale',
                    'salesdetails.exempt',
                    'salesdetails.nosujeta',
                    'salesdetails.detained13',
                    'providers.razonsocial AS provider_name',
                    'providers.nit AS provider_nit'
                )
                ->get();
        }

        // ── Construir HTML para Excel ──────────────────────────────────────────
        $html  = '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
        $html .= '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
        $html .= '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet>';
        $html .= '<x:Name>Detalle CLQ</x:Name>';
        $html .= '<x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions>';
        $html .= '</x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--></head>';
        $html .= '<body><table border="1">';

        // Encabezado general
        $html .= '<tr><th colspan="19" style="text-align:center; font-weight:bold; background:#1e3a5f; color:#fff;">DETALLE DE FACTURAS POR COMPROBANTE DE LIQUIDACIÓN</th></tr>';
        $html .= '<tr><td colspan="19" style="text-align:center;">';
        $html .= '<b>Empresa:</b> ' . $Company['name'] . ' &nbsp; <b>NRC:</b> ' . $Company['nrc'];
        $html .= ' &nbsp; <b>MES:</b> ' . $mesesMayus[(int)$request['period'] - 1];
        $html .= ' &nbsp; <b>AÑO:</b> ' . $request['year'];
        $html .= '</td></tr>';

        // Cabecera de columnas
        $html .= '<tr style="background:#c9daf8; font-weight:bold; text-align:center;">';
        // Columnas del CLQ
        $html .= '<th>N°</th>';
        $html .= '<th>FECHA CLQ</th>';
        $html .= '<th>CLIENTE</th>';
        $html .= '<th>NRC</th>';
        $html .= '<th>ESTADO</th>';
        $html .= '<th>Nº CONTROL DTE</th>';
        $html .= '<th>CÓD. GENERACIÓN</th>';
        $html .= '<th>SELLO RECEPCIÓN</th>';
        $html .= '<th>TOTAL CLQ</th>';
        // Columnas de la factura relacionada
        $html .= '<th>TIPO DOC</th>';
        $html .= '<th>TIPO GEN.</th>';
        $html .= '<th>Nº DOCUMENTO RELACIONADO</th>';
        $html .= '<th>FECHA FACTURA</th>';
        $html .= '<th>OBSERVACIONES</th>';
        $html .= '<th>GRAVADAS</th>';
        $html .= '<th>EXENTAS</th>';
        $html .= '<th>NO SUJETAS</th>';
        $html .= '<th>IVA</th>';
        $html .= '<th>PROVEEDOR</th>';
        $html .= '</tr>';

        $i = 1;
        $tot_clq = 0;
        $tot_gravadas = 0;
        $tot_exentas = 0;
        $tot_nosujetas = 0;
        $tot_iva = 0;

        foreach ($sales as $sale) {
            $estado   = $sale->typesale == '0' ? 'ANULADO' : 'ACTIVO';
            $colorFil = $sale->typesale == '0' ? 'color:red;font-weight:bold;' : '';
            $nrcLimpio = preg_replace('/[^0-9]/', '', $sale->ncrC ?? '');

            if ($sale->facturas->isEmpty()) {
                // CLQ sin facturas relacionadas
                $html .= '<tr>';
                $html .= '<td>' . $i . '</td>';
                $html .= '<td>' . ($sale->dateF ?? '-') . '</td>';
                $html .= '<td style="' . $colorFil . '">' . ($sale->typesale == '0' ? 'ANULADO' : strtoupper($sale->nombre_completo ?? '')) . '</td>';
                $html .= '<td>' . $nrcLimpio . '</td>';
                $html .= '<td style="' . $colorFil . '">' . $estado . '</td>';
                $html .= '<td>' . ($sale->numeroControl ?? '-') . '</td>';
                $html .= '<td>' . ($sale->codigoGeneracion ?? '-') . '</td>';
                $html .= '<td>' . ($sale->selloRecibido ?? '-') . '</td>';
                $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($sale->totalamount ?? 0, 2, '.', '') . '</td>';
                $html .= '<td colspan="10">-</td>';
                $html .= '</tr>';
                if ($sale->typesale != '0') $tot_clq += $sale->totalamount ?? 0;
                $i++;
                continue;
            }

            $firstFac = true;
            foreach ($sale->facturas as $fac) {
                $tipoCod  = $fac->clq_tipo_documento ?? '';
                $tipoDesc = ($tiposDoc[$tipoCod] ?? $tipoCod);
                $tipoGen  = $fac->clq_tipo_generacion == '1' ? 'Físico' : ($fac->clq_tipo_generacion == '2' ? 'Electrónico' : ($fac->clq_tipo_generacion ?? '-'));
                $gravada  = floatval($fac->pricesale ?? 0);
                $exenta   = floatval($fac->exempt ?? 0);
                $nosujeta = floatval($fac->nosujeta ?? 0);
                $iva      = floatval($fac->detained13 ?? 0);

                $html .= '<tr>';

                if ($firstFac) {
                    $html .= '<td>' . $i . '</td>';
                    $html .= '<td>' . ($sale->dateF ?? '-') . '</td>';
                    $html .= '<td style="' . $colorFil . '">' . ($sale->typesale == '0' ? 'ANULADO' : strtoupper($sale->nombre_completo ?? '')) . '</td>';
                    $html .= '<td>' . $nrcLimpio . '</td>';
                    $html .= '<td style="' . $colorFil . '">' . $estado . '</td>';
                    $html .= '<td>' . ($sale->numeroControl ?? '-') . '</td>';
                    $html .= '<td>' . ($sale->codigoGeneracion ?? '-') . '</td>';
                    $html .= '<td>' . ($sale->selloRecibido ?? '-') . '</td>';
                    $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($sale->totalamount ?? 0, 2, '.', '') . '</td>';
                    if ($sale->typesale != '0') $tot_clq += $sale->totalamount ?? 0;
                    $firstFac = false;
                } else {
                    $html .= '<td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>';
                }

                $html .= '<td>' . htmlspecialchars($tipoDesc) . '</td>';
                $html .= '<td>' . htmlspecialchars($tipoGen) . '</td>';
                $html .= '<td>' . htmlspecialchars($fac->clq_numero_documento ?? '-') . '</td>';
                $html .= '<td>' . ($fac->clq_fecha_generacion ? date('d/m/Y', strtotime($fac->clq_fecha_generacion)) : '-') . '</td>';
                $html .= '<td>' . htmlspecialchars($fac->clq_observaciones ?? '') . '</td>';
                $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($gravada, 2, '.', '') . '</td>';
                $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($exenta, 2, '.', '') . '</td>';
                $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($nosujeta, 2, '.', '') . '</td>';
                $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($iva, 2, '.', '') . '</td>';
                $html .= '<td>' . htmlspecialchars($fac->provider_name ?? '-') . '</td>';
                $html .= '</tr>';

                if ($sale->typesale != '0') {
                    $tot_gravadas += $gravada;
                    $tot_exentas  += $exenta;
                    $tot_nosujetas += $nosujeta;
                    $tot_iva      += $iva;
                }
            }
            $i++;
        }

        // Fila de totales
        $html .= '<tr style="font-weight:bold; background:#c9daf8;">';
        $html .= '<td colspan="8" style="text-align:right;">TOTALES DEL MES</td>';
        $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($tot_clq, 2, '.', '') . '</td>';
        $html .= '<td colspan="5"></td>';
        $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($tot_gravadas, 2, '.', '') . '</td>';
        $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($tot_exentas, 2, '.', '') . '</td>';
        $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($tot_nosujetas, 2, '.', '') . '</td>';
        $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($tot_iva, 2, '.', '') . '</td>';
        $html .= '<td></td>';
        $html .= '</tr>';

        $html .= '</table></body></html>';

        $filename = 'Detalle_CLQ_' . $mesesDelAno[(int)$request['period'] - 1] . '_' . $request['year'] . '.xls';

        return response($html)
            ->header('Content-Type', 'application/vnd.ms-excel; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }
}
