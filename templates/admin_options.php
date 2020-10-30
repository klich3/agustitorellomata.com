<?php

global $CONFIG, $fn_page_args, $db, $too_login, $cl_m;

if(!class_exists('tooLogin')) header("Location: {$CONFIG['site']['base']}error");
if($too_login->isLogged() !== 200) header("Location: {$CONFIG['site']['base']}admin/login");
if($too_login->isLogged() == 200 && $too_login->isAuth(100, false, $CONFIG['site']['tooSType']) !== 200) header("Location: {$CONFIG['site']['base']}{$st_lang}/error");

$fn_g = $cl_m->parse("GET");
$fn_p = $cl_m->parse("POST");
$fn_r = $cl_m->parse("REQ");
$g_action = (isset($fn_g['action'])) ? $fn_g['action'] : null;

// ----------------------------------------------------------------------------------

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
	
	if($opv->options_key == 'dir') $fn_options_array["dir"] = (isJson($opv->options_value)) ? json_decode($opv->options_value, true) : json_decode('[{"placeTitle":"","dir":"","shortDir":"","gmapUrl":"","gps":"","post":"","country":"","city":"","tel":"​","telAlter":"","cif":""}]', true);
}

$fn_xtemplate_parse['assign'][] = $fn_options_array;
$fn_xtemplate_parse['parse'][] = '';


?>