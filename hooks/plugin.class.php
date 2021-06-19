<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle plugin related actions
 */
class NashaatPluginHooks extends NashaatHookBase {
	protected $actions = array(
		'delete_plugin',
		array(
			'name' => array( 'activated_plugin', 'deactivated_plugin' ),
			'callback' => 'plugin_status_callback'
		),
		array(
			'name' => 'upgrader_process_complete',
			'args' => 2
		),
		array(
			'name' => 'deleted_plugin',
			'args' => 2
		)
	);

	protected $filters = array(
		array(
			'name' => 'upgrader_pre_install',
			'args' => 2,
			'callback' => 'pre_update_plugin_info'
		)
	);

	protected $context = 'plugin';
	protected $level = NASHAAT_LOG_LEVEL_MEDIUM;
	protected $delete_plugin_info = null;
	private $pre_update_plugin_info = null;

	/**
	 * Helper function to get plugin info
	 *
	 * @param string $plugin_name Plugin name
	 * @return array Array of plugin info
	 */
	private function get_plugin_info( $plugin_name ) {

		$plugin_path = strpos( $plugin_name, WP_PLUGIN_DIR ) !== false ? $plugin_name : WP_PLUGIN_DIR . '/' . $plugin_name;

		$plugin_data = get_plugin_data( $plugin_path, false, false );

		$plugin_info = $this->pluck_object(
			$plugin_data,
			array(
				'Name' => 'name',
				'Version' => 'version'
			)
		);

		if ( ! empty( $this->pre_update_plugin_info ) ) {
			$plugin_info['prev_version'] = $this->pre_update_plugin_info['version'];
		}

		return $plugin_info;
	}

	/**
	 * Call before update state to store current plugin info.
	 *
	 * @param bool|WP_Error $response   Response.
	 * @param array         $hook_extra Extra arguments passed to hooked filters.
	 * @return bool|void|WP_Error Return WP_Error if $response is an error, true to shortcircut
	 */
	public function pre_update_plugin_info( $response, array $hook_extra ) {

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$plugin_name = isset( $hook_extra['plugin'] ) ? $hook_extra['plugin'] : '';

		if ( empty( $plugin_name ) ) {
			return true;
		}

		$this->pre_update_plugin_info = $this->get_plugin_info( $plugin_name );

	}
	/**
	 * Get deleted plugin info before it is deleted and store into class property
	 * to process by deleted_plugin_callback
	 *
	 * @param string $plugin_name Plugin name
	 * @return void
	 */
	public function delete_plugin_callback( $plugin_name ) {
		$this->delete_plugin_info = $this->get_plugin_info( $plugin_name );
	}

	/**
	 * Hook into deleted_plugin action
	 *
	 * @param string $plugin_name Plugin name
	 * @param bool   $deleted If plugin has been deleted
	 * @return bool|void False if plugin was not deleted
	 */
	protected function deleted_plugin_callback( $plugin_name, $deleted ) {

		if ( ! $deleted ) {
			return false;
		}
		if ( ! isset( $this->delete_plugin_info ) ) {
			return false;
		}

		$this->event = 'deleted';
		$this->log_info = $this->delete_plugin_info;

	}
	/**
	 * Callback for activation and deactivation of plugins
	 *
	 * @param string $plugin_name Plugin name
	 * @return bool|void False if action is not supported
	 */
	protected function plugin_status_callback( $plugin_name ) {

		$event = false;

		switch ( current_action() ) {
			case 'activated_plugin':
				$event = 'activated';
				break;
			case 'deactivated_plugin':
				$event = 'deactivated';
				break;
		}
		if ( $event === false ) {
			return false;
		}

		$this->event = $event;
		$this->log_info = $this->get_plugin_info( $plugin_name );

	}

	/**
	 * Handle plugin install and update actions
	 *
	 * @param WP_Upgrader $upgrader Upgrader class instance
	 * @param array       $extra Action information
	 * @return boolean false if type is not plugin (i.e. theme is updating)
	 */
	protected function upgrader_process_complete_callback( WP_Upgrader $upgrader, array $extra ) {
		if ( $extra['type'] !== 'plugin' ) {
			return false;
		}

		if ( ! in_array( $extra['action'], array( 'install', 'update' ) ) ) {
			return false;
		}

		$plugin_path = $upgrader->plugin_info();
		if ( ! $plugin_path ) {
			return false;
		}

		if ( $extra['action'] === 'install' ) {
			$this->event = 'installed';
			$this->log_info = $this->get_plugin_info( $plugin_path );
		}

		if ( $extra['action'] === 'update' ) {
			// Update could be an array of plugins (bulk)
			if ( isset( $extra['bulk'] ) && $extra['bulk'] == true ) {
				foreach ( $extra['plugins'] as $plugin_path ) {
					$this->log_info_array[] = $this->get_plugin_info( $plugin_path );
				}
			} else {
				$this->log_info = $this->get_plugin_info( $plugin_path );
			}

			$this->event = 'updated';
		}
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
		$plugin_data = array(
			'name' => $log_info['name'],
			'version' => $log_info['version']
		);

		if ( isset( $log_info['prev_version'] ) ) {
			$plugin_data['prev_version'] = $log_info['prev_version'];
		}

		$output = $render_class::array_to_html( $plugin_data );

		return $output;
	}
}