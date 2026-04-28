/**
 * HubSpot Form Bridge - Field Mapper Module (v7.0)
 * @author Senior Developer
 * @description Mapeo directo de nombres de campo en el submit para compatibilidad SWPM + HubSpot
 */

const HubSpotMapper = (function($) {
    'use strict';

    const registry = window.HS_CONFIG || [];

    const setupForm = ($form, config) => {
        console.log(`[HS Mapper] Configurando mapper para: ${config.hubspotFormName}`);

        // 1. Guardar mapeo inverso (targetName → sourceName) para restaurar después
        $form.data('hs-mapping', config.mapping);
        $form.data('hs-form-name', config.hubspotFormName);

        // 2. Establecer ID y name del formulario para HubSpot
        $form.attr('id', config.hubspotFormName);
        $form.attr('name', config.hubspotFormName);
        $form.attr('data-hs-form-id', config.hubspotFormName);

        // 3. Inyectar contexto de HubSpot
        injectHubSpotContext($form);

        // 4. SUBMIT: Cambiar names directamente antes de enviar
        $form.on('submit', function(e) {
            console.log('[HS Mapper] 📤 SUBMIT - Transformando nombres de campos...');

            const mapping = $form.data('hs-mapping');
            const originalNames = {}; // Guardar nombres originales para restaurar

            // Cambiar los names de los campos originales a los nombres que HubSpot espera
            Object.keys(mapping).forEach(sourceName => {
                const targetName = mapping[sourceName];

                // Coincidencia exacta (campos simples)
                const $field = $form.find('input, select, textarea').filter(function() {
                    return this.name === sourceName;
                });

                if ($field.length > 0) {
                    originalNames[sourceName] = $field.attr('name');
                    $field.attr('name', targetName);
                    console.log(`[HS Mapper] ✓ Campo renombrado: ${sourceName} → ${targetName} (valor: "${$field.val()}")`);
                    return;
                }

                // Campo array (checkbox group): buscar checkboxes cuyo name empiece por sourceName[
                const $checked = $form.find('input[type="checkbox"]').filter(function() {
                    return this.name.indexOf(sourceName + '[') === 0 && $(this).is(':checked');
                });

                if ($checked.length > 0) {
                    $checked.each(function() {
                        console.log(`[HS Mapper] ✓ Checkbox array renombrado: ${this.name} → ${targetName} (valor: "${$(this).val()}")`);
                        $(this).attr('name', targetName);
                    });
                } else {
                    console.log(`[HS Mapper] ℹ Campo array sin selección: ${sourceName} (no se envía)`);
                }
            });

            // Guardar nombres originales en el formulario (por si acaso necesitamos restaurar)
            $form.data('hs-original-names', originalNames);

            // Limpiar clases CSS del tema para no interferir con HubSpot
            $form.attr('class', '');

            return true; // Permitir submit
        });
    };

    const injectHubSpotContext = ($form) => {
        const match = document.cookie.match(/hubspotutk=([^;]+)/);
        const context = {
            hutk: match ? match[1] : null,
            pageName: document.title,
            pageUrl: window.location.href
        };

        if ($form.find('input[name="hs_context"]').length === 0) {
            $('<input>').attr({
                type: 'hidden',
                name: 'hs_context'
            }).appendTo($form);
        }

        $form.find('input[name="hs_context"]').val(JSON.stringify(context));
        console.log('[HS Mapper] Contexto HubSpot inyectado');
    };

    return {
        init: function() {
            $(document).ready(() => {
                registry.forEach(config => {
                    const $form = $(config.formId);
                    if ($form.length > 0) {
                        setupForm($form, config);
                    } else {
                        console.warn(`[HS Mapper] Formulario no encontrado: ${config.formId}`);
                    }
                });
            });
        }
    };

})(jQuery);

HubSpotMapper.init();