<?php

class WIDGET_jsonToTmpl {
	
	private $CONFIG;
	private $db;
	private $too_login;
	public static $fn_xtemplate_parse = array(
		'assign' => false,
		'parse' => false,
	);
	
	/**
	 * __construct function.
	 * 
	 * @access public
	 * @param mixed $fn_config basicamente se le pasa $CONFIG
	 * @param mixed $fn_db conexion a la db
	 * @param mixed $too_login classe login
	 * @return void
	 */
	public function __construct($fn_config, $fn_db, $too_login)
	{
		//parse config
		$this->CONFIG = $fn_config;
		$this->db = $fn_db;
		$this->too_login = $too_login;
	}
	
	public function __destruct()
	{
		self::$fn_xtemplate_parse = array(
			'assign' => false,
			'parse' => false,
		);
	}
	
	/**
	 * wHeader function.
	 * 
	 * @access public
	 * @param mixed $fn_args
	 * @return void return array $fn_xtemplate_parse;
	 */
	public function wHeader($fn_args)
	{
		return self::$fn_xtemplate_parse;
	}
	
	/**
	 * wPage function.
	 * 
	 * @access public
	 * @param mixed $fn_args
	 * @return void
	 */
	public function wPage($fn_args)
	{
		if(empty($fn_args['url'])) return;
		
		//excepcion
		if(isset($fn_args['stage_tmpl']) && isset($fn_args['stage_id']) && $fn_args['stage_tmpl'] == $fn_args['stage_id']) $fn_hash = $fn_args['hash'];
		
		//si es admin idioma por defecto se reescribe al español
		if(preg_match('/(admin)/', $fn_args['url'])) $fn_args['lang'] = 'es';
		if(!isset($fn_args['lang'])) $fn_args['lang'] = $this->CONFIG['site']['defaultLang'];
		
		if(!isset($fn_args['hash']) && isset($fn_args['stage_id'])) $fn_args['hash'] = $fn_args['stage_id'];
		
		$fn_json = self::parseByPageContent($fn_args);
		
		if(!$fn_json)
		{
			$fn_args['hash'] = preg_replace("/\_/", "-", $fn_args['hash']);
			
			$fn_json = self::parseByPageContent($fn_args);
			if(!$fn_json) return;
		}
		
		$fn_json = base64_decode($fn_json);
		$fn_json = html_entity_decode($fn_json, ENT_QUOTES | ENT_HTML5);
		
		if(!isJson($fn_json)) return;
		
		/*
			replaces only on admin side
		*/
		if(class_exists('tooLogin') && $this->too_login->isLogged() == 200)
		{
			$fn_userData = $this->too_login->getUserData();
			
			foreach($fn_userData as $k => $v)
			{
				$fn_json = preg_replace("/%".$k."%/", $v, $fn_json);
			}
		}
		
		$fn_data = object_to_array(json_decode($fn_json));
		$fn_hbox_count = 0;
		
		foreach($fn_data as $gk => $gv)
		{
			//elements
			if(isset($gv['dom']) && !empty($gv['dom']))
			{
				if(isset($gv['boxheight']) && $gv['boxheight'] && $gv['dom']['type'] == "img")
				{
					$fn_for_data = $gv['dom'];
					
					$fn_for_data['value'] = (preg_match('/^(http:|https:|\/\/)/m', $fn_for_data['value'])) ? $fn_for_data['value'] : "{$this->CONFIG['site']['base']}{$fn_for_data['value']}";
					
					self::$fn_xtemplate_parse['assign'][] = $fn_for_data;
					self::$fn_xtemplate_parse['parse'][] = "{$fn_args['stage_id']}.grid.row.element_boxheight_img";
				}else{
					$fn_for_data = $gv['dom'];
					
					if(preg_match('/img/', $fn_for_data['type'])) $fn_for_data['value'] = (preg_match('/^(http:|https:|\/\/)/m', $fn_for_data['value'])) ? $fn_for_data['value'] : "{$this->CONFIG['site']['base']}{$fn_for_data['value']}";
					
					self::$fn_xtemplate_parse['assign'][] = $fn_for_data;
					self::$fn_xtemplate_parse['parse'][] = "{$fn_args['stage_id']}.grid.row.element_{$gv['dom']['type']}";
				}
			}
			
			//grid type
			$fn_for_data = $gv;
			$fn_for_data['type'] = (preg_match('/(mt-|pt-)/', $gv['type'])) ? "w-1-1 {$gv['type']}" : $gv['type'];
			
			//responsive type
			//if(!preg_match('/(mt-|pt-)/', $gv['type'])) $fn_for_data['type_responsive'] = preg_replace('/^w-(.*)/m', 'w-$1 ws-1-1 wm-$1 wl-$1 wxl-$1', $gv['type']);
			$fn_for_data['attributes_options'] = (preg_match('/\s/', $gv['type'])) ? $gv['type'] : preg_replace('/^w-(.*)/m', 'w-$1 ws-1-1 wm-$1 wl-$1 wxl-$1', $gv['type']);
			
			//grid + grid collapse
			if(preg_match('/(gc)/', $fn_for_data['type']))
			{
				$fn_for_data['grid_collapse'] = '</div></div><div class="g gc">';
				$fn_for_data['attributes_options'] = '';
			}else if(preg_match('/(g)/', $fn_for_data['type']))
			{
				$fn_for_data['grid_collapse'] = '</div><div class="c cc cp-4@s"><div class="g">';
				$fn_for_data['attributes_options'] = '';
			}else{
				$fn_for_data['grid_collapse'] = '';
			}
			
			//margin & padding
			if(preg_match('/(mt-|pt-)/', $fn_for_data['type']))
			{
				$fn_for_data['mt'] = 'w-1-1';
				//$fn_for_data['nb'] = '&nbsp;';
			}
			
			//box height
			if(isset($fn_for_data['boxheight']) && $fn_for_data['boxheight'])
			{
				//separador entre cuadros
				if($fn_hbox_count > 0)
				{
					self::$fn_xtemplate_parse['assign'][] = '';
					self::$fn_xtemplate_parse['parse'][] = "{$fn_args['stage_id']}.grid.row_boxh_sep";
				}
				
				$fn_hbox_count++;
			}
			
			if(preg_match('/slider/', $gv['type']))
			{
				//slider-0-1-10
				//slider-<arrow 0/1>-<dots 0/1>-<id gallery>
				
				preg_match('/^slider-(.*?)\-(.*?)\-(.*?)\s/', $gv['type'], $fn_opts_match);
				
				if(sizeof($fn_opts_match) !== 0)
				{
					//get gallery content
					$fn_data_gallery = self::parseGalleryImages($fn_opts_match[3]);
					
					if(!$fn_data_gallery) continue;
					
					$fn_slider_id = md5(microtime());
					
					/*
					var_dump($fn_opts_match);
					array (size=4)
					  0 => string 'slider-1-0-12 ' (length=14) //class
					  1 => string '1' (length=1) //arrows
					  2 => string '0' (length=1) //dots
					  3 => string '12' (length=2)
					
					*/
					
					self::$fn_xtemplate_parse['assign'][] = array(
							'sliderid' => $fn_slider_id,
							'jsonSlider' => json_encode(array(
								'config' => array(
									'dom' => "cnSlider-{$fn_slider_id}",
									'type' => 'normal',	
									'showArrows' => (isset($fn_opts_match[1]) && $fn_opts_match[1] == '0') ? false : true, //1
									'showDots' => (isset($fn_opts_match[2]) && $fn_opts_match[2] == '0') ? false : true, //2
								),
								'data' => $fn_data_gallery,
							)),
						);
					self::$fn_xtemplate_parse['parse'][] = "{$fn_args['stage_id']}.grid.row.element_slider";
				}
				
				//saltamos resto de elementos no mover esta parte
				
				self::$fn_xtemplate_parse['assign'][] = $fn_for_data;
				self::$fn_xtemplate_parse['parse'][] = "{$fn_args['stage_id']}.grid.row";
				
				continue;
			}
			
			if(preg_match('/videofs/', $gv['type']))
			{
				//slider-0-10
				//slider-<controls 0/1>-<id gallery>
				preg_match('/^videofs-(.*?)\-(.*?)\s/', $gv['type'], $fn_opts_match);
				
				if(sizeof($fn_opts_match) !== 0)
				{
					//get gallery content
					$fn_data_gallery = self::parseGalleryImages($fn_opts_match[2], false);
					
					if(!$fn_data_gallery) continue;
					
					$fn_slider_id = md5(microtime());
					
					self::$fn_xtemplate_parse['assign'][] = array(
							'videoid' => $fn_slider_id,
							'jsonVideo' => json_encode(array(
								'config' => array(
									'dom' => "cnVideo-{$fn_slider_id}",
									'type' => 'normal',	
									'showControls' => (isset($fn_opts_match[1]) && $fn_opts_match[1] == '0') ? false : true,
								),
								'data' => $fn_data_gallery,
							)),
						);
					self::$fn_xtemplate_parse['parse'][] = "{$fn_args['stage_id']}.grid.row.element_videofs";
				}
				
				//saltamos resto de elementos no mover esta parte
				
				self::$fn_xtemplate_parse['assign'][] = $fn_for_data;
				self::$fn_xtemplate_parse['parse'][] = "{$fn_args['stage_id']}.grid.row";
				
				continue;
			}
			
			if(isset($gv['control']))
			{
				if($gv['control'] > 0)
				{
					$fn_page_metas = self::getPageMetas($fn_args);
					
					//button como llegar
					if($fn_page_metas && isset($fn_page_metas['actividades_geo_show_button']) && $fn_page_metas['actividades_geo_show_button'])
					{	
						self::$fn_xtemplate_parse['assign'][] = $fn_page_metas;
						self::$fn_xtemplate_parse['parse'][] = "{$fn_args['stage_id']}.grid.row.element_control_{$gv['control']}.actividades_geo_show_button";
					}
					
					if($fn_page_metas && isset($fn_page_metas['actividades_reservas_show_button']) && $fn_page_metas['actividades_reservas_show_button'])
					{	
						self::$fn_xtemplate_parse['assign'][] = array();
						self::$fn_xtemplate_parse['parse'][] = "{$fn_args['stage_id']}.grid.row.element_control_{$gv['control']}.actividades_reservas_show_button";
					}
					
					self::$fn_xtemplate_parse['assign'][] = array();
					self::$fn_xtemplate_parse['parse'][] = "{$fn_args['stage_id']}.grid.row.element_control_{$gv['control']}";
				}
				
				//saltamos resto de elementos no mover esta parte
				
				self::$fn_xtemplate_parse['assign'][] = $fn_for_data;
				self::$fn_xtemplate_parse['parse'][] = "{$fn_args['stage_id']}.grid.row";
				
				continue;
			}
			
			self::$fn_xtemplate_parse['assign'][] = $fn_for_data;
			self::$fn_xtemplate_parse['parse'][] = "{$fn_args['stage_id']}.grid.row";
		}
	
		self::$fn_xtemplate_parse['assign'][] = '';
		self::$fn_xtemplate_parse['parse'][] = "{$fn_args['stage_id']}.grid";
		
		return self::$fn_xtemplate_parse;
	}
	
	/**
	 * getDir function.
	 * 
	 * @access private
	 * @param mixed $fn_dir_item
	 * @return void
	 */
	private function getDir($fn_dir_item = false)
	{
		$fn_dir = (isset($this->CONFIG['site']['dir']) && isJson($this->CONFIG['site']['dir'])) ? json_decode($this->CONFIG['site']['dir'], true) : false;
		
		return (!$fn_dir_item) ? $fn_dir[0] : $fn_dir[0][$fn_dir_item];
	}
	
	/**
	 * parseGalleryImages function.
	 * get gallery content
	 *
	 * @access private
	 * @param mixed $fn_gid
	 * @param boolean $fn_replace_path
	 * @return void
	 */
	private function parseGalleryImages($fn_gid, $fn_replace_path = true)
	{
		$fn_slider_q = $this->db->FetchValue("
			SELECT `objects`
			FROM `gallery`
			WHERE `id`=:i
			LIMIT 1;
		", array(
			'i' => $fn_gid,
		));
		
		$fn_array_out = array();
		
		if($fn_slider_q && isJson($fn_slider_q))
		{
			$fn_slider_q = json_decode($fn_slider_q, true);
			
			foreach($fn_slider_q as $gk => $gv)
			{
				if(isset($gv['img']))
				{
					if($fn_replace_path)
					{
						$fn_reg = str_replace('/', '\/', $this->CONFIG['site']['base_script']);
						$fn_reg = (string)"/({$fn_reg})/";
						$fn_foto = (preg_match($fn_reg, $gv['img'])) ? $gv['img'] : $this->CONFIG['site']['base_script'].$gv['img'];
					}else{
						$fn_foto = $gv['img'];
					}
					
					
					$gv['img'] = $fn_foto;
				}
				
				$fn_array_out[] = $gv;
			}
		}
		
		return (sizeof($fn_array_out) !== 0) ? $fn_array_out : false;
	}
	
	/**
	 * getPageMetas function.
	 * 
	 * @access private
	 * @param string $fn_page_hash
	 * @return void
	 */
	private function getPageMetas($fn_args)
	{
		$fn_q = $this->db->FetchAll("
			SELECT m.*
			FROM `pages_meta` m
			LEFT JOIN `pages` p ON(m.`p_id`=p.`id`)
			WHERE p.`obj_hash`=:h
			AND p.`lang`=:l
		", array(
			"h" => $fn_args['hash'],
			"l" => $fn_args['st_lang']
		));
		
		$fn_result = false;
		
		if($fn_q) foreach($fn_q as $k => $v)
		{			
			$fn_result[$v->meta_key] = $v->meta_value;
		}
		
		return $fn_result;
	}
	
	/**
	 * parseByPageContent function.
	 * 
	 * @access private
	 * @param mixed $fn_args
	 * @param mixed $fn_hash
	 * @return void
	 */
	private function parseByPageContent($fn_args)
	{
		$fn_get_normal = $this->db->FetchArray("
			SELECT *
			FROM `pages`
			WHERE `obj_hash`=:h
			AND `lang`=:l
		", array(
			"h" => $fn_args['hash'],
			"l" => $fn_args['lang']
		));
		
		if($fn_get_normal)
		{
			//normal page with lang
			$fn_q_metas = self::getPageMetas($fn_args);
			
			return $fn_q_metas['page_content'];
		}else{
			//no lang look for rel
			//redirect to correct one
			
			if(preg_match('/(admin)/', $fn_args['url'])) return false;
			
			$fn_trans = $this->db->FetchValue("
				SELECT ph.`obj_hash`
				FROM `pages` p
				LEFT JOIN `pages_lang_rel` r ON(r.`page_id`=p.`id`)
				LEFT JOIN `pages` ph ON(ph.`id`=r.`page_translate_id`)
				WHERE p.`obj_hash`=:h
				AND r.`lang_type`=:l
				LIMIT 1;
			", array(
				"h" => $fn_args['hash'],
				"l" => $fn_args['st_lang'],
			));
			
			$fn_args['hash'] = $fn_trans;
			
			$fn_q_metas = self::getPageMetas($fn_args);
			
			return ($fn_q_metas) ? $fn_q_metas['page_content'] : false;
		}
	}
	
	private function loadRelContent($fn_rel_id)
	{
		$fn_q = $this->db->FetchArray("
			SELECT p.`id`, p.`active`, p.`lang`, m.`meta_value` as 'pageContent'
			FROM `pages_lang_rel` r
			LEFT JOIN `pages` p ON(p.`id`=r.`page_translate_id`)
			LEFT JOIN `pages_meta` m ON(m.`p_id`=r.`page_translate_id`)
			WHERE r.`page_id`=:id
			AND m.`meta_key`='page_content'
			LIMIT 1;
		", array(
			'id' => $fn_rel_id
		));
		
		return ($fn_q) ? $fn_q : false;
	}
	
	/**
	 * wFooter function.
	 * 
	 * @access public
	 * @param mixed $fn_args
	 * @return void
	 */
	public function wFooter($fn_args)
	{
		return self::$fn_xtemplate_parse;
	}
}

?>