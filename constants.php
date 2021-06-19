<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;
define( 'NASHAAT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'NASHAAT_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'NASHAAT_LOG_LEVEL_NORMAL', 0 );
define( 'NASHAAT_LOG_LEVEL_LOW', 1 );
define( 'NASHAAT_LOG_LEVEL_MEDIUM', 2 );
define( 'NASHAAT_LOG_LEVEL_HIGH', 3 );
define( 'NASHAAT_DB_TABLE', $wpdb->prefix . 'nashaat_log' );
define( 'NASHAAT_SETTINGS_SLUG', 'nashaat-settings' );