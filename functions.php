<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class WAUC_Functions {

    /**
     * get auction time
     * status
     */
    public static function get_auction_time_status( $product_id ) {
        $start_time = get_post_meta( $product_id, 'wauc_auction_start', true );

        if( $start_time > time() ) {
            return 'future';
        } else {
            $endtime = get_post_meta( $product_id, 'wauc_auction_end', true );

            if( $endtime < time() ) {
                return 'past';
            } elseif ( $endtime > time() ) {
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





    public static function simple_auction_send_reminders_email(){
        //$woocommerce_auctions->send_reminders_email();
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
            $product_data = get_product($_POST["postid"]);
            $log = $wpdb->get_row( $wpdb->prepare("SELECT * FROM ".$wpdb->prefix."simple_auction_log WHERE id=%d", $_POST["logid"]) );
            if(!is_null($log)){
                if($product_data -> auction_type == 'normal'){
                    if(($log->bid == $product_data->auction_current_bid) && ($log->userid == $product_data->auction_current_bider)){

                        $newbid =$wpdb->get_row( $wpdb->prepare("SELECT * FROM ".$wpdb->prefix."simple_auction_log WHERE auction_id =%d ORDER BY  `date` desc , `bid`  desc LIMIT 1, 1 ", $_POST["postid"]) );
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
                        $wpdb->query( $wpdb->prepare("DELETE FROM ".$wpdb->prefix."simple_auction_log WHERE id= %d", $_POST["logid"]) );
                        update_post_meta($_POST["postid"], '_auction_bid_count', absint($product_data -> auction_bid_count - 1));
                        $return['action'] = 'deleted';

                    } else {
                        $wpdb->query( $wpdb->prepare("DELETE FROM ".$wpdb->prefix."simple_auction_log WHERE id= %d", $_POST["logid"]) );
                        update_post_meta($_POST["postid"], '_auction_bid_count', absint($product_data -> auction_bid_count - 1));
                        $wpdb->query( $wpdb->prepare("DELETE FROM ".$wpdb->prefix."simple_auction_log WHERE id= %d", $_POST["logid"]) );
                        do_action('woocommerce_simple_auction_delete_bid',  array( 'product_id' => $_POST["postid"] ,  'delete_user_id' => $log->userid));
                        $return['action'] = 'deleted';

                    }

                } elseif($product_data -> auction_type == 'reverse') {
                    if(($log->bid == $product_data->auction_current_bid) && ($log->userid == $product_data->auction_current_bider)){

                        $newbid =$wpdb->get_row( $wpdb->prepare("SELECT * FROM ".$wpdb->prefix."simple_auction_log WHERE auction_id =%d ORDER BY  `date` desc , `bid`  asc desc LIMIT 1, 1 ", $_POST["postid"]) );
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
                        $wpdb->query( $wpdb->prepare("DELETE FROM ".$wpdb->prefix."simple_auction_log WHERE id= %d", $_POST["logid"]) );
                        update_post_meta($_POST["postid"], '_auction_bid_count', absint($product_data -> auction_bid_count - 1));
                        $return['action'] = 'deleted';

                    } else{
                        $wpdb->query( $wpdb->prepare("DELETE FROM ".$wpdb->prefix."simple_auction_log  WHERE id= %d", $_POST["logid"]) );
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
    public static function is_eligible_to_bid( $user_id , $product_id ) {

        if( !$user_id ) return false;
        if( !$product_id ) return false;
        if( WAUC_Functions::is_user_forbidden( $user_id ) ) return false;

        $status = WAUC_Functions::get_auction_time_status( $product_id );

        if( $status == 'future' || $status == 'past' ) return false;

        return true;

    }

    /**
     *
     */
    public static function is_auction_product( $product ) {
        return $product->product_type == 'auction' ? true : false;
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
        if ( $wpdb->get_var( $wpdb->prepare( 'SELECT auction_id FROM '.$wpdb -> prefix .'simple_auction_log WHERE auction_id = %d', $post_id ) ) )
            return $wpdb->query( $wpdb->prepare( 'DELETE FROM '.$wpdb -> prefix .'simple_auction_log WHERE auction_id = %d', $post_id ) );

        return true;
    }

    /**
     *
     */
    public static function delete_log( $id ) {
        global $wpdb;
        return $wpdb->query( $wpdb->prepare( 'DELETE FROM '.$wpdb -> prefix .'wauc_auction_log WHERE id = %d', $id ) );
        return false;
    }


    /**
     * get auction logs
     * @return bool
     */
    public static function get_log_list( $post_id, $offset = 0, $per_page = 10 ) {
        global $wpdb;
        $data = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM '.$wpdb -> prefix .'wauc_auction_log LEFT JOIN '.$wpdb->prefix.'users ON '.$wpdb->prefix.'users.id = '.$wpdb->prefix.'wauc_auction_log.userid WHERE auction_id = %d ORDER BY date DESC LIMIT '.$offset.','.$per_page, $post_id ) );
        return $data;
    }

    public static function get_participant_list( $product, $offset = 0, $per_page = 10 ) {

        if( is_object( $product ) ) {
            $product_id = $product->ID;
        } elseif ( is_numeric( $product ) ) {
            $product_id = $product;
        }

        global $wpdb;
        $wauc_auction_log = $wpdb->prefix . 'wauc_auction_log';
        $users = $wpdb->prefix . 'users';
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wauc_auction_log} INNER JOIN $users ON $users.ID = $wauc_auction_log.userid WHERE $wauc_auction_log.auction_id = %s ORDER BY date DESC LIMIT $offset, $per_page",
                $product_id
            )
        );
        $user_records = array();
        foreach ( $results as $k => $res ) {
            if( !isset( $user_records[$res->userid] ) ) {
                $user_records[$res->userid] = $res;
            }
        }
        return $user_records;
    }

    /**
     * Check if the product requires token
     * @param $post_id
     * @return bool|mixed
     */
    public static function is_token_required( $post_id ) {
        $fee = get_post_meta( $post_id, 'wauc_auction_deposit', true );

        if( $fee ) {
            return get_post_meta( $post_id, 'wauc_token_id', true );
        }
        return false;
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


}


