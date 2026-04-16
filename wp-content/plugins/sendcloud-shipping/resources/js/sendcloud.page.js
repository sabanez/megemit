(function ($) {
    'use strict';
    $(document).ready(function () {
        let button = $('#sendcloud_shipping_connect .connect-button');
        button.click(function () {
            let data = {
                'action': 'get_redirect_sc_url'
            };
            $.post(ajaxurl, data, function (response) {
                if (response.redirect_url) {
                    window.open(response.redirect_url, '_blank');
                }
            });
        });
    });
})(jQuery);