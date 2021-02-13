<?php

/**
 * WAUC_Schedule class
 *
 * @class WAUC_Schedule The class that holds the entire WAUC_Schedule plugin
 */
class WAUC_Schedule {

    /**
     * Instance of self
     *
     * @var WAUC_Schedule
     */
    private static $instance = null;

    /**
     * Initializes the WAUC_Schedule class
     *
     * Checks for an existing WeDevs_Classname() instance
     * and if it doesn't find one, creates it.
     */
    public static function init() {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Constructor for the Classname class
     *
     * Sets up all the appropriate hooks and actions
     * within our plugin.
     */
    private function __construct() {

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


function WAUC_Schedule() {
    return WAUC_Schedule::init();
}

WAUC_Schedule();