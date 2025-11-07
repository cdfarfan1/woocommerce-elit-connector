# Conector ELIT para WooCommerce

Sincroniza automáticamente los productos del catálogo de ELIT con WooCommerce, incluyendo precios, imágenes y gestión de stock.

## Descripción

Este plugin conecta tu tienda de WooCommerce con el catálogo de productos de ELIT, un importante mayorista de tecnología en Argentina. La integración te permite:

*   **Sincronizar el catálogo completo:** Importa todos los productos de ELIT a tu WooCommerce con un solo clic.
*   **Actualizaciones automáticas:** Programa sincronizaciones periódicas (vía WP-Cron) para mantener precios y stock siempre al día.
*   **Conversión de moneda:** Si los precios de ELIT están en USD, el plugin puede convertirlos a ARS utilizando la cotización del dólar blue (requiere un plugin adicional).
*   **Márgenes de ganancia:** Añade un margen de ganancia porcentual sobre el costo del producto.
*   **Gestión de SKU:** Añade un prefijo personalizable a los SKUs para identificar fácilmente los productos de ELIT.

## Instalación

1.  **Descarga el plugin:** Obtén el archivo `.zip` desde la sección de [Releases en GitHub](https://github.com/cdfarfan1/woocommerce-elit-connector/releases).
2.  **Sube el plugin a WordPress:**
    *   En tu panel de WordPress, ve a `Plugins > Añadir nuevo`.
    *   Haz clic en `Subir Plugin` y selecciona el archivo `.zip` que descargaste.
    *   Activa el plugin después de la instalación.
3.  **Configura las credenciales:**
    *   Ve a `Ajustes > Conector ELIT`.
    *   Introduce tu `User ID` y `Token` de la API de ELIT.
    *   Guarda los cambios.

## Funcionalidades Principales

### Configuración de la API

Para que el plugin funcione, es fundamental que introduzcas las credenciales de la API que te proporciona ELIT.

1.  **Ve a `Ajustes > Conector ELIT`.**
2.  **Introduce tu `User ID` y `Token`.**
3.  **Haz clic en "Probar Conexión"** para verificar que las credenciales son correctas. Deberías recibir un mensaje de "Conexión exitosa".
4.  **Guarda los cambios.**

### Sincronización de Productos

El plugin ofrece dos formas de sincronizar productos:

#### 1. Sincronización Manual

Ideal para la primera vez o para forzar una actualización inmediata.

1.  **Ve a `Ajustes > Conector ELIT`.**
2.  **Haz clic en el botón "Sincronizar Productos Ahora".**

El proceso puede tardar varios minutos dependiendo de la cantidad de productos en el catálogo de ELIT. La sincronización se realiza en segundo plano, por lo que puedes salir de la página mientras se ejecuta.

#### 2. Sincronización Automática (Cron)

El plugin configura una tarea programada (WP-Cron) que ejecuta la sincronización automáticamente dos veces al día. Esto asegura que tu stock y precios se mantengan actualizados sin intervención manual.

*   **Activación:** El cron se activa automáticamente cuando el plugin es activado.
*   **Desactivación:** El cron se desactiva y elimina cuando el plugin es desactivado para no dejar tareas basura en tu sistema.

### Opciones de Sincronización

Desde la página de ajustes, puedes personalizar cómo se importan y actualizan los productos:

*   **Prefijo para SKU:** Añade un identificador único a los productos de ELIT (ej: `ELIT-12345`).
*   **Ajuste de Precios (USD a ARS):** Si tienes el plugin "Dolar Blue para WooCommerce" (o similar que actualice la cotización), esta opción convierte los precios de ELIT de USD a ARS.
*   **Margen de Ganancia:** Define un porcentaje (%) de ganancia que se añadirá sobre el precio de costo de ELIT.
*   **Opciones de Actualización:** Elige qué campos quieres que se actualicen en productos ya existentes (precio, stock, imágenes, categorías).

### Vista Previa de Producto

¿Quieres saber cómo se vería un producto de ELIT en tu tienda sin tener que importarlo? Usa la herramienta de vista previa:

1.  **Ve a `Ajustes > Conector ELIT`.**
2.  **Busca la sección "Vista Previa de Producto".**
3.  **Introduce el SKU de un producto de ELIT** (el código de producto, sin prefijos).
4.  **Haz clic en "Generar Vista Previa".**

El sistema te mostrará los datos del producto tal como se importarían en WooCommerce.

## Gestión de SKU

Para asegurar que puedas distinguir fácilmente los productos importados desde ELIT, el conector añade automáticamente un prefijo a cada SKU.

- **Prefijo por defecto:** `ELIT-`

Por ejemplo, si un producto en ELIT tiene el SKU `12345`, en WooCommerce se guardará como `ELIT_12345`.

### ¿Cómo cambiar el prefijo?

Puedes personalizar este prefijo desde el panel de administración de WordPress:

1.  **Ve a `Ajustes > Conector ELIT`.**
2.  **Busca el campo "Prefijo SKU".**
3.  **Introduce el prefijo que desees (por ejemplo, `MIPREFIJO-`) y guarda los cambios.**

## Troubleshooting

### La prueba de conexión se demora o falla

Si al hacer clic en **"Probar Conexión"** el proceso tarda demasiado y no responde, es probable que se deba a que el método de prueba anterior intentaba descargar una gran cantidad de datos, lo cual no es eficiente en un hosting compartido.

**Solución:** Este conector utiliza un método de prueba ligero que solo valida las credenciales, por lo que este problema no debería ocurrir. Si aun así falla, verifica que:

*   Tu `User ID` y `Token` son correctos.
*   Tu servidor no tiene un firewall que esté bloqueando las conexiones salientes a la API de ELIT.

### Las imágenes de los productos no aparecen

Si los productos se sincronizan pero no se muestran sus imágenes, puede deberse a varias razones. Este plugin utiliza el plugin **"Featured Image from URL (FIFU)"** como dependencia implícita para manejar las imágenes externas.

**Paso 1: Asegúrate de que FIFU esté instalado y activo**

Aunque el conector puede funcionar sin él, la forma más robusta de manejar las imágenes es con FIFU. Búscalo en el repositorio de WordPress e instálalo.

**Paso 2: Verifica la URL de la imagen en los campos personalizados**

1.  Edita un producto que debería tener una imagen de ELIT.
2.  Baja hasta encontrar una sección llamada **"Campos Personalizados"** (Custom Fields). Si no la ves, ve a la parte superior de la página, haz clic en **"Opciones de pantalla"** y asegúrate de que la casilla **"Campos Personalizados"** esté marcada.
3.  Busca un campo personalizado con el nombre `fifu_image_url`.
4.  **Verifica el valor de ese campo:**
    *   **Si el campo no existe:** El conector no está guardando la URL correctamente. Asegúrate de tener la última versión del conector.
    *   **Si el campo existe, pero está vacío:** La API de ELIT no está proporcionando una URL de imagen para ese producto.
    *   **Si el campo tiene una URL:** Cópiala y pégala en una nueva pestaña del navegador. Si la imagen no carga, el problema es que la URL de la imagen de ELIT no es accesible.

**Paso 3: Vuelve a sincronizar un producto**

Si instalaste FIFU o solucionaste un problema de URL, intenta sincronizar de nuevo un producto específico o ejecuta la sincronización general para que los cambios se apliquen.
