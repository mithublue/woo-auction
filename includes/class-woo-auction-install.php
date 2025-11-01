<?php
/**
 * Plugin Activation and Installation
 *
 * Handles database table creation, default settings, and version management.
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
 * Class Woo_Auction_Install
 *
 * Handles plugin activation, database setup, and version updates.
 *
 * @since 1.0.0
 */
class Woo_Auction_Install {

	/**
	 * Run activation procedures
	 *
	 * @since 1.0.0
	 */
	public static function activate() {
		// Create custom database tables
		self::create_tables();

		// Set default plugin options
		self::set_default_options();

		// Schedule cron jobs
		self::schedule_cron_jobs();

		// Flush rewrite rules
		flush_rewrite_rules();

		// Store plugin version
		update_option( 'woo_auction_version', WOO_AUCTION_VERSION );

		// Set activation timestamp
		if ( ! get_option( 'woo_auction_activated_time' ) ) {
			update_option( 'woo_auction_activated_time', time() );
		}

		woo_auction_log( 'Plugin activated successfully. Version: ' . WOO_AUCTION_VERSION );
	}

	/**
	 * Create custom database tables using dbDelta
	 *
	 * @since 1.0.0
	 */
	private static function create_tables() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();

		// Table 1: Auction Log (stores every bid)
		$log_table = $wpdb->prefix . WOO_AUCTION_LOG_TABLE;
		$sql_log   = "CREATE TABLE {$log_table} (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			auction_id bigint(20) NOT NULL,
			user_id bigint(20) NOT NULL,
			bid decimal(10,2) NOT NULL,
			date datetime NOT NULL,
			is_proxy tinyint(1) NOT NULL DEFAULT 0,
			PRIMARY KEY  (id),
			KEY auction_id (auction_id),
			KEY user_id (user_id),
			KEY date (date)
		) {$charset_collate};";

		// Table 2: Proxy Bidding (stores max bids)
		$proxy_table = $wpdb->prefix . WOO_AUCTION_PROXY_TABLE;
		$sql_proxy   = "CREATE TABLE {$proxy_table} (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			auction_id bigint(20) NOT NULL,
			user_id bigint(20) NOT NULL,
			max_bid decimal(10,2) NOT NULL,
			is_active tinyint(1) NOT NULL DEFAULT 1,
			last_updated datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY auction_user (auction_id, user_id),
			KEY is_active (is_active)
		) {$charset_collate};";

		// Table 3: Activity/Watchlist (stores user watching status)
		$activity_table = $wpdb->prefix . WOO_AUCTION_ACTIVITY_TABLE;
		$sql_activity   = "CREATE TABLE {$activity_table} (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			auction_id bigint(20) NOT NULL,
			user_id bigint(20) NOT NULL,
			action varchar(20) NOT NULL DEFAULT 'watch',
			PRIMARY KEY  (id),
			UNIQUE KEY auction_user_action (auction_id, user_id, action)
		) {$charset_collate};";

		// Execute table creation
		dbDelta( $sql_log );
		dbDelta( $sql_proxy );
		dbDelta( $sql_activity );

		woo_auction_log( 'Database tables created successfully.' );
	}

	/**
	 * Set default plugin options
	 *
	 * @since 1.0.0
	 */
	private static function set_default_options() {
		$defaults = array(
			'woo_auction_enable_proxy_bidding'     => 'yes',
			'woo_auction_enable_buy_now'           => 'yes',
			'woo_auction_enable_reserve_price'     => 'yes',
			'woo_auction_enable_watchlist'         => 'yes',
			'woo_auction_ending_soon_threshold'    => '60', // minutes
			'woo_auction_ajax_refresh_interval'    => '5', // seconds
			'woo_auction_bid_increment_type'       => 'fixed', // fixed or percentage
			'woo_auction_default_bid_increment'    => '1.00',
			'woo_auction_allow_guest_bidding'      => 'no',
			'woo_auction_show_bid_history'         => 'yes',
			'woo_auction_bid_history_limit'        => '10',
			'woo_auction_enable_outbid_email'      => 'yes',
			'woo_auction_enable_won_email'         => 'yes',
			'woo_auction_enable_ending_soon_email' => 'yes',
			'woo_auction_enable_proxy_war_email'   => 'yes',
			'woo_auction_countdown_format'         => 'dhms', // days, hours, minutes, seconds
			'woo_auction_show_auction_badge'       => 'yes',
			'woo_auction_badge_text'               => __( 'Auction', 'woo-live-auctions' ),
			'woo_auction_badge_color'              => '#e74c3c',
			'woo_auction_primary_color'            => '#3498db',
			'woo_auction_success_color'            => '#2ecc71',
			'woo_auction_danger_color'             => '#e74c3c',
		);

		foreach ( $defaults as $key => $value ) {
			if ( false === get_option( $key ) ) {
				add_option( $key, $value );
			}
		}

		woo_auction_log( 'Default options set successfully.' );
	}

	/**
	 * Schedule WP-Cron jobs for auction management
	 *
	 * @since 1.0.0
	 */
	private static function schedule_cron_jobs() {
		// Check for starting auctions every 5 minutes
		if ( ! wp_next_scheduled( 'woo_auction_check_starting' ) ) {
			wp_schedule_event( time(), 'woo_auction_5min', 'woo_auction_check_starting' );
		}

		// Check for ending auctions every minute
		if ( ! wp_next_scheduled( 'woo_auction_check_ending' ) ) {
			wp_schedule_event( time(), 'woo_auction_1min', 'woo_auction_check_ending' );
		}

		woo_auction_log( 'Cron jobs scheduled successfully.' );
	}

	/**
	 * Add custom cron schedules
	 *
	 * @since 1.0.0
	 * @param array $schedules Existing cron schedules.
	 * @return array Modified cron schedules.
	 */
	public static function add_cron_schedules( $schedules ) {
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

	/**
	 * Check if plugin needs database update
	 *
	 * @since 1.0.0
	 * @return bool True if update needed, false otherwise.
	 */
	public static function needs_db_update() {
		$current_version = get_option( 'woo_auction_version', '0.0.0' );
		return version_compare( $current_version, WOO_AUCTION_VERSION, '<' );
	}

	/**
	 * Perform database update if needed
	 *
	 * @since 1.0.0
	 */
	public static function maybe_update_db() {
		if ( self::needs_db_update() ) {
			self::create_tables();
			update_option( 'woo_auction_version', WOO_AUCTION_VERSION );
			woo_auction_log( 'Database updated to version ' . WOO_AUCTION_VERSION );
		}
	}
}
