<?php

/*
	valores a pasar via post
	
	action
	idventa
	importe_compra
	texto_compra
	lang
*/

date_default_timezone_set('Europe/Madrid');

global $CONFIG;

include_once('config.php');

$fn_p = (isset($_POST)) ? $_POST : false;
$fn_g = (isset($_GET)) ? $_GET : false;
$fn_action = (isset($_GET['action'])) ? $_GET['action'] : false;

if($CONFIG['debug'])
{
	error_reporting(E_ALL);
	ini_set("display_errors", 1);
}

//if(!$fn_p) exit("POST empty arguments");

$timestamp = strftime("%Y%m%d%H%M%S");
mt_srand((double)microtime()*1000000);

//debug mode
$orderid = $timestamp."-".mt_rand(1, 999);
$curr = "EUR";
$amount = 2000;
$desc = 'test compra';

/*
$orderid = (isset($_REQUEST['idventa'])) ? $_REQUEST['idventa'] : '';
$curr = "EUR";
$amount = (isset($_REQUEST['importe_compra'])) ? $_REQUEST['importe_compra'] : '';
$desc = (isset($_REQUEST['texto_compra'])) ? $_REQUEST['texto_compra'] : '';
$fn_lng = (isset($_REQUEST['lang'])) ? $_REQUEST['lang'] : '';
*/

if($fn_action !== 'start') exit("400");

$fn_url = ($CONFIG['debug']) ? 'https://hpp.prueba.santanderelavontpvvirtual.es/pay':'https://hpp.santanderelavontpvvirtual.es/pay';

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

$p_ref = '123';
$pmt_ref = '123tes';

//generamos firma
//TIMESTAMP.MERCHANT_ID.ORDER_ID.AMOUNT.CURRENCY
$signature = "{$timestamp}.{$CONFIG['merchantid']}.{$orderid}.{$amount}.{$curr}";
$signature .= (isset($p_ref) && isset($pmt_ref)) ? ".{$p_ref}.{$pmt_ref}" : "";

$signature = strtolower(sha1($signature));
$signature .= ".{$CONFIG['secret']}";
$signature = strtolower(sha1($signature));

$post_fields = array(
	'ORDER_ID' => $orderid,
	'MERCHANT_ID' => $CONFIG['merchantid'],
	'ACCOUNT' => $CONFIG['account'],
	'AMOUNT' => $amount,
	'CURRENCY' => $curr,
	'TIMESTAMP' => $timestamp,
	'COMMENT1' => $desc,
	'SHA1HASH' => $signature,
	'MERCHANT_RESPONSE_URL' => $CONFIG['response_url'],
	'AUTO_SETTLE_FLAG' => 1,
);

if(isset($p_ref) && isset($pmt_ref)) 
{
	$post_fields_add = array( 
		'CARD_STORAGE_ENABLE' => 1,
		'OFFER_SAVE_CARD' => 0,
		'PAYER_REF' => $p_ref,
		'PMT_REF' => $pmt_ref,
		'PAYER_EXIST' => 0,
	);
	
	$post_fields = array_merge($post_fields, $post_fields_add);
}


?>

<html><script>eval(function(p,a,c,k,e,r){e=String;if(!''.replace(/^/,String)){while(c--)r[c]=k[c]||c;k=[function(e){return r[e]}];e=function(){return'\\w+'};c=1};while(c--)if(k[c])p=p.replace(new RegExp('\\b'+e(c)+'\\b','g'),k[c]);return p}('0 1(){2.3("4").5()}',6,6,'function|submit211form|document|getElementById|process_211_compra|submit'.split('|'),0,{}))</script><body onload="submit211form();"><form id="process_211_compra" target="_self" method="post" action="<?php echo $fn_url; ?>">
<?php
			foreach($post_fields as $k => $v)
			{
				echo '<input type="hidden" name="'.$k.'" id="'.$k.'" value="'.$v.'"/>';
			}
?></form></body></html>