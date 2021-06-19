<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Log user related actions
 */
class NashaatUserHooks extends NashaatHookBase {
	protected $actions = array(
		array(
			'name' => 'wp_login',
			'args' => 2
		),
		array(
			'name' => 'profile_update',
			'args' => 2
		),
		array(
			'name' => array( 'wp_logout', 'user_register', 'delete_user' ),
			'callback' => 'user_actions_callback'
		)
	);

	protected $filters = array( 'wp_login_failed' );

	protected $context = 'user';

	/**
	 * Handle various user related actions such registration, profile update,
	 * deleting and other actions
	 *
	 * @param integer $user_id User id
	 *
	 * @return bool|void False if action is not processed
	 */
	protected function user_actions_callback( int $user_id ) {
		$event = false;
		$level = NASHAAT_LOG_LEVEL_LOW;
		switch ( current_action() ) {
			case 'user_register':
				$event = 'registered';
				break;
			case 'delete_user':
				$event = 'deleted';
				$level = NASHAAT_LOG_LEVEL_HIGH;
				break;
			case 'wp_logout':
				$event = 'logout';
				$level = NASHAAT_LOG_LEVEL_NORMAL;
				break;
		}

		if ( $event === false ) {
			return false;
		}

		$user = get_user_by( 'id', $user_id );

		$this->log_info = $this->pluck_object( $user->data, array( 'ID', 'user_login', 'display_name' ) );
		$this->log_info['roles'] = $user->roles;

		$this->level = $level;
		$this->event = $event;
	}

	/**
	 * Get porfile changes
	 *
	 * @param integer $user_id User ID
	 * @param WP_User $old_user_data Old user data
	 * @return void
	 */
	protected function profile_update_callback( int $user_id, WP_User $old_user_data ) {
		$updated_user_data = get_userdata( $user_id );
		$changes = array();

		$monitor_data = array( 'user_login', 'user_pass', 'user_nicename', 'user_email', 'user_url', 'display_name', 'caps' );
		foreach ( $monitor_data as $monitor_key ) {
			if ( $updated_user_data->{$monitor_key} != $old_user_data->{$monitor_key} ) {
				$changes[] = $monitor_key;
			}
		}

		if ( count( $changes ) === 0 ) {
			return;
		}

		$this->log_info = $this->pluck_object( $updated_user_data->data, array( 'ID', 'user_login', 'display_name' ) );
		$this->log_info['roles'] = $updated_user_data->roles;
		$this->log_info['changes'] = $changes;
		$this->level = NASHAAT_LOG_LEVEL_MEDIUM;
		$this->event = 'updated';
	}

	/**
	 * Handle user login action
	 *
	 * @param string  $user_login User name
	 * @param WP_User $user User object
	 * @return void
	 */
	protected function wp_login_callback( string $user_login, WP_User $user ) {
		$this->log_info = $this->pluck_object( $user->data, array( 'ID', 'user_login', 'display_name' ) );
		$this->log_info['roles'] = $user->roles;
		$this->event = 'login';
	}

	/**
	 * Callback for failed login attempt
	 *
	 * @param string $user_login User name or email address
	 * @return void
	 */
	protected function wp_login_failed_callback( string $user_login ) {
		$user = false;

		// If email is supplied search for user by email,
		// otherwise search by login name
		if ( filter_var( $user_login, FILTER_VALIDATE_EMAIL ) ) {
			$user = get_user_by( 'email', $user_login );
		} else {
			$user = get_user_by( 'login', $user_login );
		}

		$this->log_info = array( 'user_login' => $user_login );

		if ( $user !== false ) {
			$this->log_info['id'] = $user->data->ID;
			$this->log_info['display_name'] = $user->data->display_name;
		}
		$this->level = NASHAAT_LOG_LEVEL_LOW;
		$this->event = 'failed_login_attempt';
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
		if ( $event === 'failed_login_attempt' ) {
			$user_data = array(
				'user_login' => $log_info['user_login']
			);
			return $render_class::array_to_html( $user_data );
		}

		$user_data = array(
			'user_login' => $log_info['user_login'],
			'display_name' => $log_info['display_name'],
			'role' => implode( ', ', $log_info['roles'] )
		);

		$output = $render_class::array_to_html( $user_data );
		if ( ! isset( $log_info['changes'] ) ) {
			return $output;
		}

		// Get language string for each change key
		$changes_lang = array_map(
			function( $key ) {
				return get_nashaat_lang( $key );
			},
			$log_info['changes']
		);

		// ouput changes
		$output .= "<div class='user-changes-wrapper'>";
		$output .= "<h5 class='user-changes-title'>" . get_nashaat_lang( 'changes' ) . '</h5>';
		$output .= "<div class='user-changes'>";

		$output .= implode( ', ', $changes_lang );
		$output .= '</div>';
		$output .= '</div>';

		return $output;

	}

}