<?php

class finndy extends AWS_ADMIN_CONTROLLER {

    private $finndy_version = "1.0";
    private $finndy_config_file;
    private $finndy_config = array(
        //设置
        'finndy_password' => "finndyToken",
    );

    public function __construct() {
        parent::__construct();
        $this->finndy_config_file = AWS_PATH . 'config/finndy_config.php';
    }

    public function index_action() {
        $this->settings_action();
    }

    public function settings_action() {
        if (file_exists($this->finndy_config_file)) {
            require $this->finndy_config_file;
        }

        $this->crumb(AWS_APP::lang()->_t('扩展插件'), 'admin/finndy/settings/');
        TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(901));

        if (isset($config['finndy_config'])) {
            $finndy_config = $config['finndy_config'];
        } else {
            $finndy_config = $this->finndy_config;
        }
        $basic_web_address = str_replace('\\','/',$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));

        TPL::assign('finndy_version', $this->finndy_version);
        TPL::assign('basic_web_address', $basic_web_address);
        TPL::assign('finndy_config', $finndy_config);
        TPL::output('admin/finndy/settings');
    }

    public function ajax_finndy_action() {
        $finndy_password = empty($_POST['finndy_password']) ? "finndyToken" : $_POST['finndy_password'];

        $code = <<<config_code
<?php
\$config['finndy_config'] = array(
    'finndy_password' => "{$finndy_password}"
);
config_code;

        file_put_contents($this->finndy_config_file, $code);
        //$proxy_url = base_url() . "/" . G_INDEX_SCRIPT . "proxy/&token=" . urlencode($finndy_password);
        H::ajax_json_output(AWS_APP::RSM("", -1, AWS_APP::lang()->_t('保存设置成功')));
    }

}
