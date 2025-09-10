# 📊 Diccionario de Datos - ELIT ↔ WooCommerce

## 🎯 Introducción

Este documento detalla el mapeo completo de campos entre la API de ELIT y WooCommerce, incluyendo transformaciones, validaciones y reglas de negocio aplicadas.

---

## 📋 Tabla de Contenidos

1. [Estructura de Respuesta ELIT](#estructura-de-respuesta-elit)
2. [Mapeo de Campos Principales](#mapeo-de-campos-principales)
3. [Transformaciones de Datos](#transformaciones-de-datos)
4. [Reglas de Negocio](#reglas-de-negocio)
5. [Campos Calculados](#campos-calculados)
6. [Metadatos Adicionales](#metadatos-adicionales)

---

## 🏗️ Estructura de Respuesta ELIT

### Respuesta de API ELIT:
```json
{
  "codigo": 200,
  "paginador": {
    "total": 1149,
    "limit": 100,
    "offset": 0
  },
  "resultado": [
    {
      "id": 19043,
      "codigo_alfa": "LENEX5WS0T36151",
      "codigo_producto": "5WS0T36151",
      "nombre": "3Y Premier Support...",
      // ... más campos
    }
  ]
}
```

### Estructura Procesada:
- **`resultado[]`** → Array de productos para procesar
- **`paginador.total`** → Total de productos disponibles
- **`codigo`** → Status de la respuesta (200 = éxito)

---

## 🔄 Mapeo de Campos Principales

| Campo ELIT | Campo WooCommerce | Tipo | Transformación | Ejemplo |
|------------|-------------------|------|----------------|---------|
| `codigo_producto` | `_sku` | string | Prefijo + código | `5WS0T36151` → `ELIT_5WS0T36151` |
| `nombre` | `post_title` | string | Directo | `AIO Lenovo 24"` |
| `descripcion` | `post_content` | string | Directo | Descripción completa |
| `categoria` | `product_cat` | taxonomy | Crear/asignar | `Computadoras` |
| `sub_categoria` | `product_cat` | taxonomy | Crear/asignar | `All In One` |
| `marca` | `product_cat` | taxonomy | `Marca: {marca}` | `Marca: LENOVO` |
| `pvp_ars` | `_regular_price` | float | Con markup | `1000` → `1350` (35%) |
| `pvp_usd` | `_regular_price` | float | Con markup | `100` → `135` (35%) |
| `stock_total` | `_stock` | int | Directo | `5` |
| `nivel_stock` | `_stock_status` | string | Transformado | `bajo` → `onbackorder` |
| `peso` | `_weight` | float | Directo | `7.03` |
| `imagenes[]` | `_thumbnail_id` | attachment | Descarga + asigna | URL → ID imagen |
| `garantia` | `_warranty` | string | Meta personalizado | `12 meses` |
| `ean` | `_ean` | string | Meta personalizado | `198153857029` |
| `gamer` | `product_tag` | taxonomy | Condicional | `true` → `Gaming` |

---

## 🔧 Transformaciones de Datos

### 1. SKU (Stock Keeping Unit)

#### Entrada ELIT:
```json
{
  "codigo_producto": "5WS0T36151",
  "codigo_alfa": "LENEX5WS0T36151",
  "id": 19043
}
```

#### Proceso de Transformación:
1. **Prioridad**: `codigo_producto` > `codigo_alfa` > `id`
2. **Prefijo**: Agregar prefijo configurado
3. **Validación**: Verificar que sea único
4. **Sanitización**: Remover caracteres especiales

#### Salida WooCommerce:
```php
$product->set_sku('ELIT_5WS0T36151');
```

### 2. Precios

#### Entrada ELIT:
```json
{
  "precio": 564.99,           // Precio base
  "impuesto_interno": 0,      // Impuesto interno
  "iva": 10.5,               // IVA
  "pvp_usd": 874.04,         // Precio venta público USD
  "pvp_ars": 1245507,        // Precio venta público ARS
  "markup": 0.4,             // Markup de ELIT
  "cotizacion": 1425          // Cotización dólar
}
```

#### Proceso de Transformación:
1. **Selección de precio**:
   - Si `elit_sync_usd = true` → usar `pvp_usd`
   - Si `elit_sync_usd = false` → usar `pvp_ars`
   - Fallback → `precio` + `iva` + `impuesto_interno`

2. **Aplicación de markup**:
   ```php
   $precio_final = $precio_base * (1 + (markup_percentage / 100))
   ```

3. **Redondeo**: 2 decimales

#### Salida WooCommerce:
```php
$product->set_regular_price(1681434.45); // Con markup del 35%
```

### 3. Stock y Disponibilidad

#### Entrada ELIT:
```json
{
  "stock_total": 0,
  "stock_deposito_cliente": 0,
  "stock_deposito_cd": 0,
  "nivel_stock": "bajo"
}
```

#### Matriz de Transformación:

| stock_total | nivel_stock | WC stock_status | WC backorders | Descripción |
|-------------|-------------|-----------------|---------------|-------------|
| > 0 | cualquiera | `instock` | `no` | Stock disponible |
| = 0 | `bajo` | `onbackorder` | `yes` | Sin stock pero permite pedidos |
| = 0 | `alto` o vacío | `outofstock` | `no` | Sin stock, no permite pedidos |

#### Salida WooCommerce:
```php
$product->set_stock_quantity(0);
$product->set_stock_status('onbackorder');
$product->set_backorders('yes');
```

### 4. Categorías

#### Entrada ELIT:
```json
{
  "categoria": "Computadoras",
  "sub_categoria": "All In One", 
  "marca": "LENOVO",
  "gamer": false
}
```

#### Proceso de Transformación:
1. **Categoría principal**: Crear término `Computadoras`
2. **Subcategoría**: Crear término `All In One`
3. **Marca**: Crear término `Marca: LENOVO`
4. **Gaming**: Si `gamer = true`, crear término `Gaming`

#### Salida WooCommerce:
```php
wp_set_object_terms($product_id, [
    'Computadoras',
    'All In One', 
    'Marca: LENOVO'
], 'product_cat');
```

### 5. Imágenes

#### Entrada ELIT:
```json
{
  "imagenes": [
    "https://images.elit.com.ar/p/17592/i/Kc3iB_l.webp"
  ],
  "miniaturas": [
    "https://images.elit.com.ar/p/17592/i/Kc3iB_s.webp"  
  ]
}
```

#### Proceso de Transformación:
1. **Prioridad**: `imagenes[]` > `miniaturas[]`
2. **Descarga**: Usar `media_sideload_image()`
3. **Asignación**: Primera imagen → imagen destacada
4. **Galería**: Resto de imágenes → galería del producto

#### Salida WooCommerce:
```php
set_post_thumbnail($product_id, $attachment_id);
update_post_meta($product_id, '_product_image_gallery', $gallery_ids);
```

---

## 📐 Reglas de Negocio

### 1. Gestión de SKUs

#### Regla: Prefijo Obligatorio
- **Propósito**: Identificar productos de ELIT
- **Formato**: `{PREFIJO}_{CODIGO_ELIT}`
- **Ejemplo**: `ELIT_5WS0T36151`

#### Regla: Unicidad
- **Validación**: SKU debe ser único en WooCommerce
- **Conflicto**: Si existe, actualizar producto existente
- **Nuevo**: Si no existe, crear producto nuevo

### 2. Cálculo de Precios

#### Regla: Markup Obligatorio
- **Propósito**: Aplicar margen de ganancia
- **Fórmula**: `precio_final = precio_base × (1 + markup/100)`
- **Redondeo**: 2 decimales

#### Regla: Selección de Moneda
- **USD**: Usar `pvp_usd` si está configurado
- **ARS**: Usar `pvp_ars` por defecto
- **Fallback**: `precio` + impuestos si no hay PVP

### 3. Gestión de Stock

#### Regla: Stock Cero con Nivel Bajo
- **Condición**: `stock_total = 0` AND `nivel_stock = "bajo"`
- **Acción**: Permitir pedidos pendientes (`onbackorder`)
- **Propósito**: Mantener ventas con reposición próxima

#### Regla: Stock Cero Normal
- **Condición**: `stock_total = 0` AND `nivel_stock ≠ "bajo"`
- **Acción**: Marcar sin stock (`outofstock`)
- **Propósito**: Evitar pedidos sin reposición

### 4. Categorización Automática

#### Regla: Jerarquía de Categorías
1. **Categoría principal**: Desde `categoria`
2. **Subcategoría**: Desde `sub_categoria`  
3. **Marca**: Prefijo "Marca: " + `marca`
4. **Gaming**: Solo si `gamer = true`

#### Regla: Creación Automática
- **No existe**: Crear nueva categoría
- **Existe**: Asignar a existente
- **Mantener**: Jerarquía de WooCommerce

---

## 🧮 Campos Calculados

### 1. Descripción Corta

#### Campos Fuente:
- `marca`
- `categoria` + `sub_categoria`  
- `garantia`
- `gamer`
- `nivel_stock`

#### Fórmula:
```php
$parts = [
    $marca,                                    // "LENOVO"
    $categoria . " - " . $sub_categoria,       // "Computadoras - All In One"  
    "Garantía: " . $garantia,                 // "Garantía: 12 meses"
    $gamer ? "🎮 Gaming" : "",                // "🎮 Gaming" o vacío
    $nivel_stock === "bajo" ? "⚠️ Stock limitado" : ""
];

$short_description = implode(" | ", array_filter($parts));
```

#### Resultado:
`"LENOVO | Computadoras - All In One | Garantía: 12 meses | ⚠️ Stock limitado"`

### 2. Dimensiones del Producto

#### Entrada ELIT:
```json
{
  "dimensiones": {
    "largo": 18.53,
    "ancho": 54.12, 
    "alto": 43.39
  }
}
```

#### Mapeo WooCommerce:
```php
$product->set_length($dimensiones['largo']);    // 18.53
$product->set_width($dimensiones['ancho']);     // 54.12  
$product->set_height($dimensiones['alto']);     // 43.39
```

---

## 🏷️ Metadatos Adicionales

### Metadatos Personalizados Creados:

| Meta Key | Fuente ELIT | Propósito | Ejemplo |
|----------|-------------|-----------|---------|
| `_elit_id` | `id` | Referencia única | `19043` |
| `_elit_code_alfa` | `codigo_alfa` | Código alfanumérico | `LENEX5WS0T36151` |
| `_elit_link` | `link` | Enlace a ELIT | `https://elit.com.ar/producto/...` |
| `_elit_warranty` | `garantia` | Información de garantía | `36 MESES` |
| `_elit_ean` | `ean` | Código de barras | `198153857029` |
| `_elit_currency` | `moneda` | Moneda original | `USD` o `ARS` |
| `_elit_stock_level` | `nivel_stock` | Nivel de stock ELIT | `bajo` |
| `_elit_gamer` | `gamer` | Producto gaming | `true`/`false` |
| `_elit_created` | `creado` | Fecha creación ELIT | `15/8/25, 5:06 p. m.` |
| `_elit_updated` | `actualizado` | Última actualización ELIT | `26/8/25, 7:47 p. m.` |

### Uso de Metadatos:

#### Consultar productos por marca:
```php
$products = get_posts([
    'post_type' => 'product',
    'meta_query' => [
        [
            'key' => '_elit_brand',
            'value' => 'LENOVO',
            'compare' => '='
        ]
    ]
]);
```

#### Filtrar productos gaming:
```php
$gaming_products = get_posts([
    'post_type' => 'product',
    'meta_query' => [
        [
            'key' => '_elit_gamer',
            'value' => 'true',
            'compare' => '='
        ]
    ]
]);
```

---

## 🔢 Tipos de Datos y Validaciones

### Validaciones Aplicadas:

| Campo | Tipo Esperado | Validación | Valor por Defecto |
|-------|---------------|------------|-------------------|
| `id` | integer | `> 0` | `null` |
| `codigo_producto` | string | No vacío, alfanumérico | `''` |
| `nombre` | string | No vacío, max 200 chars | `'Producto sin nombre'` |
| `precio` | float | `>= 0` | `0` |
| `pvp_ars` | float | `>= 0` | `0` |
| `pvp_usd` | float | `>= 0` | `0` |
| `stock_total` | integer | `>= 0` | `0` |
| `peso` | float | `>= 0` | `0` |
| `ean` | string/integer | Numérico | `''` |
| `gamer` | boolean | `true`/`false` | `false` |
| `imagenes` | array | URLs válidas | `[]` |

### Funciones de Sanitización:

```php
// SKU
$sku = sanitize_text_field($elit_product['codigo_producto']);

// Nombre
$name = sanitize_text_field($elit_product['nombre']);

// Precio
$price = floatval($elit_product['pvp_ars']);

// Stock
$stock = intval($elit_product['stock_total']);

// Descripción
$description = wp_kses_post($elit_product['descripcion']);
```

---

## ⚙️ Reglas de Transformación Específicas

### 1. Transformación de Stock Status

#### Lógica Implementada:
```php
function get_stock_status($elit_product) {
    $stock_quantity = intval($elit_product['stock_total'] ?? 0);
    $stock_level = $elit_product['nivel_stock'] ?? '';
    
    if ($stock_quantity > 0) {
        return 'instock';           // Stock disponible
    } elseif ($stock_level === 'bajo') {
        return 'onbackorder';       // Stock bajo, permite pedidos
    } else {
        return 'outofstock';        // Sin stock, no permite pedidos
    }
}
```

#### Casos de Uso:
- **Stock = 5, nivel = "alto"** → `instock`
- **Stock = 0, nivel = "bajo"** → `onbackorder`  
- **Stock = 0, nivel = ""** → `outofstock`

### 2. Transformación de Precios

#### Lógica de Selección:
```php
function get_price($elit_product) {
    $use_usd = get_option('elit_sync_usd', false);
    
    if ($use_usd && isset($elit_product['pvp_usd'])) {
        $base_price = floatval($elit_product['pvp_usd']);
    } elseif (isset($elit_product['pvp_ars'])) {
        $base_price = floatval($elit_product['pvp_ars']);
    } else {
        // Fallback: precio base + impuestos
        $base_price = floatval($elit_product['precio'] ?? 0);
        $base_price += floatval($elit_product['iva'] ?? 0);
        $base_price += floatval($elit_product['impuesto_interno'] ?? 0);
    }
    
    // Aplicar markup
    $markup = get_option('elit_markup_percentage', 35);
    return $base_price * (1 + ($markup / 100));
}
```

### 3. Transformación de Categorías

#### Lógica de Creación:
```php
function create_categories($elit_product) {
    $categories = [];
    
    // Categoría principal
    if (!empty($elit_product['categoria'])) {
        $categories[] = $elit_product['categoria'];
    }
    
    // Subcategoría  
    if (!empty($elit_product['sub_categoria'])) {
        $categories[] = $elit_product['sub_categoria'];
    }
    
    // Marca como categoría
    if (!empty($elit_product['marca'])) {
        $categories[] = 'Marca: ' . $elit_product['marca'];
    }
    
    // Gaming
    if ($elit_product['gamer'] === true) {
        $categories[] = 'Gaming';
    }
    
    return $categories;
}
```

---

## 📊 Campos de Monitoreo y Auditoría

### Timestamps de Sincronización:

| Meta Key | Propósito | Formato | Ejemplo |
|----------|-----------|---------|---------|
| `_elit_last_sync` | Última sincronización | `Y-m-d H:i:s` | `2025-01-09 10:30:15` |
| `_elit_created_date` | Fecha creación ELIT | `d/m/y, H:i a` | `15/8/25, 5:06 p. m.` |
| `_elit_updated_date` | Última actualización ELIT | `d/m/y, H:i a` | `26/8/25, 7:47 p. m.` |
| `_elit_sync_version` | Versión del plugin | `x.x.x` | `1.0.0` |

### Campos de Trazabilidad:

```php
// Al crear/actualizar producto
update_post_meta($product_id, '_elit_id', $elit_product['id']);
update_post_meta($product_id, '_elit_last_sync', current_time('Y-m-d H:i:s'));
update_post_meta($product_id, '_elit_sync_version', VERSION_ELIT);
```

---

## 🔍 Consultas Útiles

### Productos por Marca:
```sql
SELECT p.ID, p.post_title, pm.meta_value as marca
FROM wp_posts p
JOIN wp_postmeta pm ON p.ID = pm.post_id
WHERE p.post_type = 'product'
AND pm.meta_key = '_elit_brand'
AND pm.meta_value = 'LENOVO';
```

### Productos Sin Stock:
```sql
SELECT p.ID, p.post_title, pm1.meta_value as sku, pm2.meta_value as stock_status
FROM wp_posts p
JOIN wp_postmeta pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_sku'
JOIN wp_postmeta pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_stock_status'  
WHERE p.post_type = 'product'
AND pm1.meta_value LIKE 'ELIT_%'
AND pm2.meta_value = 'outofstock';
```

### Productos Gaming:
```sql
SELECT p.ID, p.post_title
FROM wp_posts p
JOIN wp_postmeta pm ON p.ID = pm.post_id
WHERE p.post_type = 'product'
AND pm.meta_key = '_elit_gamer'
AND pm.meta_value = 'true';
```

---

## 📈 Métricas y KPIs

### Métricas de Sincronización:

| Métrica | Descripción | Query/Función |
|---------|-------------|---------------|
| **Total Productos ELIT** | Productos sincronizados desde ELIT | `COUNT(_sku LIKE 'ELIT_%')` |
| **Productos Con Stock** | Productos disponibles | `COUNT(_stock_status = 'instock')` |
| **Productos Sin Stock** | Productos agotados | `COUNT(_stock_status = 'outofstock')` |
| **Productos Gaming** | Productos para gamers | `COUNT(_elit_gamer = 'true')` |
| **Última Sincronización** | Timestamp última sync | `get_option('elit_last_update')` |
| **Productos por Marca** | Distribución por marca | `GROUP BY _elit_brand` |

### Dashboard de Estadísticas:

```php
function get_elit_dashboard_stats() {
    global $wpdb;
    
    return [
        'total_products' => $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->posts} p
            JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'product'
            AND pm.meta_key = '_sku'
            AND pm.meta_value LIKE 'ELIT_%'
        "),
        
        'in_stock' => $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->posts} p
            JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id
            JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id
            WHERE p.post_type = 'product'
            AND pm1.meta_key = '_sku' AND pm1.meta_value LIKE 'ELIT_%'
            AND pm2.meta_key = '_stock_status' AND pm2.meta_value = 'instock'
        "),
        
        'gaming_products' => $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->posts} p
            JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'product'
            AND pm.meta_key = '_elit_gamer'
            AND pm.meta_value = 'true'
        ")
    ];
}
```

---

## 🔄 Versionado de Datos

### Control de Cambios:

| Versión Plugin | Cambios en Datos | Migración Requerida |
|----------------|------------------|---------------------|
| **1.0.0** | Estructura inicial | No |
| **1.1.0** | Metadatos adicionales | Automática |
| **2.0.0** | Cambio estructura SKU | Manual |

### Migración de Datos:

```php
// Ejemplo de migración de versión
function elit_migrate_data_v1_to_v2() {
    $products = get_posts([
        'post_type' => 'product',
        'meta_query' => [
            [
                'key' => '_sku',
                'value' => 'ELIT_',
                'compare' => 'LIKE'
            ]
        ]
    ]);
    
    foreach ($products as $product) {
        // Aplicar cambios de estructura
        // Actualizar metadatos
        // Registrar migración
    }
}
```

---

## 📝 Notas Importantes

### ⚠️ Limitaciones:
- **Máximo 100 productos** por solicitud a API ELIT
- **Timeout de 30 segundos** por solicitud HTTP
- **Memoria máxima 2GB** durante sincronización
- **Tiempo máximo 30 minutos** por sincronización completa

### 🔒 Consideraciones de Seguridad:
- **Credenciales encriptadas** en base de datos WordPress
- **Validación de nonces** en todas las operaciones AJAX
- **Sanitización de datos** antes de guardar
- **Verificación de capacidades** de usuario

### 🚀 Optimizaciones:
- **Cache de tokens** durante 30 minutos
- **Procesamiento por lotes** de 50 productos
- **Limpieza de cache** cada 5 lotes
- **Logs rotativos** para evitar archivos grandes

---

**Última actualización**: Enero 9, 2025  
**Versión del plugin**: 1.0.0  
**API de ELIT**: v1
