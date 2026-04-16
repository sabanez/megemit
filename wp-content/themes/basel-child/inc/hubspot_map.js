/**
 * HubSpot Form Bridge - ID Mutation Version (v5.0)
 * @author Senior Developer
 * @description Forzamos a HubSpot a crear un nuevo formulario mutando el ID del DOM.
 */

const HubSpotMapper = (function($) {
    'use strict';
    console.log('[HS Mapper] Script cargado y ejecutándose...');

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
                
                console.log(`[HS Mapper] Shadow field creado: ${targetName} para sincronizar con ${sourceName}`);
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
            // Solo dejamos una clase genérica si quisiéramos, o ninguna.
            $form.attr('class', ''); 
            
            // Opcional: Si quieres que HubSpot vea una clase "limpia":
            // $form.addClass('hs-submitted');

            console.log(`[HS Mapper] Identidad limpiada para el envío: ${config.hubspotFormName}`);
            return true;
        });

        // Sincronización en tiempo real (manteniendo clases para el diseño)
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

    /**
     * Gestión de navegación forzosa y avisos
     */
    const handleEnforcedNavigation = () => {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('enforced')) {
            const noticeHtml = `
                <div id="hs-enforcement-modal" style="position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); backdrop-filter:blur(5px); z-index:999999; display:flex; align-items:center; justify-content:center;">
                    <div style="background:white; padding:40px; border-radius:15px; max-width:500px; text-align:center; box-shadow:0 20px 40px rgba(0,0,0,0.3); font-family:sans-serif;">
                        <div style="font-size:50px; margin-bottom:20px;">📋</div>
                        <h2 style="margin-bottom:15px; color:#333; font-weight:700;">Profil vervollständigen</h2>
                        <p style="color:#666; font-size:16px; line-height:1.5; margin-bottom:25px;">
                            Um vollen Zugriff auf den Fachkreisbereich und unsere Tools zu erhalten, füllen Sie bitte das Formular auf dieser Seite aus. 
                            Dies ist für die Aktivierung Ihres Kontos erforderlich.
                        </p>
                        <button id="close-hs-modal" style="background:#f39910; color:white; border:none; padding:12px 30px; border-radius:30px; cursor:pointer; font-weight:bold; transition:all 0.3s ease;">
                            Verstanden
                        </button>
                    </div>
                </div>
            `;

            $('body').append(noticeHtml);

            $('#close-hs-modal').on('click', function() {
                $('#hs-enforcement-modal').fadeOut(300, function() {
                    $(this).remove();
                });
            });
        }
    };

    return {
        init: function() {
            $(document).ready(() => {
                registry.forEach(config => {
                    const $form = $(config.formId);
                    if ($form.length > 0) {
                        setupForm($form, config);
                        
                        // Si es el formulario de registro, ponemos la cookie al hacer clic en enviar
                        if (config.formId.includes('registro-profesional')) {
                            // 1. Escuchar el submit estándar
                            $form.on('submit', function() {
                                document.cookie = "mgmit_hs_pending=1; path=/; max-age=86400";
                            });

                            // 2. Escuchar el CLICK en el botón de submit
                            $form.find('input[type="submit"], button[type="submit"]').on('click', function() {
                                document.cookie = "mgmit_hs_pending=1; path=/; max-age=86400";
                            });
                        }
                    }
                });
                
                // Ejecutamos el aviso de navegación si existe el parámetro
                handleEnforcedNavigation();
            });
        }
    };

})(jQuery);

HubSpotMapper.init();