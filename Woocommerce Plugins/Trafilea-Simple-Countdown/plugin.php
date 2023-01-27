<?php
/*
Plugin Name: Trafilea Simple CountDown ShortCode
Plugin URI: http://trafilea.com
Description: Plugin propio de TRAFILEA para mostrar una cuenta regresiva a partir de un shortcode. Ejemplo de uso: [countdown_line minutes=5 product_id=339 before_label="OFFER EXPIRES IN" expire_label="!"  expire_label="Time is up!"]
Version: 1.0
Author: Marcos S. Vallejos
*/

add_shortcode('countdown_line', 'countdown_trafilea');

function countdown_trafilea($atts){
    
    if(isset(WC()->session)) {

        extract( shortcode_atts( array(
            'minutes' => 3,
            'before_label' => 'OFFER EXPIRES IN ',
            'after_label' => '!',
            'expire_label' => 'Time is up!',
            'product_id' => uniqid(),
        ), $atts, 'countdown' ) );    

        
        $label = $before_label . '<span>%2</span>' . $after_label;
        

        $key_cart = 'TCD_' . $product_id;

        $time = WC()->session->get($key_cart);
        
        if(!$time) {
            $date_of_expiry = time() + (60 * $minutes);
            WC()->session->set( $key_cart, $date_of_expiry );
            $time = $date_of_expiry;
        }

        if($time < time()){
            $time = 1;  
            WC()->session->set($key_cart,'');
        }else{
            $time = $time - time(); 
        }
        
        return '
            <script type="text/javascript">
            jQuery.noConflict();

            function fancyTimeFormat(time)
            {   
                // Hours, minutes and seconds
                var hrs = ~~(time / 3600);
                var mins = ~~((time % 3600) / 60);
                var secs = time % 60;

                // Output like "1:01" or "4:03:59" or "123:03:59"
                var ret = "";

                if (hrs > 0) {
                    ret += "" + hrs + ":" + (mins < 10 ? "0" : "");
                }

                ret += "" + mins + ":" + (secs < 10 ? "0" : "");
                ret += "" + secs;
                return ret;
            }

            var count='. $time . ';
            var label="'. $label .'";
            var label_expire="'. $expire_label .'";

            var counter=setInterval(timer, 1200); //1000 will  run it every 1 second

            function timer()
            {

              count=count-1;
              var msj = label.replace("%2", fancyTimeFormat(count));
              jQuery("#tcd_timer_'. $key_cart .'").html(msj);
              if (count <= 0)
              {
                 clearInterval(counter);
                 jQuery("#tcd_timer_'. $key_cart . '").html(label_expire);
                 return;
              }

              //Do code for showing the number of seconds here
            }       
        </script>
        <span class="tcd_timer" id="tcd_timer_'. $key_cart . '"></span>';

    }
}


?>