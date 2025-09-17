/**
 *  Form Wizard
 */

"use strict";
$( document ).ready(function() {
    var operation = $('#operation').val();
    var valdraft = $('#valdraft').val();
    var valcorr = $('#valcorr').val();
    // Mostrar inmediatamente el correlativo (ID de venta) si viene en la URL
    if (valcorr && $('#corr').length) {
        $('#corr').val(valcorr);
    }
    if (operation == 'delete') {
        var stepper = new Stepper(document.querySelector('.wizard-icons-example'))
        stepper.to(3);
    }else{
        if(valdraft && $.isNumeric(valcorr)){
            var stepper = new Stepper(document.querySelector('.wizard-icons-example'))
        stepper.to(2);
        }
    }

});

$(function () {
    const select2 = $(".select2"),
        selectPicker = $(".selectpicker");

    // Bootstrap select
    if (selectPicker.length) {
        selectPicker.selectpicker();
    }

    // select2
    if (select2.length) {
        select2.each(function () {
            var $this = $(this);
            $this.wrap('<div class="position-relative"></div>');
            $this.select2({
                placeholder: "Select value",
                dropdownParent: $this.parent(),
            });
        });
    }
    //Get companies avaibles
    var iduser = $("#iduser").val();
    $.ajax({
        url: "/company/getCompanybyuser/" + iduser,
        method: "GET",
        success: function (response) {
            $("#company").append('<option value="0">Seleccione</option>');
            $.each(response, function (index, value) {
                $("#company").append(
                    '<option value="' +
                        value.id +
                        '">' +
                        value.name.toUpperCase() +
                        "</option>"
                );
            });

            // Auto seleccionar empresa y avanzar a cliente si solo hay una (comportamiento RomaCopies)
            if (Array.isArray(response) && response.length === 1) {
                var autoCompanyId = response[0].id;
                $("#company").val(autoCompanyId).trigger('change');
                // Habilitar siguiente paso y precargar clientes
                aviablenext(autoCompanyId);
                if (typeof getclientbycompanyurl === 'function') {
                    getclientbycompanyurl(autoCompanyId);
                }
                // Crear correlativo inmediatamente y avanzar al paso de cliente
                // Evitar bucle si ya venimos con un borrador/correlativo en URL o ya fue creado
                var hasValCorr = $("#valcorr").val();
                if (!hasValCorr && typeof createcorrsale === 'function' && !window.__creatingCorr) {
                    // Asegurar que typedocument esté disponible
                    var typedocument = $("#typedocument").val();
                    if (!typedocument || typedocument === '') {
                        // intentar leer de la URL como en RomaCopies
                        var urlParams = new URLSearchParams(window.location.search);
                        typedocument = urlParams.get('typedocument') || $("input[name=typedocument]:checked").val();
                    }
                    // createcorrsale usa #company, #iduser y #typedocument internamente
                    window.__creatingCorr = true;
                    createcorrsale();
                }
                // Avanzar al siguiente paso del wizard únicamente si ya hay correlativo (borrador)
                // Si acabamos de crear correlativo, el redirect hará el resto
                if (hasValCorr) {
                    setTimeout(function(){
                        $("#step1").trigger('click');
                    }, 200);
                }
            }
        },
    });

    //Get products avaibles
    $.ajax({
        url: "/product/getproductall",
        method: "GET",
        success: function (response) {
            $("#psearch").append('<option value="0">Seleccione</option>');
            $.each(response, function (index, value) {
                $("#psearch").append(
                    '<option value="' +
                        value.id +
                        '" title="'+ value.image +'">' +
                        value.name.toUpperCase() + "| Descripción: " + value.description + "| Proveedor: " + value.nameprovider +
                        "</option>"
                );
            });
        },
    });

    var selectdcompany = $(".select2company");

    if (selectdcompany.length) {
        var $this = selectdcompany;
        $this.wrap('<div class="position-relative"></div>').select2({
            placeholder: "Seleccionar empresa",
            dropdownParent: $this.parent(),
        });
    }

    var selectddestino = $(".select2destino");

    if (selectddestino.length) {
        var $this = selectddestino;
        $this.wrap('<div class="position-relative"></div>').select2({
            placeholder: "Seleccionar destino",
            dropdownParent: $this.parent(),
        });
    }

    var selectdcanal = $(".select2canal");

    if (selectdcanal.length) {
        var $this = selectdcanal;
        $this.wrap('<div class="position-relative"></div>').select2({
            placeholder: "Seleccionar destino",
            dropdownParent: $this.parent(),
        });
    }

    var selectdlinea = $(".select2linea");

    if (selectdlinea.length) {
        var $this = selectdlinea;
        $this.wrap('<div class="position-relative"></div>').select2({
            placeholder: "Seleccionar linea aerea",
            dropdownParent: $this.parent(),
        });
    }

    var selectdclient = $(".select2client");

    if (selectdclient.length) {
        var $this = selectdclient;
        $this.wrap('<div class="position-relative"></div>').select2({
            placeholder: "Seleccionar cliente",
            dropdownParent: $this.parent(),
        });
    }

    function formatState(state) {
        if (!state || state.id==0) {
          return state && state.text ? state.text : '';
        }
        var imageFile = (state.title && state.title !== 'undefined' && state.title !== 'null') ? state.title : '';
        var src = imageFile ? ('/assets/img/products/' + imageFile) : 'data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=';
        var $state = $('<span><img onerror="this.onerror=null;this.src=\'data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=\';" src="'+ src +'" class="imagen-producto-select2" /> ' + state.text + '</span>');
        return $state;
      };
    var selectdpsearch = $(".select2psearch");

    if (selectdpsearch.length) {
        var $this = selectdpsearch;
        $this.wrap('<div class="position-relative"></div>').select2({
            placeholder: "Seleccionar Producto",
            dropdownParent: $this.parent(),
            templateResult: formatState
        });
    }
        //Get destinos
        $.ajax({
            url: "/sale/destinos",
            method: "GET",
            success: function (response) {
                $("#destino").append('<option value="0">Seleccione</option>');
                $.each(response, function (index, value) {
                    $("#destino").append(
                        '<option value="' + value.id + '">' + value.iata + ' - ' + value.ciudad + ' - ' + value.pais + ' - ' + value.continente + '</option>'
                    );
                });
            },
            error: function(xhr, status, error) {
                console.error('Error cargando destinos:', error);
            }
        });

        //Get linea aerea
        $.ajax({
            url: "/sale/linea",
            method: "GET",
            success: function (response) {
                $("#linea").append('<option value="0">Seleccione</option>');
                $.each(response, function (index, value) {
                    $("#linea").append(
                        '<option value="' + value.id + '">' + value.iata + ' - ' + value.nombre + '</option>'
                    );
                });
            },
            error: function(xhr, status, error) {
                console.error('Error cargando aerolíneas:', error);
            }
        });
});

var valcorrdoc = $("#valcorr").val();
var valdraftdoc = $("#valdraft").val();
if (valcorrdoc != "" && valdraftdoc == "true") {
    var draft = draftdocument(valcorrdoc, valdraftdoc);
}



function agregarp() {

    var productid = $("#productid").val();
    //alert(productid);
    var reserva = $('#reserva').val();
    var ruta = $('#ruta').val();
    var destino = $('#destino').val();
    var linea = $('#linea').val();
    var canal = $('#Canal').val();
    // Respetar lógica original: siempre leer de #fee (visible u oculto)
    var fee = parseFloat($("#fee").val()) || 0.00;
    //var fee2 = parseFloat($("#fee2").val()) || 0.00;

    // Validar si el producto es 9 y los campos son obligatorios
    if (productid == 9) {
        if (!reserva || !ruta || !destino || !linea || !canal) {
            swal.fire("Favor complete la información del producto");
            return;
        }
    } else {
        // Si el producto no es 9, enviar valores vacíos
        reserva = "null";
        ruta = "null";
        destino = "0";
        linea = "0";
        canal = "null";
    }
    var typedoc = $('#typedocument').val();
    var clientid = $("#client").val();
    var corrid = $("#corr").val();
    var acuenta = ($("#acuenta").val()==""?'SIN VALOR DEFINIDO':$("#acuenta").val());
    var fpago = $("#fpago").val();
    var productname = $("#productname").val();
    var marca = $("#marca").length ? $("#marca").val() : '';
    var price = parseFloat($("#precio").val());
    var ivarete13 = parseFloat($("#ivarete13").val());
    var rentarete = parseFloat($("#rentarete").val())||0.00;
    var ivarete = parseFloat($("#ivarete").val());
    var type = $("#typesale").val();
    var cantidad = parseFloat($("#cantidad").val());
    var productdescription = $("#productdescription").val();
    var pricegravada = 0;
    var priceexenta = 0;
    var pricenosujeta = 0;
    var sumas = parseFloat($("#sumas").val());
    var iva13 = parseFloat($("#13iva").val());
    var rentarete10 = parseFloat($("#rentaretenido").val());
    var ivaretenido = parseFloat($("#ivaretenido").val());
    var ventasnosujetas = parseFloat($("#ventasnosujetas").val());
    var ventasexentas = parseFloat($("#ventasexentas").val());
    var ventatotal = parseFloat($("#ventatotal").val());
    var descriptionbyproduct;
    //ventatotal = parseFloat(ventatotal/1.13).toFixed(2);
    var sumasl = 0;
    var ivaretenidol = 0;
    var iva13l = 0;
    var renta10l = 0;
    var ventasnosujetasl = 0;
    var ventasexentasl = 0;
    var ventatotall = 0;
    var iva13temp = 0;
    var renta10temp = 0;
    var totaltempgravado = 0;
    var priceunitariofee = 0;
    if (type == "gravada") {
        pricegravada = parseFloat((price * cantidad)+fee);
        totaltempgravado = parseFloat(pricegravada);
        if(typedoc==6 || typedoc==8){
            iva13temp = 0.00;
        }else if(typedoc==3){
            iva13temp = parseFloat(pricegravada * 0.13).toFixed(2);
        }

        //iva13temp = parseFloat(ivarete13 * cantidad).toFixed(2);
    } else if (type == "exenta") {
        priceexenta = parseFloat(price * cantidad);
        iva13temp = 0;
    } else if (type == "nosujeta") {
        pricenosujeta = parseFloat(price * cantidad);
        iva13temp = 0;
    }
    if(typedoc=='8'){
        iva13temp = 0.00;
    }
    if(!$.isNumeric(ivarete)){
        ivarete = 0.00;
    }
    renta10temp = parseFloat(rentarete*cantidad).toFixed(2);
    var totaltemp = parseFloat(parseFloat(pricegravada) + parseFloat(priceexenta) + parseFloat(pricenosujeta));
    var ventatotaltotal =  parseFloat(ventatotal); //+ parseFloat(iva13) + parseFloat(ivaretenido);
    priceunitariofee = price + (fee/cantidad);
    var totaltemptotal = parseFloat(
    ($.isNumeric(pricegravada)? pricegravada: 0) +
    ($.isNumeric(priceexenta)? priceexenta: 0) +
    ($.isNumeric(pricenosujeta)? pricenosujeta: 0) +
    ($.isNumeric(iva13temp)? parseFloat(iva13temp): 0) -
    ($.isNumeric(renta10temp)? parseFloat(renta10temp): 0) -
    ($.isNumeric(ivarete)? ivarete: 0));

    //descripcion factura con preferencia al preview
    var customDescriptionField = $("#product-description-edit");
    if (customDescriptionField.length && customDescriptionField.val().trim() !== "") {
        descriptionbyproduct = customDescriptionField.val().trim();
    } else if(productid==10){
        descriptionbyproduct = productname;
    }else {
        descriptionbyproduct =  productname + (marca? (" " + marca) : (" " + reserva + " " + ruta));
    }

    //enviar a temp factura
    // Validar corr y client antes de enviar para evitar 500 en backend
    if(!corrid || corrid==="" || !$.isNumeric(parseFloat(corrid))){
        Swal.fire("El correlativo no es válido. Refresca el borrador e inténtalo de nuevo.");
        return;
    }
    if(!clientid || clientid==="0"){
        Swal.fire("Selecciona un cliente antes de agregar productos.");
        return;
    }

    // Normalizar datos para evitar 'undefined' / 'NaN' en la URL
    function nz(v, def){ return (v===undefined || v===null || v==="" || (typeof v === 'number' && isNaN(v))) ? def : v; }
    productid = nz(productid, 0);
    cantidad = nz(cantidad, 1);
    price = nz(price, 0.00);
    pricenosujeta = nz(pricenosujeta, 0.00);
    priceexenta = nz(priceexenta, 0.00);
    pricegravada = nz(pricegravada, 0.00);
    ivarete13 = nz(ivarete13, 0.00);
    rentarete = nz(rentarete, 0.00);
    ivarete = nz(ivarete, 0.00);
    acuenta = nz(acuenta, 'SIN VALOR DEFINIDO');
    fpago = nz(fpago, 0);
    fee = nz(fee, 0.00);
    reserva = nz(reserva, 'null');
    ruta = nz(ruta, 'null');
    destino = nz(destino, 0);
    linea = nz(linea, 0);
    canal = nz(canal, 'null');

    // Armar URL absoluta y codificada para evitar caracteres inválidos
    var url =
        "/sale/savefactemp/" + encodeURIComponent(corrid) +
        "/" + encodeURIComponent(clientid) +
        "/" + encodeURIComponent(productid) +
        "/" + encodeURIComponent(cantidad) +
        "/" + encodeURIComponent(price) +
        "/" + encodeURIComponent(pricenosujeta) +
        "/" + encodeURIComponent(priceexenta) +
        "/" + encodeURIComponent(pricegravada) +
        "/" + encodeURIComponent(ivarete13) +
        "/" + encodeURIComponent(rentarete) +
        "/" + encodeURIComponent(ivarete) +
        "/" + encodeURIComponent(acuenta) +
        "/" + encodeURIComponent(fpago) +
        "/" + encodeURIComponent(fee) +
        "/" + encodeURIComponent(reserva) +
        "/" + encodeURIComponent(ruta) +
        "/" + encodeURIComponent(destino) +
        "/" + encodeURIComponent(linea) +
        "/" + encodeURIComponent(canal);
    $.ajax({
        url: url,
        method: "GET",
        success: function (response) {
            if (response.res == 1) {
                var row =
                    '<tr id="pro' +
                    response.idsaledetail +
                    '"><td>' +
                    cantidad +
                    "</td><td>" +
                    descriptionbyproduct +
                    "</td><td>" +
                    priceunitariofee.toLocaleString("en-US", {
                        style: "currency",
                        currency: "USD",
                    }) +
                    "</td><td>" +
                    pricenosujeta.toLocaleString("en-US", {
                        style: "currency",
                        currency: "USD",
                    }) +
                    "</td><td>" +
                    priceexenta.toLocaleString("en-US", {
                        style: "currency",
                        currency: "USD",
                    }) +
                    "</td><td>" +
                    pricegravada.toLocaleString("en-US", {
                        style: "currency",
                        currency: "USD",
                    }) +
                    '</td><td class="text-center">' +
                    totaltemp.toLocaleString("en-US", {
                        style: "currency",
                        currency: "USD",
                    }) +
                    '</td><td class="quitar_documents"><button class="btn rounded-pill btn-icon btn-danger" type="button" onclick="eliminarpro(' +
                    response.idsaledetail +
                    ')"><span class="ti ti-trash"></span></button></td></tr>';
                $("#tblproduct tbody").append(row);
                sumasl = sumas + totaltemp;
                $("#sumasl").html(
                    sumasl.toLocaleString("en-US", {
                        style: "currency",
                        currency: "USD",
                    })
                );
                $("#sumas").val(sumasl);
                if(typedoc==6 || typedoc==8){
                    iva13l=0.00;
                }else if(typedoc==3){
                    //calculo de iva 13%
                    iva13l = parseFloat(parseFloat(iva13) + parseFloat(iva13temp));
                }
                $("#13ival").html(
                    iva13l.toLocaleString("en-US", {
                        style: "currency",
                        currency: "USD",
                    })
                );
                $("#13iva").val(iva13l);

                if($("#typedocument").val() == '8'){
                    //calculo de retenido 10%
                renta10l = parseFloat(parseFloat(renta10temp) + parseFloat(rentarete10));
                $("#10rental").html(
                    renta10l.toLocaleString("en-US", {
                        style: "currency",
                        currency: "USD",
                    })
                );
                $("#rentaretenido").val(renta10l);
                }
                //calculo del retenido 1%
                ivaretenidol = ivaretenido + ivarete;
                $("#ivaretenidol").html(
                    ivaretenidol.toLocaleString("en-US", {
                        style: "currency",
                        currency: "USD",
                    })
                );
                $("#ivaretenido").val(ivaretenidol);
                ventasnosujetasl = ventasnosujetas + pricenosujeta;
                $("#ventasnosujetasl").html(
                    ventasnosujetasl.toLocaleString("en-US", {
                        style: "currency",
                        currency: "USD",
                    })
                );
                $("#ventasnosujetas").val(ventasnosujetasl);
                ventasexentasl = ventasexentas + priceexenta;
                $("#ventasexentasl").html(
                    ventasexentasl.toLocaleString("en-US", {
                        style: "currency",
                        currency: "USD",
                    })
                );
                $("#ventasexentas").val(ventasexentasl);

                ventatotall = parseFloat(ventatotaltotal)  + parseFloat(totaltemptotal);
                $("#ventatotall").html(
                    parseFloat(ventatotall).toLocaleString("en-US", {
                        style: "currency",
                        currency: "USD",
                    })
                );
                $('#ventatotallhidden').val(ventatotall);
                $("#ventatotal").val(ventatotall);
            } else if (response == 0) {
            }
        },
    });
    $('#precio').val(0.00);
    $('#fee').val(0.00);
    $('#ivarete13').val(0.00);
    $('#ivarete').val(0.00);
    $('#rentarete').val(0.00);
    $('#reserva').val();
    $('#ruta').val();
    $('#destino').val(null).trigger('change');
    $('#linea').val(null).trigger('change');
    $('#canal').val(null).trigger('change');
    $("#psearch").val("0").trigger("change.select2");
}

function totalamount() {
    var typecontricompany = $("#typecontribuyente").val();
    var typecontriclient = $("#typecontribuyenteclient").val();
    var typedoc = $('#typedocument').val();

    // Convertir valores a números asegurando que no sean NaN
    var cantidad = parseFloat($("#cantidad").val()) || 0.00;
    var fee = parseFloat($("#fee").val()) || 0.00;
    //var fee2 = parseFloat($("#fee2").val()) || 0.00;
    var iva = parseFloat($("#iva").val()) || 0.00;
    var valor = parseFloat($('#precio').val()) || 0.00;

    var ivarete13 = 0.00;
    var retencionamount = 0.00;
    var renta = 0.00;
    var totalamount = 0.00;
    var totaamountsinivi = 0.00;
    var totalvalor = 0.00;
    var totalfee = 0.00;
    let retencion = 0.00;

    // Evaluar la retención de IVA según el tipo de contribuyente
    if (typecontricompany === "GRA") { // Empresa grande
        if (typecontriclient === "GRA") {
            retencion = 0.01; // 1% de retención cuando ambas son grandes
        } else if (["MED", "PEQ", "OTR"].includes(typecontriclient)) {
            retencion = 0.01; // 1% cuando empresa grande paga a mediana, pequeña u otro
        }
    } else if (["MED", "PEQ", "OTR"].includes(typecontricompany)) { // Empresa no es grande
        retencion = 0.00; // No retiene IVA
    }

    totalamount = parseFloat(valor * cantidad);
    totaamountsinivi = parseFloat(totalamount/1.13);

    // IVA 13% (solo CCF)
    if (typedoc === '3') {
        totalvalor = parseFloat(totalamount * iva);
        totalfee = parseFloat((fee * cantidad) * iva);
        ivarete13 = parseFloat(totalvalor + totalfee);
        $("#ivarete13").val(ivarete13.toFixed(8));
    } else {
        $("#ivarete13").val(0);
        ivarete13 = 0.00;
    }

    // IVA Percibido 1% sobre total por cantidad
    retencionamount = parseFloat(totalamount * retencion);
    $("#ivarete").val(retencionamount.toFixed(8));

    // Renta 10% (sujeto excluido)
    if (typedoc === '8') {
        renta = parseFloat(totalamount * 0.10);
        $("#rentarete").val(renta.toFixed(8));
    } else {
        renta = 0.00;
    }

    // Total general: precio + fee*cantidad + IVA - retenciones
    totalfee = parseFloat(fee * cantidad);
    var totalFinal = totalamount + totalfee + ivarete13 - retencionamount - renta;

    $("#total").val((typedoc==='3'? totalFinal.toFixed(8) : totalFinal.toFixed(2))); // Precisión alta para CCF
}


// Función para actualizar la descripción del producto cuando cambien ruta o reserva
function updateProductDescription() {
    var productname = $("#productname").val();
    var reserva = $("#reserva").val();
    var ruta = $("#ruta").val();
    var marca = $("#marca").val();

    if (productname) {
        var description = productname;

        // Agregar marca si existe
        if (marca) {
            description += " " + marca;
        }

        // Agregar reserva y ruta si existen
        if (reserva || ruta) {
            description += " ";
            if (reserva) description += reserva;
            if (ruta) description += " " + ruta;
        }

        // Actualizar el campo de descripción personalizada
        if ($("#product-description-edit").length) {
            $("#product-description-edit").val(description);
        }
    }
}

function searchproduct(idpro) {
    // Mostrar campos adicionales para productos de viajes/tickets
    // Se puede modificar esta lógica según las necesidades del negocio
    if(idpro==16 || idpro==1 || idpro==2 || idpro==3){
        $("#add-information-tickets").css("display", "");
    }else{
        $("#add-information-tickets").css("display", "none");
    }
    //Get products by id avaibles
    var typedoc = $('#typedocument').val();
    var typecontricompany = $("#typecontribuyente").val();
    var typecontriclient = $("#typecontribuyenteclient").val();
    var iva = parseFloat($("#iva").val());
    var iva_entre = parseFloat($("#iva_entre").val());
    //var typecontriclient = $("#typecontribuyenteclient").val();
    var retencion=0.00;
    var pricevalue;
    $.ajax({
        url: "/product/getproductid/" + btoa(idpro),
        method: "GET",
        success: function (response) {
            $.each(response, function (index, value) {

                if(typedoc=='6' || typedoc=='8'){
                    pricevalue = parseFloat(value.price);
                }else if(typedoc=='3'){
                    // Crédito Fiscal: precio unitario SIN IVA
                    pricevalue = parseFloat(value.price/1.13);
                    // Llenar también el campo de precio con IVA si existe
                    if($("#precioConIva").length){
                        $("#precioConIva").val(parseFloat(value.price).toFixed(8));
                    }
                }else{
                    pricevalue = parseFloat(value.price/iva_entre);
                }
                $("#precio").val((typedoc==='3'? pricevalue.toFixed(8) : pricevalue.toFixed(2)));
                $("#productname").val(value.productname);
                if($("#marca").length){ $("#marca").val(value.marcaname); }
                $("#productid").val(value.id);
                $("#productdescription").val(value.description);
                $("#productunitario").val(value.id);
                // Pre-llenar el campo de descripción personalizada con la descripción por defecto
                if($("#product-description-edit").length){
                    var defaultDescription = value.productname + (value.marcaname? (" " + value.marcaname) : "");
                    $("#product-description-edit").val(defaultDescription);
                }
                // Llamar a la función de actualización para incluir ruta y reserva si existen
                updateProductDescription();
                // Actualizar imagen en la vista previa (si existe el contenedor)
                if($("#product-image").length){
                    var imgTitle = value.image || value.title || '';
                    var imgSrc = imgTitle ? ('/assets/img/products/' + imgTitle) : 'data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=';
                    $("#product-image").attr('src', imgSrc).on('error', function(){
                        $(this).attr('src','data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=');
                    });
                }
                //validar si es gran contribuyente el cliente vs la empresa

                if (typecontricompany == "GRA") {
                    if (typecontriclient == "GRA") {
                        retencion = 0.01;
                    } else if (
                        typecontriclient == "MED" ||
                        typecontriclient == "PEQ" ||
                        typecontriclient == "OTR"
                    ) {
                        retencion = 0.00;
                    }
                }
                if(typecontriclient==""){
                    retencion = 0.0;
                }
                if(typedoc=='3'){
                    $("#ivarete13").val(parseFloat(pricevalue * iva).toFixed(8));
                }else{
                    $("#ivarete13").val(0);
                }
                $("#ivarete").val(
                    parseFloat(pricevalue * retencion).toFixed(8)
                );
                if(typedoc=='8'){
                    $("#rentarete").val(
                        parseFloat(pricevalue * 0.10).toFixed(8)
                    );
                }
            });
            var updateamounts = totalamount();
        },
    });
}

function changetypesale(type){
    var price = $("#precio").val();
    var typedoc = $('#typedocument').val();
    var iva = parseFloat($("#iva").val());
switch(type){
    case 'gravada':
        if(typedoc=='6' || typedoc=='8'){
            $('#ivarete13').val(parseFloat(0));
        }else{
            $('#ivarete13').val(parseFloat(price*iva).toFixed(2));
        }

        if(typedoc=='8'){
            $('#rentarete').val(parseFloat(price*0.10).toFixed(2));
        }

        break;
    case 'exenta':
        $('#ivarete13').val(0.00);
        $('#ivarete').val(0.00);
        $('#rentarete').val(0.00);
        break;
    case 'nosujeta':
        $('#ivarete13').val(0.00);
        break;
}
}

function eliminarpro(id) {
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
                var corr = $('#valcorr').val();
                var document = $('#typedocument').val();
                $.ajax({
                    url: "destroysaledetail/" + btoa(id),
                    method: "GET",
                    async: false,
                    success: function (response) {
                        if (response.res == 1) {
                            Swal.fire({
                                title: "Eliminado",
                                icon: "success",
                                confirmButtonText: "Ok",
                            }).then((result) => {
                                /* Read more about isConfirmed, isDenied below */
                                if (result.isConfirmed) {
                                    //$("#pro" + id).remove();
                                    //$('#resultados').load(location.href + " #resultados");
                                    //var details = agregarfacdetails($('#valcorr').val());
                                    //location.reload(true);
                                    window.location.href =
                                    "create?corr=" + corr + "&draft=true&typedocument=" + document +"&operation=delete";
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

function aviablenext(idcompany) {
    $("#step1").prop("disabled", false);
}

function getclientbycompanyurl(idcompany) {
    $.ajax({
        url: "/client/getclientbycompany/" + btoa(idcompany),
        method: "GET",
        success: function (response) {
            $("#client").append('<option value="0">Seleccione</option>');
            $.each(response, function (index, value) {
                //console.log(value);
                if(value.tpersona=='J'){
                    $("#client").append(
                        '<option value="' +
                            value.id +
                            '">' +
                            value.name_contribuyente.toUpperCase() +
                            "</option>"
                    );
                }else if (value.tpersona=='N'){
                    $("#client").append(
                        '<option value="' +
                            value.id +
                            '">' +
                            value.firstname.toUpperCase() +
                            " " +
                            value.firstlastname.toUpperCase() +
                            "</option>"
                    );
                }
            });
        },
    });

    //traer el tipo de contribuyente
    $.ajax({
        url: "/company/gettypecontri/" + btoa(idcompany),
        method: "GET",
        success: function (response) {
            $("#typecontribuyente").val(response.tipoContribuyente);
        },
    });
}

function valtrypecontri(idcliente) {
    // Limpiar/ocultar panel antes de cargar
    if ($("#client-info").length) { $("#client-info").hide(); }

    if (!idcliente || idcliente === '0') {
        return;
    }

    // Obtener información completa del cliente y validar CCF si aplica
    $.ajax({
        url: "/client/gettypecontri/" + btoa(idcliente),
        method: "GET",
        success: function (response) {
            $("#typecontribuyenteclient").val(response.tipoContribuyente);

            // Validar si se puede crear crédito fiscal
            var typedocument = $("#typedocument").val();
            if (typedocument === '3') { // Crédito Fiscal
                var tpersona = response.tpersona;
                var contribuyente = response.contribuyente;
                // Solo permitir: Naturales contribuyentes (contribuyente='1') y Jurídicos
                if (tpersona === 'N' && contribuyente !== '1') {
                    Swal.fire({
                        title: "No se puede crear Crédito Fiscal",
                        text: "Solo se permiten personas naturales contribuyentes y personas jurídicas.",
                        icon: "warning",
                        confirmButtonText: "Entendido"
                    });
                    $("#client").val('').trigger('change');
                    return;
                }
            }

            // Mostrar panel de información del cliente si está disponible en la vista
            if ($("#client-info").length) {
                var clientName = '';
                if (response.tpersona === 'J') {
                    clientName = (response.name_contribuyente || response.comercial_name || 'N/A');
                } else {
                    clientName = [response.firstname, response.secondname, response.firstlastname, response.secondlastname]
                        .filter(Boolean)
                        .join(' ')
                        .trim();
                }
                var clientType = (response.tpersona === 'J') ? 'Persona Jurídica' : 'Persona Natural';
                var isContrib = (response.tpersona === 'J') ? 'Sí (Jurídico)' : (response.contribuyente === '1' ? 'Sí (Natural Contribuyente)' : 'No (Natural No Contribuyente)');

                $("#client-name").text(clientName || 'N/A');
                $("#client-type").text(clientType);
                $("#client-contribuyente").text(isContrib);
                $("#client-nit").text(response.nit || response.dui || 'N/A');
                $("#client-address").text(response.address || 'N/A');
                $("#client-phone").text(response.phone || 'N/A');

                $("#client-info").show();
            }
        },
        error: function () {
            // opcional: feedback
        }
    });
}
function createcorrsale() {
    //crear correlativo temp de factura
    let salida = false;
    var valicorr = $("#corr").val();
    var valdraftcorr = $("#valcorr").val();
    // Si ya existe correlativo en campo local o viene en la URL como borrador, no crear de nuevo
    if (valicorr == "" && (typeof valdraftcorr === 'undefined' || valdraftcorr === "")) {
        var idcompany = $("#company").val();
        var iduser = $("#iduser").val();
        var typedocument = $("#typedocument").val();
        $.ajax({
            url: "newcorrsale/" + idcompany + "/" + iduser + "/" + typedocument,
            method: "GET",
            async: false,
            success: function (response) {
                if ($.isNumeric(response.sale_id)) {
                    //recargar la pagina para retomar si una factura quedo en modo borrador
                    //$("#corr").val(response);
                    //salida = true;
                    window.__creatingCorr = false;
                    window.location.href =
                        "create?corr=" + response.sale_id + "&draft=true&typedocument=" + typedocument;
                } else {
                    Swal.fire("Hay un problema, favor verificar"+response);
                    window.__creatingCorr = false;
                }
            },
            error: function(){
                window.__creatingCorr = false;
            }
        });
    } else {
        salida = true;
    }

    return salida;
}

function valfpago(fpago) {
    //alert(fpago);
}

function draftdocument(corr, draft) {
    if (draft) {
        $.ajax({
            url: "getdatadocbycorr/" + btoa(corr),
            method: "GET",
            async: false,
            success: function (response) {
                //console.log(response);
                $.each(response, function (index, value) {
                    //campo de company
                    $('#company').empty();
                    $("#company").append(
                        '<option value="' +
                            value.id +
                            '">' +
                            value.name.toUpperCase() +
                            "</option>"
                    );
                    $("#step1").prop("disabled", false);
                    $('#company').prop('disabled', true);
                    $('#corr').prop('disabled', true);
                    $("#typedocument").val(value.typedocument_id);
                    $("#typecontribuyente").val(value.tipoContribuyente);
                    $("#iva").val(value.iva);
                    $("#iva_entre").val(value.iva_entre);
                    $("#typecontribuyenteclient").val(value.client_contribuyente);
                    $('#date').prop('disabled', true);
                    $("#corr").val(corr);
                    $("#date").val(value.date);
                    //campo cliente
                    if(value.client_id != null && value.client_firstname!='N/A'){
                        $("#client").append(
                            '<option value="' +
                                value.client_id +
                                '">' +
                                value.client_firstname +' '+ value.client_secondname +
                                "</option>"
                        );
                        $('#client').prop('disabled', true);
                    }else if(value.client_firstname=='N/A') {
                        $("#client").append(
                            '<option value="' +
                                value.client_id +
                                '">' +
                                value.comercial_name +
                                "</option>"
                        );
                        $('#client').prop('disabled', true);
                    }else{
                        var getsclient =  getclientbycompanyurl(value.id);
                    }
                    if(value.waytopay != null){
                        $("#fpago option[value="+ value.waytopay +"]").attr("selected",true);
                    }
                    $("#acuenta").val(value.acuenta);
                    var details = agregarfacdetails(corr);
                });
            },
            failure: function (response) {
                Swal.fire("Hay un problema: " + response.responseText);
            },
            error: function (response) {
                Swal.fire("Hay un problema: " + response.responseText);
            },
        });
    }
}

function CheckNullUndefined(value) {
    return typeof value == 'string' && !value.trim() || typeof value == 'undefined' || value === null;
  }

function getinfodoc(){
    var corr = $('#valcorr').val();
    let salida = false;
    $.ajax({
        url: "getdatadocbycorr2/" + btoa(corr),
        method: "GET",
        async: false,
        success: function (response) {
            salida = true;
            //console.log(response);
            $('#logodocfinal').attr('src', '../assets/img/logo/' + response[0].logo);
            $('#addressdcfinal').empty();
            $('#addressdcfinal').html('' + response[0].country_name.toUpperCase() + ', ' + response[0].department_name + ', ' + response[0].municipality_name + '</br>' + response[0].address);
            $('#phonedocfinal').empty();
            $('#phonedocfinal').html('' + ((CheckNullUndefined(response[0].phone_fijo)==true) ? '' : 'PBX: +503 ' + response[0].phone_fijo) + ' ' + ((CheckNullUndefined(response[0].phone)==true) ? '' : 'Móvil: +503 ' + response[0].phone));
            $('#emaildocfinal').empty();
            $('#emaildocfinal').html(response[0].email);
            $('#name_client').empty();
            if(response[0].tpersona == 'J'){
                $('#name_client').html(response[0].name_contribuyente);
            }else if (response[0].tpersona == 'N'){
                $('#name_client').html(response[0].client_firstname + ' ' + response[0].client_secondname);
            }
            $('#date_doc').empty();
            var dateformat = response[0].date.split('-');
            $('#date_doc').html(dateformat[2] + '/' + dateformat[1] + '/' + dateformat[0]);
            $('#address_doc').empty();
            $('#address_doc').html(response[0].address);
            $('#duinit').empty();
            $('#duinit').html(response[0].nit);
            $('#municipio_name').empty();
            $('#municipio_name').html(response[0].municipality_name);
            $('#giro_name').empty();
            $('#giro_name').html(response[0].giro);
            $('#name_type_documents_details').empty();
            $('#name_type_documents_details').html(response[0].document_name);
            $('#corr_details').empty();
            $('#corr_details').html('USD' + response[0].corr + '00000');
            $('#NCR_details').empty();
            $('#NCR_details').html('NCR: ' + response[0].NCR);
            $('#NIT_details').empty();
            $('#NIT_details').html('NIT: ' + response[0].NIT);
            $('#departamento_name').empty();
            $('#departamento_name').html(response[0].department_name);
            $('#forma_pago_name').empty();
            var forma_name;
            switch(response[0].waytopay){
                case "1":
                    forma_name='CONTADO';
                    break;
                case "2":
                    forma_name='CREDITO';
                    break;
                case "3":
                    forma_name='OTRO';
                    break;
            }
            $('#forma_pago_name').html(forma_name);
            $('#acuenta_de').empty();
            $('#acuenta_de').html(response[0].acuenta);
            var div_copy = $('#tblproduct').clone();
                div_copy.removeClass();
                div_copy.addClass('table_details');
                div_copy.find('.fadeIn').removeClass();
                div_copy.children().val("");
                div_copy.find('.quitar_documents').remove();
                div_copy.find('.bg-secondary').removeClass();
                div_copy.find('.text-white').removeClass();
                div_copy.find('thead').addClass('head_details');
                div_copy.find('tfoot').addClass('tfoot_details');
                div_copy.find('th').addClass('th_details');
                div_copy.find('td').addClass('td_details');
                $('#details_products_documents').empty();
                $('#details_products_documents').append(div_copy);
                //$(".quitar_documents").empty();
                //$("#quitar_documents").remove();
        },
    });
    return salida;
}

function creardocuments() {
    const swalWithBootstrapButtons = Swal.mixin({
        customClass: {
            confirmButton: "btn btn-success",
            cancelButton: "btn btn-danger",
        },
        buttonsStyling: false,
    });

    swalWithBootstrapButtons
        .fire({
            title: "Crear Documento?",
            text: "Es seguro de guardar la información",
            icon: "info",
            showCancelButton: true,
            confirmButtonText: "Si, Crear!",
            cancelButtonText: "No, espera!",
            reverseButtons: true,
            showLoaderOnConfirm: true, // Agrega un ícono de espera en el botón de confirmación
            preConfirm: () => {
                return new Promise((resolve) => {
                    var corr = $('#valcorr').val();
                    var totalamount = $('#ventatotallhidden').val();
                    totalamount = 0 + totalamount;

                    $.ajax({
                        url: "createdocument/" + btoa(corr) + '/' + totalamount,
                        method: "GET",
                        success: function (response) {
                            console.log('Respuesta del servidor:', response);

                            if (response.res == 1) {
                                resolve(response); // Resuelve la promesa si la solicitud es exitosa
                            } else if (response.res == 0) {
                                reject("Algo salió mal"); // Rechaza la promesa si hay un problema
                            } else if (typeof response === 'string') {
                                // Respuesta de error de Hacienda (JSON string)
                                try {
                                    const errorData = JSON.parse(response);
                                    if (errorData.codEstado === "03") {
                                        reject({
                                            type: 'hacienda_rejected',
                                            message: errorData.descripcionMsg || 'Documento rechazado por Hacienda',
                                            codigo: errorData.codigoMsg,
                                            observaciones: errorData.observacionesMsg,
                                            data: errorData
                                        });
                                    } else {
                                        reject({
                                            type: 'hacienda_error',
                                            message: errorData.descripcionMsg || 'Error en Hacienda',
                                            data: errorData
                                        });
                                    }
                                } catch (e) {
                                    reject({
                                        type: 'parse_error',
                                        message: 'Error al procesar respuesta de Hacienda',
                                        rawResponse: response
                                    });
                                }
                            } else if (response.error) {
                                // Error del servidor
                                reject({
                                    type: 'server_error',
                                    message: response.message || 'Error del servidor',
                                    error: response.error
                                });
                            } else {
                                reject({
                                    type: 'unknown_error',
                                    message: 'Error desconocido',
                                    response: response
                                });
                            }
                        },
                        error: function (xhr, status, error) {
                            console.error('Error AJAX:', xhr, status, error);
                            reject({
                                type: 'ajax_error',
                                message: 'Error de conexión: ' + error,
                                status: xhr.status,
                                response: xhr.responseText
                            });
                        }
                    });
                });
            },
        })
        .then((result) => {
            if (result.value) {
                Swal.fire({
                    title: "DTE Creado correctamente",
                    icon: "success",
                    confirmButtonText: "Ok",
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = "index";
                    }
                });
            }
        })
        .catch((error) => {
            console.error('Error en creación de documento:', error);

            let title = "Error";
            let message = "Algo sucedió y el documento no fue creado.";
            let icon = "error";
            let showCancelButton = false;
            let confirmButtonText = "Entendido";
            let cancelButtonText = null;

            // Manejar diferentes tipos de errores
            if (typeof error === 'object' && error.type) {
                switch (error.type) {
                    case 'hacienda_rejected':
                        title = "Documento Rechazado por Hacienda";
                        message = `
                            <div class="text-left">
                                <p><strong>Motivo:</strong> ${error.message}</p>
                                ${error.codigo ? `<p><strong>Código:</strong> ${error.codigo}</p>` : ''}
                                ${error.observaciones ? `<p><strong>Observaciones:</strong> ${error.observaciones}</p>` : ''}
                                <hr>
                                <p class="text-muted small">La venta se ha guardado como borrador. Puedes corregir los datos y reintentar.</p>
                            </div>
                        `;
                        icon = "warning";
                        showCancelButton = true;
                        confirmButtonText = "Reintentar";
                        cancelButtonText = "Ver Borradores";
                        break;

                    case 'hacienda_error':
                        title = "Error en Hacienda";
                        message = `
                            <div class="text-left">
                                <p><strong>Error:</strong> ${error.message}</p>
                                <hr>
                                <p class="text-muted small">La venta se ha guardado como borrador. Intenta nuevamente más tarde.</p>
                            </div>
                        `;
                        icon = "error";
                        break;

                    case 'server_error':
                        title = "Error del Servidor";
                        message = `
                            <div class="text-left">
                                <p><strong>Error:</strong> ${error.message}</p>
                                <hr>
                                <p class="text-muted small">Comunícate con el administrador si el problema persiste.</p>
                            </div>
                        `;
                        icon = "error";
                        break;

                    case 'ajax_error':
                        title = "Error de Conexión";
                        message = `
                            <div class="text-left">
                                <p><strong>Error:</strong> ${error.message}</p>
                                <p><strong>Estado:</strong> ${error.status}</p>
                                <hr>
                                <p class="text-muted small">Verifica tu conexión a internet e intenta nuevamente.</p>
                            </div>
                        `;
                        icon = "error";
                        break;

                    default:
                        title = "Error Desconocido";
                        message = `
                            <div class="text-left">
                                <p><strong>Error:</strong> ${error.message || 'Error desconocido'}</p>
                                <hr>
                                <p class="text-muted small">Comunícate con el administrador.</p>
                            </div>
                        `;
                        icon = "error";
                        break;
                }
            } else if (typeof error === 'string') {
                // Error simple (compatibilidad con código anterior)
                title = "Error";
                message = error;
                icon = "error";
            }

            // Mostrar el SweetAlert con la configuración apropiada
            const swalConfig = {
                title: title,
                html: message,
                icon: icon,
                showCancelButton: showCancelButton,
                confirmButtonText: confirmButtonText,
                cancelButtonText: cancelButtonText,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33'
            };

            swalWithBootstrapButtons.fire(swalConfig).then((result) => {
                if (result.isConfirmed && error.type === 'hacienda_rejected') {
                    // Reintentar la creación del documento
                    console.log('Reintentando creación de documento...');
                    // Aquí podrías llamar nuevamente a la función de creación
                    // o recargar la página para que el usuario corrija los datos
                    window.location.reload();
                } else if (result.dismiss === Swal.DismissReason.cancel && error.type === 'hacienda_rejected') {
                    // Ir a ver borradores
                    window.location.href = "index";
                }
            });
        });
}


function agregarfacdetails(corr) {
    var typedoc = $('#typedocument').val()
    $.ajax({
        url: "getdetailsdoc/" + btoa(corr),
        method: "GET",
        async: false,
        success: function (response) {

            let totaltemptotal = 0;
            let totalsumas = 0;
            let ivarete13total = 0;
            let rentatotal = 0;
            let ivaretetotal = 0;
            let nosujetatotal = 0;
            let exempttotal = 0;
            let pricesaletotal = 0;
            let preciounitario = 0;
            let preciogravadas = 0;
            $.each(response, function (index, value) {

                if(typedoc=='6' || typedoc=='8'){
                    ivarete13total += parseFloat(0.00);
                    preciounitario = parseFloat(parseFloat(value.priceunit)+(value.detained13/value.amountp));
                    preciogravadas = parseFloat(parseFloat(value.pricesale)+parseFloat(value.detained13));
                }else{
                    ivarete13total += parseFloat(value.detained13);
                    preciounitario = parseFloat(value.priceunit);
                    preciogravadas = parseFloat(value.pricesale);
                }
                var totaltemp = (parseFloat(value.nosujeta) + parseFloat(value.exempt) + parseFloat(preciogravadas));
                totalsumas += totaltemp;
                rentatotal += parseFloat(value.renta);
                ivaretetotal += parseFloat(value.detained);
                nosujetatotal += parseFloat(value.nosujeta);
                exempttotal += parseFloat(value.exempt);
                pricesaletotal += parseFloat(value.pricesale);
                totaltemptotal += (parseFloat(value.nosujeta) + parseFloat(value.exempt) + parseFloat(value.pricesale))
                + (parseFloat(value.detained13) - (parseFloat(value.renta) + (parseFloat(value.detained))));
                var sumasl = 0;
                var iva13l = 0;
                var renta10l = 0;
                var ivaretenidol = 0;
                var ventasnosujetasl = 0;
                var ventasexentasl = 0;
                var ventatotall = 0;
                var row =
                    '<tr id="pro' +
                    value.id +
                    '"><td>' +
                    value.amountp +
                    "</td><td>" +
                    value.product_name +
                    "</td><td>" +
                    preciounitario.toLocaleString("en-US", {
                        style: "currency",
                        currency: "USD",
                    }) +
                    "</td><td>" +
                    parseFloat(value.nosujeta).toLocaleString("en-US", {
                        style: "currency",
                        currency: "USD",
                    }) +
                    "</td><td>" +
                    parseFloat(value.exempt).toLocaleString("en-US", {
                        style: "currency",
                        currency: "USD",
                    }) +
                    "</td><td>" +
                    preciogravadas.toLocaleString("en-US", {
                        style: "currency",
                        currency: "USD",
                    }) +
                    '</td><td class="text-center">' +
                    totaltemp.toLocaleString("en-US", {
                        style: "currency",
                        currency: "USD",
                    }) +
                    '</td><td class="quitar_documents"><button class="btn rounded-pill btn-icon btn-danger" type="button" onclick="eliminarpro(' +
                    value.id +
                    ')"><span class="ti ti-trash"></span></button></td></tr>';
                $("#tblproduct tbody").append(row);
                sumasl = totalsumas;
                $("#sumasl").html(
                    sumasl.toLocaleString("en-US", {
                        style: "currency",
                        currency: "USD",
                    })
                );
                $("#sumas").val(sumasl);
                iva13l = ivarete13total;
                $("#13ival").html(
                    iva13l.toLocaleString("en-US", {
                        style: "currency",
                        currency: "USD",
                    })
                );
                $("#13iva").val(iva13l);
                renta10l = rentatotal;
                $("#10rental").html(
                    renta10l.toLocaleString("en-US", {
                        style: "currency",
                        currency: "USD",
                    })
                );
                $("#rentaretenido").val(renta10l);
                ivaretenidol =  ivaretetotal;
                $("#ivaretenidol").html(
                    ivaretenidol.toLocaleString("en-US", {
                        style: "currency",
                        currency: "USD",
                    })
                );
                $("#ivaretenido").val(ivaretenidol);
                ventasnosujetasl =  nosujetatotal;
                $("#ventasnosujetasl").html(
                    ventasnosujetasl.toLocaleString("en-US", {
                        style: "currency",
                        currency: "USD",
                    })
                );
                $("#ventasnosujetas").val(ventasnosujetasl);
                ventasexentasl = exempttotal;
                $("#ventasexentasl").html(
                    ventasexentasl.toLocaleString("en-US", {
                        style: "currency",
                        currency: "USD",
                    })
                );
                $("#ventasexentas").val(ventasexentasl);
                ventatotall = totaltemptotal;
                $("#ventatotall").html(
                    ventatotall.toLocaleString("en-US", {
                        style: "currency",
                        currency: "USD",
                    })
                );
                $("#ventatotallhidden").val(ventatotall);
                $("#ventatotal").val(ventatotall);
            });
        },
        failure: function (response) {
            Swal.fire("Hay un problema: " + response.responseText);
        },
        error: function (response) {
            Swal.fire("Hay un problema: " + response.responseText);
        },
    });
}

(function () {
    // Icons Wizard
    // --------------------------------------------------------------------
    const wizardIcons = document.querySelector(".wizard-icons-example");

    if (typeof wizardIcons !== undefined && wizardIcons !== null) {
        const wizardIconsBtnNextList = [].slice.call(
                wizardIcons.querySelectorAll(".btn-next")
            ),
            wizardIconsBtnPrevList = [].slice.call(
                wizardIcons.querySelectorAll(".btn-prev")
            ),
            wizardIconsBtnSubmit = wizardIcons.querySelector(".btn-submit");

        const iconsStepper = new Stepper(wizardIcons, {
            linear: false,
        });
        if (wizardIconsBtnNextList) {
            wizardIconsBtnNextList.forEach((wizardIconsBtnNext) => {
                wizardIconsBtnNext.addEventListener("click", (event) => {
                    var id = $(wizardIconsBtnNext).attr("id");
                    switch (id) {
                        case "step1":
                            var create = createcorrsale();
                            if (create) {
                                iconsStepper.next();
                            }
                            break;
                        case "step2":
                            iconsStepper.to(3);
                            break;
                        case "step3":
                            var createdoc = getinfodoc();
                            if(createdoc){
                                iconsStepper.to(4);
                            }
                            break;

                    }
                });
            });
        }
        if (wizardIconsBtnPrevList) {
            wizardIconsBtnPrevList.forEach((wizardIconsBtnPrev) => {
                wizardIconsBtnPrev.addEventListener("click", (event) => {
                    iconsStepper.previous();
                });
            });
        }
        if (wizardIconsBtnSubmit) {
            wizardIconsBtnSubmit.addEventListener("click", (event) => {
                creardocuments();
            });
        }
    }
})();
