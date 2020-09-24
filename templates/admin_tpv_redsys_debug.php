<?php

global $CONFIG, $fn_page_args, $db, $too_login;

if(!class_exists('tooLogin')) header("Location: {$CONFIG['site']['base']}error");
if($too_login->isLogged() !== 200) header("Location: {$CONFIG['site']['base']}admin/login");
if($too_login->isLogged() == 200 && $too_login->isAuth(100, false) !== 200) header("Location: {$CONFIG['site']['base']}{$st_lang}/mi-cuenta");

$g_action = (isset($_REQUEST['action'])) ? $_REQUEST['action'] : null;
$fn_g = (isset($_GET)) ? $_GET : false;
$fn_p = (isset($_POST)) ? $_POST : false;

if(isset($g_action) && $g_action == 'showDetails')
{
	$fn_q_d = $db->FetchValue("
		SELECT `log`
		FROM `log_orders`
		WHERE `id`=:id
		LIMIT 1;
	", array(
		'id' => $fn_g['id'],
	));
	
	if($fn_q_d)
	{
		$fn_xtemplate_parse['assign'][] = array(
			'content' => base64_decode($fn_q_d),
		);
		$fn_xtemplate_parse['parse'][] = 'admin_tpv_redsys_debug.details';
	}
}

//select pag inicial
$fn_q = $db->FetchAll("
	SELECT *
	FROM `log_orders`
	ORDER BY `date` DESC;
");

if($fn_q) foreach($fn_q as $k => $v)
{
	$fn_for_data = object_to_array($v);
	
	$fn_for_data['selected'] = (isset($fn_g['id']) && $v->id == $fn_g['id']) ? 'bg-green' : '';
	
	$fn_xtemplate_parse['assign'][] = $fn_for_data;
	$fn_xtemplate_parse['parse'][] = 'admin_tpv_redsys_debug.list.sel_row';
	
	$fn_xtemplate_parse['assign'][] = '';
	$fn_xtemplate_parse['parse'][] = 'admin_tpv_redsys_debug.list';
}else{
	$fn_xtemplate_parse['assign'][] = array(
		'type' => 'warning',
		'message' => 'No hay logs',
	);
	$fn_xtemplate_parse['parse'][] = 'admin_tpv_redsys_debug.message';
}


?>