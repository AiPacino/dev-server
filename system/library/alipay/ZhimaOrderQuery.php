<?php

namespace alipay;

use alipay\zmop\ZmopClient;

include __DIR__ . '/ZmopSDk.php';
include __DIR__ . '/function.inc.php';

require_once __DIR__ . '/zmop/request/ZhimaMerchantOrderConfirmRequest.php';

class ZhimaOrderQuery {

	private $config;
	private $zmop;

	public function __construct($appid) {

		$config_file = __DIR__ . '/' . $appid . '-zhima-config.php';
		if (!file_exists($config_file) && !is_readable($config_file)) {
			set_error('芝麻认证配置错误');
			\zuji\debug\Debug::error(\zuji\debug\Location::L_Certification, '[芝麻认证]应用配置未找到', [
				'app_id' => $appid,
			]);
			throw new \Exception('芝麻认证应用配置未找到');
		}
		$config = include $config_file;
		$zmop = new ZmopClient($config ['gatewayUrl'], $config ['app_id'], $config ['charset'], $config['merchant_private_key_file'], $config['alipay_public_key_file']);
		$this->zmop = $zmop;
		$this->config = $config;
	}

	/**
	 * 
	 * @param string $order_no	    订单号
	 * @param string $transaction_id	    租机交易号
	 * @return mixed	
	 */
	public function getOrderInfo($order_no, $transaction_id) {
		$request = new \ZhimaMerchantOrderConfirmRequest();
		$request->setChannel("apppc");
		$request->setPlatform("zmop");
		$request->setOrderNo($order_no); // 必要参数 
		$request->setTransactionId($transaction_id); // 必要参数 
		$response = $this->zmop->execute($request);
		$response = json_decode(json_encode($response), true);
		if (!$response) {
			set_error('认证结果查询失败');
			\zuji\debug\Debug::error(\zuji\debug\Location::L_Certification, '[芝麻认证]接口异常', [
				'request' => [
					'order_no' => $order_no,
					'transaction_id' => $transaction_id,
				],
				'response' => $response,
			]);
			return false;
		}
		if (isset($response['success']) && $response['success'] === false) {
			set_error('认证结果查询失败');
			\zuji\debug\Debug::error(\zuji\debug\Location::L_Certification, '[芝麻认证]结果查询异常', [
				'request' => [
					'order_no' => $order_no,
					'transaction_id' => $transaction_id,
				],
				'response' => $response,
			]);
			return false;
		} elseif (!isset($response['success']) || $response['success'] !== true) {
			set_error('认证结果查询失败，且芝麻认证接口返回值格式有误');
			\zuji\debug\Debug::error(\zuji\debug\Location::L_Certification, '[芝麻认证]接口返回值格式有误', [
				'request' => [
					'order_no' => $order_no,
					'transaction_id' => $transaction_id,
				],
				'response' => $response,
			]);
			return false;
		}
		return $response;
	}

}

?>