(function ($, root, undefined) {
	'use strict';

	window.addEventListener('click', function (event) {
		if (
			!$(event.target).is('.filter-icon') &&
			!$(event.target).parents('.filter-icon').is('.filter-icon') &&
			!$(event.target).is('.filter-box-wrapper') &&
			!$(event.target).parents('.filter-box-wrapper').is('.filter-box-wrapper')
		) {
			$('.filter-box-wrapper').attr('data-show', 'false');
		}
	});

	jQuery('.filter-icon').on('click', function () {
		$(this).siblings('.filter-box-wrapper').attr('data-show', 'true');

		// Hide siblings filter box
		$(this).closest('th').siblings().find('.filter-box-wrapper').attr('data-show', 'false');
	});

	/* -------------------------------------------------------------------------- */
	/*                           Delete a single records                          */
	/* -------------------------------------------------------------------------- */
	jQuery('.delete-single-record').on('click', function () {
		const id = $(this).data('id');
		const iconElement = $(this);

		const { __ } = wp.i18n;
		const confirm_delete = confirm(__('This action will delete the selected log. Do you want to proceed?', 'nashaat'));
		if (confirm_delete !== true) {
			return;
		}
		const sendRequest = $.ajax({
			url: ajaxurl,
			method: 'POST',
			data: {
				action: 'delete_single_record',
				nonce: vars.nashaat_nonce,
				id,
			},
			beforeSend: function () {
				iconElement.closest('.single-action-wrapper').attr('data-spinner', 'active');
			},
		});

		sendRequest
			.done(function (response, textStatus, jqXHR) {
				// Hide element by wrapping the rows cells in a div then
				// hide the div and hide the cells padding
				iconElement.closest('tr').find('td').wrapInner("<div class='td_wrap'></div>");
				iconElement
					.closest('tr')
					.find('.td_wrap')
					.slideUp(300, function (index, element) {});

				// Hide cell padding
				iconElement
					.closest('tr')
					.find('td')
					.animate(
						{
							'padding-top': '0px',
							'padding-bottom': '0px',
						},
						// Remove the row
						() => iconElement.closest('tr').remove()
					);
			})
			.fail(function (response) {
				iconElement.notify(response.responseJSON, {
					position: 'left middle',
					className: 'error',
				});
				iconElement.closest('.single-action-wrapper').attr('data-spinner', '');
			})
			.always(function () {
				iconElement.closest('.single-action-wrapper').attr('data-spinner', '');
			});
	});
})(jQuery, this);
