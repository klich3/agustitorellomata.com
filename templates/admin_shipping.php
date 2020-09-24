<?php

global $CONFIG, $fn_page_args, $db, $too_login, $cl_m;

if(!class_exists('tooLogin')) header("Location: {$CONFIG['site']['base']}error");
if($too_login->isLogged() !== 200) header("Location: {$CONFIG['site']['base']}admin/login");
if($too_login->isLogged() == 200 && $too_login->isAuth(100, false) !== 200) header("Location: {$CONFIG['site']['base']}{$st_lang}/mi-cuenta");

$fn_g = $cl_m->parse("GET");
$fn_p = $cl_m->parse("POST");
$fn_r = $cl_m->parse("REQ");
$g_action = (isset($fn_g['action'])) ? $fn_g['action'] : null;

//idiomas
$fn_lang = (isset($CONFIG['site']['lang']) && isJson($CONFIG['site']['lang'])) ? json_decode($CONFIG['site']['lang']) : false;

//country_sel
/*
$fn_q_sel_country = $db->FetchAll("
	SELECT *
	FROM `apps_countries`
");


if($fn_q_sel_country) foreach($fn_q_sel_country as $ck => $cv)
{
	$fn_for_data = object_to_array($cv);
	$fn_for_data['country_code'] = strtolower($cv->country_code);
	
	$fn_xtemplate_parse['assign'][] = $fn_for_data;
	$fn_xtemplate_parse['parse'][] = 'admin_clientes.country_sel';
}
*/
//end country_sel

//modal lang
if($fn_lang) foreach($fn_lang as $l)
{
	$fn_for_data = array(
		'lang' => $l,
	);
	
	//modal servicios
	$fn_xtemplate_parse['assign'][] = $fn_for_data;
	$fn_xtemplate_parse['parse'][] = 'admin_shipping.servicio_lang_item';
}
//modal lang

switch($g_action)
{
	case "detailsTarifas":
		//add form
		$fn_q_sel_country = $db->FetchAll("
			SELECT *
			FROM `apps_countries`
		");
		
		if($fn_q_sel_country) foreach($fn_q_sel_country as $csk => $csv)
		{
			$fn_for_data = object_to_array($csv);
			
			$fn_xtemplate_parse['assign'][] = $fn_for_data;
			$fn_xtemplate_parse['parse'][] = 'admin_shipping.tarifas.country_sel_row';
		}
		
		//load data table
		if(!isset($fn_g['s_id']) && empty($fn_p['s_id']) || !isset($fn_g['t_id']) && empty($fn_p['t_id']))
		{
			$fn_xtemplate_parse['assign'][] = array(
				'type' => 'danger',
				'message' => 'Para mostrar los datos necesito unos ids minimos.',
			);
			$fn_xtemplate_parse['parse'][] = 'admin_shipping.message';
		}else{
		
			$fn_q_prices = $db->FetchAll("
				SELECT c.`country_name`, t.`kg`, t.`precio`, t.`id`
				FROM `apps_countries` c
				LEFT JOIN `shipping_tarifas` t ON(t.`c_id`=c.`id`)
				WHERE t.`s_id`=:sid
				AND t.`t_id`=:tid
				ORDER BY c.`country_name`, t.`kg` ASC
			", array(
				'sid' => $fn_g['s_id'],
				'tid' => $fn_g['t_id'],
			));
			
			if($fn_q_prices) foreach($fn_q_prices as $pk => $pv)
			{
				$fn_for_data = object_to_array($pv);
				
				$fn_xtemplate_parse['assign'][] = $fn_for_data;
				$fn_xtemplate_parse['parse'][] = 'admin_shipping.tarifas.row';
			}
		}
		
		$fn_xtemplate_parse['assign'][] = array(
			's_id' => $fn_g['s_id'],
			't_id' => $fn_g['t_id'],
		);
		$fn_xtemplate_parse['parse'][] = 'admin_shipping.tarifas';
		return;
	break;
	
	case "editCompany":
		$fn_comp_active_q = $db->FetchValue("
			SELECT `active`
			FROM `shipping_companies`
			WHERE `id`=:id
			LIMIT 1;
		", array(
			'id' => $fn_g['id'],
		));
		
		$fn_q_type = $db->FetchAll("
			SELECT *
			FROM `shipping_types`
		");
		
		if($fn_q_type) foreach($fn_q_type as $tk => $tv)
		{
			$fn_for_data = object_to_array($tv);
			
			//category title
			$fn_lang_data = (isset($tv->lang_data) && isJson($tv->lang_data)) ? object_to_array(json_decode($tv->lang_data)) : false;
				
			//title langs
			$fn_title_out = array();
			
			if(sizeof($fn_lang_data) !== 0) foreach($fn_lang as $cak)
			{
				if(!isset($fn_lang_data[$cak])) continue;
				
				$fn_title_out[] = $fn_lang_data[$cak];
			}
			
			//rel
			$fn_q_rel_types = $db->FetchValue("
				SELECT count(*)
				FROM `shipping_types_rel`
				WHERE `s_id`=:id
				AND `t_id`=:tid
				LIMIT 1;
			", array(
				'id' => $fn_g['id'],
				'tid' => $tv->id,
			));
						
			$fn_for_data['active_checked'] = ($fn_q_rel_types) ? 'checked' : '';
			
			//title
			$fn_for_data['serv_title'] = (!empty($tv->lang_data) && sizeof($fn_title_out) !== 0) ? implode(" | ", $fn_title_out) : 'Sin traduccíon';
			
			$fn_for_data['s_id'] = $fn_g['id'];
			
			$fn_xtemplate_parse['assign'][] = $fn_for_data;
			$fn_xtemplate_parse['parse'][] = 'admin_shipping.list.details.serv_row';
		}
		
		$fn_xtemplate_parse['assign'][] = array(
			'id' => $fn_g['id'],
			'active_checked' => ($fn_comp_active_q) ? 'checked' : '',
		);
		$fn_xtemplate_parse['parse'][] = 'admin_shipping.list.details';
	break;
}

//list companies
$fn_q_list = $db->FetchAll("
	SELECT *
	FROM `shipping_companies`
");

if($fn_q_list)
{
	foreach($fn_q_list as $lk => $lv)
	{
		$fn_for_data = object_to_array($lv);
		$fn_for_data['active'] = ($lv->active) ? '<i class="uk-icon-eye uk-text-success"></i> Activo' : '<i class="uk-icon-eye-slash uk-text-danger"></i> Desactivado';
		
		//selected on edit
		if(isset($fn_g['action']) && isset($fn_g['id']) && $fn_g['action'] == 'editCompany') $fn_for_data['active_row'] = ($lv->id == $fn_g['id']) ? 'class="bg-green"' : '';
		
		$fn_xtemplate_parse['assign'][] = $fn_for_data;
		$fn_xtemplate_parse['parse'][] = 'admin_shipping.list.row';
	}	
	
	$fn_xtemplate_parse['assign'][] = '';
	$fn_xtemplate_parse['parse'][] = 'admin_shipping.list';
}else{
	$fn_xtemplate_parse['assign'][] = array(
		'type' => 'danger',
		'message' => 'Ahora mismo no hay compañías de envíos',
	);
	$fn_xtemplate_parse['parse'][] = 'admin_shipping.message';
}

?>