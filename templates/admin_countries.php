<?php

global $CONFIG, $fn_page_args, $db, $too_login;

if(!class_exists('tooLogin')) header("Location: {$CONFIG['site']['base']}error");
if($too_login->isLogged() !== 200) header("Location: {$CONFIG['site']['base']}admin/login");
if($too_login->isLogged() == 200 && $too_login->isAuth(100, false) !== 200) header("Location: {$CONFIG['site']['base']}{$st_lang}/mi-cuenta");

$fn_q = $db->FetchAll("
	SELECT *
	FROM `apps_countries`
	ORDER BY `country_name` ASC;
");

if($fn_q)
{
	foreach($fn_q as $k => $v)
	{
		$fn_for_data = object_to_array($v);
		
		$fn_for_data['active'] = ($v->active) ? 'checked' : '';
		
		$fn_xtemplate_parse['assign'][] = $fn_for_data;
		$fn_xtemplate_parse['parse'][] = 'admin_countries.row';
	}
	
}else{
	$fn_xtemplate_parse['assign'][] = array(
		'type' => 'danger',
		'message' => 'No puedo acceder a la base de datos',
	);
	$fn_xtemplate_parse['parse'][] = 'admin_countries.message';
}

?>