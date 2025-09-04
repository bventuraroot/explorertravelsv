@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('vendor-style')
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/formvalidation/dist/css/formValidation.min.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/quill/editor.css') }}" />
@endsection

@section('vendor-script')
<script src="{{ asset('assets/vendor/libs/select2/select2.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/formvalidation/dist/js/FormValidation.min.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/formvalidation/dist/js/plugins/Bootstrap5.min.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/formvalidation/dist/js/plugins/AutoFocus.min.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/quill/katex.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/quill/quill.js') }}"></script>
@endsection

@section('page-script')
<script>
$(document).ready(function() {
    // Inicializar editor de texto enriquecido
    const quill = new Quill('#contenido', {
        theme: 'snow',
        modules: {
            toolbar: [
                [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ 'color': [] }, { 'background': [] }],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                [{ 'indent': '-1'}, { 'indent': '+1' }],
                [{ 'align': [] }],
                ['link', 'image'],
                ['clean']
            ]
        }
    });

    // Sincronizar contenido del editor con el textarea oculto
    quill.on('text-change', function() {
        document.getElementById('contenido_text').value = quill.root.innerHTML;
    });

    // Inicializar Select2
    $('.select2').select2({
        placeholder: 'Seleccione una opción',
        allowClear: true
    });

    // Validación del formulario
    const form = document.getElementById('manualForm');
    const fv = FormValidation.formValidation(form, {
        fields: {
            titulo: {
                validators: {
                    notEmpty: {
                        message: 'El título es requerido'
                    },
                    stringLength: {
                        min: 3,
                        max: 255,
                        message: 'El título debe tener entre 3 y 255 caracteres'
                    }
                }
            },
            modulo: {
                validators: {
                    notEmpty: {
                        message: 'El módulo es requerido'
                    }
                }
            },
            contenido: {
                validators: {
                    notEmpty: {
                        message: 'El contenido es requerido'
                    }
                }
            },
            version: {
                validators: {
                    notEmpty: {
                        message: 'La versión es requerida'
                    },
                    regexp: {
                        regexp: /^[0-9]+\.[0-9]+$/,
                        message: 'La versión debe tener el formato X.X (ej: 1.0)'
                    }
                }
            },
            orden: {
                validators: {
                    notEmpty: {
                        message: 'El orden es requerido'
                    },
                    integer: {
                        message: 'El orden debe ser un número entero'
                    },
                    between: {
                        min: 0,
                        max: 999,
                        message: 'El orden debe estar entre 0 y 999'
                    }
                }
            }
        },
        plugins: {
            trigger: new FormValidation.plugins.Trigger(),
            bootstrap5: new FormValidation.plugins.Bootstrap5({
                eleValidClass: '',
                rowSelector: '.mb-3'
            }),
            submitButton: new FormValidation.plugins.SubmitButton(),
            autoFocus: new FormValidation.plugins.AutoFocus()
        }
    });
});
</script>
@endsection

@section('title', 'Crear Manual')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header border-bottom">
                <h5 class="mb-3 card-title">
                    <i class="ti ti-plus me-2"></i>Crear Nuevo Manual
                </h5>
                <p class="text-muted mb-0">Complete la información para crear un nuevo manual de usuario</p>
            </div>
            <div class="card-body">
                <form id="manualForm" action="{{ route('manuals.store') }}" method="POST">
                    @csrf

                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="titulo" class="form-label">Título del Manual <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="titulo" name="titulo"
                                       placeholder="Ej: Cómo crear un nuevo cliente"
                                       value="{{ old('titulo') }}" required>
                                @error('titulo')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="modulo" class="form-label">Módulo <span class="text-danger">*</span></label>
                                <select class="form-select select2" id="modulo" name="modulo" required>
                                    <option value="">Seleccione un módulo</option>
                                    @foreach($modulos as $key => $value)
                                        <option value="{{ $key }}" {{ old('modulo') == $key ? 'selected' : '' }}>
                                            {{ $value }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('modulo')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3"
                                  placeholder="Breve descripción del manual...">{{ old('descripcion') }}</textarea>
                        @error('descripcion')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="contenido" class="form-label">Contenido del Manual <span class="text-danger">*</span></label>
                        <div id="contenido" style="height: 400px;"></div>
                        <textarea id="contenido_text" name="contenido" style="display: none;">{{ old('contenido') }}</textarea>
                        @error('contenido')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="version" class="form-label">Versión <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="version" name="version"
                                       placeholder="1.0" value="{{ old('version', '1.0') }}" required>
                                @error('version')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="orden" class="form-label">Orden <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="orden" name="orden"
                                       placeholder="0" value="{{ old('orden', 0) }}" min="0" max="999" required>
                                @error('orden')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="icono" class="form-label">Icono</label>
                                <input type="text" class="form-control" id="icono" name="icono"
                                       placeholder="Ej: file-text, book, user" value="{{ old('icono') }}">
                                <div class="form-text">Nombre del icono de Tabler Icons (sin el prefijo "ti ti-")</div>
                                @error('icono')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="activo" name="activo" value="1"
                                   {{ old('activo', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="activo">
                                Manual activo
                            </label>
                        </div>
                        <div class="form-text">Los manuales inactivos no se mostrarán en la lista principal</div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('manuals.index') }}" class="btn btn-label-secondary">
                            <i class="ti ti-x me-1"></i>Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-check me-1"></i>Crear Manual
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
