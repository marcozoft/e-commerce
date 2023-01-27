<?php

// this function sets the checkout form data as session transients whenever the checkout page validates
function set_persitent_checkout ( $a ) {
    $arr = WC()->session->get('form_data');

    foreach ( $a as $key => $value )
        if ( ! empty($value) || $value != '')
            $arr[$key] = $value;


    WC()->session->set( 'form_data', $arr );
    
    return $a;
}








function crear_orden($result, $x_invoice){
     global $woocommerce;  
	
	    define( 'WOOCOMMERCE_CHECKOUT', true );
    	 define( 'WOOCOMMERCE_CART', true );   
		WC()->cart->calculate_totals();	
	
	  $checkout = WC_Checkout::instance();
	  
      $data = WC()->session->get('form_data');
      
	  $order_id = $checkout->create_order( $data );
	  //var_dump($data);
	  $order = new WC_Order( $order_id );
	 	    
      //$order->set_address( removePrefix($data, 'billing_'), 'billing');
      $order->set_address( removePrefix($data, 'billing_'), 'shipping');

		$order->add_meta_data( 'DLOCAL_invoice', $x_invoice );
	
        if($result == 6){
            $order->update_status('failed', __('Transaccion fallida', 'woothemes'));
        } else if($result == 8){
            $order->update_status('cancelled', __('Transaccion rechazada', 'woothemes'));
        } else if($result == 7){
            $order->update_status('pending', __('Transaccion pendiente', 'woothemes'));
        } else if($result == 9){
            $order->payment_complete();
        } else{
            $order->update_status('failed', __('Transaccion fallida', 'woothemes'));
        }
		/*
		WC()->cart->calculate_totals();	

		$checkout->create_order_shipping_lines( $order, WC()->session->get( 'chosen_shipping_methods' ), WC()->shipping->get_packages() );
		*/
        error_log('SAVE SESSION - Orden: ' . $order_id . '- chosen: ' . print_r(WC()->session->get( 'chosen_shipping_methods' ),true). ' - packages: ' . print_r(WC()->shipping->get_packages(),true));
		
		/*
		echo '---' . $woocommerce->cart->shipping_total;
		if($woocommerce->cart->shipping_total > 0){$ship_type = 'taxa plana';}else{$ship_type = 'free shipping';}
	    $shipping_rate = new WC_Shipping_Rate( '', $ship_type, $woocommerce->cart->shipping_total, 0, 'custom_shipping_method' );
        $order->add_shipping($shipping_rate);
		*/
		
		$order->calculate_totals();
      	
		return $order; 

 
}
	
	/*   ANTIGUO METODO PROGRAMATICO DE CREACION DE ORDENES

function crear_orden($result, $x_invoice){
     define( 'WOOCOMMERCE_CHECKOUT', true );
     define( 'WOOCOMMERCE_CART', true );     
		
      // Now we create the order
      $order = wc_create_order();

      // The add_product() function below is located in /plugins/woocommerce/includes/abstracts/abstract_wc_order.php
      // 
      do_action("woocommerce_before_calculate_totals", WC()->cart);



      foreach (WC()->cart->get_cart() as $cart_item_key => $values) {
        $product = new WC_Product( $values['product_id'] );
        if( $product->is_type( 'variable' ) ){
            $product_id = $values['product_id'];
            $product = wc_get_product($product_id);
            $var_id = $values['variation_id'];
            $var_slug = $values['variation']['attribute_pa_weight'];
            $quantity = (int)$values['quantity'];
            $variationsArray = array();
            $variationsArray['variation'] = array(
              'pa_weight' => $var_slug
            );
            $var_product = new WC_Product_Variation($var_id);
            $order->add_product($var_product, $quantity, $variationsArray);          
        }else{
            $order->add_product($values['data'], $values['quantity']); // This is an existing SIMPLE product
        }

      }

      $order->set_address( removePrefix($data, 'billing_'), 'billing');
      $order->set_address( removePrefix($data, 'billing_'), 'shipping');

      apply_filters( "woocommerce_cart_ready_to_calc_shipping", true);
      
      $order->calculate_totals();
      $order->calculate_shipping();

       update_post_meta( $order->id, '_payment_method', 'dlocal_tarjeta' );
       update_post_meta( $order->id, '_payment_method_title', 'Cartões de credito' );
      
		$order->add_meta_data( 'DLOCAL_invoice', $x_invoice );
       
        if($result == 6){
            $order->update_status('failed', __('Transaccion fallida', 'woothemes'));
        } else if($result == 8){
            $order->update_status('cancelled', __('Transaccion rechazada', 'woothemes'));
        } else if($result == 7){
            $order->update_status('pending', __('Transaccion pendiente', 'woothemes'));
        } else if($result == 9){
            $order->payment_complete();
        } else{
            $order->update_status('failed', __('Transaccion fallida', 'woothemes'));
        }
	

      
        return $order; 

      //$order->update_status("Completed", 'Imported order', TRUE);     
}
*/

function borrar_session(){
    WC()->cart->empty_cart();
    //WC()->session->set( 'form_data', array() );
}



function removePrefix(array $input, $prefix) {
    $return = array();
    foreach ($input as $key => $value) {
        if (strpos($key, $prefix) === 0)
            $key = substr($key, strlen($prefix));

        if (is_array($value))
            $value = removePrefix($value); 

        $return[$key] = $value;
    }
    return $return;
}


function changePrefix(array $input, $prefix, $newPrefix) {
    $return = array();
    foreach ($input as $key => $value) {
        if (strpos($key, $prefix) === 0)
            $key = str_replace($prefix,$newPrefix,$key);

        $return[$key] = $value;
    }
    return $return;
}


function to_local_money($amount, $country){
	require_once 'lib/dLocalStreamline.php';
	
	$dl = new DLocal_Bancos;
			
	$cred = $dl->get_dlocal_settings();

   	$aps = new dLocalStreamline(($cred['test'] == 'yes' ? true : false));
	$aps->init_credencials($cred['x_login'], $cred['x_trans_key'], $cred['x_login_for_webpaystatus'], $cred['x_trans_key_for_webpaystatus'], $cred['secret_key'], ($cred['test'] == 'yes' ? true : false));
	
	$resp = $aps->get_exchange($country, 1);
	$amount = $amount * $resp;
	
	return round($amount, 2) . ' ' . get_currency_of_country($country);

}


function get_currency_of_country($country){
	$ar = get_currency_countries();
	foreach($ar as $m => $c){
		if(in_array($country,$c)){
			return $m;
			continue;
		}
	}
	return false;
}


function get_currency_countries() {
        return array(
            'AFN' => array( 'AF' ),
            'ALL' => array( 'AL' ),
            'DZD' => array( 'DZ' ),
            'USD' => array( 'AS', 'IO', 'GU', 'MH', 'FM', 'MP', 'PW', 'PR', 'TC', 'US', 'UM', 'VI' ),
            'EUR' => array( 'AD', 'AT', 'BE', 'CY', 'EE', 'FI', 'FR', 'GF', 'TF', 'DE', 'GR', 'GP', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'MQ', 'YT', 'MC', 'ME', 'NL', 'PT', 'RE', 'PM', 'SM', 'SK', 'SI', 'ES' ),
            'AOA' => array( 'AO' ),
            'XCD' => array( 'AI', 'AQ', 'AG', 'DM', 'GD', 'MS', 'KN', 'LC', 'VC' ),
            'ARS' => array( 'AR' ),
            'AMD' => array( 'AM' ),
            'AWG' => array( 'AW' ),
            'AUD' => array( 'AU', 'CX', 'CC', 'HM', 'KI', 'NR', 'NF', 'TV' ),
            'AZN' => array( 'AZ' ),
            'BSD' => array( 'BS' ),
            'BHD' => array( 'BH' ),
            'BDT' => array( 'BD' ),
            'BBD' => array( 'BB' ),
            'BYR' => array( 'BY' ),
            'BZD' => array( 'BZ' ),
            'XOF' => array( 'BJ', 'BF', 'ML', 'NE', 'SN', 'TG' ),
            'BMD' => array( 'BM' ),
            'BTN' => array( 'BT' ),
            'BOB' => array( 'BO' ),
            'BAM' => array( 'BA' ),
            'BWP' => array( 'BW' ),
            'NOK' => array( 'BV', 'NO', 'SJ' ),
            'BRL' => array( 'BR' ),
            'BND' => array( 'BN' ),
            'BGN' => array( 'BG' ),
            'BIF' => array( 'BI' ),
            'KHR' => array( 'KH' ),
            'XAF' => array( 'CM', 'CF', 'TD', 'CG', 'GQ', 'GA' ),
            'CAD' => array( 'CA' ),
            'CVE' => array( 'CV' ),
            'KYD' => array( 'KY' ),
            'CLP' => array( 'CL' ),
            'CNY' => array( 'CN' ),
            'HKD' => array( 'HK' ),
            'COP' => array( 'CO' ),
            'KMF' => array( 'KM' ),
            'CDF' => array( 'CD' ),
            'NZD' => array( 'CK', 'NZ', 'NU', 'PN', 'TK' ),
            'CRC' => array( 'CR' ),
            'HRK' => array( 'HR' ),
            'CUP' => array( 'CU' ),
            'CZK' => array( 'CZ' ),
            'DKK' => array( 'DK', 'FO', 'GL' ),
            'DJF' => array( 'DJ' ),
            'DOP' => array( 'DO' ),
            'ECS' => array( 'EC' ),
            'EGP' => array( 'EG' ),
            'SVC' => array( 'SV' ),
            'ERN' => array( 'ER' ),
            'ETB' => array( 'ET' ),
            'FKP' => array( 'FK' ),
            'FJD' => array( 'FJ' ),
            'GMD' => array( 'GM' ),
            'GEL' => array( 'GE' ),
            'GHS' => array( 'GH' ),
            'GIP' => array( 'GI' ),
            'QTQ' => array( 'GT' ),
            'GGP' => array( 'GG' ),
            'GNF' => array( 'GN' ),
            'GWP' => array( 'GW' ),
            'GYD' => array( 'GY' ),
            'HTG' => array( 'HT' ),
            'HNL' => array( 'HN' ),
            'HUF' => array( 'HU' ),
            'ISK' => array( 'IS' ),
            'INR' => array( 'IN' ),
            'IDR' => array( 'ID' ),
            'IRR' => array( 'IR' ),
            'IQD' => array( 'IQ' ),
            'GBP' => array( 'IM', 'JE', 'GS', 'GB' ),
            'ILS' => array( 'IL' ),
            'JMD' => array( 'JM' ),
            'JPY' => array( 'JP' ),
            'JOD' => array( 'JO' ),
            'KZT' => array( 'KZ' ),
            'KES' => array( 'KE' ),
            'KPW' => array( 'KP' ),
            'KRW' => array( 'KR' ),
            'KWD' => array( 'KW' ),
            'KGS' => array( 'KG' ),
            'LAK' => array( 'LA' ),
            'LBP' => array( 'LB' ),
            'LSL' => array( 'LS' ),
            'LRD' => array( 'LR' ),
            'LYD' => array( 'LY' ),
            'CHF' => array( 'LI', 'CH' ),
            'MKD' => array( 'MK' ),
            'MGF' => array( 'MG' ),
            'MWK' => array( 'MW' ),
            'MYR' => array( 'MY' ),
            'MVR' => array( 'MV' ),
            'MRO' => array( 'MR' ),
            'MUR' => array( 'MU' ),
            'MXN' => array( 'MX' ),
            'MDL' => array( 'MD' ),
            'MNT' => array( 'MN' ),
            'MAD' => array( 'MA', 'EH' ),
            'MZN' => array( 'MZ' ),
            'MMK' => array( 'MM' ),
            'NAD' => array( 'NA' ),
            'NPR' => array( 'NP' ),
            'ANG' => array( 'AN' ),
            'XPF' => array( 'NC', 'WF' ),
            'NIO' => array( 'NI' ),
            'NGN' => array( 'NG' ),
            'OMR' => array( 'OM' ),
            'PKR' => array( 'PK' ),
            'PAB' => array( 'PA' ),
            'PGK' => array( 'PG' ),
            'PYG' => array( 'PY' ),
            'PEN' => array( 'PE' ),
            'PHP' => array( 'PH' ),
            'PLN' => array( 'PL' ),
            'QAR' => array( 'QA' ),
            'RON' => array( 'RO' ),
            'RUB' => array( 'RU' ),
            'RWF' => array( 'RW' ),
            'SHP' => array( 'SH' ),
            'WST' => array( 'WS' ),
            'STD' => array( 'ST' ),
            'SAR' => array( 'SA' ),
            'RSD' => array( 'RS' ),
            'SCR' => array( 'SC' ),
            'SLL' => array( 'SL' ),
            'SGD' => array( 'SG' ),
            'SBD' => array( 'SB' ),
            'SOS' => array( 'SO' ),
            'ZAR' => array( 'ZA' ),
            'SSP' => array( 'SS' ),
            'LKR' => array( 'LK' ),
            'SDG' => array( 'SD' ),
            'SRD' => array( 'SR' ),
            'SZL' => array( 'SZ' ),
            'SEK' => array( 'SE' ),
            'SYP' => array( 'SY' ),
            'TWD' => array( 'TW' ),
            'TJS' => array( 'TJ' ),
            'TZS' => array( 'TZ' ),
            'THB' => array( 'TH' ),
            'TOP' => array( 'TO' ),
            'TTD' => array( 'TT' ),
            'TND' => array( 'TN' ),
            'TRY' => array( 'TR' ),
            'TMT' => array( 'TM' ),
            'UGX' => array( 'UG' ),
            'UAH' => array( 'UA' ),
            'AED' => array( 'AE' ),
            'UYU' => array( 'UY' ),
            'UZS' => array( 'UZ' ),
            'VUV' => array( 'VU' ),
            'VEF' => array( 'VE' ),
            'VND' => array( 'VN' ),
            'YER' => array( 'YE' ),
            'ZMW' => array( 'ZM' ),
            'ZWD' => array( 'ZW' ),
        );
    }


/*
add_action( 'woocommerce_after_checkout_validation', 'set_persitent_checkout' );


// this function hooks into woocommerce_checkout_get_value to substitute standard values with session values if present
function get_persistent_checkout ( $value, $index ) {
    $data = WC()->session->get('form_data');
    if ( ! $data || empty($data[$index]) )
        return $value;
    return is_bool($data[$index]) ? (int) $data[$index] : $data[$index];
}
add_filter( 'woocommerce_checkout_get_value', 'get_persistent_checkout', 10, 2 );


// This is a fix for the ship_to_different_address field which gets it value differently if there is no POST data on the checkout
function get_persitent_ship_to_different ( $value ) {
    $data = WC()->session->get('form_data');
    if ( ! $data || empty($data['ship_to_different_address']) )
        return $value;

    return is_bool($data['ship_to_different_address']) ? (int) $data['ship_to_different_address'] : $data['ship_to_different_address'];
}
add_action( 'woocommerce_ship_to_different_address_checked', 'get_persitent_ship_to_different' );
*/

?>