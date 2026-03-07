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
<link rel="stylesheet"
    href="{{ asset('assets/vendor/libs/bootstrap-daterangepicker/bootstrap-daterangepicker.css') }}" />
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
    // Exportar a Excel
    $('#btn-export-excel').on('click', function() {
        var company = $('#company').val();
        var year = $('#year').val();
        var period = $('#period').val();

        if (!company) {
            Swal.fire({
                icon: 'warning',
                title: 'Atención',
                text: 'Por favor, primero realiza una búsqueda para generar el reporte.'
            });
            return;
        }

        // Crear formulario temporal para enviar datos por POST
        var form = $('<form>', {
            'method': 'POST',
            'action': '{{ route("report.liquidacion.excel") }}'
        });

        form.append($('<input>', {
            'type': 'hidden',
            'name': '_token',
            'value': '{{ csrf_token() }}'
        }));

        form.append($('<input>', {
            'type': 'hidden',
            'name': 'company',
            'value': company
        }));

        form.append($('<input>', {
            'type': 'hidden',
            'name': 'year',
            'value': year
        }));

        form.append($('<input>', {
            'type': 'hidden',
            'name': 'period',
            'value': period
        }));

        // Agregar al body y enviar
        $('body').append(form);
        form.submit();
        form.remove();
    });

    // Unir PDFs de comprobantes de liquidación
    $('#btn-merge-pdf').on('click', function() {
        var company = $('#company').val();
        var year = $('#year').val();
        var period = $('#period').val();

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
            'action': '{{ route("report.liquidacion.merge-pdf") }}'
        });

        form.append($('<input>', { 'type': 'hidden', 'name': '_token', 'value': '{{ csrf_token() }}' }));
        form.append($('<input>', { 'type': 'hidden', 'name': 'company', 'value': company }));
        form.append($('<input>', { 'type': 'hidden', 'name': 'year', 'value': year }));
        form.append($('<input>', { 'type': 'hidden', 'name': 'period', 'value': period }));

        $('body').append(form);
        form.submit();
        form.remove();
    });

    $('.select2').select2();
});
</script>
@endsection

@section('content')
<h4 class="py-3 mb-4"><span class="text-muted fw-light">Reportes / </span>Libro de Comprobantes de Liquidación</h4>

<!-- User List Table -->
<div class="card">
    <div class="card-datatable table-responsive">
        <form method="POST" id="buscar" action="{{ route('report.liquidacionsearch') }}">
            @csrf
            <div class="container">
                <br>
                <div class="row">
                    <div class="mb-3 col-md-3">
                        <label for="company" class="form-label">Empresa</label>
                        <select class="form-control select2" data-allow-clear="true" id="company" name="company" required>
                            <option value="" disabled>Seleccionar...</option>
                            @php $companiesList = DB::table('companies')->get(); @endphp
                            @foreach ($companiesList as $company)
                                <option value="{{ $company->id }}" {{ (isset($heading) && $heading->id == $company->id) || (!isset($heading) && $loop->first) ? 'selected' : '' }}>
                                    {{ $company->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3 col-md-3">
                        <label for="year" class="form-label">Año</label>
                        <input type="number" id="year" name="year" class="form-control" placeholder="Ej: 2025" 
                               value="{{ isset($yearB) ? $yearB : date('Y') }}" required min="2020" max="2099">
                    </div>
                    <div class="mb-3 col-md-3">
                        <label for="period" class="form-label">Período (Mes)</label>
                        <select class="form-control" id="period" name="period" required>
                            @php
                                $meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
                                $mesActual = isset($period) ? (int)$period : (date('n') == 1 ? 12 : date('n') - 1);
                            @endphp
                            @foreach ($meses as $index => $mes)
                                <option value="{{ $index + 1 }}" {{ $mesActual == ($index + 1) ? 'selected' : '' }}>
                                    {{ $mes }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3 col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="ti ti-search me-1"></i>Buscar
                        </button>
                    </div>
                </div>
            </div>
        </form>

        @if (isset($sales))
        <div class="container mb-3">
            <div class="row">
                <div class="col-md-12">
                    <div class="btn-group" role="group">
                        <button type="button" id="btn-export-excel" class="btn btn-success">
                            <i class="ti ti-file-spreadsheet me-1"></i>Exportar a Excel
                        </button>
                        <button type="button" id="btn-merge-pdf" class="btn btn-warning">
                            <i class="ti ti-file-plus me-1"></i>Unir PDFs de documentos
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="container">
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
                <table class="table table-sm table-bordered" style="min-width: 1700px;">
                    <thead style="font-size: 13px;">
                        <tr>
                            <th class="text-center" colspan="20">
                                <b>LIBRO DE COMPROBANTES DE LIQUIDACIÓN</b>
                            </th>
                        </tr>
                        <tr>
                            <td class="text-center" colspan="20" style="font-size: 13px;">
                                <b>Nombre del Contribuyente:</b> {{ $heading->name }} &nbsp;&nbsp;
                                <b>N.R.C.:</b> {{ $heading->nrc }} &nbsp;&nbsp; <b>NIT:</b> {{ $heading->nit }} &nbsp;&nbsp;
                                <b>MES:</b> {{ strtoupper($meses[(int)$period-1]) }} &nbsp;&nbsp; <b>AÑO:</b> {{ $yearB }}
                                <p>(Valores expresados en Dólares Estadounidenses)</p>
                            </td>
                        </tr>
                        <tr style="text-transform: uppercase;">
                            <td style="font-size: 10px; text-align: center; width: 40px;"><b>Corr.</b></td>
                            <td style="font-size: 10px; text-align: left; width: 80px;"><b>FECHA</b></td>
                            <td style="font-size: 10px; text-align: left; width: 60px;"><b>No. Doc.</b></td>
                            <td style="font-size: 10px; width: 150px;"><b>CLIENTE</b></td>
                            <td style="font-size: 10px; text-align: right; width: 80px;"><b>NRC</b></td>
                            <td style="font-size: 10px; text-align: center; width: 80px;"><b>TIPO VENTA</b></td>
                            <td style="font-size: 10px; text-align: right; width: 80px;"><b>EXENTAS</b></td>
                            <td style="font-size: 10px; text-align: right; width: 80px;"><b>NO SUJETAS</b></td>
                            <td style="font-size: 10px; text-align: right; width: 80px;"><b>GRAVADAS</b></td>
                            <td style="font-size: 10px; text-align: right; width: 80px;"><b>EXPORTACIÓN</b></td>
                            <td style="font-size: 10px; text-align: right; width: 80px;"><b>IVA</b></td>
                            <td style="font-size: 10px; text-align: right; width: 80px;"><b>IVA RET.</b></td>
                            <td style="font-size: 10px; text-align: right; width: 80px;"><b>IVA PERC.</b></td>
                            <td style="font-size: 10px; text-align: right; width: 80px;"><b>TOTAL</b></td>
                            <td style="font-size: 10px; text-align: center; min-width: 200px;"><b>NÚMERO CONTROL DTE</b></td>
                            <td style="font-size: 10px; text-align: center; min-width: 200px;"><b>CÓDIGO GENERACIÓN</b></td>
                            <td style="font-size: 10px; text-align: center; min-width: 200px;"><b>SELLO RECEPCIÓN</b></td>
                            <td style="font-size: 10px; text-align: center; min-width: 200px;"><b>Nº CONTROL ANULACIÓN</b></td>
                            <td style="font-size: 10px; text-align: center; min-width: 200px;"><b>CÓD. GEN. ANULACIÓN</b></td>
                            <td style="font-size: 10px; text-align: center; min-width: 200px;"><b>SELLO ANULACIÓN</b></td>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $tot_exentas = 0;
                            $tot_int_grav = 0;
                            $tot_debfiscal = 0;
                            $tot_nosujetas = 0;
                            $tot_exportacion = 0;
                            $tot_final = 0;
                            $tot_iva_retenido = 0;
                            $tot_iva_percibido = 0;
                            $i = 1;
                        @endphp
                        @foreach ($sales as $sale)
                        <tr>
                            <td style="font-size: 10px; text-align: center; padding-top: 0px; margin-top: 0px; padding-bottom: 0px; margin-bottom: 0px;">{{ $i }}</td>
                            <td style="font-size: 10px; text-align: left; padding-top: 0px; margin-top: 0px; padding-bottom: 0px; margin-bottom: 0px;">{{ $sale->dateF }}</td>
                            <td style="font-size: 9px; text-align: left; padding-top: 0px; margin-top: 0px; padding-bottom: 0px; margin-bottom: 0px;">{{ $sale->correlativo }}</td>
                            <td class="text-uppercase" style="font-size: 10px; padding-top: 0px; margin-top: 0px; padding-bottom: 0px; margin-bottom: 0px;">
                                @if($sale->typesale=='0')
                                    <span style="color: #c00; font-weight: bold;">ANULADO</span>
                                @else
                                    {{ $sale->nombre_completo ?? '' }}
                                @endif
                            </td>
                            <td style="font-size: 10px; text-align: right; padding-top: 0px; margin-top: 0px; padding-bottom: 0px; margin-bottom: 0px;">{{ preg_replace('/[^0-9]/', '', $sale->ncrC ?? '') }}</td>
                            <td style="font-size: 10px; text-align: center; padding-top: 0px; margin-top: 0px; padding-bottom: 0px; margin-bottom: 0px;">{{ $sale->typesale=='0' ? '-' : ($sale->tipo_venta ?? 'PROPIA') }}</td>
                            @if($sale->typesale=='0')
                                @php
                                    $iva_ret_anul = $sale->iva_retenido ?? 0;
                                    $iva_perc_anul = $sale->iva_percibido ?? 0;
                                @endphp
                                <td style="font-size: 10px; text-align: right; padding-top: 0px; margin-top: 0px; padding-bottom: 0px; margin-bottom: 0px;">{{ number_format($sale->exenta ?? 0, 2) }}</td>
                                <td style="font-size: 10px; text-align: right; padding-top: 0px; margin-top: 0px; padding-bottom: 0px; margin-bottom: 0px;">{{ number_format($sale->nosujeta ?? 0, 2) }}</td>
                                <td style="font-size: 10px; text-align: right; padding-top: 0px; margin-top: 0px; padding-bottom: 0px; margin-bottom: 0px;">{{ number_format($sale->gravada ?? 0, 2) }}</td>
                                <td style="font-size: 10px; text-align: right; padding-top: 0px; margin-top: 0px; padding-bottom: 0px; margin-bottom: 0px;">{{ number_format($sale->exportacion ?? 0, 2) }}</td>
                                <td style="font-size: 10px; text-align: right; padding-top: 0px; margin-top: 0px; padding-bottom: 0px; margin-bottom: 0px;">{{ number_format($sale->iva ?? 0, 2) }}</td>
                                <td style="font-size: 10px; text-align: right; padding-top: 0px; margin-top: 0px; padding-bottom: 0px; margin-bottom: 0px;">{{ number_format($iva_ret_anul, 2) }}</td>
                                <td style="font-size: 10px; text-align: right; padding-top: 0px; margin-top: 0px; padding-bottom: 0px; margin-bottom: 0px;">{{ number_format($iva_perc_anul, 2) }}</td>
                                <td style="font-size: 10px; text-align: right; padding-top: 0px; margin-top: 0px; padding-bottom: 0px; margin-bottom: 0px;">{{ number_format($sale->totalamount ?? 0, 2) }}</td>
                            @else
                                @php
                                    $iva_retenido = $sale->iva_retenido ?? 0;
                                    $iva_percibido = $sale->iva_percibido ?? 0;
                                @endphp
                                <td style="font-size: 10px; text-align: right; padding-top: 0px; margin-top: 0px; padding-bottom: 0px; margin-bottom: 0px;">{{ number_format($sale->exenta, 2) }}</td>
                                <td style="font-size: 10px; text-align: right; padding-top: 0px; margin-top: 0px; padding-bottom: 0px; margin-bottom: 0px;">{{ number_format($sale->nosujeta, 2) }}</td>
                                <td style="font-size: 10px; text-align: right; padding-top: 0px; margin-top: 0px; padding-bottom: 0px; margin-bottom: 0px;">{{ number_format($sale->gravada, 2) }}</td>
                                <td style="font-size: 10px; text-align: right; padding-top: 0px; margin-top: 0px; padding-bottom: 0px; margin-bottom: 0px;">{{ number_format($sale->exportacion ?? 0, 2) }}</td>
                                <td style="font-size: 10px; text-align: right; padding-top: 0px; margin-top: 0px; padding-bottom: 0px; margin-bottom: 0px;">{{ number_format($sale->iva, 2) }}</td>
                                <td style="font-size: 10px; text-align: right; padding-top: 0px; margin-top: 0px; padding-bottom: 0px; margin-bottom: 0px;">{{ number_format($iva_retenido, 2) }}</td>
                                <td style="font-size: 10px; text-align: right; padding-top: 0px; margin-top: 0px; padding-bottom: 0px; margin-bottom: 0px;">{{ number_format($iva_percibido, 2) }}</td>
                                <td style="font-size: 10px; text-align: right; padding-top: 0px; margin-top: 0px; padding-bottom: 0px; margin-bottom: 0px;">{{ number_format($sale->totalamount, 2) }}</td>
                                @php
                                    $tot_exentas += $sale->exenta;
                                    $tot_nosujetas += $sale->nosujeta;
                                    $tot_int_grav += $sale->gravada;
                                    $tot_exportacion += $sale->exportacion ?? 0;
                                    $tot_debfiscal += $sale->iva;
                                    $tot_iva_retenido += $iva_retenido;
                                    $tot_iva_percibido += $iva_percibido;
                                    $tot_final += $sale->totalamount;
                                @endphp
                            @endif
                            <td style="font-size: 8px; text-align: center; padding-top: 0px; margin-top: 0px; padding-bottom: 0px; margin-bottom: 0px; white-space: nowrap; min-width: 200px;">{{ $sale->numeroControl ?? '-' }}</td>
                            <td style="font-size: 8px; text-align: center; padding-top: 0px; margin-top: 0px; padding-bottom: 0px; margin-bottom: 0px; white-space: nowrap; min-width: 200px;">{{ $sale->codigoGeneracion ?? '-' }}</td>
                            <td style="font-size: 8px; text-align: center; padding-top: 0px; margin-top: 0px; padding-bottom: 0px; margin-bottom: 0px; white-space: nowrap; min-width: 200px;">{{ $sale->selloRecibido ?? '-' }}</td>
                            <td style="font-size: 8px; text-align: center; padding-top: 0px; margin-top: 0px; padding-bottom: 0px; margin-bottom: 0px; white-space: nowrap; min-width: 200px;">@if($sale->typesale=='0'){{ $sale->numeroControl_anulacion ?? '-' }}@else - @endif</td>
                            <td style="font-size: 8px; text-align: center; padding-top: 0px; margin-top: 0px; padding-bottom: 0px; margin-bottom: 0px; white-space: nowrap; min-width: 200px;">@if($sale->typesale=='0'){{ $sale->codigoGeneracion_anulacion ?? '-' }}@else - @endif</td>
                            <td style="font-size: 8px; text-align: center; padding-top: 0px; margin-top: 0px; padding-bottom: 0px; margin-bottom: 0px; white-space: nowrap; min-width: 200px;">@if($sale->typesale=='0'){{ $sale->selloRecibido_anulacion ?? '-' }}@else - @endif</td>
                        </tr>
                        @php $i++; @endphp
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="text-right" style="font-size: 10px; font-weight: bold;">
                            <td colspan="6" style="text-align: right; padding-top: 0px; margin-top: 0px; padding-bottom: 0px; margin-bottom: 0px;">
                                TOTALES DEL MES
                            </td>
                            <td style="text-align: right; padding-top: 0px; margin-top: 0px; padding-bottom: 0px; margin-bottom: 0px;">{{ number_format($tot_exentas, 2) }}</td>
                            <td style="text-align: right; padding-top: 0px; margin-top: 0px; padding-bottom: 0px; margin-bottom: 0px;">{{ number_format($tot_nosujetas, 2) }}</td>
                            <td style="text-align: right; padding-top: 0px; margin-top: 0px; padding-bottom: 0px; margin-bottom: 0px;">{{ number_format($tot_int_grav, 2) }}</td>
                            <td style="text-align: right; padding-top: 0px; margin-top: 0px; padding-bottom: 0px; margin-bottom: 0px;">{{ number_format($tot_exportacion, 2) }}</td>
                            <td style="text-align: right; padding-top: 0px; margin-top: 0px; padding-bottom: 0px; margin-bottom: 0px;">{{ number_format($tot_debfiscal, 2) }}</td>
                            <td style="text-align: right; padding-top: 0px; margin-top: 0px; padding-bottom: 0px; margin-bottom: 0px;">{{ number_format($tot_iva_retenido, 2) }}</td>
                            <td style="text-align: right; padding-top: 0px; margin-top: 0px; padding-bottom: 0px; margin-bottom: 0px;">{{ number_format($tot_iva_percibido, 2) }}</td>
                            <td style="text-align: right; padding-top: 0px; margin-top: 0px; padding-bottom: 0px; margin-bottom: 0px;">{{ number_format($tot_final, 2) }}</td>
                            <td style="text-align: center;">-</td>
                            <td style="text-align: center;">-</td>
                            <td style="text-align: center;">-</td>
                            <td style="text-align: center;">-</td>
                            <td style="text-align: center;">-</td>
                            <td style="text-align: center;">-</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
