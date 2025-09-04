@extends('layouts/layoutMaster')

@section('title', 'Dashboard DTE')

@section('vendor-style')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
@endsection

@section('vendor-script')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endsection

@section('page-script')
<script>
$(document).ready(function() {
    // Inicializar DataTables
    $('#ultimosDteTable').DataTable({
        responsive: true,
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
        }
    });

    // Actualizar estadísticas en tiempo real
    function actualizarEstadisticas() {
        $.get('{{ route("dte.estadisticas-tiempo-real") }}', function(data) {
            $('#totalDte').text(data.estadisticas.total);
            $('#enCola').text(data.estadisticas.en_cola);
            $('#enviados').text(data.estadisticas.enviados);
            $('#rechazados').text(data.estadisticas.rechazados);
            $('#porcentajeExito').text(data.estadisticas.porcentaje_exito + '%');
            $('#erroresCriticos').text(data.errores_criticos);

            // Actualizar timestamp
            $('#lastUpdate').text(new Date().toLocaleTimeString());
        });
    }

    // Actualizar cada 30 segundos
    setInterval(actualizarEstadisticas, 30000);

    // Procesar cola
    $('#procesarCola').click(function() {
        const btn = $(this);
        const originalText = btn.text();

        btn.prop('disabled', true).text('Procesando...');

        $.post('{{ route("dte.procesar-cola") }}', {
            _token: '{{ csrf_token() }}',
            limite: $('#limiteProcesamiento').val()
        })
        .done(function(response) {
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Procesamiento completado!',
                    text: response.message,
                    timer: 3000
                });
                actualizarEstadisticas();
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
        })
        .always(function() {
            btn.prop('disabled', false).text(originalText);
        });
    });

    // Procesar reintentos
    $('#procesarReintentos').click(function() {
        const btn = $(this);
        const originalText = btn.text();

        btn.prop('disabled', true).text('Procesando...');

        $.post('{{ route("dte.procesar-reintentos") }}', {
            _token: '{{ csrf_token() }}'
        })
        .done(function(response) {
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Reintentos procesados!',
                    text: response.message,
                    timer: 3000
                });
                actualizarEstadisticas();
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
        })
        .always(function() {
            btn.prop('disabled', false).text(originalText);
        });
    });

    // Filtro por empresa
    $('#empresaFilter').change(function() {
        window.location.href = '{{ route("dte.dashboard") }}?empresa_id=' + $(this).val();
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
                    <i class="fas fa-chart-line me-2"></i>
                    Dashboard DTE
                </h4>
                <div class="d-flex gap-2">
                    <select id="empresaFilter" class="form-select" style="width: 200px;">
                        <option value="">Todas las empresas</option>
                        @foreach($empresas as $empresa)
                            <option value="{{ $empresa->id }}" {{ $empresaId == $empresa->id ? 'selected' : '' }}>
                                {{ $empresa->name }}
                            </option>
                        @endforeach
                    </select>
                    <button id="procesarCola" class="btn btn-primary">
                        <i class="fas fa-play me-1"></i>
                        Procesar Cola
                    </button>
                    <button id="procesarReintentos" class="btn btn-warning">
                        <i class="fas fa-redo me-1"></i>
                        Reintentos
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Estadísticas principales -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title text-muted">Total DTE</h6>
                            <h3 class="mb-0" id="totalDte">{{ $estadisticas['total'] }}</h3>
                        </div>
                        <div class="avatar avatar-md bg-primary rounded">
                            <i class="fas fa-file-invoice text-white"></i>
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
                            <h6 class="card-title text-muted">En Cola</h6>
                            <h3 class="mb-0 text-warning" id="enCola">{{ $estadisticas['en_cola'] }}</h3>
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
                            <h6 class="card-title text-muted">Enviados</h6>
                            <h3 class="mb-0 text-success" id="enviados">{{ $estadisticas['enviados'] }}</h3>
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
                            <h6 class="card-title text-muted">Rechazados</h6>
                            <h3 class="mb-0 text-danger" id="rechazados">{{ $estadisticas['rechazados'] }}</h3>
                        </div>
                        <div class="avatar avatar-md bg-danger rounded">
                            <i class="fas fa-times text-white"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Estadísticas secundarias -->
    <div class="row mb-4">
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Porcentaje de Éxito</h6>
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-success"
                                     style="width: {{ $estadisticas['porcentaje_exito'] }}%"></div>
                            </div>
                        </div>
                        <span class="ms-2 fw-bold" id="porcentajeExito">{{ $estadisticas['porcentaje_exito'] }}%</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Errores Críticos</h6>
                    <div class="d-flex align-items-center">
                        <i class="fas fa-exclamation-triangle text-danger me-2"></i>
                        <span class="fw-bold" id="erroresCriticos">{{ $erroresCriticos['total'] }}</span>
                        <span class="text-muted ms-2">requieren atención</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Última Actualización</h6>
                    <div class="d-flex align-items-center">
                        <i class="fas fa-clock text-info me-2"></i>
                        <span id="lastUpdate">{{ now()->format('H:i:s') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Controles de procesamiento -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-cogs me-2"></i>
                        Controles de Procesamiento
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <label for="limiteProcesamiento" class="form-label">Límite de procesamiento</label>
                            <select id="limiteProcesamiento" class="form-select">
                                <option value="5">5 documentos</option>
                                <option value="10" selected>10 documentos</option>
                                <option value="20">20 documentos</option>
                                <option value="50">50 documentos</option>
                            </select>
                        </div>
                        <div class="col-md-8 d-flex align-items-end">
                            <div class="d-flex gap-2">
                                <button id="procesarCola" class="btn btn-primary">
                                    <i class="fas fa-play me-1"></i>
                                    Procesar Cola
                                </button>
                                <button id="procesarReintentos" class="btn btn-warning">
                                    <i class="fas fa-redo me-1"></i>
                                    Procesar Reintentos
                                </button>
                                <button class="btn btn-outline-danger" disabled>
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    Ver Errores
                                </button>
                                <button class="btn btn-outline-info" disabled>
                                    <i class="fas fa-shield-alt me-1"></i>
                                    Contingencias
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Últimos DTE procesados -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-history me-2"></i>
                        Últimos DTE Procesados
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="ultimosDteTable" class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Número Control</th>
                                    <th>Tipo</th>
                                    <th>Empresa</th>
                                    <th>Estado</th>
                                    <th>Fecha</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($ultimosDte as $dte)
                                <tr>
                                    <td>{{ $dte->id }}</td>
                                    <td>{{ $dte->id_doc }}</td>
                                    <td>{{ $dte->tipoDte }}</td>
                                    <td>{{ $dte->company->name ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge bg-{{ $dte->estado_color }}">
                                            {{ $dte->estado_texto }}
                                        </span>
                                    </td>
                                    <td>{{ $dte->created_at->format('d/m/Y H:i') }}</td>
                                    <td>
                                        <a href="{{ route('dte.show', $dte->id) }}"
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
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

    <!-- Contingencias activas -->
    @if($contingenciasActivas->count() > 0)
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-shield-alt me-2"></i>
                        Contingencias Activas
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Empresa</th>
                                    <th>Tipo</th>
                                    <th>Vigencia</th>
                                    <th>Documentos</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($contingenciasActivas as $contingencia)
                                <tr>
                                    <td>{{ $contingencia->nombre ?? $contingencia->codInterno ?? 'N/A' }}</td>
                                    <td>{{ $contingencia->company->name ?? 'Empresa #' . $contingencia->company_id }}</td>
                                    <td>{{ $contingencia->tipo_texto }}</td>
                                    <td>
                                        {{ $contingencia->fecha_inicio ? $contingencia->fecha_inicio->format('d/m/Y') : 'N/A' }} -
                                        {{ $contingencia->fecha_fin ? $contingencia->fecha_fin->format('d/m/Y') : 'N/A' }}
                                    </td>
                                    <td>{{ $contingencia->documentos_afectados }}</td>
                                    <td>{!! $contingencia->estado_badge !!}</td>
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
</div>
@endsection
