<?php

/**
 * MyPayPal class.
 *
 * Anthony Sychev
 * mods for glunt paypal
 */
class MyPayPal {
    
    /**
     * createFormRequest function.
     * Iniciamos la llamada al paypal
     *
     * @access public
     * @param mixed fn_attr //array con configuracion
     	'lang'
     	'mode' //live sandbox
     	'api_user'
     	'api_pass'
     	'api_sign'
     	'currency' //default eur
     * @param mixed fn_urls //array con configuracion de urls
     	'base' //main url of project
		'url_response' 
		'url_ko'
		'logo'
     * @param mixed $fn_cart //array de items
     	'cart_items'
     	'cart_total'
     	'cart_subtotal'
     	'cart_tax' //tax
     	'cart_description'
     	'cart_orderid' //order id
     * @return void
     */
    public function createFormRequest($fn_attr, $fn_urls, $fn_cart)
    {
	    //Parameters for SetExpressCheckout, which will be sent to PayPal
		$padata = array(
			'METHOD' => 'SetExpressCheckout',
            'RETURNURL' => $fn_urls['url_response'],
            'CANCELURL' => $fn_urls['url_ko'],
            'PAYMENTREQUEST_0_PAYMENTACTION' => 'SALE',
            
            'L_PAYMENTREQUEST_0_NAME0' => '',
            'L_PAYMENTREQUEST_0_NUMBER0' => $fn_cart['cart_orderid'],
            'L_PAYMENTREQUEST_0_DESC0' => $fn_cart['cart_description'],
            'L_PAYMENTREQUEST_0_AMT0' => number_format($fn_cart['cart_total'], 2, '.', ''),
            'L_PAYMENTREQUEST_0_QTY0' => 1,
            
            'PAYMENTREQUEST_0_ITEMAMT' => number_format($fn_cart['cart_total'], 2, '.', ''),
            'PAYMENTREQUEST_0_TAXAMT' => 0,
            'PAYMENTREQUEST_0_SHIPPINGAMT' => 0,
            'PAYMENTREQUEST_0_HANDLINGAMT' => 0,
            'PAYMENTREQUEST_0_SHIPDISCAMT' => 0,
            'PAYMENTREQUEST_0_INSURANCEAMT' => 0,
            'PAYMENTREQUEST_0_AMT' => number_format($fn_cart['cart_total'], 2, '.', ''),
            'PAYMENTREQUEST_0_CURRENCYCODE' => $fn_attr['currency'],
            
            'LOCALECODE' => $fn_attr['lang'], //PayPal pages to match the language on your website.
            'SOLUTIONTYPE' => 'Sole',
            'LANDINGPAGE=' => 'billing',
            'NOSHIPPING' => 1,            
            'ALLOWNOTE' => 0,
            'CARTBORDERCOLOR' => 'FFFFFF', //border color of cart
            'LOGOIMG' => $fn_urls['logo'], //site logo
        );
        
        //asignamos calores a la session
        foreach($padata as $pk => $pv)
        {
	        $_SESSION[$pk] = $pv;
        }
        
		//We need to execute the "SetExpressCheckOut" method to obtain paypal token
		$httpParsedResponseAr = self::PPHttpPost('SetExpressCheckout', $padata, $fn_attr['api_user'], $fn_attr['api_pass'], $fn_attr['api_sign'], $fn_attr['mode']);
		
		//Respond according to message we receive from Paypal
		if("SUCCESS" == strtoupper($httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($httpParsedResponseAr["ACK"]))
		{
			$paypalmode = ($fn_attr['mode'] == 'sandbox') ? ".{$fn_attr['mode']}" : '';
			
		    //Redirect user to PayPal store with Token received.
		    $paypalurl ='https://www'.$paypalmode.'.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token='.$httpParsedResponseAr["TOKEN"].'';
		    
		    return array(
			    'status' => 200,
			    'redirect' => $paypalurl,
		    );
		    //header("Location: {$paypalurl}");
			//exit;
		}else{
		    $fn_title = urlencode($httpParsedResponseAr["L_LONGMESSAGE0"]);
		    $fn_details = urlencode(print_r($httpParsedResponseAr, true));
		    
		    return array(
			    'status' => 400,
			    'redirect' => $fn_urls['url_ko'],
		    );
		    //redirect
		    //header("Location: {$fn_urls['url_ko']}");
			//exit;
		}
    }
    
    /**
     * responsePayPal function.
     * 
     * @access public
     * @param mixed $fn_token
     * @param mixed $fn_payerId
     * @param mixed $fn_attr
     	'lang'
     	'mode' //live sandbox
     	'api_user'
     	'api_pass'
     	'api_sign'
     	'currency'
      * @param mixed fn_urls //array con configuracion de urls
     	'base' //main url of project
		'url_ko'
		'url_ok'
     * @return void
     */
    public function responsePayPal($fn_token, $fn_payerId, $fn_attr, $fn_urls)
    {
		$padata = array(
				'TOKEN' => $fn_token,
		        'PAYERID' => $fn_payerId,
		);
		
		$fn_array_rem = array(
			'PAYMENTREQUEST_0_PAYMENTACTION',
			'L_PAYMENTREQUEST_0_NAME0',
			'L_PAYMENTREQUEST_0_NUMBER0',
			'L_PAYMENTREQUEST_0_DESC0',
			'L_PAYMENTREQUEST_0_AMT0',
			'L_PAYMENTREQUEST_0_QTY0',
			'PAYMENTREQUEST_0_ITEMAMT',
			'PAYMENTREQUEST_0_TAXAMT',
			'PAYMENTREQUEST_0_SHIPPINGAMT',
			'PAYMENTREQUEST_0_HANDLINGAMT',
			'PAYMENTREQUEST_0_SHIPDISCAMT',
			'PAYMENTREQUEST_0_INSURANCEAMT',
			'PAYMENTREQUEST_0_AMT',
			'PAYMENTREQUEST_0_CURRENCYCODE',
		);
		
		foreach($_SESSION as $sk => $sv)
		{
			if(in_array($sk, $fn_array_rem)) $padata[$sk] = $sv;
		}
		
		//We need to execute the "DoExpressCheckoutPayment" at this point to Receive payment from user.
		$httpParsedResponseAr = self::PPHttpPost('DoExpressCheckoutPayment', $padata, $fn_attr['api_user'], $fn_attr['api_pass'], $fn_attr['api_sign'], $fn_attr['mode']);
		
		/*
		//error
		array(11) {
		  ["TOKEN"]=>
		  string(22) "EC%2d7EW44885U55941823"
		  ["SUCCESSPAGEREDIRECTREQUESTED"]=>
		  string(5) "false"
		  ["TIMESTAMP"]=>
		  string(28) "2017%2d05%2d16T00%3a00%3a35Z"
		  ["CORRELATIONID"]=>
		  string(13) "71e4c212c3507"
		  ["ACK"]=>
		  string(7) "Failure"
		  ["VERSION"]=>
		  string(6) "70%2e0"
		  ["BUILD"]=>
		  string(8) "33490117"
		  ["L_ERRORCODE0"]=>
		  string(5) "10415"
		  ["L_SHORTMESSAGE0"]=>
		  string(122) "Transaction%20refused%20because%20of%20an%20invalid%20argument%2e%20See%20additional%20error%20messages%20for%20details%2e"
		  ["L_LONGMESSAGE0"]=>
		  string(87) "A%20successful%20transaction%20has%20already%20been%20completed%20for%20this%20token%2e"
		  ["L_SEVERITYCODE0"]=>
		  string(5) "Error"
		}
		*/
		/*
			//ok
			array(24) { ["TOKEN"]=> string(22) "EC%2d9LL40755W7771684K" ["SUCCESSPAGEREDIRECTREQUESTED"]=> string(5) "false" ["TIMESTAMP"]=> string(28) "2017%2d05%2d16T00%3a04%3a11Z" ["CORRELATIONID"]=> string(13) "1d9bcbef3fe8e" ["ACK"]=> string(7) "Success" ["VERSION"]=> string(6) "70%2e0" ["BUILD"]=> string(8) "33490117" ["INSURANCEOPTIONSELECTED"]=> string(5) "false" ["SHIPPINGOPTIONISDEFAULT"]=> string(5) "false" ["PAYMENTINFO_0_TRANSACTIONID"]=> string(17) "3AX86615K9708081J" ["PAYMENTINFO_0_TRANSACTIONTYPE"]=> string(4) "cart" ["PAYMENTINFO_0_PAYMENTTYPE"]=> string(7) "instant" ["PAYMENTINFO_0_ORDERTIME"]=> string(28) "2017%2d05%2d16T00%3a04%3a11Z" ["PAYMENTINFO_0_AMT"]=> string(8) "139%2e90" ["PAYMENTINFO_0_FEEAMT"]=> string(6) "5%2e11" ["PAYMENTINFO_0_TAXAMT"]=> string(6) "0%2e00" ["PAYMENTINFO_0_CURRENCYCODE"]=> string(3) "EUR" ["PAYMENTINFO_0_PAYMENTSTATUS"]=> string(9) "Completed" ["PAYMENTINFO_0_PENDINGREASON"]=> string(4) "None" ["PAYMENTINFO_0_REASONCODE"]=> string(4) "None" ["PAYMENTINFO_0_PROTECTIONELIGIBILITY"]=> string(10) "Ineligible" ["PAYMENTINFO_0_PROTECTIONELIGIBILITYTYPE"]=> string(4) "None" ["PAYMENTINFO_0_ERRORCODE"]=> string(1) "0" ["PAYMENTINFO_0_ACK"]=> string(7) "Success" }
			
		*/
		if("SUCCESS" == strtoupper($httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($httpParsedResponseAr["ACK"])) 
		{
			$padata = array(
				'TOKEN' => $fn_token,
			);
			
			$httpParsedResponseAr = self::PPHttpPost('GetExpressCheckoutDetails', $padata, $fn_attr['api_user'], $fn_attr['api_pass'], $fn_attr['api_sign'], $fn_attr['mode']);
		    
		    /*
		    var_dump($httpParsedResponseAr);
		    array(50) { ["TOKEN"]=> string(22) "EC%2d91H74791FA026425D" ["BILLINGAGREEMENTACCEPTEDSTATUS"]=> string(1) "0" ["CHECKOUTSTATUS"]=> string(22) "PaymentActionCompleted" ["TIMESTAMP"]=> string(28) "2017%2d05%2d16T00%3a14%3a37Z" ["CORRELATIONID"]=> string(13) "b72be0043267b" ["ACK"]=> string(7) "Success" ["VERSION"]=> string(6) "70%2e0" ["BUILD"]=> string(8) "33490117" ["EMAIL"]=> string(61) "92D29722F93D40A5BBC35E78F56E72AB%2ePROTECT%40WHOISGUARD%2eCOM" ["PAYERID"]=> string(13) "LCHZKA92J962U" ["PAYERSTATUS"]=> string(8) "verified" ["FIRSTNAME"]=> string(4) "test" ["LASTNAME"]=> string(4) "test" ["COUNTRYCODE"]=> string(2) "ES" ["ADDRESSSTATUS"]=> string(9) "Confirmed" ["CURRENCYCODE"]=> string(3) "EUR" ["AMT"]=> string(7) "69%2e95" ["ITEMAMT"]=> string(7) "69%2e95" ["SHIPPINGAMT"]=> string(6) "0%2e00" ["HANDLINGAMT"]=> string(6) "0%2e00" ["TAXAMT"]=> string(6) "0%2e00" ["INSURANCEAMT"]=> string(6) "0%2e00" ["SHIPDISCAMT"]=> string(6) "0%2e00" ["TRANSACTIONID"]=> string(17) "4FH87434DV091803U" ["INSURANCEOPTIONOFFERED"]=> string(5) "false" ["L_NAME0"]=> string(1) "1" ["L_NUMBER0"]=> string(11) "17051603620" ["L_QTY0"]=> string(1) "1" ["L_TAXAMT0"]=> string(6) "0%2e00" ["L_AMT0"]=> string(7) "69%2e95" ["L_DESC0"]=> string(18) "Varios%20productos" ["PAYMENTREQUEST_0_CURRENCYCODE"]=> string(3) "EUR" ["PAYMENTREQUEST_0_AMT"]=> string(7) "69%2e95" ["PAYMENTREQUEST_0_ITEMAMT"]=> string(7) "69%2e95" ["PAYMENTREQUEST_0_SHIPPINGAMT"]=> string(6) "0%2e00" ["PAYMENTREQUEST_0_HANDLINGAMT"]=> string(6) "0%2e00" ["PAYMENTREQUEST_0_TAXAMT"]=> string(6) "0%2e00" ["PAYMENTREQUEST_0_INSURANCEAMT"]=> string(6) "0%2e00" ["PAYMENTREQUEST_0_SHIPDISCAMT"]=> string(6) "0%2e00" ["PAYMENTREQUEST_0_TRANSACTIONID"]=> string(17) "4FH87434DV091803U" ["PAYMENTREQUEST_0_SELLERPAYPALACCOUNTID"]=> string(20) "klich3%40gmail%2ecom" ["PAYMENTREQUEST_0_INSURANCEOPTIONOFFERED"]=> string(5) "false" ["L_PAYMENTREQUEST_0_NAME0"]=> string(1) "1" ["L_PAYMENTREQUEST_0_NUMBER0"]=> string(11) "17051603620" ["L_PAYMENTREQUEST_0_QTY0"]=> string(1) "1" ["L_PAYMENTREQUEST_0_TAXAMT0"]=> string(6) "0%2e00" ["L_PAYMENTREQUEST_0_AMT0"]=> string(7) "69%2e95" ["L_PAYMENTREQUEST_0_DESC0"]=> string(18) "Varios%20productos" ["PAYMENTREQUESTINFO_0_TRANSACTIONID"]=> string(17) "4FH87434DV091803U" ["PAYMENTREQUESTINFO_0_ERRORCODE"]=> string(1) "0" } 
		    */
		    
		    if("SUCCESS" == strtoupper($httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($httpParsedResponseAr["ACK"])) 
		    {
				//if($httpParsedResponseAr['PAYMENTINFO_0_PAYMENTSTATUS'] == 'Completed')
				return array(
					'status' => 200,
					'data' => $httpParsedResponseAr,
					'session' => $_SESSION,
					'redirect' => "{$fn_urls['url_ok']}",
				);
		    }
		}else{
			
			$fn_msg = urlencode($httpParsedResponseAr["L_LONGMESSAGE0"]);
			//header("Location: {$CONFIG['site']['base']}tickets/checkout?action=paypal_canceled&msg={$fn_msg}");
			
			return array(
				'status' => 400,
				'data' => $fn_msg,
				'redirect' => "{$fn_urls['url_ko']}",
			);
		}
    }
    
	/**
	 * PPHttpPost function.
	 *
	 * genera reepuesta 
	 *
	 * @access private
	 * @param mixed $methodName_
	 * @param mixed $nvpStr_
	 * @param mixed $PayPalApiUsername
	 * @param mixed $PayPalApiPassword
	 * @param mixed $PayPalApiSignature
	 * @param mixed $PayPalMode
	 * @return void
	 */
	private function PPHttpPost($methodName_, $nvpStr_, $PayPalApiUsername, $PayPalApiPassword, $PayPalApiSignature, $PayPalMode) 
	{	
		/*
			https://api-3t.sandbox.paypal.com/nvp?&user=us-30_api1.cri.com&pwd=EYFNSNUSV85CT34Z&signature=AH57zE.nAaElaFFAysViNA9TIte1AxtSpBjx2HLqHJOiu2js3l1Kd48i&version=70.0&METHOD=SetExpressCheckout&RETURNURL=http://www.paypal.com/test.php&CANCELURL=http://www.paypal.com/test.php&PAYMENTACTION=Sale&paymentrequest_0_itemamt=71.02&paymentrequest_0_taxamt=18.88&paymentrequest_0_amt=89.90&L_PAYMENTREQUEST_0_NAME0=Sweateranuperdry&L_PAYMENTREQUEST_0_AMT0=35.51&L_PAYMENTREQUEST_0_QTY0=2
		*/
				
		// Set up your API credentials, PayPal end point, and API version.
		$paypalmode = ($PayPalMode == 'sandbox') ? '.sandbox' : '';

		$API_Endpoint = "https://api-3t".$paypalmode.".paypal.com/nvp";
		$version = urlencode('70.0');
	
		// Set the curl parameters.
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $API_Endpoint);
		curl_setopt($ch, CURLOPT_VERBOSE, 1);
		
		// Turn off the server and peer verification (TrustManager Concept).
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		//curl_setopt($ch, CURLOPT_SSLVERSION, 6); 
	
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		
		$fn_posts = array(
			'METHOD' => $methodName_,
			'VERSION' => $version,
			'PWD' => $PayPalApiPassword,
			'USER' => $PayPalApiUsername,
			'SIGNATURE' => $PayPalApiSignature,
		);
		
		$fn_posts_out = array_merge($fn_posts, $nvpStr_);
		
		// Set the request as a POST FIELD for curl.
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fn_posts_out));
		
		// Get response from the server.
		$httpResponse = curl_exec($ch);
	
		if(!$httpResponse) exit("$methodName_ failed: ".curl_error($ch).'('.curl_errno($ch).')');
				
		// Extract the response details.
		$httpResponseAr = explode("&", $httpResponse);
		
		$httpParsedResponseAr = array();
		foreach ($httpResponseAr as $i => $value) 
		{
			$tmpAr = explode("=", $value);
			if(sizeof($tmpAr) > 1) $httpParsedResponseAr[$tmpAr[0]] = $tmpAr[1];
		}
	
		if((0 == sizeof($httpParsedResponseAr)) || !array_key_exists('ACK', $httpParsedResponseAr)) exit("Invalid HTTP Response for POST request(nvpreq) to $API_Endpoint.");
		
		return $httpParsedResponseAr;
	}
}
	
?>