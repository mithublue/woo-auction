<?php

if( !class_exists( 'WC_Product_Auction' ) ) return;
class WC_Product_Auction extends WC_Product {
    public function __construct( $product ) {
        $this->product_type = 'auction';
        parent::__construct( $product );
    }
}