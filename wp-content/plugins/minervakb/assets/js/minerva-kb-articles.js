/**
 * Project: Minerva KB
 * Copyright: 2015-2016 @KonstruktStudio
 */
(function($) {
    'use strict';

    var GLOBAL_DATA = window.MinervaKB;
    var ui = window.MinervaUI;
    var settings = GLOBAL_DATA.settings;
    var i18n = GLOBAL_DATA.i18n;

    function setupRelatedArticles() {
        var $addBtn = $('#mkb_add_related_article');
        var $relatedContainer = $('.fn-related-articles');

        $addBtn.on('click', function(e) {
            e.preventDefault();

            var btnText = $addBtn.text();

            $addBtn.text(i18n['loading']).attr('disabled', 'disabled');

            ui.fetch({
                action: 'mkb_get_articles_list',
                currentId: $addBtn.data('id')
            }).then(function(response) {
                var $related = $('<div class="mkb-related-articles__item"></div>');
                var $select = $('<select class="mkb-related-articles__select" name="mkb_related_articles[]"></select>');
                var articlesList = response.articles || [];

                articlesList.forEach(function(article) {
                    $select.append(
                        $('<option value="' + article.id + '">' + article.title + '</option>')
                    );
                });

                var $noRelatedMessage = $('.fn-no-related-message');

                $noRelatedMessage.length && $noRelatedMessage.remove();

                $related.append($select);
                $related.append(
                    $('<a class="mkb-related-articles__item-remove fn-related-remove mkb-unstyled-link" href="#">' +
                    '<i class="fa fa-close"></i>' +
                    '</a>')
                );

                $('.fn-related-articles').append($related);

                $addBtn.text(btnText).attr('disabled', false);
            });
        });

        $relatedContainer.sortable({
            'items': '.mkb-related-articles__item',
            'axis': 'y'
        });

        $relatedContainer.on('click', '.fn-related-remove', function(e) {
            e.preventDefault();

            var $link = $(e.currentTarget);

            $link.parents('.mkb-related-articles__item').remove();

            if ($relatedContainer.find('.mkb-related-articles__item').length === 0) {
                $relatedContainer.append(
                    $('<div class="fn-no-related-message mkb-no-related-message">' +
                        '<p>' + i18n['no-related'] + '</p>' +
                    '</div>'
                ));
            }
        });
    }

    function initFeedback() {
        $('#poststuff').on('click', '.fn-remove-feedback', function(e) {
            e.preventDefault();

            var $link = $(e.currentTarget);
            var $row = $link.parents('.mkb-article-feedback-item');

            $row.addClass('mkb-article-feedback-item--removing');

            ui.fetch({
                action: 'mkb_remove_feedback',
                feedback_id: parseInt($link.data('id'))
            }).then(function() {
                $row.slideUp('fast', function() {
                    $row.remove();
                });
            });
        });
    }

    function initReset() {
        $('.fn-mkb-article-reset-stats-btn').on('click', function(e) {
            e.preventDefault();

            var resetConfig = ui.getFormData($('.fn-mkb-article-reset-form'));

            if(!Object.keys(resetConfig).filter(function(key) {
                    return resetConfig[key] === true;
                }).length) {
                return;
            }

            if (!confirm('Confirm data reset')) {
                return;
            }

            ui.fetch({
                action: 'mkb_reset_stats',
                articleId: e.currentTarget.dataset.id,
                resetConfig: resetConfig
            }).then(function(response) {
                if (response.status == 0) {
                    toastr.success('Data was reset successfully.');
                } else {
                    toastr.error('Could not reset data, try to refresh the page');
                }
            });
        });
    }

    function init() {
        var $restrictContainer = $('#mkb-article-meta-restrict-id');

        setupRelatedArticles();
        initFeedback();
        initReset();

        ui.setupRolesSelector($restrictContainer);
    }

    $(document).ready(init);
})(jQuery);