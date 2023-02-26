<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add translations for gravity forms
 */
class NashaatGravity {

	/**
	 * Add translations filter
	 */
	public function __construct() {
		 // Add translations
		add_filter( 'nashaat_translations', array( $this, 'add_gravity_translations' ) );
	}

	/**
	 * Translations strings for Gravity Forms
	 *
	 * @param array $translations Current plugin translations
	 * @return array modified $translations array
	 */
	public function add_gravity_translations( array $translations ) {
		$confirmations_translations = array(
			'gravity_forms' => __( 'Gravity/Forms', 'nashaat' ),
			'gravity_confirmations' => __( 'Gravity/Confirmations', 'nashaat' ),
			'gravity_notifications' => __( 'Gravity/Notifications', 'nashaat' ),
			'gravity_core_settings' => __( 'Gravity/Core Settings', 'nashaat' ),
			'gravity_form_settings' => __( 'Gravity/Core Settings', 'nashaat' ),
			'gravity_data' => __( 'Gravity/Data', 'nashaat' ),
			'form' => __( 'Form', 'nashaat' ),
			'notification' => __( 'Notification', 'nashaat' ),
			'confirmation' => __( 'Confirmation', 'nashaat' ),
			'duplicated' => __( 'Duplicated', 'nashaat' ),
			'views_reset' => __( 'Views Reset', 'nashaat' ),
		);
		return array_merge( $translations, $confirmations_translations );
	}
}