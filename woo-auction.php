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

    }
}

WAUC_Init::instance();