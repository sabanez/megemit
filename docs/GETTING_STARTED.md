# 🚀 Getting Started — MeGeMIT WordPress

Guía rápida para nuevos desarrolladores que trabajan en el proyecto MeGeMIT.

---

## ⚡ 5 Minutos: Conceptos Clave

### 1. **El Proyecto en Una Frase**
MeGeMIT es un WordPress con integración bidireccional con HubSpot: captura datos de formularios WordPress → HubSpot, y sincroniza datos de formularios HubSpot → WordPress.

### 2. **Dos Áreas Principales**

#### 🔌 **Plugin: `mgmit-hubspot-bridge`** (Técnica)
Mapea cualquier formulario WordPress a propiedades HubSpot.
```
Formulario SWPM → Shadow Fields → HubSpot
```
📍 Ubicación: `wp-content/plugins/mgmit-hubspot-bridge/`

#### 🎨 **Tema: `basel-child`** (Negocio)
Onboarding obligatorio + sincronización de formularios HubSpot embebidos.
```
HubSpot Form → postMessage → REST Endpoint → WordPress
```
📍 Ubicación: `wp-content/themes/basel-child/`

### 3. **Base de Datos**
```
Nombre:   megemit_database
Prefix:   wpgr_
User:     root
Host:     localhost
Motor:    MySQL 8.4.8 (ServBay)
```

### 4. **Stack de Producción** (⚠️ Obligatorio)
```
PHP 7.4  ← NO 8.0+ features (union types, constructor promotion, etc.)
```

### 5. **Regla de Oro**
```
❌ NUNCA modificar sin aprobación
✅ SIEMPRE documentar en CHANGELOG.md
✅ SIEMPRE testear en PHP 7.4
```

---

## 📚 Documentación por Rol

### 👨‍💻 Desarrollador Backend

1. **Leer primero:** [ARCHITECTURE.md](ARCHITECTURE.md) — estructura completa
2. **Guía técnica:** [HUBSPOT_INTEGRATION.md](HUBSPOT_INTEGRATION.md) — endpoints y BD
3. **Reglas:** [ARCHITECTURE.md#reglas-de-conducta](ARCHITECTURE.md#reglas-de-conducta-del-agente) — PHP 7.4 + aprobación previa
4. **Debugging:** Consola con prefijo `[MGMIT_]` + `wp-content/debug.log`

**Archivos que probablemente tocarás:**
- `wp-content/plugins/mgmit-hubspot-bridge/includes/` — Lógica del plugin
- `wp-content/themes/basel-child/inc/hubspot-sync/` — Sincronización HubSpot → WP

### 🎨 Desarrollador Frontend

1. **Leer primero:** [ARCHITECTURE.md](ARCHITECTURE.md) — cómo funciona la integración
2. **Guía técnica:** [HUBSPOT_INTEGRATION.md](HUBSPOT_INTEGRATION.md) — onboarding + sync
3. **Scripts relevantes:**
   - `wp-content/plugins/mgmit-hubspot-bridge/assets/js/hubspot_map.js` — mapeo WP → HubSpot
   - `wp-content/plugins/mgmit-hubspot-bridge/assets/js/admin-mapper.js` — panel admin
   - `wp-content/themes/basel-child/inc/onboarding-enforcement.js` — bloqueo de navegación
   - `wp-content/themes/basel-child/inc/hubspot-sync/form-capture.js` — captura de eventos

### 📊 Product Manager / Stakeholder

1. **Leer:** [README.md](README.md) — visión general
2. **Flujos:** [HUBSPOT_INTEGRATION.md#flujo-completo-de-sincronización](HUBSPOT_INTEGRATION.md) — cómo el usuario experimenta el sistema
3. **Cambios:** [CHANGELOG.md](CHANGELOG.md) — qué se implementó y cuándo

---

## 🔧 Configuración Local

### Requisitos
```bash
# ServBay (macOS)
- PHP 8.3 (local development)
- MySQL 8.4.8
- WordPress 6.9.4
```

### Iniciar Proyecto
```bash
cd /Users/il115/Desktop/webs/megemit/wp-site

# 1. Verificar que WordPress carga
wp cli info

# 2. Activar plugins necesarios
wp plugin list
wp plugin activate mgmit-hubspot-bridge

# 3. Verificar la base de datos
wp db cli
# > SELECT COUNT(*) FROM wpgr_users;
```

### Habilitar Debug
```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_DISPLAY', false);
define('WP_DEBUG_LOG', true);

// Revisar logs
tail -f wp-content/debug.log
```

---

## 🎯 Tareas Comunes

### Modificar Mapeo de Campo

**Scenario:** "Necesito agregar un nuevo campo al mapeo HubSpot"

1. **Localizar dónde:** `wp-content/themes/basel-child/inc/hubspot-sync/form-capture.js`
2. **Ver mapeo actual:**
   ```javascript
   const fieldMap = {
       'firstname': 'first_name',
       // ... más campos
   };
   ```
3. **Agregar nuevo campo:**
   ```javascript
   const fieldMap = {
       'firstname': 'first_name',
       'nuevo_campo_hs': 'nuevo_campo_wp',  // ← Nuevo
   };
   ```
4. **Actualizar handler:** `wp-content/themes/basel-child/inc/hubspot-sync/handler.php`
   - Agregar lógica en `sync_user_data()`
   - Decidir si va a `usermeta` (con prefijo?) o `swpm_members_tbl`
5. **Testear:** Llenar formulario, revisar consola (`[HS Sync] ✅`)
6. **Documentar:** Agregar entrada en `CHANGELOG.md`

### Agregar Nuevo Formulario a Mapeo (WP → HubSpot)

**Scenario:** "Quiero mapear un nuevo formulario de SWPM a HubSpot"

1. **Panel Admin:** Ir a "HubSpot Bridge" en WP Admin
2. **Click:** "+ Nuevo Mapeo"
3. **Rellenar:**
   - Nombre descriptivo
   - Selector CSS del formulario (`#form-id`)
   - Nombre en HubSpot (`MeGeMIT_DE_NombreFormulario`)
4. **Agregar campos:** Table dinámica `[campo WP] → [propiedad HubSpot]`
5. **Guardar:** El sistema genera UUID automáticamente
6. **Testear:** Llenar el formulario, revisar HubSpot

**Sin código necesario.** El plugin maneja todo vía admin UI.

### Debugging: Sincronización No Funciona

**Síntomas:** Formulario HubSpot no sincroniza a WordPress

**Paso 1: Consola del Navegador (F12)**
```
✓ Buscar [HS Sync]
✓ Ver si hay ✅ o ❌
✓ Leer mensaje de error
```

**Paso 2: Verificar que el endpoint existe**
```bash
curl -X POST http://localhost/wp-json/mgmit/v1/sync-hubspot-data \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","data":{}}'
```

Esperado: `{"success":false,"message":"Usuario no encontrado"}`
Si dice `rest_no_route`: El endpoint no está registrado.

**Paso 3: Revisar logs del servidor**
```bash
tail -f wp-content/debug.log | grep MGMIT_HS
```

**Paso 4: Base de Datos**
```sql
-- ¿Usuario existe?
SELECT * FROM wpgr_users WHERE user_email = 'usuario@example.com';

-- ¿Datos están en usermeta?
SELECT * FROM wpgr_usermeta 
WHERE user_id = 123 AND meta_key LIKE 'billing_%';

-- ¿Registro SWPM existe?
SELECT * FROM wpgr_swpm_members_tbl WHERE user_id = 123;
```

---

## 📋 Reglas Críticas

### ❌ NO HAGAS ESTO

```php
// ❌ Union types (PHP 8.0+)
function sync($data: string|int): void {}

// ❌ Constructor promotion (PHP 8.0+)
public function __construct(private string $name) {}

// ❌ Match expressions (PHP 8.0+)
$result = match($status) {
    'pending' => 'Pendiente',
    'done' => 'Completado',
};

// ❌ Nullsafe operator (PHP 8.0+)
$email = $user?->getProfile()?->getEmail();

// ❌ Named arguments (PHP 8.0+)
function_name(param1: 'value', param2: 'value');
```

### ✅ HAZLO ASÍ (PHP 7.4)

```php
// ✅ Type hints básicos
function sync($data) {}

// ✅ Constructor clásico
private $name;
public function __construct($name) {
    $this->name = $name;
}

// ✅ If/else clásico
if ($status === 'pending') {
    $result = 'Pendiente';
} else {
    $result = 'Completado';
}

// ✅ isset() + array access
$email = isset($user) && isset($user['profile']) ? $user['profile']['email'] : null;

// ✅ Positional arguments
function_name('value1', 'value2');
```

---

## 🔐 Workflow de Aprobación

### Para Cualquier Cambio

1. **Proponer** — Describe qué cambios hará y por qué
2. **Esperar aprobación** — El usuario da luz verde ✅
3. **Implementar** — Hace el cambio
4. **Documentar** — Agrega entrada en `CHANGELOG.md`
5. **Testear** — Verifica que funciona
6. **Reportar** — Comparte resultados

### Ejemplo
```
Propuesta: "Necesito agregar campo 'company_name' al mapeo"
Usuario: "Adelante, asegúrate de que sea PHP 7.4 compatible"
Desarrollo: [implementa]
Documentación: [agrega a CHANGELOG.md v1.3.2]
Testing: "Completado, sincroniza correctamente"
```

---

## 📞 Recursos Rápidos

| Necesidad | Ubicación |
|---|---|
| Ver todas las versiones | [CHANGELOG.md](CHANGELOG.md) |
| Estructura completa | [ARCHITECTURE.md](ARCHITECTURE.md) |
| Endpoints REST | [HUBSPOT_INTEGRATION.md](HUBSPOT_INTEGRATION.md) |
| Especificación del plugin | [HUBSPOT_BRIDGE_PLUGIN_PLAN.md](HUBSPOT_BRIDGE_PLUGIN_PLAN.md) |
| Reglas de trabajo | [ARCHITECTURE.md#reglas-de-conducta](ARCHITECTURE.md#reglas-de-conducta-del-agente) |

---

## 🎓 Próximos Pasos

### Primer Día
- [ ] Leer [README.md](README.md) (5 min)
- [ ] Leer [ARCHITECTURE.md](ARCHITECTURE.md) (20 min)
- [ ] Explorar estructura de carpetas en WordPress
- [ ] Revisar `CHANGELOG.md` para ver qué se hizo recientemente

### Primer Cambio
- [ ] Identificar tarea específica
- [ ] Leer documentación relevante
- [ ] Proponer cambio (obtener aprobación)
- [ ] Implementar respetando PHP 7.4
- [ ] Documentar en `CHANGELOG.md`

### Luego
- [ ] Familiarizarse con endpoints REST
- [ ] Entender flujos de sincronización
- [ ] Aprender debugging con `[MGMIT_*]` logs

---

## ❓ Preguntas Frecuentes

**P: ¿Puedo usar PHP 8.0+ features?**  
R: No. Producción es PHP 7.4. Todos los cambios deben ser compatibles.

**P: ¿Dónde cambio la configuración de HubSpot?**  
R: Panel Admin → "HubSpot Bridge". Sin código necesario.

**P: ¿Qué pasa si me equivoco?**  
R: Nada grave. Siempre se pide aprobación antes. Revierte cambios si es necesario.

**P: ¿Cómo sé si mi cambio rompe algo?**  
R: Revisa `wp-content/debug.log` y la consola del navegador (F12). Busca `[MGMIT_]`.

**P: ¿Puedo agregar nuevas dependencias (librerías)?**  
R: Depende. Propón primero, obtén aprobación. Evita dependencias grandes.

---

**Última actualización:** 2026-04-22

¿Necesitas ayuda? Revisa la documentación específica o contacta al equipo principal.
