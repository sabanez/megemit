/**
 * HubSpot Form Bridge - ID Mutation Version (v5.0)
 * @author Senior Developer
 * @description Forzamos a HubSpot a crear un nuevo formulario mutando el ID del DOM.
 * Componente REUTILIZABLE para cualquier WordPress.
 */

const HubSpotMapper = (function($) {
    'use strict';

    const registry = window.HS_CONFIG || [];

    const setupForm = ($form, config) => {
        // 1. Guardamos los datos originales por si acaso
        const originalId = $form.attr('id');
        const originalClasses = $form.attr('class');
        $form.attr('data-original-id', originalId);
        $form.attr('data-original-classes', originalClasses);

        // 2. Mutación inicial (ID y Name)
        $form.attr('id', config.hubspotFormName); 
        $form.attr('name', config.hubspotFormName);
        $form.attr('data-hs-form-id', config.hubspotFormName);

        // 3. Crear Shadow Fields y sincronizar
        Object.keys(config.mapping).forEach(sourceName => {
            const targetName = config.mapping[sourceName];

            // 1. Marcamos el campo original para que HubSpot lo ignore
            $form.find(`[name="${sourceName}"]`).attr('data-hs-ignore', 'true');

            // 2. Creamos un campo oculto con el nombre que HubSpot reconoce (targetName)
            if ($form.find(`input[name="${targetName}"]`).length === 0) {
                $('<input>').attr({
                    type: 'hidden',
                    name: targetName,
                    'data-hs-bridge': targetName
                }).appendTo($form);
                
                console.log(`[HS Mapper] Shadow field creado: ${targetName} para sincronizar with ${sourceName}`);
            }
        });

        injectHubSpotContext($form);
        syncAllFields($form, config.mapping);

        // 4. EVENTO SUBMIT: El truco de la limpieza
        $form.on('submit', function() {
            // Sincronizamos valores por última vez
            syncAllFields($form, config.mapping);

            // LIMPIEZA RADICAL: 
            // Quitamos todas las clases justo antes de que HubSpot capture el envío.
            $form.attr('class', ''); 
            
            console.log(`[HS Mapper] Identidad limpiada para el envío: ${config.hubspotFormName}`);
            return true;
        });

        // Sincronización en tiempo real
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