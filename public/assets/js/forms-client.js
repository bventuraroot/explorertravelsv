/**
 * Form Picker
 */

"use strict";

// Función global para manejar el tipo de persona
function typeperson(type) {
    $('#fields_with_option').css('display', '');
    if (type == "N") {
        $("#fields_natural").css("display", "");
        $("#fields_juridico").css("display", "none");
        $("#contribuyentelabel").css("display", "");
        $("#extranjerolabel").css("display", "");
        $("#siescontri").css("display", "none");
        $("#nacimientof").css("display", "");
        $("#siextranjeroduinit").css("display", "");
    } else {
        $("#contribuyentelabel").css("display", "none");
        $("#extranjerolabel").css("display", "none");
        $("#siescontri").css("display", "");
    }
    if (type == "J") {
        $("#fields_juridico").css("display", "");
        $("#fields_natural").css("display", "none");
        $("#nacimientof").css("display", "none");
    }
}

// Función global para validar campos requeridos
function validateRequiredFields() {
    try {
        var tpersona = $("#tpersona").val();

        if (!tpersona || tpersona === "0" || tpersona === "") {
            $("#btnsavenewclient").prop("disabled", true);
            showValidationMessage("Por favor seleccione un tipo de cliente");
            return;
        }

        var email = $("#email").val() || "";
        var tel1 = $("#tel1").val() || "";
        var country = $("#country").val() || "";
        var departament = $("#departament").val() || "";
        var municipio = $("#municipio").val() || "";
        var address = $("#address").val() || "";

        if (!email || !tel1 || !country || !departament || !municipio || !address) {
            $("#btnsavenewclient").prop("disabled", true);
            showValidationMessage("Por favor complete todos los campos de contacto (email, teléfono, país, departamento, municipio y dirección)");
            return;
        }

        if (tpersona === "N") {
            var firstname = $("#firstname").val() || "";
            var firstlastname = $("#firstlastname").val() || "";
            var extranjero = $("#extranjero").is(":checked");

            if (!firstname || !firstlastname) {
                $("#btnsavenewclient").prop("disabled", true);
                showValidationMessage("Por favor complete el primer nombre y primer apellido");
                return;
            }

            if (extranjero) {
                var pasaporte = $("#pasaporte").val() || "";
                if (!pasaporte || pasaporte.trim() === "") {
                    $("#btnsavenewclient").prop("disabled", true);
                    showValidationMessage("Por favor ingrese el número de pasaporte");
                    return;
                }
            } else {
                var nit = $("#nit").val() || "";
                if (!nit || nit.trim() === "") {
                    $("#btnsavenewclient").prop("disabled", true);
                    showValidationMessage("Por favor ingrese el número de DUI");
                    return;
                }
            }

            var contribuyente = $("#contribuyente").is(":checked");
            if (contribuyente) {
                var ncr = $("#ncr").val() || "";
                var tipocontribuyente = $("#tipocontribuyente").val() || "";
                var acteconomica = $("#acteconomica").val() || "";
                if (!ncr || !tipocontribuyente || acteconomica === "0") {
                    $("#btnsavenewclient").prop("disabled", true);
                    return;
                }
            }
        } else if (tpersona === "J") {
            var comercial_name = $("#comercial_name").val() || "";
            var name_contribuyente = $("#name_contribuyente").val() || "";
            if (!comercial_name || !name_contribuyente) {
                $("#btnsavenewclient").prop("disabled", true);
                return;
            }
            var ncrJ = $("#ncr").val() || "";
            var tipocontribuyenteJ = $("#tipocontribuyente").val() || "";
            var acteconomicaJ = $("#acteconomica").val() || "";
            if (!ncrJ || !tipocontribuyenteJ || acteconomicaJ === "0") {
                $("#btnsavenewclient").prop("disabled", true);
                return;
            }
        }

        validateClientExists();
    } catch (e) {
        $("#btnsavenewclient").prop("disabled", true);
    }
}

// Función para mostrar mensajes de validación
function showValidationMessage(message) {
    // Limpiar mensajes anteriores
    $('.validation-message').remove();

    // Crear y mostrar mensaje
    var messageDiv = $('<div class="alert alert-warning validation-message" role="alert">' + message + '</div>');
    $('#addNewClientForm').prepend(messageDiv);

    // Ocultar mensaje después de 5 segundos
    setTimeout(function() {
        messageDiv.fadeOut(function() {
            $(this).remove();
        });
    }, 5000);
}

// Función global para validar clientes existentes
function validateClientExists() {
    var key = "";
    var tpersona = $("#tpersona").val();
    var extranjero = $("#extranjero").is(":checked");
    if (extranjero) {
        key = $("#pasaporte").val();
        tpersona = "E";
    } else if (tpersona == "N") {
        key = $("#nit").val();
    } else if (tpersona == "J") {
        key = $("#ncr").val();
    }

    // Quitar guiones y espacios para validación
    key = key.replace(/[- ]/g, '');

    if (!key || key.trim() === "") {
        $("#btnsavenewclient").prop("disabled", true);
        return;
    }
    $.ajax({
        url: "/client/keyclient",
        method: "POST",
        data: {
            num: key,
            tpersona: tpersona,
            company_id: $("#companyselected").val()
        },
        success: function (response) {
            if (response && response.exists === true) {
                $("#btnsavenewclient").prop("disabled", true);
                showValidationMessage("Este cliente ya existe en el sistema");
            } else {
                $("#btnsavenewclient").prop("disabled", false);
                // Limpiar mensajes de error cuando la validación es exitosa
                $('.validation-message').remove();
            }
        },
        error: function(){ $("#btnsavenewclient").prop("disabled", false); }
    });
}

$(document).ready(function () {
    $("#btnsavenewclient").prop("disabled", true);

    $("#tel1").inputmask("9999-9999");
    $("#tel2").inputmask("9999-9999");

    $("#ncredit").inputmask("999999-9");
    // Remover máscara fija para permitir DUI y NIT flexibles
    // $("#nitedit").inputmask("99999999-9");
    // Sin máscara para el pasaporte - permite cualquier formato
    $("#tel1edit").inputmask("9999-9999");
    $("#tel2edit").inputmask("9999-9999");


    $("#nit").change(function () {
        var key = $("#nit").val().replace(/[- ]/g, ''); // Quitar guiones
        var tpersona = $("#tpersona").val();
        var companyId = $("#companyselected").val();

        if (!key || key.trim() === "") {
            $("#btnsavenewclient").prop("disabled", true);
            return;
        }

        $.ajax({
            url: "/client/keyclient",
            method: "POST",
            data: {
                num: key,
                tpersona: tpersona,
                company_id: companyId
            },
            success: function (response) {
                if (response && response.exists === true) {
                    Swal.fire(
                        "Alerta",
                        "Cliente con este DUI ya se encuentra registrado, favor validar la información",
                        "info"
                    );
                    $("#btnsavenewclient").prop("disabled", true);
                } else {
                    $("#btnsavenewclient").prop("disabled", false);
                }
            },
            error: function() {
                $("#btnsavenewclient").prop("disabled", false);
            }
        });
    });
    //si es extranjero
    $("#pasaporte").change(function () {
        var key = $("#pasaporte").val();
        var tpersona = "E"; // Siempre es E para extranjeros
        var companyId = $("#companyselected").val();

        if (!key || key.trim() === "") {
            $("#btnsavenewclient").prop("disabled", true);
            return;
        }

        $.ajax({
            url: "/client/keyclient",
            method: "POST",
            data: {
                num: key,
                tpersona: tpersona,
                company_id: companyId
            },
            success: function (response) {
                if (response && response.exists === true) {
                    Swal.fire(
                        "Alerta",
                        "Cliente extranjero con este pasaporte ya se encuentra registrado, favor validar la información",
                        "info"
                    );
                    $("#btnsavenewclient").prop("disabled", true);
                } else {
                    $("#btnsavenewclient").prop("disabled", false);
                }
            },
            error: function() {
                $("#btnsavenewclient").prop("disabled", false);
            }
        });
    });

    $("#ncr").change(function () {
        var key = $("#ncr").val().replace(/[- ]/g, ''); // Quitar guiones
        var tpersona = $("#tpersona").val();
        var companyId = $("#companyselected").val();

        if (!key || key.trim() === "") {
            $("#btnsavenewclient").prop("disabled", true);
            return;
        }

        $.ajax({
            url: "/client/keyclient",
            method: "POST",
            data: {
                num: key,
                tpersona: tpersona,
                company_id: companyId
            },
            success: function (response) {
                if (response && response.exists === true) {
                    Swal.fire(
                        "Alerta",
                        "Cliente con este NCR ya se encuentra registrado, favor validar la información",
                        "info"
                    );
                    $("#btnsavenewclient").prop("disabled", true);
                } else {
                    $("#btnsavenewclient").prop("disabled", false);
                }
            },
            error: function() {
                $("#btnsavenewclient").prop("disabled", false);
            }
        });
    });

    // Validación del pasaporte en edición
    $("#pasaporteedit").change(function () {
        var key = $("#pasaporteedit").val().replace(/[- ]/g, ''); // Quitar guiones
        var tpersona = "E"; // Siempre es E para extranjeros
        var companyId = $("#companyselectededit").val();
        var clientId = $("#idedit").val();

        if (!key || key.trim() === "") {
            return;
        }

        $.ajax({
            url: "/client/keyclient",
            method: "POST",
            data: {
                num: key,
                tpersona: tpersona,
                company_id: companyId,
                client_id: clientId
            },
            success: function (response) {
                if (response && response.exists === true) {
                    Swal.fire(
                        "Alerta",
                        "Cliente extranjero con este pasaporte ya se encuentra registrado, favor validar la información",
                        "info"
                    );
                }
            },
            error: function() {
                // No hacer nada en caso de error
            }
        });
    });

    //Get companies avaibles
    $.ajax({
        url: "/company/getCompany",
        method: "GET",
        success: function (response) {
            let companyselected = $("#companyselected").val();
            $.each(response, function (index, value) {
                $("#selectcompany").append(
                    '<option value="' +
                        value.id +
                        '">' +
                        value.name +
                        "</option>"
                );
            });
            $("#selectcompany option[value=" + companyselected + "]").attr(
                "selected",
                true
            );
            // Si no hay empresa seleccionada, usar la primera y navegar
            if ((!companyselected || companyselected == 0) && response.length > 0) {
                var firstId = response[0].id;
                $("#companyselected").val(firstId);
                $("#selectcompany").val(firstId);
                window.location.href = "/client/index/" + btoa(firstId);
            }
        },
    });

    if ($("#companyselected").val() == 0) {
        $("button.add-new").attr("disabled", true);
    } else {
        $("button.add-new").attr("disabled", false);
    }
    getpaises();
    // Disparadores para validación avanzada
    $("#tpersona, #email, #tel1, #country, #departament, #municipio, #address, #firstname, #firstlastname, #comercial_name, #name_contribuyente, #nit, #ncr, #pasaporte, #tipocontribuyente, #acteconomica").on('input change', function(){
        validateRequiredFields();
    });
    $("#contribuyente, #extranjero").on('change', function(){
        validateRequiredFields();
    });
});

function getpaises(selected = "", type = "") {
    if (type == "edit") {
        $.ajax({
            url: "/getcountry",
            method: "GET",
            success: function (response) {
                $.each(response, function (index, value) {
                    if (selected != "" && value.id == selected) {
                        $("#countryedit").append(
                            '<option value="' +
                                value.id +
                                '" selected>' +
                                value.name.toUpperCase() +
                                "</option>"
                        );
                    } else {
                        $("#countryedit").append(
                            '<option value="' +
                                value.id +
                                '">' +
                                value.name.toUpperCase() +
                                "</option>"
                        );
                    }
                });
            },
        });
    } else {
        $.ajax({
            url: "/getcountry",
            method: "GET",
            success: function (response) {
                $.each(response, function (index, value) {
                    $("#country").append(
                        '<option value="' +
                            value.id +
                            '">' +
                            value.name.toUpperCase() +
                            "</option>"
                    );
                });
            },
        });
    }
}

(function () {
    // Flat Picker
    // --------------------------------------------------------------------
    const flatpickrDate = document.querySelector("#a");

    // Date
    if (flatpickrDate) {
        flatpickrDate.flatpickr({
            //monthSelectorType: 'static',
            dateFormat: "d-m-Y",
        });
    }
})();

function getdepartamentos(pais, type = "", selected, selectedact) {
    //Get countrys avaibles
    if (type == "edit") {
        $.ajax({
            url: "/getdepartment/" + btoa(pais),
            method: "GET",
            success: function (response) {
                $("#departamentedit").find("option[value!='0']").remove();
                $.each(response, function (index, value) {
                    if (selected != "" && value.id == selected) {
                        $("#departamentedit").append(
                            '<option value="' +
                                value.id +
                                '" selected>' +
                                value.name +
                                "</option>"
                        );
                    } else {
                        $("#departamentedit").append(
                            '<option value="' +
                                value.id +
                                '">' +
                                value.name +
                                "</option>"
                        );
                    }
                });
            },
        });

        //Get acteconomica
        $.ajax({
            url: "/geteconomicactivity/" + btoa(pais),
            method: "GET",
            success: function (response) {
                $.each(response, function (index, value) {
                    if (selectedact !== "" && value.id == selectedact) {
                        $("#acteconomicaedit").append(
                            '<option value="' +
                                value.id +
                                '" selected>' +
                                value.name +
                                "</option>"
                        );
                    } else {
                        $("#acteconomicaedit").append(
                            '<option value="' +
                                value.id +
                                '">' +
                                value.name +
                                "</option>"
                        );
                    }
                });
            },
        });
    } else {
        $.ajax({
            url: "/getdepartment/" + btoa(pais),
            method: "GET",
            success: function (response) {
                $("#departament").find("option[value!='0']").remove();
                $.each(response, function (index, value) {
                    $("#departament").append(
                        '<option value="' +
                            value.id +
                            '">' +
                            value.name +
                            "</option>"
                    );
                });
            },
        });
        //Get acteconomica
        $.ajax({
            url: "/geteconomicactivity/" + btoa(pais),
            method: "GET",
            success: function (response) {
                $.each(response, function (index, value) {
                    $("#acteconomica").append(
                        '<option value="' +
                            value.id +
                            '">' +
                            value.name +
                            "</option>"
                    );
                });
            },
        });
    }
}

function getmunicipio(dep, type = "", selected) {
    if (type == "edit") {
        //Get countrys avaibles
        $.ajax({
            url: "/getmunicipality/" + btoa(dep),
            method: "GET",
            success: function (response) {
                $("#municipioedit").find("option[value!='0']").remove();
                $.each(response, function (index, value) {
                    if (selected !== "" && value.id == selected) {
                        $("#municipioedit").append(
                            '<option value="' +
                                value.id +
                                '" selected>' +
                                value.name +
                                "</option>"
                        );
                    } else {
                        $("#municipioedit").append(
                            '<option value="' +
                                value.id +
                                '">' +
                                value.name +
                                "</option>"
                        );
                    }
                });
            },
        });
    } else {
        //Get countrys avaibles
        $.ajax({
            url: "/getmunicipality/" + btoa(dep),
            method: "GET",
            success: function (response) {
                $("#municipio").find("option[value!='0']").remove();
                $.each(response, function (index, value) {
                    $("#municipio").append(
                        '<option value="' +
                            value.id +
                            '">' +
                            value.name +
                            "</option>"
                    );
                });
            },
        });
    }
}

function typepersonedit(type) {

    if (type == "N") {
        $("#contribuyentelabeledit").css("display", "block");
        $("#extranjerolabeledit").css("display", "block !important");
        $("#extranjerolabeledit").show();

        // Asegurar que el padre esté visible
        var parent = $("#extranjerolabeledit").parent();
        if (parent.css("visibility") === "hidden") {
            parent.css("visibility", "visible");
        }

        // Forzar tamaño del elemento
        var element = $("#extranjerolabeledit")[0];
        if (element) {
            element.style.width = "auto";
            element.style.height = "auto";
            element.style.minWidth = "200px";
            element.style.minHeight = "40px";
        }

        $("#siescontriedit").css("display", "none");

        // Verificar si es extranjero para mostrar el campo correcto
        if ($("#extranjeroedit").is(":checked")) {
            $("#dui_fields").css("display", "none");
            $("#pasaporte_fields_edit").css("display", "block");
            $("#dui_fields").hide();
            $("#pasaporte_fields_edit").show();
        } else {
            $("#dui_fields").css("display", "block");
            $("#pasaporte_fields_edit").css("display", "none");
            $("#dui_fields").show();
            $("#pasaporte_fields_edit").hide();
        }

        validarchecked();
        $("#nacimientof").css("display", "block");
    } else if (type == "J") {
        $("#contribuyentelabeledit").css("display", "none");
        $("#extranjerolabeledit").css("display", "none");
        $("#siescontriedit").css("display", "block");
        $("#dui_fields").css("display", "none");
        $("#pasaporte_fields_edit").css("display", "none");
        $("#nacimientof").css("display", "none");
        console.log("Legal entity selected - switches should be hidden");
    }
}

function escontri() {
    if ($("#contribuyente").is(":checked")) {
        $("#siescontri").css("display", "");
    } else {
        $("#siescontri").css("display", "none");
    }
}

function esextranjero() {
    if ($("#extranjero").is(":checked")) {
        $("#siextranjero").css("display", "");
        $("#siextranjeroduinit").css("display", "none");
        // Limpiar el campo NIT cuando se selecciona extranjero
        $("#nit").val("");
    } else {
        $("#siextranjero").css("display", "none");
        $("#siextranjeroduinit").css("display", "");
        // Limpiar el campo pasaporte cuando se deselecciona extranjero
        $("#pasaporte").val("");
    }
    // Revalidar campos requeridos
    validateRequiredFields();
}

function escontriedit() {
    if ($("#contribuyenteedit").is(":checked")) {
        $("#siescontriedit").css("display", "");
    } else {
        $("#siescontriedit").css("display", "none");
    }
    validarchecked();
}

function esextranjeroedit() {
    if ($("#extranjeroedit").is(":checked")) {
        $("#pasaporte_fields_edit").css("display", "block");
        $("#dui_fields").css("display", "none");
        $("#pasaporte_fields_edit").show();
        $("#dui_fields").hide();
        // No limpiar campos automáticamente para preservar los datos
    } else {
        $("#pasaporte_fields_edit").css("display", "none");
        $("#dui_fields").css("display", "block");
        $("#pasaporte_fields_edit").hide();
        $("#dui_fields").show();
        // No limpiar campos automáticamente para preservar los datos
    }
}

function validarchecked() {
    if ($("#contribuyenteedit").is(":checked")) {
        $("#contribuyenteeditvalor").val("1");
    }
}
function llamarselected(pais, departamento, municipio, acteconomica) {
    getpaises(pais, "edit");
    getdepartamentos(pais, "edit", departamento, acteconomica);
    getmunicipio(departamento, "edit", municipio);
}

function editClient(id) {
    //Get data edit companies
    //alert('entro');
    $.ajax({
        url: "/client/getClientid/" + btoa(id),
        method: "GET",
        success: function (response) {
            // Verificar que la respuesta tenga datos
            if (!response || !response[0]) {
                console.error('No se encontraron datos del cliente');
                Swal.fire('Error', 'No se pudieron cargar los datos del cliente', 'error');
                return;
            }

            llamarselected(
                response[0]["country"],
                response[0]["departament"],
                response[0]["municipio"],
                response[0]["acteconomica"]
            );
            $.each(response[0], function (index, value) {
                if (index == "phone") {
                    $("#tel1edit").val(value);
                } else if (index == "phone_fijo") {
                    $("#tel2edit").val(value);
                }
                if (index == "phone_id") {
                    $("#phoneeditid").val(value);
                }
                if (index == "address_id") {
                    $("#addresseditid").val(value);
                }

                if (index == "contribuyente") {
                    if (value == "1") {
                        $("#contribuyenteedit").prop("checked", true);
                        $("#contribuyentelabeledit").css("display", "");
                        validarchecked();
                    } else if (value == "0") {
                        $(".contribuyenteedit").prop("checked", false);
                        $("#contribuyentelabeledit").css("display", "");
                    }
                    escontriedit();
                    if ($("#tpersonaedit").val() == "J") {
                        $("#contribuyentelabeledit").css("display", "none");
                        $("#siescontriedit").css("display", "");
                    }
                }
                if (index == "extranjero") {

                    if (value == "1") {
                        $("#extranjeroedit").prop("checked", true);
                        $("#extranjerolabeledit").css("display", "block !important");
                        $("#extranjerolabeledit").show();

                        // Asegurar que el padre esté visible
                        var parent = $("#extranjerolabeledit").parent();
                        if (parent.css("visibility") === "hidden") {
                            parent.css("visibility", "visible");
                        }

                        // Forzar tamaño del elemento
                        var element = $("#extranjerolabeledit")[0];
                        if (element) {
                            element.style.width = "auto";
                            element.style.height = "auto";
                            element.style.minWidth = "200px";
                            element.style.minHeight = "40px";
                        }

                        // Asegurar que se muestre el campo de pasaporte y se oculte el DUI
                        $("#pasaporte_fields_edit").css("display", "block");
                        $("#dui_fields").css("display", "none");

                        // Forzar ocultación del campo DUI
                        $("#dui_fields").hide();
                        $("#pasaporte_fields_edit").show();
                    } else if (value == "0") {
                        $("#extranjeroedit").prop("checked", false);
                        $("#extranjerolabeledit").css("display", "block !important");
                        $("#extranjerolabeledit").show();

                        // Asegurar que el padre esté visible
                        var parent = $("#extranjerolabeledit").parent();
                        if (parent.css("visibility") === "hidden") {
                            parent.css("visibility", "visible");
                        }

                        // Asegurar que se muestre el campo de DUI y se oculte el pasaporte
                        $("#pasaporte_fields_edit").css("display", "none");
                        $("#dui_fields").css("display", "block");

                        // Forzar ocultación del campo pasaporte
                        $("#pasaporte_fields_edit").hide();
                        $("#dui_fields").show();
                    }
                }
                if (index == "tpersona") {
                    var selectedN = "";
                    var selectedJ = "";
                    if (value == "J") {
                        selectedJ = "selected";
                        $("#fields_natural_edit").css("display", "none");
                        $("#fields_juridico_edit").css("display", "");
                        $("#dui_fields").css("display", "none");
                        $("#DOB_field").css("display", "none");
                    } else if (value == "N") {
                        selectedN = "selected";
                        $("#contribuyentelabeledit").css("display", "");
                        $("#fields_natural_edit").css("display", "");
                        $("#fields_juridico_edit").css("display", "none");
                        $("#dui_fields").css("display", "");
                        $("#DOB_field").css("display", "");
                    }
                    $("#tpersonaedit").empty();
                    $("#tpersonaedit").append(
                        '<option value="N" ' + selectedN + ">NATURAL</option>"
                    );
                    $("#tpersonaedit").append(
                        '<option value="J" ' + selectedJ + ">JURIDICO</option>"
                    );
                }

                if (index == "tipoContribuyente") {
                    $(
                        "#tipocontribuyenteedit option[value='" + value + "']"
                    ).attr("selected", true);
                }
                if (index == "pasaporte") {
                    $("#pasaporteedit").val(value);
                } else if (index == "company_id") {
                    $("#companyselectededit").val(value);
                } else {
                    $("#" + index + "edit").val(value);
                }
            });
            const bsOffcanvas = new bootstrap.Offcanvas(
                "#offcanvasUpdateClient"
            ).show();

            // Forzar visibilidad del switch extranjero después de que se abra el modal
            setTimeout(function() {
                if ($("#tpersonaedit").val() == "N") {
                    // Intentar múltiples métodos para forzar visibilidad
                    $("#extranjerolabeledit").css("display", "block !important");
                    $("#extranjerolabeledit").css("visibility", "visible !important");
                    $("#extranjerolabeledit").css("opacity", "1 !important");
                    $("#extranjerolabeledit").css("position", "relative !important");
                    $("#extranjerolabeledit").css("z-index", "9999 !important");
                    $("#extranjerolabeledit").show();
                    $("#extranjerolabeledit").removeClass("d-none");
                    $("#extranjerolabeledit").addClass("d-block");

                    // Forzar visibilidad del elemento directamente
                    var element = $("#extranjerolabeledit")[0];
                    if (element) {
                        element.style.display = "block";
                        element.style.visibility = "visible";
                        element.style.opacity = "1";
                        element.style.position = "relative";
                        element.style.zIndex = "9999";
                        element.style.width = "auto";
                        element.style.height = "auto";
                        element.style.minWidth = "200px";
                        element.style.minHeight = "40px";

                        // Si aún no es visible, crear un nuevo elemento (solo en edición)
                        if (!$("#extranjerolabeledit").is(":visible") && $("#offcanvasUpdateClient").length > 0) {

                            // Crear un nuevo elemento desde cero
                            var newElement = document.createElement('label');
                            newElement.className = 'switch switch-success';
                            newElement.id = 'extranjerolabeledit';
                            newElement.name = 'extranjerolabeledit';
                            newElement.style.display = 'block';
                            newElement.style.visibility = 'visible';
                            newElement.style.opacity = '1';
                            newElement.style.position = 'relative';
                            newElement.style.zIndex = '9999';
                            newElement.style.width = 'auto';
                            newElement.style.height = 'auto';
                            newElement.style.minWidth = '200px';
                            newElement.style.minHeight = '40px';

                            newElement.innerHTML = `
                                <input type="checkbox" class="switch-input" id="extranjeroedit" name="extranjeroedit" onclick="esextranjeroedit();" />
                                <span class="switch-toggle-slider">
                                    <span class="switch-on">
                                        <i class="ti ti-check"></i>
                                    </span>
                                    <span class="switch-off">
                                        <i class="ti ti-x"></i>
                                    </span>
                                </span>
                                <span class="switch-label">¿Es Extranjero?</span>
                            `;

                            element.parentNode.replaceChild(newElement, element);

                            // Forzar el tamaño del nuevo elemento
                            newElement.style.width = "200px";
                            newElement.style.height = "40px";
                            newElement.style.minWidth = "200px";
                            newElement.style.minHeight = "40px";
                            newElement.style.maxWidth = "none";
                            newElement.style.maxHeight = "none";
                        }
                    }

                    // Verificar si el elemento padre está oculto
                    var parent = $("#extranjerolabeledit").parent();

                    // Si el padre está oculto, mostrarlo
                    if (parent.css("visibility") === "hidden") {
                        parent.css("visibility", "visible");
                    }

                    // Asegurar que los campos correctos estén visibles
                    if ($("#extranjeroedit").is(":checked")) {
                        $("#dui_fields").hide();
                        $("#pasaporte_fields_edit").show();
                    } else {
                        $("#dui_fields").show();
                        $("#pasaporte_fields_edit").hide();
                    }
                }
            }, 500);
        },
    });
}

function deleteClient(id) {
    const swalWithBootstrapButtons = Swal.mixin({
        customClass: {
            confirmButton: "btn btn-success",
            cancelButton: "btn btn-danger",
        },
        buttonsStyling: false,
    });

    swalWithBootstrapButtons
        .fire({
            title: "¿Eliminar?",
            text: "Esta accion no tiene retorno",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Si, Eliminarlo!",
            cancelButtonText: "No, Cancelar!",
            reverseButtons: true,
        })
        .then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "/client/destroy/" + btoa(id),
                    method: "GET",
                    success: function (response) {
                        if (response.res == 1) {
                            Swal.fire({
                                title: "Eliminado",
                                icon: "success",
                                confirmButtonText: "Ok",
                            }).then((result) => {
                                /* Read more about isConfirmed, isDenied below */
                                if (result.isConfirmed) {
                                    location.reload();
                                }
                            });
                        } else if (response.res == 0) {
                            swalWithBootstrapButtons.fire(
                                "Problemas!",
                                "Algo sucedio y no pudo eliminar el cliente, favor comunicarse con el administrador.",
                                "success"
                            );
                        }
                    },
                });
            } else if (
                /* Read more about handling dismissals below */
                result.dismiss === Swal.DismissReason.cancel
            ) {
                swalWithBootstrapButtons.fire(
                    "Cancelado",
                    "No hemos hecho ninguna accion :)",
                    "error"
                );
            }
        });
}

function nitDuiMask(inputField) {
    // Obtener el valor actual y limpiarlo
    var value = inputField.value;
    var cleanValue = value.replace(/[^A-Z0-9]/gi, ''); // Solo números y letras

    // Limitar a 14 dígitos (máximo para NIT en El Salvador)
    if (cleanValue.length > 14) {
        cleanValue = cleanValue.substring(0, 14);
    }

    // Guardar la posición del cursor
    var cursorPos = inputField.selectionStart;

    // Aplicar formato basado en la longitud
    var formattedValue = '';

    if (cleanValue.length <= 8) {
        // DUI: xxxxxxxx (sin formato hasta 8 dígitos)
        formattedValue = cleanValue;
    } else if (cleanValue.length == 9) {
        // DUI completo: xxxxxxxx-x
        formattedValue = cleanValue.substring(0, 8) + '-' + cleanValue.substring(8);
    } else if (cleanValue.length <= 13) {
        // NIT: xxxx-xxxxxx-xxx
        if (cleanValue.length <= 4) {
            formattedValue = cleanValue;
        } else if (cleanValue.length <= 10) {
            formattedValue = cleanValue.substring(0, 4) + '-' + cleanValue.substring(4);
        } else {
            formattedValue = cleanValue.substring(0, 4) + '-' + cleanValue.substring(4, 10) + '-' + cleanValue.substring(10);
        }
    } else if (cleanValue.length == 14) {
        // NIT completo: xxxx-xxxxxx-xxx-x
        formattedValue = cleanValue.substring(0, 4) + '-' + cleanValue.substring(4, 10) + '-' + cleanValue.substring(10, 13) + '-' + cleanValue.substring(13);
    }

    // Solo actualizar si el valor cambió
    if (inputField.value !== formattedValue) {
        inputField.value = formattedValue;

        // Restaurar la posición del cursor ajustada por los guiones
        var newCursorPos = cursorPos;
        if (formattedValue.length > value.length) {
            newCursorPos += (formattedValue.length - value.length);
        }

        // Asegurar que el cursor no esté fuera de rango
        newCursorPos = Math.min(newCursorPos, formattedValue.length);

        // Restaurar la posición del cursor
        setTimeout(function() {
            inputField.setSelectionRange(newCursorPos, newCursorPos);
        }, 0);
    }
}

function pasaporteMask(inputField) {
    var separator = "-";
    var pasaportePattern;
    var cleanValue = inputField.value.replace(/-/g, "").toUpperCase(); // Eliminar guiones y convertir a mayúsculas

    // Solo permitir letras y números
    cleanValue = cleanValue.replace(/[^A-Z0-9]/g, "");
    inputField.value = cleanValue; // Actualiza el campo sin caracteres no permitidos

    if (/^[0-9]{9}$/.test(cleanValue)) {
        pasaportePattern = [9]; // Solo números (EE.UU., México, Brasil)
    } else if (/^[A-Z][0-9]{7,9}$/.test(cleanValue)) {
        pasaportePattern = [1, 7]; // Letra + 7-9 números (Reino Unido, Alemania)
    } else if (/^[A-Z]{2}[0-9]{7,8}$/.test(cleanValue)) {
        pasaportePattern = [2, 8]; // Dos letras + 7-8 números (España, Argentina, Italia)
    } else if (/^[0-9]{4}[0-9]{6}[0-9]{3}[A-Z]$/.test(cleanValue)) {
        pasaportePattern = [4, 6, 3, 1]; // Formato XXXX-XXXXXX-XXX-X (Centroamérica)
    } else if (/^[A-Z]{2}[0-9]{8}[A-Z]{2}$/.test(cleanValue)) {
        pasaportePattern = [2, 8, 2]; // Dos letras al inicio y fin (Algunos países europeos y asiáticos)
    } else {
        pasaportePattern = [cleanValue.length]; // Si no coincide, deja el formato sin guiones
    }

    mask(inputField, separator, pasaportePattern, false);
}


function NRCMask(inputField) {
    var separator = "-";
    var nrcPattern;
    if (inputField.value.length == 6) {
        nrcPattern = new Array(5, 1);
    } else {
        nrcPattern = new Array(6, 1);
    }
    mask(inputField, separator, nrcPattern, true);
}

function mask(inputField, separator, pattern, nums) {
    var val;
    var largo;
    var val2;
    var r;
    var z;
    var val3;
    var s;
    var q;
    if (inputField.valant != inputField.value) {
        val = inputField.value;
        largo = val.length;
        val = val.split(separator);
        val2 = "";
        for (r = 0; r < val.length; r++) {
            val2 += val[r];
        }
        if (nums) {
            for (z = 0; z < val2.length; z++) {
                if (isNaN(val2.charAt(z))) {
                    var letra = new RegExp(val2.charAt(z), "g");
                    val2 = val2.replace(letra, "");
                }
            }
        }
        val = "";
        val3 = new Array();
        for (s = 0; s < pattern.length; s++) {
            val3[s] = val2.substring(0, pattern[s]);
            val2 = val2.substr(pattern[s]);
        }
        for (q = 0; q < val3.length; q++) {
            if (q == 0) {
                val = val3[q];
            } else {
                if (val3[q] != "") {
                    val += separator + val3[q];
                }
            }
        }
        inputField.value = val;
        inputField.valant = val;
    }
}

// Manejo específico del formulario de edición de clientes
$(document).ready(function() {
    console.log('=== DEBUG: forms-client.js cargado ===');

    // Debug: Verificar que el formulario existe
    console.log('Formulario encontrado:', $('#addNewClientForm').length);
    console.log('Todos los formularios:', $('form').length);

    // Debug: Listar todos los formularios
    $('form').each(function(index) {
        console.log('Formulario ' + index + ':', $(this).attr('id'), $(this).attr('class'));
    });

    // Usar delegación de eventos para asegurar que funcione
    $(document).on('submit', '#addNewClientForm', function(e) {
        console.log('Formulario enviado - previniendo envío normal');
        e.preventDefault();
        e.stopPropagation();

        // Mostrar loading
        Swal.fire({
            title: 'Guardando...',
            text: 'Por favor espera',
            allowOutsideClick: false,
            showConfirmButton: false,
            willOpen: () => {
                Swal.showLoading();
            }
        });

        // Obtener datos del formulario
        var formData = new FormData(this);

        // Debug: Mostrar datos del formulario
        console.log('URL:', $(this).attr('action'));
        console.log('Datos del formulario:');
        var formDataArray = [];
        for (var pair of formData.entries()) {
            console.log(pair[0] + ': ' + pair[1]);
            formDataArray.push(pair[0] + ': ' + pair[1]);
        }
        console.log('Total de campos enviados:', formDataArray.length);

        // Enviar datos
        $.ajax({
            url: $(this).attr('action'),
            method: 'POST', // Usar POST con _method PATCH
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                Swal.close();
                Swal.fire({
                    title: '¡Éxito!',
                    text: response.message || 'Cliente actualizado correctamente',
                    icon: 'success',
                    confirmButtonText: 'OK'
                }).then(() => {
                    if (response.redirect_url) {
                        window.location.href = response.redirect_url;
                    } else {
                        location.reload();
                    }
                });
            },
            error: function(xhr, status, error) {
                Swal.close();

                // Debug: Mostrar información completa del error
                console.error('Error completo:', xhr);
                console.error('Status:', status);
                console.error('Error:', error);
                console.error('Response Text:', xhr.responseText);

                var errorMessage = 'Error al guardar el cliente';
                var errorDetails = '';

                if (xhr.responseJSON) {
                    console.log('Response JSON:', xhr.responseJSON);
                    if (xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    if (xhr.responseJSON.errors) {
                        errorDetails = '<ul>';
                        $.each(xhr.responseJSON.errors, function(key, value) {
                            errorDetails += '<li>' + value[0] + '</li>';
                        });
                        errorDetails += '</ul>';
                    }
                } else if (xhr.responseText) {
                    errorDetails = '<p><strong>Respuesta del servidor:</strong></p><pre>' + xhr.responseText + '</pre>';
                }

                Swal.fire({
                    title: 'Error',
                    html: '<strong>' + errorMessage + '</strong>' + (errorDetails ? '<br><br>' + errorDetails : ''),
                    icon: 'error',
                    confirmButtonText: 'OK',
                    width: '600px'
                });
            }
        });
    });
});
