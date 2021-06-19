<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Log media related actions
 */
class NashaatMediaHooks extends NashaatHookBase {
	protected $actions = array(
		array(
			'name' => array( 'add_attachment', 'delete_attachment' ),
			'args' => 1,
			'callback' => 'media_actions_callback'
		),
		array(
			'name' => 'admin_init',
			'callback' => 'media_edited_callback'
		),
		array(
			'name' => 'attachment_updated',
			'args' => 3
		)
	);

	protected $context = 'media';
	protected $level = NASHAAT_LOG_LEVEL_MEDIUM;


	/**
	 * Handle media updated action
	 *
	 * @param integer $post_id Post id
	 * @param WP_Post $post_after New post data
	 * @param WP_Post $post_before Old post data
	 * @return void
	 */
	protected function attachment_updated_callback( int $post_id, WP_Post $post_after, WP_Post $post_before ) {

		// pluck certain keys from object and rename
		$old_post_data = $this->pluck_object(
			$post_before,
			array(
				'ID' => 'id',
				'post_title' => 'title',
				'post_content' => 'description',
				'post_excerpt' => 'caption'
			)
		);

		$new_post_data = $this->pluck_object(
			$post_after,
			array(
				'ID' => 'id',
				'post_title' => 'title',
				'post_content' => 'description',
				'post_excerpt' => 'caption'
			)
		);

		$changes = array();
		foreach ( $new_post_data as $key => $value ) {
			// ignore id key
			if ( $key === 'id' ) {
				continue;
			}

			// check if old value and new value are different
			if ( strcmp( $value, $old_post_data[ $key ] ) === 0 ) {
				continue;
			}

			// for title add the actual content
			if ( $key === 'title' ) {
				$changes[ $key ] = array( $old_post_data[ $key ], $value );
			} else {
				// for other keys include length
				$changes[ $key ] = array( strlen( $old_post_data[ $key ] ), strlen( $value ) );
			}
		}

		if ( count( $changes ) === 0 ) {
			return;
		}

		$this->event = 'edited';

		$this->log_info['id'] = $post_id;
		$this->log_info['title'] = $new_post_data['title'];
		$this->log_info['changes'] = $changes;

		// Get attachment path
		$this->log_info['path'] = get_attached_file( $post_id );

	}
	/**
	 * Callback for media actions (add and delete)
	 *
	 * @param integer $post_id Media id
	 * @return bool|void False if action is not found
	 */
	protected function media_actions_callback( int $post_id ) {
		$event = false;
		switch ( current_action() ) {
			case 'add_attachment':
				$event = 'added';
				break;
			case 'delete_attachment':
				$event = 'deleted';
				$this->level = NASHAAT_LOG_LEVEL_HIGH;
				break;
		}

		if ( $event === false ) {
			return false;
		}

		$data = get_post( $post_id );
		$this->event = $event;
		$this->log_info = $this->pluck_object(
			$data,
			array(
				'ID' => 'id',
				'post_title' => 'title'
			)
		);

		// Get attachment path
		$this->log_info['path'] = get_attached_file( $post_id );

	}

	/**
	 * Callback for ajax media editing
	 *
	 * @return void
	 */
	protected function media_edited_callback() {
		$__post = filter_input_array( INPUT_POST );

		// Check if image edit request
		if ( ! $this->is_keys_values_set( $__post, array( 'action' => 'image-editor' ) ) ) {
			return;
		}

		// Check that post key exist and has value
		$is_save = $this->is_keys_values_set( $__post, array( 'do' => 'save' ) );
		$is_restore = $this->is_keys_values_set( $__post, array( 'do' => 'restore' ) );
		$is_scale = $this->is_keys_values_set( $__post, array( 'do' => 'scale' ) );

		// Only process if action is supported
		if ( $is_save === false && $is_restore === false && $is_scale === false ) {
			return;
		}

		$post_id = $__post['postid'];
		$this->event = 'image_edited';
		$data = get_post( $post_id );
		$this->log_info = $this->pluck_object(
			$data,
			array(
				'ID' => 'id',
				'post_title' => 'title'
			)
		);

		// Get attachment path
		$this->log_info['path'] = get_attached_file( $post_id );

		switch ( true ) :
			// Save action
			case $is_save:
				$this->log_info['history'] = $__post['history'];
				break;

			// Restore image (reverse edits)
			case $is_restore:
				$this->event = 'image_restored';
				break;

			// Scale image
			case $is_scale:
				// Add scale data to a history key so it can be rendered like save action
				$scale_array[] = array(
					's' => array(
						'w' => $__post['fwidth'],
						'h' => $__post['fheight']
					)
				);
				$this->log_info['history'] = json_encode( $scale_array );
				break;
		endswitch;

	}


	/**
	 * Render image edit history into HTML
	 *
	 * @param array $item Log single row data
	 * @return string html string (or empty string if history key does not exist)
	 */
	private function render_image_edit_changes( array $item ) : string {
		$log_info = maybe_unserialize( $item['log_info'] );

		if ( ! isset( $log_info['history'] ) ) {
			return '';
		}

		$history = json_decode( $log_info['history'], true );

		// get edits into an array
		$edits = array();

		foreach ( $history as $pair ) :
			foreach ( $pair as $change_key => $change_value ) :
				switch ( $change_key ) :
					// Rotation
					case 'r':
						$degress = (int) $change_value;
						$edits[] = get_nashaat_lang( ( $degress > 0 ) ? 'rotated_left' : 'rotated_right' );
						break;
					// Flipping
					case 'f':
						$direction = (int) $change_value;
						$edits[] = get_nashaat_lang( ( $direction === 0 ) ? 'flipped_horizontal' : 'flipped_vertical' );
						break;
					// Cropping
					case 'c':
						$crop_data = array();
						foreach ( $change_value as $crop_key => $crop_value ) {
							$crop_data[] = "{$crop_key}: {$crop_value}";
						}
						$crop_html = implode( ', ', $crop_data );
						$edits[] = get_nashaat_lang( 'cropped' ) . " ({$crop_html})";
						break;
					// Scale
					case 's':
						$scale_data = array();
						foreach ( $change_value as $scale_key => $scale_value ) {
							$scale_data[] = "{$scale_key}: {$scale_value}";
						}
						$scale_html = implode( ', ', $scale_data );
						$edits[] = get_nashaat_lang( 'scaled' ) . " ({$scale_html})";
						break;
				endswitch;
			endforeach;
		endforeach;

		// Get changes html output

		$output = "<div class='image-edits-wrapper'>";
		$output .= "<h5 class='image-edits-title'>" . get_nashaat_lang( 'edits' ) . '</h5>';
		$output .= "<div class='image-edits-history'>";
		$output .= implode( ' ➞ ', $edits );
		$output .= '</div>';
		$output .= '</div>';

		return $output;
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
		$log_info = maybe_unserialize( $item['log_info'] );
		$event = $item['event'];

		// Separate directory and file name into two variables
		$path = pathinfo( $log_info['path'], PATHINFO_DIRNAME );
		$file = pathinfo( $log_info['path'], PATHINFO_FILENAME );

		$media_data['id'] = $render_class::maybe_get_post_edit_link( $log_info['id'] || 0 );
		$media_data['title'] = $render_class::maybe_get_post_title( $log_info );
		$media_data['path'] = $path;
		$media_data['file'] = $file;

		$output = $render_class::array_to_html( $media_data );

		if ( $event === 'edited' ) {
			 $output .= $render_class::render_post_edit_changes( $item );
		}

		if ( $event === 'image_edited' ) {
			 $output .= $this->render_image_edit_changes( $item );
		}

		return $output;
	}
}