@extends('layouts.layoutMaster')

@section('title', 'Respaldos de Base de Datos')

@section('vendor-style')
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}">
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}">
@endsection

@section('page-style')
<style>
    .backup-card {
        background: linear-gradient(135deg, rgba(58, 70, 100, 0.9) 0%, rgba(41, 50, 73, 0.95) 100%);
        border-radius: 12px;
        padding: 24px;
        color: #fff;
        transition: transform 0.3s ease;
    }
    .backup-card:hover {
        transform: translateY(-5px);
    }
    .backup-icon {
        width: 60px;
        height: 60px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 30px;
    }
    .backup-icon.purple {
        background: rgba(115, 103, 240, 0.2);
        color: #7367f0;
    }
    .backup-icon.green {
        background: rgba(40, 199, 111, 0.2);
        color: #28c76f;
    }
    .backup-icon.orange {
        background: rgba(255, 159, 67, 0.2);
        color: #ff9f43;
    }
    .backup-icon.cyan {
        background: rgba(0, 207, 232, 0.2);
        color: #00cfe8;
    }
    .control-panel {
        background: linear-gradient(135deg, rgba(58, 70, 100, 0.9) 0%, rgba(41, 50, 73, 0.95) 100%);
        border-radius: 12px;
        padding: 30px;
        color: #fff;
        margin-bottom: 24px;
    }
    .schedule-info {
        background: rgba(255, 255, 255, 0.05);
        border-radius: 8px;
        padding: 12px;
        margin-top: 10px;
    }
    .schedule-info p {
        margin: 4px 0;
        font-size: 13px;
        color: rgba(255, 255, 255, 0.7);
    }
    .btn-test-auto {
        background: transparent;
        border: 2px solid #00cfe8;
        color: #00cfe8;
        border-radius: 8px;
        padding: 12px 24px;
        transition: all 0.3s ease;
    }
    .btn-test-auto:hover {
        background: #00cfe8;
        color: #fff;
    }
    .table-dark-custom {
        background: #2c3e50;
        color: #fff;
    }
    .table-dark-custom thead th {
        background: #34495e;
        border: none;
        padding: 15px;
        font-weight: 600;
    }
    .table-dark-custom tbody td {
        background: #2c3e50;
        border-color: #34495e;
        padding: 15px;
    }
    .badge-compressed {
        background: #28c76f;
        color: #fff;
        padding: 6px 12px;
        border-radius: 6px;
        font-weight: 500;
    }
    .badge-normal {
        background: #7367f0;
        color: #fff;
        padding: 6px 12px;
        border-radius: 6px;
        font-weight: 500;
    }
    .badge-database {
        background: #00cfe8;
        color: #fff;
        padding: 6px 12px;
        border-radius: 6px;
        font-weight: 500;
    }
</style>
@endsection

@section('content')
<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard') }}">Dashboard</a>
        </li>
        <li class="breadcrumb-item active">Respaldos</li>
    </ol>
</nav>

<h4 class="fw-bold py-3 mb-4">
    <span class="text-muted fw-light">Sistema /</span> Respaldos de Base de Datos
</h4>

<!-- Estadísticas -->
<div class="row mb-4">
    <div class="col-xl-3 col-sm-6 mb-4">
        <div class="backup-card">
            <div class="d-flex align-items-start">
                <div class="backup-icon purple">
                    <i class="bx bx-data"></i>
                </div>
                <div class="ms-3">
                    <p class="mb-0 text-white-50">Total Respaldos</p>
                    <h3 class="mb-0 text-white" id="stat-total">{{ $stats['total'] }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6 mb-4">
        <div class="backup-card">
            <div class="d-flex align-items-start">
                <div class="backup-icon green">
                    <i class="bx bx-hdd"></i>
                </div>
                <div class="ms-3">
                    <p class="mb-0 text-white-50">Espacio Total</p>
                    <h3 class="mb-0 text-white" id="stat-size">{{ $stats['total_size_formatted'] }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6 mb-4">
        <div class="backup-card">
            <div class="d-flex align-items-start">
                <div class="backup-icon orange">
                    <i class="bx bx-archive"></i>
                </div>
                <div class="ms-3">
                    <p class="mb-0 text-white-50">Comprimidos</p>
                    <h3 class="mb-0 text-white" id="stat-compressed">{{ $stats['compressed'] }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6 mb-4">
        <div class="backup-card">
            <div class="d-flex align-items-start">
                <div class="backup-icon cyan">
                    <i class="bx bx-calendar"></i>
                </div>
                <div class="ms-3">
                    <p class="mb-0 text-white-50">Último Respaldo</p>
                    <h3 class="mb-0 text-white" id="stat-last">{{ $stats['last_backup_formatted'] }}</h3>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Panel de Control -->
<div class="control-panel">
    <h5 class="text-white mb-4">Panel de Control</h5>

    <div class="row">
        <!-- Comprimir respaldo -->
        <div class="col-md-4 mb-3">
            <label class="text-white mb-2">Comprimir respaldo</label>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="compressBackup" checked>
                <label class="form-check-label text-white-50" for="compressBackup">
                    Reducir tamaño del archivo
                </label>
            </div>
        </div>

        <!-- Mantener respaldos -->
        <div class="col-md-4 mb-3">
            <label class="text-white mb-2">Mantener respaldos</label>
            <select class="form-select" id="keepBackups">
                <option value="1">1 respaldo</option>
                <option value="2">2 respaldos</option>
                <option value="3" selected>3 respaldos</option>
                <option value="5">5 respaldos</option>
                <option value="10">10 respaldos</option>
                <option value="20">20 respaldos</option>
                <option value="999">Todos</option>
            </select>
        </div>

        <!-- Respaldos Automáticos -->
        <div class="col-md-4 mb-3">
            <label class="text-white mb-2">Respaldos Automáticos</label>
            <button type="button" class="btn btn-test-auto w-100">
                <i class="bx bx-time-five me-2"></i>Probar Automático
            </button>
            <div class="schedule-info">
                <p><i class="bx bx-sun me-2"></i>Diario: 2:00 AM</p>
                <p><i class="bx bx-calendar-week me-2"></i>Semanal: Dom 3:00 AM</p>
                <p><i class="bx bx-calendar me-2"></i>Mensual: Día 1, 4:00 AM</p>
            </div>
        </div>
    </div>

    <!-- Botones de acción -->
    <div class="row mt-4">
        <div class="col-12">
            <button type="button" class="btn btn-primary me-2" id="btnCreateBackup">
                <i class="bx bx-plus me-2"></i>Crear Nuevo Respaldo
            </button>
            <button type="button" class="btn btn-secondary me-2" id="btnRefreshList">
                <i class="bx bx-refresh me-2"></i>Actualizar Lista
            </button>
            <button type="button" class="btn btn-danger" id="btnCleanOld">
                <i class="bx bx-trash me-2"></i>Limpiar Antiguos
            </button>
        </div>
    </div>
</div>

<!-- Tabla de Respaldos Disponibles -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Respaldos Disponibles</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-dark-custom" id="backupsTable">
                <thead>
                    <tr>
                        <th>ARCHIVO</th>
                        <th>BASE DE DATOS</th>
                        <th>TAMAÑO</th>
                        <th>FECHA</th>
                        <th>ESTADO</th>
                        <th>ACCIONES</th>
                    </tr>
                </thead>
                <tbody id="backupsList">
                    @forelse($backups as $backup)
                    <tr data-filename="{{ $backup['filename'] }}">
                        <td>
                            <i class="bx bx-file me-2"></i>
                            {{ $backup['filename'] }}
                        </td>
                        <td>
                            <span class="badge-database">{{ $backup['database'] }}</span>
                        </td>
                        <td>{{ $backup['size_formatted'] }}</td>
                        <td>
                            <div>{{ $backup['date'] }}</div>
                            <small class="text-muted">{{ $backup['date_relative'] }}</small>
                        </td>
                        <td>
                            @if($backup['compressed'])
                                <span class="badge-compressed">
                                    <i class="bx bx-check-circle me-1"></i>Comprimido
                                </span>
                            @else
                                <span class="badge-normal">Normal</span>
                            @endif
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-icon btn-primary me-2 btn-download"
                                    data-filename="{{ $backup['filename'] }}"
                                    title="Descargar">
                                <i class="bx bx-download"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-icon btn-danger btn-delete"
                                    data-filename="{{ $backup['filename'] }}"
                                    title="Eliminar">
                                <i class="bx bx-trash"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr id="noBackupsRow">
                        <td colspan="6" class="text-center py-4">
                            <i class="bx bx-folder-open display-4 d-block mb-3"></i>
                            <p class="mb-0">No hay respaldos disponibles</p>
                            <small class="text-muted">Crea tu primer respaldo usando el botón "Crear Nuevo Respaldo"</small>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('vendor-script')
<script src="{{ asset('assets/vendor/libs/datatables/jquery.dataTables.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/datatables-responsive/datatables.responsive.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endsection

@section('page-script')
<script>
$(document).ready(function() {
    // Crear nuevo backup
    $('#btnCreateBackup').click(function() {
        const compress = $('#compressBackup').is(':checked');
        const btn = $(this);

        btn.prop('disabled', true).html('<i class="bx bx-loader bx-spin me-2"></i>Creando...');

        $.ajax({
            url: '{{ route("backups.create") }}',
            method: 'POST',
            data: {
                compress: compress,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: response.message,
                        timer: 3000
                    });
                    refreshBackupsList();
                }
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: xhr.responseJSON?.message || 'Error al crear el backup'
                });
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="bx bx-plus me-2"></i>Crear Nuevo Respaldo');
            }
        });
    });

    // Actualizar lista
    $('#btnRefreshList').click(function() {
        refreshBackupsList();
    });

    // Limpiar antiguos
    $('#btnCleanOld').click(function() {
        const keep = $('#keepBackups').val();

        Swal.fire({
            title: '¿Estás seguro?',
            text: `Se mantendrán los ${keep} respaldos más recientes y se eliminarán los demás`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, limpiar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("backups.clean") }}',
                    method: 'POST',
                    data: {
                        keep: keep,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Éxito!',
                                text: response.message,
                                timer: 3000
                            });
                            refreshBackupsList();
                        }
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: xhr.responseJSON?.message || 'Error al limpiar respaldos'
                        });
                    }
                });
            }
        });
    });

    // Descargar backup
    $(document).on('click', '.btn-download', function() {
        const filename = $(this).data('filename');
        window.location.href = '{{ route("backups.download", ["filename" => "_FILENAME_"]) }}'.replace('_FILENAME_', filename);
    });

    // Eliminar backup
    $(document).on('click', '.btn-delete', function() {
        const filename = $(this).data('filename');

        Swal.fire({
            title: '¿Estás seguro?',
            text: 'Esta acción no se puede deshacer',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#d33'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("backups.delete", ["filename" => "_FILENAME_"]) }}'.replace('_FILENAME_', filename),
                    method: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Eliminado!',
                                text: response.message,
                                timer: 2000
                            });
                            refreshBackupsList();
                        }
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: xhr.responseJSON?.message || 'Error al eliminar el backup'
                        });
                    }
                });
            }
        });
    });

    // Función para actualizar la lista de backups
    function refreshBackupsList() {
        $.ajax({
            url: '{{ route("backups.refresh") }}',
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    updateStats(response.stats);
                    updateBackupsList(response.backups);
                }
            }
        });
    }

    // Actualizar estadísticas
    function updateStats(stats) {
        $('#stat-total').text(stats.total);
        $('#stat-size').text(stats.total_size_formatted);
        $('#stat-compressed').text(stats.compressed);
        $('#stat-last').text(stats.last_backup_formatted);
    }

    // Actualizar lista de backups
    function updateBackupsList(backups) {
        const tbody = $('#backupsList');
        tbody.empty();

        if (backups.length === 0) {
            tbody.append(`
                <tr id="noBackupsRow">
                    <td colspan="6" class="text-center py-4">
                        <i class="bx bx-folder-open display-4 d-block mb-3"></i>
                        <p class="mb-0">No hay respaldos disponibles</p>
                        <small class="text-muted">Crea tu primer respaldo usando el botón "Crear Nuevo Respaldo"</small>
                    </td>
                </tr>
            `);
        } else {
            backups.forEach(function(backup) {
                const statusBadge = backup.compressed
                    ? '<span class="badge-compressed"><i class="bx bx-check-circle me-1"></i>Comprimido</span>'
                    : '<span class="badge-normal">Normal</span>';

                tbody.append(`
                    <tr data-filename="${backup.filename}">
                        <td>
                            <i class="bx bx-file me-2"></i>
                            ${backup.filename}
                        </td>
                        <td>
                            <span class="badge-database">${backup.database}</span>
                        </td>
                        <td>${backup.size_formatted}</td>
                        <td>
                            <div>${backup.date}</div>
                            <small class="text-muted">${backup.date_relative}</small>
                        </td>
                        <td>${statusBadge}</td>
                        <td>
                            <button type="button" class="btn btn-sm btn-icon btn-primary me-2 btn-download"
                                    data-filename="${backup.filename}"
                                    title="Descargar">
                                <i class="bx bx-download"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-icon btn-danger btn-delete"
                                    data-filename="${backup.filename}"
                                    title="Eliminar">
                                <i class="bx bx-trash"></i>
                            </button>
                        </td>
                    </tr>
                `);
            });
        }
    }
});
</script>
@endsection

