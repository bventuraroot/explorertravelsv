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
                            <option value="" disabled selected>Seleccionar...</option>
                            @foreach (DB::table('companies')->get() as $company)
                                <option value="{{ $company->id }}" {{ isset($heading) && $heading->id == $company->id ? 'selected' : '' }}>
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
                                $mesActual = isset($period) ? $period : date('n');
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
                    <div class="btn-group w-100" role="group">
                        <button type="button" id="btn-export-excel" class="btn btn-success">
                            <i class="ti ti-file-spreadsheet me-1"></i>Exportar a Excel
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <h5 class="text-center mb-3">
                        <b>LIBRO DE COMPROBANTES DE LIQUIDACIÓN</b>
                    </h5>
                    <p class="text-center">
                        <b>Nombre del Contribuyente:</b> {{ $heading->name }}<br>
                        <b>N.R.C.:</b> {{ $heading->nrc }} &nbsp;&nbsp; <b>NIT:</b> {{ $heading->nit }}<br>
                        <b>MES:</b> {{ strtoupper($meses[(int)$period-1]) }} &nbsp;&nbsp; <b>AÑO:</b> {{ $yearB }}
                    </p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>Corr.</th>
                            <th>FECHA</th>
                            <th>No. Doc.</th>
                            <th>CLIENTE</th>
                            <th>NRC</th>
                            <th>EXENTAS</th>
                            <th>NO SUJETAS</th>
                            <th>GRAVADAS</th>
                            <th>IVA</th>
                            <th>IVA RET.</th>
                            <th>IVA PERC.</th>
                            <th>TOTAL</th>
                            <th>NÚME RO CONTROL</th>
                            <th>CÓDIGO GEN.</th>
                            <th>SELLO</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $tot_exentas = 0;
                            $tot_int_grav = 0;
                            $tot_debfiscal = 0;
                            $tot_nosujetas = 0;
                            $tot_final = 0;
                            $tot_iva_retenido = 0;
                            $tot_iva_percibido = 0;
                            $i = 1;
                        @endphp
                        @foreach ($sales as $sale)
                        <tr>
                            <td>{{ $i }}</td>
                            <td>{{ $sale->dateF }}</td>
                            <td>{{ $sale->correlativo }}</td>
                            <td>
                                @if($sale->typesale=='0')
                                    ANULADO
                                @else
                                    {{ strtoupper($sale->nombre_completo ?? '') }}
                                @endif
                            </td>
                            <td>{{ $sale->ncrC }}</td>
                            @if($sale->typesale=='0')
                                <td colspan="7" class="text-center">ANULADO</td>
                            @else
                                @php
                                    $iva_retenido = $sale->iva_retenido ?? 0;
                                    $iva_percibido = $sale->iva_percibido ?? 0;
                                @endphp
                                <td class="text-end">{{ number_format($sale->exenta, 2) }}</td>
                                <td class="text-end">{{ number_format($sale->nosujeta, 2) }}</td>
                                <td class="text-end">{{ number_format($sale->gravada, 2) }}</td>
                                <td class="text-end">{{ number_format($sale->iva, 2) }}</td>
                                <td class="text-end">{{ number_format($iva_retenido, 2) }}</td>
                                <td class="text-end">{{ number_format($iva_percibido, 2) }}</td>
                                <td class="text-end">{{ number_format($sale->totalamount, 2) }}</td>
                                @php
                                    $tot_exentas += $sale->exenta;
                                    $tot_nosujetas += $sale->nosujeta;
                                    $tot_int_grav += $sale->gravada;
                                    $tot_debfiscal += $sale->iva;
                                    $tot_iva_retenido += $iva_retenido;
                                    $tot_iva_percibido += $iva_percibido;
                                    $tot_final += $sale->totalamount;
                                @endphp
                            @endif
                            <td>{{ $sale->numeroControl ?? '-' }}</td>
                            <td style="font-size: 0.75rem;">{{ $sale->codigoGeneracion ?? '-' }}</td>
                            <td style="font-size: 0.75rem;">{{ $sale->selloRecibido ?? '-' }}</td>
                        </tr>
                        @php $i++; @endphp
                        @endforeach
                    </tbody>
                    <tfoot class="table-dark">
                        <tr>
                            <th colspan="5" class="text-end">TOTALES DEL MES</th>
                            <th class="text-end">{{ number_format($tot_exentas, 2) }}</th>
                            <th class="text-end">{{ number_format($tot_nosujetas, 2) }}</th>
                            <th class="text-end">{{ number_format($tot_int_grav, 2) }}</th>
                            <th class="text-end">{{ number_format($tot_debfiscal, 2) }}</th>
                            <th class="text-end">{{ number_format($tot_iva_retenido, 2) }}</th>
                            <th class="text-end">{{ number_format($tot_iva_percibido, 2) }}</th>
                            <th class="text-end">{{ number_format($tot_final, 2) }}</th>
                            <th colspan="3">-</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
