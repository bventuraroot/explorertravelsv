@extends('layouts/layoutMaster')

@section('title', 'Gestión de Contingencias DTE')

@section('vendor-style')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
@endsection

@section('vendor-script')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
@endsection

@section('page-script')
<script>
$(document).ready(function() {
    // Inicializar DataTable
    $('#contingenciasTable').DataTable({
        responsive: true,
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
        },
        order: [[0, 'desc']]
    });

    // Inicializar Select2
    $('#empresa_id, #empresa_id_modal').select2({
        theme: 'bootstrap-5',
        placeholder: 'Seleccione...'
    });

    // Inicialización específica para el select dentro del modal (evita que el dropdown se oculte)
    function initSelect2Modal() {
        $('#dte_ids').select2({
            theme: 'bootstrap-5',
            placeholder: 'Seleccione DTEs...',
            width: '100%',
            dropdownParent: $('#crearContingenciaModal')
        });
    }
    initSelect2Modal();

    // Cargar DTEs para contingencia
    $('#empresa_id').change(function() {
        const empresaId = $(this).val();
        if (empresaId) {
            $.get('{{ route("dte.dtes-para-contingencia") }}', { empresa_id: empresaId })
                .done(function(data) {
                    const select = $('#dte_ids');
                    select.empty();
                    select.append('<option value="">Seleccione DTEs...</option>');

                    data.forEach(function(dte) {
                        select.append(`<option value="${dte.id}">${dte.numero_control} - ${dte.cliente} (${dte.tipo_documento})</option>`);
                    });
                });
        }
    });

    // Cargar DTEs para el modal (empresa_id_modal)
    $('#empresa_id_modal').change(function() {
        const empresaId = $(this).val();
        // Forzar incluir borradores por defecto para este flujo
        const incluirBorradores = true;
        if (empresaId) {
            const select = $('#dte_ids');
            select.empty().append('<option value="">Cargando documentos...</option>').prop('disabled', true);
            $.get('{{ route("dte.dtes-para-contingencia") }}', { empresa_id: empresaId, incluir_borradores: incluirBorradores ? 1 : 0 })
                .done(function(data) {
                    select.empty();
                    select.append('<option value="">Seleccione DTEs...</option>').prop('disabled', false);

                    data.forEach(function(dte) {
                        select.append(`<option value="${dte.id}">${dte.numero_control} - ${dte.cliente} (${dte.tipo_documento})</option>`);
                    });
                    if (data.length === 0) {
                        select.empty().append('<option value="" disabled>No hay documentos en borrador</option>');
                    }
                    // Re-inicializar Select2 para que tome el dropdownParent tras cargar opciones
                    select.off('select2:select');
                    select.select2('destroy');
                    initSelect2Modal();
                })
                .fail(function(xhr) {
                    select.empty().append('<option value="" disabled>Error cargando documentos</option>').prop('disabled', false);
                    console.error('Error dtes-para-contingencia:', xhr.status, xhr.responseText);
                })
                ;
        }
    });

    // Al abrir el modal: si hay una única empresa, seleccionarla y cargar borradores
    $('#crearContingenciaModal').on('shown.bs.modal', function () {
        // Si no hay empresas renderizadas en servidor, cargarlas por AJAX
        const $empresa = $('#empresa_id_modal');
        // Asegurar dropdownParent correcto en cada apertura
        initSelect2Modal();
        if ($empresa.find('option').length <= 1) {
            $.get('/company/getCompany')
                .done(function(list) {
                    if (Array.isArray(list)) {
                        list.forEach(function(c) {
                            $empresa.append('<option value="' + c.id + '">' + (c.name || c.razon_social || ('Empresa ' + c.id)) + '</option>');
                        });
                        $empresa.trigger('change.select2');
                    }
                })
                .always(function() {
                    // Seleccionar automáticamente si quedó una sola
                    if ($empresa.find('option').length === 2 && !$empresa.val()) {
                        const val = $empresa.find('option:eq(1)').val();
                        $empresa.val(val).trigger('change');
                    }
                });
        }
        // Reutilizar la variable $empresa definida arriba
        if ($empresa.find('option').length === 2 && !$empresa.val()) { // placeholder + 1 empresa
            const val = $empresa.find('option:eq(1)').val();
            $empresa.val(val).trigger('change');
        } else if ($empresa.val()) {
            $empresa.trigger('change');
        }
    });

    // Aprobar contingencia
    $('.aprobar-contingencia').click(function() {
        const contingenciaId = $(this).data('contingencia-id');
        const nombre = $(this).data('nombre');

        Swal.fire({
            title: 'Aprobar Contingencia',
            text: `¿Está seguro de aprobar la contingencia "${nombre}"?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Aprobar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post(`/dte/contingencias/${contingenciaId}/aprobar`, {
                    _token: '{{ csrf_token() }}'
                })
                .done(function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Contingencia aprobada!',
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
                })
                .fail(function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error de conexión'
                    });
                });
            }
        });
    });

    // Activar contingencia
    $('.activar-contingencia').click(function() {
        const contingenciaId = $(this).data('contingencia-id');
        const nombre = $(this).data('nombre');

        Swal.fire({
            title: 'Activar Contingencia',
            text: `¿Está seguro de activar la contingencia "${nombre}"?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Activar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post(`/dte/contingencias/${contingenciaId}/activar`, {
                    _token: '{{ csrf_token() }}'
                })
                .done(function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Contingencia activada!',
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
                })
                .fail(function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error de conexión'
                    });
                });
            }
        });
    });
});
</script>
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="fw-bold py-3 mb-0">
                    <i class="fas fa-shield-alt me-2"></i>
                    Gestión de Contingencias DTE
                </h4>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#crearContingenciaModal">
                        <i class="fas fa-plus me-1"></i>
                        Nueva Contingencia
                    </button>
                    <a href="{{ route('dte.dashboard') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>
                        Volver al Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

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
                    <form method="GET" action="{{ route('dte.contingencias') }}" class="row g-3">
                        <div class="col-md-3">
                            <label for="estado" class="form-label">Estado</label>
                            <select name="estado" id="estado" class="form-select">
                                <option value="">Todos los estados</option>
                                <option value="pendiente" {{ isset($filtros['estado']) && $filtros['estado'] == 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                                <option value="aprobada" {{ isset($filtros['estado']) && $filtros['estado'] == 'aprobada' ? 'selected' : '' }}>Aprobada</option>
                                <option value="activa" {{ isset($filtros['estado']) && $filtros['estado'] == 'activa' ? 'selected' : '' }}>Activa</option>
                                <option value="finalizada" {{ isset($filtros['estado']) && $filtros['estado'] == 'finalizada' ? 'selected' : '' }}>Finalizada</option>
                                <option value="cancelada" {{ isset($filtros['estado']) && $filtros['estado'] == 'cancelada' ? 'selected' : '' }}>Cancelada</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="empresa_id" class="form-label">Empresa</label>
                            <select name="empresa_id" id="empresa_id" class="form-select">
                                <option value="">Todas las empresas</option>
                                @foreach($empresas as $empresa)
                                    <option value="{{ $empresa->id }}" {{ isset($filtros['empresa_id']) && $filtros['empresa_id'] == $empresa->id ? 'selected' : '' }}>
                                        {{ $empresa->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="tipo" class="form-label">Tipo</label>
                            <select name="tipo" id="tipo" class="form-select">
                                <option value="">Todos los tipos</option>
                                <option value="tecnica" {{ isset($filtros['tipo']) && $filtros['tipo'] == 'tecnica' ? 'selected' : '' }}>Técnica</option>
                                <option value="administrativa" {{ isset($filtros['tipo']) && $filtros['tipo'] == 'administrativa' ? 'selected' : '' }}>Administrativa</option>
                                <option value="emergencia" {{ isset($filtros['tipo']) && $filtros['tipo'] == 'emergencia' ? 'selected' : '' }}>Emergencia</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-search me-1"></i>
                                Filtrar
                            </button>
                            <a href="{{ route('dte.contingencias') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>
                                Limpiar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Estadísticas de contingencias -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title text-muted">Total Contingencias</h6>
                            <h3 class="mb-0">{{ $contingencias->total() }}</h3>
                        </div>
                        <div class="avatar avatar-md bg-secondary rounded">
                            <i class="fas fa-shield-alt text-white"></i>
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
                            <h6 class="card-title text-muted">Activas</h6>
                            <h3 class="mb-0 text-success">{{ $contingencias->where('activa', true)->count() }}</h3>
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
                            <h6 class="card-title text-muted">Pendientes</h6>
                            <h3 class="mb-0 text-warning">{{ $contingencias->where('codEstado', '01')->count() }}</h3>
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
                            <h6 class="card-title text-muted">Vigentes</h6>
                            <h3 class="mb-0 text-info">{{ $contingencias->where('fecha_fin', '>=', now())->count() }}</h3>
                        </div>
                        <div class="avatar avatar-md bg-info rounded">
                            <i class="fas fa-calendar-check text-white"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de contingencias -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-list me-2"></i>
                        Lista de Contingencias
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="contingenciasTable" class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Empresa</th>
                                    <th>Tipo</th>
                                    <th>Vigencia</th>
                                    <th>Documentos</th>
                                    <th>Estado</th>
                                    <th>Creado por</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($contingencias as $contingencia)
                                <tr>
                                    <td>{{ $contingencia->id }}</td>
                                    <td>
                                        <strong>{{ $contingencia->nombre ?? $contingencia->codInterno ?? 'N/A' }}</strong>
                                        <br>
                                        <small class="text-muted">{{ Str::limit($contingencia->motivoContingencia, 50) }}</small>
                                    </td>
                                    <td>{{ $contingencia->company->name ?? 'N/A' }}</td>
                                    <td>{!! $contingencia->tipo_texto !!}</td>
                                    <td>
                                        <div>
                                            <small class="text-muted">Inicio:</small><br>
                                            {{ $contingencia->fecha_inicio ? ((($contingencia->fecha_inicio instanceof \Illuminate\Support\Carbon) || ($contingencia->fecha_inicio instanceof \Carbon\Carbon)) ? $contingencia->fecha_inicio->format('d/m/Y H:i') : \Carbon\Carbon::parse($contingencia->fecha_inicio)->format('d/m/Y H:i')) : 'N/A' }}
                                        </div>
                                        <div class="mt-1">
                                            <small class="text-muted">Fin:</small><br>
                                            {{ $contingencia->fecha_fin ? ((($contingencia->fecha_fin instanceof \Illuminate\Support\Carbon) || ($contingencia->fecha_fin instanceof \Carbon\Carbon)) ? $contingencia->fecha_fin->format('d/m/Y H:i') : \Carbon\Carbon::parse($contingencia->fecha_fin)->format('d/m/Y H:i')) : 'N/A' }}
                                        </div>
                                        @php
                                            $__fin = $contingencia->fecha_fin ? ((($contingencia->fecha_fin instanceof \Illuminate\Support\Carbon) || ($contingencia->fecha_fin instanceof \Carbon\Carbon)) ? $contingencia->fecha_fin : \Carbon\Carbon::parse($contingencia->fecha_fin)) : null;
                                        @endphp
                                        @if($__fin && $__fin >= now())
                                            <span class="badge bg-success mt-1">Vigente</span>
                                        @else
                                            <span class="badge bg-secondary mt-1">Vencida</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-info">{{ $contingencia->documentos_afectados }}</span>
                                    </td>
                                    <td>{!! $contingencia->estado_badge !!}</td>
                                    <td>{{ $contingencia->created_by ?? 'Sistema' }}</td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            @if($contingencia->codEstado == '01')
                                                <button class="btn btn-sm btn-outline-success aprobar-contingencia"
                                                        data-contingencia-id="{{ $contingencia->id }}"
                                                        data-nombre="{{ $contingencia->nombre }}">
                                                    <i class="fas fa-check"></i>
                                                    Aprobar
                                                </button>
                                            @elseif($contingencia->codEstado == '02' && !$contingencia->activa)
                                                <button class="btn btn-sm btn-outline-primary activar-contingencia"
                                                        data-contingencia-id="{{ $contingencia->id }}"
                                                        data-nombre="{{ $contingencia->nombre }}">
                                                    <i class="fas fa-play"></i>
                                                    Activar
                                                </button>
                                            @endif
                                            <button class="btn btn-sm btn-outline-info" title="Ver detalles">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginación -->
                    <div class="d-flex justify-content-center mt-4">
                        {{ $contingencias->appends($filtros)->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Crear Contingencia -->
<div class="modal fade" id="crearContingenciaModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus me-2"></i>
                    Nueva Contingencia
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('dte.crear-contingencia') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="empresa_id" class="form-label">Empresa *</label>
                            <select name="empresa_id" id="empresa_id_modal" class="form-select" required>
                                <option value="">Seleccione empresa...</option>
                                @foreach($empresas as $empresa)
                                    <option value="{{ $empresa->id }}">{{ $empresa->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="tipo_contingencia" class="form-label">Tipo de Contingencia *</label>
                            <select name="tipo_contingencia" id="tipo_contingencia" class="form-select" required>
                                <option value="">Seleccione tipo...</option>
                                <option value="tecnica">Técnica</option>
                                <option value="administrativa">Administrativa</option>
                                <option value="emergencia">Emergencia</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label for="nombre" class="form-label">Nombre *</label>
                            <input type="text" name="nombre" id="nombre" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label for="descripcion" class="form-label">Descripción *</label>
                            <textarea name="descripcion" id="descripcion" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="col-12">
                            <label for="motivo" class="form-label">Motivo *</label>
                            <textarea name="motivo" id="motivo" class="form-control" rows="2" required></textarea>
                        </div>
                        <div class="col-md-6">
                            <label for="fecha_inicio" class="form-label">Fecha de Inicio *</label>
                            <input type="datetime-local" name="fecha_inicio" id="fecha_inicio" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label for="fecha_fin" class="form-label">Fecha de Fin *</label>
                            <input type="datetime-local" name="fecha_fin" id="fecha_fin" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label for="resolucion_mh" class="form-label">Resolución MH</label>
                            <input type="text" name="resolucion_mh" id="resolucion_mh" class="form-control">
                        </div>
                        <div class="col-12">
                            <label for="dte_ids" class="form-label">DTEs Afectados</label>
                            <select name="dte_ids[]" id="dte_ids" class="form-select" multiple>
                                <option value="">Seleccione empresa primero...</option>
                            </select>
                            <small class="text-muted">Seleccione los DTEs que serán incluidos en esta contingencia</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>
                        Crear Contingencia
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
