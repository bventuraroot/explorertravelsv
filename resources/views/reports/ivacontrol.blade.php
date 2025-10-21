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
@endsection

@section('title', 'Control de IVA y Pago a Cuenta')

@section('content')
<h4 class="py-3 mb-4 fw-bold">
    <span class="text-muted fw-light">Reportes /</span> Control de IVA y Pago a Cuenta
</h4>

<!-- Advanced Search -->
<div class="card">
    <form id="sendfilters" action="{{Route('report.ivacontrolsearch')}}" method="post">
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
                        for ($i=0; $i < 5 ; $i++) {
                            $yearnew = $year-$i;
                            $selected = "";
                            if(isset($yearB)){
                                if($yearnew==@$yearB){
                                    $selected="selected";
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
                <a href="#!" class='btn btn-success' title='Imprimir reporte' onclick="impFAC('areaImprimir');">
                    <i class="fa-solid fa-print"> </i> &nbsp;&nbsp;Imprimir
                </a>
            </div>
        </div>
    </div>
    <div id="areaImprimir">
        <div style="overflow-x: auto; max-width: 100%;">
        <table class="table" style="min-width: 800px;">
            <thead>
                <tr>
                    <th class="text-center" colspan="4">
                        <b>CONTROL DE IVA Y PAGO A CUENTA</b>
                    </th>
                </tr>
                <tr>
                    <td class="text-center" colspan="4">
                        <b>Nombre del Contribuyente: </b> <?php echo $heading['name']; ?>
                        <b>N.R.C.: </b> <?php echo $heading['ncr']; ?> <b>MES: </b><?php echo $mesesDelAnoMayuscula[(int)$period-1] ?>
                        <b>Año: </b> <?php echo $yearB; ?><p>(Valores expresados en Dólares Estadounidenses)</p>
                    </td>
                </tr>
            </thead>

            <tbody>
                <!-- SECCIÓN DE VENTAS -->
                <tr>
                    <td colspan="4" style="background-color: #f0f0f0; padding: 10px;">
                        <b>RESUMEN DE VENTAS</b>
                    </td>
                </tr>
                <tr>
                    <td style="width: 50%; padding-left: 20px;">Ventas Gravadas</td>
                    <td style="width: 20%; text-align: right;">$ {{ number_format($ventas_gravadas, 2) }}</td>
                    <td colspan="2"></td>
                </tr>
                <tr>
                    <td style="padding-left: 20px;">Ventas Exentas</td>
                    <td style="text-align: right;">$ {{ number_format($ventas_exentas, 2) }}</td>
                    <td colspan="2"></td>
                </tr>
                <tr>
                    <td style="padding-left: 20px;">Ventas No Sujetas</td>
                    <td style="text-align: right;">$ {{ number_format($ventas_nosujetas, 2) }}</td>
                    <td colspan="2"></td>
                </tr>
                <tr style="background-color: #e8f5e9;">
                    <td style="padding-left: 20px;"><b>Débito Fiscal (IVA en Ventas 13%)</b></td>
                    <td style="text-align: right;"><b>$ {{ number_format($debito_fiscal, 2) }}</b></td>
                    <td colspan="2"></td>
                </tr>

                <!-- SECCIÓN DE COMPRAS -->
                <tr>
                    <td colspan="4" style="background-color: #f0f0f0; padding: 10px; padding-top: 20px;">
                        <b>RESUMEN DE COMPRAS</b>
                    </td>
                </tr>
                <tr>
                    <td style="padding-left: 20px;">Compras Gravadas</td>
                    <td style="text-align: right;">$ {{ number_format($compras_gravadas, 2) }}</td>
                    <td colspan="2"></td>
                </tr>
                <tr>
                    <td style="padding-left: 20px;">Compras Exentas</td>
                    <td style="text-align: right;">$ {{ number_format($compras_exentas, 2) }}</td>
                    <td colspan="2"></td>
                </tr>
                <tr style="background-color: #fff3e0;">
                    <td style="padding-left: 20px;"><b>Crédito Fiscal (IVA en Compras 13%)</b></td>
                    <td style="text-align: right;"><b>$ {{ number_format($credito_fiscal, 2) }}</b></td>
                    <td colspan="2"></td>
                </tr>

                <!-- CÁLCULO DE IVA -->
                <tr>
                    <td colspan="4" style="background-color: #f0f0f0; padding: 10px; padding-top: 20px;">
                        <b>LIQUIDACIÓN DE IVA</b>
                    </td>
                </tr>
                <tr>
                    <td style="padding-left: 20px;">Débito Fiscal</td>
                    <td style="text-align: right;">$ {{ number_format($debito_fiscal, 2) }}</td>
                    <td colspan="2"></td>
                </tr>
                <tr>
                    <td style="padding-left: 20px;">(-) Crédito Fiscal</td>
                    <td style="text-align: right;">$ {{ number_format($credito_fiscal, 2) }}</td>
                    <td colspan="2"></td>
                </tr>
                <tr style="background-color: #e3f2fd;">
                    <td style="padding-left: 20px;"><b>IVA A PAGAR</b></td>
                    <td style="text-align: right;"><b>$ {{ number_format($iva_a_pagar, 2) }}</b></td>
                    <td colspan="2"></td>
                </tr>

                <!-- PAGO A CUENTA -->
                <tr>
                    <td colspan="4" style="background-color: #f0f0f0; padding: 10px; padding-top: 20px;">
                        <b>PAGO A CUENTA</b>
                    </td>
                </tr>
                <tr>
                    <td style="padding-left: 20px;">Base para Pago a Cuenta (Ventas Gravadas)</td>
                    <td style="text-align: right;">$ {{ number_format($ventas_gravadas, 2) }}</td>
                    <td colspan="2"></td>
                </tr>
                <tr>
                    <td style="padding-left: 20px;">Porcentaje</td>
                    <td style="text-align: right;">1.00 %</td>
                    <td colspan="2"></td>
                </tr>
                <tr style="background-color: #fce4ec;">
                    <td style="padding-left: 20px;"><b>PAGO A CUENTA (1% de ventas gravadas)</b></td>
                    <td style="text-align: right;"><b>$ {{ number_format($pago_a_cuenta, 2) }}</b></td>
                    <td colspan="2"></td>
                </tr>

                <!-- TOTAL A PAGAR -->
                <tr style="background-color: #c8e6c9; font-size: 18px;">
                    <td style="padding: 15px; padding-left: 20px;"><b>TOTAL A PAGAR AL FISCO</b></td>
                    <td style="text-align: right; padding: 15px;"><b>$ {{ number_format($total_a_pagar, 2) }}</b></td>
                    <td colspan="2"></td>
                </tr>

                <tr>
                    <td colspan="4" style="padding-top: 30px; padding-bottom: 10px; font-size: 12px; color: #666;">
                        <b>Nota:</b> El IVA a pagar corresponde al Débito Fiscal menos el Crédito Fiscal del mes.
                        El Pago a Cuenta corresponde al 1% de las ventas gravadas del mes. Ambos montos deben ser
                        pagados al fisco según los plazos establecidos por el Ministerio de Hacienda.
                    </td>
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

