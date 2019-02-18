<?php

date_default_timezone_set('Europe/Madrid');

header("Content-Type: text/xml; charset=utf-8");
header('Cache-Control: no-store, no-cache');
header('Expires: '.date('r'));
ini_set("soap.wsdl_cache_enabled", "0");

//redsys
require_once('../redsys/apiRedsys.php');
require_once('../nusoap-0.9.5/lib/nusoap.php');

//soap notification

/*				
https://github.com/ictmanagement/redsysHMAC256_API_ASP/blob/master/ejemploSOAP.asp
https://www.sitepoint.com/web-services-with-php-and-soap-1/
https://www.sitepoint.com/web-services-with-php-and-soap-2/
http://www.forosdelweb.com/f18/leer-xml-con-nusoap-888849/
http://solocodigo.com/45301/leer-xml-con-nusoap/
*/

error_reporting(E_ALL);
ini_set("display_errors", 1);
ini_set("log_errors" , 1);
ini_set("error_log" , "log_redsys_phperror.txt");

/*
$fn_out_put = "";
		
if($_POST) $fn_out_put .= "[POST]-> ".print_r($_POST, true)."\n\n";
if($_GET) $fn_out_put .= "[GET]-> ".print_r($_GET, true)."\n\n";
if($_SERVER) $fn_out_put .= "[SERVER]-> ".print_r($_SERVER, true)."\n\n";
$fn_out_put .= "[php://input]-> ".file_get_contents("php://input")."\n\n";
$fn_out_put .= "[RAW]-> ".print_r($HTTP_RAW_POST_DATA, true)."\n\n";

file_put_contents("log.txt", $fn_out_put."\n --------------------------------------------------------------- \n\n", FILE_APPEND);
*/

/**
 * procesaNotificacionSIS function.
 * 
 * @access public
 * @param mixed $fn_get_xml
 * @return void
 */
function procesaNotificacionSIS($fn_get_xml)
{
	$fn_sha = 'sq7HjrUOBfKmC576ILgskD5srU870gJ7';
	$redsys = new RedsysAPI();
	
	//$fn_get_xml = file_get_contents("php://input");
	//if(preg_match('/(\&gt\;|\&lt\;)/', $fn_get_xml)) $fn_get_xml = html_entity_decode($fn_get_xml, ENT_COMPAT | ENT_QUOTES | ENT_XML1, 'UTF-8');
	
	$xml = $fn_get_xml['Body']['procesaNotificacionSIS']['XML'];
	
	$fn_request_sign = $redsys->createMerchantSignatureNotifSOAPRequest($fn_sha, $xml);
	
	//sign del sis para comprar -> ok / ko
	preg_match('/\<Signature\>(.*)\<\/Signature\>/m', $xml, $fn_sig_match);
	
	//order num
	preg_match('/\<Ds_Order\>(.*)\<\/Ds_Order\>/m', $xml, $fn_order_num);
	
	$fn_match_sign = (count($fn_sig_match) !== 0 && $fn_request_sign === $fn_sig_match[1]) ? 'OK' : 'KO';
	
	/*
	$fn_out_put = '';
	
	$fn_out_put = "[S]-> ".print_r($fn_request_sign, true)."\n\n";
	$fn_out_put .= "[R]-> ".print_r($fn_sig_match[1], true)."\n\n";
	$fn_out_put .= "[O]-> ".print_r($fn_order_num[1], true)."\n\n";
	$fn_out_put .= "[M]-> ".print_r($fn_match_sign, true)."\n\n";
	file_put_contents("log.txt", $fn_out_put."\n --------------------------------------------------------------- \n\n", FILE_APPEND);
	*/
	
	$fn_response_match_out = "<Response Ds_Version='0.0'><Ds_Response_Merchant>{$fn_match_sign}</Ds_Response_Merchant></Response>";
	$fn_response_sign = $redsys->createMerchantSignatureNotifSOAPResponse($fn_sha, $fn_response_match_out, $fn_order_num[1]);
	$fn_response_sign_out = "<Signature>{$fn_response_sign}</Signature>";
	$fn_response_out = "<Message>{$fn_response_match_out}{$fn_response_sign_out}</Message>";

	
	/*
	$fn_return = '';
	
	ob_start();
	echo '<?xml version=\'1.0\' encoding=\'UTF-8\'?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
<SOAP-ENV:Body>
<ns1:procesaNotificacionSIS xmlns:ns1="InotificacionSIS" SOAP-ENV:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
<XML xsi:type="xsd:string">'.$fn_response_out.'</XML>
</ns1:procesaNotificacionSIS>
</SOAP-ENV:Body>
</SOAP-ENV:Envelope>';
	
	$fn_return = ob_get_contents();
	ob_end_clean();
	*/
	//$fn_return = htmlentities($fn_return, HTML_ENTITIES, ENT_QUOTES | ENT_XML1);
		
	$fn_return = $fn_response_out;
	
	//return $fn_return;
	return new soapval('return', 'xsd:string', $fn_return);
}

//---------------------

//$fn_wsdl = 'https://glunt.es/class/redsys_soap/InotificacionSIS.wsdl';
$fn_wsdl = 'InotificacionSIS.wsdl';

$server = new soap_server();
$server->configureWSDL('InotificacionSIS', $fn_wsdl);
$server->wsdl->schemaTargetNamespace = $fn_wsdl;
$server->soap_defencoding = 'UTF-8';
$server->register("procesaNotificacionSIS", array(
	'value' => 'xsd:string'
	), array(
		'return' => 'xsd:string'
	),
	'urn:InotificacionSIS',
	'urn:InotificacionSIS#procesaNotificacionSIS');

$server->service($HTTP_RAW_POST_DATA);

/*
$server = new SoapServer($fn_wsdl);
$server->AddFunction("procesaNotificacionSIS");
$server->handle();
*/

?>