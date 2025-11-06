# Conector ELIT para WooCommerce

Plugin de WordPress que sincroniza automáticamente productos desde la API de ELIT a tu tienda WooCommerce.

[![GitHub release](https://img.shields.io/github/release/cdfarfan1/woocommerce-elit-connector.svg)](https://github.com/cdfarfan1/woocommerce-elit-connector/releases)
[![GitHub issues](https://img.shields.io/github/issues/cdfarfan1/woocommerce-elit-connector.svg)](https://github.com/cdfarfan1/woocommerce-elit-connector/issues)
[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0)
[![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)](https://wordpress.org/)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-4.0%2B-purple.svg)](https://woocommerce.com/)

## 🔗 Enlaces

- **Repositorio GitHub:** https://github.com/cdfarfan1/woocommerce-elit-connector
- **Sitio Web:** https://www.pragmaticsolutions.com.ar
- **Soporte:** https://github.com/cdfarfan1/woocommerce-elit-connector/issues

## 🚀 Características

- **Sincronización automática** de productos desde la API de ELIT
- **Gestión de precios** con markup personalizable
- **Soporte para múltiples monedas** (ARS/USD)
- **Sincronización de imágenes** automática desde ELIT
- **Categorización inteligente** basada en datos de ELIT
- **Gestión de stock** en tiempo real
- **Programación de sincronización** (cada 1-12 horas)
- **Interfaz de administración** intuitiva
- **Vista previa de productos:** Permite previsualizar cómo se importará un producto de ELIT antes de la sincronización.

## 📋 Requisitos

- WordPress 5.0 o superior
- WooCommerce 4.0 o superior
- PHP 7.4 o superior
- Credenciales de acceso a la API de ELIT (User ID y Token)
- Plugin FIFU (Featured Image From URL) para manejo de imágenes

## 🔧 Instalación

Puedes instalar este plugin de dos maneras:

### Opción 1: Instalación desde un archivo ZIP

1.  **Descarga el plugin** desde el repositorio de GitHub haciendo clic en `Code > Download ZIP`.
2.  **Ve a tu panel de WordPress.**
3.  **Navega a `Plugins > Añadir nuevo`.**
4.  **Haz clic en "Subir plugin"** y selecciona el archivo ZIP que descargaste.
5.  **Instala y activa el plugin.**

### Opción 2: Instalación manual (vía FTP/SFTP)

1.  **Descarga y descomprime** el repositorio del plugin.
2.  **Sube la carpeta** `woocommerce-elit-connector` completa a tu directorio de plugins en WordPress:
    ```
    /wp-content/plugins/
    ```
3.  **Ve a tu panel de WordPress**, navega a `Plugins > Plugins instalados`.
4.  **Busca "Conector ELIT" y haz clic en "Activar".**

### Dependencia Adicional

**Importante:** Después de activar el conector, asegúrate de instalar y activar el plugin **FIFU (Featured Image From URL)**. Es un requisito para que las imágenes de los productos se muestren correctamente.

## ⚙️ Optimización para Hosting Compartido

Se han tenido en cuenta las limitaciones de los hostings compartidos (`memory_limit: 512M`, `max_execution_time: 300s`) para garantizar un funcionamiento estable.

-   **Procesamiento por Lotes:** La sincronización se procesa en lotes pequeños (20 productos por defecto) para evitar exceder el tiempo máximo de ejecución.
-   **Uso de Memoria Optimizado:** El plugin está diseñado para un consumo de memoria reducido, liberando recursos después de procesar cada lote para prevenir errores de memoria agotada.
-   **Consultas a la API Controladas:** Las solicitudes a la API de ELIT se gestionan en grupos más pequeños (50 productos por petición) para asegurar la estabilidad del servidor.
-   **Ajustes Flexibles:** Los valores de lotes y límites se han ajustado en el código para alinearse con estas limitaciones, basándonos en las optimizaciones previas registradas en `ERROR-LOG.md`.

## 🔧 Compatibilidad con WooCommerce (HPOS)

Entendemos que la compatibilidad con las nuevas características de WooCommerce, como el **Almacenamiento de Pedidos de Alto Rendimiento (HPOS)**, es crucial.

El **Conector ELIT** ha sido desarrollado siguiendo las prácticas recomendadas por WooCommerce para asegurar su compatibilidad con HPOS. El plugin se centra en la gestión de **productos**, utilizando funciones que son compatibles con la nueva arquitectura.

Si ves una advertencia sobre "plugins incompatibles" en tu panel de WordPress, es probable que se deba a otro plugin que aún no ha sido actualizado.

### ¿Cómo verificar la compatibilidad de tus plugins?

Puedes ver qué plugins no son compatibles con HPOS desde los ajustes de WooCommerce:

1.  Ve a **WooCommerce > Ajustes > Avanzado > Características**.
2.  Busca la opción **Almacenamiento de pedidos de alto rendimiento**.
3.  Aquí verás una lista de los plugins que son compatibles y los que no.

Esto te ayudará a identificar exactamente qué plugin está causando la advertencia.

## 🔧 Personalización del Prefijo de SKU

Para asegurar que puedas distinguir fácilmente los productos importados desde ELIT, el conector añade automáticamente un prefijo a cada SKU.

- **Prefijo por defecto:** `ELIT_`

Por ejemplo, si un producto en ELIT tiene el SKU `12345`, en WooCommerce se guardará como `ELIT_12345`.

### ¿Cómo cambiar el prefijo?

Puedes personalizar este prefijo desde el panel de administración de WordPress:

1.  **Ve a `Ajustes > Conector NB`.**
2.  **Busca el campo "Prefijo SKU".**
3.  **Introduce el prefijo que desees (por ejemplo, `MIPREFIJO_`) y guarda los cambios.**

##  Troubleshooting

### La prueba de conexión se demora o falla

Si al hacer clic en **"Probar Conexión"** el proceso tarda demasiado y no responde, es probable que se deba a que el método de prueba anterior intentaba descargar una gran cantidad de datos, lo cual no es eficiente en un hosting compartido.

**Solución:** Se ha implementado una prueba de conexión optimizada. En lugar de descargar datos, ahora se realiza una consulta rápida y ligera que solo verifica el estado de la conexión y la validez de las credenciales. Esta mejora ya está incluida en la última versión del plugin.

Si el problema persiste:

1.  **Verifica las credenciales:** Asegúrate de que el `User ID` y el `Token` son correctos.
2.  **Consulta a tu proveedor de hosting:** Pregúntales si existe alguna restricción de firewall que pueda estar bloqueando las conexiones salientes hacia `clientes.elit.com.ar`.

### Las imágenes no se muestran (aún con FIFU instalado)

Si ya instalaste y activaste el plugin **FIFU (Featured Image From URL)** pero las imágenes siguen sin aparecer, sigue estos pasos:

**Paso 1: Revisa la configuración de FIFU**

1.  En tu panel de WordPress, ve a **FIFU > Ajustes**.
2.  Asegúrate de que la opción **"Hide Featured Media"** (Ocultar medio destacado) esté **desactivada**.
3.  Ve a la pestaña **WooCommerce** dentro de los ajustes de FIFU.
4.  Verifica que las opciones **"Disable Featured Image"** (Desactivar imagen destacada) y **"Disable Product Gallery"** (Desactivar galería de productos) estén **desactivadas**.
5.  Guarda los cambios y refresca la página de un producto para ver si las imágenes aparecen.

**Paso 2: Verifica la URL de la imagen en un producto**

Vamos a confirmar que la URL de la imagen se está guardando correctamente.

1.  Edita un producto que debería tener una imagen de ELIT.
2.  Baja hasta encontrar una sección llamada **"Campos Personalizados"** (Custom Fields). Si no la ves, ve a la parte superior de la página, haz clic en **"Opciones de pantalla"** y asegúrate de que la casilla **"Campos Personalizados"** esté marcada.
3.  Busca un campo personalizado con el nombre `fifu_image_url`.
4.  **Verifica el valor de ese campo:**
    *   **Si el campo no existe:** El conector no está guardando la URL correctamente. Asegúrate de tener la última versión del conector.
    *   **Si el campo existe, pero está vacío:** La API de ELIT no está proporcionando una URL de imagen para ese producto.
    *   **Si el campo tiene una URL:** Cópiala y pégala en una nueva pestaña del navegador. Si la imagen no carga, el problema es que la URL de la imagen de ELIT no es accesible.

**Paso 3: Vuelve a sincronizar un producto**

Si has hecho cambios en la configuración, es una buena idea forzar la resincronización de un producto.

1.  Ve a **Ajustes > Conector NB**.
2.  Usa la herramienta **"Vista Previa de Producto"** con el SKU del producto que estás revisando para confirmar que la API devuelve una URL de imagen válida.
3.  Luego, en la misma página, haz clic en el botón **"Actualizar todo"** para forzar una nueva sincronización. Esto debería aplicar los nuevos ajustes.

**Paso 4: Prueba de conflicto de tema/plugins**

Si nada de lo anterior funciona, es posible que otro plugin o tu tema estén causando un conflicto.

1.  **Cambia temporalmente a un tema por defecto** de WordPress, como "Storefront" o "Twenty Twenty-Three".
2.  Revisa si las imágenes aparecen. Si lo hacen, el problema está en tu tema.
3.  Si no aparecen, desactiva todos los demás plugins excepto **WooCommerce**, **Conector ELIT** y **FIFU**. Si las imágenes aparecen ahora, ve activando los demás plugins uno por uno hasta que encuentres al culpable.
