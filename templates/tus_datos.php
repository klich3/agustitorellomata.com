<?php

global $CONFIG, $fn_page_args, $db, $st_lang, $too_login;

if(!class_exists('tooLogin')) header("Location: {$CONFIG['site']['base']}error");
if($too_login->isLogged() !== 200) header("Location: {$CONFIG['site']['base']}login");

$fn_login_user_data = $too_login->getUserData();

$fn_xtemplate_parse['assign'][] = $fn_login_user_data;
$fn_xtemplate_parse['parse'][] = '';

//check user data
$fn_user_meta = $db->FetchAll("
	SELECT *
	FROM `users_meta`
	WHERE `user_id`=:uid;
", array(
	'uid' => $fn_login_user_data->ID,
));

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
	}
}else{
	$fn_xtemplate_parse['assign'][] = array(
		'no_user_data' => 'error',	
	);
	$fn_xtemplate_parse['parse'][] = '';
}

?>