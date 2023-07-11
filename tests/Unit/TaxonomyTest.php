<?php

test('Taxonomy', function() {
		// Test taxonomy creation
		// delete category if exists
		$term = get_term_by('name', 'new categeory', 'category');
		if ($term) {
			wp_delete_term($term->term_id, 'category');
		}

		$term = wp_create_term('new categeory', 'category');
		if (is_wp_error($term)) {
			throw new Exception($term->get_error_message());
		}

		$data = get_data();
		expect($data)->toIncludeProperties(array(
				'event'    => 'created',
				'context'  => 'taxonomy',
				'log_info' => array(
					'name'    => 'new categeory',
					'term_id' => $term['term_id'],
					'slug'    => 'new-categeory',
					'type'    => 'category',
				),
			));

		assert_snapshot($data, 'taxonomy-created', array('id' => $term['term_id']));


		// Test edit
		// delete category if exists
		$delete_term = get_term_by('slug', 'test-category', 'category');
		if ($delete_term) {
			wp_delete_term($delete_term->term_id, 'category');
		}
		$term = wp_update_term($term['term_id'], 'category', array(
				'name' => 'test category',
				'slug' => 'test-category',
		));

		if (is_wp_error($term)) {
			throw new Exception($term->get_error_message());
		}

		$data = get_data();
		expect($data)->toIncludeProperties(array(
				'event'    => 'edited',
				'context'  => 'taxonomy',
				'log_info' => array(
					'name'    => 'test category',
					'term_id' => $term['term_id'],
					'type'    => 'category',
					'changes' => array(
						'name' => array('new categeory', 'test category'),
						'slug' => array('new-categeory', 'test-category'),
					),
				),
			));
		assert_snapshot($data, 'taxonomy-edited', array('id' => $term['term_id']));

		// Test delete
		$delete_term = wp_delete_term($term['term_id'], 'category');
		if (is_wp_error($delete_term)) {
			throw new Exception($delete_term->get_error_message());
		}

		$data = get_data();

		expect($data)->toIncludeProperties(array(
				'event'    => 'deleted',
				'context'  => 'taxonomy',
				'log_info' => array(
					'name'    => 'test category',
					'term_id' => $term['term_id'],
					'type'    => 'category',
				),
			));

		assert_snapshot($data, 'taxonomy-deleted', array('id' => $term['term_id']));
	});