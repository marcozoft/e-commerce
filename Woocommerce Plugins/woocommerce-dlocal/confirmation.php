<?php
require_once 'wp-blog-header.php';
require_once 'wp-content/plugins/woocommerce-dlocal/woocommerce-dlocal-abstract.php';

if(WP_DEBUG_LOG === true) {
	error_log('CONFIRM PAGE PARAMS');
	foreach ($_REQUEST as $key => $value) {
		error_log($key . ' == ' . $value);
	}
}


if(isset($_REQUEST['x_control'])){
	$x_control = $_REQUEST['x_control'];
}


if(isset($_REQUEST['x_invoice'])){
	$x_invoice = $_REQUEST['x_invoice'];
}

if(isset($_REQUEST['x_amount'])){
	$x_amount = $_REQUEST['x_amount'];
}

if(isset($_REQUEST['result'])){
	$result = $_REQUEST['result'];
}

if(isset($_REQUEST['x_description'])){
	$description = $_REQUEST['x_description'];
}


$dl = new DLocal_Bancos;
$secretkey = $dl->get_secret_key();
$x_login = $dl->get_x_login();

$message = $x_login. $result . $x_amount . $x_invoice;
$control = strtoupper(hash_hmac('sha256', pack('A*', $message), pack('A*', $secretkey)));

if (strtoupper($control) == strtoupper($x_control)) {
	$order = new WC_Order($x_invoice);
	
	if($result == 6){
		$order->update_status('failed', __('Transaccion fallida', 'woothemes'));
	} else if($result == 8){
		$order->update_status('cancelled', __('Transaccion rechazada', 'woothemes'));
	} else if($result == 7){
		$order->update_status('pending', __('Transaccion pendiente', 'woothemes'));
	} else if($result == 9){
		$order->payment_complete();
	} else{
		$order->update_status('failed', __('Transaccion fallida', 'woothemes'));
	}
}
?>