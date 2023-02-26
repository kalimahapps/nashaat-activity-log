<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle plugin setup
 */
class NashaatSetup {

	/**
	 * Current database version
	 *
	 * @var string
	 */
	private $db_version = '1.1';

	/**
	 * Table name
	 *
	 * @var string
	 */
	private $table_name = NASHAAT_DB_TABLE;

	/**
	 * Setup hooks
	 */
	public function __construct() {
		register_activation_hook( NASHAAT_MAIN_FILE, array( $this, 'on_plugin_install' ) );
		register_deactivation_hook( NASHAAT_MAIN_FILE, array( $this, 'on_plugin_deactivation' ) );

		add_action( 'plugins_loaded', array( $this, 'on_plugin_update' ) );
	}

	/**
	 * Run when plugin deactivated.
	 *
	 * @return void
	 */
	public function on_plugin_deactivation() {
		wp_clear_scheduled_hook( 'nashaat_cron_purge_after_days' );
	}

	/**
	 * Install database using dbDelta on plugin activation
	 *
	 * @return void
	 */
	public function on_plugin_install() {
		$this->create_db_table();
	}

	/**
	 * Handle plugin update
	 *
	 * @return void
	 */
	public function on_plugin_update() {
		$installed_version = get_option( 'nashaat_db_version' );

		if ( $installed_version !== $this->db_version ) {
			$this->create_db_table();
		}
	}

	/**
	 * Install or update table using dbDelta
	 *
	 * @return void
	 */
	private function create_db_table() {
		$sql = "CREATE TABLE $this->table_name (
			id int(11) NOT NULL AUTO_INCREMENT,
			date int(11) NOT NULL DEFAULT '0',
			level int(4) NOT NULL DEFAULT '0',
			user_id int(11) NOT NULL DEFAULT '0',
			bookmark smallint(1) NOT NULL DEFAULT 0,
			user_data longtext NOT NULL,
			ip varchar(100) NOT NULL,
			context varchar(225) NOT NULL,
			event varchar(225) NOT NULL,
			log_info longtext NOT NULL,
			PRIMARY KEY  (id),
			INDEX idx_level (level),
			INDEX idx_userid (user_id),
			INDEX idx_context (context),
			INDEX idx_event (event)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8;";

		include_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		update_option( 'nashaat_db_version', $this->db_version );
	}
}

new NashaatSetup();