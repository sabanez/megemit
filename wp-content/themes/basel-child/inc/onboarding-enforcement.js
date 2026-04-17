/**
 * Onboarding Enforcement System (v1.0)
 * @description Gestión de bloqueo de navegación, cookies de sesión y tokens de auto-login.
 */

const OnboardingEnforcement = (function($) {
    'use strict';

    /**
     * Gestión de navegación forzosa y aviso visual (Modal)
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

    /**
     * Preparación de tokens y marcadores antes del registro
     */
    const setupRegistrationInterceptor = () => {
        // Buscamos el formulario de registro (ID parcial o clase)
        const $regForm = $('#registro-profesional-13, #swpm-registration-form, .swpm-registration-form');
        
        if ($regForm.length > 0) {
            console.log('[Onboarding] Interceptor de registro activado.');

            const generateToken = () => {
                return Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
            };

            const setOnboardingMarkers = () => {
                const token = generateToken();
                console.log('[Onboarding] Estableciendo marcas de sesión...');
                
                // 1. Cookies (Legacy support / Client detection)
                document.cookie = "mgmit_hs_pending=1; path=/; max-age=86400";
                document.cookie = "mgmit_hs_login_token=" + token + "; path=/; max-age=86400";
                
                // 2. Campo oculto para el servidor (Prioritario para user_register hook)
                if ($regForm.find('input[name="mgmit_hs_token"]').length === 0) {
                    $regForm.append('<input type="hidden" name="mgmit_hs_token" value="' + token + '">');
                }
            };

            // Escuchar submit y click en el botón de envío
            $regForm.on('submit', setOnboardingMarkers);
            $regForm.find('input[type="submit"], button[type="submit"]').on('click', setOnboardingMarkers);
        }
    };

    return {
        init: function() {
            $(document).ready(() => {
                // 1. Manejar avisos de redirección forzosa
                handleEnforcedNavigation();

                // 2. Preparar interceptor si estamos en página de registro
                setupRegistrationInterceptor();

                // 3. Log de modo test
                const params = new URLSearchParams(window.location.search);
                if (params.has('hs_test')) {
                    console.log('[Onboarding] Modo simulación hs_test detectado.');
                }
            });
        }
    };

})(jQuery);

OnboardingEnforcement.init();
