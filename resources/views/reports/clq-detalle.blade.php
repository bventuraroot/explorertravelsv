@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('vendor-style')
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/formvalidation/dist/css/formValidation.min.css') }}" />
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

        var form = $('<form>', { method: 'POST', action: '{{ route("report.clqDetalle.excel") }}' });
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
    <span class="text-muted fw-light">Reportes / </span>Detalle de Facturas por Comprobante de Liquidación
</h4>

<div class="card">
    <div class="card-datatable table-responsive">
        <form method="POST" id="buscar" action="{{ route('report.clqDetalle.search') }}">
            @csrf
            <div class="container">
                <br>
                <div class="row">
                    <div class="mb-3 col-md-3">
                        <label for="company" class="form-label">Empresa</label>
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
                                $yearDefault = isset($yearB) ? $yearB : (date('n') == 1 ? date('Y') - 1 : date('Y'));
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
            $tiposDoc = [
                '01' => 'FCF', '03' => 'CCF', '04' => 'Nota de Remisión',
                '05' => 'Nota de Crédito', '06' => 'Nota de Débito',
                '11' => 'FEX', '14' => 'FSE',
            ];
            $i = 1;
            $tot_clq = 0;
            $tot_gravadas = 0;
            $tot_exentas  = 0;
            $tot_nosujetas = 0;
            $tot_iva = 0;
        @endphp

        <div style="overflow-x: auto; padding: 16px;">

            {{-- Encabezado del reporte --}}
            <div class="text-center mb-3">
                <h5 class="fw-bold mb-1">DETALLE DE FACTURAS POR COMPROBANTE DE LIQUIDACIÓN</h5>
                <p class="mb-0" style="font-size: 13px;">
                    <strong>Empresa:</strong> {{ $heading->name ?? '' }} &nbsp;
                    <strong>NRC:</strong> {{ $heading->nrc ?? '' }} &nbsp;
                    <strong>MES:</strong> {{ strtoupper($mesesDelAno[(int)$period - 1]) }} &nbsp;
                    <strong>AÑO:</strong> {{ $yearB }}
                </p>
            </div>

            <table class="table table-bordered" style="min-width: 2000px; font-size: 11px;">
                <thead>
                    <tr style="background-color: #1e3a5f; color: #fff; text-align: center;">
                        {{-- Columnas CLQ --}}
                        <th style="white-space: nowrap;">N°</th>
                        <th style="white-space: nowrap;">FECHA CLQ</th>
                        <th style="white-space: nowrap; min-width: 160px;">CLIENTE</th>
                        <th style="white-space: nowrap;">NRC</th>
                        <th style="white-space: nowrap;">ESTADO</th>
                        <th style="white-space: nowrap; min-width: 180px;">Nº CONTROL DTE</th>
                        <th style="white-space: nowrap; min-width: 280px;">CÓD. GENERACIÓN</th>
                        <th style="white-space: nowrap; min-width: 280px;">SELLO RECEPCIÓN</th>
                        <th style="white-space: nowrap;">TOTAL CLQ</th>
                        {{-- Separador visual --}}
                        <th style="background-color: #2e5fa3; white-space: nowrap;">TIPO DOC</th>
                        <th style="background-color: #2e5fa3; white-space: nowrap;">TIPO GEN.</th>
                        <th style="background-color: #2e5fa3; white-space: nowrap; min-width: 280px;">Nº DOCUMENTO RELACIONADO</th>
                        <th style="background-color: #2e5fa3; white-space: nowrap;">FECHA FACTURA</th>
                        <th style="background-color: #2e5fa3; white-space: nowrap; min-width: 160px;">OBSERVACIONES</th>
                        <th style="background-color: #2e5fa3; white-space: nowrap;">GRAVADAS</th>
                        <th style="background-color: #2e5fa3; white-space: nowrap;">EXENTAS</th>
                        <th style="background-color: #2e5fa3; white-space: nowrap;">NO SUJETAS</th>
                        <th style="background-color: #2e5fa3; white-space: nowrap;">IVA</th>
                        <th style="background-color: #2e5fa3; white-space: nowrap; min-width: 180px;">PROVEEDOR</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sales as $sale)
                        @php
                            $esAnulado  = $sale->typesale == '0';
                            $nrcLimpio  = preg_replace('/[^0-9]/', '', $sale->ncrC ?? '');
                            $rowBg      = $esAnulado ? '#fff0f0' : '#eaf1fb';
                            $textColor  = $esAnulado ? 'color:#c00;font-weight:bold;' : 'font-weight:bold;';
                            $facturas   = $sale->facturas ?? collect();
                        @endphp

                        @if($facturas->isEmpty())
                            {{-- CLQ sin facturas relacionadas registradas --}}
                            <tr style="background-color: {{ $rowBg }};">
                                <td style="text-align:center; white-space:nowrap;">{{ $i }}</td>
                                <td style="white-space:nowrap;">{{ $sale->dateF ?? '-' }}</td>
                                <td class="text-uppercase" style="{{ $textColor }}">
                                    @if($esAnulado)
                                        <span style="color:#c00; font-weight:bold;">ANULADO</span>
                                    @else
                                        {{ $sale->nombre_completo ?? '' }}
                                    @endif
                                </td>
                                <td style="text-align:right;">{{ $nrcLimpio }}</td>
                                <td style="text-align:center;">
                                    @if($esAnulado)
                                        <span class="badge bg-danger">ANULADO</span>
                                    @else
                                        <span class="badge bg-success">ACTIVO</span>
                                    @endif
                                </td>
                                <td style="font-size:9px; white-space:nowrap;">{{ $sale->numeroControl ?? '-' }}</td>
                                <td style="font-size:9px; white-space:nowrap;">{{ $sale->codigoGeneracion ?? '-' }}</td>
                                <td style="font-size:9px; white-space:nowrap;">{{ $sale->selloRecibido ?? '-' }}</td>
                                <td style="text-align:right;">{{ number_format($sale->totalamount ?? 0, 2) }}</td>
                                <td colspan="10" style="text-align:center; color:#888; font-style:italic;">Sin facturas relacionadas</td>
                            </tr>
                            @if(!$esAnulado) @php $tot_clq += $sale->totalamount ?? 0; @endphp @endif
                        @else
                            @foreach($facturas as $idx => $fac)
                                @php
                                    $tipoCod  = $fac->clq_tipo_documento ?? '';
                                    $tipoDesc = $tiposDoc[$tipoCod] ?? $tipoCod;
                                    $tipoGen  = $fac->clq_tipo_generacion == '1' ? 'Físico'
                                              : ($fac->clq_tipo_generacion == '2' ? 'Electrónico'
                                              : ($fac->clq_tipo_generacion ?? '-'));
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
                                <tr style="background-color: {{ $idx % 2 == 0 ? ($esAnulado ? '#fff0f0' : '#eaf1fb') : '#fff' }};">
                                    {{-- Datos del CLQ: solo en la primera factura del grupo --}}
                                    @if($idx === 0)
                                        <td style="text-align:center; white-space:nowrap; vertical-align:top;">{{ $i }}</td>
                                        <td style="white-space:nowrap; vertical-align:top;">{{ $sale->dateF ?? '-' }}</td>
                                        <td class="text-uppercase" style="vertical-align:top;">
                                            @if($esAnulado)
                                                <span style="color:#c00; font-weight:bold;">ANULADO</span>
                                            @else
                                                {{ $sale->nombre_completo ?? '' }}
                                            @endif
                                        </td>
                                        <td style="text-align:right; vertical-align:top;">{{ $nrcLimpio }}</td>
                                        <td style="text-align:center; vertical-align:top;">
                                            @if($esAnulado)
                                                <span class="badge bg-danger">ANULADO</span>
                                            @else
                                                <span class="badge bg-success">ACTIVO</span>
                                            @endif
                                        </td>
                                        <td style="font-size:9px; white-space:nowrap; vertical-align:top;">{{ $sale->numeroControl ?? '-' }}</td>
                                        <td style="font-size:9px; white-space:nowrap; vertical-align:top;">{{ $sale->codigoGeneracion ?? '-' }}</td>
                                        <td style="font-size:9px; white-space:nowrap; vertical-align:top;">{{ $sale->selloRecibido ?? '-' }}</td>
                                        <td style="text-align:right; vertical-align:top; font-weight:bold;">{{ number_format($sale->totalamount ?? 0, 2) }}</td>
                                        @if(!$esAnulado) @php $tot_clq += $sale->totalamount ?? 0; @endphp @endif
                                    @else
                                        <td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>
                                    @endif

                                    {{-- Datos de la factura relacionada --}}
                                    <td style="text-align:center; white-space:nowrap;">{{ $tipoDesc }}</td>
                                    <td style="text-align:center; white-space:nowrap;">{{ $tipoGen }}</td>
                                    <td style="font-size:9px; white-space:nowrap;">{{ $fac->clq_numero_documento ?? '-' }}</td>
                                    <td style="text-align:center; white-space:nowrap;">
                                        {{ $fac->clq_fecha_generacion ? \Carbon\Carbon::parse($fac->clq_fecha_generacion)->format('d/m/Y') : '-' }}
                                    </td>
                                    <td>{{ $fac->clq_observaciones ?? '' }}</td>
                                    <td style="text-align:right;">{{ number_format($gravada, 2) }}</td>
                                    <td style="text-align:right;">{{ number_format($exenta, 2) }}</td>
                                    <td style="text-align:right;">{{ number_format($nosujeta, 2) }}</td>
                                    <td style="text-align:right;">{{ number_format($iva, 2) }}</td>
                                    <td>{{ $fac->provider_name ?? '-' }}</td>
                                </tr>
                            @endforeach
                        @endif
                        @php $i++; @endphp
                    @empty
                        <tr>
                            <td colspan="19" class="text-center text-muted py-4">
                                No se encontraron comprobantes de liquidación para el período seleccionado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr style="background-color:#c9daf8; font-weight:bold; text-align:right;">
                        <td colspan="8" style="text-align:right; padding-right:8px;">TOTALES DEL MES</td>
                        <td>{{ number_format($tot_clq, 2) }}</td>
                        <td colspan="5"></td>
                        <td>{{ number_format($tot_gravadas, 2) }}</td>
                        <td>{{ number_format($tot_exentas, 2) }}</td>
                        <td>{{ number_format($tot_nosujetas, 2) }}</td>
                        <td>{{ number_format($tot_iva, 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

    </div>
</div>
@endif

@endsection
