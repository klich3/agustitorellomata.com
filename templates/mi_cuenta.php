<?php

global $CONFIG, $fn_page_args, $db, $st_lang, $too_login;

if(!class_exists('tooLogin')) header("Location: {$CONFIG['site']['base']}error");
if($too_login->isLogged() !== 200) header("Location: {$CONFIG['site']['base']}login");

$fn_login_user_data = $too_login->getUserData();

//check user data
$fn_user_meta = $db->FetchAll("
	SELECT *
	FROM `users_meta`
	WHERE `user_id`=:uid;
", array(
	'uid' => $fn_login_user_data->ID,
));

$fn_dir_msg = true;

//meta user data
if($fn_user_meta) 
{
	foreach($fn_user_meta as $uk => $uv)
	{
		if($uv->meta_key == 'user_pers_data' && isJson($uv->meta_value))
		{
			$fn_user_data_json = object_to_array(json_decode($uv->meta_value));
			
			$fn_xtemplate_parse['assign'][] = $fn_user_data_json;
			$fn_xtemplate_parse['parse'][] = '';
		}
		
		if($uv->meta_key == 'user_dirs' && isJson($uv->meta_value))
		{
			$fn_user_data_json = object_to_array(json_decode($uv->meta_value));
			
			foreach($fn_user_data_json as $dk => $dv)
			{
				if(isset($dv['dir_default']) && $dv['dir_default'])
				{
					$fn_q_name_country = $db->FetchValue("
						SELECT `country_name`
						FROM `apps_countries`
						WHERE `country_code`=UPPER(:dir)
						LIMIT 1;
					", array(
						'dir' => $dv['dir_country'],
					));
					
					if($fn_q_name_country) $dv['dir_country_name'] = $fn_q_name_country;
					
					$fn_xtemplate_parse['assign'][] = $dv;
					$fn_xtemplate_parse['parse'][] = 'mi_cuenta.dirs_row';
				}else{
					continue;
				}
			}
			
			$fn_dir_msg = false;
		}
	}
	
	if($fn_dir_msg)
	{
		$fn_xtemplate_parse['assign'][] = '';
		$fn_xtemplate_parse['parse'][] = 'mi_cuenta.no_dirs';	
	}	
}else{
	$fn_xtemplate_parse['assign'][] = array(
		'no_user_data' => 'error',	
	);
	$fn_xtemplate_parse['parse'][] = '';
}

?>