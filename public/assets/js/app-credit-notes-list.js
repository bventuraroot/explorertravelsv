$(function () {
    "use strict";

    var dt_credit_notes_table = $('.datatables-credit-notes');

    // DataTable with buttons
    if (dt_credit_notes_table.length) {
        var dt_credit_notes = dt_credit_notes_table.DataTable({
            processing: true,
            serverSide: false, // Usar datos del DOM en lugar de AJAX
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>><"table-responsive"t><"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
            displayLength: 10,
            lengthMenu: [10, 25, 50, 75, 100],
            columnDefs: [
                {
                    targets: 0,
                    searchable: false,
                    orderable: false,
                    render: function (data, type, full, meta) {
                        return '<div class="form-check"> <input class="form-check-input dt-checkboxes" type="checkbox" value="" id="checkbox' + meta.row + '" /><label class="form-check-label" for="checkbox' + meta.row + '"></label></div>';
                    }
                },
                {
                    targets: 1,
                    render: function (data, type, full, meta) {
                        return '<span class="fw-semibold">' + data + '</span>';
                    }
                },
                {
                    targets: 2,
                    render: function (data, type, full, meta) {
                        return '<span class="text-nowrap">' + data + '</span>';
                    }
                },
                {
                    targets: 3,
                    render: function (data, type, full, meta) {
                        return '<span class="text-truncate d-flex align-items-center">' + data + '</span>';
                    }
                },
                {
                    targets: 4,
                    render: function (data, type, full, meta) {
                        return '<span class="text-truncate d-flex align-items-center">' + data + '</span>';
                    }
                },
                {
                    targets: 5,
                    render: function (data, type, full, meta) {
                        return '<span class="fw-semibold">' + data + '</span>';
                    }
                },
                {
                    targets: 6,
                    render: function (data, type, full, meta) {
                        return data;
                    }
                },
                {
                    targets: 7,
                    render: function (data, type, full, meta) {
                        return '<span class="text-truncate d-flex align-items-center">' + data + '</span>';
                    }
                },
                {
                    targets: 8,
                    searchable: false,
                    orderable: false,
                    render: function (data, type, full, meta) {
                        return data;
                    }
                }
            ],
            order: [[1, 'desc']],
            responsive: {
                details: {
                    display: $.fn.dataTable.Responsive.display.modal({
                        header: function (row) {
                            var data = row.data();
                            return 'Detalles de la Nota de Crédito: ' + data[1];
                        }
                    }),
                    type: 'column',
                    renderer: function (api, rowIdx, columns) {
                        var data = $.map(columns, function (col, i) {
                            return col.columnIndex !== 8 && col.columnIndex !== 0
                                ? '<tr data-dt-row="' +
                                      col.rowIdx +
                                      '" data-dt-column="' +
                                      col.columnIndex +
                                      '"> <td>' +
                                      col.title +
                                      ':</td> <td>' +
                                      col.data +
                                      '</td></tr>'
                                : '';
                        }).join('');

                        return data ? $('<table class="table"/><tbody />').append(data) : false;
                    }
                }
            },
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
            }
        });
    }

    // Filter form control to default size
    setTimeout(() => {
        $('.dataTables_filter .form-control').removeClass('form-control-sm');
        $('.dataTables_length .form-select').removeClass('form-select-sm');
    }, 300);
});
