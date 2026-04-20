# Blueprint: HubSpot Bridge & Onboarding Plugin for WordPress

**Autor:** Antigravity (Senior Full-Stack Developer)  
**Fecha:** 2026-04-20  
**Versión:** 1.0.0  
**Objetivo:** Migrar la lógica de integración de HubSpot y el sistema de onboarding del tema hijo `basel-child` a un plugin independiente, modular y configurable desde el panel de administración de WordPress.

---

## 1. Arquitectura del Sistema

### 1.1 Corazón del Plugin (Core Engine)
El plugin se basará en un patrón de diseño **Singleton** para el manejo de la instancia principal, asegurando que los hooks se registren una sola vez.

- **`MGMIT_Bridge` (Clase Principal):** Carga los módulos y define las constantes del plugin.
- **`MGMIT_Admin_UI`:** Gestionará la página de ajustes, renderizado de formularios y validación de entradas.
- **`MGMIT_Field_Mapper`:** Motor encargado de inyectar el JavaScript (`hubspot_map.js`) y pasar la configuración dinâmica mediante `wp_localize_script`.
- **`MGMIT_Onboarding`:** Portabilidad de la lógica de bloqueo, tokens y sesiones PHP.

### 1.2 Persistencia de Datos (Jerarquía Formulario > Campos)
Utilizaremos la tabla `wp_options` con un array serializado. La estructura es **estrictamente jerárquica**: el formulario actúa como "padre" y contiene todas sus reglas y mapeos de campos.

**Schema de Configuración (`mgmit_hubspot_config`):**
```json
[
  {
    "id": "uuid-v4",
    "name": "Registro Profesional Nivel 13", 
    "status": "enabled",
    "selector": "#registro-profesional-13", 
    "hubspot_id": "MeGeMIT_DE_Registration",
    "onboarding_logic": true, 
    "mapping": [
       { "wp_field": "swpm-472", "hs_prop": "firstname" },
       { "wp_field": "swpm-456", "hs_prop": "email" }
    ]
  }
]
```
> [!NOTE]
> Esta estructura permite que el plugin genere el objeto `HS_CONFIG` exactamente igual al que el script JS consume actualmente, garantizando una transición sin errores.

---

## 2. Experiencia de Usuario (Admin UI/UX)

### 2.1 Dashboard Principal
Una tabla limpia que liste los "Bridges" activos. 
- **Botón "Auto-Detectar":** Escanea el DOM de las páginas seleccionadas o busca shortcodes conocidos (SWPM, CF7) para sugerir selectores CSS.

### 2.2 Editor de Mapeo (Visual Mapper Huarárquico)
Interfaz tipo "Acordeón" o "Card" donde cada tarjeta representa un formulario:
- **Cabecera del Formulario:** Nombre, Selector CSS y Toggle de Onboarding.
- **Cuerpo (Sub-nivel):** Una tabla dinámica de mapeo de campos.
    - **Selector Local:** El `name` o `id` del input en WordPress.
    - **Propiedad HubSpot:** Dropdown con opciones estándar (`email`, `firstname`, etc.).

---

## 3. Implementación Técnica (Fase a Fase)

### Fase 1: Extracción y Limpieza (Foundation)
1. Crear el boilerplate del plugin: `mgmit-hubspot-bridge/mgmit-hubspot-bridge.php`.
2. Mover los archivos `.js` a `assets/js/`.
3. Implementar el cargador de sesiones PHP (actualmente en `functions.php`) dentro del hook `init` del plugin.

### Fase 2: Motor de Inyección Dinámica
1. Desarrollar la lógica que reemplace el array estático `$config` en `functions.php` por una consulta a `get_option('mgmit_hubspot_config')`.
2. Mantener la compatibilidad con el sistema de tokens de seguridad para evitar roturas en el proceso de auto-login actual.

### Fase 3: Interfaz de Administración (Settings API)
1. Implementar la página de ajustes bajo el menú "Ajustes" o "MeGeMIT".
2. Usar AJAX para permitir añadir/eliminar filas de mapeo sin recargar la página.
3. **Pro Tip:** Añadir un "Preview" que simule cómo el script verá los campos en el frontend.

### Fase 4: Inteligencia de Detección
1. Crear una función que actúe como "Scraper" básico. Al añadir un nuevo bridge, el usuario pega la URL de la página del formulario, el plugin la visita (vía `wp_remote_get`) y extrae los `ID` y `name` de todos los `<input>`, autocompletando la Columna A del mapeador.

---

## 4. Ventajas para el Negocio

| Beneficio | Descripción |
| :--- | :--- |
| **Independencia del Tema** | Si se cambia a un tema nuevo en el futuro, la integración de HubSpot seguirá intacta. |
| **Cero Código Manual** | Cualquier miembro del equipo de marketing podrá mapear un nuevo campo sin llamar a un desarrollador. |
| **Seguridad Centralizada** | El control de sesiones y tokens se gestiona en un solo punto, reduciendo errores de colisión. |
| **Escalabilidad** | Permite gestionar 2, 10 o 50 formularios distintos con la misma arquitectura ligera. |

---

## 6. Mapa de Referencia Técnica (Código Fuente Actual)
Para facilitar la portabilidad, aquí se listan las ubicaciones exactas de la lógica funcional que debe ser migrada al plugin.

### 6.1 Backend (PHP en `functions.php`)
| Funcionalidad | Líneas | Descripción |
| :--- | :--- | :--- |
| **Registro y Sesiones** | 40-54 | Hook `user_register` y almacenamiento en `$_SESSION`. |
| **Bloqueo y Redirección** | 58-86 | Lógica de `template_redirect` hacia la página de onboarding. |
| **Captura de POST e Inicio** | 89-110 | Intercepción en `init` y generación dinámica de tokens. |
| **Auto-login y Limpieza** | 112-144 | Proceso de login automático tras éxito en HubSpot y liberación de bloqueo. |
| **Encolado y Localización** | 947-993 | Registro de scripts y el array `$config` que debe volverse dinámico. |

### 6.2 Frontend (JavaScript en `inc/`)
| Archivo | Responsabilidad | Líneas Clave |
| :--- | :--- | :--- |
| `hubspot_map.js` | **Motor de Mapeo** | Todo el archivo. Procesa el array `HS_CONFIG`. |
| `onboarding-enforcement.js` | **Intercepción UI** | Líneas 15-38 (Modal de aviso) y 44-73 (Interceptor de registro). |

---

## 5. Consideraciones Finales (Senior Notes)
- **Compatibilidad PHP 7.4:** El plugin debe respetar la restricción del entorno de producción.
- **Rendimiento:** Solo encolar los scripts en las páginas que contengan los selectores configurados para evitar "code bloat" en la Home o Landing pages sin formularios.
- **Hooks de Extensibilidad:** Incluir filtros (`apply_filters`) para que otros desarrolladores puedan modificar el comportamiento del mapeador en el futuro.

---
> [!IMPORTANT]
> Esta planificación preserva el 100% de la lógica de seguridad y onboarding actual, pero traslada el control del código a una interfaz visual soberana.
