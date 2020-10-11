<?php

global $CONFIG, $fn_page_args, $db, $st_lang, $too_login;

if(!class_exists('tooLogin')) header("Location: {$CONFIG['site']['base']}error");
if($too_login->isLogged() !== 200) header("Location: {$CONFIG['site']['base']}login");

$fn_login_user_data = $too_login->getUserData();

$fn_user_meta = $db->FetchValue("
	SELECT `meta_value`
	FROM `users_meta`
	WHERE `user_id`=:uid
	AND `meta_key`='user_dirs'
	LIMIT 1;
", array(
	'uid' => $fn_login_user_data->ID,
));

if($fn_user_meta && isJson($fn_user_meta))
{
	$fn_user_data_json = object_to_array(json_decode($fn_user_meta));
	
	foreach($fn_user_data_json as $dk => $dv)
	{
		$fn_q_name_country = $db->FetchValue("
			SELECT `country_name`
			FROM `apps_countries`
			WHERE `country_code`=UPPER(:dir)
			LIMIT 1;
		", array(
			'dir' => $dv['dir_country']
		));
		
		if($fn_q_name_country) $dv['dir_country_name'] = $fn_q_name_country;
		$dv['selected'] = (isset($dv['dir_default']) && $dv['dir_default']) ? 'selected':'';
		
		$fn_xtemplate_parse['assign'][] = $dv;
		$fn_xtemplate_parse['parse'][] = 'mis_direcciones.dirs_row';
	}
}else{
	$fn_xtemplate_parse['assign'][] = '';
	$fn_xtemplate_parse['parse'][] = 'mis_direcciones.no_dirs';
}

?>