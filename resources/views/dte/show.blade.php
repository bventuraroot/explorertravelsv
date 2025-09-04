@extends('layouts/layoutMaster')

@section('title', 'Detalles DTE #' . $dte->id)

@section('vendor-style')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
@endsection

@section('vendor-script')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="fw-bold py-3 mb-0">
                    <i class="fas fa-file-invoice me-2"></i>
                    Detalles DTE #{{ $dte->id }}
                </h4>
                <div class="d-flex gap-2">
                    <a href="{{ route('dte.dashboard') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>
                        Volver al Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Información general -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Información General
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>ID:</strong></td>
                                    <td>{{ $dte->id }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Número Control:</strong></td>
                                    <td>{{ $dte->id_doc }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Tipo DTE:</strong></td>
                                    <td>{{ $dte->tipoDte }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Empresa:</strong></td>
                                    <td>{{ $dte->company->name ?? 'N/A' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Estado:</strong></td>
                                    <td>
                                        <span class="badge bg-{{ $dte->estado_color ?? 'secondary' }}">
                                            {{ $dte->estado_texto ?? 'Desconocido' }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Código Estado:</strong></td>
                                    <td>{{ $dte->codEstado }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Fecha Creación:</strong></td>
                                    <td>{{ $dte->created_at->format('d/m/Y H:i:s') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Última Actualización:</strong></td>
                                    <td>{{ $dte->updated_at->format('d/m/Y H:i:s') }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Información técnica -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-cogs me-2"></i>
                        Información Técnica
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Ambiente:</strong></td>
                                    <td>{{ $dte->ambiente_id }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Tipo Modelo:</strong></td>
                                    <td>{{ $dte->tipoModelo }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Tipo Transmisión:</strong></td>
                                    <td>{{ $dte->tipoTransmision }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Código Generación:</strong></td>
                                    <td>{{ $dte->codigoGeneracion }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Número de Envíos:</strong></td>
                                    <td>{{ $dte->nSends }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Código Mensaje:</strong></td>
                                    <td>{{ $dte->codeMessage }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Clase Mensaje:</strong></td>
                                    <td>{{ $dte->claMessage }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Fecha Recibido:</strong></td>
                                    <td>{{ $dte->fhRecibido ? $dte->fhRecibido->format('d/m/Y H:i:s') : 'N/A' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mensajes y descripción -->
    @if($dte->descriptionMessage || $dte->detailsMessage)
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-comment me-2"></i>
                        Mensajes del Sistema
                    </h5>
                </div>
                <div class="card-body">
                    @if($dte->descriptionMessage)
                    <div class="mb-3">
                        <strong>Descripción:</strong>
                        <p class="mt-1">{{ $dte->descriptionMessage }}</p>
                    </div>
                    @endif

                    @if($dte->detailsMessage)
                    <div class="mb-3">
                        <strong>Detalles:</strong>
                        <p class="mt-1">{{ $dte->detailsMessage }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Errores asociados -->
    @if($dte->errors->count() > 0)
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Errores Asociados
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Tipo Error</th>
                                    <th>Código</th>
                                    <th>Mensaje</th>
                                    <th>Fecha</th>
                                    <th>Resuelto</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($dte->errors as $error)
                                <tr>
                                    <td>
                                        <span class="badge bg-{{ $error->tipo_error === 'CRITICO' ? 'danger' : 'warning' }}">
                                            {{ $error->tipo_error }}
                                        </span>
                                    </td>
                                    <td>{{ $error->codigo_error }}</td>
                                    <td>{{ $error->mensaje_error }}</td>
                                    <td>{{ $error->created_at->format('d/m/Y H:i:s') }}</td>
                                    <td>
                                        <span class="badge bg-{{ $error->resuelto ? 'success' : 'danger' }}">
                                            {{ $error->resuelto ? 'Sí' : 'No' }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- JSON del documento -->
    @if($dte->json)
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-code me-2"></i>
                        JSON del Documento
                    </h5>
                </div>
                <div class="card-body">
                    <pre class="bg-light p-3 rounded"><code>{{ json_encode($dte->json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
