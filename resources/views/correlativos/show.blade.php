@extends('layouts/layoutMaster')

@section('title', 'Detalle de Correlativo')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Correlativo #{{ $correlativo->id }}</h4>
    <a href="{{ route('correlativos.index') }}" class="btn btn-secondary">Volver</a>
  </div>

  <div class="card p-3">
    <dl class="row mb-0">
      <dt class="col-sm-3">Empresa</dt>
      <dd class="col-sm-9">{{ $correlativo->empresa->name ?? 'N/A' }}</dd>
      <dt class="col-sm-3">Tipo documento</dt>
      <dd class="col-sm-9">{{ $correlativo->tipoDocumento->description ?? 'N/A' }}</dd>
      <dt class="col-sm-3">Serie</dt>
      <dd class="col-sm-9">{{ $correlativo->serie }}</dd>
      <dt class="col-sm-3">Rango</dt>
      <dd class="col-sm-9">{{ number_format($correlativo->inicial) }} - {{ number_format($correlativo->final) }}</dd>
      <dt class="col-sm-3">Actual</dt>
      <dd class="col-sm-9">{{ number_format($correlativo->actual) }}</dd>
      <dt class="col-sm-3">Restantes</dt>
      <dd class="col-sm-9">{{ number_format($correlativo->numerosRestantes()) }}</dd>
      <dt class="col-sm-3">Estado</dt>
      <dd class="col-sm-9">{!! $correlativo->estado_badge !!}</dd>
    </dl>
  </div>
</div>
@endsection


