<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle duplicate post plugin actions
 */
class NashaatDuplicatePost extends NashaatHookBase {
	protected $actions = array(
		array(
			'name' => array( 'dp_duplicate_post', 'dp_duplicate_page' ),
			'callback' => 'log_post_duplicate',
			'args' => 3,
		),
	);

	protected $context = 'duplicate_post';
	protected $level = NASHAAT_LOG_LEVEL_MEDIUM;


	/**
	 * Log post duplication
	 *
	 * @param int    $new_post_id The new post's ID.
	 * @param object $post The original post object.
	 * @param string $status The new post's status.
	 * @return void
	 */
	protected function log_post_duplicate( $new_post_id, $post, $status ) {
		$post_type = get_post_type_object( $post->post_type );
		$post_type_name = $post_type->labels->singular_name;
		$post_id = $post->ID;

		$new_post = get_post( $new_post_id );

		$this->event = 'cloned';
		$this->log_info = array(
			'new_post_id' => $new_post_id,
			'prev_post_id' => $post_id,
			'status' => $status,
			'prev_post_title' => $post->post_title,
			'new_post_title' => $new_post->post_title,
			'post_type' => $post_type_name,
		);
	}


	/**
	 * Add translations strings
	 *
	 * @param array $translations Current plugin translations
	 * @return array modified $translations array
	 */
	public function set_translations( array $translations ) {
		return array(
			'duplicate_post' => __( 'Duplicate Post', 'nashaat' ),
			'cloned' => __( 'Cloned', 'nashaat' ),
			'new_post' => __( 'New Post', 'nashaat' ),
			'prev_post' => __( 'Previous Post', 'nashaat' ),
		);
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
	public function render_log_info_output( $log_info, string $event, array $item, $render_class ) : string {
		$new_post_id = $log_info['new_post_id'];
		$prev_post_id = $log_info['prev_post_id'];

		$post_data['new_post'] = $render_class::maybe_get_post_title( $log_info['new_post_title'], $new_post_id );
		$post_data['prev_post'] = $render_class::maybe_get_post_title( $log_info['prev_post_title'], $prev_post_id );
		$post_data['post_type'] = $log_info['post_type'];

		return $render_class::array_to_html( $post_data );
	}
}