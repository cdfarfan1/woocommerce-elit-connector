# 🔄 Control de Versiones - Conector ELIT

## 🎯 Introducción

Este documento establece las políticas y procedimientos para el control de versiones del plugin Conector ELIT, siguiendo las mejores prácticas de desarrollo y versionado semántico.

---

## 📋 Versionado Semántico

### Formato: `MAJOR.MINOR.PATCH`

#### **MAJOR** (X.0.0)
- **Cuándo**: Cambios incompatibles con versiones anteriores
- **Ejemplos**: 
  - Cambio de estructura de base de datos
  - Modificación de API endpoints
  - Eliminación de funcionalidades

#### **MINOR** (0.X.0)  
- **Cuándo**: Nuevas funcionalidades compatibles
- **Ejemplos**:
  - Nuevas opciones de configuración
  - Mejoras en la interfaz
  - Funcionalidades adicionales

#### **PATCH** (0.0.X)
- **Cuándo**: Correcciones de bugs y mejoras menores
- **Ejemplos**:
  - Corrección de errores
  - Optimizaciones de rendimiento
  - Actualizaciones de seguridad

---

## 📊 Historial de Versiones

### 🚀 v1.0.0 - Release Inicial (Enero 9, 2025)

#### ✨ Nuevas Funcionalidades:
- **Integración completa** con API de ELIT v1
- **Sincronización automática** de productos
- **Gestión de precios** con markup configurable
- **Soporte multi-moneda** (ARS/USD)
- **Sincronización de imágenes** desde ELIT
- **Categorización automática** inteligente
- **Gestión de stock** en tiempo real
- **Interfaz de administración** optimizada
- **Sistema de logging** avanzado
- **Programación de tareas** automáticas

#### 🔧 Características Técnicas:
- **10 archivos PHP** esenciales (optimizado)
- **Compatibilidad**: WordPress 5.0+, WooCommerce 4.0+, PHP 7.4+
- **API Integration**: ELIT API v1 con autenticación
- **Performance**: Procesamiento por lotes de 50 productos
- **Security**: Validación completa y sanitización de datos

#### 📦 Archivos Incluidos:
```
woocommerce-elit-connector/
├── woocommerce-elit-connector.php     # Archivo principal
├── includes/
│   ├── activation.php                 # Activación/desactivación
│   ├── admin-hooks.php                # Hooks de administración  
│   ├── cron-hooks.php                 # Tareas programadas
│   ├── utils.php                      # Utilidades y logging
│   ├── price-calculator.php           # Cálculo de precios
│   ├── modals.php                     # Modales de interfaz
│   ├── product-sync.php               # Motor de sincronización
│   ├── settings.php                   # Página de configuración
│   ├── elit-api.php                   # Integración API ELIT
│   └── elit-sync-callback.php         # Lógica de sincronización
├── assets/
│   ├── css/admin.css                  # Estilos de admin
│   ├── js/admin.js                    # JavaScript de admin
│   └── icon-128x128.png               # Icono del plugin
├── MANUAL-DE-USO.md                   # Manual de usuario
├── DICCIONARIO-DE-DATOS.md            # Mapeo de datos
└── CONTROL-DE-VERSIONES.md            # Este archivo
```

---

## 🔄 Proceso de Desarrollo

### Flujo de Trabajo Git

#### Ramas Principales:
- **`main`**: Código de producción estable
- **`develop`**: Desarrollo en progreso
- **`feature/*`**: Nuevas funcionalidades
- **`hotfix/*`**: Correcciones urgentes

#### Comandos Básicos:
```bash
# Clonar repositorio
git clone https://github.com/cdfarfan1/woocommerce-elit-connector.git

# Crear rama de feature
git checkout -b feature/nueva-funcionalidad

# Hacer cambios y commit
git add .
git commit -m "feat: agregar nueva funcionalidad"

# Merge a main
git checkout main
git merge feature/nueva-funcionalidad

# Tag de versión
git tag -a v1.1.0 -m "Release v1.1.0"
git push origin main --tags
```

### Convenciones de Commits

#### Formato:
```
<tipo>(<ámbito>): <descripción>

<cuerpo opcional>

<footer opcional>
```

#### Tipos de Commit:
- **`feat`**: Nueva funcionalidad
- **`fix`**: Corrección de bug
- **`docs`**: Cambios en documentación
- **`style`**: Cambios de formato (no afectan lógica)
- **`refactor`**: Refactorización de código
- **`test`**: Agregar o modificar tests
- **`chore`**: Tareas de mantenimiento

#### Ejemplos:
```bash
feat(api): agregar soporte para filtros de productos
fix(sync): corregir manejo de productos sin stock  
docs(manual): actualizar guía de instalación
style(admin): mejorar interfaz de configuración
refactor(price): optimizar cálculo de markup
```

---

## 🚀 Roadmap de Versiones

### 📅 v1.1.0 - Mejoras de Interfaz (Planificado)
- **ETA**: Febrero 2025
- **Funcionalidades**:
  - Dashboard de estadísticas
  - Filtros avanzados de productos
  - Notificaciones por email
  - Mejoras en la interfaz de usuario

### 📅 v1.2.0 - Optimizaciones (Planificado)
- **ETA**: Marzo 2025  
- **Funcionalidades**:
  - Sincronización incremental
  - Cache avanzado
  - Compresión de imágenes
  - Optimizaciones de base de datos

### 📅 v2.0.0 - Funcionalidades Avanzadas (Planificado)
- **ETA**: Abril 2025
- **Funcionalidades**:
  - Soporte multi-tienda
  - API REST personalizada
  - Webhooks de ELIT
  - Configuraciones por categoría

---

## 🧪 Testing y QA

### Tipos de Testing

#### Unit Tests:
- **Funciones de transformación** de datos
- **Cálculos de precios** y markup
- **Validaciones** de entrada
- **Utilidades** y helpers

#### Integration Tests:
- **API de ELIT** conexión y respuesta
- **WooCommerce** creación y actualización de productos
- **WordPress** hooks y filters
- **Cron jobs** programados

#### End-to-End Tests:
- **Flujo completo** de sincronización
- **Interfaz de usuario** admin
- **Funcionalidad** de frontend
- **Performance** bajo carga

### Proceso de Testing

#### Antes de Release:
1. **Tests automáticos** (si están disponibles)
2. **Testing manual** en entorno de desarrollo
3. **Verificación** en entorno de staging
4. **Aprobación** para producción

#### Scripts de Prueba Incluidos:
- `test-elit-api.php` - Prueba conexión real con ELIT
- `test-standalone-elit.php` - Simulación de transformación
- `debug-elit-response.php` - Debug de respuestas API

---

## 📦 Releases y Distribución

### Proceso de Release

#### 1. Preparación:
```bash
# Actualizar versión en archivo principal
sed -i 's/Version: 1.0.0/Version: 1.1.0/' woocommerce-elit-connector.php

# Actualizar constante de versión  
sed -i "s/VERSION_ELIT', '1.0.0'/VERSION_ELIT', '1.1.0'/" woocommerce-elit-connector.php

# Actualizar CHANGELOG.md
echo "## [1.1.0] - $(date +%Y-%m-%d)" >> CHANGELOG.md
```

#### 2. Testing:
```bash
# Ejecutar tests
php test-elit-api.php
php test-standalone-elit.php

# Verificar funcionalidad
# - Instalación limpia
# - Configuración
# - Sincronización  
# - Verificación de productos
```

#### 3. Release:
```bash
# Commit final
git add .
git commit -m "release: v1.1.0"

# Tag de versión
git tag -a v1.1.0 -m "Release v1.1.0 - Mejoras de interfaz"

# Push con tags
git push origin main --tags
```

#### 4. Documentación:
- Actualizar README.md con nuevas funcionalidades
- Crear release notes en GitHub
- Actualizar manual de uso si es necesario

### Distribución

#### GitHub Releases:
1. **Crear release** en GitHub con tag de versión
2. **Adjuntar ZIP** del plugin listo para instalar
3. **Incluir notas** de la versión
4. **Marcar como latest** si es estable

#### WordPress.org (Futuro):
1. Preparar para directorio oficial de plugins
2. Cumplir con guidelines de WordPress
3. Proceso de review y aprobación

---

## 🛡️ Políticas de Seguridad

### Versionado de Seguridad

#### Clasificación de Vulnerabilidades:
- **CRÍTICA**: Ejecución remota de código
- **ALTA**: Escalación de privilegios  
- **MEDIA**: Exposición de datos
- **BAJA**: Información sensible

#### Proceso de Patches de Seguridad:
1. **Identificación** de vulnerabilidad
2. **Desarrollo** de patch inmediato
3. **Testing** acelerado
4. **Release** prioritario
5. **Notificación** a usuarios

### Ejemplo de Patch de Seguridad:
```bash
# Versión con vulnerabilidad: v1.0.0
# Patch de seguridad: v1.0.1

git checkout main
git checkout -b hotfix/security-patch-v1.0.1

# Aplicar corrección
# Hacer tests de seguridad
# Commit y tag

git tag -a v1.0.1 -m "Security patch: fix XSS vulnerability"
git push origin main --tags
```

---

## 📈 Métricas de Desarrollo

### KPIs del Proyecto:

| Métrica | Objetivo | Actual v1.0.0 |
|---------|----------|---------------|
| **Tamaño del plugin** | < 500KB | ~200KB ✅ |
| **Archivos incluidos** | < 15 archivos | 10 archivos ✅ |
| **Tiempo de sincronización** | < 5 min/100 productos | ~3 min ✅ |
| **Memoria utilizada** | < 256MB | ~128MB ✅ |
| **Compatibilidad PHP** | 7.4+ | 7.4+ ✅ |
| **Cobertura de tests** | > 80% | Manual ⚠️ |

### Optimizaciones Implementadas:

#### v1.0.0:
- ✅ **Eliminados archivos innecesarios** (tests, docs duplicadas, PDFs)
- ✅ **Solo 10 archivos PHP** esenciales
- ✅ **Procesamiento por lotes** para mejor rendimiento
- ✅ **Cache de tokens** para reducir llamadas API
- ✅ **Logging optimizado** sin sobrecargar

---

## 🔮 Planificación Futura

### Próximas Mejoras

#### v1.1.0 - Dashboard y Estadísticas:
- Panel de control con métricas en tiempo real
- Gráficos de sincronización
- Alertas de problemas
- Exportación de reportes

#### v1.2.0 - Performance y Cache:
- Sistema de cache avanzado
- Sincronización incremental
- Optimizaciones de base de datos
- Compresión de imágenes

#### v2.0.0 - Funcionalidades Avanzadas:
- Multi-tienda support
- API REST personalizada
- Webhooks de ELIT
- Configuraciones por categoría

### Consideraciones Técnicas:

#### Compatibilidad:
- **Mantener soporte** para WordPress 5.0+
- **Evaluar soporte** para versiones más nuevas
- **Deprecar gradualmente** funcionalidades obsoletas

#### Migración:
- **Herramientas automáticas** de migración
- **Backup automático** antes de actualizaciones
- **Rollback** en caso de problemas

---

## 📞 Contacto y Soporte

### Mantenedores:
- **Desarrollador Principal**: ELIT Connector Team
- **Repositorio**: https://github.com/cdfarfan1/woocommerce-elit-connector
- **Issues**: GitHub Issues para reportar bugs

### Contribuciones:
- **Fork** el repositorio
- **Crear rama** de feature
- **Hacer PR** con descripción detallada
- **Seguir** convenciones de commits

---

**Documento vivo - Se actualiza con cada release del plugin**
