/**
 * Form Picker
 */

'use strict';
$(document).ready(function (){

    $("#name").on("keyup", function () {
        var valor = $(this).val();
        $(this).val(valor.toUpperCase());
    });

    $("#name-edit").on("keyup", function () {
        var valor = $(this).val();
        $(this).val(valor.toUpperCase());
    });

    //Get providers avaibles
    var iduser = $('#iduser').val();
    $.ajax({
        url: "/provider/getproviders",
        method: "GET",
        success: function(response){
            //console.log(response);
            $('#provider').append('<option value="0">Seleccione</option>');
            $.each(response, function(index, value) {
                $('#provider').append('<option value="'+value.id+'">'+value.razonsocial.toUpperCase()+'</option>');
                $('#provideredit').append('<option value="'+value.id+'">'+value.razonsocial.toUpperCase()+'</option>');
              });
        }
    });
});

   function editproduct(id){
    //Get data edit Products
    $.ajax({
        url: "getproductid/"+btoa(id),
        method: "GET",
        success: function(response){
            console.log(response);
            $.each(response[0], function(index, value) {
                    $('#'+index+'edit').val(value);
                    if(index=='image'){
                        $('#imageview').html("<img src='http://inetv4.test/assets/img/products/"+value+"' alt='image' width='180px'><input type='hidden' name='imageeditoriginal' id='imageeditoriginal'/>");
                        $('#imageeditoriginal').val(value);
                    }
                    if(index=='provider_id'){
                        $("#provideredit option[value='"+ value  +"']").attr("selected", true);
                    }
                    if(index=='cfiscal'){
                        $("#cfiscaledit option[value='"+ value  +"']").attr("selected", true);
                    }
                    if(index=='type'){
                        $("#typeedit option[value='"+ value  +"']").attr("selected", true);
                    }

              });
              $("#updateProductModal").modal("show");
        }
    });
   }

   function deleteproduct(id){
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

