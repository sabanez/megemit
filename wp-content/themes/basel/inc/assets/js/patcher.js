/* global baselConfig */

(function($) {
	'use strict';

	$(document).on('click', '.xts-patch-apply', function (e) {
		e.preventDefault();

		var $this = $(this);
		var patchesMap = $this.data('patches-map');
		var fileMap = [];

		for(var i = 0; i < patchesMap.length; i++) {
			fileMap[i] = 'basel/' + patchesMap[i];
		}

		var confirmation = confirm( 'These files will be updated: \r\r\n' + fileMap.join('\r\n') );

		if ( ! confirmation ) {
			return;
		}

		addLoading();
		cleanNotice();

		sendAjax($this.data('id'), function(response) {
			if ( 'undefined' !== typeof response.message ) {
				printNotice(response.status, response.message);
			}

			if ( 'undefined' !== typeof response.status && 'success' === response.status ) {
				$this.parents('.xts-patch-item').addClass('xts-applied');
				updatePatcherCounter();
			}

			removeLoading();
		});
	});

	$(document).on('click', '.xts-patch-apply-all', function (e) {
		e.preventDefault();

		var $applyAllBtn = $(this);
		var $patches     = $('.xts-patch-item:not(.xts-patch-title-wrapper):not(.xts-applied)').get();

		cleanNotice();

		if ( 0 === $patches.length ) {
			printNotice('success', 'All patches are applied.');
			return;
		}

		if ( ! confirm('Are you sure you want to download all patches?') ) {
			return;
		}

		$applyAllBtn.parent().addClass('xts-loading');
		addLoading();
		recursiveApply($patches);
	});

	// Helpers.
	function sendAjax(id, cb) {
		$.ajax({
			url    : baselConfig.ajaxUrl,
			data   : {
				action   : 'basel_patch_action',
				security : baselConfig.patcher_nonce,
				id       : id,
			},
			timeout: 1000000,
			error  : function() {
				printNotice('error', 'Something wrong with removing data. Please, try to remove data manually or contact our support center for further assistance.');
			},
			success: cb
		});
	}

	function recursiveApply($patches){
		var $applyAllBtn = $('.xts-patch-apply-all');

		if ( 0 === $patches.length ) {
			$applyAllBtn.parent().addClass('xts-applied');
			$applyAllBtn.parent().removeClass('xts-loading');
			removeLoading();

			return;
		}

		var $patch = $($patches.pop());
		var id     = $patch.find('.xts-patch-apply').data('id');

		sendAjax(id , function(response) {
			if ( 'undefined' !== typeof response.message && 'error' === response.status ) {
				$applyAllBtn.parent().removeClass('xts-loading');

				printNotice(response.status, response.message);
			}

			if ( 0 === $patches.length ) {
				printNotice('success', 'All patches are applied.');
			}

			if ( 'undefined' !== typeof response.status && 'success' === response.status ) {
				$patch.addClass('xts-applied');

				updatePatcherCounter();

				recursiveApply($patches);
			} else {
				removeLoading();
			}
		});
	}

	function printNotice(type, message) {
		$('.xts-notices-wrapper').append(`
			<div class="xts-notice xts-${type}">
				${message}
			</div>
		`);

		setTimeout(function(){
			$('.xts-notice').addClass('xts-hidden');
		}, 7000);
	}

	function cleanNotice() {
		$('.xts-notices-wrapper').text('');
	}

	function addLoading() {
		$('.basel-box-content').addClass('xtemos-loading');
	}

	function removeLoading() {
		$('.basel-box-content').removeClass('xtemos-loading');
	}

	function updatePatcherCounter() {
		var $counter = $('.xts-patcher-counter');

		if ($counter.length) {
			var $count = parseInt($counter.find('.patcher-count').text());

			if ( 1 === $count ) {
				$counter.hide();
			} else {
				$counter.find('.patcher-count').text(--$count);
			}
		}
	}

})(jQuery);