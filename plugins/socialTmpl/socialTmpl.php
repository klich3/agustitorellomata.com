<?php

class WIDGET_socialTmpl {
	
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
		$fn_s_array = self::getSocails();
		
		if($fn_s_array) 
		{
			$fn_i = 1;
			foreach($fn_s_array as $sk => $sv)
			{
				if(empty($sv->options_value)) continue;
				
				self::$fn_xtemplate_parse['assign'][] = array(
					'val' => $sv->options_value,
					'desc' => $sv->description,
				);
				self::$fn_xtemplate_parse['parse'][] = "{$fn_args['stage_id']}.socialTmpl.{$sv->options_key}_header";
				
				$fn_i++;
			}
		
			//only header cart icon
			self::$fn_xtemplate_parse['assign'][] = array();
			self::$fn_xtemplate_parse['parse'][] = "{$fn_args['stage_id']}.socialTmpl.header_cart_item";
			//only header cart icon
		
			self::$fn_xtemplate_parse['assign'][] = array(
				'total' => (int)$fn_i,
			);
			self::$fn_xtemplate_parse['parse'][] = "{$fn_args['stage_id']}.socialTmpl";
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
		$fn_s_array = self::getSocails();
		
		if($fn_s_array) 
		{
			$fn_i = 0;
			foreach($fn_s_array as $sk => $sv)
			{
				if(empty($sv->options_value)) continue;
				
				self::$fn_xtemplate_parse['assign'][] = array(
					'val' => $sv->options_value,
					'desc' => $sv->description,
				);
				self::$fn_xtemplate_parse['parse'][] = "{$fn_args['stage_id']}.socialTmpl.{$sv->options_key}_footer";
				
				$fn_i++;
			}
		
			self::$fn_xtemplate_parse['assign'][] = array(
				'total' => (int)$fn_i,
			);
			self::$fn_xtemplate_parse['parse'][] = "{$fn_args['stage_id']}.socialTmpl";
		}
		
		return self::$fn_xtemplate_parse;
	}
	
	/**
	 * getSocails function.
	 * 
	 * @access private
	 * @return void
	 */
	private function getSocails()
	{
		$fn_q = $this->db->FetchAll("
			SELECT *
			FROM `options`
			WHERE `options_key` IN ('fb','tw','gp','tr','in')
		");
		
		return $fn_q;
	}
}

?>