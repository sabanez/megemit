/**
 * Project: MinervaKB
 * Copyright: 2017-2018 @KonstruktStudio
 */
(function($) {

    'use strict';

    var GLOBAL_DATA = window.MinervaKB;
    var ui = window.MinervaUI;
    var i18n = GLOBAL_DATA.i18n;

    // containers
    var $form = $('.js-mkb-sorting-form');

    function setupSorting() {
        $form.find('.fn-mkb-posts-wrap').each(function(index, el) {
            var $el = $(el);

            $el.sortable({
                items: '.fn-mkb-sorting-tree-post',
                axis: 'y'
            });
        })
    }

    /**
     * Handle sorting save
     * @param e
     */
    function handleSortingSave(e) {
        e.preventDefault();

        var $saveBtn = $(e.currentTarget);

        if ($saveBtn.hasClass('mkb-disabled')) {
            return;
        }

        $saveBtn.addClass('mkb-disabled');

        var tax = $form.data('taxonomy');
        var $tree = $form.find('.fn-mkb-sorting-tree');
        var $terms = $tree.find('.fn-mkb-posts-wrap');

        var store = [].reduce.call($terms, function(acc, item) {
            var $el = $(item);
            var termId = $el.data('termId');

            acc[termId] = [].map.call($el.find('.fn-mkb-sorting-tree-post'), function(post) {
                return post.dataset.id;
            });

            return acc;
        }, {});

        ui.fetch({
            action: 'mkb_save_sorting',
            taxonomy: tax,
            sorting: store
        }).always(function(response) {
            var text = $saveBtn.text();

            if (response.status == 1) {
                // error

                $saveBtn.text('Error');
                $saveBtn.removeClass('mkb-disabled').addClass('mkb-action-danger');

                ui.handleErrors(response);

            } else {
                // success

                $saveBtn.text('Success!');
                $saveBtn.removeClass('mkb-disabled').addClass('mkb-success');

                toastr.success('Order has been updated');
            }

            setTimeout(function() {
                $saveBtn.text(text);
                $saveBtn.removeClass('mkb-success mkb-action-danger');
            }, 700);
        }).fail(function() {
            toastr.error('Some error happened, try to refresh page');
        });

    }

    /**
     * Init
     */
    function init() {
        setupSorting();

        $('#mkb-plugin-sorting-save').on('click', handleSortingSave);

        $form.removeClass('mkb-loading');
    }

    $(document).ready(init);
})(jQuery);