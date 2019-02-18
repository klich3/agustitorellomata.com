<?php

global $CONFIG, $fn_page_args, $db, $too_login, $st_lang, $cl_m;

$fn_g = $cl_m->parse("GET");
$fn_p = $cl_m->parse("POST");
$fn_r = $cl_m->parse("REQ");

if(!class_exists('tooLogin')) header("Location: {$CONFIG['site']['base_script']}error");

if($too_login->isLogged() == 200)
{
	if($too_login->isAuth(100, false, $CONFIG['site']['tooSType']) == 200) header("Location: {$CONFIG['site']['base_script']}admin-dashboard");
	if($too_login->isAuth(15, false, $CONFIG['site']['tooSType']) == 200) header("Location: {$CONFIG['site']['base_script']}{$st_lang}/error");
}

//on post intent
if(strtolower($_SERVER['REQUEST_METHOD']) == 'post' && isset($fn_p['form']) && $fn_p['form'] == 'login')
{
	$fn_uname = ($fn_p['login_name']) ? $fn_p['login_name'] : '';
	$fn_upass = ($fn_p['login_pass']) ? $fn_p['login_pass'] : '';
	
	if(!$fn_uname || !$fn_upass)
	{
		$fn_xtemplate_parse['assign'][] = array(
			'type' => 'danger',
			'message' => 'Hay campos en blanco.',
		);
		$fn_xtemplate_parse['parse'][] = 'admin_login.message';
	}else{
		$fn_l = $too_login->login($fn_uname, $fn_upass, false);
		
		if($fn_l == 200)
		{
			header("Location: {$CONFIG['site']['base']}admin-dashboard");
		}else if($fn_l == 303)
		{
			$fn_xtemplate_parse['assign'][] = array(
				'type' => 'danger',
				'message' => 'Demasiados intentos seguidos.',
			);
			$fn_xtemplate_parse['parse'][] = 'admin_login.message';
		}else{
			$fn_xtemplate_parse['assign'][] = array(
				'type' => 'danger',
				'message' => 'Nombre o contraseña incorrecta.',
			);
			$fn_xtemplate_parse['parse'][] = 'admin_login.message';
		}
	}
}

?>