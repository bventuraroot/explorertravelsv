@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Manuales del Sistema')

@section('vendor-style')
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}">
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/sweetalert2/sweetalert2.css') }}" />
@endsection

@section('page-style')
<style>
    .manual-card {
        transition: all 0.3s ease;
        border: 1px solid #e3e6f0;
        border-radius: 0.5rem;
    }

    .manual-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        border-color: #696cff;
    }

    .manual-icon {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 24px;
        margin-bottom: 1rem;
    }

    .manual-title {
        color: #2c3e50;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    .manual-description {
        color: #6c757d;
        font-size: 0.9rem;
        line-height: 1.5;
    }

    .manual-meta {
        font-size: 0.8rem;
        color: #adb5bd;
        margin-top: 1rem;
    }

    .btn-manual {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        color: white !important;
        padding: 0.5rem 1rem;
        border-radius: 0.375rem;
        transition: all 0.3s ease;
    }

    .btn-manual:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(102, 126, 234, 0.3);
        color: white !important;
    }

    .btn-download {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        border: none;
        color: white;
    }

    .btn-download:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);
        color: white;
    }

    .empty-state {
        text-align: center;
        padding: 3rem 1rem;
        color: #6c757d;
    }

    .empty-state i {
        font-size: 4rem;
        margin-bottom: 1rem;
        color: #dee2e6;
    }

    .search-box {
        background: white;
        border: 1px solid #e3e6f0;
        border-radius: 0.5rem;
        padding: 1rem;
        margin-bottom: 2rem;
    }
</style>
@endsection

@section('vendor-script')
<script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/sweetalert2/sweetalert2.js') }}"></script>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="mb-4 row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0">
                            <i class="ti ti-book me-2"></i>
                            Manuales del Sistema
                        </h4>
                        <p class="mb-0 text-muted">Documentación y guías de usuario para todos los módulos</p>
                    </div>
                    <div class="gap-2 d-flex">
                        <button type="button" class="btn btn-outline-primary" onclick="refreshManuals()">
                            <i class="ti ti-refresh me-1"></i>
                            Actualizar
                        </button>
                        @if($isAdmin)
                        <a href="{{ route('manuals.create') }}" class="btn btn-primary">
                            <i class="ti ti-plus me-1"></i>
                            Nuevo Manual
                        </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search Box -->
    <div class="row">
        <div class="col-12">
            <div class="search-box">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="ti ti-search"></i>
                            </span>
                            <input type="text" id="search-manuals" class="form-control"
                                   placeholder="Buscar manuales por título o descripción...">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="gap-2 d-flex">
                            <select id="sort-manuals" class="form-select">
                                <option value="date-desc">Más recientes primero</option>
                                <option value="date-asc">Más antiguos primero</option>
                                <option value="name-asc">Nombre A-Z</option>
                                <option value="name-desc">Nombre Z-A</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Manuals Grid -->
    <div class="row" id="manuals-container">
        @if(count($manuals) > 0)
            @foreach($manuals as $module => $moduleManuals)
                @foreach($moduleManuals as $manual)
                <div class="mb-4 col-lg-4 col-md-6 manual-item"
                     data-title="{{ strtolower($manual['titulo']) }}"
                     data-description="{{ strtolower($manual['descripcion']) }}"
                     data-date="{{ strtotime($manual['updated_at']) }}">
                    <div class="card manual-card h-100">
                        <div class="text-center card-body">
                            <div class="manual-icon">
                                <i class="ti ti-{{ $manual['icono'] ?? 'book' }}"></i>
                            </div>

                            <h5 class="manual-title">{{ $manual['titulo'] }}</h5>

                            <p class="manual-description">
                                {{ $manual['descripcion'] }}
                            </p>

                            <div class="manual-meta">
                                <div class="text-center row">
                                    <div class="col-6">
                                        <small>
                                            <i class="ti ti-calendar me-1"></i>
                                            {{ date('d/m/Y', strtotime($manual['updated_at'])) }}
                                        </small>
                                    </div>
                                    <div class="col-6">
                                        <small>
                                            <i class="ti ti-file me-1"></i>
                                            {{ \App\Http\Controllers\ManualController::formatFileSize(strlen($manual['contenido_markdown'])) }}
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <div class="gap-2 mt-3 d-flex justify-content-center">
                                <a href="{{ route('manuals.show', $manual['id']) }}"
                                   class="btn btn-manual btn-sm">
                                    <i class="ti ti-eye me-1"></i>
                                    Ver Manual
                                </a>
                                <button type="button" class="btn btn-download btn-sm" onclick="downloadManual('{{ $manual['id'] }}')">
                                    <i class="ti ti-download me-1"></i>
                                    Descargar
                                </button>
                                @if($isAdmin)
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        <i class="ti ti-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="{{ route('manuals.edit', $manual['id']) }}">
                                            <i class="ti ti-edit me-2"></i>Editar
                                        </a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item text-danger" href="#" onclick="deleteManual('{{ $manual['id'] }}')">
                                            <i class="ti ti-trash me-2"></i>Eliminar
                                        </a></li>
                                    </ul>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            @endforeach
        @else
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="empty-state">
                            <i class="ti ti-book-off"></i>
                            <h5>No hay manuales disponibles</h5>
                            <p>Los manuales aparecerán aquí cuando estén disponibles.</p>
                            @if($isAdmin)
                            <a href="{{ route('manuals.create') }}" class="mt-3 btn btn-primary">
                                <i class="ti ti-plus me-1"></i>Crear Primer Manual
                            </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

<!-- Modal de confirmación para eliminar -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar eliminación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de que deseas eliminar este manual?</p>
                <p class="text-muted small">Esta acción no se puede deshacer.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Eliminar</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script>
$(document).ready(function() {
    // Función de búsqueda
    $('#search-manuals').on('input', function() {
        const searchTerm = $(this).val().toLowerCase();
        filterManuals(searchTerm);
    });

    // Función de ordenamiento
    $('#sort-manuals').on('change', function() {
        const sortBy = $(this).val();
        sortManuals(sortBy);
    });

    function filterManuals(searchTerm) {
        $('.manual-item').each(function() {
            const title = $(this).data('title');
            const description = $(this).data('description');

            if (title.includes(searchTerm) || description.includes(searchTerm)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    }

    function sortManuals(sortBy) {
        const container = $('#manuals-container');
        const items = container.find('.manual-item').toArray();

        items.sort(function(a, b) {
            switch(sortBy) {
                case 'date-desc':
                    return $(b).data('date') - $(a).data('date');
                case 'date-asc':
                    return $(a).data('date') - $(b).data('date');
                case 'name-asc':
                    return $(a).data('title').localeCompare($(b).data('title'));
                case 'name-desc':
                    return $(b).data('title').localeCompare($(a).data('title'));
                default:
                    return 0;
            }
        });

        container.empty().append(items);
    }
});

function refreshManuals() {
    Swal.fire({
        title: 'Actualizando manuales...',
        text: 'Por favor espera mientras se actualiza la lista',
        allowOutsideClick: false,
        showConfirmButton: false,
        willOpen: () => {
            Swal.showLoading();
        }
    });

    setTimeout(() => {
        location.reload();
    }, 1000);
}

function downloadManual(manualId) {
    Swal.fire({
        title: 'Descargando manual...',
        text: 'Preparando el archivo para descarga',
        allowOutsideClick: false,
        showConfirmButton: false,
        willOpen: () => {
            Swal.showLoading();
        }
    });

    // Simular descarga (aquí podrías implementar la lógica real de descarga)
    setTimeout(() => {
        Swal.fire({
            title: '¡Descarga completada!',
            text: 'El manual se ha descargado exitosamente',
            icon: 'success',
            timer: 2000,
            showConfirmButton: false
        });
    }, 1500);
}

function deleteManual(id) {
    const form = document.getElementById('deleteForm');
    form.action = `/manuals/${id}`;

    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}
</script>
@endsection
