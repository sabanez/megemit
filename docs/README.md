# Documentación del Proyecto MeGeMIT WordPress

Índice centralizado de toda la documentación técnica del proyecto MeGeMIT WordPress con integración HubSpot.

## Documentos Principales

### **1. [ARCHITECTURE.md](ARCHITECTURE.md)**
Especificación completa de la arquitectura del proyecto incluyendo:
- Stack tecnológico y versiones
- Reglas de conducta y trabajo
- Estructura del plugin `mgmit-hubspot-bridge`
- Lógica de onboarding en tema hijo `basel-child`
- Herramientas auxiliares (migración, etc.)

### **2. [CHANGELOG.md](CHANGELOG.md)**
Registro histórico de todos los cambios y versiones del proyecto.

### **3. [HUBSPOT_INTEGRATION.md](HUBSPOT_INTEGRATION.md)**
Guía técnica completa de integración con HubSpot:
- **WordPress → HubSpot Bridge:** Mapeo de formularios nativos a propiedades HubSpot
- **HubSpot → WordPress:** Sincronización automática de formularios embebidos
- **Onboarding obligatorio:** Flujo de registro en 2 pasos con bloqueo de navegación
- **Login Identify:** Asociación automática de sesión HubSpot tras el login
- **Debugging:** Guías para diagnosticar problemas

### **4. [HUBSPOT_BRIDGE_PLUGIN_PLAN.md](HUBSPOT_BRIDGE_PLUGIN_PLAN.md)**
Blueprint y especificación técnica del plugin `mgmit-hubspot-bridge`.

---

## Stack Tecnológico

| Componente | Versión | Notas |
|---|---|---|
| **WordPress** | 6.9.4 | |
| **PHP** | 8.3 local / **7.4 producción** | Compatibilidad 7.4 **obligatoria** |
| **MySQL** | 8.4.8 | ServBay, macOS |
| **Tema activo** | Basel Child | Padre: Basel |
| **Database** | `megemit_database` | Prefix: `wpgr_` |

---

## Estructura del Proyecto

```
wp-site/
├── docs/                              # Esta carpeta de documentación
│   ├── README.md                      # Este archivo
│   ├── ARCHITECTURE.md                # Especificación técnica completa
│   ├── CHANGELOG.md                   # Historial de versiones
│   ├── HUBSPOT_INTEGRATION.md         # Guía de integración HubSpot
│   └── HUBSPOT_BRIDGE_PLUGIN_PLAN.md  # Especificación del plugin bridge
├── CLAUDE.md                          # Instrucciones para agentes (raíz)
├── wp-content/
│   ├── plugins/
│   │   ├── mgmit-hubspot-bridge/      # Plugin mapeo formularios → HubSpot
│   │   ├── hs-login-identify/         # Plugin identificación HubSpot tras login
│   │   └── hubspot-mapper/            # Plugin mapper campos HubSpot
│   └── themes/
│       └── basel-child/
│           ├── functions.php          # Entry point
│           └── inc/
│               ├── onboarding-enforcement.js
│               └── hubspot-sync/      # Módulo sincronización HubSpot → WP
└── hs-migration.php                   # Herramienta de migración (raíz)
```

---

## Componentes Principales

### Plugin: `mgmit-hubspot-bridge` (v1.3.0 — Estable)
Centraliza la integración de formularios WordPress → HubSpot mediante sistema de mapeos configurables.

- Panel admin para CRUD de mapeos sin tocar código
- Sistema de "Shadow Fields" para evitar conflictos con HubSpot
- Persistencia en `wp_options['mgmit_hubspot_config']`
- Frontend agnóstico: funciona con cualquier plugin de formularios
- Sin llamadas server-side a la API de HubSpot

**Ubicación:** `wp-content/plugins/mgmit-hubspot-bridge/`

---

### Plugin: `hs-login-identify` (v3.5.0 — Estable)
Asocia automáticamente la sesión de tracking de HubSpot (cookie `hubspotutk`) con el contacto correspondiente tras cada login, sin depender del navegador ni de JavaScript.

**Problema que resuelve:** HubSpot trackea la actividad anónima por cookie, pero no puede relacionarla con un contacto si el formulario de login no contiene un campo email. Este plugin lo resuelve enviando el email del usuario a la HubSpot Forms Submission API desde el servidor en el momento del login.

**Características:**
- Envío server-side vía `wp_remote_post` → inmune a bloqueadores, caché y conflictos JS
- Usa la HubSpot Forms Submission API (sin API key, solo Portal ID + Form GUID)
- Incluye la cookie `hubspotutk` en el contexto → HubSpot retroactivamente asocia toda la actividad del visitante
- Integraciones configurables desde el panel de administración:
  - SWPM (Simple WP Membership)
  - Ultimate Membership Pro
  - WordPress nativo
  - WooCommerce
- Página de diagnóstico con dos tests: conexión y simulación de login real

**Panel de ajustes:** Ajustes → HubSpot Login ID

**Ubicación:** `wp-content/plugins/hs-login-identify/`

**Detalle técnico importante:** el hook `swpm_after_login_authentication` (SWPM) y el hook `ihc_login_success` (Ultimate Membership Pro) se registran en tiempo de carga del plugin, fuera de `init`, para evitar condiciones de carrera con los hooks propios de estos plugins.

---

### Tema Hijo: `basel-child` (Lógica de Negocio)
Contiene la lógica de onboarding obligatorio y sincronización de formularios HubSpot embebidos.

**Módulos:**
1. **Onboarding Enforcement** (`inc/onboarding-enforcement.js`)
   - Triple seguro: JS + PHP init + DB metadata
   - Bloqueo de navegación hasta completar perfil
   - Metadatos: `mgmit_hs_details_pending`, `mgmit_hs_legacy_pending`

2. **HubSpot Sync** (`inc/hubspot-sync/`)
   - Captura eventos `postMessage` de formularios HubSpot embebidos
   - Sincroniza datos a `wpgr_usermeta` y `wpgr_swpm_members_tbl`
   - REST endpoint: `/wp-json/mgmit/v1/sync-hubspot-data`

**Ubicación:** `wp-content/themes/basel-child/`

---

### Herramienta de Migración: `hs-migration.php`
Importación bulk de datos HubSpot a WordPress vía CSV. Solo accesible para administradores.

**Ubicación:** Raíz del proyecto (`/hs-migration.php`)

---

## Metadatos de Control de Usuario

### Tabla `wpgr_usermeta`
- `mgmit_hs_details_pending = '1'` → Bloqueo total (nuevo registro pendiente)
- `mgmit_hs_details_pending = '0'` → Onboarding completado
- `mgmit_hs_legacy_pending = '1'` → Pendiente suave (usuario preexistente, sin bloqueo)

### Página de Control de Onboarding
- **ID:** `21568` — Slug: `/registrierungsdetails/`
- `?hs_finish=1` → Desbloqueo de onboarding
- `?legacy=1` → Formulario para usuarios legacy pending
- `?enforced=1` → Activa pop-up de aviso

---

## Endpoints REST

### Sincronización HubSpot → WordPress
- **Ruta:** `POST /wp-json/mgmit/v1/sync-hubspot-data`
- **Autenticación:** Email válido en payload
- **Respuesta exitosa:** `{"success":true,"message":"Datos sincronizados correctamente","user_id":123}`

---

## Herramientas de Debugging

### Navegador (DevTools Console)
- `[HS Mapper]` — Mapeo WP → HubSpot
- `[HS Sync]` — Sincronización HubSpot → WP

### Servidor
- Activar `WP_DEBUG=true` en `wp-config.php`
- Revisar `wp-content/debug.log` → líneas con prefijo `HSLI` (login identify) o `[MGMIT_HS_SYNC]`

### Panel de diagnóstico
- **Ajustes → HubSpot Login ID → Diagnóstico**
  - Test 1: verifica conectividad del servidor con `api.hsforms.com`
  - Test 2: simula un envío real con email de un contacto existente en HubSpot

---

## Reglas de Trabajo

1. **Aprobación previa** — cualquier modificación requiere aprobación antes de ejecutar
2. **Fases incrementales** — validar cada fase antes de continuar; registrar en `CHANGELOG.md`
3. **PHP 7.4 estricto** — prohibido: constructor promotion, union types, named arguments, match expressions, nullsafe operator
4. **Sin commits sin autorización**
5. **Arquitectura senior** — desarrollar con criterio de 15+ años en WordPress

---

**Última actualización:** 2026-06-16 (plugin hs-login-identify v3.5.0)
