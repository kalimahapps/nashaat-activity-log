<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Log taxonomy related actions
 */
class NashaatTaxonomyHooks extends NashaatHookBase {
	protected $actions = array(
		array(
			'name' => 'created_term',
			'callback' => 'term_status_callback',
			'args' => 3
		),
		array(
			'name' => 'delete_term',
			'callback' => 'term_delete_callback',
			'args' => 4
		)
	);

	protected $filters = array(
		array(
			'name' => 'wp_update_term_data',
			'args' => 4
		)
	);

	protected $context = 'taxonomy';
	protected $level = NASHAAT_LOG_LEVEL_MEDIUM;

	/**
	 * Handle term change before it is updated in database
	 *
	 * @param array  $data     Term data to be updated.
	 * @param int    $term_id  Term ID.
	 * @param string $taxonomy Taxonomy slug.
	 * @param array  $args     Arguments passed to wp_update_term().
	 *
	 * @return array Data from $data
	 */
	protected function wp_update_term_data_callback( array $data, int $term_id, string $taxonomy, array $args ) {
		if ( $taxonomy === 'nav_menu' ) {
			return $data;
		}

		$prev_data = get_term( $term_id, $taxonomy, ARRAY_A );
		$new_data = $this->pluck_object( $args, array( 'name', 'slug', 'parent', 'description' ) );

		$changes = array();
		foreach ( $new_data as $key => $value ) {
			if ( $prev_data[ $key ] != $value ) {
				$changes[ $key ] = array( $prev_data[ $key ], $value );
			}
		}

		if ( count( $changes ) === 0 ) {
			return $data;
		}

		$term_name = $prev_data['name'];
		if ( isset( $new_data['name'] ) ) {
			$term_name = $new_data['name'];
		}

		$this->event = 'edited';
		$this->log_info = array(
			'term_id' => $term_id,
			'name' => $term_name,
			'type' => $taxonomy,
			'changes' => $changes
		);

		return $data;
	}

	/**
	 * Callback for created term actions
	 *
	 * @param integer $term_id Term Id
	 * @param integer $tt_id Taxonomy Id
	 * @param string  $taxonomy Taxonomy title
	 * @return bool|void false if nav_menu taxonomy
	 */
	protected function term_status_callback( int $term_id, int $tt_id, string $taxonomy ) {
		if ( $taxonomy === 'nav_menu' ) {
			return false;
		}

		$term = get_term( $term_id, $taxonomy );

		$event = current_action() === 'created_term' ? 'created' : 'edited';

		$this->log_info = array(
			'term_id' => $term_id,
			'name' => $term->name,
			'slug' => $term->slug,
			'type' => $taxonomy
		);
		 $this->event = $event;
	}

	/**
	 * Callback for deleted_term action
	 *
	 * @param integer $term_id Term id
	 * @param integer $tt_id Taxonomy id
	 * @param string  $taxonomy taxonomy title
	 * @param WP_Term $deleted_term Deleted term object
	 * @return bool|void False if nav_menu taxonomy
	 */
	protected function term_delete_callback( int $term_id, int $tt_id, string $taxonomy, WP_Term $deleted_term ) {
		if ( $taxonomy === 'nav_menu' ) {
			return false;
		}

		$this->log_info = array(
			'term_id' => $term_id,
			'name' => $deleted_term->name,
			'slug' => $deleted_term->slug,
			'type' => $taxonomy
		);
		$this->event = 'deleted';
	}


	/**
	 * Render html output
	 *
	 * @param array                $log_info Log info array
	 * @param string               $event Event name
	 * @param array                $item Row details
	 * @param NashaatRenderLogInfo $render_class Render class instance
	 * @return string Html string
	 */
	public function render_log_info_output( array $log_info, string $event, array $item, $render_class ) : string {

		$id = $log_info['term_id'];
		$term_link = get_edit_term_link( $id, $log_info['type'] );
		if ( empty( $term_link ) ) {
			$term_link = $id;
		} else {
			$term_link = sprintf( "<a target='_blank' href='%s'>%s</a>", $term_link, $id );
		}
		$taxonomy_data = array(
			'id' => $term_link,
			'name' => $log_info['name'],
			'type' => $log_info['type'],
		);

		$output = $render_class::array_to_html( $taxonomy_data );

		// Created taxonomy will not have changes array. Don't proceeded in this case
		if ( ! isset( $log_info['changes'] ) || empty( $log_info['changes'] ) ) {
			return $output;
		}

		$changes = $log_info['changes'];
		$output .= "<div class='changes-wrapper'>";
		$output .= "<h4 class='changes-title'>" . get_nashaat_lang( 'changes' ) . '</h4>';

		// Loop through each change and output html string
		foreach ( $changes as $change_key => $change_array ) :
			$output .= '<h5> - ' . get_nashaat_lang( $change_key ) . '</h5>';
			$output .= "<div class='single-change-item'>";

			$prev_value = empty( $change_array[0] ) ? get_nashaat_lang( 'empty' ) : $change_array[0];
			$new_value = empty( $change_array[1] ) ? get_nashaat_lang( 'empty' ) : $change_array[1];

			$meta_data = array(
				'prev' => $prev_value,
				'new' => $new_value,
			);
			$output .= $render_class::array_to_html( $meta_data );
			$output .= '</div>';
		endforeach;

		return $output;
	}
}