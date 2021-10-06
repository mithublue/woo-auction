<?php
namespace wauc\core;

class Auction_Report {

    /**
     * Instance
     *
     * @since 1.0.0
     *
     * @access private
     * @static
     */
    private static $_instance = null;
	private static $page_slug = 'wauc-auction-report';

    /**
     * Instance
     *
     * Ensures only one instance of the class is loaded or can be loaded.
     *
     * @since 1.0.0
     *
     * @access public
     * @static
     *
     * @return ${ClassName} An instance of the class.
     */
    public static function instance() {

        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;

    }

    public function __construct() {
	    add_action( 'admin_menu', array( $this, 'admin_report_page' ) );
    }

	public function admin_report_page() {
		add_submenu_page( 'edit.php?post_type=product', __( 'Auction Report', 'wauc' ), __( 'Auction Report', 'wauc' ), 'manage_options', self::$page_slug, array( $this, 'report_page_content' ) );
	}

	public function report_page_content() { ?>
		<div id="wauc_auction_report">
			<?php $report_tabs = apply_filters( 'wauc_auction_report_tabs', array(
				'renently_completed' => array(
					'label' => __( 'Recently Completed Auctions'),
					'desc' => __( 'List of auctions that is completed recently but no bidded has been selected as winner'),
					'callback' => function() {
						WAUC_Functions::generate_report('recent');
					}
				),
				'proccessing_auctions' => array(
					'label' => __( 'Auctions Processing'),
					'desc' => __( 'List of auctions where winner has been selected but the product has not been claimed by the winner yet.' ),
					'callback' => function() {
						WAUC_Functions::generate_report( 'processing' );
					}
				),
				'auctions_with_winners' => array(
					'label' => __( 'Auctions With Winners'),
					'desc' => __( 'List of completed auctions where winners claimed their product' ),
					'callback' => function() {
						WAUC_Functions::generate_report( 'completed' );
					}
				),
			));
			?>
			<nav class="nav-tab-wrapper woo-nav-tab-wrapper">
				<?php
				$i = 0;
				foreach ( $report_tabs as $tab_slug => $tab_data ) {
					$active = isset($_GET['tab']) ? ( $tab_slug == $_GET['tab'] ? 'nav-tab-active' : '' ) : ( !$i ? 'nav-tab-active' : '' );
					?>
					<a href="<?php echo menu_page_url(self::$page_slug); ?>&tab=<?php echo $tab_slug; ?>" class="nav-tab <?php echo $active; ?>"><?php  echo $tab_data['label']; ?></a>
					<?php
					$i++;
				}
				?>
			</nav>
			<div id="report-tab-content">
				<?php
				if( isset( $_GET['tab'] ) ) {
					if( isset( $report_tabs[$_GET['tab']]['callback'] ) ) {
						$report_tabs[$_GET['tab']]['callback']();
					}
				} else {
					if( isset( $report_tabs[key($report_tabs)]['callback'] ) ) {
						$report_tabs[key($report_tabs)]['callback']();
					}
				}
				?>
			</div>
		</div>
		<script>
			(function ($) {
				$(document).on('click', '.select_as_winner', function () {
					var user_id = $(this).data('user_id');
					var auction_id = $(this).data('auction_id');
					$.post(
						ajaxurl,
						{
							action: 'wauc_select_as_winner',
							user_id: user_id,
							auction_id: auction_id
						},
						function (data) {
							if( data.success ) {
								$('#report-tab-content').html(data.data.report);
							}
						}
					)
				})
					.on( 'click', '.cancel_as_winner', function () {
						var user_id = $(this).data('user_id');
						var auction_id = $(this).data('auction_id');
						$.post(
							ajaxurl,
							{
								action: 'wauc_cancel_as_winner',
								user_id: user_id,
								auction_id: auction_id
							},
							function (data) {
								if( data.success ) {
									$('#report-tab-content').html(data.data.report);
								}
							}
						)
					})
					.on('click','.skip_bid', function () {
						var bid_id = $(this).data('bid_id');
						$.post(
							ajaxurl,
							{
								action: 'wauc_skip_bid',
								bid_id: bid_id
							},
							function (data) {
								if( data.success ) {
									$('#report-tab-content').html(data.data.report);
								}
							}
						)
					}).on('click','.skip_bidder', function () {
					var user_id = $(this).data('user_id');
					var auction_id = $(this).data('auction_id');
					$.post(
						ajaxurl,
						{
							action: 'wauc_skip_bidder',
							user_id: user_id,
							auction_id: auction_id
						},
						function (data) {
							if( data.success ) {
								$('#report-tab-content').html(data.data.report);
							}
						}
					)
				});
			}(jQuery))
		</script>
		<?php
	}
}