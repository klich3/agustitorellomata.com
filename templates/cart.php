<?php

global $CONFIG, $fn_page_args, $db, $st_lang, $lang_items;

if(isset($fn_page_args['order_id']))
{
	if(!class_exists('tooLogin')) header("Location: {$CONFIG['site']['base']}error");
	
	if($too_login->isLogged() == 200)
	{
		$fn_q_order = $db->FetchValue("
			SELECT `data_cart`
			FROM `orders`
			WHERE `order_id`=:oid
			LIMIT 1;
		", array(
			'oid' => $fn_page_args['order_id'],
		));
		
		if($fn_q_order)
		{
			$fn_q_order = base64_decode($fn_q_order);

			if(!isJson($fn_q_order))
			{
				$fn_xtemplate_parse['assign'][] = array(
					'message' => getLangItem('cart_error_generar'),
				);
				$fn_xtemplate_parse['parse'][] = 'cart.no_cart';
				return;
			}
			
			$fn_q_order = object_to_array(json_decode($fn_q_order));
			
			//cart
			foreach($fn_q_order['cart'] as $ok => $ov)
			{
				$fn_for_data = $ov;
				$fn_for_data['only_read'] = 'readonly';
				
				$fn_q_langdata = $db->FetchValue("
					SELECT `lang_data`
					FROM `product`
					WHERE `id`=:id
					LIMIT 1;
				", array(
					'id' => $ov['p_id'],
				));
				
				$fn_p_title = (isset($fn_q_langdata) && isJson($fn_q_langdata)) ? object_to_array(json_decode($fn_q_langdata)) : '';
				$fn_for_data['title'] = (isset($fn_p_title[$st_lang])) ? $fn_p_title[$st_lang] : $fn_p_title[$CONFIG['site']['defaultLang']];
				
				$fn_xtemplate_parse['assign'][] = $fn_for_data;
				$fn_xtemplate_parse['parse'][] = 'cart.cart.row';
			}
			
			//dir
			$fn_xtemplate_parse['assign'][] = $fn_q_order['user_dir'];
			$fn_xtemplate_parse['parse'][] = 'cart.cart.dir_shipping.dir';
			
			//shipping
			if(isset($fn_q_order['cart_checkout']['cart_shipping_type']) && $fn_q_order['cart_checkout']['cart_shipping_type'] !== 0)
			{
				$fn_q_shipping_name = $db->FetchValue("
					SELECT `lang_data`
					FROM `shipping_types`
					WHERE `id`=:id
					LIMIT 1;
				", array(
					'id' => $fn_q_order['cart_checkout']['cart_shipping_type'],
				));
				
				$fn_title_shipping = (isset($fn_q_shipping_name) && isJson($fn_q_shipping_name)) ? object_to_array(json_decode($fn_q_shipping_name)) : '';
				
				$fn_xtemplate_parse['assign'][] = array(
					'title' => (isset($fn_title_shipping[$st_lang])) ? $fn_title_shipping[$st_lang] : $fn_title_shipping[$CONFIG['site']['defaultLang']],
				);
				$fn_xtemplate_parse['parse'][] = 'cart.cart.dir_shipping.history_shipping_comp';
			}
			
			$fn_xtemplate_parse['assign'][] = '';
			$fn_xtemplate_parse['parse'][] = 'cart.cart.dir_shipping';
			
			$fn_xtemplate_parse['assign'][] = $fn_q_order['cart_checkout'];
			$fn_xtemplate_parse['parse'][] = 'cart.cart';
		}else{
			$fn_xtemplate_parse['assign'][] = array(
				'message' => getLangItem('cart_error_generar'),
			);
			$fn_xtemplate_parse['parse'][] = 'cart.no_cart';
		}
	}else{
		$fn_xtemplate_parse['assign'][] = array(
			'message' => getLangItem('cart_error_generar'),
		);
		$fn_xtemplate_parse['parse'][] = 'cart.no_cart';
	}
	
	return;
}

if(getCartCount() == 0)
{
	$fn_xtemplate_parse['assign'][] = array(
		'message' => $lang_items[$st_lang]['msg_cart_no_hay_compras'],
	);
	$fn_xtemplate_parse['parse'][] = 'cart.no_cart';
}else{
	
	$fn_process_cart = cartProcessAndCalc($_SESSION);
	
	/*
		array (size=3)
  'cart_checkout' => 
    array (size=7)
      'cart_count' => int 1
      'cart_subtotal' => float 2.42
      'cart_total' => int 0
      'cart_iva' => float 0.42
      'cart_iva_percent' => string '21' (length=2)
      'cart_shipping_type' => int 0
      'cart_shipping_cost' => int 0
  'cart' => 
    array (size=1)
      0 => 
        array (size=7)
          'cat_id' => string '1' (length=1)
          'p_id' => string '2' (length=1)
          'pax' => string '1' (length=1)
          'title' => string 'wa' (length=2)
          'stock_count' => string '5' (length=1)
          'thumb' => string 'http://too:8888/glunt.com/content/_t1.jpg' (length=41)
          'price' => float 2
  'lang' => 
    array (size=2)
      'lang_iva' => string 'IVA' (length=3)
      'lang_no_iva' => string 'No hay iva' (length=10)
	*/
	
	if($too_login->isLogged() == 200)
	{
		$fn_nodir = true;
		$fn_noshipping = true;
		$fn_dir_data = array();
		$fn_shipping_data = array();
		$fn_login_user_data = $too_login->getUserData();

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
		
		if($fn_user_meta)
		{
			$fn_d = (!empty($fn_user_meta) && isJson($fn_user_meta)) ? object_to_array(json_decode($fn_user_meta)) : false;
			
			if($fn_d)
			{
				foreach($fn_d as $mk => $mv)
				{
					if(isset($mv['dir_default']) && $mv['dir_default'] == 1)
					{
						$fn_nodir = false;
						$fn_dir_data = $mv;
						break;
					}
				}
				
				$fn_xtemplate_parse['assign'][] = $fn_dir_data;
				
				if(!$fn_nodir)
				{
					$fn_xtemplate_parse['parse'][] = 'cart.cart.dir_shipping.dir';
				}else{
					$fn_xtemplate_parse['parse'][] = 'cart.cart.dir_shipping.no_dir';
				}
			}else{
				$fn_xtemplate_parse['assign'][] = '';
				$fn_xtemplate_parse['parse'][] = 'cart.cart.dir_shipping.no_dir';
			}
		}else{
			$fn_xtemplate_parse['assign'][] = '';
			$fn_xtemplate_parse['parse'][] = 'cart.cart.logdir_shippingged.no_dir';
		}
		//check user data
		
		//shipping select
		$fn_q_shipping = $db->FetchAll("
		  SELECT *
		  FROM `shipping_companies`
		  WHERE `active`='1'
		");
		
		if(count($fn_q_shipping) !== 0) $fn_noshipping = false;
		
		if(!$fn_nodir && !$fn_noshipping)
		{
			$fn_q_tid = $db->FetchAll("
				SELECT t.`id`
				FROM `shipping_types` t
				LEFT JOIN `shipping_types_rel` r ON(r.`t_id`=t.`id`)
				WHERE r.`s_id`=:sid
			", array(
				'sid' => $fn_q_shipping[0]->id,
			));
			
			if($fn_q_tid) foreach($fn_q_tid as $tk => $tv)
			{
				$fn_get_tarifa = $db->FetchArray("
					SELECT t.*
					FROM `shipping_tarifas` t
					LEFT JOIN `apps_countries` c ON(c.`id`=t.`c_id`)
					WHERE c.`country_code`=UPPER(:dr)
					AND t.`s_id`=:sid
					AND t.`t_id`=:tid
					AND t.`kg`>=:ps
					GROUP BY t.`s_id`
				", array(
					'dr' => $fn_dir_data['dir_country'],
					'sid' => $fn_q_shipping[0]->id,
					'tid' => $tv->id, 
					'ps' => $fn_process_cart['cart_checkout']['cart_peso'], 
				));
				
				if($fn_get_tarifa) $fn_shipping_data[] = $fn_get_tarifa;
			}
			
			if($fn_shipping_data) foreach($fn_shipping_data as $sk => $sv)
			{
				$fn_for_fet_name = $db->FetchAll("
					SELECT t.*
					FROM `shipping_companies` c
					LEFT JOIN `shipping_types_rel` r ON(r.`s_id`=c.`id`)
					LEFT JOIN `shipping_types` t ON(t.`id`=r.`t_id`)
					WHERE r.`s_id`=:si
					AND r.`t_id`=:ti
					AND c.`active`='1'
				", array(
					'si' => $sv['s_id'],
					'ti' => $sv['t_id'],
				));
				
				if($fn_for_fet_name) foreach($fn_for_fet_name as $sek => $sev)
				{
					/*
						var_dump($sev);
						object(stdClass)[170]
						  public 'id' => string '8' (length=1)
						  public 'title' => string 'Zeleris Nacional' (length=16)
						  public 'lang_data' => string '{"es":"Zeleris (Nacional)","en":"Zeleris (Only Spain)","ca":"Zeleris (Nacional)"}' (length=81)
					*/
					
					$fn_title = (isset($sev->lang_data) && isJson($sev->lang_data)) ? object_to_array(json_decode($sev->lang_data)) : false;
					$fn_title = (isset($fn_title[$st_lang])) ? $fn_title[$st_lang] : $fn_title[$CONFIG['site']['defaultLang']];
					
					$fn_xtemplate_parse['assign'][] = array(
						'id' => $sev->id,
						'title' => ($fn_title) ? $fn_title : '',
						'comp_selected' => (isset($fn_process_cart['cart_checkout']['cart_shipping_type']) && $sev->id == $fn_process_cart['cart_checkout']['cart_shipping_type']) ? 'selected' : '',
					);
					$fn_xtemplate_parse['parse'][] = 'cart.cart.dir_shipping.shipping_comp.sel_envio';
				}
			}
			
			$fn_xtemplate_parse['assign'][] = '';
			$fn_xtemplate_parse['parse'][] = 'cart.cart.dir_shipping.shipping_comp';
		}else{
			$fn_xtemplate_parse['assign'][] = '';
			$fn_xtemplate_parse['parse'][] = 'cart.cart.dir_shipping.no_shipping_comp';
		}
		
		//sel_envio
		//shipping select
		
		$fn_xtemplate_parse['assign'][] = '';
		$fn_xtemplate_parse['parse'][] = 'cart.cart.dir_shipping';
	}else{
		$fn_xtemplate_parse['assign'][] = '';
		$fn_xtemplate_parse['parse'][] = 'cart.cart.no_dir_shipping';
	}
		
	//calc stage
	if($fn_process_cart)
	{
		foreach($fn_process_cart['cart'] as $ck => $cv)
		{
			if(!$cv['thumb']) $cv['thumb'] = "{$CONFIG['site']['base']}images/nofoto.png";
			
			$fn_xtemplate_parse['assign'][] = '';
			$fn_xtemplate_parse['parse'][] = 'cart.cart.row.del_but';
			
			$fn_xtemplate_parse['assign'][] = $cv;
			$fn_xtemplate_parse['parse'][] = 'cart.cart.row';
		}
		
		$fn_xtemplate_parse['assign'][] = '';
		$fn_xtemplate_parse['parse'][] = 'cart.cart.checkout_button';
		
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

?>