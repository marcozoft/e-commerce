<?php
/*
Plugin Name: Trafilea Countries Select
Plugin URI: http://trafilea.com
Description: Plugin propio de TRAFILEA para el ordenamiento del select de paises en el checkout y quitar la libreria Select2
Version: 1.0
Author: Marcos S. Vallejos
*/

include_once "countries.php";


add_filter('woocommerce_countries','custom_country', 10, 1);

function custom_country($mycountry){
    include_once WC()->plugin_path() . '/i18n/countries.php';    
    $paises = WC()->countries->get_continents();
    $order = ['SA','NA','EU','AS','OC','AF','AN'];
    $mycountry = array();
    
    foreach ($order as $o) {
        foreach ($paises[$o]['countries'] as $key => $value) {
            $name = find_country_name($value);
            if($name && $value){
                $mycountry[$value] = find_country_name($value);
            }
        }
    }
   
    return $mycountry;
}



// define the woocommerce_sort_countries callback 
function filter_woocommerce_sort_countries( $true ) { 
    // make filter magic happen here... 
    return false; 
}; 
         
// add the filter 
add_filter( 'woocommerce_sort_countries', 'filter_woocommerce_sort_countries', 10, 1 ); 


add_action( 'wp_enqueue_scripts', 'remove_select2_library', 100 );
function remove_select2_library() {
    if ( class_exists( 'woocommerce' ) ) {
        wp_dequeue_style( 'select2' );
        wp_deregister_style( 'select2' );
        wp_dequeue_script( 'select2');
        wp_deregister_script('select2');        
        wp_dequeue_script( 'selectWoo');
        wp_deregister_script('selectWoo');

        wp_enqueue_script( 'countries_select_js', plugin_dir_url( __FILE__ ) . 'custom.js', array(), '20171020', true );
        //wp_enqueue_style( 'countries_select_css', plugin_dir_url( __FILE__ ) . 'custom.css', array(), '20171020', true );


    }
}






?>