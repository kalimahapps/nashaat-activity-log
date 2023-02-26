<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * This class will display log in a table.
 */
class NashaatLogTable extends NashaatLogsTableBase {
	protected $per_page = 30;

	private $date_filter_options = array();

	private $table = NASHAAT_DB_TABLE;

	private $where = '';

	/**
	 * Prepare certain actions before displaying items
	 *
	 * @return void
	 */
	protected function prepare() {
		add_action( 'wp_ajax_delete_single_record', array( $this, 'delete_single_record' ) );

		$this->date_filter_options = array(
			'today' => get_nashaat_lang( 'today' ),
			'yesterday' => get_nashaat_lang( 'yesterday' ),
			'last_7_days' => get_nashaat_lang( 'last_7_days' ),
			'last_14_days' => get_nashaat_lang( 'last_14_days' ),
			'last_30_days' => get_nashaat_lang( 'last_30_days' ),
			'last_90_days' => get_nashaat_lang( 'last_90_days' )
		);

		$filter_query = $this->get_filter_query();
		$this->where = $filter_query['where'];
		$this->current_filters = $filter_query['filters'];
	}

	/**
	 * Ajax function to delete all log data
	 *
	 * @return void
	 */
	public function delete_single_record() {
		$__post = filter_input_array( INPUT_POST );

		 // Check admin screen and current user privileges
		if ( ! is_admin() || ! current_user_can( 'manage_options' ) || ! isset( $__post['nonce'] ) ) {
			wp_send_json( get_nashaat_lang( 'unable_to_delete' ), 400 );
		}

		$nonce = $__post['nonce'];
		if ( ! wp_verify_nonce( $nonce, 'nashaat_nonce' ) ) {
			wp_send_json( get_nashaat_lang( 'unable_to_delete' ), 400 );
		}

		global $wpdb;

		$id = isset( $__post['id'] ) ? sanitize_text_field( $__post['id'] ) : '';
		if ( empty( $id ) ) {
			wp_send_json( get_nashaat_lang( 'invalid_id' ), 200 );
		}

		$is_deleted = $wpdb->delete( $this->table, array( 'id' => $id ) );
		if ( $is_deleted ) {
			wp_send_json_success();
		}

		wp_send_json( get_nashaat_lang( 'unable_to_delete' ), 200 );
	}

	/**
	 * Set the current items for display
	 *
	 * @return array Items to display
	 */
	protected function set_items():array {
		global $wpdb;

		$offset = ( $this->get_current_page() - 1 ) * $this->per_page;

		$orderby = $this->orderby;
		if ( empty( $orderby ) ) {
			$orderby = 'date';
		}

		$data = $wpdb->get_results(
			"SELECT * FROM {$this->table} {$this->where}
			ORDER BY {$orderby} {$this->order}
			LIMIT $offset, {$this->per_page};",
			ARRAY_A
		);

		return $data;
	}

	/**
	 * Set per page count
	 *
	 * @return integer Number of items per page
	 */
	protected function set_per_page() :int {
		$per_page = (int) get_user_option( 'nashaat_logs_per_page' );

		if ( empty( $per_page ) || $per_page < 1 ) {
			return $this->per_page;
		}
		return $per_page;
	}

	/**
	 * Get column keys and titles
	 *
	 * @return array Array of columns
	 */
	protected function set_columns() :array {
		$columns = array(
			'date' => get_nashaat_lang( 'date' ),
			'level' => get_nashaat_lang( 'level' ),
			'user_data' => get_nashaat_lang( 'user' ),
			'ip' => get_nashaat_lang( 'ip' ),
			'context' => get_nashaat_lang( 'context' ),
			'event' => get_nashaat_lang( 'event' ),
			'log_info' => get_nashaat_lang( 'data' ),
			'actions' => get_nashaat_lang( 'actions' ),
		);
		return $columns;
	}

	/**
	 * Set the total count of current queried items
	 *
	 * @return integer Items count
	 */
	protected function set_total_count() :int {
		global $wpdb;

		$total_items = $wpdb->get_var(
			"SELECT COUNT(`id`) FROM {$this->table} {$this->where}"
		);
		return $total_items;
	}


	/**
	 * Set filterable columns keys
	 *
	 * @return array Filterable columns
	 */
	protected function set_filterable_columns() :array {
		return array( 'date', 'level', 'user_data', 'ip', 'context', 'event' );
	}

	/**
	 * Helper function to build WHERE query based on $_GET parameters
	 * and return an array of filters applied to display to user
	 *
	 * @return string Formed where query
	 */
	public function get_filter_query() {
		global $wpdb;
		$__get = filter_input_array( INPUT_GET );

		$where = ' WHERE 1=1';
		$filters = array();

		if ( ! empty( $__get['keywords'] ) ) :
			$search_keywords = '%' . $wpdb->esc_like( $__get['keywords'] ) . '%';
			$where .= $wpdb->prepare(
				' AND `context` LIKE %s OR `event` LIKE %s',
				$search_keywords,
				$search_keywords
			);
			endif;

		// Add order and orderby to filters array
		if ( isset( $__get['order'] ) && isset( $__get['orderby'] ) ) {
			$filters['order_filter'] = sprintf(
				'%s - %s',
				get_nashaat_lang( $__get['orderby'] ),
				get_nashaat_lang( $__get['order'] )
			);
		}

		// Loop thorough each filterable column to see if filter has been applied
		foreach ( $this->filterable as $filter_key ) :

			// user_data is special case, covert to user

			if ( $filter_key === 'user_data' ) {
				$filter_key = 'user_id';
			}
			// If filter is not applied move to the next one
			if ( ! isset( $__get[ $filter_key . '_filter' ] ) ) {
				continue;
			}

			$__get_filter = $__get[ $filter_key . '_filter' ];

			switch ( $filter_key ) :
				// Handle date filter
				case 'date':
					$datetime = current_datetime();
					$current_time = $datetime->getTimestamp();

					// Build array of date type (starttime => endtime)
					$date_types = array(
						'today' => array(
							strtotime( 'today midnight' ),
							$current_time
						),
						'yesterday' => array(
							$datetime->modify( '-1 day midnight' )->getTimestamp(),
							$datetime->modify( 'today midnight' )->getTimestamp(),
						),
						'last_7_days' => array(
							$datetime->modify( '-6 days midnight' )->getTimestamp(),
							$current_time
						),
						'last_14_days' => array(
							$datetime->modify( '-13 days midnight' )->getTimestamp(),
							$current_time
						),
						'last_30_days' => array(
							$datetime->modify( '-29 days midnight' )->getTimestamp(),
							$current_time
						),
						'last_90_days' => array(
							$datetime->modify( '-89 days midnight' )->getTimestamp(),
							$current_time
						),
					);

					// skip if a date from the list is not specified
					if ( ! in_array( $__get_filter, array_keys( $date_types ) ) ) {
						break;
					}

					$current_date_filter = $date_types[ $__get_filter ];
					list($start_time, $end_time) = $current_date_filter;

					$where .= $wpdb->prepare(
						' AND date > %d AND date < %d',
						$start_time,
						$end_time
					);

					$filters[ $filter_key ] = $this->date_filter_options[ $__get_filter ];
					break;

				// Group the rest of filters since the difference between them is minimal
				case 'context':
				case 'event':
				case 'user_id':
				case 'level':
				case 'ip':
					$where .= $wpdb->prepare(
						" AND {$filter_key} = %s",
						$__get_filter
					);

					if ( $filter_key === 'level' ) {
						$filters[ $filter_key ] = $this->get_level_text( $__get_filter );
					} else if ( in_array( $filter_key, array( 'ip', 'user_id' ) ) ) {
						$filters[ $filter_key ] = $__get_filter;
					} else {
						$filters[ $filter_key ] = get_nashaat_lang( $__get_filter );
					}
					break;
			endswitch;
		endforeach;

		return array(
			'where' => $where,
			'filters' => $filters
		);
	}


	/**
	 * Create sortable columns
	 *
	 * @return array Sortable columns array with properties
	 */
	public function set_sortable_columns() :array {
		return array(
			'date' => array( 'date', false ),
			'level' => array( 'level', false ),
			'user_data' => array( 'user_id', false ),
			'ip' => array( 'ip', false ),
			'context' => array( 'context', false ),
			'event' => array( 'event', false )
		);
	}

	/**
	 * Helper method to convert time into timeago
	 *
	 * @param string  $timestamp Timestamp
	 * @param boolean $full Whether to display full ago time (1 month, 3 week, .. etc) or just the last time
	 * @return string Elapsed time
	 */
	private function time_elapsed( string $timestamp, $full = false ) {
		$now = new DateTime();
		$ago = new DateTime();
		$ago->setTimestamp( $timestamp );
		$diff = $now->diff( $ago );

		$diff->w = floor( $diff->d / 7 );
		$diff->d -= $diff->w * 7;

		$string = array(
			'y' => 'year',
			'm' => 'month',
			'w' => 'week',
			'd' => 'day',
			'h' => 'hour',
			'i' => 'minute',
			's' => 'second',
		);
		foreach ( $string as $k => &$v ) {
			if ( $diff->$k ) {
				$v = $diff->$k . ' ' . $v . ( $diff->$k > 1 ? 's' : '' );
			} else {
				unset( $string[ $k ] );
			}
		}

		if ( ! $full ) {
			$string = array_slice( $string, 0, 1 );
		}
		return $string ? implode( ', ', $string ) . ' ago' : 'just now';
	}


	/**
	 * Helper function to get level translation
	 *
	 * @param integer $level Level number
	 * @return string Corresponding translation
	 */
	private function get_level_text( int $level ) {
		$level_text = false;
		switch ( $level ) :
			case NASHAAT_LOG_LEVEL_NORMAL:
				$level_text = get_nashaat_lang( 'information' );
				break;
			case NASHAAT_LOG_LEVEL_LOW:
				$level_text = get_nashaat_lang( 'low' );
				break;
			case NASHAAT_LOG_LEVEL_MEDIUM:
				$level_text = get_nashaat_lang( 'medium' );
				break;
			case NASHAAT_LOG_LEVEL_HIGH:
				$level_text = get_nashaat_lang( 'hight' );
				break;
			endswitch;

		if ( $level_text === false ) {
			return false;
		}
		return $level_text;
	}

	/**
	 * Show level icon with information
	 *
	 * @param int $level Current level data
	 * @return string Html string with level icon and data, or empty string if level is not found
	 */
	protected function column_level( $level ) {
		$icon = false;
		$level_text = false;
		switch ( $level ) {
			case NASHAAT_LOG_LEVEL_NORMAL:
				$icon = '<i class="fas fa-info-circle"></i>';
				break;
			case NASHAAT_LOG_LEVEL_LOW:
				$icon = '<i class="fas fa-exclamation"></i>';
				break;
			case NASHAAT_LOG_LEVEL_MEDIUM:
				$icon = '<i class="fas fa-exclamation-circle"></i>';
				break;
			case NASHAAT_LOG_LEVEL_HIGH:
				$icon = '<i class="fas fa-exclamation-triangle"></i>';
				break;
		}

		if ( $icon === false ) {
			return '';
		}
		$level_text = $this->get_level_text( $level );
		$level_data = "<div class='level-wrapper' data-level='level-{$level}' title='{$level_text}'>";
		$level_data .= $icon;
		$level_data .= '</div>';

		return $level_data;
	}

	/**
	 * Render actions column
	 *
	 * Actions include, deleting and bookmarking
	 *
	 * @param array $log_info Log information
	 * @param array $item Current item data
	 * @return string Html string with
	 */
	protected function column_actions( $log_info, array $item ) {
		$spinner = "<i class='action-spinner fas fa-sync fa-spin'></i>";
		$actions_html = '<div class="actions-wrapper">';
		$actions_html .= '<div class="single-action-wrapper">';
		$actions_html .= "<span href='#' class='delete-single-record' data-id='{$item['id']}' title='" . get_nashaat_lang( 'delete' ) . "'><i class='fas fa-trash'></i></span>";
		$actions_html .= "$spinner </div>";
		$actions_html .= '</div>';

		return $actions_html;
	}

	/**
	 * Handle log_info column display for different context and events
	 *
	 * @param array $log_info Column log_info data
	 * @param array $item Item data
	 * @return string Html string
	 */
	protected function column_log_info( $log_info, array $item ) : string {
		$context = $item['context'];
		$log_info = maybe_unserialize( $log_info );
		$event = $item['event'];

		$html = apply_filters( "render_log_info_{$context}", $log_info, $event, $item, NashaatRenderLogInfo::class );
		if ( $html === false ) {
			return '';
		}

		return "<div class='log-info-wrapper'>{$html}</div>";
	}

	/**
	 * Handle user column display
	 *
	 * @param array $user_data Serialized value for user_data column
	 * @return string Html string with user data
	 */
	protected function column_user_data( $user_data ) {
		$user_data = maybe_unserialize( $user_data );
		list( 'id' => $user_id, 'name' => $name, 'roles' => $roles ) = $user_data;
		$roles = str_replace( '|', ', ', $roles );

		$user_profile_url = admin_url( 'user-edit.php?user_id=' . $user_id );
		$name = "<a href='$user_profile_url'>$name</a>";
		if ( (int) $user_id === 0 ) {
			$name = 'Guest';
		}
		$avatar = get_avatar( $user_id, 20 );

		$user_html = '<div class="user-data">';
		$user_html .= "<div>{$avatar}</div>";
		$user_html .= "<div>{$name}</div>";
		$user_html .= "<div>{$roles}</div>";
		$user_html .= '</div>';
		return $user_html;
	}

	/**
	 * Handle data column display
	 *
	 * @param string $timestamp Current timestamp
	 * @return string Html string with date information
	 */
	protected function column_date( $timestamp ) {

		$time_elapsed = $this->time_elapsed( $timestamp );
		$formatted_date = wp_date( get_option( 'date_format' ), $timestamp );
		$formatted_time = wp_date( get_option( 'time_format' ), $timestamp );

		$date = "<div class='time_elapsed'>{$time_elapsed}</div>";
		$date .= "<div class='date_format'>{$formatted_date}</div>";
		$date .= "<div class='time_format'>{$formatted_time}</div>";
		return $date;
	}

	/**
	 * Set column default for each item
	 *
	 * @param array  $item Item data
	 * @param string $column_name Current column name
	 * @return string Html string
	 */
	protected function column_default( $item, $column_name ) {

		switch ( $column_name ) {
			/**
			 * Special case for event and context columns as they represent single word.
			 * Use this value to get translation string
			 */
			case 'event':
			case 'context':
				$value = $item[ $column_name ];
				return get_nashaat_lang( $value );
				break;
			default:
				return $item[ $column_name ];
			break;
		}
	}


	/**
	 * Display current filtered column in tags on top of table.
	 * Display export and delete filtered data button at the bottom
	 *
	 * @param string $which Place of html (top, bottom)
	 * @return string Void if not top of the table or no filters, html string otherwise
	 */
	protected function extra_tablenav( $which ) :string {

		if ( $which === 'bottom' && $this->total_count > 0 ) {
			$__get = filter_input_array( INPUT_GET );
			$button_text = get_nashaat_lang( 'export_csv' );
			// Check if any filter is set to show a different button text
			foreach ( $this->filterable as $filter_key ) :

				// If filter is  applied set different button text lang and exit
				if ( isset( $__get[ $filter_key . '_filter' ] ) ) {
					$button_text = get_nashaat_lang( 'export_filtered_csv' );
					break;
				}
			endforeach;

			$export_url = add_query_arg(
				array(
					'export-data' => 'csv',
					'export-nonce' => wp_create_nonce( 'export_csv_nonce' )
				)
			);
			$output = "<div id='log-table-action'>";
			$output .= sprintf(
				'<a class="button button-primary export" href="%s">%s</a>',
				$export_url,
				$button_text
			);

			$output .= '</div>';
			return $output;
		}

		if ( $which !== 'top' ) {
			return '';
		}

		$output = "<div id='current_filters'>";

		if ( count( $this->current_filters ) > 0 ) {

			$output .= "<div class='filter-title'>" . get_nashaat_lang( 'filters_applied' ) . '</div>';
			$output .= "<div class='filter-tags'>";

			$current_url = remove_query_arg( 'paged' );

			foreach ( $this->current_filters as $key => $value ) {
				$key_translation = get_nashaat_lang( $key );
				$link = esc_url( remove_query_arg( $key . '_filter', $current_url ) );

				if ( $key === 'order_filter' ) {
					$link = esc_url( remove_query_arg( array( 'order', 'orderby', $current_url ) ) );
				}
				$output .= "<span class='filter-span filter-{$key}'>{$key_translation}: {$value}";
				$output .= sprintf(
					'<a class="remove-filter" href="%s">×</a>',
					$link
				);
				$output .= '</span>';
			}
			$output .= '</div>';
		}
		$output .= '</div>';

		return $output;

	}
	/**
	 * Add filter box for each filterable column
	 *
	 * @param string $column_name Column name
	 * @return bool|string False if column is not filterable, html string otherwise
	 */
	protected function filterable_default( $column_name ) {
		global $wpdb;

		$filter_html = false;
		switch ( $column_name ) :
			case 'date':
				$filter_html .= $this->render_filter_box( $this->date_filter_options, 'date_filter' );
				break;
			case 'level':
			case 'ip':
			case 'user_data':
			case 'context':
			case 'event':
				$data_array = $wpdb->get_results(
					"SELECT {$column_name} as item, count(id) as count FROM {$this->table}
					GROUP BY {$column_name}
					ORDER BY count
					DESC",
					ARRAY_A
				);

				if ( count( $data_array ) === 0 ) {
					break;
				}

				$data_array_with_lang = array();
				foreach ( $data_array as $key => $single_array_data ) {

					switch ( $column_name ) :
						case 'level':
							$text = $this->get_level_text( $single_array_data['item'] );
							break;
						case 'user_data':
							$unserialize_text = maybe_unserialize( $single_array_data['item'] );

							// Get user id only for key
							$single_array_data['item'] = $unserialize_text['id'];
							$text = ( $unserialize_text['id'] !== 0 ) ? $unserialize_text['name'] : get_nashaat_lang( 'guest' );
							break;
						case 'ip':
							$text = $single_array_data['item'];
							break;
						default:
							$text = get_nashaat_lang( $single_array_data['item'] );
							break;
					endswitch;

					$data_array_with_lang[] = array(
						'key' => $single_array_data['item'],
						'text' => $text . " ({$single_array_data['count']})"
					);
				}

				$filter_key = ( $column_name === 'user_data' ) ? 'user_id' : $column_name;
				$filter_html .= $this->render_filter_box( $data_array_with_lang, $filter_key . '_filter' );

				break;

		endswitch;

		if ( $filter_html === false ) {
			return '';
		}

		$output = "<div class='filter-box-wrapper filter-{$column_name}'>";
		$output .= "<div class='filter-box-inner'>";
		$output .= "{$filter_html}";
		$output .= '</div>';
		$output .= "<div class='filter-box-apply'><button type='submit' class='button'>Apply</button></div>";
		$output .= '</div>';
		return $output;
	}

	/**
	 * Helper function to render html for filter options
	 *
	 * @param array  $filter_data Filter data
	 * @param string $filter_key Filter key to be used for $_GET and input name
	 * @return string html string otherwise
	 */
	private function render_filter_box( array $filter_data, $filter_key ) {
		$__get = filter_input_array( INPUT_GET );
		$filter_default = ( isset( $__get[ $filter_key ] ) ) ? $__get[ $filter_key ] : 'all';

		$filter_html = '';
		foreach ( $filter_data as $key => $value ) {

			$text = $value;

			if ( is_array( $value ) ) {
				$text = $value['text'];
				$key = $value['key'];
			}

			$checked = strcmp( $key, $filter_default ) === 0 ? 'checked="checked"' : '';

			$filter_html .= '<div><label>';
			$filter_html .= "<input type='radio' value='{$key}' name='{$filter_key}' $checked>";
			$filter_html .= "<span></span>{$text}";
			$filter_html .= '</label></div>';
		}
		return $filter_html;
	}

}