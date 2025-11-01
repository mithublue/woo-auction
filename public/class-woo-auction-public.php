<?php
/**
 * Public Handler Class
 *
 * Loads all public-facing functionality including scripts, styles, and templates.
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
 * Class Woo_Auction_Public
 *
 * Manages all public-facing functionality.
 *
 * @since 1.0.0
 */
class Woo_Auction_Public {

	/**
	 * Loop handler instance
	 *
	 * @since 1.0.0
	 * @var Woo_Auction_Loop
	 */
	private $loop;

	/**
	 * Product handler instance
	 *
	 * @since 1.0.0
	 * @var Woo_Auction_Product_Handler
	 */
	private $product_handler;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->load_dependencies();
		$this->init_hooks();
	}

	/**
	 * Load public dependencies
	 *
	 * @since 1.0.0
	 */
	private function load_dependencies() {
		$this->loop            = new Woo_Auction_Loop();
		$this->product_handler = new Woo_Auction_Product_Handler();
	}

	/**
	 * Register public hooks
	 *
	 * @since 1.0.0
	 */
	private function init_hooks() {
		// Enqueue public scripts and styles
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public_assets' ) );

		// Add body class for auction products
		add_filter( 'body_class', array( $this, 'add_body_class' ) );

		// Modify WooCommerce cart behavior for auctions
		add_filter( 'woocommerce_is_purchasable', array( $this, 'is_auction_purchasable' ), 10, 2 );
		add_filter( 'woocommerce_product_add_to_cart_text', array( $this, 'modify_add_to_cart_text' ), 10, 2 );
		add_filter( 'woocommerce_product_add_to_cart_url', array( $this, 'modify_add_to_cart_url' ), 10, 2 );
	}

	/**
	 * Enqueue public assets
	 *
	 * @since 1.0.0
	 */
	public function enqueue_public_assets() {
		// Enqueue public CSS
		wp_enqueue_style(
			'woo-auction-public',
			WOO_AUCTION_URL . 'public/assets/css/public.css',
			array(),
			WOO_AUCTION_VERSION,
			'all'
		);

		// Enqueue public JS
		wp_enqueue_script(
			'woo-auction-public',
			WOO_AUCTION_URL . 'public/assets/js/public.js',
			array( 'jquery' ),
			WOO_AUCTION_VERSION,
			true
		);

		// Localize script with AJAX data
		$user_id = get_current_user_id();

		wp_localize_script(
			'woo-auction-public',
			'wooAuction',
			array(
				'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
				'bidNonce'        => wp_create_nonce( 'woo_auction_bid_nonce' ),
				'updatesNonce'    => wp_create_nonce( 'woo_auction_updates_nonce' ),
				'watchlistNonce'  => wp_create_nonce( 'woo_auction_watchlist_nonce' ),
				'refreshInterval' => absint( get_option( 'woo_auction_ajax_refresh_interval', 5 ) ) * 1000,
				'userId'          => $user_id,
				'isLoggedIn'      => $user_id > 0,
				'i18n'            => array(
					'placeBid'        => __( 'Place Bid', 'woo-live-auctions' ),
					'bidding'         => __( 'Bidding...', 'woo-live-auctions' ),
					'bidPlaced'       => __( 'Bid placed!', 'woo-live-auctions' ),
					'error'           => __( 'Error', 'woo-live-auctions' ),
					'loginRequired'   => __( 'Please log in to bid', 'woo-live-auctions' ),
					'invalidAmount'   => __( 'Please enter a valid bid amount', 'woo-live-auctions' ),
					'auctionEnded'    => __( 'Auction has ended', 'woo-live-auctions' ),
					'youAreWinning'   => __( 'You are winning!', 'woo-live-auctions' ),
					'youAreOutbid'    => __( 'You have been outbid', 'woo-live-auctions' ),
					'timeRemaining'   => __( 'Time Remaining:', 'woo-live-auctions' ),
					'auctionEndedMsg' => __( 'Auction Ended', 'woo-live-auctions' ),
				),
			)
		);
	}

	/**
	 * Add body class for auction products
	 *
	 * @since 1.0.0
	 * @param array $classes Existing body classes.
	 * @return array Modified body classes.
	 */
	public function add_body_class( $classes ) {
		if ( is_singular( 'product' ) ) {
			global $post;
			$product = wc_get_product( $post->ID );

			if ( $product && 'auction' === $product->get_type() ) {
				$classes[] = 'woo-auction-product';
				$classes[] = 'woo-auction-status-' . $product->get_auction_status();
			}
		}

		return $classes;
	}

	/**
	 * Check if auction product is purchasable
	 *
	 * @since 1.0.0
	 * @param bool       $purchasable Whether product is purchasable.
	 * @param WC_Product $product     Product object.
	 * @return bool Modified purchasable status.
	 */
	public function is_auction_purchasable( $purchasable, $product ) {
		if ( 'auction' === $product->get_type() ) {
			// Auction products are only purchasable if ended and have a winner
			return $product->is_auction_ended() && $product->has_winner();
		}

		return $purchasable;
	}

	/**
	 * Modify add to cart button text for auctions
	 *
	 * @since 1.0.0
	 * @param string     $text    Button text.
	 * @param WC_Product $product Product object.
	 * @return string Modified button text.
	 */
	public function modify_add_to_cart_text( $text, $product ) {
		if ( 'auction' === $product->get_type() ) {
			if ( $product->is_auction_live() ) {
				return __( 'View Auction', 'woo-live-auctions' );
			} elseif ( $product->is_auction_ended() ) {
				return __( 'Auction Ended', 'woo-live-auctions' );
			} else {
				return __( 'View Auction', 'woo-live-auctions' );
			}
		}

		return $text;
	}

	/**
	 * Modify add to cart URL for auctions (link to product page)
	 *
	 * @since 1.0.0
	 * @param string     $url     Add to cart URL.
	 * @param WC_Product $product Product object.
	 * @return string Modified URL.
	 */
	public function modify_add_to_cart_url( $url, $product ) {
		if ( 'auction' === $product->get_type() ) {
			return get_permalink( $product->get_id() );
		}

		return $url;
	}
}
