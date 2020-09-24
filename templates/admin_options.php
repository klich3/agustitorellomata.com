<?php

global $CONFIG, $fn_page_args, $db, $too_login, $cl_m;

if(!class_exists('tooLogin')) header("Location: {$CONFIG['site']['base']}error");
if($too_login->isLogged() !== 200) header("Location: {$CONFIG['site']['base']}admin/login");
if($too_login->isLogged() == 200 && $too_login->isAuth(100, false, $CONFIG['site']['tooSType']) !== 200) header("Location: {$CONFIG['site']['base']}{$st_lang}/error");

$fn_g = $cl_m->parse("GET");
$fn_p = $cl_m->parse("POST");
$fn_r = $cl_m->parse("REQ");
$g_action = (isset($fn_g['action'])) ? $fn_g['action'] : null;

//select pag inicial
$fn_q_sel_init_p = $db->FetchAll("
	SELECT *
	FROM `pages`
");

if($fn_q_sel_init_p) foreach($fn_q_sel_init_p as $slk => $slv)
{
	$fn_for_data = object_to_array($slv);
	
	$fn_for_data['selected'] = ($CONFIG['site']['initial_page'] == $slv->obj_hash) ? 'selected' : '';
	
	$fn_xtemplate_parse['assign'][] = $fn_for_data;
	$fn_xtemplate_parse['parse'][] = "{$fn_page_args['stage_id']}.sel_init_page";	
}

//tipo de header
$fn_header_type = (isset($CONFIG['site']['initial_page_header_type'])) ? $CONFIG['site']['initial_page_header_type'] : 'small';

$fn_xtemplate_parse['assign'][] = array(
	"selected_{$fn_header_type}" => 'selected'
);
$fn_xtemplate_parse['parse'][] = '';

//lang row
$fn_lang = (isset($CONFIG['site']['lang']) && isJson($CONFIG['site']['lang'])) ? json_decode($CONFIG['site']['lang']) : false;

if($fn_lang) foreach($fn_lang as $tpk)
{
	$fn_xtemplate_parse['assign'][] = array(
		'lang' => $tpk,
	);
	$fn_xtemplate_parse['parse'][] = "{$fn_page_args['stage_id']}.lang_row";

	//table traducciones rows
	$fn_xtemplate_parse['assign'][] = array(
		'lang' => $tpk,
	);
	$fn_xtemplate_parse['parse'][] = "{$fn_page_args['stage_id']}.lang_table_row";
	
	//options default lang
	$fn_xtemplate_parse['assign'][] = array(
		'lang' => $tpk,
		'selected' => ($tpk == $CONFIG['site']['defaultLang']) ? 'selected' : '',
	);
	$fn_xtemplate_parse['parse'][] = "{$fn_page_args['stage_id']}.sel_def_lang";
}

$fn_lang_sel_q = $db->FetchAll("
	SELECT c.*, ac.*
	FROM `countries` c
	LEFT JOIN `apps_countries` ac ON(ac.`country_code`=c.`code`)
	ORDER BY `country_name` ASC
");

if($fn_lang_sel_q) foreach($fn_lang_sel_q as $slk => $slv)
{
	$fn_for_data = object_to_array($slv);
	
	$fn_xtemplate_parse['assign'][] = $fn_for_data;
	$fn_xtemplate_parse['parse'][] = "{$fn_page_args['stage_id']}.lang_sel";
}

// ----------------------------------------------------------------------------------

$fn_options_q = $db->FetchAll("
	SELECT *
	FROM `options`
");

$fn_options_array = array();

if($fn_options_q) foreach($fn_options_q as $opk => $opv)
{
	$fn_options_array[$opv->options_key] = $opv->options_value;
	
	if($opv->options_key == 'redsys_mode' && $opv->options_value == 1) $fn_options_array["selected_{$opv->options_value}"] = 'selected';
	if($opv->options_key == 'home_show_testimonios') $fn_options_array["home_show_testimonios_selected_{$opv->options_value}"] = 'selected';
	if($opv->options_key == 'langSelector') $fn_options_array["langSelector_selected_{$opv->options_value}"] = 'selected';
	
	if($opv->options_key == 'showHeaderLangs') $fn_options_array["showHeaderLangs_{$opv->options_value}"] = 'selected';
	if($opv->options_key == 'showHeaderLangsMob') $fn_options_array["showHeaderLangsMob_{$opv->options_value}"] = 'selected';
	
	if($opv->options_key == 'dir') $fn_options_array["dir"] = (isJson($opv->options_value)) ? json_decode($opv->options_value, true) : json_decode('[{"placeTitle":"","dir":"","shortDir":"","gmapUrl":"","gps":"","post":"","country":"","city":"","tel":"​","telAlter":"","cif":""}]', true);
}

$fn_xtemplate_parse['assign'][] = $fn_options_array;
$fn_xtemplate_parse['parse'][] = '';

// ----------------------------------------------------------------------------------

//traducciones

$fn_lang_q = $db->FetchAll("
	SELECT * 
	FROM `lang`
");

$fn_lang_data = array();

if($fn_lang_q)
{
	//creamos keys
	
	foreach($fn_lang_q as $lk => $lv)
	{
		if(!in_array($lv->lang_key, $fn_lang_data))
		{
			$fn_lang_data[$lv->lang_key] = array();
		}
	}

	//rellenamos
	foreach($fn_lang_q as $llk => $llv)
	{
		$fn_lang_data[$llv->lang_key][$llv->lang_type] = $llv->lang_value;
	}
	
	foreach($fn_lang_data as $ldk => $ldv)
	{
		$for_lang_rows = '';
		
		foreach($fn_lang as $gl)
		{
			$fn_xtemplate_parse['assign'][] = array(
				'key' => $ldk,
				'lang_k' => $gl,
				'lang_v' => (isset($ldv[$gl])) ? $ldv[$gl] : '',
			);
			$fn_xtemplate_parse['parse'][] = "{$fn_page_args['stage_id']}.lang_table_value.lang_table_value_input";
		}
		
		
		$fn_xtemplate_parse['assign'][] = array(
			'key' => $ldk,
		);
		$fn_xtemplate_parse['parse'][] = "{$fn_page_args['stage_id']}.lang_table_value";
	}
}

?>