<?php

test('Media hooks', function() {
		// test.jpg doesn't exist but WordPress will still
		// create an attachment post
		$attachment_id = wp_insert_attachment(array(
				'post_title' => "This is an attachment",
		), 'test.jpg', 1);
		$data = get_data();
		expect($data)->toIncludeProperties(array(
				'event'    => 'added',
				'context'  => 'media',
				'log_info' => array(
					'id'    => $attachment_id,
					'title' => 'This is an attachment',
					'path',
				),
			));


		assert_snapshot($data, 'media-added', array(
				'path' => pathinfo($data['log_info']['path'], PATHINFO_DIRNAME),
				'file' => pathinfo($data['log_info']['path'], PATHINFO_FILENAME),
			));


		// Test data update
		wp_update_post(array(
				'ID'           => $attachment_id,
				'post_title'   => 'This is the post title',
				'post_content' => 'This is the updated content',
				'post_excerpt' => 'This is the updated excerpt',
			));

		$data = get_data();
		expect($data)->toIncludeProperties(array(
				'event'    => 'edited',
				'context'  => 'media',
				'log_info' => array(
					'id'      => $attachment_id,
					'changes' => array(
						'title'       => array('This is an attachment', 'This is the post title'),
						'description' => array('0', '27'),
						'caption'     => array('0', '27'),
					),
					'path',
				),
			));


		// Test deleting
		wp_delete_attachment($attachment_id, true);
		expect(get_data())->toIncludeProperties(array(
				'event'    => 'deleted',
				'context'  => 'media',
				'log_info' => array(
					'id'    => $attachment_id,
					'title' => 'This is the post title',
					'path',
				),
			));

		// @todo Test media manipulations
	});