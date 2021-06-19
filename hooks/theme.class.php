<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Log theme related actions
 */
class NashaatThemeHooks extends NashaatHookBase {
	protected $actions = array(
		array(
			'name' => 'switch_theme',
			'args' => 3
		),
		array(
			'name' => 'upgrader_process_complete',
			'args' => 2
		),
		array(
			'name' => 'admin_init',
			'callback' => 'theme_deleted_callback'
		)
	);

	protected $filters = array(
		array(
			'name' => 'upgrader_pre_install',
			'args' => 2,
			'callback' => 'pre_update_theme_info'
		)
	);

	protected $context = 'theme';
	protected $level = NASHAAT_LOG_LEVEL_MEDIUM;
	private $pre_update_theme_info = null;

	/**
	 * Get theme info based on slug or WP_Theme object
	 *
	 * @param string|WP_theme $theme Theme slug or object
	 * @return array Theme info
	 */
	private function get_theme_info( $theme ) {
		if ( is_string( $theme ) ) {
			$theme = wp_get_theme( $theme );
		}

		$theme_info = array(
			'name' => $theme->get( 'Name' ),
			'version' => $theme->get( 'Version' )
		);

		if ( ! empty( $this->pre_update_theme_info ) ) {
			$theme_info['prev_version'] = $this->pre_update_theme_info['version'];
		}

		return $theme_info;
	}

	/**
	 * Call before update state to store current theme info.
	 *
	 * @param bool|WP_Error $response   Response.
	 * @param array         $hook_extra Extra arguments passed to hooked filters.
	 * @return bool|void|WP_Error Return WP_Error if $response is an error, true to shortcircut
	 */
	public function pre_update_theme_info( $response, array $hook_extra ) {

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$theme_name = isset( $hook_extra['theme'] ) ? $hook_extra['theme'] : '';

		if ( empty( $theme_name ) ) {
			return true;
		}

		$this->pre_update_theme_info = $this->get_theme_info( $theme_name );

	}

	/**
	 * Run on admin_init hook to check if delete-theme action is triggered
	 *
	 * @return void|bool False if action is not triggered, void otherwise
	 */
	protected function theme_deleted_callback() {
		$__post = filter_input_array( INPUT_POST );

		if ( ! $this->is_keys_values_set( $__post, array( 'action' => 'delete-theme' ) ) ) {
			return;
		}

		$this->log_info = $this->get_theme_info( $__post['slug'] );
		$this->event = 'deleted';
	}

	/**
	 * Handle switch_theme action
	 *
	 * @param string   $new_theme_name New theme name
	 * @param WP_Theme $new_theme New theme object
	 * @param WP_Theme $old_theme Old theme object
	 * @return void
	 */
	protected function switch_theme_callback( $new_theme_name, WP_Theme $new_theme, WP_Theme $old_theme ) {
		$this->log_info['changes'] = array(
			'prev_theme' => $this->get_theme_info( $old_theme ),
			'new_theme' => $this->get_theme_info( $new_theme )
		);

		$this->event = 'switched';

	}

	/**
	 * Handle theme update or install actions
	 *
	 * @param WP_Upgrader $upgrader Upgrader class
	 * @param array       $extra Action information
	 * @return boolean false if type is not theme (i.e plugin is updating) or action is not processed
	 */
	protected function upgrader_process_complete_callback( WP_Upgrader $upgrader, array $extra ) {

		if ( $extra['type'] !== 'theme' ) {
			return false;
		}

		if ( $extra['action'] !== 'install' && $extra['action'] !== 'update' ) {
			return false;
		}

		$theme_slug = $upgrader->theme_info();

		if ( ! $theme_slug ) {
			return;
		}

		if ( $extra['action'] === 'install' ) {
			$this->event = 'installed';
			$this->log_info = $this->get_theme_info( $theme_slug );
		}

		if ( $extra['action'] === 'update' ) {
			// Update could be an array of theme (bulk)
			if ( isset( $extra['bulk'] ) && $extra['bulk'] == true ) {
				foreach ( $extra['themes'] as $theme_slug ) {
					$this->log_info_array[] = $this->get_theme_info( $theme_slug );
				}
			} else {
				$this->log_info = $this->get_theme_info( $theme_slug );
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
		// Switched event
		if ( $event === 'switched' ) :
			$changes = $log_info['changes'];
			list('prev_theme' => $prev_theme, 'new_theme' => $new_theme) = $changes;

			$theme_data = array(
				'from' => "{$prev_theme['name']} (version: {$prev_theme['version']})",
				'to' => "{$new_theme['name']} (version: {$new_theme['version']})"
			);
			$output = $render_class::array_to_html( $theme_data );
			return $output;
		endif;

		// Other events
		$theme_data = array(
			'name' => $log_info['name'],
			'version' => $log_info['version']
		);

		if ( isset( $log_info['prev_version'] ) ) {
			$theme_data['prev_version'] = $log_info['prev_version'];
		}

		$output = $render_class::array_to_html( $theme_data );

		return $output;
	}
}
