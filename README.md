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

### Opción 1: Instalación desde GitHub

```bash
# Clonar el repositorio
git clone https://github.com/cdfarfan1/woocommerce-elit-connector.git

# Navegar al directorio
cd woocommerce-elit-connector

# Copiar a la carpeta de plugins de WordPress
cp -r . /ruta/a/tu/sitio/wp-content/plugins/woocommerce-elit-connector/
```

### Opción 2: Instalación manual

1. Descarga el plugin desde [GitHub Releases](https://github.com/cristianfarfan/woocommerce-elit-connector/releases)
2. Sube la carpeta `woocommerce-elit-connector` a `/wp-content/plugins/`
3. Activa el plugin desde el panel de administración de WordPress
4. Ve a **Ajustes > Conector ELIT** para configurar

## 🔄 Control de Versiones

### Repositorio GitHub

**URL del Repositorio:** https://github.com/cdfarfan1/woocommerce-elit-connector  
**Rama Principal:** `main`  
**Última Versión:** 1.0.0  

### Comandos de Control de Versiones

```bash
# Clonar el repositorio
git clone https://github.com/cdfarfan1/woocommerce-elit-connector.git

# Crear nueva rama de desarrollo
git checkout -b feature/nueva-funcionalidad

# Agregar cambios
git add .
git commit -m "feat: Agregar nueva funcionalidad de sincronización"

# Subir cambios
git push origin feature/nueva-funcionalidad

# Crear Pull Request en GitHub
# Merge a main después de revisión
```

### Tags de Versión

```bash
# Crear tag para nueva versión
git tag -a v1.0.0 -m "Release version 1.0.0 - Conector ELIT inicial"
git push origin v1.0.0

# Listar tags
git tag -l

# Verificar tag
git show v1.0.0
```

### Estructura de Commits

- `feat:` Nueva funcionalidad
- `fix:` Corrección de bugs
- `docs:` Documentación
- `style:` Formato de código
- `refactor:` Refactorización
- `test:` Pruebas
- `chore:` Tareas de mantenimiento

## ⚙️ Configuración

### Credenciales de ELIT

1. **User ID**: Tu ID de usuario en ELIT (ejemplo: 24560)
2. **Token**: Tu token de acceso a la API de ELIT (ejemplo: z9qrpjjgnwq)

### Configuraciones adicionales

- **Prefijo SKU**: Prefijo para identificar productos de ELIT (por defecto: ELIT_)
- **Sincronizar en USD**: Usar precios en dólares en lugar de pesos
- **Porcentaje de Markup**: Markup a aplicar a los precios (por defecto: 35%)
- **Intervalo de sincronización**: Frecuencia de sincronización automática

## 🔄 Uso

### Sincronización Manual

1. Ve a **Ajustes > Conector ELIT**
2. Haz clic en **"Actualizar todo"** para sincronizar todos los productos
3. Usa **"Probar Conexión ELIT"** para verificar credenciales

### Sincronización Automática

El plugin sincroniza automáticamente según el intervalo configurado:

- ✅ **Crea** productos nuevos de ELIT
- ✅ **Actualiza** productos existentes (precios, stock, información)
- ✅ **Marca sin stock** productos que no tienen existencia en ELIT
- ✅ **Elimina** productos que ya no están disponibles en ELIT

## 📊 Campos Sincronizados

### Información del Producto
- Nombre del producto
- SKU (con prefijo configurable)
- Precio (PVP en ARS o USD según configuración)
- Stock disponible
- Peso
- EAN
- Marca
- Garantía

### Categorización
- Categoría principal de ELIT
- Subcategoría de ELIT
- Marca como categoría adicional
- Etiqueta "Gaming" para productos gamer

### Imágenes
- Imágenes principales del producto
- Miniaturas como respaldo

## 🔗 API de ELIT

El plugin se conecta a la API oficial de ELIT:

```
POST https://clientes.elit.com.ar/v1/api/productos?limit=100
```

Con autenticación:
```json
{
  "user_id": 24560,
  "token": "z9qrpjjgnwq"
}
```

## 📁 Estructura del Plugin

```
woocommerce-elit-connector/
├── woocommerce-elit-connector.php    # Archivo principal
├── includes/
│   ├── elit-api.php                  # Integración con API ELIT
│   ├── elit-sync-callback.php        # Funciones de sincronización
│   ├── admin-hooks.php               # Hooks de administración
│   ├── settings.php                  # Página de configuración
│   ├── utils.php                     # Utilidades y logging
│   ├── price-calculator.php          # Cálculo de precios y markup
│   ├── product-sync.php              # Sincronización de productos
│   ├── modals.php                    # Modales de interfaz
│   ├── activation.php                # Activación del plugin
│   └── cron-hooks.php                # Tareas programadas
├── assets/
│   ├── css/admin.css                 # Estilos de administración
│   ├── js/admin.js                   # JavaScript de administración
│   └── icon-128x128.png              # Icono del plugin
├── README.md                         # Este archivo
└── INSTRUCCIONES-INSTALACION.md     # Guía de instalación
```

## 🐛 Solución de Problemas

### Error: "Credenciales no configuradas"
- Verifica que hayas ingresado el User ID y Token correctamente
- Asegúrate de guardar la configuración

### Error: "No se encontraron productos"
- Verifica tus credenciales con ELIT
- Comprueba que tu cuenta tenga productos asignados

### Imágenes no se cargan
- Instala y activa el plugin FIFU (Featured Image From URL)
- Verifica que las URLs de imágenes de ELIT sean accesibles

## 📝 Logs

Los logs se guardan en:
- `wp-content/uploads/newbytes-connector.log`
- WordPress Debug Log (si está habilitado)

## 🤝 Contribuir

1. Fork el repositorio
2. Crea una rama para tu feature (`git checkout -b feature/nueva-funcionalidad`)
3. Commit tus cambios (`git commit -am 'Agregar nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/nueva-funcionalidad`)
5. Crea un Pull Request

## 📄 Licencia

Este plugin está licenciado bajo la Licencia GPL v2 o posterior.

## 📞 Soporte y Contacto

**Desarrollador:** Cristian Farfan  
**Empresa:** Pragmatic Solutions  
**Email:** info@pragmaticsolutions.com.ar  
**Sitio Web:** https://www.pragmaticsolutions.com.ar  
**GitHub:** https://github.com/cristianfarfan  
**LinkedIn:** https://linkedin.com/in/cristianfarfan  

### Canales de Soporte

1. **GitHub Issues:** https://github.com/cdfarfan1/woocommerce-elit-connector/issues
2. **Email:** info@pragmaticsolutions.com.ar
3. **Documentación:** Manuales y guías en el repositorio
4. **Pull Requests:** Contribuciones bienvenidas

### Para soporte técnico:
- Revisa los logs del plugin
- Verifica las credenciales de ELIT
- Crea un issue en GitHub con detalles del problema

## 🏷️ Versiones

### v1.0.0
- Versión inicial
- Integración completa con API de ELIT
- Sincronización automática de productos
- Soporte para precios ARS/USD
- Gestión de categorías e imágenes

---

**Desarrollado para integración con ELIT - Mayorista de tecnología**