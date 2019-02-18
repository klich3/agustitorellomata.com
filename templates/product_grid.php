<?php
	
global $CONFIG, $fn_page_args, $db, $st_lang;

/*
//var_dump($fn_page_args);

//defaults
$fn_xtemplate_parse['assign'][] = array(
	'c' => 1,
);
$fn_xtemplate_parse['parse'][] = '';
*/

//lista de categorias
$fn_q_cat_list = $db->FetchAll("
	SELECT *
	FROM `category`
");

if($fn_q_cat_list) foreach($fn_q_cat_list as $ck => $cv)
{
	$for_data = object_to_array($cv);
	$for_data['lang_data'] = decodeLangData($for_data['lang_data']);
	
	$fn_xtemplate_parse['assign'][] = $for_data;
	$fn_xtemplate_parse['parse'][] = "{$fn_page_args['stage_id']}.cat_row";
}
//lista de categorias

//lista de colecciones
//lista de colecciones

?>