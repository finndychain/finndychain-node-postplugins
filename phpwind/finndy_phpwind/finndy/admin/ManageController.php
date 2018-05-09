<?php
defined('WEKIT_VERSION') or exit(403);
require_once dirname(__DIR__).'/library/config.php';
Wind::import('ADMIN:library.AdminBaseController');
Wind::import('EXT:finndy.library.functionGlobal');
/**
 * 应用的后台配置
 *
 * @author Shi Long <long.shi@alibaba-inc.com>
 * @copyright ©2003-2103 phpwind.com
 * @license http://www.windframework.com
 * @version $Id: ManageController.php 24202 2013-01-23 02:18:05Z jieyin $
 * @package admin
 */
class ManageController extends AdminBaseController {

	private $file = 'EXT:finndy.conf';
	private $default = array();

	public function beforeAction($handlerAdapter) {
		parent::beforeAction($handlerAdapter);
		$this->file = Wind::getRealPath($this->file, false);
	}

	/* (non-PHPdoc)
	 * @see WindController::run()
	 */
	public function run() {
		$conf = @include $this->file;
		$conf || $conf = $this->default;
                $reply = finndy_get_version();
                $conf['version'] = $reply['version'];
                $conf['supportVersion'] = $reply['supportVersion'];
                $conf['basicWebAddress'] = str_replace('\\','/',$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
		$this->setOutput($conf, 'conf');
	}

	/**
	 * 应用的设置提交
	 *
	 */
	public function doRunAction() {
		$conf = $this->getInput('conf', 'post');
		WindFile::savePhpData($this->file, $conf);
		$this->showMessage('success');
	}
}

?>