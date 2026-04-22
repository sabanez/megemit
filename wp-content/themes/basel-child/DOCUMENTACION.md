# 📚 Documentación del Tema Basel Child

## ⚠️ Documentación Centralizada

Toda la documentación técnica está centralizada en:

**`docs/` en la raíz del proyecto**

---

## 🔗 Documentos Relevantes para Este Tema

### Integración HubSpot
👉 **[Ver: docs/HUBSPOT_INTEGRATION.md](../../docs/HUBSPOT_INTEGRATION.md)**
- Flujos de sincronización (WP ↔ HubSpot)
- Onboarding obligatorio
- Módulo `/inc/hubspot-sync/`

### Arquitectura Completa
👉 **[Ver: docs/ARCHITECTURE.md](../../docs/ARCHITECTURE.md)**
- Estructura del tema hijo
- Metadatos de control
- Página de control `/registrierungsdetails/`
- Sincronización de bases de datos

### Para Nuevos Desarrolladores
👉 **[Ver: docs/GETTING_STARTED.md](../../docs/GETTING_STARTED.md)**
- Tareas comunes
- Debugging

---

## 📁 Estructura del Tema

```
basel-child/
├── functions.php                  # Entry point
├── inc/
│   ├── onboarding-enforcement.js  # Bloqueo de navegación
│   └── hubspot-sync/              # Módulo de sincronización
│       ├── loader.php
│       ├── handler.php
│       └── form-capture.js
├── DOCUMENTACION.md               # Este archivo
└── ...
```

---

## 📞 Índice General

Para una visión completa de toda la documentación:
👉 **[docs/00-START-HERE.md](../../docs/00-START-HERE.md)**

---

**Última actualización:** 2026-04-22
