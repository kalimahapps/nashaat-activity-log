<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle logging of WooCommerce Settings
 */
class NashaatWCSettings extends NashaatHookBase {

	protected $actions = array(
		array(
			'name' => 'updated_option',
			'args' => 3
		),

		array(
			'name' => 'woocommerce_init',
			'callback' => 'set_wc_settings'
		),
		array(
			'name' => 'admin_footer-woocommerce_page_wc-settings',
			'callback' => 'log_wc_settings'
		)
	);


	protected $filters = array(
		array(
			'name' => 'nashaat_translations',
			'callback' => 'add_wc_settings_translations'
		)
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

	/**
	 * Holds current page settings (General, products, shipping .. etc)
	 *
	 * @var array
	 */
	public $current_wc_page_settings = array();

	public $tab = null;


	protected $context = 'wc_settings';
	protected $level = NASHAAT_LOG_LEVEL_MEDIUM;


	/**
	 * Settings log translations
	 *
	 * @param array $translations Current plugin translations
	 * @return array modified $translations array
	 */
	public function add_wc_settings_translations( array $translations ) {
		$wc_settings_translations = array(
			'wc_settings' => __( 'WC Settings', 'nashaat' ),
			'general' => __( 'General', 'nashaat' ),
			'products' => __( 'Products', 'nashaat' ),
			'tax' => __( 'Tax', 'nashaat' ),
			'shipping' => __( 'Shipping', 'nashaat' ),
			'account' => __( 'Account', 'nashaat' ),
			'email' => __( 'Email', 'nashaat' ),
			'advanced' => __( 'Advanced', 'nashaat' )
		);
		return array_merge( $translations, $wc_settings_translations );
	}

	/**
	 * Set various woocommerce settings (general, to class properites
	 *
	 * @return void
	 */
	public function set_wc_settings() {
		$__get = filter_input_array( INPUT_GET );
		$this->tab = empty( $__get['tab'] ) ? 'general' : $__get['tab'];

		// Set all settings
		$this->all_settings = array(
			'general' => array(
				'woocommerce_store_address' => __( 'Address line 1', 'woocommerce' ),
				'woocommerce_store_address_2' => __( 'Address line 2', 'woocommerce' ),
				'woocommerce_store_city' => __( 'City', 'woocommerce' ),
				'woocommerce_default_country' => __( 'Country / State', 'woocommerce' ),
				'woocommerce_store_postcode' => __( 'Postcode / ZIP', 'woocommerce' ),
				'woocommerce_allowed_countries' => __( 'Selling location(s)', 'woocommerce' ),
				'woocommerce_all_except_countries' => __( 'Sell to all countries, except for&hellip;', 'woocommerce' ),
				'woocommerce_specific_allowed_countries' => __( 'Sell to specific countries', 'woocommerce' ),
				'woocommerce_ship_to_countries' => __( 'Shipping location(s)', 'woocommerce' ),
				'woocommerce_specific_ship_to_countries' => __( 'Ship to specific countries', 'woocommerce' ),
				'woocommerce_default_customer_address' => __( 'Default customer location', 'woocommerce' ),
				'woocommerce_calc_taxes' => __( 'Enable taxes', 'woocommerce' ),
				'woocommerce_enable_coupons' => __( 'Enable coupons', 'woocommerce' ),
				'woocommerce_currency' => __( 'Currency', 'woocommerce' ),
				'woocommerce_currency_pos' => __( 'Currency position', 'woocommerce' ),
				'woocommerce_price_thousand_sep' => __( 'Thousand separator', 'woocommerce' ),
				'woocommerce_price_decimal_sep' => __( 'Decimal separator', 'woocommerce' ),
				'woocommerce_price_num_decimals' => __( 'Number of decimals', 'woocommerce' )
			),
			'products' => array(
				'woocommerce_shop_page_id' => __( 'Shop page', 'woocommerce' ),
				'woocommerce_cart_redirect_after_add' => __( 'Add to cart behaviour', 'woocommerce' ),
				'woocommerce_placeholder_image' => __( 'Placeholder image', 'woocommerce' ),
				'woocommerce_weight_unit' => __( 'Weight unit', 'woocommerce' ),
				'woocommerce_dimension_unit' => __( 'Dimensions unit', 'woocommerce' ),
				'woocommerce_enable_reviews' => __( 'Enable reviews', 'woocommerce' ),
				'woocommerce_enable_review_rating' => __( 'Product ratings', 'woocommerce' ),
				'woocommerce_manage_stock' => __( 'Manage stock', 'woocommerce' ),
				'woocommerce_hold_stock_minutes' => __( 'Hold stock (minutes)', 'woocommerce' ),
				'woocommerce_notify_low_stock' => __( 'Notifications', 'woocommerce' ),
				'woocommerce_stock_email_recipient' => __( 'Notification recipient(s)', 'woocommerce' ),
				'woocommerce_notify_low_stock_amount' => __( 'Low stock threshold', 'woocommerce' ),
				'woocommerce_notify_no_stock_amount' => __( 'Out of stock threshold', 'woocommerce' ),
				'woocommerce_hide_out_of_stock_items' => __( 'Out of stock visibility', 'woocommerce' ),
				'woocommerce_stock_format' => __( 'Stock display format', 'woocommerce' ),
				'woocommerce_file_download_method' => __( 'File download method', 'woocommerce' ),
				'woocommerce_downloads_require_login' => __( 'Access restriction', 'woocommerce' ),
				'woocommerce_downloads_add_hash_to_filename' => __( 'Filename', 'woocommerce' )
			),
			'tax' => array(
				'woocommerce_prices_include_tax' => __( 'Prices entered with tax', 'woocommerce' ),
				'woocommerce_tax_based_on' => __( 'Calculate tax based on', 'woocommerce' ),
				'woocommerce_shipping_tax_class' => __( 'Shipping tax class', 'woocommerce' ),
				'woocommerce_tax_round_at_subtotal' => __( 'Rounding', 'woocommerce' ),
				'woocommerce_tax_classes' => __( 'Additional tax classes', 'woocommerce' ),
				'woocommerce_tax_display_shop' => __( 'Display prices in the shop', 'woocommerce' ),
				'woocommerce_tax_display_cart' => __( 'Display prices during cart and checkout', 'woocommerce' ),
				'woocommerce_price_display_suffix' => __( 'Price display suffix', 'woocommerce' ),
				'woocommerce_tax_total_display' => __( 'Display tax totals', 'woocommerce' )
			),
			'shipping' => array(
				'woocommerce_enable_shipping_calc' => __( 'Calculations', 'woocommerce' ),
				'woocommerce_ship_to_destination' => __( 'Shipping destination', 'woocommerce' ),
				'woocommerce_shipping_debug_mode' => __( 'Debug mode', 'woocommerce' )
			),
			'account' => array(
				'woocommerce_enable_guest_checkout' => __( 'Guest checkout', 'woocommerce' ),
				'woocommerce_enable_checkout_login_reminder' => __( 'Login', 'woocommerce' ),
				'woocommerce_enable_signup_and_login_from_checkout' => __( 'Account creation', 'woocommerce' ),
				'woocommerce_erasure_request_removes_order_data' => __( 'Account erasure requests', 'woocommerce' ),
				'woocommerce_allow_bulk_remove_personal_data' => __( 'Personal data removal', 'woocommerce' ),
				'woocommerce_registration_privacy_policy_text' => __( 'Registration privacy policy', 'woocommerce' ),
				'woocommerce_checkout_privacy_policy_text' => __( 'Checkout privacy policy', 'woocommerce' ),
				'woocommerce_delete_inactive_accounts' => __( 'Retain inactive accounts ', 'woocommerce' ),
				'woocommerce_trash_pending_orders' => __( 'Retain pending orders ', 'woocommerce' ),
				'woocommerce_trash_failed_orders' => __( 'Retain failed orders', 'woocommerce' ),
				'woocommerce_trash_cancelled_orders' => __( 'Retain cancelled orders', 'woocommerce' ),
				'woocommerce_anonymize_completed_orders' => __( 'Retain completed orders', 'woocommerce' )
			),
			'email' => array(
				'woocommerce_email_from_name' => __( '"From" name', 'woocommerce' ),
				'woocommerce_email_from_address' => __( '"From" address', 'woocommerce' ),
				'woocommerce_email_header_image' => __( 'Header image', 'woocommerce' ),
				'woocommerce_email_footer_text' => __( 'Footer text', 'woocommerce' ),
				'woocommerce_email_base_color' => __( 'Base colour', 'woocommerce' ),
				'woocommerce_email_background_color' => __( 'Background colour', 'woocommerce' ),
				'woocommerce_email_body_background_color' => __( 'Body background colour', 'woocommerce' ),
				'woocommerce_email_text_color' => __( 'Body text colour', 'woocommerce' ),
				'woocommerce_merchant_email_notifications' => __( 'Enable email insights', 'woocommerce' )
			),
			'advanced' => array(
				'woocommerce_cart_page_id' => __( 'Cart page', 'woocommerce' ),
				'woocommerce_checkout_page_id' => __( 'Checkout page', 'woocommerce' ),
				'woocommerce_myaccount_page_id' => __( 'My account page', 'woocommerce' ),
				'woocommerce_terms_page_id' => __( 'Terms and conditions', 'woocommerce' ),
				'woocommerce_force_ssl_checkout' => __( 'Secure checkout', 'woocommerce' ),
				'woocommerce_unforce_ssl_checkout' => __( 'Force HTTP when leaving the checkout', 'woocommerce' ),
				'woocommerce_checkout_pay_endpoint' => __( 'Pay', 'woocommerce' ),
				'woocommerce_checkout_order_received_endpoint' => __( 'Order received', 'woocommerce' ),
				'woocommerce_myaccount_add_payment_method_endpoint' => __( 'Add payment method', 'woocommerce' ),
				'woocommerce_myaccount_delete_payment_method_endpoint' => __( 'Delete payment method', 'woocommerce' ),
				'woocommerce_myaccount_set_default_payment_method_endpoint' => __( 'Set default payment method', 'woocommerce' ),
				'woocommerce_myaccount_orders_endpoint' => __( 'Orders', 'woocommerce' ),
				'woocommerce_myaccount_view_order_endpoint' => __( 'View order', 'woocommerce' ),
				'woocommerce_myaccount_downloads_endpoint' => __( 'Downloads', 'woocommerce' ),
				'woocommerce_myaccount_edit_account_endpoint' => __( 'Edit account', 'woocommerce' ),
				'woocommerce_myaccount_edit_address_endpoint' => __( 'Addresses', 'woocommerce' ),
				'woocommerce_myaccount_payment_methods_endpoint' => __( 'Payment methods', 'woocommerce' ),
				'woocommerce_myaccount_lost_password_endpoint' => __( 'Lost password', 'woocommerce' ),
				'woocommerce_logout_endpoint' => __( 'Logout', 'woocommerce' )
			)
		);

		if ( ! isset( $this->all_settings[ $this->tab ] ) ) {
			return;
		}
		$this->current_wc_page_settings = $this->all_settings[ $this->tab ];
	}

	/**
	 * Process $updated_settings array and add to database
	 *
	 * @return void
	 */
	protected function log_wc_settings() {
		if ( count( $this->updated_settings ) === 0 ) {
			return;
		}
		$this->event = 'updated';
		$this->log_info = array(
			'tab' => $this->tab,
			'changes' => $this->updated_settings
		);
	}

	/**
	 * Callback for update option hook. Add all changed settings to class property
	 * so we can later process all of it
	 *
	 * @param string $option_name Option name
	 * @param mixed  $old_value Old option value
	 * @param mixed  $new_value New option value
	 * @return bool|void False if option is not part of woocommerce
	 */
	protected function updated_option_callback( string $option_name, $old_value, $new_value ) {

		if ( ! isset( $this->current_wc_page_settings[ $option_name ] ) ) {
			return false;
		}

		$this->updated_settings[ $option_name ] = array( $old_value, $new_value );
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
		$tab = $log_info['tab'];
		$output = "<h4 class='changes-title'>" . get_nashaat_lang( $tab ) . '</h4>';
		$output .= "<div class='changes-wrapper'>";

		$output .= '<h5>' . get_nashaat_lang( 'changes' ) . '</h5>';

		// Loop through each change and output html string
		foreach ( $log_info['changes'] as $change_key => $change_array ) :
			$key_lang = ( isset( $this->all_settings[ $tab ][ $change_key ] ) ) ? $this->all_settings[ $tab ][ $change_key ] : $change_key;
			$output .= "<h5> - {$key_lang}</h5>";
			$output .= "<div class='single-change-item'>";

			$prev_value = empty( $change_array[0] ) ? get_nashaat_lang( 'empty' ) : $change_array[0];
			$new_value = empty( $change_array[1] ) ? get_nashaat_lang( 'empty' ) : $change_array[1];

			// Implode prev and next if they are an array
			if ( is_array( $prev_value ) ) {
				$prev_value = implode( ', ', $prev_value );
			}

			if ( is_array( $new_value ) ) {
				$new_value = implode( ', ', $new_value );
			}

			$meta_data = array(
				'prev' => $prev_value,
				'new' => $new_value,
			);
			$output .= $render_class::array_to_html( $meta_data );
			$output .= '</div>';
		endforeach;
		$output .= '</div>';
		return $output;
	}
}