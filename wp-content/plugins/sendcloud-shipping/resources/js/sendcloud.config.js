(function ($) {
    'use strict';
    $(document).ready(function () {
        $('.sc-expand').each(function (index) {
            $(this).click(function () {
                let targetName = $(this).attr('data-target');
                let target = $('.shipping-rates[data-name="'+targetName+'"]');
                if (target.size() > 0) {
                    if (target.hasClass('sc-hidden')) {
                        target.removeClass('sc-hidden');
                    } else {
                        target.addClass('sc-hidden');
                    }

                    replaceArrows($(this));
                }
            })
        });

        function replaceArrows(container) {
            let arrow = container.find('i.sc-arrow');
            if (arrow.hasClass('down')) {
                arrow.removeClass('down');
                arrow.addClass('right');
            } else {
                arrow.removeClass('right');
                arrow.addClass('down');
            }
        }
    });
})(jQuery);