<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Log post related actions
 */
class NashaatPostHooks extends NashaatHookBase {
	protected $actions = array(
		array(
			'name' => 'transition_post_status',
			'args' => 3
		),
		array(
			'name' => 'delete_post',
			'args' => 2
		),
		array(
			'name' => 'rest_pre_dispatch',
			'args' => 3
		),
	);

	protected $context = 'post';
	protected $render_contexts = array( 'post', 'page' );
	protected $level = NASHAAT_LOG_LEVEL_MEDIUM;

	/**
	 * Hook into rest api request before dispatch and get old post data
	 *
	 * @param array|null      $result Result of dispatch
	 * @param WP_REST_Server  $server Server
	 * @param WP_Rest_Request $request Request data
	 * @return null Return null so filter is not hijacked.
	 */
	protected function rest_pre_dispatch_callback( $result, WP_REST_Server $server, WP_Rest_Request $request ) {
		// allowed routes
		$allowed_routes = array(
			'/wp/v2/posts',
			'/wp/v2/pages',
		);

		$changes = false;
		$id = 0;
		$title = '';

		foreach ( $allowed_routes as $route ) {
			if ( strpos( $request->get_route(), $route ) !== false ) {
				$body = $request->get_body();
				if ( empty( $body ) ) {
					break;
				}

				$body = json_decode( $body );
				$changes = $this->get_post_changes( $body );

				$this->context = strpos( $request->get_route(), 'posts' ) ? 'post' : 'page';
				$this->event = 'edited';

				$id = $body->id;
				$title = property_exists( $body, 'title' ) ? $body->title : get_the_title( $id );
				break;
			}
		}

		if ( $changes !== false ) {
			$this->log_info = array(
				'id' => $id,
				'title' => $title,
				'changes' => $changes
			);
		}

		return null;
	}


	/**
	 * Get post changes between the old and new content
	 *
	 * @param object $new_data Updated data from gutenberg editor.
	 * @return bool|array False if no changes, an array of changed values otherwise
	 */
	private function get_post_changes( object $new_data ) {
		$post_id = $new_data->id;
		$old_post = get_post( $post_id );
		$changes = array();

		foreach ( $new_data as $key => $value ) :
			switch ( $key ) :
				case 'content':
					// Content will always be supplied in new data.
					// Make sure content count is different to include it in changes
					$old_content_count = strlen( $old_post->post_content );
					$new_content_count = strlen( $value );
					if ( $old_content_count !== $new_content_count ) {
						$changes['content'] = array( $old_content_count, $new_content_count );
					}
					break;

				case 'title':
					$changes['title'] = array( $old_post->post_title, $value );
					break;

				case 'author':
					$old_author_name = get_the_author_meta( 'display_name', $old_post->post_author );
					$new_author_name = get_the_author_meta( 'display_name', $value );

					$changes['author'] = array(
						array(
							'name' => $old_author_name,
							'id' => $old_post->post_author
						),
						array(
							'name' => $new_author_name,
							'id' => $value
						)
					);
					break;
				case 'status':
					$changes['status'] = array( $old_post->post_status, $value );
					break;

				case 'slug':
					$changes['slug'] = array( $old_post->post_name, $value );
					break;

				case 'categories':
					$changes['categories'] = $this->get_term_changes( $post_id, 'category', $value );
					break;

				case 'tags':
					$changes['tags'] = $this->get_term_changes( $post_id, 'post_tag', $value );
					break;

				case 'featured_media':
					// Get media data for both old and new posts data.
					$old_media_id = get_post_thumbnail_id( $post_id );
					$old_media_title = '';

					if ( ! empty( $old_media_id ) ) {
						$old_media_title = get_post( $old_media_id )->post_title;
					}

					$new_media_title = '';
					if ( ! empty( $value ) ) {
						$new_media_title = get_post( $value )->post_title;
					}

					$changes['featured_media'] = array(
						array(
							'id' => $old_media_id,
							'title' => $old_media_title
						),
						array(
							'id' => $value,
							'title' => $new_media_title
						)
					);
					break;

				case 'excerpt':
					$old_excerpt_count = strlen( $old_post->post_excerpt );
					$new_excerpt_count = strlen( $value );

					$changes['excerpt'] = array( $old_excerpt_count, $new_excerpt_count );
					break;

				case 'sticky':
					$changes['sticky'] = array( ! $value, $value );
					break;

				case 'parent':
					$old_post_parent_id = $old_post->post_parent;
					$old_post_parent = 0;

					if ( ! empty( $old_post_parent_id ) ) {
						$old_post_parent = get_post( $old_post_parent_id );
						$old_post_parent = $this->pluck_object( $old_post_parent, array( 'ID', 'post_title' ) );
					}

					$new_post_parent_id = $value;
					$new_post_parent = 0;
					if ( ! empty( $new_post_parent_id ) ) {
						$new_post_parent = get_post( $new_post_parent_id );
						$new_post_parent = $this->pluck_object( $new_post_parent, array( 'ID', 'post_title' ) );
					}

					$changes['parent'] = array( $old_post_parent, $new_post_parent );
					break;

				case 'menu_order':
				case 'comment_status':
				case 'ping_status':
					$changes[ $key ] = array( $old_post->{$key}, $value );
					break;

			endswitch;
		endforeach;

		if ( count( $changes ) === 0 ) {
			return false;
		}
		return $changes;
	}

	/**
	 * Helper function to get old and updated terms
	 *
	 * @param integer $post_id Post ID
	 * @param string  $taxonomy Post taxonomy
	 * @param array   $new_terms_ids Array of new terms ids from editor
	 * @return array Array containing old and new terms with certain data
	 */
	private function get_term_changes( int $post_id, string $taxonomy, array $new_terms_ids ) :array {
		$old_post_terms = get_the_terms( $post_id, $taxonomy );
		$new_post_terms = array();

		$old_terms_data = array();
		if ( count( $old_post_terms ) > 0 ) {
			foreach ( $old_post_terms as $term_data ) {
				$old_terms_data[] = $this->pluck_object( $term_data, array( 'term_id', 'name' ) );
			}
		}

		$new_terms_data = array();
		if ( count( $new_terms_ids ) > 0 ) {
			$new_post_terms = get_terms(
				array(
					'include' => $new_terms_ids,
					'taxonomy' => $taxonomy
				)
			);

			foreach ( $new_post_terms as $term_data ) {
				$new_terms_data[] = $this->pluck_object( $term_data, array( 'term_id', 'name' ) );
			}
		}

		return array( $old_terms_data, $new_terms_data );
	}

	/**
	 * Handle post delete action
	 *
	 * @param integer $postid Post ID
	 * @param WP_Post $post Post object
	 * @return bool|void False if revision or menu item
	 */
	protected function delete_post_callback( int $postid, WP_Post $post ) {

		if ( wp_is_post_revision( $postid ) || ! in_array( get_post_type( $postid ), array( 'page', 'post' ) ) ) {
			return false;
		}

		$this->level = NASHAAT_LOG_LEVEL_HIGH;
		$this->log_info = $this->pluck_object(
			$post,
			array(
				'ID' => 'id',
				'post_title' => 'title',
				'post_type'
			)
		);
		$this->event = 'deleted';
	}


	/**
	 * Handle post actions. This will only handle status change.
	 *
	 * @param string  $new_status New post status
	 * @param string  $old_status Old post status
	 * @param WP_Post $post Post object
	 *
	 * @return bool|void False if action is not processed
	 */
	protected function transition_post_status_callback( string $new_status, string $old_status, WP_Post $post ) {
		// Skip for revision, autosave, and any post that is not of post or page type
		if ( wp_is_post_revision( $post->ID ) || wp_is_post_autosave( $post->ID ) || ! in_array( get_post_type( $post->ID ), array( 'page', 'post' ) ) ) {
			return false;
		}

		// Go ahead if old status is not the same as the new status
		if ( $old_status === $new_status ) {
			return false;
		}

		$this->log_info = $this->pluck_object(
			$post,
			array(
				'ID' => 'id',
				'post_title' => 'title',
				'post_type'
			)
		);
		$this->log_info['new_status'] = $new_status;
		$this->log_info['old_status'] = $old_status;

		$event = false;
		switch ( true ) {
			case $new_status === 'trash':
				$event = 'trashed';
				$this->level = NASHAAT_LOG_LEVEL_HIGH;
				break;

			case $old_status === 'trash':
				$event = 'restored';
				break;

				// new post
			case $old_status === 'auto-draft':
				$event = 'created';
				$this->level = NASHAAT_LOG_LEVEL_LOW;
				break;

			case $new_status === 'future':
				$this->log_info['future_date'] = get_post_timestamp( $post );
				$event = 'scheduled';
				break;
		}

		if ( $event === false ) {
			return false;
		}

		$this->event = $event;
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
		$id = isset( $log_info['id'] ) ? $log_info['id'] : $log_info['ID'];

		if ( is_null( $id ) ) {
			$id = 0;
		}
		$post_data['id'] = $render_class::maybe_get_post_edit_link( $id );
		$post_data['title'] = $render_class::maybe_get_post_title( $log_info );

		$output = '';
		switch ( $event ) :
			case 'deleted':
			case 'trashed':
			case 'restored':
				$post_data['type'] = $log_info['post_type'];
				$output = $render_class::array_to_html( $post_data );
				break;
			case 'edited':
			case 'updated':
				$output = $render_class::array_to_html( $post_data );
				$output .= $render_class::render_post_edit_changes( $item );
				break;
			case 'created':
				$output = $render_class::array_to_html( $post_data );
				break;
		endswitch;

		return $output;
	}
}