<?php
namespace wauc\core;

use wauc\instance\Auction_Product;

class Product{

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
	    add_action( 'add_meta_boxes_product', [ $this, 'render_auction_product_metabox' ], 10 );
	    add_action( 'save_post', [ $this, 'save_auction_product_fields'], 10, 3 );
    }

    public function render_auction_product_metabox( $post ) {
	    add_meta_box(
		    'auction-product',
		    __( 'Woo Auction', 'wauc' ),
		    [ $this, 'render_metabox_content'],
		    'product'
	    );
    }

    public function render_metabox_content() {
    	include_once WAUC_ROOT . '/templates/admin/metabox-auction-product.php';
    }

	/**
	 * @param $post_id
	 * @param $post
	 * @param $update
	 */
    public function save_auction_product_fields( $post_id, $post, $update  ) {
    	if ( get_post_type() !== 'product' ) return;
	    $auction_product = new Auction_Product( $post_id );
	    $meta_attrs = $auction_product->get_attributes();
	    foreach ( $meta_attrs as $attr => $attr_array ) {
	    	if ( isset( $_POST[ 'wauc_'.$attr ] ) ) {
	    		$auction_product->set_meta( $attr, $_POST['wauc_'.$attr] );
		    }
	    }
    }
}