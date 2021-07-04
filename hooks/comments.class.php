<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle comment hooks and actions
 */
class NashaatCommentHooks extends NashaatHookBase {
	protected $actions = array(
		array(
			'name' => array(
				'edit_comment',
				'delete_comment',
				'trash_comment',
				'untrash_comment',
				'spam_comment',
				'unspam_comment'
			),
			'args' => 2,
			'callback' => 'comment_actions_callback'
		),
		array(
			'name' => 'transition_comment_status',
			'args' => 3
		)
	);

	protected $context = 'comment';
	protected $level = NASHAAT_LOG_LEVEL_LOW;

	/**
	 * Handle comment actions (edit, delete, spam, trash .. etc)
	 *
	 * @param integer          $comment_id Comment ID
	 * @param array|WP_Comment $comment Comment data
	 * @return void
	 */
	protected function comment_actions_callback( int $comment_id, $comment ) {
		$actions_array = array(
			'edit_comment' => array( 'event' => 'edited' ),
			'delete_comment' => array(
				'event' => 'deleted',
				'level' => NASHAAT_LOG_LEVEL_HIGH
			),
			'trash_comment' => array( 'event' => 'trashed' ),
			'untrash_comment' => array( 'event' => 'restored' ),
			'spam_comment' => array( 'event' => 'spammed' ),
			'unspam_comment' => array(
				'event' => 'unspammed',
				'level' => NASHAAT_LOG_LEVEL_MEDIUM
			),
		);
		if ( ! isset( $actions_array[ current_action() ] ) ) {
			return;
		}

		// in edit_comment action $comment is not an object but an array
		// post_type will not be available
		if ( is_array( $comment ) ) {
			$post_type = get_post_type( $comment['comment_post_ID'] );
		} else {
			$post_type = $comment->post_type;
		}

		// Log only for pages and posts
		if ( ! in_array( $post_type, array( 'post', 'page' ) ) ) {
			return;
		}

		$this->log_info = $this->pluck_object(
			$comment,
			array(
				'comment_post_ID' => 'post_id',
				'comment_author' => 'author',
				'comment_date' => 'date',
				'user_id',
			)
		);

		$this->log_info['id'] = $comment_id;
		$this->log_info['post_type'] = $post_type;

		$action_data = $actions_array[ current_action() ];
		$this->event = $action_data['event'];
		if ( isset( $action_data['level'] ) ) {
			$this->level = $action_data['level'];
		}
	}

	/**
	 * Get comment approved and unapproved actions
	 *
	 * @param string     $new_status New status
	 * @param string     $old_status Old status
	 * @param WP_Comment $comment Comment object
	 * @return void
	 */
	protected function transition_comment_status_callback( string $new_status, string $old_status, WP_Comment $comment ) {

		if ( $old_status === $new_status ) {
			return;
		}
		$event = false;

		switch ( true ) {
			case $new_status === 'approved':
				$event = 'approved';
				break;
			case $new_status === 'unapproved':
				$event = 'unapproved';
				break;
		}

		if ( $event === false ) {
			return;
		}

		$this->log_info = $this->pluck_object(
			$comment,
			array(
				'comment_ID' => 'id',
				'comment_post_ID' => 'post_id',
				'comment_author' => 'author',
				'comment_date' => 'date',
				'user_id',
			)
		);

		$this->log_info['post_type'] = $comment->post_type;
		$this->log_info['new_status'] = $new_status;
		$this->log_info['old_status'] = $old_status;

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
		$log_info = maybe_unserialize( $item['log_info'] );
		$event = $item['event'];

		$log_info = wp_parse_args(
			$log_info,
			array(
				'post_id' => 0,
				'post_type' => ''
			)
		);

		$comment_data = array(
			'id' => $render_class::maybe_get_post_edit_link( $log_info['id'], 'comment' ),
			'parent' => $render_class::maybe_get_post_edit_link( $log_info['post_id'] ),
			'author' => $render_class::maybe_get_user_edit_link( $log_info['user_id'], $log_info['author'] ),
			'post_type' => $log_info['post_type'],
		);

		if ( $event === 'approved' || $event === 'unapproved' ) {
			$comment_data['prev'] = $log_info['old_status'];
		}

		$output = $render_class::array_to_html( $comment_data );
		return $output;
	}
}