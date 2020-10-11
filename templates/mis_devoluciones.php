<?php

global $CONFIG, $fn_page_args, $db, $st_lang, $too_login;

if(!class_exists('tooLogin')) header("Location: {$CONFIG['site']['base']}error");
if($too_login->isLogged() !== 200) header("Location: {$CONFIG['site']['base']}login");

$fn_login_user_data = $too_login->getUserData();

$fn_q = $db->FetchAll("
	SELECT `order_id`, `date`, `payment_status`, `entrega_status`, `num_seg`, `lang`, `data_cart`
	FROM `orders`
	WHERE `user_id`=:uid
	ORDER BY `date` DESC;
", array(
	'uid' => $fn_login_user_data->ID,
));

if($fn_q)
{
	foreach($fn_q as $pk => $pv)
	{
		$fn_xtemplate_parse['assign'][] = array(
			'order_id' => $pv->order_id,
		);
		$fn_xtemplate_parse['parse'][] = 'mis_devoluciones.pedidos.sel_row';
	}
	
	$fn_xtemplate_parse['assign'][] = '';
	$fn_xtemplate_parse['parse'][] = 'mis_devoluciones.pedidos';
}else{
	$fn_xtemplate_parse['assign'][] = '';
	$fn_xtemplate_parse['parse'][] = 'mis_devoluciones.no_pedidos';
}

?>