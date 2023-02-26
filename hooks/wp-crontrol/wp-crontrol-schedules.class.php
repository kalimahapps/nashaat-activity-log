<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle WP Crontrol cron schedules
 */
class NashaatWpCrontrolSchedules extends NashaatHookBase {

	protected $actions = array(
		array(
			'name' => 'crontrol/added_new_schedule',
			'args' => 3
		),
		'crontrol/deleted_schedule'
	);

	protected $context = 'wp_crontrol_schedules';
	protected $level = NASHAAT_LOG_LEVEL_MEDIUM;

	/**
	 * Log when a new cron schedule is added.
	 *
	 * @param string $name     The internal name of the schedule.
	 * @param int    $interval The interval between executions of the new schedule.
	 * @param string $display  The display name of the schedule.
	 * @return void
	 */
	protected function crontrol_added_new_schedule_callback( $name, $interval, $display ) {
		$this->log_info = array(
			'name' => $name,
			'interval' => $interval,
			'display' => $display,
		);
		$this->event = 'added';
	}

	/**
	 * Log when a cron schedule is deleted.
	 *
	 * @param string $name The internal name of the schedule.
	 * @return void
	 */
	protected function crontrol_deleted_schedule_callback( $name ) {
		$this->log_info = array(
			'name' => $name,
		);
		$this->event = 'deleted';
		$this->level = NASHAAT_LOG_LEVEL_HIGH;
	}

	/**
	 * Add translations for WP Crontrol
	 *
	 * @param array $translations Current translations
	 * @return array List of translations to be added
	 */
	public function set_translations( array $translations ) {
		return array(
			'wp_crontrol_schedules' => __( 'WP Crontrol / Schedules', 'nashaat' ),
			'display' => __( 'Display', 'nashaat' ),
		);
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
	 * Render html output
	 *
	 * @param array                $log_info Log info array
	 * @param string               $event Event name
	 * @param array                $item Row details
	 * @param NashaatRenderLogInfo $render_class Render class instance
	 * @return string Html string
	 */
	public function render_log_info_output( $log_info, string $event, array $item, $render_class ) : string {
		if ( $event === 'deleted' ) {
			return $log_info['name'];
		}

		$added_data = array(
			'name' => $log_info['name'],
			'display' => $log_info['name'],
			'interval' => $this->convert_timestamp_to_duration( $log_info['interval'] ),
		);
		return $render_class::array_to_html( $added_data );

	}
}