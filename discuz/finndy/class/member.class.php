<?php

if (!defined('IN_DISCUZ')) {
    exit('Access Denied');
}

/**
 * Description of member
 *
 * @author Carry
 */
class finndy_member extends finndy_basic {

    public function getUsername($username) {
        $md5author = substr(md5($username), 8, 13);
        return $md5author;
    }
    public function getMember($username) {
        $uid = '';
        try {
            $uid = DB::result(DB::query("SELECT c.`uid` FROM " . DB::table('common_member') . " as c where c.`username` = '" . daddslashes(mysql_real_escape_string($username)) . "'"), 0);
        } catch (finndy_exception $exc) {
            throw $exc;
        }

        if (!$uid) {
            try {
                $uid = $this->insertMember($username);
            } catch (finndy_exception $exc) {
                throw $exc;
            }
        }
        return $uid;
    }

    public function insertMemberAvatar($uid, $remoteAvatar) {
        $this->get_user_avatar($uid, $remoteAvatar);
    }

    public function insertMember($username) {

        $email = $this->randEmail($username);

        $md5author = $this->getUsername($username);
        $uid = uc_user_register($md5author, $this->getPassword(), $email);


        if ($uid <= 0) {
            finndyfail(FINNDYERROR_ERROR, array('position' => 'insertmember', 'uid' => $uid, 'username' => $md5author, 'o_name' => $username, 'email' => $email, 'password' => $this->getPassword()), '用户插入数据库失败');
        }
        $gid = rand(10, 13);
        $credits = 0;
        try {
            $usergroupQuery = DB::query("SELECT creditshigher, creditslower FROM " . DB::table('common_usergroup') . " WHERE  groupid ='%d'", array($gid));

            while ($usergroup = DB::fetch($usergroupQuery)) {
                $credits = rand($usergroup['creditslower'], $usergroup['creditshigher']);
            }
        } catch (finndy_exception $exc) {
            throw $exc;
        }

        $userdata = array(
            'uid' => $uid,
            'username' => mysql_real_escape_string($username),
            'password' => md5(md5($this->getPassword())),
            'email' => mysql_real_escape_string($email),
            'adminid' => 0,
            'groupid' => $gid,
            'regdate' => $this->randTime(),
            'credits' => $credits,
            'timeoffset' => 9999
        );

        $status_data = array(
            'uid' => $uid,
            'regip' => '127.0.0.1',
            'lastip' => $this->randIp(),
            'lastvisit' => 0,
            'lastactivity' => 0,
            'lastpost' => 0,
            'lastsendmail' => 0,
        );

        $profile['uid'] = $uid;
        $field_forum['uid'] = $uid;
        $field_forum['sightml'] = '';

        $field_home['uid'] = $uid;

        $count_data['uid'] = $uid;

        try {

            DB::insert('common_member', daddslashes($userdata));
            DB::insert('common_member_status', daddslashes($status_data));
            DB::insert('common_member_profile', daddslashes($profile));
            DB::insert('common_member_field_forum', daddslashes($field_forum));
            DB::insert('common_member_field_home', daddslashes($field_home));
            DB::insert('common_member_count', daddslashes($count_data));
        } catch (finndy_exception $e) {
            throw $e;
        }

        return $uid;
    }

    public function get_user_avatar($user_id, $avatar) {
        $imgloc = finndyredirect_url($avatar);
        if (!empty($imgloc) && stripos($imgloc, "http") === 0) {
            $sizes = array('big', 'middle', 'small');
            foreach ($sizes as $size) {
                $avatar = $this->get_uc_server_dir() . 'data/avatar/' . $this->get_avatar($user_id, $size);
                if (!$this->httpcopy($imgloc, $avatar)) {
                    if (isset($this->config['isDebug']) && $this->config['isDebug']) {
                        throw new finndy_exception(finndy_exception::TYPE_ERROR, array($imgloc, $avatar));
                    }
                }
            }
        }
    }

    function get_uc_server_dir() {
        $str = '';
        $str = str_replace('uc_client', 'uc_server', UC_ROOT);
        $str = str_replace('\\', '/', $str);
        return $str;
    }

    public function httpcopy($url, $file = "", $timeout = 60) {
        $file = empty($file) ? pathinfo($url, PATHINFO_BASENAME) : $file;
        $dir = pathinfo($file, PATHINFO_DIRNAME);
        !is_dir($dir) && @mkdir($dir, 0755, true);
        $url = str_replace(" ", "%20", $url);

        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            $temp = curl_exec($ch);
            if (@file_put_contents($file, $temp) && !curl_error($ch)) {
                return $file;
            } else {
                return false;
            }
        } else {
            $opts = array(
                "http" => array(
                    "method" => "GET",
                    "header" => "",
                    "timeout" => $timeout)
            );
            $context = stream_context_create($opts);
            if (@copy($url, $file, $context)) {
                //$http_response_header
                return $file;
            } else {
                return false;
            }
        }
    }

    public function get_avatar($uid, $size = 'middle', $type = '') {
        $size = in_array($size, array('big', 'middle', 'small')) ? $size : 'middle';
        $uid = abs(intval($uid));
        $uid = sprintf("%09d", $uid);
        $dir1 = substr($uid, 0, 3);
        $dir2 = substr($uid, 3, 2);
        $dir3 = substr($uid, 5, 2);
        $typeadd = $type == 'real' ? '_real' : '';
        return $dir1 . '/' . $dir2 . '/' . $dir3 . '/' . substr($uid, -2) . $typeadd . "_avatar_$size.jpg";
    }

}
