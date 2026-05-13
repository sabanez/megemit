/**
 * HubSpot Bridge — Admin Mapper UI
 */

(function ($) {
    'use strict';

    // -------------------------------------------------------------------------
    // Guardar mapeo
    // -------------------------------------------------------------------------

    $('#mgmit-mapping-form').on('submit', function (e) {
        e.preventDefault();

        var $btn = $('#mgmit-save-btn');
        $btn.prop('disabled', true).text(MGMIT_Admin.strings.saving);

        var fields = [];
        $('#mgmit-fields-body .mgmit-field-row').each(function () {
            var wp  = $(this).find('.mgmit-wp-field').val().trim();
            var hs  = $(this).find('.mgmit-hs-prop').val().trim();
            if (wp && hs) {
                fields.push({ wp_field: wp, hs_prop: hs });
            }
        });

        $.post(MGMIT_Admin.ajaxurl, {
            action:           'mgmit_save_mapping',
            security:         MGMIT_Admin.nonce_save,
            id:               $('#mgmit-mapping-id').val(),
            name:             $('#mgmit-name').val().trim(),
            formId:           $('#mgmit-form-selector').val().trim(),
            hubspotFormName:  $('#mgmit-hs-name').val().trim(),
            fields:           fields,
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

    // -------------------------------------------------------------------------
    // Añadir fila de campo
    // -------------------------------------------------------------------------

    $('#mgmit-add-row').on('click', function () {
        var row = '<tr class="mgmit-field-row">' +
            '<td><input type="text" class="mgmit-wp-field widefat code" placeholder="swpm-472"></td>' +
            '<td><input type="text" class="mgmit-hs-prop widefat code" placeholder="firstname"></td>' +
            '<td style="text-align:center;">' +
            '<button type="button" class="button button-small mgmit-remove-row" style="color:#b32d2e;border-color:#b32d2e;">&#10005;</button>' +
            '</td></tr>';
        $('#mgmit-fields-body').append(row);
    });

    // -------------------------------------------------------------------------
    // Eliminar fila de campo
    // -------------------------------------------------------------------------

    $('#mgmit-fields-body').on('click', '.mgmit-remove-row', function () {
        $(this).closest('tr').remove();
    });

    // -------------------------------------------------------------------------
    // Eliminar mapeo completo
    // -------------------------------------------------------------------------

    $(document).on('click', '.mgmit-delete-mapping', function () {
        if (!confirm(MGMIT_Admin.strings.confirm_delete)) return;

        var $btn = $(this);
        var id   = $btn.data('id');
        $btn.prop('disabled', true).text(MGMIT_Admin.strings.deleting);

        $.post(MGMIT_Admin.ajaxurl, {
            action:   'mgmit_delete_mapping',
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

    // -------------------------------------------------------------------------
    // Helper
    // -------------------------------------------------------------------------

    function showNotice(type, msg) {
        var cls = type === 'error' ? 'notice-error' : 'notice-success';
        $('#mgmit-save-notice')
            .removeClass('notice-error notice-success')
            .addClass('notice ' + cls)
            .html('<p>' + msg + '</p>')
            .show();
    }

})(jQuery);
