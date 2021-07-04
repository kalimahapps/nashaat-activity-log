<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle WooCommerce Product Variations log
 */
class NashaatWCVariations extends NashaatHookBase {
	protected $actions = array(
		array(
			'name' => 'woocommerce_new_product_variation',
			'args' => 2,
			'callback' => 'log_variation_create'
		),
		array(
			'name' => 'woocommerce_update_product_variation',
			'args' => 2,
			'callback' => 'log_variation_update'
		),
		array(
			'name' => 'admin_init',
			'callback' => 'log_remove_variation'
		),
		array(
			'name' => 'admin_init',
			'callback' => 'get_variations_prev_data'
		)
	);

	protected $filters = array(
		array(
			'name' => 'nashaat_translations',
			'callback' => 'add_wc_variations_translations'
		),
	);

	private $prev_variation_data = array();

	protected $context = 'wc_variations';
	protected $level = NASHAAT_LOG_LEVEL_MEDIUM;

	/**
	 * Add translation strings specific to WooCommerce product variation
	 *
	 * @param array $translations Array of translations
	 * @return array Modified $translations array
	 */
	public function add_wc_variations_translations( array $translations ) :array {
		$wc_coupon_translations = array(
			'wc_variations' => __( 'WC Product Variation', 'nashaat' ),
			'product_id' => __( 'Product ID', 'nashaat' ),
			'attributes' => __( 'Attributes', 'nashaat' )
		);
		return array_merge( $translations, $wc_coupon_translations );
	}


	/**
	 * Get variation data
	 *
	 * @param int $variation_id Variation ID
	 * @return array variation data array
	 */
	private function get_variation_data( $variation_id ) :array {

		$variation = wc_get_product( $variation_id );

		$variation_data = array(
			'attributes' => $variation->get_attribute_summary(),
			'menu_order' => $variation->get_menu_order(),
			'upload_image_id' => $variation->get_image_id(),
			'virtual' => $variation->get_virtual(),
			'downloadable' => $variation->get_downloadable(),
			'sku' => $variation->get_sku(),
			'status' => $variation->get_status(),
			'regular_price' => $variation->get_regular_price(),
			'sale_price' => $variation->get_sale_price(),
			'manage_stock' => $variation->get_manage_stock(),
			'stock_quantity' => $variation->get_stock_quantity(),
			'backorders' => $variation->get_backorders(),
			'low_stock_amount' => $variation->get_low_stock_amount(),
			'stock_status' => $variation->get_stock_status(),
			'weight' => $variation->get_weight(),
			'length' => $variation->get_length(),
			'width' => $variation->get_width(),
			'height' => $variation->get_height(),
			'shipping_class' => $variation->get_shipping_class(),
			'tax_class' => $variation->get_tax_class(),
			'description' => strlen( $variation->get_description() ),
			'download_limit' => $variation->get_download_limit(),
			'download_expiry' => $variation->get_download_expiry(),
			'sale_price_dates_from' => '',
			'sale_price_dates_to' => '',
		);
		$sale_from = $variation->get_date_on_sale_from();
		$sale_to = $variation->get_date_on_sale_to();

		if ( ! empty( $sale_from ) ) {
			$variation_data['sale_price_dates_from'] = $sale_from->date( wc_date_format() );
		}

		if ( ! empty( $sale_to ) ) {
			$variation_data['sale_price_dates_to'] = $sale_to->date( wc_date_format() );
		}

		return $variation_data;

	}

	/**
	 * Log when a variation is removed through ajax
	 *
	 * @return void
	 */
	protected function log_remove_variation() {
		$__post = filter_input_array( INPUT_POST );

		// Check if remove variations request
		if ( ! $this->is_keys_values_set( $__post, array( 'action' => 'woocommerce_remove_variations' ) ) ) {
			return;
		}

		$variations_ids = $__post['variation_ids'];
		if ( count( $variations_ids ) === 0 ) {
			return;
		}

		foreach ( $variations_ids as $variation_id ) {
			$variation = wc_get_product( $variation_id );
			$variation_name = wp_filter_nohtml_kses( $variation->get_formatted_name() );
			$this->log_info_array[] = array(
				'product_id' => $variation->get_parent_id(),
				'title' => $variation_name
			);
		}

		$this->event = 'deleted';
	}

	/**
	 * Log when a new variation created through ajax
	 *
	 * @param int    $id Variation id
	 * @param object $product Variation object
	 * @return void
	 */
	protected function log_variation_create( $id, $product ) {
		$this->event = 'created';
		$this->log_info = array(
			'product_id' => $product->get_parent_id(),
		);
	}

	/**
	 * Log when a variation is updated through ajax
	 *
	 * @param int    $variation_id Variation id
	 * @param object $product Variation object
	 * @return void
	 */
	protected function log_variation_update( $variation_id, $product ) {

		// Make sure there is prev data to compare agains
		if ( count( $this->prev_variation_data ) === 0 || ! isset( $this->prev_variation_data[ $variation_id ] ) ) {
			return;
		}

		$variation_new_data = $this->get_variation_data( $variation_id );
		$variation_prev_data = $this->prev_variation_data[ $variation_id ];

		// find difference
		$diff = array_diff_assoc( $variation_prev_data, $variation_new_data );

		// if there are no changes skip loop
		if ( count( $diff ) === 0 ) {
			return;
		}

		$changes = array();
		$diff_keys = array_keys( $diff );
		foreach ( $diff_keys as $diff_key ) {
			if ( $diff_key !== 'upload_image_id' ) {
				$changes[ $diff_key ] = array( $variation_prev_data[ $diff_key ], $variation_new_data[ $diff_key ] );
				continue;
			}

			// Get upload_image_id data for both old and new variation data.
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
				array( $variation_prev_data[ $diff_key ], $variation_new_data[ $diff_key ] )
			);
		}

		$this->event = 'edited';
		$this->log_info = array(
			'product_id' => $product->get_parent_id(),
			'changes' => $changes
		);
	}

	/**
	 * Get variation update data
	 *
	 * @return void
	 */
	protected function get_variations_prev_data() {

		$__post = filter_input_array( INPUT_POST );

		// Check if update variations request
		$check_array = array(
			'action' => 'woocommerce_save_variations',
			'product-type' => 'variable'
		);

		if ( ! $this->is_keys_values_set( $__post, $check_array ) ) {
			return;
		}
		foreach ( $__post['variable_post_id'] as $variation_index => $variation_id ) {
			$this->prev_variation_data[ $variation_id ] = $this->get_variation_data( $variation_id );
		}

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

		switch ( $event ) :
			case 'deleted':
				$deleted_data = array(
					'product_id' => $render_class::maybe_get_post_edit_link( $log_info['product_id'] ),
					'title' => $log_info['title'],
				);
				$output = $render_class::array_to_html( $deleted_data );
				break;
			case 'created':
				$created_data = array(
					'product_id' => $render_class::maybe_get_post_edit_link( $log_info['product_id'] )
				);
				$output = $render_class::array_to_html( $created_data );
				break;
			case 'edited':
				$changes = $log_info['changes'];
				$output = $render_class::array_to_html(
					array(
						'product_id' => $render_class::maybe_get_post_edit_link( $log_info['product_id'] )
					)
				);

				$output .= "<div class='changes-wrapper'>";
				$output .= "<h5 class='changes-title'>" . get_nashaat_lang( 'changes' ) . '</h5>';

				// Loop through each change and output html string
				foreach ( $changes as $change_key => $change_array ) :
					$output .= '<h5>' . get_nashaat_lang( $change_key ) . '</h5>';
					$output .= "<div class='single-change-item'>";

					$prev_value = $change_array[0];
					$new_value = $change_array[1];

					switch ( $change_key ) :
						case 'download_limit':
							$prev_value = ( $prev_value == '-1' ) ? get_nashaat_lang( 'unlimited' ) : $prev_value;
							$new_value = ( $new_value == '-1' ) ? get_nashaat_lang( 'unlimited' ) : $new_value;
							break;
						case 'download_expiry':
							$prev_value = ( $prev_value == '-1' ) ? get_nashaat_lang( 'never' ) : $prev_value;
							$new_value = ( $new_value == '-1' ) ? get_nashaat_lang( 'never' ) : $new_value;
							break;

						case 'manage_stock':
						case 'virtual':
						case 'downloadable':
							$prev_value = empty( $prev_value ) ? get_nashaat_lang( 'disabled' ) : get_nashaat_lang( 'enabled' );
							$new_value = empty( $new_value ) ? get_nashaat_lang( 'disabled' ) : get_nashaat_lang( 'enabled' );

							break;
						case 'description':
							$prev_value = empty( $prev_value ) ? 0 : $prev_value;
							$new_value = empty( $new_value ) ? 0 : $new_value;
							break;
						case 'status':
							$prev_value = $prev_value === 'publish' ? get_nashaat_lang( 'disabled' ) : get_nashaat_lang( 'enabled' );
							$new_value = $new_value === 'publish' ? get_nashaat_lang( 'disabled' ) : get_nashaat_lang( 'enabled' );

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
						default:
							// Check if prev and new values are empty or not and assign appropriate value
							$prev_value = empty( $prev_value ) ? get_nashaat_lang( 'empty' ) : $prev_value;
							$new_value = empty( $new_value ) ? get_nashaat_lang( 'empty' ) : $new_value;

							break;
					endswitch;

					if ( $change_key === 'description' ) {
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

				break;
		endswitch;
		$output .= '</div>';
		return $output;
	}
}
?>