<?php

require_once('../nusoap-0.9.5/lib/nusoap.php');

$client = new nusoap_client('InotificacionSISlocal.wsdl', true);

$err = $client->getError();

if ($err) echo '<p><b>Error: ' . $err . '</b></p>';

ob_start();

/*
	<?xml version=\'1.0\' encoding=\'UTF-8\'?>
*/

echo '<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
<SOAP-ENV:Body>
<ns1:procesaNotificacionSIS xmlns:ns1="InotificacionSIS" SOAP-ENV:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
<XML xsi:type="xsd:string">&lt;Message&gt;&lt;Request Ds_Version=&apos;0.0&apos;&gt;&lt;Fecha&gt;17/05/2017&lt;/Fecha&gt;&lt;Hora&gt;12:55&lt;/Hora&gt;&lt;Ds_SecurePayment&gt;1&lt;/Ds_SecurePayment&gt;&lt;Ds_Card_Country&gt;724&lt;/Ds_Card_Country&gt;&lt;Ds_Amount&gt;6995&lt;/Ds_Amount&gt;&lt;Ds_Currency&gt;978&lt;/Ds_Currency&gt;&lt;Ds_Order&gt;17051707911&lt;/Ds_Order&gt;&lt;Ds_MerchantCode&gt;336528542&lt;/Ds_MerchantCode&gt;&lt;Ds_Terminal&gt;001&lt;/Ds_Terminal&gt;&lt;Ds_Response&gt;0000&lt;/Ds_Response&gt;&lt;Ds_MerchantData&gt;&lt;/Ds_MerchantData&gt;&lt;Ds_TransactionType&gt;0&lt;/Ds_TransactionType&gt;&lt;Ds_ConsumerLanguage&gt;1&lt;/Ds_ConsumerLanguage&gt;&lt;Ds_AuthorisationCode&gt;098929&lt;/Ds_AuthorisationCode&gt;&lt;Ds_Card_Brand&gt;1&lt;/Ds_Card_Brand&gt;&lt;/Request&gt;&lt;Signature&gt;IqaCQ11tdoXkdCUNjOZBQtfkgweSqzy0gI/MUenJWg8=&lt;/Signature&gt;&lt;/Message&gt;</XML>
</ns1:procesaNotificacionSIS>
</SOAP-ENV:Body>
</SOAP-ENV:Envelope>';
	
$fn_xml = ob_get_contents();
ob_end_clean();

$return = $client->call('procesaNotificacionSIS', $fn_xml);

var_dump($return);

echo($client);
	
?>