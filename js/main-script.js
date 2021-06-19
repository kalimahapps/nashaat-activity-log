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
		console.log($(this).closest('th').siblings().find('.filter-box-wrapper'));
		$(this).closest('th').siblings().find('.filter-box-wrapper').attr('data-show', 'false');
	});
})(jQuery, this);
