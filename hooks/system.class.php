<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Log system related actions
 */
class NashaatSystemHooks extends NashaatHookBase {
	protected $actions = array(
		array(
			'name' => 'upgrader_process_complete',
			'args' => 2
		),
		'export_wp'
	);

	protected $filters = array(
		array(
			'name' => 'site_transient_update_core',
			'args' => 2,
			'callback' => 'pre_update_core_version'
		)
	);

	protected $context = 'system';
	protected $level = NASHAAT_LOG_LEVEL_MEDIUM;
	private $prev_core_version = null;

	/**
	 * Handle content export
	 *
	 * @param array $args Export arguments
	 * @return void
	 */
	protected function export_wp_callback( array $args ) {
		$this->log_info = $args;
		$this->event = 'exported';
	}

	/**
	 * Call before update state to store current wordpress version
	 *
	 * @param mixed $value Value of site transient.
	 * @return mixed return $value
	 */
	public function pre_update_core_version( $value ) {
		if ( empty( $this->prev_core_version ) ) {
			require ABSPATH . WPINC . '/version.php';
			$this->prev_core_version = $wp_version;
		}
		return $value;
	}

	/**
	 * Handle core update
	 *
	 * @param WP_Upgrader $upgrader Upgrader class
	 * @param array       $extra Action information
	 *
	 * @return boolean false if type is not core (i.e plugin is updating) or action is not update
	 */
	protected function upgrader_process_complete_callback( WP_Upgrader $upgrader, array $extra ) {

		if ( $extra['type'] !== 'core' || $extra['action'] !== 'update' ) {
			return false;
		}

		require ABSPATH . WPINC . '/version.php';

		$this->log_info = array(
			'new_version' => $wp_version,
			'prev_version' => $this->prev_core_version
		);

		$this->event = 'updated';
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
		$output = '';

		switch ( $event ) :
			case 'updated':
				$updated_lang = get_nashaat_lang( 'core_updated' );
				$from_lang = get_nashaat_lang( 'from' );
				$to_lang = get_nashaat_lang( 'to' );

				list('prev_version' => $prev_version, 'new_version' => $new_version) = $log_info;
				$output = "{$updated_lang} {$from_lang} {$prev_version} {$to_lang} {$new_version}";
				break;
			case 'exported':
				$exported_lang = get_nashaat_lang( 'exported_data' );
				$output = "{$exported_lang}: {$log_info['content']}";
				break;
		endswitch;

		return $output;
	}

}
