<?php

// Returns the content content of a given url
function get_content($url) 
{
	$headers = array(
	   "Connection: keep-alive",
	   "Upgrade-Insecure-Requests: 1",
	   "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9",
	   "Accept-Language: en,es-ES;q=0.9,es;q=0.8",
	);
	
	$agent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/117.0';

	$cookieFilePath = __DIR__ . '/cookie.txt';

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_VERBOSE, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_USERAGENT, $agent);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_ENCODING, '');
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_AUTOREFERER, true);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT , 60); 
	curl_setopt($ch, CURLOPT_TIMEOUT, 60); 
	curl_setopt($ch, CURLOPT_MAXREDIRS, 5); 
	curl_setopt($ch, CURLOPT_COOKIE, 'consent=yes');
	curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFilePath);
	curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFilePath);
	curl_setopt($ch, CURLOPT_URL,$url);
	$content=curl_exec($ch);
	
	// Check for cURL errors
	if (curl_errno($ch)) {
		$error_message = curl_error($ch);
		curl_close($ch);
		return '';
	}

	curl_close($ch);
	
	return $content;
}
?>