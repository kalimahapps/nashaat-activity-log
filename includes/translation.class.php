<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class to hold and manage translations strings for the plugin
 */
class NashaatTranslation {
	private $nashaat_lang = array();

	/**
	 * Hold class instance
	 *
	 * @var object
	 */
	private static $instance = null;

	/**
	 * Private constructor because class is singleton
	 */
	private function __construct() {
		$this->nashaat_lang = $this->set_translation_strings();
	}

	/**
	 * Set translation strings array
	 *
	 * @return array Array of translations and related keys
	 */
	private function set_translation_strings() {
		$translation_strings = array(
			'nashaat' => __( 'Nashaat', 'nashaat' ),
			'nashaat_settings' => __( 'Nashaat Settings', 'nashaat' ),
			'nashaat_logs' => __( 'Nashaat Log', 'nashaat' ),
			'settings' => __( 'Settings', 'nashaat' ),
			'today' => __( 'Today', 'nashaat' ),
			'yesterday' => __( 'Yesterday', 'nashaat' ),
			'last_7_days' => __( 'Last 7 days', 'nashaat' ),
			'last_14_days' => __( 'Last 14 days', 'nashaat' ),
			'last_30_days' => __( 'Last 30 days', 'nashaat' ),
			'last_90_days' => __( 'Last 90 days', 'nashaat' ),
			'information' => __( 'Information', 'nashaat' ),
			'low' => __( 'Low', 'nashaat' ),
			'medium' => __( 'Medium', 'nashaat' ),
			'hight' => __( 'High', 'nashaat' ),
			'option_name' => __( 'Option Name', 'nashaat' ),
			'plugin_name' => __( 'Plugin Name', 'nashaat' ),
			'version' => __( 'Version', 'nashaat' ),
			'description' => __( 'Description', 'nashaat' ),
			'caption' => __( 'Caption', 'nashaat' ),
			'user_login' => __( 'User Login', 'nashaat' ),
			'display_name' => __( 'Display Name', 'nashaat' ),
			'role' => __( 'Roles', 'nashaat' ),
			'id' => __( 'ID', 'nashaat' ),
			'title' => __( 'Title', 'nashaat' ),
			'name' => __( 'Name', 'nashaat' ),
			'path' => __( 'Path', 'nashaat' ),
			'file' => __( 'File', 'nashaat' ),
			'added' => __( 'Added', 'nashaat' ),
			'trashed' => __( 'Trashed', 'nashaat' ),
			'deleted' => __( 'Deleted', 'nashaat' ),
			'restored' => __( 'Restored', 'nashaat' ),
			'updated' => __( 'Updated', 'nashaat' ),
			'created' => __( 'Created', 'nashaat' ),
			'scheduled' => __( 'Scheduled', 'nashaat' ),
			'activated' => __( 'Activated', 'nashaat' ),
			'deactivated' => __( 'Deactivated', 'nashaat' ),
			'installed' => __( 'Installed', 'nashaat' ),
			'edited' => __( 'Edited', 'nashaat' ),
			'spammed' => __( 'Spammed', 'nashaat' ),
			'unspammed' => __( 'Unspammed', 'nashaat' ),
			'approved' => __( 'Approved', 'nashaat' ),
			'unapproved' => __( 'Unapproved', 'nashaat' ),
			'exported' => __( 'Exported', 'nashaat' ),
			'imported' => __( 'Exported', 'nashaat' ),
			'reorder' => __( 'Reorder', 'nashaat' ),
			'failed_login_attempt' => __( 'Failed Login', 'nashaat' ),
			'login' => __( 'Login', 'nashaat' ),
			'logout' => __( 'Lougout', 'nashaat' ),
			'registered' => __( 'Registered', 'nashaat' ),
			'image_edited' => __( 'Image Edit', 'nashaat' ),
			'image_restored' => __( 'Image Restored', 'nashaat' ),
			'media' => __( 'Media', 'nashaat' ),
			'user' => __( 'User', 'nashaat' ),
			'options' => __( 'Options', 'nashaat' ),
			'plugin' => __( 'Plugin', 'nashaat' ),
			'system' => __( 'System', 'nashaat' ),
			'theme' => __( 'Theme', 'nashaat' ),
			'menu' => __( 'Menu', 'nashaat' ),
			'widget' => __( 'Widget', 'nashaat' ),
			'sidebar' => __( 'Sidebar', 'nashaat' ),
			'taxonomy' => __( 'Taxonomy', 'nashaat' ),
			'comment' => __( 'Comment', 'nashaat' ),
			'post' => __( 'Post', 'nashaat' ),
			'page' => __( 'Page', 'nashaat' ),
			'author' => __( 'Author', 'nashaat' ),
			'content' => __( 'Content', 'nashaat' ),
			'excerpt' => __( 'Excerpt', 'nashaat' ),
			'comment_status' => __( 'Comment Status', 'nashaat' ),
			'ping_status' => __( 'Ping Status', 'nashaat' ),
			'slug' => __( 'Slug', 'nashaat' ),
			'sticky' => __( 'Sticky', 'nashaat' ),
			'featured_media' => __( 'Featured Media', 'nashaat' ),
			'status' => __( 'Status', 'nashaat' ),
			'changes' => __( 'Changes', 'nashaat' ),
			'edits' => __( 'Edits', 'nashaat' ),
			'categories' => __( 'Categories', 'nashaat' ),
			'tags' => __( 'Tags', 'nashaat' ),
			'parent' => __( 'Parent', 'nashaat' ),
			'menu_order' => __( 'Menu order', 'nashaat' ),
			'prev_count' => __( 'Previous count', 'nashaat' ),
			'new_count' => __( 'New count', 'nashaat' ),
			'not_available' => __( 'Not available', 'nashaat' ),
			'enabled' => __( 'Enabled', 'nashaat' ),
			'disabled' => __( 'Disabled', 'nashaat' ),
			'switched' => __( 'Switched', 'nashaat' ),
			'prev' => __( 'Previous', 'nashaat' ),
			'new' => __( 'New', 'nashaat' ),
			'from' => __( 'From', 'nashaat' ),
			'to' => __( 'To', 'nashaat' ),
			'date' => __( 'Date', 'nashaat' ),
			'level' => __( 'Level', 'nashaat' ),
			'context' => __( 'Context', 'nashaat' ),
			'event' => __( 'Event', 'nashaat' ),
			'ip' => __( 'IP Address', 'nashaat' ),
			'data' => __( 'Data', 'nashaat' ),
			'edited_item' => __( 'Edited item', 'nashaat' ),
			'type' => __( 'Type', 'nashaat' ),
			'post_type' => __( 'Post type', 'nashaat' ),
			'rotated_left' => __( 'Rotated left', 'nashaat' ),
			'rotated_right' => __( 'Rotated right', 'nashaat' ),
			'flipped_horizontal' => __( 'Flipped horizontally', 'nashaat' ),
			'flipped_vertical' => __( 'Flipped vertically', 'nashaat' ),
			'cropped' => __( 'Cropped', 'nashaat' ),
			'scaled' => __( 'Scaled', 'nashaat' ),
			'index' => __( 'Index', 'nashaat' ),
			'at' => __( 'at', 'nashaat' ),
			'empty' => __( 'empty', 'nashaat' ),
			'days' => _x( 'Days', 'settings', 'nashaat' ),
			'prev_version' => __( 'Previous Version', 'nashaat' ),
			'core_updated' => __( 'Core updated', 'nashaat' ),
			'exported_data' => __( 'Exported data', 'nashaat' ),
			'processing' => __( 'Processing ...', 'nashaat' ),
			'general_settings' => __( 'General', 'nashaat' ),
			'keep_days' => _x( 'Keep logs for', 'settings', 'nashaat' ),
			'keep_days_desc' => _x( 'Number of days to store logs. Set to 0 to keep logs indefinitely ', 'settings', 'nashaat' ),
			'purge_logs' => _x( 'Purge logs', 'settings', 'nashaat' ),
			'purge_logs_desc' => _x( 'This action will delete all logs data', 'settings', 'nashaat' ),
			'purge_process_success' => _x( 'Log data deleted successfully', 'settings', 'nashaat' ),
			'purge_process_fail' => _x( 'Log data not deleted!', 'settings', 'nashaat' ),
			'log_admin_actions' => _x( 'Log admin actions', 'settings', 'nashaat' ),
			'export_csv' => __( 'Export to CSV', 'nashaat' ),
			'export_filtered_csv' => __( 'Export Filtered data to CSV', 'nashaat' ),
			'order_filter' => __( 'Order', 'nashaat' ),
			'asc' => __( 'ascending', 'nashaat' ),
			'desc' => __( 'descending', 'nashaat' ),
			'user_id' => __( 'User ID', 'nashaat' ),
			'filters_applied' => __( 'Filters Applied:', 'nashaat' ),
			'search_logs' => __( 'Search logs', 'nashaat' ),
			'guest' => __( 'Guest', 'nashaat' ),
			'no_items_found' => __( 'No items found', 'nashaat' ),
			'user_login' => __( 'login name', 'nashaat' ),
			'user_pass' => __( 'password', 'nashaat' ),
			'user_nicename' => __( 'nice name', 'nashaat' ),
			'delete' => __( 'Delete', 'nashaat' ),
			'user_email' => __( 'email', 'nashaat' ),
			'user_url' => __( 'url', 'nashaat' ),
			'display_name' => __( 'display name', 'nashaat' ),
			'caps' => __( 'capabilities', 'nashaat' ),
			'no_title' => __( 'No Title', 'nashaat' ),
			'not_logged' => __( 'Not Logged', 'nashaat' ),
			'actions' => __( 'Actions', 'nashaat' ),
			'invalid_id' => __( 'Invalid ID', 'nashaat' ),
			'record_not_deleted' => __( 'Record was not deleted!', 'nashaat' ),
			'unable_to_delete' => __( 'Unable to delete record', 'nashaat' ),
			'exception_error' => __( 'Exception error', 'nashaat' ),
		);

		return apply_filters( 'nashaat_translations', $translation_strings );
	}

	/**
	 * Check if the provided string is translatable.
	 *
	 * Translatable strings should have no spaces and be lowercase
	 *
	 * @param string $string String to check
	 * @return boolean True if translatable, false otherwise
	 */
	private function is_translatable_string( $string ) {
		return ( ! preg_match( '/\s/', $string ) && strtolower( $string ) === $string );
	}

	/**
	 * Get translation string based on key provided
	 *
	 * @param string $key Key to retrieve related translation string
	 * @return string Error message if key does not exist, translation string otherwise
	 */
	public function get_translation_string( string $key ) {
		if ( ! $this->is_translatable_string( $key ) ) {
			return $key;
		}

		if ( ! isset( $this->nashaat_lang[ $key ] ) ) {
			// translators: %s is the key of the string that could not be found
			return sprintf( __( 'Language key <b>%s</b> does not exist', 'nashaat' ), $key );
		}
		return $this->nashaat_lang[ $key ];
	}

	/**
	 * Get class instance
	 *
	 * @return object Class instance
	 */
	public static function get_instance() {
		if ( self::$instance == null ) {
			self::$instance = new NashaatTranslation();
		}

		return self::$instance;
	}
}