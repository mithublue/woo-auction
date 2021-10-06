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


namespace wauc;

use As247\WpEloquent\Application;
use wauc\core\Ajax_Actions;
use wauc\core\Auction_Admin;
use wauc\core\Auction_Product_Front;
use wauc\core\Auction_Report;
use wauc\core\News;
use wauc\core\Schedules;
use wauc\migrations\Migrator;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

if ( !function_exists( 'pri' ) ) {
	function pri( $data ) {
		echo '<pre>';print_r($data);echo '</pre>';
	}
}


spl_autoload_register(function ($class_name) {
	$file = strtolower( str_replace( ['\\','_'], ['/','-'], $class_name ) ).'.php';
	$file = str_replace('wauc', '', $file);
	if ( file_exists( __DIR__ . '/' . $file ) ) {
		include_once __DIR__ . '/' . $file;
	}
});

define( 'WAUC_ROOT', dirname(__FILE__));
define( 'WAUC_ASSET_URL', plugins_url( 'assets', __FILE__ ) );
define( 'WAUC_PRODUCTION', true );
define( 'WAUC_BASE_FILE', __FILE__ );

class WAUC {

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
	    add_action( 'admin_notices', array( $this, 'check_wc_activation' ) );
        $this->includes();
    }

    public function on_active() {
    	Migrator::instance()->run();
        Schedules::instance()->init_schedules();
    }

    /**
     * Run plugin deactivation
     */
    public static function on_deactivation(){
    	Schedules::instance()->clear_schedules();
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

    public function includes() {
	    require_once 'vendor/autoload.php';
	    Application::bootWp();
	    Ajax_Actions::instance();
	    Auction_Admin::instance();
	    Auction_Product_Front::instance();
	    Auction_Report::instance();
	    News::instance();
    }
}

WAUC::instance();

add_action('init',function (){
	Migrator::instance()->run();
});