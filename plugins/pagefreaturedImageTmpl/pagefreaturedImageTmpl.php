<?php

class WIDGET_pagefreaturedImageTmpl {
	
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
		if(empty($fn_args['url'])) return;
		
		//hash de paginas
		$fn_arg_url = (preg_match('/(pages_details|_grid)/', $fn_args['url'])) ? $fn_args['hash'] : $fn_args['url'];
		$fn_arg_url = str_replace('_', '-', $fn_arg_url);
		
		//prevent exec on admin stage
		if(preg_match('/admin/', $fn_arg_url)) return;
		
		$fn_q_freatured = $this->db->FetchArray("
			SELECT p.`obj_title` as 'stage_title', m.`meta_value` as 'stage_freatured_image', mt.`meta_value` as 'show_title', mtt.`meta_value` as 'stage_freatured_custom_title'
			FROM `pages` p
			LEFT JOIN `pages_meta` m ON(m.`p_id`=p.`id`)
			LEFT JOIN `pages_meta` mt ON(mt.`p_id`=p.`id`)
			LEFT JOIN `pages_meta` mtt ON(mtt.`p_id`=p.`id`)
			WHERE p.`obj_hash`=:h
			AND p.`lang`=:l
			AND m.`meta_key`='seo_freaturedimage'
			AND mt.`meta_key`='seo_checktitleonfreaturedimage'
			AND mtt.`meta_key`='seo_customtitleonfreaturedimage'
			LIMIT 1;
		", array(
			'h' => $fn_arg_url,
			'l' => $fn_args['st_lang']
		));
		
		if(isset($fn_q_freatured['stage_freatured_image']) && !empty($fn_q_freatured['stage_freatured_image']))
		{
			$fn_reg = str_replace('/', '\/', $this->CONFIG['site']['base_script']);
			$fn_reg = (string)"/({$fn_reg})/";
			$fn_q_freatured['stage_freatured_image'] = (preg_match($fn_reg, $fn_q_freatured['stage_freatured_image'])) ? $fn_q_freatured['stage_freatured_image'] : $this->CONFIG['site']['base_script'].$fn_q_freatured['stage_freatured_image'];
			
			if(isset($fn_q_freatured['show_title']) && $fn_q_freatured['show_title'])
			{
				self::$fn_xtemplate_parse['assign'][] = array(
					'stage_title' => (isset($fn_q_freatured['stage_freatured_custom_title']) && !empty($fn_q_freatured['stage_freatured_custom_title'])) ? $fn_q_freatured['stage_freatured_custom_title'] : $fn_q_freatured['stage_title'],
				);
				self::$fn_xtemplate_parse['parse'][] = "{$fn_args['stage_id']}.pagefreaturedImageTmpl.headerLine.title";
			}
			
			self::$fn_xtemplate_parse['assign'][] = $fn_q_freatured;
			self::$fn_xtemplate_parse['parse'][] = "{$fn_args['stage_id']}.pagefreaturedImageTmpl.headerLine";
			
			self::$fn_xtemplate_parse['assign'][] = $fn_q_freatured;
			self::$fn_xtemplate_parse['parse'][] = "{$fn_args['stage_id']}.pagefreaturedImageTmpl";
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
		if(empty($fn_args['url'])) return;
		
		//hash de paginas
		$fn_arg_url = (preg_match('/(pages_details|product_grid)/', $fn_args['url'])) ? $fn_args['hash'] : $fn_args['url'];
		$fn_arg_url = str_replace('_', '-', $fn_arg_url);
		
		//prevent exec on admin stage
		if(preg_match('/admin/', $fn_arg_url)) return;
		
		$fn_q_freatured = $this->db->FetchArray("
			SELECT p.`obj_title` as 'stage_title', m.`meta_value` as 'stage_freatured_image', mt.`meta_value` as 'show_title'
			FROM `pages` p
			LEFT JOIN `pages_meta` m ON(m.`p_id`=p.`id`)
			LEFT JOIN `pages_meta` mt ON(mt.`p_id`=p.`id`)
			WHERE p.`obj_hash`=:h
			AND p.`lang`=:l
			AND m.`meta_key`='seo_freaturedimage'
			AND mt.`meta_key`='seo_checktitleonfreaturedimage'
			LIMIT 1;
		", array(
			'h' => $fn_arg_url,
			'l' => $fn_args['st_lang'],
		));
		
		if(isset($fn_q_freatured['stage_freatured_image']))
		{
			if($fn_q_freatured['stage_freatured_image'] !== '') return;
			
			self::$fn_xtemplate_parse['assign'][] = '';
			self::$fn_xtemplate_parse['parse'][] = "{$fn_args['stage_id']}.pagefreaturedImageTmpl.menuSpacer";

			self::$fn_xtemplate_parse['assign'][] = '';
			self::$fn_xtemplate_parse['parse'][] = "{$fn_args['stage_id']}.pagefreaturedImageTmpl";
		}
			
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