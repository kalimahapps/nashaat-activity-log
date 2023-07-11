<?php

test('Options', function() {

		do_action("updated_option", "blogname", "old_name", "new_name");

		$data = get_data();
		expect($data)->toIncludeProperties(array(
				'event'    => 'updated',
				'context'  => 'options',
				'log_info' => array(
					'option_name' => 'blogname',
					'new_value'   => 'new_name',
					'old_value'   => 'old_name',
				),
			));
		assert_snapshot($data, 'option-updated');

		do_action("updated_option", "blogdescription", "old_desc", "new_desc");
		$data = get_data();
		expect($data)->toIncludeProperties(array(
				'event'    => 'updated',
				'context'  => 'options',
				'log_info' => array(
					'option_name' => 'blogdescription',
					'new_value'   => 'new_desc',
					'old_value'   => 'old_desc',
				),
			));
		assert_snapshot($data, 'option-updated');
	});