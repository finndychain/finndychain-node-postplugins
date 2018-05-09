<?php

if (!defined('IN_DISCUZ')) {
    exit('Access Denied');
}
require_once(DISCUZ_ROOT . 'source/plugin/finndy/class/basic.class.php');

class finndy_question extends finndy_basic {

    public $memberModel;
    public $postData;
    public $config = array();

    public static function getPostFormat() {
        $container = array(
            'thread' => array(
                'tid' => '',
                'fid' => '',
                'authorid' => '',
                'author' => '',
                'subject' => '',
                'views' => '', //Hits
                'replies' => '',
                'digest' => '',
                'lastposter' => '',
                'price' => 0,
            ),
            'postTableId' => array(), //pid
            'threadpartake' => array(), //tid, uid, datetime
            'post' => array(
                'pid' => '',
                'fid' => '',
                'tid' => '',
                'first' => 0,
                'author' => '',
                'authorid' => '',
                'subject' => '',
                'message' => ''
            )
        );
    }

    public static function getQuestionKeys() {
        return array(
            'question_title' => '',
            'question_author' => '匿名用户',
            'question_detail' => '',
            'question_topics' => '',
            'question_view_count' => 0,
            'question_answer' => '',
            'question_publish_time' => time(),
            'question_author_avatar' => '',
            'question_categories' => '',
            'audit' => ''
        );
    }

    public static function getAnswerKeys() {
        return array(
            'question_answer_content' => '',
            'question_answer_author' => '匿名用户',
            'question_answer_agree_count' => 0,
            'question_answer_publish_time' => 0,
            'question_answer_author_avatar' => '',
            'question_answer_comment' => ''
        );
    }

    public static function getCommentKeys() {
        return array(
            'question_answer_comment_author',
            'question_answer_comment_author_avatar',
            'question_answer_comment_content',
            'question_answer_comment_publish_time'
        );
    }

    public function __construct($postData) {
        parent::__construct();

        $this->postData = $postData;
        $this->getConfigInfo($postData);

        return $this;
    }

    public function getDefaultValue($key, $stdInputs) {
        if (isset($stdInputs[$key])) {
            return $stdInputs[$key];
        }

        return '';
    }

    public function getConfigInfo($postData) {
        global $_G;
        loadcache('forums');
        if (count($postData)) {
            foreach ($postData as $key => $item) {
                if (in_array($key, self::getConfigKeys())) {
                    $this->config[$key] = $item;
                }
            }
        }

        if (!isset($this->config['min_time'])|| !$this->config['min_time']) {
            $this->config['min_time'] = time() - 10000000;
        }
        if (!isset($this->config['default_forum']) || !$this->config['default_forum']) {
            $forums = $_G['cache']['forums'];
            foreach ($forums as $k => $v) {
                if(isset($v['type']) && $v['type'] == 'forum') {
                    $this->config['default_forum'] = $v['fid'];
                    break;
                }
            }
        }
    }

    public function getAnswersToPost($answerJson) {
        $answers = json_decode($answerJson, true);
        $posters = array();
        if (!($answers && count($answers))) {
            return array();
        }
        $keys = $this->getAnswerKeys();
        foreach ($answers as $answer) {
            $post = array();
            foreach ($answer as $akey => $value) {
                if (isset($keys[$akey])) {
                    if ($akey == 'question_answer_author') {
                        if (!$value) {
                            finndyfail(FINNDYERROR_MISSING_FIELD, "Missing question_answer_author", "缺少回复人名称字段");
                        }
                        $uid = $this->memberModel->getMember($value);
                        $post['author'] = $this->_value($value, $keys[$akey]);
                        $post['authorid'] = $uid;
                    } else if ($akey == 'question_answer_content') {
                        $post['message'] = $this->processMessage($value);
                    } else if ($akey == 'question_answer_publish_time') {
                        $post['dateline'] = $this->_value($value, $keys[$akey]);
                    } else if ($akey == 'question_answer_author_avatar') {
                        $post['avatar'] = $this->_value($value, $keys[$akey]);
                    } else if ($akey == 'question_answer_comment') {
                       
                    } else if ($akey == 'question_answer_agree_count') {
                        
                    }
                }
            }
            if (isset($post['avatar']) && $post['avatar']) {
                $this->memberModel->insertMemberAvatar($post['authorid'], $post['avatar']);
            }
            $post['first'] = 0;

            unset($post['avatar']);
            if (!empty($post)) {
                $posters[] = $post;
            }
        }

        return $posters;
    }

    public function processMessage($content) {
        $content = $this->parse_html($content);
        return $content;
    }

    public function loadPostData() {
        $post = array();
        $otherPosts = array();
        $keys = $this->getQuestionKeys();
        foreach ($this->postData as $qkey => $value) {
            if (isset($keys[$qkey])) {
                if ($qkey == 'question_answer') {
                    $otherPosts = $this->getAnswersToPost($value);
                } else if ($qkey == 'question_topics' || $qkey == 'question_categories') {
                    //$questionItem = json_decode($value, true);
                } else if ($qkey == 'question_title') {
                    if (!$value) {
                        finndyfail(FINNDYERROR_MISSING_FIELD, "Missing question_title", "缺少帖子标题");
                    }
                    $post['subject'] = $this->_value($value, $keys[$qkey]);
                } else if ($qkey == 'question_author') {
                    if (!$value) {
                        finndyfail(FINNDYERROR_MISSING_FIELD, "Missing question_author", "缺少发帖人名称字段");
                    }
                    $uid = $this->memberModel->getMember($value);
                    $post['author'] = $this->_value($value, $keys[$qkey]);
                    $post['authorid'] = $uid;
                } else if ($qkey == 'question_detail') {
                    $post['message'] = $this->processMessage($value);
                } else if ($qkey == 'question_view_count') {
                    $post['views'] = $this->_value($value, $keys[$qkey]);
                } else if ($qkey == 'question_publish_time') {
                    $post['dateline'] = $this->_value($value, $keys[$qkey]);
                } else if ($qkey == 'question_author_avatar') {
                    $post['avatar'] = $this->_value($value, $keys[$qkey]);
                }else if ($qkey == 'audit') {
                    $post['audit'] = $this->_value($value, $keys[$qkey]);
                }
            }
        }

        if (isset($post['avatar']) && $post['avatar']) {
            $this->memberModel->insertMemberAvatar($post['authorid'], $post['avatar']);
        }
        $post['first'] = 1;

        unset($post['avatar']);
        return array_merge(array($post), $otherPosts);
    }

    public function insertThread($posts) {
        $fid = isset($this->config['default_forum']) ? $this->config['default_forum'] : 1;
        $tid = '';
        $price = 0;
        $typeid = 0;
        $sortid = 0;
        $isgroup = 0;
        $replycredit = 0;
        $displayorder = 0;
        $digest = 0;
        $special = 0;
        if ($posts && count($posts) && isset($posts[0]['author'])) {
            $audit = $posts[0]['audit'];
            if ($audit) {
                $displayorder = -2;//待审核
                $audit_time = time();
            }
            $author = $posts[0]['author'];
            $uid = intval($posts[0]['authorid']);
            $subject = $posts[0]['subject'];
            $dateline = intval($posts[0]['dateline']);
            $count = count($posts);
            if (!empty($posts[$count - 1]) && isset($posts[$count - 1]['author'])) {
                $lastpost = intval($posts[$count - 1]['dateline']);
                $lastposter = $posts[$count - 1]['author'];
            }
            $replies_num = ($count - 1);
            if (isset($posts[0]['views']) && $posts[0]['views']) {
                $view_num = $posts[0]['views'];
            } else {
                $view_num = rand($replies_num, ($replies_num) * 2);
            }
        }

        try {
            DB::query("INSERT INTO " . DB::table('forum_thread') . " (fid, posttableid, readperm, price, typeid, sortid, author, authorid, subject, dateline, lastpost, lastposter, views, displayorder, digest, special, attachment, moderated, status, isgroup, replycredit, closed, replies, maxposition) VALUES ('" . $fid . "', '0', '0', '$price', '$typeid', '$sortid', '" . daddslashes($author) . "', '$uid', '" . daddslashes($subject) . "', '$dateline', '$lastpost', '" . daddslashes($lastposter) . "', '$view_num', '$displayorder', '$digest', '$special', '0', '0', '32', '$isgroup', '$replycredit', '0', '$replies_num', '$count')");
            $tid = DB::insert_id();
            if ($tid) {
                DB::query("INSERT INTO " . DB::table('forum_threadpartake') . " (tid, uid, dateline) VALUES ('" . $tid . "', '" . $uid . "', '" . $dateline . "')");
                DB::query("INSERT INTO " . DB::table('forum_newthread') . " (tid, fid, dateline) VALUES ('" . $tid . "', '" . $fid . "', '" . $dateline . "')");
                DB::query("UPDATE " . DB::table('common_member_count') . " SET threads=threads+1 WHERE uid='$uid'");
                DB::query("UPDATE " . DB::table('forum_forum') . " SET threads=threads+1 WHERE fid='" . $fid . "'");
                if ($audit) {
                    DB::query("INSERT INTO " . DB::table('forum_thread_moderate') . " (id, status, dateline) VALUES ('" . $tid . "', '0', '" . $audit_time . "')");
                }
            }
        } catch (finndy_exception $e) {
            throw $e;
        }

        return $tid;
    }

    public function getLastPid() {
        $info = DB::fetch_first("SELECT pid FROM " . DB::table('forum_post') . " order by pid desc");
        return isset($info['pid']) && $info['pid'] ? $info['pid'] : 0;
    }

    public function insertPosts($tid, $posts) {
        $fid = isset($this->config['default_forum']) ? $this->config['default_forum'] : 2;
        $lastpid = $this->getLastPid();
        foreach ($posts as $post) {
            $lastpid++;
            $audit = $post['audit'];
            if ($audit) {
                $invisible = -2;
            }else{
                $invisible = 0;
            }
            DB::query("INSERT INTO " . DB::table('forum_post') . " (`pid`, `fid`, `tid`, `first`, `author`, `authorid`, `subject`, `dateline`, `message`, `useip`, `port`, `invisible`, `anonymous`, `usesig`, `htmlon`, `bbcodeoff`, `smileyoff`, `parseurloff`, `attachment`, `rate`, `ratetimes`, `status`, `tags`, `comment`, `replycredit`, `position`) VALUES ('" . $lastpid . "', '" . $fid . "', '" . $tid . "', '" . ($post['first']) . "', '" . daddslashes($post['author']) . "', '" . intval($post['authorid']) . "', '" . (isset($post['subject']) ? daddslashes($post['subject']) : '') . "', '" . intval($post['dateline']) . "', '" . daddslashes($post['message']) . "', '::1', '0', '".$invisible."', '0', '1', '0', '0', '-1', '0', '0', '0', '0', '0', '0', '0', '0', NULL)");
            DB::query("INSERT INTO " . DB::table('forum_post_tableid') . " (`pid`) VALUES ('%d')", array(intval($lastpid)));
            DB::query("UPDATE " . DB::table('common_member_count') . " SET posts=posts+1 WHERE uid=%d", array(intval($post['authorid'])));
            //放入审核表
            DB::query("UPDATE " . DB::table('forum_forum') . " SET posts=posts+1 WHERE fid=%d", array(intval($fid)));
        }
    }

    public function insertData($posts) {
        global $_G;
        if (empty($posts)) {
            finndyfail(FINNDYERROR_ERROR, "Nothing to insert", "没有可以发布的内容");
        }
        $tid = $this->insertThread($posts);
        $this->insertPosts($tid, $posts);
        finndysuccess(array("url" => $_G['siteurl'] . "forum.php?mod=viewthread&tid=" . $tid));
    }

    public function processData() {
        $this->memberModel = new finndy_member();
        $posts = $this->loadPostData();
        $this->insertData($posts);
    }

}
