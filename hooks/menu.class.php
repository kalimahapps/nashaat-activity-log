<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Log menu related actions
 */
class NashaatMenuHooks extends NashaatHookBase {
	protected $actions = array(
		array(
			'name' => array( 'wp_update_nav_menu', 'wp_create_nav_menu', 'wp_delete_nav_menu' ),
			'callback' => 'menu_status_callback'
		)
	);

	protected $context = 'menu';
	protected $level = NASHAAT_LOG_LEVEL_MEDIUM;

	/**
	 * Helper function to get menu item details
	 *
	 * @param integer $menu_id Menu id
	 * @return bool|array False if menu does not exsit, menu details otherwise
	 */
	private function get_menu_info( int $menu_id ) {
		$menu_data = wp_get_nav_menu_object( $menu_id );
		if ( ! $menu_data ) {
			return false;
		}

		return array(
			'name' => $menu_data->name,
			'slug' => $menu_data->slug,
			'id' => $menu_data->term_id
		);
	}

	/**
	 * Callback for menu status actions (delete, update and create)
	 *
	 * @param integer $menu_id Menu id
	 * @return bool|void False if menu object can not be found
	 */
	protected function menu_status_callback( int $menu_id ) {

		$menu_data = $this->get_menu_info( $menu_id );
		if ( ! $menu_data ) {
			return false;
		}
		$this->log_info = $menu_data;

		$event = '';
		switch ( current_action() ) {
			case 'wp_update_nav_menu':
				$event = 'updated';
				break;
			case 'wp_create_nav_menu':
				$event = 'created';
				break;
			case 'wp_delete_nav_menu':
				$event = 'deleted';
				break;
		}
		 $this->event = $event;
	}


	/**
	 * Check if user exists and return link to profile page.
	 *
	 * @param integer $user_id User id
	 * @param string  $user_name User name
	 * @return string Link to user profile in backend if user exists, user_name otherwise
	 */
	private function maybe_get_menu_edit_link( int $user_id, string $user_name ) {
		$user_link = get_edit_user_link( $user_id );
		$user_exists = get_user_by( 'id', $user_id );
		$return = $user_name;

		if ( ! empty( $user_exists ) ) {
			$return = sprintf( "<a target='_blank' href='%s'>%s</a>", $user_link, $user_name );
		}

		return $return;
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
		$menu_data = array(
			'id' => $render_class::maybe_get_post_edit_link( $log_info['id'] ),
			'name' => $log_info['name']
		);

		$output = $render_class::array_to_html( $menu_data );

		return $output;

	}
}