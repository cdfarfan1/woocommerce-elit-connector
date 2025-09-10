# Instrucciones de Instalación - Conector ELIT

## Pasos de Instalación

### 1. Preparación del Plugin

1. **Copia todos los archivos** del plugin a la carpeta de tu sitio WordPress:
   ```
   /wp-content/plugins/woocommerce-elit-connector/
   ```

2. **Archivos principales que debes tener**:
   - `woocommerce-elit-connector.php` (archivo principal)
   - `includes/elit-api.php` (integración con API de ELIT)
   - `includes/elit-sync-callback.php` (funciones de sincronización)
   - `includes/settings.php` (página de configuración actualizada)
   - `includes/admin-hooks.php` (hooks de administración)
   - Todos los demás archivos de la carpeta `includes/`
   - `README-ELIT.md` (documentación)

### 2. Activación del Plugin

1. Ve al **Panel de Administración de WordPress**
2. Navega a **Plugins > Plugins Instalados**
3. Busca **"Conector ELIT"** en la lista
4. Haz clic en **"Activar"**

### 3. Instalación de Dependencias

**IMPORTANTE**: Instala el plugin FIFU para el manejo de imágenes:

1. Ve a **Plugins > Añadir nuevo**
2. Busca **"Featured Image from URL (FIFU)"**
3. Instala y activa el plugin

### 4. Configuración Inicial

1. Ve a **Ajustes > Conector NB** (el menú aún mantiene el nombre original)
2. Verás la nueva interfaz para **ELIT**

#### Configurar Credenciales de ELIT

1. **ELIT User ID**: Ingresa tu ID de usuario (ejemplo: 24560)
2. **ELIT Token**: Ingresa tu token de acceso (ejemplo: z9qrpjjgnwq)
3. **Prefijo SKU**: Deja "ELIT_" o cambia según tu preferencia

#### Configuraciones Adicionales

1. **Sincronizar en USD**: Marca si quieres precios en dólares
2. **Porcentaje de Markup**: Configura tu margen de ganancia (por defecto: 35%)
3. **Intervalo de sincronización**: Elige cada cuánto sincronizar automáticamente

### 5. Prueba de Conexión

1. En la página de configuración, busca el campo **"ELIT Token"**
2. Haz clic en **"Probar Conexión ELIT"**
3. Deberías ver un mensaje verde de éxito

### 6. Primera Sincronización

1. Haz clic en **"Actualizar todo"** para sincronizar todos los productos
2. El proceso puede tomar varios minutos dependiendo de la cantidad de productos
3. Verás un resumen de productos creados/actualizados

## Verificación de la Instalación

### Comprobar que todo funciona:

1. **Ve a WooCommerce > Productos**
2. Deberías ver productos con SKUs que comienzan con "ELIT_"
3. Los productos deben tener:
   - Nombre correcto
   - Precios con markup aplicado
   - Categorías asignadas
   - Imágenes cargadas
   - Stock actualizado

### Archivo de Prueba (Opcional)

Si incluiste `test-elit-integration.php`:

1. Ve a **Ajustes > Test ELIT**
2. Ejecuta la prueba completa
3. Revisa que todos los tests pasen

## Configuración de Credenciales ELIT

Para obtener tus credenciales de ELIT:

1. **Contacta a ELIT** en https://elit.com.ar
2. Solicita acceso a su API para desarrolladores
3. Te proporcionarán:
   - **User ID** (número único)
   - **Token** (cadena alfanumérica)

### Ejemplo de solicitud a la API de ELIT:

```php
$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => 'https://clientes.elit.com.ar/v1/api/productos?limit=100',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS => json_encode(array(
    "user_id" => 24560,
    "token" => "z9qrpjjgnwq"
  )),
  CURLOPT_HTTPHEADER => array(
    'Content-Type: application/json'
  ),
));

$response = curl_exec($curl);
curl_close($curl);
```

## Solución de Problemas

### Error: "Credenciales no configuradas"
- Verifica que hayas ingresado el User ID y Token correctamente
- Asegúrate de guardar la configuración

### Error: "No se encontraron productos"
- Verifica tus credenciales con ELIT
- Comprueba que tu cuenta tenga productos asignados
- Revisa los logs en `wp-content/uploads/newbytes-connector.log`

### Error: "Tiempo de ejecución excedido"
- Reduce el número de productos por lote
- Aumenta el límite de tiempo en PHP
- Ejecuta la sincronización en horarios de menor tráfico

### Imágenes no se cargan
- Verifica que FIFU esté instalado y activado
- Comprueba que las URLs de imágenes de ELIT sean accesibles
- Revisa los permisos de la carpeta de uploads

## Mantenimiento

### Sincronización Automática
- El plugin sincroniza automáticamente según el intervalo configurado
- Los productos se actualizan, crean o eliminan según corresponda

### Logs
- Los logs se guardan en `wp-content/uploads/newbytes-connector.log`
- Útil para diagnosticar problemas

### Actualizaciones
- Mantén el plugin actualizado
- Verifica compatibilidad con nuevas versiones de WooCommerce

## Soporte

Para soporte técnico:
1. Revisa los logs del plugin
2. Verifica la configuración de credenciales
3. Comprueba que WooCommerce esté funcionando correctamente
4. Contacta al desarrollador con los detalles del error y logs relevantes

---

¡Tu conector ELIT debería estar funcionando correctamente ahora!
