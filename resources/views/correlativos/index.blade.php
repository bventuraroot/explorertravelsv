@extends('layouts/layoutMaster')

@section('title', 'Gestión de Correlativos')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Gestión de Correlativos</h4>
    <div>
      <a href="{{ route('correlativos.create') }}" class="btn btn-success">Nuevo Correlativo</a>
      <a href="{{ route('correlativos.estadisticas') }}" class="btn btn-info">Estadísticas</a>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
  @endif

  <div class="card">
    <div class="table-responsive">
      <table class="table table-striped">
        <thead>
          <tr>
            <th>ID</th>
            <th>Empresa</th>
            <th>Tipo documento</th>
            <th>Serie</th>
            <th>Rango</th>
            <th>Actual</th>
            <th>Restantes</th>
            <th>Estado</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
        @forelse($correlativos as $correlativo)
          <tr>
            <td>{{ $correlativo->id }}</td>
            <td>{{ $correlativo->empresa->name ?? 'N/A' }}</td>
            <td>{{ $correlativo->tipoDocumento->description ?? 'N/A' }}</td>
            <td>{{ $correlativo->serie }}</td>
            <td>{{ number_format($correlativo->inicial) }} - {{ number_format($correlativo->final) }}</td>
            <td><strong>{{ number_format($correlativo->actual) }}</strong></td>
            <td>{{ number_format($correlativo->numerosRestantes()) }}</td>
            <td>{!! $correlativo->estado_badge !!}</td>
            <td class="text-end">
              <a href="{{ route('correlativos.show', $correlativo->id) }}" class="btn btn-sm btn-primary">Ver</a>
              <a href="{{ route('correlativos.edit', $correlativo->id) }}" class="btn btn-sm btn-warning">Editar</a>
              <form action="{{ route('correlativos.destroy', $correlativo->id) }}" method="POST" style="display:inline-block">
                @csrf
                @method('DELETE')
                <button class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar correlativo?')">Eliminar</button>
              </form>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="9" class="text-center">Sin correlativos</td>
          </tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection


