<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Log options related actions
 */
class NashaatOptionsHooks extends NashaatHookBase {

	public $options = array(
		'blogname',
		'blogdescription',
		'gmt_offset',
		'date_format',
		'time_format',
		'start_of_week',
		'timezone_string',
		'WPLANG',
		'new_admin_email',
		'siteurl',
		'home',
		'users_can_register',
		'default_role',
		'default_pingback_flag',
		'default_ping_status',
		'default_comment_status',
		'comments_notify',
		'moderation_notify',
		'comment_moderation',
		'require_name_email',
		'comment_previously_approved',
		'comment_max_links',
		'moderation_keys',
		'disallowed_keys',
		'show_avatars',
		'avatar_rating',
		'avatar_default',
		'close_comments_for_old_posts',
		'close_comments_days_old',
		'thread_comments',
		'thread_comments_depth',
		'page_comments',
		'comments_per_page',
		'default_comments_page',
		'comment_order',
		'comment_registration',
		'show_comments_cookies_opt_in',
		'thumbnail_size_w',
		'thumbnail_size_h',
		'thumbnail_crop',
		'medium_size_w',
		'medium_size_h',
		'large_size_w',
		'large_size_h',
		'image_default_size',
		'image_default_align',
		'image_default_link_type',
		'uploads_use_yearmonth_folders',
		'posts_per_page',
		'posts_per_rss',
		'rss_use_excerpt',
		'show_on_front',
		'page_on_front',
		'page_for_posts',
		'blog_public',
		'default_category',
		'default_email_category',
		'default_link_category',
		'default_post_format',
		'mailserver_url',
		'mailserver_port',
		'mailserver_login',
		'mailserver_pass',
		'ping_sites'
	);
	protected $actions = array(
		array(
			'name' => 'updated_option',
			'args' => 3
		)
	);

	protected $context = 'options';
	protected $level = NASHAAT_LOG_LEVEL_MEDIUM;

	/**
	 * Callback for update option hook. It will be called only on core site options
	 *
	 * @param string $option_name Option name
	 * @param mixed  $old_value Old option value
	 * @param mixed  $new_value New option value
	 * @return bool|void False if option is not part of core
	 */
	protected function updated_option_callback( string $option_name, $old_value, $new_value ) {

		if ( ! in_array( $option_name, $this->options ) ) {
			return false;
		}

		$this->log_info = array(
			'option_name' => $option_name,
			'new_value' => $new_value,
			'old_value' => $old_value
		);
		$this->event = 'updated';
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
		$options_data = array(
			'option_name' => $log_info['option_name'],
			'prev' => $log_info['old_value'],
			'new' => $log_info['new_value']
		);
		return $render_class::array_to_html( $options_data );
	}
}