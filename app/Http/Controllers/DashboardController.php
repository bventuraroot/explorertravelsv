<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Product;
use App\Models\Provider;
use App\Models\Sale;
use App\Models\Salesdetail;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    public function home(Request $request)
    {
        $user = auth()->user();
        $now = Carbon::now();

        // Procesar filtros de fecha
        $filterType = $request->input('filter_type', 'all'); // all, day, month, year, custom
        $filterDate = $request->input('filter_date', $now->format('Y-m-d'));
        $filterMonth = $request->input('filter_month', $now->format('Y-m'));
        $filterYear = $request->input('filter_year', $now->format('Y'));
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        // Determinar rango de fechas según el filtro
        switch ($filterType) {
            case 'day':
                $startDate = Carbon::parse($filterDate)->startOfDay();
                $endDate = Carbon::parse($filterDate)->endOfDay();
                break;
            case 'month':
                $startDate = Carbon::parse($filterMonth . '-01')->startOfMonth();
                $endDate = Carbon::parse($filterMonth . '-01')->endOfMonth();
                break;
            case 'year':
                $startDate = Carbon::parse($filterYear . '-01-01')->startOfYear();
                $endDate = Carbon::parse($filterYear . '-01-01')->endOfYear();
                break;
            case 'custom':
                if ($dateFrom && $dateTo) {
                    $startDate = Carbon::parse($dateFrom)->startOfDay();
                    $endDate = Carbon::parse($dateTo)->endOfDay();
                } else {
                    // Si no hay fechas personalizadas, usar el último año
                    $startDate = $now->copy()->subYear()->startOfDay();
                    $endDate = $now->copy()->endOfDay();
                }
                break;
            default: // 'all'
                $startDate = $now->copy()->subYear()->startOfDay();
                $endDate = $now->copy()->endOfDay();
                break;
        }

        // Contadores generales (sin filtro)
        $tclientes = Client::count();
        $tproviders = Provider::count();
        $tproducts = Product::count();
        $tsales = Sale::count();

        // Rangos de tiempo para comparación
        $startOfYearWindow = $now->copy()->subYear()->startOfDay();
        $startOfPrevYearWindow = $now->copy()->subYears(2)->startOfDay();
        $endOfPrevYearWindow = $now->copy()->subYear()->endOfDay();
        $startOfMonth = $now->copy()->startOfMonth();
        $startOfWeek = $now->copy()->startOfWeek();

        // Totales por ventanas con filtro aplicado
        $totalVentas = (float) (Sale::whereBetween('date', [$startDate, $endDate])->sum('totalamount') ?? 0);
        $totalVentasMes = (float) (Sale::whereDate('date', '>=', $startOfMonth)->whereBetween('date', [$startDate, $endDate])->sum('totalamount') ?? 0);
        $totalVentasSemana = (float) (Sale::whereDate('date', '>=', $startOfWeek)->whereBetween('date', [$startDate, $endDate])->sum('totalamount') ?? 0);

        // Calcular total de fees con filtro aplicado
        $totalFees = (float) (Salesdetail::join('sales', 'sales.id', '=', 'salesdetails.sale_id')
            ->whereBetween('sales.date', [$startDate, $endDate])
            ->sum('salesdetails.fee') ?? 0);

        // Calcular total de feeiva con filtro aplicado
        $totalFeesIva = (float) (Salesdetail::join('sales', 'sales.id', '=', 'salesdetails.sale_id')
            ->whereBetween('sales.date', [$startDate, $endDate])
            ->sum('salesdetails.feeiva') ?? 0);

        $ventasPrevioAnio = (float) (Sale::whereBetween('date', [$startOfPrevYearWindow, $endOfPrevYearWindow])->sum('totalamount') ?? 0);
        $crecimientoVentas = 0.0;
        if ($ventasPrevioAnio > 0) {
            $crecimientoVentas = round((($totalVentas - $ventasPrevioAnio) / $ventasPrevioAnio) * 100, 2);
        }

        // Series por mes (últimos 12 meses)
        $ventasPorMes = collect(range(0, 11))
            ->map(function ($i) use ($now) {
                $monthStart = $now->copy()->subMonths(11 - $i)->startOfMonth();
                $monthEnd = $now->copy()->subMonths(11 - $i)->endOfMonth();
                $sum = (float) (Sale::whereBetween('date', [$monthStart, $monthEnd])->sum('totalamount') ?? 0);
                return [
                    'mes' => $monthStart->format('Y-m'),
                    'total' => round($sum, 2)
                ];
            });

        // Series por día (últimos 30 días y última semana)
        $ventasPorDia30 = collect(range(0, 29))
            ->map(function ($i) use ($now) {
                $day = $now->copy()->subDays(29 - $i);
                $sum = (float) (Sale::whereDate('date', $day->toDateString())->sum('totalamount') ?? 0);
                return [
                    'dia' => $day->format('Y-m-d'),
                    'total' => round($sum, 2)
                ];
            });

        $ventasPorDia7 = collect(range(0, 6))
            ->map(function ($i) use ($now) {
                $day = $now->copy()->subDays(6 - $i);
                $sum = (float) (Sale::whereDate('date', $day->toDateString())->sum('totalamount') ?? 0);
                return [
                    'dia' => $day->format('Y-m-d'),
                    'total' => round($sum, 2)
                ];
            });

        // Top productos más vendidos (por cantidad) con filtro aplicado
        $productosMasVendidos = Salesdetail::query()
            ->select('products.id', 'products.name', DB::raw('SUM(salesdetails.amountp) as cantidad_vendida'))
            ->join('sales', 'sales.id', '=', 'salesdetails.sale_id')
            ->join('products', 'products.id', '=', 'salesdetails.product_id')
            ->whereBetween('sales.date', [$startDate, $endDate])
            ->groupBy('products.id', 'products.name')
            ->orderByDesc(DB::raw('SUM(salesdetails.amountp)'))
            ->limit(5)
            ->get();

        // Ventas por proveedor (línea del detalle)
        $ventasPorProveedor = $this->ventasAgrupadasPorDetalle(
            $startDate,
            $endDate,
            'salesdetails.line_provider_id',
            function ($raw) {
                if ($raw === null || $raw === '') {
                    return 'Sin proveedor en línea';
                }
                $pid = (int) $raw;
                $p = Provider::find($pid);

                return $p ? Str::limit($p->razonsocial, 42) : 'Proveedor #' . $pid;
            }
        );

        // Ventas por destino
        $ventasPorDestino = $this->ventasAgrupadasPorDetalle(
            $startDate,
            $endDate,
            'salesdetails.destino',
            function ($raw) {
                $s = is_string($raw) ? trim($raw) : '';

                return $s !== '' ? Str::limit($s, 48) : 'Sin destino';
            }
        );

        // Ventas por ruta (trayecto / códigos; útil cuando se registran segmentos o aeropuertos en ruta)
        $ventasPorRuta = $this->ventasAgrupadasPorDetalle(
            $startDate,
            $endDate,
            'salesdetails.ruta',
            function ($raw) {
                $s = is_string($raw) ? trim($raw) : '';

                return $s !== '' ? Str::limit($s, 48) : 'Sin ruta';
            }
        );

        // Ventas por aerolínea (campo "línea" en el detalle)
        $ventasPorAerolinea = $this->ventasAgrupadasPorDetalle(
            $startDate,
            $endDate,
            'salesdetails.linea',
            function ($raw) {
                $s = is_string($raw) ? trim($raw) : '';

                return $s !== '' ? Str::limit($s, 42) : 'Sin aerolínea';
            }
        );

        // Ventas por canal (si se usa en el detalle)
        $ventasPorCanal = $this->ventasAgrupadasPorDetalle(
            $startDate,
            $endDate,
            'salesdetails.canal',
            function ($raw) {
                $s = is_string($raw) ? trim($raw) : '';

                return $s !== '' ? Str::limit($s, 36) : 'Sin canal';
            }
        );

        // Top clientes por monto de venta (cabecera)
        $ventasPorCliente = Sale::query()
            ->whereBetween('date', [$startDate, $endDate])
            ->where('state', '<>', 0)
            ->whereNotNull('client_id')
            ->selectRaw('client_id, SUM(totalamount) as total')
            ->groupBy('client_id')
            ->orderByDesc('total')
            ->limit(6)
            ->get();

        $clienteIds = $ventasPorCliente->pluck('client_id')->filter()->unique()->values();
        $clientesMap = $clienteIds->isNotEmpty()
            ? Client::whereIn('id', $clienteIds)->get()->keyBy('id')
            : collect();

        $ventasPorCliente = $ventasPorCliente->map(function ($row) use ($clientesMap) {
            $c = $clientesMap->get($row->client_id);
            $nombre = 'Cliente #' . $row->client_id;
            if ($c) {
                if (($c->tpersona ?? '') === 'J') {
                    $nombre = Str::limit(trim((string) ($c->name_contribuyente ?: $c->comercial_name ?: $nombre)), 48);
                } else {
                    $nombre = Str::limit(trim(implode(' ', array_filter([
                        $c->firstname,
                        $c->secondname,
                        $c->firtslastname,
                        $c->secondlastname,
                    ]))), 48) ?: $nombre;
                }
            }

            return [
                'label' => $nombre,
                'total' => round((float) $row->total, 2),
            ];
        })->values();

        // Estructuras esperadas por la vista/JS
        $ventasUltimoAno = $ventasPorMes; // alias esperado por JS
        $ventasUltimoMes = $ventasPorDia30; // alias esperado por JS
        $ventasUltimaSemana = $ventasPorDia7; // alias esperado por JS

        // También se hace alias a ventasPorMes y ventasPorDia por compatibilidad
        $ventasPorDia = $ventasPorDia7;

        return view('reports.dashboard')
            ->with('tclientes', $tclientes)
            ->with('tproviders', $tproviders)
            ->with('tproducts', $tproducts)
            ->with('tsales', $tsales)
            ->with('totalVentas', round($totalVentas, 2))
            ->with('totalVentasMes', round($totalVentasMes, 2))
            ->with('totalVentasSemana', round($totalVentasSemana, 2))
            ->with('totalFees', round($totalFees, 2))
            ->with('totalFeesIva', round($totalFeesIva, 2))
            ->with('crecimientoVentas', $crecimientoVentas)
            ->with('ventasUltimoAno', $ventasUltimoAno)
            ->with('ventasUltimoMes', $ventasUltimoMes)
            ->with('ventasUltimaSemana', $ventasUltimaSemana)
            ->with('ventasPorMes', $ventasPorMes)
            ->with('ventasPorDia', $ventasPorDia)
            ->with('productosMasVendidos', $productosMasVendidos)
            ->with('ventasPorProveedor', $ventasPorProveedor)
            ->with('ventasPorDestino', $ventasPorDestino)
            ->with('ventasPorRuta', $ventasPorRuta)
            ->with('ventasPorAerolinea', $ventasPorAerolinea)
            ->with('ventasPorCanal', $ventasPorCanal)
            ->with('ventasPorCliente', $ventasPorCliente)
            ->with('filterType', $filterType)
            ->with('filterDate', $filterDate)
            ->with('filterMonth', $filterMonth)
            ->with('filterYear', $filterYear)
            ->with('dateFrom', $dateFrom)
            ->with('dateTo', $dateTo)
            ->with('startDate', $startDate->format('d/m/Y'))
            ->with('endDate', $endDate->format('d/m/Y'));
    }

    /**
     * Suma de ventas por línea de detalle agrupada por columna (excluye ventas anuladas state=0).
     *
     * @param  callable(string|null): string  $labelResolver
     * @return \Illuminate\Support\Collection<int, array{label: string, total: float}>
     */
    private function ventasAgrupadasPorDetalle(
        Carbon $startDate,
        Carbon $endDate,
        string $groupColumn,
        callable $labelResolver
    ): Collection {
        $rows = DB::table('salesdetails')
            ->join('sales', 'sales.id', '=', 'salesdetails.sale_id')
            ->whereBetween('sales.date', [$startDate, $endDate])
            ->where('sales.state', '<>', 0)
            ->selectRaw($groupColumn . ' as grp_key')
            ->selectRaw('SUM(salesdetails.pricesale + salesdetails.nosujeta + salesdetails.exempt) as total')
            ->groupBy(DB::raw($groupColumn))
            ->get();

        $merged = [];
        foreach ($rows as $row) {
            $keyRaw = $row->grp_key;
            $label = $labelResolver($keyRaw);
            $amount = (float) ($row->total ?? 0);
            if (! isset($merged[$label])) {
                $merged[$label] = 0.0;
            }
            $merged[$label] += $amount;
        }

        return collect($merged)
            ->map(fn ($total, $label) => ['label' => $label, 'total' => round($total, 2)])
            ->sortByDesc('total')
            ->values()
            ->take(10);
    }
}
