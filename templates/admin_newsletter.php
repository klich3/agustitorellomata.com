<?php

global $CONFIG, $fn_page_args, $db, $too_login;

if(!class_exists('tooLogin')) header("Location: {$CONFIG['site']['base']}error");
if($too_login->isLogged() !== 200) header("Location: {$CONFIG['site']['base']}admin/login");
if($too_login->isLogged() == 200 && $too_login->isAuth(100, false) !== 200) header("Location: {$CONFIG['site']['base']}{$st_lang}/mi-cuenta");

//modal produtos
$fn_q_nws = $db->FetchAll("
	SELECT *
	FROM `newsletter`
	ORDER BY `date_submit` ASC
");

if($fn_q_nws) foreach($fn_q_nws as $mk => $mv)
{
	$fn_foritem_data = object_to_array($mv);
	
	$fn_q_of = $db->FetchValue("
		SELECT `title`
		FROM `ofertas`
		WHERE `id`=:id
		LIMIT 1;
	", array(
		'id' => $mv->oid,
	));
	
	$fn_foritem_data['oferta_title'] = ($fn_q_of) ? $fn_q_of : 'Oferta eliminada';
	
	$fn_xtemplate_parse['assign'][] = $fn_foritem_data;
	$fn_xtemplate_parse['parse'][] = 'admin_newsletter.row';
}
//modal produtos

?>