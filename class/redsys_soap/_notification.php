<?php
	
include("Redsys.php");
include("Messages.php");

$fn_sha = 'sq7HjrUOBfKmC576ILgskD5srU870gJ7';
$redsys = new \Buuum\Redsys($fn_sha);

try{
    $result = $redsys->checkPaymentResponse($_POST);
	var_dump($result);
}catch (Exception $e) {
    echo $e->getMessage();
    die;
}

$fn_out_put = "";
		
if($_POST) $fn_out_put .= "[POST]-> ".print_r($_POST, true)."\n";
if($_GET) $fn_out_put .= "[GET]-> ".print_r($_GET, true)."\n";
if($_SERVER) $fn_out_put .= "[SERVER]-> ".print_r($_SERVER, true)."\n";
$fn_out_put .= "[php://input]-> ".file_get_contents("php://input")."\n";
$fn_out_put .= "[header]-> ".print_r($fn_header, true)."\n";
$fn_out_put .= "[params]-> ".print_r($fn_params, true)."\n";
$fn_out_put .= "[result]-> ".print_r($result, true)."\n";

file_put_contents("log.txt", $fn_out_put."\n --------------------------------------------------------------- \n\n", FILE_APPEND);

?>