@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('vendor-style')
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}">
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}">
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css') }}">
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/formvalidation/dist/css/formValidation.min.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/flatpickr/flatpickr.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/bootstrap-datepicker/bootstrap-datepicker.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/bootstrap-daterangepicker/bootstrap-daterangepicker.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/jquery-timepicker/jquery-timepicker.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/pickr/pickr-themes.css') }}" />
@endsection

@section('vendor-script')
<script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/select2/select2.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/formvalidation/dist/js/FormValidation.min.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/formvalidation/dist/js/plugins/Bootstrap5.min.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/formvalidation/dist/js/plugins/AutoFocus.min.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/cleavejs/cleave.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/cleavejs/cleave-phone.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/moment/moment.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/flatpickr/flatpickr.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/bootstrap-datepicker/bootstrap-datepicker.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/bootstrap-daterangepicker/bootstrap-daterangepicker.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/jquery-timepicker/jquery-timepicker.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/pickr/pickr.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endsection

@section('page-script')
<script>
$(document).ready(function() {
    $('#btn-export-excel').on('click', function() {
        var company = $('#company').val();
        var year    = $('#year').val();
        var period  = $('#period').val();

        if (!company) {
            Swal.fire({
                icon: 'warning',
                title: 'Atención',
                text: 'Por favor, primero realiza una búsqueda para generar el reporte.'
            });
            return;
        }

        var form = $('<form>', {
            'method': 'POST',
            'action': '{{ route("report.facturasTerceros.excel") }}'
        });
        form.append($('<input>', { 'type': 'hidden', 'name': '_token',   'value': '{{ csrf_token() }}' }));
        form.append($('<input>', { 'type': 'hidden', 'name': 'company',  'value': company }));
        form.append($('<input>', { 'type': 'hidden', 'name': 'year',     'value': year }));
        form.append($('<input>', { 'type': 'hidden', 'name': 'period',   'value': period }));
        $('body').append(form);
        form.submit();
        form.remove();
    });
});
</script>
@endsection

@section('title', 'Facturas Terceros')

@section('content')
<h4 class="py-3 mb-4 fw-bold">
    <span class="text-muted fw-light">Reportes /</span> Facturas Terceros – Mandante/Mandatario
</h4>

<div class="card">
    <form id="buscar" action="{{ route('report.facturasTerceros.search') }}" method="POST">
        @csrf
        <div class="card-header">
            <div class="row">
                <div class="col-4">
                    <div class="row g-3">
                        <select class="form-control select2" name="company" id="company">
                            @php $companiesList = DB::table('companies')->get(); @endphp
                            @foreach ($companiesList as $comp)
                                <option value="{{ $comp->id }}"
                                    {{ (isset($heading) && $heading->id == $comp->id) || (!isset($heading) && $loop->first) ? 'selected' : '' }}>
                                    {{ $comp->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-1">
                    <div class="row g-3">
                        <select class="form-control" name="year" id="year">
                            <?php
                            $currentYear = date("Y");
                            for ($yi = 0; $yi < 5; $yi++) {
                                $y = $currentYear - $yi;
                                $sel = (isset($yearB) && $yearB == $y) ? 'selected' : '';
                                echo "<option value='{$y}' {$sel}>{$y}</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="col-4">
                    <div class="row g-3">
                        <select class="form-control" name="period" id="period">
                            <?php
                            if (empty($period)) {
                                $period = (date('n') == 1) ? '12' : sprintf('%02d', date('n') - 1);
                            }
                            ?>
                            <option value="01" <?php echo (@$period == '01') ? 'selected' : '' ?>>Enero</option>
                            <option value="02" <?php echo (@$period == '02') ? 'selected' : '' ?>>Febrero</option>
                            <option value="03" <?php echo (@$period == '03') ? 'selected' : '' ?>>Marzo</option>
                            <option value="04" <?php echo (@$period == '04') ? 'selected' : '' ?>>Abril</option>
                            <option value="05" <?php echo (@$period == '05') ? 'selected' : '' ?>>Mayo</option>
                            <option value="06" <?php echo (@$period == '06') ? 'selected' : '' ?>>Junio</option>
                            <option value="07" <?php echo (@$period == '07') ? 'selected' : '' ?>>Julio</option>
                            <option value="08" <?php echo (@$period == '08') ? 'selected' : '' ?>>Agosto</option>
                            <option value="09" <?php echo (@$period == '09') ? 'selected' : '' ?>>Septiembre</option>
                            <option value="10" <?php echo (@$period == '10') ? 'selected' : '' ?>>Octubre</option>
                            <option value="11" <?php echo (@$period == '11') ? 'selected' : '' ?>>Noviembre</option>
                            <option value="12" <?php echo (@$period == '12') ? 'selected' : '' ?>>Diciembre</option>
                        </select>
                    </div>
                </div>
                <div class="col-3">
                    <button type="submit"
                        class="btn rounded-pill btn-primary waves-effect waves-light">Buscar</button>
                </div>
            </div>
        </div>
    </form>

    @isset($heading)
    <?php
    $mesesDelAno = ["Enero","Febrero","Marzo","Abril","Mayo","Junio",
                    "Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre"];
    $mesesDelAnoMayuscula = array_map('strtoupper', $mesesDelAno);
    ?>
    <div class="row">
        <div class="col-12">
            <div class="box-header" style="text-align: right; margin-right: 6%;">
                <button type="button" class="btn btn-primary" id="btn-export-excel">
                    <i class="fa-solid fa-file-excel"></i> &nbsp;&nbsp;Exportar a Excel
                </button>
                &nbsp;
                <a href="#!" class="btn btn-success" onclick="impFAC('areaImprimir');">
                    <i class="fa-solid fa-print"></i> &nbsp;&nbsp;Imprimir
                </a>
            </div>
        </div>
    </div>
    <style>
        .report-container {
            max-height: 70vh;
            overflow: auto;
        }
        .report-container table {
            font-size: 11px;
        }
        .report-container thead td,
        .report-container thead th {
            font-size: 11px;
        }
        .report-container tbody td {
            font-size: 10px;
        }
    </style>
    <div id="areaImprimir" class="report-container table-responsive">
        <table class="table table-sm table-bordered" style="min-width: 2200px;">
            <thead style="font-size: 11px;">
                <tr>
                    <th class="text-center" colspan="14">
                        FACTURAS TERCEROS – MANDANTE / MANDATARIO (Valores expresados en USD)
                    </th>
                </tr>
                <tr>
                    <td class="text-center" colspan="14" style="font-size: 12px;">
                        <b>Mandatario:</b> {{ $heading->name }} &nbsp;&nbsp;
                        <b>NIT:</b> {{ $heading->nit ?? '-' }} &nbsp;&nbsp;
                        <b>NRC:</b> {{ $heading->nrc ?? '-' }} &nbsp;&nbsp;
                        <b>MES:</b> {{ $mesesDelAnoMayuscula[(int)$period - 1] }} &nbsp;&nbsp;
                        <b>AÑO:</b> {{ $yearB }}
                    </td>
                </tr>
                <tr style="text-transform: uppercase;">
                    <td style="font-size: 10px; text-align: center; width: 35px;"><b>N°</b></td>
                    <td style="font-size: 10px; text-align: left; width: 110px;"><b>NIT MANDANTE</b></td>
                    <td style="font-size: 10px; text-align: left; width: 90px;"><b>NRC MANDANTE</b></td>
                    <td style="font-size: 10px; width: 180px;"><b>NOMBRE / RAZÓN SOCIAL MANDANTE</b></td>
                    <td style="font-size: 10px; text-align: center; width: 75px;"><b>FECHA EMISIÓN</b></td>
                    <td style="font-size: 10px; text-align: center; width: 90px;"><b>TIPO DOC.</b></td>
                    <td style="font-size: 10px; text-align: center; width: 50px;"><b>SERIE</b></td>
                    <td style="font-size: 10px; text-align: center; min-width: 180px;"><b>Nº RESOLUCIÓN<br>(CONTROL DTE)</b></td>
                    <td style="font-size: 10px; text-align: center; min-width: 280px;"><b>Nº DOCUMENTO<br>(CÓD. GENERACIÓN)</b></td>
                    <td style="font-size: 10px; text-align: right; width: 80px;"><b>IVA DE LA<br>OPERACIÓN</b></td>
                    <td style="font-size: 10px; text-align: center; min-width: 180px;"><b>RESOLUCIÓN CLQ<br>(CONTROL)</b></td>
                    <td style="font-size: 10px; text-align: center; min-width: 280px;"><b>Nº COMPROBANTE<br>(CÓD. GEN.)</b></td>
                    <td style="font-size: 10px; text-align: center; width: 80px;"><b>FECHA CLQ</b></td>
                    <td style="font-size: 10px; text-align: center; width: 80px;"><b>ESTADO</b></td>
                </tr>
            </thead>
            <tbody>
                @php
                    $i = 1;
                    $tot_iva = 0;
                @endphp
                @foreach ($sales as $sale)
                @php
                    $nrcLimpio = preg_replace('/[^0-9]/', '', $sale->mandante_ncr ?? '');
                    $tipoCod   = $sale->tipo_documento_cod
                                   ? $sale->tipo_documento_cod . ' - ' . ($sale->tipo_documento_desc ?? '')
                                   : ($sale->tipo_documento_desc ?? '-');
                    $iva       = floatval($sale->iva_operacion ?? 0);
                    $tot_iva  += $iva;
                @endphp
                <tr>
                    <td style="font-size: 10px; text-align: center; padding-top: 0; padding-bottom: 0;">{{ $i }}</td>
                    <td style="font-size: 9px; padding-top: 0; padding-bottom: 0; white-space: nowrap;">{{ $sale->mandante_nit ?? '-' }}</td>
                    <td style="font-size: 10px; text-align: right; padding-top: 0; padding-bottom: 0;">{{ $nrcLimpio ?: '-' }}</td>
                    <td class="text-uppercase" style="font-size: 10px; padding-top: 0; padding-bottom: 0;">{{ $sale->mandante_nombre ?? '-' }}</td>
                    <td style="font-size: 10px; text-align: center; padding-top: 0; padding-bottom: 0; white-space: nowrap;">{{ $sale->fecha_emision ?? '-' }}</td>
                    <td style="font-size: 10px; text-align: center; padding-top: 0; padding-bottom: 0; white-space: nowrap;">{{ $tipoCod }}</td>
                    <td style="font-size: 10px; text-align: center; padding-top: 0; padding-bottom: 0;">-</td>
                    <td style="font-size: 8px; text-align: center; padding-top: 0; padding-bottom: 0; white-space: nowrap; min-width: 180px;">{{ $sale->numero_control ?? '-' }}</td>
                    <td style="font-size: 8px; text-align: center; padding-top: 0; padding-bottom: 0; white-space: nowrap; min-width: 280px;">{{ $sale->codigo_generacion ?? '-' }}</td>
                    <td style="font-size: 10px; text-align: right; padding-top: 0; padding-bottom: 0;">{{ number_format($iva, 2) }}</td>
                    <td style="font-size: 8px; text-align: center; padding-top: 0; padding-bottom: 0; white-space: nowrap; min-width: 180px;">{{ $sale->clq_numero_control ?? '-' }}</td>
                    <td style="font-size: 8px; text-align: center; padding-top: 0; padding-bottom: 0; white-space: nowrap; min-width: 280px;">{{ $sale->clq_codigo_generacion ?? '-' }}</td>
                    <td style="font-size: 10px; text-align: center; padding-top: 0; padding-bottom: 0; white-space: nowrap;">{{ $sale->clq_fecha ?? '-' }}</td>
                    <td style="font-size: 10px; text-align: center; padding-top: 0; padding-bottom: 0;">
                        @if($sale->estado_liquidacion === 'Liquidado')
                            <span class="badge bg-success">Liquidado</span>
                        @else
                            <span class="badge bg-warning text-dark">Pendiente</span>
                        @endif
                    </td>
                </tr>
                <?php $i++; ?>
                @endforeach
                <tr style="text-align: right; font-weight: bold;">
                    <td colspan="9" style="text-align: right; font-size: 10px;"><b>TOTALES DEL MES</b></td>
                    <td style="font-size: 10px;">{{ number_format($tot_iva, 2) }}</td>
                    <td colspan="4" style="font-size: 10px;">-</td>
                </tr>
            </tbody>
        </table>
    </div>
    @endisset
</div>
@endsection
