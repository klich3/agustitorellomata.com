<?php

global $CONFIG, $fn_page_args, $db, $st_lang, $too_login, $lang_items;

if(!class_exists('tooLogin')) header("Location: {$CONFIG['site']['base']}error");
if($too_login->isLogged() !== 200) header("Location: {$CONFIG['site']['base']}login");

$fn_login_user_data = $too_login->getUserData();

if(!isset($fn_page_args['pedido_id'])) header("Location: {$CONFIG['site']['base']}error");

$fn_q = $db->FetchObject("
	SELECT `order_id`, `date`, `payment_status`, `entrega_status`, `num_seg`, `lang`, `data_cart`
	FROM `orders`
	WHERE `user_id`=:uid
	AND `order_id`=:oid
	ORDER BY `date` DESC;
", array(
	"uid" => $fn_login_user_data->ID,
	"oid" => $fn_page_args['pedido_id'],
));

if($fn_q)
{
	try{
		$fn_cart = base64_decode($fn_q->data_cart);
		$fn_cart = (isJson($fn_cart)) ? json_decode($fn_cart, true) : $fn_cart;
		
		$fn_cart['checkout']['checkout_id'] = $fn_page_args['pedido_id'];
		
		$fn_xtemplate_parse['assign'][] = $fn_cart['checkout'];
		$fn_xtemplate_parse['parse'][] = '';
		
		//procesamos carrito
		foreach($fn_cart['cart'] as $ck => $cv)
		{
			if(isset($cv['pax_multimplier']) && $cv['pax_multimplier'] > 1 && isset($cv['by_box']) && $cv['by_box'] == "1")
			{
				$fn_xtemplate_parse['assign'][] = $cv;
				$fn_xtemplate_parse['parse'][] = 'mis_pedidos_details.cart.row.by_box';
			}
			
			if(isset($cv['by_pax']) && $cv['by_pax'] == "1")
			{
				$fn_xtemplate_parse['assign'][] = $cv;
				$fn_xtemplate_parse['parse'][] = 'mis_pedidos_details.cart.row.by_pax';
			}
			
			if(isset($cv['by_box']) && $cv['by_box'] == "1" && isset($cv['by_pax']) && $cv['by_pax'] == "1")
			{
				$fn_xtemplate_parse['assign'][] = "";
				$fn_xtemplate_parse['parse'][] = 'mis_pedidos_details.cart.row.by_box_sep';
				
			}
			
			$fn_xtemplate_parse['assign'][] = $cv;
			$fn_xtemplate_parse['parse'][] = 'mis_pedidos_details.cart.row';
		}
		
		$fn_xtemplate_parse['assign'][] = $fn_cart;
		$fn_xtemplate_parse['parse'][] = 'mis_pedidos_details.cart';
		
	}catch(Exception $e)
	{
		$fn_xtemplate_parse['assign'][] = '';
		$fn_xtemplate_parse['parse'][] = 'mis_pedidos_details.no_pedidos';
	}
	
	$fn_xtemplate_parse['assign'][] = '';
	$fn_xtemplate_parse['parse'][] = 'mis_pedidos_details.pedidos';
}else{
	$fn_xtemplate_parse['assign'][] = '';
	$fn_xtemplate_parse['parse'][] = 'mis_pedidos_details.no_pedidos';
}

?>