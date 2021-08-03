<?php
/**
 * 数据集
 *
 */
class Datasets extends CI_Controller {

	public function __construct() {
		parent::__construct();

		$this->load->database();
		$this->load->model("Rest_model");
	}

	//测试
	public function test() {

		$result = $this->db->query('SELECT * FROM datasets WHERE id = 37')->result_array();

		// echo json_encode($result);

		$this->Rest_model->print_rest_json($result);

		// $result = $this->Upload_model->upload_file();

		// $this->Rest_model->print_rest_json($result);
	}
	public function search() {
		$sql = "SELECT * FROM datasets WHERE name LIKE '%" . $_REQUEST['query'] . "%'";
		$result = $this->db->query($sql)->result_array();
		$this->Rest_model->print_rest_json($result);
	}

	// 获取分类
	public function categories() {
		$result = $this->db->query("SELECT * FROM categories")->result_array();
		$this->Rest_model->print_rest_json($result);
	}

	// 获取列表
	public function datasets() {
		$result = $this->db->query("SELECT * FROM datasets")->result_array();

		// echo json_encode($result);
		$this->Rest_model->print_rest_json($result);
	}

	// 获取数据
	public function dataset() {
		$dataset_id = $_REQUEST['id'];

		$result = $this->db->query("SELECT * FROM datasets WHERE id = '$dataset_id'")->row_array();

		// echo json_encode($result);
		$this->Rest_model->print_rest_json($result);
	}

	public function drill() {
		// $start = "2018-11-10 00:00:00";
		// $end = "2018-11-23 00:00:00";
		//$start = date("Y-m-d",time());
		//$end = date("Y-m-d",strtotime("-7 day"));
		//$start= strtotime($start);
		//var_dump($start);
		//var_dump($end);

		/*//查询数据库存储的数据
			        $sql = "SELECT count( distinct tx_from) as uc_day,
			                count(*) as volume_day, sum(tx_value) as tx_day,
			                from_unixtime(timestamp, '%Y-%m-%d') as fetch_day
			                FROM `transaction`
			                WHERE `tx_to` in (SELECT `eth_address`.`address` from `dapp_apply_info`
			                inner JOIN `eth_address` on `dapp_apply_info`.`apply_id` =`eth_address`.`apply_id`
			                WHERE `dapp_apply_info`.`apply_id` =$apply_id)
			                and timestamp<=unix_timestamp('$end') and timestamp>=unix_timestamp('$start')
			                GROUP BY from_unixtime(timestamp, '%Y-%m-%d')";

		*/
		// $sql = "SELECT `eth_address`.`address` from `dapp_apply_info`
		//              inner JOIN `eth_address` on `dapp_apply_info`.`apply_id` =`eth_address`.`apply_id`
		//              WHERE `dapp_apply_info`.`apply_id` =$apply_id";
		// $address_list = $this->db->query($sql)->result_array();
		// //var_dump($address_list);
		// $address = '';
		// foreach ($address_list as $item) {
		// 	$query = strtoupper($item['address']);
		// 	$address = $address . "'" . $query . "'" . ',';
		// }

		// $address = substr($address, 0, strlen($address) - 1);
		// var_dump($address);

		// $sql_7d = "SELECT count( distinct tx_from) as uc_day,
		//                   count(*) as volume_day,sum(CAST(`tx_value` AS DOUBLE )) as tx_day,
		//                   FROM_UNIXTIME(CAST(`timestamp` AS INT ), 'Y-M-d') as `fetch_day`
		//                   FROM ipfs.`QmcB1fpBU9P1m2HJCwAXd3FZot9JZpgRQJusERN8F5f6nE`
		//                   WHERE upper(`tx_to`) in ($address)
		//                   and `timestamp`<unix_timestamp('$end') and
		//                   `timestamp`>=unix_timestamp('$start')
		//                   GROUP BY FROM_UNIXTIME(CAST(`timestamp` AS INT ), 'Y-M-d')";

		// Query
		// $sql = "SELECT * FROM " . $_REQUEST['query'] . " LIMIT 20";
		$sql = $_REQUEST['query'];

		$result = $this->drill_post_data($sql);

		$this->Rest_model->print_rest_json($result);
		// var_dump($result);
	}

	//发送drill sql查询请求
	public function drill_post_data($query) {
	    $url = "http://129.211.123.74:8047/query.json";
		$postData = array("queryType" => "SQL", "query" => $query);

		// Setup cURL
		$ch = curl_init($url);
		curl_setopt_array($ch, array(
			CURLOPT_POST => TRUE,
			CURLOPT_RETURNTRANSFER => TRUE,
			CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
			CURLOPT_POSTFIELDS => json_encode($postData),
		));
		$response = curl_exec($ch);
		$response = json_decode($response, true);
		curl_close($ch);
		return $response;
	}

}