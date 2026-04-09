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
     * FEE estricto: solo cargo administrativo y CXS.
     *
     * @return list<string>
     */
    private static function productosSoloFeeLowercase(): array
    {
        return [
            'cargo administrativo',
            'cxs',
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

    /** @param  list<string>  $nombresLowercase */
    private function sqlNombreProductoInList(array $nombresLowercase, string $aliasProducto = 'p'): string
    {
        $escaped = array_map(static function (string $s): string {
            return "'" . str_replace(["\\", "'"], ["\\\\", "''"], $s) . "'";
        }, $nombresLowercase);

        return 'LOWER(TRIM(COALESCE(' . $aliasProducto . '.name, \'\'))) IN (' . implode(',', $escaped) . ')';
    }

    private function sqlEsSoloProductoFee(string $alias = 'p'): string
    {
        return $this->sqlNombreProductoInList(self::productosSoloFeeLowercase(), $alias);
    }

    /**
     * Comisiones: cualquier producto cuyo nombre contenga «comision» (con o sin tilde, insensible a mayúsculas).
     * Captura «comisiones», «comisiones producto aéreo», «comision aérea», etc.
     */
    private function sqlEsProductoComisiones(string $alias = 'p'): string
    {
        return "(LOWER(TRIM(COALESCE({$alias}.name, ''))) LIKE '%comision%')";
    }

    /** Ventas a terceros: todo lo que NO es FEE (admin/CXS) ni Comisiones. */
    private function sqlEsVentaATercerosLinea(string $alias = 'p'): string
    {
        return '(NOT (' . $this->sqlEsSoloProductoFee($alias) . ') AND NOT (' . $this->sqlEsProductoComisiones($alias) . '))';
    }

    /**
     * @return array{
     *   ventas_operativas: float,
     *   fee_monto: float,
     *   fee_iva: float,
     *   comisiones_monto: float,
     *   comisiones_iva: float
     * }
     */
    private function totalesVentasYFeeEnRango(Carbon $startDate, Carbon $endDate): array
    {
        $grav  = $this->sqlLineaGravada('sd');
        $soloF = $this->sqlEsSoloProductoFee('p');
        $com   = $this->sqlEsProductoComisiones('p');
        $vTer  = $this->sqlEsVentaATercerosLinea('p');
        $sub   = $this->sqlSubtotalLinea('sd');

        $row = DB::table('salesdetails as sd')
            ->join('sales as s', 's.id', '=', 'sd.sale_id')
            ->leftJoin('products as p', 'p.id', '=', 'sd.product_id')
            ->whereBetween('s.date', [$startDate, $endDate])
            ->where('s.state', '<>', 0)
            ->selectRaw('
                COALESCE(SUM(CASE WHEN ' . $vTer . ' THEN ' . $sub . ' ELSE 0 END), 0) as ventas_operativas,
                COALESCE(SUM(CASE WHEN ' . $soloF . ' THEN ' . $sub . ' ELSE 0 END), 0)
                + COALESCE(SUM(CASE WHEN ' . $grav . ' AND ' . $soloF . ' THEN sd.fee ELSE 0 END), 0) as fee_monto,
                COALESCE(SUM(CASE WHEN ' . $grav . ' AND ' . $soloF . ' THEN sd.feeiva ELSE 0 END), 0)
                + COALESCE(SUM(CASE WHEN ' . $grav . ' AND ' . $soloF . ' THEN sd.detained13 ELSE 0 END), 0) as fee_iva,
                COALESCE(SUM(CASE WHEN ' . $com . ' THEN ' . $sub . ' ELSE 0 END), 0)
                + COALESCE(SUM(CASE WHEN ' . $grav . ' AND ' . $com . ' THEN sd.fee ELSE 0 END), 0) as comisiones_monto,
                COALESCE(SUM(CASE WHEN ' . $grav . ' AND ' . $com . ' THEN sd.feeiva ELSE 0 END), 0)
                + COALESCE(SUM(CASE WHEN ' . $grav . ' AND ' . $com . ' THEN sd.detained13 ELSE 0 END), 0) as comisiones_iva
            ')
            ->first();

        return [
            'ventas_operativas' => round((float) ($row->ventas_operativas ?? 0), 2),
            'fee_monto'         => round((float) ($row->fee_monto ?? 0), 2),
            'fee_iva'           => round((float) ($row->fee_iva ?? 0), 2),
            'comisiones_monto'  => round((float) ($row->comisiones_monto ?? 0), 2),
            'comisiones_iva'    => round((float) ($row->comisiones_iva ?? 0), 2),
        ];
    }

    public function home(Request $request)
    {
        $user = auth()->user();
        $now = Carbon::now();

        // Procesar filtros de fecha (por defecto: mes en curso)
        $filterType = $request->input('filter_type', 'month'); // all, day, month, year, custom
        $filterDate = $request->input('filter_date', $now->format('Y-m-d'));
        $filterYear = $request->input('filter_year', $now->format('Y'));
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        if ($request->has('filter_month_year') || $request->has('filter_month_num')) {
            $y = (int) $request->input('filter_month_year', $now->year);
            $m = (int) $request->input('filter_month_num', $now->month);
            if ($y < 2000 || $y > 2100) {
                $y = (int) $now->year;
            }
            if ($m < 1 || $m > 12) {
                $m = (int) $now->month;
            }
            $filterMonth = sprintf('%04d-%02d', $y, $m);
        } elseif ($request->filled('filter_month')) {
            try {
                $filterMonth = Carbon::createFromFormat('Y-m', $request->input('filter_month'))->format('Y-m');
            } catch (\Throwable $e) {
                $filterMonth = $now->format('Y-m');
            }
        } else {
            $filterMonth = $now->format('Y-m');
        }

        $filterMonthParts = explode('-', $filterMonth);
        $filterMonthYear = isset($filterMonthParts[0]) ? max(2000, min(2100, (int) $filterMonthParts[0])) : (int) $now->year;
        $filterMonthNum = isset($filterMonthParts[1])
            ? str_pad((string) max(1, min(12, (int) $filterMonthParts[1])), 2, '0', STR_PAD_LEFT)
            : $now->format('m');
        $yearsForMonthFilter = range((int) $now->year - 8, (int) $now->year + 2);
        if (! in_array($filterMonthYear, $yearsForMonthFilter, true)) {
            $yearsForMonthFilter[] = $filterMonthYear;
            sort($yearsForMonthFilter);
        }

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

        // Ventas a terceros | FEE (admin+CXS) | Comisiones (cualquier producto con «comision» en nombre)
        $totalesPeriodo = $this->totalesVentasYFeeEnRango($startDate, $endDate);
        $totalVentas        = $totalesPeriodo['ventas_operativas'];
        $totalFees          = $totalesPeriodo['fee_monto'];
        $totalFeesIva       = $totalesPeriodo['fee_iva'];
        $totalComisiones    = $totalesPeriodo['comisiones_monto'];
        $totalComisionesIva = $totalesPeriodo['comisiones_iva'];

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

        // Top productos más vendidos (solo ventas a terceros: sin FEE ni ingreso empresa)
        $vTerProd = $this->sqlEsVentaATercerosLinea('products');
        $productosMasVendidos = Salesdetail::query()
            ->select('products.id', 'products.name', DB::raw('SUM(salesdetails.amountp) as cantidad_vendida'))
            ->join('sales', 'sales.id', '=', 'salesdetails.sale_id')
            ->join('products', 'products.id', '=', 'salesdetails.product_id')
            ->whereBetween('sales.date', [$startDate, $endDate])
            ->whereRaw('(' . $vTerProd . ')')
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

        // FEE + Comisiones: desglose por destino / aerolínea
        $feePorDestino          = $this->feePorDestinoConAeropuerto($startDate, $endDate);
        $feePorAerolinea        = $this->feePorAerolineaConCatalogo($startDate, $endDate);
        $comisionesPorDestino   = $this->comisionesPorDestinoConAeropuerto($startDate, $endDate);
        $comisionesPorAerolinea = $this->comisionesPorAerolineaConCatalogo($startDate, $endDate);

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

        // Top clientes por ventas a terceros
        $vTerCli = $this->sqlEsVentaATercerosLinea('p');
        $subCli = $this->sqlSubtotalLinea('sd');
        $ventasPorCliente = DB::table('salesdetails as sd')
            ->join('sales as s', 's.id', '=', 'sd.sale_id')
            ->leftJoin('products as p', 'p.id', '=', 'sd.product_id')
            ->whereBetween('s.date', [$startDate, $endDate])
            ->where('s.state', '<>', 0)
            ->whereNotNull('s.client_id')
            ->groupBy('s.client_id')
            ->selectRaw('s.client_id, SUM(CASE WHEN (' . $vTerCli . ') THEN ' . $subCli . ' ELSE 0 END) as total')
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
            ->with('totalComisiones', round($totalComisiones, 2))
            ->with('totalComisionesIva', round($totalComisionesIva, 2))
            ->with('feePorDestino', $feePorDestino)
            ->with('feePorAerolinea', $feePorAerolinea)
            ->with('comisionesPorDestino', $comisionesPorDestino)
            ->with('comisionesPorAerolinea', $comisionesPorAerolinea)
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
            ->with('filterMonthYear', $filterMonthYear)
            ->with('filterMonthNum', $filterMonthNum)
            ->with('yearsForMonthFilter', $yearsForMonthFilter)
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

        $vTer = $this->sqlEsVentaATercerosLinea('p');
        $sub = $this->sqlSubtotalLinea('sd');
        $lineAmountExpr = 'CASE WHEN (' . $vTer . ') THEN ' . $sub . ' ELSE 0 END';

        return $this->agregarPorClaveDestinoOAerolinea(
            $startDate,
            $endDate,
            $destinoExpr,
            'destino_key',
            'aeropuertos',
            'id_aeropuerto',
            ['iata' => 'ap_iata', 'ciudad' => 'ap_ciudad', 'pais' => 'ap_pais'],
            $lineAmountExpr,
            'Aeropuerto',
            'Destino'
        );
    }

    /**
     * Monto por línea de comisiones (subtotal + columna fee en línea gravada).
     */
    private function sqlMontoComisionesPorLinea(): string
    {
        $grav = $this->sqlLineaGravada('sd');
        $com  = $this->sqlEsProductoComisiones('p');
        $sub  = $this->sqlSubtotalLinea('sd');

        return '(CASE WHEN ' . $com . ' THEN ' . $sub . ' ELSE 0 END + CASE WHEN ' . $grav . ' AND ' . $com . ' THEN COALESCE(sd.fee, 0) ELSE 0 END)';
    }

    /**
     * Monto por línea FEE (cargo administrativo + CXS), alineado con totales de FEE.
     */
    private function sqlMontoSoloFeePorLinea(): string
    {
        $grav  = $this->sqlLineaGravada('sd');
        $soloF = $this->sqlEsSoloProductoFee('p');
        $sub   = $this->sqlSubtotalLinea('sd');

        return '(CASE WHEN ' . $soloF . ' THEN ' . $sub . ' ELSE 0 END + CASE WHEN ' . $grav . ' AND ' . $soloF . ' THEN COALESCE(sd.fee, 0) ELSE 0 END)';
    }

    /**
     * FEE por destino (productos admin. + CXS).
     *
     * @return \Illuminate\Support\Collection<int, array{label: string, total: float}>
     */
    private function feePorDestinoConAeropuerto(Carbon $startDate, Carbon $endDate): Collection
    {
        $destinoExpr    = 'NULLIF(NULLIF(TRIM(sd.destino), ""), "0")';
        $lineAmountExpr = $this->sqlMontoSoloFeePorLinea();

        return $this->agregarPorClaveDestinoOAerolinea(
            $startDate,
            $endDate,
            $destinoExpr,
            'destino_key',
            'aeropuertos',
            'id_aeropuerto',
            ['iata' => 'ap_iata', 'ciudad' => 'ap_ciudad', 'pais' => 'ap_pais'],
            $lineAmountExpr,
            'Aeropuerto',
            'Destino'
        );
    }

    /**
     * Comisiones por destino.
     *
     * @return \Illuminate\Support\Collection<int, array{label: string, total: float}>
     */
    private function comisionesPorDestinoConAeropuerto(Carbon $startDate, Carbon $endDate): Collection
    {
        $destinoExpr    = 'NULLIF(NULLIF(TRIM(sd.destino), ""), "0")';
        $lineAmountExpr = $this->sqlMontoComisionesPorLinea();

        return $this->agregarPorClaveDestinoOAerolinea(
            $startDate,
            $endDate,
            $destinoExpr,
            'destino_key',
            'aeropuertos',
            'id_aeropuerto',
            ['iata' => 'ap_iata', 'ciudad' => 'ap_ciudad', 'pais' => 'ap_pais'],
            $lineAmountExpr,
            'Aeropuerto',
            'Destino'
        );
    }

    /**
     * @param  array<string, string>  $joinSelectMap  keys: ap fields -> alias
     * @return \Illuminate\Support\Collection<int, array{label: string, total: float}>
     */
    private function agregarPorClaveDestinoOAerolinea(
        Carbon $startDate,
        Carbon $endDate,
        string $keyExpr,
        string $keyAlias,
        string $catalogTable,
        string $catalogIdColumn,
        array $joinSelectMap,
        string $lineAmountExpr,
        string $entityLabelWhenResolved,
        string $fallbackPrefix
    ): Collection {
        $porLinea = DB::table('salesdetails as sd')
            ->join('sales as s', 's.id', '=', 'sd.sale_id')
            ->leftJoin('products as p', 'p.id', '=', 'sd.product_id')
            ->whereBetween('s.date', [$startDate, $endDate])
            ->where('s.state', '<>', 0)
            ->selectRaw($keyExpr . ' as ' . $keyAlias)
            ->selectRaw($lineAmountExpr . ' as line_amount');

        $aggregated = DB::query()
            ->fromSub($porLinea, 'pl')
            ->select('pl.' . $keyAlias)
            ->selectRaw('SUM(pl.line_amount) as total')
            ->groupBy('pl.' . $keyAlias);

        $joinAlias = $catalogTable === 'aeropuertos' ? 'ap' : 'al';

        $q = DB::query()
            ->fromSub($aggregated, 'agg')
            ->leftJoin($catalogTable . ' as ' . $joinAlias, function ($join) use ($catalogIdColumn, $keyAlias, $joinAlias) {
                $join->whereRaw($joinAlias . '.' . $catalogIdColumn . ' = CAST(agg.' . $keyAlias . ' AS UNSIGNED)');
            });

        $select = ['agg.' . $keyAlias, 'agg.total'];
        foreach ($joinSelectMap as $col => $as) {
            $select[] = $joinAlias . '.' . $col . ' as ' . $as;
        }
        $rows = $q->select($select)->get();

        return $rows
            ->map(function ($row) use ($keyAlias, $joinSelectMap, $entityLabelWhenResolved, $fallbackPrefix) {
                $key = $row->{$keyAlias};
                $total = round((float) ($row->total ?? 0), 2);
                if ($key === null || $key === '' || (string) $key === '0') {
                    $sin = $keyAlias === 'destino_key' ? 'Sin destino' : 'Sin aerolínea';

                    return ['label' => $sin, 'total' => $total];
                }
                $parts = [];
                foreach ($joinSelectMap as $col => $as) {
                    $v = $row->{$as} ?? null;
                    if ($v !== null && $v !== '') {
                        $parts[] = (string) $v;
                    }
                }
                $parts = array_filter($parts);
                if ($parts !== []) {
                    $label = trim(implode(' - ', $parts));

                    return ['label' => Str::limit($label !== '' ? $label : $entityLabelWhenResolved . ' #' . $key, 64), 'total' => $total];
                }

                return ['label' => $fallbackPrefix . ' #' . $key, 'total' => $total];
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
        $vTer = $this->sqlEsVentaATercerosLinea('p');
        $sub = $this->sqlSubtotalLinea('sd');
        $lineAmountExpr = 'CASE WHEN (' . $vTer . ') THEN ' . $sub . ' ELSE 0 END';

        return $this->agregarPorClaveDestinoOAerolinea(
            $startDate,
            $endDate,
            $lineaExpr,
            'linea_key',
            'aerolineas',
            'id_aerolinea',
            ['iata' => 'al_iata', 'nombre' => 'al_nombre'],
            $lineAmountExpr,
            'Aerolínea',
            'Aerolínea'
        );
    }

    /**
     * Comisiones por aerolínea.
     *
     * @return \Illuminate\Support\Collection<int, array{label: string, total: float}>
     */
    private function comisionesPorAerolineaConCatalogo(Carbon $startDate, Carbon $endDate): Collection
    {
        $lineaExpr      = 'NULLIF(NULLIF(TRIM(sd.linea), ""), "0")';
        $lineAmountExpr = $this->sqlMontoComisionesPorLinea();

        return $this->agregarPorClaveDestinoOAerolinea(
            $startDate,
            $endDate,
            $lineaExpr,
            'linea_key',
            'aerolineas',
            'id_aerolinea',
            ['iata' => 'al_iata', 'nombre' => 'al_nombre'],
            $lineAmountExpr,
            'Aerolínea',
            'Aerolínea'
        );
    }

    /**
     * FEE por aerolínea (cargo administrativo + CXS).
     *
     * @return \Illuminate\Support\Collection<int, array{label: string, total: float}>
     */
    private function feePorAerolineaConCatalogo(Carbon $startDate, Carbon $endDate): Collection
    {
        $lineaExpr      = 'NULLIF(NULLIF(TRIM(sd.linea), ""), "0")';
        $lineAmountExpr = $this->sqlMontoSoloFeePorLinea();

        return $this->agregarPorClaveDestinoOAerolinea(
            $startDate,
            $endDate,
            $lineaExpr,
            'linea_key',
            'aerolineas',
            'id_aerolinea',
            ['iata' => 'al_iata', 'nombre' => 'al_nombre'],
            $lineAmountExpr,
            'Aerolínea',
            'Aerolínea'
        );
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
        $vTer = $this->sqlEsVentaATercerosLinea('p');
        $sub = '(salesdetails.pricesale + salesdetails.nosujeta + salesdetails.exempt)';

        $rows = DB::table('salesdetails')
            ->join('sales', 'sales.id', '=', 'salesdetails.sale_id')
            ->leftJoin('products as p', 'p.id', '=', 'salesdetails.product_id')
            ->whereBetween('sales.date', [$startDate, $endDate])
            ->where('sales.state', '<>', 0)
            ->selectRaw($groupColumn . ' as grp_key')
            ->selectRaw('SUM(CASE WHEN (' . $vTer . ') THEN ' . $sub . ' ELSE 0 END) as total')
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
