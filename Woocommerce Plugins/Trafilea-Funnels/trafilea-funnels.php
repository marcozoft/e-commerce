<?php
/*
Plugin Name: Trafilea funnels for Woocommerce
Plugin URI: http://trafilea.com
Description: Plugin propio de TRAFILEA para la implementacion de funnels de venta
Version: 2.0
Author: Marcos S. Vallejos
Domain Path: /languages
*/


// CARGAMOS ARCHIVOS DE LENGUAGE
function trafilea_funnels_load_plugin_textdomain() {
    load_plugin_textdomain( 'trafilea-funnels', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
}
add_action( 'init', 'trafilea_funnels_load_plugin_textdomain' );


/* ---------------  EVITAMOS CARRO DE COMPRAS y ENVIAMOS DIRECTAMENTE A CHECKOUT  ------------------- */
add_filter('add_to_cart_redirect', 'redirect_to_checkout');

function redirect_to_checkout()
{
    global $woocommerce;

    // REDIRIJIMOS A CHECKOUT SIN PASAR POR CARRO DE COMPRAS
    $checkout_url = $woocommerce->cart->get_checkout_url();
    return $checkout_url;
}

/* ---------------  LIMPIAMOS CARRITO DE COMPRAS AL PRESIONAR COMPRAR  ------------------- */
add_filter('woocommerce_add_cart_item_data', 'wdm_empty_cart', 10, 3);
function wdm_empty_cart($cart_item_data, $product_id, $variation_id)
{
    global $woocommerce;
    if (!isset($_GET['not_empty'])) {
        $woocommerce->cart->empty_cart();
    }

    // Do nothing with the data and return
    return $cart_item_data;
}

/* ---------------  CAMBIAMOS NOMBRES DE BOTON 'AÑADIR A CARRO'  ------------------- */
add_filter('woocommerce_product_add_to_cart_text', 'custom_woocommerce_product_add_to_cart_text');
add_filter('woocommerce_product_single_add_to_cart_text', 'custom_woocommerce_product_add_to_cart_text');

function custom_woocommerce_product_add_to_cart_text()
{
    global $product;

    $product_type = $product->product_type;

    switch ($product_type) {
        case 'external':
            return __('Comprar producto', 'woocommerce');
            break;
        case 'grouped':
            return __('Ver productos', 'woocommerce');
            break;
        case 'simple':
            return __('Comprar', 'woocommerce');
            break;
        case 'variable':
            return __('Seleccionar opciones', 'woocommerce');
            break;
        default:
            return __('Leer mas', 'woocommerce');
    }

}

// Add cart menu Item if on mobile
/*add_filter( 'wp_nav_menu_items', 'woo_mobile_menu_item');
function woo_mobile_menu_item() {
    return '';
}*/

/* ---------------  ELIMINAMOS LABEL DE 'PRODUCTO AÑADIDO AL CARRO DE COMPRAS'  ------------------- */
add_filter('wc_add_to_cart_message', 'empty_wc_add_to_cart_message', 10, 2);
// define the wc_add_to_cart_message
function empty_wc_add_to_cart_message($message, $product_id)
{
    return '';
}

function add_query_vars_filter($vars)
{
    $vars[] = "quantity";
    return $vars;
}

add_filter('query_vars', 'add_query_vars_filter');

// AGREGAMOS LISTADO DE DESCUENTOS POR CANTIDAD PARA EL PRODUCTO
add_action('woocommerce_checkout_quality_discounts', 'wps_add_select_checkout_field');
function wps_add_select_checkout_field($checkout)
{
    global $woocommerce;
    $woocommerce->cart->calculate_shipping();
    $items = $woocommerce->cart->get_cart();
    $enabled = false;
    $arreglo_items = array();
    $key_cart = key($items);
    $quality_current = $items[$key_cart]['quantity'];
    $money = get_woocommerce_currency();
    $symbol_money = get_woocommerce_currency_symbol($money);
    //foreach ( $woocommerce->cart->cart_contents as $cart_item_key => $values ) {var_dump($values['data']);}
    /* ------ RECOPILAMOS INFO DE DESCUENTOS -------------- */
    foreach ($items as $item => $values) {
        $_product = $values['data']->post;

        /* ------ AÑADIMOS ACTUAL -------------- */
        //$item = array('product_id' => $values['product_id'], 'quantity' => $values['quantity'], 'discount' => 0, 'price' => $values['line_total'], 'name' => $_product->post_title);
       //array_push($arreglo_items, $item);

        $enabled = get_post_meta($values['product_id'], '_bulkdiscount_enabled', true);
        if ($enabled) $enabled = true;

        $cont = 1;
        $while_cond = true;
        while ($while_cond) {
            $quantity = get_post_meta($values['product_id'], '_bulkdiscount_quantity_' . $cont, true);
            $discount = get_post_meta($values['product_id'], '_bulkdiscount_discount_' . $cont, true);

            if ($quantity != '') {
                //if ($quantity >= $values['quantity']) {
                    $price = get_post_meta($values['product_id'], '_price', true);
                    $item = array('product_id' => $values['product_id'], 'quantity' => $quantity, 'discount' => $discount, 'price' => $price, 'name' => $_product->post_title);
                    array_push($arreglo_items, $item);
                //}
            } else {
                $while_cond = false;
            }

            $cont++;
        }
    }

    /* ------ IMPRIMIMOS DESCUENTOS -------------- */
    if ($enabled && count($arreglo_items) != 0) {
        $plugin_url = plugin_dir_url(__FILE__);
        echo '<h2>' . __('Select the desired amount','trafile-funnels') . '</h2>';

        echo $quantity = get_query_var('quantity');
        echo '<link rel="stylesheet" href="' . $plugin_url . 'css/trafilea-funnels.css" type="text/css" />';
        
        echo '
				<table class="discount">
					<thead>
						<tr>
							<th class="product-total">Quantity</th>
							<th class="product-total">Price</th>
						</tr>
					</thead>
                    <tbody>';

        foreach ($arreglo_items as $prod) {
            $price = $prod['price'];
            $descuento = ($price * $prod['discount']) / 100;
            $unidad = $price - $descuento;
            $total = number_format($unidad * $prod['quantity'], 2);

            $checked = '';
            if ($prod['quantity'] == $quality_current) {
                $checked = 'checked';
            }

            //$url = get_site_url() . '/checkout/?add-to-cart=' . $prod['product_id'] . '&quantity=' . $prod['quantity'];
            //$url = get_site_url() . '/wp/?add-to-cart=' . $prod['product_id'] . '&quantity=' . $prod['quantity'];

            echo '<tr class="cart_item'. ($checked != '' ? ' quantity_current' : '') .'" 
                    data-product_id="'.$prod['product_id'].'" data-quantity="'.$prod['quantity'].'">
                    <td class="product-total">                                                                        
                        <input type="radio" name="js_selectamount" ' . $checked . ' id="checkbox_'.$prod['quantity'].'"/>
                        <label for="checkbox_'.$prod['quantity'].'">
                        '.$prod['quantity'].' '.$prod['name'].' ('.$symbol_money.' ' . number_format($unidad, 2) . '/unit) - '. $prod['discount'].'% OFF
                        </label>
                    </td>
                    <td class="product-total">
                        <label class="woocommerce-Price-amount amount" for="checkbox_'.$prod['quantity'].'">
                            
                            <strong>'.$symbol_money. ' ' . $total . ' ' . $money. '</strong><br>
                        </label>						
                    </td>
                </tr>
                ';
        }

        echo '</tbody>
                </table>';
    }

}


function add_jscript_cart_item() {
    $exist_dlocal_plugin = class_exists( 'DLocal_Bancos' );
?>
        <script type="text/javascript">
                jQuery(document).ready(function ($) {
                     //$("input[name=js_selectamount]").click(function () {});
                     var recharge_payment = <?= $exist_dlocal_plugin ? 'true' : 'false' ?>;
                     
                     $("body").on("click", ".cart_item", function () {
                        data = "&action=add_to_cart_funnel";
                        data += "&quantity="+$(this).data("quantity")+"&product_id="+$(this).data("product_id");
                        $.post("<?= home_url( '/wp-admin/admin-ajax.php' ) ?>", data, function(response) {

                            if(response != "ERROR"){
                                if(response.slice(-1) == 0){
                                    response = response.slice(0, -1);
                                }                                
                                var datos = JSON.parse(response);
                                //console.log(datos);
                                //if(!recharge_payment){
                                    $(".price_total span").html(datos.total);
                                    $(".price_shipping span").html(datos.shipping);
                                    $(".price_product span").html(datos.amount);
								    $(".quantity_product").html(datos.quality);
								
                                //}
                            }
                            if(recharge_payment){
                                $( "#billing_country" ).trigger("change"); 
                            }
                        });
                        $(".cart_item").removeClass("quantity_current");
                        $(this).addClass("quantity_current");
                        $(this).find("input").prop("checked", true);
                        $("#billing_first_name").trigger("blur");
                        console.log("cambio de cantidad");
                     });
                });
        </script>
<?php
}
 
add_action( 'woocommerce_after_checkout_form', 'add_jscript_cart_item');





        // AJAX ACTUALIZAR CARRO 
        add_action("wp_ajax_add_to_cart_funnel", "add_to_cart_funnel");
        add_action("wp_ajax_nopriv_add_to_cart_funnel", "add_to_cart_funnel");

        function add_to_cart_funnel(){
           global $woocommerce;
           if(isset($_POST['quantity']) && isset($_POST['product_id'])){
                //echo $_POST['product_id'] .'--'.$_POST['quantity'] .' | ';
                $woocommerce->cart->empty_cart();
                $woocommerce->cart->add_to_cart($_POST['product_id'],$_POST['quantity']);
                
                $r = calculate_order_summary();

                echo json_encode($r);
           }else{
                echo 'ERROR';
           }
        }




        add_action("woocommerce_review_order_before_submit", "order_summary_funnel");

        function order_summary_funnel(){
            global $woocommerce;
            $r = calculate_order_summary();
		
        ?>
           <div id="order_summary" class="woocommerce-checkout-payment" style="margin-bottom: 2%;">

                <h2><?=  __('Order summary','trafilea-funnels') ?></h2>
                <span class="quantity_product"><?= $r['quality'] ?></span>
                <span class="label_product"><?= $r['name'] ?></span>
                <span class="price_product" style="float: right;"><?= $r['symbol_money'] . ' <span>' . $r['amount'] . '</span> ' . $r['money'] ?></span><br>
                <span class="label_shipping"><?= __("Shipping", "woocommerce") ?></span>
                <span class="price_shipping" style="float: right;"><?= $r['symbol_money'] . ' <span>' . $r['shipping'] . '</span> ' . $r['money'] ?></span>
                <hr>
                <span class="label_total" style="font-weight: bold;"><?= __("Total", "woocommerce") ?></span>
                <span class="price_total" style="float: right;font-weight: bold;">
                    <?= $r['symbol_money'] . ' <span>' . $r['total'] . '</span> ' . $r['money'] ?>
                </span>
            </div>
        <?php
        }



function calculate_order_summary(){
            global $woocommerce; 
            define( 'WOOCOMMERCE_CHECKOUT', true );
            define( 'WOOCOMMERCE_CART', true );
            $woocommerce->cart->calculate_shipping();
            $woocommerce->cart->calculate_totals();
            $items = $woocommerce->cart->get_cart();
            $key_cart = key($items);
            $quality = $items[$key_cart]['quantity'];
            $price = $woocommerce->cart->subtotal;
            $name = $items[$key_cart]['data']->name;
            $money = get_woocommerce_currency();
            $symbol_money = get_woocommerce_currency_symbol($money);
            $shipping = $woocommerce->cart->shipping_total;
            
            $content = $woocommerce->cart->cart_contents;
            $key_recurring = key($content);
            
            if($content[$key_recurring]['data']->subscription_sign_up_fee){
                //var_dump($content[$key_recurring]['data']->subscription_sign_up_fee);
                $shipping = $content[$key_recurring]['data']->subscription_sign_up_fee;
                $price = 0;
            }


            return ["amount" => number_format($price,2), "quality" =>  $quality, "shipping" => number_format($shipping,2), "total" => number_format(($price + $shipping),2), "symbol_money" => $symbol_money, "money" => $money, "name" => $name, "quality" => $quality];

            
}




/**
 * Hide shipping rates when free shipping is available.
 * Updated to support WooCommerce 2.6 Shipping Zones.
 *
 * @param array $rates Array of rates found for the package.
 * @return array
 */
function my_hide_shipping_when_free_is_available( $rates ) {
    $free = array();
    foreach ( $rates as $rate_id => $rate ) {
        if ( 'free_shipping' === $rate->method_id ) {
            $free[ $rate_id ] = $rate;
            break;
        }
    }
    return ! empty( $free ) ? $free : $rates;
}
add_filter( 'woocommerce_package_rates', 'my_hide_shipping_when_free_is_available', 100 );











// Redirigimos a la home si el carro esta vacio en el checkout
add_action( 'wp_head', 'cart_empty_redirect_to_home' );
function cart_empty_redirect_to_home() {
    global $woocommerce;

    if( is_checkout() && 0 == sprintf(_n('%d', '%d', $woocommerce->cart->cart_contents_count, 'woothemes'), $woocommerce->cart->cart_contents_count) && !isset($_GET['key']) ) {
        wp_redirect( home_url() );  exit;
    }
}



?>
