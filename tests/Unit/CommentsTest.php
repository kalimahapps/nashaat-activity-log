<?php

test('Comments', function() {
		$comment = array(
			'comment_ID'      => '1',
			'comment_post_ID' => '1',
			'comment_author'  => 'admin',
			'comment_date'    => '2019-01-01 00:00:00',
			'user_id'         => '1',
		);

		do_action('edit_comment', 1, $comment);
		$data = get_data();
		expect($data)->toIncludeProperties(array(
				'event'    => 'edited',
				'context'  => 'comment',
				'log_info' => array(
					'post_id'   => '1',
					'post_type' => 'post',
					'id'        => '1',
				),
			));

		assert_snapshot($data, 'comment-edited');


		$comment_object = new WP_Comment((object) $comment);

		do_action('transition_comment_status', 'approved', '', $comment_object);
		$data = get_data();
		expect($data)->toIncludeProperties(array(
				'event'    => 'approved',
				'context'  => 'comment',
				'log_info' => array(
					'post_id'   => '1',
					'post_type' => 'post',
					'id'        => '1',
				),
			));
		assert_snapshot($data, 'comment-status');

		do_action('transition_comment_status', 'unapproved', '', $comment_object);
		$data = get_data();
		expect($data)->toIncludeProperties(array(
				'event'    => 'unapproved',
				'context'  => 'comment',
				'log_info' => array(
					'post_id'   => '1',
					'post_type' => 'post',
					'id'        => '1',
				),
			));
		assert_snapshot($data, 'comment-status');

		// Since no action is taken, get_data should return
		// the last action which is unapproved
		do_action('transition_comment_status', 'same', 'same', $comment_object);
		expect($data)->toIncludeProperties(array(
				'event'    => 'unapproved',
				'context'  => 'comment',
				'log_info' => array(
					'post_id'   => '1',
					'post_type' => 'post',
					'id'        => '1',
				),
			));

		assert_snapshot($data, 'comment-status');


	});