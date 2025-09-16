@php
    $configData = Helper::appClasses();
    use Milon\Barcode\DNS1D;
@endphp

@extends('layouts/layoutMaster')

@section('vendor-style')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/formvalidation/dist/css/formValidation.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/flatpickr/flatpickr.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/bootstrap-datepicker/bootstrap-datepicker.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/bootstrap-daterangepicker/bootstrap-daterangepicker.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/jquery-timepicker/jquery-timepicker.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/pickr/pickr-themes.css') }}" />
    <style>
        .is-invalid { border-color: #dc3545 !important; box-shadow: 0 0 0 0.2rem rgba(220,53,69,.25)!important; }
        .invalid-feedback { display:block; width:100%; margin-top:.25rem; font-size:.875em; color:#dc3545; }
        .form-label .text-danger { font-weight:bold; }
        .alert { margin-bottom:1rem; }
        .alert-danger { color:#721c24; background-color:#f8d7da; border-color:#f5c6cb; }
        .alert-success { color:#155724; background-color:#d4edda; border-color:#c3e6cb; }
    </style>
@endsection

@section('vendor-script')
    <script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/select2/select2.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/formvalidation/dist/js/FormValidation.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/formvalidation/dist/js/plugins/Bootstrap5.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/formvalidation/dist/js/plugins/AutoFocus.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/cleavejs/cleave.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/cleavejs/cleave-phone.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/moment/moment.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/flatpickr/flatpickr.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/bootstrap-datepicker/bootstrap-datepicker.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/bootstrap-daterangepicker/bootstrap-daterangepicker.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/jquery-timepicker/jquery-timepicker.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/pickr/pickr.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endsection

@section('page-script')
    <script src="{{ asset('assets/js/app-product-list.js') }}"></script>
    <script src="{{ asset('assets/js/forms-product.js') }}"></script>
    <script>
      $(function(){
        // Desactivar enter en nombre (evitar submit accidental)
        $('#name, #nameedit, #codeedit').on('keydown', function(e){
          if (e.key === 'Enter') e.preventDefault();
        });
      });
    </script>
@endsection

@section('title', 'Productos')

@section('content')
    <div class="card">
        <div class="card-header border-bottom">
            <h5 class="mb-3 card-title">Productos</h5>
            <div class="gap-3 pb-2 d-flex justify-content-between align-items-center row gap-md-0">
                <div class="col-md-4 companies"></div>
            </div>
        </div>
        <div class="card-datatable table-responsive">
            <table class="table datatables-products border-top">
                <thead>
                    <tr>
                        <th>Ver</th>
                        <th>IMAGEN</th>
                        <th>CODIGO</th>
                        <th>NOMBRE</th>
                        <th>PRECIO</th>
                        <th>PROVEEDOR</th>
                        <th>C. FISCAL</th>
                        <th>TIPO</th>
                        <th>ESTADO</th>
                        <th>DESCRIPCION</th>
                        <th>ACCIONES</th>
                    </tr>
                </thead>
                <tbody>
                    @if(isset($products) && count($products))
                        @foreach($products as $product)
                            <tr>
                                <td></td>
                                <td><img src="{{ asset('assets/img/products/' . $product->image) }}" alt="image" width="150px"></td>
                                <td>{{ $product->code }}</td>
                                <td>{{ $product->name }}</td>
                                <td>$ {{ $product->price }}</td>
                                <td>{{ $product->nameprovider }}</td>
                                <td>{{ $product->cfiscal }}</td>
                                <td>{{ $product->type }}</td>
                                <td>
                                    @if($product->state == 1)
                                        <span class="badge bg-label-success">Activo</span>
                                    @else
                                        <span class="badge bg-label-danger">Inactivo</span>
                                    @endif
                                </td>
                                <td>{{ $product->description }}</td>
                                <td>
                                  <div class="d-flex align-items-center">
                                    @if(empty($isVentas))
                                      <a href="javascript: editproduct({{ $product->id }});" class="dropdown-item"><i class="ti ti-edit ti-sm me-2"></i>Editar</a>
                                      <a href="javascript:;" class="text-body dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="mx-1 ti ti-dots-vertical ti-sm"></i></a>
                                      <div class="m-0 dropdown-menu dropdown-menu-end">
                                        @if($product->state == 1)
                                          <a href="javascript:toggleState({{ $product->id }}, 0);" class="dropdown-item"><i class="ti ti-toggle-left ti-sm me-2"></i>Desactivar</a>
                                        @else
                                          <a href="javascript:toggleState({{ $product->id }}, 1);" class="dropdown-item"><i class="ti ti-toggle-right ti-sm me-2"></i>Activar</a>
                                        @endif
                                        <a href="javascript:deleteproduct({{ $product->id }});" class="dropdown-item"><i class="ti ti-eraser ti-sm me-2"></i>Eliminar</a>
                                      </div>
                                    @else
                                      <span class="text-muted">Solo lectura</span>
                                    @endif
                                  </div>
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                          <td></td>
                          <td></td>
                          <td></td>
                          <td></td>
                          <td></td>
                          <td></td>
                          <td></td>
                          <td></td>
                          <td></td>
                          <td class="text-center">No hay datos</td>
                          <td></td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <!-- Add product Modal -->
        <div class="modal fade" id="addProductModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
              <div class="p-3 modal-content p-md-5">
                <button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="Close"></button>
                <div class="modal-body">
                  <div class="mb-4 text-center">
                    <h3 class="mb-2">Crear nuevo producto</h3>
                  </div>

                  @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                  @endif
                  <form id="addproductForm" class="row" action="{{Route('product.store')}}" method="POST" enctype="multipart/form-data">
                    @csrf @method('POST')
                    <input type="hidden" name="iduser" id="iduser" value="{{Auth::user()->id}}">
                    <div class="mb-3 col-12">
                        <label class="form-label" for="code">Código <span class="text-danger">*</span></label>
                        <input type="text" id="code" name="code" class="form-control @error('code') is-invalid @enderror" placeholder="Código del producto" autofocus required value="{{ old('code') }}"/>
                        <div class="invalid-feedback">El código del producto es obligatorio</div>
                        @error('code')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3 col-12">
                      <label class="form-label" for="name">Nombre Producto <span class="text-danger">*</span></label>
                      <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" placeholder="Nombre del producto" required value="{{ old('name') }}"/>
                      <div class="invalid-feedback">El nombre del producto es obligatorio</div>
                      @error('name')
                          <div class="invalid-feedback d-block">{{ $message }}</div>
                      @enderror
                    </div>
                    <div class="mb-3 col-12">
                        <label class="form-label" for="description">Descripción <span class="text-danger">*</span></label>
                        <textarea id="description" class="form-control @error('description') is-invalid @enderror" aria-label="Descripción" name="description" rows="3" cols="46" placeholder="Descripción del producto" required>{{ old('description') }}</textarea>
                        <div class="invalid-feedback">La descripción del producto es obligatoria</div>
                        @error('description')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3 col-12">
                        <label for="provider" class="form-label">Proveedor</label>
                        <select class="select2provider form-select" id="provider" name="provider" aria-label="Seleccionar opcion"></select>
                    </div>
                    <div class="mb-3 col-6">
                        <label for="cfiscal" class="form-label">Clasificación Fiscal <span class="text-danger">*</span></label>
                        <select class="select2cfiscal form-select @error('cfiscal') is-invalid @enderror" id="cfiscal" name="cfiscal" aria-label="Seleccionar opcion" required>
                            <option value="">Seleccione</option>
                            <option value="gravado" {{ old('cfiscal') == 'gravado' ? 'selected' : '' }}>Gravado</option>
                            <option value="exento" {{ old('cfiscal') == 'exento' ? 'selected' : '' }}>Exento</option>
                        </select>
                        <div class="invalid-feedback">Debe seleccionar una clasificación fiscal</div>
                        @error('cfiscal')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3 col-6">
                        <label for="type" class="form-label">Tipo <span class="text-danger">*</span></label>
                        <select class="select2type form-select @error('type') is-invalid @enderror" id="type" name="type" aria-label="Seleccionar opcion" required>
                            <option value="">Seleccione</option>
                            <option value="directo" {{ old('type') == 'directo' ? 'selected' : '' }}>Directo</option>
                            <option value="tercero" {{ old('type') == 'tercero' ? 'selected' : '' }}>Tercero</option>
                        </select>
                        <div class="invalid-feedback">Debe seleccionar un tipo</div>
                        @error('type')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3 col-12">
                        <label class="form-label" for="price">Precio <span class="text-danger">*</span></label>
                        <input type="number" id="price" class="form-control @error('price') is-invalid @enderror" placeholder="0.00" step="0.01" min="0" aria-label="Precio $" name="price" required value="{{ old('price') }}"/>
                        <div class="invalid-feedback">El precio es obligatorio y debe ser un número válido</div>
                        @error('price')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3 col-12">
                        <label class="form-label" for="image">Imagen del Producto</label>
                        <input type="file" id="image" name="image" class="form-control" />
                    </div>
                    <div class="text-center col-12 demo-vertical-spacing">
                      <button type="submit" class="btn btn-primary me-sm-3 me-1">Crear</button>
                      <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="modal" aria-label="Close">Descartar</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          </div>

        <!-- Update product Modal -->
        <div class="modal fade" id="updateProductModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
              <div class="p-3 modal-content p-md-5">
                <button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="Close"></button>
                <div class="modal-body">
                  <div class="mb-4 text-center">
                    <h3 class="mb-2">Editar producto</h3>
                  </div>
                  <form id="editproductForm" class="row" action="{{Route('product.update')}}" method="POST" enctype="multipart/form-data">
                    @csrf @method('PATCH')
                    <input type="hidden" name="iduser" id="iduser" value="{{Auth::user()->id}}">
                    <input type="hidden" name="idedit" id="idedit">
                    <div class="mb-3 col-12">
                        <label class="form-label" for="codeedit">Código <span class="text-danger">*</span></label>
                        <input type="text" id="codeedit" name="codeedit" class="form-control" placeholder="Código del producto" autofocus required/>
                    </div>
                    <div class="mb-3 col-12">
                      <label class="form-label" for="nameedit">Nombre Producto <span class="text-danger">*</span></label>
                      <input type="text" id="nameedit" name="nameedit" class="form-control" placeholder="Nombre del producto" autofocus required/>
                    </div>
                    <div class="mb-3 col-12">
                        <label class="form-label" for="descriptionedit">Descripción <span class="text-danger">*</span></label>
                        <textarea id="descriptionedit" class="form-control" aria-label="Descripción" name="descriptionedit" rows="3" cols="46" placeholder="Descripción del producto" required></textarea>
                    </div>
                    <div class="mb-3 col-12">
                        <label for="provideredit" class="form-label">Proveedor</label>
                        <select class="select2provideredit form-select" id="provideredit" name="provideredit" aria-label="Seleccionar opcion"></select>
                    </div>
                    <div class="mb-3 col-6">
                        <label for="cfiscaledit" class="form-label">Clasificación Fiscal <span class="text-danger">*</span></label>
                        <select class="select2cfiscaledit form-select" id="cfiscaledit" name="cfiscaledit" aria-label="Seleccionar opcion" required>
                            <option value="">Seleccione</option>
                            <option value="gravado">Gravado</option>
                            <option value="exento">Exento</option>
                        </select>
                    </div>
                    <div class="mb-3 col-6">
                        <label for="typeedit" class="form-label">Tipo <span class="text-danger">*</span></label>
                        <select class="select2typeedit form-select" id="typeedit" name="typeedit" aria-label="Seleccionar opcion" required>
                            <option value="">Seleccione</option>
                            <option value="directo">Directo</option>
                            <option value="tercero">Tercero</option>
                        </select>
                    </div>
                    <div class="mb-3 col-12">
                        <label class="form-label" for="priceedit">Precio</label>
                        <input type="text" id="priceedit" class="form-control" placeholder="$" aria-label="Precio $" name="priceedit"/>
                    </div>
                    <div class="mb-3 col-12">
                        <label class="form-label" for="imageedit">Imagen del Producto</label>
                        <input type="file" id="imageedit" name="imageedit" class="form-control" />
                    </div>
                    <div class="text-center col-12 demo-vertical-spacing">
                      <button type="submit" class="btn btn-primary me-sm-3 me-1">Guardar</button>
                      <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="modal" aria-label="Close">Descartar</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          </div>
    </div>
@endsection
