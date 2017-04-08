<?php
/**
 * Add to product type drop down.
 */
function wauc_add_auction_product( $types ){
    // Key should be exactly the same as in the class
    $types[ 'auction' ] = __( 'Auction Product' );
    return $types;
}
add_filter( 'product_type_selector', 'wauc_add_auction_product' );

/**
 * Show pricing fields for simple_rental product.
 */
function auction_custom_js() {
    if ( 'product' != get_post_type() ) :
        return;
    endif;
    ?><script type='text/javascript'>
        jQuery( document ).ready( function() {
            jQuery( '.options_group.pricing' ).addClass( 'show_if_auction' ).show();
        });
    </script><?php
}
add_action( 'admin_footer', 'auction_custom_js' );

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
add_filter( 'woocommerce_product_data_tabs', 'wauc_custom_product_tabs' );

/**
 * Contents of the auction options product tab.
 */
function wauc_auction_options_product_tab_content() {
    global $post;
    ?><div id='auction_options' class='panel woocommerce_options_panel'><?php
    ?><div class='options_group'><?php
    global $wp_roles;
    $roles = $wp_roles->get_names();

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
        'id'			=> 'wauc_buy_price',
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
        'value'         => date('Y-m-d H:i',get_post_meta($post->ID,'wauc_auction_start',true))
    ) );
    woocommerce_wp_text_input( array(
        'id'			=> 'wauc_auction_end',
        'label'			=> __( 'Auction end date', 'wauc' ),
        'desc_tip'		=> 'true',
        'description'	=> __( 'Set the end date of auction', 'wauc' ),
        'type' 			=> 'text',
        'class'         => 'datepicker',
        'value'         => date('Y-m-d H:i',get_post_meta($post->ID,'wauc_auction_end',true))
    ) );

    woocommerce_wp_checkbox( array( 'id' => 'wauc_auction_role_enabled',
            'label' => __( 'Role based capability to bid', 'wauc' ),
            'description'	=> __( 'Enable this if you want the only users with specific to have capability to bid on this product', 'wauc' ),
            'cbvalue' => 'true'
        )
    );

    $wauc_auction_roles = get_post_meta( $post->ID, 'wauc_auction_roles', true );
    ?>
    <p class="form-field wauc_auction_roles_field">
        <label for="wauc_auction_roles">Select Roles</label>
        <?php foreach( $roles as $rolename => $role ): ?>
            <input type="checkbox" name="wauc_auction_roles[]" value="<?php echo $rolename;?>"
                <?php echo is_array( $wauc_auction_roles ) && in_array( $rolename, $wauc_auction_roles ) ? 'checked' : '' ?>
                > <?php echo $role; ?>
        <?php endforeach; ?>
        <span class="description"><?php _e( 'Select the roles that are capable to bid', 'wauc' ); ?></span>
    </p>
    <?php

    ?></div>

    </div><?php
}
add_action( 'woocommerce_product_data_panels', 'wauc_auction_options_product_tab_content' );


/**
 * Save the custom fields.
 */
function wauc_save_auction_option_field( $post_id ) {
    if( isset( $_POST['wauc_product_condition']) ) {
        update_post_meta( $post_id, 'wauc_product_condition', esc_attr( $_POST['wauc_product_condition'] ) );
    }
    if( isset( $_POST['wauc_base_price'] ) && is_numeric( $_POST['wauc_base_price'] ) ) {
        update_post_meta( $post_id, 'wauc_base_price', $_POST['wauc_base_price'] );
    }
    if( isset( $_POST['wauc_bid_increment']) && is_numeric( $_POST['wauc_base_price'] ) ) {
        update_post_meta( $post_id, 'wauc_bid_increment', $_POST['wauc_bid_increment'] );
    }
    if( isset( $_POST['wauc_auction_start'] ) ) {
        update_post_meta( $post_id, 'wauc_auction_start', strtotime( $_POST['wauc_auction_start'] ) );
    }
    if( isset( $_POST['wauc_auction_end']) ) {
        update_post_meta( $post_id, 'wauc_auction_end', strtotime( $_POST['wauc_auction_end'] ) );
    }
    if( isset( $_POST['wauc_auction_role_enabled'] ) && $_POST['wauc_auction_role_enabled'] == 'true' ) {
        update_post_meta( $post_id, 'wauc_auction_role_enabled', $_POST['wauc_auction_role_enabled'] );
    } else {
        update_post_meta( $post_id, 'wauc_auction_role_enabled', 'false' );
    }
    if( isset( $_POST['wauc_auction_roles'])  ) {
        update_post_meta( $post_id, 'wauc_auction_roles', filter_var( $_POST['wauc_auction_roles'] , FILTER_SANITIZE_STRING ) );
    }
}
add_action( 'woocommerce_process_product_meta_auction', 'wauc_save_auction_option_field'  );

/**
 * Hide Attributes data panel.
 */
function wauc_hide_attributes_data_panel( $tabs) {
    $tabs['attribute']['class'][] = 'hide_if_auction';
    //$tabs['general']['class'][] = 'show_if_auction';
    return $tabs;
}
add_filter( 'woocommerce_product_data_tabs', 'wauc_hide_attributes_data_panel' );


/**
 * Auction log meta section
 */

class WAUC_Auction_Log {

    /**
     * @var Singleton The reference the *Singleton* instance of this class
     */
    private static $instance;

    /**
     * Returns the *Singleton* instance of this class.
     *
     * @return Singleton The *Singleton* instance.
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action( 'add_meta_boxes_product', array( $this, 'auction_log_metabox') );

    }

    /**
     * auction_log_metabox
     */
    function auction_log_metabox( $product ) {
        add_meta_box(
            'wauc-auction-log',
            __( 'Auction History', 'wauc' ),
            array( $this, 'render_auction_log' ),
            'product',
            'normal',
            'default'
        );
    }

    function render_auction_log() {
        global $post;
        $logs = (array)WAUC_Functions::get_log_list( $post->ID, 0, 10 );
        ?>
        <div class="bs-container" id="wauc_auction_history">
            <div class="table-responsive">
                <table class="table table-bordered">
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
                        <td><a href="javascript:" @click="delete_log( key, log.id )" class="btn btn-default btn-sm"><i class="fa fa-remove"></i></a></td>
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
                                        auction_history.page_number--;
                                    }
                                } else {
                                    auction_history.page_number++;
                                }

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
                                        if( typeof data == 'string' ) {
                                            auction_history.page_number--;
                                            return;
                                        };
                                        var data = JSON.parse(data);
                                        if( data.result == 'success' ) {
                                            auction_history.logs = data.data;
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
}

WAUC_Auction_Log::get_instance();
