@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('vendor-style')
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}">
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}">
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css') }}">
@endsection

@section('vendor-script')
<script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endsection

@section('page-script')
<script>
    $(document).ready(function() {
        // Inicializar tooltips
        $('[data-bs-toggle="tooltip"]').tooltip();
    });
</script>
@endsection

@section('title', 'Manuales - ' . $nombreModulo)

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header border-bottom">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-sm me-3">
                        <span class="avatar-initial rounded bg-label-primary">
                            <i class="ti ti-book"></i>
                        </span>
                    </div>
                    <div>
                        <h5 class="mb-1 card-title">Manuales de {{ $nombreModulo }}</h5>
                        <p class="text-muted mb-0">{{ $manuals->count() }} manual(es) disponible(s)</p>
                    </div>
                </div>
                <div class="d-flex justify-content-end mt-3">
                    <a href="{{ route('manuals.index') }}" class="btn btn-label-secondary">
                        <i class="ti ti-arrow-left me-1"></i>Volver a Todos los Manuales
                    </a>
                </div>
            </div>
            <div class="card-body">
                @if($manuals->count() > 0)
                    <div class="row">
                        @foreach($manuals as $manual)
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100 border">
                                <div class="card-body">
                                    <div class="d-flex align-items-start">
                                        <div class="avatar avatar-sm me-3">
                                            <span class="avatar-initial rounded bg-label-info">
                                                <i class="ti ti-{{ $manual->icono ?? 'file-text' }}"></i>
                                            </span>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="card-title mb-1">{{ $manual->titulo }}</h6>
                                            <p class="card-text text-muted small mb-2">
                                                {{ Str::limit($manual->descripcion, 80) }}
                                            </p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="text-muted">v{{ $manual->version }}</small>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="{{ route('manuals.show', $manual->id) }}"
                                                       class="btn btn-outline-primary btn-sm"
                                                       data-bs-toggle="tooltip"
                                                       title="Ver manual">
                                                        <i class="ti ti-eye"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer bg-transparent">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <i class="ti ti-calendar me-1"></i>
                                            {{ $manual->updated_at->format('d/m/Y') }}
                                        </small>
                                        <small class="text-muted">
                                            <i class="ti ti-hash me-1"></i>
                                            Orden: {{ $manual->orden }}
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                <div class="text-center py-5">
                    <div class="avatar avatar-xl mx-auto mb-3">
                        <span class="avatar-initial rounded bg-label-secondary">
                            <i class="ti ti-book-off"></i>
                        </span>
                    </div>
                    <h5 class="mb-2">No hay manuales para este módulo</h5>
                    <p class="text-muted mb-4">Aún no se han creado manuales para el módulo de {{ $nombreModulo }}.</p>
                    <a href="{{ route('manuals.index') }}" class="btn btn-primary">
                        <i class="ti ti-arrow-left me-1"></i>Ver Todos los Manuales
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
