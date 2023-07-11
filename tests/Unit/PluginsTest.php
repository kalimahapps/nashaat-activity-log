<?php

test('Plugins', function() {
		do_action("activated_plugin", 'akismet/akismet.php');
		$data = get_data();
		expect($data)->toIncludeProperties(array(
				'event'    => 'activated',
				'context'  => 'plugin',
				'log_info' => array(
					'name'    => 'Akismet Anti-Spam',
					'version' => '5.0.1',
				),
			));
		assert_snapshot($data, 'plugin-status');

		do_action("deactivated_plugin", 'akismet/akismet.php');
		$data = get_data();
		expect($data)->toIncludeProperties(array(
				'event'    => 'deactivated',
				'context'  => 'plugin',
				'log_info' => array(
					'name'    => 'Akismet Anti-Spam',
					'version' => '5.0.1',
				),
			));
		assert_snapshot($data, 'plugin-status');

		// @todo delete and install plugin
	});