/**
 * HubSpot Form Capture & Sync
 *
 * Escucha los eventos postMessage que emite el iframe de HubSpot
 * cuando el formulario embebido se envía, y sincroniza los datos
 * al endpoint REST de WordPress.
 *
 * Documentación oficial:
 * https://legacydocs.hubspot.com/global-form-events
 */

(function () {
    'use strict';

    console.log('[HS Sync] Script cargado, esperando eventos del formulario HubSpot...');

    // Escuchar mensajes desde el iframe de HubSpot
    window.addEventListener('message', function (event) {
        // Validar que el mensaje viene de HubSpot
        if (!event.data || event.data.type !== 'hsFormCallback') {
            return;
        }

        console.log('[HS Sync] Evento HubSpot recibido:', event.data.eventName, event.data);

        // Capturar el envío del formulario (contiene los campos rellenados)
        if (event.data.eventName === 'onFormSubmit') {
            handleFormSubmission(event.data);
        }
    });

    /**
     * Procesa los datos del formulario y los envía a WordPress
     */
    function handleFormSubmission(eventData) {
        // event.data.data es un array de objetos { name, value }
        const fields = eventData.data || [];

        if (!Array.isArray(fields) || fields.length === 0) {
            console.warn('[HS Sync] No hay campos en el evento');
            return;
        }

        // Convertir array [{name, value}] a objeto plano
        const formData = {};
        fields.forEach(field => {
            if (field.name) {
                formData[field.name] = field.value || '';
            }
        });

        console.log('[HS Sync] Datos capturados:', formData);

        // Preparar payload
        const payload = {
            email: formData.email || '',
            data: extractRelevantFields(formData)
        };

        if (!payload.email) {
            console.error('[HS Sync] Email no encontrado en el formulario');
            return;
        }

        // Enviar a WordPress (no bloquea el envío a HubSpot)
        syncToWordPress(payload);
    }

    /**
     * Extrae los campos relevantes del formulario
     * Mapea nombres específicos de HubSpot a nuestra estructura
     */
    function extractRelevantFields(formData) {
        const extracted = {};

        const fieldMap = {
            'firstname': 'first_name',
            'lastname': 'last_name',
            'phone': 'phone',
            'zip': 'zip',
            'city': 'city',
            'job_title_de': 'job_title',
            'country_of_the_contact': 'country',
            'address': 'address',
            'envelope_courtesy': 'billing_titel'
        };

        Object.keys(fieldMap).forEach(hsField => {
            if (formData[hsField]) {
                extracted[fieldMap[hsField]] = formData[hsField];
            }
        });

        // Procesar dirección (separar en address + address2)
        if (extracted.address) {
            const addressParts = splitAddress(extracted.address);
            extracted.address = addressParts.main;
            if (addressParts.secondary) {
                extracted.address2 = addressParts.secondary;
            }
        }

        return extracted;
    }

    /**
     * Divide una dirección completa en principal + secundaria
     */
    function splitAddress(fullAddress) {
        if (!fullAddress.includes(',')) {
            return { main: fullAddress, secondary: null };
        }

        const parts = fullAddress.split(',').map(p => p.trim());
        return {
            main: parts[0],
            secondary: parts.slice(1).join(', ') || null
        };
    }

    /**
     * Envía los datos al endpoint REST de WordPress
     */
    function syncToWordPress(payload) {
        console.log('[HS Sync] Enviando a WordPress:', payload);

        return fetch('/wp-json/mgmit/v1/sync-hubspot-data', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(payload)
        })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    console.log('[HS Sync] ✅ Sincronización exitosa:', result);
                } else {
                    console.error('[HS Sync] ❌ Error:', result);
                }
            })
            .catch(error => {
                console.error('[HS Sync] ❌ Error de red:', error);
            });
    }
})();
