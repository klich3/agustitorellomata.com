<?php
	
global $CONFIG, $fn_page_args, $db, $st_lang;

$fn_q_cat_list = $db->FetchAll("
	SELECT p.*,  c.`lang_data` AS 'cat_title', g.`objects` AS 'file', c.`hash` as 'cat_hash' 
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
	
	foreach($fn_q_cat_list as $clk => $clv)
	{
		$fn_catid[$clv->cat_id]['cat_title'] = decodeLangData($clv->cat_title);
		$fn_catid[$clv->cat_id]['cat_items'][] = $clv;
		$fn_catid[$clv->cat_id]['cat_hash'] = $clv->cat_hash;
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
					$fn_xtemplate_parse['parse'][] = "{$fn_page_args['stage_id']}.group.prod.row.ficha_link";
				}
				
				$fn_stock = $db->FetchObject("
					SELECT *
					FROM `product_stock`
					WHERE `id`=:id
					LIMIT 1;
				", array(
					"id" => $vi->id
				));
				
				if($fn_stock && isset($fn_stock->pax_multimplier) && $fn_stock->pax_multimplier >= 1 && $fn_stock->precio_venta != 0)
				{
					$fn_stock->precio_caja = round($fn_stock->pax_multimplier * $fn_stock->precio_venta, 2);
					
					$fn_xtemplate_parse['assign'][] = $fn_stock;
					$fn_xtemplate_parse['parse'][] = "{$fn_page_args['stage_id']}.group.prod.row.multiplier";
				}
				
				if($too_login->isLogged() !== 200)
				{
					$fn_xtemplate_parse['assign'][] = array();
					$fn_xtemplate_parse['parse'][] = "{$fn_page_args['stage_id']}.group.prod.row.login_but";
				}else{
					if($fn_stock && isset($fn_stock->precio_venta) && $fn_stock->precio_venta > 0 && isset($fn_meta_array['show_button_addcart']) && $fn_meta_array['show_button_addcart'] == 1)
					{
						$fn_xtemplate_parse['assign'][] = $fn_stock;
						$fn_xtemplate_parse['parse'][] = "{$fn_page_args['stage_id']}.group.prod.row.venta_price";
						
						$fn_xtemplate_parse['assign'][] = array(
							'id' => $vi->id,
							'cat_id' => $vi->cat_id,
						);
						$fn_xtemplate_parse['parse'][] = "{$fn_page_args['stage_id']}.group.prod.row.venta_but";
					}
					
					if($fn_stock && isset($fn_stock->pax_multimplier) && $fn_stock->pax_multimplier > 1 && isset($fn_stock->precio_venta) && $fn_stock->precio_venta > 0)
					{
						$fn_xtemplate_parse['assign'][] = "";
						$fn_xtemplate_parse['parse'][] = "{$fn_page_args['stage_id']}.group.prod.row.title_sep";
					}
				}
				
				//subtitle
				if(isset($vi->subtitle_lang_data))
				{
					$fn_xtemplate_parse['assign'][] = array(
						'subtitle' => html_entity_decode(decodeLangData($vi->subtitle_lang_data))
					);
					$fn_xtemplate_parse['parse'][] = "{$fn_page_args['stage_id']}.group.prod.row.subtitle";
				}
				
				//interios del grupos
				$fn_xtemplate_parse['assign'][] = array(
					'title' => html_entity_decode(decodeLangData($vi->lang_data)),
					'image' => (isset($fn_file['img']) && $fn_file['img']) ? $fn_file['img'] : "{$CONFIG['site']['base_script']}images/nofoto.png",
					'alt' => (isset($fn_file['alt']) && $fn_file['alt']) ? $fn_file['alt'] : "",
					'id' => $vi->id,
					'cat_id' => $vi->cat_id,
					'sm' => (count($cv['cat_items']) == 3) ? "sm" : "",
					'hash' => $vi->hash,
				); 
				$fn_xtemplate_parse['parse'][] = "{$fn_page_args['stage_id']}.group.prod.row";
			}
			
			//pructos
			$fn_xtemplate_parse['assign'][] = "";
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
			'cat_hash' => $cv['cat_hash']
		);
		$fn_xtemplate_parse['parse'][] = "{$fn_page_args['stage_id']}.group";
		
		$i++;
	}
}else{
	$fn_xtemplate_parse['assign'][] = '';
	$fn_xtemplate_parse['parse'][] = "{$fn_page_args['stage_id']}.no_items";
}

?>