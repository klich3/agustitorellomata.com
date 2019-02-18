<?php

/*
	array (size=17)
  'error' => boolean false
  'code' => string '0' (length=1)
  'Ds_Amount' => string '100000' (length=6)
  'Ds_Currency' => string '978' (length=3)
  'Ds_Order' => string '19932453' (length=8)
  'Ds_Signature' => string 'u6RgsxMxD6VpLPND3GZQCNLeYfSIX6qOV3kd1y0uiJE=' (length=44)
  'Ds_MerchantCode' => string '327234688' (length=9)
  'Ds_Terminal' => string '1' (length=1)
  'Ds_Response' => string '0000' (length=4)
  'Ds_AuthorisationCode' => string '331285' (length=6)
  'Ds_TransactionType' => string 'A' (length=1)
  'Ds_SecurePayment' => string '0' (length=1)
  'Ds_Language' => string '1' (length=1)
  'Ds_ExpiryDate' => string '2012' (length=4)
  'Ds_Merchant_Identifier' => string '0f8dadd144834fda63b8500e4ead8b56f29f2d43' (length=40)
  'Ds_MerchantData' => 
    array (size=0)
      empty
  'Ds_Card_Country' => string '724' (length=3)
null
*/

/*
	array (size=12)
  'error' => boolean true
  'code' => string 'SIS0252' (length=7)
  'DS_MERCHANT_MERCHANTCODE' => string '22310080' (length=8)
  'DS_MERCHANT_AMOUNT' => string '100000' (length=6)
  'DS_MERCHANT_ORDER' => string '19932453' (length=8)
  'DS_MERCHANT_TERMINAL' => string '001' (length=3)
  'DS_MERCHANT_CURRENCY' => string '978' (length=3)
  'DS_MERCHANT_PAN' => string '4548812049400004' (length=16)
  'DS_MERCHANT_EXPIRYDATE' => string '2012' (length=4)
  'DS_MERCHANT_CVV2' => string '123' (length=3)
  'DS_MERCHANT_TRANSACTIONTYPE' => string 'A' (length=1)
  'DS_MERCHANT_IDENTIFIER' => string 'REQUIRED' (length=8)
array (size=4)
  'code' => string 'SIS0252' (length=7)
  'message' => string 'El comercio no permite el envío de tarjeta' (length=43)
  'msg' => string 'MSG0008' (length=7)
  'detail' => string '' (length=0)
	
*/

/*
	array (size=18)
  'error' => boolean true
  'code' => string 'SIS041' (length=6)
  'Ds_Amount' => string '100000' (length=6)
  'Ds_Currency' => string '978' (length=3)
  'Ds_Order' => string '19932453' (length=8)
  'Ds_Signature' => string '2Bii33CPsa95BhVuFlfxc05oV1oEbvBb4uXjDkdRxRU=' (length=44)
  'Ds_MerchantCode' => string '999008881' (length=9)
  'Ds_Terminal' => string '1' (length=1)
  'Ds_Response' => string '0000' (length=4)
  'Ds_AuthorisationCode' => string '331281' (length=6)
  'Ds_TransactionType' => string 'A' (length=1)
  'Ds_SecurePayment' => string '0' (length=1)
  'Ds_Language' => string '1' (length=1)
  'Ds_CardNumber' => string '454881******0004' (length=16)
  'Ds_ExpiryDate' => string '2012' (length=4)
  'Ds_Merchant_Identifier' => string 'db5a689afa6a58d261b05f3089f3b4ff41d6f69a' (length=40)
  'Ds_MerchantData' => 
    array (size=0)
      empty
  'Ds_Card_Country' => string '724' (length=3)
null
*/

//https://github.com/klich3/Redsys

include("Redsys.php");
include("Messages.php");

/*
$fn_sha = 'sq7HjrUOBfKmC576ILgskD5srU870gJ7';
$fn_merchant = '336528542'; //999008881
$fn_pass = 'qwertyasdf0123456789';
*/

$fn_sha = 'hPdw99bxAEM9xOWiMhppbp/11Hcwevtz';
$fn_merchant = '336528542'; //999008881
$fn_pass = 'UOBfKmC57';


$fn_terminal = '001';
$fn_moneda = '978';


$fn_card_num = '4548812049400004';
$fn_card_expire = '2009';
$fn_card_ccv = '123';

$fn_importe = '1';

$fn_order_num = "17051802927{$_GET['n']}"; //cada compra es unico

$redsys = new \Buuum\Redsys($fn_sha);

try {
	
	/*
		//pago normal webservice
    $redsys->setMerchantcode($fn_merchant);
    $redsys->setAmount($fn_importe);
    $redsys->setOrder($fn_order_num);
    $redsys->setTerminal($fn_terminal);
    $redsys->setCurrency($fn_moneda);
    $redsys->setPan($fn_card_num);
    $redsys->setExpiryDate($fn_card_expire);
    $redsys->setCVV($fn_card_ccv);
    $redsys->setTransactiontype('A');
    $redsys->setIdentifier('REQUIRED');
    $result = $redsys->firePayment('test');
    */
    
    
    /*
		//pago por id del cliente (webservice)	    
    $redsys->setMerchantcode($fn_merchant);
    $redsys->setAmount($fn_importe);
    $redsys->setOrder($fn_order_num);
    $redsys->setTerminal($fn_terminal);
    $redsys->setCurrency(978);
    $redsys->setTransactiontype('A');
    $redsys->setIdentifier($client_identifier);
    $result = $redsys->firePayment();
	*/
	
	/*
		//devolucion (webservice)
	$redsys->setMerchantcode($fn_merchant);
    $redsys->setAmount($fn_importe);
    $redsys->setOrder($fn_order_num);
    $redsys->setTerminal($fn_terminal);
    $redsys->setCurrency(978);
    $redsys->setTransactiontype(3);
    $result = $redsys->firePayment();
    */
    
    /*
    	//redirecciomn envio tarjeta directametne
    $redsys->setMerchantcode($fn_merchant);
    $redsys->setAmount($fn_importe);
    $redsys->setOrder($fn_order_num);
    $redsys->setTerminal($fn_terminal);
    $redsys->setCurrency($fn_moneda);

    $redsys->setPan($fn_card_num);
    $redsys->setExpiryDate($fn_card_expire);
    $redsys->setCVV($fn_card_ccv);
    $redsys->setMerchantDirectPayment(true);

    $redsys->setTransactiontype('0');
    $redsys->setMethod('C');

	$redsys->setNotification('http://localhost/notification.php'); //Url de notificacion
    $redsys->setUrlOk('http://localhost/payment_ok.php');
    $redsys->setUrlKo('http://localhost/payment_ko.php');
	
    $redsys->setTradeName('Store S.L');
    $redsys->setTitular('John Doe');
    $redsys->setProductDescription('Product description');

    $form = $redsys->createForm();
    
    echo($form);
    */
    
      	//redireccion
    $redsys->setMerchantcode($fn_merchant);
    $redsys->setAmount($fn_importe);
    $redsys->setOrder($fn_order_num);
    $redsys->setTerminal($fn_terminal);
    $redsys->setCurrency($fn_moneda);
    
    $redsys->setLang('en');
    
    $redsys->setTransactiontype('0');
    $redsys->setMethod('C');
    
    /*
	    //envio de la visa via web
    $redsys->setPan($fn_card_num);
    $redsys->setExpiryDate($fn_card_expire);
    $redsys->setCVV($fn_card_ccv);
    $redsys->setMerchantDirectPayment(true);
    */

    //$redsys->setNotification("https://glunt.es/class/redsys_soap/_notification.php"); //Url de notificacion
    $redsys->setNotification("https://glunt.es/index.php?ajax=redsysnotification"); //Url de notificacion
    $redsys->setUrlOk("https://glunt.es/pago-completado");
    $redsys->setUrlKo("https://glunt.es/pago-error");
	
	/*
    $redsys->setTradeName('Store S.L');
    $redsys->setTitular('John Doe');
    $redsys->setProductDescription('Product description');
	*/

    $form = $redsys->createForm('live');
    
    echo $form;
    
} catch (Exception $e) {
    echo $e->getMessage();
    die;
}

var_dump($result);

$error = \Redsys\Messages\Messages::getByCode($result['code']);

var_dump($error);

?>