<?php
namespace wauc\core;

class Ajax_Actions{

    /**
     * Instance
     *
     * @since 1.0.0
     *
     * @access private
     * @static
     */
    private static $_instance = null;

    /**
     * Instance
     *
     * Ensures only one instance of the class is loaded or can be loaded.
     *
     * @since 1.0.0
     *
     * @access public
     * @static
     *
     * @return ${ClassName} An instance of the class.
     */
    public static function instance() {

        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;

    }

    public function __construct() {
	    add_action( 'wp_ajax_wauc_delete_log', array( 'WAUC_Ajax_Action', 'delete_log' ) );
	    add_action( 'wp_ajax_wauc_render_page', array( 'WAUC_Ajax_Action', 'get_log_list' ) );
	    add_action( 'wp_ajax_wauc_render_participant_list', array( 'WAUC_Ajax_Action', 'get_participant_list' ) );
	    //
	    add_action( 'wp_ajax_wauc_select_as_winner', array( 'WAUC_Ajax_Action', 'select_as_winner' ) );
	    add_action( 'wp_ajax_wauc_cancel_as_winner', array( 'WAUC_Ajax_Action', 'cancel_as_winner' ) );
	    add_action( 'wp_ajax_wauc_skip_bid', array( 'WAUC_Ajax_Action', 'skip_bid' ) );
	    add_action( 'wp_ajax_wauc_skip_bidder', array( 'WAUC_Ajax_Action', 'skip_bidder' ) );
    }

	public static function delete_log() {
		if( Functions::delete_log( $_POST['id'] ) ) {
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

		$data = Functions::get_participant_list( (int)$_POST['product_id'], (int)$_POST['offset'], (int)$_POST['per_page'] );

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

		$data = Functions::get_log_list( (int)$_POST['product_id'], (int)$_POST['offset'], (int)$_POST['per_page'] );

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
		if( Functions::select_as_winner( $user_id, $auction_id ) ) {

			//send mail to winner
			Notification::bid_winning_notificaton( wc_get_product( $auction_id ) ,Functions::get_user_email( $user_id ) );

			//change auction to processing
			Functions::change_auction_status( 'running', 'processing', $auction_id );
			//
			wp_send_json_success( array(
				'msg' => 'Selected as winner !',
				'report' => $report = Functions::generate_report('recent', null)
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
		if( Functions::cancel_as_winner( $user_id, $auction_id ) ) {

			//change auction to processing
			Functions::change_auction_status( 'processing', 'running', $auction_id );
			//
			wp_send_json_success( array(
				'msg' => 'Selected as winner !',
				'report' => $report = Functions::generate_report('processing', null)
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
		if( Functions::fake_bid( $bid_id ) ) {
			$report = Functions::generate_report('recent', null);
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
		if( Functions::fake_auction_bidder( $user_id, $auction_id ) ) {
			$report = Functions::generate_report('recent', null);
			wp_send_json_success(
				array(
					'report' => $report
				)
			);
		}
		exit;
	}
}