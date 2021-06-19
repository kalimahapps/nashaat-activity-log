<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Log widget related actions
 */
class NashaatWidgetHooks extends NashaatHookBase {
	protected $actions = array(
		array(
			'name' => 'admin_init',
			'callback' => 'widget_actions_callback'
		)
	);

	protected $context = 'widget';
	protected $level = NASHAAT_LOG_LEVEL_MEDIUM;

	/**
	 * Handle widget added and deleted actions
	 *
	 * @return bool|void False if action could not be determined
	 */
	protected function widget_actions_callback() {
		$__post = filter_input_array( INPUT_POST );

		$is_save = $this->is_keys_values_set( $__post, array( 'action' => 'save-widget' ) );
		$is_order = $this->is_keys_values_set( $__post, array( 'action' => 'widgets-order' ) );

		if ( $is_save === false && $is_order === false ) {
			return;
		}

		$event = false;

		// Hold order changes values
		$order_changes = array();

		// Hold edit changes for a single widget
		$edited_changes = array();

		switch ( true ) :
			case $is_save:
				$is_new = $this->is_keys_values_set( $__post, array( 'add_new' => 'multi' ) );
				if ( $is_new ) {
					$event = 'added';
					break;
				}

				$is_deleted = $this->is_keys_values_set( $__post, array( 'delete_widget' => '1' ) );
				if ( $is_deleted ) {
					$event = 'deleted';
					break;
				}

				$is_edited = isset( $__post['id_base'] );
				if ( ! $is_edited ) {
					break;
				}
				$event = 'edited';

				$widget_base = $__post['id_base'];
				$widget_number = $__post['widget_number'];
				$widget_id = $__post['widget-id'];

				// Return widget data for all widget occurances
				$saved_widget_data = get_option( "widget_${widget_base}" );
				$new_widget_data = $__post[ "widget-${widget_base}" ];

				// Don't proceed if single widget data is not available in either $_POST or db
				if ( ! isset( $saved_widget_data[ $widget_number ] ) || ! isset( $new_widget_data[ $widget_number ] ) ) {
					break;
				}

				// Loop through saved and new data to get the changes
				$single_widget_saved_data = $saved_widget_data[ $widget_number ];
				$single_wdiget_new_data = $new_widget_data[ $widget_number ];

				// Change on to 1 for new widget data
				$single_wdiget_new_data = array_map(
					function( $value ) {
						if ( $value === 'on' ) {
							return 1;
						}
						return $value;
					},
					$single_wdiget_new_data
				);

				foreach ( $single_widget_saved_data as $key => $value ) {
					// Check if key exists in new widget,
					// if it does not exist it means it is a boolean value set to false
					if ( ! isset( $single_wdiget_new_data[ $key ] ) ) {
						$edited_changes[ $key ] = array( $value, 0 );
						continue;
					}

					// skip if value is the same
					if ( $single_wdiget_new_data[ $key ] == $value ) {
						continue;
					}

					// Add to changed array (prev => new)
					$edited_changes[ $key ] = array( $value, $single_wdiget_new_data[ $key ] );
				}
				break;

			case $is_order:
				if ( empty( $__post['sidebars'] ) ) {
					break;
				}

				$saved_sidebars_widgets = get_option( 'sidebars_widgets', array() );
				$saved_sidebars_widgets = $this->rearrange_sidebars_to_widgets( $saved_sidebars_widgets );

				$new_sidebars_widgets = $this->rearrange_sidebars_to_widgets( $__post['sidebars'] );

				foreach ( $saved_sidebars_widgets as $widget_id => $widget_data ) :
					// Check if id exists in new sidebar widgets
					// It should exists since widgets are the same but their order and placement is different
					// This is only a failsafe measure
					if ( ! isset( $new_sidebars_widgets[ $widget_id ] ) ) {
						continue;
					}

					$new_widget_data = $new_sidebars_widgets[ $widget_id ];

					$widget_order_changes = array();

					// Compare each value for saved widget data against the new widget data
					// if different then add to $widget_order_changes array
					foreach ( $widget_data as $key => $value ) {
						if ( $value !== $new_widget_data[ $key ] ) {
							$widget_order_changes[ $key ] = array( $value, $new_widget_data[ $key ] );
						}
					}

					// Add to order_changes array if values found
					if ( count( $widget_order_changes ) > 0 ) {
						$order_changes[ $widget_id ] = $widget_order_changes;
					}
				endforeach;

				$event = 'reorder';
				break;
		endswitch;

		if ( $event === false ) {
			return;
		}

		$this->event = $event;

		// Has there been order changes?
		if ( count( $order_changes ) > 0 ) {
			$this->log_info['changes'] = $order_changes;
			return;
		}

		// insert and delete actions. Make sure sidebar value exists
		if ( empty( $__post['sidebar'] ) ) {
			return;
		}

		global $wp_registered_sidebars;

		$sidebar_key = $__post['sidebar'];
		$sidebar_data = $wp_registered_sidebars[ $sidebar_key ];

		$this->log_info ['name'] = $__post['id_base'];
		$this->log_info ['sidebar'] = $this->pluck_object( $sidebar_data, array( 'name', 'id' ) );

		// Has there been widget changes?
		if ( count( $edited_changes ) > 0 ) {
			$this->log_info['changes'] = $edited_changes;
		}

	}
	/**
	 * Rearrange sidebars array so the main key of the array is the widget name
	 * instead of the sidebar.
	 *
	 * Each widget name will contain an array of its index and sidebar it belongs to
	 *
	 * @param array $sidebars_array Sidebar array to modify
	 * @return array Modified $sidebars_array
	 */
	private function rearrange_sidebars_to_widgets( array $sidebars_array ) {
		$widgets_array = array();

		// Exclude sidebar with empty widgets
		$sidebars_array = array_filter( $sidebars_array, array( $this, 'get_non_empty_values' ) );

		foreach ( $sidebars_array as $sidebar_name => $widgets ) :
			$sidebar_widgets = $widgets;

			// If not an array then explode comma separated widget ids
			if ( ! is_array( $sidebar_widgets ) ) {
				$sidebar_widgets = explode( ',', $sidebar_widgets );
				$sidebar_widgets = array_map( array( $this, 'clean_widget_name' ), $sidebar_widgets );
			}

			// Go through each widget and add sidebar name and index to it
			foreach ( $sidebar_widgets as $widget_index => $widget_id ) {
				$widgets_array[ $widget_id ] = array(
					'sidebar' => $sidebar_name,
					'index' => $widget_index
				);
			}
		endforeach;

		return $widgets_array;
	}

	/**
	 * Check array values and return only non empty. Callback for array_filter
	 *
	 * @param string|array $value Value to check for emptiness
	 * @return bool False if empty, true otherwise
	 */
	private function get_non_empty_values( $value ) {
		if ( ! is_array( $value ) ) {
			return strlen( $value ) > 0;
		}

		if ( count( $value ) === 0 ) {
			return false;
		}
		if ( count( $value ) === 1 && empty( $value[0] ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Widget ids are forumulated like "widget-5_archive-12".
	 * Clean up and return without widget-#
	 *
	 * @param string $widget_name Widget id
	 * @return string Widget name withouth widget-# prefix
	 */
	private function clean_widget_name( string $widget_name ) :string {
		return preg_replace( '/^(widget-[0-9]+)+?_/i', '', $widget_name );
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
		$output = '';

		// For reorder event show prev and new position
		switch ( $event ) :
			case 'reorder':
				$changes = $log_info['changes'];
				$changes_output = '';
				foreach ( $changes as $widget_id => $widget_changes ) :
					$sidebar_lang = get_nashaat_lang( 'sidebar' );
					$index_lang = get_nashaat_lang( 'index' );
					$widget_lang = get_nashaat_lang( 'widget' );

					$changes_output .= "<h5>{$widget_lang}: {$widget_id}</h5>";
					$from = array();
					$to = array();

					if ( isset( $widget_changes['sidebar'] ) ) {
						$from[] = "{$sidebar_lang} <b>{$widget_changes['sidebar'][0]}</b>";
						$to[] = "{$sidebar_lang} <b>{$widget_changes['sidebar'][1]}</b>";
					}

					if ( isset( $widget_changes['index'] ) ) {
						$from[] = sprintf( '%s <b>%s</b>', $index_lang, $widget_changes['index'][0] + 1 );
						$to[] = sprintf( '%s <b>%s</b>', $index_lang, $widget_changes['index'][1] + 1 );
					}
					$changes_data = array(
						'prev' => implode( ' @ ', $from ),
						'new' => implode( ' @ ', $to )
					);

					$changes_output .= $render_class::array_to_html( $changes_data );

				endforeach;

				$output .= $changes_output;
				break;

			case 'added':
			case 'deleted':
			case 'edited':
				$widget_data = array(
					'name' => $log_info['name'],
				);

				$output = $render_class::array_to_html( $widget_data );
				$output .= '<h5>' . get_nashaat_lang( 'sidebar' ) . '</h5>';

				$sidebar_data = array(
					'name' => $log_info['sidebar']['name'],
					'id' => $log_info['sidebar']['id']
				);

				$output .= $render_class::array_to_html( $sidebar_data );

				// Check changes key exist for edited event
				if ( ! isset( $log_info['changes'] ) ) {
					break;
				}

				$changes = $log_info['changes'];

				$output .= "<div class='changes-wrapper'>";
				$output .= "<h4 class='changes-title'>" . get_nashaat_lang( 'changes' ) . '</h4>';

				// Loop through each change and output html string
				foreach ( $log_info['changes'] as $change_key => $change_array ) :
					$output .= "<h5> - {$change_key}</h5>";
					$output .= "<div class='single-change-item'>";

					$prev_value = empty( $change_array[0] ) ? get_nashaat_lang( 'empty' ) : $change_array[0];
					$new_value = empty( $change_array[1] ) ? get_nashaat_lang( 'empty' ) : $change_array[1];

					$meta_data = array(
						'prev' => $prev_value,
						'new' => $new_value,
					);
					$output .= $render_class::array_to_html( $meta_data );
					$output .= '</div>';
				endforeach;
				break;
			endswitch;

		return $output;
	}
}