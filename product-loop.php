<?php
add_action( 'woocommerce_product_query', 'wauc_modified_product_query' );

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