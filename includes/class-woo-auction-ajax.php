<?php
/**
 * AJAX Handler Class
 *
 * Handles all AJAX requests for real-time bidding and updates.
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
 * Class Woo_Auction_Ajax
 *
 * Manages AJAX endpoints for bidding, proxy bids, watchlist, and live updates.
 *
 * @since 1.0.0
 */
class Woo_Auction_Ajax {

	/**
	 * Bidding engine instance
	 *
	 * @since 1.0.0
	 * @var Woo_Auction_Bidding
	 */
	private $bidding;

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
		$this->bidding = woo_auction()->get_bidding();
		$this->db      = woo_auction()->get_db();
		$this->init_hooks();
	}

	/**
	 * Register AJAX hooks
	 *
	 * @since 1.0.0
	 */
	private function init_hooks() {
		// Place bid (logged in users)
		add_action( 'wp_ajax_woo_auction_place_bid', array( $this, 'place_bid' ) );

		// Set proxy bid (logged in users)
		add_action( 'wp_ajax_woo_auction_set_proxy_bid', array( $this, 'set_proxy_bid' ) );

		// Buy now (logged in users)
		add_action( 'wp_ajax_woo_auction_buy_now', array( $this, 'buy_now' ) );

		// Get auction updates (public)
		add_action( 'wp_ajax_woo_auction_get_updates', array( $this, 'get_updates' ) );
		add_action( 'wp_ajax_nopriv_woo_auction_get_updates', array( $this, 'get_updates' ) );

		// Watchlist actions (logged in users)
		add_action( 'wp_ajax_woo_auction_add_to_watchlist', array( $this, 'add_to_watchlist' ) );
		add_action( 'wp_ajax_woo_auction_remove_from_watchlist', array( $this, 'remove_from_watchlist' ) );
	}

	/**
	 * Handle place bid AJAX request
	 *
	 * @since 1.0.0
	 */
	public function place_bid() {
		// Verify nonce
		check_ajax_referer( 'woo_auction_bid_nonce', 'nonce' );

		// Get and validate inputs
		$auction_id = isset( $_POST['auction_id'] ) ? absint( $_POST['auction_id'] ) : 0;
		$bid_amount = isset( $_POST['bid_amount'] ) ? floatval( sanitize_text_field( wp_unslash( $_POST['bid_amount'] ) ) ) : 0;
		$user_id    = get_current_user_id();

		if ( ! $auction_id || ! $bid_amount ) {
			wp_send_json_error( array(
				'message' => __( 'Invalid bid data.', 'woo-live-auctions' ),
			) );
		}

		// Place the bid
		$result = $this->bidding->place_bid( $auction_id, $user_id, $bid_amount, false );

		if ( $result['success'] ) {
			// Get updated auction data
			$auction_data = $this->get_auction_data( $auction_id );
			
			wp_send_json_success( array(
				'message'      => $result['message'],
				'auction_data' => $auction_data,
			) );
		} else {
			wp_send_json_error( array(
				'message' => $result['message'],
			) );
		}
	}

	/**
	 * Handle set proxy bid AJAX request
	 *
	 * @since 1.0.0
	 */
	public function set_proxy_bid() {
		// Verify nonce
		check_ajax_referer( 'woo_auction_bid_nonce', 'nonce' );

		// Get and validate inputs
		$auction_id = isset( $_POST['auction_id'] ) ? absint( $_POST['auction_id'] ) : 0;
		$max_bid    = isset( $_POST['max_bid'] ) ? floatval( sanitize_text_field( wp_unslash( $_POST['max_bid'] ) ) ) : 0;
		$user_id    = get_current_user_id();

		if ( ! $auction_id || ! $max_bid ) {
			wp_send_json_error( array(
				'message' => __( 'Invalid proxy bid data.', 'woo-live-auctions' ),
			) );
		}

		// Place the proxy bid
		$result = $this->bidding->place_bid( $auction_id, $user_id, $max_bid, true );

		if ( $result['success'] ) {
			// Get updated auction data
			$auction_data = $this->get_auction_data( $auction_id );
			
			wp_send_json_success( array(
				'message'      => $result['message'],
				'auction_data' => $auction_data,
			) );
		} else {
			wp_send_json_error( array(
				'message' => $result['message'],
			) );
		}
	}

	/**
	 * Handle buy now AJAX request
	 *
	 * @since 1.0.0
	 */
	public function buy_now() {
		// Verify nonce
		check_ajax_referer( 'woo_auction_bid_nonce', 'nonce' );

		// Get and validate inputs
		$auction_id = isset( $_POST['auction_id'] ) ? absint( $_POST['auction_id'] ) : 0;
		$user_id    = get_current_user_id();

		if ( ! $auction_id ) {
			wp_send_json_error( array(
				'message' => __( 'Invalid auction ID.', 'woo-live-auctions' ),
			) );
		}

		// Process buy now
		$result = $this->bidding->process_buy_now( $auction_id, $user_id );

		if ( $result['success'] ) {
			// Get updated auction data
			$auction_data = $this->get_auction_data( $auction_id );
			
			wp_send_json_success( array(
				'message'      => $result['message'],
				'auction_data' => $auction_data,
				'redirect_url' => wc_get_cart_url(),
			) );
		} else {
			wp_send_json_error( array(
				'message' => $result['message'],
			) );
		}
	}

	/**
	 * Get auction updates for AJAX polling
	 *
	 * @since 1.0.0
	 */
	public function get_updates() {
		// Verify nonce
		check_ajax_referer( 'woo_auction_updates_nonce', 'nonce' );

		// Get auction ID
		$auction_id = isset( $_POST['auction_id'] ) ? absint( $_POST['auction_id'] ) : 0;

		if ( ! $auction_id ) {
			wp_send_json_error( array(
				'message' => __( 'Invalid auction ID.', 'woo-live-auctions' ),
			) );
		}

		// Get auction data
		$auction_data = $this->get_auction_data( $auction_id );

		wp_send_json_success( array(
			'auction_data' => $auction_data,
		) );
	}

	/**
	 * Add auction to watchlist
	 *
	 * @since 1.0.0
	 */
	public function add_to_watchlist() {
		// Verify nonce
		check_ajax_referer( 'woo_auction_watchlist_nonce', 'nonce' );

		// Get auction ID
		$auction_id = isset( $_POST['auction_id'] ) ? absint( $_POST['auction_id'] ) : 0;
		$user_id    = get_current_user_id();

		if ( ! $auction_id || ! $user_id ) {
			wp_send_json_error( array(
				'message' => __( 'Invalid request.', 'woo-live-auctions' ),
			) );
		}

		// Add to watchlist
		$result = $this->db->add_to_watchlist( $auction_id, $user_id );

		if ( $result ) {
			wp_send_json_success( array(
				'message' => __( 'Added to watchlist.', 'woo-live-auctions' ),
			) );
		} else {
			wp_send_json_error( array(
				'message' => __( 'Failed to add to watchlist.', 'woo-live-auctions' ),
			) );
		}
	}

	/**
	 * Remove auction from watchlist
	 *
	 * @since 1.0.0
	 */
	public function remove_from_watchlist() {
		// Verify nonce
		check_ajax_referer( 'woo_auction_watchlist_nonce', 'nonce' );

		// Get auction ID
		$auction_id = isset( $_POST['auction_id'] ) ? absint( $_POST['auction_id'] ) : 0;
		$user_id    = get_current_user_id();

		if ( ! $auction_id || ! $user_id ) {
			wp_send_json_error( array(
				'message' => __( 'Invalid request.', 'woo-live-auctions' ),
			) );
		}

		// Remove from watchlist
		$result = $this->db->remove_from_watchlist( $auction_id, $user_id );

		if ( $result ) {
			wp_send_json_success( array(
				'message' => __( 'Removed from watchlist.', 'woo-live-auctions' ),
			) );
		} else {
			wp_send_json_error( array(
				'message' => __( 'Failed to remove from watchlist.', 'woo-live-auctions' ),
			) );
		}
	}

	/**
	 * Get formatted auction data for AJAX response
	 *
	 * @since 1.0.0
	 * @param int $auction_id Auction product ID.
	 * @return array Auction data array.
	 */
	private function get_auction_data( $auction_id ) {
		$product = wc_get_product( $auction_id );

		if ( ! $product || 'auction' !== $product->get_type() ) {
			return array();
		}

		$user_id = get_current_user_id();

		// Get bid history
		$bid_history_limit = absint( get_option( 'woo_auction_bid_history_limit', 10 ) );
		$bid_history_raw   = $this->db->get_bid_history( $auction_id, $bid_history_limit );
		$bid_history       = array();

		foreach ( $bid_history_raw as $bid ) {
			$bidder = get_userdata( $bid->user_id );
			$bid_history[] = array(
				'user_display_name' => $bidder ? esc_html( $bidder->display_name ) : __( 'Anonymous', 'woo-live-auctions' ),
				'bid'               => wc_price( $bid->bid ),
				'date'              => date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $bid->date ) ),
				'is_proxy'          => (bool) $bid->is_proxy,
			);
		}

		// Check if user has active proxy bid
		$user_proxy = null;
		if ( $user_id ) {
			$proxy_bid = $this->db->get_proxy_bid( $auction_id, $user_id );
			if ( $proxy_bid && $proxy_bid->is_active ) {
				$user_proxy = array(
					'max_bid'   => floatval( $proxy_bid->max_bid ),
					'is_active' => true,
				);
			}
		}

		// Check if user has placed any bids
		$user_has_bids = false;
		if ( $user_id ) {
			$user_bids = $this->db->get_user_bids( $auction_id, $user_id );
			$user_has_bids = ! empty( $user_bids );
		}

		return array(
			'auction_id'           => $auction_id,
			'current_bid'          => floatval( $product->get_current_bid() ),
			'current_bid_formatted' => wc_price( $product->get_current_bid() ),
			'current_bidder'       => $product->get_current_bidder(),
			'is_user_high_bidder'  => $user_id ? $product->is_user_high_bidder( $user_id ) : false,
			'user_has_bids'        => $user_has_bids,
			'bid_count'            => $product->get_bid_count(),
			'min_next_bid'         => floatval( $product->get_min_next_bid() ),
			'min_next_bid_formatted' => wc_price( $product->get_min_next_bid() ),
			'time_remaining'       => $product->get_time_remaining(),
			'time_remaining_formatted' => esc_html( $product->get_time_remaining_formatted() ),
			'auction_status'       => $product->get_auction_status(),
			'is_auction_live'      => $product->is_auction_live(),
			'is_auction_ended'     => $product->is_auction_ended(),
			'is_reserve_met'       => $product->is_reserve_met(),
			'buy_now_available'    => $product->is_buy_now_available(),
			'buy_now_price'        => $product->get_buy_now_price(),
			'buy_now_price_formatted' => $product->get_buy_now_price() ? wc_price( $product->get_buy_now_price() ) : null,
			'bid_history'          => $bid_history,
			'user_proxy'           => $user_proxy,
			'has_winner'           => $product->has_winner(),
			'winner_id'            => $product->get_winner(),
		);
	}
}
