<?php
if (!defined("WHMCS"))
{
	die("This file cannot be accessed directly");
}

function cib_chargily_config()
{
	return array(
		'FriendlyName' => array(
			'Type' => 'System',
			'Value' => 'CIB card payment',
		) ,
		'apiKey' => array(
			'FriendlyName' => 'API key',
			'Type' => 'text',
			'Size' => '99',
			'Default' => '',
			'Description' => 'Enter your api key',
		) ,
		'secretKey' => array(
			'FriendlyName' => 'Secret Key',
			'Type' => 'password',
			'Size' => '99',
			'Default' => '',
			'Description' => 'Enter secret key here',
		) ,
		'discount' => array(
			'FriendlyName' => 'Discount',
			'Type' => 'text',
			'Size' => '2',
			'Default' => '0',
			'Description' => 'If you offer a special discount on this payement method, write it down here (0-99)%',
		) ,
	);
}

function cib_chargily_link($params)
{
	$webhook_url = $params['systemurl'] . '/modules/gateways/callback/cib_chargily.php';
	$post_variables = array(
		"client" => $params['clientdetails']['firstname'] . ' ' . $params['clientdetails']['lastname'],
		"client_email" => $params['clientdetails']['email'],
		"invoice_number" => $params['invoiceid'],
		"amount" => $params['amount'],
		"discount" => $params['discount'],
		"back_url" => $params['returnurl'],
		"webhook_url" => $webhook_url,
		"mode" => "CIB",
		"comment" => $params["description"]
	);
	$query_string = http_build_query($post_variables);
	$headers = array();
	$headers[] = "X-Authorization: " . $params['apiKey'];
	$headers[] = "Accept: application/json";
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "http://epay.chargily.com.dz/api/invoice");
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_TIMEOUT, 90);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $query_string);
	$result = curl_exec($ch);
	if (curl_errno($ch))
	{
		$ch_error = curl_error($ch);
		logTransaction("cib_chargily", array_merge($ch_error, $params) , "Error on Payment Request", $params);
	}
	curl_close($ch);
	$data = json_decode($result, true);
	if (empty($data["checkout_url"]))
	{
		logTransaction("cib_chargily", array_merge($data, $params) , "Error on Payment Request", $params);
		return "An error has occured, please contact support or try another payement method";
	}
	$url = $data["checkout_url"];
	$strRet = "<form action=\"" .$url. "\">";
	$strRet .= "<button type=\"submit\" class=\"btn btn-primary\">\n    " . $params["langpaynow"] . "\n</button> </form>";
	return $strRet;
}