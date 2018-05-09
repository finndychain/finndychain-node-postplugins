<?php 

$cate = array(
	'discuz' => array(
			'article' => '/plugin.php?id=finndy:interface&type=category',
			'forums' => '/plugin.php?id=finndy:interface&type=forums',
		),
	);
//$request = $_REQUEST;

$token = 'finndyToken'; //$request['token']; // token
$host = 'http://test.finndy.com/discuz/plugin.php?id=finndy:interface&type=postdata'; //$request['host'];  //域名
$name = 'discuz'; //$request['name']; //论坛名称
$type = 'forums'; //$request['type']; //发布类型
$start_day = ('十八)))))—））））');
$url = $host;
$fields = array(
	'token' => $token,
	'question_title' => '致广大出口企业朋友的一封信',
    'question_detail' => '
尊敬的出口企业朋友：
　　根据现行政策规定，税务机关对出口企业2016年度出口的货物劳务、对外提供的服务，涉及出口退(免)税主要业务的办理期限做出友情提醒如下：
　　一、出口退(免)税申报期限
　　出口企业于2016年1月1日至2016年12月31日（以报关单出口日期为准）出口的货物劳务，应于2017年4月18日前向主管退税机关申报退（免）税。
　　出口企业于2016年1月1日至2016年12月31日（以财务做销售收入时间为准）对外提供的零税率应税服务，应于2017年4月18日前向主管退税机关申报出口退（免）税。
　　二、出口免税申报期限
　　出口企业对2016年度业务在2017年4月18日前未申报、未开具《代理出口证明》，或已申报未补齐有关单证的出口退（免）税，应于2017年5月15日前办理增值税、消费税免税申报。
　　三、延期申报申请时限
　　符合《国家税务总局关于&lt;出口货物劳务增值税和消费税管理办法&gt;有关问题的公告》(国家税务总局公告2013年第12号)第二条第(十八)项规定的，需要办理延期申报的，应于2017年4月18日前向主管出口退税机关提出延期申报的申请。
　　四、不能收汇申报期限
　　符合《国家税务总局关于出口企业申报出口货物退(免)税提供收汇资料有关问题的公告》(国家税务总局公告2013年第30号)第四条、第五条规定的，需要在2017年4月18日前向主管退税机关报送《出口货物不能收汇申报表》。
　　五、无相关电子信息申报期限
　　符合《国家税务总局关于调整出口退(免)税申报办法的公告》(国家税务总局公告2013年第61号)第四条规定的，需要进行无相关电子信息申报的，应于2017年4月18日前申报。
　　六、委托出口证明办理期限
　　出口企业于2016年1月1日至2016年12月31日委托出口的货物(仅指国家取消出口退税的货物)，应于2017年3月15日前办理《委托出口货物证明》。
　　七、代理出口货物证明办理期限
　　出口企业于2016年1月1日至2016年12月31日受托出口的货物，应于2017年4月15日前办理《代理出口货物证明》。
　　八、来料加工出口货物免税核销的申报期限
　　出口企业于2016年1月1日至2016年12月31日在海关办结来料加工委托加工业务核销手续的，应于2017年5月15日前，办理来料加工出口货物免税核销手续。
　　九、年度进料加工业务核销的申报期限
　　出口企业2016年度海关已核销的进料加工手(账)册项下的进料加工业务核销手续，应于2017年4月20日前申报办理。　
　　十、以边境小额贸易方式代理外国企业、外国自然人出口货物备案期限
　　出口企业2016年1月1日至2016年12月31日以边境小额贸易方式代理外国企业、外国自然人出口货物，应在2017年4月18日前向主管税务机关办理备案。
　　上述事项，出口企业可以向税务机关进行详细咨询。
　　　　　　　　　　　　　　　　　　　　　　　　　　　　　　　　　　　　新疆维吾尔自治区国家税务局
　　　　　　　　　　　　　　　　　　　　　　　　　　　　　　　　　　　　　　　2017年3月13日',
    'question_publish_time' => '',
    'question_author' => 'admin',
    'default_forum' => 37,
	);

$res = request($url, 'POST', $fields);
var_dump($res);exit();
if (!$res) {
	jsonReturn($status=0, $msg='erro', $data='');
}


function jsonReturn($status=0, $msg='erro', $data=''){
	$res = array(
			'status' => $status,
			'msg' => $msg,
			'data' => $data,
		);
	echo json_encode($res);
	exit();
}

function request($url, $method='get', $fields = array()){
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 10);

	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	if ($method === 'POST')
	{
		curl_setopt($ch, CURLOPT_POST, true );
		curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
	}
	$result = curl_exec($ch);
	return $result;
	curl_close($ch);
}
 ?>