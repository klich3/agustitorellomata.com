<?php

global $CONFIG, $fn_page_args, $db, $st_lang, $too_login;

if(!class_exists('tooLogin')) header("Location: {$CONFIG['site']['base']}error");
if($too_login->isLogged() !== 200) header("Location: {$CONFIG['site']['base']}login");

$fn_login_user_data = $too_login->getUserData();

$fn_user_invoice_dir = $db->FetchValue("
	SELECT `meta_value`
	FROM `users_meta`
	WHERE `user_id`=:uid
	AND `meta_key`='user_pers_data'
	LIMIT 1;
", array(
	'uid' => $fn_login_user_data->ID,
));

$fn_user_invoice_dir = ($fn_user_invoice_dir && isJson($fn_user_invoice_dir)) ? json_decode($fn_user_invoice_dir, true) : array();

$fn_user_shipping_dir = $db->FetchValue("
	SELECT `meta_value`
	FROM `users_meta`
	WHERE `user_id`=:uid
	AND `meta_key`='user_dirs'
	LIMIT 1;
", array(
	'uid' => $fn_login_user_data->ID,
));

$fn_user_shipping_dir = ($fn_user_shipping_dir && isJson($fn_user_shipping_dir)) ? json_decode($fn_user_shipping_dir, true) : array();


$fn_xtemplate_parse['assign'][] = array(
	"user_pers_data" => $fn_user_invoice_dir,
	"user_dirs" => $fn_user_shipping_dir,
);
$fn_xtemplate_parse['parse'][] = '';

?>