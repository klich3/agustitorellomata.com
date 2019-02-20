<?php

/**
 * get_header function.
 * 
 * @access public
 * @param mixed $fn_args
 * @return void
 */
function get_header($fn_args)
{
	global $CONFIG, $fn_page_args, $st_lang, $fn_menu, $db, $too_login, $db, $lang_items;

	//session timer
	$ses_init_timastamp = '';
	
	$fn_page_title = str_replace(array('_', '-', '/'), array(' ', ' ', ' '), $fn_args['url']);
	$fn_page_title = (isset($fn_page_args['stage_title'])) ? $fn_page_args['stage_title'] : str_replace(array('-', '_'), array(' ', ' '), $fn_page_title);
	$fn_page_title = ($fn_page_title !== '') ? $fn_page_title : 'Login';
	$fn_page_title_out = ucwords($fn_page_title).' - '.$CONFIG['site']['sitetitle'];
	
	if(!preg_match('/home/', $fn_args['url']))
	{
		parse_str($_SERVER['QUERY_STRING'], $fn_url_attribs);
		$fn_co_sep = "{$CONFIG['site']['base_script']}{$fn_url_attribs['url']}";
	}else{
		$fn_co_sep = $CONFIG['site']['base_script'];
	}
	
	$tmpl_header = (preg_match('/admin.*/', $fn_args['url'])) ? 'admin_header' : 'header';
	
	//global var
	$CONFIG['site']['current_hash'] = $fn_co_sep;
	
	//set stage_id as header
	$fn_args['stage_id'] = $tmpl_header;
	$fn_page_args = $fn_args;
	
	//admin si hay subpage, lo usara para marcar el menu
	$fn_admin_hash = '';
	if(preg_match('/admin.*/', $fn_args['url'])) $fn_admin_hash = (!isset($fn_args['subpage'])) ? $fn_args['adminUrl'] : "admin-{$fn_args['subpage']}";
	
	$b = new XTemplate ("{$CONFIG['site']['template']}{$tmpl_header}.xhtml");
	
	$fn_h_hash = (preg_match('/(product_grid|pages_details)/', $fn_args['url'])) ? $fn_args['hash'] : $fn_args['url'];
	
	$fn_dir_prop = (isset($CONFIG['site']['dir']) && isJson($CONFIG['site']['dir'])) ? object_to_array(json_decode($CONFIG['site']['dir'])) : '';
	
	$b->assign($CONFIG['site']);
	$b->assign($fn_page_args);
	$b->assign($lang_items[$st_lang]);
	$b->assign(array(
		'debug' => $CONFIG['status']['debug'],
		'user_lang' => $st_lang,
		'site_title' => ucwords($fn_page_title).' - '.$CONFIG['site']['sitetitle'],
		'current_hash' => $fn_co_sep,
		
		'hash' => $fn_args['url'],
		'stage_hash' => ($tmpl_header == 'header') ? $fn_h_hash : $fn_args['adminUrl'],
		'header_stage_hash' => ($tmpl_header == 'header') ? $fn_h_hash : $fn_admin_hash,
		
		'cookie_lifeinit' => '',
		'cookie_lifeover' => '',
		
		'production_minimization' => ($CONFIG['status']['debug']) ? '' : '.min',
		'rand' => rand(),
		
		'languages' => (isset($CONFIG['site']['lang']) && isJson($CONFIG['site']['lang'])) ? $CONFIG['site']['lang'] : json_encode(array($CONFIG['site']['defaultLang'])),
		'header_popup_data' => (isset($CONFIG['site']['initial_page_header_popup_enabled']) && $CONFIG['site']['initial_page_header_popup_enabled'] == 1) ? json_encode(array('gid' => $CONFIG['site']['initial_page_header_popup_id'])) : 'false',
		
		'dir' => (isset($CONFIG['site']['dir'])) ? $fn_dir_prop[0] : $fn_dir_prop,
	));
	
	//widgets load template
	if(isset($fn_args['plugins_templates']) && sizeof($fn_args['plugins_templates']) !== 0)
	{
		foreach($fn_args['plugins_templates'] as $tpk => $tpv)
		{
			$fn_header_parser = false;
			$b->assign_file($tpk, "{$tpv}.xhtml");
			
			$fn_w_classname = "WIDGET_{$tpk}";
			if(class_exists("WIDGET_{$tpk}")) $WIDGET_CALSS = new $fn_w_classname($CONFIG, $db, $too_login);
			if(isset($WIDGET_CALSS) && method_exists($WIDGET_CALSS, 'wHeader')) $fn_header_parser = $WIDGET_CALSS->wHeader($fn_page_args);
			if(isset($fn_header_parser) && $fn_header_parser['assign'] && $fn_header_parser['parse']) pageBParser($b, $fn_header_parser);
		}
	}
	//widgets
	
	//logged header
	if($tmpl_header == 'admin_header')
	{
		if(class_exists('tooLogin'))
		{
			if($too_login->isLogged() == 200)
			{
				//user data from session
				$fn_user_data_array = object_to_array($_SESSION[$too_login->sessionName]);
				$b->assign($fn_user_data_array);
				
				//inicio time stamp session control
				$ses_init_timastamp = (isset($_SESSION) && isset($_SESSION[$too_login->sessionName]) && isset($_SESSION[$too_login->sessionName]->time_stamp)) ? $_SESSION[$too_login->sessionName]->time_stamp : '';	
				
				//session time counter ---------------------------------------------------------------------------------------------------------------------
				$s_t = ini_get("session.cookie_lifetime");
				$c_t = ini_get("session.gc_maxlifetime");
				
				//convert to h
				$fn_c_date_loc = ($ses_init_timastamp) ? date('H:i:s', $ses_init_timastamp) : 0; //mutamos a fecha normal
				$ses_final = date('H:i:s', strtotime("{$fn_c_date_loc} + {$s_t} seconds")); //sumammos los segundos de la cookie
				
				//ahora
				$fn_now = date('H:i:s');
				$fn_dif = abs(strtotime($ses_final) - strtotime($fn_now))*1000;
				//session time counter ---------------------------------------------------------------------------------------------------------------------
				
				$b->assign(array(
					'cookie_lifeinit' => $ses_init_timastamp,
					'cookie_lifeover' => ($ses_init_timastamp) ? $fn_dif : 0,
				));
								
				//show logged content
				$b->parse("{$tmpl_header}.logged");
				$b->parse("{$tmpl_header}.logged_js"); //js
			}
		}
	}
	//end logged header
	
	$b->parse($tmpl_header);
	$b->out($tmpl_header);
}

/**
 * get_footer function.
 * 
 * @access public
 * @param mixed $fn_args
 * @return void
 */
function get_footer($fn_args)
{
	global $CONFIG, $too_login, $db, $too_login, $st_lang, $lang_items;
	
	$tmpl_footer = (preg_match('/admin.*/', $fn_args['url'])) ? 'admin_footer' : 'footer';
	
	//set stage_id as footer
	$fn_args['stage_id'] = $tmpl_footer;
	$fn_page_args = $fn_args;
	
	$fn_dir_prop = (isset($CONFIG['site']['dir']) && isJson($CONFIG['site']['dir'])) ? object_to_array(json_decode($CONFIG['site']['dir'])) : '';
	
	$b = new XTemplate ("{$CONFIG['site']['template']}{$tmpl_footer}.xhtml");
	
	$b->assign($CONFIG['site']);
	$b->assign($fn_page_args);
	$b->assign($lang_items[$st_lang]);	
	$b->assign(array(
		'now_year' => date('Y'),
		'user_lang' => $st_lang,
		'dir' => (isset($CONFIG['site']['dir'])) ? $fn_dir_prop[0] : $fn_dir_prop,
	));
	
	//widgets load template
	if(isset($fn_args['plugins_templates']) && sizeof($fn_args['plugins_templates']) !== 0)
	{
		foreach($fn_args['plugins_templates'] as $tpk => $tpv)
		{
			$fn_footer_parser = false;
			$b->assign_file($tpk, "{$tpv}.xhtml");
			
			$fn_w_classname = "WIDGET_{$tpk}";
			if(class_exists("WIDGET_{$tpk}")) $WIDGET_CALSS = new $fn_w_classname($CONFIG, $db, $too_login);
			if(isset($WIDGET_CALSS) && method_exists($WIDGET_CALSS, 'wFooter')) $fn_footer_parser = $WIDGET_CALSS->wFooter($fn_page_args);
			if(isset($fn_footer_parser) && $fn_footer_parser['assign'] && $fn_footer_parser['parse']) pageBParser($b, $fn_footer_parser);
		}
	}
	//widgets
	
	if($tmpl_footer == 'admin_footer')
	{
		if(class_exists('tooLogin'))
		{
			if($too_login->isLogged() == 200) $b->parse("{$tmpl_footer}.logged");
		}
	}else{
		//normal "client" loggged
		if(class_exists('tooLogin') && $too_login->isLogged() == 200)
		{
			$b->parse("{$tmpl_footer}.logged");
		}else{
			$b->parse("{$tmpl_footer}.unlogged");
		}
	}
	
	$b->parse($tmpl_footer);
	$b->out($tmpl_footer);
}

/**
 * page function.
 * carga pagina y su contenido 
 *
 * @access public
 * @param mixed $fn_id
 * @param mixed $fn_args (default: null)
 * @return void
 */
function page($fn_id, $fn_args = null)
{
	global $CONFIG, $fn_page_args, $st_lang, $db, $too_login, $lang_items;
	
	$fn_args = ($fn_args == null) ? array() : $fn_args;
	
	//$fn_xtemplate_parse;
	$fn_xtemplate_parse = array(
		'assign' => array(),
		'parse' => array()
	);
	
	//widgets
	/*
		how to call in templates
		
		{FILE {widget_tabs}}
	*/
	$fn_plugin_template_files = array();
	$fn_plugins_folders = read_dir("plugins", array(".htaccess", "index.php", "index.html"));
	
	//first create array of plugins
	if($fn_plugins_folders)
	{
		foreach($fn_plugins_folders as $pk => $pv)
		{
			if(!preg_match('/admin/', $pv) && file_exists("{$CONFIG['site']['pluginspath']}{$pv}/{$pv}.xhtml")) $fn_plugin_template_files[$pv] = "{$CONFIG['site']['pluginspath']}{$pv}/{$pv}";
		}
	}
	
	$fn_args['url'] = (isset($fn_args['url'])) ? $fn_args['url'] : $fn_id;
	$fn_args['stage_id'] = $fn_id;
	$fn_args['plugins_templates'] = $fn_plugin_template_files;
	//widgets
	
	//añadirmos st_lang
	$fn_args['st_lang'] = $st_lang;
	$fn_page_args = $fn_args;
	
	//widget load php
	if(isset($fn_args['plugins_templates']) && sizeof($fn_args['plugins_templates']) !== 0)
	{
		foreach($fn_args['plugins_templates'] as $ptpk => $ptpv)
		{
			include_once("{$ptpv}.php");
		}
	}
	//widget load php
	
	//first load php view controller
	if(file_exists($CONFIG['site']['template'].$fn_id.'.php')) include("{$CONFIG['site']['template']}{$fn_id}.php");
	
	//construct stage view
	get_header($fn_args);

	//get hash
	$fn_hash = ($fn_args['url'] == '/') ? 'home' : $fn_args['url'];
	
	$b = new XTemplate ("{$CONFIG['site']['template']}{$fn_id}.xhtml");
	$b->assign($CONFIG['site']);
	$b->assign($lang_items[$st_lang]);
	$b->assign(array(
		'user_lang' => $st_lang,
		'hash' => $fn_hash,
		'current_hash' => $CONFIG['site']['current_hash'],
		'user_name' => (isset($CONFIG['user'])) ? $CONFIG['user']['user_name']:'',
		'dataNow' => date('Y-m-d'),
	));
	
	//widgets load template
	if(isset($fn_plugin_template_files) && sizeof($fn_plugin_template_files) !== 0)
	{
		$fn_page_args['stage_id'] = $fn_id;
		
		foreach($fn_plugin_template_files as $tpk => $tpv)
		{
			$fn_page_parser = false;
			$b->assign_file($tpk, "{$tpv}.xhtml");
			
			$fn_w_classname = "WIDGET_{$tpk}";
			if(class_exists("WIDGET_{$tpk}")) $WIDGET_CALSS = new $fn_w_classname($CONFIG, $db, $too_login);
			if(isset($WIDGET_CALSS) && method_exists($WIDGET_CALSS, 'wPage')) $fn_page_parser = $WIDGET_CALSS->wPage($fn_page_args);
			if(isset($fn_page_parser) && $fn_page_parser['assign'] && $fn_page_parser['parse']) pageBParser($b, $fn_page_parser);
		}
	}
	//widgets
	
	pageBParser($b, $fn_xtemplate_parse);
	
	$b->parse(strtolower($fn_id));
	$b->out(strtolower($fn_id));
	
	get_footer($fn_args);
}

/**
 * pageBParser function.
 * 
 * @access public
 * @param mixed $fn_xtemplate_parse
 * @return void
 */
function pageBParser($b = null, $fn_xtemplate_parse)
{
	if(!$b) return;
	
	if(isset($fn_xtemplate_parse) && (sizeof($fn_xtemplate_parse['assign']) > 0 || sizeof($fn_xtemplate_parse['parse']) > 0))
	{
		if(sizeof($fn_xtemplate_parse['parse']) >= 1)
		{
			foreach($fn_xtemplate_parse['assign'] as $ka => $va)
			{
				$fn_objToArry = object_to_array($va);
				$b->assign($fn_objToArry);
				$b->parse($fn_xtemplate_parse['parse'][$ka]);
			}
			//$b->parse(end($fn_xtemplate_parse['parse']));
		}else if(sizeof($fn_xtemplate_parse['parse']) == 0)
		{
			$fn_objToArry = object_to_array($fn_xtemplate_parse['assign'][0]);
			$b->assign($fn_objToArry);
			if(!empty($fn_xtemplate_parse['parse'])) $b->parse($fn_xtemplate_parse['parse'][0]);
		}
	}
}

?>