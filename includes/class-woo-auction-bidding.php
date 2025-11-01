<?php
/**
 * Bidding Engine Class
 *
 * Core bidding logic including the Limited Proxy Bidding system (freemium feature).
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
 * Class Woo_Auction_Bidding
 *
 * Handles all bidding operations including manual bids and limited proxy bidding.
 *
 * @since 1.0.0
 */
class Woo_Auction_Bidding {

	/**
	 * Database helper instance
	 *
	 * @since 1.0.0
	 * @var Woo_Auction_DB
	 */
	private $db;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->db = woo_auction()->get_db();
	}

	/**
	 * Place a bid on an auction
	 *
	 * This is the main entry point for all bidding operations.
	 *
	 * @since 1.0.0
	 * @param int   $auction_id Auction product ID.
	 * @param int   $user_id    User ID placing the bid.
	 * @param float $bid_amount Bid amount.
	 * @param bool  $is_proxy   Whether this is a proxy (max) bid.
	 * @return array Result array with 'success' boolean and 'message' string.
	 */
	public function place_bid( $auction_id, $user_id, $bid_amount, $is_proxy = false ) {
		// Validate inputs
		$auction_id = absint( $auction_id );
		$user_id    = absint( $user_id );
		$bid_amount = floatval( $bid_amount );

		// Get auction product
		$product = wc_get_product( $auction_id );

		if ( ! $product || 'auction' !== $product->get_type() ) {
			return array(
				'success' => false,
				'message' => __( 'Invalid auction product.', 'woo-live-auctions' ),
			);
		}

		// Validate auction status
		$validation = $this->validate_auction_status( $product );
		if ( ! $validation['success'] ) {
			return $validation;
		}

		// Validate user
		$user_validation = $this->validate_user( $user_id, $product );
		if ( ! $user_validation['success'] ) {
			return $user_validation;
		}

		// Validate bid amount
		$amount_validation = $this->validate_bid_amount( $bid_amount, $product );
		if ( ! $amount_validation['success'] ) {
			return $amount_validation;
		}

		// Process the bid based on type
		if ( $is_proxy ) {
			return $this->process_proxy_bid( $product, $user_id, $bid_amount );
		} else {
			return $this->process_manual_bid( $product, $user_id, $bid_amount );
		}
	}

	/**
	 * Validate auction status
	 *
	 * @since 1.0.0
	 * @param WC_Product_Auction $product Auction product object.
	 * @return array Validation result.
	 */
	private function validate_auction_status( $product ) {
		if ( ! $product->is_auction_live() ) {
			if ( ! $product->has_auction_started() ) {
				return array(
					'success' => false,
					'message' => __( 'This auction has not started yet.', 'woo-live-auctions' ),
				);
			} else {
				return array(
					'success' => false,
					'message' => __( 'This auction has ended.', 'woo-live-auctions' ),
				);
			}
		}

		return array( 'success' => true );
	}

	/**
	 * Validate user eligibility to bid
	 *
	 * @since 1.0.0
	 * @param int                $user_id User ID.
	 * @param WC_Product_Auction $product Auction product object.
	 * @return array Validation result.
	 */
	private function validate_user( $user_id, $product ) {
		// Check if user is logged in
		if ( ! $user_id ) {
			$allow_guest = get_option( 'woo_auction_allow_guest_bidding', 'no' );
			if ( 'yes' !== $allow_guest ) {
				return array(
					'success' => false,
					'message' => __( 'You must be logged in to place a bid.', 'woo-live-auctions' ),
				);
			}
		}

		// Check if user is current high bidder
		if ( $product->is_user_high_bidder( $user_id ) ) {
			return array(
				'success' => false,
				'message' => __( 'You are already the highest bidder.', 'woo-live-auctions' ),
			);
		}

		return array( 'success' => true );
	}

	/**
	 * Validate bid amount
	 *
	 * @since 1.0.0
	 * @param float              $bid_amount Bid amount.
	 * @param WC_Product_Auction $product    Auction product object.
	 * @return array Validation result.
	 */
	private function validate_bid_amount( $bid_amount, $product ) {
		$min_bid = $product->get_min_next_bid();

		if ( $bid_amount < $min_bid ) {
			return array(
				'success' => false,
				/* translators: %s: minimum bid amount */
				'message' => sprintf( __( 'Your bid must be at least %s.', 'woo-live-auctions' ), wc_price( $min_bid ) ),
			);
		}

		return array( 'success' => true );
	}

	/**
	 * Process a manual (non-proxy) bid
	 *
	 * @since 1.0.0
	 * @param WC_Product_Auction $product    Auction product object.
	 * @param int                $user_id    User ID.
	 * @param float              $bid_amount Bid amount.
	 * @return array Result array.
	 */
	private function process_manual_bid( $product, $user_id, $bid_amount ) {
		$auction_id = $product->get_id();

		// Check for active proxy bids
		$active_proxies = $this->db->get_active_proxy_bids( $auction_id );

		if ( ! empty( $active_proxies ) ) {
			// There's an active proxy bid - it will auto-outbid this manual bid
			return $this->handle_proxy_vs_manual( $product, $user_id, $bid_amount, $active_proxies );
		}

		// No proxy bids - place manual bid normally
		$this->db->insert_bid_log( $auction_id, $user_id, $bid_amount, false );
		$product->update_current_bid( $bid_amount, $user_id );

		// Send outbid email to previous bidder
		$this->send_outbid_notifications( $product, $user_id );

		do_action( 'woo_auction_bid_placed', $auction_id, $user_id, $bid_amount, false );

		return array(
			'success' => true,
			'message' => __( 'Your bid has been placed successfully!', 'woo-live-auctions' ),
		);
	}

	/**
	 * Process a proxy (max) bid
	 *
	 * @since 1.0.0
	 * @param WC_Product_Auction $product    Auction product object.
	 * @param int                $user_id    User ID.
	 * @param float              $max_bid    Maximum bid amount.
	 * @return array Result array.
	 */
	private function process_proxy_bid( $product, $user_id, $max_bid ) {
		$auction_id = $product->get_id();

		// Check if proxy bidding is enabled
		if ( 'yes' !== get_option( 'woo_auction_enable_proxy_bidding', 'yes' ) ) {
			return array(
				'success' => false,
				'message' => __( 'Proxy bidding is not enabled.', 'woo-live-auctions' ),
			);
		}

		// Validate max bid is higher than current bid
		if ( $max_bid <= $product->get_current_bid() ) {
			return array(
				'success' => false,
				'message' => __( 'Your maximum bid must be higher than the current bid.', 'woo-live-auctions' ),
			);
		}

		// Check for other active proxy bids
		$active_proxies = $this->db->get_active_proxy_bids( $auction_id );

		if ( ! empty( $active_proxies ) ) {
			// CRITICAL: Limited Proxy Bidding Logic (Freemium Feature)
			return $this->handle_proxy_war( $product, $user_id, $max_bid, $active_proxies );
		}

		// No other proxy bids - set this one as active
		$this->db->set_proxy_bid( $auction_id, $user_id, $max_bid );

		// Place initial bid at minimum next bid
		$initial_bid = $product->get_min_next_bid();
		$this->db->insert_bid_log( $auction_id, $user_id, $initial_bid, true );
		$product->update_current_bid( $initial_bid, $user_id );

		// Send outbid email to previous bidder
		$this->send_outbid_notifications( $product, $user_id );

		do_action( 'woo_auction_proxy_bid_set', $auction_id, $user_id, $max_bid );

		return array(
			'success' => true,
			'message' => __( 'Your maximum bid has been set. We\'ll bid for you automatically!', 'woo-live-auctions' ),
		);
	}

	/**
	 * Handle proxy bid vs manual bid scenario
	 *
	 * @since 1.0.0
	 * @param WC_Product_Auction $product        Auction product object.
	 * @param int                $manual_user_id Manual bidder user ID.
	 * @param float              $manual_bid     Manual bid amount.
	 * @param array              $active_proxies Active proxy bids.
	 * @return array Result array.
	 */
	private function handle_proxy_vs_manual( $product, $manual_user_id, $manual_bid, $active_proxies ) {
		$auction_id = $product->get_id();

		// Get highest proxy bid
		$highest_proxy = $active_proxies[0];
		$proxy_user_id = absint( $highest_proxy->user_id );
		$max_bid       = floatval( $highest_proxy->max_bid );

		// Log the manual bid first
		$this->db->insert_bid_log( $auction_id, $manual_user_id, $manual_bid, false );

		// Check if proxy max bid can outbid the manual bid
		$counter_bid = $manual_bid + $product->get_bid_increment();

		if ( $counter_bid <= $max_bid ) {
			// Proxy wins - place automatic counter-bid
			$this->db->insert_bid_log( $auction_id, $proxy_user_id, $counter_bid, true );
			$product->update_current_bid( $counter_bid, $proxy_user_id );

			// Send outbid email to manual bidder
			$this->send_outbid_email( $product, $manual_user_id );

			do_action( 'woo_auction_proxy_auto_bid', $auction_id, $proxy_user_id, $counter_bid );

			return array(
				'success' => false,
				'message' => __( 'You have been outbid by an automatic bid. Please bid higher!', 'woo-live-auctions' ),
			);
		} else {
			// Manual bid exceeds proxy max - manual bidder wins
			$product->update_current_bid( $manual_bid, $manual_user_id );

			// Deactivate the proxy bid
			$this->db->deactivate_proxy_bid( $auction_id, $proxy_user_id );

			// Send outbid email to proxy bidder
			$this->send_outbid_email( $product, $proxy_user_id );

			do_action( 'woo_auction_proxy_exceeded', $auction_id, $proxy_user_id, $manual_user_id );

			return array(
				'success' => true,
				'message' => __( 'Your bid has been placed successfully!', 'woo-live-auctions' ),
			);
		}
	}

	/**
	 * Handle proxy vs proxy scenario (Limited Proxy Bidding - Freemium Feature)
	 *
	 * This is the core freemium limitation: When two users have proxy bids,
	 * the system places ONE counter-bid and then deactivates both proxies,
	 * triggering the "Proxy War" email to upsell the Pro version.
	 *
	 * @since 1.0.0
	 * @param WC_Product_Auction $product        Auction product object.
	 * @param int                $new_user_id    New proxy bidder user ID.
	 * @param float              $new_max_bid    New maximum bid.
	 * @param array              $active_proxies Existing active proxy bids.
	 * @return array Result array.
	 */
	private function handle_proxy_war( $product, $new_user_id, $new_max_bid, $active_proxies ) {
		$auction_id = $product->get_id();

		// Get existing proxy bidder
		$existing_proxy = $active_proxies[0];
		$existing_user_id = absint( $existing_proxy->user_id );
		$existing_max_bid = floatval( $existing_proxy->max_bid );

		// Set the new user's proxy bid (but mark as inactive immediately)
		$this->db->set_proxy_bid( $auction_id, $new_user_id, $new_max_bid );

		// Calculate counter-bid (one increment above current)
		$counter_bid = $product->get_current_bid() + $product->get_bid_increment();

		// Place ONE automatic counter-bid to make new user the high bidder
		if ( $counter_bid <= $new_max_bid ) {
			$this->db->insert_bid_log( $auction_id, $new_user_id, $counter_bid, true );
			$product->update_current_bid( $counter_bid, $new_user_id );
		}

		// CRITICAL: Deactivate BOTH proxy bids (freemium limitation)
		$this->db->deactivate_proxy_bid( $auction_id, $existing_user_id );
		$this->db->deactivate_proxy_bid( $auction_id, $new_user_id );

		// Mark that proxy war occurred for this auction
		update_post_meta( $auction_id, '_auction_proxy_war_occurred', 1 );

		// Send "Proxy War" email to BOTH users (neutral, no upgrade mention)
		$this->send_proxy_war_email( $product, $existing_user_id );
		$this->send_proxy_war_email( $product, $new_user_id );

		// Send upgrade opportunity email to admin
		$this->send_proxy_war_admin_email( $product, $active_proxies );

		do_action( 'woo_auction_proxy_war_triggered', $auction_id, $existing_user_id, $new_user_id );

		woo_auction_log( "Proxy war triggered for auction {$auction_id}. Both proxies deactivated." );

		return array(
			'success' => true,
			'message' => __( 'A bidding war has started! Your auto-bid is paused. Bid manually to stay in the lead!', 'woo-live-auctions' ),
		);
	}

	/**
	 * Send outbid notifications to previous bidders
	 *
	 * @since 1.0.0
	 * @param WC_Product_Auction $product        Auction product object.
	 * @param int                $new_bidder_id  New high bidder user ID.
	 */
	private function send_outbid_notifications( $product, $new_bidder_id ) {
		$previous_bidder = $product->get_current_bidder();

		if ( $previous_bidder && $previous_bidder !== $new_bidder_id ) {
			$this->send_outbid_email( $product, $previous_bidder );
		}
	}

	/**
	 * Send outbid email to a user
	 *
	 * @since 1.0.0
	 * @param WC_Product_Auction $product Auction product object.
	 * @param int                $user_id User ID to notify.
	 */
	private function send_outbid_email( $product, $user_id ) {
		if ( 'yes' !== get_option( 'woo_auction_enable_outbid_email', 'yes' ) ) {
			return;
		}

		do_action( 'woo_auction_send_outbid_email', $product->get_id(), $user_id );
	}

	/**
	 * Send proxy war email to a user (participant notification)
	 *
	 * @since 1.0.0
	 * @param WC_Product_Auction $product Auction product object.
	 * @param int                $user_id User ID to notify.
	 */
	private function send_proxy_war_email( $product, $user_id ) {
		if ( 'yes' !== get_option( 'woo_auction_enable_proxy_war_email', 'yes' ) ) {
			return;
		}

		do_action( 'woo_auction_send_proxy_war_email', $product->get_id(), $user_id );
	}

	/**
	 * Send proxy war admin notification email
	 *
	 * @since 1.0.0
	 * @param WC_Product_Auction $product        Auction product object.
	 * @param array              $active_proxies Array of active proxy bid objects.
	 */
	private function send_proxy_war_admin_email( $product, $active_proxies ) {
		do_action( 'woo_auction_send_proxy_war_admin_email', $product->get_id(), $active_proxies );
	}

	/**
	 * Process buy now purchase
	 *
	 * @since 1.0.0
	 * @param int $auction_id Auction product ID.
	 * @param int $user_id    User ID.
	 * @return array Result array.
	 */
	public function process_buy_now( $auction_id, $user_id ) {
		$product = wc_get_product( $auction_id );

		if ( ! $product || 'auction' !== $product->get_type() ) {
			return array(
				'success' => false,
				'message' => __( 'Invalid auction product.', 'woo-live-auctions' ),
			);
		}

		if ( ! $product->is_buy_now_available() ) {
			return array(
				'success' => false,
				'message' => __( 'Buy Now is no longer available for this auction.', 'woo-live-auctions' ),
			);
		}

		$buy_now_price = $product->get_buy_now_price();

		// Set winner and end auction
		$product->set_winner( $user_id );
		$product->update_current_bid( $buy_now_price, $user_id );

		// Log the buy now as a bid
		$this->db->insert_bid_log( $auction_id, $user_id, $buy_now_price, false );

		// Deactivate all proxy bids
		$this->db->deactivate_all_proxy_bids( $auction_id );

		do_action( 'woo_auction_buy_now_completed', $auction_id, $user_id, $buy_now_price );

		return array(
			'success' => true,
			'message' => __( 'Congratulations! You have won this auction with Buy Now.', 'woo-live-auctions' ),
		);
	}
}
