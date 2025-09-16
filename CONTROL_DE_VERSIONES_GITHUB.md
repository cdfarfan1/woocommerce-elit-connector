# CONTROL DE VERSIONES Y GITHUB - CONECTOR ELIT

## 🔗 Información del Repositorio

**URL del Repositorio:** https://github.com/cdfarfan1/woocommerce-elit-connector  
**Rama Principal:** `main`  
**Última Versión:** 1.0.0  
**Desarrollador:** Cristian Farfan, Pragmatic Solutions  

## 📋 Comandos de Control de Versiones

### Configuración Inicial

```bash
# Clonar el repositorio
git clone https://github.com/cdfarfan1/woocommerce-elit-connector.git

# Navegar al directorio
cd woocommerce-elit-connector

# Configurar usuario (si es necesario)
git config user.name "Cristian Farfan"
git config user.email "info@pragmaticsolutions.com.ar"
```

### Workflow de Desarrollo

```bash
# Crear nueva rama de desarrollo
git checkout -b feature/nueva-funcionalidad

# Verificar estado
git status

# Agregar archivos modificados
git add .

# Commit con mensaje descriptivo
git commit -m "feat: Agregar nueva funcionalidad de sincronización"

# Subir cambios
git push origin feature/nueva-funcionalidad

# Crear Pull Request en GitHub
# Merge a main después de revisión
```

### Comandos de Mantenimiento

```bash
# Actualizar rama local con cambios remotos
git pull origin main

# Ver historial de commits
git log --oneline

# Ver diferencias
git diff

# Revertir cambios
git checkout -- archivo.php

# Crear rama de hotfix
git checkout -b hotfix/correccion-urgente
```

## 🏷️ Gestión de Versiones

### Crear Nueva Versión

```bash
# Actualizar número de versión en el archivo principal
# woocommerce-elit-connector.php: Version: 1.1.0

# Commit del cambio de versión
git add woocommerce-elit-connector.php
git commit -m "chore: Incrementar versión a 1.1.0"

# Crear tag de versión
git tag -a v1.1.0 -m "Release version 1.1.0 - Mejoras en sincronización"

# Subir tag
git push origin v1.1.0

# Subir cambios
git push origin main
```

### Listar Versiones

```bash
# Ver todos los tags
git tag -l

# Ver información de un tag específico
git show v1.0.0

# Ver diferencias entre versiones
git diff v1.0.0..v1.1.0
```

## 📝 Estructura de Commits

### Convención de Commits

- `feat:` Nueva funcionalidad
- `fix:` Corrección de bugs
- `docs:` Documentación
- `style:` Formato de código
- `refactor:` Refactorización
- `test:` Pruebas
- `chore:` Tareas de mantenimiento
- `perf:` Mejoras de rendimiento
- `ci:` Configuración de CI/CD

### Ejemplos de Commits

```bash
git commit -m "feat: Agregar sincronización automática cada 6 horas"
git commit -m "fix: Corregir error en cálculo de precios USD"
git commit -m "docs: Actualizar README con nuevas instrucciones"
git commit -m "style: Aplicar estándares de codificación WordPress"
git commit -m "refactor: Optimizar consultas a la API de ELIT"
git commit -m "test: Agregar pruebas unitarias para price-calculator"
git commit -m "chore: Actualizar dependencias del plugin"
```

## 🌿 Estructura de Ramas

### Ramas Principales

```
main                    # Rama principal estable
├── develop            # Rama de desarrollo
├── feature/*          # Ramas de características
├── hotfix/*           # Ramas de correcciones urgentes
└── release/*          # Ramas de preparación de releases
```

### Flujo de Trabajo

1. **Desarrollo:** Trabajar en rama `develop`
2. **Features:** Crear ramas `feature/nombre-funcionalidad`
3. **Hotfixes:** Crear ramas `hotfix/descripcion-problema`
4. **Releases:** Crear ramas `release/v1.x.x`
5. **Merge:** Integrar cambios a `main` después de revisión

## 🔄 Workflow de Release

### Preparación de Release

```bash
# Crear rama de release
git checkout -b release/v1.1.0

# Actualizar versiones
# - woocommerce-elit-connector.php
# - README.md
# - CHANGELOG.md

# Commit cambios
git add .
git commit -m "chore: Preparar release v1.1.0"

# Merge a main
git checkout main
git merge release/v1.1.0

# Crear tag
git tag -a v1.1.0 -m "Release version 1.1.0"

# Subir cambios
git push origin main
git push origin v1.1.0

# Limpiar rama de release
git branch -d release/v1.1.0
```

### Hotfix de Emergencia

```bash
# Crear rama de hotfix desde main
git checkout main
git checkout -b hotfix/correccion-critica

# Hacer correcciones
# ... cambios ...

# Commit y push
git add .
git commit -m "fix: Corregir error crítico en sincronización"
git push origin hotfix/correccion-critica

# Merge a main
git checkout main
git merge hotfix/correccion-critica

# Crear tag de hotfix
git tag -a v1.0.1 -m "Hotfix v1.0.1 - Corrección crítica"
git push origin v1.0.1

# Limpiar rama
git branch -d hotfix/correccion-critica
```

## 📊 Monitoreo y Estadísticas

### Comandos Útiles

```bash
# Ver estadísticas de commits
git shortlog -sn

# Ver commits por autor
git log --author="Cristian Farfan" --oneline

# Ver archivos más modificados
git log --stat --summary

# Ver gráfico de ramas
git log --graph --oneline --all
```

### GitHub Actions (Futuro)

```yaml
# .github/workflows/ci.yml
name: CI/CD Pipeline
on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main ]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
    - uses: actions/checkout@v2
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '7.4'
    - name: Run tests
      run: |
        composer install
        vendor/bin/phpunit
```

## 🚀 Despliegue

### Despliegue Manual

```bash
# Crear archivo ZIP para distribución
git archive --format=zip --prefix=woocommerce-elit-connector/ HEAD > woocommerce-elit-connector-v1.0.0.zip

# Subir a servidor de producción
scp woocommerce-elit-connector-v1.0.0.zip usuario@servidor:/ruta/plugins/
```

### Despliegue Automático (Futuro)

```bash
# Configurar webhook en GitHub
# Automatizar despliegue con GitHub Actions
# Notificar cambios por email/Slack
```

## 📞 Contacto y Soporte

**Desarrollador:** Cristian Farfan  
**Empresa:** Pragmatic Solutions  
**Email:** info@pragmaticsolutions.com.ar  
**GitHub:** https://github.com/cristianfarfan  
**Sitio Web:** https://www.pragmaticsolutions.com.ar  

### Issues y Pull Requests

- **Reportar Bugs:** https://github.com/cdfarfan1/woocommerce-elit-connector/issues
- **Solicitar Features:** https://github.com/cdfarfan1/woocommerce-elit-connector/issues
- **Contribuir:** Crear Pull Request con cambios

---

**Última actualización:** 16 de Septiembre de 2025  
**Versión del documento:** 1.0  
**Estado:** Completo y actualizado
