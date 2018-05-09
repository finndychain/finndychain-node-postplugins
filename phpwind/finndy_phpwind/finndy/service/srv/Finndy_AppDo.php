<?php
defined('WEKIT_VERSION') or exit(403);

class Finndy_AppDo {
	
	/**
	 * @param array $var
	 * @return array
	 */
	public function finndyDo($var) {
		$var[] = array(
			'name' => 'finndy',
			'params' => array('len' => 8, 'age' => 2)
		);
		return $var;
	}
}

?>