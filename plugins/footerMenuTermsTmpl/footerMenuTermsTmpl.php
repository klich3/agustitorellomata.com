<?php

class WIDGET_footerMenuTermsTmpl {
	
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
		if(empty($fn_args['url'])) return;
		
		//hash de paginas
		$fn_arg_url = (preg_match('/(pages_details)/', $fn_args['url'])) ? $fn_args['hash'] : $fn_args['url'];
		
		//prevent exec on admin stage
		if(preg_match('/admin/', $fn_arg_url)) return;
		
		$fn_hash = (isset($fn_args['url']) && !preg_match('/(pages_details)/', $fn_args['url'])) ? $fn_args['url'] : $fn_args['hash'];
		
		$fn_q_menus = $this->db->FetchArray("
			SELECT m.*
			FROM `menus_types` t 
			LEFT JOIN `menus` m ON(m.`m_type`=t.`id`)
			WHERE t.`id`=:id
			LIMIT 1;
		", array(
			'id' => '3',
		));
		
		$fn_count_w = 0;
		
		if($fn_q_menus)
		{
			$fn_q_meta = $this->db->FetchAll("
				SELECT *
				FROM `menus_meta`
				WHERE `m_id`=:i
			", array(
				'i' => $fn_q_menus['id'],
			));
			
			$fn_q_structures = $this->db->FetchAll("
				SELECT *
				FROM `menus_structure`
				WHERE `m_id`=:i
				ORDER BY `order` ASC
			", array(
				'i' => $fn_q_menus['id'],
			));
			
			//menus rows
			if($fn_q_structures)
			{
				$fn_menu_tree = self::object_to_array($fn_q_structures);
				
				$fn_totla_mv = sizeof($fn_menu_tree);
				
				foreach($fn_menu_tree as $mk => $mv)
				{
					$fn_mv_page_id = (isset($mv['p_id'])) ? $mv['p_id'] : false;
					
					$mv['p_title'] = (isset($mv['title']) && !empty($mv['title'])) ? $mv['title'] : '';
					$mv['p_url'] = (isset($mv['url']) && !empty($mv['url'])) ? $mv['url'] : '';
					
					//saltamos si no esta activo
					if(!$mv['active']) continue;
					
					if($fn_mv_page_id)
					{
						$fn_p_data = $this->db->FetchArray("
							SELECT *
							FROM `pages`
							WHERE `active`='1'
							AND `id`=:i
							LIMIT 1;
						", array(
							'i' => $fn_mv_page_id,
						));
						
						if(isset($mv['title']) && empty($mv['title'])) $mv['title'] = $fn_p_data['obj_title'];
						if(isset($mv['url']) && empty($mv['url']))
						{
							$mv['url'] = "{$this->CONFIG['site']['base_script']}{$fn_args['st_lang']}/{$fn_p_data['obj_hash']}";
						}else{
							$mv['url'] = "javascript:void(0);";
						}
					}
					
					$fn_count_w++;
					
					self::$fn_xtemplate_parse['assign'][] = $mv;
					self::$fn_xtemplate_parse['parse'][] = "{$fn_args['stage_id']}.footerMenuTermsTmpl.row_item";
					//anti loop
					if($mk > ($fn_totla_mv-1) ) break;
				}
			}
		}
		
		self::$fn_xtemplate_parse['assign'][] = array(
			'count_g' => (!preg_match('/(0|8|9)/', $fn_count_w)) ? "g gw-1-{$fn_count_w}" : "mr-4",
		);
		self::$fn_xtemplate_parse['parse'][] = "{$fn_args['stage_id']}.footerMenuTermsTmpl";
		
		return self::$fn_xtemplate_parse;
	}
	
	/**
	 * object_to_array function.
	 * 
	 * @access private
	 * @param mixed $data
	 * @return void
	 */
	private function object_to_array($data)
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
}

?>