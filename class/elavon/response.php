<?php

global $CONFIG;

include_once('config.php');
include_once('basics.fn.php');

$fn_p = (isset($_POST)) ? $_POST : false;
$fn_g = (isset($_GET)) ? $_GET : false;

if($CONFIG['debug'])
{
	error_reporting(E_ALL);
	ini_set("display_errors", 1);
	ini_set("log_errors" , "1");
	ini_set("error_log" , "Errors.log.txt");
}

/*
	MERCHANT_ID=tuiddecliente
	ACCOUNT=cuenta
	ORDER_ID=ORD453-11
	TIMESTAMP=20130814122239
	AMOUNT=5000
	COMMENT1=WQYRhW4Kydrb7mBzCeXvh7AePE
	COMMENT2=NtrNfwhCZc397t73ZPNzXJb75h
	SHA1HASH=d0040af429b6be5b315fb660c585727d3abe7e5a
	
	RESULT=00
	AUTHCODE=123420
	MESSAGE=CÓDIGODE AUDE 
	AUTORIZACIÓN: 123420PASREF=1364902456
	AVSPOSTCODERESULT=U
	AVSADDRESSRESULT=U
	CVNRESULT=M 
	BATCHID=870
*/

if(!$fn_p) exit("Error post empty request");

//generamos el pago
$timestamp = $fn_p['TIMESTAMP'];
$result = $fn_p['RESULT'];
$orderid = $fn_p['ORDER_ID'];
$message = $fn_p['MESSAGE'];
$authcode = $fn_p['AUTHCODE'];
$pasref = $fn_p['PASREF'];

//TIMESTAMP, MERCHANT_ID,  ORDER_ID,  RESULT,  MESSAGE,  PASREF  y  AUTHCODE
$signature = "{$timestamp}.{$CONFIG['merchantid']}.{$orderid}.{$result}.{$message}.{$pasref}.{$authcode}";
$signature = strtolower(sha1($signature));
$signature .= ".{$CONFIG['secret']}";
$signature = strtolower(sha1($signature));

//if ($md5hash != $realexmd5) exit("hashes don't match - response not authenticated!");

if($result == "00")
{
	/*
	$_POST
		array(7) { ["MERCHANT_ID"]=> string(12) "canalsimunne" ["ORDER_ID"]=> string(18) "20160401121428-812" ["CURRENCY"]=> string(3) "EUR" ["AMOUNT"]=> string(4) "3000" ["TIMESTAMP"]=> string(14) "20160401121428" ["MD5HASH"]=> string(32) "9657ab99f9f2a6f23ce62d5ef051f111" ["AUTO_SETTLE_FLAG"]=> string(1) "1" }
	*/	
	if($CONFIG['debug']) file_put_contents('_response_de.txt', print_r($fn_p, true));
	
	cetsConfirmation(array(
		'SCPROG' => "valida.q",
		'INVNUM' => $orderid,
	), $CONFIG['cgibin']);
	
	//redirect ok
	headerPost("{$CONFIG['base']}response_ok.php", array(
    	'lang' => 'es',
	));	
	exit;	
}else{
	
	//redirect fallo
	
	headerPost("{$CONFIG['base']}response_error.php", array(
    	'lang' => 'es',
	));		
	exit;
}
	
?>
