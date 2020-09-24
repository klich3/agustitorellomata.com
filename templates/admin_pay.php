<?php

global $CONFIG, $fn_page_args, $db, $too_login;

if(!class_exists('tooLogin')) header("Location: {$CONFIG['site']['base']}error");
if($too_login->isLogged() !== 200) header("Location: {$CONFIG['site']['base']}admin/login");
if($too_login->isLogged() == 200 && $too_login->isAuth(100, false) !== 200) header("Location: {$CONFIG['site']['base']}{$st_lang}/mi-cuenta");

$fn_options_q = $db->FetchAll("
	SELECT *
	FROM `options`
");

$fn_options_array = array();

if($fn_options_q) foreach($fn_options_q as $opk => $opv)
{
	$fn_options_array[$opv->options_key] = $opv->options_value;
	
	//redsys
	if($opv->options_key == 'redsys_notification_type' && $opv->options_value == 1) $fn_options_array["selected_redsys_notification_{$opv->options_value}"] = 'selected';
	
	if($opv->options_key == 'redsys_mode' && $opv->options_value == 1) $fn_options_array["selected_redsys_mode_{$opv->options_value}"] = 'selected';
	
	if($opv->options_key == 'redsys_service') $fn_options_array["selected_redsys_service_{$opv->options_value}"] = 'selected';
	
	if($opv->options_key == 'redsys_active' && $opv->options_value == 1) $fn_options_array["selected_redsys_active_{$opv->options_value}"] = 'selected';
	
	//pp
	if($opv->options_key == 'paypal_active' && $opv->options_value == 1) $fn_options_array["selected_paypal_active_{$opv->options_value}"] = 'selected';
	
	if($opv->options_key == 'paypal_mode' && $opv->options_value == 1) $fn_options_array["selected_paypal_mode_{$opv->options_value}"] = 'selected';
}

$fn_xtemplate_parse['assign'][] = $fn_options_array;
$fn_xtemplate_parse['parse'][] = '';

?>