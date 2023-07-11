<?php
test('Duplicate hooks', function() {
		$post['ID']         = 1;
		$post['post_title'] = 'test';
		$post['post_type']  = 'post';

		$prev_post = new WP_Post((object) $post);

		do_action('dp_duplicate_post', 2, $prev_post, 'draft');
		expect(get_data())->toIncludeProperties(array(
				'event'    => 'cloned',
				'context'  => 'duplicate_post',
				'log_info' => array(
					'new_post_id'     => 2,
					'prev_post_id'    => 1,
					'status'          => 'draft',
					'prev_post_title' => 'test',
					'new_post_title'  => 'Sample Page',
					'post_type'       => 'Post',
				),
			));
	});


test('Duplicate plugin settings', function() {
		// Required to set the settings for the pluging
		do_action('admin_init');

		// Action to update the settings
		do_action('updated_option', 'duplicate_post_title_prefix', 'old_prefix', 'new_prefix');

		// Action to log the changes to database
		apply_filters('wp_redirect', '');

		expect(get_data())->toIncludeProperties(array(
				'event'    => 'updated',
				'context'  => 'duplicate_post_settings',
				'log_info' => array(
					'duplicate_post_title_prefix' => array(
						"prev" => "old_prefix",
						"new"  => "new_prefix",
					),
				),
			));


		do_action('updated_option', 'duplicate_post_copyauthor', false, true);
		do_action('updated_option', 'duplicate_post_copyexcerpt', true, false);
		do_action('updated_option', 'duplicate_post_show_original_meta_box', false, true);
		apply_filters('wp_redirect', '');


		expect(get_data())->toIncludeProperties(array(
				'event'    => 'updated',
				'context'  => 'duplicate_post_settings',
				'log_info' => array(
					'elements-to-copy' => array(
						'duplicate_post_copyauthor'  => array(
							"prev" => false,
							"new"  => true,
						),
						'duplicate_post_copyexcerpt' => array(
							"prev" => true,
							"new"  => false,
						),
					),
					'show-original'    => array(
						'duplicate_post_show_original_meta_box' => array(
							"prev" => false,
							"new"  => true,
						),
					),
				),
			));
		expect(true)->toBe(true);
	});