<?php
	
global $CONFIG, $fn_page_args, $db, $st_lang;


$fn_q_cat_list = $db->FetchAll("
	SELECT p.*,  c.`lang_data` AS 'cat_title', g.`objects` AS 'file'
	FROM `category` c
	LEFT JOIN `product` p ON(p.`cat_id`=c.`id`)
	LEFT JOIN `gallery` g ON(p.`gallery_id`=g.`id`)
	WHERE p.`active`='1'
	ORDER BY c.`order`, p.`order` ASC
");

$fn_cat_length = $db->FetchValue("
	SELECT COUNT(*) AS 'count'
	FROM `category`
");

$fn_catid = array();

if($fn_q_cat_list)
{
	$i = 1;
	
	foreach($fn_q_cat_list as $ck => $cv)
	{
		$fn_catid[$cv->cat_id]['cat_title'] = decodeLangData($cv->cat_title);
		$fn_catid[$cv->cat_id]['cat_items'][] = $cv;
	}
	
	foreach($fn_catid as $ck => $cv)
	{
		if(sizeof($cv['cat_items']) !== 0)
		{
			foreach($cv['cat_items'] as $ki => $vi)
			{
				$fn_file = (isJson($vi->file)) ? json_decode($vi->file, true) : false;
			
				if($fn_file) foreach($fn_file as $k => $fv)
				{
					if($fv['isThumb']) $fn_file = $fv;
				}
				
				$fn_meta_array = array();
				
				$fn_p_meta = $db->FetchAll("
					SELECT *
					FROM `product_meta`
					WHERE `p_id`=:pid
				", array(
					'pid' => $vi->id
				));
				
				if($fn_p_meta) foreach($fn_p_meta as $km => $vm)
				{
					$fn_meta_array[$vm->m_key] = $vm->m_value;
				}
				
				//haceos que sean links
				if(isset($fn_meta_array['page_is_link']) && $fn_meta_array['page_is_link'])
				{
					$fn_xtemplate_parse['assign'][] = array(
						'url' => (isset($fn_meta_array['page_is_link']) && preg_match('/(http|www)/', $fn_meta_array['page_is_link'])) ? $fn_meta_array['page_is_link'] : $CONFIG['site']['base_script'].$fn_meta_array['page_is_link'],
					);
					$fn_xtemplate_parse['parse'][] = "{$fn_page_args['stage_id']}.group.prod.row.a_init";
					$fn_xtemplate_parse['assign'][] = array();
					$fn_xtemplate_parse['parse'][] = "{$fn_page_args['stage_id']}.group.prod.row.a_final";
				}
				
				//interios del grupos
				$fn_xtemplate_parse['assign'][] = array(
					'title' => decodeLangData($vi->lang_data),
					'image' => ($fn_file['img']) ? $fn_file['img'] : "{$CONFIG['site']['base_script']}images/nofoto.png",
					'alt' => $fn_file['alt'],
				);
				$fn_xtemplate_parse['parse'][] = "{$fn_page_args['stage_id']}.group.prod.row";
			}
			
			//pructos
			$fn_xtemplate_parse['assign'][] = array();
			$fn_xtemplate_parse['parse'][] = "{$fn_page_args['stage_id']}.group.prod";
		}
		
		//separador final
		if($i <= $fn_cat_length-1)
		{
			$fn_xtemplate_parse['assign'][] = array();
			$fn_xtemplate_parse['parse'][] = "{$fn_page_args['stage_id']}.group.group_end_sep";
		}
		
		//grupos
		$fn_xtemplate_parse['assign'][] = array(
			'cat_title' => $cv['cat_title'],
		);
		$fn_xtemplate_parse['parse'][] = "{$fn_page_args['stage_id']}.group";
		
		$i++;
	}
}else{
	$fn_xtemplate_parse['assign'][] = '';
	$fn_xtemplate_parse['parse'][] = "{$fn_page_args['stage_id']}.no_items";
}

?>