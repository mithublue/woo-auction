<?php
/**
 * Shortcodes Handler Class
 *
 * Registers and renders shortcodes for auction functionality.
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
 * Class Woo_Auction_Shortcodes
 *
 * Handles all auction shortcodes.
 *
 * @since 1.0.0
 */
class Woo_Auction_Shortcodes {

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
		$this->init_hooks();
	}

	/**
	 * Register shortcodes
	 *
	 * @since 1.0.0
	 */
	private function init_hooks() {
		add_shortcode( 'my_auctions_dashboard', array( $this, 'render_my_auctions_dashboard' ) );
		add_shortcode( 'auction_countdown', array( $this, 'render_auction_countdown' ) );
		add_shortcode( 'auction_current_bid', array( $this, 'render_current_bid' ) );
	}

	/**
	 * Render My Auctions Dashboard shortcode
	 *
	 * @since 1.0.0
	 * @param array $atts Shortcode attributes.
	 * @return string Shortcode output.
	 */
	public function render_my_auctions_dashboard( $atts ) {
		// Check if user is logged in
		if ( ! is_user_logged_in() ) {
			return '<p>' . esc_html__( 'Please log in to view your auctions.', 'woo-live-auctions' ) . '</p>';
		}

		$user_id = get_current_user_id();

		// Parse attributes
		$atts = shortcode_atts(
			array(
				'show_bidding' => 'yes',
				'show_watching' => 'yes',
				'show_won' => 'yes',
			),
			$atts,
			'my_auctions_dashboard'
		);

		// Start output buffering
		ob_start();

		// Load template
		$template_path = WOO_AUCTION_PATH . 'templates/myaccount/my-auctions.php';
		
		if ( file_exists( $template_path ) ) {
			include $template_path;
		} else {
			echo '<p>' . esc_html__( 'Dashboard template not found.', 'woo-live-auctions' ) . '</p>';
		}

		return ob_get_clean();
	}

	/**
	 * Render auction countdown shortcode
	 *
	 * @since 1.0.0
	 * @param array $atts Shortcode attributes.
	 * @return string Shortcode output.
	 */
	public function render_auction_countdown( $atts ) {
		$atts = shortcode_atts(
			array(
				'id' => 0,
			),
			$atts,
			'auction_countdown'
		);

		$auction_id = absint( $atts['id'] );

		if ( ! $auction_id ) {
			return '<p>' . esc_html__( 'Invalid auction ID.', 'woo-live-auctions' ) . '</p>';
		}

		$product = wc_get_product( $auction_id );

		if ( ! $product || 'auction' !== $product->get_type() ) {
			return '<p>' . esc_html__( 'Invalid auction product.', 'woo-live-auctions' ) . '</p>';
		}

		$time_remaining = $product->get_time_remaining_formatted();

		return '<div class="woo-auction-countdown" data-auction-id="' . esc_attr( $auction_id ) . '">' . 
		       '<span class="countdown-label">' . esc_html__( 'Time Remaining:', 'woo-live-auctions' ) . '</span> ' .
		       '<span class="countdown-value">' . esc_html( $time_remaining ) . '</span>' .
		       '</div>';
	}

	/**
	 * Render current bid shortcode
	 *
	 * @since 1.0.0
	 * @param array $atts Shortcode attributes.
	 * @return string Shortcode output.
	 */
	public function render_current_bid( $atts ) {
		$atts = shortcode_atts(
			array(
				'id' => 0,
			),
			$atts,
			'auction_current_bid'
		);

		$auction_id = absint( $atts['id'] );

		if ( ! $auction_id ) {
			return '<p>' . esc_html__( 'Invalid auction ID.', 'woo-live-auctions' ) . '</p>';
		}

		$product = wc_get_product( $auction_id );

		if ( ! $product || 'auction' !== $product->get_type() ) {
			return '<p>' . esc_html__( 'Invalid auction product.', 'woo-live-auctions' ) . '</p>';
		}

		$current_bid = wc_price( $product->get_current_bid() );

		return '<div class="woo-auction-current-bid" data-auction-id="' . esc_attr( $auction_id ) . '">' . 
		       '<span class="bid-label">' . esc_html__( 'Current Bid:', 'woo-live-auctions' ) . '</span> ' .
		       '<span class="bid-value">' . wp_kses_post( $current_bid ) . '</span>' .
		       '</div>';
	}

	/**
	 * Get auctions user is bidding on
	 *
	 * @since 1.0.0
	 * @param int $user_id User ID.
	 * @return array Array of WC_Product_Auction objects.
	 */
	public function get_user_bidding_auctions( $user_id ) {
		$auction_ids = $this->db->get_user_bid_auctions( $user_id );
		$auctions = array();

		foreach ( $auction_ids as $auction_id ) {
			$product = wc_get_product( $auction_id );
			if ( $product && 'auction' === $product->get_type() ) {
				$auctions[] = $product;
			}
		}

		return $auctions;
	}

	/**
	 * Get auctions user is watching
	 *
	 * @since 1.0.0
	 * @param int $user_id User ID.
	 * @return array Array of WC_Product_Auction objects.
	 */
	public function get_user_watching_auctions( $user_id ) {
		$auction_ids = $this->db->get_user_watchlist( $user_id );
		$auctions = array();

		foreach ( $auction_ids as $auction_id ) {
			$product = wc_get_product( $auction_id );
			if ( $product && 'auction' === $product->get_type() ) {
				$auctions[] = $product;
			}
		}

		return $auctions;
	}

	/**
	 * Get auctions user has won
	 *
	 * @since 1.0.0
	 * @param int $user_id User ID.
	 * @return array Array of WC_Product_Auction objects.
	 */
	public function get_user_won_auctions( $user_id ) {
		$args = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'key'     => '_product_type',
					'value'   => 'auction',
					'compare' => '=',
				),
				array(
					'key'     => '_auction_winner',
					'value'   => $user_id,
					'compare' => '=',
				),
			),
		);

		$query = new WP_Query( $args );
		$auctions = array();

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$product = wc_get_product( get_the_ID() );
				if ( $product ) {
					$auctions[] = $product;
				}
			}
			wp_reset_postdata();
		}

		return $auctions;
	}
}
