<?php
require_once 'wp-blog-header.php';
require_once 'wp-content/plugins/woocommerce-dlocal/woocommerce-dlocal-abstract.php';
require_once 'wp-content/plugins/woocommerce-dlocal/save_session.php';
global $woocommerce;

get_header('shop');

if(WP_DEBUG_LOG === true) error_log("DLOCAL - PAGINA DE RESPUESTA -> " . json_encode($_REQUEST));

if(isset($_REQUEST['x_control'])){
	$x_control = $_REQUEST['x_control'];
}


if(isset($_REQUEST['x_invoice'])){
	$x_invoice = $_REQUEST['x_invoice'];
}

if(isset($_REQUEST['x_amount'])){
	$x_amount = $_REQUEST['x_amount'];
}


if(isset($_REQUEST['x_amount_usd'])){
	$x_amount_usd = $_REQUEST['x_amount_usd'];
}


if(isset($_REQUEST['result'])){
	$result = $_REQUEST['result'];
}

if(isset($_REQUEST['x_description'])){
	$description = $_REQUEST['x_description'];
}



$value = number_format($value, 1, '.', '');

$dl = new DLocal_Bancos;
$secretkey = $dl->get_secret_key();
$x_login = $dl->get_x_login();

$message = $x_login. $result . $x_amount . $x_invoice;
$control = strtoupper(hash_hmac('sha256', pack('A*', $message), pack('A*', $secretkey)));

$agradecimiento = '';
if($result == '6'){
	$estadoTx = "Transação não encontrada no sistema";
} else if($result == '7'){
	$estadoTx = "Transação pendente, aguardando aprovação";
	$agradecimiento = 'Obrigado pela sua compra!';
} else if($result == '8'){
	$estadoTx = "Operação rejeitada pelo banco";
} else{
	$estadoTx = "Transação aprovada";
	$agradecimiento = 'Obrigado pela sua compra!';
}
?>

<section class="bSe fullWidth">
	<article>
		<div class="awr lnd">


			<div class="woocommerce">
				<div class="woocommerce-order">


<?php
if (strtoupper($control) == strtoupper($x_control)) {

	if(!WC()->cart->is_empty()){
		$order = crear_orden($result, $x_invoice);
		//borrar_session();
	}else{
		$order = wc_get_order($x_invoice);
	}


	// CONVERTIMOS A MONEDA LOCAL
	 /*
	if($x_amount_usd == $x_amount){
		$x_amount = to_local_money($x_amount_usd, $woocommerce->session->customer['country']);
	}else{
		$x_amount = $x_amount . ' ' . get_woocommerce_currency();
	}
	*/
	
?>
			<!-- Facebook Pixel Code -->
			<script>
			  !function(f,b,e,v,n,t,s)
			  {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
			  n.callMethod.apply(n,arguments):n.queue.push(arguments)};
			  if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
			  n.queue=[];t=b.createElement(e);t.async=!0;
			  t.src=v;s=b.getElementsByTagName(e)[0];
			  s.parentNode.insertBefore(t,s)}(window, document,'script',
			  'https://connect.facebook.net/en_US/fbevents.js');
			  fbq('init', '932403903564537');
			  fbq('track', 'PageView');
			</script>
			<noscript>
				<img height="1" width="1" style="display:none"  src="https://www.facebook.com/tr?id=932403903564537&ev=Purchase&noscript=1"/>
			</noscript>
					<script>
						fbq('track', 'Purchase', {
						  contents: [
							<?php foreach ( $order->get_items() as $product ) { ?>
							{
							  'id': '<?= $product['product_id'] ?>',
							  'quantity': <?= $product['qty'] ?>,
							  'item_price': <?= $product['total'] ?>
							},
							<?php } ?>
						  ],
						  content_type: 'product',
						  value: <?= $order->get_total() ?>,
						  currency: '<?= get_woocommerce_currency() ?>'
						});
					</script>
			<!-- End Facebook Pixel Code -->

			<p class="woocommerce-notice woocommerce-notice--success woocommerce-thankyou-order-received"><?php echo apply_filters( 'woocommerce_thankyou_order_received_text', __( 'Thank you. Your order has been received.', 'woocommerce' ), $order ); ?></p>

			<ul class="woocommerce-order-overview woocommerce-thankyou-order-details order_details">

				<li class="woocommerce-order-overview__order order">
					<?php _e( 'Order status:', 'woocommerce' ); ?>
					<strong><?php echo $estadoTx; ?></strong>
				</li>

				<li class="woocommerce-order-overview__order order">
					<?php _e( 'Order number:', 'woocommerce' ); ?>
					<strong><?php echo $order->get_order_number(); ?></strong>
				</li>

				<li class="woocommerce-order-overview__date date">
					<?php _e( 'Date:', 'woocommerce' ); ?>
					<strong><?php echo wc_format_datetime( $order->get_date_created() ); ?></strong>
				</li>

				<li class="woocommerce-order-overview__total total">
					<?php _e( 'Total:', 'woocommerce' ); ?>
					<strong><?php echo $order->get_formatted_order_total(); ?></strong>
				</li>

				<?php if ( $order->get_payment_method_title() ) : ?>

				<li class="woocommerce-order-overview__payment-method method">
					<?php _e( 'Payment method:', 'woocommerce' ); ?>
					<strong><?php echo wp_kses_post( $order->get_payment_method_title() ); ?></strong>
				</li>

				<?php endif; ?>

			</ul>
<?php
} else { ?>

	<h1><center>La petici&oacute;n es incorrecta! Hay un error en la firma digital.</center></h1>


	<p class="woocommerce-notice woocommerce-notice--error woocommerce-thankyou-order-failed"><?php _e( 'Unfortunately your order cannot be processed as the originating bank/merchant has declined your transaction. Please attempt your purchase again.', 'woocommerce' ); ?></p>

	<p class="woocommerce-notice woocommerce-notice--error woocommerce-thankyou-order-failed-actions">
		<a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>" class="button pay"><?php _e( 'Pay', 'woocommerce' ) ?></a>
	</p>


<?php
}



		 do_action( 'woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id() );
		 do_action( 'woocommerce_thankyou', $order->get_id() );



?>
					<br><br>
				</div>
			</div>
		</div>

	</article>
</section>
<?php
get_footer('shop');

?>