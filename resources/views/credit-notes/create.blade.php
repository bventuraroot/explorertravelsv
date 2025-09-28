@php
    $configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('vendor-style')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/formvalidation/dist/css/formValidation.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/flatpickr/flatpickr.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/sweetalert2/sweetalert2.css') }}" />
@endsection

@section('vendor-script')
    <script src="{{ asset('assets/vendor/libs/select2/select2.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/formvalidation/dist/js/FormValidation.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/formvalidation/dist/js/plugins/Bootstrap5.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/formvalidation/dist/js/plugins/AutoFocus.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/flatpickr/flatpickr.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/sweetalert2/sweetalert2.js') }}"></script>
@endsection

@section('page-script')
    <script src="{{ asset('assets/js/forms-credit-notes.js') }}"></script>
@endsection

@section('title', 'Nueva Nota de Crédito')

@section('content')
    <div class="card">
        <div class="card-header border-bottom">
            <h5 class="mb-3 card-title">
                <i class="ti ti-file-minus me-2"></i>
                Nueva Nota de Crédito
            </h5>
        </div>

        <form id="creditNoteForm" action="{{ route('credit-notes.store', ['sale_id' => $sale->id ?? 0]) }}" method="POST">
            @csrf
            <input type="hidden" id="redirectToSales" value="{{ route('sale.index') }}">

            <div class="card-body">
                <!-- Mostrar errores de sesión -->
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="ti ti-alert-circle me-2"></i>
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <!-- Mostrar errores de validación -->
                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="ti ti-alert-circle me-2"></i>
                        <strong>Errores de validación:</strong>
                        <ul class="mt-2 mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <!-- Mostrar mensaje de éxito -->
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="ti ti-check-circle me-2"></i>
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <!-- Información de la venta original -->
                @if($sale)
                    <div class="mb-4 card">
                        <div class="card-header">
                            <h6 class="mb-0 card-title">
                                <i class="ti ti-file-text me-2"></i>
                                Información de la Factura Original
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Información básica de la venta -->
                                <div class="col-md-6">
                                    <h6 class="mb-3 text-primary">Datos de la Venta</h6>
                                    <div class="mb-2 row">
                                        <div class="col-5"><strong>Cliente:</strong></div>
                                        <div class="col-7">
                                            {{ $sale->client->tpersona == 'N' ? $sale->client->firstname . ' ' . $sale->client->firstlastname : $sale->client->name_contribuyente }}
                                        </div>
                                    </div>
                                    <div class="mb-2 row">
                                        <div class="col-5"><strong>NIT Cliente:</strong></div>
                                        <div class="col-7">{{ $sale->client->nit ?? 'N/A' }}</div>
                                    </div>
                                    <div class="mb-2 row">
                                        <div class="col-5"><strong>Fecha:</strong></div>
                                        <div class="col-7">{{ \Carbon\Carbon::parse($sale->date)->format('d/m/Y') }}</div>
                                    </div>
                                    <div class="mb-2 row">
                                        <div class="col-5"><strong>Total:</strong></div>
                                        <div class="col-7 text-success fw-bold">${{ number_format($sale->totalamount, 2) }}</div>
                                    </div>
                                    <div class="mb-2 row">
                                        <div class="col-5"><strong>Estado:</strong></div>
                                        <div class="col-7">
                                            @if($sale->state == 1)
                                                <span class="badge bg-success">Activa</span>
                                            @else
                                                <span class="badge bg-danger">Inactiva</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <!-- Información del DTE -->
                                <div class="col-md-6">
                                    <h6 class="mb-3 text-primary">Datos del DTE</h6>
                                    @if($sale->dte)
                                        <div class="mb-2 row">
                                            <div class="col-5"><strong>Número DTE:</strong></div>
                                            <div class="col-7">{{ $sale->dte->id_doc ?? 'N/A' }}</div>
                                        </div>
                                        <div class="mb-2 row">
                                            <div class="col-5"><strong>Tipo DTE:</strong></div>
                                            <div class="col-7">
                                                @if($sale->dte->tipoDte == '01')
                                                    <span class="badge bg-info">01 - Factura</span>
                                                @elseif($sale->dte->tipoDte == '03')
                                                    <span class="badge bg-primary">03 - Crédito Fiscal</span>
                                                @else
                                                    <span class="badge bg-secondary">{{ $sale->dte->tipoDte }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="mb-2 row">
                                            <div class="col-5"><strong>Estado Hacienda:</strong></div>
                                            <div class="col-7">
                                                @if($sale->dte->estadoHacienda == 'PROCESADO')
                                                    <span class="badge bg-success">Procesado</span>
                                                @elseif($sale->dte->estadoHacienda == 'RECHAZADO')
                                                    <span class="badge bg-danger">Rechazado</span>
                                                @elseif($sale->dte->estadoHacienda == 'PENDIENTE')
                                                    <span class="badge bg-warning">Pendiente</span>
                                                @else
                                                    <span class="badge bg-secondary">{{ $sale->dte->estadoHacienda ?? 'N/A' }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="mb-2 row">
                                            <div class="col-5"><strong>Fecha DTE:</strong></div>
                                            <div class="col-7">
                                                {{ $sale->dte->fhRecibido ? \Carbon\Carbon::parse($sale->dte->fhRecibido)->format('d/m/Y H:i') : 'N/A' }}
                                            </div>
                                        </div>
                                        <div class="mb-2 row">
                                            <div class="col-5"><strong>Autorización:</strong></div>
                                            <div class="col-7">
                                                @if($sale->dte->selloRecibido)
                                                    <small class="text-muted">{{ Str::limit($sale->dte->selloRecibido, 20) }}</small>
                                                @else
                                                    N/A
                                                @endif
                                            </div>
                                        </div>
                                        <div class="mb-2 row">
                                            <div class="col-5"><strong>Ambiente:</strong></div>
                                            <div class="col-7">
                                                @php $amb = $sale->dte->ambiente_id ?? null; @endphp
                                                @if($amb === 1 || $amb === '1' || $amb === '00')
                                                    <span class="badge bg-info">Producción</span>
                                                @elseif($amb === 2 || $amb === '2' || $amb === '01')
                                                    <span class="badge bg-warning">Pruebas</span>
                                                @else
                                                    <span class="badge bg-secondary">N/A</span>
                                                @endif
                                            </div>
                                        </div>
                                    @else
                                        <div class="mb-0 alert alert-warning">
                                            <i class="ti ti-alert-triangle me-2"></i>
                                            No se encontró información del DTE para esta venta.
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Historial de Notas de Crédito previas -->
                    @if(isset($historialNCR) && $historialNCR->count() > 0)
                    <div class="mb-4 card">
                        <div class="card-header">
                            <h6 class="mb-0 card-title">
                                <i class="ti ti-history me-2"></i>
                                Historial de Notas de Crédito de esta Factura
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Fecha</th>
                                            <th>Total</th>
                                            <th>Estado Hacienda</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($historialNCR as $ncr)
                                            <tr>
                                                <td>{{ $ncr->dte && $ncr->dte->id_doc ? $ncr->dte->id_doc : ('#' . $ncr->id) }}</td>
                                                <td>{{ \Carbon\Carbon::parse($ncr->date)->format('d/m/Y') }}</td>
                                                <td class="text-end">${{ number_format($ncr->totalamount, 2) }}</td>
                                                <td>
                                                    @if($ncr->dte && $ncr->dte->estadoHacienda)
                                                        <span class="badge {{ $ncr->dte->estadoHacienda === 'PROCESADO' ? 'bg-success' : ($ncr->dte->estadoHacienda === 'RECHAZADO' ? 'bg-danger' : 'bg-warning') }}">
                                                            {{ $ncr->dte->estadoHacienda }}
                                                        </span>
                                                    @else
                                                        <span class="badge bg-secondary">N/A</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Información básica (automática desde la venta original) -->
                    <input type="hidden" name="sale_id" value="{{ $sale->id }}">
                    <input type="hidden" name="company_id" value="{{ $sale->company_id }}">
                    <input type="hidden" name="typedocument_id" value="{{ $tipoDocumentoSeleccionado }}">

                    <div class="mb-3 row">
                        <div class="col-md-6">
                            <label class="form-label">Empresa</label>
                            <input type="text" class="form-control" value="{{ $sale->company->name ?? 'N/A' }} - {{ $sale->company->nit ?? 'N/A' }}" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tipo de Documento</label>
                            <input type="text" class="form-control" value="Nota de Crédito" readonly>
                        </div>
                    </div>
                @else
                    <!-- Campos originales para cuando no hay venta seleccionada -->
                    <div class="mb-3 row">
                        <div class="col-md-6">
                            <label class="form-label">Venta Original <span class="text-danger">*</span></label>
                            <select name="sale_id" id="sale_id" class="form-select" required>
                                <option value="">Seleccionar venta...</option>
                                <!-- Aquí se cargarían las ventas disponibles -->
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Empresa <span class="text-danger">*</span></label>
                            <select name="company_id" id="company_id" class="form-select" required>
                                <option value="">Seleccionar empresa...</option>
                                @foreach($empresas as $empresa)
                                    <option value="{{ $empresa->id }}" {{ old('company_id') == $empresa->id ? 'selected' : ($empresaSeleccionada == $empresa->id ? 'selected' : '') }}>
                                        {{ $empresa->name }} - {{ $empresa->nit }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <div class="col-md-6">
                            <label class="form-label">Tipo de Documento <span class="text-danger">*</span></label>
                            <select name="typedocument_id" id="typedocument_id" class="form-select" required>
                                <option value="">Seleccionar tipo...</option>
                                @foreach($tiposDocumento as $tipo)
                                    <option value="{{ $tipo->id }}" {{ old('typedocument_id') == $tipo->id ? 'selected' : ($tipoDocumentoSeleccionado == $tipo->id ? 'selected' : '') }}>
                                        {{ $tipo->description }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @endif

                <div class="mb-3 row">
                    <div class="col-12">
                        <label class="form-label">Motivo de la Nota de Crédito <span class="text-danger">*</span></label>
                        <textarea name="motivo" id="motivo" class="form-control" rows="3" placeholder="Describa el motivo de la nota de crédito..." required>{{ old('motivo') }}</textarea>
                    </div>
                </div>

                <!-- Resumen de productos originales -->
                @if($sale && $sale->salesdetails->count() > 0)
                    <div class="mb-4 card">
                        <div class="card-header">
                            <h6 class="mb-0 card-title">
                                <i class="ti ti-list me-2"></i>
                                Productos de la Factura Original
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Código</th>
                                            <th>Producto</th>
                                            <th>Cantidad</th>
                                            <th>Precio Unit.</th>
                                            <th>Subtotal</th>
                                            <th>IVA</th>
                                            <th>Total</th>
                                            <th>Tipo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($sale->salesdetails as $detalle)
                                            <tr>
                                                <td>{{ $detalle->product->code ?? 'N/A' }}</td>
                                                <td>{{ $detalle->product->name ?? 'N/A' }}</td>
                                                <td class="text-center">{{ number_format($detalle->amountp, 2) }}</td>
                                                <td class="text-end">${{ number_format($detalle->pricesale / $detalle->amountp, 2) }}</td>
                                                <td class="text-end">${{ number_format($detalle->pricesale, 2) }}</td>
                                                <td class="text-end">${{ number_format($detalle->detained13, 2) }}</td>
                                                <td class="text-end">${{ number_format($detalle->pricesale + $detalle->detained13, 2) }}</td>
                                                <td class="text-center">
                                                    @if($detalle->exempt > 0)
                                                        <span class="badge bg-warning">Exento</span>
                                                    @elseif($detalle->nosujeta > 0)
                                                        <span class="badge bg-info">No Sujeta</span>
                                                    @else
                                                        <span class="badge bg-success">Gravado</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="table-light">
                                        <tr>
                                            <th colspan="6" class="text-end">Total Original:</th>
                                            <th class="text-end">${{ number_format($sale->totalamount, 2) }}</th>
                                            <th></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Productos -->
                <div class="mb-3 row">
                    <div class="col-12">
                        <h6 class="mb-3">
                            <i class="ti ti-package me-2"></i>
                            Productos a Incluir en la Nota de Crédito
                        </h6>

                        @if($sale)
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th width="5%">
                                                <input type="checkbox" id="selectAll" class="form-check-input">
                                            </th>
                                            <th width="8%">Código</th>
                                            <th width="25%">Producto</th>
                                            <th width="8%">Cant. Original</th>
                                            <th width="10%">Precio Original</th>
                                            <th width="8%">Cant. NC</th>
                                            <th width="10%">Precio Unit.</th>
                                            <th width="8%">IVA</th>
                                            <th width="10%">Tipo</th>
                                        </tr>
                                    </thead>
                                    <tbody id="productsTable">
                                        @foreach($sale->salesdetails as $index => $detalle)
                                            <tr data-original-cant="{{ $detalle->amountp }}" data-original-price="{{ $detalle->pricesale / $detalle->amountp }}">
                                                <td>
                                                    <input type="checkbox" name="productos[{{ $index }}][incluir]" class="form-check-input product-checkbox" value="1">
                                                </td>
                                                <td>{{ $detalle->product->code }}</td>
                                                <td>{{ $detalle->product->name }}</td>
                                                <td>{{ $detalle->amountp }}</td>
                                                <td>${{ number_format($detalle->pricesale / $detalle->amountp, 2) }}</td>
                                                <td>
                                                    <input type="number" name="productos[{{ $index }}][cantidad]"
                                                           class="form-control form-control-sm"
                                                           min="0" max="{{ $detalle->amountp }}"
                                                           step="1" value="0">
                                                </td>
                                                <td>
                                                    <input type="number" name="productos[{{ $index }}][precio]"
                                                           class="form-control form-control-sm"
                                                           min="0" step="0.01" value="{{ number_format($detalle->pricesale / $detalle->amountp, 2) }}" readonly>
                                                </td>
                                                <td class="text-end">
                                                    <span class="iva-display">$0.00</span>
                                                </td>
                                                <td>
                                                    <select name="productos[{{ $index }}][tipo_venta]" class="form-select form-select-sm">
                                                        <option value="gravada" {{ $detalle->exempt == 0 && $detalle->nosujeta == 0 ? 'selected' : '' }}>Gravada</option>
                                                        <option value="exenta" {{ $detalle->exempt > 0 ? 'selected' : '' }}>Exenta</option>
                                                        <option value="nosujeta" {{ $detalle->nosujeta > 0 ? 'selected' : '' }}>No Sujeta</option>
                                                    </select>
                                                </td>
                                                <input type="hidden" name="productos[{{ $index }}][product_id]" value="{{ $detalle->product->id }}">
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-warning">
                                <i class="ti ti-alert-triangle me-2"></i>
                                Seleccione una venta para ver los productos disponibles.
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Resumen de totales -->
                <div class="row">
                    <div class="col-md-6 offset-md-6">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title">Resumen de Totales</h6>
                                <div class="row">
                                    <div class="col-6">Subtotal Gravado:</div>
                                    <div class="col-6 text-end" id="subtotalGravado">$0.00</div>
                                </div>
                                <div class="row">
                                    <div class="col-6">IVA (13%):</div>
                                    <div class="col-6 text-end" id="iva">$0.00</div>
                                </div>
                                <div class="row">
                                    <div class="col-6">Subtotal Exento:</div>
                                    <div class="col-6 text-end" id="subtotalExento">$0.00</div>
                                </div>
                                <div class="row">
                                    <div class="col-6">Subtotal No Sujeto:</div>
                                    <div class="col-6 text-end" id="subtotalNoSujeto">$0.00</div>
                                </div>
                                <hr>
                                <div class="row fw-bold">
                                    <div class="col-6">Total:</div>
                                    <div class="col-6 text-end" id="total">$0.00</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer">
                <div class="d-flex justify-content-between">
                    <a href="{{ route('sale.index') }}" class="btn btn-outline-secondary">
                        <i class="ti ti-arrow-left me-1"></i>
                        Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-check me-1"></i>
                        Crear Nota de Crédito
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Seleccionar todos los productos
    $('#selectAll').on('change', function() {
        $('.product-checkbox').prop('checked', this.checked);
        calculateTotals();
    });

    // Calcular totales cuando cambien los valores
    $(document).on('input', 'input[name*="[cantidad]"], input[name*="[precio]"], select[name*="[tipo_venta]"]', function() {
        calculateTotals();
    });

    $(document).on('change', '.product-checkbox', function() {
        calculateTotals();
    });

    function calculateTotals() {
        let subtotalGravado = 0;
        let subtotalExento = 0;
        let subtotalNoSujeto = 0;
        let iva = 0;

        $('.product-checkbox:checked').each(function() {
            const row = $(this).closest('tr');
            const cantidad = parseFloat(row.find('input[name*="[cantidad]"]').val()) || 0;
            const precio = parseFloat(row.find('input[name*="[precio]"]').val()) || 0;
            const tipoVenta = row.find('select[name*="[tipo_venta]"]').val();
            const subtotal = cantidad * precio;

            if (tipoVenta === 'gravada') {
                subtotalGravado += subtotal;
                iva += subtotal * 0.13;
            } else if (tipoVenta === 'exenta') {
                subtotalExento += subtotal;
            } else if (tipoVenta === 'nosujeta') {
                subtotalNoSujeto += subtotal;
            }

            // Actualizar IVA de la fila
            const ivaFila = tipoVenta === 'gravada' ? subtotal * 0.13 : 0;
            row.find('.iva-display').text('$' + ivaFila.toFixed(2));
        });

        const total = subtotalGravado + subtotalExento + subtotalNoSujeto + iva;

        $('#subtotalGravado').text('$' + subtotalGravado.toFixed(2));
        $('#iva').text('$' + iva.toFixed(2));
        $('#subtotalExento').text('$' + subtotalExento.toFixed(2));
        $('#subtotalNoSujeto').text('$' + subtotalNoSujeto.toFixed(2));
        $('#total').text('$' + total.toFixed(2));
    }

    // Validación del formulario
    $('#creditNoteForm').on('submit', function(e) {
        const checkedProducts = $('.product-checkbox:checked').length;

        if (checkedProducts === 0) {
            e.preventDefault();
            Swal.fire('Error', 'Debe seleccionar al menos un producto para la nota de crédito.', 'error');
            return false;
        }

        // Marcar como incluidos solo los productos seleccionados
        $('.product-checkbox').each(function() {
            if (!$(this).is(':checked')) {
                $(this).closest('tr').find('input, select').prop('disabled', true);
            }
        });
    });

    // Calcular totales iniciales
    calculateTotals();
});
</script>
@endpush
