<?php

/*
	Author: Anthony Sychev
	date: 20/02/2018
	ver: 2002181309
	
	Implementamos APC cache apache / php module + mysql
	
	APC: https://pecl.php.net/package/apcu_bc
	
	return status:
	 200 - ok
	 301 - user not autorized
	 302 - not found
	 303 - banned
	 400 - restricted
 */
class tooLogin {

	public 	$sessionName;

	public	$secured = false;

	//queries pdo
	private $db;

	public  $redirLoggedin = 'admin/dashboard';
	public  $redirLoggedout = 'admin/login';
	public  $redirRestrictedArea = 'admin/restricted-area';
	
	private $fn_ip_loging;
	private $fn_user_loging;
	private $fn_bantime_loging;
	private $fn_banattemp_loging;
	
	private $fn_now;
	private $fn_now_date;
	
	private $use_apc = false;
	
	private $cfg_hash;
	
	/**
	 * __construct function.
	 * 
	 * @access public
	 * @param mixed $cfg
	 * @return void
	 */
	public function __construct($cfg)
	{
		$this->sessionName = "{$cfg['site']['dm_nws']}~login_session";

		//times
		$this->fn_now = time();
		$this->fn_now_date = date('Y-m-d H:i:s');
		
		//redirects
		$this->redirLoggedin = "{$cfg['site']['base_script']}{$cfg['site']['lang']}/admin/dashboard";
		$this->redirLoggedout = "{$cfg['site']['base_script']}{$cfg['site']['lang']}/admin/login";
		$this->redirRestrictedArea = "{$cfg['site']['base_script']}{$cfg['site']['lang']}/admin/restricted-area";
		
		//check if apache get so APC cache
		$fn_php_extensions = get_loaded_extensions();
		$this->use_apc = (in_array('apcu', $fn_php_extensions)) ? true : false;
		
		if(!$this->use_apc) header('Debug: 68');
		
		if(!class_exists('tooSCrypt'))
		{
			header('Debug: 68');
			
			header('HTTP/1.1 503 Service Temporarily Unavailable');
			header('Status: 503 Service Temporarily Unavailable');
			//header('Debug: 45');
			
			exit(json_encode(array(
				'status' => 400,
				'message' => 'Working - Maintenance.',
			)));
		}
		
		//hash
		$this->cfg_hash = $cfg['site']['tooSHash'];
		
		//init db access
		try {
			$this->db = EasyPDO_MySQL::Instance($cfg['database']['host'], $cfg['database']['database'], $cfg['database']['username'], $cfg['database']['password']);
		} catch (Exception $e) 
		{
			header('HTTP/1.1 503 Service Temporarily Unavailable');
			header('Status: 503 Service Temporarily Unavailable');
			//header('Debug: 45');
			
			exit(json_encode(array(
				'status' => 400,
				'message' => 'Working - Maintenance.',
			)));
		}
		
		//by default init session
		@session_start();
		
		/*
		$currentCookieParams = session_get_cookie_params();  
		$sidvalue = session_id();  
		setcookie(  
		    'PHPSESSID',//name  
		    $sidvalue,//value  
		    0,//expires at end of session  
		    $currentCookieParams['path'],//path  
		    $currentCookieParams['domain'],//domain  
		    true //secure  
		);
		*/

		//on init check login status
		self::_sessionCheck();
	}
	
	/**
	 * __destruct function.
	 * quitamos el rastro
	 *
	 * @access public
	 * @return void
	 */
	public function __destruct()
	{
		$this->fn_now = null;
		$this->fn_now_date = null;
	}
	
	/**
	 * getUserData function.
	 * muestra informacion de usuario en la session
	 *
	 * @access public
	 * @return void
	 */
	public function getUserData()
	{
		if(isset($_SESSION[$this->sessionName])) return $_SESSION[$this->sessionName];
	}
	
	/**
	 * login function.
	 * logea a un usuario 
	 *
	 * @access public
	 * @param mixed $fn_user
	 * @param mixed $fn_pass
	 * @param bool $fn_redirect (default: true)
	 * @return void
	 */
	public function login($fn_user, $fn_pass, $fn_redirect = true)
	{
		$fn_check_ban = self::checkBan($fn_user);
		
		if($fn_check_ban)
		{
			//baneado
			if($fn_redirect)
			{
				header("HTTP/1.1 429 Too Many Requests");
				//header('Debug: 122');
				
				header("Location: {$this->redirRestrictedArea}");
			}else{
				return 303;
			}
		}else{
			$fn_admin_or_client = (!preg_match('/\@/', $fn_user)) ? 'user_name':'user_email';
			
			//check unser & pass
			/*
				SELECT u.*, us.`status_value`, m.`meta_value` AS 'user_level'
	            FROM `users` u
				INNER JOIN `users_status` us ON(u.`user_status` = us.`user_status`)
				INNER JOIN `users_meta` m ON(m.`user_id` = u.`ID`)
				WHERE `user_name`=:us
				AND `user_pass`=MD5(:ps)
				AND m.`meta_key`='user_level'
	            GROUP BY u.`ID`
	            LIMIT 1;
			var_dump(self::genPass($fn_user, $fn_pass));
			*/
			
			$fn_q = $this->db->FetchObject("
				SELECT u.*, us.`status_value`, m.`meta_value` AS 'user_level'
	            FROM `users` u
				INNER JOIN `users_status` us ON(u.`user_status` = us.`user_status`)
				INNER JOIN `users_meta` m ON(m.`user_id` = u.`ID`)
				INNER JOIN `users_meta` ma ON(ma.`user_id` = u.`ID`)
				WHERE `{$fn_admin_or_client}`=:us
				AND `user_pass`=:ps
				AND m.`meta_key`='user_level'
				AND ma.`meta_key`='user_access'
				AND ma.`meta_value`='1'
	            GROUP BY u.`ID`
	            LIMIT 1;
			", array(
				'us' => $fn_user,
				'ps' => self::genPass($fn_user, $fn_pass),
			));
			
			//bject(stdClass)#6 (5) {
			//	["ID"]=> string(1) "1"
			//	["user_name"]=> string(3) "211"
			//	["user_email"]=> string(17) "xx@xx.com"
			//	["user_registred"]=> string(19) "2013-02-18 19:31:21"
			//	["user_status"]=> string(1) "1"
			//}
			
			//clean private data
			unset($fn_q->user_pass);
			unset($fn_q->user_activation_key);
			unset($fn_q->user_registred);
			
			if($fn_q)
			{
				//check user status
				if($fn_q->user_status !== "1")
				{
					//set redirect url
					unset($_SESSION[$this->sessionName]);
					
					if($fn_redirect) header("Location: {$this->redirRestrictedArea}");
					
					return 301;
				}
				
				//update login modal
				$fn_q->time_stamp = time();
				
				//assign user data
				$_SESSION[$this->sessionName] = $fn_q;
				
				if($fn_redirect) header("Location: {$this->redirLoggedin}");
				
				//unban
				$fn_user_ip = self::_getClientIP();
				self::unBan($fn_user, $fn_user_ip);
				
				return 200; //ok
			}else{
				return 302; //user not found
			}
		}
	}
	
	/**
	 * logout function.
	 *
	 * @access public
	 * @param bool $fn_redirect (default: false)
	 * @return void
	 */
	public function logout($fn_redirect = false)
	{
		unset($_SESSION[$this->sessionName]);

		if($fn_redirect == true) header("Location: {$this->redirLoggedout}");
		
		return 200;
	}
	
	/**
	 * isLogged function.
	 *  chequea si estas logueado
	 *
	 * @access public
	 * @return void
	 */
	public function isLogged()
	{
		if(isset($_SESSION[$this->sessionName])) return 200;

		return 400;
	}
	
	/**
	 * isAuth function.
	 * comprueba si tienes el acceso suficiente para acceder a una parte de codigo 
	 *
	 * @access public
	 * @param int $fn_level (default: 0)
	 * @param bool $fn_redirect (default: true)
	 * @param bool $fn_method (default: 2) 1 - simple | 2 - completo con niveles
	 * @return void
	 */
	public function isAuth($fn_level = 0, $fn_redirect = true, $fn_method = 2)
	{
		if(isset($_SESSION[$this->sessionName]))
		{
			
			if(preg_match('/(1|2)/', $fn_method))
			{
				$fn_data = $_SESSION[$this->sessionName];
				
				//--------------- SIMPLE CHECK
				
				//si solo es comprobar nivel es una app generica GLOBAL
				if($fn_level !== 0)
				{
					if($fn_data->user_level >= $fn_level)
					{
						return 200;
					}else{
						if($fn_redirect) header("Location: {$this->redirRestrictedArea}");
						return 301;
					}
				}
			}
		}else{
			header("Location: {$this->redirRestrictedArea}");
			return 301;
		}
	}
	
	/**
	 * _sessionCheck function.
	 * 
	 * @access private
	 * @return void
	 */
	private function _sessionCheck()
	{
		if(!isset($_SESSION[$this->sessionName]) && $this->secured == true) self::logout(true);
		return 200;
	}
	
	/**
	 * checkBan function.
	 * chekeamos el si hay ban 
	 *
	 * @access private
	 * @param mixed $fn_user
	 * @return true = ban / false = not ban
	 */
	private function checkBan($fn_user)
	{
		$fn_user_ip = self::_getClientIP();
		
		$fn_result_acp = false; //is banned by acp
		$fn_result_sql = false; //is banned by sql
		
		//create apn variables
		$this->fn_ip_loging = "{$_SERVER['SERVER_NAME']}~attemptbyip:{$fn_user_ip}";
		$this->fn_user_loging = "{$_SERVER['SERVER_NAME']}~atemptsbyuser:{$fn_user}";
		$this->fn_banattemp_loging = "{$_SERVER['SERVER_NAME']}~login-blocked:".md5("{$fn_user_ip}{$fn_user}");
		$this->fn_bantime_loging = "{$_SERVER['SERVER_NAME']}~login-blocked-time:".md5("{$fn_user_ip}{$fn_user}");
		
		/*
		//debug
		apcu_delete($this->fn_ip_loging);
		apcu_delete($this->fn_user_loging);
		apcu_delete($this->fn_bantime_loging);
		apcu_delete($this->fn_banattemp_loging);
		*/
		
		if($this->use_apc)
		{
			//check by APC Cache -----------------
			$fn_apn_i = (!apcu_exists($this->fn_ip_loging)) ? 0 : (int)apcu_fetch($this->fn_ip_loging);
			$fn_apn_u = (!apcu_exists($this->fn_user_loging)) ? 0 : (int)apcu_fetch($this->fn_user_loging);
			
			$fn_apn_a = (!apcu_exists($this->fn_banattemp_loging)) ? 0 : (int)apcu_fetch($this->fn_banattemp_loging);
			$fn_apn_b = (!apcu_exists($this->fn_bantime_loging)) ? false : apcu_fetch($this->fn_bantime_loging); //timestamp ban time
			
			//print_r("ip:{$fn_apn_i} - user:{$fn_apn_u} - ban timestamp:{$fn_apn_b} - ");
			
			//unban check
			if($fn_apn_b && $fn_apn_a !== 0)
			{
				//check time apn
				if(strtotime($this->fn_now_date) > $fn_apn_b)
				{
					//unban
					self::unBan($fn_user, $fn_user_ip);
					
					return false;
				}
			}else{
				$fn_result_acp = true;
			}
			
			//many tries
			if($fn_apn_i >= 4 || $fn_apn_u >= 4) self::banTime($this->fn_banattemp_loging, $fn_user);
				
			//add items to cache
			apcu_store($this->fn_ip_loging, $fn_apn_i+1, 86400);
			apcu_store($this->fn_user_loging, $fn_apn_u+1, 86400);
		}
		
		//check by Mysql -----------------
		$fn_q_check_ip = $this->db->FetchObject("
			SELECT *
			FROM `users_login_ban`
			WHERE `u_ip`=:ip
			OR `u_name`=:u
			AND `isBanned`='1'
			LIMIT 1;
		", array(
			'ip' => $fn_user_ip,
			'u' => $fn_user,
		));
		
		if($fn_q_check_ip)
		{
			//check time mysql
			if(strtotime($this->fn_now_date) > strtotime($fn_q_check_ip->ban_time))
			{
				//unban
				self::unBan($fn_user, $fn_user_ip);
				
				return false;
			}
		}else{
			$fn_result_sql = true;
		}
		
		return ($fn_result_acp && $fn_result_sql) ? false : true;
	}
	
	/**
	 * banTime function.
	 * asignamos tiempo de baneo sengun los intentos
	 * 
	 * @access private
	 * @param mixed $fn_hash
	 * @param mixed $fn_username
	 * @return void
	 */
	private function banTime($fn_hash, $fn_username)
	{
		$fn_user_ip = self::_getClientIP();
		
		$fn_apn_b = ($this->use_apc) ? (int)apcu_fetch($fn_hash) : false;
		
		if($fn_apn_b == false)
		{
			$fn_q_count = $this->db->FetchValue("
				SELECT `count_attempt`
				FROM `users_login_ban`
				WHERE `u_name`=:u
				OR `u_ip`=:i
			", array(
				'u' => $fn_username,
				'i' => $fn_user_ip,
			));
			
			$fn_apn_b = ($fn_q_count) ? $fn_q_count : 1;
		}
		
		if($this->use_apc) apcu_store($fn_hash, $fn_apn_b); //add attemps
		
		$fn_ban_time = self::attemsToTime($fn_apn_b);
		
		//ban by apc -----------------
		if($this->use_apc) apcu_store($this->fn_bantime_loging, strtotime($fn_ban_time));
		
		//ban by mysql -----------------
		$fn_get_ban_id = $this->db->FetchValue("
			SELECT `id`
			FROM `users_login_ban`
			WHERE `u_ip`=:i
			OR `u_name`=:u
			LIMIT 1;
		", array(
			'i' => $fn_user_ip,
			'u' => $fn_username,
		));
		
		if($fn_get_ban_id)
		{
			$this->db->Fetch("
				UPDATE `users_login_ban`
				SET `count_attempt`=:c, `ban_time`=:b, `isBanned`=:ib
				WHERE `id`=:i;
			", array(
				'i' => $fn_get_ban_id,
				'c' => $fn_apn_b,
				'b' => $fn_ban_time,
				'ib' => 1,
			));
		}else{
			$this->db->Fetch("
				INSERT INTO `users_login_ban` (`u_name`, `u_ip`, `count_attempt`, `ban_time`, `isBanned`) 
				VALUES (:u, :i, :c, :b, :ib)
			", array(
				'u' => $fn_username,
				'i' => $fn_user_ip,
				'c' => $fn_apn_b,
				'b' => $fn_ban_time,
				'ib' => 1,
			));
		}
	}
	
	/**
	 * unBan function.
	 * 
	 * @access private
	 * @param mixed $fn_username (default: null)
	 * @param mixed $fn_userip (default: null)
	 * @return void
	 */
	private function unBan($fn_username = null, $fn_userip = null)
	{
		//remove apc -----------------
		if($this->use_apc)
		{
			apcu_delete($this->fn_ip_loging);
			apcu_delete($this->fn_user_loging);
			apcu_delete($this->fn_bantime_loging);
			apcu_delete($this->fn_banattemp_loging);
		}
		
		//remove mysql -----------------
		if($fn_username && $fn_userip)	$this->db->Fetch("
			DELETE FROM `users_login_ban`
			WHERE `ban_time` < NOW()
			AND `u_name`=:u
			OR `u_ip`=:i
		", array(
			'u' => $fn_username,
			'i' => $fn_userip,
		));
	}
	
	/**
	 * genPass function.
	 * 
	 * @access private
	 * @param mixed $fn_user
	 * @param mixed $fn_pass
	 * @return void
	 */
	private function genPass($fn_user, $fn_pass)
	{
		if(class_exists("tooSCrypt")) $fn_pass = tooSCrypt::en($fn_pass, $this->cfg_hash);
			
		$fn_pass = hash_hmac('sha512', "{$fn_user}~{$fn_pass}", $this->cfg_hash, false);
	
		return $fn_pass;
	}
	
	/**
	 * attemsToTime function.
	 * devuelve tiempo de baneo segun los intentos formato fecha
	 * 2^(x+1) mins: 2, 4, 8...
	 *
	 * @access private
	 * @param mixed $fn_attems
	 * @return void
	 */
	private function attemsToTime($fn_attems)
	{
		$fn_ban_time = pow(2, $fn_attems)*60;
		$fn_ban_time = date('Y-m-d H:i:s', strtotime("{$this->fn_now_date} + {$fn_ban_time} seconds"));
		
		return $fn_ban_time;
	}
	
	/**
	 * _getClientIP function.
	 * ip del cliente
	 *
	 * @access private
	 * @return void
	 */
	private function _getClientIP() 
	{
		$fn_type_ser = array(
	        'HTTP_VIA',
	        'HTTP_X_FORWARDED_FOR',
	        'HTTP_FORWARDED_FOR',
	        'HTTP_X_FORWARDED',
	        'HTTP_FORWARDED',
	        'HTTP_CLIENT_IP',
	        'HTTP_FORWARDED_FOR_IP',
	        'HTTP_XROXY_CONNECTION',
	        'HTTP_PROXY_CONNECTION',
	        'VIA',
	        'X_FORWARDED_FOR',
	        'FORWARDED_FOR',
	        'X_FORWARDED',
	        'FORWARDED',
	        'CLIENT_IP',
	        'FORWARDED_FOR_IP',
	        'HTTP_PROXY_CONNECTION'
	    );
		
		foreach($fn_type_ser as $d)
	    {
			if(isset($_SERVER) && isset($_SERVER[$d])) return $_SERVER[$d];
			if(isset($_ENV) && isset($_ENV[$d])) return $_ENV[$d];
	    }
	    
	    return (isset($_SERVER)) ? $_SERVER['REMOTE_ADDR'] : $_ENV('REMOTE_ADDR');
	}
	
	/**
	 * _isProxy function.
	 * detect proxy
	 * 
	 * @access private
	 * @return void
	 */
	private function _isProxy()
	{
		$fn_ports = array(80,8080,6588,8000,3128,3127,3124,1080,553,554);
		
		foreach($fn_ports as $p)
		{
			if(@fsockopen($_SERVER['REMOTE_ADDR'], $p, $errstr, $errno, 0.5))
			{
				return true;
			}else{
				continue;
			}
		}
		
		return false;
	}
}

?>