<?php
	
/**
 * mysqlinject class.
 * Author: Anthony Sychev (hello at dm211 dot com)
 * Control sobre POST, GET, REQUEST mysql inyeccion
 *
 * build: 23022018-1224
 * SAMPLE:
 
	include_once("class/211.mysqlinject.php");
	
	$cl_m = new mysqlinject(); //debug, redirect to 304
	
	$fn_g = $cl_m->parse("GET");
	$fn_p = $cl_m->parse("POST");
	$fn_r = $cl_m->parse("REQ");
*/
 
class mysqlinject {
	
	private $cl_debug = false;
	private $cl_error = array();
	private $cl_redirect = false;
	
	/**
	 * __construct function.
	 * 
	 * @access public
	 * @param bool $fn_debug (default: false)
	 * @param bool $fn_redirect (default: false)
	 * @return void
	 */
	public function __construct($fn_debug = false, $fn_redirect = false)
	{
		if($fn_debug) $this->cl_debug = $fn_debug;
		if($fn_redirect) $this->cl_redirect = $fn_redirect;
		
		//filter agents
		if(isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/(?i)(libwww-perl|wget|python|nikto|curl|scan|java|winhttp|clshttp|loader|<|>|\'|%0A|%0D|%27|%3C|%3E|%00)/', $_SERVER['HTTP_USER_AGENT'])) $this->cl_error[] = 'R:19';
		
		//combinations
		if(isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/(?i)(;|<|>|\'|\"|\)|\(|%0A|%0D|%22|%27|%28|%3C|%3E|%00).*(libwww-perl|wget|python|nikto|curl|scan|java|winhttp|HTTrack|clshttp|archiver|loader|email|harvest|extract|grab|miner)/', $_SERVER['HTTP_USER_AGENT'])) $this->cl_error[] = 'R:20';
		
		//request protection
		if(isset($_SERVER['REQUEST_METHOD']) && preg_match('/(?i)(%0A|%0D|HEAD|TRACE|DELETE|TRACK|DEBUG)/', $_SERVER['REQUEST_METHOD'])) $this->cl_error[] = 'R:21';
		
		if(sizeof($this->cl_error) !== 0) self::dieHeader(json_encode($this->cl_error), "R:46");
	}
	
	/**
	 * parse function.
	 * 
	 * @access public
	 * @static
	 * @param mixed $fn_method
	 * @return void
	 */
	public function parse($fn_method)
	{
		if(empty($fn_method)) return false;
		
		if(is_string($fn_method)) switch($fn_method)
		{
			case "GET":
				$this->fn_v = (isset($_GET)) ? self::proccess($_GET) : false;
			break;
			
			case "POST":
				$this->fn_v = (isset($_POST)) ? self::proccess($_POST) : false;
			break;
			
			case "REQ":
			case "REQUEST":
				$this->fn_v = (isset($_REQUEST)) ? self::proccess($_REQUEST) : false;
			break;
		}
		
		return $this->fn_v;
	}

	/**
	 * parseSTMP function.
	 * comprobamos las copias de los mails en campos de correo
	 *
	 * @access public
	 * @param mixed $fn_email_field
	 * @return void
	 *
	 *
	 * eploit STMP
		a@a.com>
		BCC:jvixnep91f83l5aejki65amiv91zprjfb2bq0@burpcollaborator.net
		apn: q
	 */
	public function parseSTMP($fn_email_field, $fn_export_first = true)
	{
		$fn_isValid = (preg_match('/(?i)(%0a|%0d|%0d%0a)(bcc|reply-to|cc)+(&#58;|%3A|&#x3a;|%3a|\\\\u003A|\:)/m', $fn_email_field)) ? false : true;
		
		if(!$fn_isValid && $fn_export_first)
		{
			preg_match('/(.*)\>/', $fn_email_field, $fn_export_first);
			
			if($fn_export_first) return current($fn_export_first);
		}else{
			return ($fn_export_first) ? $fn_email_field : $fn_isValid;
		}
	}
	
	/**
	 * parseHeaderReferer function.
	 * 
	 * @access public
	 * @param mixed $fn_referer_domain //a dominio donde esta alojado 
	 * @param boolean $fn_bock //a dominio donde esta alojado 
	 * @return void
	 */
	public function parseHeaderReferer($fn_referer_domain, $fn_block = true)
	{
		if(!isset($_SERVER['HTTP_REFERER']) && $fn_block) self::dieHeader(false, "H:62");
		
		$fn_header_ref = str_replace(array(
			'http://',
			'https://',
			'www.'
		), '', $_SERVER['HTTP_REFERER']);
		
		$fn_header_ref = explode('/', $fn_header_ref);
		
		$fn_compare = ($fn_referer_domain !== current($fn_header_ref)) ? false : true;
		
		return $fn_compare;
	}
	
	/**
	 * parseInject function.
	 *
	 * @access private
	 * @param mixed $fn_data
	 * @return void
	 */
	private function proccess($fn_data)
	{
		$fn_type = gettype($fn_data);
		
		switch($fn_type)
		{
			case "array":
				foreach($fn_data as $k => $v)
				{
					if(gettype($v) == 'array')
					{
						self::proccess($v);
						continue;
					}
					
					if(preg_match('/javascript\:void\(0\)/', $v)) continue;
					
					if(isset($v) && self::isJson($v)) continue;
					if(preg_match('/(pageContent|content|page_content)/', $k)) continue;
					
					//remove comments
					$v_preprocess = preg_replace('/(?i)(\/\*)(.*)(\*\/)/mi', '', $v);
					
					if(isset($v))
					{
						$fn_e_num = 0;
						
						/*
							//1=1-- mysql
							((\'|(%|\\u|\\x|0x)(27|0027)).?(in|or).?(([0-9])=([0-9])--|((%|\\u|\\x|0x)(0031|31)|1)( |(0x|\%|\\u|\\x)20|0b00100000|00100000)in)|[0-9].?(=).?[0-9])+.?(\-\-)
							
							//selects and globals
						*/
						
						if(preg_match('/(?i)((\'|(%|\\\\u|\\\\x|0x)(27|0027)).?(in|or).?(1=1--|((%|\\\\u|\\\\x|0x)(0031|31)|1)( |(0x|\%|\\\\u|\\\\x)20|0b00100000|00100000)in)|localhost|\|\||loopback|127\.0\.0\.1|null|(\'|(0x|%|\\\\u|\\\\x)0027|27|00034|00100111).?(\-\-|or|1).?(in)|Utl_Http.request|(from).?(dual)|waitfor$|waitfor.?(delay)|outfile|queryout|load_file|(select|update|delete)( |(0x|\%)20|0b00100000|00100000|case|char|concat|\(|(0x|\%)28|0b00101000|00101000)(\*|0b00101010|00101010|(0x|%|\\\\u|\\\\x)2A|002A)|insert( |(0x|\%|\\\\u|\\\\x)20|0b00100000|00100000).?(into)|(drop|rename|delete)( |(0x|\%|\\\\u|\\\\x)20|0b00100000|00100000)(table|if|exist|column)|(delete|create|alter).?( |(0x|\%|\\\\u|\\\\x)20|0b00100000|00100000)(table|from|view)|order.?(by)|set( |(0x|\%|\\\\u|\\\\x)20|0b00100000|00100000).?(global|session)|(convert|concat|export_set|base64|ascii|bin|cast|char|md5|version|schema|sp_password|row_count|row_|benchmark|concat|hex|sleep)(\(|(0x|\%|\\\\u|\\\\x)28|0b00101000|00101000)|(self|source|modify|call|union|declare|use|encode|aes_|des_|encr|pass|sha|uncompre|exec)( |(0x|\%|\\\\u|\\\\x)20|0b00100000|00100000)|(set).?(\@|\@\@)|(\@|\@\@)version|(inner|cross|outer|left|right|full).?(join)|(memb_)(_id|_pwd|info)|(show).?( |(0x|\%|\\\\u|\\\\x)20|0b00100000|00100000)(table))/mi', $v_preprocess, $debug)) $fn_e_num = 191;
						
						/*
							exec(
							(exec)+(\(|((%|\\u|\\x|0x)(0028|28))|@)
						*/
						if(preg_match('/(?i)(exec)+(\(|((%|\\\\u|\\\\x|0x)(0028|28))|@)/mi', $v_preprocess, $debug)) $fn_e_num = 192;
						
						//dirs
						/*
							pathing dirs
							(?i)(%%+(31|32|35))+%(66|65|63)|(%c0|%c1|\.\.|(%2+[e5]|\/)+(\.|[2ae])+(\\|\/|%+(2f|25)|%+(2f|25)+(2f|5c|c0)|%c1|%c0|%9c|%%32+(%65|%66)))
						*/
						if(preg_match('/(?i)(%%+(31|32|35))+%(66|65|63)|(%c0|%c1|\.\.|(%25|%2e)|\/)+(\.|(2a|2e))+(\\\\|\/|%+(2f|25)|%+(2f|25)+(2f|5c|c0)|%c1|%c0|%9c|%%32+(%65|%66))/mi', $v_preprocess, $debug)) $fn_e_num = 193;
						
						
						$fn_args = array();
						$fn_args[] = $k;
						$fn_args[] = $v;
						$fn_args[] = $debug;
						
						$this->cl_error[] = $fn_args;
						
						if($fn_e_num !== 0) self::dieHeader(json_encode($this->cl_error), "[M:{$fn_e_num}]");
					}
					
					//XSS scripting
					if(isset($k) && preg_match('/(?i)(\<|\>|&#60;|&lt;|(0x|%)3C|(\\\\u|u)003C|074|0b00111100|00111100)(.?|\s)(script)/mi', $k)) self::dieHeader(false, "[X:20]");
					
					if(gettype($k) == 'array') continue;
					
					if(isset($k) && preg_match('/(?i)(cPath|GLOBALS|_REQUEST|concat|CONCAT)/', $k) && !preg_match('/signed_request/', $k)) self::dieHeader(false, '[G:204]');
				}
			break;
		}
		
		return $fn_data;
	}
	
	/**
	 * dieHeader function.
	 * 
	 * @access private
	 * @param bool $fn_args (default: false)
	 * @param string $fn_msg (default: "R:42")
	 * @return void
	 */
	private function dieHeader($fn_args = false, $fn_msg)
	{
		if($this->cl_redirect)
		{
			header("HTTP/1.1 301 Moved Permanently");
			header("Location: /error");
			
			if($this->cl_debug) header("Debug: {$fn_args}");
		}
		
		$fn_result = array(
			'status' => 400,
			'message' => $fn_msg, 
		);
		
		if($this->cl_debug) $fn_result['data'] = json_decode($fn_args);
		
		exit(json_encode($fn_result));
	}
	
	/**
	 * isJson function.
	 * 
	 * @access private
	 * @param mixed $string
	 * @return void
	 */
	private function isJson($string) 
	{
		return ((is_string($string) && (is_object(json_decode($string)) || is_array(json_decode($string))))) ? true : false;
	}
}
	
?>