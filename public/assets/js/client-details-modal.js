/**
 * Modal de Detalles del Cliente
 */

// Función para mostrar los detalles del cliente
function showClientDetails(clientId) {
    // Mostrar loading
    showLoading();

    // Hacer petición AJAX para obtener los datos del cliente
    $.ajax({
        url: `/client/getClientid/${btoa(clientId.toString())}`,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response && response.length > 0) {
                const client = response[0];
                populateClientModal(client);
                $('#clientDetailsModal').modal('show');
            } else {
                Swal.fire('Error', 'No se encontraron datos del cliente', 'error');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error al cargar datos del cliente:', error);
            Swal.fire('Error', 'Error al cargar los datos del cliente', 'error');
        },
        complete: function() {
            hideLoading();
        }
    });
}

// Función para poblar el modal con los datos del cliente
function populateClientModal(client) {
    // Determinar tipo de persona
    const isNatural = client.tpersona === 'N';
    const isJuridica = client.tpersona === 'J';
    const isExtranjero = client.extranjero === '1';

    // Mostrar/ocultar campos según tipo de persona
    showHideFields(isNatural, isJuridica, isExtranjero);

    // Información Personal
    $('#clientTypePerson').text(isNatural ? 'NATURAL' : 'JURÍDICA');

    if (isNatural) {
        // Campos para Persona Natural
        $('#clientFirstName').text(client.firstname || '-');
        $('#clientSecondName').text(client.secondname || '-');
        $('#clientFirstLastName').text(client.firstlastname || '-');
        $('#clientSecondLastName').text(client.secondlastname || '-');
    } else if (isJuridica) {
        // Campos para Persona Jurídica
        $('#clientNameContribuyente').text(client.name_contribuyente || '-');
        $('#clientComercialName').text(client.comercial_name || '-');
    }

    // Información de Contacto
    $('#clientEmail').text(client.email || '-');
    $('#clientPhone').text(client.phone || '-');
    $('#clientPhoneFixed').text(client.phone_fijo || '-');
    $('#clientAddress').text(formatAddress(client));

    // Información Fiscal
    $('#clientNit').text(client.nit || '-');

    if (isJuridica) {
        $('#clientNcr').text(client.ncr || '-');
    } else if (isNatural && isExtranjero) {
        $('#clientPassport').text(client.pasaporte || '-');
    } else if (isNatural) {
        $('#clientDui').text(client.nit || '-'); // Para naturales, el NIT es el DUI
    }

    // Campos booleanos con colores
    setBooleanField('clientContribuyente', client.contribuyente === '1' || isJuridica);
    setBooleanField('clientExtranjero', isExtranjero);
    setBooleanField('clientAgenteRetencion', client.agente_retencion === '1');

    // Información Adicional
    $('#clientTipoContribuyente').text(getTipoContribuyente(client.tipoContribuyente));
    $('#clientActividadEconomica').text(client.econo || '-');
    $('#clientRepresentanteLegal').text(client.legal || 'N/A');
    $('#clientBirthday').text(client.birthday || '-');
}

// Función para mostrar/ocultar campos según el tipo de persona
function showHideFields(isNatural, isJuridica, isExtranjero) {
    // Campos de información personal
    if (isNatural) {
        $('#naturalFields').show();
        $('#juridicaFields').hide();
    } else if (isJuridica) {
        $('#naturalFields').hide();
        $('#juridicaFields').show();
    }

    // Campos fiscales
    if (isJuridica) {
        $('#ncrField').show();
        $('#duiField').hide();
        $('#passportField').hide();
    } else if (isNatural && isExtranjero) {
        $('#ncrField').hide();
        $('#duiField').hide();
        $('#passportField').show();
    } else if (isNatural) {
        $('#ncrField').hide();
        $('#duiField').show();
        $('#passportField').hide();
    }
}

// Función para formatear la dirección
function formatAddress(client) {
    const parts = [];
    if (client.pais) parts.push(client.pais);
    if (client.departamento) parts.push(client.departamento);
    if (client.municipio) parts.push(client.municipio);
    if (client.address) parts.push(client.address);

    return parts.length > 0 ? parts.join(', ') : '-';
}

// Función para establecer campos booleanos con colores
function setBooleanField(elementId, value) {
    const element = $(`#${elementId}`);
    const span = element.find('span');

    if (value) {
        element.removeClass('bg-light').addClass('bg-success text-white');
        span.text('Sí');
    } else {
        element.removeClass('bg-light').addClass('bg-danger text-white');
        span.text('No');
    }
}

// Función para obtener el tipo de contribuyente
function getTipoContribuyente(tipo) {
    const tipos = {
        'GRA': 'GRANDE',
        'MED': 'MEDIANO',
        'PEQU': 'PEQUEÑO',
        'OTR': 'OTRO'
    };
    return tipos[tipo] || tipo || '-';
}

// Función para mostrar loading
function showLoading() {
    // Implementar loading si es necesario
    console.log('Loading...');
}

// Función para ocultar loading
function hideLoading() {
    // Implementar ocultar loading si es necesario
    console.log('Loading complete');
}

// Event listener para el modal cuando se cierra
$('#clientDetailsModal').on('hidden.bs.modal', function () {
    // Limpiar datos si es necesario
    console.log('Modal cerrado');
});
