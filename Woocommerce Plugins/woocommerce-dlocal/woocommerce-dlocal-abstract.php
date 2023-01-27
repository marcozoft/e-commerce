<?php
/*
Plugin Name: WooCommerce DLocal Payment Gateway
Plugin URI: http://www.dlocal.com/
Description: Plugin de integracion entre Wordpress-Woocommerce con DLocal
Version: 1.2
Author: Marcos S. Vallejos
Author URI: http://www.trafilea.com/
*/

require_once 'lib/dLocalStreamline.php';
require_once 'save_session.php';


add_action('plugins_loaded', 'woocommerce_d_local_bancos_gateway', 0);
function woocommerce_d_local_bancos_gateway() {
	if(!class_exists('WC_Payment_Gateway')) return;


	class  DLocal_Bancos extends WC_Payment_Gateway {
	
		/**
		 * Constructor de la pasarela de pago
		 *
		 * @access public
		 * @return void
		 */
		public function __construct(){
			$this->id					= 'dlocal_bancos';
			//$this->icon					= apply_filters('woocomerce_dlocal_icon', plugins_url('/img/dlocal.png', __FILE__));
			
			$this->has_fields			= true;
			$this->method_title			= 'DLocal Bancos';
			$this->method_description	= 'Integración de Woocommerce a la pasarela de pagos de DLocal';

			$this->init_form_fields();
			$this->init_settings();
			
			$this->title = $this->settings['title'];
			$this->x_login = $this->settings['x_login'];
			$this->x_trans_key = $this->settings['x_trans_key'];
			$this->secret_key = $this->settings['secret_key'];

			$this->x_login_for_webpaystatus = $this->settings['x_login_for_webpaystatus'];
			$this->x_trans_key_for_webpaystatus = $this->settings['x_trans_key_for_webpaystatus'];

			$this->gateway_url = (isset($this->settings['gateway_url']) ? $this->settings['gateway_url'] : '');
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
                    'default' => __('Transferências bancárias on-line', 'd_local')),
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
		 * Procesa el pago 
         *
         * @access public
         * @return void
		 */
		function process_payment($order_id) {
			global $woocommerce;



			/* --------- GENERAR REDIRECCION --------------*/
			$order = new WC_Order( $order_id );
			$aps = new dLocalStreamline(($this->test == 'yes' ? true : false));
			$aps->init_credencials($this->x_login, $this->x_trans_key, $this->x_login_for_webpaystatus, $this->x_trans_key_for_webpaystatus, $this->secret_key, ($this->test == 'yes' ? true : false));

			$amount = number_format(($order->get_total()),2,'.','');
			$full_name = $order->billing_first_name . ' ' . $order->billing_last_name;
			$currency = get_woocommerce_currency();
			$return_url = $this->response_page;
			$confirmation_url = $this->confirmation_page;
			$type = isset($_POST['billing_dlocal_payment_method']) ? $_POST['billing_dlocal_payment_method'] : 'VI';
			$user_id = ($order->get_customer_id() ? $order->get_customer_id() : uniqid());
			$billing_country = $order->billing_country;

			$woo_country = wc_get_base_location()['country'];
			if($currency != 'USD' && $woo_country != $billing_country){
				$resp = $aps->get_exchange(wc_get_base_location()['country'], 1);
				$amount = $amount / $resp;
				$currency = 'USD';
			}

			$response = $aps->newinvoice($order->id, $amount, $type, $billing_country,
			              $user_id, $order->billing_company, $full_name, $order->billing_email, $currency, '', '', '', '', '', '',
			              $return_url, $confirmation_url);

			
			if(WP_DEBUG_LOG === true) error_log("DLOCAL - CALL DLOCAL BANCO -> Order:" . $order_id . ' - Amount:' . $amount . ' - Type:' . $type . ' - Country:' . $billing_country . ' - User:' . $user_id . ' - CPF:' . $billing_company . ' - Name:' . $full_name . ' - Email:' . $billing_email . ' - Currency:' . $currency);

			$decoded_response = json_decode($response);

			if(WP_DEBUG_LOG === true) error_log("DLOCAL - RESPONSE -> " . $response);

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
            	if($p->payment_type != '03' && $p->payment_type != '02'){
	            	array_push($ar, $p);
            	}
            }


           	if(empty($ar)){
           		echo '<h4>Não há métodos de pagamento disponíveis</h4>';
           	}else{


            ?>
            	<?php if(false) : ?>
            	<input type="hidden" name="billing_dlocal_payment_method" id="billing_dlocal_payment_method">
            	<!--<script type="text/javascript" src="https://code.jquery.com/jquery-1.11.1.min.js"></script>-->
		        <style>
		            #dlocal-error-message{display:block;color:red;}
		            #dlocal_iframe{display:none;}
		            /*#payment{display:none;}*/
		        </style>
		        <input type="hidden" name="order_id" id="order_id">
            	<!--<h4>Escolha um método de pagamento</h4>-->
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
		        	<input type="hidden" class="unique_type" value="<?= $ar[0]->code ?>" data-hidden-body="true"> 
		        <?php }else{ ?>
				
            	<!--<script type="text/javascript" src="https://code.jquery.com/jquery-1.11.1.min.js"></script>-->
		        <style>
		            .select_dlocal{width: 100%; padding: 2%; background-color:#FFF;height: 0%;}
		            
		            #dlocal-error-message{display:block;color:red;}
		            #dlocal_iframe{display:none;}
		        </style>
		        <input type="hidden" name="order_id" id="order_id">
            	<!--<h4>Escolha um método de pagamento</h4>-->
		        <div style="width: 100%;">
		        	<select class="select_banco select_dlocal">
			        	<option selected="selected" value="">Selecione uma opção</option>
			        	<?php foreach ($ar as $i => $p) : ?>
			        		<option value="<?= $p->code ?>">
			        			<?= $p->name ?>
			        		</option>
			        	<?php endforeach; ?>
		        	</select>
            		
		        <div>
		        <?php } ?>

		        <input type="hidden" name="billing_dlocal_payment_method" id="billing_dlocal_payment_method">	

	        <?php
	    	}
        }




	}






	add_filter( 'woocommerce_before_checkout_form' , 'error_message_dlocal_in_checkout' );

	
	function error_message_dlocal_in_checkout() {
		echo error_display($_GET['code_error']);
	}


	function error_display($error){
			if(isset($error)){
				
				switch ($error) {
					case 'Invalid param x_cpf':
						$error  = 'O CPF inserido está incorreto';		
						break;						
					case 'Empty param x_bank ':
						$error  = 'Você não selecionou um método de pagamento';		
						break;		
					case 'Empty param x_amount ':
						$error  = 'O carrinho está vazio';		
						break;							
					case 'User greylisted':
						$error  = 'O sistema de fraude detectadas várias tentativas e bloquear o utilizador';		
						break;
				}
			    return '<ul class="woocommerce-error">
			    				<div class="x" onclick="javascript:this.parentNode.remove();"></div>
								<li><strong>Dados inseridos estão incorretos.</strong> 
									<br> Por favor tente novamente
									<br> Código de erro: ' . $error . '
								</li>
							</ul>';
			}
	}


	// REDIRECCION DE RESPUESTAS
	add_action("wp", "only_pages");

	function only_pages(){

	    if(is_page()){
	    	$dl = new DLocal_Bancos;
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

	}



}









	/**
	 * Ambas funciones son utilizadas para notifcar a WC la existencia de DLocal
	 */
	add_filter('woocommerce_payment_gateways', 'add_d_local' );
	function add_d_local($methods) {
		$methods[] = 'DLocal_Boletos';
		$methods[] = 'DLocal_Tarjetas';
		$methods[] = 'DLocal_Bancos';
		return $methods;
	}




		add_action("woocommerce_after_checkout_form", "cargar_js_checkout", 20 );
        function cargar_js_checkout(){ 
        	if(!WC()->cart->is_empty()){
   		?>
			<!--<script type="text/javascript" src="https://code.jquery.com/jquery-1.11.1.min.js"></script>-->
     		<script type="text/javascript">
				jQuery.noConflict();
				window.$ = window.jQuery;
     			var generando_iframe = false;
     			var iframe_url = '';
     			var update_checkout_bool = true;

				$('body').on('click', '.container_img',function(){
					$('#billing_dlocal_payment_method').val($(this).data('pay'));
					$('.dlocal_img').removeClass('pay_select');
            		$(this).find('img').addClass('pay_select');
            		$('#place_order').show();
				});
				
				$( document ).ready(function() {
					window.$ = window.jQuery;
					
				    setTimeout(function(){
						$('#billing_first_name').trigger('blur');
					}, 2000);
		
				    setInterval(function() {
				    	var data = $("form[name='checkout']").serialize();
				        //console.log('save_form');
            			save_form(data);
					}, 3000);
					//$('#billing_first_name').trigger('blur');
					
					/*
					$('#billing_company_field').data('priority','3');
				    $('#billing_phone_field').removeClass('form-row-first');
				    $('#billing_email_field').removeClass('form-row-last');
					*/

				    $('#payment_method_dlocal_tarjeta').prop('checked', true);
				    //$('#payment').before('<input type="submit" id="continue" value="Escolha um método de pagamento"/>' );
				    //$('.wc_payment_method .payment_method_paypal').before('<li>-------</li>' );
				    //$('.methods .payment_method_dlocal').remove();
				   // $('.wc_payment_methods').appendTo( "#dlocal_pagos" );
				});
				
				/*
				$('body').on('focus', '#billing_state_field_state,#billing_state_field',function(e){
					console.log('SELECT ' + $(this).attr('id'));
				});
				*/
			
				$('body').on('focus', '.input-text,#billing_country_field,#billing_state_field,input[name="js_selectamount"]',function(e){
					
						if ($(this).attr('id') == 'billing_country_field' || $(this).attr('name') == 'js_selectamount'){
							update_checkout_bool = true;
						}else{
							update_checkout_bool = false;
						}
						
						
				});
			

				
				/*
				$('input').on('change',function(){});
				$(".wc_payment_method").hide();
				$(".container_img").hide();
				$('.payment_method_dlocal_tarjeta').show();

				$('body').on('click', '.input-radio',function(){
					if($(this).attr('id') == 'payment_method_dlocal_tarjeta'){
						$('#place_order').hide();
					}else{
						$('#place_order').show();
					}
				});
				*/
				/*
				$(window).on('beforeunload', function(e){
					  alert(e.target.activeElement.localName);
					  if(e.target.activeElement.localName == "iframe"){
					  		submit();
				      		return 'Are you sure you want to leave?';
					  }
				});
				*/
				$('body').on('blur', 'input,select,#billing_country_field,#billing_country',function(e){

					e.preventDefault();
        			//console.log('---' + generando_iframe);
	            	var data = $("form[name='checkout']").serialize();
	            	

	            	var src = $('#iframeDL').prop( "src" );
	            	if(src != ''){ iframe_url = src;}

               		if(iframe_url != '' && validar_form() && $('#iframeDL').is( ":hidden" )){
	            		$('#iframeDL').attr('src', iframe_url + '&iframe_view=1');
	            		$('#iframeDL').show();
	            		$('#cargando_tarjetas').hide();
	            		console.log('if1');
	            		return '0';
	           		}


            		if((src != '' || generando_iframe) && validar_form()){
            			//console.log('if2');
            			$('.woocommerce-error').remove();
            			return '0';
            		}else{
            			console.log('save_form');
            			save_form(data);
            		}
            		
					
            		if(validar_form()){
            			console.log('if3');
            			generando_iframe = true;
            			$('#dlocal-error-message').hide();

	            		data += '&action=Get_Iframe_DLocal';

	            		$.post('<?= home_url( '/wp-admin/admin-ajax.php' ) ?>', data, function(response) {
	            			if(response.slice(-1) == 0){
	            				response = response.slice(0, -1);
	            			}
	            			//alert(response);
	            			
	            			if (response.indexOf("woocommerce-error") >= 0 || response.indexOf("http") != 0){
	            				//console.log(response);
	            				
	            				generando_iframe = false;
	            				$('.woocommerce-error').remove();
	            				$('.woocommerce').prepend(response);
								/*
	            				var scrollPoint = $(".entry-header").offset().top;
							    $('html, body').animate({
							        scrollTop: scrollPoint 
							    }, 1000);
								*/
	            				//$('#dlocal-error-message').html(response);
	            				//$('#dlocal-error-message').show();
	        				}else{
									
		        					$('#iframeDL').attr('src', response + "&iframe_view=1");
		        					iframe_url = response;
									$("#cargando_tarjetas").hide();
									//$("#customer_details").hide();
									//$('.payment_method_dlocal_tarjeta').show();
									$('.woocommerce-error').remove();
			        				$('#dlocal-error-message').hide();
			        			    setTimeout(function(){
			        					$('#iframeDL').show();
									}, 1000);
		
	        				}
	    				});
	    			}else{
	    				console.log('if4');
	    				$("#cargando_tarjetas").show();
	    				generando_iframe = false;
	    				$('#iframeDL').hide();
	    			}
            	});
		

				function save_form(data){
						data += '&action=Save_Form_DLocal';
			     		$.post('<?= home_url( '/wp-admin/admin-ajax.php' ) ?>', data, function(response) {
	            			//console.log(response);
	    				});
				}


				function validar_form(){
					var first_name = $('#billing_first_name').val();
            		var last_name = $('#billing_last_name').val();
            		var country = $('#billing_country').val();
            		var email = $('#billing_email').val();
            		var documento = $('#billing_company').val();
            		var address = $('#billing_address_1').val();
            		var city = $('#billing_city').val();
            		return (first_name != '' && documento != '' && email != '' && country != '' && last_name != '' && address != '' && city != '');
				}

				$('body').bind('mouseover mouseout',function(){
					if(document.getElementById('payment_method_dlocal_tarjeta').checked) {
						$('#place_order').hide();
					}else{
						var method = $('input[name=payment_method]:checked').val();
						var method_dlocal = $('input[name="billing_dlocal_payment_method"]').val();
						//console.log(method_dlocal + ' oo ' + method);
						if(method == 'dlocal_boletos' || method == 'dlocal_bancos'){
							if(jQuery.inArray(method_dlocal, ['VI','MC','AE','DC']) !== -1 || method_dlocal == ''){
								$('#place_order').hide();	
							}else{
								$('#place_order').show();		
							}
						}else{
							$('#place_order').show();
						}

					}


					var src = $('#iframeDL').prop( "src" );
					if(validar_form() && src == ''){
						$( "#billing_first_name" ).trigger( "blur" );
					}
					

				});



				$('body').on('click', '.input-radio',function(){
					var this_id = $(this).attr('id');
					if(this_id.indexOf("dlocal") >= 0){
						var unique_type = $(this).parent().find(".unique_type");
						if(unique_type.length == 1){
							if(unique_type.data('hidden-body') == true){
								$(this).parent().find(".payment_box").hide();
							}
							$('#billing_dlocal_payment_method').val(unique_type.val());
							$('#place_order').show();
						}else{
							$('#place_order').hide();
							$('#billing_dlocal_payment_method').val('');
							$('.dlocal_img').removeClass('pay_select');
							$('.select_dlocal').val('');
						}
					}else{
						$('#place_order').show();
					}
				});

				$('body').on('change', '.select_dlocal',function(){
				  	$('#billing_dlocal_payment_method').val(this.value);
				  	if(this.value == ''){
				  		$('#place_order').hide();
				  	}else{
				  		$('#place_order').show();
				  	}
				})
				  				


				
				jQuery(document.body).on('update_checkout', function(e){
				    //e.preventDefault();
				    //e.stopPropagation();
				    if(!update_checkout_bool){
				    	e.stopImmediatePropagation();
				    }
				    
				});

				$('body').on('click', '.cart_item',function(){
					iframe_url = '';
					$('#iframeDL').removeAttr( "src");
					$('#iframeDL').hide();
					//alert($('#iframeDL').prop( "src" ));

					$('#billing_first_name').trigger('blur');

				});			


        	</script>

        <?php
        	}
        }





		add_action("wp_ajax_Save_Form_DLocal", "Save_Form_DLocal");
		add_action("wp_ajax_nopriv_Save_Form_DLocal", "Save_Form_DLocal");

		function Save_Form_DLocal(){
		   //echo json_encode(set_persitent_checkout($_POST));
		   set_persitent_checkout($_POST);
		}


		
		add_action("wp_ajax_Get_Iframe_DLocal", "Get_Iframe_DLocal", 1, 1);
		add_action("wp_ajax_nopriv_Get_Iframe_DLocal", "Get_Iframe_DLocal", 1, 1);

		function Get_Iframe_DLocal($session = false){
		   global $woocommerce, $wpdb;
	   		
			$woocommerce->cart->calculate_totals();
			$woocommerce->cart->calculate_shipping();

			$dl = new DLocal_Bancos;
		    $set = $dl->get_dlocal_settings();		
			
			$aps = new dLocalStreamline(($set['test'] == 'yes' ? true : false));
			$aps->init_credencials($set['x_login'], $set['x_trans_key'], $set['x_login_for_webpaystatus'], $set['x_trans_key_for_webpaystatus'], $set['secret_key'], ($set['test'] == 'yes' ? true : false));
			
			$cart_key = key($woocommerce->cart->get_cart());

   			
   			$amount = $woocommerce->cart->subtotal;
   			//$amount = (!$amount ? $woocommerce->cart->get_cart()[$cart_key]['line_total'] : $amount);
   			
   			$amount = $amount + $woocommerce->cart->shipping_total;

			$currency = get_woocommerce_currency();

			$full_name = $_POST['billing_first_name'] . ' ' . $_POST['billing_last_name'];
			$billing_country = $_POST['billing_country'];
			$billing_email = $_POST['billing_email'];
			$billing_company = $_POST['billing_company'];
				
			$data = WC()->session->get('form_data');
			if($data){
				$full_name = (trim($full_name) ? $full_name : ($data['billing_first_name'] . ' ' . $data['billing_last_name']));
				$billing_country = ($billing_country ? $billing_country : $data['billing_country']);
				$billing_email = ($billing_email ? $billing_email : $data['billing_email']);
				$billing_company = ($billing_company ? $billing_company :$data['billing_company']);				
			}

			$woo_country = wc_get_base_location()['country'];
			if($currency != 'USD' && $woo_country != $billing_country){
				$resp = $aps->get_exchange($woo_country, 1);
				$amount = $amount / $resp;
				$currency = 'USD';
			}

			$return_url = $set['response_page'];
			$confirmation_url = $set['confirmation_page'];
			$type = 'VI';
	        $user_id = $woocommerce->session->generate_customer_id();
			$order_id = uniqid();
			   
			$billing_country = ($billing_country ? $billing_country : $woo_country);

			$response = $aps->newinvoice($order_id, $amount, $type, $billing_country,
				            $user_id, $billing_company, $full_name, $billing_email, $currency, '', '', '', '', '', '',
				            $return_url, $confirmation_url);
			
			if(WP_DEBUG_LOG === true) error_log("DLOCAL - CALL DLOCAL CREDIT CARD -> Order:" . $order_id . ' - Amount:' . $amount . ' - Type:' . $type . ' - Country:' . $billing_country . ' - User:' . $user_id . ' - CPF:' . $billing_company . ' - Name:' . $full_name . ' - Email:' . $billing_email . ' - Currency:' . $currency);

			$decoded_response = json_decode($response);

			if(WP_DEBUG_LOG === true) error_log("DLOCAL - RESPONSE -> " . $response);
				
			if ($decoded_response->status == 0) {
				$url = $decoded_response->link;
				if (filter_var($url, FILTER_VALIDATE_URL)) {
					if($session){return $url;}
					else{echo $url;}
				}else{
					echo error_display("Erro de conexão de cartão de crédito");
				}
			}else{
				$error = $decoded_response->desc;
				$error = error_display($error);
				if($session){return $error;}
				else{echo $error;}
			}
				

		}



add_filter("woocommerce_checkout_fields", "custom_order_fields");

function custom_order_fields($fields) {
	/*
    $fields['billing']['billing_email']['label_class'] = '';
    $fields['billing']['billing_email']['class'] = 'input-text ';
    $fields['billing']['billing_phone']['label_class'] = '';
    $fields['billing']['billing_phone']['class'] = 'input-text ';
	*/

	/*
	$fields['billing']['billing_company'] = array(
	       'label'     => __('CPF', 'woocommerce'),
	 	   'placeholder'   => _x('CPF', 'placeholder', 'woocommerce'),
	 	   'required'  => true,
		    'class'     => array('form-row-wide'),
	  	  	'clear'     => true
	);

    $fields['billing']['billing_first_name']['priority'] = 1;
    $fields['billing']['billing_last_name']['priority'] = 2;
    $fields['billing']['billing_email']['priority'] = 3;
    $fields['billing']['billing_company']['priority'] = 3;
    $fields['billing']['billing_country']['priority'] = 5;
	$fields['billing']['billing_state']['priority'] = 6;
    $fields['billing']['billing_phone']['priority'] = 10;
    
    $fields['billing']['billing_city']['priority'] = 90;
    
    $fields['billing']['billing_postcode']['priority'] = 110;

    $fields['billing']['billing_address_1']['priority'] = 170;
    $fields['billing']['billing_address_2']['priority'] = 180;
	*/

    $data = WC()->session->get('form_data');
    
    $fields['billing']['billing_first_name']['default'] = $data['billing_first_name'];
    $fields['billing']['billing_last_name']['default'] = $data['billing_last_name'];
    $fields['billing']['billing_email']['default'] = $data['billing_email'];
    $fields['billing']['billing_company']['default'] = $data['billing_company'];
    $fields['billing']['billing_country']['default'] = $data['billing_country'];
	$fields['billing']['billing_state']['default'] = $data['billing_state'];
    $fields['billing']['billing_phone']['default'] = $data['billing_phone'];
    
    $fields['billing']['billing_city']['default'] = $data['billing_city'];
    
    $fields['billing']['billing_postcode']['default'] = $data['billing_postcode'];

    $fields['billing']['billing_address_1']['default'] = $data['billing_address_1'];
    $fields['billing']['billing_address_2']['default'] = $data['billing_address_2'];


    //unset($fields['order']['order_comments']);

    return $fields;

}


add_filter( 'woocommerce_checkout_before_customer_details', 'countdown_reserved_order');
function countdown_reserved_order(){
	global $woocommerce;

	$minutos = 30;
	$label = "O seu pedido está reservado para as <span>%2</span>!";
	$label_expire = "Seu pedido está prestes a expirar !!!";

	$key_cart = key($woocommerce->cart->get_cart());

	$time = WC()->session->get($key_cart);
	if(!$time) {
		$date_of_expiry = time() + (60 * $minutos);
    	WC()->session->set( $key_cart, $date_of_expiry );
    	$time = $date_of_expiry;
	}

	if($time < time()){
		$time = 1;	
		WC()->session->set($key_cart,'');
	}else{
		$time = $time - time();	
	}
	
?>
		
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

		var count=<?= $time ?>;
		var label= "<?= $label ?>";
		var label_expire= "<?= $label_expire ?>";

		var counter=setInterval(timer, 1200); //1000 will  run it every 1 second

		function timer()
		{

		  count=count-1;
		  var msj = label.replace("%2", fancyTimeFormat(count));
		  jQuery('#timer').html(msj);
		  if (count <= 0)
		  {
		     clearInterval(counter);
		     jQuery('#timer').html(label_expire);
		     return;
		  }

		  //Do code for showing the number of seconds here
		}		
	</script>
	<span id="timer"></span>
<?php	
}

add_filter( 'woocommerce_enable_order_notes_field', '__return_false' );


//require_once 'dlocal_bancos.php';
require_once 'dlocal_tarjeta.php';
require_once 'dlocal_boletos.php';


// AGREGAMOS WIDGET EN PAGINA DE PRODUCTO PARA PONER IMAGENES DE MEDIOS DE PAGOS
function gpwidg_generatepress_widgets_payment_method() {

$ids = array('dlocal_bancos', 'dlocal_tarjeta', 'dlocal_boletos');
foreach ($ids as $v) {
	register_sidebar( array(
		'name'          => 'Method Payment - ' . $v,
		'id'            => 'payment_methods_icons_' . $v,
		'before_widget' => '<span class="payment_methods_icons_'.$v.'">',
		'after_widget'  => '</span>',
	) );
}

}
add_action( 'widgets_init', 'gpwidg_generatepress_widgets_payment_method' );



?>
