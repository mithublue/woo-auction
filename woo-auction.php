<?php
/**
 * Plugin Name: Woo Auction
 * Plugin URI:
 * Description: A plugin to add auction feature with options to work with woocommerce.
 * Version: 0.1
 * Author: Mithu A Quayium
 * Author URI: http://cybercraftit.com/
 * Requires at least: 4.0
 * Tested up to: 4.7.3
 *
 * Text Domain: wauc
 */



if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

define( 'WAUC_ROOT', dirname(__FILE__));
define( 'WAUC_ASSET_URL', plugins_url( 'assets', __FILE__ ) );

require_once 'auction-widget.php';



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

        //session
        add_action( 'init', array( $this, 'set_session' ) );
        add_action('wp_logout', array( $this, 'session_end' ) );
        add_action('wp_login', array( $this, 'session_end' ) );
    }

    public function set_session() {
        if(!session_id()) {
            session_start();
        }
    }

    public function session_end() {
        session_destroy();
    }

    /**
     * Run plugin installation
     * WooCommerce is known to be active and initialized
     *
     */
    public function install(){
        global $wpdb;
        $data_table = $wpdb->prefix."wauc_auction_log";
        $sql = " CREATE TABLE IF NOT EXISTS $data_table (
  						`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
						  `userid` bigint(20) unsigned NOT NULL,
						  `auction_id` bigint(20) unsigned DEFAULT NULL,
						  `bid` decimal(30,2) DEFAULT NULL,
						  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
						  `proxy` tinyint(1) DEFAULT NULL,
						  PRIMARY KEY (`id`)
						);";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
        wp_schedule_event( time(), 'twicedaily', 'wauc_auction_send_reminders_email' );

    }

    /**
     * Run plugin deactivation
     *
     */
    public static function deactivation(){
        wp_clear_scheduled_hook('wauc_auction_send_reminders_email' );
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
    function include_files(){
        require_once WAUC_ROOT.'/functions.php';
        require_once WAUC_ROOT.'/ajax-action.php';
        require_once WAUC_ROOT.'/shortcode.php';
        require_once WAUC_ROOT.'/class-wc-product-auction.php';
        require_once WAUC_ROOT.'/auction-admin.php';
        require_once WAUC_ROOT.'/auction-settings-admin.php';
        require_once WAUC_ROOT.'/editor.php';
        require_once WAUC_ROOT.'/auction-product-front.php';
        require_once WAUC_ROOT.'/product-loop.php';
    }

    /**
     * css and js
     */
    function admin_enqueue_scripts_styles( $hook ){
        global $post;
        if( !in_array( $hook, array( 'post.php', 'post-new.php') ) ) return;
        if( get_post_type($post->ID) != 'product' ) return;

        //style
        wp_register_style( 'wauc-bs-css', WAUC_ASSET_URL.'/css/wrapper-bs.min.css' );
        wp_enqueue_style( 'wauc-bs-css' );
        wp_enqueue_style( 'jquery-ui-datepicker' );
        wp_enqueue_style( 'wauc-fa', WAUC_ASSET_URL.'/css/font-awesome.min.css' );
        wp_enqueue_style( 'wauc-admin-css', WAUC_ASSET_URL.'/css/admin-style.less' );

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
        wp_register_style( 'wauc-bs-css', WAUC_ASSET_URL.'/css/wrapper-bs.min.css' );

        wp_register_script( 'wauc-vue', WAUC_ASSET_URL.'/js/vue.js', array(), false, true );
        wp_enqueue_script( 'wauc-scipt', WAUC_ASSET_URL.'/js/script.js', array( 'jquery' ), false, true );
    }
}

new WAUC_Init();
