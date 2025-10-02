<!DOCTYPE html>
<html lang="es" xmlns="http://www.w3.org/1999/xhtml" xmlns:o="urn:schemas-microsoft-com:office:office">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <title>Comprobante Electrónico - Explorer Travel SV</title>
    <!--[if mso]>
  <style>
    table {border-collapse:collapse;border-spacing:0;border:none;margin:0;}
    div, td {padding:0;}
    div {margin:0 !important;}
	</style>
  <noscript>
    <xml>
      <o:OfficeDocumentSettings>
        <o:PixelsPerInch>96</o:PixelsPerInch>
      </o:OfficeDocumentSettings>
    </xml>
  </noscript>
  <![endif]-->
    <style>
        table,
        td,
        div,
        h1,
        p {
            font-family: Arial, sans-serif;
        }

        @media screen and (max-width: 530px) {
            .unsub {
                display: block;
                padding: 8px;
                margin-top: 14px;
                border-radius: 6px;
                background-color: #555555;
                text-decoration: none !important;
                font-weight: bold;
            }

            .col-lge {
                max-width: 100% !important;
            }

            .mobile-padding {
                padding: 15px !important;
            }

            .mobile-font {
                font-size: 14px !important;
            }

            .mobile-header {
                font-size: 20px !important;
                padding: 20px 15px !important;
            }
        }

        @media screen and (min-width: 531px) {
            .col-sml {
                max-width: 27% !important;
            }

            .col-lge {
                max-width: 73% !important;
            }
        }

        /* Estilos adicionales para mejor presentación */
        .gradient-bg {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
        }

        .travel-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .info-card {
            background-color: #f8f9fa;
            border-left: 4px solid #28a745;
            border-radius: 8px;
            padding: 25px;
        }

        .details-table {
            width: 100%;
            border-collapse: collapse;
        }

        .details-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .verification-badge {
            background-color: #e3f2fd;
            border: 1px solid #2196f3;
            border-radius: 8px;
            padding: 15px;
        }

        .warning-box {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 15px;
        }

        .travel-icon {
            font-size: 24px;
            margin-right: 10px;
        }
    </style>
</head>

<body style="margin:0;padding:0;word-spacing:normal;background-color:#939297;">
    <div role="article" aria-roledescription="email" lang="es"
        style="text-size-adjust:100%;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;background-color:#939297;">
        <table role="presentation" style="width:100%;border:none;border-spacing:0;">
            <tr>
                <td align="center" style="padding:0;">
                    <!--[if mso]>
          <table role="presentation" align="center" style="width:600px;">
          <tr>
          <td>
          <![endif]-->
                    <table role="presentation"
                        style="width:94%;max-width:600px;border:none;border-spacing:0;text-align:left;font-family:Arial,sans-serif;font-size:16px;line-height:22px;color:#363636;">
                        <tr>
                            <td class="mobile-header" style="padding:40px 30px 20px 30px;text-align:center;font-size:28px;font-weight:bold;background-color:#1e3c72;color:#ffffff;">
                                <div style="display:inline-block;padding:10px 20px;border:2px solid #ffffff;border-radius:8px;">
                                    <span class="travel-icon">✈️</span> Comprobante Electrónico
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="mobile-padding" style="padding:30px;background-color:#ffffff;">
                                <div class="travel-gradient" style="padding:20px;border-radius:10px;margin-bottom:25px;">
                                    <h2 style="margin:0;color:#ffffff;font-size:22px;text-align:center;">
                                        <span class="travel-icon">🎉</span> ¡Gracias por elegir Explorer Travel SV!
                                    </h2>
                                </div>

                                <div class="info-card" style="margin-bottom:20px;">
                                    <h3 style="margin:0 0 15px 0;font-size:18px;color:#1e3c72;font-weight:bold;">
                                        Estimado/a: {{$data["nombre"]}}
                                    </h3>
                                    <p class="mobile-font" style="margin:0;color:#6c757d;font-size:16px;line-height:1.6;">
                                        Adjunto encontrará su documento fiscal electrónico correspondiente a los servicios de viaje adquiridos.
                                        Este documento es válido fiscalmente y ha sido procesado por el sistema de Hacienda de El Salvador.
                                    </p>
                                </div>

                                @if($data["json"])
                                <div style="background-color:#ffffff;border:1px solid #e9ecef;border-radius:8px;padding:20px;margin-bottom:20px;">
                                    <h4 style="margin:0 0 15px 0;color:#495057;font-size:16px;font-weight:bold;border-bottom:2px solid #1e3c72;padding-bottom:8px;">
                                        <span class="travel-icon">📋</span> Detalles del Documento
                                    </h4>

                                    <!-- Debug info (temporal) -->
                                    <div style="background-color:#f8f9fa;padding:10px;margin-bottom:15px;font-size:12px;color:#666;">
                                        <strong>Debug:</strong>
                                        JSON existe: {{ isset($data["json"]) ? 'Sí' : 'No' }} |
                                        Tipo: {{ gettype($data["json"]) }} |
                                        Identificacion existe: {{ isset($data["json"]["identificacion"]) ? 'Sí' : 'No' }} |
                                        Resumen existe: {{ isset($data["json"]["resumen"]) ? 'Sí' : 'No' }}
                                    </div>

                                    <table class="details-table">
                                        <tr>
                                            <td style="padding:8px 0;font-weight:bold;color:#495057;width:40%;">Número de Control:</td>
                                            <td style="padding:8px 0;color:#6c757d;">{{$data["json"]["identificacion"]["numeroControl"] ?? 'No disponible'}}</td>
                                        </tr>
                                        <tr>
                                            <td style="padding:8px 0;font-weight:bold;color:#495057;">Código de Generación:</td>
                                            <td style="padding:8px 0;color:#6c757d;font-family:monospace;font-size:14px;">{{$data["json"]["identificacion"]["codigoGeneracion"] ?? 'No disponible'}}</td>
                                        </tr>
                                        <tr>
                                            <td style="padding:8px 0;font-weight:bold;color:#495057;">Fecha de Emisión:</td>
                                            <td style="padding:8px 0;color:#6c757d;">{{$data["json"]["identificacion"]["fecEmi"] ?? 'No disponible'}}</td>
                                        </tr>
                                        <tr>
                                            <td style="padding:8px 0;font-weight:bold;color:#495057;">Monto Total:</td>
                                            <td style="padding:8px 0;color:#28a745;font-weight:bold;font-size:16px;">{{FNumero($data["json"]["resumen"]["montoTotalOperacion"] ?? 0)}}</td>
                                        </tr>
                                        <tr>
                                            <td style="padding:8px 0;font-weight:bold;color:#495057;">Sello Recibido:</td>
                                            <td style="padding:8px 0;color:#6c757d;font-family:monospace;font-size:12px;word-break:break-all;">{{$data["json"]["selloRecibido"] ?? 'No disponible'}}</td>
                                        </tr>
                                    </table>
                                </div>

                                <div class="verification-badge" style="margin-bottom:20px;">
                                    <p style="margin:0;color:#1976d2;font-size:14px;text-align:center;">
                                        <strong>🔒 Documento Verificado:</strong> Este comprobante ha sido validado y registrado en el sistema de Hacienda
                                    </p>
                                </div>
                                @else
                                <div style="background-color:#ffffff;border:1px solid #e9ecef;border-radius:8px;padding:20px;margin-bottom:20px;">
                                    <h4 style="margin:0 0 15px 0;color:#495057;font-size:16px;font-weight:bold;border-bottom:2px solid #1e3c72;padding-bottom:8px;">
                                        <span class="travel-icon">📋</span> Detalles del Documento
                                    </h4>
                                    <p style="margin:0;color:#6c757d;font-size:16px;line-height:1.6;">
                                        <strong>Número de Documento:</strong> {{$data["numero"]}}
                                    </p>
                                    <p style="margin:10px 0 0 0;color:#6c757d;font-size:14px;">
                                        Este es un comprobante local sin DTE.
                                    </p>
                                </div>
                                @endif

                                <div style="background-color:#f8f9fa;border:1px solid #dee2e6;border-radius:8px;padding:20px;margin-bottom:20px;">
                                    <h4 style="margin:0 0 15px 0;color:#1e3c72;font-size:16px;font-weight:bold;">
                                        <span class="travel-icon">✈️</span> Servicios de Viaje
                                    </h4>
                                    <p style="margin:0;color:#6c757d;font-size:14px;line-height:1.6;">
                                        Explorer Travel SV se especializa en brindar servicios de viaje de calidad,
                                        incluyendo boletos aéreos, alojamiento, traslados y paquetes turísticos completos.
                                    </p>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <td class="mobile-padding" style="padding:20px 30px;">
                                <div class="warning-box" style="text-align:center;">
                                    <p class="mobile-font" style="margin:0;color:#856404;font-size:14px;font-weight:500;">
                                        <strong>⚠️ Importante:</strong> Este correo fue generado automáticamente.
                                        Por favor no responder a este mensaje. Si tiene alguna duda o consulta,
                                        comuníquese directamente con Explorer Travel SV.
                                    </p>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <td style="padding:30px;text-align:center;font-size:12px;background:linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);color:#ecf0f1;">
                                <div style="margin-bottom:15px;">
                                    <p style="margin:0;font-size:16px;font-weight:bold;color:#ffffff;margin-bottom:10px;">
                                        <span class="travel-icon">✈️</span> Explorer Travel SV
                                    </p>
                                    <p style="margin:0;font-size:14px;line-height:20px;color:#bdc3c7;">
                                        Agencia de Viajes • Documentos fiscales procesados y validados por Hacienda
                                    </p>
                                    <p style="margin:10px 0 0 0;font-size:12px;color:#95a5a6;">
                                        📧 dte@explorertravelsv.com | 📞 7746-4638
                                    </p>
                                </div>

                                <div style="border-top:1px solid #34495e;padding-top:15px;margin-top:15px;">
                                    <p style="margin:0;font-size:12px;color:#95a5a6;">
                                        &reg; <a href="https://explorertravelsv.com" style="color:#3498db;text-decoration:none;">Explorer Travel SV</a> &copy; 2025
                                    </p>
                                </div>
                            </td>
                        </tr>
                    </table>
                    <!--[if mso]>
          </td>
          </tr>
          </table>
          <![endif]-->
                </td>
            </tr>
        </table>
    </div>
</body>

</html>
