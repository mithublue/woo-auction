<?php
add_filter( 'wauc_auction_display_settings', function ( $settings ) {
    $settings = array_merge($settings,array(
        array(
            'title'           => __( 'Do not display auctions on shop page (Pro)', 'wauc' ),
            'desc'            => __( 'Check this if you do not want to show auctions in shop page', 'wauc' ),
            'id'              => '',
            'default'         => 'yes',
            'type'            => 'checkbox',
            'checkboxgroup'   => 'start',
        ),
        array(
            'title'           => __( 'Do not display auctions on category page (Pro)', 'wauc' ),
            'desc'            => __( 'Check this if you do not want to show auctions in category page', 'wauc' ),
            'id'              => '',
            'default'         => 'yes',
            'type'            => 'checkbox',
            'checkboxgroup'   => 'start',
        ),
        array(
            'title'           => __( 'Do not display auctions on tag page (Pro)', 'wauc' ),
            'desc'            => __( 'Check this if you do not want to show auctions in tag page', 'wauc' ),
            'id'              => '',
            'default'         => 'yes',
            'type'            => 'checkbox',
            'checkboxgroup'   => 'start',
        )
    ) );

    return $settings;
})
;
//Fake auction
add_filter( 'wauc_auction_report_tabs', 'wauc_demo_fake_auction_tab' );
function wauc_demo_fake_auction_tab( $tabs ) {
    $tabs['fake_auction_demo'] = array(
        'label' => __( 'Fake Auction', 'wauc' ),
        'desc' => __( 'List of fake bids and bidders and the auctions where these bid are places (Pro)', 'wauc' ),
        'callback' => function() {
            _e('<h3 class="text-center">Will be available in pro version</h3>','wauc' );
        }
    );
    return $tabs;
}