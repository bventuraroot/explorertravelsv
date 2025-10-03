/**
 * Form Picker
 */

'use strict';
$(document).ready(function (){

    //input mask
    $("#ncr").inputmask("999999-9");



    $("#tel1").inputmask("9999-9999");
    $("#tel2").inputmask("9999-9999");

    $("#email").on("keyup", function () {
        var valor = $(this).val();
        $(this).val(valor.toLowerCase());
    });

    //input mask
    $("#ncredit").inputmask("999999-9");
    $("#nitedit").inputmask("99999999-9");
    $("#tel1edit").inputmask("9999-9999");
    $("#tel2edit").inputmask("9999-9999");

    $("#email-edit").on("keyup", function () {
        var valor = $(this).val();
        $(this).val(valor.toLowerCase());
    });
    //Get companies avaibles
    var iduser = $('#iduser').val();
    console.log('ID Usuario:', iduser);
    console.log('URL:', "/company/getCompanybyuser/"+iduser);

    $.ajax({
        url: "/company/getCompanybyuser/"+iduser,
        method: "GET",
        success: function(response){
            console.log('Respuesta de empresas:', response);
            if (response.length > 0) {
                // Auto-seleccionar la primera empresa disponible
                var firstCompanyId = response[0].id;
                $.each(response, function(index, value) {
                    $('#company').append('<option value="'+value.id+'">'+value.name.toUpperCase()+'</option>');
                    $('#companyupdate').append('<option value="'+value.id+'">'+value.name.toUpperCase()+'</option>');
                });
                // Establecer la primera empresa como seleccionada por defecto
                $('#company').val(firstCompanyId).trigger('change');
                $('#companyupdate').val(firstCompanyId).trigger('change');

                // Forzar el valor en el campo oculto
                $('#company').attr('value', firstCompanyId);
                $('#companyupdate').attr('value', firstCompanyId);

                console.log('Empresa seleccionada:', firstCompanyId);
            } else {
                // Si no hay empresas, mostrar mensaje pero no permitir guardar
                $('#company').append('<option value="">No hay empresas disponibles</option>');
                $('#companyupdate').append('<option value="">No hay empresas disponibles</option>');
                console.log('No hay empresas disponibles para el usuario');
            }
        },
        error: function(xhr, status, error) {
            console.log('Error al cargar empresas:', error);
            console.log('Status:', status);
            console.log('Response:', xhr.responseText);
            $('#company').append('<option value="">Error al cargar empresas</option>');
            $('#companyupdate').append('<option value="">Error al cargar empresas</option>');
        }
    });
    getpaises();

    // Asegurar que el valor de empresa se mantenga al enviar el formulario
    $('#addProviderForm').on('submit', function() {
        var companyValue = $('#company').val();
        if (companyValue && companyValue !== '') {
            console.log('Enviando empresa:', companyValue);
        } else {
            console.log('Error: No hay empresa seleccionada');
        }
    });
});

function nitDuiMask(inputField) {
    var separator = "-";
    var nitPattern;
    var cleanValue = inputField.value.replace(/[- ]/g, ''); // Quitar guiones y espacios

    // Solo permitir números y letras
    cleanValue = cleanValue.replace(/[^A-Z0-9]/gi, "");
    inputField.value = cleanValue; // Actualiza el campo sin caracteres no permitidos

    var length = cleanValue.length;

    if (length <= 9) {
        // DUI: formato xxxxxxxx-x (8 dígitos + 1 dígito verificador)
        if (length == 9) {
            nitPattern = new Array(8, 1);
        } else {
            nitPattern = new Array(length); // Sin separador hasta completar
        }
    } else if (length <= 14) {
        // NIT: formato xxxx-xxxxxx-xxx-x (4 + 6 + 3 + 1)
        if (length >= 14) {
            nitPattern = new Array(4, 6, 3, 1);
        } else if (length >= 10) {
            nitPattern = new Array(4, 6, length - 10);
        } else {
            nitPattern = new Array(4, length - 4);
        }
    } else {
        // Para NIT más largos, usar formato flexible
        if (length <= 17) {
            nitPattern = new Array(4, 6, 3, length - 13);
        } else {
            nitPattern = new Array(4, 6, 3, 1, length - 14);
        }
    }

    mask(inputField, separator, nitPattern, false); // Cambiar a false para permitir letras y números
}

function NRCMask(inputField) {
    var separator = '-';
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
        val2 = '';
        for (r = 0; r < val.length; r++) {
            val2 += val[r]
        }
        if (nums) {
            for (z = 0; z < val2.length; z++) {
                if (isNaN(val2.charAt(z))) {
                    letra = new RegExp(val2.charAt(z), "g")
                    val2 = val2.replace(letra, "")
                }
            }
        }
        val = ''
        val3 = new Array()
        for (s = 0; s < pattern.length; s++) {
            val3[s] = val2.substring(0, pattern[s])
            val2 = val2.substr(pattern[s])
        }
        for (q = 0; q < val3.length; q++) {
            if (q == 0) {
                val = val3[q]
            } else {
                if (val3[q] != "") {
                    val += separator + val3[q]
                }
            }
        }
        inputField.value = val
        inputField.valant = val
    }

}

function getpaises(selected="",type=""){
    if(type=='edit'){
        $.ajax({
            url: "/getcountry",
            method: "GET",
            success: function(response){
                $.each(response, function(index, value) {
                    if(selected!="" && value.id==selected){
                        $('#countryedit').append('<option value="'+value.id+'" selected>'+value.name.toUpperCase()+'</option>');
                    }else{
                        $('#countryedit').append('<option value="'+value.id+'">'+value.name.toUpperCase()+'</option>');
                    }

                  });
            }
        });
    }else{
        $.ajax({
            url: "/getcountry",
            method: "GET",
            success: function(response){
                $.each(response, function(index, value) {
                    $('#country').append('<option value="'+value.id+'">'+value.name.toUpperCase()+'</option>');
                  });
            }
        });
    }

}

(function () {
  // Flat Picker
  // --------------------------------------------------------------------
  const flatpickrDate = document.querySelector('#birthday')

  // Date
  if (flatpickrDate) {
    flatpickrDate.flatpickr({
      //monthSelectorType: 'static',
      dateFormat: 'd-m-yy'
    });
  }
})();

function getdepartamentos(pais, type="", selected){
    //Get countrys avaibles
    if(type=='edit'){
       $.ajax({
           url: "/getdepartment/"+btoa(pais),
           method: "GET",
           success: function(response){
               $('#departamentedit').find('option:not(:first)').remove();
               $.each(response, function(index, value) {
                   if(selected!="" && value.id==selected){
                       $('#departamentedit').append('<option value="'+value.id+'" selected>'+value.name+'</option>');
                   }else{
                       $('#departamentedit').append('<option value="'+value.id+'">'+value.name+'</option>');
                   }

                 });
           }
       });
    }else{
       $.ajax({
           url: "/getdepartment/"+btoa(pais),
           method: "GET",
           success: function(response){
               $('#departament').find('option:not(:first)').remove();
               $.each(response, function(index, value) {
                   $('#departament').append('<option value="'+value.id+'">'+value.name+'</option>');
                 });
           }
       });
    }
   }

   function getmunicipio(dep, type="", selected){
    if(type=='edit'){
//Get countrys avaibles
$.ajax({
    url: "/getmunicipality/"+btoa(dep),
    method: "GET",
    success: function(response){
     $('#municipioedit').find('option:not(:first)').remove();
        $.each(response, function(index, value) {
            if(selected!=="" && value.id==selected){
                $('#municipioedit').append('<option value="'+value.id+'" selected>'+value.name+'</option>');
            }else{
                $('#municipioedit').append('<option value="'+value.id+'">'+value.name+'</option>');
            }

          });
    }
});
    }else{
//Get countrys avaibles
$.ajax({
    url: "/getmunicipality/"+btoa(dep),
    method: "GET",
    success: function(response){
     $('#municipio').find('option:not(:first)').remove();
        $.each(response, function(index, value) {
            $('#municipio').append('<option value="'+value.id+'">'+value.name+'</option>');
          });
    }
});
    }
   }

   function llamarselected(pais, departamento, municipio){
    getpaises(pais,'edit');
    getdepartamentos(pais,'edit', departamento);
    getmunicipio(departamento, 'edit', municipio);
    }

   function editProvider(id){
    //Get data edit providers
    $.ajax({
        url: "getproviderid/"+btoa(id),
        method: "GET",
        success: function(response){
            //console.log(response);
            llamarselected(response[0]['paisid'],response[0]['departamentoid'],response[0]['municipioid']);
            $.each(response[0], function(index, value) {
                    $('#'+index+'update').val(value);
                    if(index=='companyid'){
                        $("#companyupdate option[value='"+ value  +"']").attr("selected", true);
                    }

              });
              $("#updateProviderModal").modal("show");
        }
    });
   }

   function deleteProvider(id){
    const swalWithBootstrapButtons = Swal.mixin({
        customClass: {
          confirmButton: 'btn btn-success',
          cancelButton: 'btn btn-danger'
        },
        buttonsStyling: false
      })

      swalWithBootstrapButtons.fire({
        title: '¿Eliminar?',
        text: "Esta accion no tiene retorno",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Si, Eliminarlo!',
        cancelButtonText: 'No, Cancelar!',
        reverseButtons: true
      }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: "destroy/"+btoa(id),
                method: "GET",
                success: function(response){
                        if(response.res==1){
                            Swal.fire({
                                title: 'Eliminado',
                                icon: 'success',
                                confirmButtonText: 'Ok'
                              }).then((result) => {
                                /* Read more about isConfirmed, isDenied below */
                                if (result.isConfirmed) {
                                  location.reload();
                                }
                              })

                        }else if(response.res==0){
                            swalWithBootstrapButtons.fire(
                                'Problemas!',
                                'Algo sucedio y no pudo eliminar el cliente, favor comunicarse con el administrador.',
                                'success'
                              )
                        }
            }
            });
        } else if (
          /* Read more about handling dismissals below */
          result.dismiss === Swal.DismissReason.cancel
        ) {
          swalWithBootstrapButtons.fire(
            'Cancelado',
            'No hemos hecho ninguna accion :)',
            'error'
          )
        }
      })
   }

