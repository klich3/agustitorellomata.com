<?php

global $CONFIG, $fn_page_args, $db, $too_login;

if(!class_exists('tooLogin')) header("Location: {$CONFIG['site']['base']}error");
if($too_login->isLogged() !== 200) header("Location: {$CONFIG['site']['base']}admin/login");
if($too_login->isLogged() == 200 && $too_login->isAuth(100, false, $CONFIG['site']['tooSType']) !== 200) header("Location: {$CONFIG['site']['base']}{$st_lang}/mi-cuenta");

//modal produtos
$fn_modal_q = $db->FetchAll("
	SELECT *
	FROM `product`
	WHERE `active`='1'
	ORDER BY `order` ASC
");

if($fn_modal_q) foreach($fn_modal_q as $mk => $mv)
{
	$fn_foritem_data = object_to_array($mv);
	
	$fn_foritem_data['item_title'] = langTitleJsonToStringJointer($mv->lang_data);
	
	$fn_xtemplate_parse['assign'][] = $fn_foritem_data;
	$fn_xtemplate_parse['parse'][] = "{$fn_page_args['stage_id']}.prod_row";
}
//modal produtos

//ofertas list
$fn_q = $db->FetchAll("
	SELECT *
	FROM `ofertas`
	ORDER BY `date_update`, `active` DESC;
");

if($fn_q) foreach($fn_q as $ok => $ov)
{
	$fn_for_data = object_to_array($ov);
	
	$fn_for_data['tab'] = ($ov->tab_active) ? 'bookmark uk-text-success' : 'bookmark-o uk-text-danger';
	$fn_for_data['active'] = ($ov->active) ? 'toggle-on uk-text-success' : 'toggle-off uk-text-danger';
	
	if($ov->p_id == 0)
	{
		$fn_for_data['type_prod'] = 'Oferta global';
	}else{
		$fn_prod_name = $db->FetchValue("
			SELECT `lang_data`
			FROM `product`
			WHERE `id`=:id
			LIMIT 1;
		", array(
			'id' => $ov->p_id,
		));
		
		$fn_for_data['type_prod'] = (isJson($fn_prod_name)) ? langTitleJsonToStringJointer($fn_prod_name) : '';
		
	}
	
	$fn_xtemplate_parse['assign'][] = $fn_for_data;
	$fn_xtemplate_parse['parse'][] = "{$fn_page_args['stage_id']}.list_row";
}

?>