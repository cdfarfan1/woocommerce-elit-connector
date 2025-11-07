## Instrucciones de Instalación y Configuración

Sigue estos pasos para instalar y configurar correctamente el Conector ELIT en tu tienda de WooCommerce.

### 1. Instalación del Plugin

1.  **Descarga el archivo `.zip`** del plugin desde el repositorio de GitHub.
2.  En tu panel de WordPress, ve a **Plugins > Añadir nuevo**.
3.  Haz clic en **Subir Plugin** y selecciona el archivo `.zip` que descargaste.
4.  Activa el plugin.

### 2. Configuración Inicial

1.  Ve a **Ajustes > Conector ELIT**.
2.  Introduce tu **User ID** y **Token** proporcionados por ELIT.
3.  Haz clic en **Probar Conexión** para asegurarte de que las credenciales son válidas.
4.  Configura el resto de las opciones según tus necesidades (prefijo SKU, margen, etc.).
5.  **Guarda los cambios**.

### 3. Primera Sincronización

Para poblar tu tienda con los productos de ELIT por primera vez, realiza una sincronización manual:

1.  Ve a **Ajustes > Conector ELIT**.
2.  Haz clic en el botón **"Sincronizar Productos Ahora"**.

El proceso puede tardar varios minutos. Se ejecutará en segundo plano.

---

### Solución de Problemas Comunes

**Problema: El menú "Conector ELIT" no aparece en "Ajustes".**

*   **Causa:** Generalmente, esto ocurre por un conflicto con otro plugin o con el tema activo.
*   **Solución:** Realiza una prueba de conflictos:
    1.  **Desactiva todos los demás plugins** excepto WooCommerce y el Conector ELIT.
    2.  Comprueba si el menú aparece. Si es así, ve reactivando los demás plugins uno por uno hasta que el menú desaparezca de nuevo. El último plugin que activaste es el que causa el conflicto.
    3.  Si desactivar los plugins no funciona, cambia temporalmente a un tema por defecto de WordPress (como "Twenty Twenty-Three") y comprueba si el menú aparece.

**Problema: La prueba de conexión falla o tarda mucho.**

*   **Causa:** Credenciales incorrectas o un bloqueo por parte del firewall de tu hosting.
*   **Solución:** Verifica tus credenciales y contacta con tu proveedor de hosting para asegurarte de que no están bloqueando las conexiones salientes a la API de ELIT.

**Problema: Las imágenes de los productos no se muestran.**

*   **Causa:** El plugin confía en **Featured Image from URL (FIFU)** para manejar imágenes externas. Si no está instalado o bien configurado, las imágenes pueden no mostrarse.
*   **Solución:** Instala y activa el plugin FIFU desde el repositorio de WordPress. Luego, vuelve a sincronizar los productos.
