<?php

if (!defined('IN_DISCUZ')) {
    exit('Access Denied');
}
require_once DISCUZ_ROOT . './config/config_ucenter.php';
require_once DISCUZ_ROOT . './uc_client/client.php';

class finndy_basic {

    public $value = array();

    public function __construct() {
        ;
    }

    protected function randTime($min = '', $max = '') {
        if (!$max) {
            $max = time();
        }

        return rand($min, $max);
    }

    protected function paddslashes($data) {
        if (DISCUZ_VERSION != 'X2')
            return $data;
        return daddslashes($data);
    }

    protected function randEmail($username) {
        $emailSps = array('163.com', 'qq.com', 'gmail.com', 'sina.com', 'weibo.com', 'yahoo.cn', '139.com');
        $isSinogram = ereg('[' . chr(0xa1) . '-' . chr(0xff) . ']', $username);
        $isNumber = ereg('[0-9]', $username);
        $isCharacter = ereg('[a-zA-Z]', $username);
        if (!$isSinogram || (!$isSinogram && !$isNumber && !$isCharacter)) {
            $f = strtolower($username);
        } else {
            $f = strtolower(random(rand(4, 12)));
        }

        return $f . '@' . $emailSps[rand(0, 6)];
    }

    protected function getPassword() {
        $pw = '123456';
        return $pw;
    }

    protected function randIp() {
        $ip_long = array(
            array('607649792', '608174079'), //36.56.0.0-36.63.255.255
            array('1038614528', '1039007743'), //61.232.0.0-61.237.255.255
            array('1783627776', '1784676351'), //106.80.0.0-106.95.255.255
            array('2035023872', '2035154943'), //121.76.0.0-121.77.255.255
            array('2078801920', '2079064063'), //123.232.0.0-123.235.255.255
            array('-1950089216', '-1948778497'), //139.196.0.0-139.215.255.255
            array('-1425539072', '-1425014785'), //171.8.0.0-171.15.255.255
            array('-1236271104', '-1235419137'), //182.80.0.0-182.92.255.255
            array('-770113536', '-768606209'), //210.25.0.0-210.47.255.255
            array('-569376768', '-564133889'), //222.16.0.0-222.95.255.255
        );
        $rand_key = mt_rand(0, 9);
        return long2ip(mt_rand($ip_long[$rand_key][0], $ip_long[$rand_key][1]));
    }

    protected function _value($value, $default) {
        if (empty($value))
            return $default;
        return $value;
    }

    protected function parse_html($text) {
        $recursive_tags = array(       
            '#<ul(( .*?)?)>(.*?)</ul>#si' => '[list]\\3[/list]',
            '#<ol(( .*?)?)>(.*?)</ol>#si' => '[list=1]\\3[/list]',
        );
        $tags = array(
            '#\s*?<strong>(.*?)</strong>\s*?#si' => '[b]\\1[/b]',
            '#<b(( .*?)?)>(.*?)</b>#si' => '[b]\\3[/b]',
            '#<em(( .*?)?)>(.*?)</em>#si' => '[i]\\3[/i]',
            '#<i(( .*?)?)>(.*?)</i>#si' => '[i]\\3[/i]',
            '#<u(( .*?)?)>(.*?)</u>#si' => '[u]\\3[/u]',
            '#<s(( .*?)?)>(.*?)</s>#si' => '[s]\\3[/s]',
            '#<img (.*?)src="(.*?)"(.*?)>#si' => "[img]\\2[/img]\n",
            '#<a (.*?)href="(.*?)mailto:(.*?)"(.*?)>(.*?)</a>#si' => '\\3',
            '#<a (.*?)href="(.*?)"(.*?)>(.*?)</a>#si' => '[url=\\2]\\4[/url]',
            '#<code(( .*?)?)>(.*?)</code>#si' => '[code]\\3[/code]',
            '#<iframe style="(.*?)" id="ytplayer" type="text/html" width="534" height="401" src="(.*?)/embed/(.*?)" frameborder="0"/></iframe>#si' => '[youtube]\\3[/youtube]',
            '#\s*?<br(.*?)>\s*?#si' => "\n",
            '#<h2(( .*?)?)>(.*?)</h2>#si' => '[h1]\\3[/h1]',
            '#<h3(( .*?)?)>(.*?)</h3>#si' => '[h2]\\3[/h2]',
            '#<h4(( .*?)?)>(.*?)</h4>#si' => '[h3]\\3[/h3]',
            '#<li(( .*?)?)>(.*?)</li>#si' => '[*]\\3[/*]',
            '#<center(( .*?)?)>(.*?)</center>#si' => '[center]\\3[/center]',
            '#<blockquote(( .*?)?)>(.*?)</blockquote>#si' => '[quote]\\3[/quote]',
            '#<noscript(( .*?)?)>(.*?)</noscript>#si' => '\\3',
            '#<object(.*?)>.*?<param .*?name="movie"[^<]*?value="(.*?)".*?(></param>|/>|>).*?</object>#si' => '[flash]\\2[/flash]',
            '#<object(.*?)>.*?<param .*?value="(.*?)"[^<]*?name="movie".*?(></param>|/>|>).*?</object>#si' => '[flash]\\2[/flash]',
            '#<embed (.*?)src="([^<]*?)"[^<]*?flashvars="([^<]*?)"([^<]*?)(></embed>|/>|>)#si' => '[flash]\\2?\\3[/flash]',
            '#<embed (.*?)src="([^<]*?)"([^<]*?)(></embed>|/>|>)#si' => '[flash]\\2[/flash]',
        );
        foreach ($recursive_tags as $search => $replace) {
            $text2 = $text;
            do {
                $text = $text2;
                $text2 = preg_replace($search, $replace, $text);
            } while ($text2 != $text);
        }
        foreach ($tags as $search => $replace) {
            $text = preg_replace($search, $replace, $text);
        }

        $html = $this->decode_html($text);
        return strip_tags($html);
    }

    public static function getConfigKeys() {
        return array(
            'use_authors',
            'use_topics',
            'use_avatars',
            'use_pubtime',
            'use_comments',
            'min_time',
            'default_password',
            'default_forum',
            'isDebug',
            'custome_category_config',
            'custome_category'
        );
    }

    protected function decode_html($text) {
        $text2 = $text;
        do {
            $text = $text2;
            $text2 = html_entity_decode($text, ENT_COMPAT, "UTF-8");
        } while ($text2 != $text);
        return $text;
    }

}