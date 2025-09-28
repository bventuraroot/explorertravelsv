$(function () {
    'use strict';

    // Variables
    var form = $('#creditNoteForm');
    var select2 = $('.select2');

    // Funciones auxiliares para manejo de errores
    function showError(title, messages) {
        var errorHtml = '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
        errorHtml += '<i class="ti ti-alert-circle me-2"></i>';
        errorHtml += '<strong>' + title + '</strong>';

        if (Array.isArray(messages)) {
            errorHtml += '<ul class="mb-0 mt-2">';
            messages.forEach(function(message) {
                errorHtml += '<li>' + message + '</li>';
            });
            errorHtml += '</ul>';
        } else {
            errorHtml += '<div class="mt-2">' + messages + '</div>';
        }

        errorHtml += '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        errorHtml += '</div>';

        // Insertar al inicio del formulario
        form.find('.card-body').prepend(errorHtml);

        // Scroll al error
        $('html, body').animate({
            scrollTop: form.find('.alert-danger').first().offset().top - 100
        }, 500);
    }

    function clearErrors() {
        form.find('.alert-danger').remove();
    }

    function showLoading() {
        var submitBtn = form.find('button[type="submit"]');
        submitBtn.prop('disabled', true);
        submitBtn.html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Procesando...');
    }

    function hideLoading() {
        var submitBtn = form.find('button[type="submit"]');
        submitBtn.prop('disabled', false);
        submitBtn.html('<i class="ti ti-check me-1"></i>Crear Nota de Crédito');
    }

    // Form Validation
    if (form.length) {
        form.on('submit', function (e) {
            e.preventDefault();

            // Limpiar errores anteriores
            clearErrors();

            // Validar que al menos un producto esté seleccionado
            var checkedProducts = $('.product-checkbox:checked').length;
            if (checkedProducts === 0) {
                showError('Error de validación', 'Debe seleccionar al menos un producto para la nota de crédito.');
                return false;
            }

            // Validar que los productos seleccionados tengan cantidad > 0 y no excedan el original
            var isValid = true;
            var errorMessages = [];

            $('.product-checkbox:checked').each(function() {
                var row = $(this).closest('tr');
                var cantidad = parseFloat(row.find('input[name*="[cantidad]"]').val()) || 0;
                var precio = parseFloat(row.find('input[name*="[precio]"]').val()) || 0;
                var productName = row.find('td:nth-child(3)').text().trim();
                var cantidadOriginal = parseFloat(row.data('original-cant')) || 0;

                if (cantidad <= 0) {
                    isValid = false;
                    errorMessages.push('El producto "' + productName + '" debe tener una cantidad mayor a 0.');
                }

                if (cantidad > cantidadOriginal) {
                    isValid = false;
                    errorMessages.push('El producto "' + productName + '" no puede exceder la cantidad original de ' + cantidadOriginal + '.');
                }
            });

            if (!isValid) {
                showError('Errores de validación:', errorMessages);
                return false;
            }

            // Mostrar indicador de carga
            showLoading();

            // Enviar formulario normalmente (no AJAX en este caso)
            this.submit();
        });
    }

    // Select2
    if (select2.length) {
        select2.wrap('<div class="position-relative"></div>').select2({
            placeholder: 'Seleccionar...',
            dropdownParent: select2.parent()
        });
    }

    // Función para calcular IVA de una fila específica
    function calculateRowIVA(row) {
        const cantidad = parseFloat(row.find('input[name*="[cantidad]"]').val()) || 0;
        // precio siempre es el precio original (readonly)
        const precio = parseFloat(row.data('original-price')) || parseFloat(row.find('input[name*="[precio]"]').val()) || 0;
        const tipoVenta = row.find('select[name*="[tipo_venta]"]').val();
        const subtotal = cantidad * precio;

        let iva = 0;
        if (tipoVenta === 'gravada') {
            iva = subtotal * 0.13;
        }

        row.find('.iva-display').text('$' + iva.toFixed(2));
    }

    // Función para calcular totales
    function calculateTotals() {
        let subtotalGravado = 0;
        let subtotalExento = 0;
        let subtotalNoSujeto = 0;
        let iva = 0;

        $('.product-checkbox:checked').each(function() {
            const row = $(this).closest('tr');
            const cantidad = parseFloat(row.find('input[name*="[cantidad]"]').val()) || 0;
            const precio = parseFloat(row.data('original-price')) || parseFloat(row.find('input[name*="[precio]"]').val()) || 0;
            const tipoVenta = row.find('select[name*="[tipo_venta]"]').val();
            const subtotal = cantidad * precio;

            if (tipoVenta === 'gravada') {
                subtotalGravado += subtotal;
                iva += subtotal * 0.13;
            } else if (tipoVenta === 'exenta') {
                subtotalExento += subtotal;
            } else if (tipoVenta === 'nosujeta') {
                subtotalNoSujeto += subtotal;
            }
        });

        const total = subtotalGravado + subtotalExento + subtotalNoSujeto + iva;

        $('#subtotalGravado').text('$' + subtotalGravado.toFixed(2));
        $('#iva').text('$' + iva.toFixed(2));
        $('#subtotalExento').text('$' + subtotalExento.toFixed(2));
        $('#subtotalNoSujeto').text('$' + subtotalNoSujeto.toFixed(2));
        $('#total').text('$' + total.toFixed(2));
    }

    // Event listeners para calcular totales
    $(document).on('input', 'input[name*="[cantidad]"], input[name*="[precio]"], select[name*="[tipo_venta]"]', function() {
        const row = $(this).closest('tr');
        calculateRowIVA(row);
        calculateTotals();
    });

    $(document).on('change', '.product-checkbox', function() {
        calculateTotals();
    });

    // Seleccionar todos los productos
    $('#selectAll').on('change', function() {
        $('.product-checkbox').prop('checked', this.checked);
        // Recalcular IVA de todas las filas
        $('.product-checkbox').each(function() {
            const row = $(this).closest('tr');
            calculateRowIVA(row);
        });
        calculateTotals();
    });

    // Validación en tiempo real
    $(document).on('input', 'input[name*="[cantidad]"], input[name*="[precio]"]', function() {
        var value = parseFloat($(this).val());
        if (value < 0) {
            $(this).val(0);
        }

        // Validar la fila completa
        var row = $(this).closest('tr');
        validateRow(row);
    });

    // Función para validar una fila en tiempo real
    function validateRow(row) {
        var cantidad = parseFloat(row.find('input[name*="[cantidad]"]').val()) || 0;
        var precio = parseFloat(row.data('original-price')) || parseFloat(row.find('input[name*="[precio]"]').val()) || 0;
        var cantidadOriginal = parseFloat(row.data('original-cant')) || 0;
        var isChecked = row.find('.product-checkbox').is(':checked');

        // Remover clases de error anteriores
        row.find('input[name*="[cantidad]"], input[name*="[precio]"]').removeClass('is-invalid');
        row.find('.invalid-feedback').remove();

        if (isChecked) {
            if (cantidad <= 0) {
                row.find('input[name*="[cantidad]"]').addClass('is-invalid');
                row.find('input[name*="[cantidad]"]').after('<div class="invalid-feedback">La cantidad debe ser mayor a 0</div>');
            }
            if (cantidad > cantidadOriginal) {
                row.find('input[name*="[cantidad]"]').addClass('is-invalid');
                row.find('input[name*="[cantidad]"]').after('<div class="invalid-feedback">No puede superar la cantidad original (' + cantidadOriginal + ')</div>');
            }
        }
    }

    // Calcular IVA inicial de todas las filas
    $('.product-checkbox').each(function() {
        const row = $(this).closest('tr');
        calculateRowIVA(row);
    });

    // Calcular totales iniciales
    calculateTotals();

    // Auto-seleccionar tipo de documento si solo hay una opción
    $(document).ready(function() {
        var tipoSelect = $('#typedocument_id');
        var options = tipoSelect.find('option[value!=""]');

        if (options.length === 1) {
            // Si solo hay una opción (Nota de Crédito), auto-seleccionarla
            options.first().prop('selected', true);
            tipoSelect.trigger('change');
        }
    });
});
