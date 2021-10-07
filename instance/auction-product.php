<?php
namespace wauc\instance;

class Auction_Product {

    protected $meta = [
    	'is_auction_product' => [
    		'key' => 'is_auction_product',
		    'value' => null
	    ],
	    'product_condition' => [
	    	'key' => 'wauc_product_condition',
		    'value' => null
	    ],
	    'base_price' => [
	    	'key' => 'wauc_base_price',
		    'value' => null
	    ],
	    'auction_deposit' => [
	    	'key' => 'wauc_auction_deposit',
		    'value' => null
	    ],
	    'bid_increament' => [
	    	'key' => 'wauc_bid_increament',
		    'value' => null
	    ],
	    'regular_price' => [
	    	'key' => '_regular_price',
		    'value' => null
	    ],
	    'start_date' => [
	    	'key' => 'wauc_start_date',
		    'value' => null
	    ],
	    'end_date' => [
	    	'key' => 'wauc_end_date',
		    'value' => null
	    ],
	    'current_status' => [
	    	'key' => 'wauc_current_status',
		    'value' => null
	    ]
    ];

	protected $id;

	public function __construct( $id ) {
		$this->id = $id;
		return $this;
	}

	public function get_id() {
		return $this->id;
	}

	public function get_meta( $meta ) {
		if ( !isset($this->meta[$meta]) )return false;
		if ( !$this->meta[$meta]['value'] ) {
			$this->meta[$meta]['value'] = get_post_meta( $this->id, $this->meta[$meta]['key'], true );
		}
		return $this->meta[$meta]['value'];
	}

	public function set_meta( $meta, $value ) {
		if( !isset( $this->meta[$meta] ) ) return;
		update_post_meta( $this->id, $this->meta[$meta]['key'], $value );
		$this->meta[$meta]['value'] = $value;
	}

	public function delete_meta( $meta ) {
		if( !isset( $this->meta[$meta] ) ) return;
		delete_post_meta( $this->id, $this->meta[$meta]['key'] );
	}

	public function is_auction_product() {
		return $this->get_meta( 'is_auction_product' );
	}

	public function get_product_condition() {
		return $this->get_meta( 'product_condition' );
	}

	public function get_attribute( $attr ) {
		return $this->get_meta( $attr );
	}

	public function get_attributes() {
		return $this->meta;
	}
}