
<?php
class WC_Auction_Settings_Tab {
    public static function init() {
        add_filter( 'woocommerce_settings_tabs_array', __CLASS__ . '::add_auction_settings_tab', 50 );
        add_action( 'woocommerce_settings_tabs_auction_settings', __CLASS__ . '::settings_tab' );
        add_action( 'woocommerce_update_options_auction_settings', __CLASS__ . '::update_settings' );

    }
    public static function add_auction_settings_tab( $settings_tabs ) {
        $settings_tabs['auction_settings'] = __( 'Auction', 'wauc' );
        return $settings_tabs;
    }

    /**
     * Uses the WooCommerce admin fields API to output settings via the @see woocommerce_admin_fields() function.
     *
     * @uses woocommerce_admin_fields()
     * @uses self::get_settings()
     */
    public static function settings_tab() {
        woocommerce_admin_fields( self::get_settings() );
    }

    /**
     * Uses the WooCommerce options API to save settings via the @see woocommerce_update_options() function.
     *
     * @uses woocommerce_update_options()
     * @uses self::get_settings()
     */
    public static function update_settings() {
        woocommerce_update_options( self::get_settings() );
    }

    /**
     * Get all the settings for this plugin for @see woocommerce_admin_fields() function.
     *
     * @return array Array of settings for @see woocommerce_admin_fields() function.
     */
    public static function get_settings() {
        $settings = array(
            'section_title' => array(
                'name'     => __( 'Section Title', 'wauc' ),
                'type'     => 'title',
                'desc'     => '',
                'id'       => 'wc_settings_tab_demo_section_title'
            ),
            'show_completed_auctions' => array(
                'name'   => __( 'Show completed auctions', 'wauc' ),
                'desc'    => __( 'Checking this will show completed auctions', 'wauc' ),
                'id'      => 'wauc_show_completed_auctions',
                'type'    => 'checkbox',
                'default' => 'yes',
            ),
            'show_upcoming_auctions' => array(
                'name'   => __( 'Show upcoming auctions', 'wauc' ),
                'desc'    => __( 'Checking this will show upcoming auctions', 'wauc' ),
                'id'      => 'wauc_show_upcoming_auctions',
                'type'    => 'checkbox',
                'default' => 'yes',
            ),
            'hide_from_shop' => array(
                'name'   => __( 'Hide auction product from shop page', 'wauc' ),
                'desc'    => __( 'Checking this will prevent auction product to display on shop page', 'wauc' ),
                'id'      => 'wauc_hide_from_shop',
                'type'    => 'checkbox',
                'default' => 'yes',
            ),
            'section_end' => array(
                'type' => 'sectionend',
                'id' => 'wc_settings_tab_demo_section_end'
            )
        );
        return apply_filters( 'wc_settings_tab_demo_settings', $settings );
    }
}
WC_Auction_Settings_Tab::init();