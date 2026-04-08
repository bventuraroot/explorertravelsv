@extends('layouts/layoutMaster')

@section('title', 'Centro de Control · Explorer Travel')

@section('vendor-style')
<link rel="stylesheet" href="{{asset('assets/vendor/libs/apex-charts/apex-charts.css')}}" />
@endsection

@section('vendor-script')
<script src="{{asset('assets/vendor/libs/apex-charts/apexcharts.js')}}"></script>
@endsection

@section('page-script')
<script>
  window._dash = {
    ventasPorMes:        @json($ventasPorMes),
    ventasPorDia:        @json($ventasPorDia),
    ventasUltimaSemana:  @json($ventasUltimaSemana),
    ventasUltimoMes:     @json($ventasUltimoMes),
    ventasUltimoAno:     @json($ventasUltimoAno),
    productosMasVendidos:@json($productosMasVendidos),
  };
</script>
<script src="{{asset('assets/js/dashboards-crm.js')}}"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const sel = document.getElementById('filter_type');
    if (!sel) return;
    const ids = [
      'filter_day_container', 'filter_month_container',
      'filter_year_container', 'filter_custom_from_container',
      'filter_custom_to_container'
    ];
    function toggle() {
      ids.forEach(id => { const el = document.getElementById(id); if (el) el.style.display = 'none'; });
      const v = sel.value;
      const show = id => { const el = document.getElementById(id); if (el) el.style.display = 'block'; };
      if (v === 'day')    show('filter_day_container');
      if (v === 'month')  show('filter_month_container');
      if (v === 'year')   show('filter_year_container');
      if (v === 'custom') { show('filter_custom_from_container'); show('filter_custom_to_container'); }
    }
    sel.addEventListener('change', toggle);
    toggle();
  });
</script>
@endsection

@section('content')
@php
  $colorCrecimiento = $crecimientoVentas >= 0 ? 'success' : 'danger';
  $iconCrecimiento  = $crecimientoVentas >= 0 ? 'ti-trending-up' : 'ti-trending-down';
  $signo            = $crecimientoVentas >= 0 ? '+' : '';
  $totalFeeTotal    = $totalFees + $totalFeesIva;
  $coloresProd      = ['primary','success','info','warning','danger'];
@endphp

<style>
  /* ── Hero ── */
  .db-hero {
    background: linear-gradient(135deg, #1a1f71 0%, #0f3460 55%, #16213e 100%);
    border-radius: 14px;
    padding: 28px 32px;
    color: #fff;
    position: relative;
    overflow: hidden;
  }
  .db-hero::before {
    content: ''; position: absolute;
    top: -50px; right: -30px; width: 230px; height: 230px;
    border-radius: 50%; background: rgba(105,108,255,.18);
    pointer-events: none;
  }
  .db-hero::after {
    content: ''; position: absolute;
    bottom: -40px; left: 28%; width: 170px; height: 170px;
    border-radius: 50%; background: rgba(40,199,111,.12);
    pointer-events: none;
  }
  .db-hero-plane {
    position: absolute; right: 180px; top: 16px;
    font-size: 90px; opacity: .06; transform: rotate(-15deg);
    pointer-events: none;
  }
  /* ── KPI Card ── */
  .db-card {
    border: none;
    border-radius: 12px;
    transition: transform .18s, box-shadow .18s;
  }
  .db-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 28px rgba(0,0,0,.11) !important;
  }
  /* ── Icon wrap ── */
  .db-icon {
    width: 50px; height: 50px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 22px; flex-shrink: 0;
  }
  /* ── Stat pill ── */
  .db-pill { display: flex; align-items: center; gap: 14px; }
  .db-pill-icon {
    width: 44px; height: 44px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px; flex-shrink: 0;
  }
  /* ── Section label ── */
  .db-label {
    font-size: 10px; font-weight: 700; letter-spacing: 1.4px;
    text-transform: uppercase; color: #a1acb8; margin-bottom: 4px;
  }
  /* ── Product row ── */
  .db-prod-row {
    display: flex; align-items: center; gap: 14px; padding: 11px 0;
    border-bottom: 1px solid rgba(0,0,0,.055);
  }
  .db-prod-row:last-child { border-bottom: none; }
  .db-prod-rank {
    width: 30px; height: 30px; border-radius: 7px;
    display: flex; align-items: center; justify-content: center;
    font-size: 11px; font-weight: 800; flex-shrink: 0;
  }
  .db-progress { height: 5px; border-radius: 3px; background: #eee; overflow: hidden; }
  .db-progress-bar { height: 100%; border-radius: 3px; transition: width .6s ease; }
  /* ── Badge ── */
  .db-badge {
    font-size: 10.5px; padding: 4px 10px; border-radius: 20px;
    font-weight: 600; display: inline-flex; align-items: center; gap: 4px;
  }
  /* ── Divider light ── */
  .db-divider { border-top: 1px solid rgba(0,0,0,.06); margin: 10px 0; }
  /* ── Filter bar ── */
  .db-filter { border-radius: 10px; }
</style>

<div class="row g-4">

  {{-- ══════════════════════════════════════════════════════════════ HERO ══ --}}
  <div class="col-12">
    <div class="db-hero">
      <span class="db-hero-plane"><i class="ti ti-plane"></i></span>

      <div class="row align-items-center">
        {{-- Izquierda --}}
        <div class="col-lg-6 mb-3 mb-lg-0">
          <div class="d-flex align-items-center gap-3 mb-2">
            <div style="background:rgba(255,255,255,.14);border-radius:10px;padding:8px 12px;">
              <i class="ti ti-map-route" style="font-size:22px;color:#fff;"></i>
            </div>
            <div>
              <h4 class="mb-0" style="color:#fff;font-weight:800;letter-spacing:-.4px;">
                Centro de Control
              </h4>
              <span style="color:rgba(255,255,255,.55);font-size:12px;">
                Explorer Travel · Dashboard de Análisis
              </span>
            </div>
          </div>

          <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
            <span class="db-badge" style="background:rgba(40,199,111,.2);color:#28c76f;">
              <i class="ti ti-calendar-event"></i>
              {{ $startDate }} → {{ $endDate }}
            </span>
            @if($filterType != 'all')
            <span class="db-badge" style="background:rgba(255,255,255,.12);color:rgba(255,255,255,.8);">
              <i class="ti ti-filter"></i> Filtro activo
            </span>
            @endif
          </div>

          <div class="mt-4 d-flex flex-wrap gap-4">
            <div>
              <div style="color:rgba(255,255,255,.5);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:1.2px;">
                Clientes
              </div>
              <div style="color:#fff;font-size:1.5rem;font-weight:800;line-height:1.2;">
                {{ number_format($tclientes) }}
              </div>
            </div>
            <div>
              <div style="color:rgba(255,255,255,.5);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:1.2px;">
                Proveedores
              </div>
              <div style="color:#fff;font-size:1.5rem;font-weight:800;line-height:1.2;">
                {{ number_format($tproviders) }}
              </div>
            </div>
            <div>
              <div style="color:rgba(255,255,255,.5);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:1.2px;">
                Servicios
              </div>
              <div style="color:#fff;font-size:1.5rem;font-weight:800;line-height:1.2;">
                {{ number_format($tproducts) }}
              </div>
            </div>
            <div>
              <div style="color:rgba(255,255,255,.5);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:1.2px;">
                Documentos
              </div>
              <div style="color:#fff;font-size:1.5rem;font-weight:800;line-height:1.2;">
                {{ number_format($tsales) }}
              </div>
            </div>
          </div>
        </div>

        {{-- Derecha: KPI principal --}}
        <div class="col-lg-6 text-lg-end">
          <div style="color:rgba(255,255,255,.5);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:1.4px;margin-bottom:6px;">
            Ingreso total del período
          </div>
          <div style="font-size:3rem;font-weight:900;color:#fff;line-height:1;letter-spacing:-1px;">
            ${{ number_format($totalVentas, 2) }}
          </div>
          <div class="mt-2 d-flex align-items-center gap-3 justify-content-lg-end">
            <span class="db-badge"
              style="background:rgba({{ $crecimientoVentas >= 0 ? '40,199,111' : '234,84,85' }},.22);
                     color:{{ $crecimientoVentas >= 0 ? '#28c76f' : '#ea5455' }};">
              <i class="ti {{ $iconCrecimiento }}"></i>
              {{ $signo }}{{ $crecimientoVentas }}% vs año anterior
            </span>
          </div>
          <div class="mt-3 d-flex flex-wrap gap-3 justify-content-lg-end">
            <div style="background:rgba(255,255,255,.08);border-radius:10px;padding:10px 18px;text-align:center;">
              <div style="color:rgba(255,255,255,.5);font-size:9px;text-transform:uppercase;letter-spacing:1px;">Fee período</div>
              <div style="color:#28c76f;font-size:1.1rem;font-weight:800;">${{ number_format($totalFees, 2) }}</div>
            </div>
            <div style="background:rgba(255,255,255,.08);border-radius:10px;padding:10px 18px;text-align:center;">
              <div style="color:rgba(255,255,255,.5);font-size:9px;text-transform:uppercase;letter-spacing:1px;">Fee + IVA</div>
              <div style="color:#ff9f43;font-size:1.1rem;font-weight:800;">${{ number_format($totalFeesIva, 2) }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- ══════════════════════════════════════════════════════════ FILTROS ══ --}}
  <div class="col-12">
    <div class="card db-filter mb-0">
      <div class="card-body py-2 px-3">
        <form method="GET" action="{{ url('/dashboard') }}" class="row g-2 align-items-end">
          <div class="col-auto">
            <label class="form-label mb-1 db-label">Período</label>
            <select name="filter_type" id="filter_type" class="form-select form-select-sm" style="min-width:130px;">
              <option value="all"    {{ $filterType=='all'    ?'selected':'' }}>Último año</option>
              <option value="day"    {{ $filterType=='day'    ?'selected':'' }}>Por día</option>
              <option value="month"  {{ $filterType=='month'  ?'selected':'' }}>Por mes</option>
              <option value="year"   {{ $filterType=='year'   ?'selected':'' }}>Por año</option>
              <option value="custom" {{ $filterType=='custom' ?'selected':'' }}>Rango libre</option>
            </select>
          </div>
          <div class="col-auto" id="filter_day_container"
               style="display:{{ $filterType=='day'    ?'block':'none' }};">
            <label class="form-label mb-1 db-label">Día</label>
            <input type="date" name="filter_date" class="form-control form-control-sm" value="{{ $filterDate }}">
          </div>
          <div class="col-auto" id="filter_month_container"
               style="display:{{ $filterType=='month'  ?'block':'none' }};">
            <label class="form-label mb-1 db-label">Mes</label>
            <input type="month" name="filter_month" class="form-control form-control-sm" value="{{ $filterMonth }}">
          </div>
          <div class="col-auto" id="filter_year_container"
               style="display:{{ $filterType=='year'   ?'block':'none' }};">
            <label class="form-label mb-1 db-label">Año</label>
            <input type="number" name="filter_year" class="form-control form-control-sm"
                   value="{{ $filterYear }}" min="2000" max="2099" style="width:88px;">
          </div>
          <div class="col-auto" id="filter_custom_from_container"
               style="display:{{ $filterType=='custom' ?'block':'none' }};">
            <label class="form-label mb-1 db-label">Desde</label>
            <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $dateFrom }}">
          </div>
          <div class="col-auto" id="filter_custom_to_container"
               style="display:{{ $filterType=='custom' ?'block':'none' }};">
            <label class="form-label mb-1 db-label">Hasta</label>
            <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $dateTo }}">
          </div>
          <div class="col-auto">
            <button type="submit" class="btn btn-primary btn-sm px-3">
              <i class="ti ti-filter me-1"></i>Aplicar
            </button>
          </div>
          @if($filterType != 'all')
          <div class="col-auto">
            <a href="{{ url('/dashboard') }}" class="btn btn-outline-secondary btn-sm px-3">
              <i class="ti ti-refresh me-1"></i>Limpiar
            </a>
          </div>
          @endif
        </form>
      </div>
    </div>
  </div>

  {{-- ══════════════════════════════════════════════════════ KPI FINANCIEROS ══ --}}

  {{-- Ingresos período --}}
  <div class="col-xl-3 col-md-6">
    <div class="card db-card h-100">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between mb-3">
          <div class="db-icon bg-label-primary">
            <i class="ti ti-currency-dollar text-primary" style="font-size:22px;"></i>
          </div>
          <span class="db-badge bg-label-{{ $colorCrecimiento }}
                       {{ $crecimientoVentas >= 0 ? 'text-success' : 'text-danger' }}">
            <i class="ti {{ $iconCrecimiento }}"></i>
            {{ $signo }}{{ $crecimientoVentas }}%
          </span>
        </div>
        <p class="db-label mb-1">Ingresos del período</p>
        <h3 class="mb-0 fw-bold">${{ number_format($totalVentas, 2) }}</h3>
        <small class="text-muted">Tendencia últimos 12 meses</small>
        <div id="salesLastYear" class="mt-2" style="min-height:58px;"></div>
      </div>
    </div>
  </div>

  {{-- Ingresos mes --}}
  <div class="col-xl-3 col-md-6">
    <div class="card db-card h-100">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between mb-3">
          <div class="db-icon bg-label-info">
            <i class="ti ti-calendar-month text-info" style="font-size:22px;"></i>
          </div>
          <span class="db-badge bg-label-info text-info">Mes actual</span>
        </div>
        <p class="db-label mb-1">Ingresos este mes</p>
        <h3 class="mb-0 fw-bold">${{ number_format($totalVentasMes, 2) }}</h3>
        <small class="text-muted">Últimos 30 días</small>
        <div id="sessionsLastMonth" class="mt-2" style="min-height:58px;"></div>
      </div>
    </div>
  </div>

  {{-- Ingresos semana --}}
  <div class="col-xl-3 col-md-6">
    <div class="card db-card h-100">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between mb-3">
          <div class="db-icon bg-label-warning">
            <i class="ti ti-calendar-week text-warning" style="font-size:22px;"></i>
          </div>
          <span class="db-badge bg-label-warning text-warning">Esta semana</span>
        </div>
        <p class="db-label mb-1">Ingresos esta semana</p>
        <h3 class="mb-0 fw-bold">${{ number_format($totalVentasSemana, 2) }}</h3>
        <small class="text-muted">Últimos 7 días</small>
        <div id="revenueGrowth" class="mt-2" style="min-height:58px;"></div>
      </div>
    </div>
  </div>

  {{-- Fees --}}
  <div class="col-xl-3 col-md-6">
    <div class="card db-card h-100">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between mb-3">
          <div class="db-icon bg-label-success">
            <i class="ti ti-wallet text-success" style="font-size:22px;"></i>
          </div>
          <span class="db-badge bg-label-success text-success">
            <i class="ti ti-coin"></i> Comisiones
          </span>
        </div>
        <p class="db-label mb-1">Fees del período</p>
        <h3 class="mb-0 fw-bold">${{ number_format($totalFees, 2) }}</h3>
        <small class="text-muted">Sin IVA</small>
        <div class="db-divider mt-3"></div>
        <div class="d-flex justify-content-between align-items-center" style="font-size:12px;">
          <span class="text-muted">Fee + IVA</span>
          <span class="fw-bold text-warning">${{ number_format($totalFeesIva, 2) }}</span>
        </div>
        <div class="d-flex justify-content-between align-items-center mt-1" style="font-size:12px;">
          <span class="text-muted">Total combinado</span>
          <span class="fw-bold">${{ number_format($totalFeeTotal, 2) }}</span>
        </div>
      </div>
    </div>
  </div>

  {{-- ══════════════════════════════════════════════ GRÁFICO PRINCIPAL + DONUT ══ --}}

  {{-- Tendencia mensual 12 meses --}}
  <div class="col-xl-8 col-12">
    <div class="card db-card h-100">
      <div class="card-header pb-0">
        <div class="d-flex align-items-start justify-content-between">
          <div>
            <p class="db-label">Rendimiento financiero</p>
            <h5 class="mb-0 fw-bold">Tendencia de ingresos — 12 meses</h5>
          </div>
          <div class="d-flex align-items-center gap-3" style="font-size:11px;color:#a1acb8;">
            <span><i class="ti ti-circle-filled text-primary me-1" style="font-size:8px;"></i>Ingresos</span>
          </div>
        </div>
      </div>
      <div class="card-body pt-2">
        <div id="mainRevenueChart"></div>
      </div>
    </div>
  </div>

  {{-- Donut distribución últimos 6 meses --}}
  <div class="col-xl-4 col-12">
    <div class="card db-card h-100">
      <div class="card-header pb-0">
        <p class="db-label">Distribución por mes</p>
        <h5 class="mb-0 fw-bold">Últimos 6 meses</h5>
      </div>
      <div class="card-body d-flex flex-column align-items-center justify-content-center pt-2">
        <div id="feeDonutChart"></div>
      </div>
    </div>
  </div>

  {{-- ══════════════════════════════════════════════ SEMANA + TOP PRODUCTOS ══ --}}

  {{-- Tendencia semanal --}}
  <div class="col-xl-4 col-lg-5 col-12">
    <div class="card db-card h-100">
      <div class="card-header pb-0">
        <p class="db-label">Actividad reciente</p>
        <h5 class="mb-0 fw-bold">Ventas — últimos 7 días</h5>
        <div class="d-flex align-items-center gap-2 mt-1">
          <span class="fw-bold text-success" style="font-size:16px;">
            ${{ number_format($totalVentasSemana, 2) }}
          </span>
          <span class="db-badge bg-label-{{ $colorCrecimiento }}
                       {{ $crecimientoVentas >= 0 ? 'text-success' : 'text-danger' }}"
                style="font-size:9px;">
            {{ $signo }}{{ $crecimientoVentas }}% YoY
          </span>
        </div>
      </div>
      <div class="card-body pt-2">
        <div id="weeklyTrendChart"></div>
      </div>
    </div>
  </div>

  {{-- Top 5 servicios más vendidos --}}
  <div class="col-xl-8 col-lg-7 col-12">
    <div class="card db-card h-100">
      <div class="card-header pb-0">
        <div class="d-flex align-items-start justify-content-between">
          <div>
            <p class="db-label">Ranking de servicios</p>
            <h5 class="mb-0 fw-bold">Top 5 más vendidos</h5>
          </div>
          <span class="db-badge bg-label-primary text-primary" style="font-size:9.5px;">
            <i class="ti ti-trophy"></i> Período seleccionado
          </span>
        </div>
      </div>
      <div class="card-body pb-2">
        @php
          $maxCantidad = $productosMasVendidos->max('cantidad_vendida') ?: 1;
        @endphp
        @forelse($productosMasVendidos as $idx => $prod)
          @php
            $pct   = round(($prod->cantidad_vendida / $maxCantidad) * 100, 1);
            $color = $coloresProd[$idx % 5];
            $barColors = [
              'primary' => '#696cff',
              'success' => '#28c76f',
              'info'    => '#00cfe8',
              'warning' => '#ff9f43',
              'danger'  => '#ea5455',
            ];
            $barHex = $barColors[$color] ?? '#696cff';
          @endphp
          <div class="db-prod-row">
            <div class="db-prod-rank bg-label-{{ $color }}">
              <span class="text-{{ $color }}">#{{ $idx + 1 }}</span>
            </div>
            <div class="flex-grow-1">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span style="font-size:13px;font-weight:600;line-height:1.2;">
                  {{ $prod->name }}
                </span>
                <div class="d-flex align-items-center gap-2">
                  <span class="db-badge bg-label-{{ $color }} text-{{ $color }}">
                    {{ number_format($prod->cantidad_vendida) }} unidades
                  </span>
                  <span style="font-size:10px;color:#a1acb8;min-width:34px;text-align:right;">
                    {{ $pct }}%
                  </span>
                </div>
              </div>
              <div class="db-progress">
                <div class="db-progress-bar" style="width:{{ $pct }}%;background:{{ $barHex }};"></div>
              </div>
            </div>
          </div>
        @empty
          <div class="text-center py-5" style="color:#a1acb8;">
            <i class="ti ti-database-off" style="font-size:40px;opacity:.4;"></i>
            <p class="mt-2 mb-0" style="font-size:13px;">
              Sin datos para el período seleccionado
            </p>
          </div>
        @endforelse
      </div>
    </div>
  </div>

</div>
@endsection
