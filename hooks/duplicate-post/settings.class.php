<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle duplicate post plugin actions
 */
class NashaatDuplicatePostSettings extends NashaatHookBase {
	protected $actions = array(
		array(
			'name' => 'updated_option',
			'args' => 3
		),
		array(
			'name' => 'admin_init',
			'callback' => 'set_plugin_options',
			'args' => 0
		)
	);

	protected $filters = array(
		array(
			'name' => 'wp_redirect',
			'callback' => 'log_settings_changes',
		),
	);


	/**
	 * Holds all settings that will be monitored for changes
	 *
	 * @var array
	 */
	public $all_settings = array();

	/**
	 * Holds all updated settings so they can be accessed later in footer
	 *
	 * @var array
	 */
	public $updated_settings = array();

	protected $context = 'duplicate_post_settings';
	protected $level = NASHAAT_LOG_LEVEL_MEDIUM;


	/**
	 * Callback for updated_option action
	 *
	 * Loop through all settings and check if the updated option
	 * matches any of them. If it does, record the changes.
	 *
	 * @param string $option Name of the option
	 * @param mixed  $old_value The old option value.
	 * @param mixed  $new_value The new option value.
	 * @return void
	 */
	protected function updated_option_callback( $option, $old_value, $new_value ) {
		foreach ( $this->all_settings as $option_key => $option_data ) {
			if ( ! is_array( $option_data ) ) {
				if ( $option_key === $option ) {
					$this->updated_settings[ $option_key ] = array(
						'prev' => $old_value,
						'new' => $new_value
					);
				}
				continue;
			}

			foreach ( $option_data['items'] as $sub_option_key => $sub_option_data ) {
				if ( $sub_option_key !== $option ) {
					continue;
				}

				$this->updated_settings[ $option_key ][ $sub_option_key ] = array(
					'prev' => $old_value,
					'new' => $new_value
				);
			}
		}
	}

	/**
	 * Callback for wp_redirect filter
	 *
	 * If there are any updated settings, log them.
	 * The reason we are using wp_redirect filter is because
	 * it is one of the last filters to be called before the page
	 * is rendered. This way we can access the updated settings
	 * in the footer.
	 *
	 * @param string $location The path or URL to redirect to.
	 * @return string The path or URL to redirect to.
	 */
	protected function log_settings_changes( $location ) {
		if ( empty( $this->updated_settings ) ) {
			return $location;
		}

		$this->event = 'updated';
		$this->log_info = $this->updated_settings;

		// Clear updated settings
		$this->updated_settings = array();

		return $location;
	}

	/**
	 * Set plugin options to an array so they can be monitored for changes.
	 *
	 * @return void
	 */
	public function set_plugin_options() {
		$this->all_settings = array(
			'elements-to-copy' => array(
				'label' => __( 'Post/page elements to copy', 'duplicate-post' ),
				'items' => array(
					'duplicate_post_copytitle' => __( 'Title', 'duplicate-post' ),
					'duplicate_post_copydate' => __( 'Date', 'duplicate-post' ),
					'duplicate_post_copystatus' => __( 'Status', 'duplicate-post' ),
					'duplicate_post_copyslug' => __( 'Slug', 'duplicate-post' ),
					'duplicate_post_copyexcerpt' => __( 'Excerpt', 'duplicate-post' ),
					'duplicate_post_copycontent' => __( 'Content', 'duplicate-post' ),
					'duplicate_post_copythumbnail' => __( 'Featured Image', 'duplicate-post' ),
					'duplicate_post_copytemplate' => __( 'Template', 'duplicate-post' ),
					'duplicate_post_copyformat' => __( 'Post format', 'duplicate-post' ),
					'duplicate_post_copyauthor' => __( 'Author', 'duplicate-post' ),
					'duplicate_post_copypassword' => __( 'Password', 'duplicate-post' ),
					'duplicate_post_copyattachments' => __( 'Attachments', 'duplicate-post' ),
					'duplicate_post_copychildren' => __( 'Children', 'duplicate-post' ),
					'duplicate_post_copycomments' => __( 'Comments', 'duplicate-post' ),
					'duplicate_post_copymenuorder' => __( 'Menu order', 'duplicate-post' ),
				)
			),
			'duplicate_post_title_prefix' => __( 'Title prefix', 'duplicate-post' ),
			'duplicate_post_title_suffix' => __( 'Title suffix', 'duplicate-post' ),
			'duplicate_post_increase_menu_order_by' => __( 'Increase menu order by', 'duplicate-post' ),
			'duplicate_post_blacklist' => __( 'Do not copy these fields', 'duplicate-post' ),
			'duplicate_post_taxonomies_blacklist' => __( 'Do not copy these taxonomies', 'duplicate-post' ),
			'duplicate_post_roles' => __( 'Roles allowed to copy', 'duplicate-post' ),
			'duplicate_post_types_enabled' => __( 'Enable for these post types', 'duplicate-post' ),
			'show-original' => array(
				'label' => __( 'Show original item', 'duplicate-post' ),
				'items' => array(
					'duplicate_post_show_original_meta_box' => __( 'In a metabox in the Edit screen', 'duplicate-post' ),
					'duplicate_post_show_original_column' => __( 'In a column in the Post list', 'duplicate-post' ),
					'duplicate_post_show_original_in_post_states' => __( 'After the title in the Post list', 'duplicate-post' ),
				)
			),
			'duplicate_post_show_notice' => __( 'Show welcome notice', 'duplicate-post' ),
			'duplicate_post_show_link' => __( 'Show these links', 'duplicate-post' ),
			'duplicate_post_show_link_in' => __( 'Show links in', 'duplicate-post' ),
		);
	}


	/**
	 * Add translations strings
	 *
	 * @param array $translations Current plugin translations
	 * @return array modified $translations array
	 */
	public function set_translations( array $translations ) {
		return array(
			'duplicate_post_settings' => __( 'Duplicate Post/Settings', 'nashaat' ),
		);
	}

	/**
	 * Get option title by searching in all settings recursively
	 *
	 * @param string $option_key Option key
	 * @param array  $settings   Settings array
	 * @return string|boolean Option title if found, false otherwise
	 */
	private function get_option_title( $option_key, $settings = array() ) {

		if ( count( $settings ) === 0 ) {
			$settings = $this->all_settings;
		}

		if ( ! empty( $settings[ $option_key ] ) ) {
			$selected_settings = $settings[ $option_key ];
			return is_array( $selected_settings ) ? $selected_settings['label'] : $selected_settings;
		}

		foreach ( $settings as $setting_key => $setting_data ) {
			if ( isset( $setting_data['items'] ) ) {
				$title = $this->get_option_title( $option_key, $setting_data['items'] );
				if ( $title !== false ) {
					return $title;
				}
			}
		}

		return false;
	}

	/**
	 * Check if array is nested
	 *
	 * @param array $array Array to check
	 * @return bool True if array is nested, false otherwise
	 */
	private function is_nested_array( $array ) {
		if ( ! is_array( $array ) ) {
			return false;
		}

		return ! isset( $array['prev'] ) && ! isset( $array['new'] );
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

		foreach ( $log_info as $key => $value ) {

			// For single value
			if ( ! $this->is_nested_array( $value ) ) {

				// For checkbox inputs
				if ( $key === 'duplicate_post_show_link' || $key === 'duplicate_post_show_link_in' ) {
					$value['prev'] = array_keys( $value['prev'] );
					$value['new'] = array_keys( $value['new'] );
				}

				$prev_value = is_array( $value['prev'] ) ? implode( ', ', $value['prev'] ) : $value['prev'];
				$new_value = is_array( $value['new'] ) ? implode( ', ', $value['new'] ) : $value['new'];

				$changes_array = array(
					'prev' => $prev_value,
					'new' => $new_value,
				);

				$title = $this->get_option_title( $key );
				$output .= $render_class::array_to_html( $changes_array, $title );
				continue;
			}

			// Display in a table for multiple values
			$section_output = array();
			foreach ( $value as $sub_key => $sub_value ) {
				$section_changes_array = array(
					'prev' => $render_class::boolean_to_toggle( $sub_value['prev'] ),
					'new' => $render_class::boolean_to_toggle( $sub_value['new'] ),
				);

				$title = $this->get_option_title( $sub_key );
				$section_output[ $title ] = $section_changes_array;
			}

			$title = $this->get_option_title( $key );
			$output .= $render_class::array_to_table( $section_output, $title );
		}

		return $output;
	}
}