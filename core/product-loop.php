<?php
namespace wauc\core;

class Product_Loop{

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
	    add_action( 'woocommerce_product_query', [ $this, 'wauc_modified_product_query' ] );
    }

	function wauc_modified_product_query( $q ) {
		if( WC_Admin_Settings::get_option( 'wauc_hide_from_shop' ) == 'yes' ) {
			$q->set( 'tax_query', array(
				array(
					'taxonomy' => 'product_type',
					'field'    => 'slug',
					'terms'    => 'auction',
					'operator' => 'NOT IN'
				)
			) );
		}
	}
}