<?php
	
/*
	Este archivo lo llama exclusivamente redsys via POST
	No devuelve nada, en caso de error crea log en su carpeta.
*/

if(!isset($_POST)) exit;

global $db, $CONFIG;

//debug options
ini_set("log_errors" , "1");
ini_set("error_log" , "../log/errorlog_response.txt");
ini_set("display_errors", 0);
error_reporting(0);

require '../config.php';
require '../class/redsys/apiRedsys.php';
require '../class/redsys_soap/Messages.php';
require '../class/redsys_soap/Redsys.php';
require '../class/easypdo.mysql.php';
require '../class/211.basics.fn.php';

try {
	$db = EasyPDO_MySQL::Instance($CONFIG['database']['host'], $CONFIG['database']['database'], $CONFIG['database']['username'], $CONFIG['database']['password']);
}catch (Exception $e) 
{
    //$e->getMessage();
    header('HTTP/1.1 503 Service Temporarily Unavailable');
	header('Status: 503 Service Temporarily Unavailable');
	exit("Working - Maintenance");
}

//options to $CONFIG
$db_q_opt = $db->FetchAll("
	SELECT *
	FROM `options`
");

if($db_q_opt) foreach($db_q_opt as $opk => $opv) 
{
	$CONFIG['site'][$opv->options_key] = $opv->options_value;
}

$fn_redsys_mode = ($CONFIG['site']['redsys_mode']) ? 'live' : 'test';
$fn_redsys_mode_prefix = ($fn_redsys_mode == 'live') ? '_real' : '';

$redsys = new \Buuum\Redsys($CONFIG['site']["redsys_sha256{$fn_redsys_mode_prefix}"]);

//debug
$fn_out_put = '';
if($_POST) $fn_out_put .= "[POST]-> ".print_r($_POST, true)."\n";
if($_GET) $fn_out_put .= "[GET]-> ".print_r($_GET, true)."\n";
if($_SERVER) $fn_out_put .= "[SERVER]-> ".print_r($_SERVER, true)."\n";
$fn_out_put .= "[php://input]-> ".file_get_contents("php://input")."\n";

file_put_contents("../log/redsys_response_intentos.txt", $fn_out_put."\n --------------------------------------------------------------- \n\n", FILE_APPEND);

//post simple
try{
    $result = $redsys->checkPaymentResponse($_POST);
    
    //debug result
    $fn_out_put = '';
    $fn_out_put .= "[RESULT]-> ".print_r($result, true)."\n";
    if($_POST) $fn_out_put .= "[POST]-> ".print_r($_POST, true)."\n";
	file_put_contents("../log/re.txt", $fn_out_put."\n --------------------------------------------------------------- \n\n", FILE_APPEND);
    //

    if(!isset($result['Ds_ErrorCode']) && (isset($result['Ds_AuthorisationCode']) && !empty($result['Ds_AuthorisationCode']) && !preg_match('/(\+)/', $result['Ds_AuthorisationCode']) ))
    {
	    //todo correcto borramos el cart y detalles
		//----------------> 
		//----------------> 
		unset($_SESSION['cart_wiva_checkout']);
		unset($_SESSION['cart_checkout']);
		unset($_SESSION['cart']);
		unset($_SESSION['promote']);
		//----------------> 
		//---------------->
		
		//pago ok
	    $fn_response_data = base64_encode(json_encode($result));
		
		//asignamos el pago y respuesta
		$db->Fetch("
			UPDATE `orders`
			SET `data_response`=:rd, `payment_status`='1'
			WHERE `order_id`=:oid
		", array(
			'rd' => $fn_response_data,
			'oid' => $result['Ds_Order'],
		));
		
		//creamos mails de aviso de pagos
		$fn_get_order_data_based = $db->FetchValue("
			SELECT `data_cart`
			FROM `orders`
			WHERE `order_id`=:oid
		", array(
			'oid' => $result['Ds_Order'],
		));
		
		$fn_order_data = base64_decode($fn_get_order_data_based);
		$fn_order_data = (isJson($fn_order_data)) ? json_decode($fn_order_data, true) : false;
		
		//mails
		$fn_order_html = sendInvioce($fn_order_data);
		sendInvioce($fn_order_data['user']['user_email'], $result['Ds_Order'], $fn_order_data);
		sendAdminNotice($result['Ds_Order']);
    }    
	
	exit;
} catch (Exception $e) 
{
    //debug
    $fn_out_put = '';
	if($_POST) $fn_out_put .= "[POST]-> ".print_r($_POST, true)."\n";
	if($_GET) $fn_out_put .= "[GET]-> ".print_r($_GET, true)."\n";
	if($_SERVER) $fn_out_put .= "[SERVER]-> ".print_r($_SERVER, true)."\n";
	$fn_out_put .= "[php://input]-> ".file_get_contents("php://input")."\n";
	$fn_out_put .= "[TRY CATCH]-> ".print_r($e, true)."\n";
	
	file_put_contents("../log/redsys_fail_response.txt", $fn_out_put."\n --------------------------------------------------------------- \n\n", FILE_APPEND);
	
	$fn_data = array();
	$fn_data['error'] = $e;
	$fn_data['session'] = $_SESSION;
	
	//añadimos al log
	$fn_data = base64_encode(json_encode($fn_data));
	$fn_now = date('Y-m-d H:i:s');
	
	$db->Fetch("
		INSERT INTO `log_orders` (`id`, `log`, `date`)
		VALUES ('null', :log, :nw);
	", array(
		'log' => $fn_data,
		'nw' => $fn_now,
	));
		
	exit(json_encode(array(
		'status' => 400,
		'message' => "[R:500]",
	)));
}
	
?>