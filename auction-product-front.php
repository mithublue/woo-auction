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
        add_action( 'woocommerce_product_meta_start', array( $this, 'token_section') );

        add_filter('woocommerce_add_to_cart_validation', array( $this, 'woocommerce_add_to_cart_validation' ), 20, 2);
        add_action( 'wp_loaded', array( $this, 'process_bid_request' ) );

        add_filter( 'woocommerce_is_purchasable', array( $this, 'make_product_purchasable' ) );
    }


    /**
     * Auction field in
     * Single product
     */
    public function auction_section() {
        if( !session_id() ) {
            session_start();
        }
        global $post, $_SESSION;

        $product = (new WC_Product_Factory())->get_product($post->ID);
        if( !WAUC_Functions::is_auction_product( $post->ID ) ) return;

        $is_auction_eligible = apply_filters( 'wauc_is_auction_eligible', true, $product );

        if( !$is_auction_eligible ) return;

        //display notice if there is any
        if( isset( $_SESSION['wauc_'.WAUC_Functions::get_current_user_id()]['wauc_data']['result'] )) {

            if( $_SESSION['wauc_'.WAUC_Functions::get_current_user_id()]['wauc_data']['result'] == 'error' ) {
                ?>
                <div class="wauc-notice wauc-notice-danger">
                    <?php echo $_SESSION['wauc_'.WAUC_Functions::get_current_user_id()]['wauc_data']['msg'];?>
                </div>
                <?php
            } else if ( $_SESSION['wauc_'.WAUC_Functions::get_current_user_id()]['wauc_data']['result'] == 'success' ) {
                ?>
                <div class="wauc-notice wauc-notice-success">
                    <?php echo $_SESSION['wauc_'.WAUC_Functions::get_current_user_id()]['wauc_data']['msg'];?>
                </div>
                <?php
            }
            unset( $_SESSION['wauc_'.WAUC_Functions::get_current_user_id()]['wauc_data'] );
        }

        ?>
        <form action="<?php echo get_permalink();?>?wauc_bid=true" method="post">
            <?php $status = WAUC_Functions::get_auction_time_status( $product->get_id() ); ?>

            <input type="hidden" name="product_id" value="<?php echo $product->get_id(); ?>">
            <?php
            if ( $status == 'current' ) {
                ?>
                <input
                        name="bidding_price"
                        type="number" value="<?php echo WAUC_Functions::get_product_auction_price( $product->get_id() ); ?>"
                        step="<?php echo WAUC_Functions::get_bid_increament( $product->get_id() ) ?>">
                <p></p>
                <?php
                $ok_to_bid = 0;

                if( !WAUC_Functions::is_last_bidder( $product->get_id() ) ) {

                    if( WAUC_Functions::is_eligible_to_bid( WAUC_Functions::get_current_user_id(), $product->get_id() )) {

                        $token = WAUC_Functions::get_token( $product->get_id() );

                        if( $token ) {
                            if( !WAUC_Functions::has_user_token( $token ) ) {
                                $token = wc_get_product( $token );
                                ?>
                                <input type="hidden" name="auction_request" value="<?php echo $token->get_id(); ?>">
                                <?php
                                echo apply_filters( 'wauc_set_deposit_link',
                                    sprintf( 'You have to have the token to join this auction ! '.( $token->get_price() == 0 ? 'Token is free' : 'Token price : '.$token->get_price_html() ).' <button href="%s" rel="nofollow" data-product_id="%s" class="button %s product_type_%s">%s</button>',
                                        esc_url( $token->add_to_cart_url() ),
                                        esc_attr( $token->get_id() ),
                                        $product->is_purchasable() ? 'add_to_cart_button' : '',
                                        esc_attr( $token->get_type() ),
                                        esc_html( __( 'Get Token to Join Auction', 'wauc' ) )
                                    ),$product);
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
                                    esc_attr( $product->get_id() ),
                                    esc_attr( $product->get_sku() ),
                                    $product->is_purchasable() ? 'add_to_cart_button' : '',
                                    esc_attr( $product->get_type() ),
                                    esc_html( __( 'Set Bid', 'wauc' ) )
                                ),$product);
                        }

                        //if product has buy now price
	                    if( WAUC_Functions::has_buy_now_price($product) ) {
		                    // show add to cart if has buy now price and the price < auction price
		                    if( WAUC_Functions::get_product_auction_price( $product->get_id() ) <= WAUC_Functions::has_buy_now_price($product) ) {
			                    echo apply_filters( 'wauc_set_auction_link',
				                    sprintf( '<a href="%s" rel="nofollow" data-product_id="%s" data-product_sku="%s" class="button %s product_type_%s">%s</a>',
					                    esc_url( get_permalink(get_site_url()).'?add-to-cart='.$product->get_id() ),
					                    esc_attr( $product->get_id() ),
					                    esc_attr( $product->get_sku() ),
					                    $product->is_purchasable() ? 'add_to_cart_button' : '',
					                    esc_attr( $product->get_type() ),
					                    esc_html( __( 'Add to Cart', 'wauc' ) )
				                    ));
		                    }
	                    }
                    }
                }

            } // if current
            elseif ( $status == 'processing' ) {
                //check if user purchased this
                if( !wc_customer_bought_product( WAUC_Functions::get_user_email( WAUC_Functions::get_current_user_id() ), WAUC_Functions::get_current_user_id(), $product->get_id() ) ) {
                    //check if user selected
                    if( WAUC_Functions::is_selected( WAUC_Functions::get_current_user_id(), $product->get_id() ) ) {
                        ?>
                        <input type="hidden" name="auction_win_request" value="<?php echo $product->get_id(); ?>">
                        <?php
                        echo apply_filters( 'wauc_set_auction_link',
                            sprintf( '<button href="%s" rel="nofollow" data-product_id="%s" data-product_sku="%s" class="button %s product_type_%s">%s</button>',
                                esc_url( $product->add_to_cart_url() ),
                                esc_attr( $product->get_id() ),
                                esc_attr( $product->get_sku() ),
                                $product->is_purchasable() ? 'add_to_cart_button' : '',
                                esc_attr( $product->get_type() ),
                                esc_html( __( 'Own the product', 'wauc' ) )
                            ));
                    } else {
                        ?>
                        <div class="wauc-notice wauc-notice-danger">
                            <?php _e( 'Auction End for this Product !', 'wauc' ); ?>
                        </div>
                        <?php
                    }
                } else {
                    ?>
                    <div class="wauc-notice wauc-notice-danger">
                        <?php _e( 'You have purchased this item already !', 'wauc' ); ?>
                    </div>
                    <?php
                }
            } elseif ( $status == 'past' ) {
                ?>
                <div class="wauc-notice wauc-notice-success">
                    <?php _e( 'Auction is end !', 'wauc' ); ?>
                </div>
                <?php
            }
            ?>
        </form>
        <div class="wauc_timestamp_wrapper">
        </div>
        <div id="wauc_time_counter">
            <?php
            if( $status == 'future' ) {
                echo WAUC_Functions::get_auction_remaining_time( strtotime( WAUC_Functions::get_auction_start_time( $product->get_id() ) ) );
            } elseif( $status == 'current') {
                echo WAUC_Functions::get_auction_remaining_time( strtotime( WAUC_Functions::get_auction_end_time( $product->get_id() ) ) );
            } elseif ( $status == 'processing' ) {

            } elseif ( $status == 'past' ) {

            }; ?>
        </div>
<?php
    }

    /**
     * This is section for
     * product token
     */
    public function token_section() {
        global $post;
        $product = wc_get_product( $post );
        if( !WAUC_Functions::is_product_token( $product ) ) return;

        if( wc_customer_bought_product( get_userdata( WAUC_Functions::get_current_user_id() )->user_email, WAUC_Functions::get_current_user_id(), $product->get_id() ) ) {
            ?>
            <div class="wauc-notice wauc-notice-danger">
                 <?php _e( 'You have already purchased this token !', 'wauc' ); ?>
            </div>
<?php
        }
    }

    /**
     *
     * @param $valid
     * @param $product_id
     * @return bool
     */
    function woocommerce_add_to_cart_validation($valid, $product_id){
        $product = wc_get_product( $product_id );

        if( WAUC_Functions::is_product_token( $product ) ) {
            if( wc_customer_bought_product( get_userdata( WAUC_Functions::get_current_user_id() )->user_email, WAUC_Functions::get_current_user_id(), $product_id ) ) {
                wc_add_notice( __( 'You have already purchased this item !', 'woocommerce' ), 'error' );
                $valid = false;
            }
        }
        //check if it is auction product
        elseif ( WAUC_Functions::is_auction_product( $product_id ) ) {
         //check if user already purchased
            if( wc_customer_bought_product( WAUC_Functions::get_user_email( WAUC_Functions::get_current_user_id() ), WAUC_Functions::get_current_user_id(), $product_id ) ) {
                wc_add_notice( __( 'Yor have owned this product already !', 'woocommerce' ), 'error' );
                $valid = false;
            } elseif( !WAUC_Functions::is_selected( WAUC_Functions::get_current_user_id(), $product_id ) ) {
                //if it is just buy now
                if( WAUC_Functions::get_product_auction_price( $product->get_id() ) > WAUC_Functions::has_buy_now_price($product) ) {
                    wc_add_notice( __( 'Yor don\'t have permission to own '.wc_get_product( $product_id )->get_name().' !', 'woocommerce' ), 'error' );
                    $valid = false;
                }
            }


        }

        return $valid;
    }

    /**
     * Check and process the
     * bid request of user
     */
    public function process_bid_request() {
        global $woocommerce,$_SESSION;

        if( !session_id() ) {
            session_start();
        }

        if( !isset( $_GET['wauc_bid'] ) ) return;
        /*if( !isset( $_POST['bidding_price'] ) ) return;*/
        if( !isset( $_POST['product_id'] ) || !is_numeric( $_POST['product_id'] ) ) return;

        do_action( 'process_bid_request_start', $_POST );

        //if requests for auction
        if( isset( $_POST['auction_request'] ) && $_POST['auction_request'] && is_numeric( $_POST['auction_request'] ) ) {
            WC()->cart->add_to_cart( $_POST['auction_request'] );
            wp_redirect( wc_get_cart_url() );
            exit;
        } elseif ( isset( $_POST['auction_win_request'] )
            && $_POST['auction_win_request']
            && is_numeric( $_POST['auction_win_request'] )
        ) {

            $product_id = $_POST['auction_win_request'];
            $product = wc_get_product( $product_id );


            if( WAUC_Functions::is_eligible_to_own( WAUC_Functions::get_current_user_id(), $product->get_id() ) ) {
                $product->is_purchasable();
                WC()->cart->add_to_cart( $product->get_id() );
                wp_redirect( wc_get_cart_url() );
                exit;
            };
            die();
        }

        $product = wc_get_product( $_POST['product_id'] );
        if( !$product )return;

        //check if this is auction product
        if( !WAUC_Functions::is_auction_product( $product ) ) {
            $_SESSION['wauc_'.WAUC_Functions::get_current_user_id()]['wauc_data'] = array(
                'result' => 'error',
                'msg' => __('This product is not for auction','wauc')
            );
            wp_redirect( get_permalink($product->get_id()) );
            exit;
        }

        //check if the user can bid this product
        if( !WAUC_Functions::is_eligible_to_bid( WAUC_Functions::get_current_user_id(), $product->get_id() ) ) {
            $_SESSION['wauc_'.WAUC_Functions::get_current_user_id()]['wauc_data'] = array(
                'result' => 'error',
                'msg' => __('You are not eligible to bid on this product !','wauc')
            );
            wp_redirect( get_permalink($product->get_id()) );
            exit;
        }

        $latest_bid_data = WAUC_Functions::get_latest_bid_row( $product->get_id() );


        //check if current user already bid this
        if( isset( $latest_bid_data->userid ) && $latest_bid_data->userid == WAUC_Functions::get_current_user_id() ) {
            $_SESSION['wauc_'.WAUC_Functions::get_current_user_id()]['wauc_data'] = array(
                'result' => 'error',
                'msg' => __('Your bid is already highest !','wauc')
            );
            wp_redirect( get_permalink($product->get_id()) );
            exit;
        }

        $current_auction_price =  WAUC_Functions::get_product_auction_price( $product->get_id() );
        $bid_increament = WAUC_Functions::get_bid_increament( $product->get_id() );

        //check if the user's bid has the minimum bid steps
        if ( ( $_POST['bidding_price'] - $current_auction_price ) % $bid_increament != 0 ) {
            $_SESSION['wauc_'.WAUC_Functions::get_current_user_id()]['wauc_data'] = array(
                'result' => 'error',
                'msg' => __('The bid should meet minimum bid increament unit','wauc')
            );
            wp_redirect( get_permalink($product->get_id()) );
            exit;
        }


        //check if user's bid exceeds current bid
        if( isset( $latest_bid_data->bid ) && $_POST['bidding_price'] <= $latest_bid_data->bid ) {
            $_SESSION['wauc_'.WAUC_Functions::get_current_user_id()]['wauc_data'] = array(
                'result' => 'error',
                'msg' => __('Your bid should be higher then current bid !','wauc')
            );
            wp_redirect( get_permalink($product->get_id()) );
            exit;
        }


        //everything is fine
        WAUC_Functions::log_bid( $product->get_id(), $_POST['bidding_price'], WAUC_Functions::get_current_user_id() );
        $_SESSION['wauc_'.WAUC_Functions::get_current_user_id()]['wauc_data'] = array(
            'result' => 'success',
            'msg' => __('You placed bid successfully !','wauc')
        );

        //notify users
        WAUC_Notification::send_bid_notification_to_bidder( $product, WAUC_Functions::get_current_user_id() );
        WAUC_Notification::send_bid_notification_to_participants( $product, WAUC_Functions::get_current_user_id() );

        wp_redirect( get_permalink($product->get_id()) );
        exit;
    }

    /**
     * make auction prodcut purchasable
     * after the deadline ends
     * @param $bool
     * @return bool
     */
    public function make_product_purchasable( $bool ) {
        return $bool;
        global $product;

        //$product = wc_get_product( $post->ID );
        if( WAUC_Functions::is_auction_product( $product ) ) {
            if( WAUC_Functions::is_eligible_to_own( WAUC_Functions::get_current_user_id(), $product->get_id() ) ) {
                return true;
            }
        }
        return $bool;
    }
}

WAUC_Auction_Action::get_instance();


/**
 * Remove quantity field
 * @param $return
 * @param $product
 * @return bool
 */
function wc_remove_all_quantity_fields( $return, $product ) {
    if( WAUC_Functions::is_product_token( $product ) ) {
        return true;
    }
}
add_filter( 'woocommerce_is_sold_individually', 'wc_remove_all_quantity_fields', 10, 2 );

//function for deleting ....
function remove_product_description_add_cart_button(){
    global $post;
    $product = wc_get_product( $post );
    if( !$product ) return;
    if( !WAUC_Functions::is_product_token( $product ) ) return;

    if( wc_customer_bought_product( get_userdata( WAUC_Functions::get_current_user_id() )->user_email, WAUC_Functions::get_current_user_id(), $product->get_id() ) ) {
        remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
    }
}
add_action('wp','remove_product_description_add_cart_button');

add_filter( 'woocommerce_loop_add_to_cart_link', function ( $btn, $product ) {
    if( WAUC_Functions::is_product_token( $product ) ) {
        if( wc_customer_bought_product( get_userdata( WAUC_Functions::get_current_user_id() )->user_email, WAUC_Functions::get_current_user_id(), $product->get_id() ) ) {
            return '';
        }
    }

    return $btn;

}, 10, 2 );

// render auction price
add_filter( 'woocommerce_product_get_price', 'get_price', 99, 2 );
function get_price ($price = '', $product ='') {
    if( !$product ) return;
    if( !WAUC_Functions::is_auction_product( $product ) ) return $price;
    $bid_price = WAUC_Functions::is_eligible_to_own( WAUC_Functions::get_current_user_id(), $product->get_id(), true );
    //pri($bid_price);
    if( !$bid_price ) return $price;
    return isset( $bid_price->bid ) ? $bid_price->bid : $price;//for now
}

add_filter( 'woocommerce_get_price_html', function ( $price, $product ) {
    return $price;
}, 10, 2 );

/////
add_action('woocommerce_checkout_update_order_meta',function( $order_id, $posted ) {
    $order = wc_get_order( $order_id );
    $order->update_meta_data( 'wauc_order_user_id', WAUC_Functions::get_current_user_id() );
    $order->save();
} , 10, 2);
