<?php

require_once(dirname(__FILE__).'/config.php');

function call_api($api,$params,$return = false){
	$params['pub_key'] = PREMIUM_PAYCENTER_PUBKEY;
	$params['sign_type'] = PREMIUM_PAYCENTER_SIGNTYPE;
	$params['sign'] = createSign($params, PREMIUM_PAYCENTER_SECRET, strtolower($params['sign_type']));

	$result = postInvoke('http://'.PREMIUM_PAYCENTER_IP.'/paycenter/'.$api, PREMIUM_PAYCENTER_HOST, $params, $error);

    if ($return) {
        return $result;
    }
    
    echo $result, PHP_EOL;
}
function postInvoke($url, $hostName, $params, &$error){
    if (is_array($params)) {
	$postParams = array();
    foreach ($params as $key=>$val) {
        $post_params[] = $key.'='.urlencode($val);
    }

	$postString = implode('&', $post_params);
    } else {
        $postString = $params;
    }


	$result = '';
    if (function_exists('curl_init')) {
    	$ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Host: ' . $hostName));
        curl_setopt($ch, CURLOPT_POST, 1);
		//curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postString);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'leju.com BC Test PHP5 Client ver: ' . phpversion());
        $result = curl_exec($ch);
        if (false == $result) {
            $error = curl_error($ch); 
        }
        curl_close($ch);
    }

	return $result;	
}


function createSign(array $params, $secretKey, $algo = "md5"){
	ksort($params);
	reset($params);
	
	$signPars = '';
    while(list($k, $v) = each($params)){
        if('' === $v) continue;
        $signPars .= $k . '=' . $v . '&';
    }
    // 去掉最后一个 "&"
    $signPars = substr($signPars, 0, strlen($signPars)-1);
	$sign = strtolower(hash_hmac($algo, $signPars, $secretKey));
	
	return $sign;
}



?>
