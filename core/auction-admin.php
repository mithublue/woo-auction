<?php
namespace wauc\core;

class Auction_Admin{

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
	    add_filter( 'product_type_selector', array( $this, 'wauc_add_auction_product' ) );
	    add_action( 'add_meta_boxes_product', array( $this, 'auction_log_metabox') );
	    add_filter( 'woocommerce_product_data_tabs', array( $this, 'wauc_custom_product_tabs' ) );
	    add_filter( 'woocommerce_product_data_tabs', array( $this, 'wauc_hide_attributes_data_panel' ) );
	    add_action( 'woocommerce_product_data_panels', array( $this, 'wauc_auction_options_product_tab_content' ) );
	    add_action( 'woocommerce_process_product_meta_auction', array( $this, 'wauc_save_auction_option_field' )  );
	    add_action( 'save_post', array( $this, 'create_token' ), 10, 3 );
	    add_action( 'admin_footer', array( $this, 'wauc_auction_custom_js' ) );
	    //partipents list
	    add_action( 'add_meta_boxes_product', array( $this, 'auction_participant_metabox') );
	    //order panel to clear order
	    add_action( 'woocommerce_order_status_completed', array( $this, 'woocommerce_order_status_completed' ), 10, 1 );

	    //after completing the order for owning the winning product
	    add_action( 'woocommerce_order_status_completed',  array( $this, 'make_auction_total_completed' ),10, 2 );
    }

	/**
	 * Send mail to user on
	 * approval of auction request
	 * @param $order
	 */
	public function woocommerce_order_status_completed( $order ) {
		$order = new \WC_Order( $order );

		foreach( $order->get_items() as $item ) {
			$product_id = $item['product_id'];
			break;
		};

		$auction_product = get_post( wp_get_post_parent_id( $product_id ) );

		if( !$auction_product || empty( $auction_product ) ) return;
		$product = ( new \WC_Product_Factory() )->get_product($auction_product->ID);
		if( $product->get_type() !== 'auction' ) return;

		if( $order->get_billing_email() ) {
			Notification::send_auction_request_approval_notification( $auction_product, $order->get_billing_email() );
		}
	}

	public function make_auction_total_completed( $order_id, $order ) {
		$order = new \WC_Order( $order_id );
		foreach( $order->get_items() as $item ) {
			$product_id = $item['product_id'];
			$product = wc_get_product( $product_id ) ;

			//check if auction product
			if( Functions::is_auction_product( $product ) ) {
				$customer_id = $order->get_meta('wauc_order_user_id' );

				if( $customer_id ) {
					//check if user is selected for auction
					if( Functions::is_selected( $customer_id, $product->get_id() ) ) {
						//set as winner
						Functions::set_as_winner( $customer_id, $product->get_id() );
						//change auction product status to completed
						Functions::change_auction_status( 'processing', 'completed', $product->get_id() );
					}
				}

				//on purchase
				Functions::change_auction_status( null, 'completed', $product->get_id() );
			}
		};
	}

	/**
	 * Add to product type drop down.
	 */
	function wauc_add_auction_product( $types ){
		// Key should be exactly the same as in the class
		$types[ 'auction' ] = __( 'Auction Product' );
		return $types;
	}


	/**
	 * Metabox for participant list
	 * @param $product
	 */
	public function auction_participant_metabox( $product ) {

		$_pf = new \WC_Product_Factory();
		$prd = $_pf->get_product($product->ID);
		if( $prd->get_type() !== 'auction' ) return;

		add_meta_box(
			'wauc-auction-participants',
			__( 'Participant List', 'wauc' ),
			array( $this, 'render_render_participant_list' ),
			'product',
			'normal',
			'default'
		);
	}

	/**
	 * Render participant list
	 * in metabox
	 */
	public function render_render_participant_list() {
		global $post;
		$participants = (array)Functions::get_participant_list( $post, 0, 10 );

		?>
		<div class="wauc" id="wauc_participant_list" v-cloak>
			<div class="wauc-table-responsive">
				<table class="wauc-table wauc-table-bordered">
					<tr>
						<th><?php _e( 'Email', 'wauc' ); ?></th>
						<th><?php _e( 'Display Name', 'wauc' ); ?></th>
					</tr>
					<tr v-for="( user, key ) in participants">
						<td>{{ user.user_email }}</td>
						<td>{{ user.display_name }}</td>
					</tr>
				</table>
				<div class="text-right">
					<a href="javascript:" class="btn btn-sm btn-default" :class="{ disabled : is_disabled }" @click="render_list('prev')"><i class="fa fa-arrow-left"></i></a>
					<a href="javascript:" class="btn btn-sm btn-default" @click="render_list('next')"><i class="fa fa-arrow-right"></i></a>
				</div>
			</div>
		</div>
		<script>
			(function ($) {
				$(document).ready(function () {
					var participant_list = new Vue({
						el: '#wauc_participant_list',
						data : {
							participants : JSON.parse('<?php echo json_encode($participants); ?>'),
							page_number : 0,
							per_page : 10
						},
						computed: {
							is_disabled : function () {
								if ( this.page_number == 0 ) return true;
								return false;
							}
						},
						methods : {
							render_list : function ( serial ) {

								if ( serial == 'prev' ) {
									if( participant_list.page_number > 0 ) {
										--participant_list.page_number;
									}
								} else if ( serial == 'next' ){
									++participant_list.page_number;
								}
								//test

								var offset = participant_list.page_number * participant_list.per_page;

								$.post(
									ajaxurl,
									{
										action : 'wauc_render_participant_list',
										offset : offset,
										per_page : participant_list.per_page,
										product_id : '<?php echo $post->ID; ?>'
									},
									function (data) {
										var data = JSON.parse(data);

										if( data.result == 'success' ) {
											participant_list.participants = data.data;
										} else {
											participant_list.page_number--;
											return;
										}
									}
								)
							},
						}
					});
				});

			}(jQuery))
		</script>
		<?php

	}

	/**
	 * metabox for auction log
	 */
	function auction_log_metabox( $product ) {

		$_pf = new \WC_Product_Factory();
		$prd = $_pf->get_product($product->ID);
		if( $prd->get_type() !== 'auction' ) return;

		add_meta_box(
			'wauc-auction-log',
			__( 'Auction History', 'wauc' ),
			array( $this, 'render_auction_log' ),
			'product',
			'normal',
			'default'
		);
	}

	/**
	 * Render auction in
	 * metabox
	 */
	function render_auction_log() {
		global $post;
		$logs = (array)Functions::get_log_list( $post->ID, 0, 10 );
		?>
		<div class="wauc" id="wauc_auction_history" v-cloak>
			<div class="wauc-table-responsive">
				<table class="wauc-table wauc-table-bordered">
					<tr>
						<th><?php _e( 'Bid', 'wauc' ); ?></th>
						<th><?php _e( 'User', 'wauc' ); ?></th>
						<th><?php _e( 'Time', 'wauc' ); ?></th>
						<th><?php _e( 'Action', 'wauc' ); ?></th>
					</tr>
					<tr v-for="( log, key ) in logs">
						<td>{{ log.bid }}</td>
						<td>{{ log.user_nicename }} | {{ log.user_email }}</td>
						<td>{{ log.date }}</td>
						<td></td>
					</tr>
				</table>
				<div class="text-right">
					<a href="javascript:" class="btn btn-sm btn-default" :class="{ disabled : is_disabled }" @click="render_list('prev')"><i class="fa fa-arrow-left"></i></a>
					<a href="javascript:" class="btn btn-sm btn-default" @click="render_list('next')"><i class="fa fa-arrow-right"></i></a>
				</div>
			</div>
		</div>
		<script>
			(function ($) {
				$(document).ready(function () {
					var auction_history = new Vue({
						el : '#wauc_auction_history',
						data : {
							logs : JSON.parse('<?php echo json_encode($logs); ?>'),
							page_number : 0,
							per_page : 10
						},
						computed : {
							is_disabled : function () {
								if ( this.page_number == 0 ) return true;
								return false;
							}
						},
						methods : {
							delete_log : function ( key, id ) {
								$.post(
									ajaxurl,
									{
										action : 'wauc_delete_log',
										id : id,
										product_id : '<?php echo $post->ID; ?>'
									},
									function (data) {
										var data = JSON.parse(data);
										if( data.result == 'success') {
											Vue.delete( auction_history.logs, key );
										}
									}
								)
							},
							render_list : function ( serial ) {

								if ( serial == 'prev' ) {
									if( auction_history.page_number > 0 ) {
										--auction_history.page_number;
									}
								} else if ( serial == 'next' ){
									++auction_history.page_number;
								}
								//test

								var offset = auction_history.page_number * auction_history.per_page;

								$.post(
									ajaxurl,
									{
										action : 'wauc_render_page',
										offset : offset,
										per_page : auction_history.per_page,
										product_id : '<?php echo $post->ID; ?>'
									},
									function (data) {
										var data = JSON.parse(data);

										if( data.result == 'success' ) {
											auction_history.logs = data.data;
										} else {
											auction_history.page_number--;
											return;
										}
									}
								)
							},
						}
					})
				})
			}(jQuery))
		</script>
		<?php
	}

	/**
	 * Add a custom product tab.
	 */
	function wauc_custom_product_tabs( $tabs) {
		$tabs['auction'] = array(
			'label'		=> __( 'Auction', 'wauc' ),
			'target'	=> 'auction_options',
			'class'		=> array( 'show_if_auction' ),
		);
		return $tabs;
	}

	/**
	 * Contents of the
	 * auction options product tab.
	 */
	function wauc_auction_options_product_tab_content() {
		global $post;
		?><div id='auction_options' class='panel woocommerce_options_panel'><?php
		?><div class='options_group'><?php

		do_action( 'wauc_options_product_tab_top');

		// Download Type
		woocommerce_wp_select( array( 'id' => 'wauc_product_condition',
		                              'label' => __( 'Product Condition', 'wauc' ),
		                              'desc_tip'		=> 'true',
		                              'description' => sprintf( __( 'Condition of product', 'wauc' ) ),
		                              'options' => array(
			                              'new' => __( 'New', 'wauc' ),
			                              'old'       => __( 'Old', 'wauc' ),
		                              ) ) );
		woocommerce_wp_text_input( array(
			'id'			=> 'wauc_base_price',
			'label'			=> __( 'Base price', 'wauc' ),
			'desc_tip'		=> 'true',
			'description'	=> __( 'Set the price where the price of the product will start from', 'wauc' ),
			'type' 			=> 'number',
		) );
		woocommerce_wp_text_input( array(
			'id'			=> 'wauc_bid_increment',
			'label'			=> __( 'Bid increment', 'wauc' ),
			'desc_tip'		=> 'true',
			'description'	=> __( 'Set the step of increment of bid price', 'wauc' ),
			'type' 			=> 'number',
		) );
		woocommerce_wp_text_input( array(
			'id'			=> 'wauc_auction_deposit',
			'label'			=> __( 'Deposit Fee', 'wauc' ),
			'desc_tip'		=> 'true',
			'description'	=> __( 'The amount needs to deposit to join an auction, leave empty if you do not want it', 'wauc' ),
			'type' 			=> 'number',
		) );
		woocommerce_wp_text_input( array(
			'id'			=> '_regular_price',
			'label'			=> __( 'Buy now price', 'wauc' ),
			'desc_tip'		=> 'true',
			'description'	=> __( 'If you want to let the customers buy the product without auction , set a value for this, customer will be ablue
                        to see the button until the auction price is less than the buy price', 'wauc' ),
			'type' 			=> 'number',
		) );
		woocommerce_wp_text_input( array(
			'id'			=> 'wauc_auction_start',
			'label'			=> __( 'Auction start date', 'wauc' ),
			'desc_tip'		=> 'true',
			'description'	=> __( 'Set the start date of auction', 'wauc' ),
			'type' 			=> 'text',
			'class'         => 'datepicker',
			'default'       => time(),
			'value'         => date('Y-m-d H:i', get_post_meta($post->ID,'wauc_auction_start',true) ? get_post_meta($post->ID,'wauc_auction_start',true) : time() )
		) );
		woocommerce_wp_text_input( array(
			'id'			=> 'wauc_auction_end',
			'label'			=> __( 'Auction end date', 'wauc' ),
			'desc_tip'		=> 'true',
			'description'	=> __( 'Set the end date of auction', 'wauc' ),
			'type' 			=> 'text',
			'class'         => 'datepicker',
			'value'         => date('Y-m-d H:i', get_post_meta($post->ID,'wauc_auction_end',true ) ? get_post_meta($post->ID,'wauc_auction_end',true ) : time() )
		) );
		woocommerce_wp_select( array( 'id' => '_wauc_current_status',
		                              'label' => __( 'Auction Status', 'wauc' ),
		                              'desc_tip'		=> 'true',
		                              'description' => sprintf( __( 'Current status of auction. Recommended to leave it to let the system pick status by itself', 'wauc' ) ),
		                              'options' => array(
			                              '' => __( '', 'wauc' ),
			                              'future' => __( 'Future', 'wauc' ),
			                              'running' => __( 'Running', 'wauc' ),
			                              'processing' => __( 'Processing', 'wauc' ),
			                              'completed' => __( 'Completed', 'wauc' )
		                              ) ) );

		do_action( 'wauc_options_product_tab_bottom' );
		?>
		</div>
		</div>
		<?php
	}



	/**
	 * Save the custom fields.
	 */
	function wauc_save_auction_option_field( $post_id ) {

		if ( !isset( $_POST['_regular_price'] ) || !$_POST['_regular_price'] ) {
			$_POST['_regular_price'] = 0;
			update_post_meta( $post_id, '_price', $_POST['_regular_price'] );
			update_post_meta( $post_id, '_regular_price', $_POST['_regular_price'] );
		}
		/*if( !get_post_meta( $post_id, '_wauc_current_status', true ) ) {

		}*/

		if( !isset( $_POST['_wauc_current_status'] ) || !$_POST['_wauc_current_status'] ) {
			if( strtotime( $_POST['wauc_auction_start'] )  > time() ) {
				update_post_meta( $post_id, '_wauc_current_status', 'future' );
			} elseif( strtotime( $_POST['wauc_auction_start'] )  < time() && strtotime( $_POST['wauc_auction_end'] ) > time() ) {
				update_post_meta( $post_id, '_wauc_current_status', 'running' );
			} elseif( strtotime( $_POST['wauc_auction_end'] ) < time() ) {
				if( !get_post_meta( $post_id, 'wauc_winner', true ) ) {
					update_post_meta( $post_id, '_wauc_current_status', 'processing' );
				} else {
					update_post_meta( $post_id, '_wauc_current_status', 'completed' );
				}
			}
		} else {
			update_post_meta( $post_id, '_wauc_current_status', $_POST['_wauc_current_status'] );
		}

		if( isset( $_POST['wauc_product_condition']) ) {
			update_post_meta( $post_id, 'wauc_product_condition', esc_attr( $_POST['wauc_product_condition'] ) );
		}
		if( isset( $_POST['wauc_base_price'] ) && is_numeric( $_POST['wauc_base_price'] ) ) {
			update_post_meta( $post_id, 'wauc_base_price', $_POST['wauc_base_price'] );
		}
		if( isset( $_POST['wauc_bid_increment']) && is_numeric( $_POST['wauc_base_price'] ) ) {
			update_post_meta( $post_id, 'wauc_bid_increment', (int)$_POST['wauc_bid_increment'] );
		}
		if( isset( $_POST['_regular_price']) && is_numeric( $_POST['_regular_price'] ) ) {
			update_post_meta( $post_id, '_regular_price', (int)$_POST['_regular_price'] );
		}
		if( isset( $_POST['wauc_auction_deposit'])  ) {
			update_post_meta( $post_id, 'wauc_auction_deposit', $_POST['wauc_auction_deposit'] );
			$token_id = get_post_meta( $post_id, 'wauc_token_id', true );

			if( $token_id ) {
				update_post_meta( $token_id,'_regular_price', $_POST['wauc_auction_deposit'] );
				update_post_meta( $token_id,'_price', $_POST['wauc_auction_deposit'] );
			}
		}

		if( isset( $_POST['wauc_auction_start'] ) ) {
			update_post_meta( $post_id, 'wauc_auction_start', strtotime( $_POST['wauc_auction_start'] ) );
		}
		if( isset( $_POST['wauc_auction_end']) ) {
			update_post_meta( $post_id, 'wauc_auction_end', strtotime( $_POST['wauc_auction_end'] ) );
		}
		/**/
	}

	/**
	 * Hide Attributes data panel.
	 */
	function wauc_hide_attributes_data_panel( $tabs) {
		//$tabs['attribute']['class'][] = 'show_if_auction';
		//$tabs['general']['class'][] = 'show_if_auction';
		return $tabs;
	}

	/**
	 * Token creates when the
	 * product is created
	 * token is to be bought by user
	 * if depostit money option is enabled
	 * @param $post_id
	 * @param $post
	 * @param $update
	 */
	public function create_token(  $post_id, $post, $update  ) {

		if( get_post_type( $post_id ) !== 'product' ) return;

		$_pf = new \WC_Product_Factory();
		$product = $_pf->get_product($post_id);
		if( $product->get_type() !== 'auction' ) return;

		remove_action( 'save_post', array( $this, 'create_token' ) );

		$args = array(
			'post_title' => 'Token for product #'.$post->ID.' : '.$post->post_title,
			'post_parent' => $post->ID,
			'meta_input' => array(
				'wauc_product_id' => $post->ID,
				'_regular_price' => isset( $_POST['wauc_auction_deposit'] ) && !empty( $_POST['wauc_auction_deposit'] )  ? $_POST['wauc_auction_deposit'] : 0,
				'_price' => isset( $_POST['wauc_auction_deposit'] ) && !empty( $_POST['wauc_auction_deposit'] ) ? $_POST['wauc_auction_deposit'] : 0
			),
			'post_type' => 'product',
			'post_status' => 'publish'
		);
		$token_id = get_post_meta( $post->ID, 'wauc_token_id', true );

		if( $token_id ) {
			$args['ID'] = $token_id;
		}

		$token_id = wp_insert_post( $args );
		update_post_meta( $post->ID, 'wauc_token_id', $token_id );
	}


	/**
	 * Show pricing fields for simple_rental product.
	 */
	function wauc_auction_custom_js() {
		if ( 'product' != get_post_type() ) :
			return;
		endif;
		?><script type='text/javascript'>
			jQuery( document ).ready( function() {
				jQuery( '.options_group.pricing' ).addClass( 'show_if_auction' ).show();
			});
		</script><?php
	}
}