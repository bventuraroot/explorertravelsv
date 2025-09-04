@extends('layouts/layoutMaster')

@section('title', 'DTE - Estadísticas')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Estadísticas de DTE</h4>
    <div>
      <form action="{{ route('dte.procesar-cola') }}" method="POST" style="display:inline-block;">
        @csrf
        <button class="btn btn-primary">Procesar cola</button>
      </form>
      <form action="{{ route('dte.procesar-reintentos') }}" method="POST" style="display:inline-block;">
        @csrf
        <button class="btn btn-warning">Procesar reintentos</button>
      </form>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  <div class="card p-3">
    <div class="row g-3">
      <div class="col-md-3"><strong>Total</strong><div>{{ $stats['total'] }}</div></div>
      <div class="col-md-3"><strong>En cola</strong><div>{{ $stats['en_cola'] }}</div></div>
      <div class="col-md-3"><strong>Enviados</strong><div>{{ $stats['enviados'] }}</div></div>
      <div class="col-md-3"><strong>Rechazados</strong><div>{{ $stats['rechazados'] }}</div></div>
      <div class="col-md-3"><strong>En revisión</strong><div>{{ $stats['en_revision'] }}</div></div>
      <div class="col-md-3"><strong>% Éxito</strong><div>{{ $stats['porcentaje_exito'] }}%</div></div>
      <div class="col-md-3"><strong>Pend. reintento</strong><div>{{ $stats['pendientes_reintento'] }}</div></div>
      <div class="col-md-3"><strong>Necesitan contingencia</strong><div>{{ $stats['necesitan_contingencia'] }}</div></div>
    </div>
  </div>
</div>
@endsection


