<?php
defined('WEKIT_VERSION') or exit(403);

define('FINNDYERROR_NONE', 1);
define('FINNDYERROR_ERROR', 2);
define('FINNDYERROR_PLUGIN_ERROR', 3);
define('FINNDYERROR_INVALID_PWD', 100);
define('FINNDYERROR_MISSING_FIELD', 101);

function finndy_success($data = "", $message = "") {
    finndy_result(1, $data, $message);
}

function finndy_fail($code = 2, $data = "", $message = "") {
    finndy_result($code, $data, $message);
}

function finndy_result($result = 1, $data = "", $message = "") {
    die(json_encode(array("result" => $result, "data" => $data, "message" => $message)));
}

function finndy_redirect_url($url) {
    if (empty($url)) {
        return false;
    }
    if(stripos($url, "www.finndy.com") === false){
    	
    	return array('realurl' => $url, 'referer' => "");
    }
    $result = finndy_curl_headers($url.'-dl');
    if ($result !== false && strpos($result, "302 Moved Temporarily")) {
        $headers = preg_split("/\r\n+/", $result);
        if (is_array($headers)) {
            $real_url = null;
            $referer = '';
            $suffix = '';
            foreach ($headers as $header) {
                $header = trim($header);
                $locpos = stripos($header, "location");
                $refererpos = stripos($header, "X-Referer");
                if ($locpos === 0) {
                  $pp = strpos($header, ":");
                  $real_url = trim(substr($header, $pp + 1));
                }else if ($refererpos === 0) {
                  $pp = strpos($header, ":");
                  $referer = trim(substr($header, $pp + 1));
                }
            }
            if (!empty($real_url) && stripos($real_url, "http") === 0) {
                return array('realurl' => $real_url, 'referer' => $referer);
            }
        }
    }
    return false;
}

function finndy_curl_headers($url) {
    // Curl
    $ch = curl_init();
    // header
    curl_setopt($ch, CURLOPT_HEADER, true);
    // 
    curl_setopt($ch, CURLOPT_NOBODY, true);
    // 
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // 
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    // 
    curl_setopt($ch, CURLOPT_AUTOREFERER, true);
    // 
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    // URL
    curl_setopt($ch, CURLOPT_URL, $url);
    // SSL
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    // 
    return curl_exec($ch);
}

function finndy_log($data) {
    if ($data && (is_array($data) || is_object($data))) {
        if (method_exists($data, 'jsonSerialize')) {
            $data = $data->jsonSerialize();
        }
        $str = json_encode($data);
    } else {
        $str = $data;
    }
    $myfile = fopen("finndy_log.txt", "a") or die("Unable to open file!");
    fwrite($myfile, $str);
    fclose($myfile);
}
