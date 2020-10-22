<?php

global $CONFIG, $fn_page_args, $db, $too_login, $cl_m;

if(!class_exists('tooLogin')) header("Location: {$CONFIG['site']['base']}error");
if($too_login->isLogged() !== 200) header("Location: {$CONFIG['site']['base']}admin/login");
if($too_login->isLogged() == 200 && $too_login->isAuth(100, false) !== 200) header("Location: {$CONFIG['site']['base']}{$st_lang}/mi-cuenta");

$fn_g = $cl_m->parse("GET");
$fn_p = $cl_m->parse("POST");
$fn_r = $cl_m->parse("REQ");
$g_action = (isset($fn_g['action'])) ? $fn_g['action'] : null;

$fn_dir_fields = array(
	"u_name" => "Nombre",
	"u_surname" => "Apellido",
	"u_lastname" => "Apellido",
	"u_idd" => "Id interna",
	"u_tel" => "Telefono",
	"u_cif" => "CIF/NIF",
	"u_email" => "Email",
	"dir_country" => "País",
	"dir_city" => "Ciudad",
	"dir_primary" => "Dirección",
	"dir_post" => "C.P.",
);

//defaults
$fn_xtemplate_parse['assign'][] = array(
	'randome_pass' => substr(md5(uniqid(mt_rand(), true)), 0, 8),
);
$fn_xtemplate_parse['parse'][] = '';

//select pag inicial
$fn_q = $db->FetchAll("
	SELECT u.*, ml.`meta_value` as 'user_level'
	FROM `users` u
	INNER JOIN `users_meta` ml ON(ml.`user_id`=u.`ID`)
	WHERE ml.`meta_value`='15'
	AND ml.`meta_key`='user_level'
");

if($fn_q) 
{
	foreach($fn_q as $uk => $uv)
	{
		unset($uv->user_pass);
		unset($uv->user_activation_key);
		
		$fn_for_data = object_to_array($uv);
		
		$fn_q_user_data = $db->FetchValue("
			SELECT `meta_value`
			FROM `users_meta`
			WHERE `meta_key`='user_pers_data'
			AND `user_id`=:uid
		", array(
			"uid" => $uv->ID
		));
				
		if($fn_q_user_data && isJson($fn_q_user_data)) $fn_for_data = array_merge($fn_for_data, json_decode($fn_q_user_data, true));
			
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
		
		//client invoice data
		$fn_q_metas = $db->FetchAll("
			SELECT *
			FROM `users_meta`
			WHERE `user_id`=:uid
		", array(
			'uid' => $fn_g['id'],
		));
		
		foreach($fn_q_metas as $k => $v)
		{
			$fn_k = $v->meta_key;
			
			if(preg_match("/user_dirs/", $fn_k) && isJson($v->meta_value))
			{
				$fn_for_data = json_decode($v->meta_value, true);
				
				if($fn_for_data) foreach($fn_for_data as $k => $v)
				{
					if(preg_match("/dir_name|dir_id/", $k)) continue;
					
					$fn_xtemplate_parse['assign'][] = array(
						"title" => $fn_dir_fields[$k],
						"value" => $v,
					);
					$fn_xtemplate_parse['parse'][] = 'admin_clientes.details.user_dirs.row';
				}
				
				$fn_xtemplate_parse['assign'][] = $fn_for_data;
				$fn_xtemplate_parse['parse'][] = 'admin_clientes.details.user_dirs';
			}
			
			if(preg_match("/user_pers_data/", $fn_k) && isJson($v->meta_value))
			{
				$fn_for_data = json_decode($v->meta_value, true);
				
				if($fn_for_data) foreach($fn_for_data as $k => $v)
				{
					if(preg_match("/dir_name|dir_id/", $k)) continue;
					
					$fn_xtemplate_parse['assign'][] = array(
						"title" => $fn_dir_fields[$k],
						"value" => $v,
					);
					$fn_xtemplate_parse['parse'][] = 'admin_clientes.details.user_pers_data.row';
				}
				
				$fn_xtemplate_parse['assign'][] = $fn_for_data;
				$fn_xtemplate_parse['parse'][] = 'admin_clientes.details.user_pers_data';
			}
		}
			
		//user status
		$fn_q_statuts = $db->FetchAll("
			SELECT *
			FROM `users_status`;
		");
		
		if($fn_q_statuts) foreach($fn_q_statuts as $sk => $sv)
		{
			$fn_for_data = object_to_array($sv);
			
			$fn_for_data['d_selected'] = ($fn_for_data['user_status'] == $fn_q_datails['d_user_status']) ? 'selected' : '';
			
			$fn_xtemplate_parse['assign'][] = $fn_for_data;
			$fn_xtemplate_parse['parse'][] = 'admin_clientes.details.user_status';
		}
	
		$fn_xtemplate_parse['assign'][] = $fn_q_datails;
		$fn_xtemplate_parse['parse'][] = 'admin_clientes.details';
	break;
}

?>