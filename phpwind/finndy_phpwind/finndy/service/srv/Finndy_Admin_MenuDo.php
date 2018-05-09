<?php
defined('WEKIT_VERSION') or exit(403);
/**
 * 后台菜单扩展
 */
class Finndy_Admin_MenuDo {
	
	/**
	 * @param array $config 后台菜单配置
	 * @return array
	 */
	public function finndyDo($config) {
		$config += array(
			'finndy' => array('发源地数据接口', 'app/manage/*?app=finndy', '', '', 'appcenter'),
			);
		return $config;
	}
}

?>