<?php
/*
Plugin Name: Trafilea Coupon in Review Order 
Plugin URI: http://trafilea.com
Description: Plugin propio de TRAFILEA para la implementacion de cupones de descuento en el order review del checkout
Version: 1.0
Author: Marcos S. Vallejos
*/




    function coupon_apply_copy() {
        if ( is_checkout() && (wc_coupons_enabled() || !empty( WC()->cart->applied_coupons ))) {
            echo '   
            <tr>
            <td colspan="2">
                <div class="coupon coupon_copy">
                    <div class="flex-row medium-flex-wrap">
                        <div class="flex-col flex-grow">
                            <input type="text" class="input-text coupon_code_copy" placeholder="Coupon code"  value="">
                        </div>
                        <div class="flex-col">
                            <input type="submit" class="button expand apply_coupon_copy_submit"  value="Apply coupon">
                        </div>
                    </div>
                </div>
            </td>
            </tr>
            ';
    
        }
    }

    add_action( 'woocommerce_review_order_after_cart_contents', 'coupon_apply_copy', 10 );



    function load_resources_coupon_in_review(){

        if(is_checkout()) {         

            wp_enqueue_script( 'custom_coupon_in_review', plugin_dir_url( __FILE__ ) . 'custom.js', array(), '20171214', true );
            wp_enqueue_style( 'custom_coupon_in_review', plugin_dir_url( __FILE__ ) . 'custom.css', array(), '20171214', true );
            
        }   
    }

    add_action( 'wp_enqueue_scripts', 'load_resources_coupon_in_review', 2 );



?>