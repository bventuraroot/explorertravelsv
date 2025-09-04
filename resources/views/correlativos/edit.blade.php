@extends('layouts/layoutMaster')

@section('title', 'Editar Correlativo')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <h4 class="mb-3">Editar Correlativo</h4>
  <div class="card p-3">
    <form method="POST" action="{{ route('correlativos.update', $correlativo->id) }}">
      @csrf
      @method('PUT')
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Empresa</label>
          <input type="text" class="form-control" value="{{ $correlativo->empresa->name ?? 'N/A' }}" disabled />
        </div>
        <div class="col-md-4">
          <label class="form-label">Tipo de documento</label>
          <input type="text" class="form-control" value="{{ $correlativo->tipoDocumento->description ?? 'N/A' }}" disabled />
        </div>
        <div class="col-md-4">
          <label class="form-label">Serie</label>
          <input type="text" name="serie" class="form-control" value="{{ $correlativo->serie }}" required />
        </div>
        <div class="col-md-3">
          <label class="form-label">Inicial</label>
          <input type="number" name="inicial" class="form-control" min="1" value="{{ $correlativo->inicial }}" required />
        </div>
        <div class="col-md-3">
          <label class="form-label">Final</label>
          <input type="number" name="final" class="form-control" min="1" value="{{ $correlativo->final }}" required />
        </div>
        <div class="col-md-3">
          <label class="form-label">Actual</label>
          <input type="number" name="actual" class="form-control" min="1" value="{{ $correlativo->actual }}" required />
        </div>
        <div class="col-md-3">
          <label class="form-label">Estado</label>
          <select name="estado" class="form-select">
            <option value="1" @if($correlativo->estado==1) selected @endif>Activo</option>
            <option value="0" @if($correlativo->estado==0) selected @endif>Inactivo</option>
            <option value="2" @if($correlativo->estado==2) selected @endif>Agotado</option>
            <option value="3" @if($correlativo->estado==3) selected @endif>Suspendido</option>
          </select>
        </div>
      </div>
      <div class="mt-3">
        <a href="{{ route('correlativos.index') }}" class="btn btn-secondary">Cancelar</a>
        <button class="btn btn-primary">Actualizar</button>
      </div>
    </form>
  </div>
</div>
@endsection


