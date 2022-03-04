<?php

global $CONFIG, $fn_page_args, $db, $st_lang, $too_login, $lang_items, $cl_m;

if(!class_exists('tooLogin')) header("Location: {$CONFIG['site']['base']}error");

//activacion
$fn_g = $cl_m->parse("GET");

if($fn_g && !isset($fn_g['activation_key']) || empty($fn_g['activation_key'])) header("Location: {$CONFIG['site']['base']}login");

$fn_q = $db->FetchObject("
	SELECT `user_email`
	FROM `users`
	WHERE `user_activation_key`=:a
	LIMIT 1;
", array(
	"a" => $fn_g['activation_key']
));

if(!$fn_q) header("Location: {$CONFIG['site']['base']}login");

$fn_xtemplate_parse['assign'][] = array(
	"email" => (isset($fn_q->user_email)) ? $fn_q->user_email : null,
	"activation_key" => $fn_g['activation_key']
);
$fn_xtemplate_parse['parse'][] = '';

?>