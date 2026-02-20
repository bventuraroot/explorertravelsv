@extends('layouts/layoutMaster')

@section('title', 'Nuevo documento')

@section('vendor-style')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/bs-stepper/bs-stepper.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/bootstrap-select/bootstrap-select.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}" />
@endsection

@section('vendor-script')
    <script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/bs-stepper/bs-stepper.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/bootstrap-select/bootstrap-select.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/select2/select2.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endsection

@section('page-script')
    <script src="{{ asset('assets/js/form-wizard-icons.js') }}"></script>
    @if(request('typedocument')==2)
    <script>
    $(document).ready(function() {
        // Función mejorada para buscar el producto "Liquidación venta terceros" (reutilizable)
        function buscarProductoLiquidacionMejorado(callback) {
            var productoEncontrado = false;
            var productoLiquidacionId = null;
            var productoLiquidacionNombre = '';
            var productoLiquidacionDesc = '';
            
            // Variaciones posibles del nombre del producto
            var variaciones = [
                'liquidacion venta tercero',
                'liquidacion venta terceros',
                'liquidación venta tercero',
                'liquidación venta terceros',
                'liquidacion venta de terceros',
                'liquidación venta de terceros',
                'venta terceros liquidacion',
                'venta terceros liquidación'
            ];

            // Función auxiliar para normalizar texto (quitar acentos, espacios extra, etc.)
            function normalizarTexto(texto) {
                if (!texto) return '';
                return texto.toLowerCase()
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '') // Quitar acentos
                    .replace(/\s+/g, ' ') // Normalizar espacios
                    .trim();
            }

            // Función para verificar si un nombre coincide con alguna variación
            function coincideConVariacion(nombre) {
                var nombreNormalizado = normalizarTexto(nombre);
                for (var i = 0; i < variaciones.length; i++) {
                    if (nombreNormalizado.includes(variaciones[i]) || 
                        nombreNormalizado === variaciones[i]) {
                        return true;
                    }
                }
                // También verificar si contiene las palabras clave principales
                return nombreNormalizado.includes('liquidacion') && 
                       nombreNormalizado.includes('venta') && 
                       (nombreNormalizado.includes('tercero') || nombreNormalizado.includes('terceros'));
            }

            // Estrategia 1: Buscar en las opciones del select2
            try {
                $('#psearch option').each(function() {
                    var texto = $(this).text();
                    var textoNormalizado = normalizarTexto(texto);
                    
                    if (coincideConVariacion(textoNormalizado)) {
                        productoLiquidacionId = $(this).val();
                        var textoCompleto = texto.split('|')[0].trim();
                        productoLiquidacionNombre = textoCompleto;
                        productoLiquidacionDesc = textoCompleto;
                        productoEncontrado = true;
                        console.log('Producto encontrado en select2:', productoLiquidacionId, productoLiquidacionNombre);
                        return false;
                    }
                });
            } catch (e) {
                console.warn('Error al buscar en select2:', e);
            }

            // Estrategia 2: Buscar por AJAX
            if (!productoEncontrado) {
                $.ajax({
                    url: '/product/getproductall',
                    method: 'GET',
                    timeout: 10000,
                    success: function(products) {
                        if (products && products.length > 0) {
                            $.each(products, function(index, product) {
                                var nombre = product.name || '';
                                var nombreNormalizado = normalizarTexto(nombre);
                                
                                if (coincideConVariacion(nombreNormalizado)) {
                                    productoLiquidacionId = product.id;
                                    productoLiquidacionNombre = nombre;
                                    productoLiquidacionDesc = product.description || nombre;
                                    productoEncontrado = true;
                                    console.log('Producto encontrado por AJAX:', productoLiquidacionId, productoLiquidacionNombre);
                                    return false;
                                }
                            });
                        }
                        
                        // Ejecutar callback con el resultado
                        if (callback) {
                            callback(productoEncontrado, productoLiquidacionId, productoLiquidacionNombre, productoLiquidacionDesc);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error al buscar productos:', {status, error, response: xhr.responseText});
                        if (callback) {
                            callback(false, null, '', '');
                        }
                    }
                });
            } else {
                // Si ya se encontró en select2, ejecutar callback inmediatamente
                if (callback) {
                    callback(productoEncontrado, productoLiquidacionId, productoLiquidacionNombre, productoLiquidacionDesc);
                }
            }
        }

        // Cargar automáticamente el producto "Liquidación venta tercero" para CLQ
        function loadProductLiquidacion() {
            var typedoc = $('#typedocument').val();
            if (typedoc != '2') return; // Solo para CLQ

            buscarProductoLiquidacionMejorado(function(encontrado, id, nombre, descripcion) {
                if (encontrado && id) {
                    // Establecer valores ocultos
                    $('#productid').val(id);
                    $('#productname').val(nombre);
                    $('#productdescription').val(descripcion);
                    $('#cantidad').val(1);
                    $('#typesale').val('gravada');

                    console.log('Producto "Liquidación venta tercero" cargado automáticamente:', id);
                } else {
                    console.warn('No se encontró el producto "Liquidación venta tercero". Asegúrate de crearlo primero.');
                }
            });
        }

        // Cargar el producto cuando la página esté lista
        setTimeout(function() {
            loadProductLiquidacion();
        }, 1000);

        // Búsqueda de documentos para CLQ
        $('#btn-search-docs').on('click', function() {
            var tipoDoc = $('#search_tipo_documento').val();
            var numeroDoc = $('#search_numero_doc').val();
            var fechaDesde = $('#search_fecha_desde').val();
            var fechaHasta = $('#search_fecha_hasta').val();
            var cliente = $('#search_cliente').val();
            var companyId = $('#company').val();
            var clientId = $('#client').val(); // Cliente del CLQ (que es el proveedor/tercero de la factura original)

            console.log('Iniciando búsqueda con:', {
                tipoDoc, numeroDoc, fechaDesde, fechaHasta, cliente, companyId, clientId
            });

            if (!companyId) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Atención',
                    text: 'Por favor, primero selecciona una empresa'
                });
                return;
            }

            if (!clientId || clientId === '0') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Atención',
                    html: 'Por favor, primero selecciona un cliente en el paso anterior.<br><small>El cliente del CLQ debe ser el proveedor (tercero) de las facturas/CCF que deseas liquidar.</small>'
                });
                return;
            }

            // Mostrar loading
            $('#tbody-documentos-busqueda').html(`
                <tr>
                    <td colspan="7" class="text-center">
                        <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                        Buscando documentos...
                    </td>
                </tr>
            `);

            $.ajax({
                url: '{{ route("sale.searchDocumentsForCLQ") }}',
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                data: {
                    tipo_documento: tipoDoc,
                    numero_doc: numeroDoc,
                    fecha_desde: fechaDesde,
                    fecha_hasta: fechaHasta,
                    cliente: cliente,
                    company_id: companyId,
                    client_id: clientId // El cliente del CLQ (proveedor/tercero de la factura)
                },
                success: function(response) {
                    console.log('Respuesta del servidor:', response);
                    console.log('Cantidad de documentos:', response.count);
                    console.log('Filtros aplicados:', response.filtros);

                    if (response.success && response.data && response.data.length > 0) {
                        var html = '';
                        response.data.forEach(function(doc) {
                            var fechaFormateada = doc.date;
                            // Asegurar que la fecha esté en formato correcto
                            if (fechaFormateada && fechaFormateada.includes('T')) {
                                fechaFormateada = fechaFormateada.split('T')[0];
                            }

                            // El número de documento SIEMPRE es el código de generación de DTE
                            var codigoGeneracionDTE = doc.codigoGeneracion || '';
                            var tieneCodigoGeneracion = codigoGeneracionDTE !== '' && codigoGeneracionDTE !== null;

                            // Badge para tipo de generación
                            var badgeGen = tieneCodigoGeneracion
                                ? '<span class="badge bg-success">Electrónico</span>'
                                : '<span class="badge bg-secondary">Físico</span>';

                            // Verificar si está invalidado
                            var esInvalidado = doc.es_invalidado == 1 || doc.es_invalidado === true || doc.state == 0;
                            var badgeInvalidado = esInvalidado
                                ? '<span class="badge bg-danger ms-1"><i class="ti ti-x"></i> Invalidado</span>'
                                : '';

                            html += `
                                <tr ${esInvalidado ? 'style="opacity: 0.7;"' : ''}>
                                    <td><strong>${doc.id}</strong></td>
                                    <td><span class="badge bg-info">${doc.tipo_documento || 'N/A'}</span></td>
                                    <td>${new Date(doc.date).toLocaleDateString('es-SV')}</td>
                                    <td>
                                        <strong>Cliente:</strong> ${doc.cliente_nombre || 'N/A'}<br>
                                        <small class="text-primary"><i class="ti ti-building-store"></i> <strong>Tercero:</strong> ${doc.proveedor_nombre || 'N/A'}</small>
                                    </td>
                                    <td class="text-end"><strong>$${parseFloat(doc.totalamount || 0).toFixed(2)}</strong></td>
                                    <td>
                                        ${badgeGen}
                                        ${badgeInvalidado}
                                        <br><small class="text-muted">${codigoGeneracionDTE || 'Sin código DTE'}</small>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary btn-select-doc"
                                                data-id="${doc.id}"
                                                data-tipo="${doc.codigo_mh || ''}"
                                                data-tipo-gen="${tieneCodigoGeneracion ? '2' : '1'}"
                                                data-numero="${doc.id}"
                                                data-fecha="${fechaFormateada}"
                                                data-codigo-gen="${codigoGeneracionDTE}"
                                                data-tipo-nombre="${doc.tipo_documento || 'Documento'}">
                                            <i class="ti ti-check me-1"></i>Seleccionar
                                        </button>
                                    </td>
                                </tr>
                            `;
                        });
                        $('#tbody-documentos-busqueda').html(html);

                        // Mostrar contador de resultados
                        Swal.fire({
                            icon: 'info',
                            title: 'Resultados encontrados',
                            text: `Se encontraron ${response.count} documento(s)`,
                            timer: 1500,
                            showConfirmButton: false
                        });
                    } else {
                        var debugInfo = '';
                        if (response.debug) {
                            debugInfo = `<br><small class="text-muted">
                                ${response.debug.mensaje || ''}<br>
                                Total en empresa: ${response.debug.total_ventas_empresa || 0} documentos
                            </small>`;
                        }

                        $('#tbody-documentos-busqueda').html(`
                            <tr>
                                <td colspan="7" class="text-center text-muted">
                                    <i class="ti ti-info-circle me-1"></i>No se encontraron documentos con los criterios especificados
                                    ${debugInfo}
                                </td>
                            </tr>
                        `);

                        console.warn('No se encontraron resultados', response);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error al buscar documentos:', {xhr, status, error});
                    console.error('Respuesta completa:', xhr.responseText);

                    var errorMsg = 'Error al buscar documentos. ';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg += xhr.responseJSON.message;
                    } else {
                        errorMsg += 'Por favor, intenta de nuevo o contacta al administrador.';
                    }

                    $('#tbody-documentos-busqueda').html(`
                        <tr>
                            <td colspan="7" class="text-center text-danger">
                                <i class="ti ti-alert-circle me-1"></i>${errorMsg}
                                <br><small class="text-muted">Revisa la consola del navegador (F12) para más detalles</small>
                            </td>
                        </tr>
                    `);

                    Swal.fire({
                        icon: 'error',
                        title: 'Error en la búsqueda',
                        text: errorMsg,
                        footer: '<small>Código de error: ' + (xhr.status || 'desconocido') + '</small>'
                    });
                }
            });
        });

        // Al seleccionar un documento, autorellenar campos y cargar productos
        $(document).on('click', '.btn-select-doc', function() {
            var saleId = $(this).data('id');
            var tipo = $(this).data('tipo');
            var tipoGen = $(this).data('tipo-gen');
            var numero = $(this).data('numero');
            var fecha = $(this).data('fecha');
            var codigoGen = $(this).data('codigo-gen');
            var tipoNombre = $(this).data('tipo-nombre');

            console.log('Seleccionando documento:', {
                saleId, tipo, tipoGen, numero, fecha, codigoGen, tipoNombre
            });

            // Mostrar loading
            Swal.fire({
                title: 'Cargando datos...',
                html: 'Obteniendo información del documento y sus productos',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Autorellenar campos CLQ básicos
            $('#clq_tipo_documento').val(tipo);

            // Número de documento: SIEMPRE usar código de generación de DTE (columna dte.codigoGeneracion)
            // Tipo de generación: Si tiene código de generación = Electrónico (2), sino = Físico (1)
            if (codigoGen && codigoGen !== '' && codigoGen !== null && codigoGen !== 'N/A') {
                $('#clq_tipo_generacion').val('2'); // Electrónico
                $('#clq_numero_documento').val(codigoGen); // Código de generación de DTE
                console.log('Documento electrónico - Código generación:', codigoGen);
            } else {
                $('#clq_tipo_generacion').val('1'); // Físico
                // Si no hay código de generación DTE, usar el ID como fallback
                $('#clq_numero_documento').val(numero);
                console.log('Documento físico - Usando ID:', numero);
            }

            // Fecha de emisión: usar la fecha del documento seleccionado
            var fechaFormateada = fecha;
            if (fecha && fecha.includes('T')) {
                fechaFormateada = fecha.split('T')[0];
            } else if (fecha && fecha.includes(' ')) {
                fechaFormateada = fecha.split(' ')[0];
            }
            if (fechaFormateada) {
                $('#clq_fecha_generacion').val(fechaFormateada);
            }

            // Cerrar modal
            $('#modalBuscarDocumento').modal('hide');

            // Obtener detalles de la venta para cargar productos y montos
            $.ajax({
                url: '/sale/get-sale-details-clq/' + saleId,
                method: 'GET',
                success: function(response) {
                    console.log('Detalles de la venta:', response);

                    if (response.success && response.data && response.data.totales) {
                        var corrid = $("#corr").val();
                        var clientid = $("#client").val();
                        var companyId = $("#company").val();

                        if (!corrid || !clientid || clientid === '0') {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Faltan datos',
                                text: 'Asegúrate de tener un correlativo y cliente seleccionado antes de cargar montos',
                                timer: 3000
                            });
                            return;
                        }

                        var totales = response.data.totales;
                        var tipoVenta = response.data.tipo_venta || 'gravada';

                        console.log('Llenando campos de totales con datos:', totales);

                        // PRIMERO: Llenar los campos de totales SIEMPRE que haya datos (independiente del producto)
                        // Para CLQ: rellenar campos de totales manuales
                        if ($('#clq_total_gravadas').length) {
                            var gravada = parseFloat(totales.gravada || 0);
                            $('#clq_total_gravadas').val(gravada.toFixed(2));
                            console.log('Campo clq_total_gravadas llenado con:', gravada.toFixed(2));
                        } else {
                            console.warn('Campo #clq_total_gravadas no encontrado en el DOM');
                        }

                        if ($('#clq_total_exentas').length) {
                            var exenta = parseFloat(totales.exenta || 0);
                            $('#clq_total_exentas').val(exenta.toFixed(2));
                            console.log('Campo clq_total_exentas llenado con:', exenta.toFixed(2));
                        } else {
                            console.warn('Campo #clq_total_exentas no encontrado en el DOM');
                        }

                        if ($('#clq_total_no_sujetas').length) {
                            var nosujeta = parseFloat(totales.nosujeta || 0);
                            $('#clq_total_no_sujetas').val(nosujeta.toFixed(2));
                            console.log('Campo clq_total_no_sujetas llenado con:', nosujeta.toFixed(2));
                        } else {
                            console.warn('Campo #clq_total_no_sujetas no encontrado en el DOM');
                        }

                        // Llenar montos según tipo de venta (campos ocultos)
                        if (tipoVenta === 'nosujeta') {
                            if ($('#ventasnosujetas').length) {
                                $('#ventasnosujetas').val(parseFloat(totales.nosujeta || 0).toFixed(2));
                            }
                        } else if (tipoVenta === 'exenta') {
                            if ($('#ventasexentas').length) {
                                $('#ventasexentas').val(parseFloat(totales.exenta || 0).toFixed(2));
                            }
                        } else {
                            // Gravada
                            if ($('#ventasgravadas').length) {
                                $('#ventasgravadas').val(parseFloat(totales.gravada || 0).toFixed(2));
                            }
                        }

                        // IVA total
                        if ($('#ivarete13').length) {
                            $('#ivarete13').val(parseFloat(totales.iva || 0).toFixed(8));
                        }

                        // Fee total si existe
                        if (totales.fee && parseFloat(totales.fee) > 0 && $('#fee').length) {
                            $('#fee').val(parseFloat(totales.fee || 0).toFixed(8));
                        }

                        // Recalcular totales usando la función updateCLQTotals
                        if (typeof updateCLQTotals === 'function') {
                            updateCLQTotals();
                            console.log('Función updateCLQTotals() ejecutada');
                        } else if (typeof totalamount === 'function') {
                            totalamount();
                            console.log('Función totalamount() ejecutada');
                        } else {
                            console.warn('No se encontró función updateCLQTotals ni totalamount');
                        }

                        // SEGUNDO: Buscar el producto para agregarlo a la línea del comprobante
                        // Función mejorada para buscar el producto "Liquidación venta terceros"
                        function buscarProductoLiquidacion(callback) {
                            var productoEncontrado = false;
                            var productoLiquidacionId = null;
                            var productoLiquidacionNombre = '';
                            
                            // Variaciones posibles del nombre del producto
                            var variaciones = [
                                'liquidacion venta tercero',
                                'liquidacion venta terceros',
                                'liquidación venta tercero',
                                'liquidación venta terceros',
                                'liquidacion venta de terceros',
                                'liquidación venta de terceros',
                                'venta terceros liquidacion',
                                'venta terceros liquidación'
                            ];

                            // Función auxiliar para normalizar texto (quitar acentos, espacios extra, etc.)
                            function normalizarTexto(texto) {
                                if (!texto) return '';
                                return texto.toLowerCase()
                                    .normalize('NFD')
                                    .replace(/[\u0300-\u036f]/g, '') // Quitar acentos
                                    .replace(/\s+/g, ' ') // Normalizar espacios
                                    .trim();
                            }

                            // Función para verificar si un nombre coincide con alguna variación
                            function coincideConVariacion(nombre) {
                                var nombreNormalizado = normalizarTexto(nombre);
                                for (var i = 0; i < variaciones.length; i++) {
                                    if (nombreNormalizado.includes(variaciones[i]) || 
                                        nombreNormalizado === variaciones[i]) {
                                        return true;
                                    }
                                }
                                // También verificar si contiene las palabras clave principales
                                return nombreNormalizado.includes('liquidacion') && 
                                       nombreNormalizado.includes('venta') && 
                                       (nombreNormalizado.includes('tercero') || nombreNormalizado.includes('terceros'));
                            }

                            // Estrategia 1: Buscar en las opciones del select2
                            try {
                                $('#psearch option').each(function() {
                                    var texto = $(this).text();
                                    var textoNormalizado = normalizarTexto(texto);
                                    
                                    if (coincideConVariacion(textoNormalizado)) {
                                        productoLiquidacionId = $(this).val();
                                        productoLiquidacionNombre = texto.split('|')[0].trim(); // Solo el nombre, sin descripción
                                        productoEncontrado = true;
                                        console.log('Producto encontrado en select2:', productoLiquidacionId, productoLiquidacionNombre);
                                        return false; // Salir del loop
                                    }
                                });
                            } catch (e) {
                                console.warn('Error al buscar en select2:', e);
                            }

                            // Estrategia 2: Si no se encuentra en el select2, buscar por AJAX
                            if (!productoEncontrado) {
                                console.log('Buscando producto por AJAX...');
                                $.ajax({
                                    url: '/product/getproductall',
                                    method: 'GET',
                                    async: false,
                                    timeout: 10000, // 10 segundos de timeout
                                    success: function(products) {
                                        console.log('Productos recibidos:', products ? products.length : 0);
                                        if (products && products.length > 0) {
                                            $.each(products, function(index, product) {
                                                var nombre = product.name || '';
                                                var nombreNormalizado = normalizarTexto(nombre);
                                                
                                                if (coincideConVariacion(nombreNormalizado)) {
                                                    productoLiquidacionId = product.id;
                                                    productoLiquidacionNombre = nombre;
                                                    productoEncontrado = true;
                                                    console.log('Producto encontrado por AJAX:', productoLiquidacionId, productoLiquidacionNombre);
                                                    return false;
                                                }
                                            });
                                        } else {
                                            console.warn('No se recibieron productos del servidor');
                                        }
                                    },
                                    error: function(xhr, status, error) {
                                        console.error('Error al buscar productos por AJAX:', {
                                            status: status,
                                            error: error,
                                            response: xhr.responseText
                                        });
                                    }
                                });
                            }

                            // Ejecutar callback con el resultado
                            if (callback) {
                                callback(productoEncontrado, productoLiquidacionId, productoLiquidacionNombre);
                            }
                            
                            return {
                                encontrado: productoEncontrado,
                                id: productoLiquidacionId,
                                nombre: productoLiquidacionNombre
                            };
                        }

                        // Buscar el producto usando la función mejorada reutilizable
                        buscarProductoLiquidacionMejorado(function(encontrado, id, nombre, descripcion) {
                            if (!encontrado || !id) {
                                console.error('Producto no encontrado. Los totales ya fueron cargados, pero el usuario debe seleccionar el producto manualmente.');
                                
                                // Mostrar mensaje más informativo pero indicando que los totales ya están cargados
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'Producto no encontrado',
                                    html: 'Los totales del documento se cargaron correctamente.<br><br>' +
                                          'Sin embargo, no se encontró el producto "Liquidación venta terceros".<br><br>' +
                                          '<strong>Por favor:</strong><br>' +
                                          '1. Selecciona el producto "Liquidación venta terceros" manualmente<br>' +
                                          '2. O créalo si no existe en el sistema<br><br>' +
                                          '<small class="text-muted">Los campos de totales ya están llenos con los valores del documento seleccionado.</small>',
                                    confirmButtonText: 'Entendido',
                                    footer: '<small>Revisa la consola del navegador (F12) para más detalles</small>'
                                });
                                
                                Swal.close(); // Cerrar el loading
                                return; // Salir de la función
                            }
                            
                            console.log('Producto encontrado exitosamente:', id, nombre);
                            
                            // Seleccionar el producto "Liquidación venta terceros"
                            $('#psearch').val(id).trigger('change');
                            $('#productid').val(id);

                            // Esperar a que se cargue el producto
                            setTimeout(function() {
                                // Establecer cantidad (siempre 1 para liquidación)
                                if ($('#cantidad').length) {
                                    $('#cantidad').val(1);
                                }

                                // Establecer tipo de venta
                                if ($('#typesale').length) {
                                    $('#typesale').val(tipoVenta).trigger('change');
                                }

                                // Precio unitario: calcular basado en el total gravado (sin IVA para CLQ)
                                if ($('#precio').length && totales.gravada > 0) {
                                    // Para CLQ, el precio unitario es el total gravado sin IVA
                                    var precioUnitario = parseFloat(totales.gravada || 0);
                                    $('#precio').val(precioUnitario.toFixed(8));
                                }

                                Swal.fire({
                                    icon: 'success',
                                    title: '¡Montos cargados!',
                                    html: `Se rellenaron los montos del ${tipoNombre}<br>
                                           <small>Producto: <strong>${nombre}</strong><br>
                                           Gravadas: $${parseFloat(totales.gravada || 0).toFixed(2)} |
                                           Exentas: $${parseFloat(totales.exenta || 0).toFixed(2)} |
                                           No Sujetas: $${parseFloat(totales.nosujeta || 0).toFixed(2)}<br>
                                           IVA: $${parseFloat(totales.iva || 0).toFixed(2)} |
                                           Total: $${parseFloat(totales.total_general || 0).toFixed(2)}</small>`,
                                    timer: 4000,
                                    showConfirmButton: false
                                });
                            }, 500);
                        }); // Cierre del callback de buscarProductoLiquidacionMejorado
                    } else {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Sin datos',
                            text: 'Los campos básicos se rellenaron, pero no se pudieron obtener los montos del documento',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                },
                error: function(xhr) {
                    console.error('Error al obtener detalles:', xhr);
                    var tipoGenTexto = (codigoGen && codigoGen !== '' && codigoGen !== 'N/A') ? 'Electrónico' : 'Físico';
                    Swal.fire({
                        icon: 'info',
                        title: 'Campos básicos rellenados',
                        html: `Los campos del documento se rellenaron correctamente<br>
                               <small>Tipo: ${tipoGenTexto} | Fecha: ${fechaFormateada || fecha}</small><br>
                               <small class="text-muted">No se pudieron cargar los productos automáticamente. Puedes agregarlos manualmente.</small>`,
                        timer: 3000
                    });
                }
            });
        });

        // Botón para buscar todos los documentos sin filtros
        $('#btn-search-all').on('click', function() {
            console.log('Buscando TODOS los documentos...');

            // Limpiar filtros
            $('#search_tipo_documento').val('');
            $('#search_numero_doc').val('');
            $('#search_cliente').val('');
            // Mantener rango de fechas amplio (último año)
            var fechaHaceUnAno = new Date();
            fechaHaceUnAno.setFullYear(fechaHaceUnAno.getFullYear() - 1);
            $('#search_fecha_desde').val(fechaHaceUnAno.toISOString().split('T')[0]);
            $('#search_fecha_hasta').val(new Date().toISOString().split('T')[0]);

            // Ejecutar búsqueda
            $('#btn-search-docs').click();
        });

        // Permitir búsqueda con Enter en todos los campos
        $('#search_numero_doc, #search_cliente, #search_fecha_desde, #search_fecha_hasta').on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                $('#btn-search-docs').click();
            }
        });

        // Búsqueda automática al abrir el modal
        $('#modalBuscarDocumento').on('shown.bs.modal', function () {
            console.log('Modal abierto - Búsqueda automática de últimos documentos');
            // Búsqueda automática con rango de 30 días
            var fecha30Dias = new Date();
            fecha30Dias.setDate(fecha30Dias.getDate() - 30);
            $('#search_fecha_desde').val(fecha30Dias.toISOString().split('T')[0]);
            $('#search_fecha_hasta').val(new Date().toISOString().split('T')[0]);

            // Ejecutar búsqueda automáticamente
            setTimeout(function() {
                $('#btn-search-docs').click();
            }, 300);
        });
    });
    </script>
    @endif
@endsection

@section('content')
<style>
    .imagen-producto-select2 {
    width: 50px;
    height: 50px;
    margin-right: 10px;
    vertical-align: middle;
}
</style>

@php
    switch (request('typedocument')) {
        case '6':
            $document = 'Factura';
            break;
        case '8':
            $document = 'Factura de sujeto excluido';
            break;
        case '3':
            $document = 'Crédito Fiscal';
            break;
        case '7':
            $document = 'Factura de Exportación';
            break;
        case '2':
            $document = 'Comprobante de Liquidación';
            break;
        default:
            $document = 'Documento';
            break;
    }
@endphp
    <!-- Default Icons Wizard -->
    <div class="mb-4 col-12">
        <h4 class="py-3 mb-4 fw-bold">
            <span class="text-center fw-semibold">Creación de {{ $document }}
        </h4>
        <div class="mt-2 bs-stepper wizard-icons wizard-icons-example">
            <div class="bs-stepper-header">
                <div class="step" data-target="#company-select">
                    <button type="button" class="step-trigger" disabled>
                        <span class="bs-stepper-icon">
                            <svg viewBox="0 0 54 54">
                                <use xlink:href='{{ asset('assets/svg/icons/form-wizard-account.svg#wizardAccount') }}'>
                                </use>
                            </svg>
                        </span>
                        <span class="bs-stepper-label">Seleccionar Empresa</span>
                    </button>
                </div>
                <div class="line">
                    <i class="ti ti-chevron-right"></i>
                </div>
                <div class="step" data-target="#personal-info">
                    <button type="button" class="step-trigger" disabled>
                        <span class="bs-stepper-icon">
                            <svg viewBox="0 0 58 54">
                                <use xlink:href='{{ asset('assets/svg/icons/form-wizard-personal.svg#wizardPersonal') }}'>
                                </use>
                            </svg>
                        </span>
                        <span class="bs-stepper-label">Información {{ $document }}</span>
                    </button>
                </div>
                <div class="line">
                    <i class="ti ti-chevron-right"></i>
                </div>
                <div class="step" data-target="#products" id="step-products">
                    <button type="button" id="button-products" class="step-trigger" disabled>
                        <span class="bs-stepper-icon">
                            <svg viewBox="0 0 54 54">
                                <use xlink:href='{{ asset('assets/svg/icons/wizard-checkout-cart.svg#wizardCart') }}'>
                                </use>
                            </svg>
                        </span>
                        <span class="bs-stepper-label">Productos</span>
                    </button>
                </div>
                <div class="line">
                    <i class="ti ti-chevron-right"></i>
                </div>
                <div class="step" data-target="#review-submit">
                    <button type="button" class="step-trigger" disabled>
                        <span class="bs-stepper-icon">
                            <svg viewBox="0 0 54 54">
                                <use xlink:href='{{ asset('assets/svg/icons/form-wizard-submit.svg#wizardSubmit') }}'>
                                </use>
                            </svg>
                        </span>
                        <span class="bs-stepper-label">Revisión & Creación</span>
                    </button>
                </div>
            </div>
            <div class="bs-stepper-content">
                <form onSubmit="return false">
                    <!-- select company -->
                    <div id="company-select" class="content">
                        <input type="hidden" name="iduser" id="iduser" value="{{ Auth::user()->id }}">
                        <div class="row g-5">
                            <div class="col-sm-12">
                                <label for="company" class="form-label">
                                    <h6>Empresa</h6>
                                </label>
                                <select class="select2company form-select" id="company" name="company"
                                    onchange="aviablenext(this.value)" aria-label="Seleccionar opcion">
                                </select>
                                <input type="hidden" name="typedocument" id="typedocument" value="{{request('typedocument')}}">
                                <input type="hidden" name="typecontribuyente" id="typecontribuyente">
                                <input type="hidden" name="iva" id="iva">
                                <input type="hidden" name="iva_entre" id="iva_entre">
                                <input type="hidden" name="valcorr" id="valcorr" value="{{ request('corr')!='' ? request('corr') : '' }}">
                                <input type="hidden" name="valdraft" id="valdraft" value="{{ request('draft')!='' ? request('draft') : '' }}">
                                <input type="hidden" name="operation" id="operation" value="{{ request('operation')!='' ? request('operation') : '' }}">
                            </div>
                            <div class="col-12 d-flex justify-content-between">
                                <button class="btn btn-label-secondary btn-prev" disabled> <i
                                        class="ti ti-arrow-left me-sm-1"></i>
                                    <span class="align-middle d-sm-inline-block d-none">Previous</span>
                                </button>
                                <button id="step1" class="btn btn-primary btn-next" disabled> <span
                                        class="align-middle d-sm-inline-block d-none me-sm-1">Next</span> <i
                                        class="ti ti-arrow-right"></i></button>
                            </div>
                        </div>
                    </div>
                    <!-- details document -->
                    <div id="personal-info" class="content">
                        <div class="mb-3 content-header">
                            <h6 class="mb-0">Detalles de {{ $document }}</h6>
                            <small>Ingresa los campos requeridos</small>
                        </div>
                        <div class="row g-3">
                            <div class="col-sm-2">
                                <label class="form-label" for="corr">Correlativo</label>
                                <input type="text" id="corr" name="corr" class="form-control" readonly />
                            </div>
                            <div class="col-sm-2">
                                <label class="form-label" for="date">Fecha</label>
                                <input type="date" id="date" name="date" class="form-control"
                                    value="{{ now()->format('Y-m-d') }}" />
                            </div>
                            <div class="col-sm-8">
                                <label for="client" class="form-label">Cliente</label>
                                <select class="select2client form-select" id="client" name="client" onchange="valtrypecontri(this.value)"
                                    aria-label="Seleccionar opcion">
                                </select>
                                <input type="hidden" name="typecontribuyenteclient" id="typecontribuyenteclient">
                                <input type="hidden" name="cliente_agente_retencion" id="cliente_agente_retencion" value="0">
                                <!-- Información del cliente (replicado de RomaCopies) -->
                                <div id="client-info" class="mt-2" style="display: none;">
                                    <div class="alert alert-info">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <strong>Nombre:</strong> <span id="client-name">-</span><br>
                                                <strong>Tipo:</strong> <span id="client-type">-</span><br>
                                                <strong>Contribuyente:</strong> <span id="client-contribuyente">-</span>
                                            </div>
                                            <div class="col-md-6">
                                                <strong>NIT/DUI:</strong> <span id="client-nit">-</span><br>
                                                <strong>Dirección:</strong> <span id="client-address">-</span><br>
                                                <strong>Teléfono:</strong> <span id="client-phone">-</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Campos específicos para Factura de Exportación -->
                                @if(request('typedocument') == '7')
                                <div class="mt-3 alert alert-warning" id="export-fields">
                                    <h6><i class="ti ti-world-upload me-2"></i>Datos de Exportación</h6>
                                    <small class="text-muted">
                                        <i class="ti ti-info-circle me-1"></i>
                                        <strong>Restricción:</strong> Solo se pueden seleccionar personas naturales extranjeras para facturas de exportación.
                                    </small>
                                </div>
                                @endif
                            </div>
                            <div class="col-sm-4">
                                <label class="form-label" for="fpago">Forma de pago</label>
                                <select class="select2" id="fpago" name="fpago" onchange="valfpago(this.value)">
                                    <option value="0">Seleccione</option>
                                    <option selected value="1">Contado</option>
                                    <!--<option value="2">A crédito</option>-->
                                    <option value="3">Tarjeta</option>
                                </select>
                            </div>
                            <div class="col-sm-3" style="display: none;" id="isfcredito">
                                <label class="form-label" for="datefcredito">Fecha</label>
                                <input type="date" id="datefcredito" name="datefcredito" class="form-control"
                                    value="{{ now()->format('Y-m-d') }}" />
                            </div>
                            <div class="col-12 d-flex justify-content-between">
                                <button class="btn btn-label-secondary btn-prev"> <i class="ti ti-arrow-left me-sm-1"></i>
                                    <span class="align-middle d-sm-inline-block d-none">Previous</span>
                                </button>
                                <button id="step2" class="btn btn-primary btn-next"> <span
                                        class="align-middle d-sm-inline-block d-none me-sm-1">Next</span> <i
                                        class="ti ti-arrow-right"></i></button>
                            </div>
                        </div>
                    </div>
                    <!-- Products -->
                    <div id="products" class="content">
                        <div class="mb-3 content-header">
                            <h6 class="mb-0">Productos</h6>
                            <small>Agregue los productos necesarios.</small>
                        </div>
                        @if(request('typedocument') != 2)
                        <div class="row g-3 col-12" style="margin-bottom: 3%">
                            <div class="col-sm-7">
                                <label class="form-label" for="psearch">Buscar Producto</label>
                                <select class="select2psearch" id="psearch" name="psearch" onchange="searchproduct(this.value)">
                                </select>
                                <input type="hidden" id="productname" name="productname">
                                <input type="hidden" id="productid" name="productid">
                                <input type="hidden" id="productdescription" name="productdescription">
                                <input type="hidden" id="productunitario" name="productunitario">
                                <input type="hidden" id="sumas" value="0" name="sumas">
                                <input type="hidden" id="13iva" value="0" name="13iva">
                                <input type="hidden" id="ivaretenido" value="0" name="ivaretenido">
                                <input type="hidden" id="retencion_agente" value="0" name="retencion_agente">
                                <input type="hidden" id="rentaretenido" value="0" name="rentaretenido">
                                <input type="hidden" id="ventasnosujetas" value="0" name="ventasnosujetas">
                                <input type="hidden" id="ventasexentas" value="0" name="ventasexentas">
                                <input type="hidden" id="ventatotal" value="0" name="ventatotal">
                                <input type="hidden" id="ventatotallhidden" value="0" name="ventatotallhidden">

                            </div>
                            @if(request('typedocument') == '6' || request('typedocument') == '3')
                            <div class="col-sm-3">
                                <label class="form-label" for="line_provider">
                                    <i class="ti ti-building-store me-1"></i>Proveedor (Tercero)
                                </label>
                                <select class="form-select" id="line_provider" name="line_provider">
                                    <option value="">No aplica</option>
                                </select>
                                <small class="mt-1 text-muted d-block">
                                    <i class="ti ti-info-circle me-1"></i>Selecciona si este producto es a cuenta de tercero
                                </small>
                            </div>
                            @endif
                            <div class="col-sm-{{ (request('typedocument') == '6' || request('typedocument') == '3') ? '2' : '3' }}">
                                <label class="form-label" for="cantidad">Cantidad</label>
                                <input type="number" id="cantidad" name="cantidad" min="1" max="10" value="1" class="form-control" onchange="totalamount();">
                            </div>
                            <div class="col-sm-2">
                                <label class="form-label" for="typesale">Tipo de venta</label>
                                <select class="form-select" id="typesale" name="typesale" onchange="changetypesale(this.value)">
                                    <option value="gravada">Gravadas</option>
                                    <option value="exenta">Exenta</option>
                                    <option value="nosujeta">No Sujeta</option>
                                </select>
                            </div>
                            @if(request('typedocument')==3 || request('typedocument')==2)
                            <div class="col-sm-2">
                                <label class="form-label" for="precioConIva">Precio de Venta (Con IVA)</label>
                                <input type="number" id="precioConIva" name="precioConIva" step="0.00000001" min="0" max="1000000" placeholder="0.00000000" class="form-control" onchange="calculateFromPriceWithIva();" oninput="calculateFromPriceWithIva();">
                            </div>
                            @endif
                            <div class="col-sm-2">
                                @if(request('typedocument')==3 || request('typedocument')==2)
                                <label class="form-label" for="precio">Precio Unitario sin IVA</label>
                                <input type="number" id="precio" readonly name="precio" step="0.00000001" min="0" max="1000000" placeholder="0.00000000" class="form-control" onchange="totalamount();">
                                @else
                                <label class="form-label" for="precio">Precio Unitario</label>
                                <input type="number" id="precio" name="precio" step="0.00000001" min="0" max="1000000" placeholder="0.00000000" class="form-control" onchange="totalamount();">
                                @endif
                            </div>
                            @if(request('typedocument')==6 || request('typedocument')==3 || request('typedocument')==7)
                            <div class="col-sm-2">
                                @if(request('typedocument')==3)
                                <label class="form-label" for="fee">Fee (Con IVA)</label>
                                @else
                                <label class="form-label" for="fee">Fee</label>
                                @endif
                                @if(request('typedocument')==3)
                                <input type="number" id="fee" name="fee" step="0.00000001" max="1000000" placeholder="0.00000000" class="form-control" onchange="totalamount(); calculateFromPriceWithIva();" oninput="totalamount(); calculateFromPriceWithIva();">
                                @else
                                <input type="number" id="fee" name="fee" step="0.00000001" max="1000000" placeholder="0.00000000" class="form-control" onchange="totalamount();">
                                @endif
                            </div>
                            @if(request('typedocument')==3)
                            <div class="col-sm-2">
                                <label class="form-label" for="feeSinIva">Fee Sin IVA</label>
                                <input type="number" readonly id="feeSinIva" name="feeSinIva" step="0.00000001" placeholder="0.00000000" class="form-control">
                            </div>
                            @endif
                            @endif
                            <div class="col-sm-2">
                                <label class="form-label" for="ivarete13">Iva 13%</label>
                                <input type="number" readonly id="ivarete13" @if(request('typedocument')==3) readonly @endif name="ivarete13" step="0.00000001" max="1000000" placeholder="0.00000000" class="form-control" onchange="totalamount();">
                            </div>
                            <div class="col-sm-2" id="iva_percibido_field" style="display: none;">
                                <label class="form-label" for="ivarete">Iva Percibido</label>
                                <input type="number" readonly id="ivarete" @if(request('typedocument')==3) readonly @endif name="ivarete" step="0.00000001" max="1000000" placeholder="0.00000000" class="form-control" onchange="totalamount();">
                            </div>
                            <div class="col-sm-2" id="iva_retenido_field" style="display: none;">
                                <label class="form-label" for="ivaretenido_visible">IVA Retenido</label>
                                <input type="number" readonly id="ivaretenido_visible" name="ivaretenido_visible" step="0.00000001" max="1000000" placeholder="0.00000000" class="form-control">
                            </div>

                            @if(request('typedocument')==8)
                            <div class="col-sm-2">
                                <label class="form-label" for="rentarete">Renta 10%</label>
                                <input type="number" readonly id="rentarete" name="rentarete" step="0.01" max="10000" placeholder="0.00" class="form-control">
                            </div>
                            @endif
                            <div class="col-sm-2">
                                <label class="form-label" for="total">Total</label>
                                <input type="number" readonly id="total" @if(request('typedocument')==3) readonly @endif name="total" step="0.00000001" max="1000000" placeholder="0.00000000" class="form-control">
                            </div>

                        </div>
                        @else
                        <!-- Para CLQ: Campos ocultos y valores fijos -->
                        <input type="hidden" id="productname" name="productname" value="LIQUIDACION VENTA TERCEROS">
                        <input type="hidden" id="productid" name="productid">
                        <input type="hidden" id="productdescription" name="productdescription" value="LIQUIDACION VENTA TERCEROS">
                        <input type="hidden" id="productunitario" name="productunitario" value="0">
                        <input type="hidden" id="sumas" value="0" name="sumas">
                        <input type="hidden" id="13iva" value="0" name="13iva">
                        <input type="hidden" id="ivaretenido" value="0" name="ivaretenido">
                        <input type="hidden" id="retencion_agente" value="0" name="retencion_agente">
                        <input type="hidden" id="rentaretenido" value="0" name="rentaretenido">
                        <input type="hidden" id="ventasnosujetas" value="0" name="ventasnosujetas">
                        <input type="hidden" id="ventasexentas" value="0" name="ventasexentas">
                        <input type="hidden" id="ventatotal" value="0" name="ventatotal">
                        <input type="hidden" id="ventatotallhidden" value="0" name="ventatotallhidden">
                        <input type="hidden" id="cantidad" name="cantidad" value="1">
                        <input type="hidden" id="typesale" name="typesale" value="gravada">
                        <input type="hidden" id="precio" name="precio" value="0">
                        <input type="hidden" id="fee" name="fee" value="0">
                        <input type="hidden" id="ivarete13" name="ivarete13" value="0">
                        <input type="hidden" id="total" name="total" value="0">
                        @endif

                        @if(request('typedocument')==2)
                        <!-- Botón Buscar Factura/CCF (solo CLQ) - arriba para autorellenar desde documento existente -->
                        <div class="row g-3 col-12" style="margin-bottom: 1.5%; margin-top: 2%;">
                            <div class="col-12">
                                <div class="card border-primary">
                                    <div class="py-2 card-body">
                                        <button type="button" class="btn btn-primary btn-sm" id="btn-buscar-documento-clq" data-bs-toggle="modal" data-bs-target="#modalBuscarDocumento">
                                            <i class="ti ti-search me-1"></i>Buscar Factura/CCF Existente
                                        </button>
                                        <small class="text-muted ms-2">
                                            <i class="ti ti-info-circle me-1"></i>Haz clic para buscar y autorellenar los datos desde un documento ya emitido
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Campos para ingresar totales manualmente en CLQ -->
                        <div class="row g-3 col-12" style="margin-bottom: 2%;">
                            <div class="mb-2 col-12">
                                <h6 class="mb-2 fw-semibold text-info">
                                    <i class="ti ti-calculator me-2"></i>Totales por Tipo de Venta (CLQ)
                                </h6>
                                <small class="text-muted">
                                    <i class="ti ti-info-circle me-1"></i>Ingrese los totales consolidados de todos los documentos relacionados
                                </small>
                            </div>
                            <div class="col-sm-4">
                                <label class="form-label" for="clq_total_gravadas">
                                    <i class="ti ti-currency-dollar me-1"></i>Total Ventas Gravadas
                                </label>
                                <input type="number" id="clq_total_gravadas" name="clq_total_gravadas" step="0.01" min="0" max="1000000" placeholder="0.00" class="form-control" onchange="updateCLQTotals();" oninput="updateCLQTotals();">
                                <small class="text-muted">Monto total de ventas gravadas</small>
                            </div>
                            <div class="col-sm-4">
                                <label class="form-label" for="clq_total_exentas">
                                    <i class="ti ti-currency-dollar me-1"></i>Total Ventas Exentas
                                </label>
                                <input type="number" id="clq_total_exentas" name="clq_total_exentas" step="0.01" min="0" max="1000000" placeholder="0.00" class="form-control" onchange="updateCLQTotals();" oninput="updateCLQTotals();">
                                <small class="text-muted">Monto total de ventas exentas</small>
                            </div>
                            <div class="col-sm-4">
                                <label class="form-label" for="clq_total_no_sujetas">
                                    <i class="ti ti-currency-dollar me-1"></i>Total Ventas No Sujetas
                                </label>
                                <input type="number" id="clq_total_no_sujetas" name="clq_total_no_sujetas" step="0.01" min="0" max="1000000" placeholder="0.00" class="form-control" onchange="updateCLQTotals();" oninput="updateCLQTotals();">
                                <small class="text-muted">Monto total de ventas no sujetas</small>
                            </div>
                        </div>
                        @endif

                        @if(request('typedocument')==2)
                        <div class="row g-3 col-12" style="margin-bottom: 3%;" id="clq-additional-fields">
                            <div class="mb-3 col-12">
                                <h6 class="mb-3 fw-bold text-primary">
                                    <i class="ti ti-file-invoice me-2"></i>Información Adicional del Comprobante de Liquidación
                                </h6>
                            </div>

                            <!-- Primera fila: Tipo Documento -->
                            <div class="col-md-3">
                                <label class="form-label fw-semibold" for="clq_tipo_documento">
                                    <i class="ti ti-file-type me-1"></i>Tipo Documento
                                </label>
                                <select class="form-select" id="clq_tipo_documento" name="clq_tipo_documento">
                                    <option value="">Seleccione...</option>
                                    <option value="01">01 - Factura</option>
                                    <option value="03">03 - CCF</option>
                                    <option value="04">04 - Nota Remisión</option>
                                    <option value="05">05 - Nota Crédito</option>
                                    <option value="06">06 - Nota Débito</option>
                                    <option value="07">07 - Comprobante Retención</option>
                                    <option value="08">08 - Comprobante Liquidación</option>
                                    <option value="09">09 - Documento Contable Liquidación</option>
                                    <option value="11">11 - Factura Exportación</option>
                                    <option value="14">14 - Factura Sujeto Excluido</option>
                                    <option value="15">15 - Comprobante Donación</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold" for="clq_tipo_generacion">
                                    <i class="ti ti-device-desktop me-1"></i>Tipo Generación
                                </label>
                                <select class="form-select" id="clq_tipo_generacion" name="clq_tipo_generacion">
                                    <option value="">Seleccione...</option>
                                    <option value="1">1 - Físico</option>
                                    <option value="2" selected>2 - Electrónico</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold" for="clq_fecha_generacion">
                                    <i class="ti ti-calendar me-1"></i>Fecha de Generación
                                </label>
                                <input type="date" id="clq_fecha_generacion" name="clq_fecha_generacion"
                                       class="form-control" value="{{ date('Y-m-d') }}">
                            </div>

                            <!-- Segunda fila: Número de Documento -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="clq_numero_documento">
                                    <i class="ti ti-hash me-1"></i>Número de Documento
                                </label>
                                <input type="text" id="clq_numero_documento" name="clq_numero_documento"
                                       class="form-control" placeholder="Código generación o correlativo">
                                <small class="text-muted">
                                    <i class="ti ti-info-circle me-1"></i>Si es electrónico: código generación. Si es físico: correlativo
                                </small>
                            </div>

                            <!-- Tercera fila: Observaciones -->
                            <div class="col-12">
                                <label class="form-label fw-semibold" for="clq_observaciones">
                                    <i class="ti ti-notes me-1"></i>Observaciones
                                </label>
                                <textarea id="clq_observaciones" name="clq_observaciones"
                                          class="form-control" rows="2"
                                          placeholder="Ingrese observaciones adicionales">Liquidación venta tercero</textarea>
                            </div>
                        </div>
                        @endif

                        <div class="row g-3 col-12" style="margin-bottom: 3%; display: none;" id="add-information-tickets">
                            <label>Información de producto</label>
                            <div class="col-sm-2">
                                <label class="form-label" for="reserva">Reserva #</label>
                                <input type="text" id="reserva" name="reserva" class="form-control" onchange="updateProductDescription()" oninput="updateProductDescription()">
                            </div>
                            <div class="col-sm-2">
                                <label class="form-label" for="ruta">Ruta</label>
                                <input type="text" id="ruta" name="ruta" class="form-control" onchange="updateProductDescription()" oninput="updateProductDescription()">
                            </div>
                            <div class="col-sm-4">
                                <label class="form-label" for="destino">Destino</label>
                                <select class="form-select select2destino" id="destino" name="destino">
                                    <option value="">Seleccione</option>
                                </select>
                            </div>
                            <div class="col-sm-4">
                                <label class="form-label" for="linea">Aerolínea</label>
                                <select class="form-select select2linea" id="linea" name="linea">
                                    <option value="">Seleccione</option>
                                </select>
                            </div>
                            <div class="col-sm-4">
                                <label class="form-label" for="Canal">Canal</label>
                                <select class="form-select select2canal" id="Canal" name="Canal">
                                    <option value="">Seleccione</option>
                                    <option value="Facebook">Facebook</option>
                                    <option value="Instagram">Instagram</option>
                                    <option value="Referido por cliente">Referido por cliente</option>
                                    <option value="Correos Masivos">Correos Masivos</option>
                                    <option value="Whatsapp">Whatsapp</option>
                                    <option value="Flyers">Flyers</option>
                                </select>
                            </div>
                        </div>
                        <!-- Detalles del producto (replicado de RomaCopies) -->
                        <div class="row g-3 col-12" style="margin-bottom: 3%; display: none;" id="add-information-products">
                            <div class="mb-4 col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0 card-title">Detalles del Producto</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="text-center col-md-4">
                                                <img id="product-image" src="" alt="Imagen del producto" class="mb-3 img-fluid" style="max-height: 200px;">
                                            </div>
                                            <div class="col-md-8">
                                                <div class="table-responsive">
                                                    <table class="table table-borderless">
                                                        <tbody>
                                                            <tr>
                                                                <th style="width: 35%">Nombre:</th>
                                                                <td id="product-name">-</td>
                                                            </tr>
                                                            <tr>
                                                                <th>Marca:</th>
                                                                <td id="product-marca">-</td>
                                                            </tr>
                                                            <tr>
                                                                <th>Proveedor:</th>
                                                                <td id="product-provider">-</td>
                                                            </tr>
                                                            <tr>
                                                                <th>Precio:</th>
                                                                <td id="product-price">$ 0.00</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Vista previa editable de descripción -->
                        <div class="col-sm-4" style="margin-top: 3%;">
                            <label class="form-label" for="product-description-edit">
                                <i class="ti ti-eye me-1"></i>Descripción del Producto (Preview)
                            </label>
                            <div class="input-group">
                                <textarea id="product-description-edit" name="product-description-edit"
                                          class="form-control" rows="2"
                                          placeholder="Selecciona un producto para ver la descripción por defecto..."></textarea>
                                <button type="button" class="btn btn-outline-secondary"
                                        onclick="restoreDefaultDescription && restoreDefaultDescription()"
                                        title="Restaurar descripción por defecto">
                                    <i class="ti ti-refresh"></i>
                                </button>
                            </div>
                            <small class="form-text text-muted">
                                <i class="ti ti-info-circle me-1"></i>Esta descripción aparecerá en la factura. Puedes editarla antes de agregar el producto.
                            </small>
                        </div>
                        <div class="col-sm-4" style="margin-bottom: 5%">
                            <button type="button" class="btn btn-primary" onclick="agregarp()">
                                <span class="ti ti-playlist-add"></span> &nbsp;&nbsp;&nbsp;Agregar
                            </button>
                        </div>
                        <div class="card-datatable table-responsive" id="resultados">
                            <div class="panel">
                                <table class="table table-sm animated table-hover table-striped table-bordered fadeIn" id="tblproduct">
                                    <thead class="bg-secondary">
                                        <tr>
                                            <th class="text-center text-white">CANT.</th>
                                            <th class="text-white">DESCRIPCION</th>
                                            <th class="text-right text-white">PRECIO UNIT.</th>
                                            <th class="text-right text-white">NO SUJETAS</th>
                                            <th class="text-right text-white">EXENTAS</th>
                                            <th class="text-right text-white">GRAVADAS</th>
                                            <th class="text-right text-white">TOTAL</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td rowspan="8" colspan="5"></td>
                                            <td class="text-right">SUMAS</td>
                                            <td class="text-center" id="sumasl">$ 0.00</td>
                                            <td class="quitar_documents"></td>
                                        </tr>
                                        @if(request('typedocument')==2 || request('typedocument')==3 || request('typedocument')==8)
                                        <tr>
                                            <td class="text-right">IVA 13%</td>
                                            <td class="text-center" id="13ival">$ 0.00</td>
                                            <td class="quitar_documents"></td>
                                        </tr>
                                        @endif
                                        @if(request('typedocument')==8)
                                        <tr>
                                            <td class="text-right">(-) Renta 10%</td>
                                            <td class="text-center" id="10rental">$ 0.00</td>
                                            <td class="quitar_documents"></td>
                                        </tr>
                                        @endif
                                        <tr>
                                            <td class="text-right">(-) IVA Retenido</td>
                                            <td class="text-center" id="ivaretenidol">$0.00</td>
                                            <td class="quitar_documents"></td>
                                        </tr>

                                        <tr>
                                            <td class="text-right">Ventas No Sujetas</td>
                                            <td class="text-center" id="ventasnosujetasl">$0.00</td>
                                            <td class="quitar_documents"></td>
                                        </tr>

                                        <tr>
                                            <td class="text-right">Ventas Exentas</td>
                                            <td class="text-center" id="ventasexentasl">$0.00</td>
                                            <td class="quitar_documents"></td>
                                        </tr>

                                        <tr>
                                            <td class="text-right">Venta Total</td>
                                            <td class="text-center" id="ventatotall">$ 0.00</td>
                                            <td class="quitar_documents"></td>
                                        </tr>
                                    </tfoot>
                                </table>

                            </div>
                        </div>
                        <div class="col-12 d-flex justify-content-between">
                            <button class="btn btn-label-secondary btn-prev"> <i class="ti ti-arrow-left me-sm-1"></i>
                                <span class="align-middle d-sm-inline-block d-none">Previous</span>
                            </button>
                            <button id="step3" class="btn btn-primary btn-next"> <span
                                    class="align-middle d-sm-inline-block d-none me-sm-1">Next</span> <i
                                    class="ti ti-arrow-right"></i></button>
                        </div>
                    </div>
                    <!-- Social Links -->
                    <div id="social-links" class="content">
                        <div class="mb-3 content-header">
                            <h6 class="mb-0">Social Links</h6>
                            <small>Enter Your Social Links.</small>
                        </div>
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="form-label" for="twitter">Twitter</label>
                                <input type="text" id="twitter" class="form-control"
                                    placeholder="https://twitter.com/abc" />
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label" for="facebook">Facebook</label>
                                <input type="text" id="facebook" class="form-control"
                                    placeholder="https://facebook.com/abc" />
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label" for="google">Google+</label>
                                <input type="text" id="google" class="form-control"
                                    placeholder="https://plus.google.com/abc" />
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label" for="linkedin">Linkedin</label>
                                <input type="text" id="linkedin" class="form-control"
                                    placeholder="https://linkedin.com/abc" />
                            </div>
                            <div class="col-12 d-flex justify-content-between">
                                <button class="btn btn-label-secondary btn-prev"> <i class="ti ti-arrow-left me-sm-1"></i>
                                    <span class="align-middle d-sm-inline-block d-none">Previous</span>
                                </button>
                                <button class="btn btn-primary btn-next"> <span
                                        class="align-middle d-sm-inline-block d-none me-sm-1">Next</span> <i
                                        class="ti ti-arrow-right"></i></button>
                            </div>
                        </div>
                    </div>
                    <!-- Review -->
                    <div id="review-submit" class="content">
                        <style type="text/css">
                            /* Formato profesional de factura - Pre-emisión */
                            .invoice-preview-container{
                                background: #ffffff;
                                border: 1px solid #e0e3e7;
                                border-radius: 8px;
                                padding: 0;
                                box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
                                margin-bottom: 30px;
                                overflow: hidden;
                                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                                max-width: 100%;
                            }
                            .invoice-header {
                                background: linear-gradient(135deg, #7367f0 0%, #5e50ee 100%);
                                color: white;
                                padding: 20px 25px;
                                border-bottom: 4px solid rgba(255, 255, 255, 0.2);
                            }
                            .invoice-header .row {
                                margin-left: -10px;
                                margin-right: -10px;
                            }
                            .invoice-header .row > [class*="col-"] {
                                padding-left: 10px;
                                padding-right: 10px;
                            }
                            .invoice-body {
                                padding: 35px 40px;
                                background: #ffffff;
                            }
                            .invoice-footer {
                                background: #f8f9fa;
                                padding: 25px 40px;
                                border-top: 2px solid #e9ecef;
                            }
                            .invoice-doc-box{
                                background: rgba(255, 255, 255, 0.15);
                                backdrop-filter: blur(10px);
                                border: 2px solid rgba(255, 255, 255, 0.3);
                                border-radius: 8px;
                                padding: 18px 12px;
                                text-align: center;
                                color: white;
                                display: flex;
                                flex-direction: column;
                                justify-content: center;
                                height: 100%;
                            }
                            .invoice-doc-box .doc-title {
                                font-size: 20px;
                                font-weight: 700;
                                letter-spacing: 1px;
                                margin-bottom: 15px;
                                text-shadow: 0 2px 4px rgba(0,0,0,0.2);
                            }
                            .invoice-doc-box .doc-field {
                                padding: 0;
                                border-bottom: none;
                                margin-bottom: 0;
                            }
                            .invoice-doc-box .doc-field:last-child {
                                border-bottom: none;
                                margin-bottom: 0;
                            }
                            .invoice-doc-box .doc-label {
                                font-size: 9px;
                                text-transform: uppercase;
                                letter-spacing: 0.5px;
                                opacity: 0.85;
                                margin-bottom: 6px;
                                display: block;
                            }
                            .invoice-doc-box .doc-value {
                                font-size: 14px;
                                font-weight: 600;
                            }
                            #logodocfinal{
                                display:block;
                                width: 100%;
                                max-width: 180px;
                                height: auto;
                                max-height: 140px;
                                object-fit: contain;
                                margin: 0 auto;
                                filter: drop-shadow(0 3px 6px rgba(0,0,0,0.15));
                            }
                            .invoice-logo-section {
                                background: rgba(255, 255, 255, 0.1);
                                backdrop-filter: blur(10px);
                                border: 2px solid rgba(255, 255, 255, 0.2);
                                border-radius: 8px;
                                padding: 20px 15px;
                                text-align: center;
                                height: 100%;
                                display: flex;
                                flex-direction: column;
                                justify-content: center;
                                align-items: center;
                            }
                            .interlineado-nulo{
                                line-height: 1.6;
                                margin: 4px 0;
                                color: rgba(255, 255, 255, 0.95);
                                font-size: 0.85em;
                            }
                            .invoice-company-info {
                                color: rgba(255, 255, 255, 0.95);
                            }
                            .invoice-company-info h4 {
                                color: white;
                                font-weight: 700;
                                font-size: 0.95rem;
                                margin-bottom: 10px;
                                letter-spacing: 0.5px;
                            }
                            .porsi{
                                border: 1px solid #e0e0e0;
                                border-radius: 12px;
                                background: #f8f9fa;
                            }
                            .cuerpodocfinal{
                                margin-top: 2%;
                                margin-bottom: 5%;
                                width: 100%;
                                background: #ffffff;
                            }
                            .invoice-info-grid {
                                display: grid;
                                grid-template-columns: 140px 1fr;
                                gap: 0;
                                border-bottom: 1px solid #e9ecef;
                                align-items: center;
                            }
                            .invoice-info-grid:last-child {
                                border-bottom: none;
                            }
                            .camplantilla{
                                padding: 8px 15px;
                                font-weight: 600;
                                color: #566a7f;
                                font-size: 0.75em;
                                vertical-align: middle;
                                background: #f8f9fa;
                                border-right: 2px solid #e0e3e7;
                                white-space: nowrap;
                            }
                            .dataplantilla{
                                padding: 8px 15px;
                                color: #283144;
                                font-size: 0.8em;
                                vertical-align: middle;
                                font-weight: 500;
                                background: #ffffff;
                            }
                            table.sample tr:last-child .dataplantilla {
                                border-bottom: none;
                            }
                            table.sample tr:hover .dataplantilla {
                                background: #f8f9fa;
                                transition: background 0.2s ease;
                            }
                            table.desingtable{
                                margin: 2%;
                            }
                            table.sample {
                                margin: 0;
                                width: 100%;
                                border-collapse: collapse;
                                background: #ffffff;
                            }
                            .invoice-section-title {
                                font-size: 0.875rem;
                                font-weight: 700;
                                color: #566a7f;
                                margin-bottom: 12px;
                                padding-bottom: 8px;
                                border-bottom: 2px solid #e9ecef;
                                text-transform: uppercase;
                                letter-spacing: 0.5px;
                            }
                            .invoice-card {
                                background: #ffffff;
                                border: 1px solid #e0e3e7;
                                border-radius: 8px;
                                padding: 0;
                                overflow: hidden;
                            }
                            .details_products_documents{
                                width: 100%;
                                margin-top: 20px;
                            }
                            .table_details{
                                width: 100%;
                                border-collapse: collapse;
                                background: #ffffff;
                                margin: 0;
                            }
                            .head_details{
                                background: linear-gradient(135deg, #7367f0 0%, #5e50ee 100%);
                                color: white;
                            }
                            .head_details th {
                                padding: 14px 12px;
                                text-align: center;
                                font-weight: 600;
                                color: white;
                                border: none;
                                font-size: 0.875rem;
                                text-transform: uppercase;
                                letter-spacing: 0.5px;
                            }
                            .th_details{
                                text-align: center;
                                font-weight: 600;
                                color: #566a7f;
                                padding: 12px 10px;
                                background: #f8f9fa;
                                border-bottom: 2px solid #dee2e6;
                                font-size: 0.85rem;
                            }
                            .td_details{
                                padding: 12px 10px;
                                border-bottom: 1px solid #f0f0f0;
                                color: #283144;
                                font-size: 0.875rem;
                                vertical-align: middle;
                            }
                            .table_details tbody tr:hover {
                                background: #f8f9fa;
                                transition: background 0.2s ease;
                            }
                            .table_details tbody tr:last-child .td_details {
                                border-bottom: none;
                            }
                            .tfoot_details{
                                border-top: 3px solid #7367f0;
                                padding: 20px;
                                margin-top: 0;
                                text-align: right;
                                background: #f8f9fa;
                            }
                            .tfoot_details tr {
                                border-bottom: 1px solid #e0e3e7;
                            }
                            .tfoot_details tr:last-child {
                                border-bottom: none;
                                border-top: 2px solid #7367f0;
                                background: #ffffff;
                            }
                            .tfoot_details td {
                                padding: 10px 15px;
                                font-size: 0.95rem;
                                color: #283144;
                            }
                            .tfoot_details strong {
                                color: #7367f0;
                                font-size: 1.15em;
                                font-weight: 700;
                            }
                            /* Estilos para modo oscuro */
                            .dark-layout .invoice-preview-container {
                                background: #2f3349;
                                border-color: #434968;
                            }
                            .dark-layout .invoice-body {
                                background: #2f3349;
                            }
                            .dark-layout .invoice-footer {
                                background: #25293c;
                                border-top-color: #434968;
                            }
                            .dark-layout .invoice-card {
                                background: #363b52;
                                border-color: #434968;
                            }
                            .dark-layout .invoice-section-title {
                                color: #cfd3ec;
                                border-bottom-color: #434968;
                            }
                            .dark-layout .camplantilla {
                                background: #25293c;
                                color: #cfd3ec;
                                border-right-color: #434968;
                            }
                            .dark-layout .dataplantilla {
                                background: #363b52;
                                color: #b6bee3;
                            }
                            .dark-layout .invoice-info-grid {
                                border-bottom-color: #434968;
                            }
                            .dark-layout .th_details {
                                background: #25293c;
                                color: #cfd3ec;
                                border-bottom-color: #434968;
                            }
                            .dark-layout .td_details {
                                background: #363b52;
                                color: #b6bee3;
                                border-bottom-color: #434968;
                            }
                            .dark-layout .table_details tbody tr:hover {
                                background: #3b4253;
                            }
                            .dark-layout .tfoot_details {
                                background: #25293c;
                                border-top-color: #7367f0;
                            }
                            .dark-layout .tfoot_details tr:last-child {
                                background: #2f3349;
                            }
                            .dark-layout .tfoot_details td {
                                color: #b6bee3;
                            }
                            .dark-layout .tfoot_details strong {
                                color: #9e95f5;
                            }
                        </style>

                        <!-- Vista previa profesional - Pre-emisión de factura -->
                        <div class="invoice-preview-container">
                            <!-- Header con fondo morado -->
                            <div class="invoice-header">
                                <div class="row align-items-center g-4">
                                    <div class="col-md-3">
                                        <div class="invoice-logo-section">
                                            <img id="logodocfinal" src="" alt="Logo">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="invoice-company-info">
                                            <h4><i class="ti ti-building me-2"></i>INFORMACIÓN DE LA EMPRESA</h4>
                                            <p class="interlineado-nulo" id="addressdcfinal" style="font-weight: 500; margin-bottom: 8px;"></p>
                                            <p class="interlineado-nulo" id="phonedocfinal" style="margin-bottom: 8px;">
                                                <i class="ti ti-phone me-2"></i>
                                            </p>
                                            <p class="interlineado-nulo" id="emaildocfinal" style="margin-bottom: 8px;">
                                                <i class="ti ti-mail me-2"></i>
                                            </p>
                                            <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid rgba(255, 255, 255, 0.2);">
                                                <p class="interlineado-nulo" style="margin-bottom: 6px;">
                                                    <span class="doc-label" style="display: inline-block; margin-right: 8px;">NCR:</span>
                                                    <span id="NCR_details" style="font-weight: 600;">NCR: </span>
                                                </p>
                                                <p class="interlineado-nulo">
                                                    <span class="doc-label" style="display: inline-block; margin-right: 8px;">NIT:</span>
                                                    <span id="NIT_details" style="font-weight: 600;">NIT: </span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="invoice-doc-box">
                                            <div class="doc-title" id="name_type_documents_details">FACTURA</div>
                                            <div class="doc-field">
                                                <span class="doc-label">Número</span>
                                                <span class="doc-value" id="corr_details">1792067464001</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Cuerpo de la factura -->
                            <div class="invoice-body">

                                <!-- Información del Cliente -->
                                <div class="mb-3 row">
                                    <div class="mb-2 col-12">
                                        <h5 class="invoice-section-title">
                                            <i class="ti ti-user me-2" style="color: #7367f0;"></i>DATOS DEL CLIENTE
                                        </h5>
                                    </div>
                                    <div class="mb-2 col-md-6">
                                        <div class="invoice-card">
                                            <div style="padding: 12px;">
                                                <div class="invoice-info-grid">
                                                    <div class="camplantilla"><strong>Señor (es):</strong></div>
                                                    <div class="dataplantilla" id="name_client"></div>
                                                </div>
                                                <div class="invoice-info-grid">
                                                    <div class="camplantilla"><strong>Dirección:</strong></div>
                                                    <div class="dataplantilla" id="address_doc"></div>
                                                </div>
                                                <div class="invoice-info-grid">
                                                    <div class="camplantilla"><strong>Municipio:</strong></div>
                                                    <div class="dataplantilla" id="municipio_name"></div>
                                                </div>
                                                <div class="invoice-info-grid">
                                                    <div class="camplantilla"><strong>Departamento:</strong></div>
                                                    <div class="dataplantilla" id="departamento_name"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-2 col-md-6">
                                        <div class="invoice-card">
                                            <div style="padding: 12px;">
                                                <div class="invoice-info-grid">
                                                    <div class="camplantilla"><strong>Fecha:</strong></div>
                                                    <div class="dataplantilla" id="date_doc"></div>
                                                </div>
                                                <div class="invoice-info-grid">
                                                    <div class="camplantilla"><strong>DUI o NIT:</strong></div>
                                                    <div class="dataplantilla" id="duinit"></div>
                                                </div>
                                                <div class="invoice-info-grid">
                                                    <div class="camplantilla"><strong>Giro:</strong></div>
                                                    <div class="dataplantilla" id="giro_name"></div>
                                                </div>
                                                <div class="invoice-info-grid">
                                                    <div class="camplantilla"><strong>Forma de pago:</strong></div>
                                                    <div class="dataplantilla" id="forma_pago_name"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12" id="acuenta_de_container" style="display: none;">
                                        <div style="background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%); border: 2px solid #ffc107; border-radius: 8px; padding: 15px 20px; border-left: 4px solid #f39c12;">
                                            <strong style="color: #856404; font-size: 0.95em;">
                                                <i class="ti ti-info-circle me-2"></i>Venta a cuenta de:
                                            </strong>
                                            <span id="acuenta_de" style="color: #856404; margin-left: 10px; font-weight: 600;"></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tabla de Productos -->
                                <div class="row">
                                    <div class="mb-3 col-12">
                                        <h5 class="invoice-section-title">
                                            <i class="ti ti-shopping-cart me-2" style="color: #7367f0;"></i>DETALLE DE PRODUCTOS
                                        </h5>
                                    </div>
                                    <div class="col-12">
                                        <div class="invoice-card">
                                            <div class="details_products_documents" id="details_products_documents">
                                                <!-- Los productos se cargarán aquí dinámicamente -->
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Fin del cuerpo -->

                            <!-- Footer con totales -->
                            <div class="invoice-footer" id="invoice-footer" style="display: none;">
                                <!-- Los totales se mostrarán aquí -->
                            </div>
                        </div>
                        <!-- Fin vista previa profesional -->

                        <div class="col-12 d-flex justify-content-between" style="margin-top: 3%;">
                            <button class="btn btn-label-secondary btn-prev"> <i class="ti ti-arrow-left me-sm-1"></i>
                                <span class="align-middle d-sm-inline-block d-none">Previous</span>
                            </button>
                            <button class="btn btn-success btn-submit">Presentar Hacienda</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- /Default Icons Wizard -->
    </div>

    <!-- Modal de Progreso para Emisión de DTEs -->
    <div class="modal fade" id="modalProgressDTE" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="text-white modal-header bg-primary">
                    <h5 class="modal-title">
                        <i class="ti ti-refresh ti-spin me-2"></i>Emitiendo DTEs a Hacienda
                    </h5>
                </div>
                <div class="modal-body">
                    <div class="mb-4 text-center">
                        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                            <span class="visually-hidden">Procesando...</span>
                        </div>
                        <p class="mt-3 fw-bold" id="progress-main-text">Iniciando emisión...</p>
                    </div>

                    <div id="progress-list" class="mt-4">
                        <!-- Aquí se mostrarán los DTEs en progreso -->
                    </div>

                    <div class="mt-4 progress" style="height: 25px;">
                        <div id="progress-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary"
                             role="progressbar" style="width: 0%">
                            <span id="progress-percent">0%</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="display: none;" id="progress-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeProgressModal()">Cerrar</button>
                    <a href="/sale/index" class="btn btn-primary">Ir a Listado de Ventas</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para buscar documentos -->
    <div class="modal fade" id="modalBuscarDocumento" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Buscar Factura o CCF</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3 row">
                        <div class="col-md-2">
                            <label class="form-label">Tipo Documento</label>
                            <select class="form-select" id="search_tipo_documento">
                                <option value="">Todos</option>
                                <option value="6">Factura</option>
                                <option value="3">CCF</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Número Documento</label>
                            <input type="text" class="form-control" id="search_numero_doc" placeholder="ID de venta">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Fecha Desde</label>
                            <input type="date" class="form-control" id="search_fecha_desde" value="{{ date('Y-m-01') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Fecha Hasta</label>
                            <input type="date" class="form-control" id="search_fecha_hasta" value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Cliente</label>
                            <input type="text" class="form-control" id="search_cliente" placeholder="Nombre cliente">
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">&nbsp;</label>
                            <button type="button" class="btn btn-primary w-100" id="btn-search-docs">
                                <i class="ti ti-search me-1"></i>Buscar
                            </button>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">&nbsp;</label>
                            <button type="button" class="btn btn-secondary w-100" id="btn-search-all" title="Ver todos los documentos">
                                <i class="ti ti-list me-1"></i>Todos
                            </button>
                        </div>
                    </div>
                    <div class="alert alert-info" role="alert">
                        <small><i class="ti ti-info-circle me-1"></i><strong>Tip:</strong> Usa "Todos" para ver los últimos 100 documentos sin filtros. Puedes buscar con Enter en cualquier campo.</small>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover" id="tabla-documentos-busqueda">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tipo</th>
                                    <th>Fecha</th>
                                    <th>Cliente / Tercero</th>
                                    <th>Total</th>
                                    <th>Estado</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody id="tbody-documentos-busqueda">
                                <tr>
                                    <td colspan="7" class="text-center text-muted">
                                        <i class="ti ti-info-circle me-2"></i>
                                        <strong>Busca documentos a terceros pendientes de liquidar</strong>
                                        <br><small class="text-info">Solo se mostrarán facturas/CCF donde el tercero (proveedor) coincide con el cliente seleccionado en este CLQ</small>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>
@endsection
