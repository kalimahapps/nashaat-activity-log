<?php

// if uninstall.php is not called by WordPress, die
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

require_once 'constants.php';

// Delete database and options
global $wpdb;
$table = NASHAAT_DB_TABLE;
$wpdb->query( "DROP TABLE IF EXISTS $table;" );
delete_option( 'nashaat_db_version' );
delete_option( NASHAAT_SETTINGS_SLUG );