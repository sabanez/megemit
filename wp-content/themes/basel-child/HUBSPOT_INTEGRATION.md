# WordPress to HubSpot Form Bridge (Custom Integration)

Este sistema permite conectar formularios nativos de WordPress (o de plugins como Simple WP Membership, Contact Form 7, etc.) con HubSpot de forma limpia, evitando la duplicidad de campos y asegurando que las propiedades se mapeen correctamente.

## Componentes del Sistema

1.  **`inc/hubspot_map.js`**: El motor lógico en JavaScript.
2.  **`functions.php`**: La base de datos de configuración y el cargador del script.

---

## Cómo Funciona (Lógica Técnica)

### 1. Shadow Fields (Campos Sombra)
Muchos plugins de WordPress generan nombres de campos complejos o dinámicos (ej: `swpm-472`). HubSpot no reconoce estos campos automáticamente. 
*   El script crea dinámicamente campos ocultos (`<input type="hidden">`) con nombres estándar de HubSpot (`firstname`, `email`, etc.).
*   Sincroniza en tiempo real el valor del campo original con el campo oculto.

### 2. Atributo `data-hs-ignore`
Para evitar que HubSpot recoja tanto el campo original como el campo sombra (lo que causaría datos duplicados o errores de mapeo), el script marca los campos originales con `data-hs-ignore="true"`. HubSpot ignorará estos campos por completo.

### 3. Mutación de IDs de Formulario
HubSpot utiliza el `id` del formulario para identificarlo. Si el plugin de formularios cambia el ID o usa uno genérico, el script inyecta el ID deseado antes del envío para asegurar que HubSpot clasifique el lead bajo el formulario correcto.

---

## Cómo Añadir un Nuevo Formulario

Para integrar un nuevo formulario, solo debes añadir un nuevo bloque al array `$config` dentro de la función `swpm_hubspot_mapper_script` en tu `functions.php`:

```php
array(
    'formId' => '#id-del-formulario',      // ID o clase CSS del formulario
    'hubspotFormName' => 'Nombre_Interno', // Cómo se verá en HubSpot
    'mapping' => array(
        'nombre_input_original' => 'propiedad_hubspot', // Ej: 'swpm-123' => 'email'
        'otro_input'            => 'firstname'
    )
)
```

---

## Ventajas para el Desarrollador

*   **Sin dependencias:** Solo requiere jQuery (estándar en WordPress).
*   **Agnóstico:** Funciona con cualquier plugin de formularios.
*   **SEO & Rendimiento:** El script es ligero y se carga de forma asíncrona al final de la página.
*   **Privacidad de Datos:** Al usar `data-hs-ignore`, tienes control total sobre qué datos se envían a HubSpot y cuáles se quedan solo en WordPress.

---

## Flujo de Onboarding Obligatorio (Registro en 2 Pasos)

Para mejorar la captación de datos, se ha implementado un sistema de "paso obligatorio" para los miembros del **Fachkreisbereich (Nivel 2)**.

### 1. Bloqueo de Navegación
Cuando un usuario completa el primer registro (SWPM), se le asigna el metadato `mgmit_hs_details_pending = 1`. 
*   **Consecuencia:** Mientras este valor sea `1`, cualquier intento de navegar fuera de `/registrierungsdetails/` resultará en una redirección forzosa de vuelta a dicha página.
*   **Aviso UX:** La redirección incluye el parámetro `?enforced=1`, que activa un Pop-up informativo en el frontend.

### 2. Desbloqueo (Finalización)
El usuario solo recupera la libertad de navegación cuando el servidor detecta el parámetro `hs_finish=1`.

**Configuración Requerida en HubSpot:**
El formulario de HubSpot en el paso 2 DEBE estar configurado para "Redirigir a otra página" tras el envío a la siguiente URL:
`https://dominio.com/registrierungsdetails/?hs_finish=1`

### 3. Pop-up de Aviso
El Pop-up está definido en `hubspot_map.js` y utiliza estilos en línea para evitar dependencias de CSS externas, asegurando que se vea correctamente incluso si hay problemas con las hojas de estilo del tema.

---

## Guía de Debugging

Si el mapeo no parece funcionar:
1.  Abre la consola del navegador (F12).
2.  Busca mensajes con el prefijo `[HS Mapper]`. 
3.  Si ves `Shadow field creado: [nombre]`, significa que el script ha encontrado el formulario y el campo con éxito.
4.  Si no ves nada, verifica que el `formId` en `functions.php` coincida exactamente con el ID que tiene la etiqueta `<form>` en el HTML.

---
*Desarrollado para MeGeMIT - Integración de Formularios de Membresía.*
