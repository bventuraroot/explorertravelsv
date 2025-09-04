---
titulo: "Gestión de Roles y Permisos"
modulo: "Administracion"
descripcion: "Cómo configurar y administrar roles y permisos en el sistema"
version: "1.0"
activo: true
orden: 2
icono: "shield"
---

# Gestión de Roles y Permisos

El sistema de roles y permisos te permite controlar el acceso de los usuarios a diferentes funcionalidades del sistema.

## ¿Qué son los Roles?

Los roles son grupos de permisos que se asignan a los usuarios. Un usuario puede tener uno o más roles.

### Roles Predefinidos

- **Administrador**: Acceso completo al sistema
- **Usuario**: Acceso básico limitado
- **Vendedor**: Acceso al módulo de ventas
- **Contador**: Acceso a reportes y contabilidad

## Crear un Nuevo Rol

1. Ve a **Administración** > **Roles**
2. Haz clic en **Nuevo Rol**
3. Define el nombre del rol
4. Asigna los permisos correspondientes
5. Guarda el rol

## Asignar Permisos

Los permisos controlan acciones específicas:

### Permisos de Usuario
- `manage_users`: Gestionar usuarios
- `view_users`: Ver usuarios
- `create_users`: Crear usuarios
- `edit_users`: Editar usuarios
- `delete_users`: Eliminar usuarios

### Permisos de Ventas
- `manage_sales`: Gestionar ventas
- `view_sales`: Ver ventas
- `create_sales`: Crear ventas
- `edit_sales`: Editar ventas
- `delete_sales`: Eliminar ventas

### Permisos de Reportes
- `view_reports`: Ver reportes
- `export_reports`: Exportar reportes
- `manage_reports`: Gestionar reportes

## Asignar Roles a Usuarios

1. Ve al usuario que deseas modificar
2. Haz clic en **Editar**
3. Selecciona los roles apropiados
4. Guarda los cambios

## Mejores Prácticas

- **Principio de menor privilegio**: Asigna solo los permisos necesarios
- **Roles específicos**: Crea roles para funciones específicas
- **Revisión periódica**: Revisa los permisos regularmente
- **Documentación**: Documenta qué hace cada rol

## Solución de Problemas

### Usuario no puede acceder a una función
1. Verifica que el usuario tenga el rol correcto
2. Confirma que el rol tenga el permiso necesario
3. Revisa la configuración del módulo

### Permisos no se aplican
1. Verifica la caché de permisos
2. Confirma que el usuario esté activo
3. Revisa la configuración del sistema
