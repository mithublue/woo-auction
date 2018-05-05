<?php

/**
 * WooCommerce Product Settings
 *
 * @author   WooThemes
 * @category Admin
 * @package  WooCommerce/Admin
 * @version  2.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( ! class_exists( 'WC_Settings_Wauc_Auction', false ) ) :

    /**
     * WC_Settings_Products.
     */
    class WC_Settings_Wauc_Auction extends WC_Settings_Page {

        /**
         * Constructor.
         */
        public function __construct() {
            $this->id    = 'auction';
            $this->label = __( 'Auction', 'wauc' );
            parent::__construct();
            add_filter( 'wauc_auction_notifications_settings', __CLASS__.'::add_notification_fields_free' );
        }

        /**
         * Get sections.
         *
         * @return array
         */
        public function get_sections() {

            $sections = array(
                'general'          	=> __( 'General', 'wauc' ),
                'notification'       => __( 'Notification', 'wauc' ),
                'display'       => __( 'Display', 'wauc' ),
            );

            return apply_filters( 'woocommerce_get_sections_' . $this->id, $sections );
        }

        /**
         * Output the settings.
         */
        public function output() {
            global $current_section;

            $settings = $this->get_settings( $current_section );

            WC_Admin_Settings::output_fields( $settings );
        }

        /**
         * Save settings.
         */
        public function save() {
            global $current_section;

            $settings = $this->get_settings( $current_section );
            WC_Admin_Settings::save_fields( $settings );
        }

        /**
         * Get settings array.
         *
         * @param string $current_section
         *
         * @return array
         */
        public function get_settings( $current_section = '' ) {
            if( 'general' == $current_section || !$current_section ) {
                $settings = apply_filters( 'wauc_auction_general_settings', array(
                    array(
                        'title' 	=> __( 'General', 'wauc' ),
                        'type' 		=> 'title',
                        'id' 		=> 'wauc_general_options',
                    ),
                    'auc_unowned_ban_user' => array(
                        'title'           => __( 'If the user (who is selected as winner) does not claim/take/own the product within the specified time limit', 'wauc' ),
                        'desc'            => __( 'Ban that user (Pro)', 'wauc' ),
                        'id'              => '',
                        'default'         => '',
                        'type'            => 'checkbox',
                        'checkboxgroup'   => 'start',
                        //'show_if_checked' => 'option',
                    ),
                    array(
                        'desc'            => __( 'Make product private for re-auction in future', 'wauc' ),
                        'id'              => 'wauc_general_settings[winner_not_take-make_product_private]',
                        'default'         => 'yes',
                        'type'            => 'checkbox',
                        'checkboxgroup'   => '',
                        'autoload'        => false,
                    ),
                    array(
                        'title'    => __( 'Time limit to take the product', 'woocommerce' ),
                        'desc'     => __( 'This is the number of day(s) within which the winner has to take the product', 'woocommerce' ),
                        'id'       => 'wauc_general_settings[winner_product_take_time_limit]',
                        'type'     => 'number',
                        'default'  => '',
                        'show_if_checked' => 'yes',
                        'placeholder' => '3'
                    )
                ));

                $settings[] = array(
                    'type' 	=> 'sectionend',
                    'id' 	=> 'wauc_general_options',
                );
            } else if ( 'notification' == $current_section ) {
                $settings = apply_filters( 'wauc_auction_notifications_settings', array(

                    array(
                        'title' => __( 'Notifications', 'wauc' ),
                        'type' 	=> 'title',
                        'desc' 	=> '',
                        'id' 	=> 'notification_options',
                    ),

                    array(
                        'title'         => __( 'Notification on joining request approval', 'wauc' ),
                        'desc'          => __( 'Notify the user by email whose joining request to an auction is apporved', 'wauc' ),
                        'id'            => 'wauc_notification[email_auction_joining_approval]',
                        'default'       => 'yes',
                        'type'          => 'checkbox'
                    ),
                        'email_auction_joining_approval-subject' => array(
                            'desc'          => __( 'Subject for the mail (Pro)', 'wauc' ),
                            'id'            => '',
                            'placeholder'       => 'Your request for joining the auction approved %product_title%',
                            'type'          => 'text'
                        ),
                        'email_auction_joining_approval-body' => array(
                            'desc'          => __( 'Subject for the mail (Pro)', 'wauc' ),
                            'id'            => '',
                            'placeholder'       => 'Your request for joining the auction has been approved ! URL: %product_permalink%',
                            'type'          => 'textarea'
                        ),
                    array(
                        'title'         => __( 'Notify the bidder when he bids', 'wauc' ),
                        'desc'          => __( 'Notify the bidder by email when he bids', 'wauc' ),
                        'id'            => 'wauc_notification[email_bidder_on_bid]',
                        'default'       => 'yes',
                        'type'          => 'checkbox'
                    ),
                        'email_bidder_on_bid-subject' => array(
                            //'title'         => __( 'Notify the bidder when he bids', 'wauc' ),
                            'desc'          => __( 'Subject for the mail (Pro)', 'wauc' ),
                            'id'            => '',
                            'placeholder'       => 'You bid on the product %product_title%',
                            'type'          => 'text'
                        ),
                        'email_bidder_on_bid-body' => array(
                            //'title'         => __( 'Notify the bidder when he bids', 'wauc' ),
                            'desc'          => __( 'Subject for the mail (Pro)', 'wauc' ),
                            'id'            => '',
                            'placeholder'       => 'Your bid on the product %product_title% has been successful ! URL: %product_permalink%',
                            'type'          => 'textarea'
                        ),

                    array(
                        'title'         => __( 'Notify other bidders when someone bids', 'wauc' ),
                        'desc'          => __( 'Notify other bidders by email when someone bids on the auction they participated', 'wauc' ),
                        'id'            => 'wauc_notification[email_bidders_on_someones_bid]',
                        'default'       => 'yes',
                        'type'          => 'checkbox'
                    ),
                        'email_bidders_on_someones_bid-subject' => array(
                            'desc'          => __( 'Subject for the mail (Pro)', 'wauc' ),
                            'id'            => '',
                            'placeholder'       => 'Someone has bid on %product_title%',
                            'type'          => 'text'
                        ),
                        'email_bidders_on_someones_bid-body' => array(
                            //'title'         => __( 'Notify the bidder when he bids', 'wauc' ),
                            'desc'          => __( 'Subject for the mail (Pro)', 'wauc' ),
                            'id'            => '',
                            'placeholder'       => 'Someone has bid one %product_title% ! Get it before the chince misses. URL: %product_permalink%',
                            'type'          => 'textarea'
                        ),

                    'notify_by_email_for_auction_deadline' => array(
                        'title'         => __( 'Notify bidders on auction deadline (Pro)', 'wauc' ),
                        'desc'          => __( 'Notify bidders by email when the auction they particapted in is near to deadline', 'wauc' ),
                        'id'            => '',
                        'placeholder'       => '',
                        'type'          => 'checkbox'
                    ),
                        'email_bidders_on_auction_near_deadline-subject' => array(
                            'desc'          => __( 'Subject for the mail (Pro)', 'wauc' ),
                            'id'            => '',
                            'placeholder'       => 'Auction is to be end for %product_title%',
                            'type'          => 'text'
                        ),
                        'email_bidders_on_auction_near_deadline-body' => array(
                            'desc'          => __( 'Subject for the mail (Pro)', 'wauc' ),
                            'id'            => '',
                            'placeholder'       => 'Auction is to be end for %product_title% ! Grab it before it ends : %product_permalink%',
                            'type'          => 'textarea'
                        ),
                    array(
                        'title'         => __( 'Notify bidders on auction end', 'wauc' ),
                        'desc'          => __( 'Notify bidders by email when the auction is end', 'wauc' ),
                        'id'            => 'wauc_notification[email_bidders_on_auction_end]',
                        'default'       => 'yes',
                        'type'          => 'checkbox'
                    ),
                        'email_bidders_on_auction_end-subject' => array(
                            'desc'          => __( 'Subject for the mail (Pro)', 'wauc' ),
                            'id'            => '',
                            'placeholder'       => 'Auction has been end for %product_title%',
                            'type'          => 'text'
                        ),
                        'email_bidders_on_auction_end-body' => array(
                            'desc'          => __( 'Subject for the mail (Pro)', 'wauc' ),
                            'id'            => '',
                            'placeholder'       => 'Auction has been end %product_title% !',
                            'type'          => 'textarea'
                        ),
                    array(
                        'title'         => __( 'Notify bidder when he wins an auction', 'wauc' ),
                        'desc'          => __( 'Notify bidder by email when he wins an auction', 'wauc' ),
                        'id'            => 'wauc_notification[email_bidder_on_winning_auction]',
                        'default'       => 'yes',
                        'type'          => 'checkbox'
                    ),
                        'email_bidder_on_winning_auction-subject' => array(
                            'desc'          => __( 'Subject for the mail (Pro)', 'wauc' ),
                            'id'            => '',
                            'placeholder'       => 'Congrulations ! You won : %product_title%',
                            'type'          => 'text'
                        ),
                        'email_bidder_on_winning_auction-body' => array(
                            'desc'          => __( 'Subject for the mail (Pro)', 'wauc' ),
                            'id'            => '',
                            'placeholder'       => 'You have won : %product_title% !',
                            'type'          => 'textarea'
                        ),
                    array(
                        'title'         => __( 'Notify admin on auctions end', 'wauc' ),
                        'desc'          => __( 'Notify admin by email when auction ends, P.N: this notification will be sent on daily basis', 'wauc' ),
                        'id'            => 'wauc_notification[email_admin_on_end_auction]',
                        'default'       => 'yes',
                        'type'          => 'checkbox'
                    ),
                        'email_admin_on_end_auction-subject' => array(
                            'desc'          => __( 'Subject for the mail (Pro)', 'wauc' ),
                            'id'            => '',
                            'placeholder'       => 'Some auctions have completed recently !',
                            'type'          => 'text'
                        ),
                        'email_admin_on_end_auction-body' => array (
                            'desc'          => __( 'Body for the mail (Pro)', 'wauc' ),
                            'id'            => '',
                            'placeholder'       => 'Some auctions have completed recently ! : %product_title% !',
                            'type'          => 'textarea'
                        )
                ));

                $settings[] = array(
                    'type' 	=> 'sectionend',
                    'id' 	=> 'notification_options',
                );
            } elseif ( 'display' == $current_section ) {
                $settings = apply_filters( 'wauc_auction_display_settings', array(
                    array(
                        'title' 	=> __( 'Display', 'wauc' ),
                        'type' 		=> 'title',
                        'id' 		=> 'wauc_display_options',
                    ),

                ));
                $settings[] = array(
                    'type' 	=> 'sectionend',
                    'id' 	=> 'wauc_display_options',
                );
            } else {
                $settings = apply_filters( 'wauc_auction_'.$current_section.'_settings', array() );
            }

            return apply_filters( 'wauc_get_settings_' . $this->id, $settings, $current_section );
        }

        public static function add_notification_fields_free( $settings ) {
            if( WAUC_Functions::is_pro() ) return $settings;

            return $settings;
        }
    }

endif;

return new WC_Settings_Wauc_Auction();