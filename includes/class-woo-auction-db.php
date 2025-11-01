<?php
/**
 * Database Helper Class
 *
 * Handles all custom table database operations with proper security.
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
 * Class Woo_Auction_DB
 *
 * Provides secure database operations for auction log, proxy bids, and activity tables.
 *
 * @since 1.0.0
 */
class Woo_Auction_DB {

	/**
	 * WordPress database object
	 *
	 * @since 1.0.0
	 * @var wpdb
	 */
	private $wpdb;

	/**
	 * Log table name with prefix
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $log_table;

	/**
	 * Proxy bids table name with prefix
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $proxy_table;

	/**
	 * Activity table name with prefix
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $activity_table;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		global $wpdb;
		$this->wpdb           = $wpdb;
		$this->log_table      = $wpdb->prefix . WOO_AUCTION_LOG_TABLE;
		$this->proxy_table    = $wpdb->prefix . WOO_AUCTION_PROXY_TABLE;
		$this->activity_table = $wpdb->prefix . WOO_AUCTION_ACTIVITY_TABLE;
	}

	/**
	 * Insert a bid into the log table
	 *
	 * @since 1.0.0
	 * @param int    $auction_id Auction product ID.
	 * @param int    $user_id    User ID who placed the bid.
	 * @param float  $bid        Bid amount.
	 * @param bool   $is_proxy   Whether this is a proxy bid.
	 * @return int|false Insert ID on success, false on failure.
	 */
	public function insert_bid_log( $auction_id, $user_id, $bid, $is_proxy = false ) {
		$result = $this->wpdb->insert(
			$this->log_table,
			array(
				'auction_id' => absint( $auction_id ),
				'user_id'    => absint( $user_id ),
				'bid'        => floatval( $bid ),
				'date'       => current_time( 'mysql' ),
				'is_proxy'   => $is_proxy ? 1 : 0,
			),
			array( '%d', '%d', '%f', '%s', '%d' )
		);

		if ( false === $result ) {
			woo_auction_log( 'Failed to insert bid log: ' . $this->wpdb->last_error, 'error' );
			return false;
		}

		return $this->wpdb->insert_id;
	}

	/**
	 * Get bid history for an auction
	 *
	 * @since 1.0.0
	 * @param int $auction_id Auction product ID.
	 * @param int $limit      Number of bids to retrieve.
	 * @return array Array of bid objects.
	 */
	public function get_bid_history( $auction_id, $limit = 10 ) {
		$results = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->log_table} 
				WHERE auction_id = %d 
				ORDER BY date DESC 
				LIMIT %d",
				absint( $auction_id ),
				absint( $limit )
			)
		);

		return $results ? $results : array();
	}

	/**
	 * Get total bid count for an auction
	 *
	 * @since 1.0.0
	 * @param int $auction_id Auction product ID.
	 * @return int Total number of bids.
	 */
	public function get_bid_count( $auction_id ) {
		$count = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->log_table} WHERE auction_id = %d",
				absint( $auction_id )
			)
		);

		return absint( $count );
	}

	/**
	 * Get highest bid for an auction
	 *
	 * @since 1.0.0
	 * @param int $auction_id Auction product ID.
	 * @return object|null Bid object or null if no bids.
	 */
	public function get_highest_bid( $auction_id ) {
		$result = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->log_table} 
				WHERE auction_id = %d 
				ORDER BY bid DESC, date ASC 
				LIMIT 1",
				absint( $auction_id )
			)
		);

		return $result;
	}

	/**
	 * Get user's bids for an auction
	 *
	 * @since 1.0.0
	 * @param int $auction_id Auction product ID.
	 * @param int $user_id    User ID.
	 * @return array Array of bid objects.
	 */
	public function get_user_bids( $auction_id, $user_id ) {
		$results = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->log_table} 
				WHERE auction_id = %d AND user_id = %d 
				ORDER BY date DESC",
				absint( $auction_id ),
				absint( $user_id )
			)
		);

		return $results ? $results : array();
	}

	/**
	 * Insert or update a proxy bid
	 *
	 * @since 1.0.0
	 * @param int   $auction_id Auction product ID.
	 * @param int   $user_id    User ID.
	 * @param float $max_bid    Maximum bid amount.
	 * @return bool True on success, false on failure.
	 */
	public function set_proxy_bid( $auction_id, $user_id, $max_bid ) {
		$existing = $this->get_proxy_bid( $auction_id, $user_id );

		if ( $existing ) {
			// Update existing proxy bid
			$result = $this->wpdb->update(
				$this->proxy_table,
				array(
					'max_bid'      => floatval( $max_bid ),
					'is_active'    => 1,
					'last_updated' => current_time( 'mysql' ),
				),
				array(
					'auction_id' => absint( $auction_id ),
					'user_id'    => absint( $user_id ),
				),
				array( '%f', '%d', '%s' ),
				array( '%d', '%d' )
			);
		} else {
			// Insert new proxy bid
			$result = $this->wpdb->insert(
				$this->proxy_table,
				array(
					'auction_id'   => absint( $auction_id ),
					'user_id'      => absint( $user_id ),
					'max_bid'      => floatval( $max_bid ),
					'is_active'    => 1,
					'last_updated' => current_time( 'mysql' ),
				),
				array( '%d', '%d', '%f', '%d', '%s' )
			);
		}

		if ( false === $result ) {
			woo_auction_log( 'Failed to set proxy bid: ' . $this->wpdb->last_error, 'error' );
			return false;
		}

		return true;
	}

	/**
	 * Get proxy bid for a user on an auction
	 *
	 * @since 1.0.0
	 * @param int $auction_id Auction product ID.
	 * @param int $user_id    User ID.
	 * @return object|null Proxy bid object or null.
	 */
	public function get_proxy_bid( $auction_id, $user_id ) {
		$result = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->proxy_table} 
				WHERE auction_id = %d AND user_id = %d",
				absint( $auction_id ),
				absint( $user_id )
			)
		);

		return $result;
	}

	/**
	 * Get all active proxy bids for an auction
	 *
	 * @since 1.0.0
	 * @param int $auction_id Auction product ID.
	 * @return array Array of proxy bid objects.
	 */
	public function get_active_proxy_bids( $auction_id ) {
		$results = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->proxy_table} 
				WHERE auction_id = %d AND is_active = 1 
				ORDER BY max_bid DESC",
				absint( $auction_id )
			)
		);

		return $results ? $results : array();
	}

	/**
	 * Deactivate a proxy bid
	 *
	 * @since 1.0.0
	 * @param int $auction_id Auction product ID.
	 * @param int $user_id    User ID.
	 * @return bool True on success, false on failure.
	 */
	public function deactivate_proxy_bid( $auction_id, $user_id ) {
		$result = $this->wpdb->update(
			$this->proxy_table,
			array(
				'is_active'    => 0,
				'last_updated' => current_time( 'mysql' ),
			),
			array(
				'auction_id' => absint( $auction_id ),
				'user_id'    => absint( $user_id ),
			),
			array( '%d', '%s' ),
			array( '%d', '%d' )
		);

		if ( false === $result ) {
			woo_auction_log( 'Failed to deactivate proxy bid: ' . $this->wpdb->last_error, 'error' );
			return false;
		}

		return true;
	}

	/**
	 * Deactivate all proxy bids for an auction
	 *
	 * @since 1.0.0
	 * @param int $auction_id Auction product ID.
	 * @return bool True on success, false on failure.
	 */
	public function deactivate_all_proxy_bids( $auction_id ) {
		$result = $this->wpdb->update(
			$this->proxy_table,
			array(
				'is_active'    => 0,
				'last_updated' => current_time( 'mysql' ),
			),
			array( 'auction_id' => absint( $auction_id ) ),
			array( '%d', '%s' ),
			array( '%d' )
		);

		if ( false === $result ) {
			woo_auction_log( 'Failed to deactivate all proxy bids: ' . $this->wpdb->last_error, 'error' );
			return false;
		}

		return true;
	}

	/**
	 * Add auction to user's watchlist
	 *
	 * @since 1.0.0
	 * @param int $auction_id Auction product ID.
	 * @param int $user_id    User ID.
	 * @return bool True on success, false on failure.
	 */
	public function add_to_watchlist( $auction_id, $user_id ) {
		$result = $this->wpdb->insert(
			$this->activity_table,
			array(
				'auction_id' => absint( $auction_id ),
				'user_id'    => absint( $user_id ),
				'action'     => 'watch',
			),
			array( '%d', '%d', '%s' )
		);

		if ( false === $result ) {
			// Check if already exists (duplicate key error is expected)
			if ( $this->is_watching( $auction_id, $user_id ) ) {
				return true;
			}
			woo_auction_log( 'Failed to add to watchlist: ' . $this->wpdb->last_error, 'error' );
			return false;
		}

		return true;
	}

	/**
	 * Remove auction from user's watchlist
	 *
	 * @since 1.0.0
	 * @param int $auction_id Auction product ID.
	 * @param int $user_id    User ID.
	 * @return bool True on success, false on failure.
	 */
	public function remove_from_watchlist( $auction_id, $user_id ) {
		$result = $this->wpdb->delete(
			$this->activity_table,
			array(
				'auction_id' => absint( $auction_id ),
				'user_id'    => absint( $user_id ),
				'action'     => 'watch',
			),
			array( '%d', '%d', '%s' )
		);

		if ( false === $result ) {
			woo_auction_log( 'Failed to remove from watchlist: ' . $this->wpdb->last_error, 'error' );
			return false;
		}

		return true;
	}

	/**
	 * Check if user is watching an auction
	 *
	 * @since 1.0.0
	 * @param int $auction_id Auction product ID.
	 * @param int $user_id    User ID.
	 * @return bool True if watching, false otherwise.
	 */
	public function is_watching( $auction_id, $user_id ) {
		$count = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->activity_table} 
				WHERE auction_id = %d AND user_id = %d AND action = 'watch'",
				absint( $auction_id ),
				absint( $user_id )
			)
		);

		return absint( $count ) > 0;
	}

	/**
	 * Get user's watchlist
	 *
	 * @since 1.0.0
	 * @param int $user_id User ID.
	 * @return array Array of auction IDs.
	 */
	public function get_user_watchlist( $user_id ) {
		$results = $this->wpdb->get_col(
			$this->wpdb->prepare(
				"SELECT auction_id FROM {$this->activity_table} 
				WHERE user_id = %d AND action = 'watch'",
				absint( $user_id )
			)
		);

		return $results ? array_map( 'absint', $results ) : array();
	}

	/**
	 * Get auctions where user has placed bids
	 *
	 * @since 1.0.0
	 * @param int $user_id User ID.
	 * @return array Array of auction IDs.
	 */
	public function get_user_bid_auctions( $user_id ) {
		$results = $this->wpdb->get_col(
			$this->wpdb->prepare(
				"SELECT DISTINCT auction_id FROM {$this->log_table} 
				WHERE user_id = %d 
				ORDER BY date DESC",
				absint( $user_id )
			)
		);

		return $results ? array_map( 'absint', $results ) : array();
	}

	/**
	 * Delete all data for an auction (when product is deleted)
	 *
	 * @since 1.0.0
	 * @param int $auction_id Auction product ID.
	 * @return bool True on success, false on failure.
	 */
	public function delete_auction_data( $auction_id ) {
		$auction_id = absint( $auction_id );

		// Delete bid logs
		$this->wpdb->delete( $this->log_table, array( 'auction_id' => $auction_id ), array( '%d' ) );

		// Delete proxy bids
		$this->wpdb->delete( $this->proxy_table, array( 'auction_id' => $auction_id ), array( '%d' ) );

		// Delete activity records
		$this->wpdb->delete( $this->activity_table, array( 'auction_id' => $auction_id ), array( '%d' ) );

		return true;
	}
}
