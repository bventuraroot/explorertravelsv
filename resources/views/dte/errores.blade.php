@extends('layouts/layoutMaster')

@section('title', 'Gestión de Errores DTE')

@section('vendor-style')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
@endsection

@section('vendor-script')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="fw-bold py-3 mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Gestión de Errores DTE
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

    <!-- Estadísticas -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title text-muted">Total Errores</h6>
                            <h3 class="mb-0">{{ $estadisticas['total'] ?? 0 }}</h3>
                        </div>
                        <div class="avatar avatar-md bg-secondary rounded">
                            <i class="fas fa-exclamation-triangle text-white"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title text-muted">No Resueltos</h6>
                            <h3 class="mb-0 text-warning">{{ $estadisticas['no_resueltos'] ?? 0 }}</h3>
                        </div>
                        <div class="avatar avatar-md bg-warning rounded">
                            <i class="fas fa-clock text-white"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title text-muted">Resueltos</h6>
                            <h3 class="mb-0 text-success">{{ $estadisticas['resueltos'] ?? 0 }}</h3>
                        </div>
                        <div class="avatar avatar-md bg-success rounded">
                            <i class="fas fa-check text-white"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title text-muted">Críticos</h6>
                            <h3 class="mb-0 text-danger">{{ $estadisticas['criticos'] ?? 0 }}</h3>
                        </div>
                        <div class="avatar avatar-md bg-danger rounded">
                            <i class="fas fa-exclamation-circle text-white"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros y Acciones -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Filtros y Acciones</h5>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-primary" onclick="exportarErrores()">
                            <i class="fas fa-download me-1"></i> Exportar
                        </button>
                        <button type="button" class="btn btn-success" onclick="procesarReintentos()">
                            <i class="fas fa-redo me-1"></i> Procesar Reintentos
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <form id="filtrosForm" method="GET" action="{{ route('dte.errores') }}" class="row g-3">
                        <div class="col-md-3">
                            <label for="tipo" class="form-label">Tipo de Error</label>
                            <select class="form-select" id="tipo" name="tipo">
                                <option value="">Todos los tipos</option>
                                <option value="validacion" {{ isset($filtros['tipo']) && $filtros['tipo'] == 'validacion' ? 'selected' : '' }}>Validación</option>
                                <option value="hacienda" {{ isset($filtros['tipo']) && $filtros['tipo'] == 'hacienda' ? 'selected' : '' }}>Hacienda</option>
                                <option value="sistema" {{ isset($filtros['tipo']) && $filtros['tipo'] == 'sistema' ? 'selected' : '' }}>Sistema</option>
                                <option value="autenticacion" {{ isset($filtros['tipo']) && $filtros['tipo'] == 'autenticacion' ? 'selected' : '' }}>Autenticación</option>
                                <option value="firma" {{ isset($filtros['tipo']) && $filtros['tipo'] == 'firma' ? 'selected' : '' }}>Firma</option>
                                <option value="datos" {{ isset($filtros['tipo']) && $filtros['tipo'] == 'datos' ? 'selected' : '' }}>Datos</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="empresa" class="form-label">Empresa</label>
                            <select class="form-select" id="empresa" name="empresa_id">
                                <option value="">Todas las empresas</option>
                                @foreach($empresas as $empresa)
                                    <option value="{{ $empresa->id }}" {{ isset($filtros['empresa_id']) && $filtros['empresa_id'] == $empresa->id ? 'selected' : '' }}>
                                        {{ $empresa->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="resuelto" class="form-label">Estado</label>
                            <select class="form-select" id="resuelto" name="resuelto">
                                <option value="">Todos los estados</option>
                                <option value="0" {{ isset($filtros['resuelto']) && $filtros['resuelto'] === '0' ? 'selected' : '' }}>No resueltos</option>
                                <option value="1" {{ isset($filtros['resuelto']) && $filtros['resuelto'] === '1' ? 'selected' : '' }}>Resueltos</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter me-1"></i> Filtrar
                                </button>
                                <button type="button" class="btn btn-secondary" onclick="limpiarFiltros()">
                                    <i class="fas fa-times me-1"></i> Limpiar
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Errores -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Lista de Errores DTE</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="erroresTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>DTE ID</th>
                                    <th>Tipo</th>
                                    <th>Empresa</th>
                                    <th>Descripción</th>
                                    <th>Estado</th>
                                    <th>Intentos</th>
                                    <th>Fecha</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($errores as $error)
                                <tr class="{{ $error->resuelto ? 'table-success' : '' }}">
                                    <td>{{ $error->id }}</td>
                                    <td>
                                        <a href="{{ route('dte.show', $error->dte_id) }}" class="text-primary">
                                            {{ $error->dte_id }}
                                        </a>
                                    </td>
                                    <td>{!! $error->tipo_badge !!}</td>
                                    <td>{{ $error->dte->company->name ?? 'N/A' }}</td>
                                    <td>
                                        <span class="text-truncate d-inline-block" style="max-width: 300px;"
                                              title="{{ $error->descripcion }}">
                                            {{ Str::limit($error->descripcion, 80) }}
                                        </span>
                                    </td>
                                    <td>{!! $error->estado_badge !!}</td>
                                    <td>
                                        <span class="badge bg-info">
                                            {{ $error->intentos_realizados }}/{{ $error->max_intentos }}
                                        </span>
                                    </td>
                                    <td>{{ $error->created_at->format('d/m/Y H:i') }}</td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('dte.show', $error->dte_id) }}"
                                               class="btn btn-sm btn-outline-primary"
                                               title="Ver DTE">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @if(!$error->resuelto)
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-success"
                                                        onclick="resolverError({{ $error->id }})"
                                                        title="Marcar como resuelto">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-warning"
                                                        onclick="reintentarDte({{ $error->dte_id }})"
                                                        title="Reintentar DTE">
                                                    <i class="fas fa-redo"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginación -->
                    <div class="d-flex justify-content-center mt-4">
                        {{ $errores->appends($filtros)->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
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
let errorIdActual = null;

$(document).ready(function() {
    // Inicializar DataTables
    $('#erroresTable').DataTable({
        responsive: true,
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
        },
        order: [[7, 'desc']] // Ordenar por fecha descendente
    });

    // Aplicar filtros automáticamente
    $('#filtrosForm select').change(function() {
        $('#filtrosForm').submit();
    });
});

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
        url: `/dte/errores/${errorIdActual}/resolver`,
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

function reintentarDte(dteId) {
    Swal.fire({
        title: '¿Reintentar DTE?',
        text: '¿Está seguro de que desea reintentar este DTE?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, reintentar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `/dte/reintentar/${dteId}`,
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Reintento exitoso!',
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
                        text: 'Error al reintentar: ' + xhr.responseText
                    });
                }
            });
        }
    });
}

function procesarReintentos() {
    Swal.fire({
        title: 'Procesar Reintentos',
        text: '¿Desea procesar todos los reintentos automáticos?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, procesar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '{{ route("dte.procesar-reintentos") }}',
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Procesamiento completado!',
                            text: response.message,
                            timer: 3000
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
                        text: 'Error de conexión'
                    });
                }
            });
        }
    });
}

function exportarErrores() {
    const filtros = $('#filtrosForm').serialize();
    window.open(`{{ route('dte.errores') }}?${filtros}&export=1`, '_blank');
}

function limpiarFiltros() {
    $('#filtrosForm')[0].reset();
    window.location.href = '{{ route("dte.errores") }}';
}
</script>
@endsection
