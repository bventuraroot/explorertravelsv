---
titulo: "Manual Completo del Módulo de Ventas"
modulo: "Ventas"
descripcion: "Guía completa para gestionar ventas, facturación y documentos tributarios electrónicos"
version: "2.0"
activo: true
orden: 1
icono: "dollar-sign"
---

# Manual Completo del Módulo de Ventas

## Introducción

El módulo de ventas de ExplorerTravel es el corazón del sistema, permitiendo gestionar todo el proceso de venta desde la selección del cliente hasta la generación de documentos tributarios electrónicos (DTE) y el envío al SII.

## Acceso al Módulo

1. **Navegación**: Ve al menú principal y selecciona **Ventas**
2. **Vista principal**: Se mostrará la lista de todas las ventas realizadas
3. **Permisos**: Según tu rol, verás todas las ventas o solo las tuyas

## Funcionalidades Principales

### 💰 Gestión de Ventas
- Crear nuevas ventas
- Editar ventas en proceso
- Anular ventas
- Imprimir documentos
- Enviar por correo electrónico

### 📄 Documentos Tributarios
- Facturas electrónicas
- Notas de crédito
- Comprobantes de crédito fiscal
- Envío automático al SII

### 👥 Gestión de Clientes
- Selección de clientes
- Información fiscal
- Historial de compras
- Datos de contacto

### 📊 Control y Reportes
- Seguimiento de ventas
- Estadísticas de ventas
- Reportes por período
- Análisis de rendimiento

## Crear una Nueva Venta

### Paso 1: Iniciar Nueva Venta
1. En la vista de ventas, haz clic en **Nueva Venta**
2. Se abrirá el formulario de venta
3. El sistema generará automáticamente un correlativo

### Paso 2: Seleccionar Cliente
1. **Buscar cliente existente**:
   - Escribe el nombre o documento del cliente
   - Selecciona de la lista de resultados
   
2. **Crear cliente nuevo** (si es necesario):
   - Haz clic en **Nuevo Cliente**
   - Completa la información fiscal
   - Guarda el cliente

### Paso 3: Seleccionar Tipo de Documento
- **Factura**: Para ventas normales con IVA
- **Nota de Crédito**: Para devoluciones o descuentos
- **Comprobante de Crédito Fiscal**: Para ventas exentas

### Paso 4: Agregar Productos

#### Búsqueda de Productos
1. **Por código**: Ingresa el código del producto
2. **Por nombre**: Escribe el nombre del producto
3. **Por categoría**: Filtra por tipo de producto

#### Agregar al Carrito
1. Selecciona el producto de la lista
2. **Verifica el precio** mostrado
3. **Ingresa la cantidad** deseada
4. Haz clic en **Agregar**
5. El producto se agregará al detalle de la venta

#### Modificar Cantidades
- **Aumentar**: Haz clic en el botón **+**
- **Disminuir**: Haz clic en el botón **-**
- **Eliminar**: Haz clic en el botón **🗑️**

### Paso 5: Configurar Forma de Pago
- **Efectivo**: Pago en efectivo
- **Tarjeta**: Pago con tarjeta de crédito/débito
- **Transferencia**: Pago por transferencia bancaria
- **Cheque**: Pago con cheque

### Paso 6: Revisar y Confirmar
1. **Revisa el detalle** de productos
2. **Verifica los cálculos**:
   - Subtotal
   - IVA (13%)
   - Total
3. **Confirma la venta**
4. El sistema generará el documento

## Tipos de Documentos

### 📋 Factura Electrónica
**Uso**: Ventas normales a contribuyentes
**Características**:
- Incluye IVA del 13%
- Se envía automáticamente al SII
- Genera correlativo automático
- Incluye timbre fiscal

**Proceso**:
1. Selecciona **Factura** como tipo de documento
2. Completa la venta normalmente
3. El sistema genera la factura
4. Se envía automáticamente al SII
5. Recibe confirmación de recepción

### 📝 Nota de Crédito
**Uso**: Devoluciones, descuentos o correcciones
**Características**:
- Reduce el monto de facturas anteriores
- Requiere referencia a factura original
- Incluye IVA si corresponde
- Se envía al SII

**Proceso**:
1. Selecciona **Nota de Crédito**
2. Ingresa el número de la factura original
3. Especifica el motivo de la nota
4. Completa los productos a devolver
5. Genera y envía la nota

### 🧾 Comprobante de Crédito Fiscal
**Uso**: Ventas exentas a no contribuyentes
**Características**:
- No incluye IVA
- Para consumidores finales
- Genera correlativo
- No se envía al SII

**Proceso**:
1. Selecciona **CCF** como tipo
2. Completa la venta
3. El sistema genera el comprobante
4. Se puede imprimir directamente

## Cálculos Automáticos

### Estructura de Cálculos
El sistema calcula automáticamente:

#### Subtotal
- Suma de todos los productos (sin IVA)
- Se calcula: `Cantidad × Precio Unitario`

#### IVA (13%)
- Se aplica sobre el subtotal
- Cálculo: `Subtotal × 0.13`

#### Total
- Suma del subtotal + IVA
- Cálculo: `Subtotal + IVA`

### Ejemplo de Cálculo
```
Producto A: 2 unidades × $10.00 = $20.00
Producto B: 1 unidad × $15.00 = $15.00
Subtotal: $35.00
IVA (13%): $4.55
Total: $39.55
```

## Gestión de Clientes

### Información Requerida
- **Nombre completo**: Nombre y apellidos
- **Documento**: DUI, NIT o pasaporte
- **Tipo de persona**: Natural o jurídica
- **Dirección**: Dirección fiscal
- **Teléfono**: Número de contacto
- **Email**: Correo electrónico

### Clientes Contribuyentes
- **Requisitos**: NIT válido
- **Documentos**: Facturas electrónicas
- **Envío**: Automático al SII

### Clientes No Contribuyentes
- **Requisitos**: DUI o pasaporte
- **Documentos**: Comprobantes de crédito fiscal
- **Envío**: No se envía al SII

## Estados de Venta

### 📝 Borrador
- **Descripción**: Venta en proceso de creación
- **Acciones**: Se puede editar y completar
- **Tiempo**: Se mantiene por 5 minutos
- **Reutilización**: Se puede reutilizar si no tiene detalles

### ✅ Completada
- **Descripción**: Venta finalizada y documentada
- **Acciones**: Solo se puede imprimir o anular
- **DTE**: Documento generado y enviado
- **Stock**: Inventario actualizado

### ❌ Anulada
- **Descripción**: Venta cancelada
- **Acciones**: No se puede modificar
- **DTE**: Documento anulado en el SII
- **Stock**: Inventario restaurado

## Imprimir Documentos

### Desde la Lista de Ventas
1. Busca la venta en la lista
2. Haz clic en **Imprimir** (ícono de impresora)
3. Selecciona el tipo de documento
4. Se abrirá la vista previa
5. Imprime o guarda como PDF

### Tipos de Impresión
- **Factura**: Documento completo con timbre
- **Comprobante**: CCF para no contribuyentes
- **Nota de Crédito**: Documento de devolución
- **Resumen**: Lista de productos sin formato fiscal

## Envío por Correo Electrónico

### Configuración
1. **Cliente debe tener email**: Verifica que el cliente tenga email registrado
2. **Configurar SMTP**: El sistema debe tener configuración de correo
3. **Permisos**: Usuario debe tener permisos de envío

### Proceso de Envío
1. Completa la venta
2. Haz clic en **Enviar por Email**
3. El sistema enviará:
   - Factura en PDF
   - Mensaje personalizado
   - Datos de la venta

### Confirmación
- **Éxito**: Mensaje de confirmación
- **Error**: Notificación del problema
- **Reintento**: Opción de volver a enviar

## Integración con DTE

### Documentos Tributarios Electrónicos
El sistema se integra automáticamente con el SII:

#### Proceso Automático
1. **Generación**: Se crea el documento XML
2. **Firma**: Se firma digitalmente
3. **Envío**: Se envía al SII
4. **Respuesta**: Se recibe confirmación
5. **Almacenamiento**: Se guarda la respuesta

#### Estados del DTE
- **Enviado**: Documento enviado al SII
- **Aceptado**: Documento aceptado por el SII
- **Rechazado**: Documento rechazado por el SII
- **Pendiente**: Esperando respuesta del SII

### Solución de Problemas DTE
- **Error de conexión**: Verificar conectividad
- **Certificado expirado**: Renovar certificado
- **Formato incorrecto**: Verificar datos del cliente
- **SII no disponible**: Reintentar más tarde

## Reportes y Estadísticas

### Reportes Disponibles
- **Ventas por período**: Ventas en un rango de fechas
- **Ventas por cliente**: Historial por cliente
- **Ventas por producto**: Productos más vendidos
- **Ventas por vendedor**: Rendimiento por usuario
- **Ventas por forma de pago**: Análisis de pagos

### Exportar Datos
- **Excel**: Formato .xlsx con fórmulas
- **PDF**: Reporte formateado
- **CSV**: Datos para análisis

### Filtros de Reportes
- **Fecha**: Rango de fechas
- **Cliente**: Cliente específico
- **Producto**: Producto específico
- **Vendedor**: Usuario específico
- **Estado**: Estado de la venta

## Permisos y Roles

### Administrador
- **Acceso**: Todas las ventas del sistema
- **Acciones**: Crear, editar, anular, imprimir
- **Reportes**: Acceso completo a reportes
- **Configuración**: Modificar configuraciones

### Vendedor
- **Acceso**: Solo sus propias ventas
- **Acciones**: Crear, editar, imprimir
- **Reportes**: Solo sus estadísticas
- **Limitaciones**: No puede anular ventas de otros

### Cajero
- **Acceso**: Ventas del día
- **Acciones**: Crear, imprimir
- **Limitaciones**: No puede editar ventas anteriores

## Consejos y Mejores Prácticas

### Antes de Vender
- **Verificar cliente**: Confirmar datos fiscales
- **Revisar productos**: Verificar precios y stock
- **Preparar terminal**: Verificar conectividad
- **Tener respaldo**: Plan de contingencia

### Durante la Venta
- **Ser preciso**: Verificar cantidades y precios
- **Explicar al cliente**: Informar sobre el proceso
- **Confirmar datos**: Verificar información del cliente
- **Mantener calma**: En caso de problemas técnicos

### Después de la Venta
- **Imprimir comprobante**: Entregar al cliente
- **Enviar por email**: Si el cliente lo solicita
- **Verificar DTE**: Confirmar envío al SII
- **Actualizar inventario**: Verificar stock

### Gestión de Errores
- **Mantener registro**: Anotar problemas
- **Comunicar**: Informar a supervisores
- **Documentar**: Guardar evidencia
- **Seguimiento**: Verificar resolución

## Solución de Problemas

### Problemas Comunes

#### Error al crear venta
- **Causa**: Cliente sin datos fiscales
- **Solución**: Completar información del cliente

#### Producto no encontrado
- **Causa**: Producto inactivo o sin stock
- **Solución**: Verificar estado del producto

#### Error de DTE
- **Causa**: Problemas de conectividad o certificado
- **Solución**: Verificar configuración DTE

#### Error de impresión
- **Causa**: Impresora no configurada
- **Solución**: Configurar impresora o usar PDF

### Contacto y Soporte
- **Administrador del sistema**: Para problemas técnicos
- **Soporte DTE**: Para problemas con SII
- **Capacitación**: Para dudas sobre el proceso

## Actualizaciones y Versiones

### Versión 2.0 (Actual)
- Integración completa con DTE
- Gestión avanzada de clientes
- Reportes mejorados
- Interfaz optimizada

### Próximas Versiones
- App móvil para ventas
- Integración con POS
- Reportes en tiempo real
- Análisis predictivo

---

*Este manual se actualiza regularmente. Para la versión más reciente, consulta el sistema.*
