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
	$fn_data_out = array(
		"checkout" => array(
			'cart_count' => 0,
			'cart_subtotal' => 0,
			'cart_total' => 0,
			'cart_iva' => 0,
			'cart_iva_percent' => $fn_iva,
			'cart_peso' => 0,
			'cart_shipping_type' => 0,
		),
	);
	
	//complete cart data
	foreach($fn_array['cart'] as $k => $v)
	{
		$fn_q = $db->FetchArray("
			SELECT p.`id`, p.`cat_id`, p.`lang_data`, p.`subtitle_lang_data`, p.`gallery_id`, p.`hash`, s.`precio_venta`, s.`stock_count`, s.`peso`, s.`pax_multimplier` 
			FROM `product` p
			LEFT JOIN `product_stock` s ON(s.`prid`=p.`id`)
			WHERE p.`id`=:id
			LIMIT 1;
		", array(
			'id' => $v['p_id']
		));
		
		$fn_get_meta = $db->FetchAll("
			SELECT * 
			FROM `product_meta`
			WHERE `p_id`=:pid
		", array(
			'pid' => $fn_q['id']
		));
		
		$fn_metas = array();
		
		if($fn_get_meta) foreach($fn_get_meta as $kk => $vv)
		{
			$fn_metas[$vv->m_key] = $vv->m_value;
		}
		
		$fn_prod_title = (isset($fn_q['lang_data']) && isJson($fn_q['lang_data'])) ? object_to_array(json_decode($fn_q['lang_data'])) : array();
		$fn_prod_subtitle = (isset($fn_q['subtitle_lang_data']) && isJson($fn_q['subtitle_lang_data'])) ? object_to_array(json_decode($fn_q['subtitle_lang_data'])) : array();
		
		$fn_array['cart'][$k]['cat_id'] = $fn_q['cat_id'];
		$fn_array['cart'][$k]['p_id'] = $fn_q['id'];
		
		$fn_array['cart'][$k]['by_box'] = (isset($fn_metas['by_box']) && $fn_metas['by_box'] == "1") ? 1 : 0;
		$fn_array['cart'][$k]['by_pax'] = (isset($fn_metas['by_pax']) && $fn_metas['by_pax'] == "1") ? 1 : 0;
		
		$fn_array['cart'][$k]['hash'] = $fn_q['hash'];
		$fn_array['cart'][$k]['title'] = html_entity_decode($fn_prod_title[$st_lang]);
		$fn_array['cart'][$k]['subtitle'] = html_entity_decode($fn_prod_subtitle[$st_lang]);
		$fn_array['cart'][$k]['stock_count'] = $fn_q['stock_count'];
		$fn_array['cart'][$k]['thumb'] = getThumbFromGallery($fn_q['gallery_id']);
		$fn_array['cart'][$k]['pax_multimplier'] = $fn_q['pax_multimplier'];
		
		$fn_array['cart'][$k]['price_caja'] = round($fn_q['pax_multimplier'] * $fn_q['precio_venta'] , 2);
		$fn_array['cart'][$k]['price_unit'] = $fn_q['precio_venta'];
		$fn_array['cart'][$k]['price_unit_total'] = (isset($fn_array['cart'][$k]['pax']) && $fn_array['cart'][$k]['pax'] !== 0) ? round(($fn_q['precio_venta'] * $fn_array['cart'][$k]['pax']), 2) : $fn_q['precio_venta'];
		
		$fn_array['cart'][$k]['price_total'] = $fn_array['cart'][$k]['price_unit_total'];
		$fn_array['cart'][$k]['price_multimplier'] = 0;
		
		//cajas
		if(isset($fn_array['cart'][$k]['multimplier']) && $fn_array['cart'][$k]['multimplier'] !== 0)
		{
			$fn_array['cart'][$k]['price_multimplier'] = round(($fn_array['cart'][$k]['multimplier'] * ($fn_q['pax_multimplier'] * $fn_q['precio_venta'])), 2);
			
			
			$fn_array['cart'][$k]['price_total'] += $fn_array['cart'][$k]['price_multimplier'];
		}
		
		//----------
		
		$fn_data_out['cart'][] = $fn_array['cart'][$k];
		
		//calculos
		$fn_data_out['checkout']['cart_total'] += $fn_array['cart'][$k]['price_total'];
		
		//peso
		if(isset($fn_q['peso'])) $fn_data_out['checkout']['cart_peso'] += $fn_q['peso'];
	}
	
	$fn_data_out['checkout']['cart_count'] = getCartCount($_SESSION['cart']);
	$fn_data_out['checkout']['cart_iva'] = round(($fn_data_out['checkout']['cart_total'] * $fn_data_out['checkout']['cart_iva_percent'] / 100), 2);
	
	//subtotal sin iva
	$fn_data_out['checkout']['cart_subtotal'] = round(($fn_data_out['checkout']['cart_total'] - $fn_data_out['checkout']['cart_iva']), 2);

	
	if(isset($_SESSION['cart'])) $_SESSION['cart'] = $fn_array['cart'];
	
	//write all to session
	$_SESSION['checkout'] = $fn_data_out['checkout'];
	
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
	$fn_mail_html = $CONFIG['site']['standartEmail'];
	
	//------- mail cliente + resumen
	
	//mails
	$fn_s = getLangItem('mail_order_confirm');
	$fn_subject = "{$fn_s} - {$CONFIG['site']['sitetitlefull']}";
	
	$fn_message = getLangItem('mail_order_confirm_text');
	
	$fn_message = str_replace('%ref_number%', $fn_order_num, $fn_message);
	$fn_message = str_replace('%order_details%', $fn_order_html, $fn_message);
	//$fn_message = str_replace('%user_envio_data%', $fn_order_html, $fn_message);
	//$fn_message = str_replace('%user_facturacion_data%', $fn_order_html, $fn_message);
	
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
		'<img src="'.$CONFIG['site']['base'].'images/logo-mail.png?e='.urlencode($fn_user_mail).'" alt="logotype" />',
	), $fn_mail_html);
	
	$fn_content = preparehtmlmailBase64($CONFIG['site']['mailinfo'], $fn_mail_html);
	
	//------ mail admin aviso de compra
	
	//envio del mail
	$fn_send_user = @mail($fn_user_mail, $fn_subject, $fn_content['multipart'], $fn_content['headers']);
}

/**
 * sendAdminNotice function.
 * 
 * @access public
 * @param mixed $fn_order_num
 * @return void
 */
function sendAdminNotice($fn_order_num, $fn_cart_html = false)
{
	global $st_lang, $CONFIG, $lang_items;
	
	//html y content del mail
	$fn_mail_html = $CONFIG['site']['standartEmail'];
	
	//email de aviso al administrador
	$fn_subject = "[Nueva compra] - {$fn_order_num} - {$CONFIG['site']['sitetitlefull']}";
	
	$fn_message = '<p>Aviso de una nueva compra con siguiente referencia: <strong>%ref_number%</strong></p><p>Lo puede ver accediendo al panel de control en el apartado (<strong>Pedidos</strong>).</p><br/><br/><br/><br/><br/><p><h2><strong style="color:#B99219">INFORMACIÓN DE COMPRA:</strong></h2></p>%order_details%<br/><br/><hr/><br/><br/><p><h2><strong style="color:#B99219">DATOS DEL CLIENTE:</strong></h2></p>%user_envio_data%<br/><br/><p><h2><strong style="color:#B99219">DATOS DE FACTURACIÓN:</strong></h2></p>%user_facturacion_data%<br/><br/>';
	
	//------->
	//------->
	//------->
	$fn_message = str_replace('%ref_number%', $fn_order_num, $fn_message);
	$fn_message = str_replace('%order_details%', $fn_order_html, $fn_message);
	//$fn_message = str_replace('%user_envio_data%', $fn_order_html, $fn_message);
	//$fn_message = str_replace('%user_facturacion_data%', $fn_order_html, $fn_message);
	//------->
	//------->
	//------->
	//------->
	
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
		'<img src="'.$CONFIG['site']['base'].'images/logo-mail.png" alt="logotype" />',
	), $fn_mail_html);
	
	$fn_content = preparehtmlmailBase64($CONFIG['site']['botmail'], $fn_mail_html);
	
	$fn_send_admin = @mail($CONFIG['site']['mailpedidos'], $fn_subject, $fn_content['multipart'], $fn_content['headers']);
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
	$fn_order_html = '<table width="100%" style="display:block; width:100%">
		<thead>
			<tr style="width: 50%">
				<th style="text-align:left;">'.strtoupper(getLangItem('lang_cart_producto_title')).'</th>
				<th style="width: 30%">'.strtoupper(getLangItem('lang_cart_cantidad_title')).'</th>
				<th style="width: 30%">'.strtoupper(getLangItem('cart_cant_cajas')).'</th>
				<th style="width: 20%">TOTAL</th>
			</tr>
		</thead><tbody>';
	
	if(count($fn_cart_array) !== 0) foreach($fn_cart_array['cart'] as $ck => $cv)
	{
		$fn_order_html .= '<tr>
			<td style="text-transform: uppercase;width: 50%;text-align:left;">'.$cv['title'].' '.$cv['subtitle'].'</td>
			<td style="text-transform: uppercase;width: 30%;text-align:center;">'.$cv['pax'].'</td>
			<td style="text-transform: uppercase;width: 30%;text-align:center;">'.$cv['multimplier'].'</td>
			<td style="text-transform: uppercase;width: 20%;text-align:right;">'.$cv['price_unit'].'</td>
		</tr>';
	}
	
	$fn_order_html .= '<tr>
			<td colspan="4" style="height:40px;"></td>
		</tr>
		<tr>
			<td colspan="4" style="text-align: right">
				<strong>'.getLangItem('lang_iva').' ('.$fn_cart_array['checkout']['cart_iva_percent'].'%):</strong> '.$fn_cart_array['checkout']['cart_iva'].'&euro;<br/>
				<strong>Subtotal:</strong> '.$fn_cart_array['checkout']['cart_subtotal'].'&euro;<br/>
				<strong>'.getLangItem('lang_envio').':</strong> '.getLangItem('cart_shipping_included').'<br/>
				<strong>Total:</strong> '.$fn_cart_array['checkout']['cart_total'].'&euro;<br/>
			</td>
		</tr>
		</tbody></table>';
	
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
		$fn_size_of_cart = count($_SESSION['cart']);
	}else{
		unset($_SESSION['cart']);
		unset($_SESSION['cart_checkout']);
	}
	
	return intval($fn_size_of_cart);
}

/**
 * genera checkout_id si no existe 
 *
 *
 * @access public
 * @return void
 */
function assignCheckoutId()
{
	//global $db, $CONFIG;
	
	$fn_checkout_id = date('ymd')."0".substr(time(), 6, 4);
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

/**
 * treeArray function.
 * 
 * @access public
 * @param mixed $array
 * @param mixed $fn_by_value
 * @param string $fn_push_by_key (default: 'id')
 * @return void
 */
function treeArray($array, $fn_by_value = 'parent_id', $fn_push_by_key = 'id')
{
	$array = object_to_array($array);
	$tree = array();
	
	// Create an associative array with each key being the ID of the item
	foreach($array as $k => &$v) $tree[$v[$fn_push_by_key]] = &$v;
	
	// Loop over the array and add each child to their parent
	foreach($tree as $k => &$v)
	{
		if(!$v[$fn_by_value]) continue;
		$tree[$v[$fn_by_value]]['parent'][] = &$v;
	}
	
	// Loop over the array again and remove any items that don't have a parent of 0;
	foreach($tree as $k => &$v) 
	{
		if(!$v[$fn_by_value]) continue;
		unset($tree[$k]);
	}
	
	return array_values($tree);
}

?>