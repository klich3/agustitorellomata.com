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
	
	//type menu modal
	$fn_menu_type = $db->FetchAll("
		SELECT * 
		FROM `menus_types`
	");
	
	foreach($fn_menu_type as $tk => $tv)
	{
		$fn_xtemplate_parse['assign'][] = $tv;
		$fn_xtemplate_parse['parse'][] = "{$fn_page_args['stage_id']}.type_sel_modal";
	}
}

switch($g_action)
{
	case "editMenus":
		$fn_q_menus = $db->FetchArray("
			SELECT *
			FROM `menus`
			WHERE `id`=:id
			LIMIT 1
		", array(
			'id' => (int)$fn_g['id'],
		));
		
		if($fn_q_menus)
		{
			$fn_q_meta = $db->FetchAll("
				SELECT *
				FROM `menus_meta`
				WHERE `m_id`=:i
			", array(
				'i' => $fn_q_menus['id'],
			));
			
			$fn_q_structures = $db->FetchAll("
				SELECT *
				FROM `menus_structure`
				WHERE `m_id`=:i
				ORDER BY `order` ASC
			", array(
				'i' => $fn_q_menus['id'],
			));
			
			//page list selector
			$fn_q_page_list = $db->FetchAll("
				SELECT `id`, `obj_hash`, `obj_title`, `lang`
				FROM `pages`
				WHERE `active`='1'
			");
			
			if(sizeof($fn_q_page_list) !== 0) foreach($fn_q_page_list as $pk => $pv)
			{
				$fn_xtemplate_parse['assign'][] = $pv;
				$fn_xtemplate_parse['parse'][] = "{$fn_page_args['stage_id']}.editPage.pag_row";
			}
			//page list selector
			
			//menus rows
			if($fn_q_structures)
			{
				$fn_menu_tree = menu_proccess_object($fn_q_structures);
				
				$fn_totla_mv = sizeof($fn_menu_tree);
				
				foreach($fn_menu_tree as $mk => $mv)
				{
					//level 1 -----------------------------------------------------------------
					if(isset($mv['sublevel']))
					{
						$fn_total_sublevel = sizeof($mv['sublevel']);
						
						foreach($mv['sublevel'] as $sk => $sv)
						{
							$fn_sv_page_id = (isset($sv['p_id'])) ? $sv['p_id'] : false;
							
							$sv['p_title'] = (isset($sv['title']) && !empty($sv['title'])) ? $sv['title'] : '';
							$sv['p_url'] = (isset($sv['url']) && !empty($sv['url'])) ? $sv['url'] : '';
							
							if($fn_sv_page_id)
							{
								try{
									$fn_p_data_l1 = $db->FetchArray("
										SELECT *
										FROM `pages`
										WHERE `active`='1'
										AND `id`=:i
										LIMIT 1;
									", array(
										'i' => $fn_sv_page_id,
									));
									
									if(isset($sv['title']) && empty($sv['title'])) $sv['title'] = $fn_p_data_l1['obj_title'];
									if(isset($sv['url']) && empty($sv['url'])) $sv['url'] = "{$CONFIG['site']['base_script']}{$st_lang}/{$fn_p_data_l1['obj_hash']}";
								}catch(Exception $e)
								{
									
								}
								
							}
							
							$sv['active_class'] = ($sv['active']) ? '' : 'bg-grey';
							
							if(isset($sv['p_id']) && $sv['p_id'] !== '0')
							{
								$fn_xtemplate_parse['assign'][] = $pv;
								$fn_xtemplate_parse['parse'][] = "{$fn_page_args['stage_id']}.editPage.m_item.parent.row.edit_but";
							}
							
							
							$fn_xtemplate_parse['assign'][] = $sv;
							$fn_xtemplate_parse['parse'][] = "{$fn_page_args['stage_id']}.editPage.m_item.parent.row";
							
							//anti loop
							if($sk > ($fn_total_sublevel-1) ) break;
						}
						
						$fn_xtemplate_parse['assign'][] = array(
							'class_parent' => 'parent',
						);
						$fn_xtemplate_parse['parse'][] = "{$fn_page_args['stage_id']}.editPage.m_item.parent";
					}
					
					//level 0 -----------------------------------------------------------------
					
					$fn_mv_page_id = (isset($mv['p_id'])) ? $mv['p_id'] : false;
					
					$mv['p_title'] = (isset($mv['title']) && !empty($mv['title'])) ? $mv['title'] : '';
					$mv['p_url'] = (isset($mv['url']) && !empty($mv['url'])) ? $mv['url'] : '';
					
					if($fn_mv_page_id)
					{
						$fn_p_data = $db->FetchArray("
							SELECT *
							FROM `pages`
							WHERE `active`='1'
							AND `id`=:i
							LIMIT 1;
						", array(
							'i' => $fn_mv_page_id,
						));
						
						if(isset($mv['title']) && empty($mv['title'])) $mv['title'] = $fn_p_data['obj_title'];
						if(isset($mv['url']) && empty($mv['url'])) $mv['url'] = "{$CONFIG['site']['base_script']}{$st_lang}/{$fn_p_data['obj_hash']}";
					}
					
					$mv['active_class'] = ($mv['active']) ? '' : 'bg-grey';
					
					if(isset($mv['p_id']) && $mv['p_id'] !== '0')
					{
						$fn_xtemplate_parse['assign'][] = $mv;
						$fn_xtemplate_parse['parse'][] = "{$fn_page_args['stage_id']}.editPage.m_item.edit_but";
					}
					
					$fn_xtemplate_parse['assign'][] = $mv;
					$fn_xtemplate_parse['parse'][] = "{$fn_page_args['stage_id']}.editPage.m_item";
					
					//anti loop
					if($mk > ($fn_totla_mv-1) ) break;
					//
				}
			}
			//menus rows
			
			$fn_xtemplate_parse['assign'][] = array(
				'm_id' => $fn_q_menus['id'],
			);
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
	
	case "list":
	default:
		
		//select pag inicial
		$fn_q_pages = $db->FetchAll("
			SELECT *
			FROM `menus`
			ORDER BY `create_date` ASC
		");
		
		if($fn_q_pages)
		{
			foreach($fn_q_pages as $pk => $pv)
			{
				$fn_for_data = object_to_array($pv);
				
				$fn_for_data['active_classes'] = ($pv->active) ? 'uk-icon-eye uk-text-success' : 'uk-icon-eye-slash uk-text-danger';
				
				//lang
				/*
				$fn_q_lang_name = $db->FetchValue("
					SELECT `country_name`
					FROM `apps_countries`
					WHERE `country_code`=UPPER(:ln)
					LIMIT 1;
				", array(
					'ln' => $pv->lang,
				));
				*/
				
				//typo de menu
				$fn_q_menu_type = $db->FetchValue("
					SELECT `title`
					FROM `menus_types`
					WHERE `id`=:i
					LIMIT 1;
				", array(
					'i' => $fn_for_data['m_type'],
				));
				
				$fn_for_data['type'] = ($fn_q_menu_type) ? $fn_q_menu_type : '';
				
				$fn_xtemplate_parse['assign'][] = $fn_for_data;
				$fn_xtemplate_parse['parse'][] = "{$fn_page_args['stage_id']}.list.row";
			}
		}else{
			$fn_xtemplate_parse['assign'][] = array(
				'type' => 'warning',
				'message' => 'Ahora mismo no hay menús creados',
			);
			$fn_xtemplate_parse['parse'][] = "{$fn_page_args['stage_id']}.message";
		}
		
		//mostramos siempre el boton de crear uno nuevo
		$fn_xtemplate_parse['assign'][] = '';
		$fn_xtemplate_parse['parse'][] = "{$fn_page_args['stage_id']}.list";
		return;
	break;
}

?>