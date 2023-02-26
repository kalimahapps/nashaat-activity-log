<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Log notifications related actions
 */
class NashaatGravityNotifications extends NashaatHookBase {
	protected $actions = array(
		array(
			'name' => array(
				'gform_pre_notification_activated',
				'gform_pre_notification_deactivated',
				'gform_pre_notification_deleted'
			),
			'args' => 2,
			'callback' => 'notification_actions_callback'
		),
		array(
			'name' => 'gform_pre_notification_save',
			'args' => 3,
		),
	);

	protected $context = 'gravity_notifications';
	protected $level = NASHAAT_LOG_LEVEL_MEDIUM;

	/**
	 * Log notification actions
	 *
	 * @param array $notification Current notification data
	 * @param array $form Current form data
	 * @return void
	 */
	protected function notification_actions_callback( $notification, $form ) {
		switch ( current_action() ) {
			case 'gform_pre_notification_activated':
				$this->event = 'activated';
				$this->level = NASHAAT_LOG_LEVEL_LOW;
				break;

			case 'gform_pre_notification_deactivated':
				$this->event = 'deactivated';
				$this->level = NASHAAT_LOG_LEVEL_MEDIUM;
				break;

			case 'gform_pre_notification_deleted':
				$this->event = 'deleted';
				$this->level = NASHAAT_LOG_LEVEL_HIGH;
				break;
		}

		$this->log_info['form'] = $this->pluck_object(
			$form,
			array( 'id', 'title' )
		);
		$this->log_info['notification'] = $this->pluck_object(
			$notification,
			array( 'id', 'name' )
		);
	}

	/**
	 * Log notification save and edit action
	 *
	 * @param array   $notification Current notification array
	 * @param array   $form Current form data
	 * @param boolean $is_new True if this is a new notification. False otherwise.
	 * @return array $notification data
	 */
	protected function gform_pre_notification_save_callback( $notification, $form, $is_new ) {
		$this->event = $is_new ? 'created' : 'edited';
		$this->level = $is_new ? NASHAAT_LOG_LEVEL_LOW : NASHAAT_LOG_LEVEL_MEDIUM;

		$this->log_info['form'] = $this->pluck_object(
			$form,
			array( 'id', 'title' )
		);
		$this->log_info['notification'] = $this->pluck_object(
			$notification,
			array( 'id', 'name' )
		);

		return $notification;
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

		$notification_data['id'] = $log_info['notification']['id'];
		$notification_data['name'] = $log_info['notification']['name'];

		$output = $render_class::array_to_html( $form_data, get_nashaat_lang( 'form' ) );
		$output .= $render_class::array_to_html( $notification_data, get_nashaat_lang( 'notification' ) );

		return $output;
	}
}