<?php
/**
 * Plugin Name: Woo Live Auctions by CyberCraft
 * Plugin URI: https://example.com/woo-live-auctions
 * Description: The fastest, most engaging bidding experience for WooCommerce. Real-time AJAX bidding with smart proxy bidding system.
 * Version: 2
 * Author: Mithu A Quayium
 * Author URI: https://example.com
 * Text Domain: woo-live-auctions
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.5
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package Woo_Live_Auctions
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 */
define( 'WOO_AUCTION_VERSION', '1.0.0' );

/**
 * Plugin base name
 */
define( 'WOO_AUCTION_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Plugin directory path
 */
define( 'WOO_AUCTION_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Plugin directory URL
 */
define( 'WOO_AUCTION_URL', plugin_dir_url( __FILE__ ) );

/**
 * Plugin text domain for translations
 */
define( 'WOO_AUCTION_TEXT_DOMAIN', 'woo-live-auctions' );

/**
 * Database table names (without prefix)
 */
define( 'WOO_AUCTION_LOG_TABLE', 'woo_auctions_log' );
define( 'WOO_AUCTION_PROXY_TABLE', 'woo_auctions_proxy_bids' );
define( 'WOO_AUCTION_ACTIVITY_TABLE', 'woo_auctions_activity' );

/**
 * Check if WooCommerce is active before initializing plugin
 *
 * @since 1.0.0
 */
function woo_auction_check_woocommerce() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'woo_auction_woocommerce_missing_notice' );
		return false;
	}
	return true;
}

/**
 * Display admin notice if WooCommerce is not active
 *
 * @since 1.0.0
 */
function woo_auction_woocommerce_missing_notice() {
	?>
	<div class="notice notice-error">
		<p>
			<?php
			echo wp_kses_post(
				sprintf(
					/* translators: %s: WooCommerce plugin link */
					__( '<strong>Woo Live Auctions</strong> requires WooCommerce to be installed and active. Please install %s first.', 'woo-live-auctions' ),
					'<a href="' . esc_url( admin_url( 'plugin-install.php?s=woocommerce&tab=search&type=term' ) ) . '">WooCommerce</a>'
				)
			);
			?>
		</p>
	</div>
	<?php
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-woo-auction-install.php
 *
 * @since 1.0.0
 */
function activate_woo_auction() {
	require_once WOO_AUCTION_PATH . 'includes/class-woo-auction-install.php';
	Woo_Auction_Install::activate();
}

/**
 * The code that runs during plugin deactivation.
 *
 * @since 1.0.0
 */
function deactivate_woo_auction() {
	// Clear scheduled cron jobs
	wp_clear_scheduled_hook( 'woo_auction_check_starting' );
	wp_clear_scheduled_hook( 'woo_auction_check_ending' );

	// Flush rewrite rules
	flush_rewrite_rules();
}

register_activation_hook( __FILE__, 'activate_woo_auction' );
register_deactivation_hook( __FILE__, 'deactivate_woo_auction' );

/**
 * Declare HPOS (High-Performance Order Storage) compatibility
 *
 * @since 1.0.0
 */
add_action(
	'before_woocommerce_init',
	function() {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);

/**
 * Initialize the plugin after all plugins are loaded
 *
 * @since 1.0.0
 */
function woo_auction_init() {
	// Check if WooCommerce is active
	if ( ! woo_auction_check_woocommerce() ) {
		return;
	}

	// Load plugin text domain for translations
	load_plugin_textdomain( 'woo-live-auctions', false, dirname( WOO_AUCTION_BASENAME ) . '/languages' );

	// Load the core plugin class
	require_once WOO_AUCTION_PATH . 'includes/class-woo-auction-core.php';

	// Initialize the plugin
	Woo_Auction_Core::get_instance();
}
add_action( 'plugins_loaded', 'woo_auction_init', 10 );

/**
 * Add plugin action links on plugins page
 *
 * @since 1.0.0
 * @param array $links Existing plugin action links.
 * @return array Modified plugin action links.
 */
function woo_auction_plugin_action_links( $links ) {
	$settings_link = sprintf(
		'<a href="%s">%s</a>',
		esc_url( admin_url( 'admin.php?page=wc-settings&tab=auctions' ) ),
		esc_html__( 'Settings', 'woo-live-auctions' )
	);

	$docs_link = sprintf(
		'<a href="%s" target="_blank">%s</a>',
		esc_url( 'https://example.com/docs/woo-live-auctions' ),
		esc_html__( 'Docs', 'woo-live-auctions' )
	);

	array_unshift( $links, $settings_link, $docs_link );

	return $links;
}
add_filter( 'plugin_action_links_' . WOO_AUCTION_BASENAME, 'woo_auction_plugin_action_links' );

/**
 * Add plugin meta links on plugins page
 *
 * @since 1.0.0
 * @param array  $links Existing plugin meta links.
 * @param string $file  Plugin file path.
 * @return array Modified plugin meta links.
 */
function woo_auction_plugin_row_meta( $links, $file ) {
	if ( WOO_AUCTION_BASENAME !== $file ) {
		return $links;
	}

	$row_meta = array(
		'support' => sprintf(
			'<a href="%s" target="_blank">%s</a>',
			esc_url( 'https://example.com/support' ),
			esc_html__( 'Support', 'woo-live-auctions' )
		),
		'upgrade' => sprintf(
			'<a href="%s" target="_blank" style="color: #46b450; font-weight: 700;">%s</a>',
			esc_url( 'https://cybercraftit.com/woo-live-auction-pro/' ),
			esc_html__( 'Upgrade to Pro', 'woo-live-auctions' )
		),
	);

	return array_merge( $links, $row_meta );
}
add_filter( 'plugin_row_meta', 'woo_auction_plugin_row_meta', 10, 2 );

/**
 * Add custom cron schedules
 *
 * @since 1.0.0
 * @param array $schedules Existing cron schedules.
 * @return array Modified cron schedules.
 */
function woo_auction_add_cron_schedules( $schedules ) {
	// Every minute schedule
	$schedules['woo_auction_1min'] = array(
		'interval' => 60,
		'display'  => __( 'Every Minute (Woo Auctions)', 'woo-live-auctions' ),
	);

	// Every 5 minutes schedule
	$schedules['woo_auction_5min'] = array(
		'interval' => 300,
		'display'  => __( 'Every 5 Minutes (Woo Auctions)', 'woo-live-auctions' ),
	);

	return $schedules;
}
add_filter( 'cron_schedules', 'woo_auction_add_cron_schedules' );

/**
 * Log debug messages if WP_DEBUG is enabled
 *
 * @since 1.0.0
 * @param string $message Debug message to log.
 * @param string $level   Log level (info, warning, error).
 */
function woo_auction_log( $message, $level = 'info' ) {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		$logger = wc_get_logger();
		$context = array( 'source' => 'woo-live-auctions' );

		switch ( $level ) {
			case 'error':
				$logger->error( $message, $context );
				break;
			case 'warning':
				$logger->warning( $message, $context );
				break;
			default:
				$logger->info( $message, $context );
				break;
		}
	}
}
