<?php
	
global $CONFIG, $fn_page_args, $db, $st_lang;

$fn_q_list = $db->FetchAll("
	SELECT *
	FROM `pages`
	WHERE `active`='1'
	AND `type`='8'
	ORDER BY `update_date` ASC
");

if($fn_q_list)
{
	foreach($fn_q_list as $ck => $cv)
	{
		$fn_data = $cv;
		
		//metas
		$fn_metas = $db->FetchAll("
			SELECT *
			FROM `pages_meta`
			WHERE `p_id`=:pid
		", array(
			'pid' => $cv->id
		));
		
		if($fn_metas) foreach($fn_metas as $mk => $mv)
		{
			if(preg_match('/id|page_content/', $mv->meta_key)) continue;
			
			$fn_data->{$mv->meta_key} = $mv->meta_value;
		}
		
		//grupos
		$fn_xtemplate_parse['assign'][] = $fn_data;
		$fn_xtemplate_parse['parse'][] = "{$fn_page_args['stage_id']}.group";
	}
}else{
	$fn_xtemplate_parse['assign'][] = '';
	$fn_xtemplate_parse['parse'][] = "{$fn_page_args['stage_id']}.no_items";
}

?>