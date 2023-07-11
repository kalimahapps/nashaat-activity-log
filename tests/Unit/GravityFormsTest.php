<?php


test("Gravity forms", function() {
		do_action('gform_post_form_restored', 1);
		do_action('gform_post_form_trashed', 1);
		do_action('gform_before_delete_form', 1);
		do_action('gform_post_form_activated', 1);
		do_action('gform_post_form_deactivated', 1);
		do_action('gform_post_form_views_deleted', 1);

		$form = array(
			'id'    => 1,
			'title' => 'test',
		);
		do_action('gform_after_save_form', $form, false);
		do_action('gform_after_save_form', $form, true);


		do_action('gform_post_form_duplicated', 1, 2);


		// Log import export
		do_action('check_admin_referer', 'test');
		do_action('check_admin_referer', 'gf_export_forms');

		$_POST['export_forms']['gf_form_id'] = array(1, 2, 3);
		do_action('check_admin_referer', 'gf_export_forms');

		expect(true)->toBe(true);
	});