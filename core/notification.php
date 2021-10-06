<?php
namespace wauc\core;

class Notification{

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

    }

	public static function send_auction_request_approval_notification( $product, $user ) {
		if( apply_filters( 'wauc_email_auction_request_approval', WAUC_Functions::get_settings( 'notification', 'email_auction_joining_approval' ) ) != 'yes' ) return;
		$subject = apply_filters( 'wauc_email_auction_request_approval-subject', __( 'Request Approved !', 'wauc' ) );
		$text = 'Request for auction for product : '.$product->post_title.' is approved ! You can start bidding here : <br>'.get_permalink( $product->ID );
		$message = apply_filters( 'wauc_email_auction_request_approval-body', __( $text, 'wauc' ) );

		$headers = "From: " . strip_tags(get_bloginfo('admin_email')) . "\r\n";
		$headers .= "Reply-To: ". $user . "\r\n";
		$headers .= "MIME-Version: 1.0\r\n";
		$headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";

		WAUC_Functions::log(
			array( 'auction_request_approval','to:'.$user, 'subject: '. $subject,'msg:'.$message)
		);
		return wp_mail( $user, $subject, $message, $headers );
	}
	/**
	 * Send notification to others
	 * @param $item
	 * @param $bid_by
	 * @param null $bidding_time
	 */
	public static function send_bid_notification_to_participants( $item, $bid_by, $bidding_time = null ) {
		if( !$item ) return;
		if( !$bidding_time ) $bidding_time = time();
		if( apply_filters( 'wauc_email_bid_notification_to_participants', WAUC_Functions::get_settings( 'notification', 'email_bidders_on_someones_bid' ) ) != 'yes' ) return;

		$subject = apply_filters( 'wauc_email_bid_notification_to_participants-subject', 'Someone has bid on a product you are subscribed !' );

		ob_start();
		?>
		<?php echo get_userdata( $bid_by )->user_nicename; ?> has placed bid for the product : <?php echo $item->get_title(); ?> on : <?php echo date('d-m-Y H:i:s', $bidding_time ); ?>.
		Place a new bid to win. Product URL : <?php echo get_permalink( $item->get_id() ); ?>
		<?php
		$msg = ob_get_clean();
		$msg = apply_filters( 'wauc_email_bid_notification_to_participants-body', $msg );

		$participants = WAUC_Functions::get_participant_list( $item->get_id(), null, null );

		if( !empty( $participants ) ) {

			foreach ( $participants as $k => $participant ) {
				//send mail to other participants
				if( $participant['user_id'] !== get_current_user_id() ) {

					$headers = "From: " . strip_tags(get_bloginfo('admin_email')) . "\r\n";
					$headers .= "Reply-To: ". strip_tags( WAUC_Functions::get_user_email($participant['user_id'] ) ) . "\r\n";
					$headers .= "MIME-Version: 1.0\r\n";
					$headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";

					WAUC_Functions::log(
						array( 'BID TO OTHERS','to:'.WAUC_Functions::get_user_email( $participant['user_id'] ), 'subject: '. $subject,'msg:'.$msg)
					);
					wp_mail( WAUC_Functions::get_user_email( $participant['user_id'] ), $subject, $msg, $headers );
				}
			}
		}
	}

	/**
	 * Send notification to bidder
	 * @param $item
	 * @param $bid_by
	 * @param null $bidding_time
	 */
	public static function send_bid_notification_to_bidder( $item, $bid_by, $bidding_time = null  ) {
		if( !$item ) return;
		if( !$bidding_time ) $bidding_time = time();
		if( apply_filters( 'wauc_email_bid_notification_to_bidder', WAUC_Functions::get_settings( 'notification', 'email_bidder_on_bid' ) ) != 'yes' ) return;

		$subject = apply_filters( 'wauc_email_bid_notification_to_bidder-subject', 'You successfully placed bid' );
		ob_start();
		?>
		You have successfully placed bid for the product : <?php echo $item->get_title(); ?> on : <?php echo date('d-m-Y H:i:s', $bidding_time ); ?>
		Product URL: <?php echo get_permalink( $item->get_id() ); ?>
		<?php
		$msg = ob_get_clean();
		$msg = apply_filters( 'wauc_email_bid_notification_to_bidder-body', $msg );

		$headers = "From: " . strip_tags(get_bloginfo('admin_email')) . "\r\n";
		$headers .= "Reply-To: ". get_userdata( $bid_by )->user_email . "\r\n";
		$headers .= "MIME-Version: 1.0\r\n";
		$headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";

		WAUC_Functions::log(
			array( 'to:'.get_userdata( $bid_by )->user_email,'subject: '. $subject,'msg:'.$msg)
		);
		wp_mail( get_userdata( $bid_by )->user_email, $subject, $msg, $headers );
	}


	/**
	 * Notification on bid
	 * end
	 * @param $item
	 */
	public static function bid_end_notification( $items ) {
		if( apply_filters( 'wauc_email_bid_end_notification', WAUC_Functions::get_settings( 'notification', 'email_bidders_on_auction_end' ) ) != 'yes' ) return;
		if( empty( $items ) ) return;

		foreach ( $items as $k => $item ) {
			foreach ( $item as $k => $data ) {
				$to = $data['user_email'];
				$subject = apply_filters( 'wauc_email_bid_end_notification-subject', 'Auction on product '.$data['auction_name'].' has ended');

				$headers = "From: " . strip_tags(get_bloginfo('admin_email')) . "\r\n";
				$headers .= "Reply-To: ". $data['user_email'] . "\r\n";
				$headers .= "MIME-Version: 1.0\r\n";
				$headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";

				ob_start(); ?>
				Auction has completed for the product <?php echo $data['auction_name']; ?>. You can visit the URL from
				here. URL : <?php echo $data['url']; ?>
				<?php
				$msg = apply_filters( 'wauc_email_bid_end_notification-body', ob_get_clean() );

				WAUC_Functions::log(
					array( 'to:'.$to,'subject: '. $subject,'msg:'.$msg)
				);
				wp_mail( $to, $subject, $msg, $headers );
			}

		}
	}

	/**
	 * Notification to bidder
	 * on winning
	 * @param $item
	 * @param $bidder
	 */
	public static function bid_winning_notificaton( $item, $bidder ) {
		if( apply_filters( 'wauc_email_bidder_on_winning_auction', WAUC_Functions::get_settings( 'notification', 'email_bidder_on_winning_auction' ) ) != 'yes' ) return;

		$to = is_numeric( $bidder ) ? WAUC_Functions::get_user_email( $bidder ) : $bidder;
		$subject = apply_filters( 'wauc_email_bidder_on_winning_auction-subject', __( 'You have been selected as winner', 'wauc' ) );
		ob_start();
		?>
		This is to remind you about the auction you have won. You are asked to claim your product from
		here : <?php echo get_permalink($item->get_id()); ?>
		<?php
		$message = ob_get_clean();
		$message = apply_filters( 'wauc_email_bidder_on_winning_auction-body', __( $message, 'wauc' ) );

		/**
		 * header
		 */
		$headers = "From: " . strip_tags(get_bloginfo( 'admin_email' ) ) . "\r\n";
		$headers .= "Reply-To: ". strip_tags($to) . "\r\n";
		$headers .= "MIME-Version: 1.0\r\n";
		$headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";

		WAUC_Functions::log(
			array( 'to:'.$to,'subject: '. $subject,'msg:'.$message)
		);
		return wp_mail( $to, $subject, $message, $headers );
	}

	/**
	 * Notify the users
	 * who has been selected by admin
	 * as winners
	 * @param $results
	 */
	public static function notify_awaiting_winners( $results ) {
		if( apply_filters( 'wauc_email_bidder_on_winning_auction', WAUC_Functions::get_settings( 'notification', 'email_bidder_on_winning_auction' ) ) != 'yes' ) return;

		foreach ( $results as $k => $result ) {
			$to = WAUC_Functions::get_user_email( $result->userid );
			$subject = apply_filters( 'wauc_email_bidder_on_winning_auction-subject', __( 'You have been selected as winner', 'wauc' ) );
			ob_start();
			?>
			This is to remind you about the auction you have won. You are asked to claim your product from
			here : <?php echo get_permalink($result->auction_id); ?>
			<?php
			$message = ob_get_clean();
			$message = apply_filters( 'wauc_email_bidder_on_winning_auction-body', __( $message, 'wauc' ) );

			/**
			 * header
			 */
			$headers = "From: " . strip_tags(get_bloginfo( 'admin_email' ) ) . "\r\n";
			$headers .= "Reply-To: ". strip_tags($to) . "\r\n";
			$headers .= "MIME-Version: 1.0\r\n";
			$headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";

			WAUC_Functions::log(
				array( 'to:'.$to,'subject: '. $subject,'msg:'.$message)
			);
			wp_mail( $to, $subject, $message, $headers );
		}
	}

	public static function email_admin_on_newly_end_auctions() {
		if( apply_filters( 'wauc_email_admin_on_end_auction', WAUC_Functions::get_settings( 'notification', 'email_admin_on_end_auction' ) ) != 'yes' ) return;

		$to = get_bloginfo( 'admin_email' );
		$subject = apply_filters( 'wauc_email_admin_on_end_auction-subject', 'Some auctions\' time is over');

		$headers = "From: " . strip_tags(get_bloginfo('admin_email')) . "\r\n";
		$headers .= "Reply-To: ". strip_tags(get_bloginfo('admin_email')) . "\r\n";
		$headers .= "MIME-Version: 1.0\r\n";
		$headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";

		ob_start(); ?>
		Some auction has run out of their time and completed. Please , check them.
		<?php
		$msg = apply_filters( 'wauc_email_admin_on_end_auction-body', ob_get_clean() );

		WAUC_Functions::log(
			array( 'to:'.$to,'subject: '. $subject,'msg:'.$msg)
		);
		wp_mail( $to, $subject, $msg, $headers );
	}
}