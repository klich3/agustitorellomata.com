<?php

global $CONFIG, $fn_page_args, $db, $st_lang, $too_login, $lang_items;

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
		$fn_for_data = object_to_array($pv);
		
		$fn_for_data['date'] = ($pv->date) ? date('Y-m-d', strtotime($pv->date)) : '';
		
		$fn_for_data['payment_status'] = ($pv->payment_status) ? getLangItem('order_pagado') : getLangItem('order_no_pagado');
		
		$fn_for_data['entrega_status'] = ($pv->entrega_status) ? getLangItem('order_entregado') : getLangItem('order_no_entregado');
		
		$fn_for_data['num_seg'] = ($pv->num_seg) ? $pv->num_seg : getLangItem('order_no_tracking_no');
		
		$fn_xtemplate_parse['assign'][] = $fn_for_data;
		$fn_xtemplate_parse['parse'][] = 'mis_pedidos.pedidos.row';
	}
	
	$fn_xtemplate_parse['assign'][] = '';
	$fn_xtemplate_parse['parse'][] = 'mis_pedidos.pedidos';
}else{
	$fn_xtemplate_parse['assign'][] = '';
	$fn_xtemplate_parse['parse'][] = 'mis_pedidos.no_pedidos';
}

?>