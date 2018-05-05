<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

if( !function_exists( 'pri' ) ) {
    function pri( $data ) {
        echo '<pre>';print_r($data);echo '</pre>';
    }
}
class WAUC_Functions {

    /**
     * get auction time
     * status
     */
    public static function get_auction_time_status( $product_id ) {
        $start_time = get_post_meta( $product_id, 'wauc_auction_start', true );

        if( $start_time > time() ) {
            if( get_post_meta( $product_id, '_wauc_current_status', true ) == 'processing' ) {
                return 'processing';
            }
            //if product is purchased before time over
            elseif ( get_post_meta( $product_id, '_wauc_current_status', true ) == 'completed' ) {
                return 'past';
            }
            return 'future';
        } else {
            $endtime = get_post_meta( $product_id, 'wauc_auction_end', true );

            if( $endtime < time() ) {
                if( get_post_meta( $product_id, '_wauc_current_status', true ) == 'processing' ) {
                    return 'processing';
                } elseif ( get_post_meta( $product_id, '_wauc_current_status', true ) == 'completed' ) {
                    return 'past';
                }
                return 'past';
            } elseif ( $endtime > time() ) {
                if( get_post_meta( $product_id, '_wauc_current_status', true ) == 'processing' ) {
                    return 'processing';
                }
                //if product is purchased before time over
                elseif ( get_post_meta( $product_id, '_wauc_current_status', true ) == 'completed' ) {
                    return 'past';
                }
                return 'current';
            }
        }
    }

    /**
     * get auction start time
     * @param $product_id
     */
    public static function get_auction_start_time( $product_id ) {
        return date( 'Y-m-d H:i:s', get_post_meta( $product_id, 'wauc_auction_start', true ) ) ;
    }

    public static function get_auction_end_time( $product_id ) {
        return date( 'Y-m-d H:i:s', get_post_meta( $product_id, 'wauc_auction_end', true ) );
    }

    public static function get_auction_remaining_time( $time ) {
        return abs( strtotime(date('Y-m-d H:i:s')) - $time );
    }

    /**
     * return current chanllanged price
     * @param $product_id
     */
    public static function get_product_auction_price( $product_id ) {
        $latest_bid = WAUC_Functions::get_latest_bid( $product_id );
        if( $latest_bid ) {
            return $latest_bid;
        }
        return WAUC_Functions::get_base_price( $product_id );
    }

    /**
     * return product's base price
     * @param $product_id
     */
    public static function get_base_price( $product_id ) {
        return get_post_meta( $product_id, 'wauc_base_price' , true );
    }


    /**
     * get bid increament
     * @param $product_id
     * @return mixed
     */
    public static function get_bid_increament( $product_id ) {
        return get_post_meta( $product_id, 'wauc_bid_increment', true );
    }

    public static function change_auction_status( $old_status = null , $new_status, $auction_id ) {
        $change = 1;
        if( $old_status ) {
            if ( get_post_meta( $auction_id, '_wauc_current_status', true ) != $old_status ) {
                $change = 0;
            }
        }
        if( $change ) {
            if( update_post_meta( $auction_id, '_wauc_current_status', $new_status ) ) return true;
        }

        return false;
    }

    /**
     * Ajax delete bid
     *
     * Function for deleting bid in wp admin
     *
     * @access public
     * @param  array
     * @return string
     *
     */
    public static function wp_ajax_delete_bid(){
        global $wpdb;
        if ( !current_user_can('edit_product', $_POST["postid"]))  die ();

        if($_POST["postid"] && $_POST["logid"]){
            $product_data = wc_get_product($_POST["postid"]);
            $log = $wpdb->get_row( $wpdb->prepare("SELECT * FROM ".$wpdb->prefix."wauc_auction_log WHERE id=%d", $_POST["logid"]) );
            if(!is_null($log)){
                if($product_data -> auction_type == 'normal'){
                    if(($log->bid == $product_data->auction_current_bid) && ($log->userid == $product_data->auction_current_bider)){

                        $newbid =$wpdb->get_row( $wpdb->prepare("SELECT * FROM ".$wpdb->prefix."wauc_auction_log WHERE auction_id =%d ORDER BY  `date` desc , `bid`  desc LIMIT 1, 1 ", $_POST["postid"]) );
                        if(!is_null($newbid)){
                            update_post_meta($_POST["postid"], '_auction_current_bid', $newbid->bid);
                            update_post_meta($_POST["postid"], '_auction_current_bider', $newbid -> userid);
                            delete_post_meta($_POST["postid"], '_auction_max_bid');
                            delete_post_meta($_POST["postid"], '_auction_max_current_bider');
                            do_action('woocommerce_simple_auction_delete_bid',  array( 'product_id' => $_POST["postid"] ,  'delete_user_id' => $log->userid, 'new_max_bider_id ' => $newbid -> userid));
                        } else {
                            delete_post_meta($_POST["postid"], '_auction_current_bid');
                            delete_post_meta($_POST["postid"], '_auction_current_bider');
                            delete_post_meta($_POST["postid"], '_auction_max_bid');
                            delete_post_meta($_POST["postid"], '_auction_max_current_bider');
                            do_action('woocommerce_simple_auction_delete_bid',  array( 'product_id' => $_POST["postid"] ,  'delete_user_id' => $log->userid, 'new_max_bider_id ' => FALSE));
                        }
                        $wpdb->query( $wpdb->prepare("DELETE FROM ".$wpdb->prefix."wauc_auction_log WHERE id= %d", $_POST["logid"]) );
                        update_post_meta($_POST["postid"], '_auction_bid_count', absint($product_data -> auction_bid_count - 1));
                        $return['action'] = 'deleted';

                    } else {
                        $wpdb->query( $wpdb->prepare("DELETE FROM ".$wpdb->prefix."wauc_auction_log WHERE id= %d", $_POST["logid"]) );
                        update_post_meta($_POST["postid"], '_auction_bid_count', absint($product_data -> auction_bid_count - 1));
                        $wpdb->query( $wpdb->prepare("DELETE FROM ".$wpdb->prefix."wauc_auction_log WHERE id= %d", $_POST["logid"]) );
                        do_action('woocommerce_simple_auction_delete_bid',  array( 'product_id' => $_POST["postid"] ,  'delete_user_id' => $log->userid));
                        $return['action'] = 'deleted';

                    }

                } elseif($product_data -> auction_type == 'reverse') {
                    if(($log->bid == $product_data->auction_current_bid) && ($log->userid == $product_data->auction_current_bider)){

                        $newbid =$wpdb->get_row( $wpdb->prepare("SELECT * FROM ".$wpdb->prefix."wauc_auction_log WHERE auction_id =%d ORDER BY  `date` desc , `bid`  asc desc LIMIT 1, 1 ", $_POST["postid"]) );
                        if(!is_null($newbid)){
                            update_post_meta($_POST["postid"], '_auction_current_bid', $newbid->bid);
                            update_post_meta($_POST["postid"], '_auction_current_bider', $newbid -> userid);
                            delete_post_meta($_POST["postid"], '_auction_max_bid');
                            delete_post_meta($_POST["postid"], '_auction_max_current_bider');
                            do_action('woocommerce_simple_auction_delete_bid',  array( 'product_id' => $_POST["postid"] ,  'delete_user_id' => $log->userid, 'new_max_bider_id ' => $newbid -> userid));
                        } else {
                            delete_post_meta($_POST["postid"], '_auction_current_bid');
                            delete_post_meta($_POST["postid"], '_auction_current_bider');
                            do_action('woocommerce_simple_auction_delete_bid',  array( 'product_id' => $_POST["postid"] ,  'delete_user_id' => $log->userid, 'new_max_bider_id ' => FALSE));
                        }
                        $wpdb->query( $wpdb->prepare("DELETE FROM ".$wpdb->prefix."wauc_auction_log WHERE id= %d", $_POST["logid"]) );
                        update_post_meta($_POST["postid"], '_auction_bid_count', absint($product_data -> auction_bid_count - 1));
                        $return['action'] = 'deleted';

                    } else{
                        $wpdb->query( $wpdb->prepare("DELETE FROM ".$wpdb->prefix."wauc_auction_log  WHERE id= %d", $_POST["logid"]) );
                        update_post_meta($_POST["postid"], '_auction_bid_count', absint($product_data -> auction_bid_count - 1));
                        do_action('woocommerce_simple_auction_delete_bid',  array( 'product_id' => $_POST["postid"] ,  'delete_user_id' => $log->userid));
                        $return['action'] = 'deleted';

                    }


                }
                $product = wc_get_product($_POST["postid"]);
                if($product){
                    $return['auction_current_bid']   = wc_price($product->get_curent_bid());
                    $return['auction_current_bider'] = '<a href="'.get_edit_user_link($product->auction_current_bider).'">'.get_userdata($product->auction_current_bider)->display_name.'</a>';
                }

                if(isset($return))
                    wp_send_json($return);
                exit;

            }

        }
        $return['action'] = 'failed';
        if(isset($return))
            wp_send_json($return);
        exit;

    }

    /**
     * get last bid price
     * of a product
     */
    public static function get_latest_bid( $post_id ) {
        global $wpdb;
        return $wpdb->get_var( $wpdb->prepare( 'SELECT bid FROM '.$wpdb -> prefix .'wauc_auction_log WHERE auction_id = %d ORDER BY date DESC', $post_id ) );
    }

    /**
     * get last bid price
     * of a product
     */
    public static function get_latest_bid_row( $post_id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM '.$wpdb -> prefix .'wauc_auction_log WHERE auction_id = %d ORDER BY date DESC', $post_id ) );
    }

    /**
     * get last bidder
     * @param $post_id
     * @return mixed
     */
    public static function get_last_bidder( $post_id ) {
        global $wpdb;
        return $wpdb->get_var( $wpdb->prepare( 'SELECT userid FROM '.$wpdb -> prefix .'wauc_auction_log WHERE auction_id = %d ORDER BY date DESC', $post_id ) );
    }


    /**
     * check if current user
     * is last bidder
     * @param $post_id
     * @return bool
     */
    public static function is_last_bidder( $post_id ) {
        if( get_current_user_id() != WAUC_Functions::get_last_bidder( $post_id ) ) {
            return false;
        }

        return true;
    }

    /**
     * check if user is eligible to bid
     * @param $user_id
     * @param $product_id
     */
    public static function is_eligible_to_bid( $user_id = null , $product_id = null ) {

        if( !$user_id ) return false;
        if( !$product_id ) return false;
        if( WAUC_Functions::is_user_forbidden( $user_id ) ) return false;

        if( strtotime( WAUC_Functions::get_auction_start_time( $product_id ) ) < time() && time() < strtotime( WAUC_Functions::get_auction_end_time( $product_id ) ) ) {
            return true;
        } ;

        return false;

    }

    /**
     *
     */
    public static function is_auction_product( $product ) {
        if( is_integer( $product ) )
            $product = wc_get_product( $product );
        return $product->get_type() == 'auction' ? true : false;
    }

    /**
     *
     */
    public static function is_user_forbidden( $user_id ) {
        $banned_bidder = get_option( 'wauc_banned_bidder' );
        !is_array( $banned_bidder ) ? $banned_bidder = array() : '';
        if( !in_array( $user_id, $banned_bidder ) ) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Log bid
     *
     * @param string, int, int, int
     * @return void
     *
     */
    public static function log_bid($product_id, $bid, $current_user_id, $proxy = 0) {

        global $wpdb;
        $log_bid = $wpdb -> insert($wpdb -> prefix .'wauc_auction_log', array('userid' => $current_user_id, 'auction_id' => $product_id, 'bid' => $bid, 'proxy' => $proxy , 'date' => current_time('mysql')), array('%d', '%d', '%f', '%d' , '%s'));

    }

    /**
     * Delete logs when auction is deleted
     *
     * @access public
     * @param  string
     * @return void
     *
     */
    public static function del_auction_logs( $post_id){
        global $wpdb;
        if ( $wpdb->get_var( $wpdb->prepare( 'SELECT auction_id FROM '.$wpdb -> prefix .'wauc_auction_log WHERE auction_id = %d', $post_id ) ) )
            return $wpdb->query( $wpdb->prepare( 'DELETE FROM '.$wpdb -> prefix .'wauc_auction_log WHERE auction_id = %d', $post_id ) );

        return true;
    }

    /**
     * Delete log
     * @param $id
     * @return bool|false|int
     */
    public static function delete_log( $id ) {
        global $wpdb;
        return $wpdb->query( $wpdb->prepare( 'DELETE FROM '.$wpdb -> prefix .'wauc_auction_log WHERE id = %d', $id ) );
        return false;
    }


    /**
     * get log list
     * get auction logs
     * @return bool
     */
    public static function get_log_list( $post_id, $offset = 0, $per_page = 10 ) {
        global $wpdb;
        $data = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM '.$wpdb -> prefix .'wauc_auction_log LEFT JOIN '.$wpdb->prefix.'users ON '.$wpdb->prefix.'users.id = '.$wpdb->prefix.'wauc_auction_log.userid WHERE auction_id = %d ORDER BY date DESC LIMIT '.$offset.','.$per_page, $post_id ) );
        return $data;
    }

    /**
     * Get participent list
     * @param $product
     * @param int $offset
     * @param int $per_page
     * @return array
     */
    public static function get_participant_list( $product, $offset = 0, $per_page = 10 ) {

        if( is_object( $product ) ) {
            $product_id = $product->ID;
        } else{
            $product_id = $product;
        }

        $orders = WAUC_Functions::get_orders_by_product_id(get_post_meta( $product_id,'wauc_token_id', true), $offset, $per_page );
        $user_records = array();

        if ( !empty($orders) ) {
            foreach ( $orders->posts as $order_id ) {
                $order = new WC_Order( $order_id );
                $user = get_user_by( 'email', $order->get_billing_email() );

                if( !isset( $user_records[ $order->get_billing_email() ]) ) {
                    $user_records[$order->get_billing_email()] = array(
                        'user_email' => $order->get_billing_email(),
                        'display_name' => isset( $user->display_name ) ? $user->display_name : '',
                        'user_id' => isset( $user->ID ) ? $user->ID : '',
                    );
                }
            }
        }

        return $user_records;
    }

    /**
     * Get customer who purchase this product
     * @param $product_id
     * @return WP_Query
     */
    static function get_orders_by_product_id($product_id, $offset = null, $per_page = null ) {

        global $wpdb;
        $tabelaOrderItemMeta = $wpdb->prefix . 'woocommerce_order_itemmeta';
        $tabelaOrderItems = $wpdb->prefix . 'woocommerce_order_items';
        $limiter_str = '';

        if( $offset !== null && $per_page !== null ) {
            $limiter_str = " LIMIT $offset, $per_page ";
        }

        if( is_array( $product_id ) ) {

            echo "SELECT b.order_id, a.meta_value as product_id
FROM {$tabelaOrderItemMeta} a, {$tabelaOrderItems} b
WHERE a.meta_key = '_product_id'
AND a.meta_value IN (%s)
AND a.order_item_id = b.order_item_id
ORDER BY b.order_id DESC $limiter_str";

            $results = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT b.order_id, a.meta_value as product_id
FROM {$tabelaOrderItemMeta} a, {$tabelaOrderItems} b
WHERE a.meta_key = '_product_id'
AND a.meta_value IN (%s)
AND a.order_item_id = b.order_item_id
ORDER BY b.order_id DESC $limiter_str",

                    implode(',', $product_id ) //array('336','378')
                )
            );
        } else {
            $results = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT b.order_id
FROM {$tabelaOrderItemMeta} a, {$tabelaOrderItems} b
WHERE a.meta_key = '_product_id'
AND a.meta_value = %s
AND a.order_item_id = b.order_item_id
ORDER BY b.order_id DESC $limiter_str",
                    $product_id
                )
            );
        }


        if ($results) {

            $order_ids = array();
            foreach ($results as $item)
                array_push($order_ids, $item->order_id);

            if ($order_ids) {
                $args = array(
                    'post_type' => 'shop_order',
                    'post_status' => array('wc-processing', 'wc-completed'),
                    'posts_per_page' => -1,
                    'post__in' => $order_ids,
                    'fields' => 'ids'
                );
                $query = new WP_Query($args);
                return $query;
            }
        }

        return false;
    }

    /**
     * get products with ending soon
     * @param int $timediff
     * @param null $offset
     * @param null $per_page
     * @param string $auction_status
     * @return array|null|object
     */
    public static function get_near_ending_products( $timediff = 60*60*24, $offset = null, $per_page = null, $auction_status = 'current' ) {
        global $wpdb;

        $limit_str = '';
        if( $offset !== null && $per_page !== null ) {
            $limit_str = ' LIMIT 0, 10 ';
        }
    $query = "SELECT SQL_CALC_FOUND_ROWS  $wpdb->posts.ID FROM $wpdb->posts  INNER JOIN $wpdb->postmeta ON ( $wpdb->posts.ID = $wpdb->postmeta.post_id )  INNER JOIN $wpdb->postmeta AS mt1 ON ( $wpdb->posts.ID = mt1.post_id ) WHERE 1=1  AND ( 
  ( $wpdb->postmeta.meta_key = 'wauc_auction_end' AND $wpdb->postmeta.meta_value - ".time()." < $timediff AND $wpdb->postmeta.meta_value > ".time()." ) 
  AND 
  ( mt1.meta_key = 'wauc_auction_start' AND mt1.meta_value < ".time()." )
) AND $wpdb->posts.post_type = 'product' GROUP BY $wpdb->posts.ID ORDER BY $wpdb->posts.post_date DESC $limit_str";

        $products = $wpdb->get_results( $query );

        return $products;
    }
    /**
     * Check if the product requires token
     * @param $post_id
     * @return bool|mixed
     */
    public static function is_token_fee( $post_id ) {
        $fee = get_post_meta( $post_id, 'wauc_auction_deposit', true );

        return false;
    }

    public static function get_token( $post_id ) {
        return get_post_meta( $post_id, 'wauc_token_id', true );
    }

    /**
     * @param null $post_id
     * @param null $token_id
     * @return mixed
     */
    public static function get_deposit_amount( $post_id = null, $token_id = null ) {
        if( $post_id ) {
            return get_post_meta( $post_id, 'wauc_auction_deposit', true );
        } elseif ( $token_id ) {
            return get_post_meta( $post_id, '_price', true );
        }
    }


    /**
     * Check if user has deposit fee
     * @param $token
     * @return bool
     */
    public static function has_user_deposit( $token ) {
        if( is_user_logged_in() ) {
            if( wc_customer_bought_product( wp_get_current_user()->user_email, get_current_user_id(), $token ) ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if the user has got token
     * @param $token
     * @return bool
     */
    public static function has_user_token( $token ) {
        if( WAUC_Functions::get_current_user_id() ) {
            if( wc_customer_bought_product( WAUC_Functions::get_user_email(WAUC_Functions::get_current_user_id()), WAUC_Functions::get_current_user_id(), $token ) ) {
                return true;
            }
        }
        return false;
    }

    /**
     * get user email
     * @param null $user_id
     * @return bool|string
     */
    public static function get_user_email( $user_id = null ) {
        if( !$user_id ) {
            return false;
        }

        return get_userdata( $user_id )->user_email;
    }


    /**
     * get current user id
     * @return bool|int
     */
    public static function get_current_user_id() {

        if( is_user_logged_in() ) {
            return get_current_user_id();
        }

        return false;
    }

    /**
     * get product token if it has
     * @param $product
     * @return mixed
     */
    public static function is_product_token( $product ) {
        return get_post_meta( $product->get_id(), 'wauc_product_id', true );
    }

    public static function is_pro() {
        if( file_exists( WAUC_ROOT.'/pro/loader.php' ) ) {
            return true;
        }
        return false;
    }


    /**
     * @param $section
     * @param null $option
     * @param string $default
     * @return string
     */
    public static function get_settings( $section, $option = null, $default = 'no' ) {
        global $wauc_notification_settings, $wauc_general_settings;

        switch ( $section ) {
            case 'notification':
                if( empty( $wauc_notification_settings ) ) {
                    $wauc_notification_settings = get_option( 'wauc_notification' );
                }
                return $option ? ( isset( $wauc_notification_settings[$option] ) ? $wauc_notification_settings[$option] : '' ) : $default;
                break;
            case 'general' :
                if( empty( $wauc_general_settings ) ) {
                    $wauc_general_settings = get_option( 'wauc_general_settings' );
                }
                return $option ? ( isset( $wauc_general_settings[$option] ) ? $wauc_general_settings[$option] : '' ) : $default;
                break;
        }
    }

    /**
     * @param $data
     */
    public static function log( $data ) {
        if( WAUC_PRODUCTION ) return;

        $file = fopen( WAUC_ROOT.'/log.txt', 'a+' );
        fprintf( $file, date('d-m-y H:i:s',time())."\r\n" );
        foreach ( $data as $k => $line ) {
            fprintf( $file, $line."\r\n" );
        }
        fprintf( $file, "\r\n\n" );
        fclose($file);
    }

    /**
     * check if the user is selected
     * @param $user_id
     * @param $product_id
     * @param null $with_bid
     * @return array|bool|null|object|void
     */
    public static function is_selected( $user_id, $product_id, $with_bid = null ) {
        global $wpdb;
        $winners_table = $wpdb->prefix."wauc_winners";
        $row = $wpdb->get_row( "SELECT * FROM $winners_table WHERE userid = $user_id AND auction_id = $product_id AND is_selected = 1 AND is_winner = 0" );
        return $row;
    }

    /**
     * @param $user_id
     * @param $auction_id
     * @return false|int
     */
    public static function select_as_winner( $user_id, $auction_id ) {
        global $wpdb;
        $winners_table = $wpdb->prefix."wauc_winners";

        $row = $wpdb->get_row("SELECT id FROM $winners_table WHERE userid = $user_id AND auction_id = $auction_id");

        $result = '';
        if( !$row ) {
            $result = $wpdb->insert(
                $winners_table,
                array(
                    'userid' => $user_id,
                    'auction_id' => $auction_id,
                    'is_selected' => 1,
                ),
                array(
                    '%d',
                    '%d',
                    '%d'
                )
            );
        } else {
            $result = $wpdb->update(
                $winners_table,
                array(
                    'userid' => $user_id,
                    'auction_id' => $auction_id,
                    'is_selected' => 1,
                ),
                array( 'id' => $row->id ),
                array(
                    '%d',	// value1
                    '%d',	// value2
                    '%d'
                ),
                array( '%d' )
            );
        }

        return $result;
    }


    public static function set_as_winner( $user_id, $auction_id ) {
        global $wpdb;
        $winners_table = $wpdb->prefix."wauc_winners";
        $row = $wpdb->get_row("SELECT * FROM $winners_table WHERE userid = $user_id AND auction_id = $auction_id AND is_selected = 1");

        $result = '';
        if( $row ) {
            $result = $wpdb->update(
                $winners_table,
                array(
                    'userid' => $user_id,
                    'auction_id' => $auction_id,
                    'is_selected' => 1,
                    'is_winner' => 1
                ),
                array( 'id' => $row->id ),
                array(
                    '%d',	// value1
                    '%d',	// value2
                    '%d',
                    '%d'
                ),
                array( '%d' )
            );
        }
        return $result;
    }

    /**
     * Cancel bidder as winner
     * @param $user_id
     * @param $auction_id
     * @return false|int
     */
    public static function cancel_as_winner( $user_id, $auction_id ) {
        global $wpdb;
        $winners_table = $wpdb->prefix."wauc_winners";

        $row = $wpdb->get_row("SELECT id FROM $winners_table WHERE userid = $user_id AND auction_id = $auction_id");

        $result = '';
        if( !$row ) {
            $result = $wpdb->insert(
                $winners_table,
                array(
                    'userid' => $user_id,
                    'auction_id' => $auction_id,
                    'is_selected' => 0,
                ),
                array(
                    '%d',
                    '%d',
                    '%d'
                )
            );
        } else {
            $result = $wpdb->update(
                $winners_table,
                array(
                    'userid' => $user_id,
                    'auction_id' => $auction_id,
                    'is_selected' => 0,
                ),
                array( 'id' => $row->id ),
                array(
                    '%d',	// value1
                    '%d',	// value2
                    '%d'
                ),
                array( '%d' )
            );
        }

        return $result;
    }

    /**
     * Check if the user is winner
     * for this auction product
     * @param $user_id
     * @param $product_id
     * @return bool
     */
    public static function is_winner( $user_id, $product_id ) {
        global $wpdb;
        $query = "SELECT is_winner FROM wp_wauc_winners WHERE wp_wauc_winners.userid = $user_id AND wp_wauc_winners.product_id = $product_id";
        $row = $wpdb->get_row( $query );
        if(  $row ) {
            return true;
        }
        return false;
    }

    /**
     * Get buy now price
     * @param $product
     * @return mixed
     */
    public static function has_buy_now_price( $product ) {
        return $product->get_price();
    }

    /**
     * Check if the user is eligible get the product
     * @param $user_id
     * @param $product_id
     */
    public static function is_eligible_to_own( $user_id, $product_id, $with_bid = false ) {
        //if( WAUC_Functions::get_auction_time_status( $product_id ) != 'processing' ) return false;
        $sel_data = WAUC_Functions::is_selected( WAUC_Functions::get_current_user_id(), $product_id );
        if( empty( $sel_data ) ) return false;
        return $sel_data;
    }

    /**
     * get winners
     * and recently end auctions
     */
    public static function get_top_bidders_with_end_auctions() {
        global $wpdb;
        $auction_table = $wpdb->prefix."wauc_auction_log";

        //get all running product
        $args = array(
            'post_status' => 'publish',
            'post_type' => 'product',
            'meta_query' => array(
                array(
                    'key' => 'wauc_auction_end',
                    'value' => time(),
                    'type' => 'CHAR',
                    'compare' => '<',
                ),
                array(
                    'key' => '_wauc_current_status',
                    'value' => 'running',
                    'type' => 'CHAR',
                    'compare' => '=',
                )
            )
        );
        $running_auctions = new WP_Query($args);
        $running_auc_ids = array();
        if( $running_auctions->have_posts() ) {
            while( $running_auctions->have_posts() ) {
                $running_auctions->the_post();
                $running_auc_ids[] = get_the_ID();
            }
        }
        //term
        if( empty( $running_auc_ids ) ) return array();

        $query = "SELECT DISTINCT tt.*, wp_users.user_email, wp_posts.post_title as auction_name
FROM $auction_table tt
INNER JOIN
    (SELECT auction_id, MAX(bid) AS max_bid
    FROM ( SELECT * FROM $auction_table c WHERE c.is_fake = 0 ) d
    GROUP BY auction_id) groupedtt 
ON tt.auction_id = groupedtt.auction_id 
AND tt.bid = groupedtt.max_bid
INNER JOIN wp_users
ON wp_users.ID = tt.userid
INNER JOIN $wpdb->posts wp_posts
ON wp_posts.ID = tt.auction_id
WHERE tt.auction_id IN (".implode(',',$running_auc_ids).")
";

        $result = $wpdb->get_results($query);
        return $result;
    }


    /**
     * Get processing auction list with
     * winners awaiting for
     * @return array|null|object
     */
    public static function get_awaiting_winners_with_end_auctions() {
        global $wpdb;
        $winners_table = $wpdb->prefix."wauc_winners";
        $query = "SELECT * FROM $winners_table WHERE is_selected = 1 && is_winner = 0";
        $result = $wpdb->get_results($query);
        return $result;
    }

    /**
     * Get all awaiting
     * winners
     */
    public static function get_all_awaiting_winners() {
        global $wpdb;
        $winners_table = $wpdb->prefix."wauc_winners";
        return $wpdb->get_results("SELECT * FROM $winners_table WHERE is_selected = 1 AND is_winner = 0");
    }

    public static function get_winners_with_completed_auctions() {
        global $wpdb;
        $winners_table = $wpdb->prefix."wauc_winners";
        return $wpdb->get_results("SELECT * FROM $winners_table WHERE is_winner = 1");
    }

    /**
     * Make a bid row fake
     * @param $bid_id
     */
    public static function fake_bid( $bid_id ) {
        global $wpdb;
        $auction_table = $wpdb->prefix."wauc_auction_log";

        return $wpdb->update(
            $auction_table,
            array(
                'is_fake' => 1,
            ),
            array( 'id' => $bid_id ),
            array(
                '%d',	// value1
            ),
            array( '%d' )
        );
    }

    /**
     * @param $user_id
     * @param $auction_id
     * @return false|int
     */
    public static function fake_auction_bidder( $user_id, $auction_id ) {
        global $wpdb;
        $auction_table = $wpdb->prefix."wauc_auction_log";

        return $wpdb->update(
            $auction_table,
            array(
                'is_fake' => 1,
            ),
            array(
                'userid' => $user_id,
                'auction_id' => $auction_id
            ),
            array(
                '%d',	// value1
            ),
            array( '%d', '%d' )
        );
    }

    /**
     * Generate report
     */
    public static function generate_report( $type = 'recent', $echo = true ) {
        if( $type == 'recent' ) {
            $result = WAUC_Functions::get_top_bidders_with_end_auctions();
        } elseif ( $type == 'processing' ) {
            $result = WAUC_Functions::get_awaiting_winners_with_end_auctions();
        } elseif ( $type == 'completed' ) {
            $result = WAUC_Functions::get_winners_with_completed_auctions();
        }

        if( !$echo ) ob_start();
        ?>
        <table>
            <?php
            if( !empty( $result ) ) {
                foreach ( $result as $k => $item ) {
                    ?>
                    <tr class="data-column">
                        <td>
                            <?php echo wc_get_product( $item->auction_id )->get_name(); ?>
                        </td>
                        <td>
                            <?php echo get_userdata($item->userid)->user_nicename; ?>
                        </td>
                        <td>
                            <?php echo get_userdata($item->userid)->user_email; ?>
                        </td>
                        <td>
                            <table>
                                <tr>
                                    <?php
                                    if( $type == 'recent' ) {
                                        ?>
                                        <td><a href="javascript:" class="button button-primary select_as_winner"
                                              data-user_id="<?php echo $item->userid; ?>"
                                              data-auction_id="<?php echo $item->auction_id; ?>"
                                            ><?php _e( 'Select as Winner','wauc'); ?></a>
                                            <p class="description"><?php _e( 'Selecting as winner will notify this user about winning the auction and ask to claim/grab the 
                             product within a specific time', 'wauc'); ?></p>
                                        </td>
                                        <?php
                                        ?>
                                        <td>
                                            <a href="javascript:" class="button button-default skip_bid" data-bid_id="<?php echo $item->id; ?>"><?php _e( 'Skip this bid and proceed to next bid','wauc'); ?></a>
                                            <p class="description"><?php _e( 'This action will skip this bid and choose the next bid and bidder as winner','wauc'); ?></p>
                                        </td>


                                        <td>
                                            <a href="javascript:" class="button button-default skip_bidder"
                                               data-user_id="<?php echo $item->userid; ?>"
                                               data-auction_id="<?php echo $item->auction_id; ?>"
                                            ><?php _e( 'Skip all bid by this user','wauc'); ?></a>
                                            <p class="description"><?php _e( 'This action will abandon all the bid by this user', 'wauc'); ?></p>
                                        </td>
                                        <?php
                                    } elseif ( $type == 'processing' ) {
                                        ?>
                                        <td>
                                            <a href="javascript:" class="button button-primary cancel_as_winner"
                                              data-user_id="<?php echo $item->userid; ?>"
                                              data-auction_id="<?php echo $item->auction_id; ?>"
                                            ><?php _e( 'Cancel Bidder as Winner','wauc'); ?></a>
                                            <p class="description"><?php _e( 'Cancel this bidder as winner', 'wauc'); ?></p>
                                        </td>
                                        <?php
                                    }
                                    ?>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <?php
                }
            } else {
                ?>
                <td><h4><?php _e( 'There is no data here !', 'wauc' ); ?></h4></td>
                <?php
            }
            ?>
        </table>
        <?php
        if( !$echo ) return ob_get_clean();
    }


    /**
     *
     */
    public static function deselect_winner( $userids, $auction_ids ) {
        global $wpdb;
        $winners_table = $wpdb->prefix."wauc_winners";

        $query = "UPDATE $winners_table
SET is_selected='%d', is_winner='%d'
WHERE userid IN ('%s') AND auction_id IN ('%s')";

        return $wpdb->query(
            $wpdb->prepare( $query, 0, 0, implode(',',$userids), implode(',',$auction_ids) )
        );
    }

    public static function change_publish_status( $status, $post_ids, $post_id = null ) {
        global $wpdb;
        if( $post_id ) {
            $params = array(
                'ID'           => $post_id,
                'post_status' => $status
            );
            return wp_update_post( $params );
        }

        $query = "UPDATE $wpdb->posts SET post_status = '%s' WHERE ID IN ('%s')";
        return $wpdb->query( $wpdb->prepare( $query,$status,implode(',',$post_ids) ) );
    }
}

