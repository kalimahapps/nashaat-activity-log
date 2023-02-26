<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle WP Crontrol cron events
 */
class NashaatWpCrontrolEvents extends NashaatHookBase {

	protected $actions = array(
		array(
			'name' => array( 'crontrol/added_new_event', 'crontrol/added_new_php_event' ),
			'callback' => 'added_event_callback',
		),
		array(
			'name' => array( 'crontrol/edited_event', 'crontrol/edited_php_event' ),
			'callback' => 'edited_event_callback',
			'args' => 2,
		),
		array(
			'name' => 'crontrol/deleted_all_with_hook',
			'args' => 2,
		),
		'crontrol/deleted_event',
		'crontrol/ran_event',
	);

	protected $context = 'wp_crontrol_events';
	protected $level = NASHAAT_LOG_LEVEL_MEDIUM;

	/**
	 * Fires after a new cron event is added (including PHP event)
	 *
	 * @param object $event {
	 *     An object containing the event's data.
	 *
	 *     @type string       $hook      Action hook to execute when the event is run.
	 *     @type int          $timestamp Unix timestamp (UTC) for when to next run the event.
	 *     @type string|false $schedule  How often the event should subsequently recur.
	 *     @type mixed[]      $args      Array containing each separate argument to pass to the hook's callback function.
	 *     @type int          $interval  The interval time in seconds for the schedule. Only present for recurring events.
	 * }
	 * @return void
	 */
	protected function added_event_callback( $event ) {
		$this->event = 'added';
		$this->log_info = array(
			'type' => 'hook_cron',
			'hook' => $event->hook,
			'timestamp' => $event->timestamp,
			'schedule' => $event->schedule,
			'interval' => $event->interval,
			'args'  => $event->args,
		);

		if ( current_action() !== 'crontrol/added_new_php_event' ) {
			return;
		}

		$this->log_info['args']['code'] = sanitize_textarea_field( $event->args['code'] );
		$this->log_info['type'] = 'php_cron';

		// Check if PHP error was raised
		if ( ! empty( $event->args['syntax_error_message'] ) ) {
			$this->level = NASHAAT_LOG_LEVEL_HIGH;
		}
	}

	/**
	 * Fires after a new cron event is edited (including PHP event)
	 *
	 * @param object $event See {@see 'added_event_callback'} for the object structure.
	 * @param object $original An object containing the original event data.
	 * @return void
	 */
	protected function edited_event_callback( $event, $original ) {
		// Find changes between original and new event
		$changes = array();
		foreach ( $event as $key => $value ) {
			if ( $value !== $original->$key ) {
				$changes[ $key ] = array(
					'prev' => $original->$key,
					'new' => $value,
				);
			}
		}

		$this->event = 'edited';
		$this->log_info = array(
			'type' => 'hook_cron',
			'hook' => $event->hook,
			'changes' => $changes,
		);

		// Handle PHP cron event after this point
		if ( current_action() !== 'crontrol/edited_php_event' ) {
			return;
		}

		$this->log_info['type'] = 'php_cron';

		// Get name from args if available
		if ( ! empty( $event->args['name'] ) ) {
			$this->log_info['hook'] = $event->args['name'];
		}

		if ( ! empty( $changes['args'] ) ) {
			$this->log_info['changes']['args']['prev']['code'] = sanitize_textarea_field( $changes['args']['prev']['code'] );
			$this->log_info['changes']['args']['new']['code'] = sanitize_textarea_field( $changes['args']['new']['code'] );
		}
	}

	/**
	 * Fires after a cron event is deleted.
	 *
	 * @param object $event See {@see 'added_event_callback'} for the object structure.
	 * @return void
	 */
	protected function crontrol_deleted_event_callback( $event ) {
		$this->event = 'deleted';
		$this->log_info = array(
			'hook' => $event->hook
		);
	}

	/**
	 * Fires after all cron events with the given hook are deleted.
	 *
	 * @param string $hook    The hook name.
	 * @param int    $deleted The number of events that were deleted.
	 * @return void
	 */
	protected function crontrol_deleted_all_with_hook_callback( $hook, $deleted ) {
		$this->event = 'bulk_deleted';
		$this->log_info = array(
			'hook' => $hook,
			'count' => $deleted,
		);
	}

	/**
	 * Log when a cron event is scheduled to run manually.
	 *
	 * @param object $event See {@see 'added_event_callback'} for the object structure.
	 * @return void
	 */
	protected function crontrol_ran_event_callback( $event ) {
		$this->event = 'manual_ran';
		$this->log_info = array(
			'hook' => $event->hook
		);
	}

	/**
	 * Add translations for WP Crontrol
	 *
	 * @param array $translations Current translations
	 * @return array List of translations to be added
	 */
	public function set_translations( array $translations ) {
		return array(
			'wp_crontrol_events' => __( 'WP Crontrol / Events', 'nashaat' ),
			'hook' => __( 'Hook', 'nashaat' ),
			'timestamp' => __( 'Timestamp', 'nashaat' ),
			'schedule' => __( 'Schedule', 'nashaat' ),
			'args' => __( 'Arguments', 'nashaat' ),
			'next_run' => __( 'Next Run', 'nashaat' ),
			'interval' => __( 'Interval', 'nashaat' ),
			'php_cron' => __( 'PHP Cron', 'nashaat' ),
			'hook_cron' => __( 'Standard Cron', 'nashaat' ),
			'months' => __( 'Months', 'nashaat' ),
			'weeks' => __( 'Weeks', 'nashaat' ),
			'days' => __( 'Days', 'nashaat' ),
			'hours' => __( 'Hours', 'nashaat' ),
			'minutes' => __( 'Minutes', 'nashaat' ),
			'seconds' => __( 'Seconds', 'nashaat' ),
			'bulk_deleted' => __( 'Bulk deleted', 'nashaat' ),
			'manual_ran' => __( 'Manual Run', 'nashaat' ),
		);
	}

	/**
	 * Get display name of the provided schedule
	 *
	 * If the schedule is not found, the provided schedule will be returned
	 *
	 * @param string $schedule Schedule key
	 * @return string Schedule display name if found, otherwise the provided schedule
	 */
	private function get_schedule_display_name( $schedule ) {
		$schedules = wp_get_schedules();
		if ( isset( $schedules[ $schedule ] ) ) {
			return $schedules[ $schedule ]['display'];
		}
		return $schedule;
	}

	/**
	 * Show a human readable duration
	 *
	 * @param integer $timestamp Timestamp to show duration for
	 * @return string Human readable duration
	 */
	private function convert_timestamp_to_duration( int $timestamp ) :string {
		// Find how many weeks, days, hours, minutes and seconds are in the timestamp
		$duration_parts = array(
			'months' => floor( $timestamp / ( 30 * 24 * 60 * 60 ) ),
			'weeks' => floor( $timestamp / WEEK_IN_SECONDS ),
			'days' => floor( ( $timestamp % WEEK_IN_SECONDS ) / DAY_IN_SECONDS ),
			'hours' => floor( ( $timestamp % DAY_IN_SECONDS ) / HOUR_IN_SECONDS ),
			'minutes' => floor( ( $timestamp % HOUR_IN_SECONDS ) / MINUTE_IN_SECONDS ),
			'seconds' => $timestamp % MINUTE_IN_SECONDS
		);

		$output = array();
		foreach ( $duration_parts as $key => $value ) {
			if ( $value > 0 ) {
				$output[ $key ] = $value . ' ' . get_nashaat_lang( $key );
			}
		}

		return implode( ', ', $output );
	}

	/**
	 * Render cron added event
	 *
	 * @param array                $log_info Log information
	 * @param NashaatRenderLogInfo $render_class Render class instance
	 * @return string Rendered HTML
	 */
	private function render_added_event( $log_info, $render_class ) {
		list(
			'type' => $type,
			'hook' => $hook,
			'timestamp' => $timestamp,
			'schedule' => $schedule,
			'args' => $args,
			'interval' => $interval
		) = $log_info;

		if ( ! empty( $timestamp ) ) {
			$timestamp = gmdate( 'Y-m-d H:i:s', $timestamp );
		}

		$schedule = $this->get_schedule_display_name( $schedule );
		if ( empty( $schedule ) ) {
			$schedule = get_nashaat_lang( 'empty' );
		}

		if ( ! empty( $interval ) ) {
			$interval = $this->convert_timestamp_to_duration( $interval );
		}

		$added_data = array(
			'type' => get_nashaat_lang( $type ),
			'name' => $hook,
			'next_run' => $timestamp,
			'schedule' => $schedule,
			'args' => json_encode( $args ),
			'interval' => $interval,
		);

		if ( $type === 'php_cron' ) {
			unset( $added_data['args'] );

			$added_data['name'] = empty( $args['name'] ) ? $hook : $args['name'];
			$added_data['code'] = "<pre>{$args['code']}</pre>";

			// If there is a syntax error, show warning
			if ( ! empty( $args['syntax_error_message'] ) ) {
				$output = '<span class="error-message">';
				$output .= '<span class="dashicons dashicons-warning" aria-hidden="true"></span>';
				$output .= 'Line ' . esc_html( $args['syntax_error_line'] ) . ': ';
				$output .= esc_html( $args['syntax_error_message'] );
				$output .= '</span>';
			}
		}

		return $render_class::array_to_html( $added_data );
	}

	/**
	 * Render cron edited event
	 *
	 * @param array                $log_info Log information
	 * @param NashaatRenderLogInfo $render_class Render class instance
	 * @return string Rendered HTML
	 */
	private function render_edited_event( $log_info, $render_class ) {
		$changes_data = array(
			'type' => get_nashaat_lang( $log_info['type'] ),
			'name' => $log_info['hook'],
		);

		$output = $render_class::array_to_html( $changes_data );

		$args = $log_info['changes']['args'];

		// Remove args from array so it can be processed separately
		unset( $log_info['changes']['args'] );

		// Loop through changes and show them
		foreach ( $log_info['changes'] as $change_key => $change_data ) :
			$changes_array = array();

			// Format output based on change key
			switch ( $change_key ) :
				case 'timestamp':
					$changes_array['prev'] = gmdate( 'Y-m-d H:i:s', $change_data['prev'] );
					$changes_array['new'] = gmdate( 'Y-m-d H:i:s', $change_data['new'] );
					break;
				case 'schedule':
					$changes_array['prev'] = $this->get_schedule_display_name( $change_data['prev'] );
					$changes_array['new'] = $this->get_schedule_display_name( $change_data['new'] );
					break;
				case 'interval':
					$changes_array['prev'] = get_nashaat_lang( 'empty' );
					$changes_array['new'] = get_nashaat_lang( 'empty' );

					if ( ! empty( $change_data['prev'] ) ) {
						$changes_array['prev'] = $this->convert_timestamp_to_duration( $change_data['prev'] );
					}

					if ( ! empty( $change_data['new'] ) ) {
						$changes_array['new'] = $this->convert_timestamp_to_duration( $change_data['new'] );
					}

					break;
				default:
					$changes_array = array(
						'prev' => $change_data['prev'],
						'new' => $change_data['new'],
					);
					break;
			endswitch;

			$output .= $render_class::array_to_html( $changes_array, get_nashaat_lang( $change_key ) );
		endforeach;

		// Handle $args changes
		// If type is hook_cron, show args as json
		if ( $log_info['type'] === 'hook_cron' ) {
			$arg_change = array(
				'prev' => json_encode( $args['prev'] ),
				'new' => json_encode( $args['new'] ),
			);

			$output .= $render_class::array_to_html( $arg_change, get_nashaat_lang( 'args' ) );
		}

		// Check if there is a name change
		if ( ! empty( $args['prev']['name'] ) ) {
			$arg_changes = array(
				'prev' => empty( $args['prev']['name'] ) ? get_nashaat_lang( 'empty' ) : $args['prev']['name'],
				'new' => empty( $args['new']['name'] ) ? get_nashaat_lang( 'empty' ) : $args['new']['name'],
			);
			$output .= $render_class::array_to_html( $arg_changes, get_nashaat_lang( 'name' ) );
		}

		// Check if there a code change
		if ( ! empty( $args['prev']['code'] ) ) {
			$arg_changes = array(
				'prev' => "<pre>{$args['prev']['code']}</pre>",
				'new' => "<pre>{$args['new']['code']}</pre>",
			);
			$output .= $render_class::array_to_html( $arg_changes, get_nashaat_lang( 'code' ) );

			// If there is a syntax error, show warning
			if ( ! empty( $args['new']['syntax_error_message'] ) ) {
				$output .= '<span class="error-message">';
				$output .= '<span class="dashicons dashicons-warning" aria-hidden="true"></span>';
				$output .= 'Line ' . esc_html( $args['new']['syntax_error_line'] ) . ': ';
				$output .= esc_html( $args['new']['syntax_error_message'] );
				$output .= '</span>';
			}
		}

		return $output;
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
	public function render_log_info_output( $log_info, string $event, array $item, $render_class ) : string {
		$output = '';
		switch ( $event ) {
			case 'added':
				$output .= $this->render_added_event( $log_info, $render_class );
				break;
			case 'edited':
				$output .= $this->render_edited_event( $log_info, $render_class );
				break;
			case 'deleted':
				$output .= $log_info['hook'];
				break;
			case 'bulk_deleted':
				// translators: %1$s: Hook name, %2$s: Number of events
				$output .= sprintf( __( 'Deleted %1$s cron events for %2$s', 'nashaat' ), $log_info['count'], $log_info['hook'] );
				break;
			case 'manual_ran':
				// translators: %1$s: Hook name
				$output .= sprintf( __( 'Hook <strong>%1$s</strong> was manually run', 'nashaat' ), $log_info['hook'] );
				break;
		}
		return $output;
	}
}