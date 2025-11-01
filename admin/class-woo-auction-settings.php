<?php
/**
 * Settings Class
 *
 * Adds auction settings tab to WooCommerce settings.
 *
 * @package    Woo_Live_Auctions
 * @subpackage Woo_Live_Auctions/admin
 * @since      1.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Woo_Auction_Settings
 *
 * Manages auction plugin settings in WooCommerce.
 *
 * @since 1.0.0
 */
class Woo_Auction_Settings {

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
		// Add settings tab
		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_tab' ), 50 );

		// Add settings fields
		add_action( 'woocommerce_settings_tabs_auctions', array( $this, 'output_settings' ) );

		// Save settings
		add_action( 'woocommerce_update_options_auctions', array( $this, 'save_settings' ) );
	}

	/**
	 * Add settings tab to WooCommerce
	 *
	 * @since 1.0.0
	 * @param array $tabs Existing tabs.
	 * @return array Modified tabs.
	 */
	public function add_settings_tab( $tabs ) {
		$tabs['auctions'] = __( 'Auctions', 'woo-live-auctions' );
		return $tabs;
	}

	/**
	 * Output settings fields
	 *
	 * @since 1.0.0
	 */
	public function output_settings() {
		woocommerce_admin_fields( $this->get_settings() );
	}

	/**
	 * Save settings
	 *
	 * @since 1.0.0
	 */
	public function save_settings() {
		woocommerce_update_options( $this->get_settings() );
	}

	/**
	 * Get all settings fields
	 *
	 * @since 1.0.0
	 * @return array Settings fields.
	 */
	public function get_settings() {
		$settings = array(
			array(
				'title' => __( 'Auction Settings', 'woo-live-auctions' ),
				'type'  => 'title',
				'desc'  => __( 'Configure auction functionality and behavior.', 'woo-live-auctions' ),
				'id'    => 'woo_auction_general_settings',
			),

			array(
				'title'   => __( 'Enable Proxy Bidding', 'woo-live-auctions' ),
				'desc'    => __( 'Allow users to set maximum bids (Limited in Free version)', 'woo-live-auctions' ),
				'id'      => 'woo_auction_enable_proxy_bidding',
				'default' => 'yes',
				'type'    => 'checkbox',
			),

			array(
				'title'   => __( 'Enable Buy Now', 'woo-live-auctions' ),
				'desc'    => __( 'Allow instant purchase before bidding starts', 'woo-live-auctions' ),
				'id'      => 'woo_auction_enable_buy_now',
				'default' => 'yes',
				'type'    => 'checkbox',
			),

			array(
				'title'   => __( 'Enable Reserve Price', 'woo-live-auctions' ),
				'desc'    => __( 'Allow setting minimum acceptable bid', 'woo-live-auctions' ),
				'id'      => 'woo_auction_enable_reserve_price',
				'default' => 'yes',
				'type'    => 'checkbox',
			),

			array(
				'title'   => __( 'Enable Watchlist', 'woo-live-auctions' ),
				'desc'    => __( 'Allow users to watch auctions', 'woo-live-auctions' ),
				'id'      => 'woo_auction_enable_watchlist',
				'default' => 'yes',
				'type'    => 'checkbox',
			),

			array(
				'title'   => __( 'Allow Guest Bidding', 'woo-live-auctions' ),
				'desc'    => __( 'Allow non-logged-in users to place bids', 'woo-live-auctions' ),
				'id'      => 'woo_auction_allow_guest_bidding',
				'default' => 'no',
				'type'    => 'checkbox',
			),

			array(
				'type' => 'sectionend',
				'id'   => 'woo_auction_general_settings',
			),

			array(
				'title' => __( 'Display Settings', 'woo-live-auctions' ),
				'type'  => 'title',
				'desc'  => __( 'Configure how auctions are displayed.', 'woo-live-auctions' ),
				'id'    => 'woo_auction_display_settings',
			),

			array(
				'title'   => __( 'Show Auction Badge', 'woo-live-auctions' ),
				'desc'    => __( 'Display "Auction" badge on product listings', 'woo-live-auctions' ),
				'id'      => 'woo_auction_show_auction_badge',
				'default' => 'yes',
				'type'    => 'checkbox',
			),

			array(
				'title'   => __( 'Badge Text', 'woo-live-auctions' ),
				'desc'    => __( 'Text to display on auction badge', 'woo-live-auctions' ),
				'id'      => 'woo_auction_badge_text',
				'default' => __( 'Auction', 'woo-live-auctions' ),
				'type'    => 'text',
			),

			array(
				'title'   => __( 'Show Bid History', 'woo-live-auctions' ),
				'desc'    => __( 'Display bid history on product pages', 'woo-live-auctions' ),
				'id'      => 'woo_auction_show_bid_history',
				'default' => 'yes',
				'type'    => 'checkbox',
			),

			array(
				'title'   => __( 'Bid History Limit', 'woo-live-auctions' ),
				'desc'    => __( 'Number of recent bids to display', 'woo-live-auctions' ),
				'id'      => 'woo_auction_bid_history_limit',
				'default' => '10',
				'type'    => 'number',
				'custom_attributes' => array(
					'min' => '1',
					'max' => '50',
				),
			),

			array(
				'title'   => __( 'AJAX Refresh Interval', 'woo-live-auctions' ),
				'desc'    => __( 'Seconds between automatic updates (lower = more server load)', 'woo-live-auctions' ),
				'id'      => 'woo_auction_ajax_refresh_interval',
				'default' => '5',
				'type'    => 'number',
				'custom_attributes' => array(
					'min' => '3',
					'max' => '60',
				),
			),

			array(
				'type' => 'sectionend',
				'id'   => 'woo_auction_display_settings',
			),

			array(
				'title' => __( 'Email Notifications', 'woo-live-auctions' ),
				'type'  => 'title',
				'desc'  => __( 'Configure email notifications for auction events.', 'woo-live-auctions' ),
				'id'    => 'woo_auction_email_settings',
			),

			array(
				'title'   => __( 'Outbid Email', 'woo-live-auctions' ),
				'desc'    => __( 'Send email when user is outbid', 'woo-live-auctions' ),
				'id'      => 'woo_auction_enable_outbid_email',
				'default' => 'yes',
				'type'    => 'checkbox',
			),

			array(
				'title'   => __( 'Won Email', 'woo-live-auctions' ),
				'desc'    => __( 'Send email when user wins auction', 'woo-live-auctions' ),
				'id'      => 'woo_auction_enable_won_email',
				'default' => 'yes',
				'type'    => 'checkbox',
			),

			array(
				'title'   => __( 'Ending Soon Email', 'woo-live-auctions' ),
				'desc'    => __( 'Send email when auction is ending soon', 'woo-live-auctions' ),
				'id'      => 'woo_auction_enable_ending_soon_email',
				'default' => 'yes',
				'type'    => 'checkbox',
			),

			array(
				'title'   => __( 'Ending Soon Threshold', 'woo-live-auctions' ),
				'desc'    => __( 'Minutes before end to send notification', 'woo-live-auctions' ),
				'id'      => 'woo_auction_ending_soon_threshold',
				'default' => '60',
				'type'    => 'number',
				'custom_attributes' => array(
					'min' => '5',
					'max' => '1440',
				),
			),

			array(
				'title'   => __( 'Proxy War Email (Freemium)', 'woo-live-auctions' ),
				'desc'    => __( 'Send email when proxy bidding war is triggered', 'woo-live-auctions' ),
				'id'      => 'woo_auction_enable_proxy_war_email',
				'default' => 'yes',
				'type'    => 'checkbox',
			),

			array(
				'type' => 'sectionend',
				'id'   => 'woo_auction_email_settings',
			),

			array(
				'title' => __( 'Advanced Settings', 'woo-live-auctions' ),
				'type'  => 'title',
				'desc'  => __( 'Advanced configuration options.', 'woo-live-auctions' ),
				'id'    => 'woo_auction_advanced_settings',
			),

			array(
				'title'   => __( 'Bid Increment Type', 'woo-live-auctions' ),
				'desc'    => __( 'How bid increments are calculated', 'woo-live-auctions' ),
				'id'      => 'woo_auction_bid_increment_type',
				'default' => 'fixed',
				'type'    => 'select',
				'options' => array(
					'fixed'      => __( 'Fixed Amount', 'woo-live-auctions' ),
					'percentage' => __( 'Percentage (Pro)', 'woo-live-auctions' ),
				),
			),

			array(
				'title'   => __( 'Default Bid Increment', 'woo-live-auctions' ),
				'desc'    => __( 'Default increment if not set per product', 'woo-live-auctions' ),
				'id'      => 'woo_auction_default_bid_increment',
				'default' => '1.00',
				'type'    => 'text',
			),

			array(
				'title'   => __( 'Countdown Format', 'woo-live-auctions' ),
				'desc'    => __( 'Format for countdown timer', 'woo-live-auctions' ),
				'id'      => 'woo_auction_countdown_format',
				'default' => 'dhms',
				'type'    => 'select',
				'options' => array(
					'dhms' => __( 'Days, Hours, Minutes, Seconds', 'woo-live-auctions' ),
					'hms'  => __( 'Hours, Minutes, Seconds', 'woo-live-auctions' ),
					'ms'   => __( 'Minutes, Seconds', 'woo-live-auctions' ),
				),
			),

			array(
				'type' => 'sectionend',
				'id'   => 'woo_auction_advanced_settings',
			),

			array(
				'title' => __( 'Upgrade to Pro', 'woo-live-auctions' ),
				'type'  => 'title',
				'desc'  => sprintf(
					/* translators: %s: upgrade URL */
					wp_kses_post( __( '🚀 <strong>Unlock Full Proxy Bidding!</strong> The free version includes Limited Proxy Bidding. <a href="%s" target="_blank">Upgrade to Pro</a> for unlimited proxy wars, advanced analytics, bulk auction tools, and priority support!', 'woo-live-auctions' ) ),
					'https://example.com/woo-live-auctions-pro'
				),
				'id'    => 'woo_auction_upgrade_notice',
			),

			array(
				'type' => 'sectionend',
				'id'   => 'woo_auction_upgrade_notice',
			),
		);

		return apply_filters( 'woo_auction_settings', $settings );
	}
}
