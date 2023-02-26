<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Log settings related actions
 */
class NashaatGravityFormSettings extends NashaatHookBase {
	protected $actions = array(
		array(
			'name' => 'gform_loaded',
			'args' => 0,
			'callback' => 'set_gravity_form_settings'
		)
	);

	protected $filters = array(
		array(
			'name' => 'gform_form_settings_fields',
			'args' => 2,
		),
		array(
			'name' => 'gform_pre_form_settings_save',
			'args' => 1,
		)
	);

	/**
	 * Holds all settings that will be monitored for changes
	 *
	 * @var array
	 */
	public $all_settings = array();

	/**
	 * Hold a reference to gravity form before saving
	 *
	 * @var object
	 */
	public $form;

	/**
	 * Holds all updated settings so they can be accessed later in footer
	 *
	 * @var array
	 */
	public $updated_settings = array();

	protected $context = 'gravity_form_settings';
	protected $level = NASHAAT_LOG_LEVEL_MEDIUM;


	/**
	 * Get form settings before saving
	 *
	 * @param array  $fields The form settings fields
	 * @param object $form The current form.
	 * @return array $fields The form settings fields
	 */
	protected function gform_form_settings_fields_callback( $fields, $form ) {
		$this->form = $form;

		return $fields;
	}


	/**
	 * Log form settings after saving
	 *
	 * @param object $form The current form.
	 * @return object return $form
	 */
	protected function gform_pre_form_settings_save_callback( $form ) {

		$this->updated_settings['id'] = rgar( $this->form, 'id' );
		$this->updated_settings['title'] = rgar( $this->form, 'title' );

		foreach ( $this->all_settings as $setting_key => $setting_label ) {

			// Compare and get changes
			$current_value = rgar( $this->form, $setting_key );
			$new_value     = rgpost( "_gform_setting_{$setting_key}" );

			if ( gettype( $current_value ) == 'array' ) {
				$current_value = json_encode( $current_value );
				$new_value = json_encode( $new_value );
			}

			if ( $current_value != $new_value ) {
				$this->updated_settings['changes'][ $setting_key ] = array(
					'prev' => $current_value,
					'new' => $new_value,
				);
			}
		}
		$this->log_gravity_settings();

		return $form;
	}

	/**
	 * Set gravity settings array when gravity is loaded
	 */
	protected function set_gravity_form_settings() {
		$this->all_settings = array(
			'title' => __( 'Form Title', 'gravityforms' ),
			'description' => __( 'Form Description', 'gravityforms' ),
			'labelPlacement' => __( 'Label Placement', 'gravityforms' ),
			'descriptionPlacement' => __( 'Description Placement', 'gravityforms' ),
			'subLabelPlacement' => __( 'Sub-Label Placement', 'gravityforms' ),
			'validationSummary' => __( 'Validation Summary', 'gravityforms' ),
			'requiredIndicator' => __( 'Required Field Indicator', 'gravityforms' ),
			'customRequiredIndicator' => __( 'Custom Required Indicator', 'gravityforms' ),
			'cssClass' => __( 'CSS Class Name', 'gravityforms' ),
			'saveEnabled' => __( 'Enable Save and Continue', 'gravityforms' ),
			'saveButtonText' => __( 'Link Text', 'gravityforms' ),
			'limitEntries' => __( 'Limit number of entries', 'gravityforms' ),
			'limitEntriesPeriod' => __( 'Number of Entries', 'gravityforms' ),
			'limitEntriesNumber' => __( 'Number of Entries', 'gravityforms' ),
			'limitEntriesMessage' => __( 'Entry Limit Reached Message', 'gravityforms' ),
			'scheduleForm' => __( 'Schedule Form', 'gravityforms' ),
			'scheduleStart' => __( 'Schedule Start Date/Time', 'gravityforms' ),
			'scheduleEnd' => __( 'Schedule Form End Date/Time', 'gravityforms' ),
			'schedulePendingMessage' => __( 'Form Pending Message', 'gravityforms' ),
			'scheduleMessage' => __( 'Form Expired Message', 'gravityforms' ),
			'requireLogin' => __( 'Require user to be logged in', 'gravityforms' ),
			'requireLoginMessage' => __( 'Require Login Message', 'gravityforms' ),
			'enableHoneypot' => __( 'Anti-spam honeypot', 'gravityforms' ),
			'enableAnimation' => __( 'Animated transitions', 'gravityforms' ),
			'markupVersion' => __( 'Enable legacy markup', 'gravityforms' )
		);
	}

	/**
	 * Save gravity settings changes
	 *
	 * @return void
	 */
	protected function log_gravity_settings() {
		if ( count( $this->updated_settings['changes'] ) === 0 ) {
			return;
		}

		$this->event = 'updated';
		$this->log_info = $this->updated_settings;
	}

	/**
	 * Get gravity forms translations
	 *
	 * Select and radio element are saved usIng their keys
	 * This function will get the translation of the key
	 *
	 * @return array Array of elements and their options translations
	 */
	private function get_gravity_translations() {
		return array(
			'labelPlacement' => array(
				'top_label' => __( 'Top aligned', 'gravityforms' ),
				'left_label' => __( 'Left aligned', 'gravityforms' ),
				'right_label' => __( 'Right aligned', 'gravityforms' ),
			),
			'descriptionPlacement' => array(
				'below' => __( 'Below inputs', 'gravityforms' ),
				'above' => __( 'Above inputs', 'gravityforms' ),
			),
			'subLabelPlacement' => array(
				'below' => __( 'Below inputs', 'gravityforms' ),
				'above' => __( 'Above inputs', 'gravityforms' ),
			),
			'requiredIndicator' => array(
				'text' => __( 'Text: (Required)', 'gravityforms' ),
				'asterisk' => __( 'Asterisk: *', 'gravityforms' ),
				'custom' => __( 'Custom: ', 'gravityforms' ),
			),
			'limitEntriesPeriod' => array(
				'day' => __( 'per day', 'gravityforms' ),
				'week' => __( 'per week', 'gravityforms' ),
				'month' => __( 'per month', 'gravityforms' ),
				'year' => __( 'per year', 'gravityforms' ),
				'' => __( 'total entries', 'gravityforms' ),
			),
		);
	}

	/**
	 * Convert gravity date array to string
	 *
	 * @param array|string $date_array The date array, or empty string.
	 * @return string Formatted date string
	 */
	private function maybe_get_date( $date_array ) : string {
		if ( ! is_array( $date_array ) ) {
			return $date_array;
		}

		$date = $date_array['date'];
		$hour = str_pad( $date_array['hour'], 2, '0', STR_PAD_LEFT );
		$minute = str_pad( $date_array['minute'], 2, '0', STR_PAD_LEFT );
		$ampm = $date_array['ampm'];

		return sprintf( '%s %s:%s %s', $date, $hour, $minute, $ampm );
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

		$id = isset( $log_info['id'] ) ? $log_info['id'] : $log_info['ID'];

		if ( is_null( $id ) ) {
			$id = 0;
		}

		$translations = $this->get_gravity_translations();

		// Form data
		$form_data['id'] = $render_class::maybe_get_post_edit_link( $id );
		$form_data['title'] = $render_class::maybe_get_post_title( $log_info );

		$meta_data_output = $render_class::array_to_html( $form_data );

		// Show changes
		foreach ( $log_info['changes'] as $setting => $setting_data ) {
			$setting_title = $this->all_settings[ $setting ] ?? $setting;

			$meta_data = array(
				'prev' => $setting_data['prev'],
				'new' => $setting_data['new'],
			);

			/**
			 * Handle each setting according to its type
			 */
			switch ( $setting ) {
				case 'validationSummary':
				case 'saveEnabled':
				case 'limitEntries':
				case 'scheduleForm':
				case 'enableHoneypot':
				case 'enableAnimation':
				case 'markupVersion':
				case 'requireLogin':
					$meta_data = array(
						'prev' => $render_class::boolean_to_toggle( $setting_data['prev'] ),
						'new' => $render_class::boolean_to_toggle( $setting_data['new'] )
					);

					break;
				case 'labelPlacement':
				case 'descriptionPlacement':
				case 'subLabelPlacement':
				case 'requiredIndicator':
				case 'limitEntriesPeriod':
					$meta_data = array(
						'prev' => $translations[ $setting ][ $setting_data['prev'] ],
						'new' => $translations[ $setting ][ $setting_data['new'] ],
					);
					break;

				case 'scheduleStart':
				case 'scheduleEnd':
					$meta_data = array(
						'prev' => $this->maybe_get_date( $setting_data['prev'] ),
						'new' => $this->maybe_get_date( $setting_data['new'] ),
					);
					break;
			}

			$meta_data_output .= $render_class::array_to_html( $meta_data, $setting_title );
		}

		return $meta_data_output;

	}
}