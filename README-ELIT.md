# Conector ELIT para WooCommerce

Plugin de WordPress que sincroniza automáticamente productos desde la API de ELIT a tu tienda WooCommerce.

## Características

- **Sincronización automática** de productos desde ELIT
- **Gestión de precios** con markup personalizable
- **Soporte para múltiples monedas** (ARS/USD)
- **Sincronización de imágenes** automática
- **Categorización inteligente** basada en datos de ELIT
- **Gestión de stock** en tiempo real
- **Programación de sincronización** (cada 1-12 horas)

## Requisitos

- WordPress 5.0 o superior
- WooCommerce 4.0 o superior
- PHP 7.4 o superior
- Credenciales de acceso a la API de ELIT (User ID y Token)
- Plugin FIFU (Featured Image From URL) para manejo de imágenes

## Instalación

1. Sube el plugin a la carpeta `/wp-content/plugins/`
2. Activa el plugin desde el panel de administración de WordPress
3. Ve a **Ajustes > Conector ELIT**
4. Configura tus credenciales de ELIT

## Configuración

### Credenciales de ELIT

1. **User ID**: Tu ID de usuario en ELIT (ejemplo: 24560)
2. **Token**: Tu token de acceso a la API de ELIT

### Configuraciones adicionales

- **Prefijo SKU**: Prefijo para identificar productos de ELIT (por defecto: ELIT_)
- **Sincronizar en USD**: Usar precios en dólares en lugar de pesos
- **Porcentaje de Markup**: Markup a aplicar a los precios (por defecto: 35%)
- **Intervalo de sincronización**: Frecuencia de sincronización automática

## Uso

### Sincronización Manual

1. Ve a **Ajustes > Conector ELIT**
2. Haz clic en **"Actualizar todo"** para sincronizar todos los productos
3. Usa **"Actualizar descripciones"** para sincronizar solo descripciones

### Sincronización Automática

El plugin sincroniza automáticamente según el intervalo configurado. Los productos se:

- **Crean** si no existen en WooCommerce
- **Actualizan** si ya existen (precios, stock, información)
- **Eliminan** si ya no están disponibles en ELIT

### Prueba de Conexión

Usa el botón **"Probar Conexión ELIT"** en la página de configuración para verificar que tus credenciales funcionan correctamente.

## Campos Sincronizados

### Información del Producto

- Nombre del producto
- SKU (con prefijo configurable)
- Precio (PVP en ARS o USD)
- Stock disponible
- Peso
- EAN
- Marca
- Garantía

### Categorización

- Categoría principal
- Subcategoría
- Marca (como categoría)
- Etiqueta "Gaming" para productos gamer

### Imágenes

- Imágenes principales del producto
- Miniaturas como respaldo

### Atributos

- Características técnicas del producto
- Se muestran en la descripción corta

## Estructura de Datos ELIT

El plugin trabaja con los siguientes campos de la API de ELIT:

```json
{
  "id": "Código único de producto ELIT",
  "codigo_alfa": "Código alfanumérico ELIT",
  "codigo_producto": "Código de producto o SKU",
  "nombre": "Nombre del producto",
  "categoria": "Categoría del producto",
  "sub_categoria": "Subcategoría del producto",
  "marca": "Marca del producto",
  "precio": "Precio de costo",
  "pvp_usd": "Precio venta público en dólares",
  "pvp_ars": "Precio venta público en pesos",
  "stock_total": "Stock total disponible",
  "peso": "Peso del producto",
  "ean": "Código EAN",
  "garantia": "Información de garantía",
  "imagenes": ["Array de URLs de imágenes"],
  "atributos": ["Array de características"],
  "gamer": "Booleano si es producto gamer"
}
```

## Precios y Markup

El plugin maneja los precios de la siguiente manera:

1. **Precio base**: Usa `pvp_usd` o `pvp_ars` según configuración
2. **Fallback**: Si no hay PVP, usa `precio` + impuestos
3. **Markup**: Aplica el porcentaje configurado al precio final

## Logs y Depuración

Los logs se guardan en:
- Archivo: `wp-content/uploads/newbytes-connector.log`
- WordPress Debug Log (si está habilitado)

## Soporte

Para soporte técnico:
- Revisa los logs del plugin
- Verifica las credenciales de ELIT
- Asegúrate de que WooCommerce esté actualizado

## Compatibilidad

- **WordPress**: 5.0+
- **WooCommerce**: 4.0+
- **PHP**: 7.4+
- **Plugins requeridos**: FIFU (Featured Image From URL)

## Limitaciones

- Máximo 100 productos por solicitud a la API de ELIT
- Tiempo máximo de ejecución: 30 minutos por sincronización
- Memoria máxima: 2GB durante sincronización

## Changelog

### Versión 1.0.0
- Versión inicial
- Integración completa con API de ELIT
- Sincronización automática de productos
- Soporte para precios ARS/USD
- Gestión de categorías e imágenes
