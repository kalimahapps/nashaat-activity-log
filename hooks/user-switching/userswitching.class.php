<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle user switching plugin actions
 */
class NashaatUserSwitching extends NashaatHookBase {
	protected $actions = array(
		array(
			'name' => 'switch_to_user',
			'callback' => 'switch_user_callback',
			'args' => 2,
		),
		array(
			'name' => 'switch_back_user',
			'callback' => 'switch_user_callback',
			'args' => 2,
		),
		array(
			'name' => 'switch_off_user'
		),
	);

	protected $context = 'user_switching';
	protected $level = NASHAAT_LOG_LEVEL_MEDIUM;


	/**
	 * Handle user switching to and back
	 *
	 * This will handle user switching back from another users
	 * or after switching off
	 *
	 * @param int       $current_user_id The ID of the user being switched back to.
	 * @param int|false $old_user_id The ID of the user being switched from,
	 *                  or false if the user is switching back after having been switched off.
	 * @return void
	 */
	protected function switch_user_callback( $current_user_id, $old_user_id ) {
		$current_user = get_user_by( 'id', $current_user_id );
		$old_user = get_user_by( 'id', $old_user_id );

		$from_user_data = false;
		if ( $old_user_id !== false ) {
			$from_user_data = array(
				'name' => $old_user->display_name !== '' ? $old_user->display_name : $old_user->user_login,
				'id' => $old_user_id,
			);
		}

		$to_user_data = array(
			'name' => $current_user->display_name !== '' ? $current_user->display_name : $current_user->user_login,
			'id' => $current_user_id,
		);

		$this->event = current_action() === 'switch_to_user' ? 'switched_to' : 'switched_back';
		$this->log_info = array(
			'from' => $from_user_data,
			'to' => $to_user_data
		);
	}

	/**
	 * Log user switching off
	 *
	 * @param int $old_user_id The ID of the user switching off.
	 * @return void
	 */
	protected function switch_off_user_callback( $old_user_id ) {
		$user = get_user_by( 'id', $old_user_id );

		$this->event = 'switched_off';
		$this->log_info = array(
			'name' => $user->display_name !== '' ? $user->display_name : $user->user_login,
			'id' => $old_user_id,
		);
	}

	/**
	 * Get user link if available
	 *
	 * @param array $user_data User data
	 * @return string User name with link, or just user name
	 */
	private function maybe_get_user_link( $user_data ) {
		$user_id = $user_data['id'];
		$link = get_edit_user_link( $user_id );
		if ( $link === '' ) {
			return $user_data['name'];
		}

		return sprintf( '<a href="%s">%s</a>', get_edit_user_link( $user_id ), $user_data['name'] );
	}

	/**
	 * Translations strings for users witching plugin
	 *
	 * @param array $translations Current plugin translations
	 * @return array modified $translations array
	 */
	public function set_translations( array $translations ) {
		return array(
			'user_switching' => __( 'User Switching', 'nashaat' ),
			'switched_to' => __( 'Switched To', 'nashaat' ),
			'switched_back' => __( 'Switched back', 'nashaat' ),
			'switched_off' => __( 'Switched off', 'nashaat' )
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

		if ( $event === 'switched_off' ) {
			$user_link = $this->maybe_get_user_link( $log_info );

			// translators: %s: User name
			return sprintf( __( 'Switched off user %s', 'nashaat' ), $user_link );
		}

		$from_user = $log_info['from'];
		$to_user = $log_info['to'];

		$to_user_link = $this->maybe_get_user_link( $to_user );

		if ( $from_user === false ) {
			// translators: %s: User name
			return sprintf( __( 'Switched back to %s', 'nashaat' ), $to_user_link );
		}

		$from_user_link = $this->maybe_get_user_link( $from_user );

		// translators: %1$s: From user name, %2$s: To user name
		return sprintf( __( 'Switched from %1$s to %2$s', 'nashaat' ), $from_user_link, $to_user_link );

	}
}