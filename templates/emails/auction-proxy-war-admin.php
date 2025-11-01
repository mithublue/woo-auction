<?php
/**
 * Proxy War Admin Notification Email Template
 *
 * Notifies admin about proxy war limitation and upgrade opportunity.
 *
 * @package    Woo_Live_Auctions
 * @subpackage Woo_Live_Auctions/templates/emails
 * @version    1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<h2><?php esc_html_e( '🚀 Proxy War Opportunity - Upgrade Available!', 'woo-live-auctions' ); ?></h2>

<p><?php esc_html_e( 'Hello Admin,', 'woo-live-auctions' ); ?></p>

<p><?php esc_html_e( 'A proxy bidding war has been triggered on your site!', 'woo-live-auctions' ); ?></p>

<h3><?php echo esc_html( $product->get_name() ); ?></h3>

<p><?php esc_html_e( 'Multiple users have set competing auto-bids, and the free version limitation has paused their automatic bidding. This creates a great opportunity to promote your Pro upgrade!', 'woo-live-auctions' ); ?></p>

<table cellspacing="0" cellpadding="6" style="width: 100%; border: 1px solid #eee;">
	<tr>
		<th style="text-align:left; border: 1px solid #eee;"><?php esc_html_e( 'Current Bid', 'woo-live-auctions' ); ?></th>
		<td style="text-align:left; border: 1px solid #eee;"><?php echo wp_kses_post( wc_price( $product->get_current_bid() ) ); ?></td>
	</tr>
	<tr>
		<th style="text-align:left; border: 1px solid #eee;"><?php esc_html_e( 'Active Proxy Bids', 'woo-live-auctions' ); ?></th>
		<td style="text-align:left; border: 1px solid #eee;"><?php echo esc_html( count( $active_proxies ) ); ?> <?php esc_html_e( 'users', 'woo-live-auctions' ); ?></td>
	</tr>
	<tr>
		<th style="text-align:left; border: 1px solid #eee;"><?php esc_html_e( 'Time Remaining', 'woo-live-auctions' ); ?></th>
		<td style="text-align:left; border: 1px solid #eee;"><?php echo esc_html( $product->get_time_remaining_formatted() ); ?></td>
	</tr>
</table>

<p style="background-color: #d4edda; padding: 15px; border-left: 4px solid #28a745;">
	<strong><?php esc_html_e( '💡 Monetization Opportunity!', 'woo-live-auctions' ); ?></strong><br>
	<?php esc_html_e( 'Users are engaging heavily with proxy bidding. Upgrade to Pro to remove limitations and increase revenue from high-value auctions.', 'woo-live-auctions' ); ?>
</p>

<p><?php esc_html_e( 'Pro features include:', 'woo-live-auctions' ); ?></p>
<ul>
	<li><?php esc_html_e( 'Unlimited proxy bidding wars', 'woo-live-auctions' ); ?></li>
	<li><?php esc_html_e( 'Advanced auction analytics', 'woo-live-auctions' ); ?></li>
	<li><?php esc_html_e( 'Bulk auction creation tools', 'woo-live-auctions' ); ?></li>
	<li><?php esc_html_e( 'Priority support', 'woo-live-auctions' ); ?></li>
</ul>

<p style="text-align: center;">
	<a href="<?php echo esc_url( get_permalink( $product->get_id() ) ); ?>" style="background-color: #007cba; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 3px; display: inline-block; margin-right: 10px;">
		<?php esc_html_e( 'View Auction', 'woo-live-auctions' ); ?>
	</a>
	<a href="https://example.com/woo-live-auctions-pro" style="background-color: #28a745; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 3px; display: inline-block;">
		<?php esc_html_e( 'Learn About Pro', 'woo-live-auctions' ); ?>
	</a>
</p>

<p><?php esc_html_e( 'Keep up the great work with your auctions!', 'woo-live-auctions' ); ?></p>
