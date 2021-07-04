<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle logging of WooCommerce Orders
 */
class NashaatWCOrders extends NashaatHookBase {

	protected $actions = array(
		array(
			'name' => 'woocommerce_update_order',
			'args' => 3,
			'callback' => 'log_order_changes'
		),
		array(
			'name' => 'transition_post_status',
			'args' => 3,
			'callback' => 'log_order_status'
		),
		array(
			'name' => 'delete_post',
			'args' => 2,
			'callback' => 'log_order_delete'
		),
	);

	protected $filters = array(
		array(
			'name' => 'wp_insert_post_data',
			'args' => 3,
			'callback' => 'get_order_prev_data'
		),
		array(
			'name' => 'nashaat_translations',
			'callback' => 'add_wc_order_translations'
		)
	);

	protected $context = 'wc_order';
	protected $level = NASHAAT_LOG_LEVEL_MEDIUM;

	private $prev_order_data = array();

	/**
	 * Add translation strings specific to WooCommerce order
	 *
	 * @param array $translations Array of translations
	 * @return array Modified $translations array
	 */
	public function add_wc_order_translations( array $translations ) : array {
		$wc_order_translations = array(
			'wc_order' => __( 'WC Order', 'nashaat' ),
			'date_created' => __( 'Date Created', 'nashaat' ),
			'customer_id' => __( 'Customer ID', 'nashaat' ),
			'payment_method' => __( 'Payment Method', 'nashaat' ),
			'customer_note' => __( 'Customer Note', 'nashaat' ),
			'transaction_id' => __( 'Transaction ID', 'nashaat' ),
			'billing_email' => __( 'Billing Email', 'nashaat' ),
			'billing_phone' => __( 'Billing Phone', 'nashaat' ),
			'billing_first_name' => __( 'Billing First Name', 'nashaat' ),
			'billing_last_name' => __( 'Billing Last Name', 'nashaat' ),
			'billing_company' => __( 'Billing Company', 'nashaat' ),
			'billing_address_1' => __( 'Billing Address 1', 'nashaat' ),
			'billing_address_2' => __( 'Billing  Address 2', 'nashaat' ),
			'billing_city' => __( 'Billing City', 'nashaat' ),
			'billing_state' => __( 'Billing State', 'nashaat' ),
			'billing_postcode' => __( 'Billing Postcode', 'nashaat' ),
			'billing_country' => __( 'Billing Country', 'nashaat' ),
			'shipping_first_name' => __( 'Shipping First Name', 'nashaat' ),
			'shipping_last_name' => __( 'Shipping Last Name', 'nashaat' ),
			'shipping_company' => __( 'Shipping Company', 'nashaat' ),
			'shipping_address_1' => __( 'Shipping Address 1', 'nashaat' ),
			'shipping_address_2' => __( 'Shipping Address 2', 'nashaat' ),
			'shipping_city' => __( 'Shipping City', 'nashaat' ),
			'shipping_state' => __( 'Shipping State', 'nashaat' ),
			'shipping_postcode' => __( 'Shipping Postcode', 'nashaat' ),
			'shipping_country' => __( 'Shipping Country', 'nashaat' )
		);
		return array_merge( $translations, $wc_order_translations );
	}

	/**
	 * Filters slashed post data just before it is inserted into the database.
	 * Use it to get order old data
	 *
	 * @param array $data                An array of slashed, sanitized, and processed post data.
	 * @param array $postarr             An array of sanitized (and slashed) but otherwise unmodified post data.
	 * @param array $unsanitized_postarr An array of slashed yet *unsanitized* and unprocessed post data as
	 *                                   originally passed to wp_insert_post().
	 * @return array Returns $data
	 */
	protected function get_order_prev_data( array $data, array $postarr, array $unsanitized_postarr ) {
		$post = get_post( $postarr['ID'] );
		if ( is_null( $post ) || $post->post_type !== 'shop_order' ) {
			return $data;
		}

		$this->prev_order_data = $this->get_order_data( $post );

		return $data;
	}

	/**
	 * Log changes on save post
	 *
	 * @param int    $order_id Order Id
	 * @param object $order Post object
	 * @return void
	 */
	protected function log_order_changes( $order_id, $order ) {
		$order = wc_get_order( $order_id );

		 $prev_data = $this->prev_order_data;
		 $new_data = $this->get_order_data( $order );

		// compare old data with new order data to see if there are any changes
		$diff = array_diff_assoc( $new_data, $prev_data );

		 // if there are no changes don't proceed
		if ( count( $diff ) === 0 ) {
			return;
		}

		$changes = array();
		$diff_keys = array_keys( $diff );
		foreach ( $diff_keys as $diff_key ) {
			$changes[ $diff_key ] = array( $prev_data[ $diff_key ], $new_data[ $diff_key ] );
		}

		$this->event = 'edited';
		$this->log_info = array(
			'order_id' => $order_id,
			'changes' => $changes
		);
	}


	/**
	 * Log order delete action
	 *
	 * @param integer $order_id Order ID
	 * @param WP_Post $post Post object
	 * @return bool|void False if revision or menu item
	 */
	protected function log_order_delete( int $order_id, WP_Post $post ) {

		if ( wp_is_post_revision( $order_id ) || $post->post_type !== 'shop_order' ) {
			return false;
		}

		$this->level = NASHAAT_LOG_LEVEL_HIGH;
		$this->log_info['order_id'] = $order_id;
		$this->event = 'deleted';
	}

	/**
	 * Get order data
	 *
	 * @param WP_Post|int $product Either product object or product id
	 * @return array Order data
	 */
	private function get_order_data( $product ) {
		if ( empty( $product ) ) {
			return array();
		}

		$order = wc_get_order( $product );

		$order_data = array(
			'date_created' => '',
			'customer_id' => $order->get_customer_id(),
			'customer_note' => $order->get_customer_note(),
			'payment_method' => $order->get_payment_method(),
			'transaction_id' => $order->get_transaction_id(),
			'billing_email' => $order->get_billing_email(),
			'billing_phone' => $order->get_billing_phone(),
			'billing_first_name' => $order->get_billing_first_name(),
			'billing_last_name' => $order->get_billing_last_name(),
			'billing_company' => $order->get_billing_company(),
			'billing_address_1' => $order->get_billing_address_1(),
			'billing_address_2' => $order->get_billing_address_2(),
			'billing_city' => $order->get_billing_city(),
			'billing_state' => $order->get_billing_state(),
			'billing_postcode' => $order->get_billing_postcode(),
			'billing_country' => $order->get_billing_country(),
			'shipping_first_name' => $order->get_shipping_first_name(),
			'shipping_last_name' => $order->get_shipping_last_name(),
			'shipping_company' => $order->get_shipping_company(),
			'shipping_address_1' => $order->get_shipping_address_1(),
			'shipping_address_2' => $order->get_shipping_address_2(),
			'shipping_city' => $order->get_shipping_city(),
			'shipping_state' => $order->get_shipping_state(),
			'shipping_postcode' => $order->get_shipping_postcode(),
			'shipping_country' => $order->get_shipping_country()

		);

		$date_created = $order->get_date_created();
		if ( ! empty( $date_created ) ) {
			$order_data['date_created'] = $date_created->getTimestamp();
		}
		return $order_data;
	}


	/**
	 * Handle order status
	 *
	 * @param string  $new_status New post status
	 * @param string  $old_status Old post status
	 * @param WP_Post $post Post object
	 *
	 * @return bool|void False if action is not processed
	 */
	protected function log_order_status( string $new_status, string $old_status, WP_Post $post ) {
		// Skip for all posts except for order post type
		if ( wp_is_post_revision( $post->ID ) || wp_is_post_autosave( $post->ID ) || get_post_type( $post->ID ) !== 'shop_order' ) {
			return false;
		}

		// Go ahead if old status is not the same as the new status
		if ( $old_status === $new_status ) {
			return false;
		}

		$log_info['order_id'] = $post->ID;
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
			'id' => $render_class::maybe_get_post_edit_link( $log_info['order_id'] )
		);
		$output .= $render_class::array_to_html( $order_data );

		if ( in_array( $event, array( 'created', 'trashed', 'restored', 'deleted' ) ) ) {
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
				case 'date_created':
					if ( ! empty( $prev_value ) ) {
						$prev_value = wp_date( get_option( 'date_format' ) . get_option( 'time_format' ), $prev_value );
					}

					if ( ! empty( $new_value ) ) {
						$new_value = wp_date( get_option( 'date_format' ) . get_option( 'time_format' ), $new_value );
					}
					break;
				case 'customer_id':
					if ( ! empty( $prev_value ) ) {
						$user = get_userdata( $prev_value );
						$prev_value = $render_class::maybe_get_user_edit_link( $prev_value, $user->display_name );
					}

					if ( ! empty( $new_value ) ) {
						$user = get_userdata( $new_value );
						$new_value = $render_class::maybe_get_user_edit_link( $new_value, $user->display_name );
					}
					break;
			}

			// Check if prev and new values are empty or not and assign appropriate value
			$prev_value = empty( $prev_value ) ? get_nashaat_lang( 'empty' ) : $prev_value;
			$new_value = empty( $new_value ) ? get_nashaat_lang( 'empty' ) : $new_value;

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
?>