<?php
/**
 * Plugin Name: Woo Auction
 * Plugin URI:
 * Description: A plugin to add auction feature with options to work with woocommerce.
 * Version: 1.0.3
 * Author: CyberCraft
 * Author URI: http://cybercraftit.com/
 * Requires at least: 4.0
 * Tested up to: 5.0.3
 *
 * Text Domain: wauc
 */



if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

define( 'WAUC_ROOT', dirname(__FILE__));
define( 'WAUC_ASSET_URL', plugins_url( 'assets', __FILE__ ) );
define( 'WAUC_PRODUCTION', true );
define( 'WAUC_BASE_FILE', __FILE__ );

do_action('wauc_before_base_class' );

if( file_exists( WAUC_ROOT.'/pro/loader.php' ) ) {
    require_once WAUC_ROOT.'/pro/loader.php';
} else {
    include_once WAUC_ROOT.'/pro-demo.php';
}


class WAUC_Init{

    /**
     * init the task for the the plugin
     */
    public function __construct(){

        register_activation_hook( __FILE__, array( $this, 'install' ) );
        register_deactivation_hook( __FILE__, array( $this , 'deactivation' ) );

        add_action( 'admin_notices', array( $this, 'check_wc_activation' ) );
        add_action( 'init', array( $this, 'include_files' ) );

        //styles
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts_styles' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts_styles' ) );
        add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), array( $this, 'action_links' ) );

        //scheduler
        add_action( 'wauc_auction_daily_hook', array( $this, 'scheduled_tasks' ) );

        //settings
        add_filter( 'woocommerce_get_settings_pages', array( $this, 'add_settings_page' ) );

        //
        add_filter( 'wp_mail', array( $this, 'debug_mail' ) );

        //
    }

    public function action_links($links) {
        $links[] = '<a href="https://cybercraftit.com/contact/" target="_blank">'.__( 'Ask for Modification', 'wauc' ).'</a>';
        if( !WAUC_Functions::is_pro() ) {
            $links[] = '<a href="https://cybercraftit.com/woo-auction-pro/" style="color: #fa0000;" target="_blank">'.__( 'Upgrade to Pro', 'wauc' ).'</a>';
        }
        return $links;
    }

    /**
     * Run plugin installation
     * WooCommerce is known to be active and initialized
     *
     */
    public function install(){
        global $wpdb;
        $data_table = $wpdb->prefix."wauc_auction_log";

        $winners = $wpdb->prefix."wauc_winners";
        $sql = " CREATE TABLE IF NOT EXISTS $data_table (
  						`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
						  `userid` bigint(20) unsigned NOT NULL,
						  `auction_id` bigint(20) unsigned DEFAULT NULL,
						  `bid` decimal(30,2) DEFAULT NULL,
						  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
						  `is_fake` tinyint(1) DEFAULT 0,
						  `proxy` tinyint(1) DEFAULT NULL,
						  PRIMARY KEY (`id`)
						);";

        $sql_winners = "CREATE TABLE IF NOT EXISTS $winners (
  						  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
						  `userid` bigint(20) unsigned NOT NULL,
						  `auction_id` bigint(20) unsigned NOT NULL,
						  `is_selected` tinyint(1) DEFAULT 0,
						  `is_winner` tinyint(1) DEFAULT 0,
						  `log_id` bigint(20) DEFAULT NULL,
						  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
						  PRIMARY KEY (`id`)
						);";
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
        dbDelta( $sql_winners );
        if ( !wp_next_scheduled ( 'wauc_auction_daily_hook' )) {
            wp_schedule_event( time(), 'daily', 'wauc_auction_daily_hook' );
        }


    }

    /**
     * Run plugin deactivation
     *
     */
    public static function deactivation(){
        wp_clear_scheduled_hook('wauc_auction_daily_hook' );
    }

    /**
     * If WC is not active
     * admin notice will appear
     * to active it
     */
    function check_wc_activation(){
        if( !class_exists('WooCommerce')):
        ?>
        <div class="notice notice-warning">
            <p><?php _e( 'It seem\'s WooCommerce is not activated ! Please activate it to have WooCommerce Auction working !', 'sample-text-domain' ); ?></p>
        </div>
    <?php
        endif;
    }

    /**
     * Include necessary files
     */
    function include_files() {
        require_once WAUC_ROOT.'/functions.php';
        require_once WAUC_ROOT.'/ajax-action.php';
        require_once WAUC_ROOT.'/auction-admin.php';
        require_once WAUC_ROOT.'/editor.php';
        require_once WAUC_ROOT.'/auction-product-front.php';
        require_once WAUC_ROOT.'/product-loop.php';
        require_once WAUC_ROOT.'/notification.php';
        require_once WAUC_ROOT.'/auction-report-admin.php';
	    require_once WAUC_ROOT.'/news.php';
    }

    /**
     * css and js
     */
    function admin_enqueue_scripts_styles( $hook ){
        global $post;
        if( !in_array( $hook, array( 'post.php', 'post-new.php', 'product_page_wauc-auction-report' ) ) ) return;
        if( isset( $post->ID ) && get_post_type($post->ID) != 'product' ) return;

        //style
        wp_enqueue_style( 'wauc-css', WAUC_ASSET_URL.'/css/wauc.css' );
        wp_enqueue_style( 'jquery-ui-datepicker' );
        wp_enqueue_style( 'wauc-fa', WAUC_ASSET_URL.'/css/font-awesome.min.css' );

        //js
        wp_register_script( 'wauc-vue', WAUC_ASSET_URL.'/js/vue.js', array(), false, true );
        wp_enqueue_script( 'wauc-vue' );
        wp_enqueue_script( 'wauc-script-admin', plugins_url( 'assets/js/admin.script.js', __FILE__ ), array( 'jquery'), null, false );
        wp_enqueue_script(
            'wauc-timepicker-addon',
            WAUC_ASSET_URL.'/js/jquery-ui-timepicker-addon.js',
            array('jquery', 'jquery-ui-core', 'jquery-ui-datepicker'),
            '1.0',
            true
        );
        wp_localize_script( 'wauc-timepicker-addon', 'wauc_admin_data', array(
            'calendar_image' => '<i class="fa fa-calendar"></i>'
        ));
    }

    public function wp_enqueue_scripts_styles() {
        wp_enqueue_style( 'wauc-css', WAUC_ASSET_URL.'/css/wauc.css' );
        wp_enqueue_script( 'wauc-scipt', WAUC_ASSET_URL.'/js/script.js', array( 'jquery' ), false, true );
    }

    public function scheduled_tasks() {
        $results = WAUC_Functions::get_top_bidders_with_end_auctions();
        if( !empty( $results ) ) {
            WAUC_Notification::email_admin_on_newly_end_auctions();

            /**
             * Notify users on bid end
             */
            $items = array();
            foreach ( $results as $k => $result ) {
                $items[$result->auction_id][] = array(
                    'auction_id' => $result->auction_id,
                    'auction_name' => $result->auction_name,
                    'userid' => $result->userid,
                    'user_email' => $result->user_email,
                    'url' => get_permalink( $result->auction_id )
                );
            }

            if( !empty($items) ) {
                WAUC_Notification::bid_end_notification( $items );
            }
        }

        /**
         * notify awaiting winners and deselect
         * bidder who did not claim product
         * within the given time
         */
        $results = WAUC_Functions::get_all_awaiting_winners();

        if ( !empty( $results ) ) {
            $time_limit = WAUC_Functions::get_settings('general', 'winner_product_take_time_limit' );
            $timestamps_limit = $time_limit*24*60*60;

            $to_deselect_userids = array();
            $to_deselect_action_ids = array();
            $notifiable_users = array();

            foreach ( $results as $k => $result ) {
                //if time passes the time limit
                if( $timestamps_limit < time() - strtotime( $result->date ) ) {
                    $to_deselect_userids[] = $result->userid;
                    $to_deselect_action_ids[] = $result->auction_id;
                } else {
                    $notifiable_users[] = $result;
                }
            }

            /**
             * Actions taken if
             * winner unclaimed the product
             */
            //deselect winner
            WAUC_Functions::deselect_winner( $to_deselect_userids, $to_deselect_action_ids );

            //draft post if winner unclaimed the product
            if( WAUC_Functions::get_settings('general', 'winner_not_take-make_product_private') == 'yes' ) {
                WAUC_Functions::change_publish_status('draft',$to_deselect_action_ids);
            } else {
                //change auction product status to running of those auctions
                foreach ( $to_deselect_action_ids as $k => $auction_id ) {
                    WAUC_Functions::change_auction_status( 'processing', 'running', $auction_id );
                }
            }


            //notify awaiting winners who did not pass the time limit yet
            WAUC_Notification::notify_awaiting_winners( $notifiable_users );
        }


        WAUC_Functions::log(array(
            'it is running !'
        ));

        do_action( 'wauc_scheduled_task', $results );
    }

    public static function add_settings_page ( $settings ) {
        $settings[] = include( WAUC_ROOT.'/class-wc-settings-auction.php' );
    }

    public function debug_mail ( $data ) {
        if( WAUC_PRODUCTION ) return;
        //WAUC_Functions::log_mail( $data );
    }
}

new WAUC_Init();