# 🏗️ Arquitectura del Proyecto MeGeMIT WordPress

Especificación técnica completa del proyecto, incluidas reglas de conducta, stack y estructura de componentes.

---

## 📦 Stack Tecnológico

| Componente | Versión | Notas |
|---|---|---|
| **WordPress** | 6.9.4 | CMS base del proyecto |
| **PHP** | 8.3 local / **7.4 producción** | Compatibilidad **obligatoria** con 7.4 |
| **MySQL** | 8.4.8 | ServBay Community Server, macOS |
| **Entorno** | ServBay | Hosting local en macOS |
| **Tema Activo** | Basel Child | Tema padre: Basel |
| **Database** | `megemit_database` | Prefix: `wpgr_`, User: `root`, Host: `localhost` |

---

## 🔐 Reglas de Conducta del Agente

### 1. Aprobación Previa
- **Nunca modificar código sin aprobación previa del usuario.**
- Siempre solicitar confirmación antes de:
  - Cambios destructivos (git reset, deletions)
  - Modificaciones en BD
  - Push a repositorio remoto
  - Cambios en CI/CD

### 2. Fases Incrementales
- Organizar cambios por fases lógicas
- Validar cada fase antes de continuar
- Registrar el progreso en `CHANGELOG.md`
- Documentar qué se aprobó, qué se implementó y qué se validó

### 3. Compatibilidad PHP 7.4 Estricta
**Prohibido en el código:**
- ❌ Constructor promotion (`public function __construct(private string $name)`)
- ❌ Union types (`string|int`)
- ❌ Named arguments en función calls
- ❌ Match expressions
- ❌ Nullsafe operator (`$obj?->method()`)

**Alternativas compatibles:**
```php
// ❌ NO (PHP 8.0+)
public function __construct(private string $name) {}

// ✅ SÍ (PHP 7.4)
private $name;
public function __construct($name) {
    $this->name = $name;
}

// ❌ NO (PHP 8.0+)
if ($value is string|int) { ... }

// ✅ SÍ (PHP 7.4)
if (is_string($value) || is_int($value)) { ... }
```

### 4. Optimización de Tokens
- Minimizar contexto innecesario
- Compactar conversación cuando alcance 70% del límite
- Usar herramientas específicas en lugar de Bash genérico
- Reutilizar información de agentes anteriores

### 5. Compactación de Contexto
- Cuando la conversación alcance ~70% de tokens, usar `/compact`
- Preservar información clave en memory (auto-memory)
- Mantener enlaces a archivos para referencia rápida

### 6. Actitud Senior (15+ años WordPress)
- Código limpio sin sobreingeniería
- Decisiones arquitectónicas pragmáticas
- Seguimiento de estándares de la comunidad
- Documentación clara para futuros mantenedores

---

## 🏗️ Arquitectura del Sistema

```
┌─────────────────────────────────────────────────────────────────┐
│                    WordPress Core (6.9.4)                        │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  ┌──────────────────────┐         ┌──────────────────────────┐  │
│  │   Plugin Activo:     │         │   Tema Activo:           │  │
│  │ mgmit-hubspot-bridge │         │ basel-child              │  │
│  │      (v1.3.0)        │         │                          │  │
│  └──────────────────────┘         └──────────────────────────┘  │
│         │                                │                       │
│         │                                │                       │
│         ├─ Mapea formularios WP          ├─ Onboarding         │
│         │  a propiedades HubSpot         │  obligatorio        │
│         │                                │                       │
│         ├─ Panel Admin                   ├─ HubSpot Sync       │
│         │  (CRUD de mapeos)              │  (formularios       │
│         │                                │   embebidos)        │
│         └─ JavaScript frontend           └─ Validación de      │
│            (hubspot_map.js)                 metadatos (DB)     │
│                                                                   │
│  ┌──────────────────────────────────────────────────────────────┐│
│  │         WordPress REST API                                   ││
│  │  POST /wp-json/mgmit/v1/sync-hubspot-data                   ││
│  └──────────────────────────────────────────────────────────────┘│
│                                                                   │
│  ┌──────────────────────────────────────────────────────────────┐│
│  │  Database: megemit_database (Prefix: wpgr_)                 ││
│  │  ├─ wpgr_users          (Usuarios WordPress)                ││
│  │  ├─ wpgr_usermeta       (Metadatos: billing_*, custom)      ││
│  │  ├─ wpgr_swpm_members_tbl (SWPM: miembros)                  ││
│  │  └─ wp_options          (Configuración: mgmit_hubspot_*)    ││
│  └──────────────────────────────────────────────────────────────┘│
│                                                                   │
└─────────────────────────────────────────────────────────────────┘
         │
         ├────────────────────────────────────────────┐
         │                                            │
         ▼                                            ▼
    ┌─────────────┐                          ┌──────────────┐
    │   HubSpot   │                          │  Navegador   │
    │    API      │                          │  (Usuario)   │
    └─────────────┘                          └──────────────┘
         ▲                                            │
         │                                            │
         └────────────────────────────────────────────┘
               Form postMessage (cross-origin)
```

---

## 🔌 Plugin: `mgmit-hubspot-bridge` (v1.3.0 — Estable)

### Propósito
Centralizar la integración entre formularios de WordPress y propiedades de HubSpot mediante un sistema de mapeos configurables desde el panel de administración.

### Características Principales
- ✅ **Panel Admin intuitivo** — CRUD de mapeos sin tocar código
- ✅ **Sistema de Shadow Fields** — Campos ocultos para evitar conflictos HubSpot
- ✅ **Configuración dinámica** — Guardada en `wp_options['mgmit_hubspot_config']`
- ✅ **UUIDs para cada mapeo** — Identificación única (PHP 7.4 compatible)
- ✅ **Agnóstico de formularios** — Funciona con cualquier plugin (SWPM, CF7, etc.)
- ✅ **Sin API calls server-side** — 100% frontend

### Estructura del Plugin
```
mgmit-hubspot-bridge/
├── mgmit-hubspot-bridge.php          # Archivo principal
├── includes/
│   ├── class-mgmit-admin-ui.php      # Panel de administración
│   ├── class-mgmit-bridge.php        # Clase principal (Singleton)
│   └── class-mgmit-field-mapper.php  # Motor de inyección JS
├── assets/
│   └── js/
│       ├── hubspot_map.js            # Frontend - mapeo de campos
│       └── admin-mapper.js           # Frontend - panel admin
└── README.md
```

### Config: Estructura en `wp_options`
```php
$config = [
    [
        'id'              => 'uuid-v4-string',
        'name'            => 'Registro Profesional Nivel 13',
        'status'          => 'enabled',
        'selector'        => '#id-del-formulario',
        'hubspot_name'    => 'MeGeMIT_DE_Registration',
        'mapping'         => [
            ['wp_field' => 'swpm-472', 'hs_prop' => 'firstname'],
            ['wp_field' => 'swpm-456', 'hs_prop' => 'email'],
            // ... más campos
        ]
    ],
    // ... más mapeos
];
```

### Hooks Extensibles
```php
apply_filters('mgmit_hs_bridge_config', $config)
// Permite a otros desarrolladores modificar dinámicamente la config
```

### Flujo de Datos: WP → HubSpot
```
1. Usuario rellena formulario WordPress
   ↓
2. hubspot_map.js detecta el envío (validando por selector)
   ↓
3. Crea campos ocultos (Shadow Fields) con nombres HubSpot
   ↓
4. Marca campos originales con data-hs-ignore="true"
   ↓
5. Inyecta ID de formulario para HubSpot
   ↓
6. Permite que el formulario se envíe normalmente a HubSpot
   ↓
7. HubSpot recibe los datos mapeados correctamente
```

---

## 🎨 Tema Hijo: `basel-child` (Lógica de Negocio)

### Propósito
Contener exclusivamente:
- Lógica de onboarding obligatorio (bloqueo hasta completar perfil)
- Sincronización de formularios HubSpot embebidos
- Validaciones de negocio

**Separado intencionalmente del plugin para mantener responsabilidades claras.**

### Estructura
```
basel-child/
├── functions.php                     # Entry point - cargas iniciales
├── HUBSPOT_INTEGRATION.md            # Documentación técnica
├── inc/
│   ├── onboarding-enforcement.js     # Seguridad del onboarding
│   └── hubspot-sync/
│       ├── loader.php                # Carga condicional
│       ├── handler.php               # REST endpoint
│       └── form-capture.js           # Captura de eventos
└── style.css
```

### 📌 Metadatos de Control

Cada usuario en `wpgr_usermeta` tiene tres flags de control:

#### `mgmit_hs_details_pending`
- **Valor `'1'`** → Bloqueo total (nuevo registro pendiente)
  - Usuario no puede navegar fuera de `/registrierungsdetails/`
  - Redirección forzada en cada intento
  - Pop-up informativo activado con `?enforced=1`
  
- **Valor `'0'`** → Onboarding completado
  - Usuario desbloqueado
  - Acceso total a la plataforma
  - No se vuelve a pedir rellenar datos

#### `mgmit_hs_legacy_pending`
- **Valor `'1'`** → Usuario existente pendiente (sin bloqueo)
  - Login redirect a `/registrierungsdetails/?legacy=1`
  - Formulario HubSpot disponible pero no forzado
  - El usuario puede navegar normalmente

### 🎯 Página de Control: `/registrierungsdetails/`

- **ID de página:** `21568`
- **Slug:** `/registrierungsdetails/`

**Parámetros de URL:**
- `?hs_finish=1` → Usuario completó onboarding (desbloquea acceso)
- `?legacy=1` → Muestra formulario para usuarios legacy pending
- `?enforced=1` → Activa pop-up de aviso (redirección forzada)

**Flujo de Onboarding:**
```
1. Usuario se registra en SWPM
   ↓ (metadato: mgmit_hs_details_pending = '1')
   
2. Sistema lo redirige a /registrierungsdetails/?enforced=1
   ↓
   
3. Pop-up informa que debe completar su perfil
   ↓
   
4. Usuario rellena formulario HubSpot embebido
   ↓
   
5. Formulario sincroniza a WP (REST endpoint) + se envía a HubSpot
   ↓
   
6. Página redirige a /registrierungsdetails/?hs_finish=1
   ↓
   
7. Sistema detecta hs_finish=1:
   - Actualiza metadato: mgmit_hs_details_pending = '0'
   - Auto-login en SWPM
   - Redirección final a /fachkreisbereich-mitglied/
```

### 1️⃣ Módulo: Onboarding Enforcement

**Archivo:** `inc/onboarding-enforcement.js`

**Responsabilidades:**
- Triple seguro de bloqueo:
  1. **JavaScript (navegador)** — Intercepción preventiva
  2. **PHP init hook** — Detección en cada carga
  3. **Database metadata** — Persistencia entre sesiones
  
**Elementos:**
- Pop-up modal de aviso
- Redirección forzada
- Cookies de seguimiento (corta duración)
- Metadatos de usuario en BD

**Validación:** Antes de permitir navegación, verifica:
```php
if (mgmit_hs_details_pending == '1' && !isset($_GET['hs_finish'])) {
    redirect_to('/registrierungsdetails/?enforced=1');
}
```

### 2️⃣ Módulo: HubSpot Sync (Sincronización)

**Ubicación:** `inc/hubspot-sync/`

#### `loader.php` — Gestor de carga
```php
// Enqueue condicional: solo en página 21568
if (!is_page(21568)) return;

// Carga form-capture.js con cache busting
wp_enqueue_script('mgmit-hubspot-form-capture', 
    '/inc/hubspot-sync/form-capture.js',
    [],
    filemtime(...),
    true  // footer
);

// Incluye el handler REST
require_once get_stylesheet_directory() . '/inc/hubspot-sync/handler.php';
```

#### `form-capture.js` — Captura de eventos
```javascript
// Escucha: window.message desde iframe de HubSpot
// Filtro: event.data.type === 'hsFormCallback'
// Evento: onFormSubmit (contiene los campos)

// Mapeo HubSpot → WordPress:
const fieldMap = {
    'firstname' → 'first_name',
    'lastname' → 'last_name',
    'phone' → 'phone',
    'zip' → 'zip',
    'city' → 'city',
    'job_title_de' → 'job_title',
    'country_of_the_contact' → 'country',
    'address' → 'address' (o 'address' + 'address2' si tiene coma)
};

// POST a: /wp-json/mgmit/v1/sync-hubspot-data
```

#### `handler.php` — REST Endpoint
```
POST /wp-json/mgmit/v1/sync-hubspot-data

Clase: MGMIT_HubSpot_Sync
Método: handle_sync_request($request)

Payload esperado:
{
  "email": "usuario@example.com",
  "data": { campos mapeados }
}

Validación:
✓ Email requerido y válido
✓ Usuario debe existir en wpgr_users (por email)
✓ SWPM optional (sincroniza solo si existe wpgr_swpm_members_tbl)

Sincronización:
→ wpgr_usermeta: update_user_meta() con prefijo 'billing_' (WooCommerce)
→ wpgr_swpm_members_tbl: $wpdb->update() para miembros SWPM

Respuesta:
✅ {"success":true,"message":"...","user_id":123,"email":"..."}
❌ {"success":false,"message":"...","user_id":123 o "email":"..."}
```

### Campos Sincronizados: Mapeo a Base de Datos

| Campo HubSpot | Interno WP | wpgr_usermeta | wpgr_swpm_members_tbl |
|---|---|---|---|
| `firstname` | `first_name` | `first_name` | `first_name` |
| `lastname` | `last_name` | `last_name` | `last_name` |
| `phone` | `phone` | `billing_phone` | `phone` |
| `address` | `address` | `billing_address_1` | `address` |
| `address` (2ª parte) | `address2` | `billing_address_2` | `address2` |
| `zip` | `zip` | `billing_postcode` | `zip` |
| `city` | `city` | `billing_city` | `city` |
| `country_of_the_contact` | `country` | `select2-billing_country-container` | `country` |
| `job_title_de` | `job_title` | `job_title_de` | — |

---

## 🛠️ Herramienta Auxiliar: `hs-migration.php`

### Propósito
Importación bulk de datos HubSpot a WordPress mediante CSV.

### Características
- 📤 Upload de CSV exportado desde HubSpot
- 🔗 Cross-linking por email (busca usuario WP)
- 🔄 Actualización bulk de metadatos
- 🔐 Solo accesible para administradores

### Ubicación
```
wp-site/hs-migration.php (raíz del proyecto)
```

### Campos Procesados
- `Courtesy DE` (título)
- `First Name` → `first_name`
- `Last Name` → `last_name`
- `Main speciality` → `speciality_de`
- `Origin` → `hs_origin`

---

## 🔗 Integración con Bases de Datos

### Tablas Clave

#### `wpgr_users`
```sql
SELECT user_id, user_email, user_login FROM wpgr_users;
```
- Usuario principal de WordPress
- Usado para búsqueda por email en sincronización

#### `wpgr_usermeta`
```sql
SELECT user_id, meta_key, meta_value FROM wpgr_usermeta 
WHERE meta_key LIKE 'billing_%' OR meta_key LIKE 'mgmit_hs%';
```
- Metadatos de usuario (WooCommerce + custom)
- **Prefijo `billing_`** para campos WooCommerce
- **Prefijo `mgmit_hs`** para control de onboarding

#### `wpgr_swpm_members_tbl`
```sql
SELECT member_id, user_id, first_name, last_name, phone, address 
FROM wpgr_swpm_members_tbl;
```
- Datos de miembros SWPM
- Campos sin prefijo `billing_`
- Sincronización opcional (solo si existe registro)

#### `wp_options`
```sql
SELECT option_id, option_name, option_value FROM wp_options 
WHERE option_name LIKE 'mgmit%' OR option_name LIKE '%hubspot%';
```
- Configuración del plugin HubSpot Bridge
- Metadatos de control de onboarding
- Almacenamiento serializado en PHP

---

## ✅ Checklist de Validación para Cambios

Antes de hacer push o crear PR:

- [ ] **Aprobación** — Usuario confirmó el cambio
- [ ] **PHP 7.4** — Sin union types, constructor promotion, etc.
- [ ] **Compatibilidad** — Testeado en PHP 7.4
- [ ] **Changelog** — Entrada agregada en CHANGELOG.md
- [ ] **Documentación** — Se actualizó documentación técnica
- [ ] **Testing** — Feature testeada end-to-end
- [ ] **Logging** — Debug messages con prefijo `[MGMIT_*]`
- [ ] **Security** — Sanitización y validación de inputs
- [ ] **Performance** — No se agregaron queries N+1 innecesarias

---

**Última actualización:** 2026-04-22 (v1.3.1)

Para instrucciones específicas del agente, consultar `CLAUDE.md` en la raíz del proyecto.
