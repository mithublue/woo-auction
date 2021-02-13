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

if( !function_exists( 'pri' ) ) {
    function pri( $data ) {
        echo '<pre>';print_r( $data );echo '</pre>';
    }
}

define( 'WAUC_ROOT', dirname(__FILE__));
define( 'WAUC_ASSET_URL', plugins_url( 'assets', __FILE__ ) );
define( 'WAUC_PRODUCTION', true );
define( 'WAUC_BASE_FILE', __FILE__ );

class WAUC_Init {

    /**
     * Instance
     *
     * @since 1.0.0
     *
     * @access private
     * @static
     */
    private static $_instance = null;

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
        register_activation_hook( __FILE__, [ $this, 'on_active' ] );
        register_deactivation_hook( __FILE__, array( $this , 'on_deactivation' ) );
        $this->includes();
    }

    public function on_active() {
        include_once "inc/class-db.php";
        WAUC_DB()->install_tables();
        WAUC_Schedule()->init_schedules();
    }

    /**
     * Run plugin deactivation
     */
    public static function deactivation(){
        WAUC_Schedule()->clear_schedules();
    }

    public function includes() {
        require_once 'vendor/autoload.php';

        foreach ( glob( WAUC_ROOT . '/inc/*.php') as $k => $filename ) {
            include_once $filename;
        }
    }
}

WAUC_Init::instance();