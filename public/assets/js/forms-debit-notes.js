$(function () {
    'use strict';

    var form = $('#debitNoteForm');

    // Funciones de utilidad
    function showError(message, title = 'Error') {
        Swal.fire({
            title: title,
            text: message,
            icon: 'error',
            confirmButtonText: 'Entendido',
            customClass: {
                confirmButton: 'btn btn-primary'
            },
            buttonsStyling: false
        });
    }

    function clearErrors(element = null) {
        if (element) {
            element.removeClass('is-invalid');
            element.siblings('.invalid-feedback').remove();
        } else {
            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').remove();
        }
    }

    function showLoading() {
        Swal.fire({
            title: 'Procesando...',
            text: 'Creando nota de débito y enviando a Hacienda',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
    }

    function hideLoading() {
        Swal.close();
    }

    // Validación y envío del formulario
    form.on('submit', function (e) {
        e.preventDefault();

        clearErrors();

        // Validaciones específicas para notas de débito
        var motivo = $('#motivo').val();
        var productosSeleccionados = $('.product-checkbox:checked').length;

        if (!motivo.trim()) {
            showError('Debe especificar el motivo de la nota de débito.');
            $('#motivo').addClass('is-invalid');
            return false;
        }

        if (productosSeleccionados === 0) {
            showError('Debe seleccionar al menos un producto para la nota de débito.');
            return false;
        }

        // Validar que haya cantidades válidas
        var hasValidQuantities = false;
        var hasErrors = false;

        $('.product-checkbox:checked').each(function() {
            var row = $(this).closest('tr');
            var cantidad = parseFloat(row.find('.cantidad-input').val()) || 0;
            var precio = parseFloat(row.find('.precio-input').val()) || 0;

            if (cantidad <= 0) {
                hasErrors = true;
                row.find('.cantidad-input').addClass('is-invalid');
            } else {
                hasValidQuantities = true;
                row.find('.cantidad-input').removeClass('is-invalid');
            }

            if (precio <= 0) {
                hasErrors = true;
                row.find('.precio-input').addClass('is-invalid');
            } else {
                row.find('.precio-input').removeClass('is-invalid');
            }
        });

        if (hasErrors) {
            showError('Verifique las cantidades y precios de los productos seleccionados. Todos deben ser mayores a cero.');
            return false;
        }

        if (!hasValidQuantities) {
            showError('Debe especificar cantidades válidas para los productos seleccionados.');
            return false;
        }

        // Deshabilitar productos no seleccionados
        $('.product-checkbox').each(function() {
            if (!$(this).is(':checked')) {
                $(this).closest('tr').find('input, select').prop('disabled', true);
            }
        });

        // Mostrar loading y enviar formulario
        showLoading();

        // Envío via AJAX
        var formData = new FormData(this);

        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                hideLoading();

                Swal.fire({
                    title: '¡Éxito!',
                    text: 'Nota de débito creada exitosamente.',
                    icon: 'success',
                    confirmButtonText: 'Ver Nota',
                    showCancelButton: true,
                    cancelButtonText: 'Ir a Lista',
                    customClass: {
                        confirmButton: 'btn btn-primary',
                        cancelButton: 'btn btn-outline-secondary'
                    },
                    buttonsStyling: false
                }).then((result) => {
                    if (response.redirect) {
                        window.location.href = response.redirect;
                    } else if (result.isConfirmed && response.debit_note_id) {
                        window.location.href = '/debit-notes/show/' + response.debit_note_id;
                    } else {
                        window.location.href = '/debit-notes';
                    }
                });
            },
            error: function(xhr) {
                hideLoading();

                // Re-habilitar campos deshabilitados
                $('.product-checkbox').each(function() {
                    $(this).closest('tr').find('input, select').prop('disabled', false);
                });

                var errorMessage = 'Error al crear la nota de débito.';

                if (xhr.responseJSON) {
                    if (xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (xhr.responseJSON.errors) {
                        var errors = xhr.responseJSON.errors;
                        var errorText = '';

                        Object.keys(errors).forEach(function(key) {
                            if (Array.isArray(errors[key])) {
                                errorText += errors[key].join('\n') + '\n';
                            } else {
                                errorText += errors[key] + '\n';
                            }
                        });

                        errorMessage = errorText || errorMessage;
                    }
                } else if (xhr.responseText) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        errorMessage = response.message || errorMessage;
                    } catch (e) {
                        // Si no es JSON válido, usar mensaje por defecto
                    }
                }

                showError(errorMessage, 'Error al Crear Nota de Débito');
            }
        });
    });

    // Función para calcular IVA de una fila
    function calculateRowIVA(row) {
        var cantidad = parseFloat(row.find('.cantidad-input').val()) || 0;
        var precio = parseFloat(row.find('.precio-input').val()) || 0;
        var tipoVenta = row.find('.tipo-venta-select').val();
        var subtotal = cantidad * precio;
        var iva = 0;

        if (tipoVenta === 'gravada') {
            iva = subtotal * 0.13;
        }

        row.find('.iva-display').text('$' + iva.toFixed(2));
        row.find('.subtotal-display').text('$' + subtotal.toFixed(2));
    }

    // Función para calcular totales generales
    function calculateTotals() {
        var subtotalGravado = 0;
        var subtotalExento = 0;
        var subtotalNoSujeto = 0;
        var iva = 0;

        $('.product-checkbox:checked').each(function() {
            var row = $(this).closest('tr');
            var cantidad = parseFloat(row.find('.cantidad-input').val()) || 0;
            var precio = parseFloat(row.find('.precio-input').val()) || 0;
            var tipoVenta = row.find('.tipo-venta-select').val();
            var subtotal = cantidad * precio;

            if (tipoVenta === 'gravada') {
                subtotalGravado += subtotal;
                iva += subtotal * 0.13;
            } else if (tipoVenta === 'exenta') {
                subtotalExento += subtotal;
            } else if (tipoVenta === 'nosujeta') {
                subtotalNoSujeto += subtotal;
            }
        });

        var total = subtotalGravado + subtotalExento + subtotalNoSujeto + iva;

        $('#subtotalGravado').text('$' + subtotalGravado.toFixed(2));
        $('#iva').text('$' + iva.toFixed(2));
        $('#subtotalExento').text('$' + subtotalExento.toFixed(2));
        $('#subtotalNoSujeto').text('$' + subtotalNoSujeto.toFixed(2));
        $('#total').text('$' + total.toFixed(2));
    }

    // Event listeners para cálculos automáticos
    $(document).on('input change', '.cantidad-input, .precio-input, .tipo-venta-select', function() {
        var row = $(this).closest('tr');
        calculateRowIVA(row);
        calculateTotals();
        clearErrors($(this));
    });

    $(document).on('change', '.product-checkbox', function() {
        calculateTotals();
    });

    // Seleccionar/deseleccionar todos
    $('#selectAll').on('change', function() {
        $('.product-checkbox').prop('checked', this.checked);
        calculateTotals();
    });

    // Validación en tiempo real para motivo
    $('#motivo').on('input', function() {
        clearErrors($(this));
        if ($(this).val().trim().length > 0) {
            $(this).removeClass('is-invalid').addClass('is-valid');
        }
    });

    // Inicializar cálculos
    calculateTotals();

    // Inicializar Select2 si está disponible
    if ($.fn.select2) {
        $('#company_id, #typedocument_id, #sale_id').select2({
            placeholder: 'Seleccionar...',
            allowClear: true
        });
    }

    // Confirmar salida si hay cambios
    var formModified = false;

    form.find('input, select, textarea').on('change input', function() {
        formModified = true;
    });

    $(window).on('beforeunload', function() {
        if (formModified) {
            return 'Tiene cambios sin guardar. ¿Está seguro de que desea salir?';
        }
    });

    // Remover alerta al enviar formulario
    form.on('submit', function() {
        formModified = false;
    });
});

