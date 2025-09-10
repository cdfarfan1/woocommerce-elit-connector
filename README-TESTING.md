# Guía de Testing para WooCommerce NewBytes Plugin

## Requisitos Previos

Antes de ejecutar las pruebas, necesitas tener instalado:

1. **PHP 7.4 o superior**
2. **Composer** - Gestor de dependencias de PHP
3. **WordPress** - Entorno de desarrollo local
4. **WooCommerce** - Plugin instalado y activado

## Instalación de Dependencias

1. Instala Composer si no lo tienes:
   ```bash
   # Windows: Descarga desde https://getcomposer.org/download/
   # O usando Chocolatey:
   choco install composer
   ```

2. Instala las dependencias del proyecto:
   ```bash
   composer install
   ```

## Estructura de Pruebas Creada

Se ha creado una estructura completa de pruebas PHPUnit:

```
tests/
├── bootstrap.php           # Configuración inicial de pruebas
├── SecurityTest.php        # Pruebas de seguridad (CSRF, XSS, sanitización)
├── DatabaseTest.php        # Pruebas de base de datos y SQL injection
├── AjaxTest.php           # Pruebas de funciones AJAX
└── IntegrationTest.php    # Pruebas de integración completas
```

## Ejecución de Pruebas

### Ejecutar todas las pruebas:
```bash
vendor/bin/phpunit --configuration phpunit.xml --verbose
```

### Ejecutar pruebas específicas:
```bash
# Solo pruebas de seguridad
vendor/bin/phpunit tests/SecurityTest.php

# Solo pruebas de base de datos
vendor/bin/phpunit tests/DatabaseTest.php

# Solo pruebas AJAX
vendor/bin/phpunit tests/AjaxTest.php

# Solo pruebas de integración
vendor/bin/phpunit tests/IntegrationTest.php
```

### Generar reporte de cobertura:
```bash
vendor/bin/phpunit --coverage-html coverage/
```

## Pruebas Implementadas

### 1. SecurityTest.php
- ✅ Verificación de nonces en funciones AJAX
- ✅ Validación de sanitización de datos de entrada
- ✅ Verificación de escapado de salidas HTML
- ✅ Control de acceso y capacidades de usuario
- ✅ Rate limiting y registro de eventos de seguridad

### 2. DatabaseTest.php
- ✅ Prevención de inyección SQL
- ✅ Seguridad en operaciones de base de datos
- ✅ Validación de consultas preparadas
- ✅ Limpieza segura de datos

### 3. AjaxTest.php
- ✅ Autorización de usuarios en AJAX
- ✅ Validación de nonces
- ✅ Manejo de errores
- ✅ Sanitización de datos AJAX
- ✅ Rate limiting en peticiones AJAX

### 4. IntegrationTest.php
- ✅ Flujo completo de sincronización
- ✅ Integración con WooCommerce
- ✅ Manejo de errores con logging
- ✅ Optimización de base de datos
- ✅ Monitoreo de logs de seguridad

## Configuración del Entorno de Pruebas

El archivo `bootstrap.php` configura automáticamente:
- Carga del plugin y WooCommerce
- Creación de usuarios de prueba (admin, shop_manager, customer)
- Limpieza de datos de prueba
- Métodos auxiliares para simular AJAX y login

## Correcciones de Seguridad Validadas

Las pruebas validan todas las correcciones de seguridad implementadas:

1. **CSRF Protection**: Verificación de nonces en todas las operaciones
2. **SQL Injection Prevention**: Uso de `$wpdb->prepare()` en consultas
3. **XSS Prevention**: Escapado con `esc_html()`, `esc_attr()`, `esc_js()`
4. **Input Sanitization**: Sanitización con `sanitize_text_field()`
5. **Access Control**: Verificación de capacidades con `current_user_can()`

## Notas Importantes

- Las pruebas requieren un entorno WordPress funcional
- Se recomienda ejecutar en un entorno de desarrollo separado
- Los datos de prueba se limpian automáticamente después de cada test
- Todas las pruebas están diseñadas para ser independientes entre sí

## Troubleshooting

Si encuentras errores:

1. Verifica que WordPress y WooCommerce estén instalados
2. Asegúrate de que la base de datos esté accesible
3. Revisa los permisos de archivos y directorios
4. Consulta los logs de WordPress para errores específicos

## Comandos Útiles

```bash
# Ejecutar con más detalle
vendor/bin/phpunit --verbose --debug

# Ejecutar solo un test específico
vendor/bin/phpunit --filter testNonceValidation

# Generar reporte de cobertura en texto
vendor/bin/phpunit --coverage-text
```

---

**Nota**: Esta suite de pruebas ha sido diseñada específicamente para validar las correcciones de seguridad implementadas en el plugin WooCommerce NewBytes, siguiendo las mejores prácticas de WordPress y PHPUnit.