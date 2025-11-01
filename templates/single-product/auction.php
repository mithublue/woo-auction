<?php
/**
 * Auction Bidding Box Template
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/auction.php
 *
 * @package    Woo_Live_Auctions
 * @subpackage Woo_Live_Auctions/templates
 * @version    1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $product;

if ( ! $product || 'auction' !== $product->get_type() ) {
	return;
}

$user_id = get_current_user_id();
$db = woo_auction()->get_db();
$user_proxy = $user_id ? $db->get_proxy_bid( $product->get_id(), $user_id ) : null;
$is_user_high_bidder = $user_id ? $product->is_user_high_bidder( $user_id ) : false;
$proxy_war_occurred = get_post_meta( $product->get_id(), '_auction_proxy_war_occurred', true );
$is_admin = current_user_can( 'manage_options' );

?>

<div class="woo-auction-bidding-box" data-auction-id="<?php echo esc_attr( $product->get_id() ); ?>">
	
	<?php if ( $product->is_auction_ended() ) : ?>
		
		<!-- Auction Ended State -->
		<div class="woo-auction-ended-notice">
			<h3><?php esc_html_e( 'Auction Ended', 'woo-live-auctions' ); ?></h3>
			
			<?php if ( $product->has_winner() ) : ?>
				<?php if ( $product->get_winner() === $user_id ) : ?>
					<p class="winner-notice">
						<span class="dashicons dashicons-yes"></span>
						<?php esc_html_e( 'Congratulations! You won this auction!', 'woo-live-auctions' ); ?>
					</p>
					<p class="winning-bid">
						<?php
						printf(
							/* translators: %s: winning bid amount */
							esc_html__( 'Winning bid: %s', 'woo-live-auctions' ),
							'<strong>' . wp_kses_post( wc_price( $product->get_current_bid() ) ) . '</strong>'
						);
						?>
					</p>
				<?php else : ?>
					<p><?php esc_html_e( 'This auction has ended.', 'woo-live-auctions' ); ?></p>
					<p class="final-bid">
						<?php
						printf(
							/* translators: %s: final bid amount */
							esc_html__( 'Final bid: %s', 'woo-live-auctions' ),
							'<strong>' . wp_kses_post( wc_price( $product->get_current_bid() ) ) . '</strong>'
						);
						?>
					</p>
				<?php endif; ?>
			<?php else : ?>
				<p><?php esc_html_e( 'This auction ended with no winner (reserve not met or no bids).', 'woo-live-auctions' ); ?></p>
			<?php endif; ?>
		</div>

	<?php elseif ( ! $product->has_auction_started() ) : ?>
		
		<!-- Auction Not Started State -->
		<div class="woo-auction-not-started">
			<h3><?php esc_html_e( 'Auction Not Started', 'woo-live-auctions' ); ?></h3>
			<p>
				<?php
				printf(
					/* translators: %s: start date */
					esc_html__( 'This auction will start on %s', 'woo-live-auctions' ),
					'<strong>' . esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $product->get_auction_start_date() ) ) ) . '</strong>'
				);
				?>
			</p>
			<p class="starting-bid">
				<?php
				printf(
					/* translators: %s: starting bid amount */
					esc_html__( 'Starting bid: %s', 'woo-live-auctions' ),
					'<strong>' . wp_kses_post( wc_price( $product->get_auction_start_price() ) ) . '</strong>'
				);
				?>
			</p>
		</div>

	<?php else : ?>
		
		<!-- Live Auction State -->
		<div class="woo-auction-live-box">
			
			<!-- Current Bid Display -->
			<div class="woo-auction-current-bid">
				<div class="current-bid-label"><?php esc_html_e( 'Current Bid', 'woo-live-auctions' ); ?></div>
				<div class="current-bid-amount" data-current-bid="<?php echo esc_attr( $product->get_current_bid() ); ?>">
					<?php echo wp_kses_post( wc_price( $product->get_current_bid() ) ); ?>
				</div>
				<div class="bid-count">
					<?php
					printf(
						/* translators: %d: bid count */
						esc_html( _n( '%d bid', '%d bids', $product->get_bid_count(), 'woo-live-auctions' ) ),
						$product->get_bid_count()
					);
					?>
				</div>
			</div>

			<!-- Countdown Timer -->
			<div class="woo-auction-countdown" data-end-time="<?php echo esc_attr( strtotime( $product->get_auction_end_date() ) ); ?>">
				<div class="countdown-label"><?php esc_html_e( 'Time Remaining', 'woo-live-auctions' ); ?></div>
				<div class="countdown-timer"><?php echo esc_html( $product->get_time_remaining_formatted() ); ?></div>
			</div>

			<!-- User Status -->
			<?php if ( $is_user_high_bidder ) : ?>
				<div class="woo-auction-user-status winning">
					<span class="dashicons dashicons-yes"></span>
					<?php esc_html_e( 'You are the highest bidder!', 'woo-live-auctions' ); ?>
				</div>
			<?php elseif ( $user_id && $product->get_bid_count() > 0 ) : ?>
				<?php
				$user_bids = $db->get_user_bids( $product->get_id(), $user_id );
				if ( ! empty( $user_bids ) ) :
				?>
					<div class="woo-auction-user-status outbid">
						<span class="dashicons dashicons-warning"></span>
						<?php esc_html_e( 'You have been outbid!', 'woo-live-auctions' ); ?>
					</div>
				<?php endif; ?>
			<?php endif; ?>

			<!-- Proxy Bid Status -->
			<?php if ( $user_proxy && $user_proxy->is_active ) : ?>
				<div class="woo-auction-proxy-status active">
					<span class="dashicons dashicons-update"></span>
					<?php
					printf(
						/* translators: %s: max bid amount */
						esc_html__( 'Your auto-bid is active (max: %s)', 'woo-live-auctions' ),
						wp_kses_post( wc_price( $user_proxy->max_bid ) )
					);
					?>
				</div>
			<?php endif; ?>

			<!-- Manual Bid Form -->
			<div class="woo-auction-bid-form">
				<h4><?php esc_html_e( 'Place Your Bid', 'woo-live-auctions' ); ?></h4>
				
				<?php if ( ! $user_id && 'yes' !== get_option( 'woo_auction_allow_guest_bidding', 'no' ) ) : ?>
					<p class="woo-auction-login-required">
						<?php
						printf(
							/* translators: %s: login URL */
							wp_kses_post( __( 'You must be <a href="%s">logged in</a> to place a bid.', 'woo-live-auctions' ) ),
							esc_url( wc_get_page_permalink( 'myaccount' ) )
						);
						?>
					</p>
				<?php else : ?>
					<div class="bid-input-wrapper">
						<label for="bid-amount"><?php esc_html_e( 'Your Bid', 'woo-live-auctions' ); ?></label>
						<div class="bid-input-group">
							<span class="currency-symbol"><?php echo esc_html( get_woocommerce_currency_symbol() ); ?></span>
							<input 
								type="number" 
								id="bid-amount" 
								name="bid_amount" 
								class="woo-auction-bid-input" 
								min="<?php echo esc_attr( $product->get_min_next_bid() ); ?>" 
								step="<?php echo esc_attr( $product->get_bid_increment() ); ?>" 
								placeholder="<?php echo esc_attr( $product->get_min_next_bid() ); ?>"
							>
						</div>
						<p class="min-bid-notice">
							<?php
							printf(
								/* translators: %s: minimum bid amount */
								esc_html__( 'Minimum bid: %s', 'woo-live-auctions' ),
								'<strong>' . wp_kses_post( wc_price( $product->get_min_next_bid() ) ) . '</strong>'
							);
							?>
						</p>
					</div>

					<button type="button" class="button alt woo-auction-bid-button" data-auction-id="<?php echo esc_attr( $product->get_id() ); ?>">
						<?php esc_html_e( 'Place Bid', 'woo-live-auctions' ); ?>
					</button>

					<div class="woo-auction-messages"></div>
				<?php endif; ?>
			</div>

			<!-- Proxy Bid Form -->
			<?php 
			$show_proxy_form = 'yes' === get_option( 'woo_auction_enable_proxy_bidding', 'yes' ) && $user_id;
			if ( $proxy_war_occurred ) {
				$show_proxy_form = $is_admin; // Only show to admins after proxy war
			}
			if ( $show_proxy_form ) : 
			?>
				<div class="woo-auction-proxy-form<?php echo $proxy_war_occurred ? ' proxy-war-disabled' : ''; ?>">
					<h4><?php esc_html_e( 'Set Maximum Bid (Auto-Bid)', 'woo-live-auctions' ); ?></h4>
					
					<?php if ( $proxy_war_occurred ) : ?>
						<div class="woo-auction-proxy-war-notice">
							<p><?php esc_html_e( 'Auto-bidding has been paused due to multiple competing auto-bids (Free version limitation).', 'woo-live-auctions' ); ?></p>
							<p><?php esc_html_e( 'Upgrade to Pro to enable unlimited auto-bidding wars.', 'woo-live-auctions' ); ?></p>
						</div>
					<?php else : ?>
						<p class="proxy-description">
							<?php esc_html_e( 'Set your maximum bid and we\'ll automatically bid for you up to that amount.', 'woo-live-auctions' ); ?>
							<span class="proxy-help" title="<?php esc_attr_e( 'Limited in Free version: Auto-bidding pauses when another user sets a max bid.', 'woo-live-auctions' ); ?>">
								<span class="dashicons dashicons-info"></span>
							</span>
						</p>
					<?php endif; ?>
					
					<div class="proxy-input-wrapper">
						<label for="max-bid"><?php esc_html_e( 'Maximum Bid', 'woo-live-auctions' ); ?></label>
						<div class="proxy-input-group">
							<span class="currency-symbol"><?php echo esc_html( get_woocommerce_currency_symbol() ); ?></span>
							<input 
								type="number" 
								id="max-bid" 
								name="max_bid" 
								class="woo-auction-proxy-input" 
								min="<?php echo esc_attr( $product->get_min_next_bid() ); ?>" 
								step="<?php echo esc_attr( $product->get_bid_increment() ); ?>" 
								placeholder="<?php echo esc_attr( $product->get_min_next_bid() ); ?>"
								<?php echo $proxy_war_occurred ? 'disabled' : ''; ?>
							>
						</div>
					</div>

					<button type="button" class="button woo-auction-proxy-button<?php echo $proxy_war_occurred ? ' disabled' : ''; ?>" data-auction-id="<?php echo esc_attr( $product->get_id() ); ?>" <?php echo $proxy_war_occurred ? 'disabled' : ''; ?>>
						<?php echo $proxy_war_occurred ? esc_html__( 'Auto-Bid Paused', 'woo-live-auctions' ) : esc_html__( 'Set Auto-Bid', 'woo-live-auctions' ); ?>
					</button>
				</div>
			<?php endif; ?>

			<!-- Buy Now Button -->
			<?php if ( $product->is_buy_now_available() ) : ?>
				<div class="woo-auction-buy-now">
					<p class="buy-now-label"><?php esc_html_e( 'Or buy it now for:', 'woo-live-auctions' ); ?></p>
					<p class="buy-now-price"><?php echo wp_kses_post( wc_price( $product->get_buy_now_price() ) ); ?></p>
					<button type="button" class="button woo-auction-buy-now-button" data-auction-id="<?php echo esc_attr( $product->get_id() ); ?>">
						<?php esc_html_e( 'Buy Now', 'woo-live-auctions' ); ?>
					</button>
				</div>
			<?php endif; ?>

			<!-- Watchlist Button -->
			<?php if ( 'yes' === get_option( 'woo_auction_enable_watchlist', 'yes' ) && $user_id ) : ?>
				<?php
				$is_watching = $db->is_watching( $product->get_id(), $user_id );
				?>
				<div class="woo-auction-watchlist">
					<button type="button" class="button woo-auction-watchlist-button <?php echo $is_watching ? 'watching' : ''; ?>" data-auction-id="<?php echo esc_attr( $product->get_id() ); ?>">
						<span class="dashicons dashicons-<?php echo $is_watching ? 'star-filled' : 'star-empty'; ?>"></span>
						<?php echo $is_watching ? esc_html__( 'Watching', 'woo-live-auctions' ) : esc_html__( 'Watch Auction', 'woo-live-auctions' ); ?>
					</button>
				</div>
			<?php endif; ?>

		</div>

	<?php endif; ?>

</div>
