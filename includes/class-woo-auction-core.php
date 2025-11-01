<?php
/**
 * Core Plugin Class
 *
 * Main orchestrator that loads all components and initializes the plugin.
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
 * Class Woo_Auction_Core
 *
 * The main plugin class that orchestrates all components.
 *
 * @since 1.0.0
 */
class Woo_Auction_Core {

	/**
	 * The single instance of the class
	 *
	 * @since 1.0.0
	 * @var Woo_Auction_Core
	 */
	protected static $instance = null;

	/**
	 * Database helper instance
	 *
	 * @since 1.0.0
	 * @var Woo_Auction_DB
	 */
	public $db;

	/**
	 * Bidding engine instance
	 *
	 * @since 1.0.0
	 * @var Woo_Auction_Bidding
	 */
	public $bidding;

	/**
	 * AJAX handler instance
	 *
	 * @since 1.0.0
	 * @var Woo_Auction_Ajax
	 */
	public $ajax;

	/**
	 * Cron handler instance
	 *
	 * @since 1.0.0
	 * @var Woo_Auction_Cron
	 */
	public $cron;

	/**
	 * Email handler instance
	 *
	 * @since 1.0.0
	 * @var Woo_Auction_Emails
	 */
	public $emails;

	/**
	 * Shortcodes handler instance
	 *
	 * @since 1.0.0
	 * @var Woo_Auction_Shortcodes
	 */
	public $shortcodes;

	/**
	 * Admin handler instance
	 *
	 * @since 1.0.0
	 * @var Woo_Auction_Admin
	 */
	public $admin;

	/**
	 * Public handler instance
	 *
	 * @since 1.0.0
	 * @var Woo_Auction_Public
	 */
	public $public;

	/**
	 * Main Woo_Auction_Core Instance
	 *
	 * Ensures only one instance of Woo_Auction_Core is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @return Woo_Auction_Core Main instance.
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		self::$instance = $this;
		$this->load_dependencies();
		$this->init_hooks();
		$this->init_components();
	}

	/**
	 * Load required dependencies
	 *
	 * @since 1.0.0
	 */
	private function load_dependencies() {
		// Core includes
		require_once WOO_AUCTION_PATH . 'includes/class-woo-auction-db.php';
		require_once WOO_AUCTION_PATH . 'includes/class-woo-auction-product.php';
		require_once WOO_AUCTION_PATH . 'includes/class-woo-auction-bidding.php';
		require_once WOO_AUCTION_PATH . 'includes/class-woo-auction-ajax.php';
		require_once WOO_AUCTION_PATH . 'includes/class-woo-auction-cron.php';
		require_once WOO_AUCTION_PATH . 'includes/class-woo-auction-emails.php';
		require_once WOO_AUCTION_PATH . 'includes/class-woo-auction-shortcodes.php';

		// Admin includes
		if ( is_admin() ) {
			require_once WOO_AUCTION_PATH . 'admin/class-woo-auction-admin.php';
			require_once WOO_AUCTION_PATH . 'admin/class-woo-auction-product-panel.php';
			require_once WOO_AUCTION_PATH . 'admin/class-woo-auction-settings.php';
		}

		// Public includes
		if ( ! is_admin() || wp_doing_ajax() ) {
			require_once WOO_AUCTION_PATH . 'public/class-woo-auction-public.php';
			require_once WOO_AUCTION_PATH . 'public/class-woo-auction-loop.php';
			require_once WOO_AUCTION_PATH . 'public/class-woo-auction-product-handler.php';
		}
	}

	/**
	 * Initialize WordPress hooks
	 *
	 * @since 1.0.0
	 */
	private function init_hooks() {
		// Register auction product type
		add_filter( 'product_type_selector', array( $this, 'add_auction_product_type' ) );
		add_filter( 'woocommerce_product_class', array( $this, 'set_auction_product_class' ), 10, 2 );

		// Check for database updates
		if ( class_exists( 'Woo_Auction_Install' ) ) {
			add_action( 'admin_init', array( 'Woo_Auction_Install', 'maybe_update_db' ) );
		}

		// Add custom query vars
		add_filter( 'query_vars', array( $this, 'add_query_vars' ) );

		// Add rewrite rules for auction endpoints
		add_action( 'init', array( $this, 'add_rewrite_rules' ) );
	}

	/**
	 * Initialize plugin components
	 *
	 * @since 1.0.0
	 */
	private function init_components() {
		// Initialize core components
		$this->db         = new Woo_Auction_DB();
		$this->bidding    = new Woo_Auction_Bidding();
		$this->ajax       = new Woo_Auction_Ajax();
		$this->cron       = new Woo_Auction_Cron();
		$this->emails     = new Woo_Auction_Emails();
		$this->shortcodes = new Woo_Auction_Shortcodes();

		// Initialize admin components
		if ( is_admin() ) {
			$this->admin = new Woo_Auction_Admin();
		}

		// Initialize public components
		if ( ! is_admin() || wp_doing_ajax() ) {
			$this->public = new Woo_Auction_Public();
		}
	}

	/**
	 * Add auction product type to WooCommerce product type selector
	 *
	 * @since 1.0.0
	 * @param array $types Existing product types.
	 * @return array Modified product types.
	 */
	public function add_auction_product_type( $types ) {
		$types['auction'] = __( 'Auction product', 'woo-live-auctions' );
		return $types;
	}

	/**
	 * Set the correct product class for auction products
	 *
	 * @since 1.0.0
	 * @param string $classname Product class name.
	 * @param string $product_type Product type.
	 * @return string Modified class name.
	 */
	public function set_auction_product_class( $classname, $product_type ) {
		if ( 'auction' === $product_type ) {
			$classname = 'WC_Product_Auction';
		}
		return $classname;
	}

	/**
	 * Add custom query vars
	 *
	 * @since 1.0.0
	 * @param array $vars Existing query vars.
	 * @return array Modified query vars.
	 */
	public function add_query_vars( $vars ) {
		$vars[] = 'auction_id';
		$vars[] = 'auction_action';
		return $vars;
	}

	/**
	 * Add rewrite rules for auction endpoints
	 *
	 * @since 1.0.0
	 */
	public function add_rewrite_rules() {
		add_rewrite_rule(
			'^my-auctions/?$',
			'index.php?pagename=my-auctions',
			'top'
		);
	}

	/**
	 * Get database helper instance
	 *
	 * @since 1.0.0
	 * @return Woo_Auction_DB Database helper instance.
	 */
	public function get_db() {
		return $this->db;
	}

	/**
	 * Get bidding engine instance
	 *
	 * @since 1.0.0
	 * @return Woo_Auction_Bidding Bidding engine instance.
	 */
	public function get_bidding() {
		return $this->bidding;
	}

	/**
	 * Get AJAX handler instance
	 *
	 * @since 1.0.0
	 * @return Woo_Auction_Ajax AJAX handler instance.
	 */
	public function get_ajax() {
		return $this->ajax;
	}

	/**
	 * Get cron handler instance
	 *
	 * @since 1.0.0
	 * @return Woo_Auction_Cron Cron handler instance.
	 */
	public function get_cron() {
		return $this->cron;
	}

	/**
	 * Get email handler instance
	 *
	 * @since 1.0.0
	 * @return Woo_Auction_Emails Email handler instance.
	 */
	public function get_emails() {
		return $this->emails;
	}

	/**
	 * Get shortcodes handler instance
	 *
	 * @since 1.0.0
	 * @return Woo_Auction_Shortcodes Shortcodes handler instance.
	 */
	public function get_shortcodes() {
		return $this->shortcodes;
	}
}

/**
 * Get main plugin instance
 *
 * @since 1.0.0
 * @return Woo_Auction_Core Main plugin instance.
 */
function woo_auction() {
	return Woo_Auction_Core::get_instance();
}
