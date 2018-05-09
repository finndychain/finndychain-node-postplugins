<?php

if (!defined('IN_DISCUZ')) {
    exit('Access Denied');
}

define('FINNDYERROR_NONE', 1);
define('FINNDYERROR_ERROR', 2);
define('FINNDYERROR_PLUGIN_ERROR', 3);
define('FINNDYERROR_INVALID_PWD', 100);
define('FINNDYERROR_MISSING_FIELD', 101);

function finndysuccess($data = "", $message = "") {
    finndyresult(1, $data, $message);
}

function finndyfail($code = 2, $data = "", $message = "") {
    finndyresult($code, $data, $message);
}

function finndyresult($result = 1, $data = "", $message = "") {
    die(json_encode(array("result" => $result, "data" => $data, "message" => urlencode($message))));
}

// Get Real Url for 302 URL
function finndyredirect_url($url) {
    if (empty($url)) {
        return false;
    }
    $result = finndycurl_headers($url);
    if ($result !== false && strpos($result, "302 Moved Temporarily")) {
        $headers = preg_split("/\r\n+/", $result);
        if (is_array($headers)) {
            $real_url = null;
            foreach ($headers as $header) {
                $header = trim($header);
                $locpos = stripos($header, "location");
                if ($locpos === 0) {
                    $pp = strpos($header, ":");
                    $real_url = trim(substr($header, $pp + 1));
                }
            }
            if (!empty($real_url) && stripos($real_url, "http") === 0) {
                return $real_url;
            }
        }
    }
    return false;
}

function decodeForBase64(&$post) {
    if (count($post)) {
        foreach ($post as $key => $value) {
            $post[$key] = base64_decode(str_replace(" ", "+", $value));
        }
    }

    return $post;
}

function finndycurl_headers($url) {
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

function finndylog($data) {
    if ($data && (is_array($data) || is_object($data))) {
        if (method_exists($data, 'jsonSerialize')) {
            $data = $data->jsonSerialize();
        }
        $str = json_encode($data);
    } else {
        $str = $data;
    }
    $myfile = fopen("finndylog.txt", "a") or die("Unable to open file!");
    fwrite($myfile, $str);
    fclose($myfile);
}
