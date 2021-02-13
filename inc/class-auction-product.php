<?php

if( !class_exists( 'WC_Product_Auction' ) && class_exists( 'WC_Product' ) ) {
    class WC_Product_Auction extends WC_Product {
        public function __construct($product = 0) {
            $this->product_type = 'auction';
            parent::__construct($product);
        }
    }
}

class WAUC_Product {

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
        add_filter( 'product_type_selector', [ $this, 'custom_product_type' ] );
        add_action( 'init', 'create_custom_product_type' );
        add_filter( 'woocommerce_product_class', [ $this, 'woocommerce_product_class'], 10, 2 );
    }

    public function custom_product_type( $types ) {
        $types[ 'auction' ] = __( 'Auction Product', 'wauc' );
        return $types;
    }

    public function woocommerce_product_class( $classname, $product_type ) {
        if ( $product_type == 'auction' ) {
            $classname = 'WC_Product_Custom';
        }
        return $classname;
    }
}

function create_custom_product_type() {
    class WC_Product_Custom extends WC_Product {
        public function get_type() {
            return 'auction';
        }
    }
}

function WAUC_Product() {
    return WAUC_Product::instance();
}

WAUC_Product();