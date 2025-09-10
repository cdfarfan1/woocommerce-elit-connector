# 📖 Manual de Uso - Conector ELIT para WooCommerce

## 🎯 Introducción

El **Conector ELIT** es un plugin de WordPress que sincroniza automáticamente productos desde la API de ELIT (Mayorista de tecnología) hacia tu tienda WooCommerce. Este manual te guiará paso a paso para configurar y usar el plugin correctamente.

---

## 📋 Tabla de Contenidos

1. [Requisitos del Sistema](#-requisitos-del-sistema)
2. [Instalación](#-instalación)
3. [Configuración Inicial](#-configuración-inicial)
4. [Uso del Plugin](#-uso-del-plugin)
5. [Gestión de Productos](#-gestión-de-productos)
6. [Configuración Avanzada](#-configuración-avanzada)
7. [Sincronización Automática](#-sincronización-automática)
8. [Solución de Problemas](#-solución-de-problemas)
9. [Mantenimiento](#-mantenimiento)

---

## 🔧 Requisitos del Sistema

### Requisitos Mínimos:
- **WordPress**: 5.0 o superior
- **WooCommerce**: 4.0 o superior  
- **PHP**: 7.4 o superior
- **MySQL**: 5.6 o superior
- **Memoria PHP**: Mínimo 128MB (recomendado 256MB)
- **Tiempo de ejecución PHP**: Mínimo 60 segundos (recomendado 300 segundos)

### Plugins Requeridos:
- **WooCommerce** (activo y configurado)
- **FIFU (Featured Image From URL)** (para manejo de imágenes)

### Credenciales Necesarias:
- **User ID de ELIT** (ejemplo: 24560)
- **Token de API de ELIT** (ejemplo: z9qrpjjgnwq)

---

## 📦 Instalación

### Método 1: Descarga desde GitHub
1. Ve a: https://github.com/cdfarfan1/woocommerce-elit-connector
2. Haz clic en **"Code"** → **"Download ZIP"**
3. Extrae el archivo ZIP
4. Sube la carpeta `woocommerce-elit-connector` a `/wp-content/plugins/`

### Método 2: Clonación Git
```bash
cd /wp-content/plugins/
git clone https://github.com/cdfarfan1/woocommerce-elit-connector.git
```

### Activación:
1. Ve a **WordPress Admin** → **Plugins**
2. Busca **"Conector ELIT"**
3. Haz clic en **"Activar"**

---

## ⚙️ Configuración Inicial

### Paso 1: Acceder a la Configuración
1. En el admin de WordPress, ve a **Ajustes** → **Conector ELIT**
2. Verás la página de configuración del plugin

### Paso 2: Configurar Credenciales de ELIT
1. **ELIT User ID**: Ingresa tu ID de usuario (ejemplo: 24560)
2. **ELIT Token**: Ingresa tu token de API (ejemplo: z9qrpjjgnwq)
3. Haz clic en **"Probar Conexión ELIT"** para verificar

### Paso 3: Configurar Opciones Básicas
1. **Prefijo SKU**: Deja "ELIT_" o personaliza (ejemplo: "ELT_")
2. **Sincronizar en USD**: Marca si prefieres precios en dólares
3. **Porcentaje de Markup**: Configura tu margen (por defecto: 35%)

### Paso 4: Configurar Sincronización
1. **Intervalo de sincronización**: Elige frecuencia (1-12 horas)
2. **Descripción corta**: Texto adicional para todos los productos (opcional)

### Paso 5: Guardar Configuración
1. Haz clic en **"Guardar cambios"**
2. Verifica que aparezca mensaje de confirmación

---

## 🚀 Uso del Plugin

### Primera Sincronización

#### Opción A: Sincronización Manual Completa
1. En la página de configuración, haz clic en **"Actualizar todo"**
2. El proceso puede tardar varios minutos (depende de la cantidad de productos)
3. Verás un resumen con productos creados/actualizados

#### Opción B: Sincronización Solo de Descripciones
1. Haz clic en **"Actualizar descripciones"**
2. Útil para actualizar solo información de productos existentes

### Verificar Resultados
1. Ve a **WooCommerce** → **Productos**
2. Busca productos con SKUs que empiecen con tu prefijo (ejemplo: "ELIT_")
3. Verifica que tengan:
   - ✅ Precios correctos con markup aplicado
   - ✅ Stock actualizado
   - ✅ Categorías asignadas
   - ✅ Imágenes cargadas

---

## 📦 Gestión de Productos

### Tipos de Sincronización

#### 🆕 Productos Nuevos
- **Se crean automáticamente** en WooCommerce
- **Se asignan categorías** basadas en datos de ELIT
- **Se descargan imágenes** desde servidores de ELIT
- **Se aplica markup** al precio base

#### 🔄 Productos Existentes  
- **Se actualizan precios** con nuevo markup
- **Se actualiza stock** en tiempo real
- **Se mantienen configuraciones personalizadas** de WooCommerce

#### ❌ Productos Discontinuados
- **Se marcan como sin stock** si ya no están en ELIT
- **Se mantienen en WooCommerce** para historial de pedidos

### Estados de Stock

#### 🟢 Con Stock (`instock`)
- **Condición**: `stock_total > 0` en ELIT
- **Acción**: Producto disponible para compra
- **WooCommerce**: Stock normal

#### 🟡 Stock Bajo (`onbackorder`)  
- **Condición**: `stock_total = 0` pero `nivel_stock = "bajo"`
- **Acción**: Permite pedidos con advertencia
- **WooCommerce**: Pedidos pendientes habilitados

#### 🔴 Sin Stock (`outofstock`)
- **Condición**: `stock_total = 0` y `nivel_stock ≠ "bajo"`
- **Acción**: No permite pedidos
- **WooCommerce**: Producto agotado

### Precios y Markup

#### ⚠️ IMPORTANTE - Precios PVP de ELIT:
Los campos `pvp_ars` y `pvp_usd` de ELIT **ya son precios de venta público** que incluyen el margen de ELIT. 

#### Opciones de Configuración:

##### Opción 1: Precios PVP Directos (Recomendado)
- **Configuración**: "Aplicar Markup sobre PVP" = NO
- **Markup adicional**: 0%
- **Resultado**: Usar precios de ELIT directamente
- **Ejemplo**: PVP ELIT $1,000 → Precio final $1,000

##### Opción 2: PVP + Markup Adicional
- **Configuración**: "Aplicar Markup sobre PVP" = SÍ
- **Markup adicional**: Tu margen extra (ej: 10%)
- **Resultado**: PVP + tu margen adicional
- **Ejemplo**: PVP ELIT $1,000 + 10% → Precio final $1,100

#### Cálculo de Precios:
1. **Precio base**: `pvp_ars` o `pvp_usd` (ya incluyen margen ELIT)
2. **Markup adicional**: Solo si está configurado
3. **Precio final**: Se redondea a 2 decimales

---

## 🔧 Configuración Avanzada

### Opciones de Moneda

#### Sincronizar en Pesos (ARS)
- **Campo usado**: `pvp_ars` de ELIT
- **Recomendado para**: Tiendas en Argentina
- **Ventaja**: Precios estables en moneda local

#### Sincronizar en Dólares (USD)
- **Campo usado**: `pvp_usd` de ELIT  
- **Recomendado para**: Tiendas internacionales
- **Ventaja**: Precios en moneda fuerte

### Configuración de Categorías

El plugin crea automáticamente categorías basadas en:
1. **Categoría principal** de ELIT
2. **Subcategoría** de ELIT
3. **Marca** como categoría adicional
4. **"Gaming"** para productos gamer

#### Ejemplo de Categorización:
- **Producto**: AIO Lenovo Gaming
- **Categorías creadas**: 
  - Computadoras
  - All In One
  - Marca: LENOVO
  - Gaming

### Gestión de Imágenes

#### Fuentes de Imágenes (por prioridad):
1. **Array `imagenes`** (imágenes principales)
2. **Array `miniaturas`** (como fallback)

#### Proceso:
1. **Descarga automática** desde servidores de ELIT
2. **Primera imagen** se establece como imagen destacada
3. **Imágenes adicionales** van a la galería del producto

---

## ⏰ Sincronización Automática

### Configuración de Intervalos

#### Intervalos Disponibles:
- **1 hora**: Para tiendas con alta rotación
- **2-4 horas**: Equilibrio entre actualización y rendimiento
- **6-12 horas**: Para tiendas con productos estables

#### Recomendaciones:
- **Tienda nueva**: 2-3 horas (para capturar cambios rápido)
- **Tienda establecida**: 6-8 horas (menor carga del servidor)
- **Productos estables**: 12 horas (mínima carga)

### Programación del Cron

El plugin usa el sistema de cron de WordPress:
```php
wp_schedule_event(time(), 'elit_interval', 'elit_cron_sync_event');
```

#### Verificar Cron:
1. Usa un plugin como "WP Crontrol" para verificar tareas programadas
2. Busca la tarea `elit_cron_sync_event`

---

## 🔍 Solución de Problemas

### Error: "Credenciales no configuradas"

#### Causa:
- User ID o Token vacíos/incorrectos

#### Solución:
1. Verifica credenciales en **Ajustes** → **Conector ELIT**
2. Usa **"Probar Conexión ELIT"** para verificar
3. Contacta a ELIT si las credenciales no funcionan

### Error: "No se encontraron productos"

#### Posibles Causas:
- Credenciales incorrectas
- Cuenta sin productos asignados
- Problemas de conectividad

#### Solución:
1. Verifica credenciales con ELIT
2. Revisa logs en `wp-content/uploads/newbytes-connector.log`
3. Verifica conectividad a `https://clientes.elit.com.ar`

### Error: "Tiempo de ejecución excedido"

#### Causa:
- Muchos productos para sincronizar
- Límites de PHP muy bajos

#### Solución:
1. Aumenta `max_execution_time` en PHP
2. Aumenta `memory_limit` en PHP
3. Ejecuta sincronización en horarios de menor tráfico
4. Reduce frecuencia de sincronización

### Imágenes no se cargan

#### Causa:
- Plugin FIFU no instalado
- URLs de imágenes no accesibles
- Permisos de carpeta uploads

#### Solución:
1. Instala y activa **FIFU (Featured Image From URL)**
2. Verifica permisos de `/wp-content/uploads/`
3. Comprueba que las URLs de ELIT sean accesibles

### Productos duplicados

#### Causa:
- Cambio en prefijo SKU
- Múltiples sincronizaciones con configuraciones diferentes

#### Solución:
1. Mantén el mismo prefijo SKU siempre
2. Elimina productos duplicados manualmente
3. Ejecuta sincronización limpia

---

## 🛠️ Mantenimiento

### Logs del Sistema

#### Ubicación del Log:
```
/wp-content/uploads/newbytes-connector.log
```

#### Información en Logs:
- Conexiones a API de ELIT
- Productos procesados
- Errores y warnings
- Tiempos de sincronización

#### Ejemplo de Log:
```
[2025-01-09 10:30:00] [INFO] Iniciando sincronización con ELIT API
[2025-01-09 10:30:02] [INFO] Obtenidos 100 productos de ELIT
[2025-01-09 10:30:15] [INFO] Sincronización completada: 50 creados, 45 actualizados
```

### Monitoreo de Rendimiento

#### Métricas Importantes:
- **Tiempo de sincronización**: Debe ser < 5 minutos para 100 productos
- **Memoria usada**: Debe ser < 256MB durante sincronización  
- **Productos por minuto**: Objetivo ~20 productos/minuto

#### Optimizaciones:
- Ejecutar sincronización fuera de horas pico
- Mantener WordPress y plugins actualizados
- Limpiar logs antiguos periódicamente

### Actualizaciones del Plugin

#### Versionado Semántico:
- **MAJOR.MINOR.PATCH** (ejemplo: 1.0.0)
- **MAJOR**: Cambios incompatibles
- **MINOR**: Nuevas funcionalidades
- **PATCH**: Correcciones de bugs

#### Proceso de Actualización:
1. Hacer backup completo del sitio
2. Descargar nueva versión desde GitHub
3. Reemplazar archivos del plugin
4. Verificar configuración
5. Probar sincronización

---

## 📊 Monitoreo y Estadísticas

### Información Disponible

En la página de configuración puedes ver:
- **Última actualización**: Timestamp de la última sincronización
- **Próxima sincronización**: Cuándo se ejecutará la siguiente
- **Estado de conexión**: Si la API de ELIT responde correctamente

### Métricas en WooCommerce

#### Productos ELIT:
```sql
SELECT COUNT(*) FROM wp_posts p 
JOIN wp_postmeta pm ON p.ID = pm.post_id 
WHERE p.post_type = 'product' 
AND pm.meta_key = '_sku' 
AND pm.meta_value LIKE 'ELIT_%';
```

#### Productos sin stock:
```sql
SELECT COUNT(*) FROM wp_posts p 
JOIN wp_postmeta pm ON p.ID = pm.post_id 
WHERE p.post_type = 'product' 
AND pm.meta_key = '_stock_status' 
AND pm.meta_value = 'outofstock'
AND pm.post_id IN (
    SELECT post_id FROM wp_postmeta 
    WHERE meta_key = '_sku' 
    AND meta_value LIKE 'ELIT_%'
);
```

---

## 🔄 Flujos de Trabajo Recomendados

### Configuración Inicial (Primera vez)

1. **Preparación** (15 min):
   - Instalar y activar WooCommerce
   - Instalar plugin FIFU
   - Obtener credenciales de ELIT

2. **Instalación** (5 min):
   - Subir plugin a WordPress
   - Activar plugin
   - Configurar credenciales

3. **Primera Sincronización** (30-60 min):
   - Ejecutar "Actualizar todo"
   - Verificar productos creados
   - Ajustar configuraciones si es necesario

4. **Configuración Final** (10 min):
   - Configurar intervalo de sincronización
   - Verificar categorías creadas
   - Probar proceso de compra

### Mantenimiento Rutinario (Semanal)

1. **Verificar logs** (5 min):
   - Revisar errores en logs
   - Verificar sincronizaciones exitosas

2. **Revisar productos** (10 min):
   - Verificar stock actualizado
   - Comprobar nuevos productos
   - Revisar precios aplicados

3. **Optimización** (según necesidad):
   - Limpiar logs antiguos
   - Ajustar intervalos si es necesario
   - Actualizar markup si cambian márgenes

### Resolución de Problemas (Según necesidad)

1. **Diagnóstico** (10 min):
   - Revisar logs de errores
   - Probar conexión con ELIT
   - Verificar configuración

2. **Corrección** (variable):
   - Ajustar credenciales si es necesario
   - Corregir configuraciones
   - Ejecutar sincronización manual

---

## 📈 Mejores Prácticas

### Configuración de Precios
- **Markup consistente**: Usa el mismo porcentaje para todos los productos
- **Monitoreo regular**: Revisa que los precios sean competitivos
- **Actualización estratégica**: Cambia markup en horarios de bajo tráfico

### Gestión de Stock
- **Monitoreo constante**: El plugin actualiza stock automáticamente
- **Productos sin stock**: Se marcan automáticamente
- **Stock bajo**: Permite pedidos pero advierte al cliente

### Categorización
- **Revisión inicial**: Verifica que las categorías creadas sean apropiadas
- **Personalización**: Puedes mover productos a categorías personalizadas
- **Mantenimiento**: Las categorías se actualizan automáticamente

### Rendimiento
- **Horarios óptimos**: Ejecuta sincronización en horarios de bajo tráfico
- **Intervalos apropiados**: No sincronices más frecuente de lo necesario
- **Monitoreo de recursos**: Vigila uso de memoria y CPU

---

## 🔧 Configuraciones Avanzadas

### Personalización de Prefijos SKU

#### Ejemplos de Prefijos:
- `ELIT_` (por defecto)
- `ELT_` (más corto)
- `MAYORISTA_` (descriptivo)
- `TECH_` (por categoría)

#### Consideraciones:
- **No cambiar** después de la primera sincronización
- **Usar caracteres simples** (letras, números, guiones)
- **Mantener consistencia** en toda la tienda

### Configuración de Markup por Categoría

Aunque el plugin aplica markup global, puedes personalizar por categoría:

1. **Identifica categorías** de alto/bajo margen
2. **Usa hooks de WooCommerce** para ajustar precios
3. **Mantén consistencia** con estrategia comercial

### Integración con Otros Plugins

#### Plugins Compatibles:
- **Yoast SEO**: Para optimización de productos
- **WooCommerce PDF Invoices**: Para facturación
- **WP Rocket**: Para optimización de rendimiento

#### Plugins a Evitar:
- **Otros sincronizadores de productos**: Pueden causar conflictos
- **Plugins de gestión de stock manual**: Pueden sobrescribir datos

---

## 📞 Soporte y Contacto

### Recursos de Ayuda

#### Documentación:
- **Manual de Usuario**: Este documento
- **README.md**: Información técnica
- **CHANGELOG.md**: Historial de versiones

#### Logs y Debug:
- **Archivo de logs**: `/wp-content/uploads/newbytes-connector.log`
- **WordPress Debug**: Activar `WP_DEBUG` si es necesario
- **Scripts de prueba**: Usar archivos `test-*.php` incluidos

### Reportar Problemas

#### Información a Incluir:
1. **Versión del plugin**
2. **Versión de WordPress/WooCommerce**  
3. **Mensaje de error completo**
4. **Logs relevantes**
5. **Pasos para reproducir el problema**

#### Canales de Soporte:
- **GitHub Issues**: https://github.com/cdfarfan1/woocommerce-elit-connector/issues
- **Logs del plugin**: Para diagnóstico propio
- **Documentación**: Para consultas comunes

---

## 📋 Checklist de Verificación

### ✅ Instalación Correcta:
- [ ] WordPress 5.0+ instalado
- [ ] WooCommerce activo y configurado
- [ ] Plugin FIFU instalado y activo
- [ ] Conector ELIT activado
- [ ] Credenciales de ELIT configuradas
- [ ] Conexión probada exitosamente

### ✅ Configuración Óptima:
- [ ] Prefijo SKU definido
- [ ] Markup porcentaje configurado
- [ ] Moneda seleccionada (ARS/USD)
- [ ] Intervalo de sincronización apropiado
- [ ] Primera sincronización ejecutada

### ✅ Funcionamiento Correcto:
- [ ] Productos ELIT aparecen en WooCommerce
- [ ] Precios tienen markup aplicado
- [ ] Stock se actualiza correctamente
- [ ] Imágenes se cargan automáticamente
- [ ] Categorías se asignan correctamente
- [ ] Sincronización automática funciona

---

**¡Felicidades! Tu Conector ELIT está funcionando correctamente.** 🎉

Para cualquier duda o problema, consulta este manual o revisa los logs del plugin.
