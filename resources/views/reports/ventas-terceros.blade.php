@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Reporte de Ventas a Terceros')

@section('vendor-style')
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}">
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}">
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css') }}">
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/flatpickr/flatpickr.css') }}" />
@endsection

@section('vendor-script')
<script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/select2/select2.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/moment/moment.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/flatpickr/flatpickr.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endsection

@section('page-script')
<script>
$(document).ready(function() {
    // Inicializar Select2
    $('.select2').select2({
        placeholder: 'Seleccionar...',
        allowClear: true
    });

    // Inicializar Flatpickr para rango de fechas
    flatpickr("#fecha_ini", {
        dateFormat: "Y-m-d",
        locale: "es"
    });

    flatpickr("#fecha_fin", {
        dateFormat: "Y-m-d",
        locale: "es"
    });

    // Cargar proveedores
    $.ajax({
        url: "/provider/getproviders",
        method: "GET",
        success: function(response) {
            $('#provider_id').append('<option value="">Todos los proveedores</option>');
            $.each(response, function(index, value) {
                $('#provider_id').append(
                    '<option value="' + value.id + '">' +
                    value.razonsocial.toUpperCase() +
                    (value.nit ? ' - NIT: ' + value.nit : '') +
                    '</option>'
                );
            });
        }
    });

    // Función para cargar clientes por empresa
    function cargarClientes(companyId) {
        if (!companyId) {
            $('#client_id').empty().append('<option value="">Todos los clientes</option>');
            return;
        }

        $.ajax({
            url: "/client/getclientbycompany/" + btoa(companyId),
            method: "GET",
            success: function(response) {
                $('#client_id').empty().append('<option value="">Todos los clientes</option>');
                if (response && response.length > 0) {
                    $.each(response, function(index, value) {
                        var nombreCliente = value.tpersona === 'J'
                            ? value.comercial_name
                            : (value.firstname + ' ' + value.firstlastname);
                        $('#client_id').append(
                            '<option value="' + value.id + '">' +
                            nombreCliente.toUpperCase() +
                            '</option>'
                        );
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('Error al cargar clientes:', error);
                $('#client_id').empty().append('<option value="">Error al cargar clientes</option>');
            }
        });
    }

    // Cargar clientes cuando cambie la empresa
    $('#company').on('change', function() {
        var companyId = $(this).val();
        cargarClientes(companyId);
    });

    // Cargar clientes si hay una empresa preseleccionada al cargar la página
    var initialCompany = $('#company').val();
    if (initialCompany) {
        cargarClientes(initialCompany);
    }

    // Buscar ventas de terceros
    $('#form-buscar-terceros').on('submit', function(e) {
        e.preventDefault();
        e.stopPropagation();

        var company = $('#company').val();
        if (!company) {
            Swal.fire({
                icon: 'warning',
                title: 'Atención',
                text: 'Por favor, selecciona una empresa'
            });
            return false;
        }

        $.ajax({
            url: "{{ route('report.ventasTerceros.search') }}",
            method: "POST",
            data: $(this).serialize(),
            dataType: 'json',
            beforeSend: function() {
                $('#btn-buscar').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Buscando...');
                $('#tabla-resultados').hide();
                $('#resumen-cards').hide();
            },
            success: function(response) {
                console.log('Respuesta recibida:', response);
                mostrarResultados(response);
                $('#btn-buscar').prop('disabled', false).html('<i class="ti ti-search me-1"></i>Buscar');
            },
            error: function(xhr, status, error) {
                console.error('Error en AJAX:', xhr, status, error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error al buscar ventas: ' + (xhr.responseJSON?.message || error || 'Error desconocido')
                });
                $('#btn-buscar').prop('disabled', false).html('<i class="ti ti-search me-1"></i>Buscar');
            }
        });

        return false;
    });

    function mostrarResultados(response) {
        var data = response.data;
        var company = response.company;

        if (!data || data.length === 0) {
            $('#tabla-resultados').hide();
            $('#resumen-cards').hide();
            Swal.fire({
                icon: 'info',
                title: 'Sin resultados',
                text: 'No se encontraron ventas a terceros con los filtros seleccionados'
            });
            return;
        }

        // Actualizar encabezado
        $('#company-name').text(company.name || '-');
        $('#company-nit').text(company.nit || '-');
        $('#company-nrc').text(company.ncr && company.ncr !== '-' ? company.ncr : (company.ncr || '-'));

        // Mostrar resumen
        $('#resumen-cards').show();

        // Limpiar tabla
        $('#tbody-ventas').empty();

        var totalPendiente = 0;
        var totalLiquidado = 0;
        var countPendiente = 0;
        var countLiquidado = 0;

        // Agregar filas
        $.each(data, function(index, venta) {
            var estadoBadge = venta.estado_liquidacion === 'Liquidado'
                ? '<span class="badge bg-success"><i class="ti ti-check me-1"></i>Liquidado</span>'
                : '<span class="badge bg-warning"><i class="ti ti-clock me-1"></i>Pendiente</span>';

            var clqInfo = venta.clq_numero_control
                ? '<small class="text-muted"><i class="ti ti-file-text me-1"></i>' + venta.clq_numero_control + '</small>'
                : '-';

            var acciones = '';
            if (venta.estado_liquidacion === 'Pendiente') {
                acciones = '<a href="/sale/create?typedocument=2&client_id=' + venta.proveedor_nit + '&from_sale=' + venta.sale_id + '" class="btn btn-sm btn-primary" title="Crear CLQ"><i class="ti ti-file-plus"></i></a>';
                totalPendiente += parseFloat(venta.totalamount || 0);
                countPendiente++;
            } else {
                // Botón para ver/imprimir el PDF del CLQ relacionado
                acciones = '<a href="/sale/sale/print/' + venta.clq_id + '" target="_blank" class="btn btn-sm btn-info" title="Ver/Imprimir CLQ"><i class="ti ti-printer"></i></a>';
                totalLiquidado += parseFloat(venta.totalamount || 0);
                countLiquidado++;
            }

            var row = '<tr>' +
                '<td>' + (index + 1) + '</td>' +
                '<td>' + (venta.fecha_formato || '-') + '</td>' +
                '<td>' + (venta.cliente_nombre || 'N/A') + '</td>' +
                '<td>' + (venta.proveedor_nombre || 'N/A') + '<br><small class="text-muted">' + (venta.proveedor_nit || '') + '</small></td>' +
                '<td>' + (venta.tipo_documento || '-') + '</td>' +
                '<td><small class="text-muted">' + (venta.numero_control || '-') + '</small></td>' +
                '<td><small class="text-muted font-monospace" style="font-size: 0.8rem;">' + (venta.codigoGeneracion || '-') + '</small></td>' +
                '<td><small class="text-muted font-monospace" style="font-size: 0.7rem; word-break: break-all;">' + (venta.selloRecibido || '-') + '</small></td>' +
                '<td class="text-end">' + parseFloat(venta.totalamount || 0).toLocaleString('en-US', { style: 'currency', currency: 'USD' }) + '</td>' +
                '<td class="text-center">' + estadoBadge + '</td>' +
                '<td class="text-center">' + clqInfo + '</td>' +
                '<td class="text-center">' + acciones + '</td>' +
                '</tr>';

            $('#tbody-ventas').append(row);
        });

        // Actualizar resumen
        $('#count-pendiente').text(countPendiente);
        $('#total-pendiente').text(totalPendiente.toLocaleString('en-US', { style: 'currency', currency: 'USD' }));
        $('#count-liquidado').text(countLiquidado);
        $('#total-liquidado').text(totalLiquidado.toLocaleString('en-US', { style: 'currency', currency: 'USD' }));
        $('#count-total').text(data.length);
        $('#total-general').text((totalPendiente + totalLiquidado).toLocaleString('en-US', { style: 'currency', currency: 'USD' }));

        $('#tabla-resultados').show();
        $('#btn-export-excel').show();
        $('#excel-hint').hide();
    }

    // Exportar a Excel
    $('#btn-export-excel').on('click', function() {
        var company = $('#company').val();
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
            'action': '{{ route("report.ventasTerceros.excel") }}'
        });

        form.append($('<input>', {
            'type': 'hidden',
            'name': '_token',
            'value': '{{ csrf_token() }}'
        }));

        // Agregar todos los valores del formulario de búsqueda
        form.append($('<input>', {
            'type': 'hidden',
            'name': 'company',
            'value': company
        }));

        if ($('#fecha_ini').val()) {
            form.append($('<input>', {
                'type': 'hidden',
                'name': 'fecha_ini',
                'value': $('#fecha_ini').val()
            }));
        }

        if ($('#fecha_fin').val()) {
            form.append($('<input>', {
                'type': 'hidden',
                'name': 'fecha_fin',
                'value': $('#fecha_fin').val()
            }));
        }

        if ($('#provider_id').val()) {
            form.append($('<input>', {
                'type': 'hidden',
                'name': 'provider_id',
                'value': $('#provider_id').val()
            }));
        }

        if ($('#client_id').val()) {
            form.append($('<input>', {
                'type': 'hidden',
                'name': 'client_id',
                'value': $('#client_id').val()
            }));
        }

        if ($('#estado_liquidacion').val()) {
            form.append($('<input>', {
                'type': 'hidden',
                'name': 'estado_liquidacion',
                'value': $('#estado_liquidacion').val()
            }));
        }

        if ($('#typedocument_id').val()) {
            form.append($('<input>', {
                'type': 'hidden',
                'name': 'typedocument_id',
                'value': $('#typedocument_id').val()
            }));
        }

        // Agregar al body y enviar
        $('body').append(form);
        form.submit();
        form.remove();
    });

    // Limpiar filtros
    $('#btn-limpiar').on('click', function() {
        $('#form-buscar-terceros')[0].reset();
        $('#provider_id').val('').trigger('change');
        $('#client_id').val('').trigger('change');
        $('#estado_liquidacion').val('').trigger('change');
        $('#typedocument_id').val('').trigger('change');
        $('#tabla-resultados').hide();
        $('#btn-export-excel').hide();
        $('#excel-hint').show();
    });
});
</script>
@endsection

@section('content')
<h4 class="py-3 mb-4">
    <span class="text-muted fw-light">Reportes / </span>Ventas a Terceros
</h4>

<!-- Card de Filtros -->
<div class="mb-4 card">
    <div class="card-header">
        <h5 class="mb-0 card-title">
            <i class="ti ti-filter me-2"></i>Filtros de Búsqueda
        </h5>
    </div>
    <div class="card-body">
        <form id="form-buscar-terceros">
            @csrf
            <div class="row g-3">
                <!-- Empresa -->
                <div class="col-md-3">
                    <label for="company" class="form-label">Empresa <span class="text-danger">*</span></label>
                    <select class="form-control select2" id="company" name="company" required>
                        <option value="">Seleccionar...</option>
                        @foreach (DB::table('companies')->get() as $company)
                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Fecha Inicio -->
                <div class="col-md-2">
                    <label for="fecha_ini" class="form-label">Fecha Inicio</label>
                    <input type="date" id="fecha_ini" name="fecha_ini" class="form-control"
                           value="{{ now()->startOfMonth()->format('Y-m-d') }}">
                </div>

                <!-- Fecha Fin -->
                <div class="col-md-2">
                    <label for="fecha_fin" class="form-label">Fecha Fin</label>
                    <input type="date" id="fecha_fin" name="fecha_fin" class="form-control"
                           value="{{ now()->format('Y-m-d') }}">
                </div>

                <!-- Proveedor (Tercero) -->
                <div class="col-md-3">
                    <label for="provider_id" class="form-label">
                        <i class="ti ti-building-store me-1"></i>Proveedor (Tercero)
                    </label>
                    <select class="form-control select2" id="provider_id" name="provider_id">
                        <option value="">Todos los proveedores</option>
                    </select>
                </div>

                <!-- Cliente -->
                <div class="col-md-2">
                    <label for="client_id" class="form-label">
                        <i class="ti ti-user me-1"></i>Cliente
                    </label>
                    <select class="form-control select2" id="client_id" name="client_id">
                        <option value="">Todos los clientes</option>
                    </select>
                </div>

                <!-- Estado de Liquidación -->
                <div class="col-md-3">
                    <label for="estado_liquidacion" class="form-label">Estado de Liquidación</label>
                    <select class="form-control select2" id="estado_liquidacion" name="estado_liquidacion">
                        <option value="">Todos los estados</option>
                        <option value="pendiente">Pendiente de Liquidar</option>
                        <option value="liquidado">Liquidado</option>
                    </select>
                </div>

                <!-- Tipo de Documento -->
                <div class="col-md-3">
                    <label for="typedocument_id" class="form-label">Tipo de Documento</label>
                    <select class="form-control select2" id="typedocument_id" name="typedocument_id">
                        <option value="">Todos los tipos</option>
                        <option value="3">Crédito Fiscal</option>
                        <option value="6">Factura</option>
                    </select>
                </div>

                <!-- Botones -->
                <div class="col-md-6">
                    <label class="form-label d-block">&nbsp;</label>
                    <div class="btn-group w-100" role="group">
                        <button type="submit" id="btn-buscar" class="btn btn-primary">
                            <i class="ti ti-search me-1"></i>Buscar
                        </button>
                        <button type="button" id="btn-limpiar" class="btn btn-label-secondary">
                            <i class="ti ti-x me-1"></i>Limpiar
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Tarjetas de Resumen -->
<div id="resumen-cards" class="mb-4 row" style="display: none;">
    <div class="col-md-3">
        <div class="card border-warning">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 avatar me-3">
                        <span class="rounded avatar-initial bg-label-warning">
                            <i class="ti ti-clock ti-lg"></i>
                        </span>
                    </div>
                    <div class="flex-grow-1">
                        <small class="text-muted d-block">Pendientes de Liquidar</small>
                        <h5 class="mb-0"><span id="count-pendiente">0</span> ventas</h5>
                        <small class="text-warning fw-bold"><span id="total-pendiente">$0.00</span></small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-success">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 avatar me-3">
                        <span class="rounded avatar-initial bg-label-success">
                            <i class="ti ti-check ti-lg"></i>
                        </span>
                    </div>
                    <div class="flex-grow-1">
                        <small class="text-muted d-block">Liquidadas</small>
                        <h5 class="mb-0"><span id="count-liquidado">0</span> ventas</h5>
                        <small class="text-success fw-bold"><span id="total-liquidado">$0.00</span></small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-primary">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 avatar me-3">
                        <span class="rounded avatar-initial bg-label-primary">
                            <i class="ti ti-list-check ti-lg"></i>
                        </span>
                    </div>
                    <div class="flex-grow-1">
                        <small class="text-muted d-block">Total Ventas</small>
                        <h5 class="mb-0"><span id="count-total">0</span> ventas</h5>
                        <small class="text-primary fw-bold"><span id="total-general">$0.00</span></small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-info">
            <div class="text-center card-body">
                <button type="button" id="btn-export-excel" class="btn btn-success w-100" style="display: none;">
                    <i class="ti ti-file-spreadsheet me-1"></i>Exportar a Excel
                </button>
                <small class="mt-2 text-muted d-block" id="excel-hint" style="display: none;">Haz una búsqueda para exportar</small>
            </div>
        </div>
    </div>
</div>

<!-- Tabla de Resultados -->
<div id="tabla-resultados" class="card" style="display: none;">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="ti ti-building-store me-2"></i>Ventas a Terceros
        </h5>
        <div>
            <span class="badge bg-label-secondary me-2">
                Empresa: <span id="company-name">-</span>
            </span>
            <span class="badge bg-label-secondary me-2">
                NIT: <span id="company-nit">-</span>
            </span>
            <span class="badge bg-label-secondary">
                NRC: <span id="company-nrc">-</span>
            </span>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-bordered" id="tabla-ventas-terceros">
                <thead class="table-light">
                    <tr>
                        <th class="text-center">#</th>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Proveedor (Tercero)</th>
                        <th>Tipo Doc.</th>
                        <th>N° Control</th>
                        <th>Código Generación</th>
                        <th>Sello Recepción</th>
                        <th class="text-end">Monto</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center">CLQ</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tbody-ventas">
                    <!-- Se llena dinámicamente con JavaScript -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .select2-container {
        width: 100% !important;
    }

    .table th {
        font-weight: 600;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .table td {
        vertical-align: middle;
    }

    .avatar-initial {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
    }
</style>
@endsection
