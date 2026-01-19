/**
 * Page sale List - ExplorerTravelSV
 * Replicado desde RomaCopies con mejoras
 */

'use strict';

// Configurar AJAX para esta página específicamente
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
        'X-Requested-With': 'XMLHttpRequest'
    },
    xhrFields: {
        withCredentials: true
    }
});

// Función helper para extraer mensajes de respuestas del backend
function getResponseMessage(response, defaultMessage = 'Error desconocido') {
    if (typeof response === 'object' && response !== null) {
        // Si es un objeto con propiedad message
        if (response.message) {
            return response.message;
        }
        // Si es un objeto con propiedad descripcionMsg (respuestas de Hacienda)
        if (response.descripcionMsg) {
            return response.descripcionMsg;
        }
        // Si es un objeto con propiedad text
        if (response.text) {
            return response.text;
        }
        // Si es un objeto con propiedad error
        if (response.error) {
            return response.error;
        }
    }

    // Si es una cadena de texto
    if (typeof response === 'string') {
        return response;
    }

    // Si es un número o boolean, convertirlo a string
    if (typeof response === 'number' || typeof response === 'boolean') {
        return response.toString();
    }

    return defaultMessage;
}

// Datatable (jquery)
$(function () {

  let borderColor, bodyBg, headingColor;

  if (isDarkStyle) {
    borderColor = config.colors_dark.borderColor;
    bodyBg = config.colors_dark.bodyBg;
    headingColor = config.colors_dark.headingColor;
  } else {
    borderColor = config.colors.borderColor;
    bodyBg = config.colors.bodyBg;
    headingColor = config.colors.headingColor;
  }

  // Variable declaration for table
  var dt_sale_table = $('.datatables-sale');
  // Client datatable
  if (dt_sale_table.length) {
    var dt_sale = dt_sale_table.DataTable({
      columnDefs: [
        {
          // For Responsive - desactivado para mostrar todas las columnas
          className: 'control',
          searchable: false,
          orderable: false,
          responsivePriority: 0,
          targets: 0
        },
        {
          // Hacer todas las columnas buscables excepto acciones
          targets: '_all',
          searchable: true
        }
      ],
      responsive: false, // Desactivar responsividad automática
      autoWidth: false, // Desactivar ajuste automático de ancho
      scrollX: false, // Desactivar scroll horizontal para evitar problemas con dropdowns
      order: [[2, 'desc']], // Ordenar por fecha (columna 2, índice 2)
      dom:
        '<"row me-2 mb-3"' +
        '<"col-md-3"<"me-3"l>>' +
        '<"col-md-9"<"dt-action-buttons text-xl-end text-lg-start text-md-end text-start d-flex align-items-center justify-content-end flex-md-row flex-column mb-3 mb-md-0"fB>>' +
        '>t' +
        '<"row mx-2 mt-3"' +
        '<"col-sm-12 col-md-6"i>' +
        '<"col-sm-12 col-md-6"p>' +
        '>',
      buttons: [
        {
          extend: 'collection',
          className: 'btn btn-label-secondary dropdown-toggle mx-3',
          text: '<i class="ti ti-screen-share me-1 ti-xs"></i>Exportar',
          buttons: [
            {
              extend: 'print',
              text: '<i class="ti ti-printer me-2 ti-xs"></i>Imprimir',
              className: 'dropdown-item',
              exportOptions: { columns: [1, 2, 3, 4, 5, 6, 7] }
            },
            {
              extend: 'csv',
              text: '<i class="ti ti-file-text me-2 ti-xs"></i>Csv',
              className: 'dropdown-item',
              exportOptions: { columns: [1, 2, 3, 4, 5, 6, 7] }
            },
            {
              extend: 'excel',
              text: '<i class="ti ti-file-spreadsheet me-2 ti-xs"></i>Excel',
              className: 'dropdown-item',
              exportOptions: { columns: [1, 2, 3, 4, 5, 6, 7] }
            },
            {
              extend: 'pdf',
              text: '<i class="ti ti-file-type-pdf me-2 ti-xs"></i>Pdf',
              className: 'dropdown-item',
              exportOptions: { columns: [1, 2, 3, 4, 5, 6, 7] }
            },
            {
              extend: 'copy',
              text: '<i class="ti ti-copy me-2 ti-xs"></i>Copiar',
              className: 'dropdown-item',
              exportOptions: { columns: [1, 2, 3, 4, 5, 6, 7] }
            }
          ]
        }
      ],
      language: {
        lengthMenu: 'Mostrar _MENU_ registros',
        search: '',
        searchPlaceholder: 'Buscar en todas las columnas...',
        info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
        infoEmpty: 'No hay registros',
        infoFiltered: '(filtrado de _MAX_ registros totales)',
        zeroRecords: 'No se encontraron registros',
        paginate: {
          next: '<i class="ti ti-chevron-right ti-sm"></i>',
          previous: '<i class="ti ti-chevron-left ti-sm"></i>',
          first: '<i class="ti ti-chevrons-left ti-sm"></i>',
          last: '<i class="ti ti-chevrons-right ti-sm"></i>'
        }
      },
      displayLength: 25,
      lengthMenu: [10, 25, 50, 75, 100],
      searchDelay: 400, // Delay para mejorar rendimiento en búsquedas
      initComplete: function () {
        // Agregar clase a los elementos de búsqueda
        var searchInput = $('.dataTables_filter input');
        searchInput.addClass('form-control form-control-sm');
        searchInput.attr('placeholder', 'Buscar en todas las columnas...');
        searchInput.css({
          'min-width': '300px',
          'padding-left': '2.5rem'
        });
        
        // Agregar icono de búsqueda
        $('.dataTables_filter').prepend('<i class="ti ti-search position-absolute" style="left: 10px; top: 50%; transform: translateY(-50%); color: #6c757d; pointer-events: none;"></i>');
        $('.dataTables_filter').css('position', 'relative');
        
        // Agregar botón para limpiar búsqueda
        var clearBtn = $('<button type="button" class="btn btn-sm btn-link text-muted p-0 ms-2" title="Limpiar búsqueda" style="display: none;"><i class="ti ti-x"></i></button>');
        $('.dataTables_filter').append(clearBtn);
        
        // Funcionalidad del botón limpiar
        clearBtn.on('click', function() {
          searchInput.val('').trigger('keyup');
          $(this).hide();
        });
        
        // Mostrar/ocultar botón limpiar según contenido
        searchInput.on('keyup', function() {
          if ($(this).val().length > 0) {
            clearBtn.show();
          } else {
            clearBtn.hide();
          }
        });
        
        $('.dataTables_length .form-select').addClass('form-select-sm');
      }
    });
  }

  // Función para cargar borradores de factura
  window.loadDraftInvoices = function() {
    const section = document.getElementById('draft-invoices-section');
    const count = document.getElementById('draft-count');
    const tbody = document.getElementById('draft-invoices-body');

    if (section.style.display === 'none') {
      // Mostrar sección y cargar datos
      section.style.display = 'block';

      // Mostrar loading
      tbody.innerHTML = `
        <tr>
          <td colspan="9" class="text-center text-muted">
            <i class="ti ti-loader fs-1"></i>
            <br>
            Cargando borradores...
          </td>
        </tr>
      `;

      // Cargar borradores via AJAX
      $.ajax({
        url: '/presales/drafts',
        method: 'GET',
        success: function(response) {
          if (response.success && response.data && response.data.length > 0) {
            let html = '';
            response.data.forEach(function(draft) {
              html += `
                <tr>
                  <td>
                    <a href="/presale/${draft.id}" class="btn btn-sm btn-outline-primary">
                      <i class="ti ti-eye"></i>
                    </a>
                  </td>
                  <td>${draft.id}</td>
                  <td>${draft.client_name || 'Sin cliente'}</td>
                  <td>${draft.company_name || 'Sin empresa'}</td>
                  <td>${draft.document_type || 'Factura'}</td>
                  <td>$${parseFloat(draft.total || 0).toFixed(2)}</td>
                  <td>${new Date(draft.created_at).toLocaleDateString()}</td>
                  <td>${draft.user_name || 'Usuario'}</td>
                  <td class="text-center">
                    <div class="btn-group">
                      <button type="button" class="btn btn-sm btn-outline-primary" onclick="completeDraft(${draft.id})">
                        <i class="ti ti-check"></i> Completar
                      </button>
                      <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteDraft(${draft.id})">
                        <i class="ti ti-trash"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              `;
            });
            tbody.innerHTML = html;
            count.textContent = response.data.length;
          } else {
            tbody.innerHTML = `
              <tr>
                <td colspan="9" class="text-center text-muted">
                  <i class="ti ti-file-off fs-1"></i>
                  <br>
                  No hay borradores pendientes
                </td>
              </tr>
            `;
            count.textContent = '0';
          }
        },
        error: function() {
          tbody.innerHTML = `
            <tr>
              <td colspan="9" class="text-center text-muted">
                <i class="ti ti-alert-triangle fs-1"></i>
                <br>
                Error al cargar borradores
              </td>
            </tr>
          `;
          count.textContent = '0';
        }
      });
    } else {
      // Ocultar sección
      section.style.display = 'none';
    }
  };

  // Función para completar borrador
  window.completeDraft = function(draftId) {
    Swal.fire({
      title: 'Completar Borrador',
      text: '¿Está seguro que desea convertir este borrador en una venta?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Sí, Completar',
      cancelButtonText: 'Cancelar'
    }).then((result) => {
      if (result.isConfirmed) {
        window.location.href = `/sale/create?draft_id=${draftId}`;
      }
    });
  };

  // Función para eliminar borrador
  window.deleteDraft = function(draftId) {
    Swal.fire({
      title: 'Eliminar Borrador',
      text: '¿Está seguro que desea eliminar este borrador?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, Eliminar',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#d33'
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: `/presales/drafts/${draftId}`,
          method: 'DELETE',
          success: function(response) {
            if (response.success) {
              Swal.fire('Eliminado', 'El borrador ha sido eliminado correctamente', 'success');
              loadDraftInvoices(); // Recargar la lista
            } else {
              Swal.fire('Error', response.message || 'Error al eliminar el borrador', 'error');
            }
          },
          error: function() {
            Swal.fire('Error', 'Error al eliminar el borrador', 'error');
          }
        });
      }
    });
  };
});

// Función para imprimir documento
function printsale(corr) {
  var url = 'impdoc/'+corr;
  window.open(url, '_blank');
}

// Función para enviar correo (versión mejorada)
function EnviarCorreo(id_factura, correo, numero) {
    (async () => {
        const _token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        const { value: email } = await Swal.fire({
            title: 'Mandar comprobante por Correo',
            input: 'email',
            inputLabel: 'Correo a Enviar',
            inputPlaceholder: 'Introduzca el Correo',
            inputValue: correo
        });

        if (email) {
            // Mostrar loading
            Swal.fire({
                title: 'Enviando correo...',
                text: 'Por favor espere mientras se genera y envía el PDF',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Enviar correo
            $.ajax({
                url: "/sale/enviar_correo_offline",
                type: 'POST',
                data: {
                    id_factura: id_factura,
                    email: email,
                    numero: numero,
                    nombre_cliente: '', // Parámetro requerido por la nueva función
                    _token: _token
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            title: '¡Correo Enviado!',
                            html: `
                                <p>Comprobante enviado exitosamente a:</p>
                                <strong>${email}</strong>
                                <br><br>
                                <small class="text-muted">Factura: ${response.data?.numero_factura || numero}</small>
                            `,
                            icon: 'success',
                            confirmButtonText: 'OK'
                        });
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: response.message || 'Error al enviar el correo',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                },
                error: function(xhr) {
                    let errorMessage = 'Error al enviar el correo';

                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (xhr.responseText) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            errorMessage = response.message || errorMessage;
                        } catch (e) {
                            // Si no es JSON válido, usar el texto de respuesta
                            errorMessage = xhr.responseText;
                        }
                    }

                    Swal.fire({
                        title: 'Error',
                        text: errorMessage,
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            });
        }
    })();
}

// Función para retomar venta (versión mejorada)
function retomarsale(saleId, typeDocumentId) {
    const url = `/sale/create?corr=${saleId}&draft=true&typedocument=${typeDocumentId}&operation=delete`;
    window.location.href = url;
}

// Función para cancelar venta (versión mejorada)
function cancelsale(saleId) {
    // Validar que saleId sea un número válido
    if (!saleId || isNaN(saleId)) {
        Swal.fire({
            title: 'Error',
            text: 'ID de venta inválido',
            icon: 'error',
            confirmButtonText: 'OK'
        });
        return;
    }

    const swalWithBootstrapButtons = Swal.mixin({
        customClass: {
          confirmButton: 'btn btn-success',
          cancelButton: 'btn btn-danger'
        },
        buttonsStyling: false
    });

      swalWithBootstrapButtons.fire({
        title: 'Anular?',
        text: "Esta acción no tiene retorno",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Si, Anular!',
        cancelButtonText: 'No, Cancelar!',
        reverseButtons: true
      }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: "/sale/destroy/"+btoa(saleId),
                method: "GET",
                success: function(response){
                        if(response.success === true || response.res == 1){
                            Swal.fire({
                                title: '¡Éxito!',
                                text: getResponseMessage(response, 'Documento invalidado correctamente'),
                                icon: 'success',
                                confirmButtonText: 'Ok'
                              }).then((result) => {
                                if (result.isConfirmed) {
                                  location.reload();
                                }
                        });
                        } else if(response.success === false || response.res == 0){
                            Swal.fire(
                                'Error',
                                getResponseMessage(response, 'Error al invalidar el documento'),
                                'error'
                            );
                        } else {
                            Swal.fire(
                                'Error',
                                getResponseMessage(response, 'Error inesperado al invalidar el documento'),
                                'error'
                            );
                        }
                },
                error: function(xhr) {
                    let errorMessage = 'Error al invalidar el documento';

                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (xhr.responseText) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            errorMessage = response.message || errorMessage;
                        } catch (e) {
                            errorMessage = xhr.responseText;
                        }
                    }

                    Swal.fire({
                        title: 'Error',
                        text: errorMessage,
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            });
        }
      });
}

// Función para crear nota de crédito
function ncr(saleId) {
    window.location.href = `/credit-notes/create?sale_id=${saleId}`;
}

// Función para crear nota de débito
function ndb(saleId) {
    window.location.href = `/debit-notes/create?sale_id=${saleId}`;
}

// Función para mostrar mensajes de respuesta
function mensaje(titulo, mensaje, tipo) {
    Swal.fire({
        title: titulo,
        text: mensaje,
        icon: tipo,
        confirmButtonText: 'OK'
    });
}

// Función para validar formularios
function validarFormulario(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;

    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;

    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            isValid = false;
        } else {
            field.classList.remove('is-invalid');
        }
    });

    return isValid;
}

// Función para limpiar formularios
function limpiarFormulario(formId) {
    const form = document.getElementById(formId);
    if (!form) return;

    const inputs = form.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
        input.value = '';
        input.classList.remove('is-invalid', 'is-valid');
    });
}

// Función para mostrar notificaciones toast
function mostrarToast(mensaje, tipo = 'info') {
    // Verificar si existe la función de toast del tema
    if (typeof toastr !== 'undefined') {
        toastr[tipo](mensaje);
    } else {
        // Fallback con SweetAlert
        Swal.fire({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            icon: tipo,
            title: mensaje
        });
    }
}

// Función para confirmar acciones críticas
function confirmarAccion(titulo, mensaje, callback) {
    Swal.fire({
        title: titulo,
        text: mensaje,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, continuar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#d33'
    }).then((result) => {
        if (result.isConfirmed && typeof callback === 'function') {
            callback();
        }
    });
}

// Función para formatear números
function formatearNumero(numero, decimales = 2) {
    return parseFloat(numero).toFixed(decimales).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

// Función para formatear fechas
function formatearFecha(fecha, formato = 'dd/mm/yyyy') {
    const date = new Date(fecha);
    const day = date.getDate().toString().padStart(2, '0');
    const month = (date.getMonth() + 1).toString().padStart(2, '0');
    const year = date.getFullYear();

    switch (formato) {
        case 'dd/mm/yyyy':
            return `${day}/${month}/${year}`;
        case 'yyyy-mm-dd':
            return `${year}-${month}-${day}`;
        default:
            return `${day}/${month}/${year}`;
    }
}

// Función para copiar al portapapeles
function copiarPortapapeles(texto) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(texto).then(() => {
            mostrarToast('Copiado al portapapeles', 'success');
        });
    } else {
        // Fallback para navegadores antiguos
        const textArea = document.createElement('textarea');
        textArea.value = texto;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        mostrarToast('Copiado al portapapeles', 'success');
    }
}

// Función para exportar datos
function exportarDatos(formato, datos, nombreArchivo) {
    switch (formato) {
        case 'csv':
            exportarCSV(datos, nombreArchivo);
            break;
        case 'excel':
            exportarExcel(datos, nombreArchivo);
            break;
        case 'pdf':
            exportarPDF(datos, nombreArchivo);
            break;
        default:
            mostrarToast('Formato no soportado', 'error');
    }
}

// Función para exportar CSV
function exportarCSV(datos, nombreArchivo) {
    const csv = convertirAJSON(datos);
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `${nombreArchivo}.csv`;
    a.click();
    window.URL.revokeObjectURL(url);
}

// Función para convertir datos a CSV
function convertirAJSON(datos) {
    if (!datos || datos.length === 0) return '';

    const headers = Object.keys(datos[0]);
    const csvHeaders = headers.join(',');
    const csvRows = datos.map(row =>
        headers.map(header => `"${row[header] || ''}"`).join(',')
    );

    return [csvHeaders, ...csvRows].join('\n');
}

// Inicialización cuando el documento esté listo
$(document).ready(function() {
    // Inicializar tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();

    // Inicializar popovers
    $('[data-bs-toggle="popover"]').popover();

    // Configurar validación de formularios (excluyendo formularios de filtros)
    $('form').on('submit', function(e) {
        // Excluir formularios de filtros de la validación
        if (!$(this).hasClass('filter-form') && !$(this).attr('action').includes('sale.index')) {
            if (!validarFormulario(this.id)) {
                e.preventDefault();
                mostrarToast('Por favor complete todos los campos requeridos', 'error');
            }
        }
    });

    // Configurar auto-hide para alerts
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
});
