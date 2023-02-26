(function ($, root, undefined) {
	'use strict';
	/* -------------------------------------------------------------------------- */
	/*                            Process purge request                           */
	/* -------------------------------------------------------------------------- */
	jQuery('#purge_logs .button').on('click', function () {
		const { __ } = wp.i18n;
		const confirm_delete = confirm(__('This action will delete all log date. Do you want to proceed?', 'nashaat'));
		if (confirm_delete !== true) {
			return;
		}

		const button = $(this);

		const sendRequest = $.ajax({
			url: ajaxurl,
			data: {
				action: 'purge_log_data',
			},
			beforeSend: function () {
				const processing = button.attr('data-processing');
				button.text(processing).attr('disabled', true);
			},
		});

		sendRequest
			.done(function (response, textStatus, jqXHR) {
				button.notify(response, {
					position: 'top center',
					className: 'success',
				});
			})
			.fail(function () {
				button.notify(response, {
					position: 'top center',
					className: 'error',
				});
			})
			.always(function () {
				const originalText = button.attr('data-original-text');
				button.text(originalText).attr('disabled', false);
			});
	});
})(jQuery, this);
