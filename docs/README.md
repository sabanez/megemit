# 📚 Documentación del Proyecto MeGeMIT WordPress

Índice centralizado de toda la documentación técnica del proyecto MeGeMIT WordPress con integración HubSpot.

## 📖 Documentos Principales

### **1. [ARCHITECTURE.md](ARCHITECTURE.md)**
Especificación completa de la arquitectura del proyecto incluyendo:
- Stack tecnológico y versiones
- Reglas de conducta y trabajo
- Estructura del plugin `mgmit-hubspot-bridge`
- Lógica de onboarding en tema hijo `basel-child`
- Herramientas auxiliares (migración, etc.)

### **2. [CHANGELOG.md](CHANGELOG.md)**
Registro histórico de todos los cambios y versiones:
- v1.3.1 — Sincronización de formularios HubSpot → WordPress
- v1.3.0 — Visual Mapper UI para el plugin HubSpot Bridge
- v1.2.2 — Auto-login post-onboarding
- v1.2.1 — Mapeo de formulario de perfil
- v1.2.0 — Módulo independiente de onboarding
- v1.1.0 — Blindaje de onboarding (triple seguro)

### **3. [HUBSPOT_INTEGRATION.md](HUBSPOT_INTEGRATION.md)**
Guía técnica completa de integración con HubSpot:
- **WordPress → HubSpot Bridge:** Mapeo de formularios nativos a propiedades HubSpot
  - Sistema de "Shadow Fields"
  - Atributo `data-hs-ignore` para evitar duplicidad
  - Mutación de IDs de formulario
- **HubSpot → WordPress:** Sincronización automática de formularios embebidos
  - Módulo `/inc/hubspot-sync/` (captura, handler, loader)
  - Sincronización a `wpgr_usermeta` y `wpgr_swpm_members_tbl`
  - Mapeo de campos y división de direcciones
- **Onboarding obligatorio:** Flujo de registro en 2 pasos con bloqueo de navegación
- **Debugging:** Guías para diagnosticar problemas

### **4. [HUBSPOT_BRIDGE_PLUGIN_PLAN.md](HUBSPOT_BRIDGE_PLUGIN_PLAN.md)**
Blueprint y especificación técnica del plugin `mgmit-hubspot-bridge`:
- Arquitectura del sistema (patrón Singleton)
- Módulos: Core Engine, Admin UI, Field Mapper
- Persistencia de datos en `wp_options`
- Experiencia de usuario (Admin UI/UX)
- Implementación por fases
- Ventajas para el negocio

---

## 🔧 Stack Tecnológico

| Componente | Versión | Notas |
|---|---|---|
| **WordPress** | 6.9.4 | |
| **PHP** | 8.3 local / **7.4 producción** | Compatibilidad 7.4 **obligatoria** |
| **MySQL** | 8.4.8 | ServBay, macOS |
| **Tema activo** | Basel Child | Padre: Basel |
| **Database** | `megemit_database` | Prefix: `wpgr_` |

---

## 📁 Estructura del Proyecto

```
wp-site/
├── docs/                          # Esta carpeta de documentación
│   ├── README.md                  # Este archivo
│   ├── ARCHITECTURE.md            # Especificación técnica completa
│   ├── CHANGELOG.md               # Historial de versiones
│   ├── HUBSPOT_INTEGRATION.md     # Guía de integración HubSpot
│   └── HUBSPOT_BRIDGE_PLUGIN_PLAN.md # Especificación del plugin
├── CLAUDE.md                      # Instrucciones para agentes (raíz)
├── agent.md                       # Documentación de agentes (raíz)
├── CHANGELOG.md                   # Changelog master (raíz)
├── wp-content/
│   ├── plugins/
│   │   └── mgmit-hubspot-bridge/  # Plugin de integración HubSpot
│   │       ├── mgmit-hubspot-bridge.php
│   │       ├── includes/
│   │       │   └── class-mgmit-admin-ui.php
│   │       └── assets/
│   │           └── js/
│   │               ├── hubspot_map.js (mapeo WP → HubSpot)
│   │               └── admin-mapper.js (UI panel admin)
│   └── themes/
│       └── basel-child/
│           ├── functions.php      # Entry point
│           ├── HUBSPOT_INTEGRATION.md
│           └── inc/
│               ├── onboarding-enforcement.js (seguridad onboarding)
│               └── hubspot-sync/   # Módulo de sincronización HubSpot → WP
│                   ├── loader.php
│                   ├── handler.php (REST endpoint)
│                   └── form-capture.js (captura de eventos)
└── hs-migration.php               # Herramienta de migración (raíz)
```

---

## 🚀 Componentes Principales

### **Plugin: `mgmit-hubspot-bridge` (v1.3.0 — Estable)**
Centraliza la integración de formularios WordPress → HubSpot mediante sistema de mapeos configurables.

**Características:**
- Panel admin "HubSpot Bridge" para CRUD de mapeos (sin tocar código)
- Mapeo visual de campos con UUID v4 para cada configuración
- Sistema de "Shadow Fields" para evitar conflictos con HubSpot
- Persistencia en `wp_options['mgmit_hubspot_config']`
- Frontend agnóstico: funciona con cualquier plugin de formularios

**Ubicación:** `wp-content/plugins/mgmit-hubspot-bridge/`

### **Tema Hijo: `basel-child` (Lógica de Negocio)**
Contiene la lógica de onboarding obligatorio y sincronización de formularios HubSpot embebidos.

**Módulos:**
1. **Onboarding Enforcement** (`inc/onboarding-enforcement.js`)
   - Triple seguro: JS + PHP init + DB metadata
   - Bloqueo de navegación hasta completar perfil
   - Metadatos: `mgmit_hs_details_pending`, `mgmit_hs_legacy_pending`

2. **HubSpot Sync** (`inc/hubspot-sync/`)
   - Captura eventos `postMessage` de formularios HubSpot embebidos
   - Sincroniza datos a `wpgr_usermeta` (con prefijo `billing_` de WooCommerce)
   - Actualiza `wpgr_swpm_members_tbl` para miembros SWPM
   - REST endpoint: `/wp-json/mgmit/v1/sync-hubspot-data`

**Ubicación:** `wp-content/themes/basel-child/`

### **Herramienta de Migración: `hs-migration.php`**
Importación bulk de datos HubSpot a WordPress.

**Características:**
- CSV uploader integrado
- Mapeo por email (cross-linking HubSpot ↔ WordPress)
- Actualización bulk de metadatos de usuario
- Solo accesible para administradores

**Ubicación:** Raíz del proyecto (`/hs-migration.php`)

---

## 🔐 Reglas de Conducta

1. **Aprobación Previa** — Cualquier modificación de código requiere aprobación del usuario antes de proceder.
2. **Fases Incrementales** — Los cambios se organizan por fases; se valida cada fase antes de continuar.
3. **Registro en Changelog** — Todo cambio se documenta en `CHANGELOG.md` con versión y descripción.
4. **PHP 7.4 Estricto** — ❌ Prohibido: constructor promotion, union types, named arguments, match expressions, nullsafe operator.
5. **Optimización de Tokens** — Reducir contexto para maximizar eficiencia en agentes.
6. **Arquitectura Senior** — Desarrollar con criterio de 15+ años en WordPress.

---

## 🔗 Metadatos de Control de Usuario

### Tabla `wpgr_usermeta`
- `mgmit_hs_details_pending` = `'1'` → Bloqueo total (nuevo registro)
- `mgmit_hs_details_pending` = `'0'` → Onboarding completado
- `mgmit_hs_legacy_pending` = `'1'` → Pendiente suave (usuario existente, sin bloqueo)
- Campos WooCommerce: `billing_address_1`, `billing_address_2`, `billing_postcode`, `billing_city`, `billing_country`, `billing_phone`

### Página de Control
- **ID:** `21568`
- **Slug:** `/registrierungsdetails/`
- **Parámetros:**
  - `?hs_finish=1` → Desbloqueo de onboarding
  - `?legacy=1` → Formulario para usuarios legacy pending
  - `?enforced=1` → Activa pop-up de aviso

---

## 📞 Endpoints REST

### Sincronización HubSpot → WordPress
- **Ruta:** `POST /wp-json/mgmit/v1/sync-hubspot-data`
- **Autenticación:** Email válido en payload
- **Payload:**
  ```json
  {
    "email": "usuario@example.com",
    "data": {
      "first_name": "Juan",
      "last_name": "Pérez",
      "phone": "+34601234567",
      "address": "Calle Principal 123",
      "address2": "Apt 4",
      "zip": "28001",
      "city": "Madrid",
      "country": "ES",
      "job_title": "Ingeniero"
    }
  }
  ```
- **Respuesta exitosa:** `{"success":true,"message":"Datos sincronizados correctamente","user_id":123,"email":"usuario@example.com"}`
- **Respuesta fallida:** `{"success":false,"message":"...","user_id":123|"email":"..."}`

---

## 🛠️ Herramientas de Debugging

### En Navegador (F12 Console)
- `[HS Mapper]` — Mensajes del mapeo WP → HubSpot
- `[HS Sync]` — Mensajes de sincronización HubSpot → WP
- `[MGMIT_HS]` — Logs generales del módulo

### En Servidor
- Habilitar `WP_DEBUG=true` en `wp-config.php`
- Revisar `wp-content/debug.log` para mensajes con prefijo `[MGMIT_HS_SYNC]`

### En Base de Datos
- Verificar `wpgr_usermeta` para campos `billing_*`
- Verificar `wpgr_swpm_members_tbl` para sincronización SWPM
- Revisar metadatos de control (`mgmit_hs_*`)

---

## 📝 Notas Importantes

- **Sin API calls server-side a HubSpot:** Toda la integración es 100% frontend o de sincronización interna (no consulta HubSpot desde PHP).
- **Cross-origin compatible:** Usa `postMessage` API estándar (funciona con iframes de diferentes orígenes).
- **Filosofía de separación:** Onboarding (negocio) en tema hijo, Bridge (técnica) en plugin.
- **Actualización automática:** La sincronización HubSpot → WordPress es asincrónica y no bloquea el envío a HubSpot.

---

**Última actualización:** 2026-04-22 (v1.3.1)

Para consultas o cambios, revisar `CLAUDE.md` (instrucciones para agentes) en la raíz del proyecto.
