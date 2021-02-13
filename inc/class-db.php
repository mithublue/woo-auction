<?php

class WAUC_DB {

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

    public function install_tables() {
        global $wpdb;
        $data_table = $wpdb->prefix."wauc_auction_log";

        $winners = $wpdb->prefix."wauc_winners";
        $sql = " CREATE TABLE IF NOT EXISTS $data_table (
  						`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
						  `userid` bigint(20) unsigned NOT NULL,
						  `auction_id` bigint(20) unsigned DEFAULT NULL,
						  `bid` decimal(30,2) DEFAULT NULL,
						  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
						  `is_fake` tinyint(1) DEFAULT 0,
						  `proxy` tinyint(1) DEFAULT NULL,
						  PRIMARY KEY (`id`)
						);";

        $sql_winners = "CREATE TABLE IF NOT EXISTS $winners (
  						  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
						  `userid` bigint(20) unsigned NOT NULL,
						  `auction_id` bigint(20) unsigned NOT NULL,
						  `is_selected` tinyint(1) DEFAULT 0,
						  `is_winner` tinyint(1) DEFAULT 0,
						  `log_id` bigint(20) DEFAULT NULL,
						  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
						  PRIMARY KEY (`id`)
						);";
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
        dbDelta( $sql_winners );
    }
}

function WAUC_DB() {
    return WAUC_DB::instance();
}