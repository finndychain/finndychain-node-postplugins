<?php  
/*
Plugin Name: finndyAPI
Plugin URI: http://www.finndy.com/doc
Description:  发源地发布接口
Version: 1.0.1
Author: finndy
Author URI: http://www.finndy.com/
License: GPL
*/

header('Access-Control-Allow-Origin:*');

/* 注册激活插件时要调用的函数 */ 
register_activation_hook( __FILE__, 'display_finndy_install');   
/* 注册停用插件时要调用的函数 */ 
register_deactivation_hook( __FILE__, 'display_finndy_remove' );  
function display_finndy_install() {  
    /* 在数据库的 wp_options 表中添加一条记录，第二个参数为默认值 */ 
    add_option("display_finndy_text", "finndyToken");  
}

function display_finndy_remove() {  
    /* 删除 wp_options 表中的对应记录 */ 
    delete_option('display_finndy_text');  
}

if( is_admin() ) {
    /*  利用 admin_menu 钩子，添加菜单 */
    add_action('admin_menu', 'finndy_menu');
}

function finndy_menu() {
    add_menu_page('finndyAPI', '发源地发布接口', 'administrator','finndywp/finndy_html.php', '', 'dashicons-share-alt');
}

add_action('init', 'post_progress');

function post_progress(){
	//http://localhost/wordpress/wp-admin/plugins.php?plugin=finndywp%2Finterface.php&type=forums&token=finndyToken
	if ($_REQUEST['type'] == 'forums') {
		checkToken();
		$forums = getAllForums();
        if (!empty($forums)) {
            echo json_encode(array('status'=>1,'msg'=>'','data'=>$forums));exit();
        }else{
            echo json_encode(array('status'=>0,'msg'=>'err','data'=>''));exit();
        }
		
	}else if($_REQUEST['type'] == 'postdata'){
		checkToken();
		$postdata = $_POST;
		postData($postdata);
	}


}
//数据处理
function postData($postdata){
    
    $post['post_title'] = htmlspecialchars_decode($postdata['question_title']);
    $post['post_content'] = htmlspecialchars_decode($postdata['question_detail']);
    if ($postdata['question_author'] == 'admin') {
		    $post['post_author'] = 1;	
    }else{
    	$author = $postdata['question_author'];
    	$md5author = substr(md5($author), 8, 16);
        $user_id = username_exists($md5author);
        if (!$user_id) {
            $random_password = wp_generate_password();
            $userdata = array(
                'user_login' => $md5author,
                'user_pass' => $random_password,
                'display_name' => $author,
            );

            $user_id = wp_insert_user($userdata);
            $post['post_author'] = $user_id;
        }else{
        	$post['post_author'] = $user_id;
        }       
    }
    
    $post['post_date'] = date("Y-m-d", $postdata['question_publish_time']);
    if ($postdata['audit'] == 1) {
    	$post['post_status'] = 'pending';
    }else{
    	$post['post_status'] = 'publish';
    }
    $post['post_category'] = array($postdata['default_forum']);
    $post_id = wp_insert_post($post); 
    if (empty($post_id)) {
    	echo json_encode(array('status'=>0,'msg'=>'插入数据失败'));exit();
    }else{
         echo json_encode(array('status'=>1,'msg'=>'ok'));exit();
    }
}

//获取所有分类
function getAllForums(){
	$forums = get_terms('category', 'orderby=count&hide_empty=0');		
		$forums_arr = array();
		foreach ($forums as $value) {
			$forums_arr[] = array(
				'id' => $value->term_id,
			 	'name' => $value->name
			 	);
		}
		return $forums_arr;
}
//验证
function checkToken(){
	$finndyToken = get_option('display_finndy_text', "finndyToken");
	if ($_POST['token'] != $finndyToken) {
		echo json_encode(array('status'=>0,'msg'=>'Token错误'));exit();
		}
}