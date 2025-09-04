---
titulo: "Proceso Detallado de DTE (Documentos Tributarios Electrónicos)"
modulo: "Ventas"
descripcion: "Guía detallada para el manejo de documentos tributarios electrónicos y envío al SII"
version: "1.0"
activo: true
orden: 2
icono: "file-invoice"
---

# Proceso Detallado de DTE (Documentos Tributarios Electrónicos)

## Introducción

Los Documentos Tributarios Electrónicos (DTE) son documentos que se emiten y reciben electrónicamente, cumpliendo con las normativas fiscales de El Salvador. Este manual te guiará a través del proceso completo de DTE en ExplorerTravel.

## ¿Qué es un DTE?

### Definición
Un DTE es un documento fiscal que se genera, transmite, recibe y almacena electrónicamente, con la misma validez legal que un documento físico.

### Tipos de DTE
- **Factura Electrónica**: Para ventas con IVA
- **Nota de Crédito**: Para devoluciones o descuentos
- **Nota de Débito**: Para cargos adicionales
- **Comprobante de Crédito Fiscal**: Para ventas exentas

## Configuración Inicial

### Requisitos Previos
1. **Certificado Digital**: Certificado válido del SII
2. **Configuración de Empresa**: Datos fiscales completos
3. **Conectividad**: Conexión a internet estable
4. **Permisos**: Usuario con permisos de DTE

### Configuración del Certificado
1. **Obtener certificado**: Descargar del portal del SII
2. **Instalar certificado**: En el sistema
3. **Configurar ruta**: Especificar ubicación del certificado
4. **Probar firma**: Verificar funcionamiento

### Configuración de Empresa
1. **Datos básicos**:
   - Nombre comercial
   - NIT
   - Dirección fiscal
   - Actividad económica

2. **Configuración DTE**:
   - Ambiente (pruebas/producción)
   - Tipo de emisor
   - Configuración de folios

## Proceso de Emisión

### Paso 1: Crear la Venta
1. **Seleccionar cliente**: Cliente con NIT válido
2. **Agregar productos**: Productos con códigos fiscales
3. **Configurar documento**: Tipo de DTE apropiado
4. **Completar venta**: Finalizar la transacción

### Paso 2: Generación del DTE
El sistema automáticamente:
1. **Valida datos**: Verifica información fiscal
2. **Genera XML**: Crea el documento XML
3. **Firma digitalmente**: Usa el certificado
4. **Valida formato**: Verifica estructura

### Paso 3: Envío al SII
1. **Conecta con SII**: Establece conexión segura
2. **Envía documento**: Transmite el XML
3. **Recibe respuesta**: Obtiene confirmación
4. **Procesa resultado**: Interpreta la respuesta

## Estados del DTE

### 📤 Enviado
- **Descripción**: Documento enviado al SII
- **Acción**: Esperando respuesta
- **Tiempo**: Normalmente 1-5 minutos

### ✅ Aceptado
- **Descripción**: Documento aceptado por el SII
- **Acción**: DTE válido y procesado
- **Resultado**: Documento fiscal válido

### ❌ Rechazado
- **Descripción**: Documento rechazado por el SII
- **Acción**: Revisar errores y corregir
- **Resultado**: DTE no válido

### ⏳ Pendiente
- **Descripción**: Esperando respuesta del SII
- **Acción**: Reintentar envío
- **Tiempo**: Puede tardar hasta 24 horas

## Códigos de Respuesta del SII

### Códigos de Éxito (200-299)
- **200**: Documento aceptado
- **201**: Documento procesado
- **202**: Documento en cola

### Códigos de Error (400-499)
- **400**: Solicitud malformada
- **401**: No autorizado
- **403**: Prohibido
- **404**: No encontrado
- **422**: Entidad no procesable

### Códigos de Error del Servidor (500-599)
- **500**: Error interno del servidor
- **502**: Bad Gateway
- **503**: Servicio no disponible
- **504**: Timeout

## Solución de Problemas

### Error: Certificado Expirado
**Síntomas**:
- Error al firmar documento
- Mensaje de certificado inválido

**Solución**:
1. Verificar fecha de expiración
2. Renovar certificado en el SII
3. Instalar nuevo certificado
4. Probar firma nuevamente

### Error: Cliente sin NIT
**Síntomas**:
- No se puede generar DTE
- Error de validación

**Solución**:
1. Verificar NIT del cliente
2. Completar información fiscal
3. Validar formato del NIT
4. Reintentar generación

### Error: Producto sin Código Fiscal
**Síntomas**:
- Error en validación de productos
- DTE rechazado

**Solución**:
1. Asignar código fiscal al producto
2. Verificar códigos válidos
3. Actualizar catálogo de productos
4. Regenerar DTE

### Error: Conectividad
**Síntomas**:
- Timeout en envío
- Error de conexión

**Solución**:
1. Verificar conexión a internet
2. Reintentar envío
3. Verificar firewall
4. Contactar soporte técnico

## Reenvío de Documentos

### Cuándo Reenviar
- **Timeout**: Cuando el envío se agota
- **Error de red**: Problemas de conectividad
- **SII no disponible**: Servicio temporalmente fuera
- **Respuesta pendiente**: Después de 24 horas

### Proceso de Reenvío
1. **Identificar documento**: Localizar DTE pendiente
2. **Verificar estado**: Confirmar que necesita reenvío
3. **Reintentar envío**: Usar función de reenvío
4. **Monitorear resultado**: Verificar nueva respuesta

### Límites de Reenvío
- **Máximo 3 intentos**: Por documento
- **Intervalo**: 5 minutos entre intentos
- **Tiempo límite**: 24 horas desde creación

## Consulta de Documentos

### Consultar por Número
1. **Acceder a consulta**: Ir a módulo de DTE
2. **Ingresar número**: Número de correlativo
3. **Ejecutar consulta**: Buscar en el SII
4. **Ver resultado**: Estado actual del documento

### Consultar por Cliente
1. **Seleccionar cliente**: De la lista
2. **Ver documentos**: DTE emitidos
3. **Filtrar por fecha**: Rango específico
4. **Exportar lista**: Para análisis

### Consultar por Estado
1. **Filtrar por estado**: Aceptado, rechazado, pendiente
2. **Ver detalles**: Información completa
3. **Acciones disponibles**: Reenviar, imprimir, etc.

## Contingencias

### ¿Qué es una Contingencia?
Una contingencia es un documento que se envía al SII cuando hay problemas técnicos que impiden el envío normal del DTE.

### Cuándo Usar Contingencias
- **SII no disponible**: Servicio fuera de línea
- **Problemas de red**: Conectividad intermitente
- **Certificado temporal**: Problemas con firma digital
- **Volumen alto**: Sobrecarga del sistema

### Proceso de Contingencia
1. **Identificar problema**: Confirmar que es contingencia
2. **Generar contingencia**: Crear documento de contingencia
3. **Enviar al SII**: Transmitir documento
4. **Seguimiento**: Monitorear resolución

## Reportes DTE

### Reportes Disponibles
- **DTE por período**: Documentos emitidos en rango de fechas
- **DTE por estado**: Clasificación por estado
- **DTE rechazados**: Documentos con errores
- **Estadísticas de envío**: Tiempos y éxito de envío

### Exportar Datos
- **Excel**: Para análisis detallado
- **PDF**: Para reportes formales
- **XML**: Para integración con otros sistemas

## Mejores Prácticas

### Antes de Emitir
- **Verificar certificado**: Confirmar validez
- **Validar cliente**: NIT y datos correctos
- **Revisar productos**: Códigos fiscales asignados
- **Probar conectividad**: Verificar conexión

### Durante la Emisión
- **Monitorear proceso**: Seguir el envío
- **Documentar errores**: Anotar problemas
- **Mantener respaldo**: Guardar copias
- **Comunicar problemas**: Informar a supervisores

### Después de Emitir
- **Verificar estado**: Confirmar aceptación
- **Imprimir comprobante**: Para el cliente
- **Archivar documento**: Guardar evidencia
- **Actualizar registros**: Mantener información actualizada

## Configuración Avanzada

### Ambientes
- **Pruebas**: Para desarrollo y testing
- **Producción**: Para operación real

### Configuración de Folios
- **Rango de folios**: Números asignados
- **Control de secuencia**: Numeración automática
- **Reserva de folios**: Para contingencia

### Configuración de Timeouts
- **Tiempo de envío**: Límite para transmisión
- **Reintentos**: Número de intentos
- **Intervalos**: Tiempo entre reintentos

## Contacto y Soporte

### Soporte Técnico
- **Administrador del sistema**: Para problemas técnicos
- **Soporte DTE**: Para problemas específicos de DTE
- **SII**: Para problemas del servicio oficial

### Recursos Adicionales
- **Manual del SII**: Documentación oficial
- **Portal del contribuyente**: Información actualizada
- **Capacitaciones**: Cursos especializados

---

*Este manual se actualiza según las normativas del SII. Para información más reciente, consulta el portal oficial.*
