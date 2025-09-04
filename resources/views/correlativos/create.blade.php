@extends('layouts/layoutMaster')

@section('title', 'Nuevo Correlativo')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <h4 class="mb-3">Nuevo Correlativo</h4>
  <div class="card p-3">
    <form method="POST" action="{{ route('correlativos.store') }}">
      @csrf
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Empresa</label>
          <select name="id_empresa" class="form-select" required>
            @foreach($empresas as $empresa)
              <option value="{{ $empresa->id }}">{{ $empresa->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Tipo de documento</label>
          <select name="id_tipo_doc" class="form-select" required>
            @foreach($tiposDocumento as $tipo)
              <option value="{{ $tipo->type }}">{{ $tipo->description }} ({{ $tipo->codemh }})</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Serie</label>
          <input type="text" name="serie" class="form-control" required />
        </div>
        <div class="col-md-3">
          <label class="form-label">Inicial</label>
          <input type="number" name="inicial" class="form-control" min="1" required />
        </div>
        <div class="col-md-3">
          <label class="form-label">Final</label>
          <input type="number" name="final" class="form-control" min="1" required />
        </div>
        <div class="col-md-3">
          <label class="form-label">Actual</label>
          <input type="number" name="actual" class="form-control" min="1" required />
        </div>
        <div class="col-md-3">
          <label class="form-label">Estado</label>
          <select name="estado" class="form-select">
            <option value="1">Activo</option>
            <option value="0">Inactivo</option>
          </select>
        </div>
      </div>
      <div class="mt-3">
        <a href="{{ route('correlativos.index') }}" class="btn btn-secondary">Cancelar</a>
        <button class="btn btn-primary">Guardar</button>
      </div>
    </form>
  </div>
</div>
@endsection


