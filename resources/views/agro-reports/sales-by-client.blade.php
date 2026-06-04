@php
$configData = Helper::appClasses();

// Determinar modo de filtro activo para restaurar el estado después de buscar
$activeFilterMode = 'period'; // default
if (isset($date_range) && $date_range) {
    $activeFilterMode = 'range';
}

// Nombres de meses
$monthNames = [
  '01'=>'Enero','02'=>'Febrero','03'=>'Marzo','04'=>'Abril','05'=>'Mayo','06'=>'Junio',
  '07'=>'Julio','08'=>'Agosto','09'=>'Septiembre','10'=>'Octubre','11'=>'Noviembre','12'=>'Diciembre'
];

// ID de empresa activa (para auto-seleccionar)
$activeCompanyId = isset($heading) ? $heading->id : (isset($firstCompanyId) ? $firstCompanyId : null);
@endphp

@extends('layouts/layoutMaster')

@section('vendor-style')
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}">
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}">
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/flatpickr/flatpickr.css') }}" />
<style>
/* ─── KPI Cards ─────────────────────────────────────────────────────────── */
.kpi-card {
  border:none; border-radius:14px;
  box-shadow:0 2px 12px rgba(0,0,0,.07);
  transition:transform .18s, box-shadow .18s;
}
.kpi-card:hover { transform:translateY(-3px); box-shadow:0 6px 24px rgba(0,0,0,.13); }
.kpi-icon { width:46px;height:46px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0; }
.kpi-value { font-size:1.5rem;font-weight:800;line-height:1.1; }
.kpi-label { font-size:.68rem;text-transform:uppercase;letter-spacing:.07em;opacity:.7;font-weight:600; }

/* ─── Filter card ────────────────────────────────────────────────────────── */
.filter-mode-group .btn-check:checked + .btn { box-shadow:0 0 0 3px rgba(13,110,253,.25); }
.filter-block { padding:14px 16px; border-radius:10px; border:2px solid #e9ecef; background:#fafbfc; transition:border-color .2s, background .2s; }
.filter-block.active { border-color:#0d6efd; background:#f0f6ff; }
.filter-block .block-title { font-size:.75rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; margin-bottom:10px; }

/* ─── Filter summary bar ─────────────────────────────────────────────────── */
.filter-bar { background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; padding:9px 16px; font-size:.82rem; color:#1d4ed8; display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.filter-bar .tag { background:#dbeafe; color:#1e40af; border-radius:20px; padding:2px 10px; font-weight:600; font-size:.78rem; }

/* ─── Classification badges ─────────────────────────────────────────────── */
.badge-vip       { background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff; }
.badge-frecuente { background:linear-gradient(135deg,#3b82f6,#1d4ed8);color:#fff; }
.badge-ocasional { background:linear-gradient(135deg,#10b981,#065f46);color:#fff; }
.badge-inactivo  { background:linear-gradient(135deg,#ef4444,#991b1b);color:#fff; }
.badge-clasif    { font-size:.72rem;padding:.3em .72em;border-radius:20px;font-weight:700;display:inline-block;white-space:nowrap; }

/* ─── Days badges ────────────────────────────────────────────────────────── */
.badge-days        { font-size:.72rem;padding:.3em .7em;border-radius:20px;font-weight:600;display:inline-block; }
.badge-days-ok     { background:#d1fae5;color:#065f46; }
.badge-days-warn   { background:#fef3c7;color:#92400e; }
.badge-days-danger { background:#fee2e2;color:#991b1b; }

/* ─── Table ──────────────────────────────────────────────────────────────── */
#salesByClientTable thead th { font-size:.77rem;white-space:nowrap; }
#salesByClientTable tbody td { vertical-align:middle;font-size:.83rem; }

/* ─── Select2 height fix ─────────────────────────────────────────────────── */
.select2-container--default .select2-selection--single { height:38px; border-radius:.375rem; border-color:#dee2e6; }
.select2-container--default .select2-selection--single .select2-selection__rendered { line-height:36px; padding-left:10px; }
.select2-container--default .select2-selection--single .select2-selection__arrow { height:36px; }
.select2-container { min-width:100%; }
</style>
@endsection

@section('vendor-script')
<script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/select2/select2.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/moment/moment.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/flatpickr/flatpickr.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@endsection

@section('title', 'Análisis de Ventas por Clientes')

@section('content')

{{-- Encabezado de página --}}
<div class="d-flex align-items-start justify-content-between mb-3">
  <div>
    <h4 class="fw-bold mb-0">Análisis de Ventas por Clientes</h4>
    <nav aria-label="breadcrumb" class="mt-1">
      <ol class="breadcrumb mb-0" style="font-size:.8rem;">
        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Inicio</a></li>
        <li class="breadcrumb-item">Reportes</li>
        <li class="breadcrumb-item active">Ventas por Clientes</li>
      </ol>
    </nav>
  </div>
</div>

{{-- ── Barra de filtros activos ─────────────────────────────────────────────── --}}
@if(isset($heading))
<div class="filter-bar mb-3">
  <i class="fas fa-filter"></i>
  <span>Resultados para:</span>
  <span class="tag">{{ $heading->name }}</span>

  @if($activeFilterMode === 'range' && $date_range)
    <span class="tag">📅 {{ $date_range }}</span>
  @else
    @if(isset($yearB) && $yearB)
      <span class="tag">📆 Año {{ $yearB }}</span>
    @endif
    @if(isset($period) && $period)
      <span class="tag">🗓 {{ $monthNames[$period] ?? $period }}</span>
    @endif
    @if(!isset($yearB) && !isset($period))
      <span class="tag">📅 Todo el historial</span>
    @endif
  @endif

  @if(isset($client_id) && $client_id && isset($salesByClient) && $salesByClient->count() > 0)
    @php
      $fc = $salesByClient->first();
      $fcName = $fc->tpersona === 'J' ? $fc->comercial_name : ($fc->firstname . ' ' . $fc->firstlastname);
    @endphp
    <span class="tag">👤 {{ $fcName }}</span>
  @endif

  @if(isset($salesByClient))
    <span class="ms-auto" style="font-weight:700;">
      {{ $salesByClient->count() }} {{ $salesByClient->count() === 1 ? 'cliente' : 'clientes' }} encontrados
    </span>
  @endif
</div>
@endif

{{-- ── Panel de Filtros ─────────────────────────────────────────────────────── --}}
<div class="card mb-4">
  <div class="card-header d-flex align-items-center gap-2">
    <i class="fas fa-sliders-h text-primary"></i>
    <h5 class="card-title mb-0">Filtros de Búsqueda</h5>
  </div>
  <div class="card-body">
    <form method="POST" action="{{ route('agro-report.sales-by-client-search') }}" id="searchForm">
      @csrf

      {{-- ── Fila 1: Empresa y Cliente ────────────────────────────────────── --}}
      <div class="row g-3 mb-4">
        <div class="col-md-4">
          <label for="company" class="form-label fw-semibold">
            <i class="fas fa-building me-1 text-muted"></i> Empresa <span class="text-danger">*</span>
          </label>
          <select class="form-select" name="company" id="company" required>
            @isset($companies)
              @foreach($companies as $c)
                <option value="{{ $c->id }}" {{ ($activeCompanyId == $c->id) ? 'selected' : '' }}>
                  {{ $c->name }}
                </option>
              @endforeach
            @endisset
          </select>
        </div>
        <div class="col-md-8">
          <label for="client_id" class="form-label fw-semibold">
            <i class="fas fa-user me-1 text-muted"></i> Cliente específico
            <small class="text-muted fw-normal">(opcional — deja vacío para ver todos)</small>
          </label>
          <select class="form-select" name="client_id" id="client_id">
            <option value="">— Todos los clientes —</option>
            @if(isset($client_id) && $client_id && isset($salesByClient) && $salesByClient->count() > 0)
              @php
                $fc = $salesByClient->first();
                $fcLabel = $fc->tpersona === 'J'
                  ? $fc->comercial_name
                  : ($fc->firstname . ' ' . $fc->firstlastname);
              @endphp
              <option value="{{ $client_id }}" selected>{{ $fcLabel }}</option>
            @elseif(isset($client_id) && $client_id)
              <option value="{{ $client_id }}" selected>Cliente seleccionado</option>
            @endif
          </select>
        </div>
      </div>

      {{-- ── Fila 2: Período ─────────────────────────────────────────────── --}}
      <div class="mb-3">
        <label class="form-label fw-semibold d-block mb-2">
          <i class="fas fa-calendar-alt me-1 text-muted"></i> Período de Búsqueda
        </label>

        {{-- Selector de modo --}}
        <div class="d-flex gap-2 mb-3 filter-mode-group" id="filterModeGroup">
          <input type="radio" class="btn-check" name="filter_mode_ui" id="modePeriod" value="period"
                 {{ $activeFilterMode === 'period' ? 'checked' : '' }}>
          <label class="btn btn-sm btn-outline-primary" for="modePeriod">
            <i class="fas fa-calendar me-1"></i> Por Año / Mes
          </label>

          <input type="radio" class="btn-check" name="filter_mode_ui" id="modeRange" value="range"
                 {{ $activeFilterMode === 'range' ? 'checked' : '' }}>
          <label class="btn btn-sm btn-outline-primary" for="modeRange">
            <i class="fas fa-calendar-week me-1"></i> Por Rango de Fechas
          </label>
        </div>

        {{-- Bloque: Por Año / Mes --}}
        <div id="blockPeriod" class="filter-block {{ $activeFilterMode === 'period' ? 'active' : '' }}">
          <div class="block-title text-primary">📆 Filtrar por Año y/o Mes</div>
          <div class="row g-3">
            <div class="col-sm-4 col-md-2">
              <label for="year" class="form-label fw-semibold mb-1">Año</label>
              <select class="form-select" name="year" id="year">
                <option value="">Todos los años</option>
                @php $currentYear = date('Y'); @endphp
                @for($i = 0; $i < 5; $i++)
                  @php $yr = $currentYear - $i; @endphp
                  <option value="{{ $yr }}" {{ (isset($yearB) && $yearB == $yr && $activeFilterMode === 'period') ? 'selected' : '' }}>
                    {{ $yr }}
                  </option>
                @endfor
              </select>
            </div>
            <div class="col-sm-8 col-md-4">
              <label for="period" class="form-label fw-semibold mb-1">Mes</label>
              <select class="form-select" name="period" id="period">
                <option value="">Todos los meses</option>
                @foreach($monthNames as $num => $name)
                  <option value="{{ $num }}" {{ (isset($period) && $period == $num && $activeFilterMode === 'period') ? 'selected' : '' }}>
                    {{ $name }}
                  </option>
                @endforeach
              </select>
            </div>
            <div class="col-12">
              <small class="text-muted">
                <i class="fas fa-info-circle me-1"></i>
                Puedes seleccionar solo el año, solo el mes, o ambos. Deja vacío para ver todo el historial.
              </small>
            </div>
          </div>
        </div>

        {{-- Bloque: Por Rango de Fechas --}}
        <div id="blockRange" class="filter-block {{ $activeFilterMode === 'range' ? 'active' : '' }}">
          <div class="block-title text-primary">📅 Filtrar por Rango de Fechas</div>
          <div class="row g-3 align-items-center">
            <div class="col-md-6">
              <label for="date_range" class="form-label fw-semibold mb-1">Rango de fechas</label>
              <input type="text" class="form-control" id="date_range" name="date_range"
                     placeholder="Seleccionar rango..."
                     value="{{ $activeFilterMode === 'range' && isset($date_range) ? $date_range : '' }}"
                     autocomplete="off" readonly>
            </div>
            <div class="col-md-6 d-flex align-items-end">
              <small class="text-muted">
                <i class="fas fa-info-circle me-1"></i>
                Selecciona un día de inicio y uno de fin en el calendario.
              </small>
            </div>
          </div>
          {{-- Campo oculto para enviar date_range solo si modo es range --}}
        </div>
      </div>

      {{-- ── Botones ──────────────────────────────────────────────────────── --}}
      <div class="d-flex gap-2 pt-2 border-top">
        <button type="submit" class="btn btn-primary px-4">
          <i class="fas fa-search me-1"></i> Buscar
        </button>
        <a href="{{ route('agro-report.sales-by-client') }}" class="btn btn-outline-secondary">
          <i class="fas fa-undo me-1"></i> Limpiar filtros
        </a>
      </div>

    </form>
  </div>
</div>

{{-- ── RESULTADOS ────────────────────────────────────────────────────────────── --}}
@if(isset($salesByClient) && $salesByClient->count() > 0)

  {{-- ── KPIs Gerenciales ─────────────────────────────────────────────── --}}
  @if(isset($globalKpis) && count($globalKpis) > 0)
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="card kpi-card h-100">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="kpi-icon" style="background:#ede9fe;color:#7c3aed;">🏆</div>
          <div class="overflow-hidden">
            <div class="kpi-label text-muted">Top Cliente</div>
            <div style="font-size:.88rem;font-weight:700;color:#7c3aed;" title="{{ $globalKpis['top_client_name'] }}">
              {{ Str::limit($globalKpis['top_client_name'], 22) }}
            </div>
            <div class="text-muted" style="font-size:.78rem;">${{ number_format($globalKpis['top_client_amount'],2) }}</div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card kpi-card h-100">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="kpi-icon" style="background:#dbeafe;color:#1d4ed8;">💰</div>
          <div>
            <div class="kpi-label text-muted">Ticket Promedio</div>
            <div class="kpi-value" style="color:#1d4ed8;">${{ number_format($globalKpis['avg_ticket'],2) }}</div>
            <div class="text-muted" style="font-size:.76rem;">por factura completada</div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card kpi-card h-100">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="kpi-icon" style="background:#fef3c7;color:#d97706;">🥇</div>
          <div>
            <div class="kpi-label text-muted">Clientes VIP</div>
            <div class="kpi-value" style="color:#d97706;">{{ $globalKpis['vip_count'] }}</div>
            <div class="text-muted" style="font-size:.76rem;">de {{ $globalKpis['total_clients'] }} en el período</div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card kpi-card h-100">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="kpi-icon" style="background:#fee2e2;color:#991b1b;">⚠️</div>
          <div>
            <div class="kpi-label text-muted">Inactivos +90d</div>
            <div class="kpi-value" style="color:#dc2626;">{{ $globalKpis['inactive_count'] }}</div>
            <div class="text-muted" style="font-size:.76rem;">requieren atención</div>
          </div>
        </div>
      </div>
    </div>
  </div>
  @endif

  {{-- ── Tabla de Resultados ──────────────────────────────────────────── --}}
  <div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
      <h5 class="mb-0 card-title">
        <i class="fas fa-users me-2 text-primary"></i>
        Ventas por Cliente
        @if(isset($heading))<span class="text-muted fw-normal" style="font-size:.82rem;"> — {{ $heading->name }}</span>@endif
      </h5>
      <div class="d-flex gap-2 flex-wrap">
        <button class="btn btn-success btn-sm" onclick="exportToExcel()">
          <i class="fas fa-file-excel me-1"></i> Excel
        </button>
        <div class="btn-group btn-group-sm">
          <button class="btn btn-danger" onclick="exportToPDF()">
            <i class="fas fa-eye me-1"></i> PDF
          </button>
          <button class="btn btn-outline-danger" onclick="downloadPDF()" title="Descargar PDF">
            <i class="fas fa-download"></i>
          </button>
        </div>
      </div>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-striped table-hover mb-0" id="salesByClientTable">
          <thead class="table-dark">
            <tr>
              <th>#</th>
              <th>Cliente</th>
              <th>Clasificación</th>
              <th class="text-center">Ventas</th>
              <th class="text-center">Complet.</th>
              <th class="text-center">Cancels.</th>
              <th class="text-end">Monto Total</th>
              <th class="text-end">Ticket Prom.</th>
              <th class="text-center">Sin Comprar</th>
              <th>Última Compra</th>
              <th class="text-center">Detalle</th>
            </tr>
          </thead>
          <tbody>
            @php $counter = 1; @endphp
            @foreach($salesByClient as $client)
            @php
              $classMap = [
                'VIP'      => ['badge-vip',      '🏅 VIP'],
                'Frecuente'=> ['badge-frecuente', '⭐ Frecuente'],
                'Ocasional'=> ['badge-ocasional', '🆕 Ocasional'],
                'Inactivo' => ['badge-inactivo',  '💤 Inactivo'],
              ];
              $cls = $classMap[$client->classification ?? 'Ocasional'] ?? $classMap['Ocasional'];

              $days = $client->days_since_last;
              if ($days === null)   $dBadge = '<span class="badge-days badge-days-warn">—</span>';
              elseif ($days <= 30)  $dBadge = "<span class='badge-days badge-days-ok'>{$days}d</span>";
              elseif ($days <= 90)  $dBadge = "<span class='badge-days badge-days-warn'>{$days}d</span>";
              else                  $dBadge = "<span class='badge-days badge-days-danger'>{$days}d</span>";

              // Nombre según tipo de persona
              if ($client->tpersona === 'J') {
                  $clientName = $client->name_contribuyente ?: $client->comercial_name ?: '—';
              } else {
                  $parts = array_filter([
                      $client->firstname,
                      $client->secondname,
                      $client->firstlastname,
                      $client->secondlastname,
                  ], fn($p) => !empty(trim($p ?? '')));
                  $clientName = implode(' ', $parts) ?: '—';
              }
            @endphp

            <tr>
              <td class="fw-semibold text-muted">{{ $counter++ }}</td>
              <td>
                <div class="fw-semibold">{{ $clientName }}</div>
                @if($client->email)<small class="text-muted d-block">{{ $client->email }}</small>@endif
                @if($client->nit)<small class="text-muted">NIT: {{ $client->nit }}</small>@endif
              </td>
              <td><span class="badge-clasif {{ $cls[0] }}">{{ $cls[1] }}</span></td>
              <td class="text-center"><span class="badge bg-secondary rounded-pill">{{ $client->total_sales }}</span></td>
              <td class="text-center"><span class="badge bg-success rounded-pill">{{ $client->completed_sales }}</span></td>
              <td class="text-center"><span class="badge bg-danger rounded-pill">{{ $client->cancelled_sales }}</span></td>
              <td class="text-end fw-bold text-success">${{ number_format($client->total_amount, 2) }}</td>
              <td class="text-end">${{ number_format($client->average_amount ?? 0, 2) }}</td>
              <td class="text-center">{!! $dBadge !!}</td>
              <td>
                @if($client->last_sale_date)
                  {{ \Carbon\Carbon::parse($client->last_sale_date)->format('d/m/Y') }}
                @else<span class="text-muted">—</span>@endif
              </td>
              <td class="text-center">
                <button class="btn btn-sm btn-outline-primary" onclick="showClientDetails({{ $client->client_id }})">
                  <i class="fas fa-chart-line me-1"></i> Ver
                </button>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>

  {{-- ── Gráfica Top 10 ───────────────────────────────────────────────── --}}
  @php
    $top10 = $salesByClient->take(10);
    $chartLabels = $top10->map(function($c) {
        if ($c->tpersona === 'J') {
            $n = $c->name_contribuyente ?: $c->comercial_name ?: '—';
        } else {
            $n = implode(' ', array_filter([
                $c->firstname, $c->secondname, $c->firstlastname, $c->secondlastname
            ], fn($p) => !empty(trim($p ?? '')))) ?: '—';
        }
        return Str::limit($n, 30);
    })->values()->toJson();
    $chartAmounts = $top10->pluck('total_amount')->values()->toJson();
  @endphp

  <div class="card mb-4">
    <div class="card-header">
      <h5 class="card-title mb-0"><i class="fas fa-chart-bar me-2 text-primary"></i>Top 10 Clientes por Monto Facturado</h5>
    </div>
    <div class="card-body">
      <canvas id="top10Chart" style="max-height:300px;"></canvas>
    </div>
  </div>

@elseif(isset($salesByClient))
  <div class="card">
    <div class="card-body text-center py-5">
      <i class="fas fa-search fa-3x text-muted mb-3 d-block"></i>
      <h5>Sin resultados</h5>
      <p class="text-muted mb-0">No se encontraron ventas con los filtros seleccionados.<br>Intenta ampliar el rango de fechas o quitar el filtro de cliente.</p>
    </div>
  </div>
@endif

@endsection

@section('page-script')
<script>
(function () {
  'use strict';

  // ── Variables PHP → JS ──────────────────────────────────────────────────────
  var ACTIVE_COMPANY_ID  = @json($activeCompanyId);
  var SELECTED_CLIENT_ID = @json($client_id ?? null);
  var ACTIVE_MODE        = @json($activeFilterMode);
  var CHART_LABELS       = {!! $chartLabels ?? '[]' !!};
  var CHART_AMOUNTS      = {!! $chartAmounts ?? '[]' !!};
  var CSRF_TOKEN         = '{{ csrf_token() }}';
  var ROUTE_SEARCH       = '{{ route('agro-report.sales-by-client-search') }}';
  var ROUTE_PDF          = '{{ route('agro-report.sales-by-client-pdf') }}';

  // ── Elementos del DOM ───────────────────────────────────────────────────────
  var $company    = $('#company');
  var $clientSel  = $('#client_id');
  var $yearSel    = $('#year');
  var $periodSel  = $('#period');
  var $dateRange  = $('#date_range');
  var $blockPer   = $('#blockPeriod');
  var $blockRng   = $('#blockRange');
  var $modePeriod = $('#modePeriod');
  var $modeRange  = $('#modeRange');

  // ── Select2: Empresa (sin placeholder vacío, auto-selecciona primero) ───────
  $company.select2({ width: '100%', minimumResultsForSearch: 3 });

  // ── Select2: Cliente (más grande, con búsqueda) ─────────────────────────────
  $clientSel.select2({
    placeholder: '— Todos los clientes —',
    allowClear: true,
    width: '100%',
    language: {
      noResults: function () { return 'No se encontraron clientes'; },
      searching: function () { return 'Buscando...'; }
    }
  });

  // ── Toggle bloques de período ───────────────────────────────────────────────
  function showBlock(mode) {
    if (mode === 'range') {
      $blockPer.hide().removeClass('active');
      $blockRng.show().addClass('active');
      $yearSel.val('');
      $periodSel.val('');
    } else {
      $blockRng.hide().removeClass('active');
      $blockPer.show().addClass('active');
      if (typeof $dateRange[0]._flatpickr !== 'undefined') {
        $dateRange[0]._flatpickr.clear();
      } else {
        $dateRange.val('');
      }
    }
  }

  $modePeriod.on('change', function () { if (this.checked) showBlock('period'); });
  $modeRange.on('change',  function () { if (this.checked) showBlock('range'); });

  $blockPer.hide(); $blockRng.hide();
  showBlock(ACTIVE_MODE);

  // ── Flatpickr: Rango de fechas ──────────────────────────────────────────────
  flatpickr('#date_range', {
    mode: 'range',
    dateFormat: 'Y-m-d',
    locale: 'es',
    allowInput: false,
    disableMobile: true,
  });

  // ── Cargar clientes via AJAX ────────────────────────────────────────────────
  function buildClientLabel(c) {
    var name = '';
    if (c.tpersona === 'J') {
      name = (c.name_contribuyente && c.name_contribuyente !== 'null')
        ? c.name_contribuyente
        : ((c.comercial_name && c.comercial_name !== 'null') ? c.comercial_name : '');
    } else {
      var parts = [
        (c.firstname      && c.firstname      !== 'null') ? c.firstname      : '',
        (c.secondname     && c.secondname     !== 'null') ? c.secondname     : '',
        (c.firstlastname  && c.firstlastname  !== 'null') ? c.firstlastname  : '',
        (c.secondlastname && c.secondlastname !== 'null') ? c.secondlastname : '',
      ];
      name = parts.filter(Boolean).join(' ');
    }
    if (!name && c.name_format_label && c.name_format_label !== 'null') {
      name = c.name_format_label;
    }
    if (!name) name = 'Cliente #' + c.id;

    var fiscal = '';
    if (c.nit && c.nit !== 'null') fiscal = ' · ' + c.nit;
    else if (c.ncr && c.ncr !== 'null') fiscal = ' · ' + c.ncr;

    return name + fiscal;
  }

  function loadClients(companyId, restoreId) {
    if (!companyId) {
      $clientSel.html('<option value="">— Todos los clientes —</option>').trigger('change');
      return;
    }
    fetch('/client/getclientbycompany/' + btoa(String(companyId)))
      .then(function (r) { return r.ok ? r.json() : []; })
      .then(function (data) {
        var opts = '<option value="">— Todos los clientes —</option>';
        (data || []).forEach(function (c) {
          var label = buildClientLabel(c);
          var sel   = (restoreId && String(c.id) === String(restoreId)) ? ' selected' : '';
          opts += '<option value="' + c.id + '"' + sel + '>' + label + '</option>';
        });
        $clientSel.html(opts).trigger('change');
      })
      .catch(function () {});
  }

  if (ACTIVE_COMPANY_ID) {
    loadClients(ACTIVE_COMPANY_ID, SELECTED_CLIENT_ID);
  }

  $company.on('change', function () {
    loadClients(this.value, null);
  });

  // ── DataTable ───────────────────────────────────────────────────────────────
  if ($('#salesByClientTable').length) {
    $('#salesByClientTable').DataTable({
      responsive: false,
      language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' },
      order: [[6, 'desc']],
      pageLength: 25,
      columnDefs: [{ orderable: false, targets: [10] }]
    });
  }

  // ── Chart.js Top 10 ─────────────────────────────────────────────────────────
  var chartEl = document.getElementById('top10Chart');
  if (chartEl && CHART_AMOUNTS.length) {
    var palette = ['#7c3aed','#f59e0b','#10b981','#3b82f6','#ec4899','#06b6d4','#f97316','#8b5cf6','#14b8a6','#84cc16'];
    new Chart(chartEl, {
      type: 'bar',
      data: {
        labels: CHART_LABELS,
        datasets: [{
          label: 'Monto Total ($)',
          data: CHART_AMOUNTS,
          backgroundColor: CHART_AMOUNTS.map(function (v, i) { return palette[i % palette.length]; }),
          borderRadius: 6,
          borderSkipped: false
        }]
      },
      options: {
        indexAxis: 'y',
        responsive: true,
        plugins: {
          legend: { display: false },
          tooltip: { callbacks: { label: function (c) { return ' $' + parseFloat(c.raw).toLocaleString('es-SV', { minimumFractionDigits: 2 }); } } }
        },
        scales: {
          x: { grid: { color: 'rgba(0,0,0,.04)' }, ticks: { callback: function (v) { return '$' + Number(v).toLocaleString('es-SV'); } } },
          y: { grid: { display: false } }
        }
      }
    });
  }

  function buildForm(extra) {
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = ROUTE_SEARCH;
    var base = {
      '_token'    : CSRF_TOKEN,
      'company'   : $company.val(),
      'year'      : $yearSel.val(),
      'period'    : $periodSel.val(),
      'date_range': $dateRange.val(),
      'client_id' : $clientSel.val(),
    };
    Object.assign(base, extra || {});
    Object.entries(base).forEach(function ([k, v]) {
      var i = document.createElement('input'); i.type = 'hidden'; i.name = k; i.value = v || '';
      form.appendChild(i);
    });
    return form;
  }

  window.showClientDetails = function (clientId) {
    var f = buildForm({ client_id: clientId, show_details: '1' });
    document.body.appendChild(f); f.submit(); document.body.removeChild(f);
  };

  window.exportToExcel = function () {
    var f = buildForm({ export_excel: '1' });
    f.target = '_blank';
    document.body.appendChild(f); f.submit(); document.body.removeChild(f);
  };

  window.exportToPDF = function () {
    var form = document.createElement('form');
    form.method = 'GET'; form.action = ROUTE_PDF; form.target = '_blank';
    [['company', $company.val()], ['year', $yearSel.val()], ['period', $periodSel.val()], ['client_id', $clientSel.val()]].forEach(function ([n, v]) {
      var i = document.createElement('input'); i.type = 'hidden'; i.name = n; i.value = v || ''; form.appendChild(i);
    });
    document.body.appendChild(form); form.submit(); document.body.removeChild(form);
  };

  window.downloadPDF = function () {
    var form = document.createElement('form');
    form.method = 'GET'; form.action = ROUTE_PDF;
    [['company', $company.val()], ['year', $yearSel.val()], ['period', $periodSel.val()], ['client_id', $clientSel.val()]].forEach(function ([n, v]) {
      var i = document.createElement('input'); i.type = 'hidden'; i.name = n; i.value = v || ''; form.appendChild(i);
    });
    var d = document.createElement('input'); d.type = 'hidden'; d.name = 'download'; d.value = '1'; form.appendChild(d);
    document.body.appendChild(form); form.submit(); document.body.removeChild(form);
  };

}());
</script>
@endsection
