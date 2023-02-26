<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main nashaat class.
 * Handles hooking into WordPress menu actions, adding settings,
 * adding css and js and initiating log table
 */
class NashaatLog {
	use NashaatUtil;

	private $table_instance = null;

	private $settings_instance = null;

	/**
	 * Constructor function. Hook into WordPress actions and filters
	 *
	 * @param NashaatSettings $settings Nashaat Setting
	 */
	public function __construct( NashaatSettings $settings ) {
		$this->settings_instance = $settings;

		add_action( 'admin_init', array( $this, 'instantiate_table_object' ) );
		add_action( 'admin_init', array( $this, 'export_to_csv' ) );

		add_action( 'admin_menu', array( $this, 'add_nashaat_menu_items' ) );
		// add_action( 'admin_menu', array( $this, 'export_to_csv' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'set-screen-option', array( $this, 'save_nashaat_screen_options' ), 10, 3 );

		do_action( 'nashaat_init' );
	}

	/**
	 * Instantiate table object in admin_init hook as errors
	 * would occurs if done in __constructor
	 *
	 * @return void
	 */
	public function instantiate_table_object() {
		$this->table_instance = new NashaatLogTable();
	}
	/**
	 * Add js and css assets
	 *
	 * @param string $hook_suffix Screen name
	 * @return bool|void False if not viewing nashaat table
	 */
	public function enqueue_assets( string $hook_suffix ) {

		wp_enqueue_script( 'notify-js', NASHAAT_PLUGIN_URL . '/libs/notify-0.4.2.min.js', array(), '0.4.2', true );
		if ( $hook_suffix === 'toplevel_page_nashaat-table' ) {

			wp_enqueue_style( 'font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css', array(), '5.15.3' );
			wp_enqueue_style( 'main-style', NASHAAT_PLUGIN_URL . '/css/main-style.css', array(), '1.0' );
			wp_enqueue_script( 'main-script', NASHAAT_PLUGIN_URL . '/js/main-script.js', array(), '1.0', true );

			$params = array( 'nashaat_nonce' => wp_create_nonce( 'nashaat_nonce' ) );
			wp_localize_script( 'main-script', 'vars', $params );
		}

		if ( $hook_suffix === 'nashaat_page_nashaat-settings' ) {
			wp_enqueue_style( 'settings-style', NASHAAT_PLUGIN_URL . '/css/settings-style.css', array(), '1.0' );
			wp_enqueue_script( 'settings-script', NASHAAT_PLUGIN_URL . '/js/settings-script.js', array( 'jquery', 'wp-i18n' ), '1.0', true );
		}
	}

	/**
	 * Add menu item
	 *
	 * @return void
	 */
	public function add_nashaat_menu_items() {
		$menu_icon = file_get_contents( NASHAAT_PLUGIN_URL . '/assets/logo.svg' );

		$page_hook = add_menu_page(
			get_nashaat_lang( 'nashaat' ),
			get_nashaat_lang( 'nashaat' ),
			'manage_options',
			'nashaat-table',
			array( $this, 'render_nashaat_log_table' ),
			'data:image/svg+xml;base64,' . base64_encode( $menu_icon ),
			25
		);

		add_action( "load-$page_hook", array( $this, 'add_nashaat_screen_options' ) );
		add_submenu_page(
			'nashaat-table',
			get_nashaat_lang( 'nashaat_settings' ),
			get_nashaat_lang( 'settings' ),
			'manage_options',
			'nashaat-settings',
			array( $this, 'show_nashaat_settings' )
		);
	}

	/**
	 * Display settings
	 *
	 * @return void
	 */
	public function show_nashaat_settings() {
		$this->settings_instance->render_setting_outline();
	}

	/**
	 * Save perpage option
	 *
	 * @param mixed   $status The value to save instead of the option value. Default false (to skip saving the current option).
	 * @param string  $option The option name.
	 * @param integer $value The option value.
	 * @return int $value
	 */
	public function save_nashaat_screen_options( $status, string $option, int $value ) {
		return $value;
	}

	/**
	 * Add screen options.
	 * Also instantiate table class so columns toggle shows in screen options
	 *
	 * @return void
	 */
	public function add_nashaat_screen_options() {
		$option = 'per_page';
		$args = array(
			'label' => 'Logs per page',
			'default' => 30,
			'option' => 'nashaat_logs_per_page'
		);
		add_screen_option( $option, $args );

	}

	/**
	 * Show logs table
	 *
	 * @return void
	 */
	public function render_nashaat_log_table() {
		echo '<div class="wrap"><h1>' . get_nashaat_lang( 'nashaat_logs' ) . '</h1>';
		echo "<form id='nashaat-log-filter' method='get'>";
		echo "<input type='hidden' name='page' value='" . esc_attr( $_GET['page'] ) . "' />";
		$this->table_instance->render_table();
		echo '</form></div>';
	}

	/**
	 * Handle export to CSV export. Export can be for the full
	 * data set or the current filtered data
	 *
	 * @return void
	 */
	public function export_to_csv() {

		$__get = filter_input_array( INPUT_GET );
		if ( ! isset( $__get['export-data'] ) || $__get['export-data'] !== 'csv' ) {
			return;
		}

		 // Check admin screen and current user privileges
		if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		// Check for nonce
		if ( ! isset( $__get['export-nonce'] ) ) {
			return;
		}

		$nonce = $__get['export-nonce'];
		if ( ! wp_verify_nonce( $nonce, 'export_csv_nonce' ) ) {
			wp_die( 'Unable to proceed with export' );
		}
		global $wpdb;

		// Prepare sql query
		$table = NASHAAT_DB_TABLE;
		$filter_query = $this->table_instance->get_filter_query();
		$where = $filter_query['where'];

		ob_start();

		$domain = $_SERVER['SERVER_NAME'];
		$filename = 'users-' . $domain . '-' . time() . '.csv';

		$__get = filter_input_array( INPUT_GET );

		// Add headers
		$output_handler = fopen( 'php://output', 'w' );

		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		header( 'Content-Description: File Transfer' );
		header( 'Content-type: text/csv' );
		header( "Content-Disposition: attachment; filename={$filename}" );
		header( 'Expires: 0' );
		header( 'Pragma: public' );

		$orderby = 'date';
		if ( isset( $__get['orderby'] ) ) {
			$orderby = sanitize_sql_orderby( $__get['orderby'] );
		}

		$order = 'DESC';
		if ( isset( $__get['order'] ) && ( ! in_array( $__get['order'], array( 'DESC', 'ASC' ) ) ) ) {
			$order = $__get['order'];
		}

		$log_data = $wpdb->get_results(
			"SELECT * FROM {$table} {$where}
			ORDER BY {$orderby} {$order}",
			ARRAY_A
		);

		if ( count( $log_data ) === 0 ) {
			return;
		}

		foreach ( $log_data as $index => $row ) {
			$insert_row = $this->pluck_object( $row, array( 'id', 'data', 'level', 'user_id', 'ip', 'context', 'event', 'log_info' ) );
			if ( $index === 0 ) {
				fputcsv( $output_handler, array_keys( $insert_row ) );
			}

			fputcsv( $output_handler, array_values( $insert_row ) );
		}

		fclose( $output_handler );
		ob_get_flush();

		die();
	}
}
?>