<?php
global $post;
$product = new \wauc\instance\Auction_Product( $post->ID );
?>
<div>
	<table>
		<tr>
			<td><?php _e( 'Is Auction Product', 'wauc' ); ?></td>
			<td>
				<input type="hidden" name="wauc_is_auction_product" value="0">
				<input type="checkbox" name="wauc_is_auction_product" value="1"
				<?php echo $product->is_auction_product() ? 'checked' : ''; ?>
				>
				<?php _e( 'Check this, if you want this product to be in auction', 'wauc' ); ?>
			</td>
		</tr>
		<?php
		if ( 1 || $product->is_auction_product() ) {
			?>
			<tr>
				<td><?php _e( 'Product Condition', 'wauc' ); ?></td>
				<td>
					<?php
					$conditions = apply_filters( 'wauc_product_condition', [
						'new' => __( 'New', 'wauc' ),
						'old' => __( 'Old', 'wauc' ),
					] );
					?>
					<select name="wauc_product_condition">
						<?php
						foreach ( $conditions as $condition => $label ) {
							?>
							<option value="<?php echo $condition; ?>"
							<?php echo $product->get_product_condition() == $condition ? 'selected' : '' ;?>
							><?php echo $label; ?></option>
						<?php
						}
						?>
					</select>
				</td>
			</tr>
			<tr>
				<td><?php _e( 'Opening Price/Base Price', 'wauc' ); ?></td>
				<td>
					<input type="number" name="wauc_base_price" value="<?php echo $product->get_attribute( 'base_price' ); ?>">
					<?php _e( 'Set the price where the price of the product will start from.', 'ultimate-woocommerce-auction' ); ?>
				</td>
			</tr>
			<tr>
				<td><?php _e( 'Deposit Fee', 'wauc' ); ?></td>
				<td>
					<input type="number" name="wauc_auction_deposit" value="<?php echo $product->get_attribute('auction_deposit'); ?>">
					<?php _e( 'The amount needs to deposit to join an auction, leave empty if you do not want it', 'wauc' ); ?>
				</td>
			</tr>
			<tr>
				<td><?php _e( 'Bid Increament', 'wauc' ); ?></td>
				<td>
					<input type="number" name="wauc_bid_increament" value="<?php echo $product->get_attribute( 'bid_increament' ); ?>">
					<?php _e( 'Set an amount from which next bid should start.', 'ultimate-woocommerce-auction' ); ?>
				</td>
			</tr>
			<tr>
				<td><?php _e( 'Buy Now Price', 'wauc' ); ?></td>
				<td>
					<input type="number" name="_regular_price" value="<?php echo $product->get_attribute( 'regular_price' ); ?>">
					<?php _e( 'Visitors can buy your auction by making payments via Available payment method.', 'ultimate-woocommerce-auction' ); ?>
				</td>
			</tr>
			<tr>
				<td><?php _e( 'Start Date', 'wauc' ); ?></td>
				<td>
					<input type="date" name="wauc_start_date" class="datepicker" value="<?php echo $product->get_attribute( 'start_date' ); ?>">
					<?php _e( 'Set the start date of Auction Product.', 'ultimate-woocommerce-auction' ); ?>
				</td>
			</tr>
			<tr>
				<td><?php _e( 'Ending Date', 'wauc' ); ?></td>
				<td>
					<input type="date" name="wauc_end_date" class="datepicker" value="<?php echo $product->get_attribute( 'end_date' ); ?>">
					<?php _e( 'Set the end date of Auction Product.', 'ultimate-woocommerce-auction' ); ?>
				</td>
			</tr>
			<tr>
				<td><?php _e( 'Auction Status', 'wauc' ); ?></td>
				<td>
					<?php
					$statuses = array(
						'' => __( '', 'wauc' ),
						'future' => __( 'Future', 'wauc' ),
						'running' => __( 'Running', 'wauc' ),
						'processing' => __( 'Processing', 'wauc' ),
						'completed' => __( 'Completed', 'wauc' )
					);
					?>
					<select name="wauc_current_status" id="">
						<?php
						foreach ( $statuses as $status => $label ) {
							?>
							<option value="<?php echo $status; ?>"
							        <?php echo $product->get_attribute('current_status') == $status ? 'selected' : ''; ?>
							><?php echo $label; ?></option>
							<?php
						}
						?>
					</select>
					<?php _e( 'Current status of auction. Recommended to leave it to let the system pick status by itself', 'wauc' ); ?>
				</td>
			</tr>
			<?php do_action( 'wauc_options_product_tab_bottom' ); ?>
		<?php
		}
		?>
	</table>
</div>