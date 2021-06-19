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
			'name' => array( 'wp_logout', 'user_register', 'profile_update', 'delete_user' ),
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
			case 'profile_update':
				$event = 'updated';
				break;
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
		return $render_class::array_to_html( $user_data );
	}

}