<?php

global $CONFIG, $fn_page_args, $db, $st_lang, $too_login, $lang_items, $cl_m;

if(!class_exists('tooLogin')) header("Location: {$CONFIG['site']['base']}error");

if($too_login->isLogged() == 200)
{
	if($too_login->isAuth(100, false) == 200) header("Location: {$CONFIG['site']['base']}admin-dashboard");
	if($too_login->isAuth(15, false) == 200) header("Location: {$CONFIG['site']['base']}{$st_lang}/mi-cuenta");
}

//activacion
$fn_g = $cl_m->parse("GET");
$fn_p = $cl_m->parse("POST");

if($fn_g && isset($fn_g['activation_key']) && !empty($fn_g['activation_key']))
{
	$fn_ifExistKey = $db->FetchValue("
		SELECT COUNT(`user_activation_key`)
		FROM `users`
		WHERE `user_activation_key`=:act
		LIMIT 1;
	", array(
		'act' => $fn_g['activation_key'],
	));
	
	if($fn_ifExistKey)
	{
		$db->Fetch("
			UPDATE `users`
			SET `user_status`='1'
			WHERE `user_activation_key`=:act
			LIMIT 1;
		", array(
			'act' => $fn_g['activation_key'],
		));
		
		$fn_user_id = $db->FetchValue("
			SELECT `ID`
			FROM `users`
			WHERE `user_activation_key`=:act
		", array(
			'act' => $fn_g['activation_key'],
		));
		
		$fn_q = $db->Fetch("
			INSERT INTO `users_meta` (`user_id`, `meta_key`, `meta_value`) 
			VALUES (:uid, 'user_access', '1') 
			ON DUPLICATE KEY UPDATE `meta_value`='1';
		", array(
			'uid' => $fn_user_id,
		));
	
		$fn_xtemplate_parse['assign'][] = array(
			'type' => 'success',
			'message' => getLangItem('msg_account_activated'),
		);
		$fn_xtemplate_parse['parse'][] = 'login.message';
	}else{
		$fn_xtemplate_parse['assign'][] = array(
			'type' => 'error',
			'message' => getLangItem('msg_account_activate_error'),
		);
		$fn_xtemplate_parse['parse'][] = 'login.message';
	}
}
//end activacion

//on post intent
if(strtolower($_SERVER['REQUEST_METHOD']) == 'post' && isset($fn_p['form']) && $fn_p['form'] == 'login')
{
	$fn_uname = ($_POST['login_email']) ? $_POST['login_email'] : '';
	$fn_upass = ($_POST['login_pass']) ? $_POST['login_pass'] : '';
	
	if(!$fn_uname || !$fn_upass)
	{
		$fn_xtemplate_parse['assign'][] = array(
			'type' => 'error',
			'message' => getLangItem('form_empty_fields'),
		);
		$fn_xtemplate_parse['parse'][] = 'login.message';
	}else{
		$fn_l = $too_login->login($fn_uname, $fn_upass, false);
		
		if($fn_l == 200)
		{
			header("Location: {$CONFIG['site']['base']}{$st_lang}/mi-cuenta");
		}else{
			$fn_xtemplate_parse['assign'][] = array(
				'type' => 'error',
				'message' => getLangItem('pass_not_correct'),
			);
			$fn_xtemplate_parse['parse'][] = 'login.message';
		}
	}
}

?>