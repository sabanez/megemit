# 📚 Documentación del Proyecto MeGeMIT — ÍNDICE PRINCIPAL

**Última actualización:** 2026-04-22 (v1.3.1)

---

## 🎯 ¿POR DÓNDE EMPIEZO?

### Opción 1: "Tengo 5 minutos"
👉 **[GETTING_STARTED.md](GETTING_STARTED.md)** → "5 Minutos: Conceptos Clave"

### Opción 2: "Soy nuevo en el proyecto"
👉 **[README.md](README.md)** → Visión general completa

### Opción 3: "Necesito trabajar ahora"
👉 **[GETTING_STARTED.md](GETTING_STARTED.md)** → Tu rol específico + tareas comunes

### Opción 4: "Necesito la especificación técnica"
👉 **[ARCHITECTURE.md](ARCHITECTURE.md)** → Arquitectura completa

### Opción 5: "¿Qué cambió recientemente?"
👉 **[CHANGELOG.md](CHANGELOG.md)** → Historial versiones

---

## 📁 Archivos en Esta Carpeta

```
docs/
├── 00-START-HERE.md                 ← TÚ ESTÁS AQUÍ
├── README.md                        ← Visión general ejecutiva (9 KB)
├── GETTING_STARTED.md               ← Guía rápida para nuevos devs (9 KB)
├── INDEX.md                         ← Mapa de navegación completo (10 KB)
├── ARCHITECTURE.md                  ← Especificación técnica definitiva (17 KB)
├── CHANGELOG.md                     ← Historial de cambios (8 KB)
├── HUBSPOT_INTEGRATION.md           ← Guía de integración HubSpot (9 KB)
└── HUBSPOT_BRIDGE_PLUGIN_PLAN.md    ← Blueprint del plugin (4.5 KB)

TOTAL: 88 KB de documentación
```

---

## 🗺️ Mapa Mental del Proyecto

```
MeGeMIT WordPress
│
├─ Plugin: mgmit-hubspot-bridge
│  ├─ Qué: Mapea formularios WP → HubSpot
│  ├─ Dónde: wp-content/plugins/
│  └─ Doc: HUBSPOT_BRIDGE_PLUGIN_PLAN.md
│
├─ Tema: basel-child
│  ├─ Qué: Onboarding + Sincronización HubSpot → WP
│  ├─ Dónde: wp-content/themes/basel-child/
│  └─ Doc: HUBSPOT_INTEGRATION.md
│
└─ Flujos
   ├─ WP → HubSpot (formularios)
   ├─ HubSpot → WP (sincronización)
   └─ Onboarding (bloqueo 2-pasos)
```

---

## 🚀 3 Pasos Rápidos

### 1️⃣ Entiende el Proyecto (10 min)
```
Abre → README.md
Lee → Primeros 500 palabras
Entiende → Qué es, stack, estructura
```

### 2️⃣ Conócete a Ti Mismo (5 min)
```
¿Eres backend?   → Ve a ARCHITECTURE.md
¿Eres frontend?  → Ve a HUBSPOT_INTEGRATION.md
¿Eres nuevo?     → Ve a GETTING_STARTED.md
```

### 3️⃣ Resuelve Tu Tarea (Var)
```
Tarea específica → Busca en "Tareas Comunes"
Necesitas debug  → Revisa sección Debugging
Necesitas ref    → Abre INDEX.md (matriz)
```

---

## 🎓 Rutas de Lectura Recomendadas

### Ruta "Soy Nuevo (30 min)"
1. **Este archivo** (2 min) ← Termina aquí
2. [README.md](README.md) (5 min)
3. [GETTING_STARTED.md](GETTING_STARTED.md) — "5 Minutos: Conceptos Clave" (5 min)
4. [GETTING_STARTED.md](GETTING_STARTED.md) — Tu rol específico (10 min)
5. Explora código, pregunta si algo no cuadra (8 min)

### Ruta "Soy Developer Backend (2 horas)"
1. [GETTING_STARTED.md](GETTING_STARTED.md) — Conceptos clave (10 min)
2. [ARCHITECTURE.md](ARCHITECTURE.md) — Secciones: Stack + Reglas + Plugin + Tema (90 min)
3. [HUBSPOT_INTEGRATION.md](HUBSPOT_INTEGRATION.md) — Endpoints + BD (40 min)

### Ruta "Soy Developer Frontend (1.5 horas)"
1. [GETTING_STARTED.md](GETTING_STARTED.md) (15 min)
2. [HUBSPOT_INTEGRATION.md](HUBSPOT_INTEGRATION.md) (60 min)
3. [ARCHITECTURE.md](ARCHITECTURE.md) — Tema hijo (25 min)

---

## 📋 Lista de Control Inicial

- [ ] Leí este archivo (00-START-HERE.md)
- [ ] Leí README.md
- [ ] Leí GETTING_STARTED.md
- [ ] Entendí los conceptos clave (5 min section)
- [ ] Leí la sección de mi rol
- [ ] Sé dónde está el código que usaré
- [ ] Entendí las 3 reglas críticas:
  - [ ] ✅ Aprobación previa antes de cambios
  - [ ] ✅ PHP 7.4 obligatorio (sin union types, etc.)
  - [ ] ✅ Todo se documenta en CHANGELOG.md

---

## ⚡ Respuestas Rápidas

**P: ¿Cuál es el archivo más importante?**  
R: [ARCHITECTURE.md](ARCHITECTURE.md) — es la fuente de verdad técnica.

**P: ¿Cuál es el más accesible?**  
R: [GETTING_STARTED.md](GETTING_STARTED.md) — diseñado para nuevos devs.

**P: ¿Dónde veo qué cambió?**  
R: [CHANGELOG.md](CHANGELOG.md) — historial completo de versiones.

**P: ¿Necesito leerlo TODO?**  
R: No. Usa [INDEX.md](INDEX.md) (matriz) para leer solo lo relevante.

**P: ¿Y si algo no está documentado?**  
R: Crea un issue o pregunta. La documentación se actualiza constantemente.

---

## 🔗 Enlaces Principales

| Necesidad | Archivo |
|---|---|
| **Empezar desde cero** | [README.md](README.md) |
| **Guía rápida** | [GETTING_STARTED.md](GETTING_STARTED.md) |
| **Especificación técnica** | [ARCHITECTURE.md](ARCHITECTURE.md) |
| **Cómo funciona HubSpot** | [HUBSPOT_INTEGRATION.md](HUBSPOT_INTEGRATION.md) |
| **Plugin especificación** | [HUBSPOT_BRIDGE_PLUGIN_PLAN.md](HUBSPOT_BRIDGE_PLUGIN_PLAN.md) |
| **Historial de cambios** | [CHANGELOG.md](CHANGELOG.md) |
| **Mapa de navegación** | [INDEX.md](INDEX.md) |

---

## 💡 Pro Tips

- 📌 **Bookmark [INDEX.md](INDEX.md)** — Es tu "tabla de contenidos" para encontrar cualquier cosa
- 🔍 **Usa Ctrl+F** — Los documentos son largos pero muy indexados
- 📝 **Si algo se siente mal, actualiza la doc** — Somos muy sensibles a docs desactualizadas
- 🚀 **Lee GETTING_STARTED antes de cualquier cambio** — Te ahorra tiempo después

---

## 📞 Recursos Externos

**En raíz del proyecto:**
- `CLAUDE.md` — Instrucciones para agentes
- `agent.md` — Documentación de agentes
- `CHANGELOG.md` — Copia sincronizada de esta carpeta

---

## ✨ Siguiente Paso

### 👉 **[Abre README.md →](README.md)**

O si prefieres algo más rápido:

### 👉 **[Abre GETTING_STARTED.md →](GETTING_STARTED.md)**

---

**¡Bienvenido al proyecto MeGeMIT!** 🎉

Si algo no queda claro, la documentación está diseñada para ser clara y accesible. Si encuentras algo confuso, créalo en la próxima versión.

Última actualización: **2026-04-22 (v1.3.1)**
