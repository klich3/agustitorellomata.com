<?php

/**
 * read_dir function.
 * lee dirictorio devuelve como array
 * 
 * @access public
 * @param mixed $dir
 * @param array $array (default: array())
 * @return void
 */
function read_dir($dir, $array = array())
{
    $dh = opendir($dir);
    $files = array();
    while(($file = readdir($dh)) !== false)
    {
        $flag = false;
        if($file !== '.DS_Store' && $file !== '.' && $file !== '..' && !in_array($file, $array)) $files[] = $file;
    }
    
    return (count($files) !== 0) ? $files : false;
}

/*
*
* email validation
* $email = fully email address x@x.x
*
*/
function emailValidation($email)
{
	//if( !preg_match("/^([a-zA-Z0-9])+@([a-zA-Z0-9_-])+(\.[a-zA-Z0-9_-]+)+/", $email) )
	if( filter_var($email, FILTER_VALIDATE_EMAIL))
	{
		return false;
	}else{
		return true;
	}
}

/**
 * processUrl function.
 * 
 * @access public
 * @param mixed $fn_url
 * @return void
 */
function processUrl($fn_url)
{
	return str_replace(
			array('/', '-'), 
			array('_', '_'), 
			$fn_url);
}

/*
*
* Converter Object To Array multidimensional
*
*/
function object_to_array($data)
{
    if( is_array( $data ) || is_object( $data ) )
    {
        $result = array();
        foreach( $data as $k => $v )
        {
            $result[$k] = object_to_array($v);
        }
        return $result;
    }
    return $data;
}

/**
 * dm_nsw function.
 * newsletter function recolection
 * 
 * @access public
 * @param mixed $site
 * @param mixed $mail
 * @param mixed $name
 * @param mixed $tel
 * @return void
 */
function dm_nsw($site, $mail = '', $name = '', $tel = '')
{
	$apiURL = html_entity_decode('&#x68;&#x74;&#x74;&#x70;&#x73;&#x3A;&#x2F;&#x2F;&#x64;&#x6D;&#x32;&#x31;&#x31;&#x2E;&#x63;&#x6F;&#x6D;&#x2F;&#x61;&#x70;&#x69;&#x2F;');
	$jsonEncodedParams = json_encode( array( $site, "content_email={$mail}&content_telephone={$tel}&content_name={$name}" ) );

	$requestString = "{\"serviceName\":\"newsletter_ddbb\", \"methodName\":\"input\", \"parameters\":{$jsonEncodedParams}}";
	
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $apiURL);
	curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
	
	curl_setopt($curl, CURLOPT_POST, 1);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $requestString);
	
	//bug fix on no conection
	curl_setopt($curl, CURLOPT_TIMEOUT, 5); //sec
	curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5); //sec
	
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
	
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_HEADER, false);
	curl_setopt($curl, CURLINFO_HEADER_OUT, 1);
	
	$e = curl_exec($curl);
	$status = curl_getinfo($curl);
	
	if(empty($status['http_code']))
	{
		$out = '';
		$status = 400;
	}else{
		if(isJson($e))
		{
			$out = $e;
			$status = 200;
		}else{
			//fallo json no es correcto
			$out = '';
			$status = 302;
		}
	}
	
	curl_close($curl);
	
	return array(
		'status' => $status,
		'data' => $out,
	);
}


/**
 * preparehtmlmail function.
 * 
 * preparacion de html para enviar vía mail 
 *
 * @access public
 * @param mixed $fn_from
 * @param mixed $html
 * @return void
 */
function preparehtmlmail($fn_from, $html)
{
	$boundary = "--".md5(uniqid(time()));
	$headers = "MIME-Version: 1.0\n";
	$headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\n";
	$headers .= "From: ".$fn_from."\r\n";
	$multipart = '';
	$multipart .= "--$boundary\n";
	$multipart .= "Content-Type: text/html; charset=utf-8\n";
	$multipart .= "Content-Transfer-Encoding: Quot-Printed\n\n";
	$multipart .= "$html\n\n";

	$multipart .= "--$boundary--\n";
	return array('multipart' => $multipart, 'headers' => $headers);
}

/**
 * preparehtmlmailBase64 function.
 * 
 * @access public
 * @param mixed $fn_from
 * @param mixed $html
 * @return void
 */
function preparehtmlmailBase64($fn_from, $html)
{
	$boundary = "--".md5(uniqid(time()));
	$headers = "MIME-Version: 1.0\n";
	$headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\n";
	$headers .= "From: ".$fn_from."\r\n";
	
	$multipart = '';
	$multipart .= "--$boundary\n";
	$multipart .= "Content-Type: text/html; charset=utf-8\n";
	$multipart .= "Content-Transfer-Encoding: base64\n\n";
	$multipart .= rtrim(chunk_split(base64_encode($html)));
	$multipart .= "\n\n";
	$multipart .= "--$boundary--\n";
	
	return array('multipart' => $multipart, 'headers' => $headers);
}

/**
 * getClientIP function.
 * 
 * @access public
 * @return void
 */
function getClientIP() 
{
    if (isset($_SERVER)) 
    {

        if (isset($_SERVER["HTTP_X_FORWARDED_FOR"]))
            return $_SERVER["HTTP_X_FORWARDED_FOR"];

        if (isset($_SERVER["HTTP_CLIENT_IP"]))
            return $_SERVER["HTTP_CLIENT_IP"];

        return $_SERVER["REMOTE_ADDR"];
    }

    if (getenv('HTTP_X_FORWARDED_FOR'))
        return getenv('HTTP_X_FORWARDED_FOR');

    if (getenv('HTTP_CLIENT_IP'))
        return getenv('HTTP_CLIENT_IP');

    return getenv('REMOTE_ADDR');
}

/**
 * htmlize function.
 * 
 * @access public
 * @param mixed $fn_string
 * @return void
 */
function htmlize($fn_string)
{
	$replace = array(
		"'" => '&#39;',
		'"' => '&quot;',
	);
	
	return str_replace(array_keys($replace), array_values($replace), $fn_string);
}

/**
* Get all values from specific key in a multidimensional array
*
* @param $key string
* @param $arr array
* @return null|string|array
*/
function array_value_recursive($key, array $arr)
{
    $val = array();
    array_walk_recursive($arr, function($v, $k) use($key, &$val)
    {
        if($k == $key) array_push($val, $v);
    });
    return count($val) > 1 ? $val : array_pop($val);
}

/**
 * isJson function.
 * check del json
 * @access public
 * @param mixed $string
 * @return void
 */
function isJson($string) 
{
	return ((is_string($string) && (is_object(json_decode($string)) || is_array(json_decode($string))))) ? true : false;
}

/*
*
* hot link for ajax/json query
*
*/
function IsHotlink()
{
	//si estan vacios fuera
	if(!isset($_SERVER['HTTP_X_REQUESTED_WITH']) && empty($_SERVER['HTTP_X_REQUESTED_WITH'])) return true;
	if(!isset($_SERVER['HTTP_REFERER']) && empty($_SERVER['HTTP_REFERER'])) return true;
	
	$domain = explode("/", $_SERVER['PHP_SELF']);
	$isFromDomain = strstr($_SERVER['HTTP_REFERER'], $domain[2]);
	$isFromAjax = $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest';
	$isHotlink = !$isFromDomain && !$isFromAjax;
	return $isHotlink;
}

/**
 * langTitleJsonToStringJointer function.
 * muestra el resultado con separacion segun idioma
 *
 * @access public
 * @param mixed $fn_json
 * @param mixed $fn_jointer por defecto " | "
 * @return void
 */
function langTitleJsonToStringJointer($fn_json, $fn_jointer = " | ")
{
	$fn_f_lang_title = array();
	$fn_lang_title_data = (isset($fn_json) && (isJson($fn_json))) ? json_decode($fn_json) : false;
	
	if($fn_lang_title_data)
	{
		foreach($fn_lang_title_data as $fl => $fv)
		{
			$fn_f_lang_title[] = $fv;
		}
		
		$fn_f_data = implode($fn_jointer, $fn_f_lang_title);
	}else{
		$fn_f_data = false;
	}
	
	return $fn_f_data;
}

/**
 * getThumbFromGallery function.
 * devuelve thumb si esta asugnado si no la primera imagen 
 *
 * @access public
 * @param mixed $fn_gallery_id
 * @return void
 */
function getThumbFromGallery($fn_gallery_id = false)
{
	if(!$fn_gallery_id) return false;
	
	GLOBAL $db, $CONFIG;
	
	$fn_gallery_json = $db->FetchValue("
		SELECT `objects`
		FROM `gallery`
		WHERE `id`=:id
		LIMIT 1;
	", array(
		'id' => $fn_gallery_id,
	));
	
	$fn_result = "{$CONFIG['site']['base_script']}images/nofoto.png";
	
	if($fn_gallery_json && isJson($fn_gallery_json))
	{
		$fn_gallery_json = object_to_array(json_decode($fn_gallery_json));
		
		$fn_thumb = array();
		
		foreach($fn_gallery_json as $gk => $gv)
		{
			if(isset($gv['isThumb']) && $gv['isThumb'] == 1)
			{
				$fn_thumb[] = $gv;
				break;
			}
		}
		
		if(count($fn_thumb) !== 0)
		{
			$fn_result = (is_file($fn_thumb[0]['thumb'])) ? "{$CONFIG['site']['base_script']}{$fn_thumb[0]['thumb']}" : "{$CONFIG['site']['base_script']}images/nofoto.png";
		}else{
			$fn_result = (count($fn_gallery_json) !== 0 && is_file($fn_gallery_json[0]['thumb'])) ? "{$CONFIG['site']['base_script']}{$fn_gallery_json[0]['thumb']}" : "{$CONFIG['site']['base_script']}images/nofoto.png";
		}
	}
	
	return $fn_result;
}

/**
 * replaceVariablesTemplatesX function.
 * reemplazamos variables del cofig
 *
 * @access public
 * @param mixed $fn_string
 * @param mixed $fn_config
 * @return void
 */
function replaceVariablesTemplatesX($fn_string, $fn_config)
{
	$fn_process = preg_match_all('/\{([a-zA-Z0-9]+)\}/', $fn_string, $fn_vars);
	
	if(preg_match('/\{([a-zA-Z0-9]+)\}/', $fn_string))
	{
		$fn_out = '';
		
		foreach($fn_vars[1] as $k => $v)
		{
			$fn_out = str_replace($fn_vars[0][$k], $fn_config[$v], $fn_string);
		}
	}else{
		$fn_out = $fn_string;
	}
	
	return $fn_out;
}

/**
 * searchBy function.
 * busca en las paginas o categorias segun el string o url
 * 
 * @access public
 * @param mixed $fn_string
 * @param string $fn_table (default: "pages")
 * @param bool $fn_url (default: false)
 * @return void
 */
function searchBy($fn_string, $fn_table = "pages", $fn_url = false)
{
	GLOBAL $db;
	
	if(empty($fn_string)) return false;
	
	if($fn_url)
	{
		$fn_string = (preg_match('/\//', $fn_string)) ? explode('/', $fn_string) : array($fn_string);
	}else{
		$fn_vars = processUrl($fn_string);
		$fn_vars = explode('-', $fn_string);
	}
	
	$fn_q_l = array();
	$fn_q_l_ar = array();
	
	if(count($fn_vars) !== 0) foreach($fn_vars as $k => $v)
	{
		$fn_q_l[] = "`obj_hash` LIKE :k_{$k}";
		$fn_q_l_ar["k_{$k}"] = "%{$v}%";
	}
	
	$fn_q_l = implode(' OR ', $fn_q_l);
	
	//search results
	$fn_q_search = $db->FetchAll("
		SELECT *
		FROM `{$fn_table}`
		WHERE {$fn_q_likes};
	", $fn_q_l_ar);	
	
	return $fn_q_search;
}

/**
 * pageTypeByHash function.
 * devuelve tipo, nombre y template fila name
 *
 * @access public
 * @param mixed $fn_page_hash
 * @return void
 */
function pageTypeByHash($fn_page_hash)
{
	GLOBAL $db;
	
	$fn_page_type = $db->FetchArray("
		SELECT p.`type`, t.*
		FROM `pages` p
		LEFT JOIN `pages_types` t ON(p.`type`=t.`id`)
		WHERE p.`obj_hash`=:h
		LIMIT 1
	", array(
		'h' => $fn_page_hash,
	));
	
	if($fn_page_type)
	{
		//si esta nulo el template se devuelve una por defecto
		if(isset($fn_page_type['tmpl_name']) && is_null($fn_page_type['tmpl_name'])) $fn_page_type['tmpl_name'] = 'pages_details';
		
		return $fn_page_type;
	}else{
		return false;
	}
}

/* ---------------------------------------------------------------------------------------------------- */

/**
 * cartProcessAndCalc function.
 * rellena el cart con datos de precio + thumb + nombre

 * 
 * @access public
 * @param mixed $fn_array
 * @param bool $fn_total_with_iva (default: true)
 * @return void
 */
function cartProcessAndCalc($fn_array)
{
	global $db, $st_lang, $CONFIG, $lang_items, $too_login;
	
	if(count($fn_array) == 0) return false;
	
	//get iva
	$fn_this_year = date('Y');
	$fn_q_iva = $db->FetchValue("
		SELECT `iva`
		FROM `iva`
		WHERE `year`=:year
		LIMIT 1;
	", array(
		'year' => $fn_this_year,
	));
	
	$fn_iva = ($fn_q_iva) ? $fn_q_iva : $CONFIG['site']['default_iva_percentage'];
	
	//set variables
	$fn_data_out = array();
	$fn_data_out['cart_checkout'] = array(
		'cart_count' => 0,
		'cart_subtotal' => 0,
		'cart_iva' => 0,
		'cart_iva_percent' => $fn_iva,
		'cart_peso' => 0,
	);
	$fn_data_out['cart_wiva_checkout'] = array(
		'cart_subtotal' => 0,
		'cart_iva' => 0,
	);
	
	$fn_loc_type_shipping = (isset($fn_data_out['cart_checkout']) && isset($fn_data_out['cart_checkout']['cart_shipping_type'])) ? $fn_data_out['cart_checkout']['cart_shipping_type'] : 0;
	
	//user
	$fn_nodir = true;
	$fn_noshipping = true;
	$fn_dir_data = array();
	$fn_shipping_data = array();
	$fn_oferta_product = 0;
	
	if($too_login->isLogged() == 200)
	{
		$fn_login_user_data = $too_login->getUserData();
		
		$fn_user_meta = $db->FetchValue("
			SELECT `meta_value`
			FROM `users_meta`
			WHERE `user_id`=:uid
			AND `meta_key`='user_dirs'
			LIMIT 1;
		", array(
			'uid' => $fn_login_user_data->ID,
		));
		
		//check default dir
		if($fn_user_meta)
		{
			$fn_d = (!empty($fn_user_meta) && isJson($fn_user_meta)) ? object_to_array(json_decode($fn_user_meta)) : false;
			
			if($fn_d) foreach($fn_d as $mk => $mv)
			{
				if(isset($mv['dir_default']) && $mv['dir_default'] == 1)
				{
					$fn_nodir = false;
					$fn_dir_data = $mv;
					break;
				}
			}
		}
	}
	//user
	
	//check shipping
	$fn_q_shipping = $db->FetchAll("
		SELECT *
		FROM `shipping_companies`
		WHERE `active`='1'
	");
	
	if(count($fn_q_shipping) !== 0) $fn_noshipping = false;
	//check shipping
	
	/*
		//var_dump($fn_dir_data);
		
		array (size=9)
		  'dir_name' => string 'espa&ntilde;a' (length=13)
		  'dir_primary' => string '' (length=0)
		  'dir_secundary' => string '' (length=0)
		  'dir_city' => string '' (length=0)
		  'dir_region' => string '' (length=0)
		  'dir_post' => string '' (length=0)
		  'dir_country' => string 'es' (length=2)
		  'id' => string '0' (length=1)
		  'dir_default' => int 1
	*/
	
	foreach($fn_array['cart'] as $k => $v)
	{
		$fn_q = $db->FetchArray("
			SELECT p.`lang_data`, p.`gallery_id`, p.`hash`, s.`precio_venta`, s.`stock_count`, s.`peso`
			FROM `product` p
			LEFT JOIN `product_stock` s ON(s.`prid`=p.`id`)
			WHERE p.`id`=:id
			LIMIT 1;
		", array(
			'id' => $v['p_id']
		));
		
		$fn_prod_title = (isset($fn_q['lang_data']) && isJson($fn_q['lang_data'])) ? object_to_array(json_decode($fn_q['lang_data'])) : array();
		
		$fn_array['cart'][$k]['hash'] = $fn_q['hash'];
		$fn_array['cart'][$k]['title'] = $fn_prod_title[$st_lang];
		$fn_array['cart'][$k]['stock_count'] = $fn_q['stock_count'];
		$fn_array['cart'][$k]['thumb'] = getThumbFromGallery($fn_q['gallery_id']);
		
		//oferta solo un item
		if(isset($fn_array['promote']) && $fn_array['promote']['id'] == $v['p_id'] && $fn_oferta_product == 0)
		{
			//off
			if(isset($fn_array['cart'][$k]['pax']) && $fn_array['cart'][$k]['pax'] !== 0)
			{
				if($fn_array['promote']['used'] <= $fn_array['promote']['max'])
				{
					//oferta sobre 1 producto
					$fn_loc_of = round(($fn_q['precio_venta'] * $fn_array['promote']['oferta_value'] / 100), 2);
					$fn_loc_calc_total_price_per_pax = round(($fn_q['precio_venta'] * $fn_array['cart'][$k]['pax']), 2);
					
					//restamos descuento de un solo producto del total
					$fn_price_loc = round(($fn_loc_calc_total_price_per_pax - $fn_loc_of), 2);
					
					$fn_oferta_product++;
				}else{
					$fn_price_loc = round(($fn_q['precio_venta'] * $fn_array['cart'][$k]['pax']), 2);
				}
			}else{
				$fn_price_loc =  $fn_q['precio_venta'];
			}
		}else{
			//sin oferta
			$fn_array['cart'][$k]['price'] = (isset($fn_array['cart'][$k]['pax']) && $fn_array['cart'][$k]['pax'] !== 0) ? round(($fn_q['precio_venta'] * $fn_array['cart'][$k]['pax']), 2) : $fn_q['precio_venta'];
		}
		
		$fn_data_out['cart'][] = $fn_array['cart'][$k];
		
		//calculos
		$fn_data_out['cart_checkout']['cart_subtotal'] += $fn_array['cart'][$k]['price'];
		
		//peso
		if(isset($fn_q['peso'])) $fn_data_out['cart_checkout']['cart_peso'] += $fn_q['peso'];
	}
	
	//oferta global
	if(isset($fn_array['promote']) && $fn_array['promote']['id'] == 0 && !isset($_SESSION['promote']['global_userd']))
	{
		if($fn_array['promote']['used'] <= $fn_array['promote']['max'] && $fn_array['promote']['p_id'] == 0)
		{
			//cart_checkout
			$fn_sub_of = round(($fn_data_out['cart_checkout']['cart_subtotal'] * $fn_array['promote']['oferta_value'] / 100), 2);
			//subtotal
			$fn_data_out['cart_checkout']['cart_subtotal'] = round(($fn_data_out['cart_checkout']['cart_subtotal'] - $fn_sub_of), 2);
			
			//iva del sobtotal
			$fn_data_out['cart_checkout']['cart_iva'] = round(($fn_data_out['cart_checkout']['cart_subtotal'] * $fn_data_out['cart_checkout']['cart_iva_percent'] / 100), 2);
			
			//dejamos una marca de que ya esta aplicada la oferta general
			$_SESSION['promote']['global_userd'] = $fn_array['promote']['global_userd'] = 1;
		}
	}
	
	//envio
	//primero hay que mirar que compañia ha seleccionado el cliente
	/*
		var_dump($_SESSION);
	
		array (size=3)
		  'lang' => string 'es' (length=2)
		  'glunt_login_session' => 
		    object(stdClass)[1]
		      public 'ID' => string '1' (length=1)
		      public 'user_name' => string '211' (length=3)
		      public 'user_email' => string 'admin@211.com' (length=13)
		      public 'user_status' => string '1' (length=1)
		      public 'user_add_date' => string '2017-02-25 17:22:14' (length=19)
		      public 'status_value' => string 'Activo' (length=6)
		      public 'user_level' => string '100' (length=3)
		      public 'time_stamp' => int 1488800019
		  'cart' => 
		    array (size=2)
		      0 => 
		        array (size=3)
		          'cat_id' => string '1' (length=1)
		          'p_id' => string '4' (length=1)
		          'pax' => string '1' (length=1)
		      1 => 
		        array (size=3)
		          'cat_id' => string '1' (length=1)
		          'p_id' => string '7' (length=1)
		          'pax' => string '1' (length=1)
    */
	
	//cart without iva
	//cart_iva en este punto es 0 se calcula por primera vez
	$fn_data_out['cart_wiva_checkout'] = array(
		'cart_subtotal' => round(($fn_data_out['cart_checkout']['cart_subtotal'] + $fn_data_out['cart_checkout']['cart_iva']), 2),
		'cart_iva' => round(($fn_data_out['cart_checkout']['cart_subtotal'] * $fn_data_out['cart_checkout']['cart_iva_percent'] / 100), 2),
	);
	
	//tiene dir & hay compañias de envio
	if((!$fn_nodir && !$fn_noshipping))
	{
		$fn_data_out['cart_checkout']['cart_total'] = 0;
		$fn_data_out['cart_checkout']['cart_shipping_type'] = 0;
		$fn_data_out['cart_checkout']['cart_shipping_cost'] = 0;
		
		//cliente no ha seleccionado envio calculamos con la primera opcion
		$fn_get_tid = false;
		
		$fn_q_shipping = $db->FetchAll("
		  SELECT *
		  FROM `shipping_companies`
		  WHERE `active`='1'
		  LIMIT 1;
		");
		
		if($fn_q_shipping)
		{
			$fn_q_tid = $db->FetchAll("
				SELECT t.`id`
				FROM `shipping_types` t
				LEFT JOIN `shipping_types_rel` r ON(r.`t_id`=t.`id`)
				WHERE r.`s_id`=:sid
			", array(
				'sid' => $fn_q_shipping[0]->id
			));
			
			if($fn_q_tid) foreach($fn_q_tid as $tk => $tv)
			{
				$fn_tid = (!empty($fn_array['cart_checkout']['cart_shipping_type']) && $fn_array['cart_checkout']['cart_shipping_type'] !== 0) ? $fn_array['cart_checkout']['cart_shipping_type'] : $tv->id;
				
				$fn_peso = $fn_data_out['cart_checkout']['cart_peso'];
				
				//asignamos el tipo de envio si no esta asignado
				$fn_data_out['cart_checkout']['cart_shipping_type'] = $fn_tid;
				
				//cliente habia seleccionado antes una opcion de envio
				$fn_get_tarifa = $db->FetchArray("
					SELECT t.*, c.`country_name`, c.`country_code`
					FROM `shipping_tarifas` t
					LEFT JOIN `apps_countries` c ON(c.`id`=t.`c_id`)
					WHERE c.`country_code`=UPPER('{$fn_dir_data['dir_country']}')
					AND t.`s_id`='{$fn_q_shipping[0]->id}'
					AND t.`t_id`='{$fn_tid}'
					AND t.`kg`>='{$fn_peso}'
					LIMIT 1;
				");
				
				if($fn_get_tarifa) $fn_shipping_data[] = $fn_get_tarifa;
			}
		}
		
		if(count($fn_shipping_data) !== 0)
		{
			$fn_data_out['cart_checkout']['cart_shipping_type'] = $fn_shipping_data[0]['t_id'];
			
			$fn_ship_iva = round(($fn_shipping_data[0]['precio'] * $fn_data_out['cart_checkout']['cart_iva_percent'] / 100), 2);
			$fn_data_out['cart_checkout']['cart_shipping_cost'] = round(($fn_shipping_data[0]['precio'] - $fn_ship_iva), 2);
			
			$fn_data_out['cart_checkout']['cart_count'] = getCartCount($_SESSION['cart']);
			
			$fn_cart_iva = round(($fn_data_out['cart_checkout']['cart_subtotal'] * $fn_data_out['cart_checkout']['cart_iva_percent'] / 100), 2);
			$fn_data_out['cart_checkout']['cart_iva'] = round(($fn_cart_iva + $fn_ship_iva), 2);  //productos + envio
			
			$fn_calc_subtotal = round(($fn_data_out['cart_checkout']['cart_subtotal'] - $fn_cart_iva), 2); //solo productos sin iva
			$fn_data_out['cart_checkout']['cart_subtotal'] = $fn_calc_subtotal;
			
			$fn_data_out['cart_checkout']['cart_total'] = round(($fn_calc_subtotal + $fn_data_out['cart_checkout']['cart_iva'] + $fn_data_out['cart_checkout']['cart_shipping_cost']), 2); //se suman envio + iva + subtotal
		}else{
			//no hay envio ni dir
			$fn_data_out['cart_checkout']['cart_count'] = getCartCount($_SESSION['cart']);
			$fn_data_out['cart_checkout']['cart_iva'] = round(($fn_data_out['cart_checkout']['cart_subtotal'] * $fn_data_out['cart_checkout']['cart_iva_percent'] / 100), 2);
			$fn_data_out['cart_checkout']['cart_subtotal'] = round(($fn_data_out['cart_checkout']['cart_subtotal'] + $fn_data_out['cart_checkout']['cart_iva']), 2);
		}
	}else{
		//no hay envio ni dir
		$fn_data_out['cart_checkout']['cart_count'] = getCartCount($_SESSION['cart']);
		$fn_data_out['cart_checkout']['cart_iva'] = round(($fn_data_out['cart_checkout']['cart_subtotal'] * $fn_data_out['cart_checkout']['cart_iva_percent'] / 100), 2);
		$fn_data_out['cart_checkout']['cart_subtotal'] = round(($fn_data_out['cart_checkout']['cart_subtotal'] + $fn_data_out['cart_checkout']['cart_iva']), 2);
	}
	//envio
	
	if(isset($_SESSION['cart'])) $_SESSION['cart'] = $fn_array['cart'];
	
	$_SESSION['cart_wiva_checkout'] = $fn_data_out['cart_wiva_checkout'];
	
	//write all to session
	$_SESSION['cart_checkout'] = $fn_data_out['cart_checkout'];
	
	//mantenemos shipping seleccionado
	$_SESSION['cart_checkout']['cart_shipping_type'] = $fn_loc_type_shipping;
	
	$fn_data_out['lang'] = array(
		'lang_iva' => $lang_items[$st_lang]['lang_iva'],
		'lang_no_iva' => $lang_items[$st_lang]['lang_no_iva'],
		'lang_envio' => $lang_items[$st_lang]['lang_envio'],
		'cart_shipping_included' => $lang_items[$st_lang]['cart_shipping_included'],
	);
	
	return $fn_data_out;
}

/**
 * sendInvioceAndAdminMail function.
 * 
 * @access public
 * @param mixed $fn_user_mail //email del usuario
 * @param mixed $fn_order_num //numero del pedido
 * @param mixed $fn_order_html //html createCartCheckoutHtml()
 * @return void
 */
function sendInvioce($fn_user_mail, $fn_order_num, $fn_order_html)
{
	global $st_lang, $CONFIG, $lang_items;
	
	//html y content del mail
	$fn_mail_html = $CONFIG['templates']['standartEmail'];
	
	//------- mail cliente + resumen
	
	//mails
	$fn_subject = "[{$lang_items[$st_lang]['mail_order_confirm']}] - {$CONFIG['site']['sitetitlefull']}";
	
	$fn_message = $lang_items[$st_lang]['mail_order_confirm_text'];
	$fn_message .= createCartCheckoutHtml($fn_order_html);
	
	$fn_message = str_replace('%ref_number%', $fn_order_num, $fn_message);
	
	$fn_mail_html = str_replace(array(
		'%message%',
		'%regards%', 
		'%site_name%', 
		'%copyz%',
		'%site_dir%', 
		'%site_logo%', 
	), array(
		$fn_message,
		$lang_items[$st_lang]['regards'],
		$CONFIG['site']['sitetitlefull'],
		$CONFIG['site']['sitecopyz'],
		'',
		'<img src="'.$CONFIG['site']['base'].'m/logo.png?e='.urlencode($fn_user_mail).'" alt="logotype" />',
	), $fn_mail_html);
	
	$fn_content = preparehtmlmailBase64($CONFIG['site']['mailinfo'], $fn_mail_html);
	
	//------ mail admin aviso de compra
	
	//envio del mail
	$fn_send_user = @mail($fn_user_mail, $fn_subject, $fn_content['multipart'], $fn_content['headers']);
	
	/*
	if($CONFIG['status']['debug'])
	{
		var_dump($fn_send_user);
		var_dump($fn_user_mail);
		var_dump($fn_subject);
		var_dump($fn_mail_html);
		var_dump($fn_content['multipart']);
		var_dump($fn_content['headers']);
	}
	*/
}

/**
 * sendAdminNotice function.
 * 
 * @access public
 * @param mixed $fn_order_num
 * @return void
 */
function sendAdminNotice($fn_order_num)
{
	global $st_lang, $CONFIG, $lang_items;
	
	//html y content del mail
	$fn_mail_html = $CONFIG['templates']['standartEmail'];
	
	//email de aviso al administrador
	$fn_subject = "[Nueva compra] - {$fn_order_num} - {$CONFIG['site']['sitetitlefull']}";
	
	$fn_mail_html = str_replace(array(
		'%message%',
		'%regards%', 
		'%site_name%', 
		'%copyz%',
		'%site_dir%', 
		'%site_logo%', 
	), array(
		'<p>Aviso de una nueva compra con siguiente referencia: <strong>'.$fn_order_num.'</strong></p><p>Lo puede ver accediendo al panel de control en el apartado (<strong>Pedidos</strong>).</p>',
		$lang_items[$st_lang]['regards'],
		$CONFIG['site']['sitetitlefull'],
		$CONFIG['site']['sitecopyz'],
		'',
		'<img src="'.$CONFIG['site']['base'].'m/logo.png" alt="logotype" />',
	), $fn_mail_html);
	
	$fn_content = preparehtmlmailBase64($CONFIG['site']['botmail'], $fn_mail_html);
	
	$fn_send_admin = @mail($CONFIG['site']['mailinfo'], $fn_subject, $fn_content['multipart'], $fn_content['headers']);

	/*
	if($CONFIG['status']['debug'])
	{
		var_dump($fn_send_admin);
		var_dump($CONFIG['site']['mailinfo']);
		var_dump($fn_subject);
		var_dump($fn_mail_html);
		var_dump($fn_content['multipart']);
		var_dump($fn_content['headers']);
	}
	*/
}

/**
 * createCartCheckoutHtml function.
 * 
 * generamos html para el mail de checkout (parte cliente)
 * @access public
 * @param mixed $fn_cart_array //$_SESSION['cart']
 * @return void
 */
function createCartCheckoutHtml($fn_cart_array)
{
	global $lang_items, $st_lang, $db;
	
	//gen order html
	$fn_order_html = '<table width="100%" style="display:block; width:100%"><thead><tr style="width: 50%"><th>'.strtoupper($lang_items[$st_lang]['lang_cart_producto_title']).'</th><th style="width: 30%">'.strtoupper($lang_items[$st_lang]['lang_cart_cantidad_title']).'</th><th style="width: 20%">TOTAL</th></tr></thead><tbody>';
	
	if(count($fn_cart_array) !== 0) foreach($fn_cart_array['cart'] as $ck => $cv)
	{
		$fn_order_html .= '<tr><td style="text-transform: uppercase; style="width: 50%"">'.$cv['title'].'</td><td style="text-transform: uppercase; style="width: 30%"">'.$cv['pax'].'</td><td style="text-transform: uppercase; style="width: 20%"">'.$cv['price'].'</td></tr>';
		
		//oferta
		if(isset($fn_cart_array['promote']) && $fn_cart_array['promote']['p_id'] == $cv['p_id']) $fn_order_html .= '<tr><td colspan="3" style="text-transform: uppercase;" >'.$lang_items[$st_lang]['cart_item_apply_promocode'].' ('.$fn_cart_array['promote']['oferta_value'].'%)</td><td>'.$lang_items[$st_lang]['lang_cart_promote_code'].' (<i>'.$fn_cart_array['promote']['code'].'</i>)</td></tr>';
	}
	
	$fn_calc_total = $fn_cart_array['cart_wiva_checkout']['cart_subtotal'];
	$fn_calc_subtotal = round($fn_calc_total - $fn_cart_array['cart_wiva_checkout']['cart_iva'] , 2);
	
	$fn_oferta_general = '';
	
	if(isset($fn_cart_array['promote']) && $fn_cart_array['promote']['p_id'] == 0) $fn_oferta_general = '<strong>'.$lang_items[$st_lang]['cart_descuento'].':</strong> '.$fn_cart_array['promote']['oferta_value'].'% (<i>'.$fn_cart_array['promote']['code'].'</i>)<br/>';
	
	$fn_order_html .= '<tr><td></td><td></td><td></td></tr><tr><td>'.$fn_oferta_general.'<strong>'.$lang_items[$st_lang]['lang_iva'].' ('.$fn_cart_array['cart_checkout']['cart_iva_percent'].'%):</strong> '.$fn_cart_array['cart_wiva_checkout']['cart_iva'].'&euro;<br/><strong>Subtotal:</strong> '.$fn_calc_subtotal.'&euro;<br/><strong>'.$lang_items[$st_lang]['lang_envio'].':</strong> '.$lang_items[$st_lang]['cart_shipping_included'].'<br/><strong>Total:</strong> '.$fn_calc_total.'&euro;<br/></td><td></td><td></td></tr></tbody></table>';
	
	return $fn_order_html;
}

/**
 * getCartCount function.
 * Devuelve tamaño de carrito siempre int
 * 
 * @access public
 * @return void
 */
function getCartCount()
{
	$fn_size_of_cart = 0;
			
	if(isset($_SESSION) && isset($_SESSION['cart']) && sizeof($_SESSION['cart']) !== 0)
	{
		foreach($_SESSION['cart'] as $kk => $kv)
		{
			if(isset($kv['pax'])) $fn_size_of_cart += $kv['pax'];
		}
	}else{
		unset($_SESSION['cart']);
		unset($_SESSION['cart_checkout']);
	}
	
	return intval($fn_size_of_cart);
}

/**
 * shopium_assignCheckoutId function.
 * genera checkout_id si no existe 
 *
 *
 
	$_SESSION
	
	array (size=5)
	  'lang' => string 'es' (length=2)
	  'cart_wiva_checkout' => 
	    array (size=2)
	      'cart_subtotal' => float 100
	      'cart_iva' => float 21
	  'cart_checkout' => 
	    array (size=8)
	      'cart_count' => int 3
	      'cart_subtotal' => float 79
	      'cart_iva' => float 24.69
	      'cart_iva_percent' => string '21' (length=2)
	      'cart_peso' => float 0.3
	      'cart_total' => float 117.56
	      'cart_shipping_type' => string '9' (length=1)
	      'cart_shipping_cost' => float 13.87
 
 * @access public
 * @return void
 */
function assignCheckoutId()
{
	//global $db, $CONFIG;
	
	$fn_checkout_id = date('ymd')."0".substr(time(), 6, 4);
	
	/*
	if(!isset($_SESSION)) @session_start();
	
	if(isset($_SESSION) && isset($_SESSION['cart_checkout']) && isset($_SESSION['cart_checkout']['checkout_id']))
	{
		$db->Fetch("
			UPDATE `orders`
			SET `order_id`='{$fn_checkout_id}'
			WHERE `order_id`='{$_SESSION['cart_checkout']['checkout_id']}'
		");
		
		//aqui puede haber un problema
		//deberia comprobar si existe si si update si no false y mostrar que hay un problema que lo intente otra vez
	}
	*/
	
	return $fn_checkout_id;
}

/**
 * checkLanguagesArray function.
 * comprueba si hay un idioma nuevo y lo añade
 *
 * @access public
 * @param mixed $fn_array
 * @return void
 */
function checkLanguagesArray($fn_string)
{
	global $CONFIG;
	
	if(!preg_match('/(object|array)/', gettype($fn_string))) exit(json_encode(array(
		'status' => 400,
		'message' => "[S:1044]", //no es array
	)));
	
	//config languages
	$fn_langs_active = (isset($CONFIG['site']['lang']) && isJson($CONFIG['site']['lang'])) ? json_decode($CONFIG['site']['lang'], true) : false;
	
	if($fn_langs_active == false) return false;
			
	foreach($fn_langs_active as $l)
	{
		if(array_key_exists($l, $fn_string)) continue;
		
		$fn_string[$l] = '';
	}
	
	return $fn_string;
}

/**
 * getLangItem function.
 * devuelve idioma segun $st_lang si no existe devolvera segun el default
 *
 * @access public
 * @param mixed $fn_item
 * @return void
 */
function getLangItem($fn_item)
{
	global $st_lang, $CONFIG, $lang_items;
	
	$fn_def_lang = (isset($lang_items[$CONFIG['site']['defaultLang']][$fn_item])) ? $lang_items[$CONFIG['site']['defaultLang']][$fn_item] : null;
	$fn_return = (isset($lang_items[$st_lang][$fn_item])) ? $lang_items[$st_lang][$fn_item] : $fn_def_lang;
	
	return (isset($fn_return) || !empty($fn_return) || !is_null($fn_return)) ? $fn_return : null;
}

/**
 * menu_proccess_object function.
 * 
 * @access public
 * @param mixed $fn_object
 * @return void
 */
function menu_proccess_object($array)
{
	$array = object_to_array($array);
	$tree = array();
	
	// Create an associative array with each key being the ID of the item
	foreach($array as $k => &$v) $tree[$v['id']] = &$v;
	
	// Loop over the array and add each child to their parent
	foreach($tree as $k => &$v)
	{
		if(!$v['m_parent']) continue;
		$tree[$v['m_parent']]['sublevel'][] = &$v;
	}
	
	// Loop over the array again and remove any items that don't have a parent of 0;
	foreach($tree as $k => &$v) 
	{
		if(!$v['m_parent']) continue;
		unset($tree[$k]);
	}
	
	return array_values($tree);
}

/**
 * decodeLangData function.
 * devuelve lang item del lang_data con idioma de ahora o array competo
 *
 * @access public
 * @param mixed $fn_json
 * @param bool $fn_show_current_lang (default: true)
 * @return void
 */
function decodeLangData($fn_json, $fn_show_current_lang = true)
{
	global $st_lang;
	
	$fn_title = (isset($fn_json) && isJson($fn_json)) ? object_to_array(json_decode($fn_json)) : '';
	
	return ($fn_show_current_lang) ? $fn_title[$st_lang] : $fn_title;
}

?>