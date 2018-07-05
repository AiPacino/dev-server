<?php

namespace alipay;

use alipay\zmop\ZmopClient;

include __DIR__ . '/ZmopSDk.php';
include __DIR__ . '/function.inc.php';

require_once __DIR__ . '/zmop/request/ZhimaDataSingleFeedbackRequest.php';

class ZhimaDataSingleFeedback {

	private $config;
	private $zmop;

	public function __construct($appid) {
		$config_file = __DIR__ . '/' . $appid . '-zhimafankui-config.php';
		if (!file_exists($config_file) && !is_readable($config_file)) {
			set_error('芝麻反馈配置错误');
			\zuji\debug\Debug::error(\zuji\debug\Location::L_Certification, '[芝麻反馈]应用配置未找到', [
				'app_id' => $appid,
			]);
			throw new \Exception('芝麻反馈应用配置未找到');
		}
		$config = include $config_file;
		$zmop = new ZmopClient($config ['gatewayUrl'], $config ['app_id'], $config ['charset'], $config['merchant_private_key_file'], $config['alipay_public_key_file']);
		$this->zmop = $zmop;
		$this->config = $config;
	}
	public function test(){
	    echo 123;
    }

	/**
	 * 
	 * @param string
	 * @param string
	 * @return mixed
	 */
	public function ZhimaDataSingleFeedback() {
	    if(!$this->config['switch']){
	        return true;
        }
        $data = [
            'phone_no'=>'18600598865',
            'create_amt'=>'0',
            'remind_status'=>'0',
            'order_status'=>'2',
            'bill_no'=>'201602050099999',
            'bill_installment'=>'201608',
            'bill_desc'=>'商品名称',
            'bill_type'=>'200',
            'bill_amt'=>'1000',
            'bill_status'=>'2',
            'bill_payoff_date'=>'2015-01-01',
            'bill_type_ovd_amt'=>'0',
            'bill_type_ovd_date'=>'2015-01-01',

            'biz_date'=>'2016-01-01',
            'user_credentials_type'=>'0',
            'user_credentials_no'=>'130633198912010013',
            'user_name'=>'王金霖',
            'order_no'=>'201602050099',
            'order_start_date'=>'2016-01-01',
            'order_end_date'=>'2017-01-01',
            'bill_last_date'=>'',
            'memo'=>''
        ];

		$request = new \ZhimaDataSingleFeedbackRequest();
        $request->setPlatform("zmop");
        $request->setTypeId($this->config['type_id_test']);
        $request->setIdentity("order_no");
        $request->setData(json_encode($data));
        //$request->setBizExtParams("{\"extparam1\":\"value1\"}");
        $response = $this->zmop->execute($request);
        var_dump($response);
        echo json_encode($response);
        // {"success":true,"biz_success":true}


//        try{
//        }catch (Exception $e){
//        }
	}

}

?>