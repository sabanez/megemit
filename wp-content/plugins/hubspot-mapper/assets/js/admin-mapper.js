/**
 * HubSpot Mapper — Admin UI
 */

(function ($) {
    'use strict';

    // Toggle sección HubSpot según checkbox do-not-collect
    function toggleCollectSection() {
        var checked  = $('#mgmit-do-not-collect').is(':checked');
        var $section = $('#mgmit-hs-collect-section');
        if (checked) {
            $section.css({ opacity: '0.4', 'pointer-events': 'none' });
            $section.find('input, button').prop('disabled', true);
        } else {
            $section.css({ opacity: '', 'pointer-events': '' });
            $section.find('input, button').prop('disabled', false);
        }
    }

    $('#mgmit-do-not-collect').on('change', toggleCollectSection);
    toggleCollectSection();

    $('#mgmit-mapping-form').on('submit', function (e) {
        e.preventDefault();

        var $btn         = $('#mgmit-save-btn');
        var doNotCollect = $('#mgmit-do-not-collect').is(':checked');
        $btn.prop('disabled', true).text(MGMIT_Admin.strings.saving);

        var fields = [];
        var staticFields = [];
        if (!doNotCollect) {
            $('#mgmit-fields-body .mgmit-field-row').each(function () {
                var wp = $(this).find('.mgmit-wp-field').val().trim();
                var hs = $(this).find('.mgmit-hs-prop').val().trim();
                if (wp && hs) {
                    fields.push({ wp_field: wp, hs_prop: hs });
                }
            });
            $('#mgmit-static-fields-body .mgmit-static-field-row').each(function () {
                var prop   = $(this).find('.mgmit-sf-prop').val().trim();
                var val    = $(this).find('.mgmit-sf-value').val().trim();
                var append = $(this).find('.mgmit-sf-append').is(':checked') ? 1 : 0;
                if (prop) {
                    staticFields.push({ hs_prop: prop, value: val, append: append });
                }
            });
        }

        $.post(MGMIT_Admin.ajaxurl, {
            action:           'mgmit_mapper_save_mapping',
            security:         MGMIT_Admin.nonce_save,
            id:               $('#mgmit-mapping-id').val(),
            name:             $('#mgmit-name').val().trim(),
            formId:           $('#mgmit-form-selector').val().trim(),
            hubspotFormName:  doNotCollect ? '' : $('#mgmit-hs-form-name').val().trim(),
            source:           doNotCollect ? '' : $('#mgmit-source').val(),
            email_field:      doNotCollect ? '' : $('#mgmit-email-field').val().trim(),
            fields:           fields,
            static_fields:    staticFields,
            do_not_collect:   doNotCollect ? 1 : 0,
        })
        .done(function (res) {
            if (res.success) {
                window.location.href = res.data.redirect;
            } else {
                showNotice('error', res.data || MGMIT_Admin.strings.error);
                $btn.prop('disabled', false).text('Guardar Cambios');
            }
        })
        .fail(function () {
            showNotice('error', MGMIT_Admin.strings.error);
            $btn.prop('disabled', false).text('Guardar Cambios');
        });
    });

    $('#mgmit-add-row').on('click', function () {
        var row = '<tr class="mgmit-field-row">' +
            '<td><input type="text" class="mgmit-wp-field widefat code" placeholder="swpm-472"></td>' +
            '<td><input type="text" class="mgmit-hs-prop widefat code" placeholder="firstname"></td>' +
            '<td style="text-align:center;">' +
            '<button type="button" class="button button-small mgmit-remove-row" style="color:#b32d2e;border-color:#b32d2e;">&#10005;</button>' +
            '</td></tr>';
        $('#mgmit-fields-body').append(row);
    });

    $('#mgmit-fields-body').on('click', '.mgmit-remove-row', function () {
        $(this).closest('tr').remove();
    });

    $('#mgmit-add-static-row').on('click', function () {
        var row = '<tr class="mgmit-static-field-row">' +
            '<td><input type="text" class="mgmit-sf-prop widefat code" placeholder="hs_lead_source"></td>' +
            '<td><input type="text" class="mgmit-sf-value widefat code" placeholder="SWPM"></td>' +
            '<td style="text-align:center;"><input type="checkbox" class="mgmit-sf-append" value="1" title="Añadir al valor existente en lugar de sobreescribir"></td>' +
            '<td style="text-align:center;">' +
            '<button type="button" class="button button-small mgmit-remove-static-row" style="color:#b32d2e;border-color:#b32d2e;">&#10005;</button>' +
            '</td></tr>';
        $('#mgmit-static-fields-body').append(row);
    });

    $('#mgmit-static-fields-body').on('click', '.mgmit-remove-static-row', function () {
        $(this).closest('tr').remove();
    });

    $(document).on('click', '.mgmit-delete-mapping', function () {
        if (!confirm(MGMIT_Admin.strings.confirm_delete)) return;

        var $btn = $(this);
        var id   = $btn.data('id');
        $btn.prop('disabled', true).text(MGMIT_Admin.strings.deleting);

        $.post(MGMIT_Admin.ajaxurl, {
            action:   'mgmit_mapper_delete_mapping',
            security: MGMIT_Admin.nonce_delete,
            id:       id,
        })
        .done(function (res) {
            if (res.success) {
                window.location.href = res.data.redirect;
            } else {
                alert(res.data || MGMIT_Admin.strings.error);
                $btn.prop('disabled', false).text('Eliminar');
            }
        })
        .fail(function () {
            alert(MGMIT_Admin.strings.error);
            $btn.prop('disabled', false).text('Eliminar');
        });
    });

    function showNotice(type, msg) {
        var cls = type === 'error' ? 'notice-error' : 'notice-success';
        $('#mgmit-save-notice')
            .removeClass('notice-error notice-success')
            .addClass('notice ' + cls)
            .html('<p>' + msg + '</p>')
            .show();
    }

    // --- Credenciales HubSpot (vista de lista) ---
    $('#mgmit-save-creds').on('click', function () {
        var $btn      = $(this);
        var token     = $('#mgmit-access-token').val().trim();
        var portalId  = $('#mgmit-portal-id').val().trim();

        if (!token && !portalId) {
            showCredsNotice('error', 'Introduce el Portal ID y/o el Access Token.');
            return;
        }

        $btn.prop('disabled', true).text(MGMIT_Admin.strings.saving);

        $.post(MGMIT_Admin.ajaxurl, {
            action:       'mgmit_mapper_save_credentials',
            security:     MGMIT_Admin.nonce_creds,
            access_token: token,
            portal_id:    portalId,
        })
        .done(function (res) {
            if (res.success) {
                showCredsNotice('success', (res.data && res.data.message) || 'Guardado.');
                $('#mgmit-access-token').val('');
            } else {
                showCredsNotice('error', res.data || MGMIT_Admin.strings.error);
            }
            $btn.prop('disabled', false).text('Guardar Credenciales');
        })
        .fail(function () {
            showCredsNotice('error', MGMIT_Admin.strings.error);
            $btn.prop('disabled', false).text('Guardar Credenciales');
        });
    });

    function showCredsNotice(type, msg) {
        var cls = type === 'error' ? 'notice-error' : 'notice-success';
        $('#mgmit-creds-notice')
            .removeClass('notice-error notice-success')
            .addClass('notice ' + cls)
            .html('<p>' + msg + '</p>')
            .show();
    }

})(jQuery);
