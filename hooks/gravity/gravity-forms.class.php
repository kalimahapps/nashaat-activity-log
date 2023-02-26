<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Log form related actions
 */
class NashaatGravityForms extends NashaatHookBase {
	protected $actions = array(
		array(
			'name' => 'gform_after_save_form',
			'args' => 2
		),
		array(
			'name' => 'gform_post_form_duplicated',
			'args' => 2
		),
		array(
			'name' => array(
				'gform_post_form_restored',
				'gform_post_form_trashed',
				'gform_before_delete_form',
				'gform_post_form_activated',
				'gform_post_form_deactivated',
				'gform_post_form_views_deleted'
			),
			'callback' => 'form_actions_callback'
		)
	);

	protected $context = 'gravity_forms';
	protected $level = NASHAAT_LOG_LEVEL_MEDIUM;

	/**
	 * Log gravity forms various actions such as deleting, activating,
	 * deactivating ... etc
	 *
	 * @param integer $form_id Form id
	 * @return void
	 */
	protected function form_actions_callback( int $form_id ) {

		$form = GFAPI::get_form( $form_id );

		$this->log_info = $this->pluck_object(
			$form,
			array( 'id', 'title' )
		);

		switch ( current_action() ) {
			case 'gform_post_form_restored':
				$this->event = 'restored';
				$this->level = NASHAAT_LOG_LEVEL_LOW;
				break;

			case 'gform_post_form_trashed':
				$this->event = 'trashed';
				$this->level = NASHAAT_LOG_LEVEL_MEDIUM;
				break;

			case 'gform_before_delete_form':
				$this->event = 'deleted';
				$this->level = NASHAAT_LOG_LEVEL_HIGH;
				break;

			case 'gform_post_form_activated':
				$this->event = 'activated';
				$this->level = NASHAAT_LOG_LEVEL_LOW;
				break;

			case 'gform_post_form_deactivated':
				$this->event = 'deactivated';
				$this->level = NASHAAT_LOG_LEVEL_HIGH;
				break;

			case 'gform_post_form_views_deleted':
				$this->event = 'views_reset';
				$this->level = NASHAAT_LOG_LEVEL_HIGH;
				break;
		}
	}

	/**
	 * Log when a form is saved or created
	 *
	 * @param object  $form Current gravity form object
	 * @param boolean $is_new True if this is a new form being created. False if this is an existing form being updated.
	 * @return void
	 */
	protected function gform_after_save_form_callback( $form, $is_new ) {
		$this->event = $is_new ? 'created' : 'edited';
		$this->level = $is_new ? NASHAAT_LOG_LEVEL_LOW : NASHAAT_LOG_LEVEL_MEDIUM;

		$this->log_info = $this->pluck_object(
			$form,
			array( 'id', 'title' )
		);
	}

	/**
	 * Log when a form is duplicated
	 *
	 * @param integer $form_id The ID of the form being duplicated.
	 * @param integer $new_form_id The ID of the newly created, duplicate form.
	 * @return void
	 */
	protected function gform_post_form_duplicated_callback( int $form_id, int $new_form_id ) {
		$this->event = 'duplicated';
		$this->level = NASHAAT_LOG_LEVEL_MEDIUM;

		$form = GFAPI::get_form( $form_id );

		$this->log_info = $this->pluck_object(
			$form,
			array( 'id', 'title' )
		);

		$this->log_info['new_form_id'] = $new_form_id;
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

		$form_data['id'] = $render_class::maybe_get_post_edit_link( $id );
		$form_data['title'] = $render_class::maybe_get_post_title( $log_info );

		return $render_class::array_to_html( $form_data );
	}
}