@php
    $configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('vendor-style')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/flatpickr/flatpickr.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/bootstrap-datepicker/bootstrap-datepicker.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/sweetalert2/sweetalert2.css') }}" />
@endsection

@section('vendor-script')
    <script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/select2/select2.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/flatpickr/flatpickr.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/bootstrap-datepicker/bootstrap-datepicker.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/sweetalert2/sweetalert2.js') }}"></script>
@endsection

@section('page-script')
    <script src="{{ asset('assets/js/app-credit-notes-list.js') }}"></script>
@endsection

@section('title', 'Notas de Crédito')

@section('content')
    <div class="card">
        <div class="card-header border-bottom">
            <h5 class="mb-3 card-title">
                <i class="ti ti-file-minus me-2"></i>
                Notas de Crédito
            </h5>
            <div class="gap-3 pb-2 d-flex justify-content-between align-items-center row gap-md-0">
                <div class="col-md-4 companies"></div>
                <div class="col-md-8 text-end">
                    <a href="{{ route('credit-notes.create') }}" class="btn btn-primary">
                        <i class="ti ti-plus me-1"></i>
                        Nueva Nota de Crédito
                    </a>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="card-body">
            <form method="GET" action="{{ route('credit-notes.index') }}" class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label">Fecha Desde</label>
                    <input type="date" name="fecha_desde" class="form-control" value="{{ request('fecha_desde') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Fecha Hasta</label>
                    <input type="date" name="fecha_hasta" class="form-control" value="{{ request('fecha_hasta') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Cliente</label>
                    <select name="cliente_id" class="form-select">
                        <option value="">Todos los clientes</option>
                        @foreach($clientes as $cliente)
                            <option value="{{ $cliente->id }}" {{ request('cliente_id') == $cliente->id ? 'selected' : '' }}>
                                {{ $cliente->tpersona == 'N' ? $cliente->firstname . ' ' . $cliente->firstlastname : $cliente->name_contribuyente }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Empresa</label>
                    <select name="empresa_id" class="form-select">
                        <option value="">Todas las empresas</option>
                        @foreach($empresas as $empresa)
                            <option value="{{ $empresa->id }}" {{ request('empresa_id') == $empresa->id ? 'selected' : '' }}>
                                {{ $empresa->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-search me-1"></i>
                        Filtrar
                    </button>
                    <a href="{{ route('credit-notes.index') }}" class="btn btn-outline-secondary">
                        <i class="ti ti-x me-1"></i>
                        Limpiar
                    </a>
                </div>
            </form>
        </div>

        <div class="card-datatable table-responsive">
            <table class="table datatables-credit-notes border-top nowrap">
                <thead>
                    <tr>
                        <th>Ver</th>
                        <th>CORRELATIVO</th>
                        <th>FECHA</th>
                        <th>CLIENTE</th>
                        <th>EMPRESA</th>
                        <th>TOTAL</th>
                        <th>ESTADO</th>
                        <th>MOTIVO</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($notasCredito as $nota)
                        <tr>
                            <td></td>
                            <td>{{ $nota->dte->id_doc ?? $nota->id }}</td>
                            <td>{{ \Carbon\Carbon::parse($nota->date)->format('d/m/Y') }}</td>
                            <td>
                                @switch($nota->client->tpersona)
                                    @case('N')
                                        {{ $nota->client->firstname . ' ' . $nota->client->firstlastname }}
                                        @break
                                    @case('J')
                                        {{ substr($nota->client->name_contribuyente, 0, 30) }}
                                        @break
                                    @default
                                        {{ $nota->client->name_contribuyente }}
                                @endswitch
                            </td>
                            <td>{{ $nota->company->name }}</td>
                            <td>$ {{ number_format($nota->totalamount, 2, '.', ',') }}</td>
                            <td>
                                @switch($nota->state)
                                    @case(0)
                                        <span class="badge bg-danger">ANULADO</span>
                                        @break
                                    @case(1)
                                        <span class="badge bg-success">ACTIVO</span>
                                        @break
                                    @case(2)
                                        <span class="badge bg-warning">PENDIENTE</span>
                                        @break
                                    @default
                                        <span class="badge bg-secondary">DESCONOCIDO</span>
                                @endswitch
                            </td>
                            <td>{{ Str::limit($nota->motivo ?? 'N/A', 30) }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="{{ route('credit-notes.show', $nota->id) }}"
                                        class="btn btn-icon btn-outline-info btn-sm me-1" title="Ver">
                                        <i class="ti ti-eye"></i>
                                    </a>
                                    <a href="{{ route('credit-notes.print', $nota->id) }}"
                                        class="btn btn-icon btn-outline-secondary btn-sm me-1" target="_blank" title="Imprimir">
                                        <i class="ti ti-printer"></i>
                                    </a>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                                            <i class="ti ti-dots-vertical"></i>
                                        </button>
                                        <div class="dropdown-menu">
                                            @if($nota->state == 1)
                                                <a href="{{ route('credit-notes.edit', $nota->id) }}" class="dropdown-item">
                                                    <i class="ti ti-edit me-2"></i>Editar
                                                </a>
                                                <a href="javascript:void(0)" onclick="sendEmail({{ $nota->id }})" class="dropdown-item">
                                                    <i class="ti ti-mail me-2"></i>Enviar por correo
                                                </a>
                                                <div class="dropdown-divider"></div>
                                                <a href="javascript:void(0)" onclick="deleteCreditNote({{ $nota->id }})" class="dropdown-item text-danger">
                                                    <i class="ti ti-trash me-2"></i>Eliminar
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted">No hay notas de crédito registradas</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        @if($notasCredito->hasPages())
            <div class="card-footer">
                {{ $notasCredito->appends(request()->query())->links() }}
            </div>
        @endif
    </div>

    <!-- Modal para envío de correo -->
    <div class="modal fade" id="emailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Enviar Nota de Crédito por Correo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="emailForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Correo electrónico</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mensaje (opcional)</label>
                            <textarea name="mensaje" class="form-control" rows="3" placeholder="Mensaje personalizado..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Enviar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
let currentCreditNoteId = null;

function sendEmail(creditNoteId) {
    currentCreditNoteId = creditNoteId;
    $('#emailModal').modal('show');
}

function deleteCreditNote(creditNoteId) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: "Esta acción no se puede deshacer",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Crear un formulario temporal para enviar la petición DELETE
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/credit-notes/destroy/${creditNoteId}`;
            form.style.display = 'none';

            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            const methodField = document.createElement('input');
            methodField.type = 'hidden';
            methodField.name = '_method';
            methodField.value = 'DELETE';

            form.appendChild(csrfToken);
            form.appendChild(methodField);
            document.body.appendChild(form);
            form.submit();
        }
    });
}

$('#emailForm').on('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    fetch(`/credit-notes/send-email/${currentCreditNoteId}`, {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire('Enviado', data.message, 'success');
            $('#emailModal').modal('hide');
            $('#emailForm')[0].reset();
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    })
    .catch(error => {
        Swal.fire('Error', 'Ocurrió un error al enviar el correo', 'error');
    });
});
</script>
@endpush
