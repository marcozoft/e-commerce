<?php
/*
Plugin Name: Trafilea Installment Labels
Plugin URI: http://trafilea.com
Description: Plugin propio de TRAFILEA para la implementacion de un label en la pagina del producto que muestre el precio de pago en cuotas
Version: 1.0
Author: Marcos S. Vallejos
*/


 add_action( 'add_meta_boxes', 'add_installment_meta_boxes');


 function add_installment_meta_boxes( $post_type ) {

    add_meta_box( 'installment_boxes_metabox' , __( 'Label de cuotas' ) , 'installment_boxes_metabox' , 'product' , 'normal');

}

function installment_boxes_metabox( $post ) {
    ?>
    <style type="text/css">
        ._quantity_installment_field, ._price_installment_field {
            width: 49%;    
            display: inline-block;
        }
        ._quantity_installment_field input, ._price_installment_field input{
            width: 34% !important;
            margin-left: 4%;
        }        
    </style>


    <?php woocommerce_wp_text_input( array( 'id' => '_quantity_installment', 'type' => 'number' ,'label' => __( 'Cantidad de cuotas', 'woocommerce'),  'placeholder' => 'Ej: 12' ) ); ?>
    <?php woocommerce_wp_text_input( array( 'id' => '_price_installment', 'type' => 'text',  'label' => __( 'Precio de cuotas', 'woocommerce' ),  'placeholder' => 'Ej: 50' ) ); ?>


<?php

}



add_action( 'save_post', function($post_id) {
     

    if ( isset( $_POST['_quantity_installment'] ) ) {
        
        $_quantity_installment = '';
        
        if( $_POST['_quantity_installment'] !== '' ) {

            $_quantity_installment = htmlspecialchars( $_POST['_quantity_installment'] );
            
        }
        
        update_post_meta( $post_id, '_quantity_installment', $_quantity_installment );
        
    }

     

    if ( isset( $_POST['_price_installment'] ) ) {
        
        $_price_installment = '';
        
        if( $_POST['_price_installment'] !== '' ) {

            $_price_installment = htmlspecialchars( $_POST['_price_installment'] );
            
        }
        
        update_post_meta( $post_id, '_price_installment', $_price_installment );
        
    }
   
   
}); 



function installment_add_in_page_product() {
    global $post;
    $cant = get_post_meta($post->ID, '_quantity_installment' , true );
    $price = get_post_meta($post->ID, '_price_installment' , true );
    $symbol_money = get_woocommerce_currency_symbol(get_woocommerce_currency());    

    if($cant && $price){
        echo '<span class="installment">
                <span class="quantity">'.$cant.'x</span> de <span class="price">'.$symbol_money . ' ' . $price.'</span>
            </span>';
    }
}

add_action( 'woocommerce_before_add_to_cart_form', 'installment_add_in_page_product', 10 );

?>