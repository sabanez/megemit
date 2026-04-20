# Blueprint: HubSpot Bridge Plugin for WordPress

**Autor:** Antigravity (Senior Full-Stack Developer)  
**Fecha:** 2026-04-20  
**Versión:** 1.0.0  
**Objetivo:** Migrar la lógica de integración de HubSpot del tema hijo `basel-child` a un plugin independiente, modular y configurable desde el panel de administración de WordPress. *(Nota: La lógica de onboarding estricto se mantiene separada y de manera exclusiva dentro del tema hijo para no mezclar responsabilidades de capa de negocio).*

---

## 1. Arquitectura del Sistema

### 1.1 Corazón del Plugin (Core Engine)
El plugin se basa en un patrón de diseño **Singleton** para el manejo de la instancia principal, asegurando que los hooks se registren una sola vez.

- **`MGMIT_Bridge` (Clase Principal):** Carga los módulos y define las constantes del plugin.
- **`MGMIT_Admin_UI`:** Gestiona la página de ajustes, renderizado de formularios y validación de entradas.
- **`MGMIT_Field_Mapper`:** Motor encargado de inyectar el JavaScript (`hubspot_map.js`) y pasar la configuración dinámica mediante `wp_localize_script`.

### 1.2 Persistencia de Datos (Jerarquía Formulario > Campos)
Utilizamos la tabla `wp_options` con un array serializado. La estructura es **estrictamente jerárquica**: el formulario actúa como "padre" y contiene todas sus reglas y mapeos de campos.

**Schema de Configuración (`mgmit_hubspot_config`):**
```json
[
  {
    "id": "uuid-v4",
    "name": "Registro Profesional Nivel 13", 
    "status": "enabled",
    "selector": "#registro-profesional-13", 
    "hubspot_id": "MeGeMIT_DE_Registration",
    "mapping": [
       { "wp_field": "swpm-472", "hs_prop": "firstname" },
       { "wp_field": "swpm-456", "hs_prop": "email" }
    ]
  }
]
```
> [!NOTE]
> Esta estructura permite que el plugin genere el objeto `HS_CONFIG` exactamente igual al que el script JS consume, garantizando una transición sin errores para la compatibilidad actual del Frontend.

---

## 2. Experiencia de Usuario (Admin UI/UX)

### 2.1 Dashboard Principal
Una tabla limpia que liste los "Bridges" activos, basado en un editor JSON con validación integrada.

### 2.2 Editor de Mapeo (Visual Mapper Jerárquico)
Interfaz visual accesible desde Ajustes:
- **Cabecera del Formulario:** Nombre, Selector CSS.
- **Cuerpo (Sub-nivel):** Una tabla dinámica de mapeo de campos.
    - **Selector Local:** El `name` o `id` del input en WordPress.
    - **Propiedad HubSpot:** Dropdown/texto plano con opciones estándar (`email`, `firstname`, etc.).

---

## 3. Implementación Técnica (Fase a Fase)

### Fase 1: Extracción y Limpieza (Foundation) ✔️ COMPLETADA
1. Creado el boilerplate del plugin: `mgmit-hubspot-bridge/mgmit-hubspot-bridge.php`.
2. Movido el archivo `hubspot_map.js` a `assets/js/`.

### Fase 2: Motor de Inyección Dinámica ✔️ COMPLETADA
1. Lógica que reemplaza el array estático `$config` en `functions.php` por una consulta a `get_option('mgmit_hubspot_config')`.
2. Emisión de datos hacia frontend con `wp_localize_script`.

### Fase 3: Interfaz de Administración (Settings API) ✔️ COMPLETADA
1. Implementada la interfaz `MeGeMIT HubSpot Bridge` en el menú del Backend.
2. Uso de AJAX y validación exhaustiva de JSON para modificar los mapeos directos a la base de datos sin necesidad de tocar ficheros de código.

### Fase 4: Inteligencia de Detección (Scraper de campos visual) (Pendiente/Opcional)
1. Crear una función que actúe como "Scraper" básico para construir sugerencias automáticas de mapeo evitando la escritura JSON literal.

---

## 4. Ventajas para el Negocio

| Beneficio | Descripción |
| :--- | :--- |
| **Independencia del Tema** | Si se cambia a un tema nuevo en el futuro, la integración de HubSpot (el plugin de Bridge) seguirá intacta e inmutable. |
| **Paz Arquitectónica** | "Onboarding" permanece como regla de negocio en el framework Visual/Tema. "Sincronización HubSpot" vive como un microservicio interno. |
| **Cero Código Manual** | Cualquier miembro del equipo de marketing podrá mapear un nuevo campo editando JSON desde Panel, sin FTPs ni IDEs. |
| **Escalabilidad** | Permite gestionar 2, 10 o 50 formularios distintos con la misma arquitectura ligera. |

---

## 5. Consideraciones Finales (Senior Notes)
- **Compatibilidad PHP 7.4:** El plugin respeta la restricción del entorno de producción.
- **Rendimiento:** El código solo se encola cuando está verificado el entorno, de manera ligera.
- **Hooks de Extensibilidad:** Uso de filtros (`apply_filters`) (`mgmit_hs_bridge_config`) para que otros desarrolladores puedan modificar dinámicamente el JSON si se precisa.
