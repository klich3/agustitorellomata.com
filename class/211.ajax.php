<?php

global $CONFIG, $db, $lang_items, $st_lang, $too_login, $cl_m;

$fn_g = $cl_m->parse("GET");
$fn_p = $cl_m->parse("POST");
$fn_r = $cl_m->parse("REQ");
$fn_ajax = (isset($fn_g['ajax'])) ? $fn_g['ajax'] : null;

if($fn_ajax !== null)
{
	
	if(isset($fn_ajax) && !preg_match('/(redsys|paypal)+notification$/', $fn_ajax) && !preg_match('/too|agustitorellomata/', $CONFIG['site']['base_script']))
	{
		$fn_check_ref = $cl_m->parseHeaderReferer($CONFIG['site']['dm_nws']);
		
		if(!$CONFIG['status']['debug'] && !$fn_check_ref) exit(json_encode(array(
			'status' => 400,
			'message' => getLangItem('error_db'),
		)));
	}
	
	switch($fn_ajax)
	{
		//-----------------------------------------------------------------------------
		
		case "amdin-test-delsession":
			$u_level = $too_login->isAuth(100, false);
			
			if($u_level !== 200) exit(json_encode(array(
				'status' => 400,
				'message' => 'No puede hacer esto, no tiene autorización!.',
			)));
			
			unset($_SESSION['cart']);
			unset($_SESSION['cart_checkout']);
			exit;
		break;
		
		case "amdin-test-showsesion":
			$u_level = $too_login->isAuth(100, false);
			
			if($u_level !== 200) exit(json_encode(array(
				'status' => 400,
				'message' => 'No puede hacer esto, no tiene autorización!.',
			)));
			
			var_dump($_SESSION);
			exit;
		break;
			
		case "amdin-test-mailsendsimple":
			$cabeceras = 'From: webmaster@example.com' . "\r\n" .
						'Reply-To: webmaster@example.com' . "\r\n" .
						'X-Mailer: PHP/' . phpversion();
	
			$fn_email = @mail($CONFIG['site']['mailinfo'], 'test', 'test', $cabeceras);
			var_dump($fn_email);
			exit;
		break;
		
		case "amdin-test-mailsend":
			//asignamos el pago y respuesta
			//creamos mails de aviso de pagos
			$fn_get_order_data_based = $db->FetchValue("
				SELECT `data_cart`
				FROM `orders`
				WHERE `order_id`=:oid
			", array(
				'oid' => $fn_g['o'],
			));
			
			if($fn_get_order_data_based)
			{
			
				$fn_order_data = base64_decode($fn_get_order_data_based);
				$fn_order_data = (isJson($fn_order_data)) ? json_decode($fn_order_data, true) : false;
				
				//mails
				sendInvioce($fn_g['i'], $fn_g['o'], $fn_order_data);
				sendAdminNotice($fn_g['o']);
			}
			exit;
		break;
		
		case "amdin-test-mailhtml":
			//asignamos el pago y respuesta
			//creamos mails de aviso de pagos
			$fn_get_order_data_based = $db->FetchValue("
				SELECT `data_cart`
				FROM `orders`
				WHERE `order_id`=:oid
			", array(
				'oid' => $fn_g['o'],
			));
			
			if($fn_get_order_data_based)
			{
			
				$fn_order_data = base64_decode($fn_get_order_data_based);
				$fn_order_data = (isJson($fn_order_data)) ? json_decode($fn_order_data, true) : false;
				
				echo createCartCheckoutHtml($fn_order_data);
			}
			
			
			exit;
		break;
		
		//-----------------------------------------------------------------------------
		//-----------------------------------------------------------------------------
		//-----------------------------------------------------------------------------
		//-----------------------------------------------------------------------------
		
		//solo admin 
		case "repeatCart":
			if(IsHotlink()) exit(json_encode(array(
				'status' => 400,
				'message' => 'Ajax Fraud cached!',
			)));
			
			if(!isset($fn_p['data'])) exit(json_encode(array(
				'status' => 400,
				'message' => getLangItem('error_db'),
			)));
			
			$u_level = $too_login->isAuth(15, false);
			
			if($u_level !== 200) exit(json_encode(array(
				'status' => 400,
				'message' => getLangItem('no_level_msg'),
			)));
			
			$fn_login_user_data = $too_login->getUserData();
			
			$fn_q = $db->FetchObject("
				SELECT `order_id`, `date`, `payment_status`, `entrega_status`, `num_seg`, `lang`, `data_cart`
				FROM `orders`
				WHERE `user_id`=:uid
				AND `order_id`=:oid
				ORDER BY `date` DESC;
			", array(
				"uid" => $fn_login_user_data->ID,
				"oid" => $fn_p['data'],
			));
			
			try{
				$fn_cart = base64_decode($fn_q->data_cart);
				$fn_cart = (isJson($fn_cart)) ? json_decode($fn_cart, true) : $fn_cart;
				
				$_SESSION['cart'] = $fn_cart['cart'];
				$_SESSION['checkout'] = $fn_cart['checkout'];
				
				$fn_process_cart = cartProcessAndCalc($_SESSION);
				
				
				exit(json_encode(array(
					'status' => 200,
					'message' => "ok",
					'data' => $fn_process_cart
				)));
			}catch(Exception $e)
			{
				exit(json_encode(array(
					'status' => 400,
					'message' => getLangItem('error_db'),
				)));
			}
			
			exit;
		break;
		
		case "openCart":
			if(IsHotlink()) exit(json_encode(array(
				'status' => 400,
				'message' => 'Ajax Fraud cached!',
			)));
			
			if(!isset($_SESSION) || !isset($_SESSION['cart']) || count($_SESSION['cart']) == 0)
			{
				unset($_SESSION['cart']);
				unset($_SESSION['cart_checkout']);
				
				exit(json_encode(array(
					'status' => 400,
					'message' => getLangItem('cart_empty'),
				)));
			}
			
			$fn_process_cart = cartProcessAndCalc($_SESSION);
			
			if($fn_process_cart)
			{
				$fn_result = array(
					'status' => 200,
					'message' => getLangItem('cart_generado'),
					'data' => $fn_process_cart,
				);
			}else{
				$fn_result = array(
					'status' => 400,
					'message' => getLangItem('cart_error_generar'),
				);
			}
			
			exit(json_encode($fn_result));
		break;
		
		case "addCart":
			if(IsHotlink()) exit(json_encode(array(
				'status' => 400,
				'message' => 'Ajax Fraud cached!',
			)));
			
			if(!isset($fn_p['data'])) exit(json_encode(array(
				'status' => 400,
				'message' => getLangItem('msg_no_data'),
			)));
			
			//'data' => string 'product_id=2&category_id=1' (length=26)
			//parse_str($fn_p['data'], $fn_inputs);
			$fn_inputs = json_decode($fn_p['data'], true);
			
			//si no esta la session iniciada la iniciamos
			if(!isset($_SESSION)) session_start();
			
			if(!isset($_SESSION) || !isset($_SESSION['cart']) || count($_SESSION['cart']) == 0)
			{
				unset($_SESSION['cart']);
				unset($_SESSION['checkout']);
			}
			
			if(!is_numeric($fn_inputs['p_id']) || !is_numeric($fn_inputs['cat_id'])) exit(json_encode(array(
				'status' => 400,
				'message' => getLangItem('msg_error_param'),
			)));
			
			//añadimos al carrito
			$fn_q_check_stock = $db->FetchArray("
				SELECT `stock_count`, `precio_venta`, `pax_multimplier`
				FROM `product_stock` 
				WHERE `prid`=:pid
				LIMIT 1;
			", array(
				'pid' => $fn_inputs['p_id'],
			));
			
			//id del producto en el carrito
			$fn_pr_cart_id = "{$fn_inputs['p_id']}{$fn_inputs['cat_id']}";
			
			//vacio añadimos uno nuevo
			if($fn_q_check_stock >= 1 && !array_key_exists($fn_pr_cart_id, $_SESSION['cart']))
			{
				$_SESSION['cart'][$fn_pr_cart_id] = array(
					'cat_id' => $fn_inputs['cat_id'],
					'p_id' => $fn_inputs['p_id'],
					'pax' => '1',
					'pax_multimplier' => $fn_q_check_stock['pax_multimplier'],
					'multimplier' => '0',
					'precio_venta' => $fn_q_check_stock['precio_venta'],
				);
			}
			
			$fn_process_cart = cartProcessAndCalc($_SESSION);
			
			if($fn_process_cart)
			{
				$fn_result = array(
					'status' => 200,
					'message' => getLangItem('add_item_cart'),
					'data' => $fn_process_cart,
				);
			}else{
				$fn_result = array(
					'status' => 400,
					'message' => getLangItem('add_item_cart_error'),
				);
			}
			
			exit(json_encode($fn_result));
		break;
		
		case "upCart":
		case "delCart":
			if(IsHotlink()) exit(json_encode(array(
				'status' => 400,
				'message' => 'Ajax Fraud cached!',
			)));
			
			//parse_str($fn_p['data'], $fn_inputs);
			$fn_inputs = json_decode($fn_p['data'], true);
			
			if(!isset($fn_inputs['p_id']) || !isset($fn_inputs['cat_id'])) exit(json_encode(array(
				'status' => 400,
				'message' => getLangItem('msg_no_data'),
			)));
			
			if(!is_numeric($fn_inputs['p_id']) || !is_numeric($fn_inputs['cat_id'])) exit(json_encode(array(
				'status' => 400,
				'message' => getLangItem('msg_error_param'),
			)));
			
			if(!isset($_SESSION) || !isset($_SESSION['cart']))
			{
				exit(json_encode(array(
					'status' => 400,
					'message' => getLangItem('cart_empty'),
				)));
			}
			
			if(sizeof($_SESSION['cart']) == 0)
			{
				unset($_SESSION['cart']);
				unset($_SESSION['checkout']);
				
				exit(json_encode(array(
					'status' => 400,
					'message' => getLangItem('cart_empty'),
				)));
			}
			
			//id del item en el cart
			$fn_pr_cart_id = "{$fn_inputs['p_id']}{$fn_inputs['cat_id']}";
			
			//del cart
			if($fn_ajax == 'delCart')
			{
				if(sizeof($_SESSION['cart']) !== 0 && array_key_exists($fn_pr_cart_id, $_SESSION['cart'])) unset($_SESSION['cart'][$fn_pr_cart_id]);
				if(sizeof($_SESSION['cart']) == 0)
				{
					unset($_SESSION['cart']);
					
					exit(json_encode(array(
						'status' => 200,
						'message' => getLangItem('cart_empty'),
					)));
				}
			}
			
			//reloadcart
			$fn_q_check_stock = $db->FetchValue("
				SELECT `stock_count`
				FROM `product_stock` 
				WHERE `prid`=:prid
				LIMIT 1;
			", array(
				'prid' => $fn_inputs['p_id'],
			));
			
			if($fn_q_check_stock == 0 || !$fn_q_check_stock) exit(json_encode(array(
				'status' => 400,
				'message' => getLangItem('tienda_no_items'),
			)));
			
			//add
			if(array_key_exists($fn_pr_cart_id, $_SESSION['cart']))
			{
				//modificamos
				$_SESSION['cart'][$fn_pr_cart_id]['multimplier'] = isset($fn_inputs['multimplier']) ? $fn_inputs['multimplier'] : "0";
				
				//sum client pax or add existing on stock
				$fn_pax = (isset($fn_inputs['pax']) && $fn_inputs['pax'] == 0) ? "1" : $fn_inputs['pax'];
				$_SESSION['cart'][$fn_pr_cart_id]['pax'] = ($fn_pax < $fn_q_check_stock) ? $fn_pax : $fn_q_check_stock;	
			}
			
			$fn_process_cart = cartProcessAndCalc($_SESSION);
			
			exit(json_encode(array(
				'status' => 200,
				'message' => getLangItem('add_item_cart'),
				'data' => $fn_process_cart,
			)));
		break;

		case "stShippingReloadCart":
			if(IsHotlink()) exit(json_encode(array(
				'status' => 400,
				'message' => 'Ajax Fraud cached!',
			)));
			
			if(!isset($fn_p['t_id'])) exit(json_encode(array(
				'status' => 400,
				'message' => getLangItem('msg_no_data'),
			)));
			
			if(!isset($_SESSION) || !isset($_SESSION['cart']))
			{
				exit(json_encode(array(
					'status' => 400,
					'message' => getLangItem('cart_empty'),
				)));
			}
			
			if(sizeof($_SESSION['cart']) == 0)
			{
				unset($_SESSION['cart']);
				unset($_SESSION['cart_checkout']);
				
				exit(json_encode(array(
					'status' => 400,
					'message' => getLangItem('cart_empty'),
				)));
			}
			
			$_SESSION['cart_checkout']['cart_shipping_type'] = $fn_p['t_id'];
			
			$fn_process_cart = cartProcessAndCalc($_SESSION);
			
			$fn_process_cart['cart_wiva_checkout']['cart_subtotal'] = round($fn_process_cart['cart_wiva_checkout']['cart_subtotal']-$fn_process_cart['cart_wiva_checkout']['cart_iva'], 2);
			
			if($fn_process_cart)
			{
				$fn_result = array(
					'status' => 200,
					'message' => 'Recalculado',
					'data' => $fn_process_cart,
				);
			}else{
				$fn_result = array(
					'status' => 400,
					'message' => 'Hay algún error al calcular la tarifa',
				);
			}
			
			exit(json_encode($fn_result));
		break;
		
		//-----------------------------------------------------------------------------
		
		case "reclamacionSend":
			if(IsHotlink()) exit(json_encode(array(
				'status' => 400,
				'message' => 'Ajax Fraud cached!',
			)));
			
			$u_level = $too_login->isAuth(15, false);
			
			if($u_level !== 200) exit(json_encode(array(
				'status' => 400,
				'message' => getLangItem('no_level_msg'),
			)));
			
			if(!isset($fn_p['data'])) exit(json_encode(array(
				'status' => 400,
				'message' => getLangItem('msg_no_data'),
			)));
			
			if(isset($fn_p['data'])) parse_str($fn_p['data'], $fn_inputs);
			
			if(empty($fn_inputs['f_or_id'])) exit(json_encode(array(
				'status' => 400,
				'message' => getLangItem('msg_no_data'),
				'dom' => array('f_or_id'),
			)));
			
			$fn_order_id = $db->FetchValue("
				SELECT `id`
				FROM `orders`
				WHERE `order_id`=:oid
				LIMIT 1;
			", array(
				'oid' => $fn_inputs['f_or_id'],
			));
			
			if($fn_order_id)
			{
				$fn_rec_data = base64_encode(json_encode($fn_inputs));
				$fn_date = date('Y-m-d H:i:s');
				
				$db->Fetch("
					INSERT INTO `orders_reclamacion` (`o_id`, `data`, `date`)
					VALUES (:oid, :rdt, :dt);
				", array(
					'oid' => $fn_order_id,
					'rdt' => $fn_rec_data,
					'dt' => $fn_date,
				));
			}
			
			$getUserData = $too_login->getUserData();
			
			$fn_message = getLangItem('mail_message_reclamacion');
			$fn_message = str_replace(array(
				'%ID%',
				'%MOTIVO%'
			), array(
				$getUserData->ID,
				$fn_inputs['f_subject']
			), $fn_message);
			
			$fn_message .= htmlspecialchars($fn_inputs['f_message'], ENT_COMPAT, 'UTF-8');
			
			$fn_to = $CONFIG['site']['mailinfo'];
			$fn_subject = "[".getLangItem('mail_subject_reclamacion')."] - {$fn_inputs['f_or_id']} - {$CONFIG['site']['sitetitlefull']}";
			
			//html y content del mail
			$fn_mail_html = $CONFIG['site']['standartEmail'];
			
			$fn_mail_html = str_replace(array(
				'%message%',
				'%regards%', 
				'%site_name%', 
				'%copyz%',
				'%site_dir%', 
				'%site_logo%', 
			), array(
				$fn_message,
				getLangItem('regards'),
				$CONFIG['site']['sitetitlefull'],
				$CONFIG['site']['sitecopyz'],
				'',
				'<img src="'.$CONFIG['site']['base'].'m/logo-mail.png" alt="logotype" />',
			), $fn_mail_html);
			
			$fn_content = preparehtmlmailBase64($getUserData->user_email, $fn_mail_html);
			
			//envio del mail
			if(mail($fn_to, $fn_subject, $fn_content['multipart'], $fn_content['headers']))
			{
				exit(json_encode(array(
					'status' => 200,
					'message' => getLangItem('contact_form_confirm'),
				)));
			}else{
				exit(json_encode(array(
					'status' => 400,
					'message' => getLangItem('contact_form_error'),
					'dom' => array("f_subject", "f_or_id", "f_message")
				)));
			}
		break;
		
		//-----------------------------------------------------------------------------
		//-----------------------------------------------------------------------------
		//-----------------------------------------------------------------------------
		
		case "checkoutPayment":
			if(IsHotlink()) exit(json_encode(array(
				'status' => 400,
				'message' => 'Ajax Fraud cached!',
			)));
			
			if(!isset($fn_p['data'])) exit(json_encode(array(
				'status' => 400,
				'message' => getLangItem('msg_no_data'),
			)));
			
			if(isset($fn_p['data'])) parse_str($fn_p['data'], $fn_inputs);
			
			if(!isset($fn_inputs['u_email']) || emailValidation($fn_inputs['u_email'])) exit(json_encode(array(
				'status' => 400,
				'message' => getLangItem('msg_email_not_valid'),
			)));
			
			//check cart
			if(!isset($_SESSION) && !isset($_SESSION['cart_checkout']) || !isset($_SESSION['cart'])) exit(json_encode(array(
				'status' => 400,
				'message' => getLangItem('cart_empty'),
				'data' => array(
					'redirect' => "{$CONFIG['site']['base']}{$st_lang}/tienda",
				)
			)));
			
			//check user level
			$u_level = $too_login->isAuth(15, false);
			
			$fn_isPayProcess = false;
			$fn_order_data = array();
			
			if($u_level !== 200)
			{
				exit(json_encode(array(
					'status' => 400,
					'message' => getLangItem('payment_no_redirect'),
				)));
			}else{
				//logueado
				$getUserData = $too_login->getUserData();
				
				$fn_order_data['user'] = object_to_array($getUserData);
				
				$fn_user_dir = array(
					'u_id' => $getUserData->ID,
					'u_name' => (isset($fn_inputs['u_name'])) ? $fn_inputs['u_name'] : '',
					'u_surname' => (isset($fn_inputs['u_surname'])) ? $fn_inputs['u_surname'] : '',
					'u_cif' => (isset($fn_inputs['u_cif'])) ? $fn_inputs['u_cif'] : '',
					'u_tel' => (isset($fn_inputs['u_tel'])) ? $fn_inputs['u_tel'] : '',
					'u_email' => (isset($fn_inputs['u_email'])) ? $fn_inputs['u_email'] : '',
					
					'dir_country' => (isset($fn_inputs['dir_country'])) ? $fn_inputs['dir_country'] : '',
					'dir_city' => (isset($fn_inputs['dir_city'])) ? $fn_inputs['dir_city'] : '',
					'dir_primary' => (isset($fn_inputs['dir_primary'])) ? $fn_inputs['dir_primary'] : '',
					'dir_post' => (isset($fn_inputs['dir_post'])) ? $fn_inputs['dir_post'] : '',
					
					'dir_name' => getLangItem('pordefecto'),
					'dir_default' => 1,
					'dir_id' => 0,
				);
				
				$fn_user_dir_json = json_encode($fn_user_dir, JSON_UNESCAPED_UNICODE);
				
				//save
				$fn_q = $db->Fetch("
					INSERT INTO `users_meta` (`user_id`, `meta_key`, `meta_value`) 
					VALUES (:uid, 'user_dirs', :d) 
					ON DUPLICATE KEY UPDATE `meta_value`=:d;
				", array(
					'uid' => $getUserData->ID,
					'd' => $fn_user_dir_json,
				));
				
				$fn_order_data['user_dir'] = $fn_user_dir;
				$fn_isPayProcess = true;
			}
		
			//datos personales del usuario
			$fn_order_data['user_dir'] = $fn_user_dir; 
			
			//ofertas
			if(isset($fn_inputs['p_promote']) && !empty($fn_inputs['p_promote']))
			{
				//check si esta disponible
				$fn_q_promote = $db->FetchArray("
					SELECT *
					FROM `ofertas`
					WHERE `code`=:code
					AND `active`='1'
					LIMIT 1;
				", array(
					'code' => $fn_inputs['p_promote'],
				));
				
				//incluimos oferta entera
				if($fn_q_promote['used'] <= $fn_q_promote['max']) $fn_order_data['promote'] = $fn_q_promote;
			}
			
			$fn_cart_out_final = array();
			$fn_cart_calcs_subtotal = 0;
			
			//empezamos proceso de pago
			if($fn_isPayProcess)
			{
				$fn_order_num = assignCheckoutId(); //cada compra es unico
				
				//recuperamos el id anterior de order anterior
				$fn_checkout_id_loc = (isset($_SESSION) && isset($_SESSION['checkout']) && isset($_SESSION['checkout']['checkout_id'])) ? $_SESSION['checkout']['checkout_id'] : $fn_order_num;
				
				$fn_now_date = date('Y-m-d H:i:s');
				
				$fn_order_data = cartProcessAndCalc($_SESSION);
				$fn_order_data['checkout']['checkout_date'] = $fn_now_date;
				$fn_order_data['user_order'] = $fn_user_dir;
				
				$fn_user_payment_method = ($fn_inputs['p_pay_type'] == 'rd') ? 'redsys' : 'paypal';
				
				//guardamos todo en db
				$fn_order_data_based = base64_encode(json_encode($fn_order_data, JSON_UNESCAPED_UNICODE));
				
				$fn_insert = $db->Fetch("
					INSERT INTO `orders` (`user_id`, `order_id`, `date`, `lang`, `data_cart`, `payment_type`)
					VALUES (:uid, :ck, :dt, :ln, :dc, :pt) 
					ON DUPLICATE KEY UPDATE `order_id`=:oid, `data_cart`=:dc, `date`=:dt;
				", array(
					'uid' => $getUserData->ID,
					'ck' => $fn_checkout_id_loc,
					'dt' => $fn_now_date,
					'ln' => $st_lang,
					'dc' => $fn_order_data_based,
					'pt' => $fn_user_payment_method,
					'oid' => $fn_order_num,
				));
				
				if($fn_insert)
				{
					//creamos peticion de pago
					$_SESSION['checkout']['checkout_id'] = $fn_order_num;

					switch($fn_user_payment_method)
					{
						case "redsys":
							require_once('redsys_soap/Messages.php');
							require_once('redsys_soap/Redsys.php');
							
							$fn_merchant = $CONFIG['site']['redsys_user'];
							$fn_redsys_mode = ($CONFIG['site']['redsys_mode']) ? 'live' : 'test';
							
							if($fn_redsys_mode == 'live')
							{
								//real
								$fn_sha = $CONFIG['site']['redsys_sha256_real'];
								$fn_pass = $CONFIG['site']['redsys_pass_real'];
							}else{
								//sandbox
								$fn_sha = $CONFIG['site']['redsys_sha256'];
								$fn_pass = $CONFIG['site']['redsys_pass'];
							}
							
							//correct ammount
							$fn_amount = preg_replace("/\,/", ".", $fn_order_data['checkout']['cart_total']);
							
							/*
							DOCS: https://www.bbva.es/content/dam/public-web/bbvaes/documents/legal/psd2/TPV%20VIRTUAL_Manual%20Redirecci%C3%B3n_3DS%202.2.pdf
							*/
							try {
								$redsys = new \Buuum\Redsys($fn_sha);
								$redsys->setMerchantcode($fn_merchant);
								$redsys->setAmount($fn_amount);
								$redsys->setOrder($fn_order_num);
								$redsys->setTerminal('1');
								$redsys->setCurrency('978');
								$redsys->setLang($st_lang);
								 
								if($CONFIG['site']['redsys_service'] == 'soap')
								{
									//web service
									$fn_card_num = str_replace(array(' ', '  '), '', $fn_inputs['p_vnum']);
									$fn_card_expire = "{$fn_inputs['p_year']}{$fn_inputs['p_month']}";
									$fn_card_ccv = $fn_inputs['p_ccv'];
									
									$redsys->setPan($fn_card_num);
									$redsys->setExpiryDate($fn_card_expire);
									$redsys->setCVV($fn_card_ccv);
								  
									/*
									* A – Pago tradicional
									* 1 – Preautorización
									* O – Autorización en diferido
									*/
									$redsys->setTransactiontype('A');
									
									$redsys->setIdentifier('REQUIRED');
									//$redsys->setNotification('<URL>');
								
									$result = $redsys->firePayment($fn_redsys_mode); //live | test
									
									if(isset($result['Ds_Merchant_Identifier']))
									{
										//ok
										
										//update response data
										$fn_response_data = base64_encode(json_encode($result));
										$fn_mid = (isset($result['Ds_Merchant_Identifier'])) ? $result['Ds_Merchant_Identifier'] : '';
										
										//asignamos el pago y respuesta
										$db->Fetch("
											UPDATE `orders`
											SET `data_response`=:dr, `payment_status`='1', `m_id`=:mid
											WHERE `order_id`=:oid
										", array(
											'dr' => $fn_response_data,
											'mid' => $fn_mid,
											'oid' => $fn_checkout_id_loc,
										));
										
										//restamos oferta
										$db->Fetch("
											UPDATE `ofertas`
											SET `used`=`used`+1
											WHERE `id`=:s
										", array(
											's' => $_SESSION['promote']['id'],
										));
										
										sendInvioce($getUserData->user_email, $fn_checkout_id_loc, $fn_order_data);
										sendAdminNotice($fn_checkout_id_loc);
										
										//todo correcto borramos el cart y detalles
										//----------------> 
										//----------------> 
										unset($_SESSION['checkout']);
										unset($_SESSION['cart']);
										//----------------> 
										//----------------> 
													
										exit(json_encode(array(
											'status' => 200,
											'message' => getLangItem('checkout_success'),
											'data' => array(
												'redirect' => "{$CONFIG['site']['base']}{$st_lang}/mis-pedidos",
											),
										)));
									}
								}else{
									//redirect
									$redsys->setTransactiontype('0');
									$redsys->setMethod('C'); //c solo tarjeta || t visa+yupay
									
									$fn_wsd_debug = ($CONFIG['status']['debug']) ? 'local' : '';
									$fn_not_path = ($CONFIG['site']['redsys_notification_type'] == 'post') ? "{$CONFIG['site']['base_prefix']}{$CONFIG['site']['base_script']}response/response.php" : "{$CONFIG['site']['base_prefix']}{$CONFIG['site']['base_script']}class/redsys_soap/InotificacionSIS{$fn_wsd_debug}.wsdl";
									
									$redsys->setNotification($fn_not_path); //Url de notificacion
									$redsys->setUrlOk("{$CONFIG['site']['base_prefix']}{$CONFIG['site']['base_script']}{$st_lang}/pago-completado");
									$redsys->setUrlKo("{$CONFIG['site']['base_prefix']}{$CONFIG['site']['base_script']}{$st_lang}/pago-error");
									
									$result = $redsys->createForm($fn_redsys_mode, array(
										'form_name' => "{$CONFIG['site']['dm_nws']}pay_redsys",
										//'submit_value' => 'Pay',
									));
									
									exit(json_encode(array(
										'status' => 200,
										'message' => getLangItem('redirection'),
										'data' => array(
											//'redirect' => "{$CONFIG['site']['base']}{$st_lang}/mis-pedidos",
											'pay_html' => $result,
										),
									)));
								}
							} catch (Exception $e) 
							{
								$fn_data = array();
								$fn_data['error'] = $e;
								$fn_data['session'] = $_SESSION;
								
								//añadimos al log
								$fn_data = base64_encode(json_encode($fn_data));
								$fn_now = date('Y-m-d H:i:s');
								
								$db->Fetch("
									INSERT INTO `log_orders` (`id`, `log`, `date`)
									VALUES ('null', :dt, :nw);
								", array(
									'dt' => $fn_data,
									'nw' => $fn_now,
								));
								
								exit(json_encode(array(
									'status' => 400,
									'message' => getLangItem('error_db')." [511]",
								)));
							}
							
						break;
					}
				}
			}else{
				exit(json_encode(array(
					'status' => 400,
					'message' => getLangItem('payment_no_redirect'),
				)));
			}
			
			exit;
		break;
		
		//-----------------------------------------------------------------------------
		//-----------------------------------------------------------------------------
		//-----------------------------------------------------------------------------
		//-----------------------------------------------------------------------------
		
		//client reclamacion submit
		case "reclamacionSend":
			if(IsHotlink()) exit(json_encode(array(
				'status' => 400,
				'message' => 'Ajax Fraud cached!',
			)));
			
			$u_level = $too_login->isAuth(15, false);
			
			if($u_level !== 200) exit(json_encode(array(
				'status' => 400,
				'message' => getLangItem('no_level_msg'),
			)));
			
			if(!isset($fn_p['data'])) exit(json_encode(array(
				'status' => 400,
				'message' => getLangItem('msg_no_data'),
			)));
			
			if(isset($fn_p['data'])) parse_str($fn_p['data'], $fn_inputs);
			
			if(empty($fn_inputs['f_or_id'])) exit(json_encode(array(
				'status' => 400,
				'message' => getLangItem('msg_no_data'),
				'dom' => array('f_or_id'),
			)));
			
			$fn_order_id = $db->FetchValue("
				SELECT `id`
				FROM `orders`
				WHERE `order_id`=:oid
				LIMIT 1;
			", array(
				'oid' => $fn_inputs['f_or_id'],
			));
			
			if($fn_order_id)
			{
				$fn_rec_data = base64_encode(json_encode($fn_inputs));
				$fn_date = date('Y-m-d H:i:s');
				
				$db->Fetch("
					INSERT INTO `orders_reclamacion` (`o_id`, `data`, `date`)
					VALUES (:oid, :rdt, :dt);
				", array(
					'oid' => $fn_order_id,
					'rdt' => $fn_rec_data,
					'dt' => $fn_date,
				));
			}
			
			$getUserData = $too_login->getUserData();
			/*
				var_dump($getUserData);
				object(stdClass)[1]
			  public 'ID' => string '1' (length=1)
			  public 'user_name' => string '211' (length=3)
			  public 'user_email' => string 'admin@211.com' (length=13)
			  public 'user_status' => string '1' (length=1)
			  public 'user_add_date' => string '2017-02-25 17:22:14' (length=19)
			  public 'status_value' => string 'Activo' (length=6)
			  public 'user_level' => string '100' (length=3)
			  public 'time_stamp' => int 1488910705

			*/
			
			$fn_message = getLangItem('mail_message_reclamacion');
			$fn_message = str_replace(array(
				'%ID%',
				'%MOTIVO%'
			), array(
				$getUserData->ID,
				$fn_inputs['f_subject']
			), $fn_message);
			
			$fn_message .= htmlspecialchars($fn_inputs['f_message'], ENT_COMPAT, 'UTF-8');
			
			$fn_to = $CONFIG['site']['mailinfo'];
			$fn_subject = "[".getLangItem('mail_subject_reclamacion')."] - {$fn_inputs['f_or_id']} - {$CONFIG['site']['sitetitlefull']}";
			
			//html y content del mail
			$fn_mail_html = $CONFIG['site']['standartEmail'];
			
			$fn_mail_html = str_replace(array(
				'%message%',
				'%regards%', 
				'%site_name%', 
				'%copyz%',
				'%site_dir%', 
				'%site_logo%', 
			), array(
				$fn_message,
				getLangItem('regards'),
				$CONFIG['site']['sitetitlefull'],
				$CONFIG['site']['sitecopyz'],
				'',
				'<img src="'.$CONFIG['site']['base'].'m/logo.png?" alt="logotype" />',
			), $fn_mail_html);
			
			$fn_content = preparehtmlmailBase64($getUserData->user_email, $fn_mail_html);
			
			//envio del mail
			if(mail($fn_to, $fn_subject, $fn_content['multipart'], $fn_content['headers']))
			{
				exit(json_encode(array(
					'status' => 200,
					'message' => getLangItem('contact_form_confirm'),
				)));
			}else{
				exit(json_encode(array(
					'status' => 400,
					'message' => getLangItem('contact_form_error'),
				)));
			}
		break;
		
		//------------------------------------------------------------------------------------------------
		
		case "upObjects":
			$u_level = $too_login->isAuth(100, false, $CONFIG['site']['tooSType']);
			
			if($u_level !== 200) exit(json_encode(array(
				'status' => 400,
				'message' => 'No puede hacer esto, no tiene autorización!.',
			)));
			
			if(!isset($fn_p['gid'])) exit(json_encode(array(
				'status' => 400,
				'message' => 'No puedo subir archivos a una galería que no existe, primero cree una nueva.',
			)));
			
			include_once('211.upload.class.php');
			
			//upload process image
			$fn_upload = tooUpload::initUploadFiles($_FILES, $fn_p);
			
			/*
				 'status' => int 200
				  'message' => string 'Imagen subida y procesada!' (length=26)
				  'data' => 
				    array (size=3)
				      'ext' => string 'png' (length=3)
				      'src' => string 'content/a1fa2007cc32a07387716aa7f7a3b754.png' (length=44)
				      'cachedSrc' => string 'content/0.03272400-1488369450bc03c5d0ec8ce907587bb83b57504d3dw_450h_.png' (length=72)
			*/
			
			if($fn_upload)
			{
				$fn_get_gal = $db->FetchValue("
					SELECT `objects`
					FROM `gallery`
					WHERE `id`=:id
					LIMIT 1;
				", array(
					'id' => $fn_p['gid'],
				));
				
				$fn_gal = ($fn_get_gal && isJson($fn_get_gal)) ? object_to_array(json_decode($fn_get_gal)) : array();
				
				$fn_out_data = array(
					'isThumb' => 0,
					'img' => $fn_upload['data']['src'],
					'thumb' => $fn_upload['data']['cachedSrc'],
					'alt' => '',
					'title' => (isset($fn_upload['data']['title'])) ? $fn_upload['data']['title'] : '',
					'type' => (isset($fn_upload['data']['type'])) ? $fn_upload['data']['type'] : 'image',
					'id' => md5(microtime()),
				);
				
				$fn_gal[] = $fn_out_data;
				$fn_gal = json_encode($fn_gal);
				
				$fn_q = $db->Fetch("
					UPDATE `gallery`
					SET `objects`=:ob
					WHERE `id`=:gid;
				", array(
					'ob' => $fn_gal,
					'gid' => $fn_p['gid'],
				));
				
				if($fn_q)
				{
					$fn_result = array(
						'status' => 200,
						'message' => 'Item añadido.',
						'data' => $fn_out_data,
					);
				}else{
					$fn_result = array(
						'status' => 400,
						'message' => 'Por algún motivo no he podido añadir la url',
					);
				}
			}else{
				$fn_result = array(
					'status' => 400,
					'message' => 'Por algún motivo no he podido subir este archivo',
				);
			}
			
			exit(json_encode($fn_result));
		break;
		
		//gallery
		case "manageGallery":
			if(IsHotlink()) exit(json_encode(array(
				'status' => 400,
				'message' => 'Ajax Fraud cached!',
			)));
			
			$u_level = $too_login->isAuth(100, false, $CONFIG['site']['tooSType']);
			
			if($u_level !== 200) exit(json_encode(array(
				'status' => 400,
				'message' => 'No puede hacer esto, no tiene autorización!.',
			)));
			
			if(!isset($fn_p['type'])) exit(json_encode(array(
				'status' => 400,
				'message' => 'No se que hacer, puedes intentar de nuevo?',
			)));
			
			$fn_result = array();
			
			//lista de galerias
			$fn_gallery_list = $db->FetchAll("
				SELECT `id`, `title`
				FROM `gallery`;
			");

			$fn_gal_list = array();
			$fn_gal_list[] = array(
				"id" => null,
				"title" => "- Sin definir -",
			);
			
			if($fn_gallery_list && count($fn_gallery_list) !== 0) foreach($fn_gallery_list as $gk => $gv)
			{
				$fn_gal_list[] = object_to_array($gv);
			}
			//lista de galerias
			
			if(isset($fn_p['data'])) parse_str($fn_p['data'], $fn_inputs);
			
			switch($fn_p['type'])
			{
				default:
				case "reloadGalleryContent":
				case "manage":
					if(isset($fn_p['gid']))
					{
						$fn_q = $db->FetchValue("
							SELECT `objects`
							FROM `gallery`
							WHERE `id`=:gid
						", array(
							'gid' => $fn_p['gid'],
						));
						
						$fn_data = ($fn_q && isJson($fn_q)) ? json_decode($fn_q) : array();
						
						exit(json_encode(array(
							'status' => 200,
							'message' => 'Ya lo tengo',
							'data' => array(
								'cnt' => $fn_data,
								'lst' => $fn_gal_list,
							),
						)));
					}else{
						exit(json_encode(array(
							'status' => 200,
							'message' => 'Falta el id de galería, es probable que no este asignado.',
							'data' => array(
								'cnt' => array(),
								'lst' => $fn_gal_list,
							),
						)));
					}
				break;
				
				case "assignGalery":
					if(!isset($fn_p['pid'])) exit(json_encode(array(
						'status' => 400,
						'message' => 'Me falta el id de producto',
					)));
					
					if(!isset($fn_p['data'])) exit(json_encode(array(
						'status' => 400,
						'message' => 'Me falta el id de producto',
					)));
					
					//definimos si es producto o pagina
					if(isset($fn_inputs['where']) && $fn_inputs['where'] == 'producto')
					{
						$fn_q = $db->Fetch("
							UPDATE `product`
							SET `gallery_id`=:gid
							WHERE `id`=:pid
							LIMIT 1;
						", array(
							'gid' => $fn_inputs['f_gallery_sel'],
							'pid' => $fn_p['pid'],
						));
					}else{
	  					$fn_q = $db->Fetch("
							INSERT INTO `pages_meta` (`meta_key`, `meta_value`, `p_id`)
							VALUES ('gallery_id', :gid, :pid) 
							ON DUPLICATE KEY UPDATE `meta_value`=:gid
						", array(
							'gid' => $fn_inputs['f_gallery_sel'],
							'pid' => $fn_p['pid'],
						));
					}
					
					if($fn_q)
					{
						$fn_result = array(
							'status' => 200,
							'message' => 'Galería asignada',
							'data' => array(
								'gid' => $fn_inputs['f_gallery_sel'],
							)
						);
					}else{
						$fn_result = array(
							'status' => 400,
							'message' => 'Por algún motivo no he podido asignar la galería',
						);
					}
				break;
				
				case "delGallery":
					if(!isset($fn_p['gid'])) exit(json_encode(array(
						'status' => 400,
						'message' => 'Me falta el id de producto',
					)));
					
					if(!isset($fn_p['data'])) exit(json_encode(array(
						'status' => 400,
						'message' => 'Me falta el id de producto',
					)));
					
					if($fn_inputs['f_gallery_sel'] == 0) exit(json_encode(array(
						'status' => 400,
						'message' => 'No existe esta gelería.',
					)));
					
					//del fisics files
					$fn_get_gal = $db->FetchValue("
						SELECT `objects`
						FROM `gallery`
						WHERE `id`=:id
						LIMIT 1;
					", array(
						'id' => $fn_inputs['f_gallery_sel'],
					));
					
					$fn_gal = ($fn_get_gal && isJson($fn_get_gal)) ? object_to_array(json_decode($fn_get_gal)) : array();
					
					if(count($fn_gal) !== 0) foreach($fn_gal as $dk => $dv)
					{
						$fn_file = (isset($dv['img'])) ? str_replace($CONFIG['site']['base'], '', $dv['img']) : false;
						$fn_thumb = (isset($dv['thumb'])) ? str_replace($CONFIG['site']['base'], '', $dv['thumb']) : false;
					
						if($fn_file && is_file($fn_file)) unlink($fn_file);
						if($fn_thumb && is_file($fn_thumb)) unlink($fn_thumb);
					}
					//del fisics files
					
					$fn_q = $db->Fetch("
						DELETE FROM `gallery`
						WHERE `id`=:id
						LIMIT 1;
					", array(
						'id' => $fn_inputs['f_gallery_sel'],
					));
					
					if($fn_q)
					{
						$fn_q = $db->Fetch("
							DELETE FROM `pages_meta`
							WHERE `meta_value`=:id
							AND `meta_key`='gallery_id'
						", array(
							'id' => $fn_inputs['f_gallery_sel'],
						));	
						
						$fn_result = array(
							'status' => 200,
							'message' => 'Galería eliminada.',
						);
					}else{
						$fn_result = array(
							'status' => 400,
							'message' => 'Por algún motivo no he podido eliminar la galería',
						);
					}
				break;
				
				case "newGallery":
					if(!isset($fn_p['data'])) exit(json_encode(array(
						'status' => 400,
						'message' => 'Me falta el id de producto',
					)));
					
					$fn_q = $db->ExecuteSQL("
						INSERT INTO `gallery` (`title`)
						VALUES (:tl);
					", array(
						'tl' => $fn_inputs['f_gallery_title'],
					));
					
					if($fn_q)
					{
						$fn_result = array(
							'status' => 200,
							'message' => 'Galería creada.',
							'data' => array(
								'id' => $fn_q,
								'title' => $fn_inputs['f_gallery_title'],
							)
						);
					}else{
						$fn_result = array(
							'status' => 400,
							'message' => 'Por algún motivo no he podido crear la galería',
						);
					}
				break;
				
				case "addUrlGallery":
					if(!isset($fn_p['data'])) exit(json_encode(array(
						'status' => 400,
						'message' => 'Me falta el id de producto',
					)));
					
					/*array(2) {
					  ["type"]=>
					  string(13) "addUrlGallery"
					  ["data"]=>
					  string(18) "f_url=123&f_gid=14"
					}
					*/
					
					$fn_get_gal = $db->FetchValue("
						SELECT `objects`
						FROM `gallery`
						WHERE `id`=:id
						LIMIT 1;
					", array(
						'id' => $fn_inputs['f_gid'],
					));
					
					$fn_gal = ($fn_get_gal && isJson($fn_get_gal)) ? object_to_array(json_decode($fn_get_gal)) : array();
					
					$fn_count_id = (!$fn_gal) ? 1 : count($fn_gal)+1;
					$fn_thumb = "{$CONFIG['site']['base']}images/nofoto.png";
					
					if(preg_match('/yout|be/', $fn_inputs['f_url']))
					{
						$fn_q_media = get_youtube_video($fn_inputs['f_url']);
						$fn_thumb = $fn_q_media['thumbnail_url'];
					}
					
					if(preg_match('/vimeo/', $fn_inputs['f_url']))
					{
						$fn_q_media = get_vimeo_video($fn_inputs['f_url']);
						$fn_thumb = $fn_q_media['thumbnail_url'];
					}
					
					$fn_out_data = array(
						'isThumb' => 0,
						'img' => $fn_inputs['f_url'],
						'thumb' => $fn_thumb,
						'alt' => '',
						'title' => '',
						'type' => 'video',
						'id' => $fn_count_id,
					);
					
					$fn_gal[] = $fn_out_data;
					$fn_gal = json_encode($fn_gal);
					
					$fn_q = $db->Fetch("
						UPDATE `gallery`
						SET `objects`=:ob
						WHERE `id`=:id;
					", array(
						'ob' => $fn_gal,
						'id' => $fn_inputs['f_gid'],
					));
					
					if($fn_q)
					{
						$fn_result = array(
							'status' => 200,
							'message' => 'Item añadido.',
							'data' => array(
								'isThumb' => 0,
								'img' => $fn_inputs['f_url'],
								'thumb' => $fn_thumb,
								'alt' => '',
								'title' => '',
								'type' => 'video',
								'id' => $fn_count_id
							),
						);
					}else{
						$fn_result = array(
							'status' => 400,
							'message' => 'Por algún motivo no he podido añadir la url',
						);
					}
				break;

				case "delImageGallery":
					$fn_get_gal = $db->FetchValue("
						SELECT `objects`
						FROM `gallery`
						WHERE `id`=:id
						LIMIT 1;
					", array(
						'id' => $fn_p['gid'],
					));
					
					$fn_gal = ($fn_get_gal && isJson($fn_get_gal)) ? object_to_array(json_decode($fn_get_gal)) : array();
					$fn_gal_out = array();
					
					foreach($fn_gal as $gk => $gv)
					{
						if($gv['id'] == $fn_p['pid'])
						{
							//del fisic image
							$fn_file = (isset($gv['img'])) ? str_replace($CONFIG['site']['base'], '', $gv['img']) : false;
							$fn_thumb = (isset($gv['thumb'])) ? str_replace($CONFIG['site']['base'], '', $gv['thumb']) : false;
							
							//archivos + videos
							if(preg_match('/(video|file)/', $gv['type']) && is_file($fn_file)) unlink($fn_file);
							
							//imagen
							if(preg_match('/image/', $gv['type']) && is_file($fn_file)) unlink($fn_file);
							if(preg_match('/image/', $gv['type']) && is_file($fn_thumb)) unlink($fn_thumb);
							
							continue;
						}
						
						$fn_gal_out[] = $gv;
					}
					
					$fn_gal = json_encode($fn_gal_out);
					
					$fn_q = $db->Fetch("
						UPDATE `gallery`
						SET `objects`=:ob
						WHERE `id`=:id;
					", array(
						'ob' => $fn_gal,
						'id' => $fn_p['gid'],
					));
					
					if($fn_q)
					{
						$fn_result = array(
							'status' => 200,
							'message' => 'Item eliminado.',
						);
					}else{
						$fn_result = array(
							'status' => 400,
							'message' => 'Por algún motivo no he podido eliminar el item',
						);
					}
				break;

				case "orGallery":
				case "stImageAltTitle":
					/*
						'type' => string 'stImageAltTitle' (length=16)
						'data' => string 'item[1][alt]=&item[1][title]=&item[1][isThumb]=0&item[2][alt]=test lt&item[2][title]=test title&item[2][isThumb]=1&item[3][alt]=&item[3][title]=&item[3][isThumb]=0&item[4][alt]=&item[4][title]=&item[4][isThumb]=0&item[5][alt]=&item[5][title]=&item[5][isThumb]=0&f_gallery_sel=1&pid=2&f_gallery_title=&pid=2&f_url=&f_gid=1&isEdit=true&f_id=2&f_title=test title 1312&f_alt=test lt 123 1' (length=504)
					*/
					
					$fn_get_gal = $db->FetchValue("
						SELECT `objects`
						FROM `gallery`
						WHERE `id`=:id
						LIMIT 1;
					", array(
						'id' => $fn_inputs['f_gallery_sel'],
					));
					
					$fn_gal = ($fn_get_gal && isJson($fn_get_gal)) ? object_to_array(json_decode($fn_get_gal)) : array();
					$fn_gal_out = array();
					
					if(count($fn_gal) !== 0)
					{
						if($fn_p['type'] == 'orGallery')
						{
							//reorder
							foreach($fn_inputs['item'] as $ik => $iv)
							{
								foreach($fn_gal as $gk => $gv)
								{
									if($gv['id'] == $ik)
									{
										$fn_file = (isset($gv['img'])) ? str_replace($CONFIG['site']['base'], '', $gv['img']) : '';
										$fn_thumb = (isset($gv['thumb'])) ? str_replace($CONFIG['site']['base'], '', $gv['thumb']) : '';
										
										$fn_gal_out[] = array(
											'alt' => ($iv['alt']) ? $iv['alt'] : '',
											'title' => ($iv['title']) ? $iv['title'] : '',
											'isThumb' => ($iv['isThumb']) ? $iv['isThumb'] : 0,
											
											'id' => $gv['id'],
											'img' => $fn_file,
											'thumb' => $fn_thumb,
											'type' => $gv['type'],
										);
									}
								}
							}
							
						}else{
							foreach($fn_gal as $gk => $gv)
							{
								//update meta images
								foreach($fn_inputs['item'] as $ik => $iv)
								{
									if($gv['id'] == $ik)
									{
										$fn_file = str_replace($CONFIG['site']['base'], '', $gv['img']);
										$fn_thumb = str_replace($CONFIG['site']['base'], '', $gv['thumb']);
										
										$fn_gal_out[] = array(
											'alt' => ($iv['alt']) ? $iv['alt'] : '',
											'title' => ($iv['title']) ? $iv['title'] : '',
											'isThumb' => ($iv['isThumb']) ? $iv['isThumb'] : 0,
											
											'id' => $gv['id'],
											'img' => $fn_file,
											'thumb' => $fn_thumb,
											'type' => $gv['type'],
										);
									}else{
										continue;
									}
								}
							}
						}
					}else{
						exit(json_encode(array(
							'status' => 400,
							'message' => 'No hay nada que ordenar',
						)));
					}
					
					$fn_gal = json_encode($fn_gal_out);
					
					$fn_q = $db->Fetch("
						UPDATE `gallery`
						SET `objects`=:ob
						WHERE `id`=:id;
					", array(
						'ob' => $fn_gal,
						'id' => $fn_inputs['f_gallery_sel'],
					));
					
					if($fn_q)
					{
						$fn_result = array(
							'status' => 200,
							'message' => 'Ordenado.',
						);
					}else{
						$fn_result = array(
							'status' => 400,
							'message' => 'Por algún motivo no he podido ordenar la galería',
						);
					}
				break;
			}
			
			exit(json_encode($fn_result));
		break;
		
		//cliente send request to recover pass
		case "recPass":
			if(IsHotlink()) exit(json_encode(array(
				'status' => 400,
				'message' => 'Ajax Fraud cached!',
			)));
						
			if(isset($fn_p['data'])) parse_str($fn_p['data'], $fn_inputs);
		
			$fn_q = $db->FetchObject("
				SELECT `ID`, `user_email`, `user_name`
				FROM `users`
				WHERE `user_email`=:e
				LIMIT 1;
			", array(
				"e" => $fn_inputs['email']
			));
			
			if(!$fn_q)
			{
				$fn_result = array(
					'status' => 400,
					'message' => 'Error no exists',
				);
			}else{
				//set new key
				$fn_activation_key = md5(microtime());
				
				$db->Fetch("
					UPDATE `users`
					SET `user_activation_key`=:key
					WHERE `ID`=:id
					LIMIT 1;
				", array(
					"id" => $fn_q->ID,
					"key" => $fn_activation_key,
				));
				
				$fn_user_metas = $db->FetchValue("
					SELECT `meta_value`
					FROM `users_meta`
					WHERE `meta_key`='user_pers_data'
					AND `user_id`=:id
					LIMIT 1;
				", array(
					"id" => $fn_q->ID
				));
				
				if($fn_user_metas && isJson($fn_user_metas)) $fn_user_metas = json_decode($fn_user_metas, true);
				
				//send mail
				$fn_to = $fn_inputs['email'];
				$fn_subject = getLangItem('mail_recpassword');
				
				$fn_def_lang = $CONFIG['site']['defaultLang'];
				
				$fn_html_p = $lang_items[$fn_def_lang]['mail_recpassword_html'];
				$fn_mail_html = $CONFIG['site']['standartEmail'];
				
				$fn_html_p = str_replace(array(
					"%first_name%",
					"%user_name%",
					"%site_link%"
				), array(
					($fn_user_metas) ? $fn_user_metas['u_name'] : getLangItem("mail_cliente"),
					$fn_q->user_name,
					"{$CONFIG['site']['base']}{$st_lang}/nueva-contrasena?activation_key={$fn_activation_key}"
				), $fn_html_p);
				
				$fn_mail_html = str_replace(array(
					'%message%',
					'%regards%', 
					'%site_name%', 
					'%copyz%',
					'%site_dir%', 
					'%site_logo%',
					'%site_link%',
				), array(
					$fn_html_p,
					$lang_items[$fn_def_lang]['mail_regards'],
					$CONFIG['site']['sitetitlefull'],
					"&copy; ".date('Y')." {$CONFIG['site']['sitetitlefull']}. All rights reserved.",
					'',
					"",
					"",
				), $fn_mail_html);
				
				$fn_content = preparehtmlmailBase64($CONFIG['site']['botmail'], $fn_mail_html);
				
				//envio del mail
				$fn_send = @mail($fn_to, $fn_subject, $fn_content['multipart'], $fn_content['headers']);
				
				if($fn_send)
				{
					$fn_result = array(
						'status' => 200,
						'message' => 'Ordenado.',
					);
				}else{
					$fn_result = array(
						'status' => 400,
						'message' => 'Error no exists',
					);	
				}
			}
			
			exit(json_encode($fn_result));
		break;
		
		//asignar nueva pass al cliente
		case "recUpPass":
			if(IsHotlink()) exit(json_encode(array(
				'status' => 400,
				'message' => 'Ajax Fraud cached!',
			)));
			
			if(isset($fn_p['data'])) parse_str($fn_p['data'], $fn_inputs);
			
			if(!isset($fn_inputs['pass']) || !isset($fn_inputs['pass_repeat']) || empty($fn_inputs['pass']) || empty($fn_inputs['pass_repeat'])) exit(json_encode(array(
				'status' => 400,
				'message' => 'Empty fields',
				'dom' => array("pass", "pass_repeat"),
			)));
			
			if($fn_inputs['pass'] !== $fn_inputs['pass_repeat']) exit(json_encode(array(
				'status' => 400,
				'message' => 'Not Match!',
				'dom' => array("pass", "pass_repeat"),
			)));
			
			$fn_q = $db->FetchObject("
				SELECT `ID`, `user_name`, `user_email`
				FROM `users`
				WHERE `user_email`=:e
				AND `user_activation_key`=:a
				LIMIT 1;
			", array(
				"e" => $fn_inputs['email'],
				"a" => $fn_inputs['activation_key']
			));
			
			if(!$fn_q)
			{
				$fn_result = array(
					'status' => 400,
					'message' => 'Error no exists',
				);
			}else{
				//check level
				$fn_user_metas = $db->FetchValue("
					SELECT `meta_value`
					FROM `users_meta`
					WHERE `meta_key`='user_level'
					AND `user_id`=:id
					LIMIT 1;
				", array(
					"id" => $fn_q->ID
				));
				
				if($fn_user_metas && isJson($fn_user_metas)) $fn_user_metas = json_decode($fn_user_metas, true);
				
				if(!$fn_user_metas || $fn_user_metas > 99)
				{
					$fn_result = array(
						'status' => 400,
						'message' => 'No estas autorizado para hacer esto.',
					);
				}else{
					$fn_rand_key = md5(microtime());
					$fn_up = $db->Fetch("
						UPDATE `users`
						SET `user_pass`=:key, `user_activation_key`=:k
						WHERE `ID`=:id
						AND `user_activation_key`=:a
					", array(
						"id" => $fn_q->ID,
						"k" => $fn_rand_key,
						"key" => md5("{$fn_q->user_email}~{$fn_inputs['pass']}"),
						"a" => $fn_inputs['activation_key']
					));
					
					if($fn_up)
					{
						$fn_result = array(
							'status' => 200,
							'message' => 'Contraseña cambiada',
						);
					}else{
						$fn_result = array(
							'status' => 400,
							'message' => 'Error al cambiar contraseña',
						);
					}
				}
			}
			
			exit(json_encode($fn_result));
		break;
		
		/* ------------------------------------------------------------------------------------------------ */
		
		//PAGE MANAGE AJAX CALLS ADMIN SIDE
		case "addRelPage":
			if(IsHotlink()) exit(json_encode(array(
				'status' => 400,
				'message' => 'Ajax Fraud cached!',
			)));
			
			$u_level = $too_login->isAuth(100, false, $CONFIG['site']['tooSType']);
			
			if($u_level !== 200) exit(json_encode(array(
				'status' => 400,
				'message' => 'No puede hacer esto, no tiene autorización!.',
			)));
			
			if(!isset($fn_p['id']) || !isset($fn_p['rid'])) exit(json_encode(array(
				'status' => 400,
				'message' => 'No me estas mandando nada.',
			)));
			
			/*
				array(3) {
				  ["id"]=>
				  string(1) "1"
				  ["rid"]=>
				  string(1) "6"
				  ["data"]=>
				  string(0) ""
				}
			*/
			
			$fn_rel_page = $db->FetchArray("
				SELECT *
				FROM `pages`
				WHERE `id`=:id
				LIMIT 1;
			", array(
				'id' => $fn_p['rid'],
			));
			
			$fn_q = $db->ExecuteSQL("
				INSERT INTO `pages_lang_rel` (`page_id`, `lang_type`, `page_translate_id`)
				VALUES (:id, :ln, :rid)
				ON DUPLICATE KEY UPDATE `page_translate_id`=:rid, `lang_type`=:ln;
			", array(
				'id' => $fn_p['id'],
				'ln' => $fn_rel_page['lang'],
				'rid' => $fn_p['rid'],
			));
			
			if($fn_q)
			{
				//traduccion viceversa
				$fn_q_page_lang = $db->FetchValue("
					SELECT `lang`
					FROM `pages`
					WHERE `id`=:i
					LIMIT 1;
				", array(
					'i' => $fn_p['id'],
				));
				
				 $fn_q = $db->Fetch("
				 	INSERT INTO `pages_lang_rel` (`page_id`, `lang_type`, `page_translate_id`) 
					VALUES (:id, :ln, :rid)
					ON DUPLICATE KEY UPDATE `page_translate_id`=:rid, `lang_type`=:ln;
				", array(
					'id' => $fn_p['rid'],
					'ln' => $fn_q_page_lang,
					'rid' => $fn_p['id'],
				));
				
				exit(json_encode(array(
					'status' => 200,
					'message' => 'Páginas relacionadas',
					'data' => array(
						'id' => $fn_q,
						'lang' => $fn_rel_page['lang'],
						'obj_title' => $fn_rel_page['obj_title'],
					),
				)));
			}else{
				exit(json_encode(array(
					'status' => 400,
					'message' => 'No me puedo conectar a la base de datos',
				)));
			}
		break;
		
		case "delRelPage":
			if(IsHotlink()) exit(json_encode(array(
				'status' => 400,
				'message' => 'Ajax Fraud cached!',
			)));
			
			$u_level = $too_login->isAuth(100, false, $CONFIG['site']['tooSType']);
			
			if($u_level !== 200) exit(json_encode(array(
				'status' => 400,
				'message' => 'No puede hacer esto, no tiene autorización!.',
			)));
			
			if(!isset($fn_p['id'])) exit(json_encode(array(
				'status' => 400,
				'message' => 'No no, me falta el id',
			)));
		
			$fn_q = $db->Fetch("
				DELETE FROM `pages_lang_rel`
				WHERE `id`=:id
			", array(
				'id' => $fn_p['id'],
			));
		
			if($fn_q)
			{
				exit(json_encode(array(
					'status' => 200,
					'message' => 'Relación de páginas eliminada.',
				)));
			}else{
				exit(json_encode(array(
					'status' => 400,
					'message' => 'No me puedo conectar a la base de datos',
				)));
			}
		break;
		
		case "clonePage":
		case "upPage":
		case "delPage":
		case "addPage":
			if(IsHotlink()) exit(json_encode(array(
				'status' => 400,
				'message' => 'Ajax Fraud cached!',
			)));
			
			$u_level = $too_login->isAuth(100, false, $CONFIG['site']['tooSType']);
			
			if($u_level !== 200) exit(json_encode(array(
				'status' => 400,
				'message' => 'No puede hacer esto, no tiene autorización!.',
			)));
			
			$fn_data = array();
			
			switch($fn_ajax)
			{
				case "clonePage":
					if(!$fn_p['id']) exit(json_encode(array(
						'status' => 400,
						'message' => 'No puedo duplicar la página sin ID!',
					)));
					
					try{
						$getPageData = $db->FetchAll("
							SELECT *
							FROM `pages`
							WHERE `id`=:id
						", array(
							'id' => $fn_p['id']
						));
						
						$getPageMetas = $db->FetchAll("
							SELECT *
							FROM `pages_meta`
							WHERE `p_id`=:id
						", array(
							'id' => $fn_p['id']
						));
												
						$fn_new_input = $getPageData[0];
						$fn_rand = rand(0, 999);
						$fn_new_input->obj_title = "{$fn_new_input->obj_title}-copia-{$fn_rand}";
						$fn_new_input->obj_hash = (preg_match("/visita/", $fn_new_input->obj_title)) ? "Visita-".date('Ymd-his') : "{$fn_new_input->obj_hash}-copia-{$fn_rand}";
						
						$fn_q = $db->ExecuteSQL("
							INSERT INTO `pages` (`obj_title`, `obj_hash`, `type`, `lang`, `create_date`, `active`, `protected`)
							VALUES (:pn, :hs, :tp, :ln, :cd, :ac, :pt);
						", array(
							'pn' => $fn_new_input->obj_title,
							'hs' => $fn_new_input->obj_hash,
							'tp' => $fn_new_input->type,
							'ln' => $fn_new_input->lang,
							'cd' => date('Y-m-d'),
							'ac' => ($fn_new_input->active) ? '1' : '0',
							'pt' => ($fn_new_input->protected) ? '1' : '0',
						));
						
						$fn_data['id'] = $fn_q;
						$fn_data['title'] = $fn_data['hash'] = $fn_new_input->obj_title;
						
						if(sizeof($getPageMetas) != 0) foreach($getPageMetas as $mk => $mv)
						{
							$db->Fetch("
								INSERT INTO `pages_meta` (`p_id`, `meta_key`, `meta_value`) 
								VALUES (:pid, :ak, :av) 
								ON DUPLICATE KEY UPDATE `meta_value`=:av;
							", array(
								'pid' => $fn_q,
								'ak' => $mv->meta_key,
								'av' => $mv->meta_value,
							));
						}
						
					}catch (Exception $e) 
					{
						$fn_q = false;
					}
				break;
				
				case "addPage":
					//f_page_name
					//f_hash
					
					try{
						$fn_q = $db->ExecuteSQL("
							INSERT INTO `pages` (`obj_title`, `obj_hash`, `type`, `lang`, `create_date`)
							VALUES (:pn, :hs, :tp, :ln, :cd);
						", array(
							'pn' => $fn_p['f_page_name'],
							'hs' => $fn_p['f_hash'],
							'tp' => $fn_p['f_type'],
							'ln' => $fn_p['f_lang'],
							'cd' => date('Y-m-d'),
						));
						
						$fn_data['id'] = $fn_q;
					}catch (Exception $e) 
					{
						$fn_q = false;
					}
				break;
				
				case "delPage":
					if(!isset($fn_p['id'])) exit(json_encode(array(
						'status' => 400,
						'message' => 'Uiii sobre que item? podrías refrescar?',
					)));
					
					$fn_q = $db->FetchValue("
						SELECT `obj_hash`
						FROM `pages`
						WHERE `id`=:id
						LIMIT 1;
					", array(
						"id" => $fn_p['id']
					));
					
					$fn_template_filename = processUrl(preg_replace('/^([A-Za-z]{2})\//m', '', $fn_q));
					$fn_isSystemTemplate = (file_exists($CONFIG['site']['templatepath'].$fn_template_filename.'.xhtml')) ? true : false;
					
					if($fn_isSystemTemplate) exit(json_encode(array(
						'status' => 400,
						'message' => 'No se puede borrar una pantalla del sistema.',
					)));
					
					//del page 
					$fn_q = $db->Fetch("
						DELETE FROM `pages`
						WHERE `id`=:id
					", array(
						'id' => $fn_p['id'],
					));
					
					//lang rel
					$db->Fetch("
						DELETE FROM `pages_lang_rel`
						WHERE `page_id`=:pid
					", array(
						'pid' => $fn_p['id'],
					));
					
					//del meta
					$db->Fetch("
						DELETE FROM `pages_meta`
						WHERE `p_id`=:pid
					", array(
						'pid' => $fn_p['id'],
					));
				break;
				
				case "upPage":
					if(!isset($fn_p['page_id'])) exit(json_encode(array(
						'status' => 400,
						'message' => 'No puedo actualizar porque me falta el id de página :(',
					)));
					
					/*
						array (size=7)
					  'obj_title' => string 'Sobre nosotros' (length=14)
					  'obj_hash' => string 'sobre-nosotros' (length=14)
					  'pageContent' => string '123' (length=3)
					  '_wysihtml_mode' => string '1' (length=1)
					  'active' => string '1' (length=1)
					  'lang' => string 'en' (length=2)
					  'page_id' => string '1' (length=1)
					*/
					
					//update page
					$fn_active = (isset($fn_p['active'])) ? 1 : 0;
					
					$fn_q = $db->Fetch("
						UPDATE `pages`
						SET `obj_title`=:ot, `obj_hash`=:hs, `active`=:ac, `lang`=:ln, `type`=:tp, `protected`=:pt, `create_date`=:cd
						WHERE `id`=:id
					", array(
						'ot' => $fn_p['obj_title'],
						'hs' => $fn_p['obj_hash'],
						'ac' => $fn_active,
						'ln' => $fn_p['lang'],
						'id' => $fn_p['page_id'], 
						'tp' => $fn_p['type'],
						'pt' => $fn_p['protected'],
						'cd' => $fn_p['create_date'],
					));
					
					//update metas
					if(isset($fn_p['pageContent']))
					{
						//$fn_content_html = htmlentities($fn_p['pageContent']);
						//$fn_content_html = htmlize($fn_p['pageContent']);
						//$fn_content_html = htmlentities($fn_p['pageContent']);
						//$fn_content_html = htmlize($fn_content_html);
						
						//$fn_content_html = str_replace('&quot;', "\'", $fn_p['pageContent']);
						$fn_content_html = str_replace('&quot;', "&#39;", $fn_p['pageContent']);
						$fn_content_html = base64_encode($fn_content_html);
						
						$fn_up_content = $db->Fetch("
							INSERT INTO `pages_meta` (`p_id`, `meta_key`, `meta_value`) 
							VALUES (:pid, 'page_content', :mv) 
							ON DUPLICATE KEY UPDATE `meta_value`=:mv;
						", array(
							'pid' => $fn_p['page_id'],
							'mv' => $fn_content_html,
						));
						
						if(!$fn_up_content) exit(json_encode(array(
							'status' => 400,
							'message' => 'El contenido no se ha actualizado',
						)));
					}
					
					if(isset($fn_p['metas']) && count($fn_p['metas']) !== 0) foreach($fn_p['metas'] as $ak => $av)
					{
						$db->Fetch("
							INSERT INTO `pages_meta` (`p_id`, `meta_key`, `meta_value`) 
							VALUES (:pid, :ak, :av) 
							ON DUPLICATE KEY UPDATE `meta_value`=:av;
						", array(
							'pid' => $fn_p['page_id'],
							'ak' => $ak,
							'av' => $av,
						));
					}
				break;
			}
			
			if($fn_q)
			{
				exit(json_encode(array(
					'status' => 200,
					'message' => 'Ya lo tengo ;)',
					'data' => $fn_data,
				)));
			}else{
				exit(json_encode(array(
					'status' => 400,
					'message' => 'No no he podido esta vez, nose porque no puedo acceder a la base de datos :(, o estas repitiendo una página que ya existe? ¬¬',
				)));
			}
		break;
		
		/* ------------------------------------------------------------------------------------------------ */
		
		//update home page options
		case "upLang":
			if(IsHotlink()) exit(json_encode(array(
				'status' => 400,
				'message' => 'Ajax Fraud cached!',
			)));
			
			$u_level = $too_login->isAuth(100, false, $CONFIG['site']['tooSType']);
			
			if($u_level !== 200) exit(json_encode(array(
				'status' => 400,
				'message' => 'No puede hacer esto, no tiene autorización!.',
			)));
			
			if(!isset($fn_p['k']) || !isset($fn_p['l'])) exit(json_encode(array(
				'status' => 400,
				'message' => 'No se que debo hacer con esto?',
			)));
			
			$fn_q = $db->Fetch("
				INSERT INTO `lang` (`lang_key`, `lang_type`, `lang_value`) 
				VALUES (:tk, :lk, :lv) 
				ON DUPLICATE KEY UPDATE `lang_value`=:lv;
			", array(
				'tk' => $fn_p['k'],
				'lk' => $fn_p['l'],
				'lv' => (isset($fn_p['v'])) ? $fn_p['v'] : '',
			));
			
			if($fn_q)
			{
				exit(json_encode(array(
					'status' => 200,
					'message' => 'Actualizado.',
				)));
			}else{
				exit(json_encode(array(
					'status' => 400,
					'message' => 'Hay algún fallo.',
				)));
			}
						
			exit;
		break;
		
		//langs
		case "addLangIgnore":
		case "delLangIgnore":
		case "delLang":
		case "addLang":
		case "orLang":
			if(IsHotlink()) exit(json_encode(array(
				'status' => 400,
				'message' => 'Ajax Fraud cached!',
			)));
			
			$u_level = $too_login->isAuth(100, false, $CONFIG['site']['tooSType']);
			
			if($u_level !== 200) exit(json_encode(array(
				'status' => 400,
				'message' => 'No puede hacer esto, no tiene autorización!.',
			)));
			
			if(!isset($fn_p['data'])) exit(json_encode(array(
				'status' => 400,
				'message' => 'No hay nada que hacer!.',
			)));
			
			$lang = [];
			
			foreach($fn_p['data'] as $k => $v)
			{
				$lang[] = $v;
			}
			
			$fn_q = $db->Fetch("
				UPDATE `options` 
				SET `options_value`=:pv
				WHERE `options_key`=:pk;
			", array(
				"pv" => json_encode($lang),
				"pk" => (preg_match("/Ignore/", $fn_ajax)) ? "langIgnore" : "lang",
			));
			
			if($fn_q)
			{
				exit(json_encode(array(
					'status' => 200,
					'message' => 'Actualizado',
				)));
			}else{
				exit(json_encode(array(
					'status' => 400,
					'message' => 'No hay nada que actualizar',
				)));
			}
		break;
		
		case "upConfig":
			if(IsHotlink()) exit(json_encode(array(
				'status' => 400,
				'message' => 'Ajax Fraud cached!',
			)));
			
			$u_level = $too_login->isAuth(100, false, $CONFIG['site']['tooSType']);
			
			if($u_level !== 200) exit(json_encode(array(
				'status' => 400,
				'message' => 'No puede hacer esto, no tiene autorización!.',
			)));
			
			if(sizeof($fn_p) !== 0)
			{
				//parse lang
				if(isset($fn_p['langs']))
				{
					$fn_p['lang'] = json_encode($fn_p['langs']);
					unset($fn_p['langs']);
					unset($fn_p['addlang']);
				}
				
				//mod items
				foreach($fn_p as $pk => $pv)
				{
					if(preg_match('/(dm_nws)/', $pk)) continue;
					if(preg_match('/(dir)/', $pk)) $pv = json_encode($pv);
					
					$db->ExecuteSQL("
						UPDATE `options` 
						SET `options_value`=:pv
						WHERE `options_key`=:pk;
					", array(
						'pv' => $pv,
						'pk' => $pk,
					));	
				}
				
				exit(json_encode(array(
					'status' => 200,
					'message' => 'Actualizado',
				)));
			}else{
				exit(json_encode(array(
					'status' => 400,
					'message' => 'No hay nada que actualizar',
				)));
			}
		break;
		
		/* ------------------------------------------------------------------------------------------------ */
		
		case "manageMenus":
			if(IsHotlink()) exit(json_encode(array(
				'status' => 400,
				'message' => 'Ajax Fraud cached!',
			)));
			
			$u_level = $too_login->isAuth(100, false, $CONFIG['site']['tooSType']);
			
			if($u_level !== 200) exit(json_encode(array(
				'status' => 400,
				'message' => 'No puede hacer esto, no tiene autorización!.',
			)));
		
			if(!isset($fn_p['type'])) exit(json_encode(array(
				'status' => 400,
				'message' => 'No se que hacer, puedes intentar de nuevo?',
			)));
			
			if(isset($fn_p['data']) && $fn_p['type'] !== 'parentItem') parse_str($fn_p['data'], $fn_inputs);
			
			switch($fn_p['type'])
			{
				case "addMenus":
					if(!isset($fn_p['data'])) exit(json_encode(array(
						'status' => 400,
						'message' => 'Faltan datos',
					)));
					
					$fn_q = $db->ExecuteSQL("
						INSERT INTO `menus` (`title`, `active`, `order`, `lang`, `create_date`, `m_type`) 
						VALUES (:t, :a, :o, :l, :d, :m);
					", array(
						't' => $fn_inputs['f_title'],
						'a' => (isset($fn_inputs['f_active'])) ? $fn_inputs['f_active'] : 0,
						'o' => $fn_inputs['f_or'],
						'l' => (isset($fn_inputs['f_lang'])) ? $fn_inputs['f_lang'] : $CONFIG['site']['defaultLang'],
						'd' => date('Y-m-d H:i:s'),
						'm' => (isset($fn_inputs['f_mtype'])) ? $fn_inputs['f_mtype'] : null,
					));
					
					if($fn_q)
					{
						exit(json_encode(array(
							'status' => 200,
							'message' => 'Menú creado',
							'data' => array(
								'id' => $fn_q,
							),
						)));
					}else{
						exit(json_encode(array(
							'status' => 400,
							'message' => 'Error en base de datos',
						)));
					}
				break;
				
				case "delMenus":
					if(!isset($fn_p['id'])) exit(json_encode(array(
						'status' => 400,
						'message' => 'Faltan id',
					)));
					
					$fn_q = $db->Fetch("
						DELETE FROM `menus`
						WHERE `id`=:id
						LIMIT 1;
					", array(
						'id' => $fn_p['id'],
					));
					
					if($fn_q)
					{
						//metas
						$db->Fetch("
							DELETE FROM `menus_meta`
							WHERE `m_id`=:id
							LIMIT 1;
						", array(
							'id' => $fn_p['id']
						));
						
						//menu_structure
						$db->Fetch("
							DELETE FROM `menus_structure`
							WHERE `m_id`=:id
							LIMIT 1;
						", array(
							'id' => $fn_p['id']
						));
						
						exit(json_encode(array(
							'status' => 200,
							'message' => 'Menú eliminado.',
						)));
					}else{
						exit(json_encode(array(
							'status' => 400,
							'message' => 'Error en base de datos',
						)));
					}
				break;
				
				case "addUpMenuItem":
					if(!isset($fn_inputs['m_id'])) exit(json_encode(array(
						'status' => 400,
						'message' => 'Hay un fallo nose a que menú pertenece este item.',
					)));
					
					$fn_m_pid = (isset($fn_inputs['m_pid']) && $fn_inputs['m_pid'] !== '') ? $fn_inputs['m_pid'] : 0;
					
					$fn_m_title = (isset($fn_inputs['m_title']) && $fn_inputs['m_title'] !== '') ? $fn_inputs['m_title'] : '';
					$fn_m_url = (isset($fn_inputs['m_url']) && $fn_inputs['m_url'] !== '') ? $fn_inputs['m_url'] : '';
					
					$fn_m_p_title = $fn_m_title;
					$fn_m_p_url = $fn_m_url;
					
					$out_m_title = '';
					$out_m_url = '';
					
					if($fn_m_pid)
					{
						$fn_q_page = $db->FetchArray("
							SELECT *
							FROM `pages`
							WHERE `active`='1'
							AND `id`=:i
							LIMIT 1;
						", array(
							'i' => $fn_m_pid,
						));
						
						if(empty($fn_m_title)) $out_m_title = (isset($fn_q_page['obj_title'])) ? $fn_q_page['obj_title'] : $fn_m_p_title;
						$out_m_url = (empty($fn_m_url)) ? "{$CONFIG['site']['base_script']}{$st_lang}/{$fn_q_page['obj_hash']}" : $fn_m_url;
					}
					
					$fn_q = $db->ExecuteSQL("
						INSERT INTO `menus_structure` (`id`, `m_id`, `m_parent`, `title`, `url`, `p_id`, `order`, `active`) 
						VALUES (:id, :m, :p, :t, :u, :pid, :o, :a)
						ON DUPLICATE KEY UPDATE `title`=:t, `url`=:u, `active`=:a, `p_id`=:pid;
					", array(
						'id' => (isset($fn_inputs['id']) && $fn_inputs['id'] !== '') ? $fn_inputs['id'] : null,
						'm' => $fn_inputs['m_id'],
						'p' => 0,
						't' => $fn_m_title,
						'u' => $fn_m_url,
						'pid' => $fn_m_pid,
						'o' => 0,
						'a' => (isset($fn_inputs['m_active'])) ? '1' : '0',
					));
					
					if($fn_q)
					{
						exit(json_encode(array(
							'status' => 200,
							'message' => 'Item añadido',
							'data' => array(
								'id' => $fn_q,
								'title' => $out_m_title,
								'url' => $out_m_url,
								'p_title' => $fn_m_p_title,
								'p_url' => $fn_m_p_url,
								'or' => 0,
								'pid' => $fn_m_pid,
								'active' => (isset($fn_inputs['m_active'])) ? $fn_inputs['m_active'] : 0,
							),
						)));
					}else{
						exit(json_encode(array(
							'status' => 400,
							'message' => 'Error en la base de datos, no he podido realizar la tarea.',
						)));
					}
				break;
				
				case "parentItem":
					if(!isset($fn_p['data'])) exit(json_encode(array(
						'status' => 400,
						'message' => 'Todo sigue igual que antes.',
					)));
					
					foreach($fn_p['data'] as $k => $v)
					{
						$db->ExecuteSQL("
							UPDATE `menus_structure`
							SET `m_parent`=:i, `order`=:o
							WHERE `id`=:id;
						", array(
							'i' => $v['pid'],
							'o' => $v['or'],
							'id' => $v['id'],
						));
					}
					
					exit(json_encode(array(
						'status' => 200,
						'message' => 'Items actualizados.',
					)));
				break;
				
				case "delItem":
					if(!isset($fn_p['id'])) exit(json_encode(array(
						'status' => 400,
						'message' => 'Este item no tiene id.',
					)));
					
					$fn_q_del = $db->Fetch("
						DELETE FROM `menus_structure`
						WHERE `id`=:id
						LIMIT 1;
					", array(
						'id' => $fn_p['id'],
					));
					
					if($fn_q_del)
					{
						//quitamos parent 
						$db->Fetch("
							UPDATE `menus_structure`
							SET `m_parent`='0'
							WHERE `m_parent`=:id;
						", array(
							'id' => $fn_p['id'],
						));
						
						exit(json_encode(array(
							'status' => 200,
							'message' => 'Items borrado.',
						)));
					}else{
						exit(json_encode(array(
							'status' => 400,
							'message' => 'Hay algún fallo en base de datos no he podido borrar el item.',
						)));
					}
				break;
			}
		break;
		
		/* ------------------------------------------------------------------------------------------------ */
		
		//envios shipping
		case "sippingManage":
			if(IsHotlink()) exit(json_encode(array(
				'status' => 400,
				'message' => 'Ajax Fraud cached!',
			)));
			
			$u_level = $too_login->isAuth(100, false, $CONFIG['site']['tooSType']);
			
			if($u_level !== 200) exit(json_encode(array(
				'status' => 400,
				'message' => 'No puede hacer esto, no tiene autorización!.',
			)));
			
			if(!isset($fn_p['type'])) exit(json_encode(array(
				'status' => 400,
				'message' => 'No se que hacer, puedes intentar de nuevo?',
			)));
			
			if(isset($fn_p['data'])) parse_str($fn_p['data'], $fn_inputs);
			
			switch($fn_p['type'])
			{
				case "delPrice":
					if(!isset($fn_p['id'])) exit(json_encode(array(
						'status' => 400,
						'message' => 'Faltan id',
					)));
					
					/*
						array(3) {
					  ["type"]=>
					  string(8) "delPrice"
					  ["data"]=>
					  string(0) ""
					  ["id"]=>
					  string(4) "3469"
					}
					*/
					
					$fn_q = $db->Fetch("
						DELETE FROM `shipping_tarifas`
						WHERE `id`=:id
						LIMIT 1;
					", array(
						'id' => $fn_p['id'],
					));
					
					if($fn_q)
					{
						exit(json_encode(array(
							'status' => 200,
							'message' => 'Tarifa eliminada',
						)));
					}else{
						exit(json_encode(array(
							'status' => 400,
							'message' => 'Error en base de datos',
						)));
					}
				break;
				
				case "getPriceEdit":
					if(!isset($fn_p['id'])) exit(json_encode(array(
						'status' => 400,
						'message' => 'Faltan id',
					)));
					
					//countries
					$fn_q_cn = $db->FetchAll("
						SELECT *
						FROM `apps_countries`
						WHERE `active`='1';
					");
					
					$fn_countries = array();
					if($fn_q_cn) foreach($fn_q_cn as $ck => $cv)
					{
						$fn_countries[] = array(
							'id' => $cv->id,
							'country_name' => $cv->country_name,
						);
					}
					//countries
					
					$fn_q = $db->FetchArray("
						SELECT *
						FROM `shipping_tarifas`
						WHERE `id`=:id
						LIMIT 1;
					", array(
						'id' => $fn_p['id'],
					));
					
					if($fn_q)
					{
						$fn_data_out = $fn_q;
						$fn_data_out['country_list'] = $fn_countries;
						
						exit(json_encode(array(
							'status' => 200,
							'message' => 'Tengo los datos',
							'data' => $fn_data_out,
						)));
					}else{
						exit(json_encode(array(
							'status' => 400,
							'message' => 'Error en base de datos',
						)));
					}
				break;
				
				case "upPrice":
				case "addPrice":
					if(!isset($fn_p['data'])) exit(json_encode(array(
						'status' => 400,
						'message' => 'Faltan datos',
					)));
					
					/*
						array(3) {
						  ["type"]=>
						  string(8) "addPrice"
						  ["data"]=>
						  string(42) "f_country=1&f_peso=&f_price=&s_id=2&t_id=1"
						  ["id"]=>
						  string(0) ""
						}
					*/
					
					$fn_data_out = array();
					
					if($fn_p['type'] == 'upPrice')
					{
						//update
						$fn_q = $db->Fetch("
							UPDATE `shipping_tarifas`
							SET `kg`=:kg, `precio`=:pvp, `c_id`=:cid
							WHERE `id`=:id
							LIMIT 1;
						", array(
							'kg' => $fn_inputs['f_peso'],
							'pvp' => $fn_inputs['f_price'],
							'cid' => $fn_inputs['f_country'],
							'id' => $fn_inputs['id'],
						));
					}else{
						//add
						$fn_q = $db->ExecuteSQL("
							INSERT INTO `shipping_tarifas` (`s_id`, `t_id`, `c_id`, `kg`, `precio`)
							VALUES (:sid, :tid, :cid, :ps, :pvp);
						", array(
							'sid' => $fn_inputs['s_id'],
							'tid' => $fn_inputs['t_id'],
							'cid' => $fn_inputs['f_country'],
							'ps' => $fn_inputs['f_peso'],
							'pvp' => $fn_inputs['f_price'],
						));
					}
					
					if($fn_q)
					{
						if($fn_p['type'] !== 'upPrice')
						{
							//create
							$fn_data_out = $fn_inputs;
							$fn_data_out['id'] = $fn_q;
							
							//country name
							$fn_q_cn = $db->FetchValue("
								SELECT `country_name`
								FROM `apps_countries`
								WHERE `id`=:id
							", array(
								'id' => $fn_inputs['f_country'],
							));
							
							$fn_data_out['country_name'] = $fn_q_cn;
						}
						
						exit(json_encode(array(
							'status' => 200,
							'message' => 'Tarifa creada',
							'data' => $fn_data_out,
						)));
					}else{
						exit(json_encode(array(
							'status' => 400,
							'message' => 'Error en base de datos',
						)));
					}
				break;
				
				case "addCompany":
					if(!isset($fn_p['data'])) exit(json_encode(array(
						'status' => 400,
						'message' => 'Faltan datos',
					)));
					
					/*
						'type' => string 'addCompany' (length=10)
						  'data' => string 'f_title=123' (length=11)
						  'id' => string '' (length=0)
					*/
					
					$fn_q = $db->ExecuteSQL("
						INSERT INTO `shipping_companies` (`title`)
						VALUES (:tt);
					", array(
						'tt' => $fn_inputs['f_title'],
					));
					
					if($fn_q)
					{
						exit(json_encode(array(
							'status' => 200,
							'message' => 'Compañía creada',
							'data' => array(
								'id' => $fn_q,
								'title' => $fn_inputs['f_title'],
							),
						)));
					}else{
						exit(json_encode(array(
							'status' => 400,
							'message' => 'Error en base de datos',
						)));
					}
				break;
				
				case "delCompany":
					if(!isset($fn_p['id'])) exit(json_encode(array(
						'status' => 400,
						'message' => 'Falta el id',
					)));
				
					$fn_q = $db->Fetch("
						DELETE FROM `shipping_companies`
						WHERE `id`=:id
						LIMIT 1;
					", array(
						'id' => $fn_p['id'],
					));
					
					if($fn_q)
					{
						//remove rel
						$db->Fetch("
							DELETE FROM `shipping_types_rel`
							WHERE `s_id`=:id
						", array(
							'id' => $fn_p['id'],
						));
						
						//del tarifas
						$db->Fetch("
							DELETE FROM `shipping_tarifas`
							WHERE `s_id`=:id
						", array(
							'id' => $fn_p['id']
						));
						
						exit(json_encode(array(
							'status' => 200,
							'message' => 'Compañía eliminada',
						)));
					}else{
						exit(json_encode(array(
							'status' => 400,
							'message' => 'Error en base de datos',
						)));
					}
				break;
			
				case "upCompany":
					if(IsHotlink()) exit(json_encode(array(
						'status' => 400,
						'message' => 'Ajax Fraud cached!',
					)));
					
					$u_level = $too_login->isAuth(100, false, $CONFIG['site']['tooSType']);
					
					if($u_level !== 200) exit(json_encode(array(
						'status' => 400,
						'message' => 'No puede hacer esto, no tiene autorización!.',
					)));
					
					if(!isset($fn_inputs['c_id'])) exit(json_encode(array(
						'status' => 400,
						'message' => 'Me faltan id',
					)));
					
					if(!isset($fn_p['data'])) exit(json_encode(array(
						'status' => 400,
						'message' => 'Me faltan datos',
					)));

					/*
					array (size=3)
					  'type' => string 'upCompany' (length=13)
					  'data' => string 'c_id=&f_active=1&f_type_active%5B1%5D=1&f_type_active%5B2%5D=1&f_type_active%5B3%5D=1&f_type_active%5B4%5D=1' (length=102)
					  'id' => string '' (length=0)
					*/
					
					$fn_active = (isset($fn_inputs['f_active']) && $fn_inputs['f_active'] == 1) ? 1 : 0;
					
					$fn_q = $db->Fetch("
						UPDATE `shipping_companies`
						SET `active`=:ac
						WHERE `id`=:id
						LIMIT 1;
					", array(
						'ac' => $fn_active,
						'id' => $fn_inputs['c_id'],
					));
					
					//del rels
					$db->Fetch("
						DELETE FROM `shipping_types_rel`
						WHERE `s_id`=:id
					", array(
						'id' => $fn_inputs['c_id'],
					));
					
					//create nes ones
					if(isset($fn_inputs['f_type_active']) && count($fn_inputs['f_type_active']) !== 0) foreach($fn_inputs['f_type_active'] as $ak => $av)
					{
						$db->Fetch("
							INSERT INTO shipping_types_rel (`s_id`, `t_id`)
							VALUES (:cid, :ak);
						", array(
							'cid' => $fn_inputs['c_id'],
							'ak' => $ak,
						));
					}
					
					exit(json_encode(array(
						'status' => 200,
						'message' => 'Actualizado',
					)));
				break;
				
				case "delCompanyType":
					if(IsHotlink()) exit(json_encode(array(
						'status' => 400,
						'message' => 'Ajax Fraud cached!',
					)));
					
					$u_level = $too_login->isAuth(100, false, $CONFIG['site']['tooSType']);
					
					if($u_level !== 200) exit(json_encode(array(
						'status' => 400,
						'message' => 'No puede hacer esto, no tiene autorización!.',
					)));
					
					if(!isset($fn_p['id'])) exit(json_encode(array(
						'status' => 400,
						'message' => 'Me faltan id',
					)));
					
					//del 
					//shipping_types_rel | shipping_types
					
					$fn_q = $db->Fetch("
						DELETE FROM `shipping_types`
						WHERE `id`=:id;
					", array(
						'id' => $fn_p['id'],
					));
					
					if($fn_q)
					{
						$db->Fetch("
							DELETE FROM `shipping_types_rel`
							WHERE `t_id`=:tid;
						", array(
							'tid' => $fn_p['id'],
						));
						
						exit(json_encode(array(
							'status' => 200,
							'message' => 'Tipo eliminado',
						)));
					}else{
						exit(json_encode(array(
							'status' => 400,
							'message' => 'Error en base de datos',
						)));
					}
				break;
				
				case "editCompanyType":
					if(IsHotlink()) exit(json_encode(array(
						'status' => 400,
						'message' => 'Ajax Fraud cached!',
					)));
					
					$u_level = $too_login->isAuth(100, false, $CONFIG['site']['tooSType']);
					
					if($u_level !== 200) exit(json_encode(array(
						'status' => 400,
						'message' => 'No puede hacer esto, no tiene autorización!.',
					)));
					
					if(!isset($fn_p['id'])) exit(json_encode(array(
						'status' => 400,
						'message' => 'Me faltan id',
					)));
					
					//get data type
					/*
						array (size=3)
					  'type' => string 'editCompanyType' (length=15)
					  'data' => string 'f_active=1&f_type_active%5B1%5D=1&f_type_active%5B2%5D=1&f_type_active%5B3%5D=1&f_type_active%5B4%5D=1' (length=102)
					  'id' => string '5' (length=1)
					*/
					
					$fn_q = $db->FetchArray("
						SELECT *
						FROM `shipping_types`
						WHERE `id`=:id
						LIMIT 1;
					", array(
						'id' => $fn_p['id'],
					));
					
					$fn_data_titles = ($fn_q && isJson($fn_q['lang_data'])) ? object_to_array(json_decode($fn_q['lang_data'])) : array();
					$fn_lang_data = array();
					
					foreach($fn_data_titles as $tk => $tv)
					{
						$fn_lang_data[] = array(
							'code' => $tk,
							'title' => $tv,
						);
					}	
					
					if($fn_q)
					{
						$fn_lang_list = json_decode($CONFIG['site']['lang']);
						
						exit(json_encode(array(
							'status' => 200,
							'message' => 'Datos generados',
							'data' => array(
								's_id' => $fn_q['id'],
								'title' => $fn_q['title'],
								'lang_title' => $fn_lang_data,
								'lang' => $fn_lang_list,
							),
						)));
					}else{
						exit(json_encode(array(
							'status' => 400,
							'message' => 'Error en base de datos',
						)));
					}
				break;
				
				case "upCompanyType":
				case "addCompanyType":
					if(IsHotlink()) exit(json_encode(array(
						'status' => 400,
						'message' => 'Ajax Fraud cached!',
					)));
					
					$u_level = $too_login->isAuth(100, false, $CONFIG['site']['tooSType']);
					
					if($u_level !== 200) exit(json_encode(array(
						'status' => 400,
						'message' => 'No puede hacer esto, no tiene autorización!.',
					)));
					
					if(!isset($fn_p['data'])) exit(json_encode(array(
						'status' => 400,
						'message' => 'Me faltan datos',
					)));
					
					/*
						array (size=3)
					  'type' => string 'addCompanyType' (length=14)
					  'data' => string 'f_title=1&f_title_es=es&f_title_en=en&f_title_ca=ca' (length=51)
					  'id' => string '' (length=0)
					*/
					
					$fn_lang_title_data = array();
					$fn_lang_title_out = array();
					
					$fn_all_empties = 0;
					
					$fn_site_lang = json_decode($CONFIG['site']['lang']);
					foreach($fn_site_lang as $l)
					{
						//marcamos que esta vacio el campo
						if(empty($fn_inputs["f_title_{$l}"])) $fn_all_empties++;
						
						$fn_lang_title_data[$l] = htmlentities($fn_inputs["f_title_{$l}"]);
						$fn_lang_title_out[] = $fn_inputs["f_title_{$l}"];
					}
					
					$fn_lang_title_data = (count($fn_site_lang) == $fn_all_empties) ? json_encode(array()) : json_encode($fn_lang_title_data);
					$fn_lang_title_out = (count($fn_site_lang) == $fn_all_empties) ? '' : implode(' | ', $fn_lang_title_out);
					
					$fn_id = null;
					
					if(isset($fn_inputs['s_id'])) $fn_id = $fn_inputs['s_id'];
					
					$fn_q = $db->ExecuteSQL("
						INSERT INTO `shipping_types` (`id`, `title`, `lang_data`) 
						VALUES (:id, :tl, :td) 
						ON DUPLICATE KEY UPDATE `title`=:tl, `lang_data`=:td;
					", array(
						'id' => $fn_id,
						'tl' => $fn_inputs['f_title'],
						'td' => $fn_lang_title_data,
					));
					
					if($fn_q)
					{
						exit(json_encode(array(
							'status' => 200,
							'message' => 'Tipo creado',
							'data' => array(
								'id' => $fn_q,
								'title' => $fn_inputs['f_title'],
								'serv_title' => $fn_lang_title_out,
								's_id' => (isset($fn_inputs['id'])) ? $fn_inputs['id'] : '',
							),
						)));
					}else{
						exit(json_encode(array(
							'status' => 400,
							'message' => 'Error en base de datos',
						)));
					}
				break;
			}
			
			exit;
		break;
		
		//activacion de paises envios
		case "upActivesCountries":
			if(IsHotlink()) exit(json_encode(array(
				'status' => 400,
				'message' => 'Ajax Fraud cached!',
			)));
			
			$u_level = $too_login->isAuth(100, false, $CONFIG['site']['tooSType']);
			
			if($u_level !== 200) exit(json_encode(array(
				'status' => 400,
				'message' => 'No puede hacer esto, no tiene autorización!.',
			)));
			
			parse_str($fn_p['data'], $fn_inputs);
			
			if(count($fn_inputs['country']) !== 0) 
			{
				//set all to 0
				$db->Fetch("
					UPDATE `apps_countries` 
					SET `active`='0'
					WHERE `active`='1';
				");
				
				foreach($fn_inputs['country'] as $k => $v)
				{
					$db->Fetch("
						UPDATE `apps_countries` 
						SET `active`='1'
						WHERE `id`=:id
						LIMIT 1;
					", array(
						'id' => $k,
					));
				}
			}
			
			$fn_result = array(
				'status' => 200,
				'message' => 'Guardado.',
			);
			
			exit(json_encode($fn_result));
		break;

		/* ------------------------------------------------------------------------------------------------ */
		
		//Iva		
		case "addIva":
		case "delIva":
		case "upIva":
			if(IsHotlink()) exit(json_encode(array(
				'status' => 400,
				'message' => 'Ajax Fraud cached!',
			)));
			
			$u_level = $too_login->isAuth(100, false, $CONFIG['site']['tooSType']);
			
			if($u_level !== 200) exit(json_encode(array(
				'status' => 400,
				'message' => 'No puede hacer esto, no tiene autorización!.',
			)));
			
			if($fn_ajax == "delIva")
			{
				if(!isset($fn_p['year'])) exit(json_encode(array(
					'status' => 400,
					'message' => 'Uiii me faltan datos para eliminar iva correctamente.',
				)));
				
				$fn_q = $db->Fetch("
					DELETE FROM `iva`
					WHERE `year`=:yr
					LIMIT 1;
				", array(
					'yr' => $fn_p['year'],
				));
				
				if($fn_q)
				{
					exit(json_encode(array(
						'status' => 200,
						'message' => 'Eliminado ;)',
					)));
				}else{
					exit(json_encode(array(
						'status' => 400,
						'message' => 'Hay algún fallo en base de datos.',
					)));
				}
			}
			
			if(!isset($fn_p['data'])) exit(json_encode(array(
				'status' => 400,
				'message' => 'Uiii me faltan datos para hacer eso.',
			)));
			
			parse_str($fn_p['data'], $fn_inputs);
			
			$fn_result = array();
			
			if($fn_ajax == 'addIva')
			{
				$fn_q = $db->Fetch("
					INSERT INTO `iva` (`year`, `iva`, `irpf`) 
					VALUES (:yr, :ai, :ar) 
					ON DUPLICATE KEY UPDATE `iva`=:ai, `irpf`=:ar;
				", array(
					'yr' => $fn_inputs['a_year'],
					'ai' => $fn_inputs['a_iva'],
					'ar' => $fn_inputs['a_irpf'],
				));
				
				if($fn_q)
				{
					$fn_result = array(
						'status' => 200,
						'message' => 'Iva creado',
						'data' => $fn_inputs,
					);
				}else{
					$fn_result = array(
						'status' => 400,
						'message' => 'No he podido crear nada',
					);
				}
			}
			
			if($fn_ajax == 'upIva')
			{
				$fn_q_sets = array();
				
				if(isset($fn_inputs['i_iva']) && count($fn_inputs['i_iva']) !== 0)
				{
					foreach($fn_inputs['i_iva'] as $mk => $mv)
					{
						$db->Fetch("
							INSERT INTO `iva` (`year`, `iva`, `irpf`) 
							VALUES (:mk, :mi, :mir) 
							ON DUPLICATE KEY UPDATE `year`=:mk, `iva`=:mi, `irpf`=:mir;
						", array(
							'mk' => $mk,
							'mi' => $mv['iva'],
							'mir' => $mv['irpf'],
						));
					}
					
					$fn_result = array(
						'status' => 200,
						'message' => 'Datos guardados',
					);
				}else{
					$fn_result = array(
						'status' => 400,
						'message' => 'No hay datos a guardar',
					);
				}
			}
			
			exit(json_encode($fn_result));
		break;
		
		/* ------------------------------------------------------------------------------------------------ */
		
		//CLIENTES MANAGE AJAX CALLS ADMIN SIDE
		case "upMetas":
		case "delDir":
		case "addDir":
		case "upPassCliente":
		case "upPassUsuarios":
		case "upUsuarios":
		case "upCliente":
		case "delCliente":
		case "delUsuarios":
		case "addUsuarios":
		case "addCliente":
			if(IsHotlink()) exit(json_encode(array(
				'status' => 400,
				'message' => 'Ajax Fraud cached!',
			)));
			
			$u_level = $too_login->isAuth(100, false, $CONFIG['site']['tooSType']);
			
			if($u_level !== 200) exit(json_encode(array(
				'status' => 400,
				'message' => 'No puede hacer esto, no tiene autorización!.',
			)));
			
			$fn_data = array();
			
			switch($fn_ajax)
			{
				case "addUsuarios":
				case "addCliente":
					//f_page_name
					//f_hash
					
					/*
					//slo puede crear y modificar el admins
					if(preg_match("/addUsuarios/", $fn_ajax) && adminUSER)
					{
						
					}
					*/
					
					if(!isset($fn_p['f_user_name'])) exit(json_encode(array(
						'status' => 400,
						'message' => 'Me has de decir un nombre',
					)));
					
					if(!isset($fn_p['f_user_pass'])) exit(json_encode(array(
						'status' => 400,
						'message' => 'No puedo crear un usuario sin una contraseña inicial.',
					)));
					
					$fn_reg = date('Y-m-d H:i:s');
					$fn_now = microtime();
					
					
					//admin pass is more strong than clients one
					if(preg_match("/Usuarios/", $fn_ajax))
					{
						$fn_pass = (class_exists("tooSCrypt")) ? tooSCrypt::en($fn_p['f_user_pass'], $CONFIG['site']['tooSHash']) : hash_hmac('sha512', "{$fn_p['f_user_name']}~{$fn_p['f_user_pass']}", $CONFIG['site']['tooSHash'], false);
					}else{
						$fn_pass = md5("{$fn_p['f_user_name']}~{$fn_p['f_user_pass']}");
					}
					
					$fn_q = $db->ExecuteSQL("
						INSERT INTO `users` (`user_name`, `user_pass`, `user_email`, `user_registred`, `user_status`, `user_activation_key`)
						VALUES (:un, :up, :ue, :rg, '1', MD5(:nw));
					", array(
						'un' => $fn_p['f_user_name'],
						'up' => $fn_pass,
						'ue' => $fn_p['f_email'],
						'rg' => $fn_reg,
						'nw' => $fn_now,
					));
					
					if($fn_q) 
					{
						$db->Fetch("
							INSERT INTO `users_meta` (`user_id`, `meta_key`, `meta_value`)
							VALUES (:uid, 'user_level', :lvl);
						", array(
							'uid' => $fn_q,
							'lvl' => (preg_match("/Usuarios/", $fn_ajax)) ? '100' : '15',
						));
						
						$fn_isAdmin = preg_match("/Usuarios/", $fn_ajax) ? "1" : "0";
						
						$db->Fetch("
							INSERT INTO `users_meta` (`user_id`, `meta_key`, `meta_value`)
							VALUES (:uid, 'user_access', :val);
						", array(
							'uid' => $fn_q,
							'val' => (isset($fn_p['f_user_access'])) ? $fn_p['f_user_access'] : $fn_isAdmin,
						));
						
						$db->Fetch("
							INSERT INTO `users_meta` (`user_id`, `meta_key`, `meta_value`)
							VALUES (:uid, 'user_or', :val);
						", array(
							'uid' => $fn_q,
							'val' => (isset($fn_p['f_user_or'])) ? $fn_p['f_user_or'] : $fn_isAdmin,
						));
					}
					
					//send mail con accesos
					if(isset($fn_p['send_access_to_email']) && isset($fn_p['f_email']))
					{
						$fn_def_lang = $CONFIG['site']['defaultLang'];
						
						if(!isset($lang_items[$fn_def_lang]['mail_invitacion_html']) || empty($lang_items[$fn_def_lang]['mail_invitacion_html'])) exit(json_encode(array(
							'status' => 400,
							'message' => 'Falta el mensaje de texto de restablecimiento de contraseña, lo puede modificar en Opciones.',
						)));
						
						$fn_to = $fn_p['f_email'];
						$fn_subject = "[{$CONFIG['site']['sitetitlefull']}] ".getLangItem('mail_invitacion');
						
						$fn_html_p = $lang_items[$fn_def_lang]['mail_invitacion_html'];
						$fn_mail_html = $CONFIG['site']['standartEmail'];
						
						$link_admin_or_cliente = (preg_match("/upUsuarios/", $fn_ajax)) ? "admin" : "mi-cuenta";
						
						$fn_mail_html = str_replace(array(
							'%message%',
							'%regards%', 
							'%site_name%', 
							'%copyz%',
							'%site_dir%', 
							'%site_logo%',
							'%site_link%',
							'%user_name%',
							'%user_pass%',
						), array(
							$fn_html_p,
							$lang_items[$fn_def_lang]['mail_regards'],
							$CONFIG['site']['sitetitlefull'],
							"&copy; ".date('Y')." {$CONFIG['site']['sitetitlefull']}. All rights reserved.",
							'',
							"",
							"{$CONFIG['site']['base']}{$fn_def_lang}/{$link_admin_or_cliente}", //link
							$fn_p['f_user_name'],
							$fn_p['f_user_pass'],
						), $fn_mail_html);
						
						$fn_content = preparehtmlmailBase64($CONFIG['site']['botmail'], $fn_mail_html);
						
						//envio del mail
						@mail($fn_to, $fn_subject, $fn_content['multipart'], $fn_content['headers']);
					}
					
					$fn_data['id'] = $fn_q;
				break;
				
				case "delUsuarios":
				case "delCliente":
					if(!isset($fn_p['id'])) exit(json_encode(array(
						'status' => 400,
						'message' => 'Uiii sobre que item? podrías refrescar?',
					)));
				
					//del page 
					$fn_q = $db->Fetch("
						DELETE FROM `users`
						WHERE `ID`=:id
					", array(
						'id' => $fn_p['id'],
					));
					
					//lang rel
					$db->Fetch("
						DELETE FROM `users_meta`
						WHERE `user_id`=:uid
					", array(
						'uid' => $fn_p['id'],
					));
				break;
				
				case "upUsuarios":
				case "upCliente":
					if(!isset($fn_p['data'])) exit(json_encode(array(
						'status' => 400,
						'message' => 'No puedo actualizar porque no se me esta pasando datos :(',
					)));
					
					if(!isset($fn_p['id'])) exit(json_encode(array(
						'status' => 400,
						'message' => 'Me falta id del usuario para hacer los cambios',
					)));
					
					parse_str($fn_p['data'], $fn_inputs);
					
					$fn_update_rows = array();
					$fn_q_a = array(
						'id' => $fn_p['id'],
					);
					
					$i = 0;
					foreach($fn_inputs as $ik => $iv)
					{
						if(preg_match('/(pass|activ|key|meta|user_access)/', $ik)) continue;
						
						$fn_parse_field_name = str_replace('f_', '', $ik);
						$fn_update_rows[] = "`{$fn_parse_field_name}`=:iv_{$i}";
						$fn_q_a["iv_{$i}"] = $iv;
						
						$i++;
					}
					
					$fn_update_rows = implode(', ', $fn_update_rows);
					
					$fn_q = $db->Fetch("
						UPDATE `users` 
						SET {$fn_update_rows}
						WHERE `ID`=:id
					", $fn_q_a);
					
					//metas
					if(isset($fn_inputs['meta']))
					{
						foreach($fn_inputs['meta'] as $ku => $kv)
						{
							$fn_q = $db->Fetch("
								INSERT INTO `users_meta` (`user_id`, `meta_key`, `meta_value`) 
								VALUES (:uid, :key, :val) 
								ON DUPLICATE KEY UPDATE `meta_value`=:val;
							", array(
								"uid" => $fn_p['id'],
								"key" => $ku,
								"val" => $kv
							));
						}
					}
				break;
				
				case "upPassUsuarios":
				case "upPassCliente":
					if(!isset($fn_p['data'])) exit(json_encode(array(
						'status' => 400,
						'message' => 'No puedo actualizar porque no se me esta pasando datos :(',
					)));
					
					if(!isset($fn_p['id']) && $fn_p['id'] !== '1') exit(json_encode(array(
						'status' => 400,
						'message' => 'Me falta id del usuario para hacer los cambios',
					)));
					
					parse_str($fn_p['data'], $fn_inputs);
					
					if(!isset($fn_inputs['f_user_pass']) || !isset($fn_inputs['f_user_pass_rep'])) exit(json_encode(array(
						'status' => 400,
						'message' => 'La falta la contraseña.',
					)));
					
					if($fn_inputs['f_user_pass'] !== $fn_inputs['f_user_pass_rep']) exit(json_encode(array(
						'status' => 400,
						'message' => 'No coinciden las contraseñas introducidas :(',
					)));
					
					if(preg_match("/Usuarios/", $fn_ajax))
					{
						//get user name;
						$fn_user_name = $db->FetchValue("
							SELECT `user_name`
							FROM `users`
							WHERE `ID`=:id
							LIMIT 1;
						", array(
							'id' => $fn_p['id'],
						));
						
						$fn_pass = (class_exists("tooSCrypt")) ? tooSCrypt::en($fn_inputs['f_user_pass'], $CONFIG['site']['tooSHash']) : hash_hmac('sha512', "{$fn_user_name}~{$fn_inputs['f_user_pass']}", $CONFIG['site']['tooSHash'], false);
					}else{
						//get user name;
						$fn_user_email = $db->FetchValue("
							SELECT `user_email`
							FROM `users`
							WHERE `ID`=:id
							LIMIT 1;
						", array(
							'id' => $fn_p['id'],
						));
						
						$fn_pass = md5("{$fn_user_email}~{$fn_inputs['f_user_pass']}");
					}
					
					$fn_q = $db->Fetch("
						UPDATE `users` 
						SET `user_pass`=:up
						WHERE `ID`=:id
					", array(
						'up' => $fn_pass,
						'id' => $fn_p['id'],
					));
					
					//send mail con accesos
					if(isset($fn_inputs['send_access_to_email']) && isset($fn_inputs['f_email']))
					{
						$fn_def_lang = $CONFIG['site']['defaultLang'];
						
						if(!isset($lang_items[$fn_def_lang]['mail_restablecerpass_html']) || empty($lang_items[$fn_def_lang]['mail_restablecerpass_html'])) exit(json_encode(array(
							'status' => 400,
							'message' => 'Falta el mensaje de texto de restablecimiento de contraseña, lo puede modificar en Opciones.',
						)));
						
						$fn_to = $fn_inputs['f_email'];
						$fn_subject = "[{$CONFIG['site']['sitetitlefull']}] ".getLangItem('mail_restablecerpass');
						
						$fn_html_p = $lang_items[$fn_def_lang]['mail_restablecerpass_html'];
						$fn_mail_html = $CONFIG['site']['standartEmail'];
						
						$fn_mail_html = str_replace(array(
							'%message%',
							'%regards%', 
							'%site_name%', 
							'%copyz%',
							'%site_dir%', 
							'%site_logo%',
							'%site_link%',
							'%user_name%',
							'%user_pass%',
						), array(
							$fn_html_p,
							$lang_items[$fn_def_lang]['mail_regards'],
							$CONFIG['site']['sitetitlefull'],
							"&copy; ".date('Y')." {$CONFIG['site']['sitetitlefull']}. All rights reserved.",
							'',
							"<img src=\"{$CONFIG['site']['base']}images/logo-mail.png\" width=\"472\" height=\"71\" />",
							"{$CONFIG['site']['base']}{$fn_def_lang}/mi-cuenta",
							$fn_user_name,
							$fn_inputs['f_user_pass'],
						), $fn_mail_html);
						
						$fn_content = preparehtmlmailBase64($CONFIG['site']['botmail'], $fn_mail_html);
						
						//envio del mail
						@mail($fn_to, $fn_subject, $fn_content['multipart'], $fn_content['headers']);
					}
				break;
				
				case "addDir":
					if(!isset($fn_p['data'])) exit(json_encode(array(
						'status' => 400,
						'message' => 'No puedo actualizar porque no se me esta pasando datos :(',
					)));
					
					parse_str($fn_p['data'], $fn_inputs);
					
					//get user_dirs
					$fn_q_meta_dirs = $db->FetchValue("
						SELECT `meta_value`
						FROM `users_meta`
						WHERE `user_id`=:uid
						AND `meta_key`='user_dirs'
						LIMIT 1;
					", array(
						'uid' => $fn_inputs['user_id'],
					));
					
					$fn_dir_data = ($fn_q_meta_dirs && isJson($fn_q_meta_dirs)) ? json_decode($fn_q_meta_dirs) : array();
					
					//counter assign id
					$fn_inputs['id'] = count($fn_dir_data);
					$fn_inputs['dir_default'] = (isset($fn_inputs['dir_default'])) ? 1 : 0;
					
					//desmarcamos otros defaults y dejamos este como principal
					if($fn_inputs['dir_default'])
					{
						foreach($fn_dir_data as $dk => $dv)
						{
							if(isset($dv->dir_default)) unset($fn_dir_data[$dk]->dir_default);
						}
					}
					
					$fn_dir_data[] = $fn_inputs;
					$fn_dir_data = json_encode($fn_dir_data, JSON_UNESCAPED_UNICODE);
					
					//save
					$fn_q = $db->Fetch("
						INSERT INTO `users_meta` (`user_id`, `meta_key`, `meta_value`) 
						VALUES (:uid, 'user_dirs', :dt) 
						ON DUPLICATE KEY UPDATE `meta_value`=:dt;
					", array(
						'uid' => $fn_inputs['user_id'],
						'dt' => $fn_dir_data,
					));
					
					//return
					$fn_data = $fn_inputs;
				break;
				
				case "delDir":
					if(!isset($fn_p['user_id']) && empty($fn_p['user_id'])) exit(json_encode(array(
						'status' => 400,
						'message' => 'El id del usuario es nulo, podrías refrescar la página?',
					)));
					
					if(!isset($fn_p['id']) && empty($fn_p['id'])) exit(json_encode(array(
						'status' => 400,
						'message' => 'Ahora falta el id de la dirección a eliminar.',
					)));
					
					//get user_dirs
					$fn_q_meta_dirs = $db->FetchValue("
						SELECT `meta_value`
						FROM `users_meta`
						WHERE `user_id`=:uid
						AND `meta_key`='user_dirs'
						LIMIT 1;
					", array(
						'uid' => $fn_p['user_id'],
					));
					
					$fn_dir_data = ($fn_q_meta_dirs && isJson($fn_q_meta_dirs)) ? json_decode($fn_q_meta_dirs) : false;
					
					if($fn_dir_data)
					{
						$fn_new_data = array();
						
						foreach($fn_dir_data as $ok => $ov)
						{
							if($ok == $fn_p['id'] || $ov->id == $fn_p['id']) continue;
							$fn_new_data[] = $ov;
						}
						
						$fn_dir_data = json_encode($fn_new_data, JSON_UNESCAPED_UNICODE);
						
						//save
						$fn_q = $db->Fetch("
							UPDATE `users_meta` 
							SET `meta_value`=:mv
							WHERE `meta_key`='user_dirs'
							AND `user_id`=:uid;
						", array(
							'mv' => $fn_dir_data,
							'uid' => $fn_p['user_id'],
						));
					}else{
						$fn_q = $fn_dir_data;
					}
				break;
				
				case "upMetas":
					if(!isset($fn_p['data'])) exit(json_encode(array(
						'status' => 400,
						'message' => 'No puedo actualizar porque no se me esta pasando datos :(',
					)));
					
					parse_str($fn_p['data'], $fn_inputs);
					
					$fn_metas_q_a = array();
					
					foreach($fn_inputs['e'] as $mk => $mv)
					{
						if($mk == 'user_level') continue;
						
						$fn_q = $db->Fetch("
							INSERT INTO `users_meta` (`user_id`, `meta_key`, `meta_value`) 
							VALUES (:uid, :key, :val) 
							ON DUPLICATE KEY UPDATE `meta_value`=:val;
						", array(
							'uid' => $fn_p['id'],
							'key' => $mk,
							'val' => ($mv) ? $mv : '',
						));
					}
					//return
					$fn_data = '';
				break;
			}
			
			if($fn_q)
			{
				exit(json_encode(array(
					'status' => 200,
					'message' => 'Okey, ya esta hecho ;)',
					'data' => $fn_data,
				)));
			}else{
				exit(json_encode(array(
					'status' => 400,
					'message' => 'No no he podido esta vez, nose porque no puedo acceder a la base de datos :(',
				)));
			}
		break;
		
		/* ------------------------------------------------------------------------------------------------ */
		
		//PRODUCTO MANAGE AJAX CALLS ADMIN SIDE
		case "upProducto":
			if(IsHotlink()) exit(json_encode(array(
				'status' => 400,
				'message' => 'Ajax Fraud cached!',
			)));
			
			$u_level = $too_login->isAuth(100, false, $CONFIG['site']['tooSType']);
			
			if($u_level !== 200) exit(json_encode(array(
				'status' => 400,
				'message' => 'No puede hacer esto, no tiene autorización!.',
			)));
			
			if(!isset($fn_p['product_id'])) exit(json_encode(array(
				'status' => 400,
				'message' => 'Falta id del producto, no puedo seguir modificando nada.',
			)));
			
			//titulos
			$fn_title_lang_data = array();
			
			if(isset($fn_p['title'])) foreach($fn_p['title'] as $tk => $tv)
			{
				$fn_title_lang_data[$tk] = htmlentities($tv);
			}
			
			$fn_title_lang_data = json_encode($fn_title_lang_data);
			//end titulos
			
			//subtitulos
			$fn_subtitle_lang_data = array();
			
			if(isset($fn_p['subtitle'])) foreach($fn_p['subtitle'] as $tk => $tv)
			{
				$fn_subtitle_lang_data[$tk] = htmlentities($tv);
			}
			
			$fn_subtitle_lang_data = json_encode($fn_subtitle_lang_data);
			//end subtitulos
	
			//product
			$fn_active = (isset($fn_p['active'])) ? 1 : 0;
			
			$fn_q = $db->Fetch("
				UPDATE `product`
				SET `lang_data`=:ld, `active`=:act, `menu_title`=:mtl, `hash`=:hash, `subtitle_lang_data`=:sld
				WHERE `id`=:pid
			", array(
				'ld' => $fn_title_lang_data,
				'sld' => $fn_subtitle_lang_data,
				'act' => $fn_active,
				'mtl' => $fn_p['menu_title'],
				'hash' => $fn_p['hash'],
				'pid' => $fn_p['product_id'],
			));
			
			//stock
			if(isset($fn_p['stock']) && count($fn_p['stock']) !== 0)
			{
				$fn_q_sets_stock = array();
				$fn_q_a = array(
					'pid' => $fn_p['product_id'],
				);
				
				$i = 0;
				foreach($fn_p['stock'] as $sk => $sv)
				{
					$fn_q_sets_stock[] = "`{$sk}`=:sv_{$i}";
					
					$fn_q_a["sv_{$i}"] = $sv;
					$i++;
				}
				
				$fn_q_sets_stock = implode(', ', $fn_q_sets_stock);
				
				//stock
				$db->Fetch("
					UPDATE `product_stock`
					SET {$fn_q_sets_stock}
					WHERE `prid`=:pid
				", $fn_q_a);
			}
			
			//meta
			if(isset($fn_p['lang_content']) || isset($fn_p['lang_envio_extra']))
			{
				//lang encode
				$fn_lang_content = array();
				
				if(count($fn_p['lang_content']) !== 0) foreach($fn_p['lang_content'] as $k => $v)
				{
					$fn_parse = str_replace(array("\n", "\t", "\r"), '', $v);
					
					$fn_lang_content[$k] = htmlentities($fn_parse);
				}
				
				$fn_lang_content = json_encode($fn_lang_content);
				
				$db->Fetch("
					INSERT INTO `product_meta` (`p_id`, `m_key`, `m_value`) 
					VALUES (:pid, 'lang_content', :mv) 
					ON DUPLICATE KEY UPDATE `m_value`=:mv;
				", array(
					'pid' => $fn_p['product_id'],
					'mv' => $fn_lang_content,
				));
				
				//lang encode
				$fn_lang_envio_extra = array();
				
				if(count($fn_p['lang_envio_extra']) !== 0) foreach($fn_p['lang_envio_extra'] as $k => $v)
				{
					$fn_parse = str_replace(array("\n", "\t", "\r"), '', $v);
					
					$fn_lang_envio_extra[$k] = htmlentities($fn_parse);
				}
				
				$fn_lang_envio_extra = json_encode($fn_lang_envio_extra);
				
				if(isset($fn_p['product_id'])) $db->Fetch("
					INSERT INTO `product_meta` (`p_id`, `m_key`, `m_value`) 
					VALUES (:pid, 'lang_envio_extra', :mv) 
					ON DUPLICATE KEY UPDATE `m_value`=:mv;
				", array(
					'pid' => $fn_p['product_id'],
					'mv' => $fn_lang_envio_extra,
				));
				
				if(isset($fn_p['metas']) && count($fn_p['metas']) !== 0) foreach($fn_p['metas'] as $ak => $av)
				{
					$db->Fetch("
						INSERT INTO `product_meta` (`p_id`, `m_key`, `m_value`) 
						VALUES (:pid, :ak, :av) 
						ON DUPLICATE KEY UPDATE `m_value`=:av;
					", array(
						'pid' => $fn_p['product_id'],
						'ak' => $ak,
						'av' => $av,
					));
				}
			}
			
			if($fn_q)
			{
				exit(json_encode(array(
					'status' => 200,
					'message' => 'Ya esta todo guardado.',
				)));
			}else{
				exit(json_encode(array(
					'status' => 400,
					'message' => 'No he podido acceder a la base de datos.',
				)));
			}
		break;
		
		case "upProduct":
		case "upCategoria":
			if(IsHotlink()) exit(json_encode(array(
				'status' => 400,
				'message' => 'Ajax Fraud cached!',
			)));
			
			$u_level = $too_login->isAuth(100, false, $CONFIG['site']['tooSType']);
			
			if($u_level !== 200) exit(json_encode(array(
				'status' => 400,
				'message' => 'No puede hacer esto, no tiene autorización!.',
			)));
			
			if(!isset($fn_p['id'])) exit(json_encode(array(
				'status' => 400,
				'message' => 'De que estamos hablando? Falta el id :(',
			)));
			
			if(!isset($CONFIG['site']['lang']) && !isJson($CONFIG['site']['lang'])) exit(json_encode(array(
				'status' => 400,
				'message' => 'Hay un problema grave con idiomas, vaya a opciones y revise apartado de idiomas.',
			)));
			
			$fn_lang_title_data = array();
			$fn_site_lang = json_decode($CONFIG['site']['lang']);
			
			foreach($fn_site_lang as $l)
			{
				$fn_lang_title_data[$l] = $fn_p["f_title_{$l}"];
			}
			
			$fn_lang_title_data = json_encode($fn_lang_title_data);
			
			$fn_q = false;
			
			switch($fn_ajax)
			{
				case "upCategoria":
					$fn_q = $db->Fetch("
						UPDATE `category` 
						SET `hash`=:hs, `lang_data`=:ln, `menu_name`=:mu
						WHERE `id`=:id
						LIMIT 1;
					", array(
						'hs' => $fn_p['f_hash'],
						'ln' => $fn_lang_title_data,
						'mu' => $fn_p['f_menu_name'],
						'id' => $fn_p['id'],
					));
				break;
				
				case "upProduct":
					
					$fn_q = $db->Fetch("
						UPDATE `product` 
						SET `hash`=:hash, `lang_data`=:ld, `menu_title`=:mt, `active`=:ac
						WHERE `id`=:id
						LIMIT 1;
					", array(
						'hash' => $fn_p['f_hash'],
						'ld' => $fn_lang_title_data,
						'mt' => $fn_p['f_menu_title'],
						'ac' => $fn_p['f_active'],
						'id' => $fn_p['id'],
					));
					
					//update stock item
					if(isset($fn_p['stock']) && count($fn_p['stock']) !== 0)
					{
						$fn_f_precio_tachado = (isset($fn_p['stock']['f_precio_tachado'])) ? $fn_p['stock']['f_precio_tachado'] : 0;
						$fn_f_precio_coste = (isset($fn_p['stock']['f_precio_coste'])) ? $fn_p['stock']['f_precio_coste'] : 0;
						$fn_f_precio_venta = (isset($fn_p['stock']['f_precio_venta'])) ? $fn_p['stock']['f_precio_venta'] : 0;
						$fn_f_stock_min = (isset($fn_p['stock']['f_stock_min'])) ? $fn_p['stock']['f_stock_min'] : 0;
						$fn_f_stock_base = (isset($fn_p['stock']['f_stock_base'])) ? $fn_p['stock']['f_stock_base'] : 0;
						$fn_f_stock_count = (isset($fn_p['stock']['f_stock_count'])) ? $fn_p['stock']['f_stock_count'] : 0;
						$fn_f_pax_multimplier = (isset($fn_p['stock']['pax_multimplier'])) ? $fn_p['stock']['pax_multimplier'] : 1;
						
						$fn_args_in = array();
						
						if(isset($fn_p['stock']['f_peso'])) $fn_args_in[] = "`peso`='{$fn_p['stock']['f_peso']}'";
						if(isset($fn_p['stock']['f_size_y'])) $fn_args_in[] = "`size_y`='{$fn_p['stock']['f_size_y']}'";
						if(isset($fn_p['stock']['f_size_x'])) $fn_args_in[] = "`size_x`='{$fn_p['stock']['f_size_x']}'";
						
						if(count($fn_args_in) !== 0)
						{
							$fn_args_in = implode(', ', $fn_args_in);
							$fn_args_in = ", {$fn_args_in}";
						}else{
							$fn_args_in = '';
						}
						
						//stock table
						$db->ExecuteSQL("
							UPDATE `product_stock` 
							SET `size_id`=:szid, `color_id`=:cid, `precio_coste`=:pc, `precio_venta`=:pv, `stock_min`=:sm, `stock_base`=:sb, `stock_count`=:sc {$fn_args_in}, `precio_tachado`=:ptch, `pax_multimplier`=:mult
							WHERE `prid`=:id
							AND `item_base`='1'
							LIMIT 1;
						", array(
							'szid' => $fn_p['stock']['f_size_id'],
							'cid' => $fn_p['stock']['f_color_id'],
							'pc' => $fn_f_precio_coste,
							'pv' => $fn_f_precio_venta,
							'sm' => $fn_f_stock_min,
							'sb' => $fn_f_stock_base,
							'sc' => $fn_f_stock_count,
							'ptch' => $fn_f_precio_tachado,
							'mult' => $fn_f_pax_multimplier,
							'id' => $fn_p['id'],
						));
					}
				break;
			}
			
			if($fn_q)
			{
				exit(json_encode(array(
					'status' => 200,
					'message' => 'Yeah! Ya te tengo esto actualizado :)',
				)));
			}else{
				exit(json_encode(array(
					'status' => 400,
					'message' => 'Oh no! no puedo actualizar sera que hay problema de conexión.',
				)));
			}
		break;
		
		//ordenamos productos / categorias
		case "orCategoria":
			if(IsHotlink()) exit(json_encode(array(
				'status' => 400,
				'message' => 'Ajax Fraud cached!',
			)));
			
			$u_level = $too_login->isAuth(100, false, $CONFIG['site']['tooSType']);
			
			if($u_level !== 200) exit(json_encode(array(
				'status' => 400,
				'message' => 'No puede hacer esto, no tiene autorización!.',
			)));
			
			if(!isset($fn_p['data'])) exit(json_encode(array(
				'status' => 400,
				'message' => 'No hay nada que actualizar.',
			)));
			
			foreach($fn_p['data'] as $k => $v)
			{
				$db->ExecuteSQL("
					UPDATE `category`
					SET `order`=:or
					WHERE `id`=:id;
				", array(
					'or' => $v['or'],
					'id' => $v['id'],
				));
			}
			
			exit(json_encode(array(
				'status' => 200,
				'message' => 'Ordenado',
			)));
		break;
		
		//cargamos lista de dependecias sobre categorias
		case "manageCategoria":
			if(IsHotlink()) exit(json_encode(array(
				'status' => 400,
				'message' => 'Ajax Fraud cached!',
			)));
			
			$u_level = $too_login->isAuth(100, false);
			
			if($u_level !== 200) exit(json_encode(array(
				'status' => 400,
				'message' => 'No puede hacer esto, no tiene autorización!.',
			)));
			
			$fn_q = $db->FetchAll("
				SELECT *
				FROM `category`
			");
			
			if($fn_q)
			{
				try {
					$fn_data = treeArray($fn_q);
					
					exit(json_encode(array(
						'status' => 200,
						'message' => 'Lista generada',
						'data' => $fn_data,
					)));
				}catch(Exception $e)
				{
					exit(json_encode(array(
						'status' => 400,
						'message' => 'Por alguna causa no puedo cargar el contenido.',
					)));
				}
			}else{
				exit(json_encode(array(
					'status' => 400,
					'message' => 'Por alguna causa no puedo cargar el contenido.',
				)));
			}
		break;
		
		//creamos categoria y productos
		case "addCategoria":
		case "addProducto":
			if(IsHotlink()) exit(json_encode(array(
				'status' => 400,
				'message' => 'Ajax Fraud cached!',
			)));
			
			$u_level = $too_login->isAuth(100, false, $CONFIG['site']['tooSType']);
			
			if($u_level !== 200) exit(json_encode(array(
				'status' => 400,
				'message' => 'No puede hacer esto, no tiene autorización!.',
			)));
			
			if(!isset($CONFIG['site']['lang']) && !isJson($CONFIG['site']['lang'])) exit(json_encode(array(
				'status' => 400,
				'message' => 'Hay un problema grave con idiomas, vaya a opciones y revise apartado de idiomas.',
			)));
			
			$fn_active = (isset($fn_p['f_active'])) ? $fn_p['f_active'] : 0;
			$fn_order = (isset($fn_p['f_or'])) ? $fn_p['f_or'] : 0;
			
			$fn_lang_title_data = array();
			$fn_lang_title_out = array();
			
			$fn_site_lang = json_decode($CONFIG['site']['lang']);
			foreach($fn_site_lang as $l)
			{
				$fn_lang_title_data[$l] = htmlentities($fn_p["f_title_{$l}"]);
				$fn_lang_title_out[] = $fn_p["f_title_{$l}"];
			}
			
			$fn_lang_title_data = json_encode($fn_lang_title_data);
			$fn_lang_title_out = implode(' | ', $fn_lang_title_out);
			
			$fn_insert_id = false;
			
			if(preg_match('/addCategoria/', $fn_ajax))
			{
				$fn_insert_id = $db->ExecuteSQL("
					INSERT INTO `category` (`parent_id`, `order`, `hash`, `lang_data`, `menu_name`)
					VALUES ('0', :or, :hs, :lt, :mn);
				", array(
					'or' => $fn_order,
					'hs' => $fn_p['f_hash'],
					'lt' => $fn_lang_title_data,
					'mn' => $fn_p['f_menu_name'],
				));
			}
			
			if(preg_match('/addProducto/', $fn_ajax))
			{
				$fn_insert_id = $db->ExecuteSQL("
					INSERT INTO `product` (`order`, `lang_data`, `active`, `menu_title`, `hash`)
					VALUES (:or, :lt, :ac, :mt, :hs);
				", array(
					'or' => $fn_order,
					'lt' => $fn_lang_title_data,
					'ac' => $fn_active,
					'mt' => $fn_p['f_menu_title'],
					'hs' => $fn_p['f_hash'],
				));
				
				$fn_f_precio_coste = (isset($fn_p['stock']['f_precio_coste'])) ? $fn_p['stock']['f_precio_coste'] : 0;
				$fn_f_precio_venta = (isset($fn_p['stock']['f_precio_venta'])) ? $fn_p['stock']['f_precio_venta'] : 0;
				$fn_f_stock_min = (isset($fn_p['stock']['f_stock_min'])) ? $fn_p['stock']['f_stock_min'] : 0;
				$fn_f_stock_base = (isset($fn_p['stock']['f_stock_base'])) ? $fn_p['stock']['f_stock_base'] : 0;
				$fn_f_stock_count = (isset($fn_p['stock']['f_stock_count'])) ? $fn_p['stock']['f_stock_count'] : 0;
				$fn_f_peso = (isset($fn_p['stock']['f_peso'])) ? $fn_p['stock']['f_peso'] : 0;
				$fn_f_size_y = (isset($fn_p['stock']['f_size_y'])) ? $fn_p['stock']['f_size_y'] : 0;
				$fn_f_size_x = (isset($fn_p['stock']['f_size_x'])) ? $fn_p['stock']['f_size_x'] : 0;
				$fn_f_pax_multimplier = (isset($fn_p['stock']['pax_multimplier'])) ? $fn_p['stock']['pax_multimplier'] : 1;
				
				//stock table
				$fn_stock = $db->ExecuteSQL("
					INSERT INTO `product_stock` (`prid`, `size_id`, `color_id`, `precio_coste`, `precio_venta`, `stock_min`, `stock_base`, `stock_count`, `peso`, `size_y`, `size_x`, `item_base`, `pax_multimplier`)
					VALUES (:pid, :szid, :cid, :pr, :pv, :sm, :sb, :sc, :fp, :sy, :sx, '1', :mult);
				", array(
					'pid' => $fn_insert_id,
					'szid' => (isset($fn_p['stock']['f_size_id'])) ? $fn_p['stock']['f_size_id'] : null,
					'cid' => (isset($fn_p['stock']['f_color_id'])) ? $fn_p['stock']['f_color_id'] : null,
					'pr' => ($fn_f_precio_coste) ? $fn_f_precio_coste : 0,
					'pv' => ($fn_f_precio_venta) ? $fn_f_precio_venta : 0,
					'sm' => ($fn_f_stock_min) ? $fn_f_stock_min : 0,
					'sb' => ($fn_f_stock_base) ? $fn_f_stock_base : 0,
					'sc' => ($fn_f_stock_count) ? $fn_f_stock_count : 0,
					'fp' => ($fn_f_peso) ? $fn_f_peso : 0,
					'sy' => ($fn_f_size_y) ? $fn_f_size_y : 0,
					'sx' => ($fn_f_size_x) ? $fn_f_size_x : 0,
					'mult' => ($fn_f_pax_multimplier) ? $fn_f_pax_multimplier : 1,
				));
			}
			
			if($fn_insert_id)
			{
				$fn_result = array(
					'status' => 200,
					'message' => 'Hura! Item creado con éxito. :)',
					'data' => array(
						'id' => $fn_insert_id,
						'category_title' => $fn_lang_title_out,
						'item_title' => $fn_lang_title_out,
					),
				);
			}else{
				$fn_result = array(
					'status' => 400,
					'message' => 'Por alguna causa no puedo crear este item. No estarás repitiendo el título y el hash? :P en ese caso no puedo crear un item que ya existe.',
				);
			}
			
			exit(json_encode($fn_result));
		break;
		
		//eliminamos producto o categoria
		case "delItemProduct":
		case "delItemCategoria":
			if(IsHotlink()) exit(json_encode(array(
				'status' => 400,
				'message' => 'Ajax Fraud cached!',
			)));
			
			$u_level = $too_login->isAuth(100, false, $CONFIG['site']['tooSType']);
			
			if($u_level !== 200) exit(json_encode(array(
				'status' => 400,
				'message' => 'No puede hacer esto, no tiene autorización!.',
			)));
			
			if(!isset($fn_p['id'])) exit(json_encode(array(
				'status' => 400,
				'message' => 'Nose que hay que hacer no me dices la id?',
			)));
			
			$fn_q_table = (preg_match('/delItemProduct/', $fn_ajax)) ? 'product' : 'category';
			
			$fn_del_item = $db->Fetch("
				DELETE FROM {$fn_q_table}
				WHERE `id`=:id
				LIMIT 1;
			", array(
				'id' => $fn_p['id'],
			));
			
			if($fn_del_item)
			{
				if(preg_match('/delItemProduct/', $fn_ajax))
				{
					//remove stock
					$db->ExecuteSQL("
						DELETE FROM `product_stock`
						WHERE `prid`=:id
					", array(
						'id' => $fn_p['id'], 
					));
					
					//remove ofertas
					$db->ExecuteSQL("
						DELETE FROM `oferta_rel`
						WHERE `prid`=:id
					", array(
						'id' => $fn_p['id'],
					));
					
					//remove metas
					$db->ExecuteSQL("
						DELETE FROM `product_meta`
						WHERE `p_id`=:id
					", array(
						'id' => $fn_p['id'],
					));
				}
				
				$fn_result = array(
					'status' => 200,
					'message' => 'Hura! Un@ menos.',
				);
			}else{
				$fn_result = array(
					'status' => 400,
					'message' => 'No he podido eliminar este item.',
				);
			}
			
			exit(json_encode($fn_result));
		break;
		
		//quitamos / ponemos categoria del producto
		case "uncatProduct":
		case "catProduct":
			if(IsHotlink()) exit(json_encode(array(
				'status' => 400,
				'message' => 'Ajax Fraud cached!',
			)));
			
			$u_level = $too_login->isAuth(100, false, $CONFIG['site']['tooSType']);
			
			if($u_level !== 200) exit(json_encode(array(
				'status' => 400,
				'message' => 'No puede hacer esto, no tiene autorización!.',
			)));
			
			if($fn_ajax == 'uncatProduct')
			{
				if(!isset($fn_p['ids'])) exit(json_encode(array(
					'status' => 400,
					'message' => 'Nose que hay que hacer no me dices la id?',
				)));
				
				$fn_ids = implode(', ', $fn_p['ids']);
				
				$fn_q = $db->ExecuteSQL("
					UPDATE `product`
					SET `cat_id`=NULL
					WHERE `id` IN({$fn_ids})
				");
			}else{
				if(!isset($fn_p['data'])) exit(json_encode(array(
					'status' => 400,
					'message' => 'Nose que hay que hacer no se esta pasando el data.',
				)));
				
				foreach($fn_p['data'] as $dk => $dv)
				{
					$db->ExecuteSQL("
						UPDATE `product`
						SET `cat_id`=:cid, `order`=:or
						WHERE `id`=:id
					", array(
						'cid' => $dv['cat_id'], 
						'or' => $dv['or'],
						'id' => $dv['id'],
					));
				}
				
				$fn_q = true;
			}
			
			if($fn_q)
			{
				exit(json_encode(array(
					'status' => 200,
					'message' => 'Actualizado',
				)));
			}else{
				exit(json_encode(array(
					'status' => 400,
					'message' => 'No hay nada que actualizar',
				)));
			}
		break;
		
		/* ------------------------------------------------------------------------------------------------ */
		
		//creamos actualizamos variedad de producto
		case "upVarProducto":
			if(IsHotlink()) exit(json_encode(array(
				'status' => 400,
				'message' => 'Ajax Fraud cached!',
			)));
			
			$u_level = $too_login->isAuth(100, false);
			
			if($u_level !== 200) exit(json_encode(array(
				'status' => 400,
				'message' => 'No puede hacer esto, no tiene autorización!.',
			)));
			
			if(!isset($fn_p['prid'])) exit(json_encode(array(
				'status' => 400,
				'message' => 'Me falta el ID del producto.',
			)));
			
			if(!isset($fn_p['id']))
			{
				try{
					$fn_q_atr = array(
						'prid' => $fn_p['prid'],
						's' =>  (isset($fn_p['var_size'])) ? $fn_p['var_size'] : null,
						'c' =>  (isset($fn_p['var_color'])) ? $fn_p['var_color'] : null,
						'pc' => (isset($fn_p['var_precio_coste'])) ? $fn_p['var_precio_coste'] : 0,
						'pt' => (isset($fn_p['var_precio_oferta'])) ? $fn_p['var_precio_oferta'] : 0,
						'pv' => (isset($fn_p['var_precio_venta'])) ? $fn_p['var_precio_venta'] : 0,
						'sm' => (isset($fn_p['var_stock_min'])) ? $fn_p['var_stock_min'] : 0,
						'sb' => (isset($fn_p['var_stock_base'])) ? $fn_p['var_stock_base'] : 0,
						'sc' => (isset($fn_p['var_stock_actual'])) ? $fn_p['var_stock_actual'] : 0,
						'p' =>  (isset($fn_p['var_peso']) && !empty($fn_p['var_peso'])) ? $fn_p['var_peso'] : "0.1",
						'sy' => (isset($fn_p['var_size_y'])) ? $fn_p['var_size_y'] : 0,
						'sx' => (isset($fn_p['var_size_x'])) ? $fn_p['var_size_x'] : 0,
						'mult' => (isset($fn_p['pax_multimplier'])) ? $fn_p['pax_multimplier'] : 1,
					);
					
					$fn_q_c = $db->ExecuteSQL("
						INSERT INTO `product_stock` (`prid`, `size_id`, `color_id`, `precio_coste`, `precio_tachado`, `precio_venta`, `stock_min`, `stock_base`, `stock_count`, `peso`, `size_y`, `size_x`, `pax_multimplier`) 
						VALUES (:prid, :s, :c, :pc, :pt, :pv, :sm, :sb, :sc, :p, :sy, :sx, :mult);
					", $fn_q_atr);
				}catch (Exception $e) 
				{
					$fn_result = array(
						'status' => 400,
						'message' => 'Todos los campos son obligatorios, asegurese de que no exista un dublicado.',
					);
					
					exit(json_encode($fn_result));
				}
			}
			
			if(isset($fn_p['id']) && isset($fn_p['prid']))
			{
				//update
				$fn_q_c = $db->Fetch("
					UPDATE `product_stock`
					SET `prid`=:prid, `size_id`=:s, `color_id`=:c, `precio_coste`=:pc, `precio_tachado`=:pt, `precio_venta`=:pv, `stock_min`=:sm, `stock_base`=:sb, `stock_count`=:sc, `peso`=:p, `size_y`=:sy, `size_x`=:sx, `pax_multimplier`
					WHERE `id`=:id
				", array(
					'id' => $fn_p['id'],
					'prid' => $fn_p['prid'],
					's' =>  (isset($fn_p['var_size'])) ? $fn_p['var_size'] : null,
					'c' =>  (isset($fn_p['var_color'])) ? $fn_p['var_color'] : null,
					'pc' => (isset($fn_p['var_precio_coste'])) ? $fn_p['var_precio_coste'] : 0,
					'pt' => (isset($fn_p['var_precio_oferta'])) ? $fn_p['var_precio_oferta'] : 0,
					'pv' => (isset($fn_p['var_precio_venta'])) ? $fn_p['var_precio_venta'] : 0,
					'sm' => (isset($fn_p['var_stock_min'])) ? $fn_p['var_stock_min'] : 0,
					'sb' => (isset($fn_p['var_stock_base'])) ? $fn_p['var_stock_base'] : 0,
					'sc' => (isset($fn_p['var_stock_actual'])) ? $fn_p['var_stock_actual'] : 0,
					'p' => (isset($fn_p['var_peso'])) ? $fn_p['var_peso'] : "0.1",
					'sy' => (isset($fn_p['var_size_y'])) ? $fn_p['var_size_y'] : 0,
					'sx' => (isset($fn_p['var_size_x'])) ? $fn_p['var_size_x'] : 0,
					'mult' => (isset($fn_p['pax_multimplier'])) ? $fn_p['pax_multimplier'] : 1,
				));
			}
			
			if($fn_q_c)
			{
				$fn_size_title = '';
				$fn_color_title = '';
				
				if(isset($fn_p['var_size']))
				{
					$fn_q_size = $db->FetchValue("
						SELECT `lang_data`
						FROM `product_size`
						WHERE `id`=:id
					", array(
						'id' => $fn_p['var_size'],
					));
				
					$fn_size_title = langTitleJsonToStringJointer($fn_q_size);
				}
				
				if(isset($fn_p['var_color']))
				{
					$fn_q_color = $db->FetchValue("
						SELECT `lang_data`
						FROM `product_color`
						WHERE `id`=:id
					", array(
						'id' => $fn_p['var_color'],
					));
				
					$fn_color_title = langTitleJsonToStringJointer($fn_q_color);
				}
				
				$fn_result = array(
					'status' => 200,
					'message' => 'Hura! Item creado con éxito. :)',
					'data' => array(
						'id' => (isset($fn_p['id'])) ? $fn_p['id'] : $fn_q_c,
						'size' => $fn_size_title,
						'color' => $fn_color_title,
						'stock_count' => (isset($fn_p['var_stock_actual'])) ? $fn_p['var_stock_actual'] : 0,
						'precio_venta' => (isset($fn_p['var_precio_venta'])) ? $fn_p['var_precio_venta'] : 0,
						'precio_tachado' => (isset($fn_p['var_precio_oferta'])) ? $fn_p['var_precio_oferta'] : 0,
					),
				);
			}else{
				$fn_result = array(
					'status' => 400,
					'message' => 'Por alguna causa no puedo crear este item.',
				);
			}
			
			exit(json_encode($fn_result));
		break;
		
		//crear usuario recuperar contraseña
		case "newClient":
			if(IsHotlink()) exit(json_encode(array(
				'status' => 400,
				'message' => 'Ajax Fraud cached!',
			)));
			
			if(!isset($fn_p['data'])) exit(json_encode(array(
				'status' => 400,
				'message' => getLangItem('msg_no_data'),
				'dom' => array("n_email"),
			)));
			
			parse_str($fn_p['data'], $fn_inputs);
			
			if(!isset($fn_inputs['n_email']) || empty($fn_inputs['n_email'])) exit(json_encode(array(
				'status' => 400,
				'message' => getLangItem('msg_no_data'),
				'dom' => array("n_email"),
			)));
			
			if(emailValidation($fn_inputs['n_email'])) exit(json_encode(array(
				'status' => 400,
				'message' => getLangItem('msg_email_not_valid'),
				'dom' => array("n_email"),
			)));
			
			$fn_f_check_email = $cl_m->parseSTMP($fn_inputs['n_email'], false);
				
			if(!$fn_f_check_email) exit(json_encode(array(
				'status' => 400,
				'message' => getLangItem('msg_error_param'),
				'dom' => array("n_email"),
			)));
			
			$fn_check_exist_mail = $db->FetchArray("
				SELECT COUNT(*) AS 'count', `user_email`
				FROM `users`
				WHERE `user_email`=:em
				LIMIT 1;
			", array(
				'em' => $fn_inputs['n_email'],
			));
			
			if($fn_check_exist_mail && $fn_check_exist_mail['count'] != "0")
			{
				exit(json_encode(array(
					'status' => 400,
					'message' => getLangItem('msg_new_client_create_error'),
					'dom' => array("n_email"),
				)));
			}else{
				$fn_new_act_key = md5(microtime());
				$fn_set_new_password = substr(md5(rand()), 8);
				$fn_set_new_name_user = substr(str_replace(array('.', ' '), '', microtime()), 8);
				$fn_today_date = date('Y-m-d H:i:s');
				
				$fn_f_check_email = $cl_m->parseSTMP($fn_inputs['n_email']);
				
				$fn_q = $db->ExecuteSQL("
					INSERT INTO `users` (`user_name`, `user_email`, `user_pass`, `user_registred`, `user_status`, `user_activation_key`)
					VALUES (:un, :en, :ps, :td, '0', :ac);
				", array(
					'un' => $fn_set_new_name_user,
					'en' => $fn_f_check_email,
					'ps' => md5("{$fn_f_check_email}~{$fn_set_new_password}"),
					'td' => $fn_today_date,
					'ac' => $fn_new_act_key,
				));
				
				//mail al cliente con contraseña y activación
				if($fn_q)
				{
					//metas
					$db->Fetch("
						INSERT INTO `users_meta` (`user_id`, `meta_key`, `meta_value`)
						VALUES (:uid, 'user_level', '15');
					", array(
						'uid' => $fn_q
					));
					
					$fn_to = $fn_f_check_email;
					$fn_subject = getLangItem('mail_subject_new_client');
					
					$fn_html_p = getLangItem('mail_new_client_html');
					$fn_mail_html = $CONFIG['site']['standartEmail'];
					
					$fn_mail_html = str_replace(array(
						'%message%',
						'%regards%', 
						'%site_name%', 
						'%copyz%',
						'%site_dir%', 
						'%site_logo%',
						'%site_link%',
						'%user_name%',
						'%user_pass%',
					), array(
						$fn_html_p,
						getLangItem('mail_regards'),
						$CONFIG['site']['sitetitlefull'],
						"&copy; ".date('Y')." {$CONFIG['site']['sitetitlefull']}. All rights reserved.",
						"Client ID: <strong>{$fn_set_new_name_user}</strong>",
						"<img src=\"{$CONFIG['site']['base']}images/logo-mail.png\" width=\"472\" height=\"71\" />",
						"{$CONFIG['site']['base']}{$st_lang}/login?activation_key={$fn_new_act_key}", //link
						$fn_inputs['n_email'],
						$fn_set_new_password,
					), $fn_mail_html);
					
					$fn_content = preparehtmlmailBase64($CONFIG['site']['botmail'], $fn_mail_html);
					
					//envio del mail
					$fn_send = @mail($fn_to, $fn_subject, $fn_content['multipart'], $fn_content['headers']);
					
					if($fn_send)
					{
						exit(json_encode(array(
							'status' => 200,
							'message' => getLangItem('msg_new_client_mail_sended'),
						)));
					}else{
						exit(json_encode(array(
							'status' => 400,
							'message' => getLangItem('msg_new_client_create_error'),
						)));
					}
				}else{
					exit(json_encode(array(
						'status' => 400,
						'message' => getLangItem('msg_new_client_create_error'),
					)));
				}
			}
		break;
		
		//client update pass desde su admin
		case "clientUpPass":
			if(IsHotlink()) exit(json_encode(array(
				'status' => 400,
				'message' => 'Ajax Fraud cached!',
			)));
			
			$u_level = $too_login->isAuth(15, false);
			
			if($u_level !== 200) exit(json_encode(array(
				'status' => 400,
				'message' => getLangItem('no_level_msg'),
				'dom' => array("pass", "pass_repeat")
			)));
			
			if(!isset($fn_p['data'])) exit(json_encode(array(
				'status' => 400,
				'message' => getLangItem('msg_no_data'),
				'dom' => array("pass", "pass_repeat")
			)));
			
			//array(1) { ["data"]=> string(44) "u_name=test&u_surname=123&u_idd=123&u_tel=13" }
			parse_str($fn_p['data'], $fn_inputs);
			
			if(!isset($fn_inputs['pass']) || !isset($fn_inputs['pass_repeat']) || empty($fn_inputs['pass']) || empty($fn_inputs['pass_repeat'])) exit(json_encode(array(
				'status' => 400,
				'message' => 'Empty fields',
				'dom' => array("pass", "pass_repeat"),
			)));
			
			if($fn_inputs['pass'] !== $fn_inputs['pass_repeat']) exit(json_encode(array(
				'status' => 400,
				'message' => 'Not Match!',
				'dom' => array("pass", "pass_repeat"),
			)));
			
			$fn_login_user_data = $too_login->getUserData();
			
			$fn_up = $db->Fetch("
				UPDATE `users`
				SET `user_pass`=:key
				WHERE `ID`=:id
			", array(
				"id" => $fn_login_user_data->ID,
				"key" => md5("{$fn_login_user_data->user_email}~{$fn_inputs['pass']}"),
			));
			
			if($fn_up)
			{
				$too_login->updateUserData();
				
				$fn_result = array(
					'status' => 200,
					'message' => 'Contraseña cambiada',
				);
			}else{
				$fn_result = array(
					'status' => 400,
					'message' => 'Error al cambiar contraseña',
				);
			}
			
			exit(json_encode($fn_result));
		break;
		
		//modificacion de datos personales del usuario
		case "clientPersonalData":
			if(IsHotlink()) exit(json_encode(array(
				'status' => 400,
				'message' => 'Ajax Fraud cached!',
			)));
			
			$u_level = $too_login->isAuth(15, false);
			
			if($u_level !== 200) exit(json_encode(array(
				'status' => 400,
				'message' => getLangItem('no_level_msg'),
				'dom' => array("u_name", "u_surname", "user_name")
			)));
			
			if(!isset($fn_p['data'])) exit(json_encode(array(
				'status' => 400,
				'message' => getLangItem('msg_no_data'),
				'dom' => array("u_name", "u_surname", "user_name")
			)));
			
			//array(1) { ["data"]=> string(44) "u_name=test&u_surname=123&u_idd=123&u_tel=13" }
			parse_str($fn_p['data'], $fn_inputs);
			
			$fn_login_user_data = $too_login->getUserData();
			$fn_user_id = ($fn_login_user_data->ID) ? $fn_login_user_data->ID : exit(json_encode(array(
				'status' => 400,
				'message' => getLangItem('msg_client_persdata_no_updated'),
				'dom' => array("u_name", "u_surname", "user_name")
			)));
			
			$fn_inJson = $fn_inputs;
			unset($fn_inJson['user_name']);
			unset($fn_inJson['user_email']);
			unset($fn_inJson['pass']);
			unset($fn_inJson['pass_repeat']);
			
			$fn_get_user_pers_data = $db->FetchValue("
				SELECT `meta_value`
				FROM `users_meta`
				WHERE `user_id`=:id
				AND `meta_key`='user_pers_data'
				LIMIT 1;
			", array(
				"id" => $fn_login_user_data->ID
			));
			
			$fn_json_data = ($fn_get_user_pers_data && isJson($fn_get_user_pers_data)) ? json_encode(array_merge(json_decode($fn_get_user_pers_data, true), $fn_inJson)) : json_encode($fn_inJson);
			
			//update user name
			if(isset($fn_inputs['user_name'])) $db->Fetch("
				UPDATE `users`
				SET `user_name`=:u
				WHERE `ID`=:id
				LIMIT 1;
			", array(
				"id" => $fn_login_user_data->ID,
				"u" => $fn_inputs['user_name']
			));
			
			//save
			$fn_q = $db->Fetch("
				INSERT INTO `users_meta` (`user_id`, `meta_key`, `meta_value`) 
				VALUES (:uid, 'user_pers_data', :jd) 
				ON DUPLICATE KEY UPDATE `meta_value`=:jdd;
			", array(
				'uid' => $fn_user_id,
				'jd' => $fn_json_data,
				'jdd' => $fn_json_data,
			));
			
			if($fn_q)
			{
				$too_login->updateUserData();
				
				exit(json_encode(array(
					'status' => 200,
					'message' => getLangItem('msg_client_persdata_updated'),
				)));
			}else{
				exit(json_encode(array(
					'status' => 400,
					'message' => getLangItem('msg_client_persdata_no_updated'),
					'dom' => array("u_name", "u_surname", "user_name")
				)));
			}
		break;
		
		case "clientUpInvoiceDir":
		case "clientUpShippingDir":
			if(IsHotlink()) exit(json_encode(array(
				'status' => 400,
				'message' => 'Ajax Fraud cached!',
			)));
			
			$u_level = $too_login->isAuth(15, false);
			
			if($u_level !== 200) exit(json_encode(array(
				'status' => 400,
				'message' => getLangItem('no_level_msg'),
				'dom' => array("u_name", "u_surname", "user_name")
			)));
			
			if(!isset($fn_p['data'])) exit(json_encode(array(
				'status' => 400,
				'message' => getLangItem('msg_no_data'),
				'dom' => array("u_name", "u_surname", "user_name")
			)));
			
			//array(1) { ["data"]=> string(44) "u_name=test&u_surname=123&u_idd=123&u_tel=13" }
			parse_str($fn_p['data'], $fn_inputs);
			
			$fn_type = ($fn_ajax == "clientUpInvoiceDir") ? "user_pers_data" : "user_dirs";
			
			
			$fn_error = array();
			//check fields
			if(!isset($fn_inputs[$fn_type]['u_name']) || empty($fn_inputs[$fn_type]['u_name'])) array_push($fn_error, "{$fn_type}[u_name]");
			if(!isset($fn_inputs[$fn_type]['u_email']) || empty($fn_inputs[$fn_type]['u_email'])) array_push($fn_error, "{$fn_type}[u_email]");
			if(!isset($fn_inputs[$fn_type]['u_surname']) || empty($fn_inputs[$fn_type]['u_surname'])) array_push($fn_error, "{$fn_type}[u_surname]");
			if(!isset($fn_inputs[$fn_type]['u_tel']) || empty($fn_inputs[$fn_type]['u_tel'])) array_push($fn_error, "{$fn_type}[u_tel]");
			if(!isset($fn_inputs[$fn_type]['u_cif']) || empty($fn_inputs[$fn_type]['u_cif'])) array_push($fn_error, "{$fn_type}[u_cif]");
			if(!isset($fn_inputs[$fn_type]['dir_country']) || empty($fn_inputs[$fn_type]['dir_country'])) array_push($fn_error, "{$fn_type}[dir_country]");
			if(!isset($fn_inputs[$fn_type]['dir_city']) || empty($fn_inputs[$fn_type]['dir_city'])) array_push($fn_error, "{$fn_type}[dir_city]");
			if(!isset($fn_inputs[$fn_type]['dir_primary']) || empty($fn_inputs[$fn_type]['dir_primary'])) array_push($fn_error, "{$fn_type}[dir_primary]");
			if(!isset($fn_inputs[$fn_type]['dir_post']) || empty($fn_inputs[$fn_type]['dir_post'])) array_push($fn_error, "{$fn_type}[dir_post]");
			if(!isset($fn_inputs[$fn_type]['dir_country']) || empty($fn_inputs[$fn_type]['dir_country'])) array_push($fn_error, "{$fn_type}[dir_country]");
			
			if(sizeof($fn_error) !== 0) exit(json_encode(array(
				'status' => 400,
				'message' => getLangItem('msg_no_data'),
				'dom' => $fn_error
			)));
			
			$fn_login_user_data = $too_login->getUserData();
			
			$fn_user_id = ($fn_login_user_data->ID) ? $fn_login_user_data->ID : exit(json_encode(array(
				'status' => 400,
				'message' => getLangItem('msg_client_persdata_no_updated')
			)));
			
			//datos de facturacion
			$fn_get_user_pers_data = $db->FetchValue("
				SELECT `meta_value`
				FROM `users_meta`
				WHERE `user_id`=:id
				AND `meta_key`=:mkey
				LIMIT 1;
			", array(
				"id" => $fn_login_user_data->ID,
				"mkey" => ($fn_ajax == "clientUpInvoiceDir") ? "user_pers_data" : "user_dirs",
			));
			
			$fn_inJson = ($fn_ajax == "clientUpInvoiceDir") ? $fn_inputs['user_pers_data'] : $fn_inputs['user_dirs'];
			$fn_json_data = ($fn_get_user_pers_data && isJson($fn_get_user_pers_data)) ? json_encode(array_merge(json_decode($fn_get_user_pers_data, true), $fn_inJson)) : 
				json_encode($fn_inJson);
			
			//save
			$fn_q = $db->Fetch("
				INSERT INTO `users_meta` (`user_id`, `meta_key`, `meta_value`) 
				VALUES (:uid, :mkey, :mval) 
				ON DUPLICATE KEY UPDATE `meta_value`=:mvald;
			", array(
				'uid' => $fn_login_user_data->ID,
				'mkey' => ($fn_ajax == "clientUpInvoiceDir") ? "user_pers_data" : "user_dirs",
				'mval' => $fn_json_data,
				'mvald' => $fn_json_data,
			));
			
			if($fn_q)
			{
				$too_login->updateUserData();
				
				exit(json_encode(array(
					'status' => 200,
					'message' => getLangItem('msg_client_pers_data_saved'),
				)));
			}else{
				exit(json_encode(array(
					'status' => 400,
					'message' => getLangItem('msg_client_persdata_no_updated'),
				)));
			}
		break;
		
		/* ------------------------------------------------------------------------------------------------ */
				
		//get data para editar contenido desde modal
		case "getEditData":
			if(IsHotlink()) exit(json_encode(array(
				'status' => 400,
				'message' => 'Ajax Fraud cached!',
			)));
			
			$u_level = $too_login->isAuth(100, false, $CONFIG['site']['tooSType']);
			
			if($u_level !== 200) exit(json_encode(array(
				'status' => 400,
				'message' => 'No puede hacer esto, no tiene autorización!.',
			)));
			
			if(!isset($fn_p['id'])) exit(json_encode(array(
				'status' => 400,
				'message' => 'De que estamos hablando?',
			)));
			
			if(!isset($fn_p['type'])) exit(json_encode(array(
				'status' => 400,
				'message' => 'De que tipo?',
			)));
			
			$fn_q = false;
			$fn_q_out = array();
			
			switch($fn_p['type'])
			{
				case "categoria":
					$fn_q = $db->FetchArray("
						SELECT *
						FROM `category`
						WHERE `id`=:id
					", array(
						'id' => $fn_p['id'],
					));
					
					$fn_q_out = $fn_q;
					$fn_lng_data = (isJson($fn_q['lang_data'])) ? json_decode($fn_q['lang_data'], true) : $fn_q['lang_data'];
					$fn_q_out['lang_data'] = checkLanguagesArray($fn_lng_data);
				break;
				
				case "product":
					//colores y tamaños
					$fn_array_out = array();
			
					$fn_q_c = $db->FetchAll("
						SELECT *
						FROM `product_color`;
					");
					
					$fn_q_s = $db->FetchAll("
						SELECT *
						FROM `product_size`;
					");
					
					if($fn_q_c) foreach($fn_q_c as $ck => $cv)
					{
						$fn_f_data = object_to_array($cv);
						$fn_f_data['lang_parse'] = langTitleJsonToStringJointer($cv->lang_data);
						$fn_array_out['color'][] = $fn_f_data;
					}
					
					unset($fn_f_data);
					
					if($fn_q_s) foreach($fn_q_s as $sk => $sv)
					{
						$fn_f_data = object_to_array($sv);
						$fn_f_data['lang_parse'] = langTitleJsonToStringJointer($sv->lang_data);
						$fn_array_out['size'][] = $fn_f_data;
					}
				
					//p
					$fn_q_p = $db->FetchArray("
						SELECT *
						FROM `product`
						WHERE `id`=:id
						LIMIT 1;
					", array(
						'id' => $fn_p['id'],
					));
					
					//stock
					$fn_q_stock = $db->FetchAll("
						SELECT *
						FROM `product_stock`
						WHERE `prid`=:pid
						LIMIT 1;
					", array(
						'pid' => $fn_p['id'],
					));
					
					$fn_lng_data = (isJson($fn_q_p['lang_data'])) ? json_decode($fn_q_p['lang_data'], true) : $fn_q_p['lang_data'];
					$fn_q_p['lang_data'] = checkLanguagesArray($fn_lng_data);
					
					$fn_q_out['product'] = $fn_q_p;
					$fn_q_out['stock'] = ($fn_q_stock) ? $fn_q_stock : false;
					$fn_q_out['sc'] = (count($fn_array_out) !== 0) ? $fn_array_out : false;
					//$fn_q_out['lang'] = (isset($CONFIG['site']['lang']) && isJson($CONFIG['site']['lang'])) ?  json_decode($CONFIG['site']['lang']) : array($CONFIG['site']['defaultLang']);
				break;
			}
			
			if($fn_q_out)
			{
				exit(json_encode(array(
					'status' => 200,
					'message' => 'Ya lo tengo',
					'data' => $fn_q_out,
				)));
			}else{
				exit(json_encode(array(
					'status' => 400,
					'message' => 'No hay nada respecto a lo que me preguntas',
				)));
			}
		break;
		
		//admin get order details
		case "getPedidoDetails":
			if(IsHotlink()) exit(json_encode(array(
				'status' => 400,
				'message' => 'Ajax Fraud cached!',
			)));
			
			$u_level = $too_login->isAuth(100, false, $CONFIG['site']['tooSType']);
			
			if($u_level !== 200) exit(json_encode(array(
				'status' => 400,
				'message' => 'No puede hacer esto, no tiene autorización!.',
			)));
			
			if(!isset($fn_p['id'])) exit(json_encode(array(
				'status' => 400,
				'message' => 'De que estamos hablando? Falta el id :(',
			)));
			
			$fn_q = $db->FetchArray("
				SELECT `data_cart`, `user_id`, `order_id`
				FROM `orders`
				WHERE `id`=:id
				LIMIT 1;
			", array(
				'id' => $fn_p['id'],
			));
			
			$fn_user_id = (isset($fn_q['user_id']) && $fn_q['user_id']) ? $fn_q['user_id'] : false;
			$fn_order_id = (isset($fn_q['order_id']) && $fn_q['order_id']) ? $fn_q['order_id'] : false;
			
			$fn_q = base64_decode($fn_q['data_cart']);
			
			if($fn_q && isJson($fn_q))
			{
				$fn_q = object_to_array(json_decode($fn_q));
				
				if(isset($fn_q['cart_checkout']) && count($fn_q['cart_checkout']))
				{
					//nombres de la persona
					if($fn_user_id)
					{
						$fn_q_userPersNames = $db->FetchValue("
							SELECT `meta_value`
							FROM `users_meta`
							WHERE `user_id`=:uid
							AND `meta_key`='user_pers_data'
							LIMIT 1;
						", array(
							'uid' => $fn_user_id,
						));
						
						if($fn_q_userPersNames && isJson($fn_q_userPersNames))
						{
							$fn_json_data_loc = json_decode($fn_q_userPersNames, JSON_UNESCAPED_UNICODE);
							$fn_q['user_personal_data'] = $fn_json_data_loc;
						}
					}
					
					$fn_shipping_comp_name = $db->FetchValue("
						SELECT `title`
						FROM `shipping_types`
						WHERE `id`=:id
						LIMIT 1;
					", array(
						'id' => $fn_q['cart_checkout']['cart_shipping_type'],
					));
					
					$fn_q['cart_checkout']['cart_shipping_name'] = $fn_shipping_comp_name;
					if($fn_order_id) $fn_q['cart_checkout']['checkout_id'] = $fn_order_id;
				}
				
				exit(json_encode(array(
					'status' => 200,
					'message' => 'Yeah! aquí esta el order :)',
					'data' => $fn_q,
				)));
			}else{
				exit(json_encode(array(
					'status' => 400,
					'message' => 'Oh no! Algo falla no puedo leer los datos es posible que hay un problema del Ajax o Base64.',
				)));
			}
		break;
		
		//obtiene estado del pedido
		case "getPedidoEstado":
			if(IsHotlink()) exit(json_encode(array(
				'status' => 400,
				'message' => 'Ajax Fraud cached!',
			)));
			
			$u_level = $too_login->isAuth(100, false, $CONFIG['site']['tooSType']);
			
			if($u_level !== 200) exit(json_encode(array(
				'status' => 400,
				'message' => 'No puede hacer esto, no tiene autorización!.',
			)));
			
			if(!isset($fn_p['id'])) exit(json_encode(array(
				'status' => 400,
				'message' => 'De que estamos hablando? Falta el id :(',
			)));
			
			$fn_q = $db->FetchArray("
				SELECT `id`, `payment_status`, `entrega_status`, `num_seg`
				FROM `orders`
				WHERE `id`=:id
				LIMIT 1;
			", array(
				'id' => $fn_p['id'],
			));
			
			if($fn_q)
			{
				exit(json_encode(array(
					'status' => 200,
					'message' => 'Yeah! aquí esta el order :)',
					'data' => $fn_q,
				)));
			}else{
				exit(json_encode(array(
					'status' => 400,
					'message' => 'Oh no! Algo falla no puedo leer los datos.',
				)));
			}
		break;
		
		//modificar datos de envio
		case "adminStPedidoStatus":
			if(IsHotlink()) exit(json_encode(array(
				'status' => 400,
				'message' => 'Ajax Fraud cached!',
			)));
			
			$u_level = $too_login->isAuth(100, false, $CONFIG['site']['tooSType']);
			
			if($u_level !== 200) exit(json_encode(array(
				'status' => 400,
				'message' => 'No puede hacer esto, no tiene autorización!.',
			)));
			
			if(!isset($fn_p['data'])) exit(json_encode(array(
				'status' => 400,
				'message' => 'No me estas mandando nada.',
			)));
			
			/*
				var_dump($fn_p);
			
				array (size=1)
			  'data' => string 'f_num=+13131231+1231231&f_sel_shipping=0&f_sel_payment=1&id=1' (length=61)
			*/
			
			parse_str($fn_p['data'], $fn_inputs);
			
			if(!isset($fn_inputs['id'])) exit(json_encode(array(
				'status' => 400,
				'message' => 'Uy falta id',
			)));
			
			$fn_q = $db->Fetch("
				UPDATE `orders`
				SET `num_seg`=:nm, `entrega_status`=:sh, `payment_status`=:ps
				WHERE `id`=:id
				LIMIT 1;
			", array(
				'nm' => $fn_inputs['f_num'],
				'sh' => $fn_inputs['f_sel_shipping'],
				'ps' => $fn_inputs['f_sel_payment'],
				'id' => $fn_inputs['id'],
			));
			
			if($fn_q)
			{
				exit(json_encode(array(
					'status' => 200,
					'message' => 'Yeah! aquí esta el order :)',
				)));
			}else{
				exit(json_encode(array(
					'status' => 400,
					'message' => 'Oh no! Algo falla no puedo leer los datos.',
				)));
			}
		break;
		
		/* ------------------------------------------------------------------------------------------------ */
		
		//MODAL LOGIN
		case "modal_login":
			
			if(!$fn_p) exit(json_encode(array(
				'status' => 400,
				'message' => 'Metodo no permitido',
			)));
			
			$fn_show_error = true;
			$fn_get_user = (isset($fn_p['login_name'])) ? $fn_p['login_name'] : '';
			$fn_get_pass = (isset($fn_p['login_pass'])) ? $fn_p['login_pass'] : '';
			
			if(!$fn_get_user)
			{
				$fn_show_error .= 'Campo con el nombre de usuario esta vacío.<br/>';
			}
		
			if(!$fn_get_pass)
			{
				$fn_show_error .= 'Campo con la contraseña esta vacío.';
			}
			
			if($fn_show_error == true)
			{
				$l = $too_login->login($fn_get_user, $fn_get_pass, false);
				
				if($l == 200)
				{
					$fn_result = array(
						'status' => $l,
						'message' => 'ok',
					);
				}else if($l == 302){
					$fn_result = array(
						'status' => $l,
						'message' => 'Revise los campos.',
					);
				}else if($l == 400){
					$fn_result = array(
						'status' => $l,
						'message' => 'Hay algún problema con tus credenciales.',
					);
				}
			}else{
				$fn_result = array(
					'status' => 400,
					'message' => $fn_show_error,
				);
			}
			
			exit(json_encode($fn_result));
		break;
		
		default:
			exit;
		break;
	}
}

?>