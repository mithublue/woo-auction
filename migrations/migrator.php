<?php

namespace wauc\migrations;

use As247\WpEloquent\Support\Facades\Schema;

class Migrator{

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

    public function run() {
	    if( ! Schema::hasTable( 'wauc_auction_log') ) {
		    Schema::create('wauc_auction_log', function ($table) {
			    $table->bigIncrements('id');
			    $table->bigInteger('userid')->nullable();
			    $table->bigInteger('auction_id')->nullable();
			    $table->decimal('bid')->nullable();
			    $table->timestamp('date')->nullable();
			    $table->tinyInteger('is_fake')->nullable();
			    $table->tinyInteger('proxy')->nullable();
			    $table->timestamps();
		    });
	    }

	    if( ! Schema::hasTable( 'wauc_winners') ) {
		    Schema::create('wauc_winners', function ($table) {
			    $table->bigIncrements('id');
			    $table->bigInteger('userid')->nullable();
			    $table->bigInteger('auction_id')->nullable();
			    $table->tinyInteger('is_selected')->nullable();
			    $table->tinyInteger('is_winner')->nullable();
			    $table->bigInteger('log_id')->nullable();
			    $table->timestamp('date')->nullable();
			    $table->timestamps();
		    });
	    }

    }
}