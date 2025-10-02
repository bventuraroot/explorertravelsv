<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Comprobante</title>

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
    </style>

</head>

<body>
<!-- Encabezado y QR -->
    <table width="100%">
        <tr valign="top">
            <td width=45%>
                <table width="100%">
                    <tr>
                        <td>
                            <img src="{{ logo_pdf($emisor[0]['nrc'] ?? ($emisor[0]['ncr'] ?? '')) }}" alt="logo" width="120px" style="display: block; margin: 0 auto; object-fit: contain;">
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size: x-small;">
                            <strong>{{$emisor[0]["nombre"] ?? ''}}</strong>
                        </td>
                    </tr>

                    <tr>
                        <td>NIT:{{$emisor[0]["nit"] ?? ''}}</td>
                    </tr>
                    <tr>
                        <td>NRC:{{$emisor[0]["nrc"] ?? ($emisor[0]["ncr"] ?? '')}}</td>
                    </tr>
                    <tr>
                        <td>Actividad económica:{{$emisor[0]["descActividad"] ?? ''}}</td>
                    </tr>
                    <tr>
                        <td>Dirección: @if(isset($emisor[0]["direccion"]) && is_array($emisor[0]["direccion"]))
                                {{$emisor[0]["direccion"]["complemento"] ?? ''}}
                            @else
                                {{$emisor[0]["direccion"] ?? ''}}
                            @endif<br>
                        {{$MunicipioE}},{{$DepartamentoE}}</td>
                    </tr>
                    <tr>
                        <td>Número de teléfono:{{$emisor[0]["telefono"] ?? ''}}</td>
                    </tr>
                    <tr>
                        <td>Correo electrónico:{{$emisor[0]["correo"] ?? ''}}</td>
                    </tr>
                    <tr>
                        <td>Nombre comercial:{{$emisor[0]["nombreComercial"] ?? ''}}</td>
                    </tr>
                    <tr>
                        <td>Tipo de establecimiento:{{Tipo_Establecimiento($emisor[0]["tipoEstablecimiento"] ?? '')}}
                             - {{$emisor[0]["nombreComercial"] ?? ''}}
                        </td>
                    </tr>

                </table>
            </td>
            <td>
                <table width="100%" style="border:1px solid #000;">
                    <tr style="background-color: lightgray;">
                        <td colspan="3" align="center" style="font-size: x-small;">
                            <strong>DOCUMENTO TRIBUTARIO ELECTRÓNICO</strong><br>
                            <strong>NOTA DE DÉBITO</strong>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Código de Generación:</strong></td>
                        <td colspan="2">{{ $json["codigoGeneracion"] ?? ($json["identificacion"]["codigoGeneracion"] ?? '') }}</td>
                    </tr>
                    <tr>
                        <td><strong>Sello de recepción:</strong></td>
                        <td colspan="2">{{$json["selloRecibido"] ?? 'N/A'}}</td>
                    </tr>
                    <tr>
                        <td><strong>Número de Control:</strong></td>
                        <td colspan="2">{{ $json["identificacion"]["numeroControl"] ?? '' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Modélo facturación:</strong></td>
                        <td>Previo</td>
                        <td><strong>Versión del Json:</strong> {{ $documento[0]["versionjson"] ?? ($documento[0]["versionJson"] ?? ($documento[0]["version"] ?? '')) }}</td>
                    </tr>
                    <tr>
                        <td><strong>Tipo de transmisión</strong></td>
                        <td>Normal</td>
                        <td><strong>Fecha emisión:</strong> {{ isset($json["fhRecibido"]) ? date('d/m/Y', strtotime($json["fhRecibido"])) : (isset($json["identificacion"]["fecEmi"]) ? date('d/m/Y', strtotime($json["identificacion"]["fecEmi"])) : '') }}</td>
                    </tr>
                    <tr>
                        <td><strong>Hora de emisión:</strong></td>
                        <td>{{ isset($json["fhRecibido"]) ? substr($json["fhRecibido"],12,8) : ($json["identificacion"]["horEmi"] ?? '') }}</td>
                        <td><strong>Documento interno No:</strong>{{$documento[0]["actual"] ?? ''}}</td>
                    </tr>
                    <tr>
                        <td colspan="3" align="center">
                            <img src="data:image/png;base64,{{$qr}}" alt="">
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
                        <td colspan="2" >{{$cliente[0]["nombre"] ?? ($json["receptor"]["nombre"] ?? '')}}  </td>
                    </tr>
                    <tr>
                        <td align="right"><strong>Actividad económica:</strong></td>
                        <td width="60%">{{$cliente[0]["descActividad"] ?? ($json["receptor"]["descActividad"] ?? '')}}</td>
                        <td><strong>NIT:</strong> {{$cliente[0]["numDocumento"] ?? ($json["receptor"]["numDocumento"] ?? '')}}</td>
                    </tr>
                    <tr>
                        <td align="right"><strong>Correo electrónico:</strong></td>
                        <td>{{$cliente[0]["correo"] ?? ($json["receptor"]["correo"] ?? '')}}</td>
                        <td><strong>NRC:</strong> {{$cliente[0]["nrc"] ?? ($json["receptor"]["nrc"] ?? '')}}</td>
                    </tr>
                    <tr>
                        <td align="right"><strong>Dirección:</strong></td>
                        <td>
                            @if(isset($cliente[0]["direccion"]) && is_array($cliente[0]["direccion"]))
                                {{$cliente[0]["direccion"]["complemento"] ?? ''}}
                            @else
                                {{$cliente[0]["direccion"] ?? ($json["receptor"]["direccion"]["complemento"] ?? '')}}
                            @endif
                        </td>
                        <td><strong>Teléfono:</strong> {{$cliente[0]["telefono"] ?? ($json["receptor"]["telefono"] ?? '')}}</td>
                    </tr>


                    <tr>
                        <td align="right"><strong>Municipio:</strong></td>
                        <td>{{$MunicipioR}}</td>
                        <td><strong>Forma pago:</strong> @if(($totales['condicionOperacion'] ?? '')=="1")
                            CONTADO
                            @elseif (($totales['condicionOperacion'] ?? '')=="2")
                            CREDITO
                            @elseif (($totales['condicionOperacion'] ?? '')=="3")
                            OTRO
                        @endif</td>
                    </tr>
                    <tr>
                        <td align="right"><strong>Departamento:</strong></td>
                        <td>{{$DepartamentoR}}</td>
                        <td><strong>Moneda:</strong>USD</td>
                    </tr>

                </table>
            </td>
        </tr>

    </table>

<!-- Datos Receptor -->
{{-- Sección de terceros comentada hasta implementar nueva estructura --}}
    <br />

    <table width="100%" style="border-collapse:collapse;">
        <thead style="background-color: lightgray;">
            <tr>
                <th class="cuadro">No</th>
                <th class="cuadro">Cnt</th>

                <th class="cuadro">Descripcion</th>
                <th class="cuadro">Precio<br>Unitario</th>
                <th class="cuadro">Descuento<br>por Item</th>
                <th class="cuadro">Otros montos<br>no afectos</th>
                <th class="cuadro">Ventas No<br>Sujetas</th>
                <th class="cuadro">Ventas<br>Exentas</th>
                <th class="cuadro">Ventas<br>Gravadas</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($detalle as $d)


            <tr>
                <th>{{$loop->index+1}}</th>
                <td>{{$d["cantidad"] ?? 1}}</td>
                <td>{{$d["descripcion"] ?? ''}}</td>
                <td align="right">{{FNumero(($d["precio_unitario"] ?? $d["precioUni"] ?? 0))}}</td>
                <td align="right">{{FNumero($d["montoDescu"] ?? 0)}}</td>
                <td align="right">{{FNumero($d["noGravado"] ?? 0)}}</td>
                <td align="right">{{FNumero(($d["no_sujetas"] ?? $d["ventaNoSuj"] ?? 0))}}</td>
                <td align="right">{{FNumero(($d["exentas"] ?? $d["ventaExenta"] ?? 0))}}</td>
                <td align="right">{{FNumero(($d["gravadas"] ?? $d["ventaGravada"] ?? 0))}}</td>
            </tr>
            @endforeach
        </tbody>


    </table>
    <footer>
        <div class="footer" style="position: absolute; bottom: 0;border-spacing: 0 0;border-collapse:collapse;margin-top:0;">
            <table width="100%" style="border-collapse:collapse;margin-top:0;border-spacing: 0 0;" class="cuadro">
                <tr>
                    <td width="490px">
                        <table width="100%">
                            <tr>
                                <td colspan="2"><strong>Valor en Letras:</strong> {{$totales["totalLetras"] ?? ''}}</td>
                            </tr>
                            <tr>
                                <td colspan="2" align="center" style="background-color: lightgray;"><strong>EXTENSIÓN</strong></td>
                            </tr>
                            <tr>
                                <td width="245px"><strong>Nombre entrega</strong> {{$json["extension"]["nombEntrega"] ?? ''}}</td>
                                <td><strong>No Documento</strong> {{$json["extension"]["docuEntrega"] ?? ''}}</td>
                            </tr>
                            <tr>
                                <td><strong>Nombre recibe</strong> {{$json["extension"]["nombRecibe"] ?? ''}}</td>
                                <td><strong>No Documento</strong> {{$json["extension"]["docuRecibe"] ?? ''}}</td>
                            </tr>
                            <tr>
                                <td colspan="2" align="center" style="background-color: lightgray;"><strong>OBSERVACIONES</strong></td>
                            </tr>
                            <tr>
                                <td width="245px">
                                   <center><strong>Forma de Pago</strong></center>
                                </td>
                                <td></td>
                            </tr>
                            <tr>
                                <td width="245px">
                                <table width="100%">
                                    <tr>
                                        <td align="center"><strong>Credito</strong></td>
                                        <td align="center"><strong>Contado</strong></td>
                                        <td align="center"><strong>Tarjeta</strong></td>
                                    </tr>
                                </table>
                                </td>
                                <td></td>
                            </tr>
                            <tr>
                                <td width="245px">
                                    <table width="100%">
                                        <tr>
                                            <td align="right">{{FNumero(($totales["condicionOperacion"] ?? '') == "02" ? ($totales["totalPagar"] ?? 0) : 0.00)}}</td>
                                            <td align="right">{{FNumero(($totales["condicionOperacion"] ?? '') == "01" ? ($totales["totalPagar"] ?? 0) : 0.00)}}</td>
                                            <td align="right">{{FNumero(($totales["condicionOperacion"] ?? '') == "03" ? ($totales["totalPagar"] ?? 0) : 0.00)}}</td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td>&nbsp;</td>
                            </tr>
                        </table>

                    </td>
                    <td style="border:1px solid #000;" width="230px">
                        <!--- Totales-->
                        <table style="border-spacing: 0 0;">
                            <tr>
                                <td width="80px">Sumas $</td>
                                <td align="right" width="50px" class="sumas">{{FNumero(($totales["totalNoSuj"] ?? $totales["totalNoSuj"] ?? 0))}}</td>
                                <td align="right" width="50px" class="sumas">{{FNumero(($totales["totalExenta"] ?? $totales["totalExenta"] ?? 0))}}</td>
                                <td align="right" width="50px" class="sumas">{{FNumero(($totales["totalGravada"] ?? $totales["totalGravada"] ?? 0))}}</td>

                            </tr>
                            <tr>
                                <td colspan="3" width="160px">Suma total de operaciones</td>
                                <td align="right" class="cuadro-izq">{{FNumero(($totales["subTotalVentas"] ?? $totales["subTotal"] ?? 0))}}</td>

                            </tr>
                            <tr>
                                <td colspan="3">Total descuentos</td>
                                <td align="right" class="cuadro-izq">{{FNumero(0.00)}}</td>

                            </tr>
                            <tr>
                                <td colspan="3" width="160px">Impuestos al Valor Agregado 13%</td>
                                <td align="right" class="cuadro-izq">{{FNumero(($totales["totalIva"] ?? $totales["totalIva"] ?? 0))}}</td>

                            </tr>
                            <tr>
                                <td colspan="3">Sub-Total</td>
                                <td align="right" class="cuadro-izq">{{FNumero(($totales["subTotal"] ?? $totales["subTotal"] ?? 0))}}</td>

                            </tr>
                            <tr>
                                <td colspan="3">IVA Percibido</td>
                                <td align="right" class="cuadro-izq">{{FNumero(($totales["ivaPerci1"] ?? $totales["totalIva"] ?? 0))}}</td>

                            </tr>
                            <tr>
                                <td colspan="3">IVA Retenido</td>
                                <td align="right" class="cuadro-izq">{{FNumero(($totales["ivaRete1"] ?? $totales["ivaRete1"] ?? 0))}}</td>

                            </tr>
                            <tr>
                                <td colspan="3">Monto Total de la operación</td>
                                <td align="right" class="cuadro-izq">{{FNumero(($totales["montoTotalOperacion"] ?? $totales["montoTotalOperacion"] ?? 0))}}</td>

                            </tr>
                            <tr>
                                <td colspan="3">Total otros montos no afectos</td>
                                <td align="right" class="cuadro-izq">{{FNumero(($totales["totalNoGravado"] ?? $totales["totalNoGravado"] ?? 0))}}</td>

                            </tr>
                            <tr>
                                <td colspan="3" ><strong>TOTAL A PAGAR</strong></td>
                                <td align="right" class="cuadro-izq"><strong>{{FNumero(($totales["totalPagar"] ?? $totales["totalPagar"] ?? 0))}}</strong></td>

                            </tr>

                        </table>
                        <!--- Fin Totales-->
                    </td>
                </tr>
                <tr class="cuadro">
                    <td colspan="2" style="font-size:6px;">                    <span style="margin:0;padding=0;"><center>Condiciones generales de los servicios prestados por
                        {{$emisor[0]["nombre"] ?? ''}}</center><br style="margin:0;padding=0;">
                    • {{$emisor[0]["nombre"] ?? ''}}. declara expresamente que actúa como agente representante o distribuidor de
                    los
                    transportistas aéreos, que previamente la han autorizado para vender transporte aéreo de su propiedad, atendiendo a lo
                    estipulado en el Régimen respectivo del Código de Comercio de El Salvador, por ende, se sujeta estrictamente a las
                    instrucciones emanadas por ellos, sin tener injerencia alguna en cuanto al precio de tarifa , políticas de equipaje,
                    horarios de vuelos, entre otras condiciones.
                    • El contrato de transporte Aéreo se celebra entre el consumidor o pasajero y el transportista aéreo, por ende,
                    {{$emisor[0]["nombre"] ?? ''}}, no tiene responsabilidad alguna en casos de muerte o lesiones de los
                    pasajeros, destrucción,
                    perdida o avería de su equipaje, así como por atrasaos, huelgas, terremotos o cualquier otro acontecimiento de fuerza
                    mayor. El contrato se rige por la Ley Orgánica de Aviación Civil y Convenios Internacionales ratificados por el Estado
                    de El Salvador como el Convenio de Montreal y pacto de Varsovia.
                    • El precio cancelado en concepto de boletos aéreos no es reembolsable.
                    • Es obligación del pasajero cumplir los requisitos gubernamentales establecidos para la realización del viaje y
                    disponer de los documentos de salida, entrada, visa, permisos y demás exigencias en El Salvador y/o cualquier otro
                    Estado, así como llegar al aeropuerto a las horas señaladas por el transportista y con la antelación suficiente que le
                    permite completar los tramite de chequeo y salida.
                    • El consumidor declara que previo a la compra de su boleto aéreo o paquete vacacional, personeros de
                    {{$emisor[0]["nombre"] ?? ''}}, explicaron cada una de las condiciones descritas anteriormente, entendiéndolas
                    y aceptándolas, eximiéndola
                    de tal forma de cualquier responsabilidad que se derive de ellas.</span>
                    </td>
                </tr>
            </table>
        </div>
    </footer>
</body>

</html>

