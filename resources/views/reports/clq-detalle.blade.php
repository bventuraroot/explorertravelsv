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
            'action': '{{ route("report.clqDetalle.excel") }}'
        });
        form.append($('<input>', { 'type': 'hidden', 'name': '_token',  'value': '{{ csrf_token() }}' }));
        form.append($('<input>', { 'type': 'hidden', 'name': 'company', 'value': company }));
        form.append($('<input>', { 'type': 'hidden', 'name': 'year',    'value': year }));
        form.append($('<input>', { 'type': 'hidden', 'name': 'period',  'value': period }));
        $('body').append(form);
        form.submit();
        form.remove();
    });
});
</script>
@endsection

@section('title', 'Detalle Facturas por CLQ')

@section('content')
<h4 class="py-3 mb-4 fw-bold">
    <span class="text-muted fw-light">Reportes /</span> Detalle de Facturas por Comprobante de Liquidación
</h4>

<div class="card">
    <form id="buscar" action="{{ route('report.clqDetalle.search') }}" method="POST">
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
    $tiposDoc = [
        '01' => 'FCF', '03' => 'CCF', '04' => 'Nota de Remisión',
        '05' => 'Nota de Crédito', '06' => 'Nota de Débito',
        '11' => 'FEX', '14' => 'FSE',
    ];
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
        <table class="table table-sm table-bordered" style="min-width: 2000px;">
            <thead style="font-size: 11px;">
                <tr>
                    <th class="text-center" colspan="19">
                        DETALLE DE FACTURAS POR COMPROBANTE DE LIQUIDACIÓN (Valores expresados en USD)
                    </th>
                </tr>
                <tr>
                    <td class="text-center" colspan="19" style="font-size: 12px;">
                        <b>Nombre del Contribuyente:</b> {{ $heading->name }} &nbsp;&nbsp;
                        <b>N.R.C.:</b> {{ $heading->nrc ?? '-' }} &nbsp;&nbsp;
                        <b>NIT:</b> {{ $heading->nit ?? '-' }} &nbsp;&nbsp;
                        <b>MES:</b> {{ $mesesDelAnoMayuscula[(int)$period - 1] }} &nbsp;&nbsp;
                        <b>AÑO:</b> {{ $yearB }}
                    </td>
                </tr>
                <tr style="text-transform: uppercase;">
                    <td style="font-size: 10px; text-align: center; width: 35px;"><b>N°</b></td>
                    <td style="font-size: 10px; text-align: center; width: 75px;"><b>FECHA CLQ</b></td>
                    <td style="font-size: 10px; text-align: left; width: 60px;"><b>No. DOC.</b></td>
                    <td style="font-size: 10px; width: 160px;"><b>CLIENTE</b></td>
                    <td style="font-size: 10px; text-align: right; width: 80px;"><b>NRC</b></td>
                    <td style="font-size: 10px; text-align: center; width: 70px;"><b>ESTADO</b></td>
                    <td style="font-size: 10px; text-align: center; min-width: 200px;"><b>Nº CONTROL DTE</b></td>
                    <td style="font-size: 10px; text-align: center; min-width: 280px;"><b>CÓD. GENERACIÓN</b></td>
                    <td style="font-size: 10px; text-align: center; min-width: 280px;"><b>SELLO RECEPCIÓN</b></td>
                    <td style="font-size: 10px; text-align: right; width: 80px;"><b>TOTAL CLQ</b></td>
                    <td style="font-size: 10px; text-align: center; width: 70px;"><b>TIPO DOC</b></td>
                    <td style="font-size: 10px; text-align: center; width: 80px;"><b>TIPO GEN.</b></td>
                    <td style="font-size: 10px; text-align: center; min-width: 280px;"><b>Nº DOC. RELACIONADO</b></td>
                    <td style="font-size: 10px; text-align: center; width: 75px;"><b>FECHA FACTURA</b></td>
                    <td style="font-size: 10px; width: 140px;"><b>OBSERVACIONES</b></td>
                    <td style="font-size: 10px; text-align: right; width: 80px;"><b>GRAVADAS</b></td>
                    <td style="font-size: 10px; text-align: right; width: 80px;"><b>EXENTAS</b></td>
                    <td style="font-size: 10px; text-align: right; width: 80px;"><b>NO SUJETAS</b></td>
                    <td style="font-size: 10px; text-align: right; width: 80px;"><b>IVA</b></td>
                </tr>
            </thead>
            <tbody>
                @php
                    $i = 1;
                    $tot_clq      = 0;
                    $tot_gravadas = 0;
                    $tot_exentas  = 0;
                    $tot_nosujetas = 0;
                    $tot_iva      = 0;
                @endphp
                @forelse($sales as $sale)
                @php
                    $esAnulado = $sale->typesale == '0';
                    $nrcLimpio = preg_replace('/[^0-9]/', '', $sale->ncrC ?? '');
                    $facturas  = $sale->facturas ?? collect();
                @endphp

                @if($facturas->isEmpty())
                    <tr>
                        <td style="text-align:center; padding-top:0; padding-bottom:0;">{{ $i }}</td>
                        <td style="padding-top:0; padding-bottom:0; white-space:nowrap;">{{ $sale->dateF ?? '-' }}</td>
                        <td style="font-size:9px; padding-top:0; padding-bottom:0;">{{ $sale->correlativo ?? '-' }}</td>
                        <td class="text-uppercase" style="padding-top:0; padding-bottom:0;">
                            @if($esAnulado)<span style="color:#c00; font-weight:bold;">ANULADO</span>@else{{ $sale->nombre_completo ?? '' }}@endif
                        </td>
                        <td style="text-align:right; padding-top:0; padding-bottom:0;">{{ $nrcLimpio }}</td>
                        <td style="text-align:center; padding-top:0; padding-bottom:0;">
                            @if($esAnulado)<span class="badge bg-danger">ANULADO</span>@else<span class="badge bg-success">ACTIVO</span>@endif
                        </td>
                        <td style="font-size:8px; white-space:nowrap; padding-top:0; padding-bottom:0; min-width:200px;">{{ $sale->numeroControl ?? '-' }}</td>
                        <td style="font-size:8px; white-space:nowrap; padding-top:0; padding-bottom:0; min-width:280px;">{{ $sale->codigoGeneracion ?? '-' }}</td>
                        <td style="font-size:8px; white-space:nowrap; padding-top:0; padding-bottom:0; min-width:280px;">{{ $sale->selloRecibido ?? '-' }}</td>
                        <td style="text-align:right; font-weight:bold; padding-top:0; padding-bottom:0;">{{ number_format($sale->totalamount ?? 0, 2) }}</td>
                        @if(!$esAnulado)@php $tot_clq += $sale->totalamount ?? 0; @endphp@endif
                        <td colspan="9" style="text-align:center; color:#888; font-style:italic; padding-top:0; padding-bottom:0;">Sin facturas relacionadas</td>
                    </tr>
                @else
                    @foreach($facturas as $idx => $fac)
                    @php
                        $tipoCod  = $fac->clq_tipo_documento ?? '';
                        $tipoDesc = $tiposDoc[$tipoCod] ?? $tipoCod;
                        $tipoGen  = $fac->clq_tipo_generacion == '1' ? 'Físico'
                                  : ($fac->clq_tipo_generacion == '2' ? 'Electrónico' : ($fac->clq_tipo_generacion ?? '-'));
                        $gravada  = floatval($fac->pricesale ?? 0);
                        $exenta   = floatval($fac->exempt ?? 0);
                        $nosujeta = floatval($fac->nosujeta ?? 0);
                        $iva      = floatval($fac->detained13 ?? 0);
                        if (!$esAnulado) {
                            $tot_gravadas  += $gravada;
                            $tot_exentas   += $exenta;
                            $tot_nosujetas += $nosujeta;
                            $tot_iva       += $iva;
                        }
                    @endphp
                    <tr>
                        @if($idx === 0)
                            <td style="text-align:center; padding-top:0; padding-bottom:0; vertical-align:top;">{{ $i }}</td>
                            <td style="padding-top:0; padding-bottom:0; white-space:nowrap; vertical-align:top;">{{ $sale->dateF ?? '-' }}</td>
                            <td style="font-size:9px; padding-top:0; padding-bottom:0; vertical-align:top;">{{ $sale->correlativo ?? '-' }}</td>
                            <td class="text-uppercase" style="padding-top:0; padding-bottom:0; vertical-align:top;">
                                @if($esAnulado)<span style="color:#c00; font-weight:bold;">ANULADO</span>@else{{ $sale->nombre_completo ?? '' }}@endif
                            </td>
                            <td style="text-align:right; padding-top:0; padding-bottom:0; vertical-align:top;">{{ $nrcLimpio }}</td>
                            <td style="text-align:center; padding-top:0; padding-bottom:0; vertical-align:top;">
                                @if($esAnulado)<span class="badge bg-danger">ANULADO</span>@else<span class="badge bg-success">ACTIVO</span>@endif
                            </td>
                            <td style="font-size:8px; white-space:nowrap; padding-top:0; padding-bottom:0; min-width:200px; vertical-align:top;">{{ $sale->numeroControl ?? '-' }}</td>
                            <td style="font-size:8px; white-space:nowrap; padding-top:0; padding-bottom:0; min-width:280px; vertical-align:top;">{{ $sale->codigoGeneracion ?? '-' }}</td>
                            <td style="font-size:8px; white-space:nowrap; padding-top:0; padding-bottom:0; min-width:280px; vertical-align:top;">{{ $sale->selloRecibido ?? '-' }}</td>
                            <td style="text-align:right; font-weight:bold; padding-top:0; padding-bottom:0; vertical-align:top;">{{ number_format($sale->totalamount ?? 0, 2) }}</td>
                            @if(!$esAnulado)@php $tot_clq += $sale->totalamount ?? 0; @endphp@endif
                        @else
                            <td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>
                        @endif
                        <td style="text-align:center; white-space:nowrap; padding-top:0; padding-bottom:0;">{{ $tipoDesc }}</td>
                        <td style="text-align:center; white-space:nowrap; padding-top:0; padding-bottom:0;">{{ $tipoGen }}</td>
                        <td style="font-size:8px; white-space:nowrap; padding-top:0; padding-bottom:0; min-width:280px;">{{ $fac->clq_numero_documento ?? '-' }}</td>
                        <td style="text-align:center; white-space:nowrap; padding-top:0; padding-bottom:0;">
                            {{ $fac->clq_fecha_generacion ? \Carbon\Carbon::parse($fac->clq_fecha_generacion)->format('d/m/Y') : '-' }}
                        </td>
                        <td style="padding-top:0; padding-bottom:0;">{{ $fac->clq_observaciones ?? '' }}</td>
                        <td style="text-align:right; padding-top:0; padding-bottom:0;">{{ number_format($gravada, 2) }}</td>
                        <td style="text-align:right; padding-top:0; padding-bottom:0;">{{ number_format($exenta, 2) }}</td>
                        <td style="text-align:right; padding-top:0; padding-bottom:0;">{{ number_format($nosujeta, 2) }}</td>
                        <td style="text-align:right; padding-top:0; padding-bottom:0;">{{ number_format($iva, 2) }}</td>
                    </tr>
                    @endforeach
                @endif
                <?php $i++; ?>
                @empty
                <tr>
                    <td colspan="19" class="text-center text-muted py-3">No se encontraron comprobantes para el período seleccionado.</td>
                </tr>
                @endforelse
                <tr style="text-align: right; font-weight: bold;">
                    <td colspan="9" style="text-align: right; font-size: 9px;"><b>TOTALES DEL MES</b></td>
                    <td style="font-size: 10px;">{{ number_format($tot_clq, 2) }}</td>
                    <td colspan="5" style="font-size: 10px;"></td>
                    <td style="font-size: 10px;">{{ number_format($tot_gravadas, 2) }}</td>
                    <td style="font-size: 10px;">{{ number_format($tot_exentas, 2) }}</td>
                    <td style="font-size: 10px;">{{ number_format($tot_nosujetas, 2) }}</td>
                    <td style="font-size: 10px;">{{ number_format($tot_iva, 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
    @endisset
</div>
@endsection
