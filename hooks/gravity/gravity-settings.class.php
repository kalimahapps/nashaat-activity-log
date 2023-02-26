<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Log settings related actions
 */
class NashaatGravitySettings extends NashaatHookBase {

	protected $actions = array(
		array(
			'name' => 'updated_option',
			'args' => 3
		),
		array(
			'name' => 'gform_loaded',
			'args' => 0,
			'callback' => 'set_gravity_settings'
		)
	);

	protected $filters = array(
		array(
			'name' => 'gform_settings_save_button',
			'callback' => 'log_gravity_settings'
		)
	);

	/**
	 * Holds all settings that will be monitored for changes
	 *
	 * @var array
	 */
	public $all_settings = array();

	/**
	 * Holds all updated settings so they can be accessed later in footer
	 *
	 * @var array
	 */
	public $updated_settings = array();

	protected $context = 'gravity_core_settings';
	protected $level = NASHAAT_LOG_LEVEL_MEDIUM;

	/**
	 * Hook into updated option.
	 *
	 * Check the name of the option against the predefined
	 * settings and if it matches, log the change.
	 *
	 * @param string $option_name Name of the option
	 * @param mixed  $old_value  Old value of the option
	 * @param mixed  $new_value New value of the option
	 * @return void
	 */
	protected function updated_option_callback( string $option_name, $old_value, $new_value ) {

		if ( ! isset( $this->all_settings[ $option_name ] ) ) {
			return;
		}

		// Don't register license key values
		if ( 'rg_gforms_key' === $option_name ) {
			$this->updated_settings[ $option_name ] = array(
				get_nashaat_lang( 'not_logged' ),
				get_nashaat_lang( 'not_logged' )
			);
			return;
		}

		$this->updated_settings[ $option_name ] = array(
			'prev' => $old_value,
			'new' => $new_value
		);
	}

	/**
	 * Set gravity settings array when gravity is loaded
	 */
	protected function set_gravity_settings() {
		$this->all_settings = array(
			'gform_enable_toolbar_menu' => __( 'Enable Toolbar Menu', 'gravityforms' ),
			'gform_enable_logging' => __( 'Enable Logging', 'gravityforms' ),
			'gform_enable_background_updates' => __( 'Automatic Background Updates', 'gravityforms' ),
			'gform_sticky_admin_messages' => __( 'Enable Toolbar Menu', 'gravityforms' ),
			'rg_gforms_disable_css' => __( 'Output Default CSS', 'gravityforms' ),
			'rg_gforms_enable_html5' => __( 'Output HTML5', 'gravityforms' ),
			'rg_gforms_currency' => __( 'Default Currency', 'gravityforms' ),
			'gform_enable_noconflict' => __( 'No Conflict Mode', 'gravityforms' ),
			'rg_gforms_enable_akismet' => __( 'Enable Akismet Integration', 'gravityforms' ),
			'rg_gforms_key' => __( 'Support License Key', 'gravityforms' )
		);
	}

	/**
	 * Save gravity settings changes
	 *
	 * @param string $html HTML of the save button.
	 * @return string $html
	 */
	protected function log_gravity_settings( $html ) {
		if ( count( $this->updated_settings ) === 0 ) {
			return $html;
		}

		$this->event = 'updated';
		$this->log_info = $this->updated_settings;

		return $html;
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
		$meta_data_output = '';
		foreach ( $log_info as $setting => $setting_data ) {
			$setting_title = $this->all_settings[ $setting ] ?? $setting;

			$meta_data = array(
				'prev' => $render_class::boolean_to_toggle( $setting_data['prev'] ),
				'new' => $render_class::boolean_to_toggle( $setting_data['new'] ),
			);

			$meta_data_output .= $render_class::array_to_html( $meta_data, $setting_title );
		}

		return $meta_data_output;

	}
}