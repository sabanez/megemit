/**
 * HubSpot Form Mapper - Frontend Field Renaming (v1.0.0)
 * Renombra campos del formulario SWPM en el submit para compatibilidad con HubSpot embed script.
 * Sin llamadas server-side — integración 100% frontend.
 */

// Aplicar do_not_collect en DOMContentLoaded nativo (antes de que LeadIn escanee los forms)
document.addEventListener('DOMContentLoaded', function () {
    var registry = (window.MGMIT_MAPPER_CONFIG && Array.isArray(window.MGMIT_MAPPER_CONFIG))
        ? window.MGMIT_MAPPER_CONFIG : [];

    registry.forEach(function (config) {
        if (!config.do_not_collect) return;
        var el = document.querySelector(config.formId);
        if (!el) return;
        var form = (el.tagName === 'FORM') ? el : el.querySelector('form');
        if (form) {
            form.setAttribute('data-hs-do-not-collect', 'true');
        }
    });
});

var HubSpotMapper = (function($) {
    'use strict';

    var registry = window.MGMIT_MAPPER_CONFIG || [];

    var setupForm = function($form, config) {
        if (config.do_not_collect) {
            return; // bloqueado en DOMContentLoaded nativo, no hacer nada más
        }

        $form.data('hs-mapping', config.mapping);
        $form.data('hs-form-name', config.hubspotFormName);

        $form.attr('id', config.hubspotFormName);
        $form.attr('name', config.hubspotFormName);
        $form.attr('data-hs-form-id', config.hubspotFormName);

        injectHubSpotContext($form);

        $form.on('submit', function() {
            var mapping       = $form.data('hs-mapping');
            var originalNames = {};

            Object.keys(mapping).forEach(function(sourceName) {
                var targetName = mapping[sourceName];

                var $field = $form.find('input, select, textarea').filter(function() {
                    return this.name === sourceName;
                });

                if ($field.length > 0) {
                    originalNames[sourceName] = $field.attr('name');
                    $field.attr('name', targetName);
                    return;
                }

                // Checkbox array: buscar inputs cuyo name empiece por sourceName[
                var $checked = $form.find('input[type="checkbox"]').filter(function() {
                    return this.name.indexOf(sourceName + '[') === 0 && $(this).is(':checked');
                });

                if ($checked.length > 0) {
                    $checked.each(function() {
                        $(this).attr('name', targetName);
                    });
                }
            });

            $form.data('hs-original-names', originalNames);
            $form.attr('class', '');

            return true;
        });
    };

    var injectHubSpotContext = function($form) {
        var match   = document.cookie.match(/hubspotutk=([^;]+)/);
        var context = {
            hutk:    match ? match[1] : null,
            pageName: document.title,
            pageUrl:  window.location.href
        };

        if ($form.find('input[name="hs_context"]').length === 0) {
            $('<input>').attr({ type: 'hidden', name: 'hs_context' }).appendTo($form);
        }

        $form.find('input[name="hs_context"]').val(JSON.stringify(context));
    };

    return {
        init: function() {
            $(document).ready(function() {
                if (!Array.isArray(registry) || registry.length === 0) {
                    return;
                }
                registry.forEach(function(config) {
                    var $target = $(config.formId);
                    if ($target.length === 0) {
                        return;
                    }
                    // Si el selector no apunta directamente a un <form>, buscar dentro
                    var $form = $target.is('form') ? $target : $target.find('form').first();
                    if ($form.length > 0) {
                        setupForm($form, config);
                    }
                });
            });
        }
    };

})(jQuery);

HubSpotMapper.init();
