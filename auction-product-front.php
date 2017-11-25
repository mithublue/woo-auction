<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class WAUC_Auction_Action {


    /**
     * @var Singleton The reference the *Singleton* instance of this class
     */
    private static $instance;
    protected $result = array();

    /**
     * Returns the *Singleton* instance of this class.
     *
     * @return Singleton The *Singleton* instance.
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action( 'woocommerce_product_meta_start', array( $this, 'auction_section') );
        add_action( 'wp_loaded', array( $this, 'process_bid_request' ) );
    }




    public function auction_section() {
        session_start();
        global $post, $_SESSION;

        $product = wc_get_product( $post );
        if( !WAUC_Functions::is_auction_product( $product ) ) return;

        //display notice if there is any
        if( isset( $_SESSION['wauc_'.get_current_user_id()]['wauc_data']['result'] )) {

            if( $_SESSION['wauc_'.get_current_user_id()]['wauc_data']['result'] == 'error' ) {
                ?>
                <div class="bs-container">
                    <div class="alert alert-danger">
                        <?php echo $_SESSION['wauc_'.get_current_user_id()]['wauc_data']['msg'];?>
                    </div>
                </div>
                <?php
            } else if ( $_SESSION['wauc_'.get_current_user_id()]['wauc_data']['result'] == 'success' ) {
                ?>
                <div class="bs-container">
                    <div class="alert alert-success">
                        <?php echo $_SESSION['wauc_'.get_current_user_id()]['wauc_data']['msg'];?>
                    </div>
                </div>
                <?php
            }
            unset( $_SESSION['wauc_'.get_current_user_id()]['wauc_data'] );
        }

        ?>
        <form action="<?php echo get_permalink();?>?wauc_bid=true" method="post">
            <input type="hidden" name="product_id" value="<?php echo $product->id; ?>">
            <input
                name="bidding_price"
                type="number" value="<?php echo WAUC_Functions::get_product_auction_price( $product->id ); ?>"
                   step="<?php echo WAUC_Functions::get_bid_increament( $product->id ) ?>">
            <p></p>
            <?php

            $ok_to_bid = 0;

            if( !WAUC_Functions::is_last_bidder( $product->id ) ) {

                if( WAUC_Functions::is_eligible_to_bid( get_current_user_id(), $product->id )) {

                    $token = WAUC_Functions::is_token_required( $product->id );

                    if( $token ) {

                        if( !WAUC_Functions::has_user_deposit( $token ) ) {
                            $token = new WC_Product( $token );
                            ?>
                            <input type="hidden" name="auction_request" value="<?php echo $token->id; ?>">
                            <?php
                            echo apply_filters( 'wauc_set_deposit_link',
                                sprintf( 'You have to deposit '.$token->get_price_html().' to join this auction ! <button href="%s" rel="nofollow" data-product_id="%s" class="button %s product_type_%s">%s</button>',
                                    esc_url( $token->add_to_cart_url() ),
                                    esc_attr( $token->id ),
                                    $product->is_purchasable() ? 'add_to_cart_button' : '',
                                    esc_attr( $token->product_type ),
                                    esc_html( __( 'Join Auction', 'wauc' ) )
                                ));
                        } else {
                            $ok_to_bid = 1;
                        }
                    } else {
                        $ok_to_bid = 1;
                    }

                    if( $ok_to_bid ) {
                        echo apply_filters( 'wauc_set_auction_link',
                            sprintf( '<button href="%s" rel="nofollow" data-product_id="%s" data-product_sku="%s" class="button %s product_type_%s">%s</button>',
                                esc_url( $product->add_to_cart_url() ),
                                esc_attr( $product->id ),
                                esc_attr( $product->get_sku() ),
                                $product->is_purchasable() ? 'add_to_cart_button' : '',
                                esc_attr( $product->product_type ),
                                esc_html( __( 'Set Bid', 'wauc' ) )
                            ));
                    }

                }
            }
            ?>
        </form>
        <div class="wauc_timestamp_wrapper">

        </div>
        <div id="wauc_time_counter">
            <?php
            $status = WAUC_Functions::get_auction_time_status( $product->id );

            if( $status == 'future' ) {
                echo WAUC_Functions::get_auction_remaining_time( strtotime( WAUC_Functions::get_auction_start_time( $product->id ) ) );
            } elseif( $status == 'current') {
                echo WAUC_Functions::get_auction_remaining_time( strtotime( WAUC_Functions::get_auction_end_time( $product->id ) ) );
            } elseif ( $status == 'past' ) {

            }; ?>
        </div>
<?php

    }

    /**
     * Check and process the
     * bid request of user
     */
    public function process_bid_request() {
        session_start();
        if( !isset( $_GET['wauc_bid'] ) ) return;
        if( !isset( $_POST['bidding_price'] ) ) return;
        if( !isset( $_POST['product_id'] ) || !is_numeric( $_POST['product_id'] ) ) return;

        if( isset( $_POST['auction_request'] ) && $_POST['auction_request'] && is_numeric( $_POST['auction_request'] ) ) {
            global $woocommerce;
            WC()->cart->add_to_cart( $_POST['auction_request'] );
            wp_redirect( wc_get_cart_url() );
            exit;
        }

        global $_SESSION;
        $product = wc_get_product( $_POST['product_id'] );
        if( !$product )return;

        //check if this is auction product
        if( !WAUC_Functions::is_auction_product( $product ) ) {
            $_SESSION['wauc_'.get_current_user_id()]['wauc_data'] = array(
                'result' => 'error',
                'msg' => __('This product is not for auction','wauc')
            );
            wp_redirect( get_permalink($product->id) );
            exit;
        }

        //check if the user can bid this product
        if( !WAUC_Functions::is_eligible_to_bid( get_current_user_id(), $product->id ) ) {
            $_SESSION['wauc_'.get_current_user_id()]['wauc_data'] = array(
                'result' => 'error',
                'msg' => __('You are not eligible to bid on this product !','wauc')
            );
            wp_redirect( get_permalink($product->id) );
            exit;
        }

        $latest_bid_data = WAUC_Functions::get_latest_bid_row( $product->id );


        //check if current user already bid this
        if( isset( $latest_bid_data->userid ) && $latest_bid_data->userid == get_current_user_id() ) {
            $_SESSION['wauc_'.get_current_user_id()]['wauc_data'] = array(
                'result' => 'error',
                'msg' => __('Your bid is already highest !','wauc')
            );
            wp_redirect( get_permalink($product->id) );
            exit;
        }

        $current_auction_price =  WAUC_Functions::get_product_auction_price( $product->id );
        $bid_increament = WAUC_Functions::get_bid_increament( $product->id );

        //check if the user's bid has the minimum bid steps
        if ( ( $_POST['bidding_price'] - $current_auction_price ) % $bid_increament != 0 ) {
            $_SESSION['wauc_'.get_current_user_id()]['wauc_data'] = array(
                'result' => 'error',
                'msg' => __('The bid should meet minimum bid increament unit','wauc')
            );
            wp_redirect( get_permalink($product->id) );
            exit;
        }


        //check if user's bid exceeds current bid
        if( $_POST['bidding_price'] <= $latest_bid_data->bid ) {
            $_SESSION['wauc_'.get_current_user_id()]['wauc_data'] = array(
                'result' => 'error',
                'msg' => __('Your bid should be higher then current bid !','wauc')
            );
            wp_redirect( get_permalink($product->id) );
            exit;
        }


        //everything is fine
        WAUC_Functions::log_bid( $product->id, $_POST['bidding_price'], get_current_user_id() );
        $_SESSION['wauc_'.get_current_user_id()]['wauc_data'] = array(
            'result' => 'success',
            'msg' => __('You placed bid successfully !','wauc')
        );
        wp_redirect( get_permalink($product->id) );
        exit;
    }
}

WAUC_Auction_Action::get_instance();
