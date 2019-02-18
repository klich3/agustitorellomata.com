<?php

class WIDGET_contactoTmpl {
	
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
		//hash de paginas
		$fn_arg_url = (preg_match('/(pages_details)/', $fn_args['url'])) ? $fn_args['hash'] : $fn_args['url'];
		
		//prevent exec on admin stage
		if(preg_match('/admin/', $fn_arg_url)) return;
		
		if(preg_match('/home/', $fn_arg_url))
		{
			self::$fn_xtemplate_parse['assign'][] = '';
			self::$fn_xtemplate_parse['parse'][] = "{$fn_args['stage_id']}.contactoTmpl.home_header";
		}else{
			self::$fn_xtemplate_parse['assign'][] = '';
			self::$fn_xtemplate_parse['parse'][] = "{$fn_args['stage_id']}.contactoTmpl.rest_header";
		}
		
		//gmap dir
		$fn_dir = array();
		
		if(isset($this->CONFIG['site']['dir']) && isJson($this->CONFIG['site']['dir']))
		{	
			$fn_dir = json_decode($this->CONFIG['site']['dir'], true);
			$fn_dir = ($this->CONFIG['site']['dirDefault'] !== '-1') ? $fn_dir[$this->CONFIG['site']['dirDefault']] : $fn_dir;
			
			unset($fn_dir['placeTitle']);
			unset($fn_dir['shortDir']);
			unset($fn_dir['tel']);
			unset($fn_dir['telAlter']);
			unset($fn_dir['cif']);
			
			$fn_dir['renderDomId'] = 'gmap';
		}
		//gmap dir
		
		self::$fn_xtemplate_parse['assign'][] = array(
			'jsonGmap' => json_encode($fn_dir),
		);
		self::$fn_xtemplate_parse['parse'][] = "{$fn_args['stage_id']}.contactoTmpl";
		
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