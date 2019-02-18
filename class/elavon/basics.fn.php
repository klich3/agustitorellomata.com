<?php

/**
 * cetsConfirmation function.
 * envio de confirmacion el cets de enric side 
 *
 * @access public
 * @param mixed $r
 * @return void
 */
function cetsConfirmation($fn_post_fields, $final_submition_url = null)
{
	if($final_submition_url == null) return;
	
	global $CONFIG;
	
	$scurl = curl_init();

	curl_setopt($scurl, CURLOPT_URL, $final_submition_url);
	curl_setopt($scurl, CURLOPT_POST, 1);
	curl_setopt($scurl, CURLOPT_POSTFIELDS, $fn_post_fields);
	
	curl_exec($scurl);
	curl_close($scurl);
}

function headerPost($fn_url, $fn_post_fields)
{
	$ch = curl_init($fn_url);
	
	if(isset($fn_post_fields))
	{
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $fn_post_fields);
	}
	
	curl_setopt($ch, CURLOPT_RETURNTRANSFER , 1);  // RETURN THE CONTENTS OF THE CALL
	$resp = curl_exec($ch);
	
	echo($resp);
}

?>