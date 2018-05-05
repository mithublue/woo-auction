<?php

add_action( 'wp_ajax_wauc_show_auction_modal', function(){

    ?>
    <style>
        /* The Modal (background) */
        .modal {
            /*display: none; /!* Hidden by default *!/*/
            position: fixed; /* Stay in place */
            z-index: 99999; /* Sit on top */
            padding-top: 100px; /* Location of the box */
            left: 0;
            top: 0;
            width: 100%; /* Full width */
            height: 100%; /* Full height */
            overflow: auto; /* Enable scroll if needed */
            background-color: rgb(0,0,0); /* Fallback color */
            background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
        }

        /* Modal Content */
        .modal-content {
            background-color: #fefefe;
            margin: auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
        }

        /* The Close Button */
        .close {
            color: #aaaaaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: #000;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
    <!-- The Modal -->
    <div id="myModal" class="modal">

        <!-- Modal content -->
        <div class="modal-content">
            <span class="close">x</span>

            <form class="wauc_widget">
                <?php ( new WAUC_Auction_Widget())->form( array() ); ?>
                <input type="submit" class="button button-primary button-large insert_wauc_shortcode" value="Insert" name="insert">
            </form>
            <script>
                (function ($) {
                    $(document).on('click','.insert_wauc_shortcode',function () {
                        $.post(
                            ajaxurl,
                            {
                                action : 'wauc_insert_widget',
                                data : $('.wauc_widget').serialize()
                            },
                            function (data) {
                                tinyMCE.activeEditor.selection.setContent(data);
                                $('.modal-content .close').click();
                            }
                        );
                        return false;
                    });

                }(jQuery))
            </script>

        </div>

    </div>

    <script>
        /*// Get the modal
        var modal = document.getElementById('myModal');

        // Get the button that opens the modal
        var btn = document.getElementById("myBtn");

        // Get the <span> element that closes the modal
        var span = document.getElementsByClassName("close")[0];

        // When the user clicks the button, open the modal
        btn.onclick = function() {
            modal.style.display = "block";
        }

        // When the user clicks on <span> (x), close the modal
        span.onclick = function() {
            modal.style.display = "none";
        }

        // When the user clicks anywhere outside of the modal, close it
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }*/
    </script>
<?php
});

add_action( 'wp_ajax_wauc_insert_widget',function () {

    /*$_POST['data']*/
    if( !is_string( $_POST['data'] ) ) {
        return;
    }

    parse_str( $_POST['data'], $data );

    if( !is_array( $data ) ) return;

    if( isset($data['widget-wauc_auction_widget']) && !empty( $data['widget-wauc_auction_widget'] ) && is_array( $data['widget-wauc_auction_widget'] ) ) {
        $instance = $data['widget-wauc_auction_widget'];

        $ins = array();
        foreach ( $instance  as $i => $each) {
            $ins = array_merge($ins,$each);
        }

        echo (new WAUC_Auction_Widget())->widget(array(),$ins,true);
    }
    exit;
});

class WAUC_Ajax_Action {

    public static function init() {
        add_action( 'wp_ajax_wauc_delete_log', array( 'WAUC_Ajax_Action', 'delete_log' ) );
        add_action( 'wp_ajax_wauc_render_page', array( 'WAUC_Ajax_Action', 'get_log_list' ) );
        add_action( 'wp_ajax_wauc_render_participant_list', array( 'WAUC_Ajax_Action', 'get_participant_list' ) );
        //
        add_action( 'wp_ajax_wauc_select_as_winner', array( 'WAUC_Ajax_Action', 'select_as_winner' ) );
        add_action( 'wp_ajax_wauc_cancel_as_winner', array( 'WAUC_Ajax_Action', 'cancel_as_winner' ) );
        add_action( 'wp_ajax_wauc_skip_bid', array( 'WAUC_Ajax_Action', 'skip_bid' ) );
        add_action( 'wp_ajax_wauc_skip_bidder', array( 'WAUC_Ajax_Action', 'skip_bidder' ) );
    }

    public static function delete_log() { return;
        if( WAUC_Functions::delete_log( $_POST['id'] ) ) {
            echo json_encode(array(
                'result' => 'success',
                'msg' => 'Deleted successfully !'
            ));
        } else {
            echo json_encode(array(
                'result' => 'error',
                'msg' => 'Failed to delete !'
            ));
        }
        exit;
    }

    public static function get_participant_list() {
        if( !isset( $_POST['product_id'])
            || !is_numeric( $_POST['product_id'] )
            || !isset( $_POST['offset'] )
            || !is_numeric( $_POST['offset'] )
            || !isset( $_POST['per_page'] )
            || !is_numeric( $_POST['per_page'] )
        ) {
            echo json_encode( array(
                'result' => 'error',
                'msg' => 'Data could not be loaded !'
            ));
            exit;
        };

        $data = WAUC_Functions::get_participant_list( (int)$_POST['product_id'], (int)$_POST['offset'], (int)$_POST['per_page'] );

        if( !empty( $data ) ) {
            echo json_encode(
                array(
                    'result' => 'success',
                    'msg' => 'Data loaded successfully !',
                    'data' => $data
                )
            );
        } else {
            echo json_encode(
                array(
                    'result' => 'error',
                    'msg' => 'Data could not be loaded !',
                    'data' => ''
                )
            );
        }
        exit;

    }

    public static function get_log_list() {

        if( !isset( $_POST['product_id'])
            || !is_numeric( $_POST['product_id'] )
            || !isset( $_POST['offset'] )
            || !is_numeric( $_POST['offset'] )
            || !isset( $_POST['per_page'] )
            || !is_numeric( $_POST['per_page'] )
        ) {
            echo json_encode( array(
                'result' => 'error',
                'msg' => 'Data could not be loaded !'
            ));
            exit;
        };

        $data = WAUC_Functions::get_log_list( (int)$_POST['product_id'], (int)$_POST['offset'], (int)$_POST['per_page'] );

        if( !empty( $data ) ) {
            echo json_encode(
                array(
                    'result' => 'success',
                    'msg' => 'Data loaded successfully !',
                    'data' => $data
                )
            );
        } else {
            echo json_encode(
                array(
                    'result' => 'error',
                    'msg' => 'Data could not be loaded !',
                    'data' => ''
                )
            );
        }
        exit;

    }

    /**
     * Select the user as winner
     */
    public static function select_as_winner() {
        if( !isset($_POST['user_id'] ) || !is_numeric($_POST['user_id']) ) wp_send_json_error();
        if( !isset($_POST['auction_id'] ) || !is_numeric($_POST['auction_id']) ) wp_send_json_error();

        //insert into db
        $user_id = $_POST['user_id'];
        $auction_id = $_POST['auction_id'];
        if( WAUC_Functions::select_as_winner( $user_id, $auction_id ) ) {

            //send mail to winner
            WAUC_Notification::bid_winning_notificaton( wc_get_product( $auction_id ) ,WAUC_Functions::get_user_email( $user_id ) );

            //change auction to processing
            WAUC_Functions::change_auction_status( 'running', 'processing', $auction_id );
            //
            wp_send_json_success( array(
                'msg' => 'Selected as winner !',
                'report' => $report = WAUC_Functions::generate_report('recent', null)
            ));
        };
        exit;
    }

    public static function cancel_as_winner() {
        if( !isset($_POST['user_id'] ) || !is_numeric($_POST['user_id']) ) wp_send_json_error();
        if( !isset($_POST['auction_id'] ) || !is_numeric($_POST['auction_id']) ) wp_send_json_error();

        //insert into db
        $user_id = $_POST['user_id'];
        $auction_id = $_POST['auction_id'];
        if( WAUC_Functions::cancel_as_winner( $user_id, $auction_id ) ) {

            //change auction to processing
            WAUC_Functions::change_auction_status( 'processing', 'running', $auction_id );
            //
            wp_send_json_success( array(
                'msg' => 'Selected as winner !',
                'report' => $report = WAUC_Functions::generate_report('processing', null)
            ));
        };
        exit;
    }

    /**
     * Skip bid
     */
    public static function skip_bid() {
        if( !isset($_POST['bid_id'] ) || !is_numeric($_POST['bid_id']) ) wp_send_json_error();

        $bid_id = $_POST['bid_id'];
        if( WAUC_Functions::fake_bid( $bid_id ) ) {
            $report = WAUC_Functions::generate_report('recent', null);
            wp_send_json_success(
                    array(
                            'report' => $report
                    )
            );
        };
        exit;
    }

    public static function skip_bidder() {
        if( !isset($_POST['user_id'] ) || !is_numeric($_POST['user_id']) ) wp_send_json_error();
        if( !isset($_POST['auction_id'] ) || !is_numeric($_POST['auction_id']) ) wp_send_json_error();

        $user_id = $_POST['user_id'];
        $auction_id = $_POST['auction_id'];
        if( WAUC_Functions::fake_auction_bidder( $user_id, $auction_id ) ) {
            $report = WAUC_Functions::generate_report('recent', null);
            wp_send_json_success(
                array(
                    'report' => $report
                )
            );
        }
        exit;
    }
}

WAUC_Ajax_Action::init();