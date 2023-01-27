<?php


require_once 'lib/dLocalStreamline.php';




add_action('plugins_loaded', 'woocommerce_d_local_gateway', 0);
function woocommerce_d_local_gateway() {
	if(!class_exists('WC_Payment_Gateway')) return;
	
	class WC_d_local extends WC_Payment_Gateway {
	
		/**
		 * Constructor de la pasarela de pago
		 *
		 * @access public
		 * @return void
		 */
		public function __construct(){
			$this->id					= 'dlocal';
			$this->icon					= apply_filters('woocomerce_dlocal_icon', plugins_url('/img/dlocal.png', __FILE__));
			$this->has_fields			= true;
			$this->method_title			= 'DLocal';
			$this->method_description	= 'Integración de Woocommerce a la pasarela de pagos de DLocal';

			$this->init_form_fields();
			$this->init_settings();
			
			$this->title = $this->settings['title'];
			$this->x_login = $this->settings['x_login'];
			$this->x_trans_key = $this->settings['x_trans_key'];
			$this->secret_key = $this->settings['secret_key'];

			$this->x_login_for_webpaystatus = $this->settings['x_login_for_webpaystatus'];
			$this->x_trans_key_for_webpaystatus = $this->settings['x_trans_key_for_webpaystatus'];

			$this->gateway_url = $this->settings['gateway_url'];
			$this->test = $this->settings['test'];
			$this->response_page = $this->settings['response_page'];
			$this->confirmation_page = $this->settings['confirmation_page'];
			
			if (version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=' )) {
                add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
             } else {
                add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
            }
			add_action('woocommerce_receipt_dlocal', array(&$this, 'receipt_page'));
		}

		

        public function payment_fields(){
			global $woocommerce;
			
			//wp_enqueue_script('jquery-1.11.1', 'http://code.jquery.com/jquery-1.11.1.min.js');

   			$country = $woocommerce->session->customer['country'];
   			$cart_key = key($woocommerce->cart->get_cart());
   			$amount = $woocommerce->cart->get_cart()[$cart_key]['line_total'];
   			
			$aps = new dLocalStreamline();
			$aps->init_credencials($this->x_login, $this->x_trans_key, $this->x_login_for_webpaystatus, $this->x_trans_key_for_webpaystatus, $this->secret_key, $this->test);

            if ( $description = $this->get_description() ) {
                echo wpautop( wptexturize( $description ) );
            }

            $cc = $bank = $bol = array();
            $pays = json_decode($aps->get_banks_by_country($country, 'json'));
            foreach ($pays as $i => $p){
            	switch ($p->payment_type) {
            		case '01': array_push($bank, $p); break;
            		case '02': array_push($bol, $p); break;
            		case '03': array_push($cc, $p); break;
            	}
            }

    ?>
    	<script type="text/javascript" src="http://code.jquery.com/jquery-1.11.1.min.js"></script>
        <style>
            .dlocal_img{max-height: 5em !important; vertical-align: middle; display: inline-block;}
            .pay_select{border: 7px solid #73AD21 !important;background-color: #73AD21;}
            	
            .container_img{    display: inline-block;width: 80px;height: 80px; text-align: center; cursor: pointer; padding: 3%;}
            .container_img:before {	    content: ' ';vertical-align: middle;}
            #dlocal-error-message{display:block;color:red;}
            #dlocal_iframe{display:none;}
            /*#payment{display:none;}*/
        </style>
        
		<ul id="dlocal_pagos">

			<?php if($cc) : ?>
			<li class="wc_payment_method payment_method_dlocal">
				<input id="credit_card" class="input-radio-dlocal" name="payment_method" value="dlocal" checked="checked" data-order_button_text="" type="radio">

				<label for="credit_card">
					Tarjeta de credito 	
				</label>


				<div class="boxes payment_box_credit_card" style="display: block;">
					<iframe id="iframeDL" style="width: 100%;display: none;" height="280" frameBorder="0"></iframe>
					<?php $primero = ''; ?>
					<?php if(false) : ?>
                    <div style="width: 100%;">
	                    <?php foreach ($cc as $i => $p) : ?>
	                    	<?php if($i == 0) $primero = $p->code; ?>
	                    	<div class="container_img" data-pay="<?= $p->code ?>">
		                   		<img src="<?= $p->logo ?>" width="80px" height="70px" 
		                   			class="dlocal_img <?= ''//$primero == $p->code ? 'pay_select' : '' ?>">
		                   	</div>
	                    <?php endforeach; ?>
                    <div>
                	<?php endif; ?>
				</div>		
			</li>
			<?php endif; ?>

			<?php if($bank) : ?>
			<li class="wc_payment_method payment_method_dlocal">
				<input id="bank" class="input-radio-dlocal" name="payment_method" value="dlocal" data-order_button_text="" type="radio">

				<label for="bank">
					Bancos 
				</label>

				<div class="boxes payment_box_bank" style="display: none;">
					<?php $primero = ''; ?>
                    <div style="width: 100%;">
	                    <?php foreach ($bank as $i => $p) : ?>
	                    	<?php if($i == 0) $primero = $p->code; ?>
	                    	<div class="container_img" data-pay="<?= $p->code ?>">
		                   		<img src="<?= $p->logo ?>" width="80px" height="70px" 
		                   			class="dlocal_img <?= ''//$primero == $p->code ? 'pay_select' : '' ?>">
		                   	</div>
	                    <?php endforeach; ?>
                    <div>
				</div>			
			</li>
			<?php endif; ?>

			<?php if($bol) : ?>
			<li class="wc_payment_method payment_method_dlocal">
				<input id="boleto" class="input-radio-dlocal" name="payment_method" value="dlocal" data-order_button_text="" type="radio">

				<label for="boleto">
					Boletos
				</label>

				<div class="boxes payment_box_boleto" style="display: none;">
					<?php $primero = ''; ?>
                    <div style="width: 100%;">
	                    <?php foreach ($bol as $i => $p) : ?>
	                    	<?php if($i == 0) $primero = $p->code; ?>
	                    	<div class="container_img" data-pay="<?= $p->code ?>">
		                   		<img src="<?= $p->logo ?>" width="80px" height="70px" 
		                   			class="dlocal_img <?= '' //$primero == $p->code ? 'pay_select' : '' ?>">
		                   	</div>
	                    <?php endforeach; ?>
                    <div>
				</div>			
			</li>	
			<?php endif; ?>
		</ul>
		<input type="hidden" value="<?= $primero ?>" name="dlocal_payment_method" id="dlocal_payment_method">
		<!--
		<div id="dlocal_iframe">
			<a href="#" id="dlocal_regresar">Regresar a medios de pagos</a>
			<iframe src="" style="width: 100%;" height="250" frameBorder="0"></iframe>
		</div>
		-->
     
	<?php
		}



        public function payment_fields_ANT(){
			global $woocommerce;
			
   			$country = $woocommerce->session->customer['country'];

			$aps = new dLocalStreamline();
			$aps->init_credencials($this->x_login, $this->x_trans_key, $this->x_login_for_webpaystatus, $this->x_trans_key_for_webpaystatus, $this->secret_key, $this->test);

            if ( $description = $this->get_description() ) {
                echo wpautop( wptexturize( $description ) );
            }

            $pays = json_decode($aps->get_banks_by_country($country, 'json'));
			
            ?>
            <style>
            	.dlocal_img{max-height: 5em !important; vertical-align: middle; display: inline-block;}
            	.pay_select{border: 7px solid #73AD21 !important;background-color: #73AD21;}
            	
            	.container_img{    display: inline-block;width: 80px;height: 80px; text-align: center; cursor: pointer; padding: 3%;}
            	.container_img:before {	    content: ' ';vertical-align: middle;}
            </style>
            <div id="custom_input">
                <p class="form-row form-row-wide">
                    <label for="mobile" class=""><strong><?php _e('Elija la forma de pago', $this->domain); ?></strong></label>
                    <br><br>
                    <?php $primero = ''; ?>
                    <div style="width: 100%;">
	                    <?php foreach ($pays as $i => $p) : ?>
	                    	<?php if($i == 0) $primero = $p->code; ?>
	                    	<div class="container_img" data-pay="<?= $p->code ?>">
		                   		<img src="<?= $p->logo ?>" width="80px" height="70px" 
		                   			class="dlocal_img <?= $primero == $p->code ? 'pay_select' : '' ?>">
		                   	</div>
	                    <?php endforeach; ?>
                    <div>
                    <input type="hidden" value="<?= $primero ?>" name="dlocal_payment_method" id="dlocal_payment_method">
                </p>
                
            </div>
            <script type="text/javascript">
            	$('.container_img').on('click',function(){
            		$('.dlocal_img').removeClass('pay_select');
            		$(this).find('img').addClass('pay_select');
            		$('#dlocal_payment_method').val($(this).data('pay'));

            		$.post('/wp-admin/admin-ajax.php', { action: "Get_Iframe_DLocal", pay_type: $(this).data('pay')}, function(response) {
        				alert(response);           
    				});
            	});
            </script>
            <?php
        }



		
		/**
		 * Funcion que define los campos que iran en el formulario en la configuracion
		 * de la pasarela de DLocal
		 *
		 * @access public
		 * @return void
		 */
		function init_form_fields() {
			$this->form_fields = array(
				'enabled' => array(
                    'title' => __('Habilitar/Deshabilitar', 'd_local'),
                    'type' => 'checkbox',
                    'label' => __('Habilita la pasarela de pago DLocal', 'd_local'),
                    'default' => 'no'),
                'title' => array(
                    'title' => __('Título', 'd_local'),
                    'type'=> 'text',
                    'description' => __('Título que el usuario verá durante checkout.', 'd_local'),
                    'default' => __('DLocal', 'd_local')),
                'x_login' => array(
                    'title' => __('X Login', 'd_local'),
                    'type' => 'text',
                    'description' => __('Credencial de integracion x_login.', 'd_local')),
                'x_trans_key' => array(
                    'title' => __('X Trans Key', 'd_local'),
                    'type' => 'text',
                    'description' => __('Credencial de integracion x_trans_key', 'd_local')),
                'secret_key' => array(
                    'title' => __('Secret Key', 'd_local'),
                    'type' => 'text',
                    'description' => __('Credencial de integracion secret_key', 'd_local')),


                'x_login_for_webpaystatus' => array(
                    'title' => __('X Login WEBPAYSTATUS', 'd_local'),
                    'type' => 'text',
                    'description' => __('Credencial de integracion webpaystatus x_login_for_webpaystatus', 'd_local')),
                'x_trans_key_for_webpaystatus' => array(
                    'title' => __('X Trans Key WEBPAYSTATUS', 'd_local'),
                    'type' => 'text',
                    'description' => __('Credencial de integracion webpaystatus x_trans_key_for_webpaystatus', 'd_local')),                                    
                /*
                'gateway_url' => array(
                    'title' => __('Gateway URL', 'd_local'),
                    'type' => 'text',
                    'description' => __('URL de la pasarela de pago Dlocal.', 'd_local')),
                */
				'test' => array(
                    'title' => __('Transacciones en modo SandBox', 'd_local'),
                    'type' => 'checkbox',
                    'label' => __('Habilita las transacciones en modo de prueba.', 'd_local'),
                    'default' => 'no'),
                'response_page' => array(
                    'title' => __('Página de respuesta'),
                    'type' => 'text',
                    'description' => __('URL de la página mostrada después de finalizar el pago. No olvide cambiar su dominio.LA PAGINA DEBE EXISTIR.', 'd_local'),
					'default' => __('http://su.dominio.com/woocommerce-dlocal-response', 'd_local')),
                'confirmation_page' => array(
                    'title' => __('Página de confirmación'),
                    'type' => 'text',
                    'description' => __('URL de la página que recibe la respuesta definitiva sobre los pagos. No olvide cambiar su dominio. LA PAGINA DEBE EXISTIR.', 'd_local'),
                    'default' => __('http://su.dominio.com/woocommerce-dlocal-confirmation', 'd_local')),
			);
		}
		
		/**
         * Muestra el fomrulario en el admin con los campos de configuracion del gateway DLocal
		 * 
		 * @access public
         * @return void
         */
        public function admin_options() {
			echo '<h3>'.__('DLocal Payment Gateway', 'd_local').'</h3>';
			echo '<table class="form-table">';
			$this -> generate_settings_html();
			echo '</table>';
		}
		
		/**
		 * Atiende el evento de checkout y genera la pagina con el formularion de pago.
		 * Solo para la versiones anteriores a la 2.1.0 de WC
         *
         * @access public
         * @return void
		 */
		function receipt_page($order){
			$this -> generate_dlocal_buttom($order);
		}
		
		/**
		 * Construye un arreglo con todos los parametros que seran enviados al gateway de DLocal
         *
         * @access public
         * @return void
		 */
		/*
		public function get_params_post($order_id){
			global $woocommerce;
			$order = new WC_Order( $order_id );
			$currency = get_woocommerce_currency();
			$amount = number_format(($order -> get_total()),2,'.','');
			$signature = md5($this->secret_key . '~' . $this ->x_login . '~' . $order -> id . '~' . $amount . '~' . $currency );
			$description = "";
			$products = $order->get_items();
			foreach($products as $product) {
				$description .= $product['name'] . ',';
			}
                        
                        if (strlen($description) > 255){
                            $description = substr($description,0,240).' y otros...';                            
                        }
                        
			$tax = number_format(($order -> get_total_tax()),2,'.','');
			$taxReturnBase = number_format(($amount - $tax),2,'.','');
			if ($tax == 0) $taxReturnBase = 0;
			
			$test = 0;
			if($this->test == 'yes') $test = 1;
			
			$parameters_args = array(
				'merchantId' => $this->x_login,
				'referenceCode' => $order -> id,
				'description' => trim($description, ','),
				'amount' => $amount,
				'tax' => $tax,
				'taxReturnBase' => $taxReturnBase,
				'signature' => $signature,
				'accountId' => $this->x_trans_key,
				'currency' => $currency,
				'buyerEmail' => $order -> billing_email,
				'test' => $test,
				'confirmationUrl' => $this->confirmation_page,
				'responseUrl' => $this->response_page,
				'shippingAddress' => $order->shipping_address_1,
				'shippingCountry' => $order->shipping_country,
				'shippingCity' => $order->shipping_city,
				'billingAddress' => $order->billing_address_1,
				'billingCountry' => $order->billing_country,
				'billingCity' => $order->billing_city,
				'extra1' => 'WOOCOMMERCE'
			);
			return $parameters_args;
		}
		*/
		/**
		 * Metodo que genera el formulario con los datos de pago
         *
         * @access public
         * @return void
		 */
		public function generate_dlocal_buttom($order_id){			
			/*
			$parameters_args = $this->get_params_post($order_id);
			
			$payu_args_array = array();
			foreach($parameters_args as $key => $value){
				$payu_args_array[] = $key . '=' . $value;
			}
			$params_post = implode('&', $payu_args_array);

			$payu_args_array = array();
			foreach($parameters_args as $key => $value){
			  $payu_args_array[] = "<input type='hidden' name='$key' value='$value'/>";
			}
			*/
		
			$order = new WC_Order( $order_id );
			$aps = new dLocalStreamline();
			$aps->init_credencials($this->x_login, $this->x_trans_key, $this->x_login_for_webpaystatus, $this->x_trans_key_for_webpaystatus, $this->secret_key, $this->test);

			$amount = number_format(($order -> get_total()),2,'.','');
			$full_name = $order->billing_first_name . ' ' . $order->billing_last_name;
			$currency = get_woocommerce_currency();
			$return_url = $this->response_page;
			$confirmation_url = $this->confirmation_page;
			$type = $_GET['dlocal_payment_method'];

			$response = $aps->newinvoice($order->id, $amount, $type, $order->billing_country,
			              $order->user_id, $order->billing_company, $full_name, $order->billing_email, $currency, '', '', '', '', '', '',
			              $return_url, $confirmation_url);

			$decoded_response = json_decode($response);
						
			if ($decoded_response->status == 0) {
			    $url = $decoded_response->link;
			    echo '<p>'.__('Gracias por su pedido, de clic en el botón que aparece para continuar el pago con DLocal.', 'd_local').'</p>';
			    echo '<form action="'.$url.'" method="post" id="d_local_form">'
				. '<input type="submit" id="submit_d_local" value="' .__('Pagar', 'd_local').'" /></form>';
			} else { 
			    $error = $decoded_response->desc; 
			    echo '<ul class="woocommerce-error">
						<li><strong>Los datos ingresados no son correctos.</strong> 
							Por favor intentelo nuevamente
							<br> Codigo de error: ' . $error . '
						</li>
					</ul>';
			    echo '<input type="button" value="Volver" onclick="window.history.back()" /> ';
			}
			

		}
		
		/**
		 * Procesa el pago 
         *
         * @access public
         * @return void
		 */
		function process_payment($order_id) {
			global $woocommerce;
			//$order = new WC_Order($order_id);
			
			//$params_post = '&dlocal_payment_method=' . $_POST['dlocal_payment_method'];



			/* --------- GENERAR REDIRECCION --------------*/
			$order = new WC_Order( $order_id );
			$aps = new dLocalStreamline();
			$aps->init_credencials($this->x_login, $this->x_trans_key, $this->x_login_for_webpaystatus, $this->x_trans_key_for_webpaystatus, $this->secret_key, $this->test);

			$amount = number_format(($order -> get_total()),2,'.','');
			$full_name = $order->billing_first_name . ' ' . $order->billing_last_name;
			$currency = get_woocommerce_currency();
			$return_url = $this->response_page;
			$confirmation_url = $this->confirmation_page;
			$type = isset($_POST['dlocal_payment_method']) ? $_POST['dlocal_payment_method'] : 'VI';
			$user_id = uniqid();

			$response = $aps->newinvoice($order->id, $amount, $type, $order->billing_country,
			              $user_id, $order->billing_company, $full_name, $order->billing_email, $currency, '', '', '', '', '', '',
			              $return_url, $confirmation_url);

			$decoded_response = json_decode($response);

			/* --------- GENERAR REDIRECCION --------------*/						

			if ($decoded_response->status == 0) {
				$woocommerce->cart->empty_cart();
				$url = $decoded_response->link;
				return array('result' => 'success', 'type' => 'dlocal', 'result_dlocal' => 'success', 'redirect' => $url,'messages' => $decoded_response);
			}else{
				$error = $decoded_response->desc; 
				$param = '?code_error=' . urlencode($error) . '&del=' . $type;
				return array('result' => 'success', 'type' => 'dlocal', 'result_dlocal' => 'failed', 'redirect' => $woocommerce->cart->get_checkout_url() . $param );
			}
				/*
				if (version_compare(WOOCOMMERCE_VERSION, '2.0.19', '<=' )) {

					return array('result' => 'success', 
								'redirect' => add_query_arg('order', $order->id, add_query_arg('key', $order->order_key, get_permalink(get_option('woocommerce_pay_page_id')))) . $params_post
					);

				
				} else {
				
					//$parameters_args = $this->get_params_post($order_id);
					/*
					$payu_args_array = array();
					foreach($parameters_args as $key => $value){
						$payu_args_array[] = $key . '=' . $value;
					}
					$params_post = implode('&', $payu_args_array);
					*/
					/*
					return array(
						'result' => 'success',
						'redirect' =>  $order->get_checkout_payment_url( true ) . $params_post
					);
					
				}
				*/

			

		}



		/**
		 * Metodo que genera la URL de Invoice de DLOCAL
         *
         * @access public
         * @return void
		 */
		public function get_url_invoice($order_id = null){			
			global $woocommerce, $post;

			if(!$order_id){ $order_id = $post->ID;}
		
			$order = new WC_Order( $order_id );
			$aps = new dLocalStreamline();
			$aps->init_credencials($this->x_login, $this->x_trans_key, $this->x_login_for_webpaystatus, $this->x_trans_key_for_webpaystatus, $this->secret_key, $this->test);

			$amount = number_format(($order -> get_total()),2,'.','');
			$full_name = $order->billing_first_name . ' ' . $order->billing_last_name;
			$currency = get_woocommerce_currency();
			$return_url = $this->response_page;
			$confirmation_url = $this->confirmation_page;
			$type = $_GET['dlocal_payment_method'];

			$response = $aps->newinvoice($order->id, $amount, $type, $order->billing_country,
			              $order->user_id, $order->billing_company, $full_name, $order->billing_email, $currency, '', '', '', '', '', '',
			              $return_url, $confirmation_url);

			$decoded_response = json_decode($response);
						
			if ($decoded_response->status == 0) {
			    $url = $decoded_response->link;
			    return array('status' => 'true', 'url' => $url);
			} else { 
			    $error = $decoded_response->desc; 
			    return array('status' => 'false', 'message' => $error);
			}
			

		}
		


		
		/**
		 * Retorna la configuracion del api key
		 */
		function get_secret_key() {
			return $this->settings['secret_key'];
		}

		/**
		 * Retorna la configuracion del api key
		 */
		function get_x_login() {
			return $this->settings['x_login'];
		}		

		
		/**
		 * Retorna la configuracion del response_page
		 */
		function get_response_page() {
			return $this->settings['response_page'];
		}		

		/**
		 * Retorna la configuracion del confirmation_page
		 */
		function get_confirmation_page() {
			return $this->settings['confirmation_page'];
		}		

		public function get_dlocal_settings() {
			return array(
				'x_login' => $this->settings['x_login'],
				'secret_key' => $this->settings['secret_key'],
				'x_trans_key' => $this->settings['x_trans_key'],
				'x_login_for_webpaystatus' => $this->settings['x_login_for_webpaystatus'],
				'x_trans_key_for_webpaystatus' => $this->settings['x_trans_key_for_webpaystatus'],
				'test' => $this->settings['test'],
				'response_page' => $this->settings['response_page'],
				'confirmation_page' => $this->settings['confirmation_page'],
			);
		}	


	}

	/**
	 * Ambas funciones son utilizadas para notifcar a WC la existencia de DLocal
	 */
	function add_d_local($methods) {
		$methods[] = 'WC_d_local';
		return $methods;
	}
	add_filter('woocommerce_payment_gateways', 'add_d_local' );




	add_filter( 'woocommerce_checkout_fields' , 'custom_override_checkout_fields' );

	// Our hooked in function - $fields is passed via the filter!
	function custom_override_checkout_fields( $fields ) {
	     $fields['billing']['billing_company'] = array(
	        'label'     => __('Documento', 'woocommerce'),
	 	   'placeholder'   => _x('Documento', 'placeholder', 'woocommerce'),
	 	   'required'  => true,
		    'class'     => array('form-row-wide'),
	  	  	'clear'     => true
	     );

	     return $fields;
	}



	add_filter( 'woocommerce_before_checkout_form' , 'error_message_dlocal_in_checkout' );

	
	function error_message_dlocal_in_checkout(  ) {
		if(isset($_GET['code_error'])){
			$error  = $_GET['code_error'];
		    echo '<ul class="woocommerce-error">
							<li><strong>Los datos ingresados no son correctos.</strong> 
								Por favor intentelo nuevamente
								<br> Codigo de error: ' . $error . '
							</li>
						</ul>';
		}
	}



	// REDIRECCION DE RESPUESTAS
	add_action("wp", "only_pages");

	function only_pages(){

	    if(is_page()){
	    	$dl = new WC_d_local;
			$response_page = $dl->get_response_page();
			$confirmation_page = $dl->get_confirmation_page();

	    	$actual_link = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
	    	$url_actual = rtrim($actual_link,"/");
	    	$url_response = rtrim($response_page,"/");
	    	$url_confirm = rtrim($confirmation_page,"/");
	    	
	    	 if($url_actual == $url_response){
	    	 	include_once 'response.php';
	    	 	exit;
	    	 }

	      	 if($url_actual == $url_confirm){
	    	 	include 'confirmation.php';
	    	 	exit;
	    	 }
	    }
		/*	    	
	    if(!is_admin()){
	        wp_reset_query();
	        $payu = new Payu();

	        if(is_page() && strpos($_SERVER['REQUEST_URI'], 'confirm_payu_pay') !== false){
	            confirm_func();
				exit;
	        }
	        
	        if(is_page() && strpos($_SERVER['REQUEST_URI'], 'response_payu_pay') !== false){
	            response_func();
				exit;
	        }    
	        $uri_actual = (substr($_SERVER['REQUEST_URI'], -1) == '/') ? rtrim($_SERVER['REQUEST_URI'],"/") : $_SERVER['REQUEST_URI'];
	        $uri_approved = parse_url($payu->info()['approved_page'])['path'];
	        $uri_approved = (substr($uri_approved, -1) == '/') ? rtrim($uri_approved,"/") : $uri_approved;
	        //if(is_page() && strpos($_SERVER['REQUEST_URI'], parse_url($payu->info()['approved_page'])['path']) !== false){
	        if(is_page() && $uri_actual == $uri_approved){
				if (!$payu->validateSignureRespuesta($_REQUEST)) {
					$url =  $payu->info()['rejected_page'] . '?' . $_SERVER['QUERY_STRING'];
					wp_redirect($url);
				}
	        }
	    

	    }
	    */
	}



		add_action("wp_ajax_Get_Iframe_DLocal", "Get_Iframe_DLocal");
		add_action("wp_ajax_nopriv_Get_Iframe_DLocal", "Get_Iframe_DLocal");

		function Get_Iframe_DLocal(){
			
		   global $woocommerce, $wpdb;
		   
		   
			$dl = new WC_d_local;
		    $set = $dl->get_dlocal_settings();
		   
			
			$aps = new dLocalStreamline();
			$aps->init_credencials($set['x_login'], $set['x_trans_key'], $set['x_login_for_webpaystatus'], $set['x_trans_key_for_webpaystatus'], $set['secret_key'], $set['test']);

			$amount = number_format(($_POST['amount']),2,'.','');
			$full_name = $_POST['full_name'];
			$currency = get_woocommerce_currency();
			$return_url = $set['response_page'];
			$confirmation_url = $set['confirmation_page'];
			$type = $_POST['pay_type'];

			$response = $aps->newinvoice($_POST['order_id'], $amount, $type, $_POST['country'],
			              $_POST['user_id'], $_POST['document'], $full_name, $_POST['email'], $currency, '', '', '', '', '', '',
			              $return_url, $confirmation_url);

			$decoded_response = json_decode($response);
									
			if ($decoded_response->status == 0) {
				$url = $decoded_response->link;
				echo $url;
			}else{
				echo 'ERROR - ' . $decoded_response->desc;
			}
			
		}





		add_action("woocommerce_after_checkout_form", "cargar_js_checkout", 20 );
        function cargar_js_checkout(){ ?>

       <script type="text/javascript">

	            $(".input-radio-dlocal").on('click', function(){
				        $('.boxes').slideUp('fast'); 
				      	var id = $(this).attr('id');
				        $('.payment_box_' + id).slideDown('fast'); 
	            });
            
	            $("#dlocal_regresar").on('click', function(){
			        $('#dlocal_pagos').show();
			        $('#dlocal_iframe').hide();
			        $('.dlocal_img').removeClass('pay_select');
	            });
			
				/*
				$(window).unload(function(){
					alert("Goodbye!");
				});
				window.onbeforeunload = function() {
					//alert('1');
				    return "Bye now!";
				};
				*/
				/*
				$(window).on('beforeunload', function(e){
					  
					  if(e.target.activeElement.localName == "iframe"){
					  	$("#place_order").click();
					  	e.preventDefault();
				      	//return 'Are you sure you want to leave?';
					  }
				});
				*/

				$('.container_img').on('click',function(){
					$('#dlocal_payment_method').val($(this).data('pay'));
					$('.dlocal_img').removeClass('pay_select');
            		$(this).find('img').addClass('pay_select');
				});


				$( document ).ready(function() {
				    $('#payment').before('<input type="submit" id="continue" value="Continuar"/>' );
				    $('.wc_payment_method .payment_method_paypal').before('<li>-------</li>' );
				    //$('.methods .payment_method_dlocal').remove();
				   // $('.wc_payment_methods').appendTo( "#dlocal_pagos" );
				    
				    
				    
				});
				

            	$('#continue').on('click',function(){
            		doAfterCheckout();
            	});

            	/*
            	$('input').on('change',function(){
            		var src = $('#iframeDL').prop( "src" );
            		if(src != ''){return '0';}

            		var full_name = $('#billing_first_name').val() + ' ' + $('#billing_last_name').val();
            		var country = "<?= ''//$country ?>";
            		var order_id = "<?= ''//uniqid() //$cart_key ?>";
            		var amount = "<?= $amount ?>";
            		var user_id = "<?= ''//uniqid() ?>";
            		var email = $('#billing_email').val();
            		var documento = $('#billing_company').val();
            		var pay_type = 'VI';

            		if(full_name && country && order_id && amount && user_id && email && documento && pay_type){
            			$('#dlocal-error-message').hide();
	            		
	            		var data = { action: "Get_Iframe_DLocal", 
	            					pay_type: pay_type,
	            					amount: amount,
	            					user_id: user_id,
	            					order_id: order_id,
	            					country: country,
	            					email: email,
	            					document: documento,
	            					full_name: full_name,
	            				};

	            		$.post('/wp-admin/admin-ajax.php', data, function(response) {
	            			if(response.slice(-1) == 0){
	            				response = response.slice(0, -1);
	            			}
	            			//alert(response);
	            			if (response.indexOf("ERROR") >= 0){
	            				console.log(response);
	            				//$('#dlocal-error-message').html(response);
	            				//$('#dlocal-error-message').show();
	        				}else{
		        				//$('#dlocal_pagos').hide();
		        				//$('#dlocal_iframe').find('iframe').attr('src', response + '&iframe_view=1');
		        				$('#iframeDL').attr('src', response + '&iframe_view=1');
		        				//$('#dlocal_iframe').show();
		        				$('#iframeDL').show();
		        				$('#dlocal-error-message').hide();
	        				}
	    				});
	    			}else{
	    				$('.dlocal_img').removeClass('pay_select');
	    				$('#dlocal-error-message').html('Debe completar todos los datos del formulario correctamente');
	    				$('#dlocal-error-message').show();
	    			}
            	});
	    			*/
            </script>

        <?php
        }

 
}
