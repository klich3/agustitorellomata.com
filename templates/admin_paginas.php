<?php

global $CONFIG, $fn_page_args, $db, $too_login, $cl_m;

if(!class_exists('tooLogin')) header("Location: {$CONFIG['site']['base']}error");
if($too_login->isLogged() !== 200) header("Location: {$CONFIG['site']['base']}admin/login");
if($too_login->isLogged() == 200 && $too_login->isAuth(100, false, $CONFIG['site']['tooSType']) !== 200) header("Location: {$CONFIG['site']['base']}{$st_lang}/error");

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
}else{
	
	//lang modal
	foreach($fn_lang as $lk => $lv)
	{
		$fn_xtemplate_parse['assign'][] = array(
			'key' => $lv,
			'title' => $lv,
		);
		$fn_xtemplate_parse['parse'][] = "{$fn_page_args['stage_id']}.lang_sel_modal";
	}
	
	//page types
	$fn_q_page_types = $db->FetchAll("
		SELECT *
		FROM `pages_types`
	");
	
	$fn_q_page_types_title_by_id = array();
	
	//page type
	if(sizeof($fn_q_page_types) !== 0) foreach($fn_q_page_types as $ptk => $ptv)
	{
		$fn_for_data = object_to_array($ptv);
		
		$fn_q_page_types_title_by_id[$fn_for_data['id']] = $fn_for_data['title'];
		
		$fn_xtemplate_parse['assign'][] = array(
			'key' => $fn_for_data['id'],
			'title' => $fn_for_data['title'],
		);
		$fn_xtemplate_parse['parse'][] = "{$fn_page_args['stage_id']}.pag_type_modal";
	}
}

switch($g_action)
{
	case "editPage":
		$fn_q_page = $db->FetchArray("
			SELECT *
			FROM `pages`
			WHERE `id`=:id
			LIMIT 1
		", array(
			'id' => (int)$fn_g['id'],
		));
		
		if($fn_q_page)
		{
			$fn_q_meta = $db->FetchAll("
				SELECT *
				FROM `pages_meta`
				WHERE `p_id`=:id
			", array(
				'id' => $fn_q_page['id'],
			));
			
			$fn_q_langrel = $db->FetchAll("
				SELECT *
				FROM `pages_lang_rel`
				WHERE `page_id`=:id
			", array(
				'id' => $fn_q_page['id'],
			));
			
			//page type
			if(sizeof($fn_q_page_types) !== 0) foreach($fn_q_page_types as $ptk => $ptv)
			{
				$fn_for_data = object_to_array($ptv);
				
				$fn_xtemplate_parse['assign'][] = array(
					'key' => $fn_for_data['id'],
					'title' => $fn_for_data['title'],
					'type_selected' => ($fn_for_data['id'] == $fn_q_page['type']) ? 'selected' : '',
				);
				$fn_xtemplate_parse['parse'][] = "{$fn_page_args['stage_id']}.editPage.page_type_sel";
				
				switch($fn_q_page['type'])
				{
					case "1": //pagina
					case "3": //blog
						$fn_xtemplate_parse['assign'][] = array(
							'resumen_only_article' => 'uk-hidden', //ocultamos resumen
						);
						$fn_xtemplate_parse['parse'][] = '';
					break;
					
					case "2": //articulo
						/*
						$fn_xtemplate_parse['assign'][] = array(
							'header_freatured_image' => 'uk-hidden', //ocultamos imagen destacada
						);
						$fn_xtemplate_parse['parse'][] = '';
						*/
					break;
				}
			}
			
			//lang
			if(sizeof($fn_lang) !== 0) foreach($fn_lang as $lk => $lv)
			{
				$fn_xtemplate_parse['assign'][] = array(
					'key' => $lv,
					'title' => $lv,
					'lang_selected' => ($lv == $fn_q_page['lang']) ? 'selected' : '',
				);
				$fn_xtemplate_parse['parse'][] = "{$fn_page_args['stage_id']}.editPage.lang_sel";
			}
			
			//sel pages
			$fn_list_p = $db->FetchAll("
				SELECT `id`, `obj_title`
				FROM `pages`
				WHERE `lang`!=:lang;
			", array(
				'lang' => $fn_q_page['lang'],
			));
			
			if($fn_list_p) foreach($fn_list_p as $lk => $lv)
			{
				$fn_for_data = object_to_array($lv);
				
				$fn_xtemplate_parse['assign'][] = $fn_for_data;
				$fn_xtemplate_parse['parse'][] = "{$fn_page_args['stage_id']}.editPage.list_page_sel";
			}
			
			//rel list
			$fn_p_tr = $db->FetchAll("
				SELECT *
				FROM `pages_lang_rel`
				WHERE `page_id`=:pid
			", array(
				'pid' => $fn_q_page['id'],
			));
			
			if(count($fn_p_tr) !== 0) foreach($fn_p_tr as $tk => $tv)
			{
				$fn_for_data = object_to_array($tv);
				
				$fn_g_t = $db->FetchValue("
					SELECT `obj_title`
					FROM `pages`
					WHERE `id`=:id
				", array(
					'id' => $fn_for_data['page_translate_id'],
				));
				
				$fn_xtemplate_parse['assign'][] = array(
					'r_id' => $fn_for_data['id'],
					'lang' => $fn_for_data['lang_type'],
					'page_translate_id' => $fn_for_data['page_translate_id'],
					'obj_title' => ($fn_g_t) ? $fn_g_t : '',
				);
				$fn_xtemplate_parse['parse'][] = "{$fn_page_args['stage_id']}.editPage.rel_list";
			}
			//end rel list
			
			//metas seo
			$for_isContent = false;
			
			if($fn_q_meta && sizeof($fn_q_meta) !== 0) foreach($fn_q_meta as $mk => $mv)
			{
				if($mv->meta_key == 'page_content' && !empty($mv->meta_value))
				{
					$fn_html_debase = base64_decode($mv->meta_value);
					$fn_html_decode = html_entity_decode($fn_html_debase, ENT_QUOTES | ENT_HTML5);
					$fn_q_page['pageContent'] = (isJson($fn_html_decode)) ? $fn_html_decode : 'false';
					$for_isContent = true;
				}
								
				if(preg_match('/(customimage|keywords|description|resumen|image|freatured|gallery)/', $mv->meta_key)) $fn_q_page[$mv->meta_key] = (!empty($mv->meta_value)) ? html_entity_decode($mv->meta_value, ENT_QUOTES) : '';
				
				if(preg_match('/(noodp|noydir|nofollow|noarchive|check)/', $mv->meta_key)) $fn_q_page[$mv->meta_key] = (!empty($mv->meta_value) && $mv->meta_value == 1) ? 'selected' : '';
				
				//tipo de header
				if($mv->meta_key == 'header_type' && !empty($mv->meta_value))
				{
					$fn_q_page["selected_{$mv->meta_value}"] = 'selected';
				}else{
					$fn_q_page["selected_empty"] = 'selected';
				}
			}
			
			if(!$for_isContent) $fn_q_page['pageContent'] = 'false';
			//end metas seo
			
			//visibilidad
			$fn_q_page['active_checked'] = ($fn_q_page['active']) ? 'checked' : '';
			
			//protected
			$fn_q_page["protected_selected_{$fn_q_page['protected']}"] = 'selected';
			
			//fecha
			$fn_q_page["create_date_input"] = date('Y-m-d', strtotime($fn_q_page['create_date']));
			$fn_q_page["create_date_limit"] = date('Y-m-d');
			
			$fn_xtemplate_parse['assign'][] = $fn_q_page;
			$fn_xtemplate_parse['parse'][] = "{$fn_page_args['stage_id']}.editPage";
		}else{
			$fn_xtemplate_parse['assign'][] = array(
				'type' => 'danger',
				'message' => 'Este item ya no existe.',
			);
			$fn_xtemplate_parse['parse'][] = "{$fn_page_args['stage_id']}.message";
		}
		
		return;
	break;
	
	case "showComments":
		
		$fn_q_comments = $db->FetchAll("
			SELECT *
			FROM `blog_comments`
			WHERE `p_id`=:pid
			AND `parent`='0'
			ORDER BY `date_create` DESC;
		", array(
			'pid' => $fn_g['id'],
		));
		
		if($fn_q_comments) foreach($fn_q_comments as $ck => $cv)
		{
			$fn_for_data = object_to_array($cv);
			$fn_for_data['active'] = ($cv->active == 1) ? 'Aprobado y visible' : 'No aprobado';
			
			//parents
			$fn_parent_q = $db->FetchAll("
				SELECT *
				FROM `blog_comments`
				WHERE `p_id`=:pid
				AND `parent`=:pr
				ORDER BY `date_create` ASC;
			", array(
				'pid' => $fn_g['id'],
				'pr' => $cv->id,
			));
			
			if($fn_parent_q)
			{
				foreach($fn_parent_q as $rk => $rv)
				{
					$fn_for_data_loc = object_to_array($rv);
					$fn_for_data_loc['active'] = ($rv->active == 1) ? 'Aprobado y visible' : 'No aprobado';
					$fn_for_data_loc['parent_id'] = $cv->id;
					
					$fn_xtemplate_parse['assign'][] = $fn_for_data_loc;
					$fn_xtemplate_parse['parse'][] = "{$fn_page_args['stage_id']}.blog_comments.row.reply.row";
				}
				
				$fn_xtemplate_parse['assign'][] = '';
				$fn_xtemplate_parse['parse'][] = "{$fn_page_args['stage_id']}.blog_comments.row.reply";
			}
			
			$fn_xtemplate_parse['assign'][] = $fn_for_data;
			$fn_xtemplate_parse['parse'][] = "{$fn_page_args['stage_id']}.blog_comments.row";
		}
	
		$fn_xtemplate_parse['assign'][] = array(
			'pid' => $fn_g['id'],
		);
		$fn_xtemplate_parse['parse'][] = "{$fn_page_args['stage_id']}.blog_comments";
		return;
	break;
	
	case "list":
	default:
		
		//select pag inicial
		$fn_q_pages = $db->FetchAll("
			SELECT *
			FROM `pages`
		");
		
		if($fn_q_pages)
		{
			foreach($fn_q_pages as $pk => $pv)
			{
				$fn_for_data = object_to_array($pv);
				
				$fn_for_data['active_classes'] = ($pv->active) ? 'uk-icon-eye uk-text-success' : 'uk-icon-eye-slash uk-text-danger';
				
				//lang
				$fn_q_lang_name = $db->FetchValue("
					SELECT `country_name`
					FROM `apps_countries`
					WHERE `country_code`=UPPER(:ln)
					LIMIT 1;
				", array(
					'ln' => $pv->lang,
				));
				
				switch($pv->type)
				{
					case "1": //pagina
						$fn_type_page = "row_page";
					break;
					
					case "2": //articulo
						$fn_type_page = "row_page";
					break;
					
					case "3": //blog
						$fn_type_page = "row_blog";
						
						//counter de comentarios
						$fn_comments_count = $db->FetchValue("
							SELECT COUNT(*) AS 'count'
							FROM `blog_comments`
							WHERE `p_id`=:pid;
						", array(
							'pid' => $pv->id,
						));
						
						$comment_count_pub = $db->FetchValue("
							SELECT COUNT(*) AS 'count'
							FROM `blog_comments`
							WHERE `p_id`=:pid
							AND `active`='1'
						", array(
							'pid' => $pv->id,
						));
						//counter de comentarios
						
						$fn_for_data['comment_count_pub'] = ($comment_count_pub) ? $comment_count_pub : 0;
						$fn_for_data['comment_count'] = ($fn_comments_count) ? $fn_comments_count : 0;
						$fn_for_data['lang_name'] = $fn_q_lang_name;
					break;
				}
				
				//tipo de pagina title
				if(isset($pv->type)) $fn_for_data['type'] = $fn_q_page_types_title_by_id[$pv->type];
				
				//pagins protegidas
				if(!$pv->protected)
				{
					$fn_xtemplate_parse['assign'][] = '';
					$fn_xtemplate_parse['parse'][] = "{$fn_page_args['stage_id']}.list_page.{$fn_type_page}.delAllow";
				}
				
				$fn_xtemplate_parse['assign'][] = $fn_for_data;
				$fn_xtemplate_parse['parse'][] = "{$fn_page_args['stage_id']}.list_page.{$fn_type_page}";
			}
		}else{
			$fn_xtemplate_parse['assign'][] = array(
				'type' => 'warning',
				'message' => 'Ahora mismo no hay páginas',
			);
			$fn_xtemplate_parse['parse'][] = "{$fn_page_args['stage_id']}.message";
		}
		
		//mostramos siempre el boton de crear uno nuevo
		$fn_xtemplate_parse['assign'][] = '';
		$fn_xtemplate_parse['parse'][] = "{$fn_page_args['stage_id']}.list_page";
		return;
	break;
}

?>