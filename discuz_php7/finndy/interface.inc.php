<?php 
require_once(DISCUZ_ROOT . 'source/function/function_member.php');
require_once(DISCUZ_ROOT . 'source/plugin/finndy/lib/function.global.php');
require_once(DISCUZ_ROOT . 'source/plugin/finndy/class/basic.class.php');
require_once(DISCUZ_ROOT . 'source/plugin/finndy/class/question.class.php');
require_once(DISCUZ_ROOT . 'source/plugin/finndy/class/member.class.php');
require_once(DISCUZ_ROOT . 'source/plugin/finndy/class/article.class.php');
if (!defined('IN_DISCUZ')) {
    exit('Access Denied');
}
define('DISABLEXSSCHECK', true);

$token = $_REQUEST['token'];
if (!empty($token)) {
	if (!empty($token) && !empty($_G['cache']['plugin']['finndy']['finndy_token'])) {
		//token验证成功
        if ($token == $_G['cache']['plugin']['finndy']['finndy_token']) {       	
                $question = !empty($_G['cache']['plugin']['finndy']['discuz_question'])?$_G['cache']['plugin']['finndy']['discuz_question']:'';
                $answer = !empty($_G['cache']['plugin']['finndy']['discuz_answer'])?$_G['cache']['plugin']['finndy']['discuz_answer']:'';
                //登陆方法
                $result = userlogin($_G['cache']['plugin']['finndy']['discuz_account'], $_G['cache']['plugin']['finndy']['discuz_password'], $question, $answer);
            if ($result && $result['status'] == 1) {
                //To do ...
                //http://localhost/discuz/upload/plugin.php?id=finndy:interface&token=finndyToken&type=forums
                if ($_REQUEST['type'] == 'forums') {
                    $forums = getAllForums();
                    if (!empty($forums)) {
                        echo json_encode(array('status'=>1,'msg'=>'','data'=>$forums));exit();
                    }else{
                        echo json_encode(array('status'=>0,'msg'=>'err','data'=>''));exit();
                    }
                }elseif($_REQUEST['type'] == 'postdata'){
                    $postdata = $_POST;
                    postData($postdata);

                }else if($_REQUEST['type'] == 'category'){
                    $category = getAllCategory();
                    if (!empty($category)) {
                        echo json_encode(array('status'=>1,'msg'=>'','data'=>$category));exit();
                    }else{
                        echo json_encode(array('status'=>0,'msg'=>'err','data'=>''));exit();
                    }

                }else if($_REQUEST['type'] == 'catedata'){
                    $postdata = $_POST;
                    postCateData($postdata);

                }else{
                    return false;
                }

            }else{
                return false;
            }
            
        }
    }
}

function postCateData($postdata) {    
    $article = new finndy_article($postdata);
    $article->processData();
}

function postData($postdata){
    $question = new finndy_question($postdata);
    $question->processData();
}
function getAllCategory(){
    global $_G;
    $category = array();
    loadcache('portalcategory');
    $categorys = $_G['cache']['portalcategory'];
    foreach ($categorys as $key => $value) {
       
        $category[] = array('id' => $value['catid'], 'name' => $value['catname']);
    }
    return $category;
}
//获取所有栏目名称
function getAllForums(){
	global $_G;
	$forums_arr = array();
	loadcache('forums');
	$forums = $_G['cache']['forums'];
	foreach ($forums as $key => $value) {
        if ($value['type'] == 'forum' || $value['type'] == 'sub'){
            $forums_arr[] = array('id' => $value['fid'], 'name' => $value['name']);
        }
    }
    return $forums_arr;
}

?>