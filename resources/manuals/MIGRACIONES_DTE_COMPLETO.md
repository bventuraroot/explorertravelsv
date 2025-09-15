# 📋 **MIGRACIONES DTE COMPLETO - ExplorerTravelSV**

## 🎯 **Resumen de Migraciones Creadas**

Este documento lista todas las migraciones necesarias para el sistema completo de DTE (Documento Tributario Electrónico) con manejo de errores, contingencia y envío de correos.

---

## 📊 **Migraciones Principales**

### **1. Tabla `dte_errors` - Manejo de Errores**
**Archivo:** `2025_09_13_192511_create_dte_errors_table.php`

```sql
CREATE TABLE dte_errors (
    id BIGINT PRIMARY KEY,
    dte_id BIGINT NOT NULL,
    tipo_error VARCHAR(255) NOT NULL,
    codigo_error VARCHAR(255),
    descripcion TEXT NOT NULL,
    detalles JSON NULL,
    stack_trace JSON NULL,
    json_completo LONGTEXT NULL,
    intentos_realizados INT DEFAULT 0,
    max_intentos INT DEFAULT 3,
    proximo_reintento TIMESTAMP NULL,
    resuelto BOOLEAN DEFAULT FALSE,
    resuelto_por BIGINT NULL,
    resuelto_en TIMESTAMP NULL,
    solucion_aplicada VARCHAR(255) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

**Índices:**
- `dte_id + tipo_error`
- `resuelto + tipo_error`
- `proximo_reintento`
- `created_at`

---

### **2. Tabla `contingencias` - Gestión de Contingencias**
**Archivo:** `2025_09_13_210920_create_contingencias_table.php`

```sql
CREATE TABLE contingencias (
    id BIGINT PRIMARY KEY,
    codInterno VARCHAR(255) UNIQUE NOT NULL,
    idEmpresa BIGINT NOT NULL,
    codEstado VARCHAR(255) NOT NULL,
    descripcion VARCHAR(255) NOT NULL,
    fInicio DATE NOT NULL,
    fFin DATE NOT NULL,
    observacionesMsg TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

**Índices:**
- `idEmpresa + codEstado`
- `fInicio + fFin`
- `codInterno`

---

### **3. Campos Adicionales para Tabla `dte`**
**Archivo:** `2025_09_13_210929_add_missing_columns_to_dte_table.php`

```sql
ALTER TABLE dte ADD COLUMN sale_id BIGINT NULL;
ALTER TABLE dte ADD COLUMN jsonDte LONGTEXT NULL;
ALTER TABLE dte ADD COLUMN estadoHacienda VARCHAR(255) NULL;
ALTER TABLE dte ADD COLUMN fecha_envio TIMESTAMP NULL;
ALTER TABLE dte ADD COLUMN fecha_respuesta TIMESTAMP NULL;
ALTER TABLE dte ADD COLUMN intentos_envio INT DEFAULT 0;
ALTER TABLE dte ADD COLUMN proximo_reintento TIMESTAMP NULL;
ALTER TABLE dte ADD COLUMN necesita_contingencia BOOLEAN DEFAULT FALSE;
```

**Índices:**
- `sale_id`
- `codEstado + estadoHacienda`
- `fecha_envio`
- `proximo_reintento`
- `necesita_contingencia`

---

### **4. Tabla `correlativos` - Control de Numeración**
**Archivo:** `2025_09_13_210940_create_correlativos_table.php`

```sql
CREATE TABLE correlativos (
    id BIGINT PRIMARY KEY,
    company_id BIGINT NOT NULL,
    tipo_documento VARCHAR(10) NOT NULL,
    codigo_establecimiento VARCHAR(10) NOT NULL,
    codigo_punto_venta VARCHAR(10) NOT NULL,
    numero_actual INT DEFAULT 1,
    numero_final INT NULL,
    activo BOOLEAN DEFAULT TRUE,
    descripcion VARCHAR(255) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

**Índices:**
- `company_id + tipo_documento + codigo_establecimiento + codigo_punto_venta` (UNIQUE)
- `company_id + activo`
- `tipo_documento`

---

## 🔧 **Migraciones de Precisión Decimal**

### **5. Precisión Decimal para `salesdetails`**
**Archivo:** `2025_09_13_200907_increase_decimal_precision_salesdetails_table.php`

```sql
ALTER TABLE salesdetails 
MODIFY COLUMN pricesale DECIMAL(10,8),
MODIFY COLUMN priceunit DECIMAL(10,8),
MODIFY COLUMN nosujeta DECIMAL(10,8),
MODIFY COLUMN exempt DECIMAL(10,8),
MODIFY COLUMN detained DECIMAL(10,8),
MODIFY COLUMN detained13 DECIMAL(10,8);
```

### **6. Precisión Decimal para `sales`**
**Archivo:** `2025_09_13_200919_increase_decimal_precision_sales_table.php`

```sql
ALTER TABLE sales 
MODIFY COLUMN totalamount DECIMAL(10,8);
```

---

## 🗂️ **Tablas Existentes que se Mantienen**

### **7. Tabla `manuals` - Sistema de Manuales**
**Archivo:** `2025_09_04_062624_create_manuals_table.php`

```sql
CREATE TABLE manuals (
    id BIGINT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    modulo VARCHAR(255) NOT NULL,
    descripcion TEXT NULL,
    contenido LONGTEXT NOT NULL,
    version VARCHAR(255) DEFAULT '1.0',
    activo BOOLEAN DEFAULT TRUE,
    orden INT DEFAULT 0,
    icono VARCHAR(255) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

---

## 🚀 **Orden de Ejecución**

### **Paso 1: Migraciones Base**
```bash
php artisan migrate --path=database/migrations/2025_09_13_210920_create_contingencias_table.php
php artisan migrate --path=database/migrations/2025_09_13_210940_create_correlativos_table.php
```

### **Paso 2: Campos Adicionales**
```bash
php artisan migrate --path=database/migrations/2025_09_13_210929_add_missing_columns_to_dte_table.php
```

### **Paso 3: Sistema de Errores**
```bash
php artisan migrate --path=database/migrations/2025_09_13_192511_create_dte_errors_table.php
```

### **Paso 4: Precisión Decimal**
```bash
php artisan migrate --path=database/migrations/2025_09_13_200907_increase_decimal_precision_salesdetails_table.php
php artisan migrate --path=database/migrations/2025_09_13_200919_increase_decimal_precision_sales_table.php
```

### **Paso 5: Todas las Migraciones**
```bash
php artisan migrate
```

---

## 📋 **Verificación Post-Migración**

### **Comandos de Verificación:**
```bash
# Verificar estructura de tablas
php artisan tinker
>>> Schema::hasTable('dte_errors')
>>> Schema::hasTable('contingencias')
>>> Schema::hasTable('correlativos')

# Verificar campos en tabla dte
>>> Schema::hasColumn('dte', 'sale_id')
>>> Schema::hasColumn('dte', 'jsonDte')
>>> Schema::hasColumn('dte', 'necesita_contingencia')

# Verificar precisión decimal
>>> DB::select("DESCRIBE salesdetails");
>>> DB::select("DESCRIBE sales");
```

---

## 🔗 **Relaciones de Claves Foráneas**

| **Tabla** | **Campo** | **Referencia** | **Acción** |
|-----------|-----------|----------------|------------|
| `dte_errors` | `dte_id` | `dte.id` | `CASCADE` |
| `dte_errors` | `resuelto_por` | `users.id` | `SET NULL` |
| `contingencias` | `idEmpresa` | `companies.id` | `CASCADE` |
| `dte` | `sale_id` | `sales.id` | `CASCADE` |
| `correlativos` | `company_id` | `companies.id` | `CASCADE` |

---

## ⚠️ **Notas Importantes**

1. **Backup:** Siempre hacer backup antes de ejecutar migraciones
2. **Orden:** Respetar el orden de ejecución para evitar errores de claves foráneas
3. **Testing:** Probar en ambiente de desarrollo antes de producción
4. **Rollback:** Todas las migraciones tienen métodos `down()` para rollback

---

## 📊 **Estadísticas de Migraciones**

- **Total de Migraciones:** 6 nuevas
- **Tablas Creadas:** 3 (`dte_errors`, `contingencias`, `correlativos`)
- **Tablas Modificadas:** 3 (`dte`, `sales`, `salesdetails`)
- **Índices Agregados:** 15+
- **Claves Foráneas:** 5

---

**✅ Sistema DTE Completo Listo para Producción**
