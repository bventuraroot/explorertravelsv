# Módulo de Prueba del Firmador

## 🔍 Descripción

Este módulo te permite diagnosticar problemas de conectividad con el servicio de firma de documentos usando las URLs configuradas en la base de datos (tabla `ambientes`) desde tu servidor de cPanel.

## 🚀 Instalación

### 1. **Crear permisos**
```bash
php artisan firmador:crear-permisos --rol=admin
```

### 2. **Acceder al módulo**
- Ir a **Administracion DTE** → **Prueba de Conectividad Firmador**

## 🛠️ Funcionalidades

### **1. Información del Servidor y Ambientes**
- Muestra información técnica del servidor
- Versión de PHP, Laravel, cURL
- Configuración de `allow_url_fopen`
- Tiempo máximo de ejecución
- **Nuevo**: Lista de ambientes disponibles con sus URLs del firmador

### **2. Prueba de Conexión Básica**
- Prueba la conectividad HTTP al firmador
- Configurable timeout (5-120 segundos)
- Muestra tiempo de respuesta
- Detecta errores de conexión

### **3. Prueba de Firma**
- Simula el proceso completo de firma
- Envía datos de prueba al firmador
- Verifica respuesta del servicio
- Útil para detectar problemas de autenticación

### **4. Diagnóstico de Red**
- **DNS**: Resolución de nombres
- **Puerto**: Conectividad al puerto 8113
- **HTTP**: Conexión HTTP básica
- Resumen de pruebas exitosas/fallidas

## 🔧 Uso del Módulo

### **Paso 0: Configurar URLs del Firmador**
1. Verificar las URLs actuales en la sección "Ambientes Disponibles"
2. Si necesitas cambiar las URLs, usar el comando:
   ```bash
   php artisan firmador:actualizar-urls --url-produccion="nueva-url" --url-test="nueva-url"
   ```
3. Recargar la página para ver los cambios

### **Paso 1: Verificar Información del Servidor**
1. Al cargar la página, se muestra automáticamente
2. Verificar que `allow_url_fopen` esté habilitado
3. Confirmar versión de cURL compatible

### **Paso 2: Probar Conexión Básica**
1. Configurar timeout (recomendado: 30 segundos)
2. Hacer clic en **"Probar Conexión"**
3. Revisar resultado:
   - ✅ **Verde**: Conexión exitosa
   - ❌ **Rojo**: Error de conexión

### **Paso 3: Probar Firma**
1. Configurar timeout para firma
2. Hacer clic en **"Probar Firma"**
3. Revisar respuesta del servicio

### **Paso 4: Diagnóstico de Red**
1. Hacer clic en **"Ejecutar Diagnóstico"**
2. Revisar cada prueba individualmente
3. Analizar el resumen final

## 📊 Interpretación de Resultados

### **Conexión Exitosa**
```
✅ ¡Conexión exitosa!
Status: 200 | Tiempo: 150ms | URL: http://147.93.176.3:8113/firmardocumento/
```

### **Error de Conexión**
```
❌ Error de conexión
cURL error 7: Failed to connect to 147.93.176.3 port 8113
```

### **Diagnóstico de Red**
- **DNS**: ✅ Resolución DNS exitosa
- **Puerto**: ❌ Puerto no accesible
- **HTTP**: ❌ No se pudo conectar via HTTP

## 🚨 Problemas Comunes y Soluciones

### **Error: cURL error 7**
**Causa**: No se puede conectar al puerto 8113
**Solución**: 
- Contactar proveedor de hosting para abrir puerto
- Verificar firewall del servidor
- Confirmar que el servicio esté activo

### **Error: cURL error 28**
**Causa**: Timeout de conexión
**Solución**:
- Aumentar timeout en la configuración
- Verificar velocidad de red
- Contactar proveedor del servicio

### **Error: allow_url_fopen disabled**
**Causa**: Configuración de PHP restrictiva
**Solución**:
- Contactar proveedor de hosting
- Habilitar `allow_url_fopen` en php.ini
- Usar cURL como alternativa

## 🔍 Comandos de Diagnóstico

### **Verificar permisos**
```bash
php artisan firmador:crear-permisos --rol=admin
```

### **Actualizar URLs del firmador**
```bash
# Actualizar con URLs por defecto
php artisan firmador:actualizar-urls

# Actualizar con URLs personalizadas
php artisan firmador:actualizar-urls --url-produccion="http://nueva-ip:8113/firmardocumento/" --url-test="http://localhost:8113/firmardocumento/"
```

### **Verificar logs**
```bash
tail -f storage/logs/laravel.log | grep "firmador"
```

### **Probar desde terminal**
```bash
# Probar conectividad básica
curl -v http://147.93.176.3:8113/firmardocumento/

# Probar puerto
telnet 147.93.176.3 8113
```

## 📋 Checklist de Verificación

### **Antes de Usar**
- [ ] Permisos creados correctamente
- [ ] Usuario tiene acceso al módulo
- [ ] Servidor tiene conexión a internet

### **Durante las Pruebas**
- [ ] Información del servidor se carga
- [ ] Prueba de conexión básica funciona
- [ ] Prueba de firma responde
- [ ] Diagnóstico de red completo

### **Después de las Pruebas**
- [ ] Revisar logs para detalles
- [ ] Documentar resultados
- [ ] Contactar soporte si es necesario

## 🎯 Casos de Uso

### **Caso 1: Problema en Producción**
1. Ejecutar diagnóstico completo
2. Comparar con resultados en local
3. Identificar diferencias de configuración
4. Contactar proveedor de hosting

### **Caso 2: Cambio de Servidor**
1. Ejecutar pruebas en nuevo servidor
2. Verificar configuración de red
3. Confirmar compatibilidad
4. Documentar configuración

### **Caso 3: Mantenimiento Preventivo**
1. Ejecutar pruebas periódicas
2. Monitorear tiempos de respuesta
3. Detectar problemas temprano
4. Mantener logs de pruebas

## 🔧 Configuración Avanzada

### **Personalizar Timeouts**
```php
// En FirmadorTestController.php
$timeout = $request->get('timeout', 30); // Default 30 segundos
```

### **Agregar Nuevas Pruebas**
```php
// Agregar método en FirmadorTestController
public function testCustom()
{
    // Lógica de prueba personalizada
}
```

### **Personalizar URLs**
```php
// Cambiar URL del firmador
$url = 'http://nueva-ip:puerto/firmardocumento/';
```

## 📞 Soporte

### **Problemas del Módulo**
- Revisar logs de Laravel
- Verificar permisos de usuario
- Confirmar rutas configuradas

### **Problemas de Conectividad**
- Contactar proveedor de hosting
- Verificar configuración de firewall
- Confirmar estado del servicio de firma

---

**Nota**: Este módulo es una herramienta de diagnóstico. Los problemas de conectividad deben resolverse a nivel de infraestructura con el proveedor de hosting.
