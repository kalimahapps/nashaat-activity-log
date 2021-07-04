<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle logging of WooCommerce coupons
 */
class NashaatWCCoupon extends NashaatHookBase {

	protected $actions = array(
		array(
			'name' => 'save_post_shop_coupon',
			'args' => 3,
			'callback' => 'log_coupon_data'
		)
	);

	protected $filters = array(
		array(
			'name' => 'wp_insert_post_data',
			'args' => 3,
			'callback' => 'get_coupon_prev_data'
		),
		array(
			'name' => 'nashaat_translations',
			'callback' => 'add_wc_coupon_translations'
		)
	);

	protected $context = 'wc_coupons';
	protected $level = NASHAAT_LOG_LEVEL_MEDIUM;

	/**
	 * Holds old product data to compare with new data
	 * after save
	 *
	 * @var array
	 */
	private $prev_coupon_data = array();

	/**
	 * Coupon log translations
	 *
	 * @param array $translations Current plugin translations
	 * @return array modified $translations array
	 */
	public function add_wc_coupon_translations( array $translations ) {
		$wc_coupon_translations = array(
			'wc_coupons' => __( 'WC Coupons', 'nashaat' ),
			'code' => __( 'Code', 'nashaat' ),
			'amount' => __( 'Amount', 'nashaat' ),
			'date_expires' => __( 'Expiry date', 'nashaat' ),
			'discount_type' => __( 'Discount type', 'nashaat' ),
			'individual_use' => __( 'Individual use only', 'nashaat' ),
			'products' => __( 'Products', 'nashaat' ),
			'excluded_products' => __( 'Excluded products', 'nashaat' ),
			'usage_limit' => __( 'Usage limit per coupon', 'nashaat' ),
			'usage_limit_per_user' => __( 'Usage limit per user', 'nashaat' ),
			'limit_usage_to_x_items' => __( 'Limit usage to X items', 'nashaat' ),
			'product_categories' => __( 'Categories', 'nashaat' ),
			'free_shipping' => __( 'Free shipping', 'nashaat' ),
			'excluded_product_categories' => __( 'Exclude categories', 'nashaat' ),
			'exclude_sale_items' => __( 'Exclude sale items', 'nashaat' ),
			'minimum_amount' => __( 'Minimum amount', 'nashaat' ),
			'maximum_amount' => __( 'Maximum amount', 'nashaat' ),
			'email_restrictions' => __( 'Email restrictions', 'nashaat' ),

		);
		return array_merge( $translations, $wc_coupon_translations );
	}

	/**
	 * Get a list of coupon data to compare data and find differences
	 * to log into database
	 *
	 * @param int $coupon_id Coupon ID
	 * @return array Array of coupon data
	 */
	private function get_coupon_data( int $coupon_id ) {
		if ( empty( $coupon_id ) ) {
			return array();
		}

		$coupon = new WC_Coupon( $coupon_id );

		$coupon_data = array(
			'code' => $coupon->get_code(),
			'amount' => $coupon->get_amount(),
			'date_expires' => '',
			'discount_type' => $coupon->get_discount_type(),
			'description' => strlen( $coupon->get_description() ),
			'individual_use' => $coupon->get_individual_use(),
			'product_ids' => implode( ',', $coupon->get_product_ids() ),
			'excluded_product_ids' => implode( ',', $coupon->get_excluded_product_ids() ),
			'usage_limit' => $coupon->get_usage_limit(),
			'usage_limit_per_user' => $coupon->get_usage_limit_per_user(),
			'limit_usage_to_x_items' => $coupon->get_limit_usage_to_x_items(),
			'free_shipping' => $coupon->get_free_shipping(),
			'product_categories' => implode( ',', $coupon->get_product_categories() ),
			'excluded_product_categories' => implode( ',', $coupon->get_excluded_product_categories() ),
			'exclude_sale_items' => $coupon->get_exclude_sale_items(),
			'minimum_amount' => $coupon->get_minimum_amount(),
			'maximum_amount' => $coupon->get_maximum_amount(),
			'email_restrictions' => implode( ',', $coupon->get_email_restrictions() )
		);

		$date_expired = $coupon->get_date_expires();

		if ( ! empty( $date_expired ) ) {
			$coupon_data['date_expires'] = $date_expired->getTimestamp();
		}

		return $coupon_data;
	}

	/**
	 * Filters slashed post data just before it is inserted into the database.
	 * Use it to get coupon old data
	 *
	 * @param array $data                An array of slashed, sanitized, and processed post data.
	 * @param array $postarr             An array of sanitized (and slashed) but otherwise unmodified post data.
	 * @param array $unsanitized_postarr An array of slashed yet *unsanitized* and unprocessed post data as
	 *                                   originally passed to wp_insert_post().
	 * @return array Returns $data
	 */
	protected function get_coupon_prev_data( array $data, array $postarr, array $unsanitized_postarr ) {
		$post = get_post( $postarr['ID'] );
		if ( is_null( $post ) || $post->post_type !== 'shop_coupon' ) {
			return $data;
		}

		$this->prev_coupon_data = $this->get_coupon_data( $postarr['ID'] );

		return $data;
	}

	/**
	 * Helper function to get old and updated terms
	 *
	 * @param string $taxonomy Taxonomy type
	 * @param string $term_ids Comma separated terms ids
	 * @return array Array containing terms data or empty if terms_ids is empty
	 */
	private function get_term_changes( string $taxonomy, string $term_ids ) :array {
		if ( empty( $term_ids ) ) {
			return array();
		}

		$ids = explode( ',', $term_ids );

		if ( count( $ids ) === 0 ) {
			return array();
		}

		// Query terms and retrive certain information
		$post_terms = get_terms(
			array(
				'include' => $ids,
				'hide_empty' => false,
				'taxonomy' => $taxonomy
			)
		);

		$terms_data = array();
		foreach ( $post_terms as $single_term ) {
			$terms_data[] = $this->pluck_object( $single_term, array( 'term_id', 'name' ) );
		}

		return $terms_data;
	}

	/**
	 * Get products data after coupon update.
	 *
	 * @param string $products_ids Comma separated ids
	 * @return array array of prev and new product data
	 */
	private function get_products_changes( $products_ids ) :array {

		$ids = explode( ',', $products_ids );
		if ( count( $ids ) === 0 ) {
			return array();
		}

		$args = array(
			'include' => $ids,
		);
		$products = wc_get_products( $args );

		$products_data = array();
		foreach ( $products as $product ) {
			$products_data[] = array(
				'id' => $product->get_id(),
				'title' => $product->get_title()
			);
		}

		return $products_data;
	}

	/**
	 * Log changes on save post
	 *
	 * @param int     $coupon_id Coupon ID
	 * @param WP_Post $post Post object
	 * @param bool    $update Whether this is an existing post being updated.
	 * @return void
	 */
	protected function log_coupon_data( $coupon_id, $post, $update ) {

		if ( is_null( $post ) ) {
			return;
		}

		// If not an update then add as created log
		if ( ! $update ) {
			$this->event = 'created';
			$this->log_info = array(
				'id' => $coupon_id
			);
		}

		$prev_data = $this->prev_coupon_data;
		if ( empty( $prev_data ) ) {
			return;
		}

		$new_data = $this->get_coupon_data( $coupon_id );

		// array_diff_assoc goes one level only. Array values are flattened
		$diff = array_diff_assoc( $new_data, $prev_data );

		// if there are no changes don't proceed
		if ( count( $diff ) === 0 ) {
			return;
		}

		$changes = array();
		$diff_keys = array_keys( $diff );

		// Loop through each change and get the neccessary data to insert into database
		foreach ( $diff_keys as $diff_key ) {
			$prev_data_value = $prev_data[ $diff_key ];
			$new_data_value = $new_data[ $diff_key ];

			switch ( $diff_key ) :
				case 'excluded_product_categories':
				case 'product_categories':
					$prev_cat_ids = $this->get_term_changes( 'product_cat', $prev_data_value );
					$new_cat_ids = $this->get_term_changes( 'product_cat', $new_data_value );

					$changes[ $diff_key ] = array( $prev_cat_ids, $new_cat_ids );
					break;

				case 'product_ids':
					$changes['products'] = array_map( array( $this, 'get_products_changes' ), array( $prev_data_value, $new_data_value ) );
					break;
				case 'excluded_product_ids':
					$changes['excluded_products'] = array_map( array( $this, 'get_products_changes' ), array( $prev_data_value, $new_data_value ) );
					break;
				default:
					$changes[ $diff_key ] = array( $prev_data[ $diff_key ], $new_data[ $diff_key ] );
					break;
			endswitch;
		}

		$this->event = 'edited';
		$this->log_info = array(
			'id' => $coupon_id,
			'changes' => $changes
		);
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

		$order_data = array(
			'id' => $render_class::maybe_get_post_edit_link( $log_info['id'] )
		);
		$output .= $render_class::array_to_html( $order_data );

		if ( $event === 'created' ) {
			return $output;
		}

		$changes = $log_info['changes'];
		$output .= "<div class='changes-wrapper'>";
		$output .= "<h5 class='changes-title'>" . get_nashaat_lang( 'changes' ) . '</h5>';

		// Loop through each change and output html string
		foreach ( $changes as $change_key => $change_array ) :
			$output .= '<h5>' . get_nashaat_lang( $change_key ) . '</h5>';
			$output .= "<div class='single-change-item'>";

			$prev_value = $change_array[0];
			$new_value = $change_array[1];

			// For certain keys we need to format before display
			switch ( $change_key ) {
				case 'description':
					$content_data = array(
						'prev_count' => $prev_value,
						'new_count' => $new_value,
					);
					$output .= $render_class::array_to_html( $content_data );
					break;
				case 'excluded_product_categories':
				case 'product_categories':
					$terms_links = $render_class::get_terms_links( 'product_cat', $change_array );

					$terms_data = array(
						'prev' => $terms_links[0],
						'new' => $terms_links[1]
					);

					$output .= $render_class::array_to_html( $terms_data );
					break;
				case 'products':
				case 'excluded_products':
					$product_changes = array_map( array( $render_class, 'get_posts_links' ), $change_array );

					$product_data = array(
						'prev' => $product_changes[0],
						'new' => $product_changes[1]
					);

					$output .= $render_class::array_to_html( $product_data );
					break;
				case 'date_expires':
					if ( ! empty( $prev_value ) ) {
						$prev_value = wp_date( get_option( 'date_format' ) . get_option( 'time_format' ), $prev_value );
					}

					if ( ! empty( $new_value ) ) {
						$new_value = wp_date( get_option( 'date_format' ) . get_option( 'time_format' ), $new_value );
					}

					// Check if prev and new values are empty or not and assign appropriate value
					$prev_value = empty( $prev_value ) ? get_nashaat_lang( 'empty' ) : $prev_value;
					$new_value = empty( $new_value ) ? get_nashaat_lang( 'empty' ) : $new_value;

					$date_value = array(
						'prev' => $prev_value,
						'new' => $new_value,
					);
					$output .= $render_class::array_to_html( $date_value );
					break;
				case 'exclude_sale_items':
				case 'free_shipping':
				case 'individual_use':
					$boolean_data = array(
						'prev' => empty( $prev_value ) ? get_nashaat_lang( 'disabled' ) : get_nashaat_lang( 'enabled' ),
						'new' => empty( $new_value ) ? get_nashaat_lang( 'disabled' ) : get_nashaat_lang( 'enabled' )
					);
					$output .= $render_class::array_to_html( $boolean_data );
					break;
				default:
					// Check if prev and new values are empty or not and assign appropriate value
					$prev_value = empty( $prev_value ) ? get_nashaat_lang( 'empty' ) : $prev_value;
					$new_value = empty( $new_value ) ? get_nashaat_lang( 'empty' ) : $new_value;

					$meta_data = array(
						'prev' => $prev_value,
						'new' => $new_value,
					);
					$output .= $render_class::array_to_html( $meta_data );
					break;
			}

			$output .= '</div>';
		endforeach;

		$output .= '</div>';
		return $output;
	}
}
?>