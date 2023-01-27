<?php 

add_action('plugins_loaded', 'woocommerce_d_local_boletos_gateway', 0);
function woocommerce_d_local_boletos_gateway() {
	if(!class_exists('WC_Payment_Gateway')) return;

	class DLocal_Boletos extends WC_Payment_Gateway {
	   function __construct() {
       	   	$this->id					= 'dlocal_boletos';
			$this->has_fields			= true;
			$this->method_title			= 'DLocal_boletos';
			$this->method_description	= 'Dlocal con boletos';
			$this->enabled	= $this->settings['woocommerce_dlocal_boletos_enabled'];

			$this->init_form_fields();
			$this->init_settings();

			$this->title = (isset($this->settings['title']) ? $this->settings['title'] : 'Dinheiro');

			if (version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=' )) {
                add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
             } else {
                add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
            }			
   		}




   		function init_form_fields() {
			$this->form_fields = array(				
				'enabled' => array(
                    'title' => __('Habilitar/Deshabilitar', 'dlocal_boletos'),
                    'type' => 'checkbox',
                    'label' => __('Habilita la pasarela de pago dlocal_boletos', 'dlocal_boletos'),
                    'default' => 'no'),
 				'title' => array(
                    'title' => __('Título', 'dlocal_boletos'),
                    'type'=> 'text',
                    'description' => __('Título que el usuario verá durante checkout.', 'dlocal_boletos'),
                    'default' => __('Boletos', 'dlocal_boletos')),				
                );
		}


        public function admin_options() {
			echo '<h3>'.__('DLocal Boletos', 'd_local').'</h3>';
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

			$amount = number_format(($order->get_total()),2,'.','');
			$full_name = $order->billing_first_name . ' ' . $order->billing_last_name;
			$currency = get_woocommerce_currency();
			$return_url = $cred['response_page'];
			$confirmation_url = $cred['confirmation_page'];
			$type = isset($_POST['billing_dlocal_payment_method']) ? $_POST['billing_dlocal_payment_method'] : 'VI';
			$user_id = ($order->get_customer_id() ? $order->get_customer_id() : uniqid());

			$woo_country = wc_get_base_location()['country'];
			if($currency != 'USD' && $woo_country != $billing_country){
				$resp = $aps->get_exchange(wc_get_base_location()['country'], 1);
				$amount = $amount / $resp;
				$currency = 'USD';
			}

			$response = $aps->newinvoice($order->id, $amount, $type, $order->billing_country,
			              $user_id, $order->billing_company, $full_name, $order->billing_email, $currency, '', '', '', '', '', '',
			              $return_url, $confirmation_url);

			
			if(WP_DEBUG_LOG === true) error_log("DLOCAL - CALL DLOCAL BOLETO -> Order:" . $order_id . ' - Amount:' . $amount . ' - Type:' . $type . ' - Country:' . $billing_country . ' - User:' . $user_id . ' - CPF:' . $billing_company . ' - Name:' . $full_name . ' - Email:' . $billing_email . ' - Currency:' . $currency);

			$decoded_response = json_decode($response);

			if(WP_DEBUG_LOG === true) error_log("DLOCAL - RESPONSE -> " . $response);
			/* --------- GENERAR REDIRECCION --------------*/						

			if ($decoded_response->status == 0) {
				$woocommerce->cart->empty_cart();
				$url = $decoded_response->link;
				return array('result' => 'success', 'redirect' => $url,'messages' => $decoded_response);
			}else{
				$error = $decoded_response->desc; 
				$param = '?code_error=' . urlencode($error) . '&order_id=' . $order_id;
				return array('result' => 'success', 'redirect' => $woocommerce->cart->get_checkout_url() . $param );
			}	

		}




        public function payment_fields(){
			global $woocommerce;

			$dl = new DLocal_Bancos;
			
			$cred = $dl->get_dlocal_settings();

   			$country = $woocommerce->session->customer['country'];
   			$cart_key = key($woocommerce->cart->get_cart());
   			$amount = $woocommerce->cart->get_cart()[$cart_key]['line_total'];
   			
   			$aps = new dLocalStreamline(($cred['test'] == 'yes' ? true : false));
			$aps->init_credencials($cred['x_login'], $cred['x_trans_key'], $cred['x_login_for_webpaystatus'], $cred['x_trans_key_for_webpaystatus'], $cred['secret_key'], ($cred['test'] == 'yes' ? true : false));



            if ( $description = $this->get_description() ) {
                echo wpautop( wptexturize( $description ) );
            }

            $ar = array();
            $pays = json_decode($aps->get_banks_by_country($country, 'json'));
            foreach ($pays as $i => $p){
            	switch ($p->payment_type) {
            		case '02': array_push($ar, $p); break;
            	}
            }
            //unset($ar[1]);unset($ar[2]);unset($ar[3]);unset($ar[4]);unset($ar[5]);
           	if(empty($ar)){
           		echo '<h4>Não há métodos de pagamento disponíveis</h4>';
           	}else{

            ?>	
            	<style type="text/css">
            		.aclaracion_boletos div{width: 100%;}
            	</style>
            	<div class="aclaracion_boletos">
            		<!--<div class="img_boletos"><img src="https://image.flaticon.com/icons/png/512/54/54253.png"></div>-->
            		<div class="aclaracion_text_1">1 - Boleto (somente á vista)</div>
            		<div class="aclaracion_text_2">2 - Pagamentos com Boleto Bancário levam até 3 dias úteis para serem compensados e entäo terem os produtos liberados</div>
            		<div class="aclaracion_text_3">3 - Depois do pagamento, fique atento ao seu e-mail para receber os dados de acesso ao produto (verifique também a caixa de SPAM)</div>
            	</div>
            	<?php if(false) : ?>
		        <div style="width: 100%;">
		        	<?php foreach ($ar as $i => $p) : ?>
		        		<div class="container_img" data-pay="<?= $p->code ?>">
		        			<img src="<?= $p->logo ?>" width="80px" height="70px" 
		        			class="dlocal_img">
		        		</div>
		        	<?php endforeach; ?>
		        <div>
		        <?php endif; ?>

		        <?php if(count($ar) == 1){ ?>
		        	<input type="hidden" class="unique_type" value="<?= $ar[0]->code ?>" data-hidden-body="false"> 
		        <?php }else{ ?>
			        <div style="width: 100%;">
						<select class="select_boleto select_dlocal">
				        	<option selected="selected" value="">Selecione uma opção</option>
				        	<?php foreach ($ar as $i => $p) : ?>
				        		<option value="<?= $p->code ?>">
				        			<?= $p->name ?>
				        		</option>
				        	<?php endforeach; ?>
			        	</select>
			        <div>
		    	<?php } ?>

		    <?php
			}

        }


	}
	

}