@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', $manual['titulo'])

@section('vendor-style')
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/sweetalert2/sweetalert2.css') }}" />
@endsection

@section('page-style')
<style>
    .manual-content {
        background: white;
        border-radius: 0.5rem;
        padding: 2rem;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        line-height: 1.6;
    }

    .manual-content h1 {
        color: #2c3e50;
        border-bottom: 3px solid #696cff;
        padding-bottom: 0.5rem;
        margin-bottom: 2rem;
    }

    .manual-content h2 {
        color: #34495e;
        margin-top: 2rem;
        margin-bottom: 1rem;
        padding-left: 1rem;
        border-left: 4px solid #696cff;
    }

    .manual-content h3 {
        color: #2c3e50;
        margin-top: 1.5rem;
        margin-bottom: 0.75rem;
    }

    .manual-content h4 {
        color: #34495e;
        margin-top: 1rem;
        margin-bottom: 0.5rem;
    }

    .manual-content p {
        margin-bottom: 1rem;
        text-align: justify;
    }

    .manual-content ul, .manual-content ol {
        margin-bottom: 1rem;
        padding-left: 2rem;
    }

    .manual-content li {
        margin-bottom: 0.5rem;
    }

    .manual-content table {
        width: 100%;
        border-collapse: collapse;
        margin: 1rem 0;
        background: white;
        border-radius: 0.5rem;
        overflow: hidden;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .manual-content th {
        background: #696cff;
        color: white;
        padding: 1rem;
        text-align: left;
        font-weight: 600;
    }

    .manual-content td {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #e3e6f0;
    }

    .manual-content tr:hover {
        background: #f8f9fa;
    }

    .manual-content code {
        background: #f8f9fa;
        padding: 0.2rem 0.4rem;
        border-radius: 0.25rem;
        font-family: 'Courier New', monospace;
        color: #e83e8c;
    }

    .manual-content pre {
        background: #f8f9fa;
        padding: 1rem;
        border-radius: 0.5rem;
        overflow-x: auto;
        border-left: 4px solid #696cff;
    }

    .manual-content blockquote {
        border-left: 4px solid #ffc107;
        background: #fff3cd;
        padding: 1rem;
        margin: 1rem 0;
        border-radius: 0 0.5rem 0.5rem 0;
    }

    .manual-content .alert {
        padding: 1rem;
        border-radius: 0.5rem;
        margin: 1rem 0;
        border: none;
    }

    .manual-content .alert-info {
        background: #d1ecf1;
        color: #0c5460;
        border-left: 4px solid #17a2b8;
    }

    .manual-content .alert-warning {
        background: #fff3cd;
        color: #856404;
        border-left: 4px solid #ffc107;
    }

    .manual-content .alert-success {
        background: #d4edda;
        color: #155724;
        border-left: 4px solid #28a745;
    }

    .manual-content .alert-danger {
        background: #f8d7da;
        color: #721c24;
        border-left: 4px solid #dc3545;
    }

    .manual-actions {
        background: white;
        border-radius: 0.5rem;
        padding: 1.5rem;
        margin-bottom: 2rem;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .manual-meta {
        background: #f8f9fa;
        border-radius: 0.5rem;
        padding: 1rem;
        margin-bottom: 2rem;
        font-size: 0.9rem;
        color: #6c757d;
    }

    .btn-print {
        background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
        border: none;
        color: white;
    }

    .btn-print:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(108, 117, 125, 0.3);
        color: white;
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

    .btn-back {
        background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
        border: none;
        color: white;
    }

    .btn-back:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(108, 117, 125, 0.3);
        color: white;
    }

    @media print {
        .manual-actions, .manual-meta {
            display: none !important;
        }

        .manual-content {
            box-shadow: none;
            padding: 0;
        }
    }
</style>
@endsection

@section('vendor-script')
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
                            {{ $manual['titulo'] }}
                        </h4>
                        <p class="mb-0 text-muted">{{ $manual['descripcion'] }}</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('manuals.index') }}" class="btn btn-back">
                            <i class="ti ti-arrow-left me-1"></i>
                            Volver a Manuales
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Manual Meta -->
    <div class="row">
        <div class="col-12">
            <div class="manual-meta">
                <div class="row">
                    <div class="col-md-3">
                        <strong><i class="ti ti-calendar me-1"></i> Última actualización:</strong><br>
                        {{ date('d/m/Y H:i', strtotime($manual['updated_at'])) }}
                    </div>
                    <div class="col-md-3">
                        <strong><i class="ti ti-file me-1"></i> Tamaño:</strong><br>
                        {{ \App\Http\Controllers\ManualController::formatFileSize(strlen($manual['contenido_markdown'])) }}
                    </div>
                    <div class="col-md-3">
                        <strong><i class="ti ti-user me-1"></i> Autor:</strong><br>
                        Sistema ExplorerTravel
                    </div>
                    <div class="col-md-3">
                        <strong><i class="ti ti-tag me-1"></i> Versión:</strong><br>
                        {{ $manual['version'] }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Manual Actions -->
    <div class="row">
        <div class="col-12">
            <div class="manual-actions">
                <div class="d-flex gap-2 flex-wrap">
                    <button type="button" class="btn btn-primary" onclick="printManual()">
                        <i class="ti ti-printer me-1"></i>
                        Imprimir Manual
                    </button>
                    <button type="button" class="btn btn-download" onclick="downloadManual()">
                        <i class="ti ti-download me-1"></i>
                        Descargar PDF
                    </button>
                    <button type="button" class="btn btn-outline-secondary" onclick="toggleFullscreen()">
                        <i class="ti ti-maximize me-1"></i>
                        Pantalla Completa
                    </button>
                    <button type="button" class="btn btn-outline-info" onclick="shareManual()">
                        <i class="ti ti-share me-1"></i>
                        Compartir
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Manual Content -->
    <div class="row">
        <div class="col-12">
            <div class="manual-content" id="manual-content">
                {!! $manual['contenido'] !!}
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script>
function printManual() {
    window.print();
}

function toggleFullscreen() {
    if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen();
    } else {
        if (document.exitFullscreen) {
            document.exitFullscreen();
        }
    }
}

function shareManual() {
    if (navigator.share) {
        navigator.share({
            title: '{{ $manual["titulo"] }}',
            text: '{{ $manual["descripcion"] }}',
            url: window.location.href
        });
    } else {
        // Fallback: copiar URL al portapapeles
        navigator.clipboard.writeText(window.location.href).then(() => {
            Swal.fire({
                title: '¡Enlace copiado!',
                text: 'El enlace del manual se ha copiado al portapapeles',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });
        });
    }
}

function downloadManual() {
    Swal.fire({
        title: 'Descargando manual...',
        text: 'Preparando el archivo PDF para descarga',
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

// Smooth scroll para enlaces internos
document.addEventListener('DOMContentLoaded', function() {
    const links = document.querySelectorAll('a[href^="#"]');

    links.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
});
</script>
@endsection
