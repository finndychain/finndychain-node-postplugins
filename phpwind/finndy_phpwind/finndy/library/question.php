<?php
require_once 'basic.php';
require_once 'member.php';
require_once 'FinndyThreadDao.php';
Wind::import('SRV:forum.srv.PwPost');
Wind::import('SRV:forum.dao.PwForumStatisticsDao');
Wind::import('SRV:forum.dao.PwThreadsCateIndexDao');
Wind::import('SRV:forum.dao.PwThreadsIndexDao');
Wind::import('SRV:forum.dao.PwThreadsContentDao');
Wind::import('SRV:forum.dao.PwThreadsHitsDao');
Wind::import('SRV:forum.dao.PwThreadsDao');
Wind::import('SRV:forum.dao.PwPostsDao');
Wind::import('SRV:user.dao.PwUserDataDao');
Wind::import('SRV:forum.srv.PwForumMiscService');

class finndy_question extends finndy_basic {

    public $memberModel;
    public $postData;
    public $config = array();
    public $threadDao = null;

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
            'question_categories' => ''
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
            $forums = $this->getFroums();
            foreach ($forums as $k => $v) {
                $this->config['default_forum'] = $v['value'];
                break;
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
                            finndy_fail(FINNDYERROR_MISSING_FIELD, "Missing question_answer_author", "缺少回复人名称字段");
                        }
                        $uid = $this->memberModel->getMember($value);
                        $post['author'] = $this->_value($value, $keys[$akey]);
                        $post['authorid'] = $uid;
                    } else if ($akey == 'question_answer_content') {
                        if (!$value) {
                            finndy_fail(FINNDYERROR_MISSING_FIELD, "Missing question_answer_content", "缺少回复内容字段");
                        }
                        $post['message'] = $this->processMessage($value);
                    } else if ($akey == 'question_answer_publish_time') {
                        $post['dateline'] = $this->_value($value, $keys[$akey]);
                    } else if ($akey == 'question_answer_author_avatar') {
                        $post['avatar'] = $this->_value($value, $keys[$akey]);
                    } else if ($akey == 'question_answer_comment') {
                        //$post['avatar'] =  $this->_value($value, $keys[$qkey]);
                    } else if ($akey == 'question_answer_agree_count') {
                        $post['agree_count'] =  $this->_value($value, $keys[$qkey]);
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
                    $post['topics'] = json_decode($value, true);
                } else if ($qkey == 'question_title') {
                    if (!$value) {
                        finndy_fail(FINNDYERROR_MISSING_FIELD, "Missing question_title", "缺少帖子标题");
                    }
                    $post['subject'] = $this->_value($value, $keys[$qkey]);
                } else if ($qkey == 'question_author') {
                    if (!$value) {
                        finndy_fail(FINNDYERROR_MISSING_FIELD, "Missing question_author", "缺少发帖人名称字段");
                    }
                    $uid = $this->memberModel->getMember($value);
                    $post['author'] = $this->_value($value, $keys[$qkey]);
                    $post['authorid'] = $uid;
                } else if ($qkey == 'question_detail') {
                    if (!$value) {
                        finndy_fail(FINNDYERROR_MISSING_FIELD, "Missing question_detail", "缺少帖子内容字段");
                    }
                    $post['message'] = $this->processMessage($value);
                } else if ($qkey == 'question_view_count') {
                    $post['views'] = $this->_value($value, $keys[$qkey]);
                } else if ($qkey == 'question_publish_time') {
                    $post['dateline'] = $this->_value($value, $keys[$qkey]);
                } else if ($qkey == 'question_author_avatar') {
                    $post['avatar'] = $this->_value($value, $keys[$qkey]);
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
        $uid = 0;
        if ($posts && count($posts) && isset($posts[0]['author'])) {
            $author = $posts[0]['author'];
            $uid = intval($posts[0]['authorid']);
            $title = $posts[0]['subject'];
            $content = $posts[0]['message'];
            $topics = $posts[0]['topics'];
            $dateline = intval($posts[0]['dateline']);
            $count = count($posts);
            if (!empty($posts[$count - 1]) && isset($posts[$count - 1]['author'])) {
                $lastpost = intval($posts[$count - 1]['dateline']);
                $lastposter = $posts[$count - 1]['author'];
                $lastposterid = $posts[$count - 1]['authorid'];
            }
            $replies_num = ($count - 1);
            if (isset($posts[0]['views']) && $posts[0]['views']) {
                $view_num = $posts[0]['views'];
            } else {
                $view_num = rand($replies_num, ($replies_num) * 2);
            }

            unset($posts[0]);
        }

        try {
                $special = 'default';
                Wind::import('SRV:forum.srv.post.PwTopicPost');
                $postAction = new PwTopicPost($fid);
                $special && $postAction->setSpecial($special);
		$pwPost = new PwPost($postAction);
		//$this->runHook('c_post_doadd', $pwPost);

		$postDm = $pwPost->getDm();
		$postDm->setTitle($title)
			->setContent($content)
			->setReplyNotice(0);
                $postDm->setAuthor($uid, $author, $this->randIp());
                $postDm->setCreatedTime($dateline);
                $postDm->addReplies($count);
                $postDm->setTopictype(0);
                $postDm->setDisabled(0);

		//set topic type


		if (($result = $pwPost->execute($postDm)) !== true) {
			$data = $result->getData();
			$data && $this->addMessage($data, 'data');
			if (strpos($result->getError()[0], 'post.content.length.more') !== false) {
			    finndy_fail(FINNDYERROR_ERROR, $result->getError(), "发布主题失败, 请到到phpwind网站后台设置论坛-》内容长度");
			} else {
			    finndy_fail(FINNDYERROR_ERROR, $result->getError(), "发布主题失败");
			}
		}
		$tid = $pwPost->getNewId();

                $this->threadDao = new FinndyThreadDao();
                $this->threadDao->updateThread($tid, array(
                    'disabled' => 0,
                    'ischeck' => 1,
                    'replies' => $replies_num,
                    'hit' => $view_num,
                    'created_time' => $dateline,
                    'created_username' => $author,
                    'created_userid' => $uid,
                    'lastpost_time' => $lastpost,
                    'lastpost_username' => $lastposter,
                    'lastpost_userid' => $lastposterid
                ));

                $pwThreadsCateIndexDao = new PwThreadsCateIndexDao();
                $pwThreadsCateIndexDao->updateThread($tid, array(
                    'disabled' => 0,
                    'created_time' => $dateline,
                    'lastpost_time' => $lastpost
                ));

                $pwThreadsContentDao = new PwThreadsContentDao();
                $pwThreadsContentDao->updateThread($tid, array(
                    'useubb' => 1,
                    'usehtml' => 1
                ));

                if ($view_num) {
                    $pwThreadsHitsDao = new PwThreadsHitsDao();
                    $pwThreadsHitsDao->add(array('tid' => $tid, 'hits' => $view_num));
                }

                $pwThreadsIndexDao = new PwThreadsIndexDao();
                $pwThreadsIndexDao->updateThread($tid, array(
                    'disabled' => 0,
                    'created_time' => $dateline,
                    'lastpost_time' => $lastpost
                ));

                $taForumStatistics = new PwForumStatisticsDao();
                $forumStatistic = $taForumStatistics->getForum($fid);

                $taForumStatistics->updateForum($fid, array(
                    'todayposts' => $forumStatistic['todayposts'],
                    'todaythreads' => $forumStatistic['todaythreads'],
                    'article' => $forumStatistic['article']+1+count($posts),
                    'posts' => $forumStatistic['posts']+count($posts),
                    'threads' => $forumStatistic['threads']+1,
                    'lastpost_time' => $lastpost,
                    'lastpost_username' => $lastposter,
                    'lastpost_tid' => $tid
                ));

                $pwUserDataDao = new PwUserDataDao();
                $userData = $pwUserDataDao->getUserByUid($uid);

                $pwUserDataDao->editUser($uid, array(
                    'lastvisit' => $dateline,
                    'lastactivetime' => $dateline,
                    'lastpost' => $dateline,
                    'postnum' => $userData['postnum'] + 1,
                    'credit1' => $userData['credit1'] + 2,
                    'credit2' => $userData['credit2'] + 4
                ));

                if ($this->isToday($dateline)) {
                    $taForumStatistics->updateForum($fid, array(
                        'todaythreads' => $forumStatistic['todaythreads'] + 1,
                    ));
                    $pwUserDataDao->editUser($uid, array(
                        'todaypost' => $userData['todaypost'] + 1
                    ));
                }
        } catch (\Exception $e) {
            throw $e;
        }

        return $tid;
    }

    public function insertPosts($tid, $posts) {
        $fid = isset($this->config['default_forum']) ? $this->config['default_forum'] : 2;
        foreach ($posts as $postvar) {
            $title = $postvar['subject'];
            $content = $postvar['message'];
            $author = $postvar['author'];
            $uid = intval($postvar['authorid']);
            $dateline = intval($postvar['dateline']);
            $_getHtml = 1;
            Wind::import('SRV:forum.srv.post.PwReplyPost');
            $postAction = new PwReplyPost($tid);
            $pwPost = new PwPost($postAction);
            //$this->runHook('c_post_doreply', $pwPost);

            $info = $pwPost->getInfo();
            $title == 'Re:' . $info['subject'] && $title = '';

            $postDm = $pwPost->getDm();
            $postDm->setTitle($title)
                    ->setContent($content)
                    ->setHide(0)
                    ->setReplyPid(0);
            $postDm->setAuthor($uid, $author, $this->randIp());
            $postDm->setCreatedTime($dateline);
            $postDm->setDisabled(0);

            if (($result = $pwPost->execute($postDm)) !== true) {
                    $data = $result->getData();
                    $data && $this->addMessage($data, 'data');
                    finndy_fail(FINNDYERROR_ERROR, $result->getError(), "发布回复失败");
            }
            $pid = $pwPost->getNewId();

            if ($_getHtml == 1) {
                Wind::import('SRV:forum.srv.threadDisplay.PwReplyRead');
                Wind::import('SRV:forum.srv.PwThreadDisplay');
                $threadDisplay = new PwThreadDisplay($tid, PwUserBo::getInstance($postvar['authorid']));
                //$this->runHook('c_post_replyread', $threadDisplay);
                $dataSource = new PwReplyRead($tid, $pid);
                $threadDisplay->execute($dataSource);
            }

            $pwPostsDao = new PwPostsDao();
            $pwPostsDao->updatePost($pid, array(
                'ischeck' => 1,
                'disabled' => 0,
                'useubb' => 1,
                'usehtml' => 1,
                'created_time' => $dateline,
                'created_username' => $author,
                'created_userid' => $uid
            ));

            $pwUserDataDao = new PwUserDataDao();
            $userData = $pwUserDataDao->getUserByUid($uid);
            $pwUserDataDao->editUser($uid, array(
                'lastvisit' => $dateline,
                'lastactivetime' => $dateline,
                'lastpost' => $dateline,
                'postnum' => $userData['postnum'] + 1,
                'credit1' => $userData['credit1'] + 3
            ));

            if ($this->isToday($dateline)) {
                $pwUserDataDao->editUser($uid, array(
                    'todaypost' => $userData['todaypost'] + 1
                ));

                $taForumStatistics = new PwForumStatisticsDao();
                $forumStatistic = $taForumStatistics->getForum($fid);

                $taForumStatistics->updateForum($fid, array(
                    'todayposts' => $forumStatistic['todayposts']+1
                ));
            }
        }
    }

    public function insertData($posts) {
        $fid = isset($this->config['default_forum']) ? $this->config['default_forum'] : 2;
        if (empty($posts)) {
            finndy_fail(FINNDYERROR_ERROR, "Nothing to insert", "没有可以发布的内容");
        }
        $tid = $this->insertThread($posts);
        if ($tid) {
            unset($posts[0]);
        }
        if (count($posts)) {
            $this->insertPosts($tid, $posts);
        }
        $pwForum = new PwForum();

        finndy_success(array("url" => Wind::getComponent('request')->getBaseUrl(true) . "/read.php?tid=".$tid."&fid=" . $fid));
    }

    public function processData() {
        $this->memberModel = new finndy_member();
        $posts = $this->loadPostData();
        $this->insertData($posts);
        try {
            Wekit::load('forum.srv.PwForumMiscService')->countAllForumStatistics();
        } catch (Exception $e) {

        }
    }

}
