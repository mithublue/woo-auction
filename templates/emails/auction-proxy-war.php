<?php
/**
 * Proxy War Email Template (Participant Notification)
 *
 * Neutral notification to participants about paused proxy bidding.
 *
 * @package    Woo_Live_Auctions
 * @subpackage Woo_Live_Auctions/templates/emails
 * @version    1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<h2><?php esc_html_e( '⚡ Bidding Update', 'woo-live-auctions' ); ?></h2>

<p><?php printf( esc_html__( 'Hello %s,', 'woo-live-auctions' ), esc_html( $user->display_name ) ); ?></p>

<p><?php esc_html_e( 'There\'s an update on your auction bid.', 'woo-live-auctions' ); ?></p>

<h3><?php echo esc_html( $product->get_name() ); ?></h3>

<p><?php esc_html_e( 'Your automatic bidding has been temporarily paused due to multiple competing auto-bids.', 'woo-live-auctions' ); ?></p>

<table cellspacing="0" cellpadding="6" style="width: 100%; border: 1px solid #eee;">
	<tr>
		<th style="text-align:left; border: 1px solid #eee;"><?php esc_html_e( 'Current Bid', 'woo-live-auctions' ); ?></th>
		<td style="text-align:left; border: 1px solid #eee;"><?php echo wp_kses_post( wc_price( $product->get_current_bid() ) ); ?></td>
	</tr>
	<tr>
		<th style="text-align:left; border: 1px solid #eee;"><?php esc_html_e( 'Time Remaining', 'woo-live-auctions' ); ?></th>
		<td style="text-align:left; border: 1px solid #eee;"><?php echo esc_html( $product->get_time_remaining_formatted() ); ?></td>
	</tr>
</table>

<p><?php esc_html_e( 'You can continue bidding manually if you\'d like to increase your bid.', 'woo-live-auctions' ); ?></p>

<p style="text-align: center;">
	<a href="<?php echo esc_url( get_permalink( $product->get_id() ) ); ?>" style="background-color: #e74c3c; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 3px; display: inline-block;">
		<?php esc_html_e( 'View Auction & Bid', 'woo-live-auctions' ); ?>
	</a>
</p>

<p><?php esc_html_e( 'Thank you for participating!', 'woo-live-auctions' ); ?></p>
