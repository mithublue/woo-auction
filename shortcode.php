<?php

class wauc_shortcode{

    public function __construct(){
        add_shortcode( 'wauc_auction', array( $this, 'register_shortcode') );
    }

    public function register_shortcode( $atts ) {
        wp_enqueue_style( 'wauc-bs-css' );
        wp_enqueue_script( 'wauc-vue' );

        $atts = shortcode_atts( array(
            'display_sections' => 'current_auctions',
            'display_style' => 'normal',
            'posts_per_page' => 10
        ), $atts, 'wauc_auction' );

        $display_sections = explode('|',$atts['display_sections']);
        !is_array($display_sections)?$display_sections = array() : '';

        $all_sections = array(
            'current_auctions' => array(
                'label' => 'Current Auctions',
                'meta_query' => array(
                    array(
                        'key' => 'wauc_auction_start',
                        'value' => time(),
                        'compare' => '<',
                    ),
                    array(
                        'key' => 'wauc_auction_end',
                        'value' => time(),
                        'compare' => '>',
                    ),
                )
            ),
            'completed_auctions' => array(
                'label' => 'Completed Auctions',
                'meta_query' => array(
                    array(
                        'key' => 'wauc_auction_end',
                        'value' => time(),
                        'compare' => '<',
                    ),
                )
            ),
            'upcoming_auctions' => array(
                'label' => 'Upcoming Auctions',
                'meta_query' => array(
                    array(
                        'key' => 'wauc_auction_start',
                        'value' => time(),
                        'compare' => '>',
                    )
                )
            )
        );

        $result = array();

        ?>
        <div class="bs-container">
        <?php
        foreach ( $display_sections as $key => $section ) {
            $args = array(
                'post_type' => 'product',
                'posts_per_page' => $atts['posts_per_page'],
                'tax_query' => array(
                    array(
                        'taxonomy' => 'product_type',
                        'field'    => 'slug',
                        'terms'    => 'auction',
                    ),
                ),
                'meta_query' => $all_sections[$section]['meta_query']
            );
            $result[$section] = new WP_Query($args);

            ?>

                <div class="panel panel-default">
                    <div class="panel-heading">
                        <?php _e( $all_sections[$section]['label'], 'wauc' ); ?>
                    </div>
                    <div class="panel-body">
                        <ul class="list-group">
                            <?php
                            if( $result[$section]->have_posts() ) :
                                while( $result[$section]->have_posts() ) :
                                    $result[$section]->the_post();
                                    $product = new WC_Product( get_the_ID());
                                    ?>
                                    <li class="list-group-item oh">
                                        <div class="row">
                                            <?php if( $thumb = get_the_post_thumbnail(get_the_ID(),array(100,100)) ) : ?>
                                                <div class="col-sm-3">
                                                    <?php echo $thumb; ?>
                                                </div>
                                            <?php endif; ?>
                                            <div class="col-sm-9">
                                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                            </div>
                                        </div>

                                    </li>
                                    <?php
                                endwhile;
                            else :
                                ?>
                                <li class="list-group-item"><?php _e( 'No auctions found', 'wauc' ); ?></li>
                                <?php
                            endif;
                            ?>
                        </ul>
                    </div><!--panel body-->
                </div>
            <?php

        }
        ?>
        </div><!--bs container-->
            <?php







        wp_reset_query();
    }
}

new wauc_shortcode();