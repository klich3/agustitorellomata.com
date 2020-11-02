<?php

global $CONFIG, $fn_page_args, $db, $too_login, $cl_m;

if(!class_exists('tooLogin')) header("Location: {$CONFIG['site']['base']}error");
if($too_login->isLogged() !== 200) header("Location: {$CONFIG['site']['base']}admin/login");
if($too_login->isLogged() == 200 && $too_login->isAuth(100, false, $CONFIG['site']['tooSType']) !== 200) header("Location: {$CONFIG['site']['base']}{$st_lang}/error");

$fn_g = $cl_m->parse("GET");
$fn_p = $cl_m->parse("POST");
$fn_r = $cl_m->parse("REQ");
$g_action = (isset($fn_g['action'])) ? $fn_g['action'] : null;

//lang row
$fn_lang = (isset($CONFIG['site']['lang']) && isJson($CONFIG['site']['lang'])) ? json_decode($CONFIG['site']['lang']) : false;
$fn_lang_ignore = (isset($CONFIG['site']['langIgnore']) && isJson($CONFIG['site']['langIgnore'])) ? json_decode($CONFIG['site']['langIgnore']) : false;

//ignore list
if($fn_lang_ignore) foreach($fn_lang_ignore as $lngi)
{
	$fn_xtemplate_parse['assign'][] = array(
		'lang' => $lngi,
	);
	$fn_xtemplate_parse['parse'][] = "{$fn_page_args['stage_id']}.lang_ignore_row";
}

$fn_lang_ignore_list = array_diff($fn_lang, $fn_lang_ignore);

foreach($fn_lang_ignore_list as $lngisel)
{
	
	$fn_xtemplate_parse['assign'][] = array(
		'code' => $lngisel,
	);
	$fn_xtemplate_parse['parse'][] = "{$fn_page_args['stage_id']}.lang_ignore_sel";
}
//ignore list

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
	
	//order
	$fn_xtemplate_parse['assign'][] = array(
		'code' => $tpk,
	);
	$fn_xtemplate_parse['parse'][] = "{$fn_page_args['stage_id']}.lang_order_row";
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
	
	if($opv->options_key == 'langSelector') $fn_options_array["langSelector_selected_{$opv->options_value}"] = 'selected';
	if($opv->options_key == 'showHeaderLangs') $fn_options_array["showHeaderLangs_{$opv->options_value}"] = 'selected';
	if($opv->options_key == 'showHeaderLangsMob') $fn_options_array["showHeaderLangsMob_{$opv->options_value}"] = 'selected';
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