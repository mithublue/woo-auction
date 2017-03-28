<?php
add_action( 'admin_init', 'wauc_shortcode_button' );

function wauc_shortcode_button(){
    if( current_user_can('edit_posts') &&  current_user_can('edit_pages') )
    {
        add_filter( 'mce_external_plugins', 'wauc_add_buttons' );
        add_filter( 'mce_buttons', 'wauc_register_buttons' );
    }
}

function wauc_add_buttons( $plugin_array )
{
    if(get_bloginfo('version') >= 3.9){
        $plugin_array['wauc_auction'] = plugin_dir_url( __FILE__ ) . 'assets/js/tinymce-button.js';
    }else{
        $plugin_array['wauc_auction'] = plugin_dir_url( __FILE__ ) . 'assets/js/tinymce-button-older.js';
    }


    return $plugin_array;
}
function wauc_register_buttons( $buttons )
{
    array_push( $buttons, 'separator', 'wauc_auction' );
    return $buttons;
}