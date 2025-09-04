@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Perfil de Usuario')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0 card-title">Perfil de Usuario</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-4 card">
                                <div class="card-header">
                                    <h5 class="mb-0 card-title">Información del Perfil</h5>
                                </div>
                                <div class="card-body">
                                    @include('profile.partials.update-profile-information-form')
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-4 card">
                                <div class="card-header">
                                    <h5 class="mb-0 card-title">Cambiar Contraseña</h5>
                                </div>
                                <div class="card-body">
                                    @include('profile.partials.update-password-form')
                                </div>
                            </div>
                        </div>
                    </div>
                    <!--<div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0 card-title">Eliminar Cuenta</h5>
                                </div>
                                <div class="card-body">
                                    @include('profile.partials.delete-user-form')
                                </div>
                            </div>
                        </div>
                    </div>-->
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
