<?php
/**
 * Product Panel Class
 *
 * Adds auction data tab and fields to WooCommerce product edit page.
 *
 * @package    Woo_Live_Auctions
 * @subpackage Woo_Live_Auctions/admin
 * @since      1.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Woo_Auction_Product_Panel
 *
 * Manages auction product data fields in admin.
 *
 * @since 1.0.0
 */
class Woo_Auction_Product_Panel {

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->init_hooks();
	}

	/**
	 * Register hooks
	 *
	 * @since 1.0.0
	 */
	private function init_hooks() {
		// Add auction tab to product data
		add_filter( 'woocommerce_product_data_tabs', array( $this, 'add_auction_tab' ) );

		// Add auction fields
		add_action( 'woocommerce_product_data_panels', array( $this, 'add_auction_fields' ) );

		// Save auction fields
		add_action( 'woocommerce_process_product_meta_auction', array( $this, 'save_auction_fields' ) );

		// Hide unnecessary fields for auction products
		add_filter( 'product_type_options', array( $this, 'hide_product_type_options' ) );
	}

	/**
	 * Add auction tab to product data tabs
	 *
	 * @since 1.0.0
	 * @param array $tabs Existing tabs.
	 * @return array Modified tabs.
	 */
	public function add_auction_tab( $tabs ) {
		$tabs['auction'] = array(
			'label'    => __( 'Auction', 'woo-live-auctions' ),
			'target'   => 'auction_product_data',
			'class'    => array( 'show_if_auction' ),
			'priority' => 25,
		);

		return $tabs;
	}

	/**
	 * Add auction fields to product data panel
	 *
	 * @since 1.0.0
	 */
	public function add_auction_fields() {
		global $post;

		?>
		<div id="auction_product_data" class="panel woocommerce_options_panel hidden">
			<div class="options_group">
				<h3><?php esc_html_e( 'Auction Dates', 'woo-live-auctions' ); ?></h3>

				<?php
				woocommerce_wp_text_input(
					array(
						'id'          => '_auction_start_date',
						'label'       => __( 'Start Date', 'woo-live-auctions' ),
						'placeholder' => 'YYYY-MM-DD HH:MM:SS',
						'desc_tip'    => true,
						'description' => __( 'Date and time when the auction starts', 'woo-live-auctions' ),
						'class'       => 'woo-auction-datetime-picker',
						'type'        => 'text',
					)
				);

				woocommerce_wp_text_input(
					array(
						'id'          => '_auction_end_date',
						'label'       => __( 'End Date', 'woo-live-auctions' ),
						'placeholder' => 'YYYY-MM-DD HH:MM:SS',
						'desc_tip'    => true,
						'description' => __( 'Date and time when the auction ends', 'woo-live-auctions' ),
						'class'       => 'woo-auction-datetime-picker',
						'type'        => 'text',
					)
				);
				?>
			</div>

			<div class="options_group">
				<h3><?php esc_html_e( 'Pricing', 'woo-live-auctions' ); ?></h3>

				<?php
				woocommerce_wp_text_input(
					array(
						'id'          => '_auction_start_price',
						'label'       => __( 'Start Price', 'woo-live-auctions' ) . ' (' . get_woocommerce_currency_symbol() . ')',
						'placeholder' => '0.00',
						'desc_tip'    => true,
						'description' => __( 'Starting bid price for the auction', 'woo-live-auctions' ),
						'type'        => 'number',
						'custom_attributes' => array(
							'step' => '0.01',
							'min'  => '0',
						),
					)
				);

				woocommerce_wp_text_input(
					array(
						'id'          => '_auction_bid_increment',
						'label'       => __( 'Bid Increment', 'woo-live-auctions' ) . ' (' . get_woocommerce_currency_symbol() . ')',
						'placeholder' => '1.00',
						'desc_tip'    => true,
						'description' => __( 'Minimum amount to increase each bid', 'woo-live-auctions' ),
						'type'        => 'number',
						'custom_attributes' => array(
							'step' => '0.01',
							'min'  => '0.01',
						),
					)
				);

				woocommerce_wp_text_input(
					array(
						'id'          => '_auction_reserve_price',
						'label'       => __( 'Reserve Price', 'woo-live-auctions' ) . ' (' . get_woocommerce_currency_symbol() . ')',
						'placeholder' => __( 'Optional', 'woo-live-auctions' ),
						'desc_tip'    => true,
						'description' => __( 'Minimum price for the auction to be valid (optional)', 'woo-live-auctions' ),
						'type'        => 'number',
						'custom_attributes' => array(
							'step' => '0.01',
							'min'  => '0',
						),
					)
				);

				woocommerce_wp_text_input(
					array(
						'id'          => '_auction_buy_now_price',
						'label'       => __( 'Buy Now Price', 'woo-live-auctions' ) . ' (' . get_woocommerce_currency_symbol() . ')',
						'placeholder' => __( 'Optional', 'woo-live-auctions' ),
						'desc_tip'    => true,
						'description' => __( 'Price to instantly win the auction (disabled after first bid)', 'woo-live-auctions' ),
						'type'        => 'number',
						'custom_attributes' => array(
							'step' => '0.01',
							'min'  => '0',
						),
					)
				);
				?>
			</div>

			<div class="options_group">
				<h3><?php esc_html_e( 'Current Status', 'woo-live-auctions' ); ?></h3>

				<?php
				$current_bid = get_post_meta( $post->ID, '_auction_current_bid', true );
				$bid_count = get_post_meta( $post->ID, '_auction_bid_count', true );
				$auction_status = get_post_meta( $post->ID, '_auction_status', true );

				?>
				<p class="form-field">
					<label><?php esc_html_e( 'Auction Status:', 'woo-live-auctions' ); ?></label>
					<strong><?php echo esc_html( ucfirst( $auction_status ? $auction_status : 'Not Started' ) ); ?></strong>
				</p>

				<p class="form-field">
					<label><?php esc_html_e( 'Current Bid:', 'woo-live-auctions' ); ?></label>
					<strong><?php echo $current_bid ? wp_kses_post( wc_price( $current_bid ) ) : esc_html__( 'No bids yet', 'woo-live-auctions' ); ?></strong>
				</p>

				<p class="form-field">
					<label><?php esc_html_e( 'Total Bids:', 'woo-live-auctions' ); ?></label>
					<strong><?php echo esc_html( $bid_count ? $bid_count : '0' ); ?></strong>
				</p>

				<?php if ( $bid_count > 0 ) : ?>
					<p class="form-field">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=woo-auctions&auction_id=' . $post->ID ) ); ?>" class="button">
							<?php esc_html_e( 'View Bid History', 'woo-live-auctions' ); ?>
						</a>
					</p>
				<?php endif; ?>
			</div>

			<div class="options_group">
				<p class="woo-auction-help-text">
					<strong><?php esc_html_e( 'Need help?', 'woo-live-auctions' ); ?></strong><br>
					<?php
					printf(
						/* translators: %s: documentation URL */
						wp_kses_post( __( 'Check out our <a href="https://cybercraftit.com/woo-live-auction-pro/" target="_new">documentation</a> for detailed instructions on setting up auctions.', 'woo-live-auctions' ) ),
						'#'
					);
					?>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Save auction fields
	 *
	 * @since 1.0.0
	 * @param int $post_id Product ID.
	 */
	public function save_auction_fields( $post_id ) {
		// Verify nonce
		if ( ! isset( $_POST['woocommerce_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce_meta_nonce'] ) ), 'woocommerce_save_data' ) ) {
			return;
		}

		// Check user permissions
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Save auction dates (normalize to site timezone)
		$start_date_raw = isset( $_POST['_auction_start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['_auction_start_date'] ) ) : '';
		$end_date_raw   = isset( $_POST['_auction_end_date'] ) ? sanitize_text_field( wp_unslash( $_POST['_auction_end_date'] ) ) : '';

		$start_date = $this->normalize_datetime_input( $start_date_raw );
		$end_date   = $this->normalize_datetime_input( $end_date_raw );

		update_post_meta( $post_id, '_auction_start_date', $start_date );
		update_post_meta( $post_id, '_auction_end_date', $end_date );

		// Save pricing fields
		$start_price = isset( $_POST['_auction_start_price'] ) ? wc_format_decimal( sanitize_text_field( wp_unslash( $_POST['_auction_start_price'] ) ) ) : '';
		$bid_increment = isset( $_POST['_auction_bid_increment'] ) ? wc_format_decimal( sanitize_text_field( wp_unslash( $_POST['_auction_bid_increment'] ) ) ) : '';
		$reserve_price = isset( $_POST['_auction_reserve_price'] ) ? wc_format_decimal( sanitize_text_field( wp_unslash( $_POST['_auction_reserve_price'] ) ) ) : '';
		$buy_now_price = isset( $_POST['_auction_buy_now_price'] ) ? wc_format_decimal( sanitize_text_field( wp_unslash( $_POST['_auction_buy_now_price'] ) ) ) : '';

		update_post_meta( $post_id, '_auction_start_price', $start_price );
		update_post_meta( $post_id, '_auction_bid_increment', $bid_increment );
		update_post_meta( $post_id, '_auction_reserve_price', $reserve_price );
		update_post_meta( $post_id, '_auction_buy_now_price', $buy_now_price );

		// Initialize auction status if new
		$auction_status = get_post_meta( $post_id, '_auction_status', true );
		if ( ! $auction_status ) {
			$current_timestamp = current_time( 'timestamp' );
			$start_timestamp   = $this->datetime_to_timestamp( $start_date );
			$end_timestamp     = $this->datetime_to_timestamp( $end_date );

			if ( $start_timestamp && $start_timestamp > $current_timestamp ) {
				update_post_meta( $post_id, '_auction_status', 'future' );
			} elseif ( $start_timestamp && $start_timestamp <= $current_timestamp && $end_timestamp && $end_timestamp > $current_timestamp ) {
				update_post_meta( $post_id, '_auction_status', 'live' );
			}
		}

		// Initialize bid count if not set
		$bid_count = get_post_meta( $post_id, '_auction_bid_count', true );
		if ( '' === $bid_count ) {
			update_post_meta( $post_id, '_auction_bid_count', 0 );
		}

		// Set initial current bid to start price if not set
		$current_bid = get_post_meta( $post_id, '_auction_current_bid', true );
		if ( '' === $current_bid && $start_price ) {
			update_post_meta( $post_id, '_auction_current_bid', $start_price );
		}
	}

	/**
	 * Hide unnecessary product type options for auction products
	 *
	 * @since 1.0.0
	 * @param array $options Product type options.
	 * @return array Modified options.
	 */
	public function hide_product_type_options( $options ) {
		// Hide virtual and downloadable for auction products
		$options['virtual']['wrapper_class'] .= ' hide_if_auction';
		$options['downloadable']['wrapper_class'] .= ' hide_if_auction';

		return $options;
	}

	/**
	 * Normalize datetime input to canonical MySQL format in site timezone.
	 *
	 * @since 1.0.0
	 * @param string $datetime Raw datetime string from form.
	 * @return string Normalized datetime string (Y-m-d H:i:s) or empty string.
	 */
	private function normalize_datetime_input( $datetime ) {
		if ( empty( $datetime ) ) {
			return '';
		}

		$datetime = trim( $datetime );

		// Split out timezone offset if provided (e.g., +06:00)
		$parts = preg_split( '/\s+/', $datetime );
		$offset = null;

		if ( count( $parts ) >= 2 ) {
			// Check last token for offset pattern
			$last = end( $parts );
			if ( preg_match( '/^[+-]\d{2}:?\d{2}$/', $last ) ) {
				$offset = $last;
				array_pop( $parts );
			}
		}

		$base_string = implode( ' ', $parts );

		try {
			if ( $offset ) {
				$timezone = new DateTimeZone( $offset );
			} else {
				$timezone = function_exists( 'wp_timezone' ) ? wp_timezone() : new DateTimeZone( wp_timezone_string() );
			}

			$dt_site = new DateTime( $base_string, $timezone );
			if ( ! $offset ) {
				return $dt_site->format( 'Y-m-d H:i:s' );
			}

			$site_timezone = function_exists( 'wp_timezone' ) ? wp_timezone() : new DateTimeZone( wp_timezone_string() );
			$dt_site->setTimezone( $site_timezone );
			return $dt_site->format( 'Y-m-d H:i:s' );
		} catch ( Exception $e ) {
			// Fallback to strtotime interpretation
			$timestamp = strtotime( $datetime );
			if ( ! $timestamp ) {
				return '';
			}
			return wp_date( 'Y-m-d H:i:s', $timestamp );
		}
	}

	/**
	 * Convert normalized datetime string to timestamp.
	 *
	 * @since 1.0.0
	 * @param string $datetime Normalized datetime string.
	 * @return int|false Timestamp or false on failure.
	 */
	private function datetime_to_timestamp( $datetime ) {
		if ( empty( $datetime ) ) {
			return false;
		}

		try {
			$timezone = function_exists( 'wp_timezone' ) ? wp_timezone() : new DateTimeZone( wp_timezone_string() );
			$dt = new DateTime( $datetime, $timezone );
			return $dt->getTimestamp();
		} catch ( Exception $e ) {
			return strtotime( $datetime );
		}
	}
}
