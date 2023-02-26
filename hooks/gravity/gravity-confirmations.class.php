<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Log confirmation related actions
 */
class NashaatGravityConfirmations extends NashaatHookBase {
	protected $actions = array(
		array(
			'name' => array( 'gform_pre_confirmation_save', 'gform_pre_confirmation_deleted' ),
			'args' => 2,
			'callback' => 'confirmation_actions_callback'
		),
	);

	protected $filters = array(
		array(
			'name' => 'nashaat_translations',
			'callback' => 'add_gravity_confirmations_translations'
		),
	);

	protected $context = 'gravity_confirmations';
	protected $level = NASHAAT_LOG_LEVEL_MEDIUM;

	/**
	 * Log confirmation actions
	 *
	 * @param array $confirmation Current confirmation data
	 * @param array $form Current form data
	 * @return array $confirmation
	 */
	protected function confirmation_actions_callback( $confirmation, $form ) {
		switch ( current_action() ) {
			case 'gform_pre_confirmation_save':
				$this->event = 'edited';
				$this->level = NASHAAT_LOG_LEVEL_MEDIUM;
				break;

			case 'gform_pre_confirmation_deleted':
				$this->event = 'deleted';
				$this->level = NASHAAT_LOG_LEVEL_HIGH;
				break;
		}

		$this->log_info['form'] = $this->pluck_object(
			$form,
			array( 'id', 'title' )
		);

		$this->log_info['confirmation'] = $this->pluck_object(
			$confirmation,
			array( 'id', 'name' )
		);

		return $confirmation;
	}

	/**
	 * Translations strings for Gravity Forms confirmations
	 *
	 * @param array $translations Current plugin translations
	 * @return array modified $translations array
	 */
	public function add_gravity_confirmations_translations( array $translations ) {
		$confirmations_translations = array(
			'gravity_confirmations' => __( 'Gravity/Confirmations', 'nashaat' )
		);
		return array_merge( $translations, $confirmations_translations );
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

		$form_data['id'] = $log_info['form']['id'];
		$form_data['title'] = $log_info['form']['title'];

		$confirmation_data['id'] = $log_info['confirmation']['id'];
		$confirmation_data['name'] = $log_info['confirmation']['name'];

		$output = $render_class::array_to_html( $form_data, get_nashaat_lang( 'form' ) );
		$output .= $render_class::array_to_html( $confirmation_data, get_nashaat_lang( 'confirmation' ) );

		return $output;
	}
}