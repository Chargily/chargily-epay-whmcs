<?php
require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';


$gatewayParams = getGatewayVariables("edahabia_chargily");

if (!$gatewayParams['type']) {
	die("Module Not Activated");
}

$secret = $gatewayParams['secretKey'];
$data = json_decode( preg_replace('/[\x00-\x1F\x80-\xFF]/', '', file_get_contents('php://input')), true );
$localSignature = hash_hmac('sha256', json_encode($data),$secret);
$headers = $_SERVER['HTTP_SIGNATURE'];
$validated =  hash_equals($localSignature, $headers);
if($validated)
{
	if($data["invoice"]["status"] === 'paid'){
		$invoiceId = $data["invoice"]["invoice_number"];
		$transactionId = $data["invoice"]["invoice_token"];
		$paymentFee = $data["invoice"]["fee"];
		addInvoicePayment(
			$invoiceId,
			$transactionId,
			$paymentAmount,
			$paymentFee,
			'edahabia_chargily'
		);
	}
}
?>