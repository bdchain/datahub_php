<?php
/**
 * Created by PhpStorm.
 * User: ckx
 * Date: 2018/11/6
 * Time: 上午11:59
 */

class Rest_model extends CI_Model {
	public function __construct() {
		$this->load->database();
	}

	// 输出rest标准的json数据
	public function print_rest_json($result) {
		header("content-type:text/json;charset=utf-8");
		header('Access-Control-Allow-Origin: *');
		//设置no-cache
		//header("Pragma:no-cache");
		echo json_encode($result);
	}

	// 输出正确信息
	public function print_success_json() {
		$result = array("status" => 200, "message" => "update success");
		$this->print_rest_json($result);
	}
}
