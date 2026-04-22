# 📑 Índice Completo de Documentación

Mapa de navegación de toda la documentación del proyecto MeGeMIT.

---

## 🌳 Estructura de la Carpeta `docs/`

```
docs/
├── README.md                          ← COMIENZA AQUÍ (visión general)
├── GETTING_STARTED.md                 ← Guía para nuevos desarrolladores
├── ARCHITECTURE.md                    ← Especificación técnica completa
├── CHANGELOG.md                       ← Historial de cambios (v1.0.0 → v1.3.1)
├── HUBSPOT_INTEGRATION.md             ← Guía de integración HubSpot (detallada)
├── HUBSPOT_BRIDGE_PLUGIN_PLAN.md      ← Blueprint del plugin (especificación)
└── INDEX.md                           ← Este archivo (navegación)
```

---

## 🎯 Guía de Navegación por Necesidad

### "Acabo de llegar al proyecto"
```
1. README.md           (5 min)  — Qué es este proyecto
2. GETTING_STARTED.md  (10 min) — Conceptos clave
3. ARCHITECTURE.md     (20 min) — Cómo está construido
```

### "Necesito trabajar en backend"
```
1. ARCHITECTURE.md              — Reglas de PHP 7.4 + estructura
2. HUBSPOT_INTEGRATION.md       — Endpoints REST + BD
3. Revisar código:
   - wp-content/plugins/mgmit-hubspot-bridge/
   - wp-content/themes/basel-child/inc/hubspot-sync/
```

### "Necesito trabajar en frontend"
```
1. HUBSPOT_INTEGRATION.md       — Flujos de usuario
2. GETTING_STARTED.md           — Tareas comunes
3. Revisar código:
   - assets/js/hubspot_map.js           (WP → HubSpot)
   - assets/js/admin-mapper.js          (Panel admin)
   - inc/hubspot-sync/form-capture.js   (HubSpot → WP)
   - inc/onboarding-enforcement.js      (Bloqueo)
```

### "Necesito entender el plugin HubSpot Bridge"
```
1. HUBSPOT_BRIDGE_PLUGIN_PLAN.md  — Arquitectura + fases
2. ARCHITECTURE.md                 — Cómo funciona en el proyecto
3. CHANGELOG.md                    — Historial (v1.3.0)
```

### "Necesito hacer debugging"
```
1. HUBSPOT_INTEGRATION.md          → Sección "Debugging"
2. GETTING_STARTED.md              → "Debugging: Sincronización No Funciona"
3. Herramientas: F12 Console + wp-content/debug.log
```

### "Necesito agregar una nueva funcionalidad"
```
1. GETTING_STARTED.md              → "Tareas Comunes"
2. ARCHITECTURE.md                 → Reglas de trabajo
3. CHANGELOG.md                    → Cómo documentar
4. Código → Cambio → Documento
```

---

## 📄 Contenido de Cada Archivo

### **README.md** (9 KB)
**Propósito:** Visión general ejecutiva del proyecto

**Contiene:**
- Descripción rápida de componentes
- Stack tecnológico
- Estructura de carpetas
- Componentes principales (plugin + tema)
- Metadatos de control de usuario
- Endpoints REST
- Herramientas de debugging

**Leer si:** Necesitas entender qué es el proyecto en 5 minutos.

---

### **GETTING_STARTED.md** (9 KB)
**Propósito:** Onboarding rápido para nuevos desarrolladores

**Contiene:**
- 5 conceptos clave en 5 minutos
- Documentación por rol (backend, frontend, product)
- Configuración local
- Tareas comunes (con pasos)
- Debugging
- Reglas críticas (PHP 7.4)
- Workflow de aprobación
- FAQ

**Leer si:** Es tu primer día o necesitas resolver una tarea rápido.

---

### **ARCHITECTURE.md** (17 KB)
**Propósito:** Especificación técnica completa y definitiva

**Contiene:**
- Stack tecnológico detallado
- Reglas de conducta del agente (6 reglas)
- Arquitectura del sistema (diagrama ASCII)
- Especificación del plugin HubSpot Bridge
- Especificación del tema hijo basel-child
- Módulos de onboarding + sincronización
- Metadatos de control (base de datos)
- Página de control `/registrierungsdetails/`
- Flujos de datos (WP → HubSpot, HubSpot → WP)
- Tabla de campos (mapeo completo)
- Integración con bases de datos
- Checklist de validación

**Leer si:** Necesitas entender cómo funciona TODO o implementar algo importante.

---

### **CHANGELOG.md** (8 KB)
**Propósito:** Historial cronológico de cambios

**Contiene:**
- v1.3.1 (2026-04-22) — Sincronización HubSpot → WordPress
- v1.3.0 (2026-04-21) — Visual Mapper UI
- v1.2.2 (2026-04-21) — Auto-login post-onboarding
- v1.2.1 (2026-04-20) — Mapeo de formulario perfil
- v1.2.0 (2026-04-17) — Módulo independiente de onboarding
- v1.1.0 (2026-04-16) — Blindaje de onboarding (triple seguro)
- v1.0.0 — Inicio del proyecto

**Leer si:** Necesitas saber qué cambios se hicieron, cuándo y por qué.

---

### **HUBSPOT_INTEGRATION.md** (9 KB)
**Propósito:** Guía técnica detallada de la integración HubSpot

**Contiene:**
- **Bidireccional (2 flujos):**
  - WP → HubSpot: Shadow Fields, data-hs-ignore, mutación de IDs
  - HubSpot → WP: postMessage API, REST endpoint, sincronización a BD
- Onboarding obligatorio (registro en 2 pasos)
- Estructura modular `/inc/hubspot-sync/`
- Flujo completo diagrama
- Consideraciones técnicas (cross-origin, PHP 7.4, logging)
- Guía de debugging (consola, servidor, BD)

**Leer si:** Necesitas entender cómo funciona la integración HubSpot o hacer debugging.

---

### **HUBSPOT_BRIDGE_PLUGIN_PLAN.md** (4.5 KB)
**Propósito:** Blueprint y especificación del plugin mgmit-hubspot-bridge

**Contiene:**
- Arquitectura del sistema (patrón Singleton)
- 3 módulos principales: Core Engine, Admin UI, Field Mapper
- Persistencia en wp_options (schema de config)
- Experiencia de usuario (Admin UI/UX)
- Implementación por fases (Fase 1-4, con estado de completitud)
- Ventajas para el negocio

**Leer si:** Necesitas entender el plugin en profundidad o extender su funcionalidad.

---

### **INDEX.md** (Este archivo)
**Propósito:** Mapa de navegación de la documentación

**Contiene:**
- Estructura de carpeta
- Guías por necesidad
- Resumen de cada archivo
- Matriz: cuál documento leer según la tarea

---

## 📋 Matriz: Documento → Tarea

| Tarea | Documento | Sección |
|---|---|---|
| Entender el proyecto | README.md | Toda |
| Primeros pasos | GETTING_STARTED.md | "5 Minutos: Conceptos Clave" |
| Arquitectura completa | ARCHITECTURE.md | Toda |
| Reglas de PHP 7.4 | GETTING_STARTED.md | "Reglas Críticas" |
| Agregar campo a mapeo | GETTING_STARTED.md | "Tareas Comunes: Modificar Mapeo" |
| Nuevo formulario WP→HubSpot | GETTING_STARTED.md | "Tareas Comunes: Agregar Formulario" |
| Debugging sincronización | HUBSPOT_INTEGRATION.md | "Debugging de Sincronización" |
| Debugging general | GETTING_STARTED.md | "Tareas Comunes: Debugging" |
| Plugin especificación | HUBSPOT_BRIDGE_PLUGIN_PLAN.md | Toda |
| Onboarding flujo | HUBSPOT_INTEGRATION.md | "Flujo de Onboarding Obligatorio" |
| HubSpot postMessage | HUBSPOT_INTEGRATION.md | "Sincronización Automática" |
| REST endpoints | HUBSPOT_INTEGRATION.md | "Endpoints REST" |
| Metadatos BD | ARCHITECTURE.md | "Metadatos de Control" |
| Historial cambios | CHANGELOG.md | Toda |
| Workflow aprobación | GETTING_STARTED.md | "Workflow de Aprobación" |

---

## 🔗 Enlaces Rápidos

**Dentro de `docs/`:**
- [Ver README (Inicio)](README.md)
- [Ver GETTING_STARTED (Nuevos dev)](GETTING_STARTED.md)
- [Ver ARCHITECTURE (Especificación)](ARCHITECTURE.md)
- [Ver CHANGELOG (Historial)](CHANGELOG.md)
- [Ver HUBSPOT_INTEGRATION (Integración)](HUBSPOT_INTEGRATION.md)
- [Ver HUBSPOT_BRIDGE_PLUGIN_PLAN (Plugin)](HUBSPOT_BRIDGE_PLUGIN_PLAN.md)

**En raíz del proyecto:**
- [CLAUDE.md](../CLAUDE.md) — Instrucciones para agentes
- [agent.md](../agent.md) — Documentación de agentes

---

## 📝 Cómo Actualizar la Documentación

### Cuando Hagas un Cambio

1. **Edita el archivo de documentación relevante**
   ```
   Cambio en tema hijo        → HUBSPOT_INTEGRATION.md
   Cambio en plugin           → HUBSPOT_BRIDGE_PLUGIN_PLAN.md
   Cambio importante          → ARCHITECTURE.md + CHANGELOG.md
   Nuevos desarrolladores     → GETTING_STARTED.md
   ```

2. **Agrega entrada en CHANGELOG.md**
   ```markdown
   ## [1.3.2] - 2026-04-23
   
   ### Añadido
   - Descripción breve del cambio
   - Qué archivo cambió
   
   ### Notas Técnicas
   - Consideraciones importantes
   ```

3. **Sincroniza con docs/**
   ```bash
   cp docs/CHANGELOG.md ../../CHANGELOG.md
   # O viceversa, según dónde hagas el cambio primero
   ```

---

## ✅ Checklist: "Creo que leí todo"

- [ ] Leí README.md
- [ ] Leí GETTING_STARTED.md
- [ ] Entendí las 5 reglas de conducta
- [ ] Entendí la diferencia entre plugin (técnica) y tema (negocio)
- [ ] Entendí los 2 flujos HubSpot (WP→HS y HS→WP)
- [ ] Sé cómo debuggear
- [ ] Entendí la regla de PHP 7.4
- [ ] Leí ARCHITECTURE.md completamente
- [ ] Conozco dónde está el código que usaré

---

## 🎓 Rutas de Aprendizaje Recomendadas

### Ruta 1: Backend Developer (3 horas)
```
GETTING_STARTED.md       (10 min)
ARCHITECTURE.md          (60 min)
HUBSPOT_INTEGRATION.md   (60 min)
Código + Debugging       (30 min)
```

### Ruta 2: Frontend Developer (2.5 horas)
```
GETTING_STARTED.md       (10 min)
HUBSPOT_INTEGRATION.md   (45 min)
GETTING_STARTED.md       Tareas comunes (30 min)
Código + Debugging       (30 min)
```

### Ruta 3: Quick Ref (30 min)
```
README.md                (5 min)
GETTING_STARTED.md       (15 min)
Tareas específicas       (10 min)
```

### Ruta 4: Plugin Deep Dive (90 min)
```
HUBSPOT_BRIDGE_PLUGIN_PLAN.md  (45 min)
ARCHITECTURE.md                (30 min)
Código + Testing               (15 min)
```

---

## 💬 Notas Importantes

- **Documentación es fuente de verdad** — Si el código dice algo diferente, confía en la documentación (el código puede estar desactualizado).
- **Actualiza mientras trabajas** — No dejes actualizaciones de docs para "después".
- **Sé específico** — Cuando agregues a CHANGELOG, sé claro sobre QUÉ, POR QUÉ, DÓNDE.
- **Cross-reference** — Enlaza a secciones relevantes de otros documentos.

---

**Última actualización:** 2026-04-22 (v1.3.1)

¿Necesitas documentar algo nuevo? Crea el archivo, agrega un resumen aquí, y sincroniza con la raíz.
