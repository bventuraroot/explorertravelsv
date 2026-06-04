@php
$configData = Helper::appClasses();

// Agrupar el detalle: una fila por venta, productos como lista dentro
$salesGrouped = collect();
if (isset($salesDetails) && $salesDetails->count() > 0) {
    $salesGrouped = $salesDetails->groupBy('sale_id')->map(function ($rows) {
        $first = $rows->first();
        return (object) [
            'sale_id'       => $first->sale_id,
            'formatted_date'=> $first->formatted_date,
            'date'          => $first->date,
            'document_type' => $first->document_type,
            'client_name'   => $first->client_name,
            'state'         => $first->state,
            'totalamount'   => $first->totalamount,
            'products'      => $rows->map(fn($r) => (object)[
                'name'      => $r->product_name,
                'qty'       => $r->quantity,
                'price'     => $r->pricesale,
                'subtotal'  => $r->quantity * $r->pricesale,
                'exempt'    => $r->exempt,
            ]),
        ];
    })->values()->sortByDesc('date');

    $firstRow   = $salesDetails->first();
    $clientName = $firstRow->client_name;

    $completedSales = $salesGrouped->where('state', 1);
    $totalAmount    = $completedSales->sum('totalamount');
    $avgTicket      = $completedSales->count() > 0 ? $totalAmount / $completedSales->count() : 0;

    // Frecuencia
    $dates    = $completedSales->sortBy('date')->pluck('date');
    $daysSinceLast    = $dates->count() > 0 ? now()->diffInDays(\Carbon\Carbon::parse($dates->last())) : null;
    $purchaseFrequency= null;
    if ($dates->count() > 1) {
        $span = \Carbon\Carbon::parse($dates->first())->diffInDays(\Carbon\Carbon::parse($dates->last()));
        $purchaseFrequency = $span > 0 ? round($span / ($dates->count() - 1)) : 0;
    }
}

// Periodo en texto
$monthNames = ['01'=>'Enero','02'=>'Febrero','03'=>'Marzo','04'=>'Abril','05'=>'Mayo','06'=>'Junio',
               '07'=>'Julio','08'=>'Agosto','09'=>'Septiembre','10'=>'Octubre','11'=>'Noviembre','12'=>'Diciembre'];
$periodoTexto = '';
if (isset($date_range) && $date_range) {
    $periodoTexto = $date_range;
} elseif (isset($yearB) && $yearB) {
    $periodoTexto = 'Año ' . $yearB;
    if (isset($period) && $period) $periodoTexto .= ' · ' . ($monthNames[$period] ?? $period);
} else {
    $periodoTexto = 'Todo el historial';
}
@endphp

@extends('layouts/layoutMaster')

@section('vendor-style')
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}">
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}">
<style>
/* ── KPI mini cards ─────────────────────────────────────────────────────── */
.stat-card { border:none;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,.06); }
.stat-icon  { width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0; }

/* ── Header del cliente ─────────────────────────────────────────────────── */
.client-header { background:linear-gradient(135deg,#1e3a8a 0%,#3b82f6 100%); color:#fff; border-radius:12px; padding:20px 24px; }
.client-header .meta { font-size:.82rem;opacity:.85; }

/* ── Tabla del reporte ──────────────────────────────────────────────────── */
#detailsTable thead th { font-size:.77rem;white-space:nowrap; }
#detailsTable tbody td { vertical-align:top;font-size:.82rem; }

/* ── Lista de productos dentro de la celda ─────────────────────────────── */
.product-list { list-style:none;padding:0;margin:0; }
.product-list li { padding:3px 0;border-bottom:1px solid #f1f5f9; }
.product-list li:last-child { border-bottom:none; }
.product-pill { display:inline-block;background:#f1f5f9;border-radius:4px;padding:1px 7px;font-size:.72rem;color:#475569; }
</style>
@endsection

@section('vendor-script')
<script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
@endsection

@section('title', 'Detalle de Ventas — ' . (isset($clientName) ? $clientName : 'Cliente'))

@section('content')

{{-- Encabezado de página --}}
<div class="d-flex align-items-start justify-content-between mb-3">
  <div>
    <h4 class="fw-bold mb-0">Detalle de Ventas por Cliente</h4>
    <nav aria-label="breadcrumb" class="mt-1">
      <ol class="breadcrumb mb-0" style="font-size:.8rem;">
        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Inicio</a></li>
        <li class="breadcrumb-item"><a href="{{ route('agro-report.sales-by-client') }}">Reportes</a></li>
        <li class="breadcrumb-item active">Detalle del Cliente</li>
      </ol>
    </nav>
  </div>
  <a href="{{ route('client.index') }}" class="btn btn-outline-secondary btn-sm">
    <i class="fas fa-arrow-left me-1"></i> Regresar
  </a>
</div>

@if(isset($salesGrouped) && $salesGrouped->count() > 0)

  {{-- ── Encabezado del cliente ──────────────────────────────────────────── --}}
  <div class="client-header mb-4">
    <div class="d-flex align-items-center gap-3 flex-wrap">
      <div style="width:52px;height:52px;border-radius:50%;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;">
        👤
      </div>
      <div class="flex-grow-1">
        <h5 class="mb-0 fw-bold">{{ $clientName }}</h5>
        <div class="meta">
          @if(isset($heading))<span class="me-3"><i class="fas fa-building me-1"></i>{{ $heading->name }}</span>@endif
          <span><i class="fas fa-calendar-alt me-1"></i>{{ $periodoTexto }}</span>
        </div>
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-light btn-sm" onclick="exportToExcel()">
          <i class="fas fa-file-excel me-1 text-success"></i> Excel
        </button>
        <button class="btn btn-light btn-sm" onclick="exportToPDF()">
          <i class="fas fa-eye me-1 text-danger"></i> PDF
        </button>
      </div>
    </div>
  </div>

  {{-- ── Estadísticas del cliente ─────────────────────────────────────────── --}}
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="card stat-card h-100">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="stat-icon" style="background:#dbeafe;color:#1d4ed8;">🧾</div>
          <div>
            <div style="font-size:.68rem;text-transform:uppercase;letter-spacing:.06em;color:#6b7280;font-weight:600;">Total Facturas</div>
            <div style="font-size:1.5rem;font-weight:800;color:#1e3a8a;line-height:1.1;">{{ $salesGrouped->count() }}</div>
            <div style="font-size:.75rem;color:#6b7280;">{{ $completedSales->count() }} completadas</div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card stat-card h-100">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="stat-icon" style="background:#d1fae5;color:#065f46;">💵</div>
          <div>
            <div style="font-size:.68rem;text-transform:uppercase;letter-spacing:.06em;color:#6b7280;font-weight:600;">Monto Total</div>
            <div style="font-size:1.3rem;font-weight:800;color:#065f46;line-height:1.1;">${{ number_format($totalAmount, 2) }}</div>
            <div style="font-size:.75rem;color:#6b7280;">solo completadas</div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card stat-card h-100">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="stat-icon" style="background:#ede9fe;color:#7c3aed;">📋</div>
          <div>
            <div style="font-size:.68rem;text-transform:uppercase;letter-spacing:.06em;color:#6b7280;font-weight:600;">Ticket Promedio</div>
            <div style="font-size:1.3rem;font-weight:800;color:#7c3aed;line-height:1.1;">${{ number_format($avgTicket, 2) }}</div>
            <div style="font-size:.75rem;color:#6b7280;">por factura</div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card stat-card h-100">
        <div class="card-body d-flex align-items-center gap-3">
          @if($daysSinceLast !== null && $daysSinceLast > 90)
            <div class="stat-icon" style="background:#fee2e2;color:#991b1b;">⚠️</div>
            @php $daysColor = '#991b1b'; @endphp
          @elseif($daysSinceLast !== null && $daysSinceLast > 30)
            <div class="stat-icon" style="background:#fef3c7;color:#92400e;">🕐</div>
            @php $daysColor = '#92400e'; @endphp
          @else
            <div class="stat-icon" style="background:#d1fae5;color:#065f46;">✅</div>
            @php $daysColor = '#065f46'; @endphp
          @endif
          <div>
            <div style="font-size:.68rem;text-transform:uppercase;letter-spacing:.06em;color:#6b7280;font-weight:600;">Días sin Comprar</div>
            <div style="font-size:1.5rem;font-weight:800;color:{{ $daysColor }};line-height:1.1;">
              {{ $daysSinceLast !== null ? $daysSinceLast . 'd' : '—' }}
            </div>
            @if($purchaseFrequency)
              <div style="font-size:.75rem;color:#6b7280;">Compra cada {{ $purchaseFrequency }}d</div>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- ── Tabla principal: una fila por venta ─────────────────────────────── --}}
  <div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
      <h5 class="card-title mb-0">
        <i class="fas fa-list-alt me-2 text-primary"></i>
        Historial de Ventas
      </h5>
      <span class="badge bg-primary rounded-pill">{{ $salesGrouped->count() }} registros</span>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-striped table-hover mb-0" id="detailsTable">
          <thead class="table-dark">
            <tr>
              <th style="width:100px;">Fecha</th>
              <th style="width:120px;">Tipo Doc.</th>
              <th style="width:90px;" class="text-center">Estado</th>
              <th>Productos</th>
              <th style="width:110px;" class="text-end">Total Venta</th>
            </tr>
          </thead>
          <tbody>
            @foreach($salesGrouped as $sale)
            <tr>
              <td>
                <span class="fw-semibold">{{ $sale->formatted_date }}</span>
                <br><small class="text-muted">#{{ $sale->sale_id }}</small>
              </td>
              <td>{{ $sale->document_type }}</td>
              <td class="text-center">
                @if($sale->state == 1)
                  <span class="badge bg-success">Completada</span>
                @else
                  <span class="badge bg-danger">Cancelada</span>
                @endif
              </td>
              <td>
                @if($sale->products->count() === 1)
                  {{-- Una sola línea si hay un producto --}}
                  @php $p = $sale->products->first(); @endphp
                  <span>{{ $p->name }}</span>
                  <span class="ms-2 product-pill">×{{ number_format($p->qty, 0) }} · ${{ number_format($p->price, 2) }}c/u</span>
                  @if($p->exempt)
                    <span class="ms-1 badge bg-label-info" style="font-size:.68rem;">Exento</span>
                  @endif
                @else
                  {{-- Lista compacta si hay varios productos --}}
                  <ul class="product-list">
                    @foreach($sale->products as $p)
                    <li>
                      <span>{{ $p->name }}</span>
                      <span class="ms-2 product-pill">×{{ number_format($p->qty, 0) }} · ${{ number_format($p->price, 2) }}c/u</span>
                      @if($p->exempt)
                        <span class="ms-1 badge bg-label-info" style="font-size:.68rem;">Exento</span>
                      @endif
                    </li>
                    @endforeach
                  </ul>
                @endif
              </td>
              <td class="text-end">
                <span class="fw-bold {{ $sale->state == 1 ? 'text-success' : 'text-danger' }}">
                  ${{ number_format($sale->totalamount, 2) }}
                </span>
              </td>
            </tr>
            @endforeach
          </tbody>
          <tfoot>
            <tr class="table-light">
              <td colspan="4" class="text-end fw-semibold">Total (completadas):</td>
              <td class="text-end fw-bold text-success" style="font-size:1rem;">${{ number_format($totalAmount, 2) }}</td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>

@elseif(isset($salesDetails))
  <div class="card">
    <div class="card-body text-center py-5">
      <i class="fas fa-search fa-3x text-muted mb-3 d-block"></i>
      <h5>Sin resultados</h5>
      <p class="text-muted">No se encontraron ventas para este cliente en el período seleccionado.</p>
      <a href="{{ route('agro-report.sales-by-client') }}" class="btn btn-primary">
        <i class="fas fa-arrow-left me-1"></i> Volver al reporte
      </a>
    </div>
  </div>
@endif

@endsection

@section('page-script')
<script>
$(document).ready(function () {
  if ($('#detailsTable').length) {
    $('#detailsTable').DataTable({
      language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' },
      order: [[0, 'desc']],
      pageLength: 25,
      responsive: false,
      columnDefs: [{ orderable: false, targets: [3] }]
    });
  }
});

function _buildForm(extra) {
  var form = document.createElement('form');
  form.method = 'POST';
  form.action = '{{ route('agro-report.sales-by-client-search') }}';
  var fields = Object.assign({
    '_token'      : '{{ csrf_token() }}',
    'company'     : '{{ isset($heading) ? $heading->id : '' }}',
    'year'        : '{{ isset($yearB) ? $yearB : '' }}',
    'period'      : '{{ isset($period) ? $period : '' }}',
    'date_range'  : '{{ isset($date_range) ? $date_range : '' }}',
    'client_id'   : '{{ isset($client_id) ? $client_id : '' }}',
    'show_details': '1',
  }, extra || {});
  Object.entries(fields).forEach(function ([k, v]) {
    var i = document.createElement('input'); i.type = 'hidden'; i.name = k; i.value = v; form.appendChild(i);
  });
  return form;
}

function exportToExcel() {
  var f = _buildForm({ export_excel: '1' });
  f.target = '_blank';
  document.body.appendChild(f); f.submit(); document.body.removeChild(f);
}

function exportToPDF() {
  var form = document.createElement('form');
  form.method = 'GET';
  form.action = '{{ route('agro-report.sales-by-client-details-pdf') }}';
  form.target = '_blank';
  [
    ['company', '{{ isset($heading) ? $heading->id : '' }}'],
    ['year',    '{{ isset($yearB) ? $yearB : '' }}'],
    ['period',  '{{ isset($period) ? $period : '' }}'],
    ['client_id','{{ isset($client_id) ? $client_id : '' }}'],
  ].forEach(function ([n, v]) {
    var i = document.createElement('input'); i.type = 'hidden'; i.name = n; i.value = v; form.appendChild(i);
  });
  document.body.appendChild(form); form.submit(); document.body.removeChild(form);
}
</script>
@endsection
