<?php

global $CONFIG, $fn_page_args, $db, $too_login, $cl_m;

if(!class_exists('tooLogin')) header("Location: {$CONFIG['site']['base']}error");
if($too_login->isLogged() !== 200) header("Location: {$CONFIG['site']['base']}admin/login");
if($too_login->isLogged() == 200 && $too_login->isAuth(100, false) !== 200) header("Location: {$CONFIG['site']['base']}{$st_lang}/mi-cuenta");

$fn_g = $cl_m->parse("GET");
$fn_p = $cl_m->parse("POST");
$fn_r = $cl_m->parse("REQ");
$g_action = (isset($fn_g['action'])) ? $fn_g['action'] : null;

//defaults
$fn_xtemplate_parse['assign'][] = array(
	'randome_pass' => substr(md5(uniqid(mt_rand(), true)), 0, 8),
);
$fn_xtemplate_parse['parse'][] = '';

//select pag inicial
$fn_q = $db->FetchAll("
	SELECT u.*
	FROM `users` u
	LEFT JOIN `users_meta` m ON(m.`user_id`=u.`ID`)
	WHERE m.`meta_key`='user_level'
	AND m.`meta_value`='15'
");

if($fn_q) 
{
	foreach($fn_q as $uk => $uv)
	{
		unset($uv->user_pass);
		unset($uv->user_activation_key);
		
		$fn_for_data = object_to_array($uv);
		
		$fn_q_userstatus = $db->FetchValue("
			SELECT `status_value`
			FROM `users_status`
			WHERE `user_status`=:us
		", array(
			'us' => $uv->user_status,
		));
		
		$fn_for_data['user_status'] = $fn_q_userstatus;
		$fn_for_data['active'] = (isset($fn_g['id']) && $fn_g['id'] == $uv->ID) ? 'class="bg-green"' : '';
		
		$fn_xtemplate_parse['assign'][] = $fn_for_data;
		$fn_xtemplate_parse['parse'][] = 'admin_clientes.list.row';	
	}
	
	$fn_xtemplate_parse['assign'][] = '';
	$fn_xtemplate_parse['parse'][] = 'admin_clientes.list';
}else{
	$fn_xtemplate_parse['assign'][] = array(
		'status' => 400,
		'message' => 'No hay clientes registrados en estos momentos.',
	);
	$fn_xtemplate_parse['parse'][] = 'admin_clientes.message';
}

//country_sel
$fn_q_sel_country = $db->FetchAll("
	SELECT *
	FROM `apps_countries`
");

if($fn_q_sel_country) foreach($fn_q_sel_country as $ck => $cv)
{
	$fn_for_data = object_to_array($cv);
	$fn_for_data['country_code'] = strtolower($cv->country_code);
	
	$fn_xtemplate_parse['assign'][] = $fn_for_data;
	$fn_xtemplate_parse['parse'][] = 'admin_clientes.country_sel';
}
//end country_sel

//edit page
switch($g_action)
{
	case "editCliente":
		$fn_q_datails = $db->FetchArray("
			SELECT u.`user_name` AS 'd_user_name', u.`user_email` AS 'd_user_email', u.`user_status` AS 'd_user_status', u.`ID` AS 'd_user_id'
			FROM `users` u
			LEFT JOIN `users_meta` m ON(m.`user_id`=u.`ID`)
			WHERE m.`meta_key`='user_level'
			AND m.`meta_value`='15'
			AND u.`ID`=:id
			LIMIT 1;
		", array(
			'id' => $fn_g['id']
		));
		
		unset($fn_q_datails['user_pass']);
		unset($fn_q_datails['user_activation_key']);
		
		if(!$fn_q_datails)
		{
			$fn_xtemplate_parse['assign'][] = array(
				'status' => 400,
				'message' => 'No ningún cliente con esta id.',
			);
			$fn_xtemplate_parse['parse'][] = 'admin_clientes.message';
			
			return;
		}
		
		//meta dirs
		$fn_q_meta_dirs = $db->FetchValue("
			SELECT `meta_value`
			FROM `users_meta`
			WHERE `user_id`=:uid
			AND `meta_key`='user_dirs'
			LIMIT 1;
		", array(
			'uid' => $fn_g['id'],
		));
		
		if(!empty($fn_q_meta_dirs) && isJson($fn_q_meta_dirs))
		{
			$fn_dirs_array = json_decode($fn_q_meta_dirs);
			
			foreach($fn_dirs_array as $dk => $dv)
			{
				$fn_for_data = object_to_array($dv);
				$fn_for_data['user_id'] = $fn_g['id'];
				
				if(isset($dv->dir_default) && $dv->dir_default)
				{
					
					$fn_xtemplate_parse['assign'][] = '';
					$fn_xtemplate_parse['parse'][] = 'admin_clientes.details.dir_row.default';
				}
				
				$fn_xtemplate_parse['assign'][] = $fn_for_data;
				$fn_xtemplate_parse['parse'][] = 'admin_clientes.details.dir_row';
			}
		}else{
			$fn_xtemplate_parse['assign'][] = array(
				'status' => 400,
				'message' => 'Ahora mismo este cliente no tiene direcciones guardadas.'
			);
			$fn_xtemplate_parse['parse'][] = 'admin_clientes.details.message';
		}
		
		//user data pers
		$fn_q_meta_persdata = $db->FetchValue("
			SELECT `meta_value`
			FROM `users_meta`
			WHERE `user_id`=:uid
			AND `meta_key`='user_pers_data'
			LIMIT 1;
		", array(
			'uid' => $fn_g['id'],
		));
		
		if(!empty($fn_q_meta_persdata) && isJson($fn_q_meta_persdata))
		{
			$fn_for_data = json_decode($fn_q_meta_persdata);
		
			$fn_xtemplate_parse['assign'][] = $fn_for_data;
			$fn_xtemplate_parse['parse'][] = 'admin_clientes.details.client_data_pers';
		}else{
			$fn_xtemplate_parse['assign'][] = '';
			$fn_xtemplate_parse['parse'][] = 'admin_clientes.details.no_client_data_pers';
		}
		
		//user status
		$fn_q_statuts = $db->FetchAll("
			SELECT *
			FROM `users_status`;
		");
		
		if($fn_q_statuts) foreach($fn_q_statuts as $sk => $sv)
		{
			$fn_for_data = object_to_array($sv);
			
			$fn_for_data['d_selected'] = ($sv->user_status == $fn_q_datails['d_user_status']) ? 'selected' : '';
			
			$fn_xtemplate_parse['assign'][] = $fn_for_data;
			$fn_xtemplate_parse['parse'][] = 'admin_clientes.details.user_status';
		}
	
		$fn_xtemplate_parse['assign'][] = $fn_q_datails;
		$fn_xtemplate_parse['parse'][] = 'admin_clientes.details';
	break;
}

?>