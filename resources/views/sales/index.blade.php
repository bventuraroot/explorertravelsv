@php
    $configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('vendor-style')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/formvalidation/dist/css/formValidation.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/flatpickr/flatpickr.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/bootstrap-datepicker/bootstrap-datepicker.css') }}" />
    <link rel="stylesheet"
        href="{{ asset('assets/vendor/libs/bootstrap-daterangepicker/bootstrap-daterangepicker.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/jquery-timepicker/jquery-timepicker.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/pickr/pickr-themes.css') }}" />
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
    <script src="{{ asset('assets/js/app-sale-list.js') }}"></script>
    <script>
        function EnviarCorreo(id_factura,correo,numero) {
            (async () => {
              //const csrfToken = document.head.querySelector('meta[name="csrf-token"]').content;
                _token = '{{ csrf_token() }}';
                //alert(_token);
                const { value: email } = await Swal.fire({
                    title: 'Mandar comprobante por Correo',
                    input: 'email',
                    inputLabel: 'Correo a Enviar',
                    inputPlaceholder: 'Introduzca el Correo',
                    inputValue: correo
                });
                url = "{{ route('sale.envia_correo') }}";
                if (email) {
                    $.ajax({
                    url: url,
                    type:'GET',
                    data: {
                    id_factura : id_factura,
                    email: email,
                    numero: numero,
                    _token : _token
        
                    },
                    success: function(data,status)
                    {
                        Swal.fire(`Comprobante Enviado a: ${email}`)
        
                    },
                    error: function(){
                    mensaje("Creación de Factura", "No se pudo Actualizar", "error")
                    },
                    });
                }
            })()
        }
        
            </script>
@endsection

@section('title', 'Ventas')

@section('content')
    <div class="card">
        <div class="card-header border-bottom">
            <h5 class="mb-3 card-title">Ventas</h5>
            <div class="gap-3 pb-2 d-flex justify-content-between align-items-center row gap-md-0">
                <div class="col-md-4 companies"></div>
            </div>
        </div>
        <div class="card-datatable table-responsive">
            <table class="table datatables-sale border-top nowrap">
                <thead>
                    <tr>
                        <th>Ver</th>
                        <th>CORRELATIVO</th>
                        <th>FECHA</th>
                        <th>TIPO</th>
                        <th>CLIENTE</th>
                        <th>TOTAL</th>
                        <th>ESTADO</th>
                        <th>FORMA DE PAGO</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @isset($sales)
                        @forelse($sales as $sale)
                            <tr>
                                <td></td>
                                @if ($sale->estadoHacienda=='PROCESADO')
                                <td>{{ $sale->id_doc }}</td>
                                @else
                                <td>{{ $sale->id }}</td>
                                @endif

                                <td>{{ \Carbon\Carbon::parse($sale->date)->format('d/m/Y') }}</td>
                                <td>{{ $sale->document_name }}</td>
                                <td>
                                    @switch($sale->tpersona)
                                        @case('N')
                                    {{$sale->firstname . ' ' . $sale->firstlastname}}
                                            @break
                                        @case('J')
                                    {{substr($sale->nameClient,0,30)}}
                                        @break

                                        @default

                                    @endswitch
                                </td>
                                <td>$ {{ number_format($sale->totalamount, 2, '.', ',') }}</td>
                                <td>
                                    @switch($sale->state)
                                        @case(0)
                                            ANULADO
                                        @break

                                        @case(1)
                                            CONFIRMADO
                                        @break

                                        @case(2)
                                            PENDIENTE
                                        @break

                                        @case(3)
                                            FACTURADO
                                        @break

                                        @default
                                    @endswitch</td>
                                    <td>
                                        @switch($sale->waytopay)
                                            @case(1)
                                                CONTADO
                                            @break

                                            @case(2)
                                                CRÉDITO
                                            @break

                                            @case(3)
                                                OTRO
                                            @break

                                            @default
                                        @endswitch</td>
                                <td>
                                    @switch($sale->typesale)
                                        @case(1)
                                        <div class="d-flex align-items-center">
                                            <!--
                                            <a href="javascript: printsale({{ $sale->id }});" class="dropdown-item"><i
                                                class="ti ti-edit ti-sm me-2"></i>imprimir</a>-->
                                            <a href="{{route('sale.print', $sale->id)}}"
                                                    class="btn btn-icon btn-secondary btn-xm" target="_blank"><i
                                                        class="fas fa-print"></i></a>
                                            <a href="#"
                                                    onclick="EnviarCorreo({{$sale->id}} ,'{{ $sale->mailClient}}','{{$sale->id_doc }}')"
                                                    class="btn btn-icon btn-success btn-xm"><i
                                                        class="fas fa-paper-plane"></i></a>
                                            <a href="javascript:;" class="text-body dropdown-toggle hide-arrow"
                                                data-bs-toggle="dropdown"><i class="mx-1 ti ti-dots-vertical ti-sm"></i></a>
                                            <div class="m-0 dropdown-menu dropdown-menu-end">
                                                <a href="javascript:cancelsale({{ $sale->id }});" class="dropdown-item"><i
                                                        class="ti ti-eraser ti-sm me-2"></i>Anular</a>
                                                        @if ($sale->tipoDte=="03"  && $sale->estadoHacienda=='PROCESADO' && $sale->tipoDte!="05" && $sale->relatedSale=="")
                                                        <a href="javascript:ncr({{ $sale->id }});" class="dropdown-item"><i
                                                            class="ti ti-pencil ti-sm me-2"></i>Crear Nota de Credito</a>
                                                        @endif
                                            </div>
                                        </div>
                                        @break

                                        @case(2)
                                        <div class="d-flex align-items-center">
                                            <a href="javascript: retomarsale({{ $sale->id }}, {{ $sale->typedocument_id}});" class="dropdown-item"><i
                                                class="ti ti-edit ti-sm me-2"></i>Retomar</a>
                                        </div>
                                        @break
                                        @case(0)
                                        <div class="d-flex align-items-center">

                                        </div>
                                        @break

                                        @default
                                    @endswitch
                                </td>
                            </tr>
                            @empty
                                <tr>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td>No data</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                            @endforelse
                        @endisset
                    </tbody>
                </table>
            </div>
            <!-- select type document to create -->
            <div class="modal fade" id="selectDocumentModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-simple modal-pricing">
                  <div class="p-3 modal-content p-md-5">
                    <button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="Close"></button>
                    <div class="modal-body">
                      <div class="mb-4 text-center">
                        <h3 class="mb-2">Documentos disponibles</h3>
                      </div>
                      <form id="selectDocumentForm" class="row" action="{{Route('sale.create')}}" method="GET">
                        @csrf @method('GET')
                        <input type="hidden" name="iduser" id="iduser" value="{{Auth::user()->id}}">
                        <div id="wizard-create-deal" class="mt-2 bs-stepper vertical">
                            <div class="bs-stepper-content">
                                <!-- Deal Type -->
                                <div id="deal-type" class="content">
                                  <div class="row g-3">
                                    <div class="pt-4 border rounded col-12 d-flex justify-content-center">
                                      <img src="{{ asset('assets/img/illustrations/auth-register-illustration-'.$configData['style'].'.png') }}" alt="wizard-create-deal" data-app-light-img="illustrations/auth-register-illustration-light.png" data-app-dark-img="illustrations/auth-register-illustration-dark.png" width="250" class="img-fluid">
                                    </div>
                                    <div class="pb-2 col-12">
                                      <div class="row">
                                        <div class="mb-2 col-md mb-md-0">
                                          <div class="form-check custom-option custom-option-icon">
                                            <label class="form-check-label custom-option-content" for="factura">
                                              <span class="custom-option-body">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-receipt-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                    <path d="M5 21v-16a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v16l-3 -2l-2 2l-2 -2l-2 2l-2 -2l-3 2"></path>
                                                    <path d="M14 8h-2.5a1.5 1.5 0 0 0 0 3h1a1.5 1.5 0 0 1 0 3h-2.5m2 0v1.5m0 -9v1.5"></path>
                                                 </svg>

                                                <span class="custom-option-title">FACTURA</span>
                                                <small>Creación de factura para personas naturales contribuyentes o no contribuyentes</small>
                                              </span>
                                              <input name="typedocument" class="form-check-input" type="radio" value="6" id="factura" checked />
                                            </label>
                                          </div>
                                        </div>
                                        <div class="mb-2 col-md mb-md-0">
                                          <div class="form-check custom-option custom-option-icon">
                                            <label class="form-check-label custom-option-content" for="fiscal">
                                              <span class="custom-option-body">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-receipt" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                    <path d="M5 21v-16a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v16l-3 -2l-2 2l-2 -2l-2 2l-2 -2l-3 2m4 -14h6m-6 4h6m-2 4h2"></path>
                                                 </svg>

                                                <span class="custom-option-title">COMPROBANTE DE CREDITO FISCAL</span>
                                                <small>Creación de documentos donde necesitas una persona natural o jurídica que declare IVA</small>
                                              </span>
                                              <input name="typedocument" class="form-check-input" type="radio" value="3" id="fiscal" />
                                            </label>
                                          </div>
                                        </div>
                                        <div class="mb-2 col-md mb-md-0">
                                          <div class="form-check custom-option custom-option-icon">
                                            <label class="form-check-label custom-option-content" for="nota">
                                              <span class="custom-option-body">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-receipt-refund" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                    <path d="M5 21v-16a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v16l-3 -2l-2 2l-2 -2l-2 2l-2 -2l-3 2"></path>
                                                    <path d="M15 14v-2a2 2 0 0 0 -2 -2h-4l2 -2m0 4l-2 -2"></path>
                                                 </svg>
                                                <span class="custom-option-title">FACTURAS DE SUJETO EXCLUIDO</span>
                                                <small>Creación de documento para que el impuesto no es aplicable a la operación que se realiza.</small>
                                              </span>
                                              <input name="typedocument" class="form-check-input" type="radio" value="8" id="nota" />
                                            </label>
                                          </div>
                                        </div>
                                        <div class="mt-4 col-12 d-flex justify-content-center">
                                            <button class="btn btn-success btn-submit btn-next"><span class="align-center d-sm-inline-block d-none me-sm-1">Comenzar</span><i class="ti ti-arrows-join-2 ti-xs"></i></button>
                                          </div>
                                      </div>
                                    </div>
                            </div>
                          </div>
                      </form>
                    </div>
                  </div>
                </div>
              </div>
    @endsection
