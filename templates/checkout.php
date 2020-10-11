<?php

global $CONFIG, $fn_page_args, $db, $st_lang, $lang_items, $too_login;

if(!class_exists('tooLogin')) header("Location: {$CONFIG['site']['base']}error");

if(getCartCount() == '0')
{
	$fn_xtemplate_parse['assign'][] = array(
		'message' => getLangItem('msg_cart_no_hay_compras'),
	);
	$fn_xtemplate_parse['parse'][] = 'checkout.no_cart';
}else{
	
	$fn_process_cart = cartProcessAndCalc($_SESSION);
	
	//selector año
	$fn_y = date('y');
	for($i = $fn_y; $i <= ($fn_y+10); $i++)
	{
		$fn_xtemplate_parse['assign'][] = array(
			'val' => $i,
		);
		$fn_xtemplate_parse['parse'][] = 'checkout.cart.year_row';
	}
	//end selector año
	
	//paises
	$fn_q_country = $db->FetchAll("
		SELECT `country_code` AS 'c', `country_name` AS 'n'
		FROM `apps_countries`
		WHERE `active`='1'
		ORDER BY `order` DESC, `country_code` ASC;
	");
	
	if($fn_q_country)
	{
		foreach($fn_q_country as $ck => $cv)
		{
			$fn_for_data = object_to_array($cv);
			
			$fn_xtemplate_parse['assign'][] = $fn_for_data;
			$fn_xtemplate_parse['parse'][] = 'checkout.cart.contry_list_row';
		}
	}else{
		$fn_xtemplate_parse['assign'][] = array(
			'c' => null,
			'n' => getLangItem('country_list_error'),
		);
		$fn_xtemplate_parse['parse'][] = 'checkout.cart.contry_list_row';
	}
	//end paises
	
	//carrito lleno o no
	if($fn_process_cart)
	{
		//tipos de pagos
		$fn_redsys = $db->FetchValue("
			SELECT `options_value`
			FROM `options`
			WHERE `options_key`='redsys_active'
			LIMIT 1;
		");
		
		if($fn_redsys)
		{
			$fn_xtemplate_parse['assign'][] = '';
			$fn_xtemplate_parse['parse'][] = 'checkout.cart.pay_rd';
		}
		
		$fn_paypal = $db->FetchValue("
			SELECT `options_value`
			FROM `options`
			WHERE `options_key`='paypal_active'
			LIMIT 1;
		");
		
		if($fn_paypal)
		{
			$fn_xtemplate_parse['assign'][] = '';
			$fn_xtemplate_parse['parse'][] = 'checkout.cart.pay_pp';
		}
		
		//activamos boton de pago
		if($fn_paypal || $fn_redsys)
		{
			$fn_xtemplate_parse['assign'][] = '';
			$fn_xtemplate_parse['parse'][] = 'checkout.cart.cart.pay_button';
		}else{
			$fn_xtemplate_parse['assign'][] = '';
			$fn_xtemplate_parse['parse'][] = 'checkout.cart.no_payment_actived';
		}
		
		//procesamos carrito
		foreach($fn_process_cart['cart'] as $ck => $cv)
		{
			$fn_xtemplate_parse['assign'][] = $cv;
			$fn_xtemplate_parse['parse'][] = 'checkout.cart.cart.cart_row';
		}
		
		$fn_data_out = $cv;
		
		if(isset($fn_process_cart['cart_checkout']) && isset($fn_process_cart['cart_wiva_checkout']))
		{
			$fn_data_out['cart_iva_percent'] = $fn_process_cart['cart_checkout']['cart_iva_percent'];
			/*
			$fn_data_out['cart_iva'] = $fn_process_cart['cart_checkout']['cart_iva'];
			$fn_data_out['cart_subtotal'] = $fn_process_cart['cart_checkout']['cart_subtotal'];
			*/
			$fn_data_out['cart_shipping_cost'] = (isset($fn_process_cart['cart_checkout']['cart_shipping_cost'])) ?  $fn_process_cart['cart_checkout']['cart_shipping_cost'] : 0;
			
			$fn_data_out['cart_iva'] = $fn_process_cart['cart_wiva_checkout']['cart_iva'];
			$fn_data_out['cart_subtotal'] = round($fn_process_cart['cart_wiva_checkout']['cart_subtotal']-$fn_process_cart['cart_wiva_checkout']['cart_iva'] , 2);
			
			$fn_data_out['cart_total'] = $fn_process_cart['cart_wiva_checkout']['cart_subtotal'];
		}
		
		$fn_xtemplate_parse['assign'][] = $fn_data_out;
		$fn_xtemplate_parse['parse'][] = 'checkout.cart.cart';
	}else{
		$fn_xtemplate_parse['assign'][] = array(
			'message' => getLangItem('cart_error_generar'), 
		);
		$fn_xtemplate_parse['parse'][] = 'checkout.cart.no_cart';
	}
	
	//rellenamos datos si estamos logeados
	if($too_login->isLogged() == 200)
	{
		$fn_login_user_data = $too_login->getUserData();
		
		//user data
		$fn_user_datos = $db->FetchValue("
			SELECT `meta_value`
			FROM `users_meta`
			WHERE `user_id`=:uid
			AND `meta_key`='user_pers_data'
			LIMIT 1;
		", array(
			'uid' => $fn_login_user_data->ID,
		));
		
		if($fn_user_datos)
		{
			$fn_ud = (!empty($fn_user_datos) && isJson($fn_user_datos)) ? json_decode($fn_user_datos, true) : false;
			
			//u_email
			$fn_u_data = $too_login->getUserData();
			$fn_ud['u_email'] = $fn_u_data->user_email;
			
			$fn_xtemplate_parse['assign'][] = $fn_ud;
			$fn_xtemplate_parse['parse'][] = '';
		}
		
		//check user data
		$fn_user_meta = $db->FetchValue("
			SELECT `meta_value`
			FROM `users_meta`
			WHERE `user_id`=:uid
			AND `meta_key`='user_dirs'
			LIMIT 1;
		", array(
			'uid' => $fn_login_user_data->ID,
		));
		
		//direcicon de usuario
		if($fn_user_meta)
		{
			$fn_u_dir = (!empty($fn_user_meta) && isJson($fn_user_meta)) ? json_decode($fn_user_meta, true) : false;
			
			if($fn_u_dir) foreach($fn_u_dir as $udk => $udv)
			{
				if($udv['dir_default'] == 1)
				{
					$fn_u_dir = $udv;
					break;
				}
			}
			
			$fn_xtemplate_parse['assign'][] = $fn_u_dir;
			$fn_xtemplate_parse['parse'][] = '';
		}else{
			$fn_xtemplate_parse['assign'][] = '';
			$fn_xtemplate_parse['parse'][] = 'checkout.cart.no_dir';
		}
	}else{
		$fn_xtemplate_parse['assign'][] = '';
		$fn_xtemplate_parse['parse'][] = 'checkout.cart.nologged';
	}
	
	$fn_xtemplate_parse['assign'][] = '';
	$fn_xtemplate_parse['parse'][] = 'checkout.cart';
}

?>