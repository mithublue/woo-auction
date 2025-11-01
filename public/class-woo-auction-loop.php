<?php
/**
 * Loop Handler Class
 *
 * Modifies WooCommerce product loop for auction products.
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
 * Class Woo_Auction_Loop
 *
 * Handles auction product display in shop loops.
 *
 * @since 1.0.0
 */
class Woo_Auction_Loop {

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
		// Add auction badge
		add_action( 'woocommerce_before_shop_loop_item_title', array( $this, 'add_auction_badge' ), 15 );

		// Modify price display
		add_filter( 'woocommerce_get_price_html', array( $this, 'modify_price_html' ), 10, 2 );

		// Add countdown timer
		add_action( 'woocommerce_after_shop_loop_item_title', array( $this, 'add_countdown_timer' ), 15 );
	}

	/**
	 * Add auction badge to product thumbnails
	 *
	 * @since 1.0.0
	 */
	public function add_auction_badge() {
		global $product;

		if ( ! $product || 'auction' !== $product->get_type() ) {
			return;
		}

		if ( 'yes' !== get_option( 'woo_auction_show_auction_badge', 'yes' ) ) {
			return;
		}

		$badge_text = get_option( 'woo_auction_badge_text', __( 'Auction', 'woo-live-auctions' ) );
		$status = $product->get_auction_status();

		?>
		<span class="woo-auction-badge woo-auction-badge-<?php echo esc_attr( $status ); ?>">
			<?php echo esc_html( $badge_text ); ?>
		</span>
		<?php
	}

	/**
	 * Modify price HTML for auction products
	 *
	 * @since 1.0.0
	 * @param string     $price_html Price HTML.
	 * @param WC_Product $product    Product object.
	 * @return string Modified price HTML.
	 */
	public function modify_price_html( $price_html, $product ) {
		if ( 'auction' !== $product->get_type() ) {
			return $price_html;
		}

		$current_bid = $product->get_current_bid();
		$bid_count = $product->get_bid_count();

		if ( $product->is_auction_ended() ) {
			if ( $product->has_winner() ) {
				$price_html = '<span class="woo-auction-price woo-auction-ended">' .
				              '<span class="label">' . esc_html__( 'Sold for:', 'woo-live-auctions' ) . '</span> ' .
				              wc_price( $current_bid ) .
				              '</span>';
			} else {
				$price_html = '<span class="woo-auction-price woo-auction-ended">' .
				              esc_html__( 'Auction Ended', 'woo-live-auctions' ) .
				              '</span>';
			}
		} else {
			$label = $bid_count > 0 ? __( 'Current Bid:', 'woo-live-auctions' ) : __( 'Starting Bid:', 'woo-live-auctions' );
			
			$price_html = '<span class="woo-auction-price woo-auction-live">' .
			              '<span class="label">' . esc_html( $label ) . '</span> ' .
			              wc_price( $current_bid );
			
			if ( $bid_count > 0 ) {
				$price_html .= '<span class="bid-count"> (' . 
				               sprintf(
				                   /* translators: %d: bid count */
				                   esc_html( _n( '%d bid', '%d bids', $bid_count, 'woo-live-auctions' ) ),
				                   $bid_count
				               ) . 
				               ')</span>';
			}
			
			$price_html .= '</span>';
		}

		return $price_html;
	}

	/**
	 * Add countdown timer to loop items
	 *
	 * @since 1.0.0
	 */
	public function add_countdown_timer() {
		global $product;

		if ( ! $product || 'auction' !== $product->get_type() ) {
			return;
		}

		if ( ! $product->is_auction_live() ) {
			return;
		}

		$time_remaining = $product->get_time_remaining();

		?>
		<div class="woo-auction-countdown-loop" data-auction-id="<?php echo esc_attr( $product->get_id() ); ?>" data-end-time="<?php echo esc_attr( $time_remaining ); ?>">
			<span class="countdown-label"><?php esc_html_e( 'Time left:', 'woo-live-auctions' ); ?></span>
			<span class="countdown-value"><?php echo esc_html( $product->get_time_remaining_formatted() ); ?></span>
		</div>
		<?php
	}
}
