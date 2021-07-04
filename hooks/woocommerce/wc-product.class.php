<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle logging of WooCommerce products
 */
class NashaatWCProduct extends NashaatHookBase {

	protected $actions = array(
		array(
			'name' => 'save_post_product',
			'args' => 3,
			'callback' => 'log_product_changes'
		),

		array(
			'name' => 'transition_post_status',
			'args' => 3,
			'callback' => 'log_product_status'
		)
	);

	protected $filters = array(
		array(
			'name' => 'wp_insert_post_data',
			'args' => 3,
			'callback' => 'get_product_prev_data'
		),
		array(
			'name' => 'nashaat_translations',
			'callback' => 'add_wc_product_translations'
		)
	);

	protected $context = 'wc_product';
	protected $level = NASHAAT_LOG_LEVEL_MEDIUM;


	/**
	 * Product log translations
	 *
	 * @param array $translations Current plugin translations
	 * @return array modified $translations array
	 */
	public function add_wc_product_translations( array $translations ) {
		$wc_product_translations = array(
			'wc_product' => __( 'WC Product', 'nashaat' ),
			'catalog_visibility' => __( 'Catalog Visibility', 'nashaat' ),
			'sku' => __( 'SKU', 'nashaat' ),
			'short_description' => __( 'Short Description', 'nashaat' ),
			'price' => __( 'Price', 'nashaat' ),
			'regular_price' => __( 'Regular Price', 'nashaat' ),
			'sale_price' => __( 'Sale Price', 'nashaat' ),
			'tax_status' => __( 'Tax Status', 'nashaat' ),
			'tax_class' => __( 'Tax Class', 'nashaat' ),
			'manage_stock' => __( 'Manage Stock', 'nashaat' ),
			'stock_quantity' => __( 'Stock Quantity', 'nashaat' ),
			'stock_status' => __( 'Stock Status', 'nashaat' ),
			'low_stock_amount' => __( 'Low Stock Threshold', 'nashaat' ),
			'backorders' => __( 'Backorder', 'nashaat' ),
			'sold_individually' => __( 'Sold Individually', 'nashaat' ),
			'weight' => __( 'Weight', 'nashaat' ),
			'length' => __( 'Length', 'nashaat' ),
			'width' => __( 'Width', 'nashaat' ),
			'height' => __( 'Height', 'nashaat' ),
			'upsells' => __( 'Upsells', 'nashaat' ),
			'cross_sells' => __( 'Cross-sells', 'nashaat' ),
			'reviews_allowed' => __( 'Review Allowed', 'nashaat' ),
			'purchase_note' => __( 'Purchase Note', 'nashaat' ),
			'menu_order' => __( 'Menu Order', 'nashaat' ),
			'post_password' => __( 'Password', 'nashaat' ),
			'virtual' => __( 'Virtual', 'nashaat' ),
			'gallery_images' => __( 'Gallery Images', 'nashaat' ),
			'shipping_class' => __( 'Shipping Class', 'nashaat' ),
			'downloadable' => __( 'Downloadable', 'nashaat' ),
			'download_expiry' => __( 'Download Expiry', 'nashaat' ),
			'download_limit' => __( 'Download Limit', 'nashaat' ),
			'product_type' => __( 'Product Type', 'nashaat' ),
			'product_url' => __( 'Url', 'nashaat' ),
			'button_text' => __( 'Button Text', 'nashaat' ),
			'children' => __( 'Children Products', 'nashaat' ),
			'unlimited' => __( 'unlimited', 'nashaat' ),
			'never' => __( 'never', 'nashaat' ),
			'sale_price_dates_from' => __( 'Sale price from', 'nashaat' ),
			'sale_price_dates_to' => __( 'Sale price to', 'nashaat' ),
		);
		return array_merge( $translations, $wc_product_translations );
	}

	/**
	 * Holds old product data to compare with new data
	 * after save
	 *
	 * @var array
	 */
	private $prev_product_data = array();

	/**
	 * Get a list of product data. This function can be used when saving product
	 * to retrive a list of data and compare before and after save
	 *
	 * @param int $product_id Product ID
	 * @return array Array of product data
	 */
	private function get_poduct_data( $product_id ) {
		$product = wc_get_product( $product_id );

		$product_data = array(
			'title' => $product->get_title(),
			'slug' => $product->get_slug(),
			'catalog_visibility' => $product->get_catalog_visibility(),
			'description' => strlen( $product->get_description() ),
			'short_description' => strlen( $product->get_short_description() ),
			'sku' => $product->get_sku(),
			'price' => $product->get_price(),
			'regular_price' => $product->get_regular_price(),
			'sale_price' => $product->get_sale_price(),
			'tax_status' => $product->get_tax_status(),
			'tax_class' => $product->get_tax_class(),
			'manage_stock' => $product->get_manage_stock(),
			'stock_quantity' => $product->get_stock_quantity(),
			'stock_status' => $product->get_stock_status(),
			'low_stock_amount' => $product->get_low_stock_amount(),
			'backorders' => $product->get_backorders(),
			'sold_individually' => $product->get_sold_individually(),
			'weight' => $product->get_weight(),
			'length' => $product->get_length(),
			'width' => $product->get_width(),
			'height' => $product->get_height(),
			'upsell_ids' => implode( ',', $product->get_upsell_ids() ),
			'cross_sell_ids' => implode( ',', $product->get_cross_sell_ids() ),
			'reviews_allowed' => $product->get_reviews_allowed(),
			'purchase_note' => $product->get_purchase_note(),
			'menu_order' => $product->get_menu_order(),
			'post_password' => $product->get_post_password(),
			'category_ids' => implode( ',', $product->get_category_ids() ),
			'tag_ids' => implode( ',', $product->get_tag_ids() ),
			'virtual' => $product->get_virtual(),
			'image_id' => $product->get_image_id(),
			'gallery_image_ids' => implode( ',', $product->get_gallery_image_ids() ),
			'shipping_class' => $product->get_shipping_class(),
			'shipping_class_id' => $product->get_shipping_class_id(),
			'downloadable' => $product->get_downloadable(),
			'download_expiry' => $product->get_download_expiry(),
			'download_limit' => $product->get_download_limit(),
			'product_type' => $product->get_type(),
			'children' => implode( ',', $product->get_children() ),
			'product_url' => '',
			'button_text' => ''
		);

		if ( $product->is_type( 'external' ) ) {
			$product_data['product_url'] = $product->get_product_url();
			$product_data['button_text'] = $product->get_button_text();
		}

		$sale_from = $product->get_date_on_sale_from();
		$sale_to = $product->get_date_on_sale_to();

		if ( ! empty( $sale_from ) ) {
			$product_data['sale_price_dates_from'] = $sale_from->date( wc_date_format() );
		}

		if ( ! empty( $sale_to ) ) {
			$product_data['sale_price_dates_to'] = $sale_to->date( wc_date_format() );
		}

		return $product_data;
	}


	/**
	 * Log changes on save post
	 *
	 * @param int    $product_id Product ID
	 * @param object $product Post object
	 * @param bool   $update True if post is updated
	 * @return void
	 */
	protected function log_product_changes( $product_id, $product, $update ) {

		if ( wp_is_post_revision( $product_id ) || wp_is_post_autosave( $product_id ) || is_null( $product ) || $update !== true ) {
			return;
		}

		$prev_data = $this->prev_product_data;
		if ( empty( $prev_data ) ) {
			return;
		}
		$new_data = $this->get_poduct_data( $product_id );

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
				case 'category_ids':
					$prev_cat_ids = $this->get_term_changes( 'product_cat', $prev_data_value );
					$new_cat_ids = $this->get_term_changes( 'product_cat', $new_data_value );

					$changes['categories'] = array( $prev_cat_ids, $new_cat_ids );
					break;
				case 'tag_ids':
					$prev_tag_ids = $this->get_term_changes( 'product_tag', $prev_data_value );
					$new_tag_ids = $this->get_term_changes( 'product_tag', $new_data_value );
					$changes['tags'] = array( $prev_tag_ids, $new_tag_ids );

					break;
				case 'upsell_ids':
					$changes['upsells'] = array_map( array( $this, 'get_linked_products_changes' ), array( $prev_data_value, $new_data_value ) );
					break;
				case 'cross_sell_ids':
					$changes['cross_sells'] = array_map( array( $this, 'get_linked_products_changes' ), array( $prev_data_value, $new_data_value ) );
					break;
				case 'children':
					$changes['children'] = array_map( array( $this, 'get_linked_products_changes' ), array( $prev_data_value, $new_data_value ) );
					break;
				case 'image_id':
					// Get media data for both old and new product data.
					$changes['featured_media'] = array_map(
						function( $media_id ) {

							$media_title = '';
							if ( ! empty( $media_id ) ) {
								$media_title = get_post( $media_id )->post_title;
							}

							return array(
								'id' => $media_id,
								'title' => $media_title
							);
						},
						array( $prev_data_value, $new_data_value )
					);
					break;
				case 'gallery_image_ids':
					// Get gallery images data for both old and new product data.
					$changes['gallery_images'] = array_map(
						function( $images_ids ) {
							$ids = explode( ',', $images_ids );
							if ( count( $ids ) === 0 ) {
								return array();
							}
							$images_data = array();
							foreach ( $ids as $image_id ) {
								$images_data[] = array(
									'id' => $image_id,
									'title' => get_post( $image_id )->post_title
								);
							}
							return $images_data;
						},
						array( $prev_data_value, $new_data_value )
					);
					break;
				default:
					$changes[ $diff_key ] = array( $prev_data[ $diff_key ], $new_data[ $diff_key ] );
					break;
			endswitch;
		}

		$this->level = NASHAAT_LOG_LEVEL_MEDIUM;
		$this->event = 'edited';
		$this->log_info = array(
			'id' => $product_id,
			'title' => $new_data['title'],
			'changes' => $changes
		);
	}

	/**
	 * Handle product status
	 *
	 * @param string  $new_status New post status
	 * @param string  $old_status Old post status
	 * @param WP_Post $post Post object
	 *
	 * @return bool|void False if action is not processed
	 */
	protected function log_product_status( string $new_status, string $old_status, WP_Post $post ) {
		// Skip for all posts except for product post type
		if ( wp_is_post_revision( $post->ID ) || wp_is_post_autosave( $post->ID ) || get_post_type( $post->ID ) !== 'product' ) {
			return false;
		}

		// Go ahead if old status is not the same as the new status
		if ( $old_status === $new_status ) {
			return false;
		}

		$log_info = $this->pluck_object(
			$post,
			array(
				'ID' => 'id',
				'post_title' => 'title'
			)
		);

		$log_info['new_status'] = $new_status;
		$log_info['old_status'] = $old_status;

		$event = false;
		switch ( true ) {
			case $new_status === 'trash':
				$event = 'trashed';
				$this->level = NASHAAT_LOG_LEVEL_HIGH;
				break;

			case $old_status === 'trash':
				$event = 'restored';
				break;

			// new post
			case $old_status === 'auto-draft':
				$event = 'created';
				$this->level = NASHAAT_LOG_LEVEL_LOW;
				break;

			case $new_status === 'future':
				$log_info['future_date'] = get_post_timestamp( $post );
				$event = 'scheduled';
				break;
		}

		if ( $event === false ) {
			return false;
		}

		$this->event = $event;
		$this->log_info = $log_info;
	}
	/**
	 * Filters slashed post data just before it is inserted into the database.
	 * Use it to get product old data
	 *
	 * @param array $data                An array of slashed, sanitized, and processed post data.
	 * @param array $postarr             An array of sanitized (and slashed) but otherwise unmodified post data.
	 * @param array $unsanitized_postarr An array of slashed yet *unsanitized* and unprocessed post data as
	 *                                   originally passed to wp_insert_post().
	 * @return array Returns $data
	 */
	protected function get_product_prev_data( array $data, array $postarr, array $unsanitized_postarr ) {
		$post = get_post( $postarr['ID'] );
		if ( is_null( $post ) || $post->post_type !== 'product' ) {
			return $data;
		}

		$this->prev_product_data = $this->get_poduct_data( $post );

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
	 * Get linked products (Upsell and cross sell) data after product update.
	 *
	 * @param string $products_ids Comma separated ids
	 * @return array array of prev and new product data
	 */
	private function get_linked_products_changes( $products_ids ) :array {

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
			'id' => $log_info['id'],
			'title' => $render_class::maybe_get_post_title( $log_info['title'], $log_info['id'] ),
		);
		$output .= $render_class::array_to_html( $order_data );
		if ( $event === 'created' ) {
			return $output;
		}

		// Make sure we have an array for changes
		if ( ! isset( $log_info['changes'] ) || ! is_array( $log_info['changes'] ) ) {
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
				case 'download_limit':
					$prev_value = ( $prev_value == '-1' ) ? get_nashaat_lang( 'unlimited' ) : $prev_value;
					$new_value = ( $new_value == '-1' ) ? get_nashaat_lang( 'unlimited' ) : $new_value;
					break;
				case 'download_expiry':
					$prev_value = ( $prev_value == '-1' ) ? get_nashaat_lang( 'never' ) : $prev_value;
					$new_value = ( $new_value == '-1' ) ? get_nashaat_lang( 'never' ) : $new_value;
					break;
				case 'categories':
				case 'tags':
					$taxonomy = ( $change_array === 'tags' ) ? 'product_tag' : 'product_cat';
					$terms_links = $render_class::get_terms_links( $taxonomy, $change_array );

					$prev_value = $terms_links[0];
					$new_value = $terms_links[1];
					break;

				case 'upsells':
				case 'cross_sells':
				case 'children':
					$product_changes = array_map( array( $render_class, 'get_posts_links' ), $change_array );

					$prev_value = $product_changes[0];
					$new_value = $product_changes[1];
					break;
				case 'manage_stock':
				case 'sold_individually':
				case 'downloadable':
				case 'virtual':
				case 'reviews_allowed':
					$prev_value = empty( $prev_value ) ? get_nashaat_lang( 'disabled' ) : get_nashaat_lang( 'enabled' );
					$new_value = empty( $new_value ) ? get_nashaat_lang( 'disabled' ) : get_nashaat_lang( 'enabled' );

					break;
				case 'featured_media':
					// Destruct media keys
					list('title' => $prev_media_title, 'id' => $prev_media_id) = $change_array[0];
					list('title' => $new_media_title, 'id' => $new_media_id) = $change_array[1];

					if ( empty( $prev_media_id ) ) {
						$prev_media_id = 0;
					}

					$prev_value = $render_class::maybe_get_post_title( $prev_media_title, $prev_media_id, 'not_available' );
					$new_value = $render_class::maybe_get_post_title( $new_media_title, $new_media_id, 'not_available' );

					break;
				case 'gallery_images':
					$gallery_changes = array_map( array( $render_class, 'get_posts_links' ), $change_array );

					$prev_value = $gallery_changes[0];
					$new_value = $gallery_changes[1];
					break;
				case 'description':
				case 'short_description':
					$prev_value = empty( $prev_value ) ? 0 : $prev_value;
					$new_value = empty( $new_value ) ? 0 : $new_value;
					break;
				default:
					// Check if prev and new values are empty or not and assign appropriate value
					$prev_value = empty( $prev_value ) ? get_nashaat_lang( 'empty' ) : $prev_value;
					$new_value = empty( $new_value ) ? get_nashaat_lang( 'empty' ) : $new_value;
					break;
			}

			if ( in_array( $change_key, array( 'short_description', 'description' ) ) ) {
				$meta_data = array(
					'prev_count' => $prev_value,
					'new_count' => $new_value,
				);
			} else {
				$meta_data = array(
					'prev' => $prev_value,
					'new' => $new_value,
				);
			}
			$output .= $render_class::array_to_html( $meta_data );

			$output .= '</div>';
		endforeach;

		$output .= '</div>';
		return $output;
	}
}
?>