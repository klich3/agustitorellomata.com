<?php

global $CONFIG, $fn_page_args, $db, $st_lang, $lang_items;

if(!class_exists('tooLogin')) header("Location: {$CONFIG['site']['base']}error");

if($too_login->isLogged() == 400)
{
	$fn_xtemplate_parse['assign'][] = array(
		'message' => getLangItem('cart_error_generar'),
	);
	$fn_xtemplate_parse['parse'][] = 'cart.no_cart';
}else{
	
	if(getCartCount() == 0)
	{
		$fn_xtemplate_parse['assign'][] = array(
			'message' => $lang_items[$st_lang]['msg_cart_no_hay_compras'],
		);
		$fn_xtemplate_parse['parse'][] = 'cart.no_cart';
	}else{
		$fn_process_cart = cartProcessAndCalc($_SESSION);
		
		$fn_dir_data = array();
		$fn_shipping_data = array();
			
		//calc stage
		if($fn_process_cart)
		{
			foreach($fn_process_cart['cart'] as $ck => $cv)
			{
				if(!$cv['thumb']) $cv['thumb'] = "{$CONFIG['site']['base']}images/nofoto.png";
				
				if(!isset($cv['pax'])) $cv['pax'] = 1;
				if(!isset($cv['multimplier'])) $cv['multimplier'] = 0;
				
				
				if(isset($cv['pax_multimplier']) && $cv['pax_multimplier'] > 1 && isset($cv['by_box']) && $cv['by_box'] == "1")
				{
					$fn_xtemplate_parse['assign'][] = $cv;
					$fn_xtemplate_parse['parse'][] = 'cart.cart.row.by_box';
				}
				
				if(isset($cv['by_pax']) && $cv['by_pax'] == "1")
				{
					$fn_xtemplate_parse['assign'][] = $cv;
					$fn_xtemplate_parse['parse'][] = 'cart.cart.row.by_pax';
				}
				
				if(isset($cv['by_box']) && $cv['by_box'] == "1" && isset($cv['by_pax']) && $cv['by_pax'] == "1")
				{
					$fn_xtemplate_parse['assign'][] = "";
					$fn_xtemplate_parse['parse'][] = 'cart.cart.row.by_box_sep';
					
				}
				
				$fn_xtemplate_parse['assign'][] = $cv;
				$fn_xtemplate_parse['parse'][] = 'cart.cart.row';
			}
			
			$fn_xtemplate_parse['assign'][] = $fn_process_cart['checkout'];
			$fn_xtemplate_parse['parse'][] = 'cart.cart';
		}else{
			$fn_xtemplate_parse['assign'][] = array(
				'message' => getLangItem('cart_error_generar'),
			);
			$fn_xtemplate_parse['parse'][] = 'cart.no_cart';
			
		}
	}
}

?>