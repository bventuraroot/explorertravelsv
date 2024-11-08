@extends('layouts/layoutMaster')

@section('title', 'Permisos')

@section('vendor-style')
<link rel="stylesheet" href="{{asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css')}}">
<link rel="stylesheet" href="{{asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css')}}">
<link rel="stylesheet" href="{{asset('assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css')}}">
<link rel="stylesheet" href="{{asset('assets/vendor/libs/formvalidation/dist/css/formValidation.min.css')}}" />
@endsection

@section('vendor-script')
<script src="{{asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js')}}"></script>

<script src="{{asset('assets/vendor/libs/formvalidation/dist/js/FormValidation.min.js')}}"></script>
<script src="{{asset('assets/vendor/libs/formvalidation/dist/js/plugins/Bootstrap5.min.js')}}"></script>
<script src="{{asset('assets/vendor/libs/formvalidation/dist/js/plugins/AutoFocus.min.js')}}"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endsection

@section('page-script')
<script src="{{asset('assets/js/app-access-permission.js')}}"></script> 
@endsection

@section('content')
<h4 class="mb-4 fw-semibold">Lista de Permisos</h4>


<!-- Permission Table -->
<div class="card">
  <div class="card-datatable table-responsive">
    <table class="table datatables-permissions border-top">
      <thead>
        <tr>
          <th></th>
          <th>Nombre</th>
          <th>Roles</th>
          <th>Fecha de Creación</th>
          <th></th>
        </tr>
      </thead>
    </table>
  </div>
</div>
<!--/ Permission Table -->


<!-- Modal -->
@include('_partials/_modals/modal-add-permission')
@include('_partials/_modals/modal-edit-permission')
<!-- /Modal -->
@endsection
