/**
 * Woo Live Auctions - Admin JavaScript
 *
 * @package    Woo_Live_Auctions
 * @subpackage Woo_Live_Auctions/admin/assets/js
 * @since      1.0.0
 */

(function($) {
	'use strict';

	/**
	 * Admin Manager Class
	 */
	var WooAuctionAdmin = {
		
		/**
		 * Initialize
		 */
		init: function() {
			this.initDateTimePickers();
			this.initProductTypeToggle();
			this.initConditionalFields();
		},

		/**
		 * Initialize date/time pickers
		 */
		initDateTimePickers: function() {
			$('.woo-auction-datetime-picker').each(function() {
				var $input = $(this);
				
				// Add date picker
				$input.datepicker({
					dateFormat: 'yy-mm-dd',
					changeMonth: true,
					changeYear: true,
					showButtonPanel: true,
					beforeShow: function(input, inst) {
						setTimeout(function() {
							inst.dpDiv.css({
								marginTop: '0px',
								marginLeft: '0px'
							});
						}, 0);
					}
				});

				// Add time picker functionality (simple implementation)
				$input.after('<input type="time" class="woo-auction-time-picker" style="margin-left: 5px;" />');
				
				var $timePicker = $input.next('.woo-auction-time-picker');
				
				// Parse existing value
				var existingValue = $input.val();
				if (existingValue) {
					var parts = existingValue.split(' ');
					if (parts.length === 2) {
						$input.val(parts[0]);
						$timePicker.val(parts[1]);
					}
				}

				// Combine date and time on change
				$input.add($timePicker).on('change', function() {
					var date = $input.val();
					var time = $timePicker.val() || '00:00:00';
					
					if (date) {
						$input.val(date + ' ' + time);
						$timePicker.hide();
					}
				});

				// Show time picker when date is selected
				$input.on('change', function() {
					if ($input.val()) {
						$timePicker.show();
					}
				});

				// Hide time picker initially if no value
				if (!existingValue) {
					$timePicker.hide();
				}
			});
		},

		/**
		 * Initialize product type toggle
		 */
		initProductTypeToggle: function() {
			$('select#product-type').on('change', function() {
				var productType = $(this).val();
				
				if (productType === 'auction') {
					$('.show_if_auction').show();
					$('.hide_if_auction').hide();
					$('#auction_product_data').show();
				} else {
					$('.show_if_auction').hide();
					$('.hide_if_auction').show();
				}
			}).trigger('change');
		},

		/**
		 * Initialize conditional fields
		 */
		initConditionalFields: function() {
			// Show/hide fields based on settings
			$('input[type="checkbox"]').on('change', function() {
				var $checkbox = $(this);
				var fieldId = $checkbox.attr('id');
				
				// Handle conditional fields if needed
				if (fieldId === 'woo_auction_enable_proxy_bidding') {
					// Could show/hide proxy-related settings
				}
			});

			// Validate bid increment
			$('#_auction_bid_increment').on('change', function() {
				var value = parseFloat($(this).val());
				if (value <= 0) {
					alert('Bid increment must be greater than 0');
					$(this).val('1.00');
				}
			});

			// Validate dates
			$('#_auction_start_date, #_auction_end_date').on('change', function() {
				var startDate = $('#_auction_start_date').val();
				var endDate = $('#_auction_end_date').val();

				if (startDate && endDate) {
					var start = new Date(startDate);
					var end = new Date(endDate);

					if (end <= start) {
						alert('End date must be after start date');
						$('#_auction_end_date').val('');
					}
				}
			});

			// Validate reserve price
			$('#_auction_reserve_price').on('change', function() {
				var reservePrice = parseFloat($(this).val());
				var startPrice = parseFloat($('#_auction_start_price').val());

				if (reservePrice && startPrice && reservePrice < startPrice) {
					if (!confirm('Reserve price is lower than start price. Continue?')) {
						$(this).val('');
					}
				}
			});

			// Validate buy now price
			$('#_auction_buy_now_price').on('change', function() {
				var buyNowPrice = parseFloat($(this).val());
				var startPrice = parseFloat($('#_auction_start_price').val());

				if (buyNowPrice && startPrice && buyNowPrice < startPrice) {
					alert('Buy now price should be higher than start price');
					$(this).val('');
				}
			});
		}
	};

	/**
	 * Initialize on document ready
	 */
	$(document).ready(function() {
		WooAuctionAdmin.init();
	});

})(jQuery);
