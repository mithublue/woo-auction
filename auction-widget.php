<?php
/**
 * Adds Foo_Widget widget.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // disable direct access
}

class WAUC_Auction_Widget extends WP_Widget {

	/**
	 * Register widget with WordPress.
	 */
	function __construct() {
		parent::__construct(
			'wauc_auction_widget', // Base ID
			__( 'WooCommerce Auction widget', 'wauc' ), // Name
			array( 'description' => __( 'Auction widget for WooCommerce', 'wauc' ), ) // Args
		);
	}

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance, $return = false ) {

		$shortcode = '[wauc_auction 
		display_sections="'.implode('|',$instance['display_sections']).'" 
		display_style="'.$instance['display_style'].'" 
		posts_per_page="'.$instance['numposts'].'"
		]';

		if ( $return ) {
			return $shortcode;
		}

		echo $args['before_widget'];
		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
		}

		echo do_shortcode( $shortcode );
		echo $args['after_widget'];
	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'New title', 'text_domain' );
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php _e( esc_attr( 'Title:' ) ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<?php
		//display what ; product list of current products, past products , upcoming products, featured products
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'numposts' ) ); ?>"><?php _e( esc_attr( 'Number of items to display:' ) ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'numposts' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'numposts' ) ); ?>" type="number" value="<?php echo isset($instance['numposts'])?esc_attr( $instance['numposts'] ) : 10; ?>">
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'display_sections' ) ); ?>"><?php _e( esc_attr( 'Section(s) to display:' ) ); ?></label>
			<?php
			$sections = array(
				'current_auctions' => 'Current Auctions',
				'completed_auctions' => 'Completed Auctions',
				'upcoming_auctions' => 'Upcoming Auctions'
			);

			foreach( $sections as $section_name => $val ) {
				?>
				<p><label>
					<input class="widefat"  name="<?php echo esc_attr( $this->get_field_name( 'display_sections' ) ); ?>[]" type="checkbox" value="<?php echo $section_name; ?>"
						<?php echo isset( $instance['display_sections'] ) && is_array($instance['display_sections']) && in_array( $section_name,$instance['display_sections'] )?'checked':''; ?>
						> <?php echo $val; ?>
				</label></p>
					<?php
			}
			?>
		</p>
		<p>
<?php
		$display_styles = array(
			'normal' => 'Normal',
			'accordion' => 'Accordion',
			'tabs' => 'Tabs'
		);
		?>
		<select name="<?php echo esc_attr( $this->get_field_name( 'display_style' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'display_style' ) ); ?>">
			<?php
			foreach( $display_styles as $style => $val ) {
				?>
				<option class="widefat"
						value="<?php echo $style; ?>"
					<?php echo isset($instance['display_style']) && $style == $instance['display_style'] ? 'selected' : '' ; ?>
					> <?php echo $val; ?>
				</option>
				<?php
			}
			?>
		</select>
		</p>
<?php

		//style ; tab, accordion, normal
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['display_sections'] = isset( $new_instance['display_sections'] ) ? $new_instance['display_sections'] : array();
		$instance['display_style'] = $new_instance['display_style'];
		$instance['numposts'] = $new_instance['numposts'];
		return $instance;
	}

} // class Foo_Widget

if( phpversion() > '5.3' ) {
	add_action( 'widgets_init', function(){
		register_widget( 'WAUC_Auction_Widget' );
	});
} else {
	add_action('widgets_init',
		create_function('', 'return register_widget("WAUC_Auction_Widget");')
	);
}

