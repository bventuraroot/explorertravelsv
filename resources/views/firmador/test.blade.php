@php
    $configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('vendor-style')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/formvalidation/dist/css/formValidation.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/flatpickr/flatpickr.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/bootstrap-datepicker/bootstrap-datepicker.css') }}" />
    <link rel="stylesheet"
        href="{{ asset('assets/vendor/libs/bootstrap-daterangepicker/bootstrap-daterangepicker.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/jquery-timepicker/jquery-timepicker.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/pickr/pickr-themes.css') }}" />
@endsection

@section('vendor-script')
    <script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/select2/select2.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/formvalidation/dist/js/FormValidation.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/formvalidation/dist/js/plugins/Bootstrap5.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/formvalidation/dist/js/plugins/AutoFocus.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/cleavejs/cleave.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/cleavejs/cleave-phone.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/moment/moment.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/flatpickr/flatpickr.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/bootstrap-datepicker/bootstrap-datepicker.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/bootstrap-daterangepicker/bootstrap-daterangepicker.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/jquery-timepicker/jquery-timepicker.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/pickr/pickr.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endsection

@section('title', 'Prueba de Conectividad del Firmador')

@section('content')
    <div class="card">
        <div class="card-header border-bottom">
            <h5 class="mb-3 card-title">
                <i class="fas fa-network-wired me-2"></i>
                Prueba de Conectividad del Firmador
            </h5>
            <p class="card-text text-muted">
                Herramienta para diagnosticar problemas de conexión con el servicio de firma de documentos.
                <br>
                <strong>URL actual:</strong> <span class="badge bg-primary" id="currentUrl">{{ $firmadorUrl ?? 'Cargando...' }}</span>
            </p>
        </div>
        <div class="card-body">

                    <!-- Información del Servidor y Ambientes -->
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <div class="card border-primary">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-server me-2"></i>
                                        Información del Servidor
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div id="serverInfo" class="row">
                                        <div class="col-12 text-center">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="visually-hidden">Cargando...</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-info">
                                <div class="card-header bg-info text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-cogs me-2"></i>
                                        Ambientes Disponibles
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div id="ambientesInfo">
                                        <div class="text-center">
                                            <div class="spinner-border text-info" role="status">
                                                <span class="visually-hidden">Cargando...</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pruebas de Conectividad -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card border-info">
                                <div class="card-header bg-info text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-plug me-2"></i>
                                        Prueba de Conexión Básica
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted mb-3">Prueba la conectividad básica al servicio de firma.</p>
                                    <div class="mb-3">
                                        <label for="timeout" class="form-label fw-semibold">Timeout (segundos)</label>
                                        <input type="number" class="form-control" id="timeout" value="30" min="5" max="120">
                                    </div>
                                    <button type="button" class="btn btn-info" onclick="testConnection()">
                                        <i class="fas fa-play me-2"></i>
                                        Probar Conexión
                                    </button>
                                    <div id="connectionResult" class="mt-3"></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card border-warning">
                                <div class="card-header bg-warning text-dark">
                                    <h5 class="mb-0">
                                        <i class="fas fa-signature me-2"></i>
                                        Prueba de Firma
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted mb-3">Prueba el proceso completo de firma de documentos.</p>
                                    <div class="mb-3">
                                        <label for="timeoutFirma" class="form-label fw-semibold">Timeout (segundos)</label>
                                        <input type="number" class="form-control" id="timeoutFirma" value="30" min="5" max="120">
                                    </div>
                                    <button type="button" class="btn btn-warning" onclick="testFirma()">
                                        <i class="fas fa-play me-2"></i>
                                        Probar Firma
                                    </button>
                                    <div id="firmaResult" class="mt-3"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pruebas de Red -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card border-success">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-network-wired me-2"></i>
                                        Diagnóstico de Red
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted mb-3">Pruebas detalladas de conectividad de red al servicio de firma.</p>
                                    <button type="button" class="btn btn-success" onclick="testNetwork()">
                                        <i class="fas fa-search me-2"></i>
                                        Ejecutar Diagnóstico
                                    </button>
                                    <div id="networkResult" class="mt-3"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para mostrar resultados detallados -->
<div class="modal fade" id="resultModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="resultModalTitle">
                    <i class="fas fa-info-circle me-2"></i>
                    Resultado de Prueba
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <pre id="resultModalBody" class="bg-light p-3 rounded border"></pre>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('page-script')
<script>
// Cargar información del servidor y ambientes al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    loadServerInfo();
    loadAmbientesInfo();
});

function loadServerInfo() {
    fetch('/firmador/server-info')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const info = data.server_info;
                document.getElementById('serverInfo').innerHTML = `
                    <div class="col-md-3 mb-3">
                        <div class="d-flex flex-column align-items-center">
                            <span class="badge bg-primary fs-6 mb-1">PHP Version</span>
                            <span class="fw-bold text-primary">${info.php_version}</span>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="d-flex flex-column align-items-center">
                            <span class="badge bg-success fs-6 mb-1">Laravel Version</span>
                            <span class="fw-bold text-success">${info.laravel_version}</span>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="d-flex flex-column align-items-center">
                            <span class="badge bg-info fs-6 mb-1">Server</span>
                            <span class="fw-bold text-info">${info.server_software}</span>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="d-flex flex-column align-items-center">
                            <span class="badge bg-warning fs-6 mb-1">cURL Version</span>
                            <span class="fw-bold text-warning">${info.curl_version}</span>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="d-flex flex-column align-items-center">
                            <span class="badge ${info.allow_url_fopen === 'Enabled' ? 'bg-success' : 'bg-danger'} fs-6 mb-1">allow_url_fopen</span>
                            <span class="fw-bold ${info.allow_url_fopen === 'Enabled' ? 'text-success' : 'text-danger'}">${info.allow_url_fopen}</span>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="d-flex flex-column align-items-center">
                            <span class="badge bg-secondary fs-6 mb-1">Max Execution Time</span>
                            <span class="fw-bold text-secondary">${info.max_execution_time}s</span>
                        </div>
                    </div>
                `;
            }
        })
        .catch(error => {
            document.getElementById('serverInfo').innerHTML = `
                <div class="col-12">
                    <div class="alert alert-danger border-danger">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-exclamation-triangle fa-2x text-danger me-3"></i>
                            <div>
                                <h6 class="alert-heading mb-1">Error al cargar información</h6>
                                <p class="mb-0 text-danger">${error.message}</p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
}

function loadAmbientesInfo() {
    fetch('/firmador/ambientes')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let html = '';
                data.ambientes.forEach(ambiente => {
                    const isCurrent = ambiente.url_firmador === data.current_url;
                    const badgeClass = isCurrent ? 'bg-success' : 'bg-secondary';
                    const textClass = isCurrent ? 'text-success' : 'text-muted';

                    html += `
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="badge ${badgeClass}">${ambiente.cod}</span>
                                <small class="${textClass}">${ambiente.description}</small>
                            </div>
                            <div class="small text-break ${textClass}">
                                <i class="fas fa-link me-1"></i>
                                ${ambiente.url_firmador}
                            </div>
                        </div>
                    `;
                });

                document.getElementById('ambientesInfo').innerHTML = html;

                // Actualizar la URL actual en el header
                if (data.current_url) {
                    document.getElementById('currentUrl').textContent = data.current_url;
                }
            }
        })
        .catch(error => {
            document.getElementById('ambientesInfo').innerHTML = `
                <div class="alert alert-danger border-danger">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-exclamation-triangle fa-2x text-danger me-3"></i>
                        <div>
                            <h6 class="alert-heading mb-1">Error al cargar ambientes</h6>
                            <p class="mb-0 text-danger">${error.message}</p>
                        </div>
                    </div>
                </div>
            `;
        });
}

function testConnection() {
    const timeout = document.getElementById('timeout').value;
    const resultDiv = document.getElementById('connectionResult');

    resultDiv.innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Probando...</span>
            </div>
            <p class="mt-2">Probando conexión...</p>
        </div>
    `;

    fetch('/firmador/test-connection', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ timeout: timeout })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            resultDiv.innerHTML = `
                <div class="alert alert-success border-success">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-check-circle fa-2x text-success me-3"></i>
                        <div>
                            <h6 class="alert-heading mb-1">¡Conexión exitosa!</h6>
                            <p class="mb-2 text-success">
                                <strong>Status:</strong> ${data.status_code} |
                                <strong>Tiempo:</strong> ${data.response_time_ms}ms |
                                <strong>URL:</strong> ${data.url}
                            </p>
                        </div>
                    </div>
                </div>
            `;
        } else {
            resultDiv.innerHTML = `
                <div class="alert alert-danger border-danger">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-times-circle fa-2x text-danger me-3"></i>
                        <div>
                            <h6 class="alert-heading mb-1">Error de conexión</h6>
                            <p class="mb-2 text-danger">${data.message}</p>
                            <button class="btn btn-sm btn-outline-danger" onclick="showDetailedResult('${JSON.stringify(data).replace(/'/g, "\\'")}')">
                                <i class="fas fa-eye me-1"></i>
                                Ver Detalles
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }
    })
            .catch(error => {
            resultDiv.innerHTML = `
                <div class="alert alert-danger border-danger">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-exclamation-triangle fa-2x text-danger me-3"></i>
                        <div>
                            <h6 class="alert-heading mb-1">Error en la prueba</h6>
                            <p class="mb-0 text-danger">${error.message}</p>
                        </div>
                    </div>
                </div>
            `;
        });
}

function testFirma() {
    const timeout = document.getElementById('timeoutFirma').value;
    const resultDiv = document.getElementById('firmaResult');

    resultDiv.innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-warning" role="status">
                <span class="visually-hidden">Probando...</span>
            </div>
            <p class="mt-2">Probando firma...</p>
        </div>
    `;

    fetch('/firmador/test-firma', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ timeout: timeout })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            resultDiv.innerHTML = `
                <div class="alert alert-success border-success">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-check-circle fa-2x text-success me-3"></i>
                        <div>
                            <h6 class="alert-heading mb-1">¡Prueba de firma exitosa!</h6>
                            <p class="mb-2 text-success">
                                <strong>Status:</strong> ${data.status_code} |
                                <strong>Tiempo:</strong> ${data.response_time_ms}ms
                            </p>
                            <button class="btn btn-sm btn-outline-success" onclick="showDetailedResult('${JSON.stringify(data).replace(/'/g, "\\'")}')">
                                <i class="fas fa-eye me-1"></i>
                                Ver Respuesta
                            </button>
                        </div>
                    </div>
                </div>
            `;
        } else {
            resultDiv.innerHTML = `
                <div class="alert alert-danger border-danger">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-times-circle fa-2x text-danger me-3"></i>
                        <div>
                            <h6 class="alert-heading mb-1">Error en firma</h6>
                            <p class="mb-2 text-danger">${data.message}</p>
                            <button class="btn btn-sm btn-outline-danger" onclick="showDetailedResult('${JSON.stringify(data).replace(/'/g, "\\'")}')">
                                <i class="fas fa-eye me-1"></i>
                                Ver Detalles
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }
    })
    .catch(error => {
        resultDiv.innerHTML = `
            <div class="alert alert-danger border-danger">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-triangle fa-2x text-danger me-3"></i>
                    <div>
                        <h6 class="alert-heading mb-1">Error en la prueba</h6>
                        <p class="mb-0 text-danger">${error.message}</p>
                    </div>
                </div>
            </div>
        `;
    });
}

function testNetwork() {
    const resultDiv = document.getElementById('networkResult');

    resultDiv.innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-info" role="status">
                <span class="visually-hidden">Diagnosticando...</span>
            </div>
            <p class="mt-2">Ejecutando diagnóstico de red...</p>
        </div>
    `;

    fetch('/firmador/test-network')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let html = '<div class="row">';

                                Object.keys(data.tests).forEach(testName => {
                    const test = data.tests[testName];
                    const icon = test.success ? 'fas fa-check-circle fa-2x text-success' : 'fas fa-times-circle fa-2x text-danger';
                    const alertClass = test.success ? 'alert-success border-success' : 'alert-danger border-danger';

                    html += `
                        <div class="col-md-4 mb-3">
                            <div class="alert ${alertClass}">
                                <div class="d-flex align-items-center">
                                    <i class="${icon} me-3"></i>
                                    <div>
                                        <h6 class="alert-heading mb-1">${testName.toUpperCase()}</h6>
                                        <p class="mb-0 ${test.success ? 'text-success' : 'text-danger'}">${test.message}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });

                html += '</div>';
                html += `
                    <div class="alert alert-info border-info">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-chart-bar fa-2x text-info me-3"></i>
                            <div>
                                <h6 class="alert-heading mb-1">Resumen del Diagnóstico</h6>
                                <p class="mb-0 text-info">
                                    <strong>${data.summary.passed_tests}</strong> de <strong>${data.summary.total_tests}</strong> pruebas exitosas
                                </p>
                            </div>
                        </div>
                    </div>
                `;

                resultDiv.innerHTML = html;
            }
        })
        .catch(error => {
            resultDiv.innerHTML = `
                <div class="alert alert-danger border-danger">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-exclamation-triangle fa-2x text-danger me-3"></i>
                        <div>
                            <h6 class="alert-heading mb-1">Error en el diagnóstico</h6>
                            <p class="mb-0 text-danger">${error.message}</p>
                        </div>
                    </div>
                </div>
            `;
        });
}

function showDetailedResult(data) {
    const modal = new bootstrap.Modal(document.getElementById('resultModal'));
    document.getElementById('resultModalTitle').textContent = 'Detalles del Resultado';
    document.getElementById('resultModalBody').textContent = JSON.stringify(JSON.parse(data), null, 2);
    modal.show();
}
</script>
@endsection
