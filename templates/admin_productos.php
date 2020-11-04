<?php

global $CONFIG, $fn_page_args, $db, $too_login, $cl_m;

if(!class_exists('tooLogin')) header("Location: {$CONFIG['site']['base']}error");
if($too_login->isLogged() !== 200) header("Location: {$CONFIG['site']['base']}admin/login");
if($too_login->isLogged() == 200 && $too_login->isAuth(100, false, $CONFIG['site']['tooSType']) !== 200) header("Location: {$CONFIG['site']['base']}{$st_lang}/mi-cuenta");

$fn_g = $cl_m->parse("GET");
$fn_p = $cl_m->parse("POST");
$fn_r = $cl_m->parse("REQ");

$g_action = (isset($fn_g['action'])) ? $fn_g['action'] : null;

//idiomas
$fn_lang = (isset($CONFIG['site']['lang']) && isJson($CONFIG['site']['lang'])) ? json_decode($CONFIG['site']['lang']) : false;

if(!$fn_lang)
{
	$fn_xtemplate_parse['assign'][] = array(
		'type' => 'danger',
		'message' => 'Hay un problema de comunicación con base de datos por favor refresque la página.',
	);
	$fn_xtemplate_parse['parse'][] = "{$fn_page_args['stage_id']}.message";
	
	return;
}

switch($g_action)
{
	case "editProduct":
		$fn_data = array();
		
		$fn_q_prod = $db->FetchArray("
			SELECT p.*, s.*, p.`gallery_id` AS 'gid'
			FROM `product` p
			LEFT JOIN `product_stock` s ON(s.`prid`=p.`id`)
			WHERE p.`id`=:id
			LIMIT 1;
		", array(
			'id' => $fn_g['id'],
		));
		
		//meta content
		$fn_meta_content = $db->FetchAll("
			SELECT *
			FROM `product_meta`
			WHERE `p_id`=:id;
		", array(
			'id' => $fn_g['id']
		));
		
		$fn_meta_data = array();
		
		if($fn_meta_content) foreach($fn_meta_content as $pmk => $pmv)
		{
			$fn_meta_data[$pmv->m_key] = (!empty($pmv->m_value) && isJson($pmv->m_value)) ? object_to_array(json_decode($pmv->m_value)) : '';
		}
		
		//edit normal content
		$fn_meta_title = (!empty($fn_q_prod['lang_data']) && isJson($fn_q_prod['lang_data'])) ? object_to_array(json_decode($fn_q_prod['lang_data'])) : '';
		
		$fn_meta_subtitle = (!empty($fn_q_prod['subtitle_lang_data']) && isJson($fn_q_prod['subtitle_lang_data'])) ? object_to_array(json_decode($fn_q_prod['subtitle_lang_data'])) : '';
		
		//lang tabs and content inside
		foreach($fn_lang as $lk => $lv)
		{
			$fn_for_tabs = array(
				'lang' => $lv,
			);
			
			$fn_lang_content = (isset($fn_meta_data['lang_content']) && !empty($fn_meta_data['lang_content'][$lv])) ? html_entity_decode($fn_meta_data['lang_content'][$lv], ENT_QUOTES) : '';
			$fn_lang_envio_extre = (isset($fn_meta_data['lang_envio_extra']) && !empty($fn_meta_data['lang_envio_extra'][$lv])) ? html_entity_decode($fn_meta_data['lang_envio_extra'][$lv], ENT_QUOTES) : '';
			
			$fn_for_data = array(
				'lang' => $lv,
				'lang_title' => (isset($fn_meta_title[$lv])) ? html_entity_decode($fn_meta_title[$lv]) : '',
				'lang_subtitle' => (isset($fn_meta_subtitle[$lv])) ? html_entity_decode($fn_meta_subtitle[$lv]) : '',
				'lang_content' => $fn_lang_content,
				'lang_envio_extra' => $fn_lang_envio_extre,
			);
			
			//tab content
			$fn_xtemplate_parse['assign'][] = $fn_for_data;
			$fn_xtemplate_parse['parse'][] = "{$fn_page_args['stage_id']}.editProducto.tab_lang_content";
			
			$fn_xtemplate_parse['assign'][] = $fn_for_tabs;
			$fn_xtemplate_parse['parse'][] = "{$fn_page_args['stage_id']}.editProducto.tab_lang";
		}
		
		//categoria
		if($fn_q_prod['cat_id'] !== 0)
		{
			$fn_cat_hash = $db->FetchValue("
				SELECT `hash`
				FROM `category`
				WHERE `id`=:id
				LIMIT 1;
			", array(
				'id' => $fn_q_prod['cat_id'],
			));
			
			$fn_q_prod['url'] = "{$fn_cat_hash}/{$fn_q_prod['hash']}";
		}else{
			$fn_q_prod['url'] = "{$fn_q_prod['hash']}";
		}
		//end categoria
		
		//metas
		if(count($fn_meta_content) !== 0) foreach($fn_meta_content as $mk => $mv)
		{
			if(preg_match('/(link|customimage|keywords|description|resumen|image|freatured|gallery)/', $mv->m_key)) $fn_q_prod[$mv->m_key] = (!empty($mv->m_value)) ? html_entity_decode($mv->m_value, ENT_QUOTES) : '';
			
			if(preg_match('/(noodp|noydir|nofollow|noarchive|check|show_button_addcart)/', $mv->m_key)) $fn_q_prod[$mv->m_key] = (!empty($mv->m_value) && $mv->m_value == 1) ? 'selected' : '';
		}
		//metas
		
		$fn_q_prod['active_checked'] = (isset($fn_q_prod['active']) && $fn_q_prod['active']) ? 'checked' : '';
		
		$fn_q_prod['prod_id'] = $fn_g['id'];
		
		$fn_xtemplate_parse['assign'][] = $fn_q_prod;
		$fn_xtemplate_parse['parse'][] = "{$fn_page_args['stage_id']}.editProducto";
	break;
	
	default:
	case "list":	
		//producto
		$q_product = $db->FetchAll("
			SELECT *
			FROM `product`
			WHERE ISNULL(`cat_id`)
			ORDER BY `order`
		");
		
		if($q_product) foreach($q_product as $pk => $pv)
		{
			$fn_for_data = object_to_array($pv);
			
			//title
			$fn_for_data['item_title'] = html_entity_decode(langTitleJsonToStringJointer($pv->lang_data));
			
			//thumb
			if($pv->gallery_id == NULL || $pv->gallery_id == 0)
			{
				$fn_for_data['thumb'] = "{$CONFIG['site']['base']}images/nofoto.png";
			}else{
				$fn_for_data['thumb'] = getThumbFromGallery($pv->gallery_id);
			}
			
			$fn_xtemplate_parse['assign'][] = $fn_for_data;
			$fn_xtemplate_parse['parse'][] = "{$fn_page_args['stage_id']}.list.item";
		}
		
		//categoria + producti
		$q_cat = $db->FetchAll("
			SELECT *
			FROM `category`
			ORDER BY `order` ASC
		");
		
		if($q_cat) foreach($q_cat as $ck => $cv)
		{
			$fn_for_data = object_to_array($cv);
			
			//category title
			$fn_cat_lang_data = (isset($cv->lang_data) && isJson($cv->lang_data)) ? object_to_array(json_decode($cv->lang_data)) : false;
				
			//title langs
			$fn_cat_title_out = array();
			
			if(sizeof($fn_cat_lang_data) !== 0) foreach($fn_lang as $cak)
			{
				$fn_cat_title_out[] = (isset($fn_cat_lang_data[$cak])) ? html_entity_decode($fn_cat_lang_data[$cak]) : '';
			}
			
			//title
			$fn_for_data['category_title'] = (sizeof($fn_cat_title_out) !== 0) ? implode(" | ", $fn_cat_title_out) : 'Sin título';
			
			// CATEGORY ------------------------------------------------
			
			//items in category
			$fn_q_items = $db->FetchAll("
				SELECT *
				FROM `product`
				WHERE `cat_id`=:cid
				ORDER BY `order` ASC
			", array(
				'cid' => $cv->id,
			));
			
			if($fn_q_items) foreach($fn_q_items as $ik => $iv)
			{
				$fn_foritem_data = object_to_array($iv);
				
				$fn_foritem_data['item_title'] = html_entity_decode(langTitleJsonToStringJointer($iv->lang_data));
				
				//thumb
				if($iv->gallery_id == NULL || $iv->gallery_id == 0)
				{
					$fn_foritem_data['thumb'] = "{$CONFIG['site']['base']}images/nofoto.png";
				}else{
					$fn_foritem_data['thumb'] = getThumbFromGallery($iv->gallery_id);
				}
				
				$fn_xtemplate_parse['assign'][] = $fn_foritem_data;
				$fn_xtemplate_parse['parse'][] = "{$fn_page_args['stage_id']}.list.category.item";	
			}
			
			$fn_xtemplate_parse['assign'][] = $fn_for_data;
			$fn_xtemplate_parse['parse'][] = "{$fn_page_args['stage_id']}.list.category";
		}
		
		// MODAL ------------------------------------------------
		
		//lang modal
		if($fn_lang) foreach($fn_lang as $l)
		{
			$fn_for_data = array(
				'lang' => $l,
			);
			
			//modal cat
			$fn_xtemplate_parse['assign'][] = $fn_for_data;
			$fn_xtemplate_parse['parse'][] = "{$fn_page_args['stage_id']}.list.categoria_lang_item";
			
			//modal producto
			$fn_xtemplate_parse['assign'][] = $fn_for_data;
			$fn_xtemplate_parse['parse'][] = "{$fn_page_args['stage_id']}.list.producto_lang_item";
		}
		
		$fn_xtemplate_parse['assign'][] = '';
		$fn_xtemplate_parse['parse'][] = "{$fn_page_args['stage_id']}.list";
	break;
}

?>