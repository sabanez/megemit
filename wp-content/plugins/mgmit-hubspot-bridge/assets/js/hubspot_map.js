(function () {
    'use strict';

    var registry = (window.HS_CONFIG && Array.isArray(window.HS_CONFIG)) ? window.HS_CONFIG : [];

    if (!registry.length) return;

    document.addEventListener('DOMContentLoaded', function () {
        registry.forEach(function (config) {
            var form = document.querySelector(config.formId);
            if (!form) return;

            form.setAttribute('data-hs-do-not-collect', 'true');

            if (!form.querySelector('input[name="mgmit_hs_form_id"]')) {
                var hidden = document.createElement('input');
                hidden.type  = 'hidden';
                hidden.name  = 'mgmit_hs_form_id';
                hidden.value = config.formId;
                form.appendChild(hidden);
            }
        });
    });

}());
