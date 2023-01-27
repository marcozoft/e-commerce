<?php
/*
Plugin Name: Trafilea Payment Methods Widgets
Plugin URI: http://trafilea.com
Description: Plugin propio de TRAFILEA para la implementacion de Widgets configurables en el panel de control para agregar imagenes de los medios de pago en la pagina de producto y el carro de compras
Version: 1.0
Author: Marcos S. Vallejos
*/


// AGREGAMOS WIDGET EN PAGINA DE PRODUCTO PARA PONER IMAGENES DE MEDIOS DE PAGOS
 if ( ! function_exists( 'gpwidg_generatepress_widgets_init_below_title' ) ) {
    function gpwidg_generatepress_widgets_init_below_title() {

        register_sidebar( array(

            'name'          => 'Page Product - Method Payment',

            'id'            => 'payment_methods_page_product',

            'before_widget' => '<div>',

            'after_widget'  => '</div>',

            'before_title'  => '<h2 class="wiget-title">',

            'after_title'   => '</h2>',

        ) );

    }
    add_action( 'widgets_init', 'gpwidg_generatepress_widgets_init_below_title' );
}


if ( ! function_exists( 'gpwidg_generatepress_widgets_body' ) ) {
    function gpwidg_generatepress_widgets_body() {

        if ( is_active_sidebar( 'payment_methods_page_product' ) ){

        echo '<div id="primary-sidebar" class="primary-sidebar widget-area" role="complementary">';

            dynamic_sidebar( 'payment_methods_page_product' );

        echo '</div>';

        }

    }
    add_action( 'woocommerce_single_product_summary', 'gpwidg_generatepress_widgets_body', 10000);
}






// AGREGAMOS WIDGET EN PAGINA DE PRODUCTO PARA PONER IMAGENES DE MEDIOS DE PAGOS
if ( ! function_exists( 'gpwidg_generatepress_widgets_init_below_title_cart' ) ) {
    function gpwidg_generatepress_widgets_init_below_title_cart() {

        register_sidebar( array(

            'name'          => 'Cart Page - Method Payment',

            'id'            => 'payment_methods_page_cart',

            'before_widget' => '<div>',

            'after_widget'  => '</div>',

            'before_title'  => '<h2 class="wiget-title">',

            'after_title'   => '</h2>',

        ) );

    }
    add_action( 'widgets_init', 'gpwidg_generatepress_widgets_init_below_title_cart' );
}



if ( ! function_exists( 'gpwidg_generatepress_widgets_body_cart' ) ) {
    function gpwidg_generatepress_widgets_body_cart() {

        if ( is_active_sidebar( 'payment_methods_page_cart' ) ){

        echo '<div id="primary-sidebar" class="primary-sidebar widget-area" role="complementary">';

            dynamic_sidebar( 'payment_methods_page_cart' );

        echo '</div>';

        }

    }
    add_action( 'woocommerce_after_cart_totals', 'gpwidg_generatepress_widgets_body_cart', 10000);
}


?>