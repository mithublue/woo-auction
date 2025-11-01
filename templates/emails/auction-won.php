<?php
/**
 * Won Auction Email Template
 *
 * @package    Woo_Live_Auctions
 * @subpackage Woo_Live_Auctions/templates/emails
 * @version    1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<h2><?php esc_html_e( 'Congratulations! You won the auction!', 'woo-live-auctions' ); ?></h2>

<p><?php printf( esc_html__( 'Hello %s,', 'woo-live-auctions' ), esc_html( $user->display_name ) ); ?></p>

<p><?php esc_html_e( 'Great news! You have won the following auction:', 'woo-live-auctions' ); ?></p>

<h3><?php echo esc_html( $product->get_name() ); ?></h3>

<table cellspacing="0" cellpadding="6" style="width: 100%; border: 1px solid #eee;">
	<tr>
		<th style="text-align:left; border: 1px solid #eee;"><?php esc_html_e( 'Winning Bid', 'woo-live-auctions' ); ?></th>
		<td style="text-align:left; border: 1px solid #eee;"><?php echo wp_kses_post( wc_price( $product->get_current_bid() ) ); ?></td>
	</tr>
	<tr>
		<th style="text-align:left; border: 1px solid #eee;"><?php esc_html_e( 'Auction Ended', 'woo-live-auctions' ); ?></th>
		<td style="text-align:left; border: 1px solid #eee;"><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $product->get_auction_end_date() ) ) ); ?></td>
	</tr>
</table>

<p><?php esc_html_e( 'Please complete your purchase to claim your item.', 'woo-live-auctions' ); ?></p>

<p style="text-align: center;">
	<a href="<?php echo esc_url( get_permalink( $product->get_id() ) ); ?>" style="background-color: #2ecc71; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 3px; display: inline-block;">
		<?php esc_html_e( 'Complete Purchase', 'woo-live-auctions' ); ?>
	</a>
</p>
