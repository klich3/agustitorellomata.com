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
		
		//activamos boton de pago
		if($fn_redsys)
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
			if(isset($cv['pax_multimplier']) && $cv['pax_multimplier'] > 1)
			{
				$fn_xtemplate_parse['assign'][] = $cv;
				$fn_xtemplate_parse['parse'][] = 'checkout.cart.cart.cart_row.cajas';
			}
			
			$fn_xtemplate_parse['assign'][] = $cv;
			$fn_xtemplate_parse['parse'][] = 'checkout.cart.cart.cart_row';
		}
		
		$fn_xtemplate_parse['assign'][] = $fn_process_cart;
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
			
			$fn_xtemplate_parse['assign'][] = $fn_u_dir;
			$fn_xtemplate_parse['parse'][] = '';
		}
	}else{
		$fn_xtemplate_parse['assign'][] = '';
		$fn_xtemplate_parse['parse'][] = 'checkout.cart.nologged';
	}
	
	$fn_xtemplate_parse['assign'][] = '';
	$fn_xtemplate_parse['parse'][] = 'checkout.cart';
}

?>