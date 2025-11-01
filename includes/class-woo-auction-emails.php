<?php
/**
 * Email Handler Class
 *
 * Registers and triggers custom WooCommerce emails for auction events.
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
 * Class Woo_Auction_Emails
 *
 * Manages all auction-related email notifications.
 *
 * @since 1.0.0
 */
class Woo_Auction_Emails {

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->init_hooks();
	}

	/**
	 * Register email hooks
	 *
	 * @since 1.0.0
	 */
	private function init_hooks() {
		// Register custom email classes with WooCommerce
		add_filter( 'woocommerce_email_classes', array( $this, 'register_email_classes' ) );

		// Trigger email actions
		add_action( 'woo_auction_send_outbid_email', array( $this, 'trigger_outbid_email' ), 10, 2 );
		add_action( 'woo_auction_send_won_email', array( $this, 'trigger_won_email' ), 10, 2 );
		add_action( 'woo_auction_send_ending_soon_email', array( $this, 'trigger_ending_soon_email' ), 10, 2 );
		add_action( 'woo_auction_send_proxy_war_email', array( $this, 'trigger_proxy_war_email' ), 10, 2 );
		add_action( 'woo_auction_send_proxy_war_admin_email', array( $this, 'trigger_proxy_war_admin_email' ), 10, 2 );

		// Add email templates path
		add_filter( 'woocommerce_locate_template', array( $this, 'locate_email_template' ), 10, 3 );
	}

	/**
	 * Register custom email classes
	 *
	 * @since 1.0.0
	 * @param array $emails Existing email classes.
	 * @return array Modified email classes.
	 */
	public function register_email_classes( $emails ) {
		// Note: Email classes would be defined in separate files in a full implementation
		// For now, we'll trigger emails using standard WooCommerce email system
		return $emails;
	}

	/**
	 * Trigger outbid email
	 *
	 * @since 1.0.0
	 * @param int $auction_id Auction product ID.
	 * @param int $user_id    User ID to notify.
	 */
	public function trigger_outbid_email( $auction_id, $user_id ) {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return;
		}

		$product = wc_get_product( $auction_id );
		if ( ! $product ) {
			return;
		}

		$to      = $user->user_email;
		$subject = sprintf(
			/* translators: %s: Product name */
			__( 'You have been outbid on %s', 'woo-live-auctions' ),
			$product->get_name()
		);

		$message = $this->get_email_content( 'outbid', array(
			'user'    => $user,
			'product' => $product,
		) );

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );

		wp_mail( $to, $subject, $message, $headers );

		woo_auction_log( "Outbid email sent to user {$user_id} for auction {$auction_id}" );
	}

	/**
	 * Trigger won email
	 *
	 * @since 1.0.0
	 * @param int $auction_id Auction product ID.
	 * @param int $user_id    Winner user ID.
	 */
	public function trigger_won_email( $auction_id, $user_id ) {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return;
		}

		$product = wc_get_product( $auction_id );
		if ( ! $product ) {
			return;
		}

		$to      = $user->user_email;
		$subject = sprintf(
			/* translators: %s: Product name */
			__( 'Congratulations! You won the auction for %s', 'woo-live-auctions' ),
			$product->get_name()
		);

		$message = $this->get_email_content( 'won', array(
			'user'    => $user,
			'product' => $product,
		) );

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );

		wp_mail( $to, $subject, $message, $headers );

		woo_auction_log( "Won email sent to user {$user_id} for auction {$auction_id}" );
	}

	/**
	 * Trigger ending soon email
	 *
	 * @since 1.0.0
	 * @param int $auction_id Auction product ID.
	 * @param int $user_id    User ID to notify.
	 */
	public function trigger_ending_soon_email( $auction_id, $user_id ) {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return;
		}

		$product = wc_get_product( $auction_id );
		if ( ! $product ) {
			return;
		}

		$to      = $user->user_email;
		$subject = sprintf(
			/* translators: %s: Product name */
			__( 'Auction ending soon: %s', 'woo-live-auctions' ),
			$product->get_name()
		);

		$message = $this->get_email_content( 'ending-soon', array(
			'user'    => $user,
			'product' => $product,
		) );

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );

		wp_mail( $to, $subject, $message, $headers );

		woo_auction_log( "Ending soon email sent to user {$user_id} for auction {$auction_id}" );
	}

	/**
	 * Trigger proxy war email (participant notification)
	 *
	 * @since 1.0.0
	 * @param int $auction_id Auction product ID.
	 * @param int $user_id    User ID to notify.
	 */
	public function trigger_proxy_war_email( $auction_id, $user_id ) {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return;
		}

		$product = wc_get_product( $auction_id );
		if ( ! $product ) {
			return;
		}

		$to      = $user->user_email;
		$subject = sprintf(
			/* translators: %s: Product name */
			__( 'Bidding Update: %s', 'woo-live-auctions' ),
			$product->get_name()
		);

		$message = $this->get_email_content( 'proxy-war', array(
			'user'    => $user,
			'product' => $product,
		) );

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );

		wp_mail( $to, $subject, $message, $headers );

		woo_auction_log( "Proxy war participant email sent to user {$user_id} for auction {$auction_id}" );
	}

	/**
	 * Trigger proxy war admin notification email
	 *
	 * @since 1.0.0
	 * @param int $auction_id     Auction product ID.
	 * @param array $active_proxies Array of active proxy bid objects.
	 */
	public function trigger_proxy_war_admin_email( $auction_id, $active_proxies ) {
		$admin_email = get_option( 'admin_email' );
		if ( ! $admin_email ) {
			return;
		}

		$product = wc_get_product( $auction_id );
		if ( ! $product ) {
			return;
		}

		$to      = $admin_email;
		$subject = sprintf(
			/* translators: %s: Product name */
			__( 'Proxy War Triggered: %s - Upgrade Opportunity!', 'woo-live-auctions' ),
			$product->get_name()
		);

		$message = $this->get_email_content( 'proxy-war-admin', array(
			'product'        => $product,
			'active_proxies' => $active_proxies,
		) );

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );

		wp_mail( $to, $subject, $message, $headers );

		woo_auction_log( "Proxy war admin notification sent for auction {$auction_id}" );
	}

	/**
	 * Get email content from template
	 *
	 * @since 1.0.0
	 * @param string $template_name Template name (without .php).
	 * @param array  $args          Template arguments.
	 * @return string Email content HTML.
	 */
	private function get_email_content( $template_name, $args = array() ) {
		// Extract args for template
		extract( $args ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract

		// Start output buffering
		ob_start();

		// Load template
		$template_path = WOO_AUCTION_PATH . 'templates/emails/auction-' . $template_name . '.php';
		
		if ( file_exists( $template_path ) ) {
			include $template_path;
		} else {
			// Fallback to simple message
			echo '<p>' . esc_html__( 'Auction notification', 'woo-live-auctions' ) . '</p>';
		}

		// Get content and clean buffer
		$content = ob_get_clean();

		// Wrap in WooCommerce email template
		return $this->wrap_email_content( $content );
	}

	/**
	 * Wrap email content in WooCommerce email template
	 *
	 * @since 1.0.0
	 * @param string $content Email content.
	 * @return string Wrapped email content.
	 */
	private function wrap_email_content( $content ) {
		ob_start();
		
		// Use WooCommerce email header
		do_action( 'woocommerce_email_header', __( 'Auction Notification', 'woo-live-auctions' ), null );
		
		echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		
		// Use WooCommerce email footer
		do_action( 'woocommerce_email_footer', null );
		
		return ob_get_clean();
	}

	/**
	 * Locate email template (allow theme override)
	 *
	 * @since 1.0.0
	 * @param string $template      Template path.
	 * @param string $template_name Template name.
	 * @param string $template_path Template path.
	 * @return string Modified template path.
	 */
	public function locate_email_template( $template, $template_name, $template_path ) {
		// Check if it's an auction email template
		if ( strpos( $template_name, 'emails/auction-' ) !== false ) {
			// Check theme override
			$theme_template = get_stylesheet_directory() . '/woocommerce/' . $template_name;
			
			if ( file_exists( $theme_template ) ) {
				return $theme_template;
			}
			
			// Use plugin template
			$plugin_template = WOO_AUCTION_PATH . 'templates/' . $template_name;
			
			if ( file_exists( $plugin_template ) ) {
				return $plugin_template;
			}
		}
		
		return $template;
	}
}
