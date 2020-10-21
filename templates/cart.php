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
				
				$fn_price_total_row = 0;
				$fn_price_total_row = $cv['pax'] * $cv['precio_venta'];
				
				if(isset($cv['pax_multimplier']))
				{
					$cv['precio_caja'] = round($cv['pax_multimplier'] * $cv['precio_venta'], 2);
					
					if(isset($cv['multimplier']))
					{
						$fn_price_total_row += $fn_price_total_row + (($cv['multimplier'] * $cv['pax_multimplier']) * $cv['precio_venta']);
					}else{
						$cv['multimplier'] = 0;
					}
					
					$fn_xtemplate_parse['assign'][] = $cv;
					$fn_xtemplate_parse['parse'][] = 'cart.cart.row.multiplier';
					
					$fn_xtemplate_parse['assign'][] = $cv;
					$fn_xtemplate_parse['parse'][] = 'cart.cart.row.multiplier_selector';
				}
				
				$cv['price_total_row'] = round($fn_price_total_row, 2);
				
				$fn_xtemplate_parse['assign'][] = $cv;
				$fn_xtemplate_parse['parse'][] = 'cart.cart.row';
			}
			
			$fn_out_stage_data = $fn_process_cart['cart_wiva_checkout'];
			$fn_out_stage_data['cart_iva_percent'] = $fn_process_cart['cart_checkout']['cart_iva_percent'];
			$fn_out_stage_data['cart_subtotal'] = round($fn_process_cart['cart_wiva_checkout']['cart_subtotal']-$fn_process_cart['cart_wiva_checkout']['cart_iva'], 2);
			
			$fn_xtemplate_parse['assign'][] = $fn_out_stage_data;
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