@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('vendor-style')
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}" />
@endsection

@section('vendor-script')
<script src="{{ asset('assets/vendor/libs/select2/select2.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endsection

@section('page-script')
<script>
$(document).ready(function() {
    $('.select2').select2();

    $('#btn-export-excel').on('click', function() {
        var company = $('#company').val();
        var year    = $('#year').val();
        var period  = $('#period').val();

        if (!company) {
            Swal.fire({ icon: 'warning', title: 'Atención', text: 'Primero realiza una búsqueda para exportar.' });
            return;
        }

        var form = $('<form>', { method: 'POST', action: '{{ route("report.facturasTerceros.excel") }}' });
        form.append($('<input>', { type: 'hidden', name: '_token', value: '{{ csrf_token() }}' }));
        form.append($('<input>', { type: 'hidden', name: 'company', value: company }));
        form.append($('<input>', { type: 'hidden', name: 'year',    value: year }));
        form.append($('<input>', { type: 'hidden', name: 'period',  value: period }));
        $('body').append(form);
        form.submit();
        form.remove();
    });
});
</script>
@endsection

@section('content')
<h4 class="py-3 mb-4">
    <span class="text-muted fw-light">Reportes / </span>Facturas Terceros – Mandante/Mandatario
</h4>

<div class="card">
    <div class="card-datatable table-responsive">
        <form method="POST" action="{{ route('report.facturasTerceros.search') }}">
            @csrf
            <div class="container">
                <br>
                <div class="row">
                    <div class="mb-3 col-md-3">
                        <label for="company" class="form-label">Empresa (Mandatario)</label>
                        <select class="form-control select2" id="company" name="company" required>
                            <option value="" disabled>Seleccionar...</option>
                            @php $companiesList = DB::table('companies')->get(); @endphp
                            @foreach ($companiesList as $comp)
                                <option value="{{ $comp->id }}"
                                    {{ (isset($heading) && $heading->id == $comp->id) || (!isset($heading) && $loop->first) ? 'selected' : '' }}>
                                    {{ $comp->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3 col-md-2">
                        <label for="year" class="form-label">Año</label>
                        <input type="number" id="year" name="year" class="form-control"
                               placeholder="Ej: 2025" min="2020" max="2099"
                               value="{{ $yearB ?? date('Y') }}" required>
                    </div>
                    <div class="mb-3 col-md-3">
                        <label for="period" class="form-label">Período (Mes)</label>
                        <select class="form-control" id="period" name="period" required>
                            @php
                                $meses = ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
                                          'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
                                $mesDefault = isset($period) ? (int)$period : (date('n') == 1 ? 12 : date('n') - 1);
                            @endphp
                            @foreach ($meses as $idx => $mes)
                                <option value="{{ $idx + 1 }}" {{ $mesDefault == ($idx + 1) ? 'selected' : '' }}>
                                    {{ $mes }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3 col-md-4 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-magnifying-glass me-1"></i> Buscar
                        </button>
                        @if(isset($sales))
                        <button type="button" id="btn-export-excel" class="btn btn-success">
                            <i class="fa-solid fa-file-excel me-1"></i> Exportar Excel
                        </button>
                        @endif
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

@if(isset($sales))
<div class="card mt-3">
    <div class="card-body p-0">

        @php
            $i = 1;
            $tot_iva = 0;
        @endphp

        {{-- Encabezado del reporte --}}
        <div class="text-center py-3">
            <h5 class="fw-bold mb-1">FACTURAS TERCEROS – MANDANTE / MANDATARIO</h5>
            <p class="mb-0" style="font-size: 13px;">
                <strong>Mandatario:</strong> {{ $heading->name ?? '' }} &nbsp;
                <strong>NIT:</strong> {{ $heading->nit ?? '' }} &nbsp;
                <strong>NRC:</strong> {{ $heading->nrc ?? '' }} &nbsp;
                <strong>MES:</strong> {{ strtoupper($mesesDelAno[(int)$period - 1]) }} &nbsp;
                <strong>AÑO:</strong> {{ $yearB }}
            </p>
        </div>

        <div style="overflow-x: auto; padding: 0 16px 16px;">
            <table class="table table-bordered" style="min-width: 2200px; font-size: 11px;">
                <thead>
                    {{-- Fila 1: grupos --}}
                    <tr style="background:#1e3a5f; color:#fff; text-align:center; font-weight:bold;">
                        <th rowspan="2" style="vertical-align:middle;">N°</th>
                        <th rowspan="2" style="vertical-align:middle; min-width:120px;">NIT MANDANTE</th>
                        <th rowspan="2" style="vertical-align:middle; min-width:100px;">NRC MANDANTE</th>
                        <th rowspan="2" style="vertical-align:middle; min-width:200px;">NOMBRE / RAZÓN SOCIAL MANDANTE</th>
                        <th rowspan="2" style="vertical-align:middle;">FECHA EMISIÓN</th>
                        <th rowspan="2" style="vertical-align:middle; min-width:100px;">TIPO DOC.</th>
                        <th rowspan="2" style="vertical-align:middle;">SERIE</th>
                        <th rowspan="2" style="vertical-align:middle; min-width:180px;">Nº RESOLUCIÓN<br><small>(Control DTE)</small></th>
                        <th rowspan="2" style="vertical-align:middle; min-width:280px;">Nº DOCUMENTO<br><small>(Cód. Generación)</small></th>
                        <th rowspan="2" style="vertical-align:middle;">IVA DE LA<br>OPERACIÓN</th>
                        <th colspan="3" style="background:#2e5fa3;">COMPROBANTE DE LIQUIDACIÓN RELACIONADO</th>
                        <th rowspan="2" style="vertical-align:middle;">ESTADO</th>
                    </tr>
                    {{-- Fila 2: sub-columnas CLQ --}}
                    <tr style="background:#2e5fa3; color:#fff; text-align:center; font-weight:bold;">
                        <th style="min-width:180px;">RESOLUCIÓN CLQ<br><small>(Control)</small></th>
                        <th style="min-width:280px;">Nº COMPROBANTE<br><small>(Cód. Generación)</small></th>
                        <th style="min-width:110px;">FECHA CLQ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sales as $sale)
                        @php
                            $nrcLimpio = preg_replace('/[^0-9]/', '', $sale->mandante_ncr ?? '');
                            $tipoCod   = $sale->tipo_documento_cod
                                           ? $sale->tipo_documento_cod . ' - ' . ($sale->tipo_documento_desc ?? '')
                                           : ($sale->tipo_documento_desc ?? '-');
                            $liquidado = $sale->estado_liquidacion === 'Liquidado';
                            $rowBg     = $liquidado ? '#f0fff0' : '#fff';
                            $iva       = floatval($sale->iva_operacion ?? 0);
                            $tot_iva  += $iva;
                        @endphp
                        <tr style="background:{{ $rowBg }};">
                            <td style="text-align:center;">{{ $i }}</td>
                            <td style="white-space:nowrap;">{{ $sale->mandante_nit ?? '-' }}</td>
                            <td style="white-space:nowrap; text-align:right;">{{ $nrcLimpio ?: '-' }}</td>
                            <td class="text-uppercase">{{ $sale->mandante_nombre ?? '-' }}</td>
                            <td style="text-align:center; white-space:nowrap;">{{ $sale->fecha_emision ?? '-' }}</td>
                            <td style="text-align:center; white-space:nowrap;">{{ $tipoCod }}</td>
                            <td style="text-align:center;">-</td>
                            <td style="font-size:9px; white-space:nowrap;">{{ $sale->numero_control ?? '-' }}</td>
                            <td style="font-size:9px; white-space:nowrap;">{{ $sale->codigo_generacion ?? '-' }}</td>
                            <td style="text-align:right;">{{ number_format($iva, 2) }}</td>
                            <td style="font-size:9px; white-space:nowrap; background:#f0f4ff;">
                                {{ $sale->clq_numero_control ?? '-' }}
                            </td>
                            <td style="font-size:9px; white-space:nowrap; background:#f0f4ff;">
                                {{ $sale->clq_codigo_generacion ?? '-' }}
                            </td>
                            <td style="text-align:center; white-space:nowrap; background:#f0f4ff;">
                                {{ $sale->clq_fecha ?? '-' }}
                            </td>
                            <td style="text-align:center;">
                                @if($liquidado)
                                    <span class="badge bg-success">Liquidado</span>
                                @else
                                    <span class="badge bg-warning text-dark">Pendiente</span>
                                @endif
                            </td>
                        </tr>
                        @php $i++; @endphp
                    @empty
                        <tr>
                            <td colspan="14" class="text-center text-muted py-4">
                                No se encontraron facturas de terceros para el período seleccionado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr style="background:#c9daf8; font-weight:bold;">
                        <td colspan="9" style="text-align:right; padding-right:8px;">TOTALES DEL MES</td>
                        <td style="text-align:right;">{{ number_format($tot_iva, 2) }}</td>
                        <td colspan="4"></td>
                    </tr>
                </tfoot>
            </table>
        </div>

    </div>
</div>
@endif

@endsection
