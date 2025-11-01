<?php
/**
 * Auction Product Class
 *
 * Extends WC_Product to create the auction product type.
 *
 * @package    Woo_Live_Auctions
 * @subpackage Woo_Live_Auctions/includes
 * @since      1.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class WC_Product_Auction
 *
 * Custom product type for auctions that extends WooCommerce product functionality.
 *
 * @since 1.0.0
 */
class WC_Product_Auction extends WC_Product {

	/**
	 * Product type
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $product_type = 'auction';

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 * @param mixed $product Product ID or object.
	 */
	public function __construct( $product = 0 ) {
		$this->product_type = 'auction';
		parent::__construct( $product );
	}

	/**
	 * Get internal type
	 *
	 * @since 1.0.0
	 * @return string Product type.
	 */
	public function get_type() {
		return 'auction';
	}

	/**
	 * Check if product is purchasable
	 *
	 * @since 1.0.0
	 * @return bool True if purchasable, false otherwise.
	 */
	public function is_purchasable() {
		return $this->is_auction_ended() && $this->has_winner();
	}

	/**
	 * Check if product is in stock
	 *
	 * @since 1.0.0
	 * @return bool True if in stock, false otherwise.
	 */
	public function is_in_stock() {
		return $this->is_auction_live();
	}

	/**
	 * Get auction start date
	 *
	 * @since 1.0.0
	 * @return string|null Start date timestamp or null.
	 */
	public function get_auction_start_date() {
		return get_post_meta( $this->get_id(), '_auction_start_date', true );
	}

	/**
	 * Get auction end date
	 *
	 * @since 1.0.0
	 * @return string|null End date timestamp or null.
	 */
	public function get_auction_end_date() {
		return get_post_meta( $this->get_id(), '_auction_end_date', true );
	}

	/**
	 * Get auction start price
	 *
	 * @since 1.0.0
	 * @return float Start price.
	 */
	public function get_auction_start_price() {
		return floatval( get_post_meta( $this->get_id(), '_auction_start_price', true ) );
	}

	/**
	 * Get bid increment
	 *
	 * @since 1.0.0
	 * @return float Bid increment amount.
	 */
	public function get_bid_increment() {
		return floatval( get_post_meta( $this->get_id(), '_auction_bid_increment', true ) );
	}

	/**
	 * Get reserve price
	 *
	 * @since 1.0.0
	 * @return float|null Reserve price or null if not set.
	 */
	public function get_reserve_price() {
		$reserve = get_post_meta( $this->get_id(), '_auction_reserve_price', true );
		return $reserve ? floatval( $reserve ) : null;
	}

	/**
	 * Get buy now price
	 *
	 * @since 1.0.0
	 * @return float|null Buy now price or null if not available.
	 */
	public function get_buy_now_price() {
		$buy_now = get_post_meta( $this->get_id(), '_auction_buy_now_price', true );
		return $buy_now ? floatval( $buy_now ) : null;
	}

	/**
	 * Get current bid
	 *
	 * @since 1.0.0
	 * @return float Current highest bid.
	 */
	public function get_current_bid() {
		$current_bid = get_post_meta( $this->get_id(), '_auction_current_bid', true );
		return $current_bid ? floatval( $current_bid ) : $this->get_auction_start_price();
	}

	/**
	 * Get current bidder user ID
	 *
	 * @since 1.0.0
	 * @return int|null Current bidder user ID or null.
	 */
	public function get_current_bidder() {
		$bidder = get_post_meta( $this->get_id(), '_auction_current_bidder', true );
		return $bidder ? absint( $bidder ) : null;
	}

	/**
	 * Get bid count
	 *
	 * @since 1.0.0
	 * @return int Total number of bids.
	 */
	public function get_bid_count() {
		return absint( get_post_meta( $this->get_id(), '_auction_bid_count', true ) );
	}

	/**
	 * Get auction status
	 *
	 * @since 1.0.0
	 * @return string Auction status: 'future', 'live', or 'ended'.
	 */
	public function get_auction_status() {
		$status = get_post_meta( $this->get_id(), '_auction_status', true );
		
		if ( ! $status ) {
			// Calculate status if not set
			$status = $this->calculate_auction_status();
			update_post_meta( $this->get_id(), '_auction_status', $status );
		}
		
		return $status;
	}

	/**
	 * Get auction winner user ID
	 *
	 * @since 1.0.0
	 * @return int|null Winner user ID or null.
	 */
	public function get_winner() {
		$winner = get_post_meta( $this->get_id(), '_auction_winner', true );
		return $winner ? absint( $winner ) : null;
	}

	/**
	 * Check if auction has started
	 *
	 * @since 1.0.0
	 * @return bool True if started, false otherwise.
	 */
	public function has_auction_started() {
		$start_date = $this->get_auction_start_date();
		return $start_date && strtotime( $start_date ) <= current_time( 'timestamp' );
	}

	/**
	 * Check if auction has ended
	 *
	 * @since 1.0.0
	 * @return bool True if ended, false otherwise.
	 */
	public function is_auction_ended() {
		$end_date = $this->get_auction_end_date();
		return $end_date && strtotime( $end_date ) <= current_time( 'timestamp' );
	}

	/**
	 * Check if auction is currently live
	 *
	 * @since 1.0.0
	 * @return bool True if live, false otherwise.
	 */
	public function is_auction_live() {
		return $this->has_auction_started() && ! $this->is_auction_ended();
	}

	/**
	 * Check if auction has a winner
	 *
	 * @since 1.0.0
	 * @return bool True if has winner, false otherwise.
	 */
	public function has_winner() {
		return null !== $this->get_winner();
	}

	/**
	 * Check if reserve price is met
	 *
	 * @since 1.0.0
	 * @return bool True if reserve met or no reserve set, false otherwise.
	 */
	public function is_reserve_met() {
		$reserve = $this->get_reserve_price();
		
		if ( null === $reserve ) {
			return true; // No reserve price set
		}
		
		return $this->get_current_bid() >= $reserve;
	}

	/**
	 * Check if buy now is available
	 *
	 * @since 1.0.0
	 * @return bool True if buy now available, false otherwise.
	 */
	public function is_buy_now_available() {
		$buy_now = $this->get_buy_now_price();
		
		if ( null === $buy_now ) {
			return false;
		}
		
		// Buy now disabled after first bid or reserve met
		if ( $this->get_bid_count() > 0 || $this->is_reserve_met() ) {
			return false;
		}
		
		return $this->is_auction_live();
	}

	/**
	 * Get time remaining in seconds
	 *
	 * @since 1.0.0
	 * @return int Seconds remaining or 0 if ended.
	 */
	public function get_time_remaining() {
		if ( $this->is_auction_ended() ) {
			return 0;
		}
		
		$end_date = $this->get_auction_end_date();
		if ( ! $end_date ) {
			return 0;
		}
		
		$remaining = strtotime( $end_date ) - current_time( 'timestamp' );
		return max( 0, $remaining );
	}

	/**
	 * Get formatted time remaining
	 *
	 * @since 1.0.0
	 * @return string Formatted time remaining.
	 */
	public function get_time_remaining_formatted() {
		$seconds = $this->get_time_remaining();
		
		if ( $seconds <= 0 ) {
			return __( 'Auction ended', 'woo-live-auctions' );
		}
		
		$days    = floor( $seconds / DAY_IN_SECONDS );
		$hours   = floor( ( $seconds % DAY_IN_SECONDS ) / HOUR_IN_SECONDS );
		$minutes = floor( ( $seconds % HOUR_IN_SECONDS ) / MINUTE_IN_SECONDS );
		$secs    = $seconds % MINUTE_IN_SECONDS;
		
		$parts = array();
		
		if ( $days > 0 ) {
			/* translators: %d: number of days */
			$parts[] = sprintf( _n( '%d day', '%d days', $days, 'woo-live-auctions' ), $days );
		}
		
		if ( $hours > 0 || $days > 0 ) {
			/* translators: %d: number of hours */
			$parts[] = sprintf( _n( '%d hour', '%d hours', $hours, 'woo-live-auctions' ), $hours );
		}
		
		if ( $days === 0 ) {
			/* translators: %d: number of minutes */
			$parts[] = sprintf( _n( '%d min', '%d mins', $minutes, 'woo-live-auctions' ), $minutes );
		}
		
		if ( $days === 0 && $hours === 0 ) {
			/* translators: %d: number of seconds */
			$parts[] = sprintf( _n( '%d sec', '%d secs', $secs, 'woo-live-auctions' ), $secs );
		}
		
		return implode( ' ', $parts );
	}

	/**
	 * Calculate minimum next bid amount
	 *
	 * @since 1.0.0
	 * @return float Minimum next bid.
	 */
	public function get_min_next_bid() {
		return $this->get_current_bid() + $this->get_bid_increment();
	}

	/**
	 * Check if user is current high bidder
	 *
	 * @since 1.0.0
	 * @param int $user_id User ID to check.
	 * @return bool True if user is high bidder, false otherwise.
	 */
	public function is_user_high_bidder( $user_id ) {
		return absint( $user_id ) === $this->get_current_bidder();
	}

	/**
	 * Calculate auction status based on dates
	 *
	 * @since 1.0.0
	 * @return string Status: 'future', 'live', or 'ended'.
	 */
	private function calculate_auction_status() {
		if ( ! $this->has_auction_started() ) {
			return 'future';
		}
		
		if ( $this->is_auction_ended() ) {
			return 'ended';
		}
		
		return 'live';
	}

	/**
	 * Update current bid and bidder
	 *
	 * @since 1.0.0
	 * @param float $bid     New bid amount.
	 * @param int   $user_id User ID of bidder.
	 */
	public function update_current_bid( $bid, $user_id ) {
		update_post_meta( $this->get_id(), '_auction_current_bid', floatval( $bid ) );
		update_post_meta( $this->get_id(), '_auction_current_bidder', absint( $user_id ) );
		
		// Increment bid count
		$count = $this->get_bid_count();
		update_post_meta( $this->get_id(), '_auction_bid_count', $count + 1 );
		
		// Remove buy now if reserve met or first bid placed
		if ( $this->is_reserve_met() || $count === 0 ) {
			delete_post_meta( $this->get_id(), '_auction_buy_now_price' );
		}
	}

	/**
	 * Set auction winner
	 *
	 * @since 1.0.0
	 * @param int $user_id Winner user ID.
	 */
	public function set_winner( $user_id ) {
		update_post_meta( $this->get_id(), '_auction_winner', absint( $user_id ) );
		update_post_meta( $this->get_id(), '_auction_status', 'ended' );
	}

	/**
	 * Get product price (returns current bid for display)
	 *
	 * @since 1.0.0
	 * @param string $context Context: 'view' or 'edit'.
	 * @return string Current bid price.
	 */
	public function get_price( $context = 'view' ) {
		return $this->get_current_bid();
	}

	/**
	 * Get regular price (returns start price)
	 *
	 * @since 1.0.0
	 * @param string $context Context: 'view' or 'edit'.
	 * @return string Start price.
	 */
	public function get_regular_price( $context = 'view' ) {
		return $this->get_auction_start_price();
	}

	/**
	 * Get sale price (not applicable for auctions)
	 *
	 * @since 1.0.0
	 * @param string $context Context: 'view' or 'edit'.
	 * @return string Empty string.
	 */
	public function get_sale_price( $context = 'view' ) {
		return '';
	}

	/**
	 * Check if product is on sale (not applicable for auctions)
	 *
	 * @since 1.0.0
	 * @param string $context Context: 'view' or 'edit'.
	 * @return bool Always false.
	 */
	public function is_on_sale( $context = 'view' ) {
		return false;
	}
}
