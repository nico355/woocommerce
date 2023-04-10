if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
  function nic_custom_shipping() {
    if ( ! class_exists( 'NIC_Custom_Shipping' ) ) {
      class NIC_Custom_Shipping extends WC_Shipping_Method {
        public function __construct($instance_id = 0) {        
          $this->id                               = 'nic_custom_shipping';
          $this->instance_id                      = absint( $instance_id );
          $this->method_title                     = __( 'NIC Custom Shipping', 'nic-custom-shipping' );
          $this->method_description               = __( 'Custom Shipping Method provides custom shipping cost based on data entered in a custom field.', 'nic-custom-shipping' );
          $this->supports                         = array( 'shipping-zones', 'instance-settings', 'settings' );
          
          
          $this->init_form_fields();
          $this->set_settings();
          
          add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
         
          
                } // Construct
        
        
        public function is_available( $package ) {
		if ( empty( $package['destination']['country'] ) ) {
			return false;
		}

		return apply_filters( 'woocommerce_shipping_' . $this->id . '_is_available', true, $package );
	}
        
        private function set_settings() {
        // Availability & Countries 
          $this->availability = 'including';
          $this->countries = array(

                        'US' // Unites States of America 
                      //  'CA', // Canada 
                    //    'DE', // Germany 
                    //    'GB', // United Kingdom 
                    //    'IT',   // Italy 
                    //    'ES', // Spain 
                    //    'HR'  // Croatia
          );
           $this->enabled = isset( $this->settings['enabled'] ) ? $this->settings['enabled'] : 'yes';
          $this->title = isset( $this->settings['title'] ) ? $this->settings['title'] : __( 'NIC Custom Shipping', 'nic-custom-shipping' );
          
        
        }
        
        public function process_admin_options() {
		parent::process_admin_options();

		$this->set_settings();
	}
        
        
        public function debug( $message, $type = 'notice' ) {
		  if ( $this->debug ) {
			wc_add_notice( $message, $type );
		  }
        }


/** 
* Init your settings 
* 
* @access public 
* @return void 
*/
      

/** 
* Define settings field for this shipping 
* @return void
*/
        public function init_form_fields() { 
          $this->form_fields = array(
            'enabled' => array(
            'title' => __( 'Enable', 'nic-custom-shipping' ),
            'type' => 'checkbox',
            'description' => __( 'Enable this shipping.', 'nic-custom-shipping' ),
            'default' => 'yes',
            'desc_tip'=> true
          ),
          'title' => array(
            'title' => __( 'Title', 'nic-custom-shipping' ),
            'type' => 'text',
            'description' => __( 'Title to be display on site', 'nic-custom-shipping' ),
            'default' => __( 'NIC Custom Shipping', 'nic-custom-shipping' ),
            'desc_tip' => true
          ),
          'weight' => array(
            'title' => __( 'Weight (kg)', 'nic-custom-shipping' ),
            'type' => 'number',
            'description' => __( 'Maximum allowed weight', 'nic-custom-shipping' ),
            'default' => 0,
            'desc_tip' => true
            ),
            'debug'                       => array(
				'title'       => __( 'Debug Mode', 'woocommerce-shipping-fedex' ),
				'label'       => __( 'Enable debug mode', 'woocommerce-shipping-fedex' ),
				'type'        => 'checkbox',
				'default'     => 'no',
				'desc_tip'    => true,
				'description' => __( 'Enable debug mode to show debugging information on the cart/checkout.', 'woocommerce-shipping-fedex' )
			),
          );
          
          
          $shipping_classes = WC()->shipping()->get_shipping_classes();

if ( ! empty( $shipping_classes ) ) {
	$settings['class_costs'] = array(
		'title'       => __( 'Shipping class costs', 'woocommerce' ),
		'type'        => 'title',
		'default'     => '',
		/* translators: %s: URL for link. */
		'description' => sprintf( __( 'These costs can optionally be added based on the <a href="%s">product shipping class</a>.', 'woocommerce' ), admin_url( 'admin.php?page=wc-settings&tab=shipping&section=classes' ) ),
	);
	foreach ( $shipping_classes as $shipping_class ) {
		if ( ! isset( $shipping_class->term_id ) ) {
			continue;
		}
		$settings[ 'class_cost_' . $shipping_class->term_id ] = array(
			/* translators: %s: shipping class name */
			'title'             => sprintf( __( '"%s" shipping class cost', 'woocommerce' ), esc_html( $shipping_class->name ) ),
			'type'              => 'text',
			'placeholder'       => __( 'N/A', 'woocommerce' ),
			'description'       => $cost_desc,
			'default'           => $this->get_option( 'class_cost_' . $shipping_class->slug ), // Before 2.5.0, we used slug here which caused issues with long setting names.
			'desc_tip'          => true,
			'sanitize_callback' => array( $this, 'sanitize_cost' ),
		);
	}

	$settings['no_class_cost'] = array(
		'title'             => __( 'No shipping class cost', 'woocommerce' ),
		'type'              => 'text',
		'placeholder'       => __( 'N/A', 'woocommerce' ),
		'description'       => $cost_desc,
		'default'           => '',
		'desc_tip'          => true,
		'sanitize_callback' => array( $this, 'sanitize_cost' ),
	);

	$settings['type'] = array(
		'title'   => __( 'Calculation type', 'woocommerce' ),
		'type'    => 'select',
		'class'   => 'wc-enhanced-select',
		'default' => 'class',
		'options' => array(
			'class' => __( 'Per class: Charge shipping for each shipping class individually', 'woocommerce' ),
			'order' => __( 'Per order: Charge shipping for the most expensive shipping class', 'woocommerce' ),
		),
	);
}

          
          
          
        }
        
        public function get_instance_form_fields() {
          return parent::get_instance_form_fields();
        }
                /** 
* This function is used to calculate the shipping cost. Within this function we can check for weights, dimensions and other parameters. 
* 
* @access public 
* @param mixed $package 
* @return void 
*/

        public function calculate_shipping( $package ) {
          $weight = 0;
          $cost = 0;
          $productIDs = '';
          $country = $package["destination"]["country"];
          $item_count = 0;
          $ship_cost = 0;
         
         // this is from the flat_rate shipping method.
         // this should probably be done manually in our case
         // also this should be done inside the loop so that only 1 product at a time is affected
       //   $shipping_classes_of_product = $this->find_shipping_classes( $package );
          
            foreach ( $package['contents'] as $item_id => $values ){
              $_product = $values['data'];
              $productIDs = $values['product_id'];
              $weight = $weight + $_product->get_weight() * $values['quantity'];
              $item_count += $values['quantity'];
              
             $ship_cost += get_field( 'custom_shipping_cost', $productIDs) * $values['quantity'];
              
            }
            $weight = wc_get_weight( $weight, 'kg' );
            
            if( $weight <= 10 ) {
              $cost = 0;
            } elseif( $weight <= 30 ) {
                $cost = 5;
            } elseif( $weight <= 50 ) {
                $cost = 10;
            } else {
                $cost = 20;
            }


                    $countryZones = array(
                        'HR' => 0,
                        'US' => 3,
                        'GB' => 2,
                        'CA' => 3,
                        'ES' => 2,
                        'DE' => 1,
                        'IT' => 1     
                        );
                    $zonePrices = array(
                        0 => 10,
                        1 => 30,
                        2 => 50,
                        3 => 70
                        );

                    $zoneFromCountry = $countryZones[ $country ];
                    $priceFromZone = $zonePrices[ $zoneFromCountry ];

                    $cost += $priceFromZone;
                    
                    
                    $customShippingCost = get_field( 'custom_shipping_cost', $productIDs);
                
                    $customShippingCost = $customShippingCost * $item_count;
                    
                   // var_dump($customShippingCost);

                    $rate = array(
                        'id' => $this->id,
                        'label' => $this->title,
                        'cost' => $ship_cost, //  $customShippingCost // $customShippingCost //$cost
                      //  'package' => $package
                    );

                    $this->add_rate( $rate );
                    
                   // var_dump($customShippingCost);

                   
        } // calculate shipping



// not sure if needed
//         public function is_available( $package ) {
//           $is_available = true;
//           return apply_filters( 'woocommerce_shipping_' . $this->id . '_is_available', $is_available, $package, $this );
//        }


/**
	 * Finds and returns shipping classes and the products with said class.
	 *
	 * @param mixed $package Package of items from cart.
	 * @return array
	 */
	public function find_shipping_classes( $package ) {
		$found_shipping_classes = array();

		foreach ( $package['contents'] as $item_id => $values ) {
			if ( $values['data']->needs_shipping() ) {
				$found_class = $values['data']->get_shipping_class();

				if ( ! isset( $found_shipping_classes[ $found_class ] ) ) {
					$found_shipping_classes[ $found_class ] = array();
				}

				$found_shipping_classes[ $found_class ][ $item_id ] = $values;
			}
		}

		return $found_shipping_classes;
	}






            } // Class

        } // class exists
        
        


    } // wrapper function
    add_action( 'woocommerce_shipping_init', 'nic_custom_shipping' );
    
    

    function add_nic_custom_shipping( $methods ) {
        $methods['nic_custom_shipping'] = 'NIC_Custom_Shipping';
        return $methods;
    }

    add_filter( 'woocommerce_shipping_methods', 'add_nic_custom_shipping' );

    function nic_custom_shipping_validate_order( $posted )   {
        $packages = WC()->shipping->get_packages();

        $chosen_methods = WC()->session->get( 'chosen_shipping_methods' );
        
        if( is_array( $chosen_methods ) && in_array( 'nic-custom-shipping', $chosen_methods ) ) {
            foreach ( $packages as $i => $package ) {
              if ( $chosen_methods[ $i ] != "nic-custom-shipping" ) {
                continue;
              }
            
            $NIC_Custom_Shipping = new NIC_Custom_Shipping();
            $weightLimit = (int) $NIC_Custom_Shipping->settings['weight'];
            $weight = 0;
              foreach ( $package['contents'] as $item_id => $values ){ 
                $_product = $values['data']; 
                $weight = $weight + $_product->get_weight() * $values['quantity']; 
              }
                $weight = wc_get_weight( $weight, 'kg' );
               
                if( $weight > $weightLimit ) {
                        $message = sprintf( __( 'Sorry, %d kg exceeds the maximum weight of %d kg for %s', 'nic-custom-shipping' ), $weight, $weightLimit, $NIC_Custom_Shipping->title );                            
                        $messageType = "error";
                        if( ! wc_has_notice( $message, $messageType ) ) {
                        
                            wc_add_notice( $message, $messageType );
                     
                        }
                }
            }       
        } 
    }

    add_action( 'woocommerce_review_order_before_cart_contents', 'nic_custom_shipping_validate_order' , 10 );

    add_action( 'woocommerce_after_checkout_validation', 'nic_custom_shipping_validate_order' , 10 );
    
    
   // add_filter( 'woocommerce_package_rates', 'shipping_cost_based_on_number_of_items', 10, 2 );
    function shipping_cost_based_on_number_of_items( $rates, $package ) {
    $number_of_items = (int) sizeof($package['contents']);
    
    // var_dump($rates);

    // Loop through shipping rates
    foreach ( $rates as $rate_key => $rate ){
        // Targetting "NIC Custom Shipping" shipping method
        if( 'nic_custom_shipping' === $rate->method_id ) {
            $has_taxes = false;

            // Set the new cost
            $rates[$rate_key]->cost = $rate->cost * $number_of_items;

            // Taxes rate cost (if enabled)
            foreach ($rates[$rate_key]->taxes as $key => $tax){
                if( $tax > 0 ){
                    // New tax calculated cost
                    $taxes[$key] = $tax * $number_of_items;
                    $has_taxes = true;
                }
            }
            // Set new taxes cost
            if( $has_taxes )
                $rates[$rate_key]->taxes = $taxes;
        }
    }
   // return $rates;
}






/*
add_filter('woocommerce_package_rates', 'calculate_shipping_separately', 10, 2);

function calculate_shipping_separately($rates, $package) {
    $new_rates = array();

    foreach ($package['contents'] as $item_id => $item) {
        $shipping_class_id = $item['data']->get_shipping_class_id();

        foreach ($rates as $rate_id => $rate) {
            if (strpos($rate_id, "shipping_class_{$shipping_class_id}") !== false) {
                // Make a unique rate ID by combining the original rate ID and the item ID
                $new_rate_id = $rate_id . ':' . $item_id;

                // Clone the rate and set the new rate ID
                $new_rate = clone $rate;
                $new_rate->id = $new_rate_id;

                // Calculate the cost based on the item's quantity
                $new_rate->cost = $rate->cost * $item['quantity'];

                // Add the new rate to the new_rates array
                $new_rates[$new_rate_id] = $new_rate;
            }
        }
    }

    return $new_rates;
}
*/


function filter_shipping_methods_based_on_shipping_class( $available_methods, $package ) {
    $shipping_classes = array();
    $custom_shipping_class = 'freight'; // Replace with the slug of the shipping class you want to check
    $allowed_shipping_methods = array('nic_custom_shipping', 'local_pickup:2', 'legacy_local_pickup'); // Replace with the IDs of the shipping methods you want to keep

    // Loop through the items in the cart and gather their shipping classes
    foreach ( $package['contents'] as $item ) {
        $product = $item['data'];
        $shipping_class = $product->get_shipping_class();

        if ( ! empty( $shipping_class ) ) {
            $shipping_classes[] = $shipping_class;
        }
    }

    // Check if the custom shipping class is in the cart
    if ( in_array( $custom_shipping_class, $shipping_classes ) ) {
        // Loop through the available shipping methods and remove all methods except the allowed ones
        foreach ( $available_methods as $method_id => $method ) {
            if ( !in_array($method_id, $allowed_shipping_methods) ) {
                unset( $available_methods[$method_id] );
            }
        }
    }

    return $available_methods;
}
add_filter( 'woocommerce_package_rates', 'filter_shipping_methods_based_on_shipping_class', 10, 2 );



}
