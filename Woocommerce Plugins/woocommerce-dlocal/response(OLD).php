<?php
require_once 'wp-blog-header.php';
require_once 'wp-content/plugins/woocommerce-dlocal/woocommerce-dlocal-abstract.php';
require_once 'wp-content/plugins/woocommerce-dlocal/save_session.php';
global $woocommerce;

get_header('shop');

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
	$estadoTx = "Transacción no encontrada en el sistema";
} else if($result == '7'){
	$estadoTx = "Transacción pendiente, en espera de aprobación";
	$agradecimiento = '¡Gracias por tu compra!';
} else if($result == '8'){
	$estadoTx = "Operación rechazada por el banco";
} else{
	$estadoTx = "Transacci&oacute;n aprobada";
	$agradecimiento = '¡Gracias por tu compra!';
}

if (strtoupper($control) == strtoupper($x_control)) {

	if(!WC()->cart->is_empty()){
		$x_invoice = crear_orden($result);
		borrar_session();
	}

	
		// CONVERTIMOS A MONEDA LOCAL
	if($x_amount_usd == $x_amount){
		$x_amount = to_local_money($x_amount_usd, $woocommerce->session->customer['country']);
	}else{
		$x_amount = $x_amount . ' ' . get_woocommerce_currency();
	}	


?>
	<center>
		<table style="width: 42%; margin-top: 100px;">
			<tr align="center">
				<th colspan="2">DATOS DE LA COMPRA</th>
			</tr>
			<tr align="right">
				<td>Estado de la transacci&oacute;n</td>
				<td><?php echo $estadoTx; ?></td>
			</tr>
			<tr align="right">
				<td>ID de la transacci&oacute;n</td>
				<td><?php echo $x_invoice; ?></td>
			</tr>		

			<tr align="right">
				<td>Valor total</td>
				<td><?php echo $x_amount; ?> </td>
			</tr>

		</table>
		<p/>
		<h1><?php echo $agradecimiento ?></h1>
	</center>
<?php
} else {
	echo '<h1><center>La petici&oacute;n es incorrecta! Hay un error en la firma digital.</center></h1>';
}


get_footer('shop');

?>