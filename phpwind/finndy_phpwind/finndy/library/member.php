<?php
require_once 'basic.php';
Wind::import('SRV:user.vo.PwUserSo');
Wind::import('SRV:user.dao.PwUserBelongDao');

class finndy_member extends finndy_basic {

    public function getUsername($username) {
        $md5author = substr(md5($username), 8, 13);
        return $md5author;
    }

    public function getMember($username) {
        $uid = '';
        try {
            $vo = new PwUserSo();
            $vo->setUsername($this->getUsername($username));
            $searchDs = Wekit::load('user.PwUserSearch');
            $result = $searchDs->searchUser($vo, 1, 0);
            if (count($result)) {
                foreach ($result as $user) {
                    $uid = $user['uid'];
                }
            }
        } catch (\Exception $exc) {
            throw $exc;
        }

        if (!$uid) {
            try {
                $uid = $this->insertMember($username);
            } catch (\Exception $exc) {
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
        $uid = 0;

        try {
            Wind::import('SRC:service.user.dm.PwUserInfoDm');
            $dm = new PwUserInfoDm();
            $dm->setUsername($md5author)
                    ->setPassword($this->getPassword())
                ->setEmail($email)
                ->setRegdate($this->randTime())
                    ->setRegip($this->randIp());
            $groupid = 0;
            $dm->setGroupid($groupid);
            /* @var $groupService PwUserGroupsService */
            $groupService = Wekit::load('usergroup.srv.PwUserGroupsService');
            $memberid = $groupService->calculateLevel(0);
            $dm->setMemberid($memberid);

            $result = Wekit::load('user.PwUser')->addUser($dm);
            if ($result instanceof PwError) {
                finndy_fail(FINNDYERROR_ERROR, array('position' => 'insertmember', 'uid' => $uid, 'username' => $md5author, 'o_name' => $username, 'email' => $email, 'password' => $this->getPassword()), '用户插入 数据库失败,'.json_encode($result->getError()));
            }
            //添加站点统计信息
            Wind::import('SRV:site.dm.PwBbsinfoDm');
            $bbsDm = new PwBbsinfoDm();
            $bbsDm->setNewmember($dm->getField('username'))->addTotalmember(1);
            Wekit::load('site.PwBbsinfo')->updateInfo($bbsDm);
            //Wekit::load('user.srv.PwUserService')->restoreDefualtAvatar($result);
            $uid = $dm->uid;
        } catch (\Exception $e) {
            finndy_fail(FINNDYERROR_ERROR, array('position' => 'insertmember', 'uid' => $uid, 'username' => $md5author, 'o_name' => $username, 'email' => $email, 'password' => $this->getPassword()), '用户插入数据库失败,'.$e->getMessage());
        }

        if ($uid) {
            $pwUserBelongDao = new PwUserBelongDao();
            $pwUserBelongDao->edit($uid, array(
                'gid' => 8
            ));

            $pwUserDataDao = new PwUserDataDao();
            $pwUserDataDao->editUser($uid, array(
                'credit1' => 10,
                'credit2' => 10
            ));
        }

        return $uid;
    }

    public function get_user_avatar($user_id, $avatar) {
        $imgloc = finndy_redirect_url($avatar);
        if ($imgloc !== false && isset($imgloc['realurl']) && $imgloc['realurl']) {
            $sizes = array('', 'middle', 'small');
            foreach ($sizes as $size) {
                $avatar =  FINNDY_PATH. '/windid/attachment/avatar/' . $this->get_avatar($user_id, $size);
                if (!$this->httpcopy($imgloc['realurl'], $avatar, $imgloc['referer'])) {
                    if (isset($this->config['isDebug']) && $this->config['isDebug']) {
                        throw new Exception(FINNDYERROR_ERROR, array($imgloc['realurl'], $avatar));
                    }
                }
            }
        }
    }

    public function httpcopy($url, $file = "", $referer = "", $timeout = 60) {
        $file = empty($file) ? pathinfo($url, PATHINFO_BASENAME) : $file;
        $dir = pathinfo($file, PATHINFO_DIRNAME);
        !is_dir($dir) && @mkdir($dir, 0755, true);
        $url = str_replace(" ", "%20", $url);

        if (function_exists('curl_init')) {
            $ch = curl_init();
            $headers = array('Referer: '.$referer);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
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
                    "header" => 'Referer: '.$referer."\r\n",
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
        $size = in_array($size, array('', 'middle', 'small')) ? $size : 'middle';
        $uid = abs(intval($uid));
        $uid = sprintf("%09d", $uid);
        $dir1 = substr($uid, 0, 3);
        $dir2 = substr($uid, 3, 2);
        $dir3 = substr($uid, 5, 2);
        return $dir1 . '/' . $dir2 . '/' . $dir3 . '/' . substr($uid, -2) . ($size ? "_" . $size : '') . ".".jpg;
    }

}
