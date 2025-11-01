<?php
/**
 * Admin Handler Class
 *
 * Loads all admin functionality including scripts, styles, and menu items.
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
 * Class Woo_Auction_Admin
 *
 * Manages all admin-side functionality.
 *
 * @since 1.0.0
 */
class Woo_Auction_Admin {

	/**
	 * Product panel instance
	 *
	 * @since 1.0.0
	 * @var Woo_Auction_Product_Panel
	 */
	private $product_panel;

	/**
	 * Settings instance
	 *
	 * @since 1.0.0
	 * @var Woo_Auction_Settings
	 */
	private $settings;

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
	 * Load admin dependencies
	 *
	 * @since 1.0.0
	 */
	private function load_dependencies() {
		$this->product_panel = new Woo_Auction_Product_Panel();
		$this->settings      = new Woo_Auction_Settings();
	}

	/**
	 * Register admin hooks
	 *
	 * @since 1.0.0
	 */
	private function init_hooks() {
		// Enqueue admin scripts and styles
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

		// Add admin menu items
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );

		// Add custom columns to products list
		add_filter( 'manage_product_posts_columns', array( $this, 'add_auction_columns' ) );
		add_action( 'manage_product_posts_custom_column', array( $this, 'render_auction_columns' ), 10, 2 );

		// Make auction columns sortable
		add_filter( 'manage_edit-product_sortable_columns', array( $this, 'make_auction_columns_sortable' ) );

		// Add auction status filter
		add_action( 'restrict_manage_posts', array( $this, 'add_auction_status_filter' ) );
		add_filter( 'parse_query', array( $this, 'filter_by_auction_status' ) );

		// Add admin notices
		add_action( 'admin_notices', array( $this, 'display_admin_notices' ) );
	}

	/**
	 * Enqueue admin assets
	 *
	 * @since 1.0.0
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_assets( $hook ) {
		// Only load on product pages and settings pages
		$allowed_hooks = array( 'post.php', 'post-new.php', 'edit.php', 'woocommerce_page_wc-settings' );
		
		if ( ! in_array( $hook, $allowed_hooks, true ) ) {
			return;
		}

		// Check if we're on a product page
		$screen = get_current_screen();
		if ( $screen && 'product' !== $screen->post_type && 'woocommerce_page_wc-settings' !== $hook ) {
			return;
		}

		// Enqueue admin CSS
		wp_enqueue_style(
			'woo-auction-admin',
			WOO_AUCTION_URL . 'admin/assets/css/admin.css',
			array(),
			WOO_AUCTION_VERSION,
			'all'
		);

		// Enqueue admin JS
		wp_enqueue_script(
			'woo-auction-admin',
			WOO_AUCTION_URL . 'admin/assets/js/admin.js',
			array( 'jquery', 'jquery-ui-datepicker' ),
			WOO_AUCTION_VERSION,
			true
		);

		// Enqueue jQuery UI CSS for datepicker
		wp_enqueue_style( 'jquery-ui-style', '//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css', array(), '1.12.1' );

		// Localize script
		wp_localize_script(
			'woo-auction-admin',
			'wooAuctionAdmin',
			array(
				'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
				'dateFormat'  => 'yy-mm-dd',
				'timeFormat'  => 'HH:mm:ss',
				'i18n'        => array(
					'selectDate' => __( 'Select date', 'woo-live-auctions' ),
					'selectTime' => __( 'Select time', 'woo-live-auctions' ),
				),
			)
		);
	}

	/**
	 * Add admin menu items
	 *
	 * @since 1.0.0
	 */
	public function add_admin_menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Auctions', 'woo-live-auctions' ),
			__( 'Auctions', 'woo-live-auctions' ),
			'manage_woocommerce',
			'woo-auctions',
			array( $this, 'render_auctions_page' )
		);
	}

	/**
	 * Render auctions overview page
	 *
	 * @since 1.0.0
	 */
	public function render_auctions_page() {
		?>
		<div class="wrap woo-auction-admin-page">
			<h1><?php esc_html_e( 'Auctions Overview', 'woo-live-auctions' ); ?></h1>
			
			<div class="woo-auction-stats">
				<?php $this->render_auction_stats(); ?>
			</div>

			<div class="woo-auction-quick-links">
				<h2><?php esc_html_e( 'Quick Links', 'woo-live-auctions' ); ?></h2>
				<p>
					<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=product&auction=1' ) ); ?>" class="button button-primary">
						<?php esc_html_e( 'Create New Auction', 'woo-live-auctions' ); ?>
					</a>
					<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=product&auction_status=live' ) ); ?>" class="button">
						<?php esc_html_e( 'View Live Auctions', 'woo-live-auctions' ); ?>
					</a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=auctions' ) ); ?>" class="button">
						<?php esc_html_e( 'Auction Settings', 'woo-live-auctions' ); ?>
					</a>
				</p>
			</div>

			<div class="woo-auction-upgrade-notice">
				<h3><?php esc_html_e( '🚀 Upgrade to Pro for Full Proxy Bidding!', 'woo-live-auctions' ); ?></h3>
				<p><?php esc_html_e( 'The free version includes Limited Proxy Bidding. Upgrade to Pro for unlimited proxy wars, advanced analytics, and more!', 'woo-live-auctions' ); ?></p>
				<a href="#" target="_blank" class="button button-primary">
					<?php esc_html_e( 'Learn More', 'woo-live-auctions' ); ?>
				</a>
			</div>
		</div>
		<?php
	}

	/**
	 * Render auction statistics
	 *
	 * @since 1.0.0
	 */
	private function render_auction_stats() {
		// Get auction counts by status
		$live_count = $this->get_auction_count_by_status( 'live' );
		$future_count = $this->get_auction_count_by_status( 'future' );
		$ended_count = $this->get_auction_count_by_status( 'ended' );

		?>
		<div class="woo-auction-stat-box">
			<h3><?php echo esc_html( $live_count ); ?></h3>
			<p><?php esc_html_e( 'Live Auctions', 'woo-live-auctions' ); ?></p>
		</div>
		<div class="woo-auction-stat-box">
			<h3><?php echo esc_html( $future_count ); ?></h3>
			<p><?php esc_html_e( 'Scheduled Auctions', 'woo-live-auctions' ); ?></p>
		</div>
		<div class="woo-auction-stat-box">
			<h3><?php echo esc_html( $ended_count ); ?></h3>
			<p><?php esc_html_e( 'Ended Auctions', 'woo-live-auctions' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Get auction count by status
	 *
	 * @since 1.0.0
	 * @param string $status Auction status.
	 * @return int Auction count.
	 */
	private function get_auction_count_by_status( $status ) {
		$args = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'key'     => '_product_type',
					'value'   => 'auction',
					'compare' => '=',
				),
				array(
					'key'     => '_auction_status',
					'value'   => $status,
					'compare' => '=',
				),
			),
		);

		$query = new WP_Query( $args );
		return $query->post_count;
	}

	/**
	 * Add auction columns to products list
	 *
	 * @since 1.0.0
	 * @param array $columns Existing columns.
	 * @return array Modified columns.
	 */
	public function add_auction_columns( $columns ) {
		$new_columns = array();

		foreach ( $columns as $key => $value ) {
			$new_columns[ $key ] = $value;

			// Add auction columns after title
			if ( 'name' === $key ) {
				$new_columns['auction_status'] = __( 'Auction Status', 'woo-live-auctions' );
				$new_columns['auction_current_bid'] = __( 'Current Bid', 'woo-live-auctions' );
				$new_columns['auction_end_date'] = __( 'End Date', 'woo-live-auctions' );
			}
		}

		return $new_columns;
	}

	/**
	 * Render auction columns content
	 *
	 * @since 1.0.0
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
	 */
	public function render_auction_columns( $column, $post_id ) {
		$product = wc_get_product( $post_id );

		if ( ! $product || 'auction' !== $product->get_type() ) {
			return;
		}

		switch ( $column ) {
			case 'auction_status':
				$status = $product->get_auction_status();
				$status_labels = array(
					'live'   => '<span class="woo-auction-status live">' . esc_html__( 'Live', 'woo-live-auctions' ) . '</span>',
					'future' => '<span class="woo-auction-status future">' . esc_html__( 'Scheduled', 'woo-live-auctions' ) . '</span>',
					'ended'  => '<span class="woo-auction-status ended">' . esc_html__( 'Ended', 'woo-live-auctions' ) . '</span>',
				);
				echo isset( $status_labels[ $status ] ) ? wp_kses_post( $status_labels[ $status ] ) : '';
				break;

			case 'auction_current_bid':
				echo wp_kses_post( wc_price( $product->get_current_bid() ) );
				echo '<br><small>' . sprintf(
					/* translators: %d: bid count */
					esc_html( _n( '%d bid', '%d bids', $product->get_bid_count(), 'woo-live-auctions' ) ),
					$product->get_bid_count()
				) . '</small>';
				break;

			case 'auction_end_date':
				$end_date = $product->get_auction_end_date();
				if ( $end_date ) {
					echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $end_date ) ) );
				}
				break;
		}
	}

	/**
	 * Make auction columns sortable
	 *
	 * @since 1.0.0
	 * @param array $columns Existing sortable columns.
	 * @return array Modified sortable columns.
	 */
	public function make_auction_columns_sortable( $columns ) {
		$columns['auction_status'] = 'auction_status';
		$columns['auction_current_bid'] = 'auction_current_bid';
		$columns['auction_end_date'] = 'auction_end_date';
		return $columns;
	}

	/**
	 * Add auction status filter dropdown
	 *
	 * @since 1.0.0
	 */
	public function add_auction_status_filter() {
		global $typenow;

		if ( 'product' !== $typenow ) {
			return;
		}

		$current_status = isset( $_GET['auction_status'] ) ? sanitize_text_field( wp_unslash( $_GET['auction_status'] ) ) : '';

		?>
		<select name="auction_status">
			<option value=""><?php esc_html_e( 'All Auction Statuses', 'woo-live-auctions' ); ?></option>
			<option value="live" <?php selected( $current_status, 'live' ); ?>><?php esc_html_e( 'Live', 'woo-live-auctions' ); ?></option>
			<option value="future" <?php selected( $current_status, 'future' ); ?>><?php esc_html_e( 'Scheduled', 'woo-live-auctions' ); ?></option>
			<option value="ended" <?php selected( $current_status, 'ended' ); ?>><?php esc_html_e( 'Ended', 'woo-live-auctions' ); ?></option>
		</select>
		<?php
	}

	/**
	 * Filter products by auction status
	 *
	 * @since 1.0.0
	 * @param WP_Query $query Query object.
	 */
	public function filter_by_auction_status( $query ) {
		global $pagenow, $typenow;

		if ( 'edit.php' !== $pagenow || 'product' !== $typenow || ! $query->is_main_query() ) {
			return;
		}

		if ( ! isset( $_GET['auction_status'] ) || '' === $_GET['auction_status'] ) {
			return;
		}

		$status = sanitize_text_field( wp_unslash( $_GET['auction_status'] ) );

		$meta_query = $query->get( 'meta_query' );
		if ( ! is_array( $meta_query ) ) {
			$meta_query = array();
		}

		$meta_query[] = array(
			'key'     => '_auction_status',
			'value'   => $status,
			'compare' => '=',
		);

		$query->set( 'meta_query', $meta_query );
	}

	/**
	 * Display admin notices
	 *
	 * @since 1.0.0
	 */
	public function display_admin_notices() {
		// Check if cron is working
		if ( ! wp_next_scheduled( 'woo_auction_check_ending' ) ) {
			// Try to reschedule cron jobs if Woo_Auction_Cron class is available
			if ( class_exists( 'Woo_Auction_Cron' ) ) {
				Woo_Auction_Cron::schedule_cron_jobs();
			}

			// Check again after attempting to reschedule
			if ( ! wp_next_scheduled( 'woo_auction_check_ending' ) ) {
				?>
				<div class="notice notice-warning">
					<p>
						<?php esc_html_e( 'Woo Live Auctions: WP-Cron jobs are not scheduled. Auctions may not start or end automatically.', 'woo-live-auctions' ); ?>
					</p>
				</div>
				<?php
			}
		}
	}
}
