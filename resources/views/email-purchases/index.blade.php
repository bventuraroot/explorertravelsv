@php
    $configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Buzón de Compras DTE')

@section('vendor-style')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/flatpickr/flatpickr.css') }}" />
@endsection

@section('vendor-script')
    <script src="{{ asset('assets/vendor/libs/flatpickr/flatpickr.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

    {{-- Encabezado --}}
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
        <div>
            <h4 class="fw-bold mb-1">
                <span class="text-muted fw-light">Compras /</span> Buzón de Importación DTE
            </h4>
            <p class="text-muted mb-0 small">
                <i class="ti ti-mail me-1"></i>
                Escaneo y registro automático de compras a partir de facturas DTE (Ministerio de Hacienda) recibidas por Email.
            </p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary d-flex align-items-center gap-2 shadow-sm" onclick="window.location.href='{{ route('purchase.index') }}'">
                <i class="ti ti-arrow-left fs-5"></i>
                <span>Volver a Compras</span>
            </button>
            <button id="btnVerLogs" class="btn btn-outline-info d-flex align-items-center gap-2 shadow-sm">
                <i class="ti ti-terminal fs-5"></i>
                <span>Ver logs</span>
            </button>
            <button id="btnRevisarCorreo" class="btn btn-primary d-flex align-items-center gap-2 shadow-sm">
                <span class="spinner-border spinner-border-sm d-none" id="spinnerRevisar"></span>
                <i class="ti ti-refresh fs-5" id="iconRevisar"></i>
                <span id="btnRevisarTexto">Escanear correos</span>
            </button>
        </div>
    </div>

    {{-- Tarjetas de resumen --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card h-100 text-center border-0 shadow-sm">
                <div class="card-body py-3">
                    <div class="mb-1">
                        <i class="ti ti-mail-opened fs-3 text-primary opacity-75"></i>
                    </div>
                    <div class="display-6 fw-bold text-primary">{{ number_format($stats['total']) }}</div>
                    <div class="small text-muted mt-1">Total revisados</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100 text-center border-0 shadow-sm border-success border-start border-4">
                <div class="card-body py-3">
                    <div class="mb-1">
                        <i class="ti ti-circle-check fs-3 text-success opacity-75"></i>
                    </div>
                    <div class="display-6 fw-bold text-success">{{ number_format($stats['processed']) }}</div>
                    <div class="small text-muted mt-1">Procesados (Compras creadas)</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100 text-center border-0 shadow-sm border-warning border-start border-4">
                <div class="card-body py-3">
                    <div class="mb-1">
                        <i class="ti ti-alert-circle fs-3 text-warning opacity-75"></i>
                    </div>
                    <div class="display-6 fw-bold text-warning">{{ number_format($stats['pending']) }}</div>
                    <div class="small text-muted mt-1">Pendientes de Mapear</div>
                    @if($stats['pending'] > 0)
                        <span class="badge bg-label-warning mt-1">
                            <i class="ti ti-exclamation-mark me-1"></i>Mapeo requerido
                        </span>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100 text-center border-0 shadow-sm">
                <div class="card-body py-3">
                    <div class="mb-1">
                        <i class="ti ti-circle-x fs-3 text-danger opacity-75"></i>
                    </div>
                    <div class="display-6 fw-bold text-danger">{{ number_format($stats['errors']) }}</div>
                    <div class="small text-muted mt-1">Errores</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('email-purchases.index') }}" class="row g-3 align-items-end">
                <div class="col-12 col-sm-6 col-md-3">
                    <label class="form-label small fw-medium">Estado / Filtro</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="" @selected(request('status') === null || request('status') === '')>Pendientes de ingresar (Sin Compra)</option>
                        <option value="all" @selected(request('status') === 'all')>Todos (Historial completo)</option>
                        <option value="processed" @selected(request('status') === 'processed')>✓ Procesados</option>
                        <option value="pending" @selected(request('status') === 'pending')>⚠ Pendientes</option>
                        <option value="error" @selected(request('status') === 'error')>✗ Errores</option>
                        <option value="skipped" @selected(request('status') === 'skipped')>— Omitidos</option>
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <label class="form-label small fw-medium">Desde</label>
                    <input type="date" name="date_from" class="form-control form-control-sm"
                           value="{{ request('date_from') }}">
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <label class="form-label small fw-medium">Hasta</label>
                    <input type="date" name="date_to" class="form-control form-control-sm"
                           value="{{ request('date_to') }}">
                </div>
                <div class="col-6 col-md-1.5 col-lg">
                    <button type="submit" class="btn btn-sm btn-secondary w-100">
                        <i class="ti ti-filter me-1"></i>Filtrar
                    </button>
                </div>
                <div class="col-6 col-md-1.5 col-lg">
                    <a href="{{ route('email-purchases.index') }}"
                       class="btn btn-sm btn-outline-secondary w-100">
                        <i class="ti ti-x me-1"></i>Limpiar
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Tabla de historial --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header border-0 pb-0 d-flex align-items-center justify-content-between">
            <h5 class="card-title mb-0">
                <i class="ti ti-history me-2 text-muted"></i>Importaciones del Buzón DTE
            </h5>
            <span class="badge bg-label-secondary">
                {{ $imports->total() }} registros
            </span>
        </div>
        <div class="card-body px-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr class="text-uppercase text-muted" style="font-size: 0.75rem; letter-spacing: 0.5px; font-weight: 600;">
                            <th class="text-center" style="width:140px;">Acciones</th>
                            <th style="min-width:130px;">Estado</th>
                            <th class="ps-4" style="min-width:130px;">
                                <div class="d-inline-flex align-items-center gap-1">
                                    <i class="ti ti-calendar ti-xs text-muted"></i>
                                    <span>Fecha Correo</span>
                                </div>
                            </th>
                            <th style="min-width:150px;">Tipo DTE</th>
                            <th style="min-width:180px;">N° Control / Código Gen.</th>
                            <th style="min-width:140px;">
                                <div class="d-inline-flex align-items-center gap-1">
                                    <i class="ti ti-building ti-xs text-muted"></i>
                                    <span>Empresa Receptora</span>
                                </div>
                            </th>
                            <th style="min-width:160px;">
                                <div class="d-inline-flex align-items-center gap-1">
                                    <i class="ti ti-store ti-xs text-muted"></i>
                                    <span>Proveedor Emisor</span>
                                </div>
                            </th>
                            <th class="text-end pe-4" style="min-width:110px;">
                                <div class="d-inline-flex align-items-center justify-content-end gap-1 w-100">
                                    <i class="ti ti-currency-dollar ti-xs text-muted"></i>
                                    <span>Monto DTE</span>
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($imports as $import)
                        <tr class="{{ $import->status === 'error' ? 'table-danger bg-opacity-10' : '' }}">

                            {{-- Acciones (Al inicio de la fila) --}}
                            <td class="text-center">
                                <div class="d-flex align-items-center justify-content-center gap-1">

                                    {{-- Ver detalle / Mapeo manual --}}
                                    @if($import->status === 'pending')
                                        <a href="{{ route('email-purchases.show', $import->id) }}"
                                           class="btn btn-sm btn-icon btn-outline-warning"
                                           title="Mapear Proveedor / Empresa y Registrar Compra">
                                            <i class="ti ti-link"></i>
                                        </a>
                                    @else
                                        <a href="{{ route('email-purchases.show', $import->id) }}"
                                           class="btn btn-sm btn-icon btn-outline-primary"
                                           title="Ver detalle completo de DTE">
                                            <i class="ti ti-eye"></i>
                                        </a>
                                    @endif

                                    {{-- Ver compra oficial generada --}}
                                    @if($import->status === 'processed' && $import->purchase_id)
                                        <a href="{{ route('purchase.index') }}"
                                           class="btn btn-sm btn-icon btn-outline-success"
                                           title="Ver compra generada en el sistema">
                                            <i class="ti ti-file-text"></i>
                                        </a>
                                    @endif

                                    {{-- Ver Factura PDF si existe --}}
                                    @if($import->pdf_path)
                                        <a href="javascript:void(0);"
                                           onclick="previewPdf('{{ route('email-purchases.pdf', $import->id) }}')"
                                           class="btn btn-sm btn-icon btn-outline-danger"
                                           title="Ver Factura PDF original">
                                            <i class="ti ti-file-analytics"></i>
                                        </a>
                                    @endif

                                </div>
                            </td>

                            {{-- Estado --}}
                            <td>
                                @php
                                    $statusIcon = match($import->status) {
                                        'processed' => 'ti-circle-check',
                                        'error'     => 'ti-circle-x',
                                        'skipped'   => 'ti-circle-minus',
                                        default     => 'ti-clock',
                                    };
                                    $statusColor = match($import->status) {
                                        'processed' => 'success',
                                        'error'     => 'danger',
                                        'skipped'   => 'secondary',
                                        default     => 'warning',
                                    };
                                @endphp
                                <span class="badge bg-label-{{ $statusColor }} d-inline-flex align-items-center gap-1">
                                    <i class="ti {{ $statusIcon }}"></i>
                                    {{ $import->status_label }}
                                </span>
                                @if($import->status === 'error' && $import->error_message)
                                    <div class="small text-danger mt-1 text-truncate" style="max-width:130px"
                                         title="{{ $import->error_message }}">
                                        <i class="ti ti-info-circle me-1"></i>{{ Str::limit($import->error_message, 45) }}
                                    </div>
                                @endif
                            </td>

                            {{-- Fecha --}}
                            <td class="ps-4">
                                <span class="fw-medium small text-dark">
                                    {{ $import->email_date ? $import->email_date->format('d/m/Y') : '—' }}
                                </span>
                                <div class="small text-muted">{{ $import->email_date ? $import->email_date->format('H:i') : '' }}</div>
                            </td>

                            {{-- Tipo DTE --}}
                            <td>
                                @if($import->dte_tipo_dte)
                                    <span class="badge bg-label-info">
                                        {{ $import->dte_tipo_dte }} – {{ Str::limit($import->dte_tipo_nombre, 22) }}
                                    </span>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>

                            {{-- N° Control --}}
                            <td>
                                <span class="font-monospace small text-nowrap text-dark" title="Generación: {{ $import->dte_codigo_generacion ?? 'No disponible' }}" data-bs-toggle="tooltip" data-bs-placement="top">
                                    {{ $import->dte_numero_control ?? '—' }}
                                </span>
                                @if($import->dte_codigo_generacion)
                                    <div class="small text-muted font-monospace text-truncate" style="max-width: 170px; font-size: 0.72rem" title="Código Generación: {{ $import->dte_codigo_generacion }}" data-bs-toggle="tooltip" data-bs-placement="top">
                                        MH: {{ Str::limit($import->dte_codigo_generacion, 15) }}
                                    </div>
                                @endif
                            </td>

                            {{-- Empresa receptora --}}
                            <td>
                                @if($import->company)
                                    <div class="d-flex align-items-center gap-1">
                                        <i class="ti ti-building text-primary opacity-75"></i>
                                        <span class="small text-dark fw-medium">{{ $import->company->name }}</span>
                                    </div>
                                @else
                                    <span class="text-muted small text-warning"><i class="ti ti-alert-triangle me-1"></i>No resuelto</span>
                                @endif
                            </td>

                            {{-- Proveedor (emisor del DTE) --}}
                            <td>
                                @php $emisor = $import->emisor_nombre @endphp
                                @if($emisor)
                                    <div class="d-flex align-items-center gap-1" title="{{ $emisor }}">
                                        <i class="ti ti-store text-secondary opacity-75 flex-shrink-0"></i>
                                        <span class="small text-truncate text-dark fw-medium" style="max-width:150px">{{ $emisor }}</span>
                                    </div>
                                @else
                                    <span class="text-muted small text-warning"><i class="ti ti-alert-triangle me-1"></i>No resuelto</span>
                                @endif
                            </td>

                            {{-- Total DTE --}}
                            <td class="text-end pe-4">
                                @php $total = $import->total_dte @endphp
                                @if($total !== null)
                                    <span class="fw-bold text-dark fs-6">
                                        ${{ number_format($total, 2) }}
                                    </span>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="ti ti-inbox display-4 d-block mb-3 opacity-50"></i>
                                <p class="mb-1 fw-semibold text-dark">No hay importaciones registradas</p>
                                <p class="small mb-0">Haz clic en <strong>"Escanear correos"</strong> para buscar nuevos adjuntos DTE en el email.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Paginación Bootstrap 5 --}}
            @if($imports->hasPages())
                <div class="d-flex justify-content-between align-items-center px-4 py-3 border-top">
                    <small class="text-muted">
                        Mostrando {{ $imports->firstItem() }}–{{ $imports->lastItem() }} de {{ $imports->total() }} registros
                    </small>
                    <div>
                        {{ $imports->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Modal de Previsualización de PDF --}}
    <div class="modal fade" id="previewPdfModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title d-flex align-items-center">
                        <i class="ti ti-file-text text-danger me-2 fs-4"></i>
                        <span>Previsualización de Factura PDF</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0 position-relative" style="background-color: #f8f9fa;">
                    {{-- Spinner de Carga --}}
                    <div id="pdfLoadingSpinner" class="position-absolute top-50 start-50 translate-middle d-flex flex-column align-items-center" style="z-index: 10;">
                        <div class="spinner-border text-danger" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <span class="text-muted small mt-2">Cargando documento PDF...</span>
                    </div>
                    {{-- Embed del PDF --}}
                    <embed id="pdfPreviewIframe" src="" type="application/pdf" class="w-100 border-0 d-none" style="height: 700px; max-height: 80vh;"></embed>
                </div>
                <div class="modal-footer border-top bg-light d-flex justify-content-between">
                    <a id="btnPdfDownload" href="" target="_blank" class="btn btn-outline-secondary">
                        <i class="ti ti-download me-1"></i>Descargar / Abrir en pestaña
                    </a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal de Consola de Logs Diagnósticos (Estilo Terminal) --}}
    <div class="modal fade" id="logsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-bottom bg-light">
                    <h5 class="modal-title d-flex align-items-center">
                        <i class="ti ti-terminal text-info me-2 fs-3"></i>
                        <span>Consola de Diagnóstico IMAP (Producción)</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3 style-terminal-dark" style="background-color: #0f172a !important;">
                    {{-- Barra de herramientas de consola --}}
                    <div class="d-flex align-items-center justify-content-between mb-3 gap-2 flex-wrap">
                        <div class="input-group input-group-merge input-group-sm w-auto flex-grow-1" style="max-width: 250px;">
                            <span class="input-group-text bg-secondary border-0 text-white"><i class="ti ti-search ti-xs"></i></span>
                            <input type="text" id="inputFiltrarLogs" class="form-control bg-secondary border-0 text-white form-control-sm" placeholder="Filtrar en consola...">
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-success bg-opacity-10 text-success cursor-pointer" onclick="filtrarLogTipo('info')">INFO</span>
                            <span class="badge bg-warning bg-opacity-10 text-warning cursor-pointer" onclick="filtrarLogTipo('warning')">WARN</span>
                            <span class="badge bg-danger bg-opacity-10 text-danger cursor-pointer" onclick="filtrarLogTipo('error')">ERROR</span>
                            <span class="badge bg-secondary bg-opacity-10 text-secondary cursor-pointer" onclick="filtrarLogTipo('all')">TODOS</span>
                        </div>
                    </div>

                    {{-- Caja de la consola --}}
                    <div class="position-relative border border-secondary border-opacity-50 rounded" style="background-color: #020617; box-shadow: inset 0 2px 8px rgba(0,0,0,0.8);">
                        {{-- Contenedor de Carga --}}
                        <div id="logsConsoleSpinner" class="position-absolute top-50 start-50 translate-middle d-flex flex-column align-items-center d-none" style="z-index: 10;">
                            <div class="spinner-border text-info" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                        </div>
                        {{-- Panel Monospace --}}
                        <div id="panelConsolaLogs" class="p-3" style="height: 400px; overflow-y: auto; font-family: 'Consolas', 'Courier New', monospace; font-size: 0.82rem; line-height: 1.5; color: #cbd5e1; scroll-behavior: smooth;">
                            <pre id="logsConsole" class="mb-0" style="white-space: pre-wrap; word-break: break-all; font-family: inherit; color: inherit;"></pre>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light d-flex justify-content-between">
                    <div class="d-flex gap-2">
                        <button type="button" id="btnCopiarLogs" class="btn btn-sm btn-outline-secondary">
                            <i class="ti ti-copy me-1"></i>Copiar logs
                        </button>
                        <button type="button" id="btnLimpiarConsola" class="btn btn-sm btn-outline-danger">
                            <i class="ti ti-trash me-1"></i>Limpiar pantalla
                        </button>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" id="btnRefrescarLogs" class="btn btn-sm btn-info">
                            <i class="ti ti-refresh me-1"></i>Actualizar logs
                        </button>
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script>
document.getElementById('btnRevisarCorreo').addEventListener('click', function () {
    const btn     = this;
    const spinner = document.getElementById('spinnerRevisar');
    const icon    = document.getElementById('iconRevisar');
    const texto   = document.getElementById('btnRevisarTexto');

    btn.disabled = true;
    spinner.classList.remove('d-none');
    icon.classList.add('d-none');
    texto.textContent = 'Buscando...';

    fetch('{{ route("email-purchases.run") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        },
        body: JSON.stringify({ limit: 0 }),
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const s = data.summary;
            const total = s.processed + s.errors + s.skipped;
            Swal.fire({
                icon: s.errors > 0 ? 'warning' : 'success',
                title: 'Escaneo completado',
                html: `
                    <div class="text-start">
                        <div class="d-flex justify-content-between mb-2">
                            <span><i class="ti ti-circle-check text-success me-2"></i>Procesados (Auto):</span>
                            <strong class="text-success">${s.processed}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span><i class="ti ti-circle-x text-danger me-2"></i>Errores:</span>
                            <strong class="text-danger">${s.errors}</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span><i class="ti ti-circle-minus text-secondary me-2"></i>Omitidos:</span>
                            <strong class="text-secondary">${s.skipped}</strong>
                        </div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between">
                            <span><i class="ti ti-mail me-2"></i>Total escaneados:</span>
                            <strong>${total}</strong>
                        </div>
                    </div>`,
                confirmButtonText: '<i class="ti ti-refresh me-1"></i>Actualizar buzón',
                confirmButtonColor: '#7367f0',
            }).then(() => window.location.reload());
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error al escanear',
                text: data.message,
                confirmButtonColor: '#ea5455',
            });
        }
    })
    .catch(() => {
        Swal.fire({
            icon: 'error',
            title: 'Error de conexión',
            text: 'No se pudo conectar al servidor de correos. Verifica tu archivo .env.',
        });
    })
    .finally(() => {
        btn.disabled = false;
        spinner.classList.add('d-none');
        icon.classList.remove('d-none');
        texto.textContent = 'Escanear correos';
    });
});

// Función para previsualizar PDF
function previewPdf(url) {
    const modalElement = document.getElementById('previewPdfModal');
    const modal = new bootstrap.Modal(modalElement);
    const iframe = document.getElementById('pdfPreviewIframe');
    const spinner = document.getElementById('pdfLoadingSpinner');
    const downloadBtn = document.getElementById('btnPdfDownload');

    // Resetear vistas
    iframe.classList.add('d-none');
    spinner.classList.remove('d-none');
    iframe.src = '';
    downloadBtn.href = url;

    // Mostrar modal
    modal.show();

    // Establecer la URL del embed
    iframe.src = url + '#view=FitH';

    // Quitar el spinner al cargar o tras un breve retraso
    iframe.onload = function() {
        spinner.classList.add('d-none');
        iframe.classList.remove('d-none');
    };
    setTimeout(function() {
        spinner.classList.add('d-none');
        iframe.classList.remove('d-none');
    }, 400);
}

// Variable global para almacenar logs
let rawLogsCache = [];

// Mostrar modal de logs
document.getElementById('btnVerLogs').addEventListener('click', function () {
    const modalElement = document.getElementById('logsModal');
    const modal = new bootstrap.Modal(modalElement);
    modal.show();
    cargarLogs();
});

// Recargar logs
document.getElementById('btnRefrescarLogs').addEventListener('click', function () {
    cargarLogs();
});

// Limpiar consola
document.getElementById('btnLimpiarConsola').addEventListener('click', function () {
    document.getElementById('logsConsole').innerHTML = '<span style="color: #64748b;">(Consola limpia)</span>';
    rawLogsCache = [];
});

// Copiar logs al portapapeles
document.getElementById('btnCopiarLogs').addEventListener('click', function () {
    const consolePre = document.getElementById('logsConsole');
    const textToCopy = consolePre.innerText;
    
    if (!textToCopy || textToCopy.includes('(Consola limpia)') || textToCopy.includes('Cargando logs')) {
        Swal.fire({
            icon: 'info',
            title: 'Sin datos',
            text: 'No hay logs disponibles para copiar.',
            timer: 1500,
            showConfirmButton: false
        });
        return;
    }

    navigator.clipboard.writeText(textToCopy).then(() => {
        const btn = this;
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="ti ti-check me-1"></i>¡Copiado!';
        btn.classList.replace('btn-outline-secondary', 'btn-outline-success');
        
        setTimeout(() => {
            btn.innerHTML = originalText;
            btn.classList.replace('btn-outline-success', 'btn-outline-secondary');
        }, 2000);
    }).catch(err => {
        Swal.fire({
            icon: 'error',
            title: 'Error al copiar',
            text: 'No se pudo copiar de forma automática. Intenta seleccionando el texto manualmente.'
        });
    });
});

// Filtrar logs por escritura
document.getElementById('inputFiltrarLogs').addEventListener('input', function (e) {
    renderizarLogs(rawLogsCache, e.target.value);
});

// Cargar logs mediante AJAX
function cargarLogs() {
    const spinner = document.getElementById('logsConsoleSpinner');
    const consolePre = document.getElementById('logsConsole');
    const panel = document.getElementById('panelConsolaLogs');

    spinner.classList.remove('d-none');
    consolePre.innerHTML = '<span style="color: #64748b;">Cargando logs del servidor...</span>';

    fetch('{{ route("email-purchases.logs") }}')
    .then(r => r.json())
    .then(data => {
        spinner.classList.add('d-none');
        if (data.success) {
            const logsText = data.logs || 'No hay logs de sincronización recientes.';
            rawLogsCache = logsText.split("\n");
            
            // Restablecer el buscador
            document.getElementById('inputFiltrarLogs').value = '';
            
            renderizarLogs(rawLogsCache);
            // Hacer scroll hacia abajo al final
            setTimeout(() => { panel.scrollTop = panel.scrollHeight; }, 150);
        } else {
            consolePre.innerHTML = `<span style="color: #f87171;">Error al cargar logs: ${data.message}</span>`;
        }
    })
    .catch(err => {
        spinner.classList.add('d-none');
        consolePre.innerHTML = '<span style="color: #f87171;">Fallo de conexión de red al obtener logs.</span>';
    });
}

// Renderizar logs formateados
function renderizarLogs(lineas, filtro = '') {
    const consolePre = document.getElementById('logsConsole');
    consolePre.innerHTML = '';
    
    let html = '';
    lineas.forEach(linea => {
        if (!linea.trim()) return;

        // Filtro de texto simple (insensible a mayúsculas)
        if (filtro && !linea.toLowerCase().includes(filtro.toLowerCase())) {
            return;
        }

        let style = 'color: #cbd5e1;'; // slate-300 por defecto
        
        // Determinar colores según severidad
        if (linea.includes('.ERROR') || linea.includes('Error') || linea.includes('Fallo') || linea.includes('exception') || linea.includes('Exception') || linea.includes('fail')) {
            style = 'color: #f87171; font-weight: 600;'; // rojo claro
        } else if (linea.includes('.WARNING') || linea.includes('warning') || linea.includes('Warning') || linea.includes('omitido') || linea.includes('Omitiendo') || linea.includes('skip') || linea.includes('skipped')) {
            style = 'color: #facc15;'; // amarillo
        } else if (linea.includes('establecida con éxito') || linea.includes('éxito') || linea.includes('completada con éxito') || linea.includes('processed') || linea.includes('procesado')) {
            style = 'color: #4ade80; font-weight: 500;'; // verde
        } else if (linea.includes('[EmailPurchase]')) {
            style = 'color: #38bdf8;'; // celeste
        }

        // Resaltar marcas de tiempo
        const regexFecha = /^\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]/;
        let formateada = linea;
        if (regexFecha.test(linea)) {
            const fecha = linea.match(regexFecha)[0];
            const resto = linea.replace(fecha, '');
            formateada = `<span style="color: #64748b; font-weight: normal;">${fecha}</span><span style="${style}">${resto}</span>`;
        } else {
            formateada = `<span style="${style}">${linea}</span>`;
        }

        html += formateada + "\n";
    });

    consolePre.innerHTML = html || '<span style="color: #64748b;">(No hay líneas que coincidan con el filtro actual)</span>';
}

// Filtrar log por botones de estado rápido
function filtrarLogTipo(tipo) {
    const input = document.getElementById('inputFiltrarLogs');
    if (tipo === 'all') {
        input.value = '';
        renderizarLogs(rawLogsCache);
    } else {
        input.value = tipo;
        renderizarLogs(rawLogsCache, tipo);
    }
}
</script>
@endsection
