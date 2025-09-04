@extends('layouts/layoutMaster')

@section('title', 'Prueba de Conectividad del Firmador')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <h4 class="mb-3">Prueba de Conectividad del Firmador</h4>
  <div class="card p-3 mb-3">
    <div><strong>URL actual:</strong> <span class="badge bg-primary" id="currentUrl">{{ $firmadorUrl ?: 'No configurada' }}</span></div>
  </div>
  <div class="card p-3">
    <div class="mb-2">
      <button class="btn btn-primary" id="btnPing">Probar Conexión</button>
      <button class="btn btn-secondary" id="btnServer">Info del Servidor</button>
      <button class="btn btn-warning" id="btnSign">Probar Firma</button>
    </div>
    <pre id="result" style="min-height:180px" class="bg-light p-3"></pre>
  </div>
</div>

<script>
document.getElementById('btnPing').addEventListener('click', async () => {
  const res = await fetch('{{ route('firmador.test-connection') }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }});
  document.getElementById('result').textContent = JSON.stringify(await res.json(), null, 2);
});
document.getElementById('btnServer').addEventListener('click', async () => {
  const res = await fetch('{{ route('firmador.server-info') }}');
  document.getElementById('result').textContent = JSON.stringify(await res.json(), null, 2);
});
document.getElementById('btnSign').addEventListener('click', async () => {
  const res = await fetch('{{ route('firmador.test-firma') }}', { method: 'POST', headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }, body: JSON.stringify({ dteJson: { ping: 'pong' } }) });
  document.getElementById('result').textContent = JSON.stringify(await res.json(), null, 2);
});
</script>
@endsection


