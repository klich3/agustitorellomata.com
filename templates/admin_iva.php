<?php

global $CONFIG, $fn_page_args, $db, $too_login;

if(!class_exists('tooLogin')) header("Location: {$CONFIG['site']['base']}error");
if($too_login->isLogged() !== 200) header("Location: {$CONFIG['site']['base']}admin/login");
if($too_login->isLogged() == 200 && $too_login->isAuth(100, false) !== 200) header("Location: {$CONFIG['site']['base']}{$st_lang}/mi-cuenta");

//check this year
$fn_this_year = date('Y');
$fn_q_iva = $db->FetchValue("
	SELECT `iva`
	FROM `iva`
	WHERE `year`=:yr
	LIMIT 1;
", array(
	'yr' => $fn_this_year,
));

$fn_iva = ($fn_q_iva) ? '<i class="uk-icon-check-circle uk-text-success"></i> Este año esta apuntado el iva (<strong>'.$fn_q_iva.'</strong>%)' : '<i class="uk-icon-warning uk-text-danger"></i> Atención este año no esta apuntado el iva. Por el momento se pondrá un valor por defecto ('.$CONFIG['site']['default_iva_percentage'].'%)';

$fn_xtemplate_parse['assign'][] = array(
	'check_this_year' => $fn_iva,
	'this_year' => $fn_this_year,
);
$fn_xtemplate_parse['parse'][] = '';

//todos los años
$fn_q = $db->FetchAll("
	SELECT *
	FROM `iva`
	ORDER BY `year` ASC;
");

if($fn_q)
{
	foreach($fn_q as $k => $v)
	{
		$fn_for_data = object_to_array($v);
		$fn_for_data['bg_class'] = ($v->year == $fn_this_year) ? 'bg-green' : 'bg-gray'; 
		
		$fn_xtemplate_parse['assign'][] = $fn_for_data;
		$fn_xtemplate_parse['parse'][] = 'admin_iva.iva_row';
	}
	
}else{
	$fn_xtemplate_parse['assign'][] = array(
		'type' => 'danger',
		'message' => 'No puedo acceder a la base de datos',
	);
	$fn_xtemplate_parse['parse'][] = 'admin_iva.message';
}

?>