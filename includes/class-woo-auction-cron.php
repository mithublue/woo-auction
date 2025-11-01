<?php
/**
 * Cron Handler Class
 *
 * Manages WP-Cron jobs for auction lifecycle (starting and ending auctions).
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
 * Class Woo_Auction_Cron
 *
 * Handles scheduled tasks for auction status updates and winner notifications.
 *
 * @since 1.0.0
 */
class Woo_Auction_Cron {

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
	 * Schedule WP-Cron jobs for auction management
	 *
	 * @since 1.0.0
	 */
	public static function schedule_cron_jobs() {
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
	 * Register cron hooks
	 *
	 * @since 1.0.0
	 */
	private function init_hooks() {
		// Check for starting auctions
		add_action( 'woo_auction_check_starting', array( $this, 'check_starting_auctions' ) );

		// Check for ending auctions
		add_action( 'woo_auction_check_ending', array( $this, 'check_ending_auctions' ) );
	}

	/**
	 * Check and activate auctions that should start
	 *
	 * Runs every 5 minutes via WP-Cron.
	 *
	 * @since 1.0.0
	 */
	public function check_starting_auctions() {
		$current_time = current_time( 'mysql' );

		// Query for auctions that should start
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
					'key'     => '_auction_status',
					'value'   => 'future',
					'compare' => '=',
				),
				array(
					'key'     => '_auction_start_date',
					'value'   => $current_time,
					'compare' => '<=',
					'type'    => 'DATETIME',
				),
			),
		);

		$query = new WP_Query( $args );

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$auction_id = get_the_ID();
				$this->start_auction( $auction_id );
			}
			wp_reset_postdata();
		}

		woo_auction_log( 'Checked for starting auctions. Found: ' . $query->post_count );
	}

	/**
	 * Check and end auctions that have expired
	 *
	 * Runs every minute via WP-Cron.
	 *
	 * @since 1.0.0
	 */
	public function check_ending_auctions() {
		$current_time = current_time( 'mysql' );

		// Query for auctions that should end
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
					'key'     => '_auction_status',
					'value'   => 'live',
					'compare' => '=',
				),
				array(
					'key'     => '_auction_end_date',
					'value'   => $current_time,
					'compare' => '<=',
					'type'    => 'DATETIME',
				),
			),
		);

		$query = new WP_Query( $args );

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$auction_id = get_the_ID();
				$this->end_auction( $auction_id );
			}
			wp_reset_postdata();
		}

		// Also check for "ending soon" notifications
		$this->check_ending_soon_notifications();

		woo_auction_log( 'Checked for ending auctions. Found: ' . $query->post_count );
	}

	/**
	 * Start an auction
	 *
	 * @since 1.0.0
	 * @param int $auction_id Auction product ID.
	 */
	private function start_auction( $auction_id ) {
		// Update auction status
		update_post_meta( $auction_id, '_auction_status', 'live' );

		// Initialize bid count if not set
		$bid_count = get_post_meta( $auction_id, '_auction_bid_count', true );
		if ( '' === $bid_count ) {
			update_post_meta( $auction_id, '_auction_bid_count', 0 );
		}

		// Initialize current bid with start price
		$start_price = get_post_meta( $auction_id, '_auction_start_price', true );
		if ( $start_price ) {
			update_post_meta( $auction_id, '_auction_current_bid', $start_price );
		}

		do_action( 'woo_auction_started', $auction_id );

		woo_auction_log( "Auction {$auction_id} started." );
	}

	/**
	 * End an auction and determine winner
	 *
	 * @since 1.0.0
	 * @param int $auction_id Auction product ID.
	 */
	private function end_auction( $auction_id ) {
		$product = wc_get_product( $auction_id );

		if ( ! $product || 'auction' !== $product->get_type() ) {
			return;
		}

		// Update auction status
		update_post_meta( $auction_id, '_auction_status', 'ended' );

		// Deactivate all proxy bids
		$this->db->deactivate_all_proxy_bids( $auction_id );

		// Determine winner
		$current_bidder = $product->get_current_bidder();
		$reserve_price  = $product->get_reserve_price();
		$current_bid    = $product->get_current_bid();

		// Check if there's a bidder and reserve is met
		if ( $current_bidder && ( null === $reserve_price || $current_bid >= $reserve_price ) ) {
			// Set winner
			$product->set_winner( $current_bidder );

			// Send winner email
			$this->send_winner_email( $auction_id, $current_bidder );

			// Send loser emails to other bidders
			$this->send_loser_emails( $auction_id, $current_bidder );

			woo_auction_log( "Auction {$auction_id} ended. Winner: User {$current_bidder}" );
		} else {
			// No winner (reserve not met or no bids)
			woo_auction_log( "Auction {$auction_id} ended with no winner." );
		}

		do_action( 'woo_auction_ended', $auction_id, $current_bidder );
	}

	/**
	 * Check for auctions ending soon and send notifications
	 *
	 * @since 1.0.0
	 */
	private function check_ending_soon_notifications() {
		if ( 'yes' !== get_option( 'woo_auction_enable_ending_soon_email', 'yes' ) ) {
			return;
		}

		$threshold = absint( get_option( 'woo_auction_ending_soon_threshold', 60 ) ); // minutes
		$current_time = current_time( 'timestamp' );
		$threshold_time = date( 'Y-m-d H:i:s', $current_time + ( $threshold * MINUTE_IN_SECONDS ) );

		// Query for auctions ending soon
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
					'key'     => '_auction_status',
					'value'   => 'live',
					'compare' => '=',
				),
				array(
					'key'     => '_auction_end_date',
					'value'   => array( current_time( 'mysql' ), $threshold_time ),
					'compare' => 'BETWEEN',
					'type'    => 'DATETIME',
				),
				array(
					'key'     => '_auction_ending_soon_sent',
					'compare' => 'NOT EXISTS',
				),
			),
		);

		$query = new WP_Query( $args );

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$auction_id = get_the_ID();
				$this->send_ending_soon_notifications( $auction_id );
				
				// Mark as sent
				update_post_meta( $auction_id, '_auction_ending_soon_sent', 1 );
			}
			wp_reset_postdata();
		}
	}

	/**
	 * Send ending soon notifications to bidders and watchers
	 *
	 * @since 1.0.0
	 * @param int $auction_id Auction product ID.
	 */
	private function send_ending_soon_notifications( $auction_id ) {
		// Get all users who have bid
		$bid_auctions = $this->db->get_user_bid_auctions( $auction_id );
		
		// Get all users watching
		global $wpdb;
		$activity_table = $wpdb->prefix . WOO_AUCTION_ACTIVITY_TABLE;
		$watchers = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT user_id FROM {$activity_table} WHERE auction_id = %d AND action = 'watch'",
				$auction_id
			)
		);

		// Combine and deduplicate
		$users_to_notify = array_unique( array_merge( $bid_auctions, $watchers ) );

		foreach ( $users_to_notify as $user_id ) {
			do_action( 'woo_auction_send_ending_soon_email', $auction_id, $user_id );
		}

		woo_auction_log( "Sent ending soon notifications for auction {$auction_id} to " . count( $users_to_notify ) . ' users.' );
	}

	/**
	 * Send winner email
	 *
	 * @since 1.0.0
	 * @param int $auction_id Auction product ID.
	 * @param int $winner_id  Winner user ID.
	 */
	private function send_winner_email( $auction_id, $winner_id ) {
		if ( 'yes' !== get_option( 'woo_auction_enable_won_email', 'yes' ) ) {
			return;
		}

		do_action( 'woo_auction_send_won_email', $auction_id, $winner_id );
	}

	/**
	 * Send loser emails to non-winning bidders
	 *
	 * @since 1.0.0
	 * @param int $auction_id Auction product ID.
	 * @param int $winner_id  Winner user ID.
	 */
	private function send_loser_emails( $auction_id, $winner_id ) {
		// Get all users who bid (excluding winner)
		global $wpdb;
		$log_table = $wpdb->prefix . WOO_AUCTION_LOG_TABLE;
		
		$losers = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT user_id FROM {$log_table} 
				WHERE auction_id = %d AND user_id != %d",
				$auction_id,
				$winner_id
			)
		);

		foreach ( $losers as $user_id ) {
			do_action( 'woo_auction_send_lost_email', $auction_id, $user_id );
		}
	}
}
