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
    /**
     * Productos que representan fee/comisiones de la agencia (no venta operativa ni passthrough).
     * Alineado con la lógica de ReportsController: el resto del detalle cuenta como venta (incl. venta a terceros).
     *
     * @return list<string>
     */
    private static function productosNombreFeeLowercase(): array
    {
        return [
            'cargo administrativo',
            'cxs',
            'comisiones',
            'producto aereo',
            'producto aéreo',
            'comisiones producto aereo',
            'comisiones producto aéreo',
        ];
    }

    private function sqlSubtotalLinea(string $alias = 'sd'): string
    {
        return "({$alias}.pricesale + {$alias}.nosujeta + {$alias}.exempt)";
    }

    private function sqlLineaGravada(string $alias = 'sd'): string
    {
        return "({$alias}.pricesale > 0 AND ({$alias}.exempt = 0 OR {$alias}.exempt IS NULL) AND ({$alias}.nosujeta = 0 OR {$alias}.nosujeta IS NULL))";
    }

    /** Condición SQL: nombre de producto es uno de los de fee (comparación en minúsculas). */
    private function sqlEsNombreProductoFee(string $aliasProducto = 'p'): string
    {
        $escaped = array_map(static function (string $s): string {
            return "'" . str_replace(["\\", "'"], ["\\\\", "''"], $s) . "'";
        }, self::productosNombreFeeLowercase());

        return 'LOWER(TRIM(COALESCE(' . $aliasProducto . '.name, \'\'))) IN (' . implode(',', $escaped) . ')';
    }

    /**
     * @return array{ventas_operativas: float, fee_monto: float, fee_iva: float}
     */
    private function totalesVentasYFeeEnRango(Carbon $startDate, Carbon $endDate): array
    {
        $grav = $this->sqlLineaGravada('sd');
        $feeN = $this->sqlEsNombreProductoFee('p');
        $sub = $this->sqlSubtotalLinea('sd');

        $row = DB::table('salesdetails as sd')
            ->join('sales as s', 's.id', '=', 'sd.sale_id')
            ->leftJoin('products as p', 'p.id', '=', 'sd.product_id')
            ->whereBetween('s.date', [$startDate, $endDate])
            ->where('s.state', '<>', 0)
            ->selectRaw('
                COALESCE(SUM(CASE WHEN NOT (' . $feeN . ') THEN ' . $sub . ' ELSE 0 END), 0) as ventas_operativas,
                COALESCE(SUM(CASE WHEN ' . $grav . ' THEN sd.fee ELSE 0 END), 0)
                + COALESCE(SUM(CASE WHEN ' . $feeN . ' THEN ' . $sub . ' ELSE 0 END), 0) as fee_monto,
                COALESCE(SUM(CASE WHEN ' . $grav . ' THEN sd.feeiva ELSE 0 END), 0)
                + COALESCE(SUM(CASE WHEN ' . $grav . ' AND ' . $feeN . ' THEN sd.detained13 ELSE 0 END), 0) as fee_iva
            ')
            ->first();

        return [
            'ventas_operativas' => round((float) ($row->ventas_operativas ?? 0), 2),
            'fee_monto' => round((float) ($row->fee_monto ?? 0), 2),
            'fee_iva' => round((float) ($row->fee_iva ?? 0), 2),
        ];
    }

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

        $startOfMonth = $now->copy()->startOfMonth();
        $startOfWeek = $now->copy()->startOfWeek();

        // Totales: "ventas" = detalle sin líneas de productos fee; fee = columnas fee/feeiva + líneas de esos productos (como reportes)
        $totalesPeriodo = $this->totalesVentasYFeeEnRango($startDate, $endDate);
        $totalVentas = $totalesPeriodo['ventas_operativas'];
        $totalFees = $totalesPeriodo['fee_monto'];
        $totalFeesIva = $totalesPeriodo['fee_iva'];

        $inicioMes = $startDate->greaterThan($startOfMonth) ? $startDate : $startOfMonth;
        $totalVentasMes = $this->totalesVentasYFeeEnRango($inicioMes, $endDate)['ventas_operativas'];

        $inicioSemana = $startDate->greaterThan($startOfWeek) ? $startDate : $startOfWeek;
        $totalVentasSemana = $this->totalesVentasYFeeEnRango($inicioSemana, $endDate)['ventas_operativas'];

        $ventasMismoRangoAnioAnterior = $this->totalesVentasYFeeEnRango(
            $startDate->copy()->subYear(),
            $endDate->copy()->subYear()
        )['ventas_operativas'];
        $crecimientoVentas = 0.0;
        if ($ventasMismoRangoAnioAnterior > 0) {
            $crecimientoVentas = round((($totalVentas - $ventasMismoRangoAnioAnterior) / $ventasMismoRangoAnioAnterior) * 100, 2);
        }

        // Series por mes (últimos 12 meses) — ventas operativas por mes
        $ventasPorMes = collect(range(0, 11))
            ->map(function ($i) use ($now) {
                $monthStart = $now->copy()->subMonths(11 - $i)->startOfMonth();
                $monthEnd = $now->copy()->subMonths(11 - $i)->endOfMonth();
                $sum = $this->totalesVentasYFeeEnRango($monthStart, $monthEnd)['ventas_operativas'];

                return [
                    'mes' => $monthStart->format('Y-m'),
                    'total' => round($sum, 2),
                ];
            });

        // Series por día (últimos 30 días y última semana)
        $ventasPorDia30 = collect(range(0, 29))
            ->map(function ($i) use ($now) {
                $day = $now->copy()->subDays(29 - $i)->startOfDay();
                $sum = $this->totalesVentasYFeeEnRango($day, $day->copy()->endOfDay())['ventas_operativas'];

                return [
                    'dia' => $day->format('Y-m-d'),
                    'total' => round($sum, 2),
                ];
            });

        $ventasPorDia7 = collect(range(0, 6))
            ->map(function ($i) use ($now) {
                $day = $now->copy()->subDays(6 - $i)->startOfDay();
                $sum = $this->totalesVentasYFeeEnRango($day, $day->copy()->endOfDay())['ventas_operativas'];

                return [
                    'dia' => $day->format('Y-m-d'),
                    'total' => round($sum, 2),
                ];
            });

        // Top productos más vendidos (excluye líneas de fee/comisiones de agencia)
        $feeN = $this->sqlEsNombreProductoFee('products');
        $productosMasVendidos = Salesdetail::query()
            ->select('products.id', 'products.name', DB::raw('SUM(salesdetails.amountp) as cantidad_vendida'))
            ->join('sales', 'sales.id', '=', 'salesdetails.sale_id')
            ->join('products', 'products.id', '=', 'salesdetails.product_id')
            ->whereBetween('sales.date', [$startDate, $endDate])
            ->whereRaw('NOT (' . $feeN . ')')
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

        // Ventas por destino (id → tabla aeropuertos)
        $ventasPorDestino = $this->ventasPorDestinoConAeropuerto($startDate, $endDate);

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

        // Ventas por aerolínea (id → tabla aerolineas)
        $ventasPorAerolinea = $this->ventasPorAerolineaConCatalogo($startDate, $endDate);

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

        // Top clientes por ventas operativas (sin líneas de productos fee)
        $feeNCli = $this->sqlEsNombreProductoFee('p');
        $subCli = $this->sqlSubtotalLinea('sd');
        $ventasPorCliente = DB::table('salesdetails as sd')
            ->join('sales as s', 's.id', '=', 'sd.sale_id')
            ->leftJoin('products as p', 'p.id', '=', 'sd.product_id')
            ->whereBetween('s.date', [$startDate, $endDate])
            ->where('s.state', '<>', 0)
            ->whereNotNull('s.client_id')
            ->groupBy('s.client_id')
            ->selectRaw('s.client_id, SUM(CASE WHEN NOT (' . $feeNCli . ') THEN ' . $subCli . ' ELSE 0 END) as total')
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
     * Ventas agrupadas por destino: salesdetails.destino = aeropuertos.id_aeropuerto.
     *
     * @return \Illuminate\Support\Collection<int, array{label: string, total: float}>
     */
    private function ventasPorDestinoConAeropuerto(Carbon $startDate, Carbon $endDate): Collection
    {
        $destinoExpr = 'NULLIF(NULLIF(TRIM(sd.destino), ""), "0")';

        $feeN = $this->sqlEsNombreProductoFee('p');
        $sub = $this->sqlSubtotalLinea('sd');
        $lineAmountExpr = 'CASE WHEN NOT (' . $feeN . ') THEN ' . $sub . ' ELSE 0 END';

        // 1) Una fila por línea de detalle: clave normalizada + monto (sin GROUP BY).
        //    Así se evita 1055: el SUM no puede ir en la misma query que GROUP BY
        //    sobre expresión de sd.destino con referencias a p.name y sd.*.
        $porLinea = DB::table('salesdetails as sd')
            ->join('sales as s', 's.id', '=', 'sd.sale_id')
            ->leftJoin('products as p', 'p.id', '=', 'sd.product_id')
            ->whereBetween('s.date', [$startDate, $endDate])
            ->where('s.state', '<>', 0)
            ->selectRaw($destinoExpr . ' as destino_key')
            ->selectRaw($lineAmountExpr . ' as line_amount');

        // 2) Agregación solo por destino_key (columna del subresultado).
        $aggregated = DB::query()
            ->fromSub($porLinea, 'pl')
            ->select('pl.destino_key')
            ->selectRaw('SUM(pl.line_amount) as total')
            ->groupBy('pl.destino_key');

        $rows = DB::query()
            ->fromSub($aggregated, 'agg')
            ->leftJoin('aeropuertos as ap', function ($join) {
                $join->whereRaw('ap.id_aeropuerto = CAST(agg.destino_key AS UNSIGNED)');
            })
            ->select('agg.destino_key', 'agg.total', 'ap.iata as ap_iata', 'ap.ciudad as ap_ciudad', 'ap.pais as ap_pais')
            ->get();

        return $rows
            ->map(function ($row) {
                $key = $row->destino_key;
                $total = round((float) ($row->total ?? 0), 2);
                if ($key === null || $key === '' || (string) $key === '0') {
                    return ['label' => 'Sin destino', 'total' => $total];
                }
                $parts = array_filter([
                    $row->ap_iata !== null ? (string) $row->ap_iata : null,
                    $row->ap_ciudad !== null ? (string) $row->ap_ciudad : null,
                    $row->ap_pais !== null ? (string) $row->ap_pais : null,
                ]);
                if ($parts !== []) {
                    $label = trim(implode(' - ', $parts));

                    return ['label' => Str::limit($label !== '' ? $label : 'Aeropuerto #' . $key, 64), 'total' => $total];
                }

                return ['label' => 'Destino #' . $key, 'total' => $total];
            })
            ->sortByDesc('total')
            ->values()
            ->take(10);
    }

    /**
     * Ventas agrupadas por línea aérea: salesdetails.linea = aerolineas.id_aerolinea.
     *
     * @return \Illuminate\Support\Collection<int, array{label: string, total: float}>
     */
    private function ventasPorAerolineaConCatalogo(Carbon $startDate, Carbon $endDate): Collection
    {
        $lineaExpr = 'NULLIF(NULLIF(TRIM(sd.linea), ""), "0")';

        $feeN = $this->sqlEsNombreProductoFee('p');
        $sub = $this->sqlSubtotalLinea('sd');
        $lineAmountExpr = 'CASE WHEN NOT (' . $feeN . ') THEN ' . $sub . ' ELSE 0 END';

        $porLinea = DB::table('salesdetails as sd')
            ->join('sales as s', 's.id', '=', 'sd.sale_id')
            ->leftJoin('products as p', 'p.id', '=', 'sd.product_id')
            ->whereBetween('s.date', [$startDate, $endDate])
            ->where('s.state', '<>', 0)
            ->selectRaw($lineaExpr . ' as linea_key')
            ->selectRaw($lineAmountExpr . ' as line_amount');

        $aggregated = DB::query()
            ->fromSub($porLinea, 'pl')
            ->select('pl.linea_key')
            ->selectRaw('SUM(pl.line_amount) as total')
            ->groupBy('pl.linea_key');

        $rows = DB::query()
            ->fromSub($aggregated, 'agg')
            ->leftJoin('aerolineas as al', function ($join) {
                $join->whereRaw('al.id_aerolinea = CAST(agg.linea_key AS UNSIGNED)');
            })
            ->select('agg.linea_key', 'agg.total', 'al.iata as al_iata', 'al.nombre as al_nombre')
            ->get();

        return $rows
            ->map(function ($row) {
                $key = $row->linea_key;
                $total = round((float) ($row->total ?? 0), 2);
                if ($key === null || $key === '' || (string) $key === '0') {
                    return ['label' => 'Sin aerolínea', 'total' => $total];
                }
                $parts = array_filter([
                    $row->al_iata !== null ? (string) $row->al_iata : null,
                    $row->al_nombre !== null ? (string) $row->al_nombre : null,
                ]);
                if ($parts !== []) {
                    $label = trim(implode(' - ', $parts));

                    return ['label' => Str::limit($label !== '' ? $label : 'Aerolínea #' . $key, 52), 'total' => $total];
                }

                return ['label' => 'Aerolínea #' . $key, 'total' => $total];
            })
            ->sortByDesc('total')
            ->values()
            ->take(10);
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
        $feeN = $this->sqlEsNombreProductoFee('p');
        $sub = '(salesdetails.pricesale + salesdetails.nosujeta + salesdetails.exempt)';

        $rows = DB::table('salesdetails')
            ->join('sales', 'sales.id', '=', 'salesdetails.sale_id')
            ->leftJoin('products as p', 'p.id', '=', 'salesdetails.product_id')
            ->whereBetween('sales.date', [$startDate, $endDate])
            ->where('sales.state', '<>', 0)
            ->selectRaw($groupColumn . ' as grp_key')
            ->selectRaw('SUM(CASE WHEN NOT (' . $feeN . ') THEN ' . $sub . ' ELSE 0 END) as total')
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
