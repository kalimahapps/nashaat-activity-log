<?php

test('Menus', function() {

		// Delete menu if it exists
		$menu = wp_get_nav_menu_object('menu_test');
		if ($menu) {
			wp_delete_nav_menu($menu->term_id);
		}
		$menu_id = wp_create_nav_menu('menu_test');
		if (is_wp_error($menu_id)) {
			throw new Exception($menu_id->get_error_message());
		}

		$data = get_data();
		expect($data)->toIncludeProperties(array(
				'event'    => 'created',
				'context'  => 'menu',
				'log_info' => array(
					'name' => 'menu_test',
					'slug' => 'menu_test',
					'id'   => $menu_id,
				),
			));
		assert_snapshot($data, 'menu-created');

		// menu update is not working
		$menu_id = wp_update_nav_menu_object($menu_id, array(
				'menu-name' => 'testing2',
				'slug'      => 'testing2',
		));

		if (is_wp_error($menu_id)) {
			throw new Exception($menu_id->get_error_message());
		}

		$data = get_data();
		expect($data)->toIncludeProperties(array(
				'event'    => 'updated',
				'context'  => 'menu',
				'log_info' => array(
					'name' => 'testing2',
					'slug' => 'testing2',
					'id'   => $menu_id,
				),
			));

		assert_snapshot($data, 'menu-updated');

		// Test delete
		$menu_id = wp_delete_nav_menu($menu_id);
		if (is_wp_error($menu_id)) {
			throw new Exception($menu_id->get_error_message());
		}


		// @todo fix delete not being in callback in menu.class.php
		// expect(get_data())->toIncludeProperties(array(
	// 		'event'    => 'deleted',
	// 		'context'  => 'menu',
	// 		'log_info' => array(
	// 			'name' => 'menu_test',
	// 			'slug' => 'menu_test',
	// 			'id'   => $menu_id,
	// 		),
	// 	));

	});