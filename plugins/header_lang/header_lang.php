<?php

class WIDGET_header_lang {
	
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
	
	/**
	 * wHeader function.
	 * 
	 * @access public
	 * @param mixed $fn_args
	 * @return void return array $fn_xtemplate_parse;
	 */
	public function wHeader($fn_args)
	{
		if(!$this->CONFIG['site']['showHeaderLangs']) return;
		
		if(empty($fn_args['url'])) return;
		if(preg_match('/(admin)/', $fn_args['url'])) return;
		
		$fn_lng = (isset($this->CONFIG['site']['lang']) && isJson($this->CONFIG['site']['lang'])) ? json_decode($this->CONFIG['site']['lang'], true) : false;
		
		//lang ignore
		$fn_lng_ignore = (isset($this->CONFIG['site']['langIgnore']) && isJson($this->CONFIG['site']['langIgnore'])) ? json_decode($this->CONFIG['site']['langIgnore'], true) : false;
		
		if($fn_lng_ignore)
		{
			$fn_lng = array_diff($fn_lng, $fn_lng_ignore);
		}
		
		$fn_rels = self::getAllLangsRels($fn_args);
		
		if($fn_lng && sizeof($fn_lng) > 1)
		{
			$fn_arg_url = (preg_match('/(pages_details)/', $fn_args['url'])) ? $fn_args['hash'] : $fn_args['url'];
			$fn_blog_url = (isset($fn_args['hash']) && preg_match('/(blog_details|blog)/', $fn_args['url'])) ? "blog/{$fn_args['hash']}" : $fn_arg_url;
			
			//reorder
			foreach($fn_lng as $fv)
			{
				//if($fv == $fn_args['st_lang']) continue;
				
				$fn_get_flag_image = (file_exists("images/flags/{$fv}.png")) ? $this->CONFIG['site']['base_script']."images/flags/{$fv}.png" : false;
				
				
				$fn_hash = $fn_arg_url;
				
				if(isset($fn_rels[$fv]))
				{
					$fn_hash = self::getPageHashById($fn_rels[$fv]);
				}else{
					$fn_hash_n = self::getTransHash($fn_arg_url, $fv);
					
					if(!$fn_hash_n)
					{
						$fn_hash_correct = preg_replace("/\_/", "-", $fn_arg_url);
						$fn_hash_nn = self::getTransHash($fn_hash_correct, $fv);
						
						if($fn_hash_nn) $fn_hash = $fn_hash_nn;
					}else{
						$fn_hash = $fn_hash_n; 
					}
				}
				
				self::$fn_xtemplate_parse['assign'][] = array(
					'active' => ($fv == $fn_args['st_lang']) ? 'active' : '',
					'lng' => $fv,
					'plugin_hash' => $fn_hash,
					'img' => ($fn_get_flag_image) ? '<img class="lang-flag" src="'.$fn_get_flag_image.'" />' : '',
				);
				self::$fn_xtemplate_parse['parse'][] = "{$fn_args['stage_id']}.not_home.header_lang.row_lang";
			}
			
			self::$fn_xtemplate_parse['assign'][] = array();
			self::$fn_xtemplate_parse['parse'][] = "{$fn_args['stage_id']}.not_home.header_lang";
		}
		
		return self::$fn_xtemplate_parse;
	}
	
	private function getTransHash($fn_original_hash, $fn_trans_lang)
	{
		$fn_trans = $this->db->FetchValue("
			SELECT ph.`obj_hash`
			FROM `pages` p
			LEFT JOIN `pages_lang_rel` r ON(r.`page_id`=p.`id`)
			LEFT JOIN `pages` ph ON(ph.`id`=r.`page_translate_id`)
			WHERE p.`obj_hash`=:h
			AND r.`lang_type`=:l
			LIMIT 1;
		", array(
			"h" => $fn_original_hash,
			"l" => $fn_trans_lang,
		));
		
		return ($fn_trans) ? $fn_trans : false; 
	}
	
	private function getAllLangsRels($fn_args)
	{
		if(!isset($fn_args['hash'])) return false;
		
		$fn_q_this_id = $this->db->FetchValue("
			SELECT `id`
			FROM `pages`
			WHERE `obj_hash`=:h
		", array(
			"h" => $fn_args['hash']
		));
		
		if($fn_q_this_id)
		{
			$get_rels_id = $this->db->FetchAll("
				SELECT `lang_type` as 'lang', `page_translate_id` as `id`
				FROM `pages_lang_rel`
				WHERE `page_id`=:pid
			", array(
				"pid" => $fn_q_this_id
			));
			
			if($get_rels_id)
			{
				$fn_out = array();
				
				foreach($get_rels_id as $k => $v)
				{
					$fn_out[$v->lang] = $v->id;
				}
				
				return $fn_out;	
			}else{
				return false;
			}
		}else{
			return false;
		}
	}
	
	private function getPageHashById($fn_id)
	{
		if(!$fn_id) return false;
		$fn_q = $this->db->FetchValue("
			SELECT `obj_hash`
			FROM `pages`
			WHERE `id`=:id
			LIMIT 1;
		", array(
			"id" => $fn_id
		));
		
		return $fn_q;
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
		return self::$fn_xtemplate_parse;
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