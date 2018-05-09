<?php
require_once dirname(__DIR__).'/library/config.php';
require_once dirname(__DIR__).'/library/functionGlobal.php';
require_once dirname(__DIR__).'/library/basic.php';
require_once dirname(__DIR__).'/library/member.php';
require_once dirname(__DIR__).'/library/question.php';
define('FINNDY_PATH', dirname(dirname(dirname(dirname(dirname(__FILE__))))) . DIRECTORY_SEPARATOR);
/**
 * 应用前台入口
 */
header('Access-Control-Allow-Origin:*');
class IndexController extends PwBaseController {

    private $file = 'EXT:finndy.conf';
    private $default = array();

    public function beforeAction($handlerAdapter) {
            parent::beforeAction($handlerAdapter);
            $this->file = Wind::getRealPath($this->file, false);
            set_error_handler(array(&$this,"appError"));
    }

    public function appError() {
        echo "123";
    }

    public function detailsAction() {
        $forumService = $this->_getFroumService();
        $map = $forumService->getForumMap();
        $catedb = $map[0];
        $detail = array();

        foreach ($catedb as $key => $value) {
            $forumList[$value['fid']] = $forumService->getForumsByLevel($value['fid'], $map);
        }

        foreach ($map[0] as $forum) {
            //$detail[] = array('value' => $forum['fid'], 'text' => urlencode($forum['name']));
            if (isset($forumList[$forum['fid']])) {
                foreach ($forumList[$forum['fid']] as $k => $v) {
                    $detail[] = array('id' => $v['fid'], 'name' => $v['name'] . '(' . $v['fupname'] . ')');
                }
            }
        }

        if (!empty($detail)) {
            echo json_encode(array('status'=>1,'msg'=>'','data'=>$detail));exit();
        }else{
            echo json_encode(array('status'=>0,'msg'=>'err','data'=>''));exit();
        }
    }

    public function versionAction() {
        $postData = $this->getRequest()->getPost();
        //$this->finndy_validation($postData);
        $reply = finndy_get_version();
        finndy_success($reply);
    }

   

    public function postAction() {
        $postData = $this->getRequest()->getPost();
        $this->finndy_validation($postData);
        $question = new finndy_question($postData);
        $question->processData();
    }

    private function finndy_validation($postData) {
        $conf = @include $this->file;
        $conf || $conf = $this->default;
        if (!($postData && isset($postData['token']) && $postData['token']) || !(isset($conf['token']) && isset($conf['token']) && $conf['token'] == $postData['token'])) {
            finndy_fail(FINNDYERROR_INVALID_PWD, "password is wrong", "发布密码填写错误");
        }
    }

    protected function _getFroumService() {
        return Wekit::load('forum.srv.PwForumService');
    }

}
