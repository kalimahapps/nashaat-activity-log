<?php

/**
 * Base class for table. Replaces WP_List_Table
 */
class NashaatLogsTableBase {


	/**
	 * List of items to display
	 *
	 * @var array
	 */
	protected $items = array();

	protected $total_count = 0;

	private $total_pages = 0;

	protected $per_page = 30;

	protected $table_header = array();

	protected $orderby = '';

	protected $order = 'DESC';

	protected $keywords = '';

	protected $paged = 0;

	protected $columns = array();

	/**
	 * Current filterable items
	 *
	 * @var array
	 */
	protected $filterable = array();


	/**
	 * Current sortable items
	 *
	 * @var array
	 */
	protected $sortable = array();


	/**
	 * Constructor function. Prepare sortable, filterable columns.
	 * Do initial setup
	 */
	public function __construct() {

		$this->sortable = $this->set_sortable_columns();
		$this->filterable = $this->set_filterable_columns();

		$this->prepare();

		$this->total_count = $this->set_total_count();
		$this->per_page = $this->set_per_page();
		$this->total_pages = number_format_i18n( $this->total_count / $this->per_page );
		$this->filter_global_get();

		$this->columns = $this->set_columns();
		$this->items = $this->set_items();

	}

	/**
	 * Filter $_GET variables and set defaults for private propeties if available.
	 *
	 * @return bool|void False if $_GET is not an array
	 */
	private function filter_global_get() {
		$__get = filter_input_array( INPUT_GET );
		if ( ! is_array( $__get ) ) {
			return false;
		}

		foreach ( $__get as $key => $value ) :
			switch ( $key ) :
				case 'paged':
					if ( isset( $__get['paged'] ) ) {
						$this->paged = ( $value > $this->total_pages ) ? $this->total_pages : absint( $value );
					}
					break;
				case 'orderby':
					if ( isset( $__get['orderby'] ) ) {
							$this->orderby = sanitize_sql_orderby( $value );
					}
					break;
				case 'order':
					if ( isset( $__get['order'] ) && in_array( strtolower( $value ), array( 'desc', 'asc' ) ) ) {
						$this->order = $value;
					}
					break;
				case 'keywords':
					if ( isset( $__get['keywords'] ) ) {
						$this->keywords = esc_html( $value );
					}
					break;
			endswitch;
		endforeach;
	}

	/**
	 * Set items to be displayed for current page
	 *
	 * @return array
	 */
	protected function set_items() :array {
		return array();
	}

	/**
	 * Prepare data, items and other tasks before display.
	 * Must be called by subclass
	 *
	 * @return void
	 */
	protected function prepare() {}

	/**
	 * Set pagination args like total_count and per_page
	 *
	 * @return array Pagination args
	 */
	protected function set_pagination_args() :array {
		return array(
			'total_count' => $this->set_total_count(),
			'per_page'    => $this->per_page,
		);
	}



	/**
	 * Set items total count.
	 * Must be called by subclass to set total count
	 *
	 * @return integer Total count, Default: 0
	 */
	protected function set_total_count() : int {
		return 0;
	}

	/**
	 * Set how many items to show per page
	 * Must be called by subclass to change the value
	 *
	 * @return integer Default 20
	 */
	protected function set_per_page() : int {
		return 20;
	}

	/**
	 * Set which columns to display.
	 * The array should include the column key as well as
	 * the header title
	 * array('id' => "ID")
	 *
	 * @return array
	 */
	protected function set_columns() :array {
		return array();
	}

	/**
	 * Set which column is sortable
	 *
	 * @return array Sortable columns
	 */
	protected function set_sortable_columns() : array {
		return array();
	}



	/**
	 * Set which columns are filterable
	 *
	 * @return array Filterable columns
	 */
	protected function set_filterable_columns() : array {
		return array();
	}

	/**
	 * Handle default filterable column
	 *
	 * @param string $column_name Column name
	 * @return void
	 */
	protected function filterable_default( $column_name ) {}


	/**
	 * Render table header
	 *
	 * @return string Table header with titles
	 */
	private function render_table_header() :string {
		$header_cells = '';
		foreach ( $this->columns as $column_key => $column_display_name ) {
			$classes = array( 'manage-column', "column-{$column_key}" );
			$column_display_name = sprintf( "<span class='column-name'>%s</span>", $column_display_name );

			if ( isset( $this->sortable[ $column_key ] ) ) {
				list( $orderby, $desc_first ) = $this->sortable[ $column_key ];

				if ( $this->orderby === $orderby ) {
					$order = $this->order === 'asc' ? 'desc' : 'asc';

					$classes[] = 'sorted';
					$classes[] = $this->order;
				} else {
					$order = strtolower( $desc_first );

					if ( ! in_array( $order, array( 'desc', 'asc' ), true ) ) {
						$order = $desc_first ? 'desc' : 'asc';
					}

					$classes[] = 'sortable';
					$classes[] = 'desc' === $order ? 'asc' : 'desc';
				}

				$sorting_indicator = ( $order === 'desc' ) ? 'fa-sort-amount-down' : 'fa-sort-amount-down-alt';

				$column_display_name .= sprintf(
					'<a class="sorting-icon" href="%s"><i class="fas %s"></i></a>',
					esc_url( add_query_arg( compact( 'orderby', 'order' ) ) ),
					$sorting_indicator
				);
			}

			if ( in_array( $column_key, $this->filterable ) ) {
				$column_display_name .= "<div class='filter-icon'><i class='fas fa-filter'></i></div>";
				$column_display_name .= $this->filterable_default( $column_key );
				$classes[] = 'filterable';
			}

			$header_cells .= "<th id='{$column_key}' class='" . implode( ' ', $classes ) . "'>";
			$header_cells .= "<div class='column-name-wrapper'>{$column_display_name}</div>";
			$header_cells .= '</th>';
		}

		return "<thead>{$header_cells}</thead>";

	}

	/**
	 * Extra controls to be displayed between bulk actions and pagination.
	 *
	 * @param string $position Position of nav (top, bottom)
	 */
	protected function extra_tablenav( $position ) :string {
		return '';
	}


	/**
	 * Render table rows
	 *
	 * @return string Rendered rows
	 */
	private function render_table_rows() :string {
		if ( $this->total_count === 0 ) {
			$col_count = count( $this->columns );
			return "<tr> <td colspan='{$col_count}'>" . get_nashaat_lang( 'no_items_found' ) . '</td></tr>';
		}
		return array_reduce(
			$this->items,
			array( $this, 'render_single_row' )
		);
	}

	/**
	 * Render nav about table
	 *
	 * @return string Top nav string
	 */
	private function render_top_nav() :string {

		$output = '<div id="nav-wrapper">';

		$output .= $this->extra_tablenav( 'top' );
		$output .= '<div class="table-nav top">';
		$output .= $this->render_search_box( get_nashaat_lang( 'search_logs' ), 'nashaat-search' );
		$output .= $this->render_pagination();

		// $output .= '<br class="clear"></div>';
		$output .= '</div>';
		$output .= '</div>';

		return $output;
	}

	/**
	 * Render bottom nav (below the table)
	 *
	 * @return string html string
	 */
	private function render_bottom_nav(): string {
		return $this->extra_tablenav( 'bottom' );
	}

	/**
	 * Render search box
	 *
	 * @param string $text The 'submit' button label.
	 * @param string $input_id ID attribute value for the search input field.
	 * @return string Search box string
	 */
	private function render_search_box( $text, $input_id ) :string {
		$__get = filter_input_array( INPUT_GET );

		if ( empty( $__get['keywords'] ) && $this->total_count === 0 ) {
			return '';
		}

		$input_id = $input_id . '-search-input';
		$output = '';

		$__get_array = array( 'order', 'orderby', 'post_mime_type', 'detached' );
		foreach ( $__get_array as $key ) {
			if ( isset( $__get[ $key ] ) ) {
				$output .= "<input type='hidden' name='{$key}' value='" . esc_attr( $__get[ $key ] ) . "' />";
			}
		}

		$output .= '<div class="search-box">';
		$output .= "<label class='screen-reader-text' for='" . esc_attr( $input_id ) . "'>" . $text . "':</label>";
		$output .= "<input type='search' id='" . esc_attr( $input_id ) . "' name='keywords' value='" . _admin_search_query() . "' />";
		$output .= get_submit_button( $text, '', '', false, array( 'id' => 'search-submit' ) );
		$output .= '</div>';

		return $output;
	}

	/**
	 * Render pagination view
	 *
	 * @return string Either pagination with links for multiple pages, or text for single page data
	 */
	private function render_pagination() :string {

		$current_page = $this->get_current_page();
		$removable_query_args = wp_removable_query_args();

		$current_url = remove_query_arg( $removable_query_args );

		$total_pages = $this->total_pages;
		if ( $total_pages < 1 ) {
			$output = "<div class='tablenav-pages'>";
			$output .= '<span class="displaying-num">';
			$output .= sprintf(
				/* translators: %s: Number of items. */
				_n( '%s item', '%s items', $this->total_count ),
				number_format_i18n( $this->total_count )
			);
			$output .= '</span> ';
			$output .= '</div>';

			return $output;
		}

		$pagination = '';
		$link_types = array( 'first-page', 'prev-page', 'current-page', 'next-page', 'last-page' );
		foreach ( $link_types as $link_type ) :
			$tag = 'span';
			$text = '';
			$link = '';
			switch ( $link_type ) :
				case 'first-page':
					$text = '&laquo;';
					if ( $current_page > 2 ) {
						$tag = 'a';
						$link = remove_query_arg( 'paged', $current_url );
					}
					break;

				case 'prev-page':
					$text = '&lsaquo;';
					if ( $current_page > 1 ) {
						$tag = 'a';
						$link = add_query_arg( 'paged', max( 1, $current_page - 1 ), $current_url );
					}
					break;

				case 'next-page':
					$text = '&rsaquo;';
					if ( $current_page < $total_pages ) {
						$tag = 'a';
						$link = add_query_arg( 'paged', min( $total_pages, $current_page + 1 ), $current_url );
					}
					break;
				case 'last-page':
					$text = '&raquo;';
					if ( $current_page < $total_pages - 1 ) {
						$tag = 'a';
						$link = add_query_arg( 'paged', $total_pages, $current_url );
					}
					break;
			endswitch;
			if ( $link_type === 'current-page' ) {

				$html_total_pages = sprintf( "<span class='total-pages'>%s</span>", $total_pages );
				$html_current_page = sprintf(
					"<input class='current-page' id='current-page-selector' type='text' name='paged' value='%s' size='%d'/><span class='tablenav-paging-text'>",
					$current_page,
					strlen( $total_pages )
				);
				$pagination .= '<span class="paging-input">';
				$pagination .= sprintf(
					/* translators: 1: Current page, 2: Total pages. */
					_x( '%1$s of %2$s', 'paging' ),
					$html_current_page,
					$html_total_pages
				);

				$pagination  .= ' </span></span>';
			} elseif ( $tag === 'span' ) {
				$pagination .= "<{$tag} class='tablenav-pages-navspan button disabled'>{$text}</{$tag}> ";
			} else {
				$url = esc_url( $link );
				$pagination .= "<{$tag} class='{$link_type} button' href='{$url}'>{$text}</{$tag}> ";
			}
		endforeach;

		$output = "<div class='tablenav-pages'>";
		$output .= '<span class="displaying-num">';
		$output .= sprintf(
			/* translators: %s: Number of items. */
			_n( '%s item', '%s items', $this->total_count ),
			number_format_i18n( $this->total_count )
		);
		$output .= '</span> ';
		$output .= "<span class='pagination-links'>{$pagination}</span>";
		$output .= '</div>';

		return $output;
	}

	/**
	 * Gets the current page number.
	 *
	 * @return int
	 */
	protected function get_current_page() :int {
		$page_number = absint( $this->paged );

		return max( 1, $page_number );
	}


	/**
	 * Render data for columns that don't have custom callback function.
	 * Must be called by subclass
	 *
	 * @param array  $item Row data
	 * @param string $column_name Current column name
	 * @return void
	 */
	protected function column_default( array $item, string $column_name ) {
		wp_die( 'function WP_List_Table::column_default() must be overridden in a subclass.' );
	}

	/**
	 * Loop through each column in a row and render its content.
	 * Attempt to find a method matching column_$column_name, if found
	 * return its content, otherwise call column_default
	 *
	 * @param string $row Output collector string
	 * @param array  $item Current row data
	 * @return string Row content
	 */
	private function render_single_row( $row, array $item ) :string {
		$selected_columns = array_keys( $this->columns );

		$output = '';
		foreach ( $selected_columns as $column_name ) {
			$value = $item[ $column_name ] ?? '';
			try {

				// Call column callback function if provided
				if ( method_exists( $this, 'column_' . $column_name ) ) {
					$cell = call_user_func( array( $this, 'column_' . $column_name ), $value, $item );
				} else {
					$cell = $this->column_default( $item, $column_name );
				}
				$output .= "<td>{$cell}</td>";
			} catch ( Throwable $th ) {
				$output .= '<td>';
				$output .= '<div class="error-message">' . get_nashaat_lang( 'exception_error' ) . '</div>';
				$output .= $th->getMessage();
				$output .= '</td>';
			}
		}

		$row .= "<tr>{$output}</tr>";
		return $row;
	}

	/**
	 * Render table's html
	 *
	 * @return void
	 */
	public function render_table() {

		$mode = get_user_setting( 'posts_list_mode', 'list' );

		$mode_class = esc_attr( 'table-view-' . $mode );

		$screen = convert_to_screen( null );

		$classes = array( 'widefat', 'fixed', 'striped', $mode_class, $screen->base );

		$output = $this->render_top_nav();
		$output .= "<table class='wp-list-table " . implode( ' ', $classes ) . "'>";
		$output .= $this->render_table_header();
		$output .= $this->render_table_rows();
		$output .= '</table>';
		$output .= $this->render_bottom_nav();

		nashaat_kses_post( $output );
	}
}