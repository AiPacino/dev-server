<?php
namespace oms\order_creater;

use oms\OrderCreater;
use zuji\Config;

/**
 * 电子合同生成组件
 *	须订单创建后使用
 * @author limin <limin@huishoubao.com.cn>
 */
class ContractComponnet implements OrderCreaterComponnet {
    
    private $flag = true;
    private $componnet = null;
	private $schema = null;

	//合同模板id
	private $template_id = null;
	//合同编号
	private $contract_id = null;
	//合同交易号
	private $transaction_id = null;
	//请求参数
	private $params = [];
	//请求结果
	private $result = false;
	//请求结果数组
	private $filling_result = [];
	//合同下载地址
	private $download_url = null;
	//合同查看地址
	private $viewpdf_url = null;

    public function __construct(OrderCreaterComponnet $componnet) {
        $this->componnet = $componnet;

		$this->schema = $this->get_data_schema();
		//查询合同模板
		$this->contract = \hd_load::getInstance()->table("contract/contract");
		$contract_info = $this->contract->where(['status'=>0])->find();
		//组装参数
		$this->template_id = $contract_info['template_id'];
		$this->contract_id = $this->schema['user']['user_id'].date("YmdHis");
		$this->transaction_id = $this->schema['user']['user_id'].time();
		$this->params = [
				//姓名
				'name' => $this->schema['credit']['realname'],
				//身份证号
				'id_cards' => $this->schema['credit']['realname'],
				//手机号
				'mobile' => $this->schema['user']['mobile'],
				//邮箱(可选)
				'email' => '',
				//合同模版ID
				'template_id' => $this->template_id,
				//合同编号
				'contract_id' => $this->contract_id,
				//文档标题
				'doc_title' => '测试',
				//交易号
				'transaction_id' => $this->transaction_id,
				//签名关键字
				'sign_word' => '委托方签字',
		];
    }

	public function get_order_creater(): OrderCreater {
		return $this->componnet->get_order_creater();
	}

	public function filter():bool {
		$filter_b =  $this->componnet->filter();

		//电子合同签署
		$url = config("Contract_Sign_Url");
		//测试参数
		$this->params = [
				'name' => '章三',
				'id_cards' => '441622197909120326',
				'mobile' => '18683674985',
				'email' => '',
				'template_id' => $this->template_id,
				'contract_id' => time(),
				'doc_title' => '测试合同',
				'transaction_id' => time().mt_rand(1000,9999),
				'sign_word' => '委托方签字',
				'paramters' => [
						"borrower"=>"章三",
						"platformName"=>"机市",
						"homeUrl"=>"https://zuji.huishoubao.com"
				]
		];
		$params = json_encode($this->params);
		$result = \Curl::post($url,$params,['Content-Type:application/json']);
		$result = json_decode($result,true);
		if($result['result']=="success" || $result['code'] == 1000){
			$this->result = true;
			$this->download_url = $result['download_url'];
			$this->viewpdf_url = $result['viewpdf_url'];
			$this->filling_result = $result['filling_result'];
		}
		return $this->flag && $filter_b;
    }

	public function get_data_schema(): array{
		$schema = $this->componnet->get_data_schema();
		return array_merge($schema,[
			'construct' => [
				'params' => $this->params,
				'result' => $this->result,
				'download_url'=>$this->download_url,
				'viewpdf_url'=>$this->viewpdf_url,
				'filling_result' => $this->filling_result
			]
		]);
	}
	public function create():bool {

		if( !$this->flag ){
			return false;
		}
		$b = $this->componnet->create();
		if( !$b ){
			return false;
		}

		//创建合同单
		if($this->result){
			$this->service = \hd_load::getInstance()->table("order2/order2_contract");
			$data = [
					'order_no' => $this->schema['order']['order_no'],
					'user_id' => $this->schema['user']['user_id'],
					'template_id' => $this->template_id,
					'contract_id' => $this->contract_id,
					'status' => 0,
					'transaction_id' => $this->transaction_id,
					'download_url' => $this->download_url,
					'viewpdf_url' => $this->viewpdf_url,
					'create_time' => time(),
			];
			$contract_id = $this->service->add($data);
		}

		return true;
	}

}
