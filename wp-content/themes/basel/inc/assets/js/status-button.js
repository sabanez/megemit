/* global baselConfig */
/* global basel_discounts_notice */
(function($) {
	$(document).on('click', '.xts-switcher-btns .xts-switcher-btn', function() {
			var $switcher = $(this).parents('.xts-switcher-btns');

			$switcher.addClass('xts-loading');

			$.ajax({
				url     : baselConfig.ajaxUrl,
				method  : 'POST',
				data    : {
					action  : 'basel_change_post_status',
					id      : $switcher.data('id'),
					status  : 'publish' === $switcher.data('status') ? 'draft' : 'publish',
					security: $switcher.data('security')
				},
				dataType: 'json',
				success : function(response) {
					$switcher.replaceWith(response.new_html);
				},
				error   : function(error) {
					console.error(error);
				}
			});
		});
})(jQuery)
