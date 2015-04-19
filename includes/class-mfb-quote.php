<?php
/**
 * MFB_Quote
 * 
 * Quote saved from API response, with corresponding offers.
 * 
 */

class MFB_Quote {

	public $id = 0;
  public $api_quote_uuid = 0;

  public $offers = array();
  public $post = null;

	public function __construct() {
    
	}

	public static function get( $quote_id ) {
    
		if ( is_numeric( $quote_id ) ) {
      $instance = new self();
			$instance->id   = absint( $quote_id );
			$instance->post = get_post( $instance->id );
      $instance->populate();
    }
    return $instance;
	}  

  public function populate() {
    $this->api_quote_uuid = get_post_meta( $this->id, '_api_uuid', true );
    
    // Loading offers
    foreach ( MFB_Offer::get_all_for_quote( $this->id ) as $offer) {
      $this->offers[$offer->product_code] = $offer;
    }
    
  }

  /**
   * Returns all existing dimension objects.
   * If none exist, then we initialize the default values.
   */
  public static function get_all() {
    
    $all_quotes = get_posts( array(
			'posts_per_page'=> -1,
			'post_type' 	=> 'mfb_quote',
      'post_status' => 'private',
      'field' => 'ids',
      'orderby'  => array( 'date' => 'DESC' )
		));

    $quotes = array();
    
    foreach($all_quotes as $quote) {
      $quotes[] = self::get($quote->ID);
    }

    return $quotes;
	}

  public function save() {
    // ID equal to zero, this is a new record
    if ($this->id == 0) {
      $quote = array(
        'post_type' => 'mfb_quote',
        'post_status' => 'private',
        'ping_status' => 'closed',
        'comment_status' => 'closed',
        'post_author' => 1,
        'post_password' => uniqid( 'quote_' ),
        'post_title' => $this->api_quote_uuid
      );

      $this->id = wp_insert_post( $quote, true );

      update_post_meta( $this->id, '_api_uuid',             $this->api_quote_uuid );

      $this->post = get_post( $this->id );
    }
    // Reloading object
    $this->populate();
    return true;
  }
}
