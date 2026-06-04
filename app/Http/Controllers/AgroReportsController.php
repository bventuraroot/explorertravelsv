<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Sale;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AgroReportsController extends Controller
{
    public function salesByClient()
    {
        $companies = Company::select('id', 'name')->orderBy('name')->get();
        $firstCompany = $companies->first();

        return view('agro-reports.sales-by-client', [
            'companies'      => $companies,
            'firstCompanyId' => $firstCompany ? $firstCompany->id : null,
        ]);
    }

    public function salesByClientSearch(Request $request)
    {
        $Company   = Company::find($request['company']);
        $companies = Company::select('id', 'name')->orderBy('name')->get();

        // ── Consulta principal: agrupada solo por cliente ────────────────────────
        $salesByClient = Sale::join('clients', 'sales.client_id', '=', 'clients.id')
            ->select(
                'clients.id as client_id',
                'clients.tpersona',
                'clients.firstname',
                'clients.secondname',
                'clients.firstlastname',
                'clients.secondlastname',
                'clients.comercial_name',
                'clients.name_contribuyente',
                'clients.nit',
                'clients.ncr',
                'clients.email'
            )
            ->selectRaw('COUNT(sales.id) as total_sales')
            ->selectRaw('SUM(CASE WHEN sales.state = 1 THEN 1 ELSE 0 END) as completed_sales')
            ->selectRaw('SUM(CASE WHEN sales.state = 0 THEN 1 ELSE 0 END) as cancelled_sales')
            ->selectRaw('SUM(CASE WHEN sales.state = 1 THEN sales.totalamount ELSE 0 END) as total_amount')
            ->selectRaw('AVG(CASE WHEN sales.state = 1 THEN sales.totalamount ELSE NULL END) as average_amount')
            ->selectRaw('MIN(CASE WHEN sales.state = 1 THEN sales.date ELSE NULL END) as first_sale_date')
            ->selectRaw('MAX(CASE WHEN sales.state = 1 THEN sales.date ELSE NULL END) as last_sale_date')
            ->where('sales.company_id', $request['company'])
            ->when($request->filled('date_range'), function ($query) use ($request) {
                $dateRange = explode(' to ', $request['date_range']);
                if (count($dateRange) === 2) {
                    $query->whereBetween('sales.date', [trim($dateRange[0]), trim($dateRange[1])]);
                }
            })
            ->when(!$request->filled('date_range') && $request->filled('year'), function ($query) use ($request) {
                $query->whereRaw('YEAR(sales.date) = ?', [$request['year']]);
            })
            ->when(!$request->filled('date_range') && $request->filled('period'), function ($query) use ($request) {
                $query->whereRaw('MONTH(sales.date) = ?', [$request['period']]);
            })
            ->when($request->filled('client_id'), function ($query) use ($request) {
                $query->where('clients.id', $request['client_id']);
            })
            ->groupBy(
                'clients.id', 'clients.tpersona',
                'clients.firstname', 'clients.secondname', 'clients.firstlastname', 'clients.secondlastname',
                'clients.comercial_name', 'clients.name_contribuyente',
                'clients.nit', 'clients.ncr', 'clients.email'
            )
            ->orderBy('total_amount', 'desc')
            ->get();


        // ─── Enriquecer con métricas gerenciales ─────────────────────────────────
        $now = now();
        foreach ($salesByClient as $client) {
            $client->days_since_last = $client->last_sale_date
                ? $now->diffInDays(\Carbon\Carbon::parse($client->last_sale_date))
                : null;

            if ($client->first_sale_date && $client->last_sale_date && $client->completed_sales > 1) {
                $totalDays = \Carbon\Carbon::parse($client->first_sale_date)
                    ->diffInDays(\Carbon\Carbon::parse($client->last_sale_date));
                $client->purchase_frequency = $totalDays > 0
                    ? round($totalDays / ($client->completed_sales - 1))
                    : 0;
            } else {
                $client->purchase_frequency = null;
            }

            $client->classification = $this->classifyClient(
                (float) $client->total_amount,
                (int) $client->completed_sales,
                $client->days_since_last
            );
        }

        // ─── KPIs Globales ────────────────────────────────────────────────────────
        $globalKpis = [];
        if ($salesByClient->count() > 0) {
            $topClient = $salesByClient->first();
            $globalKpis = [
                'top_client_name'   => $topClient->tpersona === 'J'
                    ? $topClient->comercial_name
                    : trim($topClient->firstname . ' ' . $topClient->firstlastname),
                'top_client_amount' => $topClient->total_amount,
                'avg_ticket'        => $salesByClient->avg('average_amount') ?? 0,
                'avg_frequency'     => $salesByClient->whereNotNull('purchase_frequency')->avg('purchase_frequency') ?? 0,
                'inactive_count'    => $salesByClient->filter(fn($c) => ($c->days_since_last ?? 0) > 90)->count(),
                'vip_count'         => $salesByClient->filter(fn($c) => $c->classification === 'VIP')->count(),
                'total_clients'     => $salesByClient->count(),
                'total_amount'      => $salesByClient->sum('total_amount'),
            ];
        }

        // ─── Detalles de ventas para un cliente específico ─────────────────────
        $salesDetails = null;
        if ($request->filled('client_id') && $request->filled('show_details')) {
            $salesDetails = Sale::join('clients', 'sales.client_id', '=', 'clients.id')
                ->join('typedocuments', 'typedocuments.id', '=', 'sales.typedocument_id')
                ->join('salesdetails', 'sales.id', '=', 'salesdetails.sale_id')
                ->join('products', 'salesdetails.product_id', '=', 'products.id')
                ->select(
                    'sales.id as sale_id', 'sales.date', 'sales.totalamount', 'sales.state',
                    'typedocuments.description as document_type',
                    'products.name as product_name',
                    'salesdetails.amountp as quantity', 'salesdetails.pricesale',
                    'salesdetails.exempt', 'salesdetails.detained13'
                )
                ->selectRaw("DATE_FORMAT(sales.date, '%d/%m/%Y') AS formatted_date")
                ->selectRaw("CASE WHEN clients.tpersona = 'J' THEN clients.comercial_name ELSE CONCAT(clients.firstname, ' ', clients.firstlastname) END as client_name")
                ->where('clients.id', $request['client_id'])
                ->where('sales.company_id', $request['company'])
                ->where('sales.state', 1)
                ->when($request->filled('date_range'), function ($query) use ($request) {
                    $dateRange = explode(' to ', $request['date_range']);
                    if (count($dateRange) === 2) {
                        $query->whereBetween('sales.date', [trim($dateRange[0]), trim($dateRange[1])]);
                    }
                })
                ->when(!$request->filled('date_range') && $request->filled('year'), function ($query) use ($request) {
                    $query->whereRaw('YEAR(sales.date) = ?', [$request['year']]);
                })
                ->when(!$request->filled('date_range') && $request->filled('period'), function ($query) use ($request) {
                    $query->whereRaw('MONTH(sales.date) = ?', [$request['period']]);
                })
                ->orderBy('sales.date', 'desc')
                ->get();
        }

        // ─── Export Excel ─────────────────────────────────────────────────────────
        if ($request->filled('export_excel')) {
            $fileName = 'reporte_ventas_clientes_' . date('Ymd_His') . '.csv';
            $headers  = [
                'Content-type'        => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=$fileName",
                'Pragma'              => 'no-cache',
                'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
                'Expires'             => '0',
            ];
            $callback = function () use ($salesByClient) {
                $file = fopen('php://output', 'w');
                fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM
                fputcsv($file, ['#', 'Cliente', 'Tipo', 'NIT', 'Clasificación', 'Total Ventas', 'Completadas', 'Canceladas', 'Monto Total', 'Ticket Prom.', 'Días sin Comprar', 'Primera Compra', 'Última Compra']);
                $i = 1;
                foreach ($salesByClient as $c) {
                    $name = $c->tpersona === 'J' ? $c->comercial_name : ($c->firstname . ' ' . $c->firstlastname);
                    fputcsv($file, [
                        $i++, $name,
                        $c->tpersona === 'J' ? 'Jurídica' : 'Natural',
                        $c->nit ?? 'N/A',
                        $c->classification ?? 'N/A',
                        $c->total_sales,
                        $c->completed_sales,
                        $c->cancelled_sales,
                        number_format($c->total_amount, 2, '.', ''),
                        number_format($c->average_amount ?? 0, 2, '.', ''),
                        $c->days_since_last ?? 'N/A',
                        $c->first_sale_date ? date('d/m/Y', strtotime($c->first_sale_date)) : 'N/A',
                        $c->last_sale_date  ? date('d/m/Y', strtotime($c->last_sale_date))  : 'N/A',
                    ]);
                }
                fclose($file);
            };
            return response()->stream($callback, 200, $headers);
        }

        if ($request->filled('client_id') && $request->filled('show_details')) {
            return view('agro-reports.sales-by-client-details', [
                'heading'      => $Company,
                'yearB'        => $request['year'] ?? null,
                'period'       => $request['period'] ?? null,
                'date_range'   => $request['date_range'] ?? null,
                'client_id'    => $request['client_id'] ?? null,
                'companies'    => $companies,
                'salesDetails' => $salesDetails,
            ]);
        }

        return view('agro-reports.sales-by-client', [
            'heading'       => $Company,
            'yearB'         => $request['year'] ?? null,
            'period'        => $request['period'] ?? null,
            'date_range'    => $request['date_range'] ?? null,
            'client_id'     => $request['client_id'] ?? null,
            'client_name'   => $request->filled('client_id') ? $salesByClient->first()?->firstname ?? null : null,
            'companies'     => $companies,
            'salesByClient' => $salesByClient,
            'salesDetails'  => $salesDetails,
            'globalKpis'    => $globalKpis,
        ]);
    }

    private function classifyClient(float $totalAmount, int $completedSales, ?int $daysSinceLast): string
    {
        if ($daysSinceLast !== null && $daysSinceLast > 90) {
            return 'Inactivo';
        }
        if ($totalAmount >= 5000 || $completedSales >= 20) {
            return 'VIP';
        }
        if ($totalAmount >= 2000 || $completedSales >= 10) {
            return 'Frecuente';
        }
        return 'Ocasional';
    }

    public function salesByClientPdf(Request $request)
    {
        $request->validate(['company' => 'required|integer']);
        $Company = Company::findOrFail($request->input('company'));

        $salesByClient = Sale::join('clients', 'sales.client_id', '=', 'clients.id')
            ->select(
                'clients.id as client_id', 'clients.firstname', 'clients.firstlastname', 'clients.comercial_name', 'clients.tpersona', 'clients.nit', 'clients.email'
            )
            ->selectRaw('COUNT(sales.id) as total_sales')
            ->selectRaw('SUM(CASE WHEN sales.state = 1 THEN sales.totalamount ELSE 0 END) as total_amount')
            ->where('sales.company_id', $request->input('company'))
            ->where('sales.state', 1)
            ->when($request->filled('year'), function ($query) use ($request) { $query->whereRaw('YEAR(sales.date) = ?', [$request->input('year')]); })
            ->when($request->filled('period'), function ($query) use ($request) { $query->whereRaw('MONTH(sales.date) = ?', [$request->input('period')]); })
            ->when($request->filled('client_id'), function ($query) use ($request) { $query->where('clients.id', $request->input('client_id')); })
            ->groupBy('clients.id', 'clients.firstname', 'clients.firstlastname', 'clients.comercial_name', 'clients.tpersona', 'clients.nit', 'clients.email')
            ->orderBy('total_amount', 'desc')
            ->get();

        $pdf = app('dompdf.wrapper');
        $pdf->set_option('isHtml5ParserEnabled', true);
        $pdf->set_option('isRemoteEnabled', true);
        $pdf->loadView('agro-reports.sales-by-client-pdf', [
            'heading' => $Company,
            'yearB' => $request->input('year'),
            'period' => $request->input('period'),
            'salesByClient' => $salesByClient,
        ]);
        $pdf->setPaper('Letter', 'portrait');
        $fileName = 'reporte_ventas_por_clientes_' . date('Y-m-d_H-i-s') . '.pdf';
        if ($request->has('download') && $request->input('download') == '1') {
            return $pdf->download($fileName);
        }
        return $pdf->stream($fileName);
    }

    public function salesByClientDetailsPdf(Request $request)
    {
        $request->validate(['company' => 'required|integer', 'client_id' => 'required|integer']);
        $Company = Company::findOrFail($request->input('company'));

        $salesDetails = Sale::join('clients', 'sales.client_id', '=', 'clients.id')
            ->join('typedocuments', 'typedocuments.id', '=', 'sales.typedocument_id')
            ->join('salesdetails', 'sales.id', '=', 'salesdetails.sale_id')
            ->join('products', 'salesdetails.product_id', '=', 'products.id')
            ->select('sales.id as sale_id', 'sales.date', 'sales.totalamount', 'sales.state', 'typedocuments.description as document_type', 'products.name as product_name', 'salesdetails.amountp as quantity', 'salesdetails.pricesale', 'salesdetails.exempt', 'salesdetails.detained13')
            ->selectRaw("DATE_FORMAT(sales.date, '%d/%m/%Y') AS formatted_date")
            ->selectRaw("CASE WHEN clients.tpersona = 'J' THEN clients.comercial_name ELSE CONCAT(clients.firstname, ' ', clients.firstlastname) END as client_name")
            ->where('clients.id', $request->input('client_id'))
            ->where('sales.company_id', $request->input('company'))
            ->when($request->filled('year'), function ($query) use ($request) { $query->whereRaw('YEAR(sales.date) = ?', [$request->input('year')]); })
            ->when($request->filled('period'), function ($query) use ($request) { $query->whereRaw('MONTH(sales.date) = ?', [$request->input('period')]); })
            ->orderBy('sales.date', 'desc')
            ->get();

        $pdf = app('dompdf.wrapper');
        $pdf->set_option('isHtml5ParserEnabled', true);
        $pdf->set_option('isRemoteEnabled', true);
        $pdf->loadView('agro-reports.sales-by-client-details-pdf', [
            'heading' => $Company,
            'yearB' => $request->input('year'),
            'period' => $request->input('period'),
            'client_id' => $request->input('client_id'),
            'salesDetails' => $salesDetails,
        ]);
        $pdf->setPaper('Letter', 'portrait');
        $fileName = 'detalles_cliente_' . date('Y-m-d_H-i-s') . '.pdf';
        if ($request->has('download') && $request->input('download') == '1') {
            return $pdf->download($fileName);
        }
        return $pdf->stream($fileName);
    }
}
