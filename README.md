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
      
      ## 📋 Requisitos
      
      - WordPress 5.0 o superior
      - WooCommerce 4.0 o superior
      - PHP 7.4 o superior
      - Credenciales de acceso a la API de ELIT (User ID y Token)
      - Plugin FIFU (Featured Image From URL) para manejo de imágenes
      
      ## 🔧 Instalación
      
      ##  troubleshooting

      ### soluciona el problema de las imágenes

      Para solucionar el problema de las imágenes, es muy probable que necesites instalar y activar el plugin **FIFU (Featured Image From URL)**.

      1.  **Ve a tu panel de WordPress.**
      2.  **Navega a `Plugins > Añadir nuevo`.**
      3.  **Busca "Featured Image from URL".**
      4.  **Instala y activa el plugin.**

      Una vez activado, las imágenes de los productos de ELIT deberían empezar a mostrarse correctamente. El conector utiliza FIFU para manejar las imágenes externas, incluyendo formatos modernos como `.webp`.

      Si el problema persiste después de instalar FIFU, comprueba lo siguiente:

      -   **Permisos de la carpeta:** Asegúrate de que la carpeta `wp-content/uploads` tiene los permisos de escritura correctos.
      -   **URLs de las imágenes:** Verifica que las URLs de las imágenes proporcionadas por la API de ELIT sean accesibles públicamente.
      