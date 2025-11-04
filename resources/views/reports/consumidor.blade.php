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
<script src="{{ asset('assets/js/tables-consumidor.js') }}"></script>
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
            'action': '{{ route("report.consumidor.excel") }}'
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

    // Exportar a PDF (Libro Consumidor)
    $('#btn-export-pdf').on('click', function() {
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
            'action': '{{ route("report.consumidor.pdf") }}'
        });

        form.append($('<input>', { 'type': 'hidden', 'name': '_token', 'value': '{{ csrf_token() }}' }));
        form.append($('<input>', { 'type': 'hidden', 'name': 'company', 'value': company }));
        form.append($('<input>', { 'type': 'hidden', 'name': 'year', 'value': year }));
        form.append($('<input>', { 'type': 'hidden', 'name': 'period', 'value': period }));

        $('body').append(form);
        form.submit();
        form.remove();
    });

    // Concatenar PDFs individuales de cada documento
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
            'action': '{{ route("report.consumidor.merge-pdf") }}'
        });

        form.append($('<input>', { 'type': 'hidden', 'name': '_token', 'value': '{{ csrf_token() }}' }));
        form.append($('<input>', { 'type': 'hidden', 'name': 'company', 'value': company }));
        form.append($('<input>', { 'type': 'hidden', 'name': 'year', 'value': year }));
        form.append($('<input>', { 'type': 'hidden', 'name': 'period', 'value': period }));

        $('body').append(form);
        form.submit();
        form.remove();
    });
});
</script>
@endsection

@section('title', 'Reporte de Ventas')

@section('content')
<h4 class="py-3 mb-4 fw-bold">
    <span class="text-muted fw-light">Reportes /</span> Ventas a Consumidor
</h4>

<!-- Advanced Search -->
<div class="card">
    <form id="sendfilters" action="{{Route('report.consumidorsearch')}}" method="post">
        @csrf @method('POST')
        <input type="hidden" name="iduser" id="iduser" value="{{ Auth::user()->id }}">
        <div class="card-header">
            <div class="row">
                <div class="col-4">
                    <div class="row g-3">
                        <select class="form-control" name="company" id="company">

                        </select>
                    </div>
                </div>
                <div class="col-1">
                    <div class="row g-3">
                        <select class="form-control" name="year" id="year">
                            <?php
                        $year = date("Y");
                        //echo "<option value ='".$year."'>".$year."</option>";
                        for ($i=0; $i < 5 ; $i++) {
                            $yearnew = $year-$i;
                            if(isset($year)){
                                if($yearnew==@$yearB){
                                    $selected="selected";
                                }else {
                                    $selected="";
                                }

                            }
                            echo "<option value ='".$yearnew."' ".$selected.">".$yearnew."</option>";
                        }
                        ?>
                        </select>
                    </div>
                </div>
                <div class="col-4">
                    <div class="row g-3">
                        <select class="form-control" name="period" id="period">
                            <option value="01" <?php echo (@$period == '01') ? "selected" : "" ?>>Enero</option>
                            <option value="02" <?php echo (@$period == '02') ? "selected" : "" ?>>Febrero</option>
                            <option value="03" <?php echo (@$period == '03') ? "selected" : "" ?>>Marzo</option>
                            <option value="04" <?php echo (@$period == '04') ? "selected" : "" ?>>Abril</option>
                            <option value="05" <?php echo (@$period == '05') ? "selected" : "" ?>>Mayo</option>
                            <option value="06" <?php echo (@$period == '06') ? "selected" : "" ?>>Junio</option>
                            <option value="07" <?php echo (@$period == '07') ? "selected" : "" ?>>Julio</option>
                            <option value="08" <?php echo (@$period == '08') ? "selected" : "" ?>>Agosto</option>
                            <option value="09" <?php echo (@$period == '09') ? "selected" : "" ?>>Septiembre</option>
                            <option value="10" <?php echo (@$period == '10') ? "selected" : "" ?>>Octubre</option>
                            <option value="11" <?php echo (@$period == '11') ? "selected" : "" ?>>Noviembre</option>
                            <option value="12" <?php echo (@$period == '12') ? "selected" : "" ?>>Diciembre</option>
                        </select>
                    </div>
                </div>
                <div class="col-3">
                    <button type="button" id="first-filter"
                        class="btn rounded-pill btn-primary waves-effect waves-light">Buscar</button>
                </div>
            </div>
        </div>
    </form>
    @isset($heading)
    <?php
    $mesesDelAno = array(
  "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio",
  "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"
);

$mesesDelAnoMayuscula = array_map('strtoupper', $mesesDelAno);
    ?>
    <div class="row">
        <div class="col-12">
            <div class="box-header" style="text-align: right; margin-right: 6%;">
                <button type="button" class='btn btn-primary' title='Exportar a Excel' id="btn-export-excel">
                    <i class="fa-solid fa-file-excel"></i> &nbsp;&nbsp;Exportar a Excel
                </button>
                &nbsp;
                <button type="button" class='btn btn-danger' title='Exportar a PDF' id="btn-export-pdf">
                    <i class="fa-solid fa-file-pdf"></i> &nbsp;&nbsp;Exportar a PDF
                </button>
                &nbsp;
                <button type="button" class='btn btn-warning' title='Concatenar PDFs de documentos' id="btn-merge-pdf">
                    <i class="fa-solid fa-file-pdf"></i> &nbsp;&nbsp;Unir PDFs de documentos
                </button>
                &nbsp;
                <a href="#!" class='btn btn-success' title='Imprimir credito' onclick="impFAC('areaImprimir');">
                    <i class="fa-solid fa-print"> </i> &nbsp;&nbsp;Imprimir
                </a>
            </div>
        </div>
    </div>
    <div id="areaImprimir">
        <div style="overflow-x: auto; max-width: 100%;">
        <table class="table" style="min-width: 1600px;">
            <thead>
                <tr>
                    <th class="text-center" colspan="13">
                        <b>LIBRO DE VENTAS CONSUMIDOR</b>
                    </th>
                </tr>
                <tr>
                    <td class="text-center" colspan="13">
                        <b>Nombre del Contribuyente: </b> <?php echo $heading['name']; ?>
                        <b>N.R.C.: </b> <?php echo $heading['ncr']; ?> <b>MES: </b><?php echo $mesesDelAnoMayuscula[(int)$period-1] ?>
                        <b>Año: </b> <?php echo $yearB; ?><p>(Valores expresados en Dolares Estadounidenses)</p>
                    </td>
                </tr>
            </thead>

            <tbody>
                <tr class="text-center">
                    <td style="width: 40px;">Corr.</td>
                    <td style="width: 80px;">FECHA</td>
                    <td style="width: 60px;">No. Doc.</td>
                    <td style="text-align: left; width: 150px;">CLIENTE</td>
                    <td style="width: 80px;">EXENTAS</td>
                    <td style="text-align: right; width: 80px;">NO SUJETAS</td>
                    <td style="text-align: right; width: 100px;">INTERNAS GRAVADAS</td>
                    <td style="text-align: right; width: 80px;">DEBITO FISCAL</td>
                    <td style="text-align: right; width: 80px;">VENTA TOTAL</td>
                    <td style="text-align: center; min-width: 200px;">NÚMERO CONTROL DTE</td>
                    <td style="text-align: center; min-width: 200px;">CÓDIGO GENERACIÓN</td>
                    <td style="text-align: center; min-width: 200px;">SELLO RECEPCIÓN</td>
                </tr>
                        <?php
                        $i = 1;
                        $tot_exentas = 0.00;
                        $tot_int_grav = 0.00;
                        $tot_debfiscal = 0.00;
                        $tot_nosujetas = 0.00;
                        $tot_exentas2 = 0.00;
                        $tot_int_grav2 = 0.00;
                        $tot_deb_fiscal2 = 0.00;
                        $tot_iva_percibido = 0.00;
                        $tot_final = 0.00;
                            ?>
                            @foreach ($sales as $sale)
                    <tr>
                        <td style="text-align: center; padding-top: 0px; margin-top: 0px; padding-bottom: 0px; margin-bottom: 0px;">
                            <?php
                            echo $i;
                            ?>
                        </td>
                        <td style="text-align: center; padding-top: 0px; margin-top: 0px; padding-bottom: 0px; margin-bottom: 0px;">
                            <?php echo $sale['dateF']; ?>
                        </td>
                        <td style="text-align: center; padding-top: 0px; margin-top: 0px; padding-bottom: 0px; margin-bottom: 0px; font-size: 9px;">
                            <?php echo $sale['correlativo'] ?? '-'; ?>
                        </td>
                        <td class="text-uppercase" style="text-align: left; padding-top: 0px; margin-top: 0px; padding-bottom: 0px; margin-bottom: 0px;">
                            @if($sale['typesale']=='0')
                            ANULADO
                            @else
                            @if($sale['tpersona']=='J')
                                {{$sale['comercial_name']}}
                            @endif
                            @if($sale['tpersona']=='N')
                                {{$sale['firstname'] .' '.$sale['firstlastname'] }}
                            @endif
                        @endif
                        </td>
                        <td class="text-uppercase" style="text-align: center; padding-top: 0px; margin-top: 0px; padding-bottom: 0px; margin-bottom: 0px;">
                            @if($sale['typesale']=='0')
                            ANULADO
                            @else
                             {{ number_format($sale['exenta'], 2) }}
                        @endif
                            <?php
                            $tot_exentas += $sale['exenta'];
                            $dfe = $sale['exenta'];
                            ?>
                        </td>
                        <td style="text-align: center; padding-top: 0px; margin-top: 0px; padding-bottom: 0px; margin-bottom: 0px;">
                            @if($sale['typesale']=='0')
                            ANULADO
                            @else
                             {{ number_format($sale['nosujeta'], 2) }}
                        @endif
                            <?php
                            $tot_nosujetas += $sale['nosujeta'];
                            ?>
                        </td>
                        <td style="text-align: center;">
                            @if($sale['typesale']=='0')
                            ANULADO
                            @else
                             {{ number_format($sale['gravada'], 2) }}
                        @endif
                            <?php
                                    $df = ($sale['gravada']);
                                    $tot_int_grav += $df;
                            ?>
                        </td>
                        <td style="text-align: center; padding-top: 0px; margin-top: 0px; padding-bottom: 0px; margin-bottom: 0px;">
                            @if($sale['typesale']=='0')
                            ANULADO
                            @else
                             {{ number_format($sale['iva'], 2) }}
                        @endif
                            <?php
                                    $deb_Fiscal = $sale['iva'];
                                    $tot_debfiscal += $deb_Fiscal;
                            ?>

                        </td>

                        <td style="text-align: center; padding-top: 0px; margin-top: 0px; padding-bottom: 0px; margin-bottom: 0px;">
                            @if($sale['typesale']=='0')
                            ANULADO
                            @else
                             {{ number_format($sale['totalamount'], 2) }}
                        @endif
                            <?php
                            $tot = $sale['totalamount'];
                            $tot_final = $tot_final + $tot;
                            ?>
                        </td>
                        <td style="text-align: center; padding-top: 0px; margin-top: 0px; padding-bottom: 0px; margin-bottom: 0px; font-size: 8px; white-space: nowrap; min-width: 200px;">
                            @if($sale['typesale']=='0')
                            ANULADO
                            @else
                            {{ $sale['numeroControl'] ?? '-' }}
                        @endif
                        </td>
                        <td style="text-align: center; padding-top: 0px; margin-top: 0px; padding-bottom: 0px; margin-bottom: 0px; font-size: 8px; white-space: nowrap; min-width: 200px;">
                            @if($sale['typesale']=='0')
                            ANULADO
                            @else
                            {{ $sale['codigoGeneracion'] ?? '-' }}
                        @endif
                        </td>
                        <td style="text-align: center; padding-top: 0px; margin-top: 0px; padding-bottom: 0px; margin-bottom: 0px; font-size: 8px; white-space: nowrap; min-width: 200px;">
                            @if($sale['typesale']=='0')
                            ANULADO
                            @else
                            {{ $sale['selloRecibido'] ?? '-' }}
                        @endif
                        </td>
                    </tr><?php
                    ++$i;
                ?>
                @endforeach
                <tr class="text-right">
                    <td class="text-right" colspan="4">
                        TOTALES DEL MES
                    </td>
                    <td class="text-center">
                        <?php
                        echo number_format($tot_exentas,2);
                        ?>
                    </td>
                    <td class="text-center">
                        <?php
                        echo number_format($tot_nosujetas,2);
                        ?>
                    </td>
                    <td class="text-center">
                        <?php
                        echo number_format($tot_int_grav,2);
                        ?>
                    </td>
                    <td class="text-center">
                    <?php
                    echo number_format($tot_debfiscal,2);
                    ?>
                    </td>
                    <td class="text-center">
                    <?php
                    echo number_format($tot_final,2);
                    ?>
                    </td>
                    <td class="text-center">
                        -
                    </td>
                    <td class="text-center">
                        -
                    </td>
                    <td class="text-center">
                        -
                    </td>
                </tr>
                <tr>
                    <td colspan="13" style="text-align: center;">
                        <br><br><b>LIQUIDACION DEL DEBITO FISCAL EN VENTAS DIRECTAS</b>
                    </td>
                </tr>
                <tr>
                    <td colspan="6" style="text-align: right;">
                        <b>GRAVADAS, NO SUJETAS, EXENTAS, SIN IVA</b>
                    </td>
                    <td colspan="2" style="text-align: right;">
                        <?php echo number_format($tot_int_grav+$tot_exentas+$tot_nosujetas, 2); ?>
                    </td>
                    <td colspan="5"></td>
                </tr>
                <tr>
                    <td colspan="3">
                        VENTAS EXENTAS
                    </td>
                    <td style="text-align: right;">
                        <?php echo number_format($tot_exentas, 2); ?>
                    </td>
                    <td style="text-align: right;">
                        13 %
                    </td>
                    <td style="text-align: right;">
                        <?php echo number_format($tot_debfiscal, 2); ?>
                    </td>
                    <td colspan="7"></td>
                </tr>
                <tr>
                    <td colspan="3">
                        VENTAS NO SUJETAS
                    </td>
                    <td style="text-align: right;">
                        <?php echo number_format($tot_nosujetas, 2); ?>
                    </td>
                    <td style="text-align: right;">
                        0 %
                    </td>
                    <td style="text-align: right;">
                        0.00
                    </td>
                    <td colspan="7"></td>
                </tr>
                <tr>
                    <td colspan="3">
                        <b>VENTA LOCALES GRAVADAS</b>
                    </td>
                    <td style="text-align: right;">
                        <?php echo number_format($tot_int_grav, 2); ?>
                    </td>
                    <td style="text-align: right;">
                        <b>TOTAL</b>
                    </td>
                    <td style="text-align: right;">
                        <?php
                        $totales = $tot_int_grav + $tot_debfiscal;
                        echo number_format($totales, 2);
                        ?>
                    </td>
                    <td colspan="7"></td>
                </tr>
            </tbody>
        </table>
        </div>
    </div>
</div>
    @endisset
    <!--Search Form -->
<!--/ Advanced Search -->
@endsection
