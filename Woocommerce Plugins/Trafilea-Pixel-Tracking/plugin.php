<?php
/*
Plugin Name: Trafilea Pixel Tracking
Plugin URI: http://trafilea.com
Description: Plugin propio de TRAFILEA para la implementacion de pixeles de trackeos en cada pagina
Version: 1.0
Author: Marcos S. Vallejos
*/


 add_action( 'add_meta_boxes', 'add_tracking_meta_boxes');


 function add_tracking_meta_boxes( $post_type ) {

    add_meta_box( 'tracking_boxes_metabox' , __( 'Tracking pixels' ) , 'tracking_boxes_metabox' , 'page' , 'normal');

}

function tracking_boxes_metabox( $post ) {
    ?>
    
    
    <h4><?php // _e( 'URL Tracking Pixel' ); ?></h4>
    <?php woocommerce_wp_text_input( array( 'placeholder' => 'http://tracking.url/sample?param=1', 'id' => '_url_tracking', 'type' => 'url' ,'label' => __( 'URL Tracking Pixel', 'woocommerce' ) ) ); ?>
    


<?php

}



add_action( 'save_post', function($post_id) {
     

    if ( isset( $_POST['_url_tracking'] ) ) {
        
        $_url_tracking = '';
        
        if( $_POST['_url_tracking'] !== '' ) {

            $_url_tracking = htmlspecialchars( $_POST['_url_tracking'] );
            
        }
        
        update_post_meta( $post_id, '_url_tracking', $_url_tracking );
        
    }
   
}); 



function add_script_tracking_footer_function() {
    global $post;
    if(is_page($post->ID)){
        $url = get_post_meta($post->ID, '_url_tracking' , true );
        echo '<script type="text/javascript">var xhttp = new XMLHttpRequest();xhttp.open("GET", "'.$url.'", true);xhttp.send();</script>';
    }
}
add_action( 'wp_footer', 'add_script_tracking_footer_function');

?>