<?php
/*
Plugin Name: Trafilea Custom Product Page
Plugin URI: http://trafilea.com
Description: Plugin propio de TRAFILEA para mostrar mensajes personalizados en la pagina del producto
Version: 1.0
Author: Marcos S. Vallejos
*/


add_filter( 'woocommerce_format_price_range', 'woocommerce_format_price_range2', 1, 3 );
function woocommerce_format_price_range2( $price, $from, $to ) {
	if(is_product()){
		global $product;
		
		if ($product->is_type( 'variable' ) ) {
		
    		$saved = wc_price( $product->get_variation_regular_price('max') - $product->get_variation_sale_price('min'), ["decimals" => 0] );
			
    		return $price . '<span class="price_save">' . sprintf( __(' Save %s', 'woocommerce' ), $saved ) . '</span>';
		
		}else{
			$saved = wc_price( $regular_price - $sale_price , ["decimals" => 0]); 
		}
	}else{
		return $price;   
	}
}


add_filter( 'woocommerce_format_sale_price', 'woocommerce_custom_sales_price2', 99, 3 );
function woocommerce_custom_sales_price2( $price, $regular_price, $sale_price ) {
	if(is_product()){
		global $product;
		
		if (is_string($regular_price) && $product->is_type( 'variable' ) ) {

			$_product = $product->get_available_variations()[0];
		
    		$saved = wc_price( $_product['display_regular_price'] - $_product['display_price'], ["decimals" => 0] );
	
		}else{
			$saved = wc_price( $regular_price - $sale_price , ["decimals" => 0]); 
		}

    	return $price . '<span class="price_save">' . sprintf( __(' Save %s', 'woocommerce' ), $saved ) . '</span>';   
	}else{
		return $price;   
	}
}



if ( ! function_exists( 'woocommerce_quantity_input' ) ) {
	function woocommerce_quantity_input($data = null) {
	global $product;
	if (!$data || is_product()) {
		$defaults = array(
		'input_name'   => 'quantity',
		'input_value'   => '1',
		'max_value'     => apply_filters( 'woocommerce_quantity_input_max', '', $product ),
		'min_value'     => apply_filters( 'woocommerce_quantity_input_min', '', $product ),
		'step'         => apply_filters( 'woocommerce_quantity_input_step', '1', $product ),
		'style'         => apply_filters( 'woocommerce_quantity_style', 'float:left;', $product )
		);
		$label = '<span class="quantity">' . __('Quantity', 'woocommerce') . '</span>';
	} else {
		$defaults = array(

		'input_name'   => $data['input_name'],

		'input_value'   => $data['input_value'],
		'step'         => apply_filters( 'cw_woocommerce_quantity_input_step', '1', $product ),

		'max_value'     => apply_filters( 'cw_woocommerce_quantity_input_max', '', $product ),

		'min_value'     => apply_filters( 'cw_woocommerce_quantity_input_min', '', $product ),

		'style'         => apply_filters( 'cw_woocommerce_quantity_style', 'float:left;', $product )

		);
		$label = '';
	}



	if ( ! empty( $defaults['min_value'] ) )

	$min = $defaults['min_value'];

	else $min = 1;



	if ( ! empty( $defaults['max_value'] ) )

	$max = $defaults['max_value'];

	else $max = 15;



	if ( ! empty( $defaults['step'] ) )

	$step = $defaults['step'];

	else $step = 1;



	$options = '';



	for ( $count = $min; $count <= $max; $count = $count+$step ) {

	$selected = $count === $defaults['input_value'] ? ' selected' : '';

	$options .= '<option value="' . $count . '"'.$selected.'>' . $count . '</option>';

	}



	echo '<div class="cw_quantity_select" style="' . $defaults['style'] . '">' .
				$label .
				'<select name="' . esc_attr( $defaults['input_name'] ) . '" title="' . __('Quantity', 'woocommerce') . '" class="cw_qty">' . $options . '</select>
			</div>';



	}

}








function load_resources_variations_prices(){

	if(is_woocommerce() && is_product() ) { 		

		wp_enqueue_script( 'custom_product_page', plugin_dir_url( __FILE__ ) . 'custom.js', array(), '20171019', true );
		wp_enqueue_style( 'custom_product_page', plugin_dir_url( __FILE__ ) . 'custom.css', array(), '20171019', true );
		
	}	
}

add_action( 'wp_enqueue_scripts', 'load_resources_variations_prices', 999 );





?>