/**
 * Woo Live Auctions - Public JavaScript
 *
 * Handles real-time bidding, AJAX updates, and countdown timers.
 *
 * @package    Woo_Live_Auctions
 * @subpackage Woo_Live_Auctions/public/assets/js
 * @since      1.0.0
 */

(function($) {
	'use strict';

	/**
	 * Auction Manager Class
	 */
	var WooAuctionManager = {
		
		/**
		 * Initialize
		 */
		init: function() {
			this.bindEvents();
			this.startCountdowns();
			this.startAutoRefresh();
		},

		/**
		 * Bind event handlers
		 */
		bindEvents: function() {
			// Place bid button
			$(document).on('click', '.woo-auction-bid-button', this.placeBid.bind(this));

			// Set proxy bid button
			$(document).on('click', '.woo-auction-proxy-button', this.setProxyBid.bind(this));

			// Buy now button
			$(document).on('click', '.woo-auction-buy-now-button', this.buyNow.bind(this));

			// Watchlist button
			$(document).on('click', '.woo-auction-watchlist-button', this.toggleWatchlist.bind(this));

			// Enter key in bid input
			$(document).on('keypress', '.woo-auction-bid-input, .woo-auction-proxy-input', function(e) {
				if (e.which === 13) {
					e.preventDefault();
					if ($(this).hasClass('woo-auction-bid-input')) {
						$('.woo-auction-bid-button').trigger('click');
					} else {
						$('.woo-auction-proxy-button').trigger('click');
					}
				}
			});
		},

		/**
		 * Place a bid
		 */
		placeBid: function(e) {
			e.preventDefault();

			var $button = $(e.currentTarget);
			var auctionId = $button.data('auction-id');
			var $input = $('#bid-amount');
			var bidAmount = parseFloat($input.val());
			var $messages = $('.woo-auction-messages');

			// Validate
			if (!wooAuction.isLoggedIn) {
				this.showMessage($messages, wooAuction.i18n.loginRequired, 'error');
				return;
			}

			if (!bidAmount || bidAmount <= 0) {
				this.showMessage($messages, wooAuction.i18n.invalidAmount, 'error');
				return;
			}

			// Disable button
			$button.prop('disabled', true).text(wooAuction.i18n.bidding);

			// Send AJAX request
			$.ajax({
				url: wooAuction.ajaxUrl,
				type: 'POST',
				data: {
					action: 'woo_auction_place_bid',
					nonce: wooAuction.bidNonce,
					auction_id: auctionId,
					bid_amount: bidAmount
				},
				success: function(response) {
					if (response.success) {
						this.showMessage($messages, response.data.message, 'success');
						$input.val('');
						this.updateAuctionData(response.data.auction_data);
					} else {
						this.showMessage($messages, response.data.message, 'error');
					}
				}.bind(this),
				error: function() {
					this.showMessage($messages, wooAuction.i18n.error, 'error');
				}.bind(this),
				complete: function() {
					$button.prop('disabled', false).text(wooAuction.i18n.placeBid);
				}
			});
		},

		/**
		 * Set proxy bid
		 */
		setProxyBid: function(e) {
			e.preventDefault();

			var $button = $(e.currentTarget);
			var auctionId = $button.data('auction-id');
			var $input = $('#max-bid');
			var maxBid = parseFloat($input.val());
			var $messages = $('.woo-auction-messages');

			// Validate
			if (!maxBid || maxBid <= 0) {
				this.showMessage($messages, wooAuction.i18n.invalidAmount, 'error');
				return;
			}

			// Disable button
			$button.prop('disabled', true);

			// Send AJAX request
			$.ajax({
				url: wooAuction.ajaxUrl,
				type: 'POST',
				data: {
					action: 'woo_auction_set_proxy_bid',
					nonce: wooAuction.bidNonce,
					auction_id: auctionId,
					max_bid: maxBid
				},
				success: function(response) {
					if (response.success) {
						this.showMessage($messages, response.data.message, 'success');
						$input.val('');
						this.updateAuctionData(response.data.auction_data);
					} else {
						this.showMessage($messages, response.data.message, 'error');
					}
				}.bind(this),
				error: function() {
					this.showMessage($messages, wooAuction.i18n.error, 'error');
				}.bind(this),
				complete: function() {
					$button.prop('disabled', false);
				}
			});
		},

		/**
		 * Buy now
		 */
		buyNow: function(e) {
			e.preventDefault();

			if (!confirm(wooAuction.i18n.confirmBuyNow || 'Are you sure you want to buy this item now?')) {
				return;
			}

			var $button = $(e.currentTarget);
			var auctionId = $button.data('auction-id');

			// Disable button
			$button.prop('disabled', true);

			// Send AJAX request
			$.ajax({
				url: wooAuction.ajaxUrl,
				type: 'POST',
				data: {
					action: 'woo_auction_buy_now',
					nonce: wooAuction.bidNonce,
					auction_id: auctionId
				},
				success: function(response) {
					if (response.success && response.data.redirect_url) {
						window.location.href = response.data.redirect_url;
					} else if (response.data.message) {
						alert(response.data.message);
					}
				},
				error: function() {
					alert(wooAuction.i18n.error);
				},
				complete: function() {
					$button.prop('disabled', false);
				}
			});
		},

		/**
		 * Toggle watchlist
		 */
		toggleWatchlist: function(e) {
			e.preventDefault();

			var $button = $(e.currentTarget);
			var auctionId = $button.data('auction-id');
			var isWatching = $button.hasClass('watching');
			var action = isWatching ? 'woo_auction_remove_from_watchlist' : 'woo_auction_add_to_watchlist';

			// Send AJAX request
			$.ajax({
				url: wooAuction.ajaxUrl,
				type: 'POST',
				data: {
					action: action,
					nonce: wooAuction.watchlistNonce,
					auction_id: auctionId
				},
				success: function(response) {
					if (response.success) {
						$button.toggleClass('watching');
						$button.find('.dashicons').toggleClass('dashicons-star-empty dashicons-star-filled');
						$button.text(isWatching ? wooAuction.i18n.watchAuction : wooAuction.i18n.watching);
					}
				}
			});
		},

		/**
		 * Show message
		 */
		showMessage: function($container, message, type) {
			$container.html('<div class="woo-auction-message ' + type + '">' + message + '</div>');
			setTimeout(function() {
				$container.find('.woo-auction-message').fadeOut(function() {
					$(this).remove();
				});
			}, 5000);
		},

		/**
		 * Update auction data
		 */
		updateAuctionData: function(data) {
			if (!data) return;

			// Update current bid
			$('.current-bid-amount').html(data.current_bid_formatted).data('current-bid', data.current_bid);

			// Update bid count
			$('.bid-count').text(data.bid_count + (data.bid_count === 1 ? ' bid' : ' bids'));

			// Update minimum bid
			$('.woo-auction-bid-input').attr('min', data.min_next_bid);
			$('.min-bid-notice strong').html(data.min_next_bid_formatted);

			// Update user status
			if (data.is_user_high_bidder) {
				$('.woo-auction-user-status').removeClass('outbid').addClass('winning')
					.html('<span class="dashicons dashicons-yes"></span> ' + wooAuction.i18n.youAreWinning);
			} else if (data.user_has_bids) {
				$('.woo-auction-user-status').removeClass('winning').addClass('outbid')
					.html('<span class="dashicons dashicons-warning"></span> ' + wooAuction.i18n.youAreOutbid);
			} else {
				$('.woo-auction-user-status').remove();
			}

			// Update time remaining
			if (data.time_remaining_formatted) {
				$('.countdown-timer').text(data.time_remaining_formatted);
			}
		},

		/**
		 * Start countdown timers
		 */
		startCountdowns: function() {
			var self = this;

			$('.woo-auction-countdown').each(function() {
				var $countdown = $(this);
				var endTime = parseInt($countdown.data('end-time'));

				if (endTime) {
					self.updateCountdown($countdown, endTime);
					setInterval(function() {
						self.updateCountdown($countdown, endTime);
					}, 1000);
				}
			});

			// Loop countdowns
			$('.woo-auction-countdown-loop').each(function() {
				var $countdown = $(this);
				var endTime = parseInt($countdown.data('end-time'));

				if (endTime) {
					self.updateCountdown($countdown.find('.countdown-value'), endTime);
					setInterval(function() {
						self.updateCountdown($countdown.find('.countdown-value'), endTime);
					}, 1000);
				}
			});
		},

		/**
		 * Update countdown display
		 */
		updateCountdown: function($element, endTime) {
			var now = Math.floor(Date.now() / 1000);
			var remaining = endTime - now;

			if (remaining <= 0) {
				$element.text(wooAuction.i18n.auctionEndedMsg);
				return;
			}

			var days = Math.floor(remaining / 86400);
			var hours = Math.floor((remaining % 86400) / 3600);
			var minutes = Math.floor((remaining % 3600) / 60);
			var seconds = remaining % 60;

			var parts = [];
			if (days > 0) parts.push(days + 'd');
			if (hours > 0 || days > 0) parts.push(hours + 'h');
			if (days === 0) parts.push(minutes + 'm');
			if (days === 0 && hours === 0) parts.push(seconds + 's');

			$element.text(parts.join(' '));
		},

		/**
		 * Start auto-refresh for auction data
		 */
		startAutoRefresh: function() {
			var $biddingBox = $('.woo-auction-bidding-box');
			
			if ($biddingBox.length === 0) return;

			var auctionId = $biddingBox.data('auction-id');
			var self = this;

			setInterval(function() {
				$.ajax({
					url: wooAuction.ajaxUrl,
					type: 'POST',
					data: {
						action: 'woo_auction_get_updates',
						nonce: wooAuction.updatesNonce,
						auction_id: auctionId
					},
					success: function(response) {
						if (response.success && response.data.auction_data) {
							self.updateAuctionData(response.data.auction_data);
						}
					}
				});
			}, wooAuction.refreshInterval);
		}
	};

	/**
	 * Initialize on document ready
	 */
	$(document).ready(function() {
		WooAuctionManager.init();
	});

})(jQuery);
