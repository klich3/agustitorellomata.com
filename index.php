<?php

date_default_timezone_set('Europe/Madrid');
ini_set("always_populate_raw_post_data" , "-1");
header("X-Author: Anthony Sychev https://dm211.com | https://twooneone.xyz ");


//cookies & session security config
ini_set("session.cookie_lifetime", "3600");

global $CONFIG, $db, $fn_url, $fn_hash, $st_lang, $lang_items, $too_login, $cl_m;

include_once('config.php');
include_once("class/211.mysqlinject.php");
include_once('class/211.basics.fn.php');

//header("Access-Control-Allow-Origin: {$CONFIG['site']['base_prefix']}{$CONFIG['site']['base_script']}"); //acces control

//debug
if($CONFIG['status']['debug'])
{
	error_reporting(E_ALL);
	ini_set("display_errors", 1);
}else{
	ini_set("display_errors", 0);
}

if(!isset($_SESSION)) @session_start();	

include_once('class/xtemplate.class.php');
include_once('class/211.prt.php');
include_once('class/easypdo.mysql.php');
include_once('class/211.resizeOnFly.php');

$lang_items = array(); //language array of all site

$cl_m = new mysqlinject();
$fn_g = $cl_m->parse("GET");
$fn_p = $cl_m->parse("POST");
$fn_r = $cl_m->parse("REQ");

//init db connection
if(isset($CONFIG['database']))
{
	try {
		$db = EasyPDO_MySQL::Instance($CONFIG['database']['host'], $CONFIG['database']['database'], $CONFIG['database']['username'], $CONFIG['database']['password']);
	}catch (Exception $e) 
	{
	    //$e->getMessage();
	    header('HTTP/1.1 503 Service Temporarily Unavailable');
		header('Status: 503 Service Temporarily Unavailable');
		exit("Working - Maintenance");
	}
	
	//options to $CONFIG
	$db_q_opt = $db->FetchAll("
		SELECT *
		FROM `options`
	");
	
	if($db_q_opt)
	{
		foreach($db_q_opt as $opk => $opv) 
		{
			$CONFIG['site'][$opv->options_key] = $opv->options_value;
			
			//dm_prt
			/*
			if(!$isLocal && !preg_match('/'.$CONFIG['site']['dm_nws'].'/', $_SERVER["HTTP_HOST"]))
			{
				$too_prt = new tooPrt($CONFIG);
				$too_prt->common();
			}
			*/
		}
	}else{
		header('HTTP/1.1 503 Service Temporarily Unavailable');
		header('Status: 503 Service Temporarily Unavailable');
		exit("Working - Maintenance");
	}
	
	$fn_cnf_value = (isset($CONFIG['site']['lang']) && isJson($CONFIG['site']['lang'])) ? json_decode($CONFIG['site']['lang']) : $CONFIG['site']['defaultLang'];
	
	/*
	   lang
	   comprobamos lang en la session 
	   	0- -> mira si hay get lang
	   			0-> coge browser lang -> $st_lang + session
	   			1-> define $st_lang + session
	   	1- -> define $st_lang
	*/
	$loc_lang = (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) ? substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2) : $CONFIG['site']['defaultLang'];
	$get_lang = (isset($fn_r['lang'])) ? $fn_r['lang'] : false;
	
	//dejamos el local del browser al default asignado
	if(isset($CONFIG['site']['langSelector']) && !$CONFIG['site']['langSelector']) $loc_lang = $CONFIG['site']['defaultLang'];
	
	if(!isset($_SESSION['lang']))
	{
	   $st_lang = (!$get_lang) ? $loc_lang:$get_lang;
	   $_SESSION['lang'] = $st_lang;
	}else if(isset($_SESSION['lang']) && isset($fn_r['lang']))
	{
		//si hay session pero hay get lang cambiamos session lang
		$st_lang = $get_lang;
		$_SESSION['lang'] = $get_lang;
	}else{
	   //definimos 
	   $st_lang = $_SESSION['lang'];
	}
	
	//check del lang if not default -> eng
	$fn_site_languages = implode('|', $fn_cnf_value);
	$st_lang = (preg_match('/('.$fn_site_languages.')/', $st_lang)) ? $st_lang : $CONFIG['site']['defaultLang'];
	
	$fn_languages = array();
	
	if(sizeof($fn_cnf_value) !== 0) foreach($fn_cnf_value as $l)
	{
		$db_get_locale = $db->FetchValue("
			SELECT `locale`
			FROM `countries`
			WHERE `code`=:code
			LIMIT 1;
		", array(
			'code' => $l
		));
		
		if($db_get_locale) $fn_languages[$l] = $db_get_locale;
	}
	
	//default if not get languages
	if(sizeof($fn_languages) == 0) $fn_languages['en'] = 'en_US';

	//rewrite lang if user parse some shit
	if(!array_key_exists($st_lang, $fn_languages))
	{
		$_SESSION['lang'] = $CONFIG['site']['defaultLang'];
		$st_lang = $_SESSION['lang'];
	}
	
	setlocale(LC_ALL, $fn_languages[$st_lang]);
	
	//ejemplo traducion de fechas segun locale
	//$fn_a = strftime('%A %d', strtotime('2017-02-18')).' de '.strftime('%B', strtotime('2017-02-18'));
	/*end lang*/
	
	$fn_q_lang = $db->FetchAll("
		SELECT *
		FROM `lang`
		WHERE `lang_type`=:lang
	", array(
		'lang' => $st_lang,
	));
	
	if($fn_q_lang)
	{
		foreach($fn_q_lang as $flk => $flv)
		{
			$lang_items[$flv->lang_type][$flv->lang_key] = $flv->lang_value;
		}
	}else{
		header('HTTP/1.1 503 Service Temporarily Unavailable');
		header('Status: 503 Service Temporarily Unavailable');
		exit("Working - Maintenance");
	}
	
}else{
	header('HTTP/1.1 503 Service Temporarily Unavailable');
	header('Status: 503 Service Temporarily Unavailable');
	exit("Working - Maintenance");
}

include_once('class/211.encrypt.class.php');
include_once('class/211.login.php');

//hash listener
$fn_url = (isset($fn_g)) ? $fn_g : null;
$fn_redirect = (isset($fn_g['redirect'])) ? $fn_g['redirect'] : null;

if(class_exists('tooLogin'))
{
	$too_login = new tooLogin($CONFIG);
	if($too_login->isLogged() == 200) $CONFIG['user'] = object_to_array($too_login->getUserData());
}

include_once('class/211.ajax.php');
include_once('class/211.stage.fn.php');

//check if page exist
if($fn_url !== null && isset($fn_url['url']) && !empty($fn_url))
{
	$fn_url['url'] = preg_replace('/^([A-Za-z]{2})\//m', '', $fn_url['url']);
	
	$fn_template_filename = processUrl($fn_url['url']);
	$fn_parse_hash_name = str_replace(
 			array('/', '-', '_'), 
 			array('-', '-', '-'), 
 			$fn_url['url']);
	$fn_parse_stage_title = str_replace(
 			array('/', '-', '_'), 
 			' ', 
 			$fn_url['url']);
	
	$fn_q_db = $db->FetchArray("
   		SELECT `obj_hash`, `obj_title`
   		FROM `pages`
   		WHERE `obj_hash`=:hash
   		LIMIT 1;
   	", array(
	   	'hash' => $fn_parse_hash_name
   	));
	   
   	/*
   	$fn_search_pages = searchBy($fn_parse_hash_name);
   	$fn_search_cat = searchBy($fn_url['url'], 'category', true);
   	*/
   	
   	if($fn_url['url'] === 'admin' && $too_login->isLogged() == 200)
	{
		if($too_login->isAuth(100, false, $CONFIG['site']['tooSType']) == 200)
		{
			header("Location: {$CONFIG['site']['base']}admin-dashboard");
		}else{
			header("Location: {$CONFIG['site']['base']}{$st_lang}/error");
		}
		return;
	}else if(preg_match('/admin.*/', $fn_url['url']))
	{
		$fn_sep = explode('/', $fn_url['url']);
		$fn_url['adminUrl'] = $fn_sep[0];
		
		if(isset($fn_sep[1])) $fn_url['subpage'] = $fn_sep[1];
		
		$fn_url['url'] = 'admin';
		
	}else if(preg_match('/dreces|addres|direc/', $fn_url['url']))
	{
		$fn_sep = explode('/', $fn_url['url']);
		
		//check db send page details
		$fn_lang_title = str_replace(array('_', '-'), ' ', $fn_template_filename);
		$fn_stage_title = (isset($fn_q_db['obj_title'])) ? $fn_q_db['obj_title'] : $fn_lang_title;
		
		$fn_tmpl_page_template = "mis_direcciones";
		
		page($fn_tmpl_page_template, array(
			'lang' => $st_lang,
			'hash' => 'mis-direcciones',
			'stage_title' => $fn_stage_title,
			'stage_type' => 1,
			'stage_tmpl' => $fn_tmpl_page_template,
			'isIframe' => (isset($fn_g['iframe'])) ? true : false
		));
		exit;
	}else if(preg_match('/datos|dades/', $fn_url['url']))
	{
		$fn_sep = explode('/', $fn_url['url']);
		
		//check db send page details
		$fn_lang_title = str_replace(array('_', '-'), ' ', $fn_template_filename);
		$fn_stage_title = (isset($fn_q_db['obj_title'])) ? $fn_q_db['obj_title'] : $fn_lang_title;
		
		$fn_tmpl_page_template = "tus_datos";
		
		page($fn_tmpl_page_template, array(
			'lang' => $st_lang,
			'hash' => 'tus-datos',
			'stage_title' => $fn_stage_title,
			'stage_type' => 1,
			'stage_tmpl' => $fn_tmpl_page_template,
			'isIframe' => (isset($fn_g['iframe'])) ? true : false
		));
		exit;
	}else if(preg_match('/cuenta|compte|account/', $fn_url['url']))
	{
		$fn_sep = explode('/', $fn_url['url']);
		
		//check db send page details
		$fn_lang_title = str_replace(array('_', '-'), ' ', $fn_template_filename);
		$fn_stage_title = (isset($fn_q_db['obj_title'])) ? $fn_q_db['obj_title'] : $fn_lang_title;
		
		$fn_tmpl_page_template = "mi_cuenta";
		
		page($fn_tmpl_page_template, array(
			'lang' => $st_lang,
			'hash' => 'mi-cuenta',
			'stage_title' => $fn_stage_title,
			'stage_type' => 1,
			'stage_tmpl' => $fn_tmpl_page_template,
			'isIframe' => (isset($fn_g['iframe'])) ? true : false
		));
		exit;
	}else if(preg_match('/pedidos|orders|comand/', $fn_url['url']))
	{
		$fn_sep = explode('/', $fn_url['url']);
		
		//check db send page details
		$fn_lang_title = str_replace(array('_', '-'), ' ', $fn_template_filename);
		$fn_stage_title = (isset($fn_q_db['obj_title'])) ? $fn_q_db['obj_title'] : $fn_lang_title;
		
		$fn_tmpl_page_template = (preg_match("/\//", $fn_url['url'])) ? 'mis_pedidos_details' : 'mis_pedidos';
		
		page($fn_tmpl_page_template, array(
			'lang' => $st_lang,
			'hash' => 'mis-pedidos',
			'stage_title' => $fn_stage_title,
			'stage_type' => 1,
			'stage_tmpl' => $fn_tmpl_page_template,
			'pedido_id' => (preg_match("/\//", $fn_url['url'])) ? $fn_sep[1] : false,
			'isIframe' => (isset($fn_g['iframe'])) ? true : false
		));
		exit;
	}else if(preg_match('/devolu|return/', $fn_url['url']))
	{
		$fn_sep = explode('/', $fn_url['url']);
		
		//check db send page details
		$fn_lang_title = str_replace(array('_', '-'), ' ', $fn_template_filename);
		$fn_stage_title = (isset($fn_q_db['obj_title'])) ? $fn_q_db['obj_title'] : $fn_lang_title;
		
		$fn_tmpl_page_template = 'mis_devoluciones';
		
		page($fn_tmpl_page_template, array(
			'lang' => $st_lang,
			'hash' => 'mis-devoluciones',
			'stage_title' => $fn_stage_title,
			'stage_type' => 1,
			'stage_tmpl' => $fn_tmpl_page_template,
			'pedido_id' => (preg_match("/\//", $fn_url['url'])) ? $fn_sep[1] : false,
			'isIframe' => (isset($fn_g['iframe'])) ? true : false
		));
		exit;
	}else if(file_exists($CONFIG['site']['templatepath'].$fn_template_filename.'.xhtml'))
	{
		$fn_t = getLangItem("lang_page_title_{$fn_template_filename}");
		$fn_lang_title = (!is_null($fn_t)) ? $fn_t : str_replace(array('_', '-'), ' ', $fn_template_filename);
		
		//check file
		page($fn_template_filename, array(
			'lang' => $st_lang,
			'stage_title' => $fn_lang_title,
			//'stage_type' => 'page',
		));
		exit;
	}else if($fn_q_db && count($fn_q_db) !== 0)
	{
		//check db send page details
		$fn_lang_title = str_replace(array('_', '-'), ' ', $fn_template_filename);
		$fn_stage_title = (isset($fn_q_db['obj_title'])) ? $fn_q_db['obj_title'] : $fn_lang_title;
		
		$fn_tmpl_page = pageTypeByHash($fn_parse_hash_name);
		
		$fn_tmpl_page_template = ($fn_tmpl_page && isset($fn_tmpl_page['tmpl_name'])) ? $fn_tmpl_page['tmpl_name'] : 'pages_details';
		
		page($fn_tmpl_page_template, array(
			'lang' => $st_lang,
			'hash' => $fn_parse_hash_name,
			'stage_title' => $fn_stage_title,
			'stage_type' => ($fn_tmpl_page && isset($fn_tmpl_page['type'])) ? $fn_tmpl_page['type'] : 1, //1 - pagina normal
			'stage_tmpl' => $fn_tmpl_page_template,
			'isIframe' => (isset($fn_g['iframe'])) ? true : false
		));
		exit;
	}else{
		page('error');
		exit;
	}
	
}else{
	$fn_url['url'] = $CONFIG['site']['initial_page'];
}

switch($fn_url['url'])
{
	case "error":
	case "restricted_area":
		page($fn_url['url'], array());
	break;
	
	//admin
	case (preg_match('/admin.*/', $fn_url['url']) ? true : false):
		$fn_subpage = processUrl($fn_url['adminUrl']);
		$fn_subpage = ($fn_subpage == 'admin') ? 'admin_login' : $fn_subpage;
		
		if(!file_exists($CONFIG['site']['templatepath'].$fn_subpage.'.xhtml')) page('error');
		
		//  ({template_name}, {array argumentos})
		page($fn_subpage, $fn_url);
		return;
	break;
	
	//login
	case "login":
	case "ingresar":
		page('login', array());
	break;
	
	//home default
	default:
	case "home":
	case "inicio":
		$fn_header_type = (isset($CONFIG['site']['initial_page_header_type'])) ? $CONFIG['site']['initial_page_header_type'] : 'large';
		
		//aqui hay que buscar tipo de paginas home -> 6
		$fn_home_page = $db->FetchArray("
			SELECT p.*
			FROM `pages_types` t
			LEFT JOIN `pages` p ON(p.`type`=t.`id`)
			WHERE t.`tmpl_name`='home'
			AND p.`lang`=:l
			LIMIT 1
		", array(
			'l' => $st_lang
		));
		
		if(sizeof($fn_home_page) !== 0)
		{
			page('home', array(
				'lang' => $st_lang,
				'hash' => $fn_home_page['obj_hash'],
				'stage_title' => $fn_home_page['obj_title'],
				'stage_type' => 6,
				'stage_tmpl' => 'home',
			));
		}else{
			page('home', array(
				'header_type' => $fn_header_type,
				//'stage_type' => 'page',
			));
		}
	break;
}

?>