<?php

class tooSCrypt {
	static public function de($string, $key)
	{
		$result = "";
		$string = base64_decode($string);
		for($i=0; $i<strlen($string); $i++) 
		{
			$char = substr($string, $i, 1);
			$keychar = substr($key, ($i % strlen($key))-1, 1);
			$char = chr(ord($char)-ord($keychar));
			$result.=$char;
		}
		
		return $result;
	}
	
	static public function en($string, $key)
	{
		$result = "";
	
		for($i=0; $i<strlen($string); $i++) 
		{
			$char = substr($string, $i, 1);
			$keychar = substr($key, ($i % strlen($key))-1, 1);
			$char = chr(ord($char)+ord($keychar));
			$result.=$char;
		}
		
		return base64_encode($result);
	}
}

?>