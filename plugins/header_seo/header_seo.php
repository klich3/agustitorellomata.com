<?php

class WIDGET_header_seo {
	
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
		if(empty($fn_args['url'])) return;
		if(!isset($fn_args['stage_type'])) return;
		if(!preg_match('/(page|product)/', $fn_args['stage_type'])) return;
		
		$fn_hash = (isset($fn_args['url']) && !preg_match('/(pages_details)/', $fn_args['url'])) ? $fn_args['url'] : $fn_args['hash'];
		
		//$fn_args['stage_type'] == 'page';
		//$fn_args['stage_type'] == 'product';
		if($fn_args['stage_type'] == 'product') $fn_hash_array = (preg_match('/\//', $fn_hash)) ? explode('/', $fn_hash) : array('', $fn_hash);
		
		$fn_query_metas = ($fn_args['stage_type'] == 'page') ? "SELECT m.*
			FROM `pages` p
			LEFT JOIN `pages_meta` m ON(m.`p_id`=p.`id`)
			WHERE p.`obj_hash`=:hash" : 
			
			"SELECT m.*
			FROM `product` p
			LEFT JOIN `product_meta` m ON(m.`p_id`=p.`id`)
			WHERE p.`hash`=:hash";
		
		$fn_q = $this->db->FetchAll($fn_query_metas, array(
			'hash' => ($fn_args['stage_type'] == 'page') ? $fn_hash : $fn_hash_array[1],
		));
		
		$fn_meta_array = array();
		
		if($fn_q) foreach($fn_q as $fk => $fv)
		{
			if($fn_args['stage_type'] == 'page' && preg_match('/seo_/', $fv->meta_key) && $fv->meta_value && !empty($fv->meta_value)) $fn_meta_array[$fv->meta_key] = $fv->meta_value;
			
			if($fn_args['stage_type'] == 'product' && preg_match('/seo_/', $fv->m_key) && $fv->m_value && !empty($fv->m_value)) $fn_meta_array[$fv->m_key] = $fv->m_value;
		}
		
		if(count($fn_meta_array) !== 0) foreach($fn_meta_array as $sk => $sv)
		{
			self::$fn_xtemplate_parse['assign'][] = array(
				'value' => $sv,
			);
			self::$fn_xtemplate_parse['parse'][] = "{$fn_args['stage_id']}.not_home.{$sk}";	
		}
		
		$fn_hash_to_title = ($fn_hash) ? ucfirst(str_replace(array('-', '_', '/'), ' ', $fn_hash)) : '';
		$fn_ob_image = (isset($fn_meta_array['seo_customimage'])) ? $fn_meta_array['seo_customimage'] : $this->CONFIG['site']['base'].'images/logo-fb-share.png';
		
		self::$fn_xtemplate_parse['assign'][] = array(
			'og_title' => $fn_hash_to_title,
			'og_desc_value' => (array_key_exists('seo_description', $fn_meta_array)) ? $fn_meta_array['seo_description'] : $this->CONFIG['site']['description'],
			'base' => $this->CONFIG['site']['base'],
			'user_lang' => $fn_args['st_lang'],
			'stage_hash' => $fn_hash,
			'sitetitlefull' => $this->CONFIG['site']['sitetitlefull'],
			'og_image' => $fn_ob_image,
		);
		self::$fn_xtemplate_parse['parse'][] = "{$fn_args['stage_id']}.not_home.og_cart";
		
		//fb api
		$fn_q_fbapi = $this->db->FetchValue("
			SELECT `options_value`
			FROM `options`
			WHERE `options_key`='fb_app'
			LIMIT 1;
		");		
		
		if($fn_q_fbapi)
		{
			self::$fn_xtemplate_parse['assign'][] = array(
				'fb_app' => $fn_q_fbapi,
			);
			self::$fn_xtemplate_parse['parse'][] = "{$fn_args['stage_id']}.not_home.fb_api";
		}
		//fb api
		
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