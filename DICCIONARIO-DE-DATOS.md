# Diccionario de Datos - Conector ELIT para WooCommerce

Este documento detalla los campos de datos utilizados en la integración entre la API de ELIT y WooCommerce, explicando el origen, la transformación y el destino de cada campo.

**Versión:** 1.1.0

---

## 1. Campos de la API de ELIT

Estos son los campos clave que se obtienen de la API de ELIT (`https://clientes.elit.com.ar/v1/api/productos`).

| Campo API         | Tipo        | Descripción                                                                                                | Ejemplo                                     |
| ----------------- | ----------- | ---------------------------------------------------------------------------------------------------------- | ------------------------------------------- |
| `id`              | `Integer`   | Identificador único del producto en el sistema de ELIT.                                                    | `34567`                                     |
| `codigo_producto` | `String`    | SKU (Stock Keeping Unit) del producto. **Campo clave para la sincronización.**                             | `"LENEX5WS0T36151"`                         |
| `nombre`            | `String`    | Nombre completo del producto.                                                                              | `"NOTEBOOK LENOVO V15 G3 IAP"`              |
| `marca`             | `String`    | Marca del producto.                                                                                        | `"LENOVO"`                                  |
| `categoria`         | `String`    | Categoría principal del producto.                                                                          | `"NOTEBOOKS"`                               |
| `sub_categoria`     | `String`    | Subcategoría del producto.                                                                                 | `"NOTEBOOK LENOVO"`                         |
| `precio`            | `Float`     | Precio base del producto (sin impuestos).                                                                  | `999.99`                                    |
| `pvp_ars`           | `Float`     | Precio de Venta al Público sugerido en Pesos Argentinos (ARS), con impuestos incluidos.                      | `1500.00`                                   |
| `pvp_usd`           | `Float`     | Precio de Venta al Público sugerido en Dólares Estadounidenses (USD), con impuestos incluidos.             | `1250.00`                                   |
| `moneda`            | `Integer`   | Moneda del precio base (`1` para ARS, `2` para USD).                                                       | `2`                                         |
| `stock_total`       | `Integer`   | Cantidad total de stock disponible.                                                                        | `50`                                        |
| `nivel_stock`       | `String`    | Nivel de disponibilidad del stock (`"alto"`, `"medio"`, `"bajo"`).                                     | `"alto"`                                    |
| `imagenes`          | `Array`     | Array de URLs de las imágenes del producto.                                                                | `["https://.../img1.jpg"]`                |
| `garantia`          | `String`    | Información sobre la garantía del producto.                                                                | `"12 meses"`                                |
| `gamer`             | `Boolean`   | Indica si el producto es de la línea "Gaming".                                                             | `false`                                     |

---

## 2. Campos en WooCommerce

Estos son los campos de un producto en WooCommerce y cómo se mapean desde los datos de ELIT.

### Campos Principales del Producto

| Campo WooCommerce        | Origen (Campo ELIT)                                    | Transformación y Lógica Aplicada                                                                                                                                                                                                                                                                                                                                                          |
| ------------------------ | ------------------------------------------------------ | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Nombre del Producto**  | `nombre`                                               | Se utiliza directamente el valor del campo `nombre` de ELIT.                                                                                                                                                                                                                                                                                                                              |
| **SKU**                  | `codigo_producto`                                      | Se utiliza el `codigo_producto` de ELIT, pero se le añade un prefijo personalizable (por defecto `ELIT_`) para evitar colisiones y facilitar la identificación. Ejemplo: `ELIT_LENEX5WS0T36151`.                                                                                                                                                                                               |
| **Precio Regular**       | `pvp_ars` o `pvp_usd`                                  | 1.  El sistema elige entre `pvp_ars` y `pvp_usd` según la configuración del plugin.<br>2.  Se aplica un porcentaje de `markup` (configurable) sobre este precio para calcular el precio de venta final. <br> `PrecioFinal = PrecioELIT * (1 + Markup / 100)`                                                                                                                                                           |
| **Stock**                | `stock_total` y `nivel_stock`                          | **Cantidad:** Se usa `stock_total`. <br> **Estado:** Se usa `nivel_stock`. Si es `bajo`, el producto se configura como "Permitir reservas" (`onbackorder`). Si es `alto` o `medio`, es `instock`. Si no hay stock, es `outofstock`.                                                                                                                                                 |
| **Categorías**           | `categoria`, `sub_categoria`, `marca`, `gamer`       | Se crea una jerarquía de categorías:<br>1.  Se añade `categoria` y `sub_categoria` como categorías del producto. <br>    **Regla Clave:** Si el nombre de `categoria` o `sub_categoria` es idéntico a `marca`, se ignora para evitar duplicados (ej: no crear una categoría "LENOVO").<br>2.  Se añade la marca siempre con el prefijo `"Marca: "` (ej: `"Marca: LENOVO"`).<br>3.  Si `gamer` es `true`, se añade la categoría `"Gaming"`. | 
| **Imágenes**             | `imagenes`                                             | **Integración con FIFU:** El sistema NO descarga las imágenes. <br>1.  La primera URL del array `imagenes` se guarda en el campo `fifu_image_url` para ser usada como imagen destacada.<br>2.  El resto de las URLs se guardan en `fifu_image_urls` para la galería del producto.                                                                                                                                                                 |
| **Descripción Corta**    | `marca`, `garantia`, `gamer`                           | Se construye dinámicamente para ofrecer un resumen rápido. Ejemplo: `"LENOVO | Garantía: 12 meses | 🎮 Gaming"`.                                                                                                                                                                                                                                                                               |

### Campos de Metadatos (Custom Fields)

Se guardan datos adicionales de ELIT como metadatos del producto para referencia futura.

| Meta Key               | Origen (Campo ELIT) | Descripción                                          |
| ---------------------- | ------------------- | ---------------------------------------------------- |
| `elit_id`              | `id`                | ID del producto en el sistema de ELIT.               |
| `elit_precio`          | `precio`            | Precio base de ELIT sin impuestos.                   |
| `elit_moneda`          | `moneda`            | Moneda del precio base.                              |
| `elit_link`            | `link`              | Enlace a la ficha técnica del producto en ELIT.      |

---
