<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Base class for all log types
 */
abstract class NashaatHookBase {
	use NashaatUtil;

	/**
	 * Context of log item (page, comment, post .. etc)
	 *
	 * @var string
	 */
	protected $context;

	/**
	 * Log level
	 *
	 * @var int
	 */
	protected $level = NASHAAT_LOG_LEVEL_NORMAL;

	/**
	 * Event type (edited, created, activated .. etc)
	 *
	 * @var string
	 */
	protected $event;

	/**
	 * Log info array
	 *
	 * @var array
	 */
	protected $log_info;

	/**
	 * Log info array of arrays.
	 * Use this instead of $log_info to add multiple log item at once
	 *
	 * @var array
	 */
	protected $log_info_array = null;

	/**
	 * Array of action hooks to be added add_action
	 *
	 * @var array
	 */
	protected $actions = array();

	/**
	 * Array of filter hooks to be added to add_filter
	 *
	 * @var array
	 */
	protected $filters = array();

	/**
	 * When set, it will be used by add_filter to add a hook for each
	 * context in the array.
	 * If set to null, hook will only be added to $context
	 *
	 * @var array
	 */
	protected $render_contexts = null;

	/**
	 * Array of all hooks (actions and filters) that have been added
	 *
	 * @var array
	 */
	private $hooks = array();

	/**
	 * Data of the user performing the action
	 *
	 * @var object
	 */
	private $user_data;

	/**
	 * Current timestamp
	 *
	 * @var int
	 */
	private $date;

	/**
	 * IP address of user
	 *
	 * @var string
	 */
	private $ip_address;

	private $last_action = '';
	/**
	 * Constructor function
	 */
	public function __construct() {

		// combine actions and filters
		$this->hooks['filters'] = $this->filters;
		$this->hooks['actions'] = $this->actions;

		$this->process_hooks();

		if ( ! is_array( $this->render_contexts ) ) {
			$this->render_contexts = array( $this->context );
		}

		foreach ( $this->render_contexts as $context ) {
			add_filter( "render_log_info_{$context}", array( $this, 'render_log_info_output' ), 10, 4 );
		}

		add_filter( 'nashaat_translations', array( $this, 'set_translations_callback' ) );
	}

	/**
	 * Handle action hooks from subclasses
	 *
	 * @throws UnexpectedValueException If callback key is not found when name key is an array.
	 * @return void
	 */
	private function process_hooks() {

		foreach ( $this->hooks as $hook_type => $hooks_array ) {
			$hook_function = $hook_type === 'actions' ? 'add_action' : 'add_filter';

			foreach ( $hooks_array as $hook ) {
				if ( ! is_array( $hook ) ) {
					$callback_name = $this->clean_string( $hook );
					$hook_function( $hook, array( $this, $callback_name . '_callback' ) );
					continue;
				}

				// Set defaults
				$hook['priority'] = $this->not_set_get_default( $hook, 'priority', 10 );

				// Add 20 as default argument number.
				// Change in future to reflect actual argument count
				$hook['args'] = $this->not_set_get_default( $hook, 'args', 20 );

				// if name is an array make sure there is a callback key
				if ( is_array( $hook['name'] ) ) {
					if ( ! isset( $hook['callback'] ) ) {
						throw new UnexpectedValueException( 'Callback key is missing' );
					}

					foreach ( $hook['name'] as $name ) {
						$hook_function( $name, array( $this, $hook['callback'] ), $hook['priority'], $hook['args'] );
					}

					// no need to go further
					continue;
				}

				$callback_name = $this->clean_string( $hook['name'] );
				$hook['callback'] = $this->not_set_get_default( $hook, 'callback', $callback_name . '_callback' );

				$hook_function( $hook['name'], array( $this, $hook['callback'] ), $hook['priority'], $hook['args'] );
			}
		}
	}


	/**
	 * Render log info data to html
	 *
	 * @param array                $log_info Log info array
	 * @param string               $event Event type
	 * @param array                $item Row details
	 * @param NashaatRenderLogInfo $render_class Render class
	 * @return void
	 */
	abstract public function render_log_info_output( array $log_info, string $event, array $item, $render_class);

	/**
	 * Check if key is set in array. If set return value otherwise return $default
	 *
	 * @param array  $array Array to check against
	 * @param string $key Key to check for
	 * @param mixed  $default Default value to return if key is not set
	 * @return mixed Key value if set or default
	 */
	private function not_set_get_default( array $array, string $key, $default ) {
		if ( isset( $array[ $key ] ) ) {
			return $array[ $key ];
		}

		return $default;

	}

	/**
	 * Check if keys exist and have values. Used for arrays like $_POST
	 * to make sure to proceed when certain keys and values exist
	 *
	 * @param array $check_against Check the keys and values of this array
	 * @param array $check_for Compare of keys and values from this array
	 * @return boolean False if single key or value not set, true otherwise
	 */
	protected function is_keys_values_set( $check_against, array $check_for ) {
		if ( empty( $check_against ) ) {
			return false;
		}
		foreach ( $check_for as $key => $value ) {
			if ( ! isset( $check_against[ $key ] ) || $check_against[ $key ] !== $value ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Catches all function calls except __construct().
	 *
	 * Arguments will always be provided as an array even for strings
	 *
	 * @param  string $callback Function name
	 * @param  array  $arguments Function arguments
	 *
	 * @throws BadMethodCallException If method does not exist.
	 * @throws InvalidArgumentException If method is not callable.
	 *
	 * @return mixed|void Return results for add_filter, void otherwise
	 */
	public function __call( string $callback, array $arguments ) {

		if ( ! method_exists( $this, $callback ) ) {
			throw new BadMethodCallException( "Method '{$callback}' does not exist" );
		}

		if ( ! is_callable( array( $this, $callback ) ) ) {
			// Wrong function called.
			throw new InvalidArgumentException(
				sprintf(
					'File: %1$s<br>Line %2$d<br>Not callable: %3$s',
					__FILE__,
					__LINE__,
					print_r( $callback, true )
				)
			);
		}

		$results = $this->{$callback}( ...$arguments );

		// Log only if there log info
		if ( empty( $this->log_info ) && empty( $this->log_info_array ) ) {
			return $results;
		}

		$this->log_items();

		// reset log_info and log_info_array
		$this->log_info = null;
		$this->log_info_array = null;

		// Return results in case of filter
		return $results;

	}

	/**
	 * Prepare log data. If log_info_array property is set then loop through data
	 * and add for each element of the array. log_info property will be ignored.
	 *
	 * @return void
	 */
	public function log_items() {

		$this->ip_address = $this->get_the_user_ip();
		$this->date = time();
		$this->user_data = $this->get_current_user_data();

		if ( isset( $this->log_info_array ) ) {
			foreach ( $this->log_info_array as $log_info ) {
				$this->log_single_item( $log_info );
			}
			return;
		}

		$this->log_single_item( $this->log_info );
	}

	/**
	 * Insert single item into database
	 *
	 * @param array $log_info Log info
	 * @return void
	 */
	private function log_single_item( array $log_info ) {
		global $wpdb;
		// Check if admin actions are recorded
		$settings = get_option( NASHAAT_SETTINGS_SLUG );
		if ( current_user_can( 'manage_options' ) && ( ! isset( $settings['log_admin_actions'] ) || $settings['log_admin_actions'] != 1 ) ) {
			return;
		}

		$log_details = array(
			'date' => $this->date,
			'level' => $this->level,
			'user_id' => $this->user_data['id'],
			'user_data' => maybe_serialize( $this->user_data ),
			'ip' => $this->ip_address,
			'context' => $this->context,
			'event' => $this->event,
			'log_info' => maybe_serialize( $log_info )
		);

		$wpdb->insert(
			NASHAAT_DB_TABLE,
			$log_details,
			array( '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
		);
	}

	/**
	 * Retrieve user ip
	 *
	 * @return string user ip
	 */
	private function get_the_user_ip() {
		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip_address = $_SERVER['HTTP_CLIENT_IP'];
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			$ip_address = $_SERVER['REMOTE_ADDR'];
		}

		 return $ip_address;
	}

	/**
	 * Get information about current logged in user,
	 * or default data if not logged in
	 *
	 * @return array user data
	 */
	private function get_current_user_data() {
		$current_user = wp_get_current_user();
		if ( $current_user->ID === 0 ) {
			return array(
				'id' => $current_user->ID,
				'name' => '',
				'roles' => 'guest'
			);
		}

		$user_data = array(
			'id' => $current_user->ID,
			'name' => $current_user->data->display_name,
			'roles' => implode( '|', $current_user->roles )
		);

		return $user_data;
	}

	/**
	 * Add or manipulate translations
	 *
	 * Override this method in your child class to add your own translations.
	 *
	 * @param array $translations Current plugin translations
	 * @return array modified $translations array
	 */
	public function set_translations_callback( array $translations ) {
		$new_translations = array();
		if ( method_exists( $this, 'set_translations' ) ) {
			$new_translations = $this->set_translations( $translations );
		}

		return array_merge( $translations, $new_translations );
	}
}