---
titulo: "Configuración de Correlativos"
modulo: "Correlativos"
descripcion: "Cómo configurar y gestionar los correlativos de documentos"
version: "1.0"
activo: true
orden: 1
icono: "list-ol"
---

# Configuración de Correlativos

Los correlativos son números secuenciales que se asignan automáticamente a los documentos fiscales para mantener un control ordenado y cumplir con las regulaciones tributarias.

## ¿Qué son los Correlativos?

Los correlativos son números consecutivos que:

- Se asignan automáticamente a cada documento
- Mantienen un orden secuencial
- Son requeridos por las autoridades fiscales
- Facilitan el control y auditoría

## Crear un Nuevo Correlativo

Para configurar un nuevo correlativo:

1. Ve al módulo de **Correlativos**
2. Haz clic en **Nuevo Correlativo**
3. Completa la información:
   - **Empresa**: Selecciona la empresa
   - **Tipo de documento**: Factura, Nota de Crédito, etc.
   - **Número inicial**: Primer número del rango
   - **Número final**: Último número del rango
   - **Resolución**: Número de resolución fiscal
4. Guarda el correlativo

## Tipos de Documentos

### Factura
- Para ventas normales
- Incluye IVA
- Rango típico: 1-9999

### Nota de Crédito
- Para devoluciones
- Referencia a factura original
- Rango independiente

### Comprobante de Crédito Fiscal
- Para ventas exentas
- No incluye IVA
- Rango específico

## Estados del Correlativo

### Activo
- En uso para generar documentos
- Asigna números automáticamente
- Estado normal de operación

### Inactivo
- No disponible para uso
- Mantiene números asignados
- Para mantenimiento

### Agotado
- Ha alcanzado el número final
- Requiere nuevo correlativo
- No puede generar más documentos

## Gestión Automática

El sistema automáticamente:

- **Asigna el siguiente número** disponible
- **Marca como agotado** cuando se alcanza el límite
- **Mantiene un registro** de uso
- **Valida disponibilidad** antes de asignar

## Resoluciones Fiscales

### Información Requerida

- **Número de resolución**
- **Fecha de emisión**
- **Rango autorizado**
- **Vigencia**

### Documentos Necesarios

- Resolución del Ministerio de Hacienda
- Certificado de registro
- Documentos de la empresa

## Monitoreo de Correlativos

### Estadísticas Disponibles

- **Números utilizados**: Cantidad de documentos emitidos
- **Números disponibles**: Cantidad restante
- **Porcentaje de uso**: Progreso del correlativo
- **Fecha de agotamiento estimada**: Proyección de uso

### Alertas

El sistema te notifica cuando:

- Un correlativo está por agotarse
- Se alcanza el 80% del rango
- Un correlativo se agota
- Hay problemas de configuración

## Renovar Correlativos

Cuando un correlativo se agota:

1. **Solicita nueva resolución** a Hacienda
2. **Crea nuevo correlativo** con el nuevo rango
3. **Desactiva el correlativo anterior**
4. **Actualiza la configuración**

## Consejos de Gestión

- **Planifica con anticipación** la renovación
- **Mantén copias** de las resoluciones
- **Monitorea el uso** regularmente
- **Configura alertas** tempranas
- **Documenta cambios** importantes

## Solución de Problemas

### Correlativo Agotado
- Crea nuevo correlativo inmediatamente
- Solicita nueva resolución
- Actualiza configuración

### Números Duplicados
- Verifica configuración
- Revisa resolución
- Contacta soporte técnico

### Error en Asignación
- Verifica estado del correlativo
- Revisa permisos de usuario
- Consulta logs del sistema
