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
	 * @param array $input_array Array to convert
	 * @return string Html string
	 */
	public static function array_to_html( array $input_array ) {
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

		return $html;
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
		// if array is sapplied
		if ( is_array( $title ) ) {
			if ( empty( $title['title'] ) ) {
				return get_nashaat_lang( $default );
			}
			$title = $title['title'];
		}

		// if string is spplied
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
					$taxonomy = ( $change_key === 'categories' ) ? 'category' : 'post_tag';
					list($prev_terms, $new_terms) = $change_array;

					// Loop through prev categories or tags and build an array
					// of categories names with links if possible, otherwise just names
					$prev_terms_links = array();
					foreach ( $prev_terms as $cat_data ) {
						$cat_link = get_edit_term_link( $cat_data['term_id'], $taxonomy );
						if ( empty( $cat_link ) ) {
							$prev_terms_links[] = $cat_data['name'];
						} else {
							$prev_terms_links[] = sprintf( "<a target='_blank' href='%s'>%s</a>", $cat_link, $cat_data['name'] );
						}
					}

					// Loop through new categories or tags and build an array
					// of categories names with links if possible, otherwise just names

					$new_terms_links = array();
					foreach ( $new_terms as $cat_data ) {
						$cat_link = get_edit_term_link( $cat_data['term_id'], $taxonomy );
						if ( empty( $cat_link ) ) {
							$new_terms_links[] = $cat_data['name'];
						} else {
							$new_terms_links[] = sprintf( "<a target='_blank' href='%s'>%s</a>", $cat_link, $cat_data['name'] );
						}
					}

					// Make sure a value is displayed when array is empty
					if ( count( $prev_terms_links ) === 0 ) {
						$prev_terms_links[] = get_nashaat_lang( 'not_available' );
					}

					if ( count( $new_terms_links ) === 0 ) {
						$new_terms_links[] = get_nashaat_lang( 'not_available' );
					}

					$term_data = array(
						'prev' => implode( ', ', $prev_terms_links ),
						'new' => implode( ', ', $new_terms_links )
					);

					$output .= self::array_to_html( $term_data );

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
}