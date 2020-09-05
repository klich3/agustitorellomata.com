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
		/*
		var_dump($fn_args);
		
		array(5) { ["header_type"]=> string(6) "select" ["url"]=> string(4) "home" ["stage_id"]=> string(4) "home" ["plugins_templates"]=> array(3) { ["header_slider"]=> string(35) "plugins/header_slider/header_slider" ["jsonToTmpl"]=> string(29) "plugins/jsonToTmpl/jsonToTmpl" ["productos_destacados_footer"]=> string(63) "plugins/productos_destacados_footer/productos_destacados_footer" } ["st_lang"]=> string(2) "es" }
		*/
		
		if(empty($fn_args['url'])) return;
		
		//hash de paginas
		//$fn_arg_url = (preg_match('/(pages_details)/', $fn_args['url'])) ? $fn_args['hash'] : $fn_args['url'];
		$fn_hash = (isset($fn_args['stage_id']) && $fn_args['stage_id'] !== 'pages_details') ? $fn_args['stage_id'] : $fn_args['hash'];
		
		//excepcion
		if(isset($fn_args['stage_tmpl']) && isset($fn_args['stage_id']) && $fn_args['stage_tmpl'] == $fn_args['stage_id']) $fn_hash = $fn_args['hash'];
		
		//si es admin idioma por defecto se reescribe al español
		if(preg_match('/(admin)/', $fn_args['url'])) $fn_args['lang'] = 'es';
		if(!isset($fn_args['lang'])) $fn_args['lang'] = $this->CONFIG['site']['defaultLang'];
		
		$fn_json = self::parseByPageContent($fn_args, $fn_hash);
		
		if(!$fn_json) return;
		
		$fn_json = base64_decode($fn_json);
		$fn_json = html_entity_decode($fn_json, ENT_QUOTES | ENT_HTML5);
		
		if(!isJson($fn_json)) return;
		
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
			
			self::$fn_xtemplate_parse['assign'][] = $fn_for_data;
			self::$fn_xtemplate_parse['parse'][] = "{$fn_args['stage_id']}.grid.row";
		}
	
		self::$fn_xtemplate_parse['assign'][] = '';
		self::$fn_xtemplate_parse['parse'][] = "{$fn_args['stage_id']}.grid";
		
		return self::$fn_xtemplate_parse;
	}
	
	/**
	 * parseGalleryImages function.
	 * get gallery content
	 *
	 * @access private
	 * @param mixed $fn_gid
	 * @return void
	 */
	private function parseGalleryImages($fn_gid)
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
					$fn_reg = str_replace('/', '\/', $this->CONFIG['site']['base_script']);
					$fn_reg = (string)"/({$fn_reg})/";
					$fn_foto = (preg_match($fn_reg, $gv['img'])) ? $gv['img'] : $this->CONFIG['site']['base_script'].$gv['img'];
					
					$gv['img'] = $fn_foto;
				}
				
				$fn_array_out[] = $gv;
			}
		}
		
		return (sizeof($fn_array_out) !== 0) ? $fn_array_out : false;
	}
	
	/**
	 * parseByPageContent function.
	 * 
	 * @access private
	 * @param mixed $fn_args
	 * @param mixed $fn_hash
	 * @return void
	 */
	private function parseByPageContent($fn_args, $fn_hash)
	{
		//idioma por defecto
		$fn_def_lang = $this->db->FetchArray("
			SELECT p.`id`, p.`active`, p.`lang`, m.`meta_value` AS 'pageContent'
			FROM `pages` p
			LEFT JOIN `pages_meta` m ON(m.`p_id`=p.`id`)
			WHERE p.`obj_hash`=:hash
			AND p.`lang`=:l
			AND m.`meta_key`='page_content'
			AND p.`active`='1'
			LIMIT 1;
		", array(
			'hash' => $fn_hash,
			'l' => $this->CONFIG['site']['defaultLang'],
		));
		
		if(!$fn_def_lang) return false;
		
		$fn_translate = $this->db->FetchValue("
			SELECT m.`meta_value` AS 'pageContent'
			FROM `pages_lang_rel` pr
			LEFT JOIN `pages_meta` m ON(m.`p_id`=pr.`page_translate_id`)
			WHERE m.`meta_key`='page_content'
			AND pr.`lang_type`=:lang
			AND pr.`page_id`=:pid
		", array(
			'lang' => $fn_args['lang'],
			'pid' => $fn_def_lang['id'],
		));

		$fn_translate_unbase = base64_decode($fn_translate);
		$fn_json = (isset($fn_translate) && $fn_translate_unbase !== 'false' && $fn_args['lang'] !== $this->CONFIG['site']['defaultLang']) ? $fn_translate : $fn_def_lang['pageContent'];
		
		return $fn_json;
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