---
titulo: "Flujo Completo de DTE con Manejo de Errores y Correos"
modulo: "Facturación Electrónica"
descripcion: "Guía completa del sistema de DTE mejorado con manejo de errores y envío automático de correos"
version: "2.0"
activo: true
orden: 1
icono: "file-invoice"
---

# Flujo Completo de DTE con Manejo de Errores y Correos

## 🚀 Introducción

Este manual describe el sistema completo de Documentos Tributarios Electrónicos (DTE) implementado en ExplorerTravelSV, que incluye:

- ✅ **Procesamiento robusto de cola DTE**
- ✅ **Manejo avanzado de errores**
- ✅ **Envío automático de correos**
- ✅ **Sistema de contingencias automáticas**
- ✅ **Reintentos inteligentes**
- ✅ **Monitoreo y estadísticas**

## 📋 Componentes del Sistema

### 1. **Modelo DteError**
- Registra todos los errores que ocurren durante el procesamiento
- Clasifica errores por tipo (validación, red, autenticación, etc.)
- Maneja reintentos automáticos con backoff exponencial
- Proporciona estadísticas detalladas

### 2. **Servicio ElectronicInvoiceErrorHandler**
- Clasifica automáticamente los errores
- Determina la severidad y acciones a tomar
- Crea contingencias automáticas cuando es necesario
- Programa reintentos inteligentes

### 3. **Servicio DteService Mejorado**
- Procesa la cola de DTE de forma robusta
- Integra manejo de errores y envío de correos
- Proporciona estadísticas detalladas

### 4. **Sistema de Correos Automáticos**
- Envía correos automáticamente después de DTE exitoso
- Solo en ambiente de producción (01)
- Incluye PDF y JSON del comprobante

## 🔧 Configuración Inicial

### 1. **Ejecutar Migraciones**
```bash
php artisan migrate
```

### 2. **Configurar Logging**
Agregar al archivo `config/logging.php`:
```php
'electronic_invoice' => [
    'driver' => 'daily',
    'path' => storage_path('logs/electronic-invoice.log'),
    'level' => 'debug',
    'days' => 14,
],
```

### 3. **Configurar Correos**
Verificar configuración en `.env`:
```env
MAIL_MAILER=smtp
MAIL_HOST=tu-servidor-smtp
MAIL_PORT=587
MAIL_USERNAME=tu-email
MAIL_PASSWORD=tu-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@tu-dominio.com
MAIL_FROM_NAME="ExplorerTravel DTE"
```

## 📊 Uso del Sistema

### 1. **Procesamiento Manual de Cola**
```bash
# Procesar 10 DTE (por defecto)
php artisan dte:procesar-cola

# Procesar 25 DTE con información detallada
php artisan dte:procesar-cola --limite=25 --verbose
```

### 2. **Procesamiento Automático**
Configurar cron job para procesamiento automático:
```bash
# Cada 5 minutos
*/5 * * * * cd /path/to/project && php artisan dte:procesar-cola >> /dev/null 2>&1
```

### 3. **Monitoreo de Errores**
```php
use App\Services\ElectronicInvoiceErrorHandler;

$errorHandler = new ElectronicInvoiceErrorHandler();

// Obtener estadísticas de errores
$stats = $errorHandler->getErrorStats($empresaId, 7); // últimos 7 días

// Obtener documentos urgentes
$urgentes = $errorHandler->getUrgentDocuments($empresaId);
```

## 🚨 Tipos de Errores

### **Errores de Red (network)**
- **Causa**: Problemas de conectividad
- **Acción**: Reintento automático
- **Máximo intentos**: 5

### **Errores de Autenticación (autenticacion)**
- **Causa**: Credenciales inválidas
- **Acción**: Revisión manual inmediata
- **Máximo intentos**: 1

### **Errores de Firma (firma)**
- **Causa**: Problemas con el firmador
- **Acción**: Revisión manual inmediata
- **Máximo intentos**: 1

### **Errores de Hacienda (hacienda)**
- **Causa**: Rechazo del servidor de Hacienda
- **Acción**: Reintento automático
- **Máximo intentos**: 2

### **Errores de Validación (validacion)**
- **Causa**: Datos inválidos en el DTE
- **Acción**: Revisión de datos
- **Máximo intentos**: 3

### **Errores de Sistema (sistema)**
- **Causa**: Errores internos del sistema
- **Acción**: Reintento automático
- **Máximo intentos**: 3

## 📧 Sistema de Correos

### **Envío Automático**
- Se activa automáticamente cuando un DTE se procesa exitosamente
- Solo funciona en ambiente de producción (ambiente_id = '01')
- Requiere que el cliente tenga email registrado

### **Contenido del Correo**
- Saludo personalizado con nombre del cliente
- Detalles del documento (número de control, código de generación, etc.)
- Monto total de la operación
- Sello recibido de Hacienda
- Información de verificación

### **Archivos Adjuntos**
- PDF del comprobante (si está disponible)
- JSON del DTE para verificación

## 🔄 Sistema de Reintentos

### **Backoff Exponencial**
Los reintentos siguen un patrón de backoff exponencial:
- 1er intento: 5 minutos
- 2do intento: 10 minutos
- 3er intento: 20 minutos
- 4to intento: 40 minutos
- 5to intento: 80 minutos
- Máximo: 24 horas

### **Condiciones para Reintento**
- DTE en estado '01' (En Cola)
- Menos de 3 intentos realizados
- No asignado a contingencia
- Tiempo de espera cumplido

## 🚨 Sistema de Contingencias

### **Contingencias Automáticas**
Se crean automáticamente cuando:
- Un DTE ha fallado 3 veces o más
- Hay múltiples errores de conectividad
- Se detectan problemas con el servidor de Hacienda

### **Tipos de Contingencias**
- **Técnica**: Problemas de infraestructura
- **Operacional**: Problemas de proceso
- **Sistema**: Fallas del sistema

## 📊 Estadísticas y Monitoreo

### **Estadísticas de DTE**
```php
$estadisticas = $dteService->obtenerEstadisticas($empresaId);

// Retorna:
[
    'total' => 1000,
    'en_cola' => 50,
    'enviados' => 800,
    'rechazados' => 100,
    'en_revision' => 50,
    'porcentaje_exito' => 80.0,
    'pendientes_reintento' => 25,
    'necesitan_contingencia' => 10
]
```

### **Estadísticas de Errores**
```php
$estadisticasErrores = $dteService->obtenerEstadisticasErrores($empresaId);

// Retorna:
[
    'total_errores' => 150,
    'no_resueltos' => 25,
    'criticos' => 5,
    'por_tipo' => [
        'network' => 50,
        'validacion' => 30,
        'hacienda' => 20,
        // ...
    ],
    'porcentaje_resueltos' => 83.33
]
```

## 🔍 Resolución de Problemas

### **Problemas Comunes**

#### 1. **Correos no se envían**
- ✅ Verificar configuración SMTP en `.env`
- ✅ Confirmar que el cliente tiene email
- ✅ Verificar que es ambiente de producción
- ✅ Revisar logs en `storage/logs/laravel.log`

#### 2. **Errores de conectividad**
- ✅ Verificar conectividad a internet
- ✅ Confirmar URLs de Hacienda
- ✅ Verificar configuración del firmador
- ✅ Revisar logs de `electronic-invoice`

#### 3. **DTE rechazados por validación**
- ✅ Verificar datos del cliente (NIT, nombre, etc.)
- ✅ Confirmar códigos de productos
- ✅ Validar montos y cálculos
- ✅ Revisar formato del JSON

#### 4. **Contingencias frecuentes**
- ✅ Verificar estado del servidor de Hacienda
- ✅ Confirmar certificados digitales
- ✅ Revisar configuración de la empresa
- ✅ Analizar patrones de errores

### **Comandos de Diagnóstico**

```bash
# Ver logs de DTE
tail -f storage/logs/electronic-invoice.log

# Ver logs generales
tail -f storage/logs/laravel.log

# Probar conectividad del firmador
php artisan firmador:test

# Verificar configuración de correos
php artisan tinker
>>> Mail::raw('Test', function($msg) { $msg->to('test@example.com')->subject('Test'); });
```

## 📈 Mejores Prácticas

### **1. Monitoreo Proactivo**
- Revisar estadísticas diariamente
- Configurar alertas para errores críticos
- Monitorear porcentaje de éxito

### **2. Mantenimiento Preventivo**
- Renovar certificados antes de expirar
- Mantener URLs de Hacienda actualizadas
- Limpiar logs antiguos regularmente

### **3. Resolución Rápida**
- Atender errores críticos inmediatamente
- Revisar documentos en estado de revisión
- Procesar contingencias activas

### **4. Optimización**
- Ajustar límites de procesamiento según capacidad
- Optimizar consultas de base de datos
- Usar cache para estadísticas frecuentes

## 🔧 Configuración Avanzada

### **Variables de Entorno**
```env
# Configuración DTE
DTE_FIRMADOR_URL=http://147.93.176.3:8113/firmardocumento/
DTE_HACIENDA_URL=https://api.dtes.mh.gob.sv/fesv/recepciondte

# Configuración de reintentos
DTE_MAX_REINTENTOS=3
DTE_DELAY_REINTENTO_MIN=5

# Configuración de alertas
DTE_ALERT_THRESHOLD=10
DTE_CRITICAL_ERRORS_EMAIL=admin@empresa.com
```

### **Personalización de Correos**
Para personalizar el template de correo, editar:
`resources/views/emails/comprobante_electronico.blade.php`

### **Configuración de Logs**
Para cambiar el nivel de logging:
```php
// En config/logging.php
'electronic_invoice' => [
    'level' => 'info', // debug, info, warning, error, critical
],
```

## 📞 Soporte

Para problemas técnicos o consultas:
- 📧 Email: soporte@explorertravel.com
- 📱 Teléfono: +503 XXXX-XXXX
- 🌐 Portal: https://soporte.explorertravel.com

---

**Versión**: 2.0  
**Última actualización**: Septiembre 2025  
**Autor**: Sistema ExplorerTravelSV
