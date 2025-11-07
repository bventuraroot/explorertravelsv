<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Purchase;
use App\Models\Report;
use App\Models\Sale;
use App\Models\Salesdetail;
use Illuminate\Http\Request;

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
        ->selectRaw("(SELECT SUM(sde.exempt) FROM salesdetails AS sde WHERE sde.sale_id=sales.id) AS exenta")
        ->selectRaw("(SELECT SUM(sdg.pricesale) FROM salesdetails AS sdg WHERE sdg.sale_id=sales.id) AS gravada")
        ->selectRaw("(SELECT SUM(sdn.nosujeta) FROM salesdetails AS sdn WHERE sdn.sale_id=sales.id) AS nosujeta")
        ->selectRaw("(SELECT SUM(sdi.detained13) FROM salesdetails AS sdi WHERE sdi.sale_id=sales.id) AS iva")
        ->where('sales.typedocument_id', "=", "3")
        ->whereRaw('YEAR(sales.date)=?', $request['year'])
        ->whereRaw('MONTH(sales.date)=?', $request['period'])
        ->WhereRaw('DAY(sales.date) BETWEEN "01" AND "31"')
        ->where('sales.company_id', '=', $request['company'])
        // Solo incluir DTE que fueron enviados exitosamente (estado "Enviado")
        ->where('dte.codEstado', '=', '02')
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
        ->selectRaw("CASE
            WHEN clients.tpersona = 'J' THEN clients.comercial_name
            WHEN clients.tpersona = 'N' THEN CONCAT_WS(' ', clients.firstname, clients.secondname, clients.firstlastname, clients.secondlastname)
            ELSE ''
        END AS nombre_completo")
        ->selectRaw("(SELECT SUM(sde.exempt) FROM salesdetails AS sde WHERE sde.sale_id=sales.id) AS exenta")
        ->selectRaw("(SELECT SUM(sdg.pricesale) FROM salesdetails AS sdg WHERE sdg.sale_id=sales.id) AS gravada")
        ->selectRaw("(SELECT SUM(sdn.nosujeta) FROM salesdetails AS sdn WHERE sdn.sale_id=sales.id) AS nosujeta")
        ->selectRaw("(SELECT SUM(sdi.detained13) FROM salesdetails AS sdi WHERE sdi.sale_id=sales.id) AS iva")
        ->selectRaw("(SELECT 
            COALESCE(SUM(sd.fee), 0) + 
            COALESCE(SUM(CASE 
                WHEN p.name IN ('Cargo administrativo', 'CXS') THEN 
                    CASE 
                        WHEN (sd.pricesale + sd.nosujeta + sd.exempt) > 0 THEN 
                            (sd.pricesale + sd.nosujeta + sd.exempt) / 1.13
                        ELSE 0
                    END
                ELSE 0
            END), 0)
        FROM salesdetails AS sd 
        LEFT JOIN products AS p ON p.id = sd.product_id
        WHERE sd.sale_id = sales.id) AS fee")
        ->selectRaw("(SELECT 
            COALESCE(SUM(sd.feeiva), 0) + 
            COALESCE(SUM(CASE 
                WHEN p.name IN ('Cargo administrativo', 'CXS') THEN 
                    CASE 
                        WHEN (sd.pricesale + sd.nosujeta + sd.exempt) > 0 THEN 
                            ((sd.pricesale + sd.nosujeta + sd.exempt) / 1.13) * 0.13
                        ELSE 0
                    END
                ELSE 0
            END), 0)
        FROM salesdetails AS sd 
        LEFT JOIN products AS p ON p.id = sd.product_id
        WHERE sd.sale_id = sales.id) AS ivafee")
        ->where('sales.typedocument_id', "=", "6")
        ->whereRaw('(clients.tpersona = "N" OR clients.tpersona = "J")' )
        ->whereRaw('YEAR(sales.date)=?', $request['year'])
        ->whereRaw('MONTH(sales.date)=?', $request['period'])
        ->WhereRaw('DAY(sales.date) BETWEEN "01" AND "31"')
        ->where('sales.company_id', '=', $request['company'])
        // Solo incluir DTE que fueron enviados exitosamente o ventas sin DTE
        ->where(function($query) {
            $query->whereNull('dte.codEstado')
                  ->orWhere('dte.codEstado', '=', '02');
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
        ->selectRaw("(SELECT SUM(sde.exempt) FROM salesdetails AS sde WHERE sde.sale_id=sales.id) AS exenta")
        ->selectRaw("(SELECT SUM(sdg.pricesale) FROM salesdetails AS sdg WHERE sdg.sale_id=sales.id) AS gravada")
        ->selectRaw("(SELECT SUM(sdn.nosujeta) FROM salesdetails AS sdn WHERE sdn.sale_id=sales.id) AS nosujeta")
        ->selectRaw("(SELECT SUM(sdi.detained13) FROM salesdetails AS sdi WHERE sdi.sale_id=sales.id) AS iva")
        ->where('sales.typedocument_id', "=", "3")
        ->whereRaw('YEAR(sales.date)=?', $request['year'])
        ->whereRaw('MONTH(sales.date)=?', $request['period'])
        ->WhereRaw('DAY(sales.date) BETWEEN "01" AND "31"')
        ->where('sales.company_id', '=', $request['company'])
        ->where('dte.codEstado', '=', '02')
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
        $html .= '<tr><th colspan="17" style="text-align:center; font-weight:bold;">LIBRO DE VENTAS CONTRIBUYENTES (Valores expresados en USD)</th></tr>';
        $html .= '<tr><td colspan="17" style="text-align:center;">';
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
        $html .= '<th>No Sujetas</th>';
        $html .= '<th>Exentas (Terceros)</th>';
        $html .= '<th>Internas Gravadas (Terceros)</th>';
        $html .= '<th>Debito Fiscal (Terceros)</th>';
        $html .= '<th>IVA Percibido</th>';
        $html .= '<th>TOTAL</th>';
        $html .= '<th>NÚMERO CONTROL DTE</th>';
        $html .= '<th>CÓDIGO GENERACIÓN</th>';
        $html .= '<th>SELLO RECEPCIÓN</th>';
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
        $i = 1;

        foreach ($sales as $sale) {
            $html .= '<tr>';
            $html .= '<td>' . $i . '</td>';
            $html .= '<td>' . $sale['dateF'] . '</td>';
            $html .= '<td>' . ($sale['correlativo'] ?? '-') . '</td>';

            // Nombre del cliente
            if($sale['typesale']=='0'){
                $html .= '<td>ANULADO</td>';
            } else {
                if($sale['tpersona']=='J'){
                    $html .= '<td>' . strtoupper($sale['comercial_name']) . '</td>';
                } else {
                    $html .= '<td>' . strtoupper($sale['firstname'] . ' ' . $sale['firstlastname']) . '</td>';
                }
            }

            $html .= '<td>' . $sale['ncrC'] . '</td>';

            if($sale['typesale']=='0'){
                $html .= '<td>ANULADO</td>';
                $html .= '<td>ANULADO</td>';
                $html .= '<td>ANULADO</td>';
                $html .= '<td>ANULADO</td>';
            } else {
                $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($sale['exenta'], 2, '.', '') . '</td>';
                $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($sale['gravada'], 2, '.', '') . '</td>';
                $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($sale['iva'], 2, '.', '') . '</td>';
                $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($sale['nosujeta'], 2, '.', '') . '</td>';

                $total_ex += $sale['exenta'];
                $total_gv += $sale['gravada'];
                $total_iva += $sale['iva'];
                $total_ns += $sale['nosujeta'];
            }

            $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">0.00</td>';
            $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">0.00</td>';
            $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">0.00</td>';
            $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">0.00</td>';

            if($sale['typesale']=='0'){
                $html .= '<td>ANULADO</td>';
            } else {
                $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($sale['totalamount'], 2, '.', '') . '</td>';
                $tot_final += $sale['totalamount'];
            }

            $html .= '<td>' . ($sale['numeroControl'] ?? '-') . '</td>';
            $html .= '<td>' . ($sale['codigoGeneracion'] ?? '-') . '</td>';
            $html .= '<td>' . ($sale['selloRecibido'] ?? '-') . '</td>';
            $html .= '</tr>';
            $i++;
        }

        // Totales
        $html .= '<tr style="font-weight:bold;">';
        $html .= '<td colspan="5" style="text-align:right;">TOTALES DEL MES</td>';
        $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($total_ex, 2, '.', '') . '</td>';
        $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($total_gv, 2, '.', '') . '</td>';
        $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($total_iva, 2, '.', '') . '</td>';
        $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($total_ns, 2, '.', '') . '</td>';
        $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">0.00</td>';
        $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">0.00</td>';
        $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">0.00</td>';
        $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">0.00</td>';
        $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($tot_final, 2, '.', '') . '</td>';
        $html .= '<td>-</td><td>-</td><td>-</td>';
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
        ->selectRaw("CASE
            WHEN clients.tpersona = 'J' THEN clients.comercial_name
            WHEN clients.tpersona = 'N' THEN CONCAT_WS(' ', clients.firstname, clients.secondname, clients.firstlastname, clients.secondlastname)
            ELSE ''
        END AS nombre_completo")
        ->selectRaw("(SELECT SUM(sde.exempt) FROM salesdetails AS sde WHERE sde.sale_id=sales.id) AS exenta")
        ->selectRaw("(SELECT SUM(sdg.pricesale) FROM salesdetails AS sdg WHERE sdg.sale_id=sales.id) AS gravada")
        ->selectRaw("(SELECT SUM(sdn.nosujeta) FROM salesdetails AS sdn WHERE sdn.sale_id=sales.id) AS nosujeta")
        ->selectRaw("(SELECT SUM(sdi.detained13) FROM salesdetails AS sdi WHERE sdi.sale_id=sales.id) AS iva")
        ->selectRaw("(SELECT 
            COALESCE(SUM(sd.fee), 0) + 
            COALESCE(SUM(CASE 
                WHEN p.name IN ('Cargo administrativo', 'CXS') THEN 
                    CASE 
                        WHEN (sd.pricesale + sd.nosujeta + sd.exempt) > 0 THEN 
                            (sd.pricesale + sd.nosujeta + sd.exempt) / 1.13
                        ELSE 0
                    END
                ELSE 0
            END), 0)
        FROM salesdetails AS sd 
        LEFT JOIN products AS p ON p.id = sd.product_id
        WHERE sd.sale_id = sales.id) AS fee")
        ->selectRaw("(SELECT 
            COALESCE(SUM(sd.feeiva), 0) + 
            COALESCE(SUM(CASE 
                WHEN p.name IN ('Cargo administrativo', 'CXS') THEN 
                    CASE 
                        WHEN (sd.pricesale + sd.nosujeta + sd.exempt) > 0 THEN 
                            ((sd.pricesale + sd.nosujeta + sd.exempt) / 1.13) * 0.13
                        ELSE 0
                    END
                ELSE 0
            END), 0)
        FROM salesdetails AS sd 
        LEFT JOIN products AS p ON p.id = sd.product_id
        WHERE sd.sale_id = sales.id) AS ivafee")
        ->where('sales.typedocument_id', "=", "6")
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
        $html .= '<tr><th colspan="14" style="text-align:center; font-weight:bold;">LIBRO DE VENTAS CONSUMIDOR</th></tr>';
        $html .= '<tr><td colspan="14" style="text-align:center;">';
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
        $html .= '<th>VENTA TOTAL</th>';
        $html .= '<th>NÚMERO CONTROL DTE</th>';
        $html .= '<th>CÓDIGO GENERACIÓN</th>';
        $html .= '<th>SELLO RECEPCIÓN</th>';
        $html .= '</tr>';

        // Datos
        $tot_exentas = 0;
        $tot_int_grav = 0;
        $tot_debfiscal = 0;
        $tot_nosujetas = 0;
        $tot_final = 0;
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
                $html .= '<td>ANULADO</td>';
            } else {
                $html .= '<td>' . strtoupper($sale['nombre_completo'] ?? '') . '</td>';
            }

            if($sale['typesale']=='0'){
                $html .= '<td>ANULADO</td>';
                $html .= '<td>ANULADO</td>';
                $html .= '<td>ANULADO</td>';
                $html .= '<td>ANULADO</td>';
                $html .= '<td>ANULADO</td>';
                $html .= '<td>ANULADO</td>';
                $html .= '<td>ANULADO</td>';
            } else {
                $fee = $sale['fee'] ?? 0;
                $ivafee = $sale['ivafee'] ?? 0;
                
                $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($sale['exenta'], 2, '.', '') . '</td>';
                $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($sale['nosujeta'], 2, '.', '') . '</td>';
                $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($sale['gravada'], 2, '.', '') . '</td>';
                $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($sale['iva'], 2, '.', '') . '</td>';
                $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($fee, 2, '.', '') . '</td>';
                $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($ivafee, 2, '.', '') . '</td>';
                $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($sale['totalamount'], 2, '.', '') . '</td>';

                $tot_exentas += $sale['exenta'];
                $tot_nosujetas += $sale['nosujeta'];
                $tot_int_grav += $sale['gravada'];
                $tot_debfiscal += $sale['iva'];
                $tot_fee += $fee;
                $tot_ivafee += $ivafee;
                $tot_final += $sale['totalamount'];
            }

            $html .= '<td>' . ($sale['numeroControl'] ?? '-') . '</td>';
            $html .= '<td>' . ($sale['codigoGeneracion'] ?? '-') . '</td>';
            $html .= '<td>' . ($sale['selloRecibido'] ?? '-') . '</td>';
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
        $html .= '<td style="mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($tot_final, 2, '.', '') . '</td>';
        $html .= '<td>-</td><td>-</td><td>-</td>';
        $html .= '</tr>';

        // Liquidación del débito fiscal
        $html .= '<tr><td colspan="14" style="text-align:center; font-weight:bold;"><br>LIQUIDACION DEL DEBITO FISCAL EN VENTAS DIRECTAS</td></tr>';

        $html .= '<tr>';
        $html .= '<td colspan="6" style="text-align:right; font-weight:bold;">GRAVADAS, NO SUJETAS, EXENTAS, SIN IVA</td>';
        $html .= '<td colspan="2" style="text-align:right; mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($tot_int_grav + $tot_exentas + $tot_nosujetas, 2, '.', '') . '</td>';
        $html .= '<td colspan="6"></td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<td colspan="3">VENTAS EXENTAS</td>';
        $html .= '<td style="text-align:right; mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($tot_exentas, 2, '.', '') . '</td>';
        $html .= '<td style="text-align:right;">13 %</td>';
        $html .= '<td style="text-align:right; mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($tot_debfiscal, 2, '.', '') . '</td>';
        $html .= '<td colspan="8"></td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<td colspan="3">VENTAS NO SUJETAS</td>';
        $html .= '<td style="text-align:right; mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($tot_nosujetas, 2, '.', '') . '</td>';
        $html .= '<td style="text-align:right;">0 %</td>';
        $html .= '<td style="text-align:right; mso-number-format:\'\#\,\#\#0\.00\';">0.00</td>';
        $html .= '<td colspan="8"></td>';
        $html .= '</tr>';

        $html .= '<tr style="font-weight:bold;">';
        $html .= '<td colspan="3">VENTA LOCALES GRAVADAS</td>';
        $html .= '<td style="text-align:right; mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($tot_int_grav, 2, '.', '') . '</td>';
        $html .= '<td style="text-align:right;">TOTAL</td>';
        $html .= '<td style="text-align:right; mso-number-format:\'\#\,\#\#0\.00\';">' . number_format($tot_int_grav + $tot_debfiscal, 2, '.', '') . '</td>';
        $html .= '<td colspan="8"></td>';
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
            // Se usa versión local del PDF para evitar dependencias remotas
            try {
                if (method_exists($saleController, 'genera_pdflocal')) {
                    $pdf = $saleController->genera_pdflocal($saleId);
                } else {
                    $pdf = $saleController->genera_pdf($saleId);
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
                if (method_exists($saleController, 'genera_pdflocal')) {
                    $pdf = $saleController->genera_pdflocal($saleId);
                } else {
                    $pdf = $saleController->genera_pdf($saleId);
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
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Report  $report
     * @return \Illuminate\Http\Response
     */
    public function destroy(Report $report)
    {
        //
    }
}
