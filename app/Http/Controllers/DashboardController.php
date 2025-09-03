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

class DashboardController extends Controller
{
    public function home()
    {
        $user = auth()->user();
        $tclientes = Client::count();
        $tproviders = Provider::count();
        $tproducts = Product::count();
        $tsales = Sale::count();

        $now = Carbon::now();

        // Rangos de tiempo
        $startOfYearWindow = $now->copy()->subYear()->startOfDay();
        $startOfPrevYearWindow = $now->copy()->subYears(2)->startOfDay();
        $endOfPrevYearWindow = $now->copy()->subYear()->endOfDay();
        $startOfMonth = $now->copy()->startOfMonth();
        $startOfWeek = $now->copy()->startOfWeek();

        // Totales por ventanas
        $totalVentas = (float) (Sale::whereDate('date', '>=', $startOfYearWindow)->sum('totalamount') ?? 0);
        $totalVentasMes = (float) (Sale::whereDate('date', '>=', $startOfMonth)->sum('totalamount') ?? 0);
        $totalVentasSemana = (float) (Sale::whereDate('date', '>=', $startOfWeek)->sum('totalamount') ?? 0);

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

        // Top productos más vendidos (por cantidad) en el último año
        $productosMasVendidos = Salesdetail::query()
            ->select('products.id', 'products.name', DB::raw('SUM(salesdetails.amountp) as cantidad_vendida'))
            ->join('sales', 'sales.id', '=', 'salesdetails.sale_id')
            ->join('products', 'products.id', '=', 'salesdetails.product_id')
            ->whereDate('sales.date', '>=', $startOfYearWindow)
            ->groupBy('products.id', 'products.name')
            ->orderByDesc(DB::raw('SUM(salesdetails.amountp)'))
            ->limit(5)
            ->get();

        // Estructuras esperadas por la vista/JS
        $ventasUltimoAno = $ventasPorMes; // alias esperado por JS
        $ventasUltimoMes = $ventasPorDia30; // alias esperado por JS
        $ventasUltimaSemana = $ventasPorDia7; // alias esperado por JS

        // También se hace alias a ventasPorMes y ventasPorDia por compatibilidad
        $ventasPorDia = $ventasPorDia7;

        return view('dashboard')
            ->with('tclientes', $tclientes)
            ->with('tproviders', $tproviders)
            ->with('tproducts', $tproducts)
            ->with('tsales', $tsales)
            ->with('totalVentas', round($totalVentas, 2))
            ->with('totalVentasMes', round($totalVentasMes, 2))
            ->with('totalVentasSemana', round($totalVentasSemana, 2))
            ->with('crecimientoVentas', $crecimientoVentas)
            ->with('ventasUltimoAno', $ventasUltimoAno)
            ->with('ventasUltimoMes', $ventasUltimoMes)
            ->with('ventasUltimaSemana', $ventasUltimaSemana)
            ->with('ventasPorMes', $ventasPorMes)
            ->with('ventasPorDia', $ventasPorDia)
            ->with('productosMasVendidos', $productosMasVendidos);
    }
}
