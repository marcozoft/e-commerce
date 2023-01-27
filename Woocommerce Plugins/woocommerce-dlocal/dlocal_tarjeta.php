<?php 

add_action('plugins_loaded', 'woocommerce_d_local_tarjeta_gateway', 0);
function woocommerce_d_local_tarjeta_gateway() {
	if(!class_exists('WC_Payment_Gateway')) return;
	class DLocal_Tarjetas extends WC_Payment_Gateway {
	   function __construct() {
       	   	$this->id					= 'dlocal_tarjeta';
       	   	//$this->icon					= apply_filters('woocomerce_dlocal_icon', plugins_url('/img/metodos_tarjeta.png', __FILE__));
			$this->has_fields			= true;
			$this->method_title			= 'DLocal_tarjeta';
			$this->method_description	= 'Dlocal con tarjetas de credito';
			
			$this->init_form_fields();
			$this->init_settings();

			$this->title = (isset($this->settings['title']) ? $this->settings['title'] : 'Cartão de crédito');

			if (version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=' )) {
                add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
             } else {
                add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
            }			
   		}
   		function init_form_fields() {
			$this->form_fields = array(				
				'enabled' => array(
                    'title' => __('Habilitar/Deshabilitar', 'dlocal_tarjeta'),
                    'type' => 'checkbox',
                    'label' => __('Habilita la pasarela de pago dlocal_tarjeta', 'dlocal_tarjeta'),
                    'default' => 'no'),
 				'title' => array(
                    'title' => __('Título', 'dlocal_tarjeta'),
                    'type'=> 'text',
                    'description' => __('Título que el usuario verá durante checkout.', 'dlocal_tarjeta'),
                    'default' => __('Tarjetas de credito', 'dlocal_tarjeta')),				
                );
		}





        public function payment_fields(){
        	global $woocommerce;
        	if($woocommerce->cart->total > 0){
	        	$url = Get_Iframe_DLocal(true);
				
			    //echo '<script type="text/javascript" src="https://code.jquery.com/jquery-1.11.1.min.js">jQuery.noConflict();</script>';
		       
		       if($url && strpos($url, 'woocommerce-error') == false){
				echo '<iframe id="iframeDL" style="width: 100%;" height="280" frameBorder="0" src="'.$url.'&iframe_view=1"></iframe>';
		       }else{
				echo '<iframe id="iframeDL" style="width: 100%;display: none;" height="280" frameBorder="0"></iframe>';
		       }
							
				echo '<h4 id="cargando_tarjetas">Você deve completar os dados do formulário...</h4>';	
        	}
		}



        public function admin_options() {
			echo '<h3>'.__('DLocal Tarjeta', 'd_local').'</h3>';
			echo '<table class="form-table">';
			$this -> generate_settings_html();
			echo '</table>';
		}



		function process_payment($order_id) {
			global $woocommerce;
			

			$dl = new DLocal_Bancos;			
			$cred = $dl->get_dlocal_settings();

			/* --------- GENERAR REDIRECCION --------------*/
			$order = new WC_Order( $order_id );
			$aps = new dLocalStreamline(($cred['test'] == 'yes' ? true : false));
			$aps->init_credencials($cred['x_login'], $cred['x_trans_key'], $cred['x_login_for_webpaystatus'], $cred['x_trans_key_for_webpaystatus'], $cred['secret_key'], ($cred['test'] == 'yes' ? true : false));

			$amount = number_format(($order -> get_total()),2,'.','');
			$full_name = $order->billing_first_name . ' ' . $order->billing_last_name;
			$currency = get_woocommerce_currency();
			$return_url = $cred['response_page'];
			$confirmation_url = $cred['confirmation_page'];
			//$type = isset($_POST['billing_dlocal_payment_method']) ? $_POST['billing_dlocal_payment_method'] : 'VI';
			$type =  'VI';
			$user_id = ($order->get_customer_id() ? $order->get_customer_id() : uniqid());

			$response = $aps->newinvoice($order->id, $amount, $type, $order->billing_country,
			              $user_id, $order->billing_company, $full_name, $order->billing_email, $currency, '', '', '', '', '', '',
			              $return_url, $confirmation_url);
			/*
			$mj = $cred['x_login'] . ' | ' .  $cred['x_trans_key'] . ' | ' .  $cred['x_login_for_webpaystatus'] . ' | ' .  $cred['x_trans_key_for_webpaystatus'] . ' | ' .  $cred['secret_key'] . ' | ' .  $cred['test'];
			
			$mj .= ' ||| ' . $order->id . ' | ' .  $amount . ' | ' . $type . ' | ' . $order->billing_country . ' | ' . $user_id . ' | ' . $order->billing_company . ' | ' . $full_name . ' | ' . $order->billing_email . ' | ' . $currency . ' | ' . 
			              $return_url . ' | ' . $confirmation_url;
			*/
			$decoded_response = json_decode($response);

			/* --------- GENERAR REDIRECCION --------------*/						

			if ($decoded_response->status == 0) {
				$woocommerce->cart->empty_cart();
				$url = $decoded_response->link;
				return array('result' => 'success', 'type' => 'dlocal', 'result_dlocal' => 'success', 'redirect' => $url,'messages' => $decoded_response, 'order_id' => $order_id);
			}else{
				$error = $decoded_response->desc; 
				$param = '?code_error=' . urlencode($error) . '&del=' . $type;
				return array('result' => 'success', 'type' => 'dlocal', 'result_dlocal' => 'failed', 'redirect' => $woocommerce->cart->get_checkout_url() . $param );
			}	



		}	

	}

}