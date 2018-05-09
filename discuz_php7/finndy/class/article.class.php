<?php

if (!defined('IN_DISCUZ')) {
    exit('Access Denied');
}
require_once(DISCUZ_ROOT . 'source/plugin/finndy/class/basic.class.php');

class finndy_article extends finndy_basic {

    public $memberModel;
    public $postData;
    public $config = array();

    public static function getPostFormat() {
        return array(
            'title' => '',
            'author' => '',
            'from' => '',
            'fromurl' => '',
            'dateline' => '',
            'url' => '',
            'allowcomment' => '1',
            'summary' => '',
            'catid' => 0,
            'tag' => '', //article_make_tag($_POST['tag']),
            'status' => 0,
            'highlight' => '',
            'showinnernav' => '0',
            'pic' => '',
            'thumb' => '',
            'remote' => '1',
            'id' => '',
        );
    }

    public static function getArticleKeys() {
        return array(
            'article_title' => '',
            'article_content' => '',
            'article_author' => '匿名用户',
            'article_origin_from' => '',
            'article_topics' => '',
            'article_categories' => '',
            'article_origin_url' => '',
            'article_publish_time' => time(),
            'article_brief' => '',
            'article_thumbnail' => '',
            'article_avatar' => '',
            'article_comment' => ''
        );
    }

    public static function getCommentKeys() {
        return array(
            'article_comment_content' => '',
            'article_comment_author' => '匿名用户',
            'article_comment_publish_time' => time(),
            'article_comment_author_avatar' => 0,
            'article_comment_agree_count' => ''
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
        loadcache('portalcategory');
        if (count($postData)) {
            foreach ($postData as $key => $item) {
                if (in_array($key, self::getConfigKeys())) {
                    $this->config[$key] = $item;
                }
            }
        }

        if (!isset($this->config['min_time']) || !$this->config['min_time']) {
            $this->config['min_time'] = time() - 10000000;
        }
        if (!isset($this->config['default_forum']) || !$this->config['default_forum']) {
            $forums = $_G['cache']['portalcategory'];
            foreach ($forums as $k => $v) {
                $this->config['default_forum'] = $v['catid'];
                break;
            }
        }
    }

    public function getCommentsToPost($commentJson) {
        $comments = json_decode($commentJson, true);
        $posters = array();
        if (!($comments && count($comments))) {
            return array();
        }
        $keys = $this->getCommentKeys();
        foreach ($comments as $comment) {
            $post = array();
            foreach ($comment as $akey => $value) {
                if (isset($keys[$akey])) {
                    if ($akey == 'article_comment_author') {
                        if (!$value) {
                            finndyfail(FINNDYERROR_MISSING_FIELD, "Missing article_comment_author", "缺少回复人名称字段");
                        }
                        $uid = $this->memberModel->getMember($value);
                        $post['author'] = $this->_value($value, $keys[$akey]);
                        $post['authorid'] = $uid;
                    } else if ($akey == 'article_comment_content') {
                        $post['message'] = $value; //$this->processMessage($value);
                    } else if ($akey == 'article_comment_publish_time') {
                        $post['dateline'] = $this->_value($value, $keys[$akey]);
                    } else if ($akey == 'article_comment_author_avatar') {
                        $post['avatar'] = $this->_value($value, $keys[$akey]);
                    } else if ($akey == 'article_comment_agree_count') {
                        //$post['avatar'] =  $this->_value($value, $keys[$qkey]);
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
        $post = self::getPostFormat();
        $otherPosts = array();
        $keys = $this->getArticleKeys();
        foreach ($this->postData as $qkey => $value) {
            if (isset($keys[$qkey])) {
                if ($qkey == 'article_comment') {
                    $otherPosts = $this->getCommentsToPost($value);
                } else if ($qkey == 'article_topics' || $qkey == 'article_categories') {
                    //$questionItem = json_decode($value, true);
                    $post['tag'] = json_decode($value) ? article_make_tag(json_decode($value, true)) : '';
                } else if ($qkey == 'article_title') {
                    if (!$value) {
                        finndyfail(FINNDYERROR_MISSING_FIELD, "Missing article_title", "缺少帖子标题");
                    }
                    $post['title'] = $this->_value($value, $keys[$qkey]);
                } else if ($qkey == 'article_author') {
                    if (!$value) {
                        finndyfail(FINNDYERROR_MISSING_FIELD, "Missing article_author", "缺少发帖人名称字段");
                    }
                    $uid = $this->memberModel->getMember($value);
                    $post['author'] = $this->_value($value, $keys[$qkey]);
                    $post['uid'] = $uid;
                    $post['username'] = $post['author'];
                } else if ($qkey == 'article_content') {
                    $post['message'] = $value;
                    $post['summary'] = $this->getSummary($post['message']);
                } else if ($qkey == 'article_publish_time') {
                    $post['dateline'] = $this->_value($value, $keys[$qkey]);
                } else if ($qkey == 'article_brief' && $post['summary']) {
                    $post['summary'] = $this->_value($value, $keys[$qkey]);
                } else if ($qkey == 'article_thumbnail') {
                    $post['pic'] = $this->_value($value, $keys[$qkey]);
                } else if ($qkey == 'article_origin_from') {
                    $post['from'] = $this->_value($value, $keys[$qkey]);
                } else if ($qkey == 'article_origin_url') {
                    $post['fromurl'] = $this->_value($value, $keys[$qkey]);
                } else if ($qkey == 'article_avatar') {
                    $post['avatar'] = $this->_value($value, $keys[$qkey]);
                }
            }
        }

        if (isset($post['avatar']) && $post['avatar']) {
            $this->memberModel->insertMemberAvatar($post['uid'], $post['avatar']);
        }

        unset($post['avatar']);
        return array($post, $otherPosts);
    }

    public function insertArticle($post) {
        $cid = isset($this->config['default_forum']) ? $this->config['default_forum'] : 1;
        $title = substr(trim($post['title']), 80);
        $author = dhtmlspecialchars($post['author']);
        $url = ''; //str_replace('&amp;', '&', dhtmlspecialchars($post['url']));
        $from = dhtmlspecialchars($post['from']);
        $fromurl = str_replace('&amp;', '&', dhtmlspecialchars($post['fromurl']));
        $dateline = !empty($post['dateline']) ? intval($post['dateline']) : TIMESTAMP;

        if (substr($fromurl, 0, 7) !== 'http://') {
            $fromurl = '';
        }
        $post['catid'] = $cid;
        $content = $post['message'];
        unset($post['message']);
        try {
            $post['id'] = 0;
            $post['htmlname'] = '';
            $aid = C::t('portal_article_title')->insert($post, 1);
            C::t('common_member_status')->update($post['uid'], array('lastpost' => TIMESTAMP), 'UNBUFFERED');
            C::t('portal_category')->increase($post['catid'], array('articles' => 1));
            C::t('portal_category')->update($post['catid'], array('lastpublish' => TIMESTAMP));
            C::t('portal_article_count')->insert(array('aid' => $aid, 'catid' => $post['catid'], 'viewnum' => 1));

            $regexp = '/(\<strong\>##########NextPage(\[title=(.*?)\])?##########\<\/strong\>)+/is';
            preg_match_all($regexp, $content, $arr);
            $pagetitle = !empty($arr[3]) ? $arr[3] : array();
            $pagetitle = array_map('trim', $pagetitle);
            array_unshift($pagetitle, $post['pagetitle']);
            $contents = preg_split($regexp, $content);
            $cpostcount = count($contents);
            $dbcontents = C::t('portal_article_content')->fetch_all($aid);

            $pagecount = $cdbcount = count($dbcontents);
            if ($cdbcount > $cpostcount) {
                $cdelete = array();
                foreach (array_splice($dbcontents, $cpostcount) as $value) {
                    $cdelete[$value['cid']] = $value['cid'];
                }
                if (!empty($cdelete)) {
                    C::t('portal_article_content')->delete($cdelete);
                }
                $pagecount = $cpostcount;
            }
            foreach ($dbcontents as $key => $value) {
                C::t('portal_article_content')->update($value['cid'], array('title' => $pagetitle[$key], 'content' => $contents[$key], 'pageorder' => $key + 1));
                unset($pagetitle[$key], $contents[$key]);
            }

            if ($cdbcount < $cpostcount) {
                foreach ($contents as $key => $value) {
                    C::t('portal_article_content')->insert(array('aid' => $aid, 'id' => $post['id'], 'idtype' => $post['idtype'], 'title' => $pagetitle[$key], 'content' => $contents[$key], 'pageorder' => $key + 1, 'dateline' => $post['dateline']));
                }
                $pagecount = $cpostcount;
            }

            $updatearticle = array('contents' => $pagecount);
            $updatearticle = array_merge($updatearticle, $this->portalcp_article_pre_next($post['catid'], $aid));
            C::t('portal_article_title')->update($aid, $updatearticle);
        } catch (Exception $e) {
            throw $e;
        }

        return $aid;
    }

    public function insertCommits($aid, $comments) {
        global $_G;
        foreach ($comments as $comment) {
            $setarr = array(
                'uid' => $comment['authorid'],
                'username' => $comment['author'],
                'id' => $aid,
                'idtype' => 'aid',
                'postip' => $_G['clientip'],
                'port' => $_G['remoteport'],
                'dateline' => $comment['dateline'],
                'status' => 0,
                'message' => $comment['message']
            );
            $pcid = C::t('portal_comment')->insert($setarr, true);
            C::t('portal_article_count')->increase($aid, array('commentnum' => 1));
            C::t('common_member_status')->update($comment['authorid'], array('lastpost' => $comment['dateline']), 'UNBUFFERED');
            updatecreditbyaction('portalcomment', 0, array(), 'aid' . $aid);
        }
    }

    public function insertData($post, $commits) {
        global $_G;
        if (empty($post)) {
            finndyfail(FINNDYERROR_ERROR, "Nothing to insert", "没有可以发布的内容");
        }
        $aid = $this->insertArticle($post);
        $this->insertCommits($aid, $commits);
        finndysuccess(array("url" => $_G['siteurl'] . "portal.php?mod=view&aid=" . $aid));
    }

    public function processData() {
        $this->memberModel = new finndy_member();
        list($post, $commits) = $this->loadPostData();
        $this->insertData($post, $commits);
    }

    private function getSummary($message) {
        $message = preg_replace(array("/\[attach\].*?\[\/attach\]/", "/\&[a-z]+\;/i", "/\<script.*?\<\/script\>/"), '', $message);
        $message = preg_replace("/\[.*?\]/", '', $message);
        $message = substr(strip_tags($message), 0,100);
        return $message;
    }

    private function portalcp_article_pre_next($catid, $aid) {
        $data = array(
            'preaid' => C::t('portal_article_title')->fetch_preaid_by_catid_aid($catid, $aid),
            'nextaid' => C::t('portal_article_title')->fetch_nextaid_by_catid_aid($catid, $aid),
        );
        if ($data['preaid']) {
            C::t('portal_article_title')->update($data['preaid'], array(
                'preaid' => C::t('portal_article_title')->fetch_preaid_by_catid_aid($catid, $data['preaid']),
                'nextaid' => C::t('portal_article_title')->fetch_nextaid_by_catid_aid($catid, $data['preaid']),
                    )
            );
        }
        return $data;
    }

}
