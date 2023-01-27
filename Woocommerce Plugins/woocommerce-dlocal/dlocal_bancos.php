<?php 
class DLocal_Bancos extends WC_d_local {




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


	
}
