<?php
/**
 * Product Handler Class
 *
 * Handles auction product single page display and bidding box.
 *
 * @package    Woo_Live_Auctions
 * @subpackage Woo_Live_Auctions/public
 * @since      1.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Woo_Auction_Product_Handler
 *
 * Manages auction product single page functionality.
 *
 * @since 1.0.0
 */
class Woo_Auction_Product_Handler {

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
		// Ensure add to cart is only replaced for auction products
		add_action( 'woocommerce_before_single_product', array( $this, 'setup_single_product_hooks' ) );
		add_action( 'woocommerce_single_product_summary', array( $this, 'display_auction_bidding_box' ), 30 );

		// Add auction info after short description
		add_action( 'woocommerce_single_product_summary', array( $this, 'display_auction_info' ), 25 );

		// Add bid history after product tabs
		add_action( 'woocommerce_after_single_product_summary', array( $this, 'display_bid_history' ), 15 );
	}

	/**
	 * Prepare single product hooks based on product type.
	 *
	 * @since 1.0.0
	 */
	public function setup_single_product_hooks() {
		global $product;

		if ( ! $product ) {
			return;
		}

		if ( 'auction' === $product->get_type() ) {
			remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
		} else {
			// Make sure non-auction products retain the default add to cart button
			add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
		}
	}

	/**
	 * Display auction bidding box
	 *
	 * @since 1.0.0
	 */
	public function display_auction_bidding_box() {
		global $product;

		if ( ! $product || 'auction' !== $product->get_type() ) {
			return;
		}

		// Load template
		wc_get_template(
			'single-product/auction.php',
			array( 'product' => $product ),
			'',
			WOO_AUCTION_PATH . 'templates/'
		);
	}

	/**
	 * Display auction information
	 *
	 * @since 1.0.0
	 */
	public function display_auction_info() {
		global $product;

		if ( ! $product || 'auction' !== $product->get_type() ) {
			return;
		}

		?>
		<div class="woo-auction-info">
			<?php if ( $product->get_reserve_price() && ! $product->is_reserve_met() ) : ?>
				<p class="woo-auction-reserve-notice">
					<span class="dashicons dashicons-info"></span>
					<?php esc_html_e( 'This auction has a reserve price that has not been met.', 'woo-live-auctions' ); ?>
				</p>
			<?php elseif ( $product->get_reserve_price() && $product->is_reserve_met() ) : ?>
				<p class="woo-auction-reserve-met">
					<span class="dashicons dashicons-yes"></span>
					<?php esc_html_e( 'Reserve price has been met!', 'woo-live-auctions' ); ?>
				</p>
			<?php endif; ?>

			<?php if ( $product->is_auction_live() ) : ?>
				<div class="woo-auction-status-live">
					<span class="status-indicator"></span>
					<?php esc_html_e( 'Auction is live!', 'woo-live-auctions' ); ?>
				</div>
			<?php elseif ( ! $product->has_auction_started() ) : ?>
				<div class="woo-auction-status-future">
					<?php
					printf(
						/* translators: %s: start date */
						esc_html__( 'Auction starts on %s', 'woo-live-auctions' ),
						'<strong>' . esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $product->get_auction_start_date() ) ) ) . '</strong>'
					);
					?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Display bid history
	 *
	 * @since 1.0.0
	 */
	public function display_bid_history() {
		global $product;

		if ( ! $product || 'auction' !== $product->get_type() ) {
			return;
		}

		if ( 'yes' !== get_option( 'woo_auction_show_bid_history', 'yes' ) ) {
			return;
		}

		$db = woo_auction()->get_db();
		$limit = absint( get_option( 'woo_auction_bid_history_limit', 10 ) );
		$bid_history = $db->get_bid_history( $product->get_id(), $limit );

		if ( empty( $bid_history ) ) {
			return;
		}

		?>
		<div class="woo-auction-bid-history">
			<h2><?php esc_html_e( 'Bid History', 'woo-live-auctions' ); ?></h2>
			<div class="woo-auction-bid-history-scrollable">
				<table class="woo-auction-bid-history-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Bidder', 'woo-live-auctions' ); ?></th>
							<th><?php esc_html_e( 'Bid Amount', 'woo-live-auctions' ); ?></th>
							<th><?php esc_html_e( 'Date', 'woo-live-auctions' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $bid_history as $bid ) : ?>
							<?php
							$bidder = get_userdata( $bid->user_id );
							$bidder_name = $bidder ? $bidder->display_name : __( 'Anonymous', 'woo-live-auctions' );
							?>
							<tr class="<?php echo $bid->is_proxy ? 'proxy-bid' : 'manual-bid'; ?>">
								<td>
									<?php echo esc_html( $bidder_name ); ?>
									<?php if ( $bid->is_proxy ) : ?>
										<span class="proxy-badge" title="<?php esc_attr_e( 'Automatic bid', 'woo-live-auctions' ); ?>">
											<?php esc_html_e( 'Auto', 'woo-live-auctions' ); ?>
										</span>
									<?php endif; ?>
								</td>
								<td><?php echo wp_kses_post( wc_price( $bid->bid ) ); ?></td>
								<td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $bid->date ) ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
		<?php
	}
}
