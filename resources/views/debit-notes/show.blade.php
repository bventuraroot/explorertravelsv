@php
    $configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Detalle de Nota de Débito')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 card-title">
                            <i class="ti ti-file-text me-2"></i>
                            Nota de Débito {{ $debitNote->dte->id_doc ?? '#' . $debitNote->id }}
                        </h5>
                        <div>
                            <a href="{{ route('debit-notes.print', $debitNote->id) }}" class="btn btn-outline-secondary" target="_blank">
                                <i class="ti ti-printer me-1"></i>
                                Imprimir
                            </a>
                            @if($debitNote->state == 1)
                                <a href="{{ route('debit-notes.edit', $debitNote->id) }}" class="btn btn-outline-primary">
                                    <i class="ti ti-edit me-1"></i>
                                    Editar
                                </a>
                            @endif
                            <a href="{{ route('debit-notes.index') }}" class="btn btn-primary">
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
                            <h6 class="text-primary mb-3">Información General</h6>
                            <div class="row mb-2">
                                <div class="col-4"><strong>Fecha:</strong></div>
                                <div class="col-8">{{ \Carbon\Carbon::parse($debitNote->date)->format('d/m/Y') }}</div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-4"><strong>Total:</strong></div>
                                <div class="col-8 text-success fw-bold">${{ number_format($debitNote->totalamount, 2) }}</div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-4"><strong>Estado:</strong></div>
                                <div class="col-8">
                                    @switch($debitNote->state)
                                        @case(0)
                                            <span class="badge bg-danger">Anulado</span>
                                            @break
                                        @case(1)
                                            <span class="badge bg-success">Activo</span>
                                            @break
                                        @case(2)
                                            <span class="badge bg-warning">Pendiente</span>
                                            @break
                                        @default
                                            <span class="badge bg-secondary">Desconocido</span>
                                    @endswitch
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-4"><strong>Usuario:</strong></div>
                                <div class="col-8">{{ $debitNote->user->name ?? 'N/A' }}</div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-4"><strong>Empresa:</strong></div>
                                <div class="col-8">{{ $debitNote->company->name ?? 'N/A' }}</div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">Cliente</h6>
                            <div class="row mb-2">
                                <div class="col-4"><strong>Nombre:</strong></div>
                                <div class="col-8">
                                    {{ $debitNote->client->tpersona == 'N' ? 
                                        $debitNote->client->firstname . ' ' . $debitNote->client->firstlastname : 
                                        $debitNote->client->nameClient }}
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-4"><strong>NIT:</strong></div>
                                <div class="col-8">{{ $debitNote->client->nit ?? 'N/A' }}</div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-4"><strong>NCR:</strong></div>
                                <div class="col-8">{{ $debitNote->client->ncr ?? 'N/A' }}</div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-4"><strong>Email:</strong></div>
                                <div class="col-8">{{ $debitNote->client->email ?? 'N/A' }}</div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-4"><strong>Teléfono:</strong></div>
                                <div class="col-8">{{ $debitNote->client->phone ?? 'N/A' }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Información del DTE -->
                    @if($debitNote->dte)
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="mb-0 text-primary">
                                    <i class="ti ti-file-check me-2"></i>
                                    Información del DTE
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="row mb-2">
                                            <div class="col-5"><strong>Número DTE:</strong></div>
                                            <div class="col-7">{{ $debitNote->dte->id_doc ?? 'N/A' }}</div>
                                        </div>
                                        <div class="row mb-2">
                                            <div class="col-5"><strong>Tipo DTE:</strong></div>
                                            <div class="col-7">
                                                <span class="badge bg-warning">06 - Nota de Débito</span>
                                            </div>
                                        </div>
                                        <div class="row mb-2">
                                            <div class="col-5"><strong>Código Generación:</strong></div>
                                            <div class="col-7">
                                                <small class="text-muted">{{ Str::limit($debitNote->dte->codigoGeneracion, 20) ?? 'N/A' }}</small>
                                            </div>
                                        </div>
                                        <div class="row mb-2">
                                            <div class="col-5"><strong>Sello Recibido:</strong></div>
                                            <div class="col-7">
                                                <small class="text-muted">{{ Str::limit($debitNote->dte->selloRecibido, 20) ?? 'N/A' }}</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="row mb-2">
                                            <div class="col-5"><strong>Estado Hacienda:</strong></div>
                                            <div class="col-7">
                                                @if($debitNote->dte->estadoHacienda == 'PROCESADO')
                                                    <span class="badge bg-success">Procesado</span>
                                                @elseif($debitNote->dte->estadoHacienda == 'RECHAZADO')
                                                    <span class="badge bg-danger">Rechazado</span>
                                                @elseif($debitNote->dte->estadoHacienda == 'PENDIENTE')
                                                    <span class="badge bg-warning">Pendiente</span>
                                                @else
                                                    <span class="badge bg-secondary">{{ $debitNote->dte->estadoHacienda ?? 'N/A' }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="row mb-2">
                                            <div class="col-5"><strong>Fecha Procesamiento:</strong></div>
                                            <div class="col-7">
                                                {{ $debitNote->dte->fhRecibido ? \Carbon\Carbon::parse($debitNote->dte->fhRecibido)->format('d/m/Y H:i:s') : 'N/A' }}
                                            </div>
                                        </div>
                                        <div class="row mb-2">
                                            <div class="col-5"><strong>Ambiente:</strong></div>
                                            <div class="col-7">
                                                @php $amb = $debitNote->dte->ambiente_id ?? null; @endphp
                                                @if($amb === 1 || $amb === '1' || $amb === '00')
                                                    <span class="badge bg-info">Producción</span>
                                                @elseif($amb === 2 || $amb === '2' || $amb === '01')
                                                    <span class="badge bg-warning">Pruebas</span>
                                                @else
                                                    <span class="badge bg-secondary">N/A</span>
                                                @endif
                                            </div>
                                        </div>
                                        @if($debitNote->dte->descriptionMessage)
                                            <div class="row mb-2">
                                                <div class="col-5"><strong>Mensaje:</strong></div>
                                                <div class="col-7">
                                                    <small class="text-muted">{{ $debitNote->dte->descriptionMessage }}</small>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Motivo -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0 text-primary">
                                <i class="ti ti-message me-2"></i>
                                Motivo de la Nota de Débito
                            </h6>
                        </div>
                        <div class="card-body">
                            <p class="mb-0">{{ $debitNote->motivo ?? 'Sin motivo especificado' }}</p>
                        </div>
                    </div>

                    <!-- Detalles de productos -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0 text-primary">
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
                                            <th class="text-center">Cantidad</th>
                                            <th class="text-end">Precio Unit.</th>
                                            <th class="text-center">Tipo</th>
                                            <th class="text-end">IVA</th>
                                            <th class="text-end">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $subtotalGravado = 0;
                                            $subtotalExento = 0;
                                            $subtotalNoSujeto = 0;
                                            $totalIva = 0;
                                        @endphp
                                        @foreach($debitNote->salesdetails as $detalle)
                                            @php
                                                $subtotal = $detalle->amountp * $detalle->pricesale;
                                                $iva = $detalle->detained13;
                                                
                                                if ($detalle->exempt > 0) {
                                                    $subtotalExento += $subtotal;
                                                } elseif ($detalle->nosujeta > 0) {
                                                    $subtotalNoSujeto += $subtotal;
                                                } else {
                                                    $subtotalGravado += $subtotal;
                                                }
                                                $totalIva += $iva;
                                            @endphp
                                            <tr>
                                                <td>{{ $detalle->product->code ?? 'N/A' }}</td>
                                                <td>{{ $detalle->product->name ?? 'N/A' }}</td>
                                                <td class="text-center">{{ number_format($detalle->amountp, 2) }}</td>
                                                <td class="text-end">${{ number_format($detalle->pricesale, 2) }}</td>
                                                <td class="text-center">
                                                    @if($detalle->exempt > 0)
                                                        <span class="badge bg-warning">Exento</span>
                                                    @elseif($detalle->nosujeta > 0)
                                                        <span class="badge bg-info">No Sujeta</span>
                                                    @else
                                                        <span class="badge bg-success">Gravado</span>
                                                    @endif
                                                </td>
                                                <td class="text-end">${{ number_format($iva, 2) }}</td>
                                                <td class="text-end">${{ number_format($subtotal, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="table-light">
                                        <tr>
                                            <th colspan="4" class="text-end">Subtotal Gravado:</th>
                                            <th class="text-end">${{ number_format($subtotalGravado, 2) }}</th>
                                            <th></th>
                                            <th></th>
                                        </tr>
                                        <tr>
                                            <th colspan="4" class="text-end">Subtotal Exento:</th>
                                            <th class="text-end">${{ number_format($subtotalExento, 2) }}</th>
                                            <th></th>
                                            <th></th>
                                        </tr>
                                        <tr>
                                            <th colspan="4" class="text-end">Subtotal No Sujeto:</th>
                                            <th class="text-end">${{ number_format($subtotalNoSujeto, 2) }}</th>
                                            <th></th>
                                            <th></th>
                                        </tr>
                                        <tr>
                                            <th colspan="4" class="text-end">IVA (13%):</th>
                                            <th class="text-end">${{ number_format($totalIva, 2) }}</th>
                                            <th></th>
                                            <th></th>
                                        </tr>
                                        <tr class="table-primary">
                                            <th colspan="4" class="text-end">TOTAL:</th>
                                            <th class="text-end">${{ number_format($debitNote->totalamount, 2) }}</th>
                                            <th></th>
                                            <th></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

