/**
 * HubSpot Form Bridge - Field Mapper Module (v6.0)
 * @author Senior Developer
 * @description Modulo puro de mapeo de campos entre formularios locales y HubSpot.
 */

const HubSpotMapper = (function($) {
    'use strict';

    const registry = window.HS_CONFIG || [];

    const setupForm = ($form, config) => {
        console.log(`[HS Mapper] Configurando mapper para: ${config.hubspotFormName}`);
        
        // 1. Guardamos los datos originales
        const originalId = $form.attr('id');
        const originalClasses = $form.attr('class');
        $form.attr('data-original-id', originalId);
        $form.attr('data-original-classes', originalClasses);

        // 2. Mutación inicial (ID y Name para que HubSpot identifique el form)
        $form.attr('id', config.hubspotFormName); 
        $form.attr('name', config.hubspotFormName);
        $form.attr('data-hs-form-id', config.hubspotFormName);

        // 3. Crear Shadow Fields y sincronizar
        Object.keys(config.mapping).forEach(sourceName => {
            const targetName = config.mapping[sourceName];

            // Marcamos el campo original para que HubSpot lo ignore
            $form.find(`[name="${sourceName}"]`).attr('data-hs-ignore', 'true');

            // Creamos un campo oculto con el nombre que HubSpot reconoce (targetName)
            if ($form.find(`input[name="${targetName}"]`).length === 0) {
                $('<input>').attr({
                    type: 'hidden',
                    name: targetName,
                    'data-hs-bridge': targetName
                }).appendTo($form);
            }
        });

        injectHubSpotContext($form);
        syncAllFields($form, config.mapping);

        // 4. EVENTOS: Sincronización y Envio
        $form.on('submit', function() {
            syncAllFields($form, config.mapping);
            // Limpieza de clases CSS del tema para no interferir con el selector de HubSpot
            $form.attr('class', ''); 
            return true;
        });

        $form.on('input change', 'input, select, textarea', function() {
            syncAllFields($form, config.mapping);
        });
    };

    const syncAllFields = ($form, mapping) => {
        Object.keys(mapping).forEach(sourceName => {
            const targetName = mapping[sourceName];
            const value = getNormalizedValue($form, sourceName);
            $form.find(`input[data-hs-bridge="${targetName}"]`).val(value);
        });
    };

    const getNormalizedValue = ($form, sourceName) => {
        const $fields = $form.find(`[name="${sourceName}"]`);
        if ($fields.length === 0) return null;
        const values = [];
        $fields.each(function() {
            const $el = $(this);
            if (($el.is(':checkbox') || $el.is(':radio')) && $el.is(':checked')) {
                values.push($el.val());
            } else if ($el.is('select') && $el.prop('multiple')) {
                const selectedOptions = $el.val();
                if (selectedOptions) values.push(...selectedOptions);
            } else if (!$el.is(':checkbox') && !$el.is(':radio')) {
                const val = $el.val();
                if (val !== undefined) values.push(val);
            }
        });
        return values.join(';');
    };

    const injectHubSpotContext = ($form) => {
        const match = document.cookie.match(/hubspotutk=([^;]+)/);
        const context = {
            hutk: match ? match[1] : null,
            pageName: document.title,
            pageUrl: window.location.href
        };
        if ($form.find('input[name="hs_context"]').length === 0) {
            $('<input>').attr({ type: 'hidden', name: 'hs_context' }).appendTo($form);
        }
        $form.find('input[name="hs_context"]').val(JSON.stringify(context));
    };

    return {
        init: function() {
            $(document).ready(() => {
                registry.forEach(config => {
                    const $form = $(config.formId);
                    if ($form.length > 0) {
                        setupForm($form, config);
                    }
                });
            });
        }
    };

})(jQuery);

HubSpotMapper.init();