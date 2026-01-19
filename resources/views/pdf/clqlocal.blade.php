<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Comprobante de Liquidación</title>

    <style type="text/css">
        * {
            font-family: Verdana, Arial, sans-serif;
        }

        table {
            font-size: xx-small;
        }

        tfoot tr td {
            font-weight: bold;
            font-size: xx-small;
        }

        .gray {
            background-color: lightgray
        }

        .cuadro{
            border:1px solid #000;
            border-spacing: 0 0;
            padding: 0;
        }
        .cuadro-izq{
        border-left:1px solid #000;
        border-spacing: 0 0;

        }
        .sumas{
        border-left:1px solid #000;
        border-bottom:1px solid #000;
        border-spacing: 0 0;
        margin: 0;

        }
        #watermark {
        position: fixed;

        /**
        Set a position in the page for your image
        This should center it vertically
        **/

        bottom: 5cm;
        left: 6.5cm;

        /** Change image dimensions**/
        width: 8cm;
        height: 8cm;

        -webkit-transform: rotate(-45deg);
        -moz-transform: rotate(-45deg);
        -ms-transform: rotate(-45deg);
        -o-transform: rotate(-45deg);
        transform: rotate(-45deg);

        -webkit-transform-origin: 50% 50%;
        -moz-transform-origin: 50% 50%;
        -ms-transform-origin: 50% 50%;
        -o-transform-origin: 50% 50%;
        transform-origin: 50% 50%;

        font-size: 100px;
        width: 250px;

        /** Your watermark should be behind every content**/
        z-index: 1000;
        }
    </style>

</head>

<body>
    @if ($codTransaccion == "02")
    <div id="watermark">
        ANULADO
    </div>
    @endif
<!-- Encabezado y QR -->
    <table width="100%">
        <tr valign="top">
            <td width=45%>
                <table width="100%">
                    <tr>
                        <td>
                            <img src="{{ logo_pdf($emisor[0]['ncr']) }}" alt="logo" width="120px" style="display: block; margin: 0 auto; object-fit: contain;">
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size: x-small;">
                            <strong>{{$emisor[0]["nombreComercial"]}}</strong>
                        </td>
                    </tr>

                    <tr>
                        <td>NIT: {{$emisor[0]["nit"]}}</td>
                    </tr>
                    <tr>
                        <td>NRC: {{$emisor[0]["ncr"]}}</td>
                    </tr>
                    <tr>
                        <td>Actividad económica: {{$emisor[0]["descActividad"]}}</td>
                    </tr>
                    <tr>
                        <td><strong>Dirección:</strong> {{$emisor[0]["direccion"]}}<br>
                            {{get_name_municipio($emisor[0]['municipio'])}}, {{get_name_departamento($emisor[0]['departamento'])}}</td>
                    </tr>
                    <tr>
                        <td>Número de teléfono: {{$emisor[0]["telefono"]}}</td>
                    </tr>
                    <tr>
                        <td>Correo electrónico: {{$emisor[0]["correo"]}}</td>
                    </tr>
                    <tr>
                        <td>Nombre comercial: {{$emisor[0]["nombreComercial"]}}</td>
                    </tr>
                    <tr>
                        <td>Tipo de establecimiento:
                             Casa Matriz
                        </td>
                    </tr>

                </table>
            </td>
            <td>
                <table width="100%" style="border:1px solid #000;">
                    <tr style="background-color: lightgray;">
                        <td colspan="3" align="center" style="font-size: x-small;">
                            <strong>DOCUMENTO TRIBUTARIO ELECTRÓNICO</strong><br>
                            <strong>COMPROBANTE DE LIQUIDACIÓN</strong>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Código de Generación:</strong></td>
                        <td colspan="2">{{@$json["codigoGeneracion"]}}</td>
                    </tr>
                    <tr>
                        <td><strong>Sello de recepción:</strong></td>
                        <td colspan="2">{{@$json["selloRecibido"]}}</td>
                    </tr>
                    <tr>
                        <td><strong>Número de Control:</strong></td>
                        <td colspan="2">{{@$json["identificacion"]["numeroControl"]}}</td>
                    </tr>
                    <tr>
                        <td><strong>Modelo facturación:</strong></td>
                        <td>Previo</td>
                        <td><strong>Versión del Json:</strong> {{@$documento[0]["versionJson"]}}</td>
                    </tr>
                    <tr>
                        <td><strong>Tipo de transmisión</strong></td>
                        <td>Normal</td>
                        <td><strong>Fecha emisión:</strong> {{date('d/m/Y', strtotime(@$json["fhRecibido"]))}}</td>
                    </tr>
                    <tr>
                        <td><strong>Hora de emisión:</strong></td>
                        <td>{{substr(@$json["fhRecibido"],12,8)}}</td>
                        <td><strong>Documento interno No:</strong>{{$documento[0]["id_doc"]}}</td>
                    </tr>
                    <tr>
                        <td><strong>Estado Hacienda:</strong></td>
                        <td colspan="2">{{@$json["estadoHacienda"]}}</td>
                    </tr>
                    <tr>
                        <td colspan="3" align="center">
                            <img src="data:image/png;base64,{{@$qr}}" alt="">
                        </td>
                    </tr>

                </table>
            </td>
        </tr>

    </table>

 <!-- Final de Encabezado y QR -->

 <!-- Datos Receptor -->
    <table width="100%" style="border-collapse:collapse;"">
        <tr valign="top" >

            <td width="480px">
                <table width="100%" style="border-top:1px solid #000;">

                    <tr>
                        <td align="right" width="100px"><strong>Nombre:</strong></td>
                        <td colspan="2" >{{$cliente[0]["nombre"]}}  </td>
                    </tr>
                    <tr>
                        <td align="right"><strong>Actividad económica:</strong></td>
                        <td width="60%">{{$cliente[0]["descActividad"]}}</td>
                        <td><strong>NIT:</strong> {{$cliente[0]["nit"]}}</td>
                    </tr>
                    <tr>
                        <td align="right"><strong>Correo electrónico:</strong></td>
                        <td>{{$cliente[0]["correo"]}}</td>
                        <td><strong>NRC:</strong> {{$cliente[0]["ncr"]}}</td>
                    </tr>
                    <tr>
                        <td align="right"><strong>Dirección:</strong></td>
                        <td>{{$cliente[0]["direccion"]}}</td>
                        <td><strong>Teléfono:</strong> {{$cliente[0]["telefono"]}}</td>
                    </tr>


                    <tr>
                        <td align="right"><strong>Municipio:</strong></td>
                        <td>{{get_name_municipio($cliente[0]['municipio'])}}</td>
                        <td><strong>Forma pago:</strong> @if($totales['condicionOperacion']=="1")
                            CONTADO
                            @elseif ($totales['condicionOperacion']=="2")
                            CREDITO
                            @elseif ($totales['condicionOperacion']=="3")
                            OTRO
                        @endif</td>
                    </tr>
                    <tr>
                        <td align="right"><strong>Departamento:</strong></td>
                        <td>{{get_name_departamento($cliente[0]['departamento'])}}</td>
                        <td><strong>Moneda:</strong>USD</td>
                    </tr>

                </table>
            </td>
        </tr>

    </table>

<!-- Datos Receptor -->

<!-- Cuerpo de detalle -->
    <br>
    <table width="100%" style="border:1px solid #000;">
        <thead style="background-color: lightgray;">
            <tr style="font-size: xx-small;">
                <th width="40px" class="cuadro">CANT</th>
                <th width="90px" class="cuadro">CODIGO</th>
                <th width="200px" class="cuadro" align="left">DESCRIPCIÓN</th>
                <th width="60px"  class="cuadro">PRECIO UNITARIO</th>
                <th width="60px"  class="cuadro">VENTAS NO SUJETAS</th>
                <th width="60px"  class="cuadro">VENTAS EXENTAS</th>
                <th width="60px"  class="cuadro">VENTAS GRAVADAS</th>
                <th width="60px"  class="cuadro">MONTO IVA</th>
                <th width="60px"  class="cuadro">VENTAS TOTAL</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($comprobante as $key => $item)
                @if($key=='0')
            <tr style="font-size: xx-small;">
                <td align="center" class="cuadro-izq">{{$item["cantidad"]}}</td>
                <td align="left" class="cuadro-izq">{{$item["codigo"]}}</td>
                <td align="left" class="cuadro-izq">{{$item["descripcion"]}}</td>
                <td align="right" class="cuadro-izq">{{number_format($item["precioUni"],4)}}</td>
                <td align="right" class="cuadro-izq">{{number_format($item["noSujeta"],2)}}</td>
                <td align="right" class="cuadro-izq">{{number_format($item["ventaExenta"],2)}}</td>
                <td align="right" class="cuadro-izq">{{number_format($item["ventaGravada"],2)}}</td>
                <td align="right" class="cuadro-izq">{{number_format(round((($item["ventaGravada"]*13)/100),2),2)}}</td>
                <td align="right" class="cuadro-izq">{{number_format($item["montoDescu"]<>"0.00" ? $item["montoDescu"]:($item["precioUni"]*$item["cantidad"]),2)}}</td>
            </tr>
                @endif
            @endforeach
        </tbody>
        <tfoot>
            <tr style="font-size: xx-small;">
                <td colspan="4" class="sumas"><strong>SUMAS</strong></td>
                <td align="right" class="sumas"><strong>{{number_format($totales['totalNoSuj'],2)}}</strong></td>
                <td align="right" class="sumas"><strong>{{number_format($totales['totalExenta'],2)}}</strong></td>
                <td align="right" class="sumas"><strong>{{number_format($totales['totalGravada'],2)}}</strong></td>
                <td align="right" class="sumas"><strong>{{number_format(round((($totales['totalGravada']*13)/100),2),2)}}</strong></td>
                <td align="right" class="sumas"><strong>{{number_format($totales['subTotal'],2)}}</strong></td>
            </tr>
            <tr style="font-size: xx-small;">
                <td colspan="7" rowspan="8" class="cuadro-izq" ><strong>OBSERVACIONES:</strong></td>
                <td align="left" class="sumas"><strong>SUB TOTAL</strong></td>
                <td align="right" class="sumas">{{number_format($totales['subTotal'],2)}}</td>
            </tr>
            <tr style="font-size: xx-small;">
                <td align="left" class="sumas"><strong>IVA RETENIDO</strong></td>
                <td align="right" class="sumas">{{number_format($totales['ivaRete1'],2)}}</td>
            </tr>
            <tr style="font-size: xx-small;">
                <td align="left" class="sumas"><strong>RETENCION RENTA</strong></td>
                <td align="right" class="sumas">{{number_format($totales['reteRenta'],2)}}</td>
            </tr>
            <tr style="font-size: xx-small;">
                <td align="left" class="sumas"><strong>MONTO SUJETO PERCEPCION</strong></td>
                <td align="right" class="sumas">{{number_format($totales['montoTotalOperacion'],2)}}</td>
            </tr>
            <tr style="font-size: xx-small;">
                <td align="left" class="sumas"><strong>IVA PERCIBIDO</strong></td>
                <td align="right" class="sumas">{{number_format($totales['ivaPerci1'],2)}}</td>
            </tr>
            <tr style="font-size: xx-small;">
                <td align="left" class="sumas"><strong>MONTO TOTAL OPERACIÓN</strong></td>
                <td align="right" class="sumas">{{number_format($totales['montoTotalOperacion'],2)}}</td>
            </tr>
            <tr style="font-size: xx-small;">
                <td align="left" class="sumas"><strong>TOTAL NO SUJETO</strong></td>
                <td align="right" class="sumas">{{number_format($totales['totalNoSuj'],2)}}</td>
            </tr>
            <tr style="font-size: xx-small;">
                <td align="left" class="cuadro-izq" style="border-bottom:1px solid #000;"><strong>TOTAL A PAGAR</strong></td>
                <td align="right" class="cuadro-izq" style="border-bottom:1px solid #000;">{{number_format($totales['totalPagar'],2)}}</td>
            </tr>
        </tfoot>
    </table>
<!-- Fin Cuerpo de detalle -->
<br>
<table width="100%">
    <tr>
        <td align="center" style="font-size: xx-small;">
            @if (!empty(@$json["observaciones"]))
                <strong>OBSERVACIONES: </strong>{{@$json["observaciones"]}}
            @else
                <strong>OBSERVACIONES: </strong> N/A
            @endif
        </td>
    </tr>
</table>
<br>
<table width="100%">
    <tr>
        <td width="33%" style="font-size: xx-small; text-align: center; vertical-align: bottom;">
            _______________________________<br>
            <strong>ENTREGADO POR</strong>
        </td>
        <td width="33%" style="font-size: xx-small; text-align: center; vertical-align: bottom;">
            _______________________________<br>
            <strong>RECIBIDO POR</strong>
        </td>
        <td width="33%" style="font-size: xx-small; text-align: center; vertical-align: bottom;">
            _______________________________<br>
            <strong>SELLO</strong>
        </td>
    </tr>
</table>

</body>

</html>
