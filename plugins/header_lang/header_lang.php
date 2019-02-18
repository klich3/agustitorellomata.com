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
		$fn_lng = (isset($this->CONFIG['site']['lang']) && isJson($this->CONFIG['site']['lang'])) ? json_decode($this->CONFIG['site']['lang'], true) : false;
				
		if($fn_lng && sizeof($fn_lng) > 1)
		{
			$fn_arg_url = (preg_match('/(pages_details)/', $fn_args['url'])) ? $fn_args['hash'] : $fn_args['url'];
			$fn_blog_url = (isset($fn_args['hash']) && preg_match('/(blog_details|blog)/', $fn_args['url'])) ? "blog/{$fn_args['hash']}" : $fn_arg_url;
			
			//default lang flag
			$fn_get_flag_def_image = (file_exists("images/flags/{$fn_args['st_lang']}.png")) ? $this->CONFIG['site']['base_script']."images/flags/{$fn_args['st_lang']}.png" : false;
			$fn_user_lang_img = ($fn_get_flag_def_image) ? '<img class="lang-flag" src="'.$fn_get_flag_def_image.'" />' : '';
			
			//reorder
			foreach($fn_lng as $fv)
			{
				if($fv == $fn_args['st_lang']) continue;
				
				$fn_get_flag_image = (file_exists("images/flags/{$fv}.png")) ? $this->CONFIG['site']['base_script']."images/flags/{$fv}.png" : false;
				
				self::$fn_xtemplate_parse['assign'][] = array(
					'active' => ($fv == $fn_args['st_lang']) ? 'active' : '',
					'lng' => $fv,
					'plugin_hash' => (isset($fn_args['hash'])) ? $fn_blog_url : $fn_arg_url,
					'img' => ($fn_get_flag_image) ? '<img class="lang-flag" src="'.$fn_get_flag_image.'" />' : '',
				);
				self::$fn_xtemplate_parse['parse'][] = "{$fn_args['stage_id']}.header_lang.row_lang";
			}
			
			self::$fn_xtemplate_parse['assign'][] = array(
				'user_lang_img' => $fn_user_lang_img,
			);
			self::$fn_xtemplate_parse['parse'][] = "{$fn_args['stage_id']}.header_lang";
		}
		
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