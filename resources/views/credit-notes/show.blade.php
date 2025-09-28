@php
    $configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('vendor-style')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/sweetalert2/sweetalert2.css') }}" />
@endsection

@section('vendor-script')
    <script src="{{ asset('assets/vendor/libs/sweetalert2/sweetalert2.js') }}"></script>
@endsection

@section('title', 'Detalle Nota de Crédito')

@section('content')
    <div class="card">
        <div class="card-header border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 card-title">
                    <i class="ti ti-file-minus me-2"></i>
                    Detalle de Nota de Crédito #{{ $creditNote->dte->id_doc ?? $creditNote->id }}
                </h5>
                <div class="d-flex gap-2">
                    <a href="{{ route('credit-notes.print', $creditNote->id) }}" class="btn btn-outline-secondary" target="_blank">
                        <i class="ti ti-printer me-1"></i>
                        Imprimir
                    </a>
                    @if($creditNote->state == 1)
                        <a href="{{ route('credit-notes.edit', $creditNote->id) }}" class="btn btn-outline-primary">
                            <i class="ti ti-edit me-1"></i>
                            Editar
                        </a>
                    @endif
                    <a href="{{ route('credit-notes.index') }}" class="btn btn-primary">
                        <i class="ti ti-arrow-left me-1"></i>
                        Volver
                    </a>
                </div>
            </div>
        </div>

        <div class="card-body">
            <!-- Información general -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="ti ti-info-circle me-2"></i>
                                Información General
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-2 row">
                                <div class="col-4"><strong>Fecha:</strong></div>
                                <div class="col-8">{{ \Carbon\Carbon::parse($creditNote->date)->format('d/m/Y') }}</div>
                            </div>
                            <div class="mb-2 row">
                                <div class="col-4"><strong>Usuario:</strong></div>
                                <div class="col-8">{{ $creditNote->user->name ?? 'N/A' }}</div>
                            </div>
                            <div class="mb-2 row">
                                <div class="col-4"><strong>Empresa:</strong></div>
                                <div class="col-8">{{ $creditNote->company->name ?? 'N/A' }}</div>
                            </div>
                            <div class="mb-2 row">
                                <div class="col-4"><strong>Estado:</strong></div>
                                <div class="col-8">
                                    @switch($creditNote->state)
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
                                </div>
                            </div>
                            <div class="mb-2 row">
                                <div class="col-4"><strong>Total:</strong></div>
                                <div class="col-8 text-success fw-bold">${{ number_format($creditNote->totalamount, 2) }}</div>
                            </div>
                            @if($creditNote->motivo)
                                <div class="mb-2">
                                    <strong>Motivo:</strong>
                                    <p class="mt-1 mb-0">{{ $creditNote->motivo }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="ti ti-user me-2"></i>
                                Información del Cliente
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-2 row">
                                <div class="col-4"><strong>Nombre:</strong></div>
                                <div class="col-8">
                                    {{ $creditNote->client->tpersona == 'N'
                                        ? $creditNote->client->firstname . ' ' . $creditNote->client->firstlastname
                                        : $creditNote->client->name_contribuyente }}
                                </div>
                            </div>
                            <div class="mb-2 row">
                                <div class="col-4"><strong>NIT:</strong></div>
                                <div class="col-8">{{ $creditNote->client->nit ?? 'N/A' }}</div>
                            </div>
                            <div class="mb-2 row">
                                <div class="col-4"><strong>NCR:</strong></div>
                                <div class="col-8">{{ $creditNote->client->ncr ?? 'N/A' }}</div>
                            </div>
                            <div class="mb-2 row">
                                <div class="col-4"><strong>Email:</strong></div>
                                <div class="col-8">{{ $creditNote->client->email ?? 'N/A' }}</div>
                            </div>
                            <div class="mb-2 row">
                                <div class="col-4"><strong>Tipo:</strong></div>
                                <div class="col-8">
                                    {{ $creditNote->client->tpersona == 'N' ? 'Persona Natural' : 'Persona Jurídica' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información del DTE -->
            @if($creditNote->dte)
                <div class="mb-4 card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="ti ti-file-certificate me-2"></i>
                            Información del DTE
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-2 row">
                                    <div class="col-5"><strong>Número DTE:</strong></div>
                                    <div class="col-7">{{ $creditNote->dte->id_doc ?? 'N/A' }}</div>
                                </div>
                                <div class="mb-2 row">
                                    <div class="col-5"><strong>Tipo DTE:</strong></div>
                                    <div class="col-7">
                                        <span class="badge bg-warning">05 - Nota de Crédito</span>
                                    </div>
                                </div>
                                <div class="mb-2 row">
                                    <div class="col-5"><strong>Código Gen.:</strong></div>
                                    <div class="col-7">
                                        @if($creditNote->dte->codigoGeneracion)
                                            <small class="text-muted">{{ Str::limit($creditNote->dte->codigoGeneracion, 20) }}</small>
                                        @else
                                            N/A
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-2 row">
                                    <div class="col-6"><strong>Estado Hacienda:</strong></div>
                                    <div class="col-6">
                                        @if($creditNote->dte->estadoHacienda == 'PROCESADO')
                                            <span class="badge bg-success">Procesado</span>
                                        @elseif($creditNote->dte->estadoHacienda == 'RECHAZADO')
                                            <span class="badge bg-danger">Rechazado</span>
                                        @elseif($creditNote->dte->estadoHacienda == 'PENDIENTE')
                                            <span class="badge bg-warning">Pendiente</span>
                                        @else
                                            <span class="badge bg-secondary">{{ $creditNote->dte->estadoHacienda ?? 'N/A' }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="mb-2 row">
                                    <div class="col-6"><strong>Fecha DTE:</strong></div>
                                    <div class="col-6">
                                        {{ $creditNote->dte->fhRecibido ? \Carbon\Carbon::parse($creditNote->dte->fhRecibido)->format('d/m/Y H:i') : 'N/A' }}
                                    </div>
                                </div>
                                <div class="mb-2 row">
                                    <div class="col-6"><strong>Envíos:</strong></div>
                                    <div class="col-6">{{ $creditNote->dte->nSends ?? 0 }}</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-2 row">
                                    <div class="col-6"><strong>Ambiente:</strong></div>
                                    <div class="col-6">
                                        @php $amb = $creditNote->dte->ambiente_id ?? null; @endphp
                                        @if($amb === 1 || $amb === '1' || $amb === '00')
                                            <span class="badge bg-info">Producción</span>
                                        @elseif($amb === 2 || $amb === '2' || $amb === '01')
                                            <span class="badge bg-warning">Pruebas</span>
                                        @else
                                            <span class="badge bg-secondary">N/A</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="mb-2 row">
                                    <div class="col-6"><strong>Sello:</strong></div>
                                    <div class="col-6">
                                        @if($creditNote->dte->selloRecibido)
                                            <small class="text-muted">{{ Str::limit($creditNote->dte->selloRecibido, 15) }}</small>
                                        @else
                                            N/A
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if($creditNote->dte->descriptionMessage)
                            <hr>
                            <div class="alert alert-info">
                                <strong>Mensaje de Hacienda:</strong>
                                <p class="mb-0">{{ $creditNote->dte->descriptionMessage }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Detalle de productos -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="ti ti-package me-2"></i>
                        Detalle de Productos
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
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
                                @php
                                    $totalGravadas = 0;
                                    $totalExentas = 0;
                                    $totalNoSujetas = 0;
                                    $totalIva = 0;
                                @endphp
                                @foreach($creditNote->salesdetails as $detalle)
                                    @php
                                        $subtotal = $detalle->pricesale;
                                        $iva = $detalle->detained13;
                                        $total = $subtotal + $iva;

                                        if ($detalle->exempt > 0) {
                                            $totalExentas += $subtotal;
                                            $tipo = 'Exento';
                                            $tipoBadge = 'bg-warning';
                                        } elseif ($detalle->nosujeta > 0) {
                                            $totalNoSujetas += $subtotal;
                                            $tipo = 'No Sujeta';
                                            $tipoBadge = 'bg-info';
                                        } else {
                                            $totalGravadas += $subtotal;
                                            $tipo = 'Gravado';
                                            $tipoBadge = 'bg-success';
                                        }
                                        $totalIva += $iva;
                                    @endphp
                                    <tr>
                                        <td>{{ $detalle->product->code ?? 'N/A' }}</td>
                                        <td>{{ $detalle->product->name ?? 'N/A' }}</td>
                                        <td class="text-center">{{ number_format($detalle->amountp, 2) }}</td>
                                        <td class="text-end">${{ number_format($detalle->pricesale / $detalle->amountp, 2) }}</td>
                                        <td class="text-end">${{ number_format($subtotal, 2) }}</td>
                                        <td class="text-end">${{ number_format($iva, 2) }}</td>
                                        <td class="text-end">${{ number_format($total, 2) }}</td>
                                        <td class="text-center">
                                            <span class="badge {{ $tipoBadge }}">{{ $tipo }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="4" class="text-end">Subtotal Gravado:</th>
                                    <th class="text-end">${{ number_format($totalGravadas, 2) }}</th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                </tr>
                                <tr>
                                    <th colspan="4" class="text-end">Subtotal Exento:</th>
                                    <th class="text-end">${{ number_format($totalExentas, 2) }}</th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                </tr>
                                <tr>
                                    <th colspan="4" class="text-end">Subtotal No Sujeto:</th>
                                    <th class="text-end">${{ number_format($totalNoSujetas, 2) }}</th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                </tr>
                                <tr>
                                    <th colspan="4" class="text-end">IVA (13%):</th>
                                    <th></th>
                                    <th class="text-end">${{ number_format($totalIva, 2) }}</th>
                                    <th></th>
                                    <th></th>
                                </tr>
                                <tr class="table-success">
                                    <th colspan="4" class="text-end">TOTAL:</th>
                                    <th></th>
                                    <th></th>
                                    <th class="text-end">${{ number_format($creditNote->totalamount, 2) }}</th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-footer">
            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    Creado el {{ $creditNote->created_at->format('d/m/Y H:i') }}
                    @if($creditNote->updated_at != $creditNote->created_at)
                        | Modificado el {{ $creditNote->updated_at->format('d/m/Y H:i') }}
                    @endif
                </small>

                @if($creditNote->state == 1 && $creditNote->client->email)
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="sendEmail({{ $creditNote->id }}, '{{ $creditNote->client->email }}')">
                        <i class="ti ti-mail me-1"></i>
                        Enviar por correo
                    </button>
                @endif
            </div>
        </div>
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
                            <input type="email" name="email" id="emailInput" class="form-control" required>
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
let currentCreditNoteId = {{ $creditNote->id }};

function sendEmail(creditNoteId, defaultEmail = '') {
    currentCreditNoteId = creditNoteId;
    document.getElementById('emailInput').value = defaultEmail;
    $('#emailModal').modal('show');
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
