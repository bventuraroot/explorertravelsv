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
    ventasPorMes:         @json($ventasPorMes),
    ventasPorDia:         @json($ventasPorDia),
    ventasUltimaSemana:   @json($ventasUltimaSemana),
    ventasUltimoMes:      @json($ventasUltimoMes),
    ventasUltimoAno:      @json($ventasUltimoAno),
    productosMasVendidos: @json($productosMasVendidos),
    ventasPorProveedor:   @json($ventasPorProveedor),
    ventasPorDestino:     @json($ventasPorDestino),
    ventasPorRuta:        @json($ventasPorRuta),
    ventasPorAerolinea:   @json($ventasPorAerolinea),
    ventasPorCanal:       @json($ventasPorCanal),
    ventasPorCliente:       @json($ventasPorCliente),
    feePorDestino:          @json($feePorDestino ?? []),
    feePorAerolinea:        @json($feePorAerolinea ?? []),
    comisionesPorDestino:   @json($comisionesPorDestino ?? []),
    comisionesPorAerolinea: @json($comisionesPorAerolinea ?? []),
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
  $totalFeeTotal       = $totalFees + $totalFeesIva;
  $totalComisionesTotal = $totalComisiones + $totalComisionesIva;
  $coloresProd         = ['primary','success','info','warning','danger'];
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
  .db-hbar { min-height: 300px; }
  /* Pestañas BI */
  .db-bi-tabs .nav-link {
    border-radius: 10px;
    font-weight: 600;
    font-size: 13px;
    padding: 10px 18px;
    color: #697a8d;
    border: 1px solid transparent;
  }
  .db-bi-tabs .nav-link:hover { color: #696cff; background: rgba(105,108,255,.06); }
  .db-bi-tabs .nav-link.active {
    color: #fff;
    background: linear-gradient(135deg, #696cff 0%, #5a5fef 100%);
    border-color: transparent;
    box-shadow: 0 4px 12px rgba(105,108,255,.28);
  }
  .db-bi-tabs + .tab-content { margin-top: 4px; }
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
            Ventas totales del período
          </div>
          <div style="font-size:3rem;font-weight:900;color:#fff;line-height:1;letter-spacing:-1px;">
            ${{ number_format($totalVentas, 2) }}
          </div>
          <div class="text-lg-end ms-lg-auto mt-1" style="color:rgba(255,255,255,.48);font-size:10px;max-width:30rem;line-height:1.35;">
            <strong style="color:rgba(255,255,255,.7);">Ventas</strong> = todo lo vendido (productos y servicios). ·
            <strong style="color:rgba(255,255,255,.7);">FEE</strong> y <strong style="color:rgba(255,255,255,.7);">comisiones</strong> se muestran desglosados en las tarjetas y en su pestaña.
          </div>
          <div class="mt-2 d-flex align-items-center gap-3 justify-content-lg-end">
            <span class="db-badge"
              style="background:rgba({{ $crecimientoVentas >= 0 ? '40,199,111' : '234,84,85' }},.22);
                     color:{{ $crecimientoVentas >= 0 ? '#28c76f' : '#ea5455' }};">
              <i class="ti {{ $iconCrecimiento }}"></i>
              {{ $signo }}{{ $crecimientoVentas }}% vs año anterior
            </span>
          </div>
          <div class="mt-3 d-flex flex-wrap gap-2 justify-content-lg-end">
            <div style="background:rgba(255,255,255,.08);border-radius:10px;padding:9px 14px;text-align:center;">
              <div style="color:rgba(255,255,255,.5);font-size:9px;text-transform:uppercase;letter-spacing:1px;">FEE</div>
              <div style="color:#28c76f;font-size:1rem;font-weight:800;">${{ number_format($totalFees, 2) }}</div>
            </div>
            <div style="background:rgba(255,255,255,.08);border-radius:10px;padding:9px 14px;text-align:center;">
              <div style="color:rgba(255,255,255,.5);font-size:9px;text-transform:uppercase;letter-spacing:1px;">IVA FEE</div>
              <div style="color:#ff9f43;font-size:1rem;font-weight:800;">${{ number_format($totalFeesIva, 2) }}</div>
            </div>
            <div style="background:rgba(255,255,255,.08);border-radius:10px;padding:9px 14px;text-align:center;">
              <div style="color:rgba(255,255,255,.5);font-size:9px;text-transform:uppercase;letter-spacing:1px;">FEE COMISIONES</div>
              <div style="color:#00cfe8;font-size:1rem;font-weight:800;">${{ number_format($totalComisiones, 2) }}</div>
            </div>
            <div style="background:rgba(255,255,255,.08);border-radius:10px;padding:9px 14px;text-align:center;">
              <div style="color:rgba(255,255,255,.5);font-size:9px;text-transform:uppercase;letter-spacing:1px;">IVA COMISIONES</div>
              <div style="color:#7367f0;font-size:1rem;font-weight:800;">${{ number_format($totalComisionesIva, 2) }}</div>
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
            <div class="d-flex flex-wrap align-items-end gap-2">
              <div>
                <label class="form-label mb-1 db-label">Mes</label>
                <select name="filter_month_num" class="form-select form-select-sm" style="min-width:152px;">
                  @foreach([
                    '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo', '04' => 'Abril',
                    '05' => 'Mayo', '06' => 'Junio', '07' => 'Julio', '08' => 'Agosto',
                    '09' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre',
                  ] as $num => $mesNombre)
                    <option value="{{ $num }}" @selected($filterMonthNum === $num)>{{ $mesNombre }}</option>
                  @endforeach
                </select>
              </div>
              <div>
                <label class="form-label mb-1 db-label">Año</label>
                <select name="filter_month_year" class="form-select form-select-sm" style="min-width:92px;">
                  @foreach($yearsForMonthFilter as $y)
                    <option value="{{ $y }}" @selected((int) $filterMonthYear === (int) $y)>{{ $y }}</option>
                  @endforeach
                </select>
              </div>
            </div>
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
          @if(count(request()->query()) > 0)
          <div class="col-auto">
            <a href="{{ url('/dashboard') }}" class="btn btn-outline-secondary btn-sm px-3" title="Quitar filtros y ver el mes en curso">
              <i class="ti ti-refresh me-1"></i>Mes actual
            </a>
          </div>
          @endif
        </form>
      </div>
    </div>
  </div>

  {{-- ══════════════════════════════════════════════════════ KPI VENTAS ══ --}}

  {{-- Ventas período --}}
  <div class="col-6 col-lg-4 col-xl">
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
        <p class="db-label mb-1">Ventas del período</p>
        <h3 class="mb-0 fw-bold">${{ number_format($totalVentas, 2) }}</h3>
        <small class="text-muted">Tendencia últimos 12 meses</small>
        <div id="salesLastYear" class="mt-2" style="min-height:58px;"></div>
      </div>
    </div>
  </div>

  {{-- Ventas mes --}}
  <div class="col-6 col-lg-4 col-xl">
    <div class="card db-card h-100">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between mb-3">
          <div class="db-icon bg-label-info">
            <i class="ti ti-calendar-month text-info" style="font-size:22px;"></i>
          </div>
          <span class="db-badge bg-label-info text-info">Mes actual</span>
        </div>
        <p class="db-label mb-1">Ventas este mes</p>
        <h3 class="mb-0 fw-bold">${{ number_format($totalVentasMes, 2) }}</h3>
        <small class="text-muted">Últimos 30 días</small>
        <div id="sessionsLastMonth" class="mt-2" style="min-height:58px;"></div>
      </div>
    </div>
  </div>

  {{-- Ventas semana --}}
  <div class="col-6 col-lg-4 col-xl">
    <div class="card db-card h-100">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between mb-3">
          <div class="db-icon bg-label-warning">
            <i class="ti ti-calendar-week text-warning" style="font-size:22px;"></i>
          </div>
          <span class="db-badge bg-label-warning text-warning">Esta semana</span>
        </div>
        <p class="db-label mb-1">Ventas esta semana</p>
        <h3 class="mb-0 fw-bold">${{ number_format($totalVentasSemana, 2) }}</h3>
        <small class="text-muted">Últimos 7 días</small>
        <div id="revenueGrowth" class="mt-2" style="min-height:58px;"></div>
      </div>
    </div>
  </div>

  {{-- FEE: cargo administrativo + CXS --}}
  <div class="col-6 col-lg-4 col-xl">
    <div class="card db-card h-100">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between mb-3">
          <div class="db-icon bg-label-success">
            <i class="ti ti-receipt-2 text-success" style="font-size:22px;"></i>
          </div>
          <span class="db-badge bg-label-success text-success">
            <i class="ti ti-receipt"></i> FEE
          </span>
        </div>
        <p class="db-label mb-1">FEE (admin. + CXS)</p>
        <h3 class="mb-0 fw-bold">${{ number_format($totalFees, 2) }}</h3>
        <small class="text-muted">Cargo administrativo y CXS</small>
        <div class="db-divider mt-3"></div>
        <div class="d-flex justify-content-between align-items-center" style="font-size:12px;">
          <span class="text-muted">IVA FEE</span>
          <span class="fw-bold text-warning">${{ number_format($totalFeesIva, 2) }}</span>
        </div>
        <div class="d-flex justify-content-between align-items-center mt-1" style="font-size:12px;">
          <span class="text-muted">Total</span>
          <span class="fw-bold">${{ number_format($totalFeeTotal, 2) }}</span>
        </div>
      </div>
    </div>
  </div>

  {{-- FEE Comisiones --}}
  <div class="col-6 col-lg-4 col-xl">
    <div class="card db-card h-100" style="border:1px solid rgba(0,207,232,.2);">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between mb-3">
          <div class="db-icon bg-label-info">
            <i class="ti ti-percentage text-info" style="font-size:22px;"></i>
          </div>
          <span class="db-badge bg-label-info text-info">
            <i class="ti ti-coin"></i> FEE COMISIONES
          </span>
        </div>
        <p class="db-label mb-1">FEE COMISIONES</p>
        <h3 class="mb-0 fw-bold">${{ number_format($totalComisiones, 2) }}</h3>
        <small class="text-muted">Productos con «comision» en nombre</small>
        <div class="db-divider mt-3"></div>
        <div class="d-flex justify-content-between align-items-center" style="font-size:12px;">
          <span class="text-muted">IVA COMISIONES</span>
          <span class="fw-bold text-warning">${{ number_format($totalComisionesIva, 2) }}</span>
        </div>
        <div class="d-flex justify-content-between align-items-center mt-1" style="font-size:12px;">
          <span class="text-muted">Total</span>
          <span class="fw-bold">${{ number_format($totalComisionesTotal, 2) }}</span>
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
            <p class="db-label">Evolución comercial</p>
            <h5 class="mb-0 fw-bold">Tendencia de ventas — 12 meses</h5>
          </div>
          <div class="d-flex align-items-center gap-3" style="font-size:11px;color:#a1acb8;">
            <span><i class="ti ti-circle-filled text-primary me-1" style="font-size:8px;"></i>Ventas</span>
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
        <p class="db-label">Participación por mes</p>
        <h5 class="mb-0 fw-bold">Ventas — últimos 6 meses</h5>
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

  {{-- ══════════════════════════════════════════ ANÁLISIS OPERATIVO (BI) — pestañas ══ --}}
  <div class="col-12">
    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-2">
      <div class="d-flex align-items-center gap-2">
        <i class="ti ti-chart-dots-3 text-primary" style="font-size:22px;"></i>
        <div>
          <p class="db-label mb-0">Inteligencia de negocio</p>
          <h5 class="mb-0 fw-bold">Reportes operativos</h5>
          <small class="text-muted">Consulta <strong>Ventas</strong> (todo lo operativo que se vende) o el análisis de <strong>FEE + comisiones</strong>. Solo ventas con <strong>DTE relacionado</strong> y no anuladas.</small>
        </div>
      </div>
    </div>

    <ul class="nav nav-pills db-bi-tabs mb-3" id="biReportTabs" role="tablist">
      <li class="nav-item flex-fill text-center" role="presentation">
        <button class="nav-link active w-100" id="tab-bi-ventas" data-bs-toggle="tab" data-bs-target="#pane-bi-ventas"
                type="button" role="tab" aria-controls="pane-bi-ventas" aria-selected="true">
          <i class="ti ti-shopping-cart me-1"></i> Ventas
        </button>
      </li>
      <li class="nav-item flex-fill text-center" role="presentation">
        <button class="nav-link w-100" id="tab-bi-fee-comisiones" data-bs-toggle="tab" data-bs-target="#pane-bi-fee-comisiones"
                type="button" role="tab" aria-controls="pane-bi-fee-comisiones" aria-selected="false">
          <i class="ti ti-receipt-2 me-1"></i> FEE + comisiones
        </button>
      </li>
    </ul>

    <div class="tab-content" id="biReportTabContent">
      {{-- Pestaña Ventas --}}
      <div class="tab-pane fade show active" id="pane-bi-ventas" role="tabpanel" aria-labelledby="tab-bi-ventas" tabindex="0">
        <p class="text-muted small mb-3">
          <strong>Ventas</strong>: montos por línea (gravado + exento + no sujeto) de todo lo vendido como producto o servicio; las líneas de FEE (admin./CXS) y comisiones van en la otra pestaña. Período filtrado; anuladas excluidas.
        </p>
        <div class="row g-4">
          <div class="col-xl-6 col-12">
            <div class="card db-card h-100">
              <div class="card-header pb-0">
                <p class="db-label">Red de proveedores</p>
                <h5 class="mb-0 fw-bold"><i class="ti ti-truck-delivery me-1 text-primary"></i>Ventas por proveedor (línea)</h5>
              </div>
              <div class="card-body pt-2"><div id="chartVentasProveedor" class="db-hbar"></div></div>
            </div>
          </div>
          <div class="col-xl-6 col-12">
            <div class="card db-card h-100">
              <div class="card-header pb-0">
                <p class="db-label">Mercados</p>
                <h5 class="mb-0 fw-bold"><i class="ti ti-map-pin me-1 text-danger"></i>Ventas por destino (aeropuerto)</h5>
                <small class="text-muted">Etiqueta desde la tabla <code>aeropuertos</code> (IATA · ciudad · país).</small>
              </div>
              <div class="card-body pt-2"><div id="chartVentasDestino" class="db-hbar"></div></div>
            </div>
          </div>
          <div class="col-xl-6 col-12">
            <div class="card db-card h-100">
              <div class="card-header pb-0">
                <p class="db-label">Trayectos</p>
                <h5 class="mb-0 fw-bold"><i class="ti ti-route me-1 text-info"></i>Ventas por ruta / segmento</h5>
                <small class="text-muted">Incluye códigos o texto de ruta registrado en cada línea.</small>
              </div>
              <div class="card-body pt-2"><div id="chartVentasRuta" class="db-hbar"></div></div>
            </div>
          </div>
          <div class="col-xl-6 col-12">
            <div class="card db-card h-100">
              <div class="card-header pb-0">
                <p class="db-label">Transporte</p>
                <h5 class="mb-0 fw-bold"><i class="ti ti-plane-inflight me-1 text-warning"></i>Ventas por aerolínea</h5>
                <small class="text-muted">Nombre desde la tabla <code>aerolineas</code> (IATA · nombre).</small>
              </div>
              <div class="card-body pt-2"><div id="chartVentasAerolinea" class="db-hbar"></div></div>
            </div>
          </div>
          <div class="col-xl-6 col-12">
            <div class="card db-card h-100">
              <div class="card-header pb-0">
                <p class="db-label">Canales</p>
                <h5 class="mb-0 fw-bold"><i class="ti ti-device-analytics me-1 text-success"></i>Ventas por canal</h5>
              </div>
              <div class="card-body pt-2"><div id="chartVentasCanal" class="db-hbar"></div></div>
            </div>
          </div>
          <div class="col-xl-6 col-12">
            <div class="card db-card h-100">
              <div class="card-header pb-0">
                <p class="db-label">Relaciones</p>
                <h5 class="mb-0 fw-bold"><i class="ti ti-users me-1 text-primary"></i>Principales clientes por ventas</h5>
                <small class="text-muted">Suma de montos de documento en el período.</small>
              </div>
              <div class="card-body pt-2"><div id="chartVentasClientes" class="db-hbar"></div></div>
            </div>
          </div>
        </div>
      </div>

      {{-- Pestaña FEE + Comisiones --}}
      <div class="tab-pane fade" id="pane-bi-fee-comisiones" role="tabpanel" aria-labelledby="tab-bi-fee-comisiones" tabindex="0">
        <p class="text-muted small mb-3">
          <strong>FEE</strong> = cargo administrativo y CXS. <strong>Comisiones</strong> = productos cuyo nombre contiene «comision». Mismo criterio de monto que las tarjetas superiores (subtotal + columna fee en líneas gravadas).
        </p>
        <div class="row g-4">
          <div class="col-12">
            <p class="db-label mb-1 text-success">FEE (admin. + CXS)</p>
          </div>
          <div class="col-xl-6 col-12">
            <div class="card db-card h-100 border border-success border-opacity-25">
              <div class="card-header pb-0">
                <p class="db-label">Mercados</p>
                <h5 class="mb-0 fw-bold"><i class="ti ti-map-pin me-1 text-success"></i>FEE por destino</h5>
                <small class="text-muted">Etiqueta desde <code>aeropuertos</code>.</small>
              </div>
              <div class="card-body pt-2"><div id="chartFeeDestino" class="db-hbar"></div></div>
            </div>
          </div>
          <div class="col-xl-6 col-12">
            <div class="card db-card h-100 border border-success border-opacity-25">
              <div class="card-header pb-0">
                <p class="db-label">Transporte</p>
                <h5 class="mb-0 fw-bold"><i class="ti ti-plane-inflight me-1 text-success"></i>FEE por aerolínea</h5>
                <small class="text-muted">Nombre desde <code>aerolineas</code>.</small>
              </div>
              <div class="card-body pt-2"><div id="chartFeeAerolinea" class="db-hbar"></div></div>
            </div>
          </div>
          <div class="col-12 mt-1">
            <p class="db-label mb-1 text-info">Comisiones</p>
          </div>
          <div class="col-xl-6 col-12">
            <div class="card db-card h-100" style="border:1px solid rgba(0,207,232,.18);">
              <div class="card-header pb-0">
                <p class="db-label">Mercados</p>
                <h5 class="mb-0 fw-bold"><i class="ti ti-map-pin me-1 text-info"></i>Comisiones por destino</h5>
                <small class="text-muted">Etiqueta desde <code>aeropuertos</code>.</small>
              </div>
              <div class="card-body pt-2"><div id="chartComisionesDestino" class="db-hbar"></div></div>
            </div>
          </div>
          <div class="col-xl-6 col-12">
            <div class="card db-card h-100" style="border:1px solid rgba(0,207,232,.18);">
              <div class="card-header pb-0">
                <p class="db-label">Transporte</p>
                <h5 class="mb-0 fw-bold"><i class="ti ti-plane-inflight me-1 text-info"></i>Comisiones por aerolínea</h5>
                <small class="text-muted">Nombre desde <code>aerolineas</code>.</small>
              </div>
              <div class="card-body pt-2"><div id="chartComisionesAerolinea" class="db-hbar"></div></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

</div>
@endsection
