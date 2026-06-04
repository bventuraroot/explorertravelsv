@php
    $configData = Helper::appClasses();
    
    // Extraer totales del JSON de forma ultra-segura
    $dteTotal = 0.00;
    $dteExenta = 0.00;
    $dteGravada = 0.00;
    $dteIva = 0.00;
    $dteRetencion = 0.00;
    $dteOtros = 0.00;
    $emisorNit = '';
    $emisorNrc = '';
    $emisorNombre = '';

    if ($import->raw_json) {
        try {
            $dte = json_decode($import->raw_json, true);
            $root = isset($dte['dte']) ? $dte['dte'] : (isset($dte['DTE']) ? $dte['DTE'] : $dte);
            $resumen = $root['resumen'] ?? [];
            $dteTotal = (float) ($resumen['montoTotalOperacion'] ?? $resumen['totalPagar'] ?? 0);
            $dteExenta = (float) ($resumen['totalExenta'] ?? 0);
            $dteGravada = (float) ($resumen['totalGravada'] ?? 0);
            $dteIva = (float) ($resumen['totalIva'] ?? ($resumen['totalIva13'] ?? 0));
            
            // Retención de IVA
            $dteRetencion = (float) ($resumen['ivaRet1'] ?? 0);
            
            // Fovial / Contrans / otros tributos
            $tributos = $resumen['tributos'] ?? [];
            if (is_array($tributos)) {
                foreach ($tributos as $trib) {
                    if (isset($trib['valor'])) {
                        $dteOtros += (float)$trib['valor'];
                    }
                }
            }

            $emisor = $root['emisor'] ?? [];
            $emisorNit = $emisor['nit'] ?? '';
            $emisorNrc = $emisor['nrc'] ?? '';
            $emisorNombre = $emisor['nombre'] ?? $emisor['nombreComercial'] ?? '';
        } catch (\Throwable $e) {}
    }
@endphp

@extends(request()->has('embed') ? 'layouts/blankLayout' : 'layouts/layoutMaster')

@section('title', 'Detalle de Importación DTE')

@section('vendor-style')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github.min.css">
    <style>
        .custom-gradient-card {
            background: linear-gradient(135deg, #7367f0 0%, #a89eff 100%);
            color: #fff;
        }
        .financial-box {
            background-color: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #7367f0;
            transition: all 0.2s ease;
        }
        .financial-box:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
        }
    </style>
@endsection

@section('vendor-script')
    <script src="{{ asset('assets/vendor/libs/select2/select2.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

    {{-- Breadcrumb - Solo mostrar si no está embebido --}}
    @if(!request()->has('embed'))
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
        <div>
            <h4 class="fw-bold mb-1">
                <span class="text-muted fw-light">
                    <a href="{{ route('email-purchases.index') }}" class="text-muted">Buzón DTE</a> /
                </span>
                Detalle de Importación
            </h4>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-label-info">
                    {{ $import->dte_tipo_dte }} – {{ $import->dte_tipo_nombre ?? 'Tipo desconocido' }}
                </span>
                @php
                    $statusColor = match($import->status) {
                        'processed' => 'success',
                        'error'     => 'danger',
                        'skipped'   => 'secondary',
                        default     => 'warning',
                    };
                @endphp
                <span class="badge bg-label-{{ $statusColor }}">
                    {{ $import->status_label }}
                </span>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            @if($import->pdf_path)
                <a href="javascript:void(0);" onclick="previewPdf('{{ route('email-purchases.pdf', $import->id) }}')" class="btn btn-sm btn-outline-danger">
                    <i class="ti ti-file-analytics me-1"></i> Ver Factura PDF
                </a>
            @endif
            <a href="{{ route('email-purchases.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="ti ti-arrow-left me-1"></i> Volver al Buzón
            </a>
        </div>
    </div>
    @endif

    <div class="row g-4">

        {{-- Columna izquierda: Datos del JSON DTE e Información MH --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header border-0 bg-label-primary py-3">
                    <h5 class="card-title mb-0 text-primary fw-bold d-flex align-items-center">
                        <i class="ti ti-file-text me-2 fs-4"></i> Datos del Comprobante DTE (Hacienda)
                    </h5>
                </div>
                <div class="card-body py-3">
                    <div class="table-responsive">
                        <table class="table table-sm table-borderless align-middle mb-0">
                            <tbody>
                                <tr>
                                    <td class="text-muted small py-2" style="width: 35%;">Tipo de DTE</td>
                                    <td class="small fw-semibold text-dark py-2">
                                        {{ $import->dte_tipo_dte }} – {{ $import->dte_tipo_nombre ?? 'Desconocido' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted small py-2">Número Control</td>
                                    <td class="small font-monospace text-dark fw-medium py-2">
                                        {{ $import->dte_numero_control ?? '—' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted small py-2">Cód. Generación (UUID)</td>
                                    <td class="small font-monospace text-dark py-2" style="word-break: break-all; font-size: 0.75rem;">
                                        {{ $import->dte_codigo_generacion ?? '—' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted small py-2">Sello Recepción MH</td>
                                    <td class="small font-monospace text-dark py-2" style="word-break: break-all; font-size: 0.75rem;">
                                        {{ $import->dte_sello_recepcion ?? '—' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted small py-2">Fecha Emisión DTE</td>
                                    <td class="small text-dark py-2">
                                        {{ $import->purchase?->date ? $import->purchase->date : (isset($parsedHeader->date) ? date('d/m/Y', strtotime($parsedHeader->date)) : '—') }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted small py-2">Correo de Origen</td>
                                    <td class="small text-dark text-break py-2">{{ $import->email_from ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted small py-2">Asunto del Correo</td>
                                    <td class="small text-muted py-2">{{ $import->email_subject ?? '—' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <hr class="my-3">

                    {{-- Proveedor Emisor según DTE --}}
                    <h6 class="small text-muted text-uppercase fw-bold mb-3"><i class="ti ti-store me-1 text-secondary"></i>Emisor según XML / JSON DTE</h6>
                    <div class="p-3 bg-light rounded mb-3">
                        <dl class="row mb-0">
                            <dt class="col-4 small text-muted">Razón Social</dt>
                            <dd class="col-8 small fw-semibold text-dark">{{ $emisorNombre ?: '—' }}</dd>
                            
                            <dt class="col-4 small text-muted">NIT</dt>
                            <dd class="col-8 small font-monospace text-dark">{{ $emisorNit ?: '—' }}</dd>
                            
                            <dt class="col-4 small text-muted">NRC</dt>
                            <dd class="col-8 small font-monospace text-dark">{{ $emisorNrc ?: '—' }}</dd>
                        </dl>
                    </div>

                    {{-- Totales Financieros extraídos --}}
                    <h6 class="small text-muted text-uppercase fw-bold mb-3"><i class="ti ti-cash me-1 text-success"></i>Resumen Financiero del Comprobante</h6>
                    <div class="row g-2 mb-2">
                        <div class="col-6 col-md-4">
                            <div class="p-2 financial-box">
                                <div class="small text-muted mb-0">Exento</div>
                                <div class="fw-bold text-dark">${{ number_format($dteExenta, 2) }}</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-4">
                            <div class="p-2 financial-box">
                                <div class="small text-muted mb-0">Gravado</div>
                                <div class="fw-bold text-dark">${{ number_format($dteGravada, 2) }}</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-4">
                            <div class="p-2 financial-box">
                                <div class="small text-muted mb-0">IVA (13%)</div>
                                <div class="fw-bold text-dark">${{ number_format($dteIva, 2) }}</div>
                            </div>
                        </div>
                        @if($dteRetencion > 0)
                        <div class="col-6 col-md-4">
                            <div class="p-2 financial-box" style="border-left-color: #ea5455;">
                                <div class="small text-muted mb-0">Ret. IVA (1%)</div>
                                <div class="fw-bold text-dark">${{ number_format($dteRetencion, 2) }}</div>
                            </div>
                        </div>
                        @endif
                        @if($dteOtros > 0)
                        <div class="col-6 col-md-4">
                            <div class="p-2 financial-box" style="border-left-color: #00cfc5;">
                                <div class="small text-muted mb-0">Otros Tributos</div>
                                <div class="fw-bold text-dark">${{ number_format($dteOtros, 2) }}</div>
                            </div>
                        </div>
                        @endif
                        <div class="col-12 mt-2">
                            <div class="p-3 custom-gradient-card rounded shadow-sm">
                                <div class="small mb-1 opacity-75 fw-medium">TOTAL A PAGAR MH (DTE)</div>
                                <div class="fw-bold fs-4">${{ number_format($dteTotal, 2) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Columna derecha: Mapeo y Confirmación de Compra --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header border-0 bg-label-success py-3">
                    <h5 class="card-title mb-0 text-success fw-bold d-flex align-items-center">
                        <i class="ti ti-link me-2 fs-4"></i> Integración al Sistema (Registro de Compra)
                    </h5>
                </div>
                <div class="card-body py-3 d-flex flex-column justify-content-between">
                    
                    <div>
                        {{-- Caso 1: Compra ya Procesada --}}
                        @if($import->status === 'processed' && $import->purchase)
                            <div class="alert alert-success border-0 shadow-xs mb-0 py-3" role="alert">
                                <div class="d-flex align-items-start">
                                    <i class="ti ti-circle-check fs-2 me-3 text-success"></i>
                                    <div>
                                        <h5 class="alert-heading fw-bold mb-1">¡Completamente Procesado!</h5>
                                        <p class="mb-2 small">Esta factura DTE ya fue correctamente ingresada y registrada en el módulo de compras.</p>
                                        <hr class="my-2 text-success opacity-25">
                                        <dl class="row mb-0 small">
                                            <dt class="col-5 text-muted">ID Compra Interna</dt>
                                            <dd class="col-7 font-monospace fw-bold">#{{ $import->purchase->id }}</dd>
                                            
                                            <dt class="col-5 text-muted">Número Comprobante</dt>
                                            <dd class="col-7 font-monospace fw-bold">{{ $import->purchase->number }}</dd>
                                            
                                            <dt class="col-5 text-muted">Empresa Registrada</dt>
                                            <dd class="col-7">{{ $import->company->name }}</dd>
                                            
                                            <dt class="col-5 text-muted">Proveedor Asignado</dt>
                                            <dd class="col-7">{{ $import->purchase->provider->razonsocial }}</dd>
                                            
                                            <dt class="col-5 text-muted">Fecha Compra</dt>
                                            <dd class="col-7">{{ date('d/m/Y', strtotime($import->purchase->date)) }}</dd>
                                        </dl>
                                        <div class="mt-3">
                                            <a href="{{ route('purchase.index') }}" class="btn btn-success btn-sm w-100 shadow-xs">
                                                <i class="ti ti-file-text me-1"></i> Ver en listado de Compras
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @else
                            {{-- Caso 2: Importación con Error --}}
                            @if($import->status === 'error')
                                <div class="alert alert-danger border-0 shadow-xs mb-3 py-2 small" role="alert">
                                    <i class="ti ti-alert-triangle me-2 text-danger"></i>
                                    <strong>Error en importación previa:</strong> {{ $import->error_message }}
                                </div>
                            @endif

                            {{-- Formulario de Mapeo Manual --}}
                            <div class="bg-label-warning rounded p-3 mb-3 py-2 small text-warning border-0">
                                <i class="ti ti-info-circle me-1"></i>
                                <strong>Acción Requerida:</strong> 
                                Por favor asocia y confirma la empresa receptora y el proveedor local para crear automáticamente la compra en el sistema.
                            </div>

                            <form id="formConfirmManual" class="row g-3">
                                @csrf
                                <div class="col-12">
                                    <label class="form-label small fw-semibold text-dark" for="company_id">
                                        1. Empresa Receptora <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select select2-setup" id="company_id" name="company_id" required>
                                        <option value="">— Seleccionar Empresa Local —</option>
                                        @foreach($companies as $company)
                                            <option value="{{ $company->id }}" @selected($import->company_id == $company->id)>
                                                {{ $company->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <span class="text-muted small d-block mt-1">Nuestra empresa que registrará el gasto fiscal.</span>
                                </div>

                                <div class="col-12">
                                    <label class="form-label small fw-semibold text-dark" for="provider_id">
                                        2. Proveedor Asociado <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select select2-setup" id="provider_id" name="provider_id" required>
                                        <option value="">— Seleccionar Proveedor Local —</option>
                                        @foreach($providers as $prov)
                                            @php
                                                // Intentar auto-emparejar por NIT si coincide
                                                $cleanedDteNit = preg_replace('/[^0-9]/', '', $emisorNit);
                                                $cleanedProvNit = preg_replace('/[^0-9]/', '', $prov->nit);
                                                $isMatched = ($cleanedDteNit && $cleanedProvNit && $cleanedDteNit === $cleanedProvNit);
                                                
                                                // O si ya está pre-cargado
                                                $isPreselected = (isset($parsedHeader->provider_id) && $parsedHeader->provider_id == $prov->id);
                                            @endphp
                                            <option value="{{ $prov->id }}" @selected($isMatched || $isPreselected)>
                                                {{ $prov->razonsocial }} (NIT: {{ $prov->nit ?? 'S/N' }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <span class="text-muted small d-block mt-1">El proveedor emisor local. Si no existe, debes crearlo primero en el módulo de Proveedores.</span>
                                </div>
                            </form>
                        @endif
                    </div>

                    {{-- Acciones del pie en Borrador/Pendiente --}}
                    @if($import->status !== 'processed')
                        <div class="mt-4 pt-3 border-top d-flex gap-2">
                            @if($import->pdf_path)
                                <a href="javascript:void(0);" onclick="previewPdf('{{ route('email-purchases.pdf', $import->id) }}')" class="btn btn-outline-danger w-50 shadow-xs">
                                    <i class="ti ti-file-analytics me-1"></i> Ver PDF original
                                </a>
                            @endif
                            <button id="btnConfirmarManual" type="button" class="btn btn-success w-100 shadow-xs">
                                <i class="ti ti-checks me-1"></i> Confirmar y Registrar Compra
                            </button>
                        </div>
                    @endif

                </div>
            </div>
        </div>

    </div>

    {{-- Estructura de JSON Original --}}
    @if($import->raw_json)
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header border-0 d-flex align-items-center justify-content-between py-3 bg-light">
            <h6 class="card-title mb-0 fw-bold text-secondary d-flex align-items-center">
                <i class="ti ti-code me-2"></i> Estructura del JSON DTE Original (Hacienda)
            </h6>
            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#jsonBlock">
                Ver / Ocultar Código JSON
            </button>
        </div>
        <div class="collapse" id="jsonBlock">
            <div class="card-body p-0">
                <pre class="m-0 p-3" style="max-height: 450px; overflow: auto; font-size: 0.75rem;"><code class="language-json" id="jsonContent">{{ json_encode(json_decode($import->raw_json), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</code></pre>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal de PDF --}}
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
                    <div id="pdfLoadingSpinner" class="position-absolute top-50 start-50 translate-middle d-flex flex-column align-items-center" style="z-index: 10;">
                        <div class="spinner-border text-danger" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <span class="text-muted small mt-2">Cargando documento PDF...</span>
                    </div>
                    <embed id="pdfPreviewIframe" src="" type="application/pdf" class="w-100 border-0 d-none" style="height: 700px; max-height: 80vh;"></embed>
                </div>
                <div class="modal-footer border-top bg-light d-flex justify-content-between">
                    <a id="btnPdfDownload" href="" target="_blank" class="btn btn-outline-secondary shadow-xs">
                        <i class="ti ti-download me-1"></i>Descargar / Abrir en pestaña
                    </a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@section('page-script')
<script>
$(document).ready(function() {
    // Inicializar Select2 en los selectores del formulario
    $('.select2-setup').each(function() {
        var $this = $(this);
        $this.wrap('<div class="position-relative"></div>').select2({
            placeholder: $this.attr('placeholder') || '— Seleccionar —',
            allowClear: false,
            dropdownParent: $this.parent(),
            width: '100%'
        });
    });

    // Resaltar sintaxis de JSON
    if (document.getElementById('jsonContent')) {
        hljs.highlightElement(document.getElementById('jsonContent'));
    }

    // Confirmar mapeo manual
    $('#btnConfirmarManual').on('click', function(e) {
        e.preventDefault();

        const companyId = $('#company_id').val();
        const providerId = $('#provider_id').val();

        if (!companyId) {
            Swal.fire({ icon: 'warning', title: 'Empresa Requerida', text: 'Debes seleccionar la empresa receptora local.' });
            return;
        }
        if (!providerId) {
            Swal.fire({ icon: 'warning', title: 'Proveedor Requerido', text: 'Debes seleccionar el proveedor local asociado.' });
            return;
        }

        Swal.fire({
            title: '¿Confirmar e Ingresar Compra?',
            text: 'Se creará el registro oficial de la compra con los montos exactos de este DTE.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, registrar compra',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#7367f0',
            cancelButtonColor: '#a8aaae',
        }).then((result) => {
            if (result.isConfirmed) {
                const btn = $('#btnConfirmarManual');
                btn.prop('disabled', true);
                btn.html('<span class="spinner-border spinner-border-sm me-1" role="status"></span> Registrando...');

                fetch('{{ route("email-purchases.confirm", $import->id) }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        company_id: companyId,
                        provider_id: providerId
                    })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Compra Registrada Exitosamente!',
                            text: 'El registro de compra manual ha sido creado y vinculado al DTE.',
                            confirmButtonColor: '#7367f0',
                        }).then(() => {
                            // Si está embebido en modal iframe, cerrar modal del padre e instruir recarga
                            if (window.parent && window.parent.document.getElementById('viewDteModal')) {
                                window.parent.location.reload();
                            } else {
                                window.location.reload();
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error al registrar',
                            text: data.message || 'Ocurrió un error inesperado al procesar.',
                            confirmButtonColor: '#ea5455',
                        });
                        btn.prop('disabled', false);
                        btn.html('<i class="ti ti-checks me-1"></i> Confirmar y Registrar Compra');
                    }
                })
                .catch(() => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de Red',
                        text: 'No se pudo conectar con el servidor. Reintenta.',
                        confirmButtonColor: '#ea5455',
                    });
                    btn.prop('disabled', false);
                    btn.html('<i class="ti ti-checks me-1"></i> Confirmar y Registrar Compra');
                });
            }
        });
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
</script>
@endsection
