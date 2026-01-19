<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Comprobante</title>

    <style type="text/css">
        * {
            font-family: 'Segoe UI', 'Arial', 'Helvetica', sans-serif;
        }

        body {
            margin: 0;
            padding: 0;
            color: #000000;
        }

        table {
            font-size: 10px;
            border-collapse: collapse;
        }

        tfoot tr td {
            font-weight: bold;
            font-size: 10px;
        }

        .gray {
            background-color: #f5f5f5;
        }

        .cuadro{
            border: 1.5px solid #333333;
            border-spacing: 0 0;
            padding: 6px;
        }
        .cuadro-izq{
        border-left: 0.75px solid #666666;
        border-spacing: 0 0;
        padding: 6px;

        }
        .sumas{
        border-left: 0.75px solid #666666;
        border-bottom: 0.75px solid #666666;
        border-spacing: 0 0;
        margin: 0;
        padding: 6px;

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
    <table width="100%" style="margin-bottom: 10px;">
        <tr valign="top">
            <td width=45% style="padding-right: 10px;">
                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="padding-bottom: 4px;">
                            <img src="{{ logo_pdf($emisor[0]['ncr'] ?? ($emisor[0]['ncr'] ?? '')) }}" alt="logo" width="125px" style="display: block; margin: 0 auto; object-fit: contain;">
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size: 10px; padding-bottom: 2px; font-weight: bold; color: #2c3e50;">
                            {{$emisor[0]["nombre"] ?? ($emisor[0]["nombreComercial"] ?? '')}}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding-bottom: 2px; font-size: 10px; line-height: 1.3;"><strong>Tipo:</strong> Casa Matriz</td>
                    </tr>
                    <tr>
                        <td style="padding-bottom: 2px; font-size: 10px; line-height: 1.3;"><strong>NIT:</strong> {{$emisor[0]["nit"] ?? ''}} | <strong>NRC:</strong> {{$emisor[0]["nrc"] ?? ($emisor[0]["ncr"] ?? '')}}</td>
                    </tr>
                    <tr>
                        <td style="padding-bottom: 2px; font-size: 10px; line-height: 1.3;"><strong>Act. económica:</strong> {{$emisor[0]["descActividad"] ?? ''}}</td>
                    </tr>
                    <tr>
                        <td style="padding-bottom: 2px; font-size: 10px; line-height: 1.3;"><strong>Dirección:</strong>
                            @if(isset($emisor[0]["direccion"]["complemento"]))
                                {{$emisor[0]["direccion"]["complemento"]}}, {{$MunicipioE}}, {{$DepartamentoE}}
                            @else
                                {{$emisor[0]["direccion"] ?? ''}}, {{$MunicipioE}}, {{$DepartamentoE}}
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="padding-bottom: 2px; font-size: 10px; line-height: 1.3;"><strong>Tel:</strong> {{$emisor[0]["telefono"] ?? ''}} | <strong>Correo:</strong> {{$emisor[0]["correo"] ?? ''}}</td>
                    </tr>
                    <tr>
                        <td style="padding-bottom: 2px; font-size: 10px; line-height: 1.3;"><strong>Nombre comercial:</strong> {{$emisor[0]["nombreComercial"] ?? ''}}</td>
                    </tr>

                </table>
            </td>
            <td>
                <table width="100%" style="border: 2px solid #333333;" cellpadding="3" cellspacing="0">
                    <tr style="background-color: #e8e8e8;">
                        <td colspan="3" align="center" style="font-size: 10px; padding: 7px; border-bottom: 2px solid #333333;">
                            <strong style="color: #000000; letter-spacing: 0.3px;">DOCUMENTO TRIBUTARIO ELECTRÓNICO</strong><br>
                            <strong style="color: #000000; font-size: 10px; letter-spacing: 0.3px;">COMPROBANTE DE CRÉDITO FISCAL</strong>
                        </td>
                    </tr>
                    <tr style="background-color: #ffffff;">
                        <td style="padding: 3px; font-size: 10px;"><strong>Código Generación:</strong></td>
                        <td colspan="2" style="padding: 3px; font-size: 10px;">{{ $json["codigoGeneracion"] ?? ($json["identificacion"]["codigoGeneracion"] ?? '') }}</td>
                    </tr>
                    <tr style="background-color: #ffffff;">
                        <td style="padding: 3px; font-size: 10px;"><strong>Sello recepción:</strong></td>
                        <td colspan="2" style="padding: 3px; font-size: 10px;">{{$json["selloRecibido"] ?? 'N/A'}}</td>
                    </tr>
                    <tr style="background-color: #ffffff;">
                        <td style="padding: 3px; font-size: 10px;"><strong>Número Control:</strong></td>
                        <td colspan="2" style="padding: 3px; font-size: 10px;">{{ $json["identificacion"]["numeroControl"] ?? ($json["numeroControl"] ?? '') }}</td>
                    </tr>
                    <tr style="background-color: #ffffff;">
                        <td style="padding: 3px; font-size: 10px;"><strong>Modelo:</strong></td>
                        <td style="padding: 3px; font-size: 10px;">Previo</td>
                        <td style="padding: 3px; font-size: 10px;"><strong>Versión Json:</strong> {{ $documento[0]["versionjson"] ?? ($documento[0]["versionJson"] ?? ($documento[0]["version"] ?? ($json["version"] ?? ''))) }}</td>
                    </tr>
                    <tr style="background-color: #ffffff;">
                        <td style="padding: 3px; font-size: 10px;"><strong>Transmisión:</strong></td>
                        <td style="padding: 3px; font-size: 10px;">Normal</td>
                        <td style="padding: 3px; font-size: 10px;"><strong>Fecha:</strong> {{ isset($json["fhRecibido"]) ? date('d/m/Y', strtotime($json["fhRecibido"])) : (isset($json["identificacion"]["fecEmi"]) ? date('d/m/Y', strtotime($json["identificacion"]["fecEmi"])) : (isset($json["fecEmi"]) ? date('d/m/Y', strtotime($json["fecEmi"])) : '')) }}</td>
                    </tr>
                    <tr style="background-color: #ffffff;">
                        <td style="padding: 3px; font-size: 10px;"><strong>Hora:</strong></td>
                        <td style="padding: 3px; font-size: 10px;">{{ isset($json["fhRecibido"]) ? substr($json["fhRecibido"],12,8) : ($json["identificacion"]["horEmi"] ?? ($json["horEmi"] ?? '')) }}</td>
                        <td style="padding: 3px; font-size: 10px;"><strong>Doc. Interno:</strong> {{$documento[0]["actual"] ?? ''}}</td>
                    </tr>
                    @if(isset($json["estadoHacienda"]))
                    <tr style="background-color: #ffffff;">
                        <td style="padding: 3px; font-size: 10px;"><strong>Estado:</strong></td>
                        <td colspan="2" style="padding: 3px; font-size: 10px;">{{$json["estadoHacienda"]}}</td>
                    </tr>
                    @endif
                    <tr style="background-color: #ffffff;">
                        <td colspan="3" align="center" style="padding: 4px;">
                            <img src="data:image/png;base64,{{$qr}}" alt="Código QR" width="100px" style="max-width: 125px; height: auto;">
                        </td>
                    </tr>

                </table>
            </td>
        </tr>

    </table>

 <!-- Final de Encabezado y QR -->

 <!-- Datos Receptor -->
    <table width="100%" style="border-collapse:collapse; margin-bottom: 12px;">
        <tr valign="top">
            <td width="100%">
                <table width="100%" style="border-top: 2px solid #333333; padding-top: 8px;" cellpadding="3" cellspacing="0">
                    <tr>
                        <td width="50%" valign="top" style="padding: 4px;">
                            <table width="100%" cellpadding="2" cellspacing="0">
                                <tr>
                                    <td width="40%" style="padding: 3px; font-size: 10px; vertical-align: top;"><strong>Nombre:</strong></td>
                                    <td width="60%" style="padding: 3px; font-size: 10px;">{{$cliente[0]["nombre"] ?? ($json["receptor"]["nombre"] ?? '')}}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 3px; font-size: 10px; vertical-align: top;"><strong>Actividad económica:</strong></td>
                                    <td style="padding: 3px; font-size: 10px; line-height: 1.3;">{{$cliente[0]["descActividad"] ?? ($json["receptor"]["descActividad"] ?? '')}}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 3px; font-size: 10px; vertical-align: top;"><strong>Correo electrónico:</strong></td>
                                    <td style="padding: 3px; font-size: 10px;">{{$cliente[0]["correo"] ?? ($json["receptor"]["correo"] ?? '')}}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 3px; font-size: 10px; vertical-align: top;"><strong>Dirección:</strong></td>
                                    <td style="padding: 3px; font-size: 10px;">
                                        @if(isset($cliente[0]["direccion"]) && is_array($cliente[0]["direccion"]))
                                            {{$cliente[0]["direccion"]["complemento"] ?? ''}}, {{$MunicipioR}}, {{$DepartamentoR}}
                                        @else
                                            {{$cliente[0]["direccion"] ?? ($json["receptor"]["direccion"]["complemento"] ?? '')}}, {{$MunicipioR}}, {{$DepartamentoR}}
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </td>
                        <td width="50%" valign="top" style="padding: 4px;">
                            <table width="100%" cellpadding="2" cellspacing="0">
                                <tr>
                                    <td width="40%" style="padding: 3px; font-size: 10px;"><strong>NIT:</strong></td>
                                    <td width="60%" style="padding: 3px; font-size: 10px;">{{$cliente[0]["nit"] ?? ($json["receptor"]["nit"] ?? '')}}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 3px; font-size: 10px;"><strong>NRC:</strong></td>
                                    <td style="padding: 3px; font-size: 10px;">{{$cliente[0]["nrc"] ?? ($cliente[0]["ncr"] ?? ($json["receptor"]["nrc"] ?? ($json["receptor"]["ncr"] ?? '')))}}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 3px; font-size: 10px;"><strong>Teléfono:</strong></td>
                                    <td style="padding: 3px; font-size: 10px;">{{$cliente[0]["telefono"] ?? ($json["receptor"]["telefono"] ?? '')}}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 3px; font-size: 10px;"><strong>Forma pago:</strong></td>
                                    <td style="padding: 3px; font-size: 10px;">@if(($totales['condicionOperacion'] ?? ($json["condicionOperacion"] ?? ''))=="1")
                                        CONTADO
                                        @elseif (($totales['condicionOperacion'] ?? ($json["condicionOperacion"] ?? ''))=="2")
                                        CREDITO
                                        @elseif (($totales['condicionOperacion'] ?? ($json["condicionOperacion"] ?? ''))=="3")
                                        OTRO
                                    @endif</td>
                                </tr>
                                <tr>
                                    <td style="padding: 3px; font-size: 10px;"><strong>Moneda:</strong></td>
                                    <td style="padding: 3px; font-size: 10px;">USD</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

<!-- Datos Receptor -->
@if (!empty($json["ventaTercero"]))
    <table width="100%" style="border-top: 1px solid #ddd; margin-bottom: 15px; padding-top: 10px;" cellpadding="5" cellspacing="0">
        <tr align="center">
            <td colspan="2" style="padding: 5px; background-color: #ddd; border-bottom: 1px solid #ddd;">
                <strong style="font-size: 10px; color: #2c3e50;">VENTA A CUENTA DE TERCEROS</strong>
            </td>
        </tr>
        <tr>
            <td style="padding: 3px; font-size: 10px; width: 20%;"><strong>Nombre, denominación o razón social:&nbsp;</strong> {{$json["ventaTercero"]["nombre"] ?? ''}}</td>
            <td style="padding: 3px; font-size: 10px; width: 20%;"><strong>NIT:&nbsp;</strong> {{$json["ventaTercero"]["nit"] ?? ''}}</td>
        </tr>

    </table>
@endif

    <table width="100%" style="border-collapse:collapse; page-break-after: auto;">
        <thead style="background-color: #e8e8e8;">
            <tr>
                <th class="cuadro" style="font-size: 10px; font-weight: bold; color: #000000; padding: 6px 4px;">No</th>
                <th class="cuadro" style="font-size: 10px; font-weight: bold; color: #000000; padding: 6px 4px;">Cantidad</th>
                <th class="cuadro" style="font-size: 10px; font-weight: bold; color: #000000; padding: 6px 4px;">Descripcion</th>
                <th class="cuadro" style="font-size: 10px; font-weight: bold; color: #000000; padding: 6px 4px;">Precio<br>Unitario</th>
                <th class="cuadro" style="font-size: 10px; font-weight: bold; color: #000000; padding: 6px 4px;">Descuento<br>por Item</th>
                <th class="cuadro" style="font-size: 10px; font-weight: bold; color: #000000; padding: 6px 4px;">Otros montos<br>no afectos</th>
                <th class="cuadro" style="font-size: 10px; font-weight: bold; color: #000000; padding: 6px 4px;">Ventas No<br>Sujetas</th>
                <th class="cuadro" style="font-size: 10px; font-weight: bold; color: #000000; padding: 6px 4px;">Ventas<br>Exentas</th>
                <th class="cuadro" style="font-size: 10px; font-weight: bold; color: #000000; padding: 6px 4px;">Ventas<br>Gravadas</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($detalle as $d)


            <tr style="page-break-inside:avoid;">
                <td style="padding: 6px 4px; text-align: center; font-size: 10px; white-space: nowrap;">{{$loop->index+1}}</td>
                <td style="padding: 6px 4px; text-align: center; font-size: 10px; white-space: nowrap;">{{$d["cantidad"]}}</td>
                <td style="padding: 6px 4px; font-size: 10px; white-space: nowrap; word-break: break-all;">{{$d["descripcion"]}}</td>
                <td align="center" style="padding: 6px 4px; font-size: 10px; white-space: nowrap;">{{FNumero($d["precio_unitario"] ?? $d["precioUni"] ?? 0)}}</td>
                <td align="center" style="padding: 6px 4px; font-size: 10px; white-space: nowrap;">0.00</td>
                <td align="center" style="padding: 6px 4px; font-size: 10px; white-space: nowrap;">{{FNumero($d["no_imponible"] ?? $d["noGravado"] ?? 0)}}</td>
                <td align="center" style="padding: 6px 4px; font-size: 10px; white-space: nowrap;">{{FNumero($d["no_sujetas"] ?? $d["ventaNoSuj"] ?? 0)}}</td>
                <td align="center" style="padding: 6px 4px; font-size: 10px; white-space: nowrap;">{{FNumero($d["exentas"] ?? $d["ventaExenta"] ?? 0)}}</td>
                <td align="center" style="padding: 6px 4px; font-size: 10px; white-space: nowrap;">{{FNumero($d["gravadas"] ?? $d["ventaGravada"] ?? 0)}}</td>
            </tr>

            @if (($loop->index+1) % 37 == 0)
            <tr style='page-break-after: always;'>
                <td align="right" colspan="9">Pasan ......</td>
            </tr>

            @endif
            @endforeach
        </tbody>


    </table>
    <footer>
        <div class="footer" style="position: absolute; bottom: 0;border-spacing: 0 0;border-collapse:collapse;margin-top:0;">
            <table width="100%" style="border-collapse:collapse;margin-top:0;border-spacing: 0 0;" class="cuadro">
                <tr>
                    <td width="460px" style="padding: 1;">
                        <table width="100%" cellpadding="2" cellspacing="0" style="border-spacing: 0;">
                            <tr>
                                <td colspan="3" style="padding: 3px 0; font-size: 10px;"><strong>Valor en Letras:</strong> {{$totales["totalLetras"] ?? ''}}</td>
                            </tr>
                            <tr>
                                <td colspan="2" align="center" style="padding: 3px; background-color: #f5f5f5; font-size: 10px; font-weight: bold;"><strong>EXTENSIÓN</strong></td>
                            </tr>
                            <tr>
                                <td width="245px" style="padding: 2px 0; font-size: 10px;"><strong>Nombre entrega:</strong> {{$json["extension"]["nombEntrega"] ?? ''}}</td>
                                <td style="padding: 2px 0; font-size: 10px;"><strong>No Documento:</strong> {{$json["extension"]["docuEntrega"] ?? ''}}</td>
                            </tr>
                            <tr>
                                <td width="245px" style="padding: 2px 0; font-size: 10px;"><strong>Nombre recibe:</strong> {{$json["extension"]["nombRecibe"] ?? ''}}</td>
                                <td style="padding: 2px 0; font-size: 10px;"><strong>No Documento:</strong> {{$json["extension"]["docuRecibe"] ?? ''}}</td>
                            </tr>
                            <tr>
                                <td colspan="2" align="center" style="padding: 3px; background-color: #f5f5f5; font-size: 10px; font-weight: bold; margin-top: 3px;"><strong>OBSERVACIONES</strong></td>
                            </tr>
                            <tr>
                                <td colspan="2" style="padding: 2px 0;">
                                    <table width="100%" cellpadding="2" cellspacing="0" style="border-spacing: 0;">
                                        <tr>
                                            <td align="center" style="font-size: 10px; font-weight: bold; padding: 2px;"><strong>Forma de Pago</strong></td>
                                        </tr>
                                        <tr>
                                            <td align="center" style="font-size: 10px; padding: 2px;">
                                                <table width="100%" cellpadding="2" cellspacing="0">
                                                    <tr>
                                                        <td align="center" style="font-size: 10px; font-weight: bold; padding: 2px;"><strong>Crédito</strong></td>
                                                        <td align="center" style="font-size: 10px; font-weight: bold; padding: 2px;"><strong>Contado</strong></td>
                                                        <td align="center" style="font-size: 10px; font-weight: bold; padding: 2px;"><strong>Tarjeta</strong></td>
                                                    </tr>
                                                    <tr>
                                                        <td align="center" style="font-size: 10px; padding: 2px;">{{FNumero(($totales["condicionOperacion"] ?? '') == "02" ? ($totales["totalPagar"] ?? 0) : 0.00)}}</td>
                                                        <td align="center" style="font-size: 10px; padding: 2px;">{{FNumero(($totales["condicionOperacion"] ?? '') == "01" ? ($totales["totalPagar"] ?? 0) : 0.00)}}</td>
                                                        <td align="center" style="font-size: 10px; padding: 2px;">{{FNumero(($totales["condicionOperacion"] ?? '') == "03" ? ($totales["totalPagar"] ?? 0) : 0.00)}}</td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
                    <td style="border: 2px solid #333333; padding: 2px; background-color: #f9f9f9;" width="230px">
                        <!--- Totales-->
                        <table width="100%" cellpadding="2" cellspacing="0" style="border-spacing: 0; font-size: 10px;">
                            <tr>
                                <td width="80px" style="padding: 2px;">Sumas $</td>
                                <td align="right" width="50px" class="sumas" style="padding: 2px;">{{FNumero($totales["totalNoSuj"] ?? 0)}}</td>
                                <td align="right" width="50px" class="sumas" style="padding: 2px;">{{FNumero($totales["totalExenta"] ?? 0)}}</td>
                                <td align="right" width="50px" class="sumas" style="padding: 2px;">{{FNumero($totales["totalGravada"] ?? 0)}}</td>
                            </tr>
                            <tr>
                                <td colspan="3" width="160px" style="padding: 2px;">Suma total de operaciones</td>
                                <td align="right" class="cuadro-izq" style="padding: 2px;">{{FNumero($totales["subTotalVentas"] ?? $totales["subTotal"] ?? 0)}}</td>
                            </tr>
                            <tr>
                                <td colspan="3" style="padding: 2px;">Total descuentos</td>
                                <td align="right" class="cuadro-izq" style="padding: 2px;">{{FNumero(0.00)}}</td>
                            </tr>
                            <tr>
                                <td colspan="3" style="padding: 2px;">Impuestos al Valor Agregado 13%</td>
                                <td align="right" class="cuadro-izq" style="padding: 2px;">{{FNumero($totales["tributos"][0]["valor"] ?? $totales["totalIva"] ?? $totales["ivaPerci1"] ?? 0)}}</td>
                            </tr>
                            <tr>
                                <td colspan="3" style="padding: 2px;">Sub-Total</td>
                                <td align="right" class="cuadro-izq" style="padding: 2px;">{{FNumero(($totales["subTotal"] ?? 0)+($totales["tributos"][0]["valor"] ?? $totales["totalIva"] ?? $totales["ivaPerci1"] ?? 0))}}</td>
                            </tr>
                            <tr>
                                <td colspan="3" style="padding: 2px;">IVA Percibido</td>
                                <td align="right" class="cuadro-izq" style="padding: 2px;">{{FNumero($totales["ivaPerci1"] ?? 0)}}</td>
                            </tr>
                            <tr>
                                <td colspan="3" style="padding: 2px;">IVA Retenido</td>
                                <td align="right" class="cuadro-izq" style="padding: 2px;">{{FNumero($totales["ivaRete1"] ?? 0)}}</td>
                            </tr>
                            <tr>
                                <td colspan="3" style="padding: 2px;">Monto Total de la operación</td>
                                <td align="right" class="cuadro-izq" style="padding: 2px;">{{FNumero(($totales["montoTotalOperacion"] ?? 0))}}</td>
                            </tr>
                            <tr>
                                <td colspan="3" style="padding: 2px;">Total otros montos no afectos</td>
                                <td align="right" class="cuadro-izq" style="padding: 2px;">{{FNumero($totales["totalNoGravado"] ?? 0)}}</td>

                            </tr>
                            <tr>
                                <td colspan="3" style="padding: 2px;">Retencion Renta</td>
                                <td align="right" class="cuadro-izq" style="padding: 2px;">{{FNumero($totales["reteRenta"] ?? 0)}}</td>
                            </tr>
                            <tr style="background-color: #333333;">
                                <td colspan="3" style="padding: 4px; color: #ffffff; font-size: 10px;"><strong>TOTAL A PAGAR</strong></td>
                                <td align="right" class="cuadro-izq" style="padding: 4px; color: #ffffff; font-size: 10px; border-left: 1px solid #666666;"><strong>{{FNumero(($totales["totalPagar"] ?? 0)-($totales["reteRenta"] ?? 0))}}</strong></td>
                            </tr>

                        </table>
                        <!--- Fin Totales-->
                    </td>
                </tr>
                <tr class="cuadro">
                    <td colspan="2" style="font-size: 7px; padding: 3px 0; text-align: center;">
                        Condiciones generales de los servicios prestados por {{$emisor[0]["nombre"] ?? ($emisor[0]["nombreComercial"] ?? '')}}
                    </td>
                </tr>
            </table>
        </div>
    </footer>

    <script type="text/php">
        if (isset($pdf)) {
                $x = 530;
                $y = 10;
                $text = "Página {PAGE_NUM} de {PAGE_COUNT}";
                $font = null;
                $size = 8;
                $color = array(0,0,0);
                $word_space = 0.0;  //  default
                $char_space = 0.0;  //  default
                $angle = 0.0;   //  default
                $pdf->page_text($x, $y, $text, $font, $size, $color, $word_space, $char_space, $angle);
            }
        </script>
</body>

</html>
