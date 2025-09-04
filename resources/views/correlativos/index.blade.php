@extends('layouts/layoutMaster')

@section('title', 'Gestión de Correlativos')

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
    <!-- Header -->
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="fw-bold py-3 mb-0">
                    <i class="fas fa-list-ol me-2"></i>
                    Gestión de Correlativos
                </h4>
                <div class="d-flex gap-2">
                    <a href="{{ route('correlativos.create') }}" class="btn btn-success">
                        <i class="fas fa-plus me-1"></i>
                        Nuevo Correlativo
                    </a>
                    <a href="{{ route('correlativos.estadisticas') }}" class="btn btn-info">
                        <i class="fas fa-chart-bar me-1"></i>
                        Estadísticas
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Filtros -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-filter me-2"></i>
                        Filtros
                    </h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('correlativos.index') }}">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="empresa_id" class="form-label">Empresa</label>
                                <select name="empresa_id" id="empresa_id" class="form-select">
                                    <option value="">Todas las empresas</option>
                                    @foreach($empresas as $empresa)
                                        <option value="{{ $empresa->id }}"
                                            {{ request('empresa_id') == $empresa->id ? 'selected' : '' }}>
                                            {{ $empresa->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="tipo_documento" class="form-label">Tipo de Documento</label>
                                <select name="tipo_documento" id="tipo_documento" class="form-select">
                                    <option value="">Todos los tipos</option>
                                    @foreach($tiposDocumento as $tipo)
                                        <option value="{{ $tipo->type }}"
                                            {{ request('tipo_documento') == $tipo->type ? 'selected' : '' }}>
                                            {{ $tipo->description }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="estado" class="form-label">Estado</label>
                                <select name="estado" id="estado" class="form-select">
                                    <option value="">Todos los estados</option>
                                    <option value="1" {{ request('estado') == '1' ? 'selected' : '' }}>Activo</option>
                                    <option value="0" {{ request('estado') == '0' ? 'selected' : '' }}>Inactivo</option>
                                    <option value="2" {{ request('estado') == '2' ? 'selected' : '' }}>Agotado</option>
                                    <option value="3" {{ request('estado') == '3' ? 'selected' : '' }}>Suspendido</option>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="fas fa-search me-1"></i>
                                    Filtrar
                                </button>
                                <a href="{{ route('correlativos.index') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-1"></i>
                                    Limpiar
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Estadísticas rápidas -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title text-muted">Total Correlativos</h6>
                            <h3 class="mb-0">{{ $correlativos->count() }}</h3>
                        </div>
                        <div class="avatar avatar-md bg-primary rounded">
                            <i class="fas fa-list-ol text-white"></i>
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
                            <h6 class="card-title text-muted">Activos</h6>
                            <h3 class="mb-0 text-success">{{ $correlativos->where('estado', 1)->count() }}</h3>
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
                            <h6 class="card-title text-muted">Agotados</h6>
                            <h3 class="mb-0 text-warning">{{ $correlativos->where('estado', 2)->count() }}</h3>
                        </div>
                        <div class="avatar avatar-md bg-warning rounded">
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
                            <h6 class="card-title text-muted">Inactivos</h6>
                            <h3 class="mb-0 text-secondary">{{ $correlativos->where('estado', 0)->count() }}</h3>
                        </div>
                        <div class="avatar avatar-md bg-secondary rounded">
                            <i class="fas fa-pause text-white"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Correlativos -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-table me-2"></i>
                        Lista de Correlativos
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="correlativosTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Empresa</th>
                                    <th>Tipo Documento</th>
                                    <th>Serie</th>
                                    <th>Rango</th>
                                    <th>Actual</th>
                                    <th>Restantes</th>
                                    <th>Progreso</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($correlativos as $correlativo)
                                    <tr>
                                        <td>{{ $correlativo->id }}</td>
                                        <td>{{ $correlativo->empresa->name ?? 'N/A' }}</td>
                                        <td>
                                            <span class="badge bg-primary">{{ $correlativo->tipoDocumento->type ?? 'N/A' }}</span><br>
                                            <small>{{ $correlativo->tipoDocumento->description ?? 'Sin definir' }}</small>
                                        </td>
                                        <td>{{ $correlativo->serie }}</td>
                                        <td>
                                            <small class="text-muted">
                                                {{ number_format($correlativo->inicial) }} - {{ number_format($correlativo->final) }}
                                            </small>
                                        </td>
                                        <td>
                                            <strong>{{ number_format($correlativo->actual) }}</strong>
                                        </td>
                                        <td>
                                            <span class="badge {{ $correlativo->numerosRestantes() < 100 ? 'bg-warning' : 'bg-success' }}">
                                                {{ number_format($correlativo->numerosRestantes()) }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                @php
                                                    $porcentaje = $correlativo->porcentajeUso();
                                                    $colorClass = $porcentaje > 90 ? 'bg-danger' : ($porcentaje > 70 ? 'bg-warning' : 'bg-success');
                                                @endphp
                                                <div class="progress-bar {{ $colorClass }}" role="progressbar"
                                                     style="width: {{ $porcentaje }}%"
                                                     aria-valuenow="{{ $porcentaje }}"
                                                     aria-valuemin="0"
                                                     aria-valuemax="100">
                                                    {{ number_format($porcentaje, 1) }}%
                                                </div>
                                            </div>
                                        </td>
                                        <td>{!! $correlativo->estado_badge !!}</td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('correlativos.show', $correlativo->id) }}"
                                                   class="btn btn-sm btn-outline-info" title="Ver">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('correlativos.edit', $correlativo->id) }}"
                                                   class="btn btn-sm btn-outline-primary" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                @if($correlativo->estado == 2) {{-- Agotado --}}
                                                    <button type="button" class="btn btn-sm btn-outline-success"
                                                            title="Reactivar" onclick="mostrarModalReactivar({{ $correlativo->id }})">
                                                        <i class="fas fa-redo"></i>
                                                    </button>
                                                @endif
                                                @if($correlativo->actual == $correlativo->inicial)
                                                    <form action="{{ route('correlativos.destroy', $correlativo->id) }}"
                                                          method="POST" style="display: inline;">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger"
                                                                title="Eliminar"
                                                                onclick="return confirm('¿Está seguro de eliminar este correlativo?')">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center">
                                            <div class="py-4">
                                                <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                                                <p class="text-muted">No se encontraron correlativos</p>
                                                <a href="{{ route('correlativos.create') }}" class="btn btn-primary">
                                                    <i class="fas fa-plus me-1"></i>
                                                    Crear primer correlativo
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Reactivar Correlativo -->
<div class="modal fade" id="modalReactivar" tabindex="-1" aria-labelledby="modalReactivarLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formReactivar" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="modalReactivarLabel">
                        <i class="fas fa-redo me-2"></i>
                        Reactivar Correlativo
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Para reactivar este correlativo, debe asignar un nuevo rango de números que continúe después del rango anterior.
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <label for="nuevo_inicial" class="form-label">Nuevo Número Inicial</label>
                            <input type="number" class="form-control" id="nuevo_inicial" name="nuevo_inicial" required>
                        </div>
                        <div class="col-md-6">
                            <label for="nuevo_final" class="form-label">Nuevo Número Final</label>
                            <input type="number" class="form-control" id="nuevo_final" name="nuevo_final" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-redo me-1"></i>
                        Reactivar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script>
$(document).ready(function() {
    // Inicializar DataTables
    $('#correlativosTable').DataTable({
        responsive: true,
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
        },
        order: [[0, 'desc']],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]]
    });
});

function mostrarModalReactivar(correlativoId) {
    const modal = new bootstrap.Modal(document.getElementById('modalReactivar'));
    const form = document.getElementById('formReactivar');
    form.action = `/correlativos/${correlativoId}/reactivar`;
    modal.show();
}

// Limpiar formulario al cerrar modal
document.getElementById('modalReactivar').addEventListener('hidden.bs.modal', function () {
    document.getElementById('formReactivar').reset();
});

// Auto-dismiss alerts
setTimeout(function() {
    $('.alert').fadeOut('slow');
}, 5000);
</script>
@endsection
