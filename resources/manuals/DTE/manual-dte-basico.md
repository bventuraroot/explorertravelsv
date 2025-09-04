---
titulo: "Manual Básico de DTE"
modulo: "DTE"
descripcion: "Guía básica para el manejo de Documentos Tributarios Electrónicos"
version: "1.0"
activo: true
orden: 1
icono: "file-invoice"
---

# Manual Básico de DTE

## Introducción

Los Documentos Tributarios Electrónicos (DTE) son documentos que se emiten y reciben electrónicamente, cumpliendo con las normativas fiscales del país.

## Características Principales

### 1. Emisión de DTE
- **Facturas**: Documentos de venta
- **Notas de Crédito**: Correcciones a facturas
- **Notas de Débito**: Cargos adicionales
- **Guías de Despacho**: Documentos de transporte

### 2. Proceso de Emisión

1. **Crear el documento** en el sistema
2. **Completar la información** requerida
3. **Validar los datos** antes de emitir
4. **Firmar digitalmente** el documento
5. **Enviar al SII** (Servicio de Impuestos Internos)

### 3. Estados del DTE

- **Borrador**: Documento en creación
- **Emitido**: Documento firmado y enviado
- **Aceptado**: Documento aceptado por el receptor
- **Rechazado**: Documento rechazado por el receptor

## Configuración Inicial

### Certificados Digitales
1. Obtener certificado digital del SII
2. Instalar en el sistema
3. Configurar la ruta del certificado
4. Probar la firma digital

### Configuración de Empresa
1. Datos de la empresa
2. Actividades económicas
3. Direcciones de envío
4. Configuración de folios

## Solución de Problemas

### Errores Comunes
- **Error de certificado**: Verificar validez y ruta
- **Error de folios**: Verificar disponibilidad de folios
- **Error de conexión**: Verificar conectividad con SII

### Logs del Sistema
- Revisar logs en `/storage/logs/dte.log`
- Verificar estado de envíos
- Consultar respuestas del SII

## Contacto y Soporte

Para soporte técnico contactar al administrador del sistema.
