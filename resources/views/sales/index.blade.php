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
    <script src="{{ asset('assets/js/app-sale-list.js') }}"></script>
@endsection

@section('title', 'Ventas')

@section('page-style')
<style>
    /* Estilos específicos para la tabla de borradores */
    .draft-table {
        border-collapse: separate !important;
    }
    .draft-table thead th {
        position: relative;
    }
    /* Prevenir que DataTables afecte esta tabla */
    .draft-table_wrapper {
        display: none !important;
    }

    /* Protección adicional contra DataTables */
    #draft-invoices-table_wrapper {
        display: none !important;
    }

    /* Asegurar que la tabla de borradores no reciba estilos de DataTables */
    .draft-table .sorting,
    .draft-table .sorting_asc,
    .draft-table .sorting_desc {
        background-image: none !important;
        cursor: default !important;
    }

    /* Prevenir que DataTables procese tablas marcadas como excluidas */
    [data-exclude-datatables="true"] {
        pointer-events: auto !important;
    }

    /* Forzar separación de contextos entre tablas */
    .draft-table table,
    #draft-invoices-table {
        isolation: isolate !important;
    }

    /* Prevenir que DataTables añada clases automáticamente a tablas de borradores */
    .draft-table.dataTable,
    #draft-invoices-table.dataTable {
        display: table !important;
    }

    /* Ocultar cualquier wrapper de DataTables que se pueda generar para borradores */
    .draft-table .dataTables_wrapper,
    #draft-invoices-table_wrapper,
    .draft-table_wrapper {
        display: none !important;
    }

    /* Estilos para el scroll horizontal de la tabla principal */
    .card-datatable .table-responsive {
        border: 0px solid #e7eaf3;
        border-radius: 0.375rem;
        overflow-x: auto !important;
    }

    .datatables-sale {
        margin-bottom: 0;
        width: 100% !important;
        table-layout: fixed;
    }

    .datatables-sale th,
    .datatables-sale td {
        white-space: nowrap;
        padding: 0.75rem 1rem;
        vertical-align: middle;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Asegurar que las columnas tengan un ancho fijo */
    .datatables-sale th:nth-child(1),
    .datatables-sale td:nth-child(1) { width: 150px; } /* Acciones */
    .datatables-sale th:nth-child(2),
    .datatables-sale td:nth-child(2) { width: 250px; } /* Correlativo */
    .datatables-sale th:nth-child(3),
    .datatables-sale td:nth-child(3) { width: 120px; } /* Fecha */
    .datatables-sale th:nth-child(4),
    .datatables-sale td:nth-child(4) { width: 150px; } /* Tipo */
    .datatables-sale th:nth-child(5),
    .datatables-sale td:nth-child(5) { width: 90px; } /* Estado */
    .datatables-sale th:nth-child(6),
    .datatables-sale td:nth-child(6) { width: 120px; } /* Cliente */
    .datatables-sale th:nth-child(7),
    .datatables-sale td:nth-child(7) { width: 120px; } /* Total */
    .datatables-sale th:nth-child(8),
    .datatables-sale td:nth-child(8) { width: 90px; } /* Forma de Pago */

    /* Forzar que DataTables no oculte columnas */
    .datatables-sale .dtr-hidden {
        display: table-cell !important;
    }

    /* Asegurar que el wrapper de DataTables no limite el ancho */
    .dataTables_wrapper {
        overflow-x: auto !important;
    }

    /* Asegurar que los dropdowns funcionen correctamente */
    .dropdown-menu {
        z-index: 9999 !important;
        position: absolute !important;
        top: 100% !important;
        left: 0 !important;
        min-width: 160px !important;
        padding: 0.5rem 0 !important;
        margin: 0.125rem 0 0 !important;
        background-color: #fff !important;
        border: 1px solid rgba(0,0,0,.15) !important;
        border-radius: 0.375rem !important;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.175) !important;
    }

    /* Asegurar que el dropdown se muestre sobre DataTables */
    .datatables-sale .dropdown-menu {
        z-index: 9999 !important;
        position: absolute !important;
    }

    /* Asegurar que el contenedor del dropdown tenga posición relativa */
    .datatables-sale .btn-group {
        position: relative !important;
    }

    .btn-group .dropdown-toggle::after {
        display: inline-block;
        margin-left: 0.255em;
        vertical-align: 0.255em;
        content: "";
        border-top: 0.3em solid;
        border-right: 0.3em solid transparent;
        border-bottom: 0;
        border-left: 0.3em solid transparent;
    }

    /* Asegurar que los botones del dropdown sean clickeables */
    .dropdown-item {
        cursor: pointer;
        display: block;
        width: 100%;
        padding: 0.25rem 1rem;
        clear: both;
        font-weight: 400;
        color: #212529;
        text-align: inherit;
        text-decoration: none;
        white-space: nowrap;
        background-color: transparent;
        border: 0;
    }

    .dropdown-item:hover {
        color: #1e2125;
        background-color: #e9ecef;
    }

    /* Asegurar que las celdas de la tabla permitan overflow visible para dropdowns */
    .datatables-sale td {
        overflow: visible !important;
        position: relative !important;
    }

    /* Asegurar que el wrapper de DataTables no corte los dropdowns */
    .dataTables_wrapper {
        overflow: visible !important;
    }

    .table-responsive {
        overflow: visible !important;
    }
</style>
@endsection

@section('content')
    <div class="card">
        <div class="card-header border-bottom">
            <h5 class="mb-3 card-title">
                <i class="ti ti-receipt me-2"></i>
                Ventas
            </h5>
            <div class="gap-3 pb-2 d-flex justify-content-between align-items-center row gap-md-0">
                <div class="col-md-4 companies"></div>
                <div class="col-md-8 text-end">
                    <button type="button" class="btn btn-outline-warning me-2" onclick="loadDraftInvoices()">
                        <i class="ti ti-file-invoice me-1"></i>
                        Borradores de Factura
                        <span class="badge bg-warning ms-1" id="draft-count">0</span>
                    </button>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#selectDocumentModal">
                        <i class="ti ti-plus me-1"></i>
                        Nueva Venta
                    </button>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="card-body">
            <form method="GET" action="{{ route('sale.index') }}" class="mb-4 row g-3">
                <div class="col-md-2">
                    <label class="form-label">Fecha Desde</label>
                    <input type="date" name="fecha_desde" class="form-control" value="{{ request('fecha_desde') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Fecha Hasta</label>
                    <input type="date" name="fecha_hasta" class="form-control" value="{{ request('fecha_hasta') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tipo Documento</label>
                    <select name="tipo_documento" class="form-select">
                        <option value="">Todos los tipos</option>
                        @foreach($tiposDocumento as $tipo)
                            <option value="{{ $tipo->id }}" {{ request('tipo_documento') == $tipo->id ? 'selected' : '' }}>
                                {{ $tipo->description }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Correlativo</label>
                    <input type="text" name="correlativo" class="form-control" placeholder="ID o DTE" value="{{ request('correlativo') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Cliente</label>
                    <select name="cliente_id" class="form-select">
                        <option value="">Todos los clientes</option>
                        @foreach($clientes as $cliente)
                            <option value="{{ $cliente->id }}" {{ request('cliente_id') == $cliente->id ? 'selected' : '' }}>
                                @if($cliente->tpersona == 'N')
                                    {{ $cliente->firstname . ' ' . $cliente->firstlastname }}
                                @else
                                    {{ $cliente->name_contribuyente ?: $cliente->comercial_name }}
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-search me-1"></i>
                        Filtrar
                    </button>
                    <a href="{{ route('sale.index') }}" class="btn btn-outline-secondary">
                        <i class="ti ti-x me-1"></i>
                        Limpiar
                    </a>
                </div>
            </form>
        </div>

        <!-- Sección de Borradores de Factura Pendientes -->
        <div class="card-body" id="draft-invoices-section" style="display: none;">
            <div class="alert alert-info">
                <h6 class="alert-heading">
                    <i class="ti ti-info-circle me-2"></i>
                    Borradores de Factura Pendientes (desde Preventas)
                </h6>
                <p class="mb-0">Estos son borradores de factura creados desde el módulo de preventas que están listos para ser completados.</p>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-hover draft-table" id="draft-invoices-table" data-exclude-datatables="true">
                    <thead class="table-light">
                        <tr>
                            <th>Ver</th>
                            <th>ID</th>
                            <th>Cliente</th>
                            <th>Empresa</th>
                            <th>Tipo Doc.</th>
                            <th>Total</th>
                            <th>Fecha</th>
                            <th>Usuario</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="draft-invoices-body">
                        <tr>
                            <td colspan="9" class="text-center text-muted">
                                <i class="ti ti-loader fs-1"></i>
                                <br>
                                Cargando borradores...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card-datatable">
            <div class="table-responsive" style="overflow-x: auto; max-width: 100%;">
                <table class="table datatables-sale border-top" style="width: 1130px;">
                <thead>
                    <tr>
                        <th>Acciones</th>
                        <th>CORRELATIVO</th>
                        <th>FECHA</th>
                        <th>TIPO</th>
                        <th>ESTADO</th>
                        <th>CLIENTE</th>
                        <th>TOTAL</th>
                        <th>FORMA DE PAGO</th>
                    </tr>
                </thead>
                <tbody>
                    @isset($sales)
                        @forelse($sales as $sale)
                            <tr>
                                <td>
                                    @switch($sale->typesale)
                                        @case(1)
                                        <div class="d-flex align-items-center">
                                            <a href="{{route('sale.print', $sale->id)}}"
                                                    class="btn btn-icon btn-outline-secondary btn-sm me-1" target="_blank" title="Imprimir">
                                                <i class="ti ti-printer"></i>
                                            </a>
                                            <a href="#"
                                                    onclick="EnviarCorreo({{$sale->id}} ,'{{ $sale->mailClient}}','{{$sale->id_doc }}')"
                                                    class="btn btn-icon btn-outline-success btn-sm me-1" title="Enviar por correo">
                                                <i class="ti ti-mail"></i>
                                            </a>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                                                    <i class="ti ti-dots-vertical"></i>
                                                </button>
                                                <div class="dropdown-menu">
                                                    @if ($sale->state != 0)
                                                    <a href="javascript:cancelsale({{ $sale->id }});" class="dropdown-item">
                                                        <i class="ti ti-x me-2"></i>Anular
                                                    </a>
                                                    @endif
                                                    @if ($sale->tipoDte=="03"  && $sale->estadoHacienda=='PROCESADO' && $sale->tipoDte!="05" && $sale->relatedSale=="")
                                                    <a href="{{ route('credit-notes.create', ['sale_id' => $sale->id]) }}" class="dropdown-item">
                                                        <i class="ti ti-file-minus me-2"></i>Crear Nota de Crédito
                                                    </a>
                                                    <a href="{{ route('debit-notes.create', ['sale_id' => $sale->id]) }}" class="dropdown-item">
                                                        <i class="ti ti-file-plus me-2"></i>Crear Nota de Débito
                                                    </a>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        @break

                                        @case(2)
                                        <div class="d-flex align-items-center">
                                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="retomarsale({{ $sale->id }}, {{ $sale->typedocument_id}})">
                                                <i class="ti ti-pencil me-1"></i>Retomar Borrador
                                            </button>
                                            @if ($sale->state != 0)
                                            <div class="btn-group ms-1">
                                                <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                                                    <i class="ti ti-dots-vertical"></i>
                                                </button>
                                                <div class="dropdown-menu">
                                                    <a href="javascript:cancelsale({{ $sale->id }});" class="dropdown-item">
                                                        <i class="ti ti-x me-2"></i>Anular
                                                    </a>
                                                </div>
                                            </div>
                                            @endif
                                        </div>
                                        @break
                                        @case(0)
                                        <div class="d-flex align-items-center">
                                            <span class="text-muted">Sin acciones</span>
                                        </div>
                                        @break

                                        @default
                                        <div class="d-flex align-items-center">
                                            @if ($sale->state != 0)
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                                                    <i class="ti ti-dots-vertical"></i>
                                                </button>
                                                <div class="dropdown-menu">
                                                    <a href="javascript:cancelsale({{ $sale->id }});" class="dropdown-item">
                                                        <i class="ti ti-x me-2"></i>Anular
                                                    </a>
                                                </div>
                                            </div>
                                            @else
                                            <span class="text-muted">Sin acciones</span>
                                            @endif
                                        </div>
                                    @endswitch
                                </td>
                                @if ($sale->estadoHacienda=='PROCESADO')
                                <td style="color: green; font-weight: bold; font-size: 0.8rem;">{{ $sale->id_doc }}</td>
                                @else
                                <td>{{ $sale->id }}</td>
                                @endif

                                <td>{{ \Carbon\Carbon::parse($sale->date)->format('d/m/Y') }}</td>
                                <td>{{ $sale->document_name }}</td>
                                <td>
                                    @switch($sale->state)
                                        @case(0)
                                            <span class="badge bg-danger">ANULADO</span>
                                        @break

                                        @case(1)
                                            <span class="badge bg-success">CONFIRMADO</span>
                                        @break

                                        @case(2)
                                            <span class="badge bg-warning">PENDIENTE</span>
                                        @break

                                        @case(3)
                                            <span class="badge bg-info">FACTURADO</span>
                                        @break

                                        @default
                                    @endswitch
                                </td>
                                <td>
                                    @switch($sale->tpersona)
                                        @case('N')
                                    {{$sale->firstname . ' ' . $sale->firstlastname}}
                                            @break
                                        @case('J')
                                    {{substr($sale->nameClient,0,30)}}
                                        @break

                                        @default

                                    @endswitch
                                </td>
                                <td>$ {{ number_format($sale->totalamount, 2, '.', ',') }}</td>

                                <td>
                                    @switch($sale->waytopay)
                                        @case(1)
                                            <span class="badge bg-primary">CONTADO</span>
                                        @break

                                        @case(2)
                                            <span class="badge bg-secondary">CRÉDITO</span>
                                        @break

                                        @case(3)
                                            <span class="badge bg-info">OTRO</span>
                                        @break

                                        @default
                                    @endswitch
                                </td>

                            </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted">No hay ventas registradas</td>
                                </tr>
                            @endforelse
                        @endisset
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal para seleccionar tipo de documento -->
    <div class="modal fade" id="selectDocumentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-simple modal-pricing">
          <div class="p-3 modal-content p-md-5">
            <button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="Close"></button>
            <div class="modal-body">
              <div class="mb-4 text-center">
                <h3 class="mb-2">
                    <i class="ti ti-file-text me-2"></i>
                    Documentos disponibles
                </h3>
                <p class="text-muted">Seleccione el tipo de documento que desea crear</p>
              </div>
              <form id="selectDocumentForm" class="row" action="{{Route('sale.create')}}" method="GET">
                @csrf @method('GET')
                <input type="hidden" name="iduser" id="iduser" value="{{Auth::user()->id}}">
                <div id="wizard-create-deal" class="mt-2 bs-stepper vertical">
                    <div class="bs-stepper-content">
                        <!-- Deal Type -->
                        <div id="deal-type" class="content">
                          <div class="row g-3">
                            <div class="pt-4 border rounded col-12 d-flex justify-content-center">
                              <img src="{{ asset('assets/img/illustrations/auth-register-illustration-'.$configData['style'].'.png') }}" alt="wizard-create-deal" data-app-light-img="illustrations/auth-register-illustration-light.png" data-app-dark-img="illustrations/auth-register-illustration-dark.png" width="250" class="img-fluid">
                            </div>
                            <div class="pb-2 col-12">
                              <div class="row">
                                <div class="mb-2 col-md mb-md-0">
                                  <div class="form-check custom-option custom-option-icon">
                                    <label class="form-check-label custom-option-content" for="factura">
                                      <span class="custom-option-body">
                                        <i class="mb-2 ti ti-receipt-2"></i>
                                        <span class="custom-option-title">FACTURA CONSUMIDOR FINAL</span>
                                        <small>Creación de factura para personas naturales contribuyentes o no contribuyentes</small>
                                      </span>
                                      <input name="typedocument" class="form-check-input" type="radio" value="6" id="factura" checked />
                                    </label>
                                  </div>
                                </div>
                                <div class="mb-2 col-md mb-md-0">
                                  <div class="form-check custom-option custom-option-icon">
                                    <label class="form-check-label custom-option-content" for="fiscal">
                                      <span class="custom-option-body">
                                        <i class="mb-2 ti ti-receipt"></i>
                                        <span class="custom-option-title">COMPROBANTE DE CREDITO FISCAL</span>
                                        <small>Creación de documentos donde necesitas una persona natural o jurídica que declare IVA</small>
                                      </span>
                                      <input name="typedocument" class="form-check-input" type="radio" value="3" id="fiscal" />
                                    </label>
                                  </div>
                                </div>
                                <div class="mb-2 col-md mb-md-0">
                                  <div class="form-check custom-option custom-option-icon">
                                    <label class="form-check-label custom-option-content" for="nota">
                                      <span class="custom-option-body">
                                        <i class="mb-2 ti ti-receipt-refund"></i>
                                        <span class="custom-option-title">FACTURAS DE SUJETO EXCLUIDO</span>
                                        <small>Creación de documento para que el impuesto no es aplicable a la operación que se realiza.</small>
                                      </span>
                                      <input name="typedocument" class="form-check-input" type="radio" value="8" id="nota" />
                                    </label>
                                  </div>
                                </div>
                                <div class="mt-4 col-12 d-flex justify-content-center">
                                    <button class="btn btn-success btn-submit btn-next">
                                        <span class="align-center d-sm-inline-block d-none me-sm-1">Comenzar</span>
                                        <i class="ti ti-arrow-right ti-xs"></i>
                                    </button>
                                </div>
                              </div>
                            </div>
                    </div>
                  </div>
              </form>
            </div>
          </div>
        </div>
      </div>
@endsection
