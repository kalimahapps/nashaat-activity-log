<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Helper class to render various aspect of log info data into html string
 */
class NashaatRenderLogInfo {

	/**
	 * Helper function to Convert array to html string
	 *
	 * @param array  $input_array Array to convert
	 * @param string $heading Optional heading
	 * @return string Html string
	 */
	public static function array_to_html( array $input_array, string $heading = '' ) {
		$html = '';
		foreach ( $input_array as $key => $value ) {
			if ( empty( $input_array[ $key ] ) && $input_array[ $key ] != 0 ) {
				continue;
			}
			$translation = get_nashaat_lang( $key );
			$html .= "<div class='extra-data-row'>";
			$html .= "<span>{$translation}:</span> <span>{$value}</span>";
			$html .= '</div>';
		}

		if ( empty( $heading ) ) {
			return $html;
		}

		// Wrap with heading
		$output = "<div class='extra-data-wrapper'>";
		$output .= "<h5 class='extra-data-title'>{$heading}</h5>";
		$output .= $html;
		$output .= '</div>';
		return $output;
	}

	/**
	 * Helper function to Convert array to html table string
	 *
	 * @param array  $input_array Array to convert
	 * @param string $heading Optional heading
	 * @return string Html string
	 */
	public static function array_to_table( array $input_array, string $heading = '' ) {
		$html = '';

		// Get first element of the array to get header
		$first_element = reset( $input_array );

		// Get keys of first element
		$keys = array_keys( $first_element );

		// Start table
		$html .= "<table class='inner-log-table'>";
		// Table header
		$html .= '<thead>';
		$html .= '<tr>';
		$html .= '<th></th>';
		foreach ( $keys as $key ) {
			$translation = get_nashaat_lang( $key );
			$html .= "<th>{$translation}</th>";
		}
		$html .= '</tr>';
		$html .= '</thead>';

		// Table body
		$html .= '<tbody>';
		foreach ( $input_array as $row_key => $row ) {
			$html .= '<tr>';
			$html .= "<td><b>$row_key</b></td>";
			foreach ( $row as $key => $value ) {
				$html .= "<td>{$value}</td>";
			}
			$html .= '</tr>';
		}
		$html .= '</tbody>';

		// End table
		$html .= '</table>';

		if ( empty( $heading ) ) {
			return $html;
		}

		// Wrap with heading
		$output = "<div class='extra-data-wrapper'>";
		$output .= "<h5 class='extra-data-title'>{$heading}</h5>";
		$output .= $html;
		$output .= '</div>';
		return $output;

	}

	/**
	 * Get post title. If not found return default text of no title
	 *
	 * @param string|array $title Log data
	 * @param int          $post_id Post id. If supplied attempt to get title with link
	 * @param string       $default Default language string
	 * @return string Title or default language
	 */
	public static function maybe_get_post_title( $title, int $post_id = 0, string $default = 'no_title' ) :string {
		// if array is supplied
		if ( is_array( $title ) ) {
			if ( empty( $title['title'] ) ) {
				return get_nashaat_lang( $default );
			}
			$title = $title['title'];
		}

		// if string is supplied
		if ( empty( $title ) ) {
			return get_nashaat_lang( $default );
		}

		// if post id is not supplied return title
		if ( empty( $post_id ) ) {
			return $title;
		}

		$post_link = get_edit_post_link( $post_id );
		if ( ! empty( $post_link ) ) {
			return sprintf( "<a target='_blank' href='%s'>%s</a>", $post_link, $title );
		}
	}

	/**
	 * Render post link by using it id. Return id if link is not available
	 *
	 * @param integer $post_id Post id
	 * @param string  $post_type Post type. Default to post
	 * @return string html string if link found, post_id otherwise
	 */
	public static function maybe_get_post_edit_link( int $post_id, string $post_type = 'post' ) : string {
		if ( $post_type === 'comment' ) {
			$post_link = get_edit_comment_link( $post_id );
		} else {
			$post_link = get_edit_post_link( $post_id );
		}

		if ( ! empty( $post_link ) ) {
			$post_id = sprintf( "<a target='_blank' href='%s'>%d</a>", $post_link, $post_id );
		}
		return $post_id;
	}

	/**
	 * Check if user exists and return link to profile page.
	 *
	 * @param integer $user_id User id
	 * @param string  $user_name User name
	 * @return string Link to user profile in backend if user exists, user_name otherwise
	 */
	public static function maybe_get_user_edit_link( int $user_id, string $user_name ) {
		$user_link = get_edit_user_link( $user_id );
		$user_exists = get_user_by( 'id', $user_id );
		$return = $user_name;

		if ( ! empty( $user_exists ) ) {
			$return = sprintf( "<a target='_blank' href='%s'>%s</a>", $user_link, $user_name );
		}

		return $return;
	}

	/**
	 * Get links and title of posts
	 *
	 * @param array $posts_list Array of posts
	 * @return string Array with links and titles or not_available string
	 */
	public static function get_posts_links( array $posts_list ) :string {

		// if not product available then display a message
		if ( count( $posts_list ) === 0 ) {
			return get_nashaat_lang( 'not_available' );
		}

		// Loop through products and get links if available, or just the title
		$posts_links = array();

		foreach ( $posts_list as $post ) {
			$post_link = get_edit_post_link( $post['id'] );
			if ( ! empty( $post_link ) ) {
				$posts_links[] = sprintf( "<a target='_blank' href='%s'>%s</a>", $post_link, $post['title'] );
			} else {
				$posts_links[] = $post['title'];
			}
		}

		return implode( ', ', $posts_links );
	}


	/**
	 * Get terms links
	 *
	 * @param string $taxonomy Taxonomy that terms are related to
	 * @param array  $change_array Array of prev and new terms ids
	 * @return array Modified $change_array with titles and links
	 */
	public static function get_terms_links( string $taxonomy, array $change_array ) :array {
		$results = array_map(
			function( $terms ) use ( $taxonomy ) {
				// Loop through terms and build an array of terms names with links if possible, otherwise just names
				$terms_links = array();
				foreach ( $terms as $cat_data ) {
					$cat_link = get_edit_term_link( $cat_data['term_id'], $taxonomy );
					if ( empty( $cat_link ) ) {
						$terms_links[] = $cat_data['name'];
					} else {
						$terms_links[] = sprintf( "<a target='_blank' href='%s'>%s</a>", $cat_link, $cat_data['name'] );
					}
				}

				if ( count( $terms_links ) === 0 ) {
					$terms_links[] = get_nashaat_lang( 'not_available' );
				}

				return implode( ', ', $terms_links );
			},
			$change_array
		);

		return $results;
	}

	/**
	 * Render edit changes into html (title, description, slug .. etc)
	 * Show previous and new values
	 *
	 * @param array $item Log single row data
	 * @return string Html string (empty if changes array is empty)
	 */
	public static function render_post_edit_changes( array $item ) : string {

		$log_info = maybe_unserialize( $item['log_info'] );

		if ( ! isset( $log_info['changes'] ) ) {
			return '';
		}

		// Get changes html output
		$output = "<div class='changes-wrapper'>";
		$output .= "<h4 class='changes-title'>" . get_nashaat_lang( 'changes' ) . '</h4>';

		// Loop through each change and output html string
		foreach ( $log_info['changes'] as $change_key => $change_array ) :
			$output .= "<div class='single-change-item'>";
			$output .= '<h5> - ' . get_nashaat_lang( $change_key ) . '</h5>';
			switch ( $change_key ) :
				case 'content':
				case 'excerpt':
				case 'caption':
				case 'description':
					$content_data = array(
						'prev_count' => $change_array[0],
						'new_count' => $change_array[1],
					);
					$output .= self::array_to_html( $content_data );
					break;

				case 'title':
					$content_data = array(
						'prev' => self::maybe_get_post_title( $change_array[0] ),
						'new' => self::maybe_get_post_title( $change_array[1] ),
					);
					$output .= self::array_to_html( $content_data );
					break;

				case 'author':
					list('name' => $prev_name, 'id' => $prev_id) = $change_array[0];
					list('name' => $new_name, 'id' => $new_id) = $change_array[1];

					$content_data = array(
						'prev' => self::maybe_get_user_edit_link( $prev_id, $prev_name ),
						'new' => self::maybe_get_user_edit_link( $new_id, $new_name ),
					);
					$output .= self::array_to_html( $content_data );
					break;

				case 'featured_media':
					// Destruct media keys
					list('title' => $prev_media_title, 'id' => $prev_media_id) = $change_array[0];
					list('title' => $new_media_title, 'id' => $new_media_id) = $change_array[1];

					if ( empty( $prev_media_id ) ) {
						$prev_media_id = 0;
					}
					$media_data = array(
						'prev' => self::maybe_get_post_title( $prev_media_title, $prev_media_id, 'not_available' ),
						'new' => self::maybe_get_post_title( $new_media_title, $new_media_id, 'not_available' )
					);

					$output .= self::array_to_html( $media_data );
					break;

				case 'tags':
				case 'categories':
					$taxonomy = ( $change_key === 'tags' ) ? 'post_tag' : 'category';
					$terms_links = self::get_terms_links( $taxonomy, $change_array );

					$terms_data = array(
						'prev' => $terms_links[0],
						'new' => $terms_links[1]
					);

					$output .= self::array_to_html( $terms_data );
					break;

				case 'parent':
					list($prev_parent, $new_parent) = $change_array;

					$parent_data = array(
						'prev' => self::maybe_get_post_title( $prev_parent['post_title'], $new_parent['ID'], 'not_available' ),
						'new' => self::maybe_get_post_title( $new_parent['post_title'], $new_parent['ID'], 'not_available' )
					);

					$output .= self::array_to_html( $parent_data );
					break;
				case 'status':
				case 'menu_order':
				case 'slug':
				case 'comment_status':
				case 'ping_status':
					$content_data = array(
						'prev' => $change_array[0],
						'new' => $change_array[1],
					);
					$output .= self::array_to_html( $content_data );
					break;

				case 'sticky':
					$prev = $change_array[0];
					$new = $change_array[1];

					$sticky_data = array(
						'prev' => empty( $prev ) ? get_nashaat_lang( 'disabled' ) : get_nashaat_lang( 'enabled' ),
						'new' => empty( $new ) ? get_nashaat_lang( 'disabled' ) : get_nashaat_lang( 'enabled' )
					);
					$output .= self::array_to_html( $sticky_data );
					break;
			endswitch;
			$output .= '</div>';
		endforeach;

		$output .= '</br>';
		return $output;
	}

	/**
	 * Convert boolean to toggle value (off/on)
	 *
	 * @param boolean|null $bool_value Boolean value. Can be null.
	 * @return string on if $bool_value is 1, off otherwise.
	 */
	public static function boolean_to_toggle( $bool_value ) :string {
		if ( is_null( $bool_value ) ) {
			return 'off';
		}

		return $bool_value ? 'on' : 'off';
	}
}