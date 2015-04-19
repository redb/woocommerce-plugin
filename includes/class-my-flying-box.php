<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class My_Flying_Box  extends WC_Shipping_Method {

	/**
	 * The single instance of My_Flying_Box.
	 * @var 	object
	 * @access  private
	 * @since 	1.0.0
	 */
	private static $_instance = null;

	/**
	 * Settings class object
	 * @var     object
	 * @access  public
	 * @since   1.0.0
	 */
	public $settings = null;

	/**
	 * The version number.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $_version;

	/**
	 * The token.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $_token;

	/**
	 * The main plugin file.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $file;

	/**
	 * The main plugin directory.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $dir;

	/**
	 * The plugin assets directory.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $assets_dir;

	/**
	 * The plugin assets URL.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $assets_url;

	/**
	 * Suffix for Javascripts.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $script_suffix;

	/**
	 * Constructor function.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function __construct ( $file = '', $version = '1.0.0' ) {
		$this->_version = $version;
		$this->_token = 'my_flying_box';

    $this->includes();

    $this->register_custom_post_types();
    $this->register_custom_post_statuses();


		// Load plugin environment variables
		$this->file = $file;
		$this->dir = dirname( $this->file );
		$this->assets_dir = trailingslashit( $this->dir ) . 'assets';
		$this->assets_url = esc_url( trailingslashit( plugins_url( '/assets/', $this->file ) ) );

		$this->script_suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		register_activation_hook( $this->file, array( $this, 'install' ) );

		// Load frontend JS & CSS
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ), 10 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10 );

		// Load admin JS & CSS
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 10, 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_styles' ), 10, 1 );

    // Add delivery location selector for shipment methods supporting it
    add_filter( 'woocommerce_cart_shipping_method_full_label', array( &$this,'add_delivery_location_selector_to_shipping_method_label'), 10, 2 );
    add_action( 'woocommerce_after_checkout_form',  array( &$this,'load_delivery_location_selector_tools')  );
    add_action( 'wp_ajax_mfb_ajax_get_delivery_locations', array( &$this, 'ajax_get_delivery_locations') );

    // Save selected delivery location during cart checkout

    add_action( 'woocommerce_checkout_update_order_meta', array( &$this, 'reset_selected_delivery_location' ) );
    add_action( 'woocommerce_checkout_process', array( &$this,'hook_process_order_checkout'));
    add_action( 'woocommerce_checkout_order_processed', array( &$this,'hook_new_order'));


		// Load API for generic admin functions
		if ( is_admin() ) {
			$this->admin = new My_Flying_Box_Admin_API();
		}

    $api_env = My_Flying_Box_Settings::get_option('mfb_api_env');
    $api_login = My_Flying_Box_Settings::get_option('mfb_api_login');
    $api_password = My_Flying_Box_Settings::get_option('mfb_api_password');
    
    if ($api_env != 'staging' && $api_env != 'production') $api_env = 'staging';
    $this->api = Lce\Lce::configure($api_login, $api_password, $api_env);
    $this->api->application = "woocommerce-mfb";
    $this->api->application_version = $this->_version . " (WOO: " . WC()->version . ")";

		// Handle localisation
		$this->load_plugin_textdomain();
    
		add_action( 'init', array( $this, 'load_localisation' ), 0 );


    // Add shipping methods
    add_filter('woocommerce_shipping_methods', array(&$this, 'myflyingbox_filter_shipping_methods'));

	} // End __construct ()

	/**
	 * Wrapper function to register a new post type
	 * @param  string $post_type   Post type name
	 * @param  string $plural      Post type item plural name
	 * @param  string $single      Post type item single name
	 * @param  string $description Description of post type
	 * @return object              Post type class object
	 */
	public function register_post_type ( $post_type = '', $plural = '', $single = '', $description = '', $in_menu = false ) {

		if ( ! $post_type || ! $plural || ! $single ) return;

		$post_type = new My_Flying_Box_Post_Type( $post_type, $plural, $single, $description, $in_menu );

		return $post_type;
	}

	/**
	 * Wrapper function to register a new taxonomy
	 * @param  string $taxonomy   Taxonomy name
	 * @param  string $plural     Taxonomy single name
	 * @param  string $single     Taxonomy plural name
	 * @param  array  $post_types Post types to which this taxonomy applies
	 * @return object             Taxonomy class object
	 */
	public function register_taxonomy ( $taxonomy = '', $plural = '', $single = '', $post_types = array() ) {

		if ( ! $taxonomy || ! $plural || ! $single ) return;

		$taxonomy = new My_Flying_Box_Taxonomy( $taxonomy, $plural, $single, $post_types );

		return $taxonomy;
	}

	/**
	 * Load frontend CSS.
	 * @access  public
	 * @since   1.0.0
	 * @return void
	 */
	public function enqueue_styles () {
		wp_register_style( $this->_token . '-frontend', esc_url( $this->assets_url ) . 'css/frontend.css', array(), $this->_version );
		wp_enqueue_style( $this->_token . '-frontend' );
	} // End enqueue_styles ()

	/**
	 * Load frontend Javascript.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function enqueue_scripts () {
		wp_register_script( $this->_token . '-frontend', esc_url( $this->assets_url ) . 'js/frontend' . $this->script_suffix . '.js', array( 'jquery' ), $this->_version );
		wp_enqueue_script( $this->_token . '-frontend' );
	} // End enqueue_scripts ()

	/**
	 * Load admin CSS.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function admin_enqueue_styles ( $hook = '' ) {
		wp_register_style( $this->_token . '-admin', esc_url( $this->assets_url ) . 'css/admin.css', array(), $this->_version );
		wp_enqueue_style( $this->_token . '-admin' );
	} // End admin_enqueue_styles ()

	/**
	 * Load admin Javascript.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function admin_enqueue_scripts ( $hook = '' ) {
		wp_register_script( $this->_token . '-admin', esc_url( $this->assets_url ) . 'js/admin' . $this->script_suffix . '.js', array( 'jquery' ), $this->_version );
		wp_enqueue_script( $this->_token . '-admin' );
	} // End admin_enqueue_scripts ()

	/**
	 * Load plugin localisation
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function load_localisation () {
		load_plugin_textdomain( 'my-flying-box', false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	} // End load_localisation ()

	/**
	 * Load plugin textdomain
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function load_plugin_textdomain () {
	    $domain = 'my-flying-box';

	    $locale = apply_filters( 'plugin_locale', get_locale(), $domain );

	    load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
	    load_plugin_textdomain( $domain, false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	} // End load_plugin_textdomain ()


  public function register_custom_post_types() {
    $this->register_post_type( 'mfb_shipment', __( 'Shipments', 'my-flying-box' ), __( 'Shipment', 'my-flying-box' ), '', true );
    $this->register_post_type( 'mfb_carrier', __( 'Carriers', 'my-flying-box' ), __( 'Carrier', 'my-flying-box' ), '', false );
    $this->register_post_type( 'mfb_dimension', __( 'Dimensions', 'my-flying-box' ), __( 'Carrier', 'my-flying-box', '', false ) );
  }

  public function register_custom_post_statuses(){
    register_post_status( 'mfb-inactive', array(
      'label'                     => _x( 'Disabled', 'my-flying-box' ),
      'public'                    => false,
      'exclude_from_search'       => true,
      'show_in_admin_all_list'    => false,
      'show_in_admin_status_list' => false,
      'label_count'               => _n_noop( 'Disabled <span class="count">(%s)</span>', 'Disabled <span class="count">(%s)</span>' ),
    ) );
    
    register_post_status( 'mfb-active', array(
      'label'                     => _x( 'Enabled', 'my-flying-box' ),
      'public'                    => false,
      'exclude_from_search'       => true,
      'show_in_admin_all_list'    => false,
      'show_in_admin_status_list' => false,
      'label_count'               => _n_noop( 'Enabled <span class="count">(%s)</span>', 'Enabled <span class="count">(%s)</span>' ),
    ) );
    
  }


	/**
	 * Main My_Flying_Box Instance
	 *
	 * Ensures only one instance of My_Flying_Box is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see My_Flying_Box()
	 * @return Main My_Flying_Box instance
	 */
	public static function instance ( $file = '', $version = '1.0.0' ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $file, $version );
		}
		return self::$_instance;
	} // End instance ()

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->_version );
	} // End __clone ()

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->_version );
	} // End __wakeup ()

	/**
	 * Installation. Runs on activation.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function install () {
		$this->_log_version_number();
    
	} // End install ()

	/**
	 * Log the plugin version number.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	private function _log_version_number () {
		update_option( $this->_token . '_version', $this->_version );
	} // End _log_version_number ()


  private function includes() {

    include_once( 'lib/php-lce/bootstrap.php' );
    include_once( 'class-mfb-carrier.php' );
    include_once( 'class-mfb-shipping-method.php' );
    include_once( 'class-mfb-quote.php' );
    include_once( 'class-mfb-offer.php' );
    include_once( 'class-mfb-dimension.php' );
    if ( is_admin() ) {
      include_once( 'class-mfb-admin-menus.php' );
    }
  }

  /**
   * Add MFB shipping methods, based on active services 
   */
  public function myflyingbox_filter_shipping_methods( $methods ) {
    
    $active_carriers = MFB_Carrier::get_all_active();

    foreach ( $active_carriers as $key => $carrier ) {
      $method_name = $carrier->code;
      if ( ! class_exists($method_name)){
        eval("class $method_name extends MFB_Shipping_Method{}");
      }
      if ( !in_array( $method_name, $methods ) ) {
        $methods[] = new $method_name();
      }
    }

    return $methods;
  }

  /**
   * Reset delivery location, in the case of shop delivery.
   */
  public function reset_selected_delivery_location( $order_id ) {
    $order = wc_get_order( $order_id );
      
    update_post_meta( $order->id, '_mfb_delivery_location', '' );				
  }


  /**
   * Validate the checkout form, so we can save the delivery location
  **/
  public function hook_process_order_checkout() {
    global $order_id;

    // We save the latest quote associated to this cart
    update_post_meta( $order_id, '_mfb_last_quote_id', WC()->session->get('myflyingbox_shipment_quote_id') );

    // Check if the parcel point is needed and if it has been chosen
    if (isset($_POST['shipping_method']))	{
      foreach($_POST['shipping_method'] as $shipping_method) {
        $carrier = MFB_Carrier::get_by_code( $shipping_method );
        if ($carrier->shop_delivery) {
          if (isset($_POST['_delivery_location'])) {
            update_post_meta( $order_id, '_mfb_delivery_location', $_POST['_delivery_location'] );
          }
          else {
            wc_add_notice(__('Please select a delivery location','my-flying-box'),'error');
          }
          
        }
      }
    }
  }

  public function hook_new_order($order_id) {

    // We save the latest quote associated to this cart
    update_post_meta( $order_id, '_mfb_last_quote_id', WC()->session->get('myflyingbox_shipment_quote_id') );

    if (isset($_POST['shipping_method']))	{
      foreach($_POST['shipping_method'] as $shipping_method) {
        $carrier = MFB_Carrier::get_by_code( $shipping_method );
        update_post_meta( $order_id, '_mfb_carrier_code', $shipping_method );
        
        if ($carrier->shop_delivery) {
          if (isset($_POST['_delivery_location'])) {
            update_post_meta( $order_id, '_mfb_delivery_location', $_POST['_delivery_location'] );
          }          
        }
      }
    }
  }

  /**
   * Extending labels of shipping methods for MFB services:
   *  - add description
   *  - add selector of delivery location when applicable
   *
   */
  public function add_delivery_location_selector_to_shipping_method_label($full_label, $shipping_rate){

    $method_code = $shipping_rate->id;
    $carrier = MFB_Carrier::get_by_code( $method_code );

    // If this is not a MFB service, we do not do anything.
    if ( ! $carrier )
      return $full_label;

    
    if ( ! class_exists( $method_code ))
      eval("class $method_code extends MFB_Shipping_Method{}");

    $method = new $method_code();

    // Adding description, if present
    if ( ! empty($method->description) )
      $full_label .= '<br/><span class="description">'.$method->description.'</span>';

    // Add a selector of available delivery locations, pulled from the API
    if ( ( (stristr(WC()->cart->get_checkout_url(), $_SERVER['REQUEST_URI']) ||  (stristr(WC()->cart->get_checkout_url(), $_SERVER['HTTP_REFERER'] ) ) ) ) 
          && in_array($method->id , WC()->session->get('chosen_shipping_methods') ) ) {

      if ($carrier->shop_delivery) {
        
        // Initializing currently valid quote; We need the offer's uuid to request the locations
        $quote = MFB_Quote::get( WC()->session->get('myflyingbox_shipment_quote') );
        
        $full_label .=  '<br/><span class="select-location" id="locationselector__'.$method->id.'__'.$quote->offers[$method->id]->api_offer_uuid.'">'.__( 'Choose a location', 'my-flying-box' ).'</span>';
        $full_label .=  '<br/><span>'.__( 'Selected ', 'my-flying-box' ).' : <span id="mfb-location-client"></span></span>';
        $full_label .=  '<span id="input_'.$method->id.'"></span>';
      }
    }


    return $full_label;
  }


  public function load_delivery_location_selector_tools( $checkout ) {
    
    $translations = array(
      'Unable to load parcel points' => __( 'Unable to load parcel points', 'my-flying-box' ),
      'Select this location' => __( 'Select this location', 'my-flying-box' ),
      'day_1' => __( 'Monday', 'my-flying-box' ),
      'day_2' => __( 'Tuesday', 'my-flying-box' ),
      'day_3' => __( 'Wednesday', 'my-flying-box' ),
      'day_4' => __( 'Thursday', 'my-flying-box' ),
      'day_5' => __( 'Friday', 'my-flying-box' ),
      'day_6' => __( 'Saturday', 'my-flying-box' ),
      'day_7' => __( 'Sunday', 'my-flying-box' )
    );

    wp_enqueue_script( 'jquery' );
    wp_enqueue_script( 'gmap', '//maps.google.com/maps/api/js?sensor=false' );
    wp_enqueue_script( 'mfb_delivery_locations','/wp-content/plugins/my-flying-box/assets/js/delivery_locations.js',array( 'jquery', 'gmap' ) );
    wp_localize_script( 'mfb_delivery_locations', 'plugin_url', plugins_url() );
    wp_localize_script( 'mfb_delivery_locations', 'lang', $translations );
    wp_localize_script( 'mfb_delivery_locations', 'map', My_Flying_Box::generate_google_map_html_container() );

    // Get the protocol of the current page
    $protocol = isset( $_SERVER['HTTPS'] ) ? 'https://' : 'http://';

    $params = array(
        // Get the url to the admin-ajax.php file using admin_url()
        // All Ajax requests in WP go to admin-ajax.php
        'ajaxurl' => admin_url( 'admin-ajax.php', $protocol ),
        'action'  => 'mfb_ajax_get_delivery_locations'
    );
    // Print the script to our page
    wp_localize_script( 'mfb_delivery_locations', 'mfb_params', $params );
    
  }


  public function ajax_get_delivery_locations () {

    // Getting Offer from transmitted uuid:
    $offer = MFB_Offer::get_by_uuid( $_REQUEST['k'] );

    // Extracting address and city
    $customer = WC()->session->get('customer');

    $street = $customer['shipping_address'];
    if ( ! empty($customer['shipping_address_2']) ) {
      $street .= "\n".$customer['shipping_address_2'];
    }

    $params = array(
      'street' => $street,
      'city' => $customer['shipping_city']
    );


    // Building the response
    $response = array();

    $locations = $offer->get_delivery_locations( $params );

    if ( ! empty($locations) ) {

      $response['data'] = 'success';
      $response['locations'] = $locations;
      
    } else {
      $response['data'] = 'error';
      $response['message'] = 'Failed to load locations';
    }
    
    // Whatever the outcome, send the Response back
    wp_send_json( $response );
 
  }

  public static function generate_google_map_html_container() {
    return  '<div id="map-container">
               <p>
                 <a class="mfb-close-map">'.__( 'Hide map', 'my-flying-box' ).'</a>
               </p>
               <div id="map-canvas"></div>
             </div>';
  }
}
