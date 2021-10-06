<?php
namespace wauc\core;

class Schedules{

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

    }

	public function init_schedules() {
		if ( !wp_next_scheduled ( 'wauc_auction_daily_hook' )) {
			wp_schedule_event( time(), 'daily', 'wauc_auction_daily_hook' );
		}
	}

	public function clear_schedules() {
		wp_clear_scheduled_hook('wauc_auction_daily_hook' );
	}
}