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
		
		/* ------------------------------------------------------------------------------------------------ */
		
		//--- ofertas
		case "ofertasManage":
			if(IsHotlink()) exit(json_encode(array(
				'status' => 400,
				'message' => 'Ajax Fraud cached!',
			)));
			
			$u_level = $too_login->isAuth(100, false, $CONFIG['site']['tooSType']);
			
			if($u_level !== 200) exit(json_encode(array(
				'status' => 400,
				'message' => 'No puede hacer esto, no tiene autorización!.',
			)));
		
			if(isset($fn_p['data'])) parse_str($fn_p['data'], $fn_inputs);
			
			switch($fn_p['type'])
			{
				//add
				case "upOferta":
				case "addOferta":
					$fn_active = (isset($fn_inputs['f_active']) && $fn_inputs['f_active'] == 1) ? 1 : 0;
					$fn_active = ($fn_inputs['f_type'] == 'prd' && $fn_inputs['f_product'] == 0) ? 0 : $fn_active;
					
					$fn_tab_active = (isset($fn_inputs['f_tab_active']) && $fn_inputs['f_tab_active'] == 1) ? 1 : 0;
					$fn_tab_sendmail = (isset($fn_inputs['f_tab_sendemail']) && $fn_inputs['f_tab_sendemail'] == 1) ? 1 : 0;
					
					$fn_pid = (isset($fn_inputs['f_type']) && $fn_inputs['f_type'] == 'prd') ? $fn_inputs['f_product'] : 0; //producto o 0 querra decir todos los productos
					
					//desactivamos los tabs anteriores
					if($fn_tab_active)
					{
						$db->Fetch("
							UPDATE `ofertas`
							SET `tab_active`='0'
						");
					}
					
					if($fn_p['type'] == 'addOferta')
					{
						//add
						$fn_q = $db->ExecuteSQL("
							INSERT INTO `ofertas` (`active`, `p_id`, `title`, `oferta_value`, `desc`, `code`, `max`, `tab_active`, `tab_sendemail`)
							VALUES (:ac, :pid, :tl, :pr, :dsc, :fc, :fmax, :tba, :tbs);
						", array(
							'ac' => $fn_active,
							'pid' => $fn_pid,
							'tl' => $fn_inputs['f_title'],
							'pr' => $fn_inputs['f_percent'],
							'dsc' => $fn_inputs['f_desc'],
							'fc' => $fn_inputs['f_code'],
							'fmax' => $fn_inputs['f_max'],
							'tba' => $fn_tab_active,
							'tbs' => $fn_tab_sendmail,
						));
					}else{
						if(!isset($fn_inputs['id'])) exit(json_encode(array(
							'status' => 400,
							'message' => 'Faltan id',
						)));
						
						//update
						$fn_q = $db->Fetch("
							UPDATE `ofertas`
							SET `active`=:ac, `p_id`=:pid, `title`=:tl, `oferta_value`=:ov, `desc`=:dsc, `code`=:code, `max`=:max, `tab_active`=:tac, `tab_sendemail`=:tsdm
							WHERE `id`='{$fn_inputs['id']}';
						", array(
							'ac' => $fn_active,
							'pid' => $fn_pid,
							'tl' => $fn_inputs['f_title'],
							'ov' => $fn_inputs['f_percent'],
							'dsc' => $fn_inputs['f_desc'],
							'code' => $fn_inputs['f_code'],
							'max' => $fn_inputs['f_max'],
							'tac' => $fn_tab_active,
							'tsdm' => $fn_tab_sendmail,
						));
					}
					
					if($fn_q)
					{
						exit(json_encode(array(
							'status' => 200,
							'message' => 'Okey',
						)));
					}else{
						exit(json_encode(array(
							'status' => 400,
							'message' => 'Error en base de datos',
						)));
					}
				break;
				
				case "delOferta":
					if(!isset($fn_p['id'])) exit(json_encode(array(
						'status' => 400,
						'message' => 'Faltan id',
					)));
					
					$fn_q = $db->Fetch("
						DELETE FROM `ofertas`
						WHERE `id`=:id
						LIMIT 1;
					", array(
						'id' => $fn_p['id'],
					));
					
					if($fn_q)
					{
						exit(json_encode(array(
							'status' => 200,
							'message' => 'Oferta eliminada',
						)));
					}else{
						exit(json_encode(array(
							'status' => 400,
							'message' => 'Error en base de datos',
						)));
					}
				break;
				
				case "getOfertaEdit":
					if(!isset($fn_p['id'])) exit(json_encode(array(
						'status' => 400,
						'message' => 'Faltan id',
					)));
					
					$fn_q = $db->FetchArray("
						SELECT *
						FROM `ofertas`
						WHERE `id`=:id
						LIMIT 1;
					", array(
						'id' => $fn_p['id'],
					));
					
					if($fn_q)
					{
						//select productos data
						$fn_modal_q = $db->FetchAll("
							SELECT *
							FROM `product`
							ORDER BY `order` ASC
						");
						
						$fn_sel_array = array();
						
						if($fn_modal_q) foreach($fn_modal_q as $mk => $mv)
						{
							$fn_foritem_data = object_to_array($mv);
							$fn_foritem_data['item_title'] = langTitleJsonToStringJointer($mv->lang_data);
							$fn_sel_array[] = $fn_foritem_data;
						}
						
						$fn_q['select_products'] = $fn_sel_array;
						
						exit(json_encode(array(
							'status' => 200,
							'message' => 'Ofera',
							'data' => $fn_q,
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
				VALUES (:id, :ln, :rid);
			", array(
				'id' => $fn_p['id'],
				'ln' => $fn_rel_page['lang'],
				'rid' => $fn_p['rid'],
			));
			
			if($fn_q)
			{
				if(isset($fn_p['data'])) parse_str($fn_p['data'], $fn_inputs);
				
				//traduccion viceversa
				if(isset($fn_p['data']) && isset($fn_inputs['vice']))
				{
					$fn_q_page_lang = $db->FetchValue("
						SELECT `lang`
						FROM `pages`
						WHERE `id`=:i
						LIMIT 1;
					", array(
						'i' => $fn_p['id'],
					));
					
					 $fn_q = $db->ExecuteSQL("
						INSERT INTO `pages_lang_rel` (`page_id`, `lang_type`, `page_translate_id`)
						VALUES (:id, :ln, :rid);
					", array(
						'id' => $fn_p['rid'],
						'ln' => $fn_q_page_lang,
						'rid' => $fn_p['id'],
					));
				}
				
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
						$fn_new_input->obj_title = $fn_new_input->obj_hash = "Visita-".date('Ymd-his');
						
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
				'lv' => (isset($fn_p['v'])) ? htmlize($fn_p['v']) : '',
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
		case "upCliente":
		case "delCliente":
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
				case "addCliente":
					//f_page_name
					//f_hash
					
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
					
					if(class_exists("tooSCrypt")) $fn_pass = tooSCrypt::en($fn_p['f_user_pass'], $CONFIG['site']['tooSHash']);
					$fn_pass = hash_hmac('sha512', "{$fn_p['f_user_name']}~{$fn_pass}", $CONFIG['site']['tooSHash'], false);
					
					$fn_q = $db->ExecuteSQL("
						INSERT INTO `users` (`user_name`, `user_pass`, `user_email`, `user_registred`, `user_status`, `user_activation_key`)
						VALUES (:un, :up, :ue, :rg, '1', SHA2(:nw, 512));
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
							'lvl' => '15',
						));
						
						$db->Fetch("
							INSERT INTO `users_meta` (`user_id`, `meta_key`, `meta_value`)
							VALUES (:uid, 'user_access', :val);
						", array(
							'uid' => $fn_q,
							'val' => (isset($fn_p['f_user_access'])) ? $fn_p['f_user_access'] : '0',
						));
						
						$db->Fetch("
							INSERT INTO `users_meta` (`user_id`, `meta_key`, `meta_value`)
							VALUES (:uid, 'user_or', :val);
						", array(
							'uid' => $fn_q,
							'val' => (isset($fn_p['f_user_or'])) ? $fn_p['f_user_or'] : '0',
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
						$fn_mail_html = $CONFIG['templates']['standartEmail'];
						
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
							"<img src=\"{$CONFIG['site']['base']}/m/logo.png?e={$fn_p['f_email']}\" alt=\"logotype\" />",
							"<a href=\"{$CONFIG['site']['base']}{$fn_def_lang}/mi-cuenta\">{$CONFIG['site']['base']}{$fn_def_lang}/mi-cuenta</a>", //link
							$fn_p['f_user_name'],
							$fn_p['f_user_pass'],
						), $fn_mail_html);
						
						$fn_content = preparehtmlmailBase64($CONFIG['site']['botmail'], $fn_mail_html);
						
						//envio del mail
						@mail($fn_to, $fn_subject, $fn_content['multipart'], $fn_content['headers']);
					}
					
					$fn_data['id'] = $fn_q;
				break;
				
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
						if(preg_match('/(pass|activ|key)/', $ik)) continue;
						
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
				break;
				
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
					
					//get user name;
					$fn_user_name = $db->FetchValue("
						SELECT `user_name`
						FROM `users`
						WHERE `ID`=:id
						LIMIT 1;
					", array(
						'id' => $fn_p['id'],
					));
					
					if(class_exists("tooSCrypt")) $fn_pass = tooSCrypt::en($fn_inputs['f_user_pass'], $CONFIG['site']['tooSHash']);
					$fn_pass = hash_hmac('sha512', "{$fn_user_name}~{$fn_pass}", $CONFIG['site']['tooSHash'], false);
					
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
						$fn_mail_html = $CONFIG['templates']['standartEmail'];
						
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
							"<img src=\"{$CONFIG['site']['base']}/m/logo.png?e={$fn_inputs['f_email']}\" alt=\"logotype\" />",
							"<a href=\"{$CONFIG['site']['base']}{$fn_def_lang}/mi-cuenta\">{$CONFIG['site']['base']}{$fn_def_lang}/mi-cuenta</a>", //link
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
	
			//product
			$fn_active = (isset($fn_p['active'])) ? 1 : 0;
			
			$fn_q = $db->Fetch("
				UPDATE `product`
				SET `lang_data`=:ld, `active`=:act, `menu_title`=:mtl, `hash`=:hash
				WHERE `id`=:pid
			", array(
				'ld' => $fn_title_lang_data,
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
							SET `size_id`=:szid, `color_id`=:cid, `precio_coste`=:pc, `precio_venta`=:pv, `stock_min`=:sm, `stock_base`=:sb, `stock_count`=:sc {$fn_args_in}, `precio_tachado`=:ptch
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
				
				//stock table
				$fn_stock = $db->ExecuteSQL("
					INSERT INTO `product_stock` (`prid`, `size_id`, `color_id`, `precio_coste`, `precio_venta`, `stock_min`, `stock_base`, `stock_count`, `peso`, `size_y`, `size_x`, `item_base`)
					VALUES (:pid, :szid, :cid, :pr, :pv, :sm, :sb, :sc, :fp, :sy, :sx, '1');
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
		
		//get all colors sizes
		case "getColorSizes":
			if(IsHotlink()) exit(json_encode(array(
				'status' => 400,
				'message' => 'Ajax Fraud cached!',
			)));
			
			$u_level = $too_login->isAuth(100, false, $CONFIG['site']['tooSType']);
			
			if($u_level !== 200) exit(json_encode(array(
				'status' => 400,
				'message' => 'No puede hacer esto, no tiene autorización!.',
			)));
			
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
			
			//idiomas para los fields
			$fn_array_out['lang'] = (isset($CONFIG['site']['lang']) && isJson($CONFIG['site']['lang'])) ?  json_decode($CONFIG['site']['lang']) : array($CONFIG['site']['defaultLang']);
			
			if(sizeof($fn_array_out) !== 0)
			{
				exit(json_encode(array(
					'status' => 200,
					'message' => 'Ya lo tengo',
					'data' => $fn_array_out,
				)));
			}else{
				exit(json_encode(array(
					'status' => 400,
					'message' => 'No he podido crear la lista',
				)));
			}
		break;
		
		//add/update color size
		case "ctUpColorSize":
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
			
			parse_str($fn_p['data'], $fn_inputs);
			
			$fn_data_out = array();
			$fn_db = ($fn_inputs['f_ctAdmType'] == 'color') ? 'product_color' : 'product_size';
			$fn_lang_parse = array();
			$fn_create_or_update = true;
			
			//titulo
			$fn_title_lang_data = array();
			
			foreach($fn_inputs['f_lang_data'] as $tk => $tv)
			{
				$fn_title_lang_data[] = htmlentities($tv);
				$fn_lang_parse[$tk] = htmlentities($tv);
			}
			$fn_title_lang_data = implode(' | ', $fn_title_lang_data);
			
			$fn_lang_parse = json_encode($fn_lang_parse);
			
			if(isset($fn_inputs['id']) && empty($fn_inputs['id']))
			{
				//create
				$fn_q = $db->ExecuteSQL("
					INSERT INTO :db (`lang_data`)
					VALUES (:vl);
				", array(
					'db' => $fn_db,
					'vl' => $fn_lang_parse,
				));
			}else{
				//update
				$fn_q = $db->Fetch("
					UPDATE :db 
					SET `lang_data`=:ln
					WHERE `id`=:id
				", array(
					'db' => $fn_db,
					'ln' => $fn_lang_parse,
					'id' => $fn_inputs['id'],
				));
				
				$fn_create_or_update = false;
			}
			
			if($fn_q)
			{
				exit(json_encode(array(
					'status' => 200,
					'message' => 'Ya lo tengo',
					'data' => array(
						'id' => ($fn_create_or_update) ? $fn_q : $fn_inputs['id'],
						'lang_data' => $fn_lang_parse,
						'lang_parse' => $fn_title_lang_data,
						'type' => $fn_inputs['f_ctAdmType'],
						'create' => $fn_create_or_update,
					),
				)));
			}else{
				exit(json_encode(array(
					'status' => 400,
					'message' => 'No hay nada respecto a lo que me preguntas',
				)));
			}
		break;
		
		//del color size
		case "delSizeItem":
		case "delColorItem":
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
				'message' => 'No sé que tipo.',
			)));
			
			$fn_db = ($fn_p['type'] == 'color') ? 'product_color' : 'product_size';
			
			$fn_q = $db->Fetch("
				DELETE FROM :db
				WHERE `id`=:id
			", array(
				'db' => $fn_db,
				'id' => $fn_p['id'],
			));
			
			if($fn_q)
			{
				exit(json_encode(array(
					'status' => 200,
					'message' => 'Ya lo tengo, eliminado',
				)));
			}else{
				exit(json_encode(array(
					'status' => 400,
					'message' => 'No he podido eliminar nada :(',
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
		
		/*
		case "submitForms":
			
			if(IsHotlink()) exit(json_encode(array(
				'status' => 400,
				'message' => 'Ajax Fraud cached!',
			)));
			
			if(!isset($fn_p['type'])) exit(json_encode(array(
				'status' => 400,
				'message' => getLangItem('form_error_404'),
			)));
			
			$fn_result = array();
			
			if(isset($fn_p['data'])) parse_str($fn_p['data'], $fn_inputs);
			
			switch($fn_p['type'])
			{
				case "contact":
				case "contacto":
					$fn_error_doms = array();
					
					//check inputs
					foreach($fn_inputs as $fk => $fv)
					{
						if(preg_match('/(name|email|message)/', $fk))
						{
							//empty check
							if(empty($fv)) $fn_error_doms[] = $fk;
						
							//email check
							if(preg_match('/email/', $fk) && emailValidation($fv)) $fn_error_doms[] = $fk;
						}
					}
					
					if(sizeof($fn_error_doms) == 0)
					{
						dm_nsw($CONFIG['site']['dm_nws'], $fn_inputs['c_email'], $fn_inputs['c_name'], '');
						
						$fn_message = htmlspecialchars($fn_inputs['c_message'], ENT_COMPAT, 'UTF-8');
						$fn_message_to_mail = "<p><strong>Nombre</strong> {$fn_inputs['c_name']}</p> <p><strong>Tema</strong> {$fn_inputs['c_subject']}</p> <p><strong>Mensaje</strong><br/>{$fn_message}</p>" ;
						
						$fn_to = $CONFIG['site']['mailinfo'];
						$fn_subject = "[Contacto] - {$CONFIG['site']['sitetitlefull']}";
						
						$fn_mail_html = str_replace(array(
							'%message%',
							'%regards%', 
							'%site_name%', 
							'%copyz%',
							'%site_dir%', 
							'%site_logo%', 
						), array(
							$fn_message_to_mail,
							getLangItem('regards'),
							$CONFIG['site']['sitetitlefull'],
							$CONFIG['site']['sitecopyz'],
							'',
							'',
						), $CONFIG['templates']['standartEmail']);
						
						$fn_content = preparehtmlmail($fn_inputs['c_email'], $fn_mail_html);
						
						if(mail($fn_to, $fn_subject, $fn_content['multipart'], $fn_content['headers']))
						{
							exit(json_encode(array(
								'status' => 200,
								'message' => getLangItem('lang_contact_success'),
							)));
						}else{
							exit(json_encode(array(
								'status' => 400,
								'message' => getLangItem('lang_contact_error_send'),
								'dom' => $fn_error_doms
							)));
						}
					}else{
						exit(json_encode(array(
							'status' => 400,
							'message' => getLangItem('lang_contact_error'),
							'dom' => $fn_error_doms
						)));
					}
				break;
			}
			
			exit;
		break;
		*/
		
		/* ------------------------------------------------------------------------------------------------ */
		
		/* sin login categorias expuesto al publico */
		
		case "productManager":
			if(IsHotlink()) exit(json_encode(array(
				'status' => 400,
				'message' => 'Ajax Fraud cached!',
			)));
			
			if(!isset($fn_p['a'])) exit(json_encode(array(
				'status' => 400,
				'message' => '[M3689]',
			)));
			
			//parse_str($fn_p['data'], $fn_inputs);
			
			switch($fn_p['a'])
			{
				case "category":
					//a = action, i = id
					$fn_q_a = array();
					$fn_w = '';
					
					if(empty($fn_p['i'])) exit(json_encode(array(
						'status' => 400,
						'message' => getLangItem('lang_fail_query'),
					)));
					
					if($fn_p['i'] !== 'all')
					{
						$fn_w = "AND `cat_id`=:i";
						$fn_q_a = array(
							'i' => $fn_p['i'],
						);
					}
					
					$fn_q = $db->FetchAll("
						SELECT `id`, `lang_data`, `gallery_id`
						FROM `product`
						WHERE `active`='1'
						{$fn_w}
						ORDER BY `order` ASC
					", $fn_q_a);
					
					if($fn_q)
					{
						$fn_q_out = array();
						
						foreach($fn_q as $qk => $qv)
						{
							$for_data = object_to_array($qv);
							
							$for_data['lang_data'] = decodeLangData($qv->lang_data);
							$for_data['image'] = getThumbFromGallery($qv->gallery_id);
							
							$fn_q_out[] = $for_data;
						}
						
						shuffle($fn_q_out);
						
						exit(json_encode(array(
							'status' => 200,
							'message' => "[M3720]",
							'data' => $fn_q_out,
							'lang' => array(
								'lang_details' => getLangItem('lang_details'),
								'lang_buy_now_button' => getLangItem('lang_buy_now_button'),
							),
						)));
					}else{
						exit(json_encode(array(
							'status' => 400,
							'message' => getLangItem('lang_no_items'),
						)));
					}
				break;
				
				case "collection":
///---------------->
///---------------->
///---------------->
///---------------->
///---------------->
///---------------->
///---------------->
///---------------->
				break;
				
				case "getProductDetails":
					if(empty($fn_p['i'])) exit(json_encode(array(
						'status' => 400,
						'message' => getLangItem('lang_fail_query'),
					)));
					
					$fn_q_prod = $db->FetchArray("
						SELECT `id`, `lang_data`, `hash`, `gallery_id`
						FROM `product`
						WHERE `active`='1'
						AND `id`=:i
						LIMIT 1
					", array(
						'i' => $fn_p['i'],
					));
					
					if($fn_q_prod)
					{
						$fn_data_out = $fn_q_prod;
						$fn_data_out['lang_data'] = decodeLangData($fn_data_out['lang_data']);
						
						$fn_q_prod_metas = $db->FetchAll("
							SELECT * 
							FROM `product_meta`
							WHERE `p_id`=:i
						", array(
							'i' => $fn_q_prod['id'],
						));
						
						if(sizeof($fn_q_prod_metas) !== 0)
						{
							foreach($fn_q_prod_metas as $mk => $mv)
							{
								$fn_data_out[$mv->m_key] = (preg_match('/(_content|_envio_ex)/', $mv->m_key)) ? decodeLangData($mv->m_value) : $mv->m_value;
							}
						}
						
						$fn_q_prod_gallery = $db->FetchValue("
							SELECT `objects`
							FROM `gallery`
							WHERE `id`=:i
						", array(
							'i' => $fn_q_prod['gallery_id'],
						));
						
						$fn_data_out['slider'] = array(
							"config" => array(
								'dom' => 'slider_product',
								'type' => 'dual',	
								'showArrows' => false,
								'showDots' => false,
							),
							'data' => ($fn_q_prod_gallery) ? json_decode($fn_q_prod_gallery, true) : null,
						);
						
						exit(json_encode(array(
							'status' => 200,
							'message' => "[M3798]",
							'data' => $fn_data_out,
						)));
					}else{
						exit(json_encode(array(
							'status' => 400,
							'message' => getLangItem('lang_no_items'),
						)));
					}
					
				break;
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