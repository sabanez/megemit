/**
 * Onboarding Enforcement Logic
 * @description Handles the UI and browser-side logic for mandatory profile completion.
 * This is specific to this project's workflow.
 */

(function($) {
    'use strict';

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

    const setupSubmitInterceptor = () => {
        // Intercept SWPM registration form
        $(document).on('submit', '#registro-profesional-13, #swpm-registration-form, .swpm-registration-form', function() {
            const date = new Date();
            date.setTime(date.getTime() + (24*60*60*1000));
            document.cookie = "mgmit_hs_pending_token=1; expires=" + date.toUTCString() + "; path=/";
            console.log('[Onboarding] Security token placeholder set via JS');
        });
    };

    $(document).ready(() => {
        handleEnforcedNavigation();
        setupSubmitInterceptor();
    });

})(jQuery);
