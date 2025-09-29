$(function () {
    'use strict';

    var dt_debit_notes_table = $('.datatables-debit-notes');

    // Configuración de DataTable para notas de débito
    if (dt_debit_notes_table.length) {
        var dt_debit_notes = dt_debit_notes_table.DataTable({
            ajax: {
                url: baseUrl + 'debit-notes/data', // Si implementas endpoint para datos
                type: 'GET'
            },
            columns: [
                // columns según tu tabla
                { data: '' },
                { data: 'correlativo' },
                { data: 'fecha' },
                { data: 'cliente' },
                { data: 'empresa' },
                { data: 'total' },
                { data: 'estado' },
                { data: 'motivo' },
                { data: 'acciones' }
            ],
            columnDefs: [
                {
                    // Control para expandir/contraer
                    className: 'control',
                    orderable: false,
                    searchable: false,
                    responsivePriority: 2,
                    targets: 0,
                    render: function (data, type, full, meta) {
                        return '';
                    }
                },
                {
                    // Correlativo
                    targets: 1,
                    searchable: true,
                    orderable: true
                },
                {
                    // Fecha
                    targets: 2,
                    searchable: true,
                    orderable: true
                },
                {
                    // Cliente
                    targets: 3,
                    searchable: true,
                    orderable: true
                },
                {
                    // Empresa
                    targets: 4,
                    searchable: true,
                    orderable: true
                },
                {
                    // Total
                    targets: 5,
                    searchable: false,
                    orderable: true,
                    className: 'text-end'
                },
                {
                    // Estado
                    targets: 6,
                    searchable: false,
                    orderable: true,
                    className: 'text-center'
                },
                {
                    // Motivo
                    targets: 7,
                    searchable: true,
                    orderable: false
                },
                {
                    // Acciones
                    targets: -1,
                    title: 'Acciones',
                    searchable: false,
                    orderable: false,
                    className: 'text-center'
                }
            ],
            order: [[2, 'desc']], // Ordenar por fecha descendente
            dom:
                '<"row"' +
                '<"col-md-2"<"me-3"l>>' +
                '<"col-md-10"<"dt-action-buttons text-xl-end text-lg-start text-md-end text-start d-flex align-items-center justify-content-end flex-md-row flex-column mb-3 mb-md-0 gap-3"fB>>' +
                '>t' +
                '<"row"' +
                '<"col-sm-12 col-md-6"i>' +
                '<"col-sm-12 col-md-6"p>' +
                '>',
            language: {
                sLengthMenu: '_MENU_',
                search: '',
                searchPlaceholder: 'Buscar notas de débito...',
                paginate: {
                    next: '<i class="ti ti-chevron-right ti-sm"></i>',
                    previous: '<i class="ti ti-chevron-left ti-sm"></i>'
                },
                info: 'Mostrando _START_ a _END_ de _TOTAL_ notas de débito',
                infoEmpty: 'Mostrando 0 a 0 de 0 notas de débito',
                infoFiltered: '(filtrado de _MAX_ notas de débito totales)',
                emptyTable: 'No hay notas de débito disponibles',
                zeroRecords: 'No se encontraron coincidencias'
            },
            buttons: [
                {
                    extend: 'collection',
                    className: 'btn btn-outline-secondary dropdown-toggle me-2',
                    text: '<i class="ti ti-download me-1"></i>Exportar',
                    buttons: [
                        {
                            extend: 'print',
                            text: '<i class="ti ti-printer me-2"></i>Imprimir',
                            className: 'dropdown-item',
                            exportOptions: {
                                columns: [1, 2, 3, 4, 5, 6, 7],
                                format: {
                                    body: function (inner, coldex, rowdex) {
                                        if (inner.length <= 0) return inner;
                                        var el = $.parseHTML(inner);
                                        var result = '';
                                        $.each(el, function (index, item) {
                                            if (item.classList !== undefined && item.classList.contains('user-name')) {
                                                result = result + item.lastChild.firstChild.textContent;
                                            } else if (item.innerText === undefined) {
                                                result = result + item.textContent;
                                            } else result = result + item.innerText;
                                        });
                                        return result;
                                    }
                                }
                            },
                            customize: function (win) {
                                // Personalizar la impresión
                                $(win.document.body)
                                    .css('color', '#000')
                                    .css('border-color', '#007bff')
                                    .css('background-color', '#fff');
                                $(win.document.body)
                                    .find('table')
                                    .addClass('compact')
                                    .css('color', 'inherit')
                                    .css('border-color', 'inherit')
                                    .css('background-color', 'inherit');
                            }
                        },
                        {
                            extend: 'csv',
                            text: '<i class="ti ti-file-text me-2"></i>CSV',
                            className: 'dropdown-item',
                            exportOptions: {
                                columns: [1, 2, 3, 4, 5, 6, 7]
                            }
                        },
                        {
                            extend: 'excel',
                            text: '<i class="ti ti-file-spreadsheet me-2"></i>Excel',
                            className: 'dropdown-item',
                            exportOptions: {
                                columns: [1, 2, 3, 4, 5, 6, 7]
                            }
                        },
                        {
                            extend: 'pdf',
                            text: '<i class="ti ti-file-description me-2"></i>PDF',
                            className: 'dropdown-item',
                            exportOptions: {
                                columns: [1, 2, 3, 4, 5, 6, 7]
                            }
                        },
                        {
                            extend: 'copy',
                            text: '<i class="ti ti-copy me-2"></i>Copiar',
                            className: 'dropdown-item',
                            exportOptions: {
                                columns: [1, 2, 3, 4, 5, 6, 7]
                            }
                        }
                    ]
                },
                {
                    text: '<i class="ti ti-plus me-0 me-sm-1"></i><span class="d-none d-sm-inline-block">Nueva Nota de Débito</span>',
                    className: 'btn btn-primary',
                    action: function () {
                        window.location.href = baseUrl + 'debit-notes/create';
                    }
                }
            ],
            responsive: {
                details: {
                    display: $.fn.dataTable.Responsive.display.modal({
                        header: function (row) {
                            var data = row.data();
                            return 'Detalles de Nota de Débito';
                        }
                    }),
                    type: 'column',
                    renderer: function (api, rowIdx, columns) {
                        var data = $.map(columns, function (col, i) {
                            return col.title !== '' // Solo mostrar columnas con título
                                ? '<tr data-dt-row="' +
                                col.rowIndex +
                                '" data-dt-column="' +
                                col.columnIndex +
                                '">' +
                                '<td>' +
                                col.title +
                                ':' +
                                '</td> ' +
                                '<td>' +
                                col.data +
                                '</td>' +
                                '</tr>'
                                : '';
                        }).join('');

                        return data ? $('<table class="table"/><tbody />').append(data) : false;
                    }
                }
            }
        });

        // Re-inicializar Select2 en elementos modal
        $('.datatables-debit-notes tbody').on('click', '.dtr-details', function () {
            if ($.fn.select2) {
                $('.modal .select2').select2({ dropdownParent: $('.modal') });
            }
        });
    }

    // Filtros personalizados si los hay
    $('.filter-select').on('change', function () {
        var column = $(this).data('column');
        var value = $(this).val();
        
        if (dt_debit_notes_table.length) {
            dt_debit_notes.column(column).search(value).draw();
        }
    });

    // Funciones globales para acciones
    window.sendEmailDebitNote = function(debitNoteId) {
        // Lógica para enviar email
        console.log('Enviar email para nota de débito:', debitNoteId);
    };

    window.deleteDebitNote = function(debitNoteId) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: 'Esta acción no se puede deshacer',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            customClass: {
                confirmButton: 'btn btn-primary',
                cancelButton: 'btn btn-outline-secondary'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                // Enviar solicitud de eliminación
                $.ajax({
                    url: '/debit-notes/destroy/' + debitNoteId,
                    type: 'POST',
                    data: {
                        _method: 'DELETE',
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        Swal.fire({
                            title: '¡Eliminado!',
                            text: 'La nota de débito ha sido eliminada.',
                            icon: 'success',
                            customClass: {
                                confirmButton: 'btn btn-primary'
                            },
                            buttonsStyling: false
                        }).then(() => {
                            // Recargar la página o actualizar la tabla
                            if (dt_debit_notes_table.length) {
                                dt_debit_notes.ajax.reload();
                            } else {
                                window.location.reload();
                            }
                        });
                    },
                    error: function(xhr) {
                        var errorMessage = 'Error al eliminar la nota de débito.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        
                        Swal.fire({
                            title: 'Error',
                            text: errorMessage,
                            icon: 'error',
                            customClass: {
                                confirmButton: 'btn btn-primary'
                            },
                            buttonsStyling: false
                        });
                    }
                });
            }
        });
    };
});
