@extends('layouts/layoutMaster')

@section('title', 'Detalles del Error DTE')

@section('vendor-style')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
@endsection

@section('vendor-script')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="row">
        <div class="col-12">
            <div class="mb-4 d-flex justify-content-between align-items-center">
                <h4 class="py-3 mb-0 fw-bold">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Detalles del Error DTE #{{ $error->id }}
                </h4>
                <div class="gap-2 d-flex">
                    @if(!$error->resuelto)
                        <button type="button" class="btn btn-success" onclick="resolverError({{ $error->id }})">
                            <i class="fas fa-check me-1"></i>
                            Resolver Error
                        </button>
                    @endif
                    <a href="{{ route('dte.errores') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>
                        Volver a Errores
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Información principal del error -->
    <div class="mb-4 row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 card-title">
                        <i class="fas fa-info-circle me-2"></i>
                        Información del Error
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>ID Error:</strong></td>
                                    <td>{{ $error->id }}</td>
                                </tr>
                                <tr>
                                    <td><strong>DTE ID:</strong></td>
                                    <td>
                                        <a href="{{ route('dte.show', $error->dte_id) }}" class="text-primary">
                                            {{ $error->dte_id }}
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Tipo de Error:</strong></td>
                                    <td>
                                        @php
                                            $badgeClass = match($error->tipo_error) {
                                                'hacienda' => 'bg-warning',
                                                'sistema' => 'bg-secondary',
                                                'validacion' => 'bg-info',
                                                'autenticacion', 'firma' => 'bg-danger',
                                                'network' => 'bg-primary',
                                                'datos' => 'bg-dark',
                                                default => 'bg-secondary'
                                            };
                                        @endphp
                                        <span class="badge {{ $badgeClass }}">
                                            {{ ucfirst($error->tipo_error) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Código de Error:</strong></td>
                                    <td><code>{{ $error->codigo_error }}</code></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Estado:</strong></td>
                                    <td>
                                        @if($error->resuelto)
                                            <span class="badge bg-success">
                                                <i class="fas fa-check me-1"></i> Resuelto
                                            </span>
                                        @else
                                            <span class="badge bg-warning">
                                                <i class="fas fa-clock me-1"></i> Pendiente
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Intentos:</strong></td>
                                    <td>
                                        <span class="badge bg-info">
                                            {{ $error->intentos_realizados }}/{{ $error->max_intentos }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Fecha Creación:</strong></td>
                                    <td>{{ $error->created_at->format('d/m/Y H:i:s') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Última Actualización:</strong></td>
                                    <td>{{ $error->updated_at->format('d/m/Y H:i:s') }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 card-title">
                        <i class="fas fa-chart-pie me-2"></i>
                        Información Adicional
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3 d-flex justify-content-between">
                        <span>Es crítico:</span>
                        <strong>{{ $error->isCritico() ? 'Sí' : 'No' }}</strong>
                    </div>
                    <div class="mb-3 d-flex justify-content-between">
                        <span>Puede reintentar:</span>
                        <strong>{{ $error->puedeReintentar() ? 'Sí' : 'No' }}</strong>
                    </div>
                    <div class="mb-3 d-flex justify-content-between">
                        <span>Necesita intervención:</span>
                        <strong>{{ $error->necesitaIntervencionManual() ? 'Sí' : 'No' }}</strong>
                    </div>
                    @if($error->proximo_reintento)
                    <div class="d-flex justify-content-between">
                        <span>Próximo reintento:</span>
                        <strong>{{ $error->proximo_reintento->format('d/m/Y H:i') }}</strong>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Descripción del error -->
    <div class="mb-4 row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 card-title">
                        <i class="fas fa-file-alt me-2"></i>
                        Descripción del Error
                    </h5>
                </div>
                <div class="card-body">
                    <p class="mb-0">{{ $error->descripcion }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Detalles del error -->
    @if($error->detalles)
    <div class="mb-4 row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 card-title">
                        <i class="fas fa-list me-2"></i>
                        Detalles Técnicos
                    </h5>
                </div>
                <div class="card-body">
                    <pre class="p-3 rounded bg-light" style="max-height: 300px; overflow-y: auto;"><code>{{ json_encode($error->detalles, JSON_PRETTY_PRINT) }}</code></pre>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Stack trace -->
    @if($error->trace)
    <div class="mb-4 row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 card-title">
                        <i class="fas fa-bug me-2"></i>
                        Stack Trace
                    </h5>
                </div>
                <div class="card-body">
                    <pre class="p-3 rounded bg-light" style="max-height: 300px; overflow-y: auto;"><code>{{ json_encode($error->trace, JSON_PRETTY_PRINT) }}</code></pre>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Información del DTE asociado -->
    @if($error->dte)
    <div class="mb-4 row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 card-title">
                        <i class="fas fa-file-invoice me-2"></i>
                        DTE Asociado
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>ID DTE:</strong></td>
                                    <td>
                                        <a href="{{ route('dte.show', $error->dte->id) }}" class="text-primary">
                                            {{ $error->dte->id }}
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Empresa:</strong></td>
                                    <td>{{ $error->dte->company->name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Tipo DTE:</strong></td>
                                    <td>{{ $error->dte->tipoDte }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Estado DTE:</strong></td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            {{ $error->dte->codEstado ?? 'N/A' }}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Número Control:</strong></td>
                                    <td>{{ $error->dte->id_doc }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Fecha Creación:</strong></td>
                                    <td>{{ $error->dte->created_at->format('d/m/Y H:i:s') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Intentos DTE:</strong></td>
                                    <td>{{ $error->dte->nSends }}</td>
                                </tr>
                                @if($error->dte->sale)
                                <tr>
                                    <td><strong>Cliente:</strong></td>
                                    <td>{{ $error->dte->sale->client->name_contribuyente ?? 'N/A' }}</td>
                                </tr>
                                @endif
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Información de resolución -->
    @if($error->resuelto)
    <div class="mb-4 row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 card-title">
                        <i class="fas fa-check-circle me-2"></i>
                        Información de Resolución
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Resuelto por:</strong></td>
                                    <td>{{ $error->resueltoPor->name ?? 'Sistema' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Fecha resolución:</strong></td>
                                    <td>{{ $error->resolved_at ? $error->resolved_at->format('d/m/Y H:i:s') : 'N/A' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Solución aplicada:</strong></td>
                                    <td>{{ $error->solucion ?? 'N/A' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<!-- Modal para resolver error -->
<div class="modal fade" id="resolverErrorModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Resolver Error</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="resolverErrorForm">
                    <div class="mb-3">
                        <label for="solucion" class="form-label">Solución aplicada:</label>
                        <textarea class="form-control" id="solucion" name="solucion" rows="3" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" onclick="confirmarResolucion()">Resolver</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script>
let errorIdActual = {{ $error->id }};

function resolverError(errorId) {
    errorIdActual = errorId;
    document.getElementById('solucion').value = '';
    new bootstrap.Modal(document.getElementById('resolverErrorModal')).show();
}

function confirmarResolucion() {
    const solucion = document.getElementById('solucion').value;
    if (!solucion.trim()) {
        Swal.fire({
            icon: 'warning',
            title: 'Campo requerido',
            text: 'Por favor ingrese una solución'
        });
        return;
    }

    $.ajax({
        url: `/dte-admin/errores/${errorIdActual}/resolver`,
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        data: JSON.stringify({ solucion: solucion }),
        success: function(response) {
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Error resuelto!',
                    text: response.message,
                    timer: 2000
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.message
                });
            }
        },
        error: function(xhr) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error al resolver: ' + xhr.responseText
            });
        }
    });
}
</script>
@endsection
