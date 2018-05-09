<?php 

$cate = array(
	'discuz' => array(
			'article' => '/plugin.php?id=finndy:interface&type=category',
			'forums' => '/plugin.php?id=finndy:interface&type=forums',
		),
	);
//$request = $_REQUEST;

$token = 'finndyToken'; //$request['token']; // token
$host = 'http://localhost/discuz/upload'; //$request['host'];  //域名
$name = 'discuz'; //$request['name']; //论坛名称
$type = 'article'; //$request['type']; //发布类型

//$url = $host.$cate[$name][$type];
var_dump($type);exit();
$fields = array(
	'token' => $token;
	);

$res = request($url, 'POST', $fields);
if (!$res) {
	jsonReturn($status=0, $msg='erro', $data='');
}

echo $res;
exit();

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