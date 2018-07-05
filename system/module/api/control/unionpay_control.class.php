<?php

use zuji\order\Order;
use zuji\debug\Debug;
use zuji\debug\Location;
use zuji\Config;
use zuji\Time;
use zuji\order\Service;
use zuji\email\EmailConfig;

hd_core::load_class('api', 'api');
/**
 * 订单控制器
 * @access public
 * @author limin <limin@huishoubao.com.cn>
 * @copyright (c) 2018, Huishoubao
 */
class unionpay_control extends user_control {

	private $pay_notify_url = "/api.php?m=pay_notify_api&c=notify&a=pay";

	private $curl_create = [
			"version"   => "1.0",
			"sign_type"   => "MD5",
			"sign"    => "",
			"appid"    => "",
			"method"    => "",
			"params"    => [],
			"timestamp"    => "",
	];
    
    public function _initialize() {
        parent::_initialize();
		$this->pay_notify_url = $_SERVER['REQUEST_SCHEME']."://".$_SERVER['HTTP_HOST'].$this->pay_notify_url;
		//禁止测试用户支付
		if(config("Test_Mobile_On")==true){
			if($this->member['mobile']==config("Test_Mobile")){
				api_resopnse([], ApiStatus::CODE_20001,ApiSubCode::Order_Error_Order_no,'测试用户禁止支付');
				return ;
			}
		}
		//初始化appid和时间戳
		$this->curl_create['appid'] = 1; //租机平台
		$this->curl_create['timestamp'] = date("Y-m-d H:i:s",time());
    }
	//发起curl请求
	public function send_curl(){
		$result = \zuji\Curl::post(config('Interior_Pay_Url'),json_encode($this->curl_create));
		$data  = json_decode($result,true);
		if(!is_array($data)){
			$this->curl_create['request_url'] = config('Interior_Pay_Url');
			$this->curl_create['response'] = json_encode($result);
			Debug::error(Location::L_Trade, "支付系统接口请求错误", $this->curl_create);
			return false;
		}
		return $data;
	}
	//银联开通接口
	public function open(){
		//接收请求参数
		$params = $this->params;
		//过滤参数
		$data = filter_array($params,[
				"order_no"  => "required", //订单号
				"acc_no"  => "required", //银行卡号
				"phone_no"  => "required", //	银行预留手机号
				"front_url"  => "required", //	回跳地址
		]);
		//验证参数
		if(empty($data['order_no'])){
			api_resopnse( [], ApiStatus::CODE_20001,'order_no必须');
			return;
		}
		if(empty($data['acc_no'])){
			api_resopnse( [], ApiStatus::CODE_20001,'acc_no必须');
			return;
		}
		if(empty($data['phone_no'])){
			api_resopnse( [], ApiStatus::CODE_20001,'phone_no必须');
			return;
		}
		if(empty($data['front_url'])){
			api_resopnse( [], ApiStatus::CODE_20001,'front_url必须');
			return;
		}
		//查询订单信息
		$this->order = $this->load->service('order2/order');
		//查询条件
		$where['order_no'] = $data['order_no'];
		$order_info = $this->order->get_order_info($where);
		//验证订单
		if(!$order_info){
			api_resopnse( [], ApiStatus::CODE_50000,'订单不存在');
			return;
		}
		$cert_no = $data['cert_no']?$data['cert_no']:$order_info['cert_no'];
		if(!$cert_no){
			api_resopnse( [], ApiStatus::CODE_20001,'身份证号不存在');
			return;
		}
		//请求参数赋值
		$this->curl_create['method'] = "pay.unionpay.open";
		$this->curl_create['params'] = [
				'acc_no' => $data['acc_no'],
				'phone_no' => $data['phone_no'],
				'certif_id' => $cert_no,
				'customer_nm' => $order_info['realname'],
				'user_id' => $order_info['user_id'],
				'front_url' => $data['front_url'],
		];
		//curl调起请求
		$result = $this->send_curl();
		Debug::error(Location::L_Order,'银联开通请求',$result);
		if(!$result){
			api_resopnse( $result, ApiStatus::CODE_60000,'银联开通接口异常');
			return;
		}
		if($result['code']!=0){
			api_resopnse( $result, ApiStatus::CODE_50000,'银联开通接口异常');
			return;
		}
		api_resopnse( $result['data'], ApiStatus::CODE_0);
		return;
	}
	//银联开通并支付
	public function openandpay(){
		//接收请求参数
		$params = $this->params;
		//过滤参数
		$data = filter_array($params,[
				"order_no"  => "required", //订单号
				"acc_no"  => "required", //银行卡号
				"phone_no"  => "required", //	银行预留手机号
				"front_url"  => "required", //	回跳地址
		]);
		//验证参数
		if(empty($data['order_no'])){
			api_resopnse( [], ApiStatus::CODE_20001,'order_no必须');
			return;
		}
		if(empty($data['acc_no'])){
			api_resopnse( [], ApiStatus::CODE_20001,'acc_no必须');
			return;
		}
		if(empty($data['phone_no'])){
			api_resopnse( [], ApiStatus::CODE_20001,'phone_no必须');
			return;
		}
		if(empty($data['front_url'])){
			api_resopnse( [], ApiStatus::CODE_20001,'front_url必须');
			return;
		}
		//查询订单信息
		$this->order = $this->load->service('order2/order');
		//开启事务
		$this->order->startTrans();

		$where['order_no'] = $data['order_no'];
		$order_info = $this->order->get_order_info($where,['lock'=>true]);
		//验证订单
		if(!$order_info){
			$this->order->rollback();
			api_resopnse( [], ApiStatus::CODE_50000,'订单不存在');
			return;
		}
		$cert_no = $data['cert_no']?$data['cert_no']:$order_info['cert_no'];
		if(!$cert_no){
			api_resopnse( [], ApiStatus::CODE_20001,'身份证号不存在');
			return;
		}
		//验证订单已支付
		if($order_info["payment_status"] == zuji\order\PaymentStatus::PaymentSuccessful){
			$this->order->rollback();
			api_resopnse( [], ApiStatus::CODE_50000,'订单已支付');
			return;
		}
		//验证订单
		if($order_info['status']!=oms\state\State::OrderCreated && $order_info['status']!=oms\state\State::StoreConfirmed ){
			$this->order->rollback();
			api_resopnse( [], ApiStatus::CODE_50000,'订单异常');
			return;
		}
		//支付单
		$this->payment_table = $this->load->table('order2/order2_payment');
		$payment_info = $this->payment_table->where(['order_no'=>$order_info['order_no']])->find();
		$payment_id = $payment_info['payment_id'];
		$trade_no = $payment_info['trade_no'];
		if(!$payment_id){
			//创建支付单
			$trade_no = \zuji\Business::create_business_no();
			$order_info['amount'] = 100*$order_info['amount'];
			$payment_data = [
					'payment_status' =>  zuji\order\PaymentStatus::PaymentPaying,// 直接支付中状态
					'business_key' => $order_info['business_key'],
					'order_id' => $order_info['order_id'],
					'order_no' => $order_info['order_no'],
					'trade_no' => $trade_no,
					'amount' => $order_info['amount'],    // 元转换成分
					'payment_channel_id' => $order_info['payment_type_id'],
					'payment_type_id' => $order_info['payment_type_id'],
			];
			$payment_id =$this->payment_table->create_data($payment_data);
			if(!$payment_id){
				$this->order->rollback();
				api_resopnse( [], ApiStatus::CODE_50000,'支付单创建失败');
				return;
			}
			$this->order_table = $this->load->table('order2/order2');
			$payment = ['order_id'=>$order_info['order_id'],"payment_id"=>$payment_id];
			$ret = $this->order_table->save($payment);
			if(!$ret){
				Debug::error(Location::L_Order,'更新订单支付id失败',$payment);
			}
		}

		//请求参数赋值
		$this->curl_create['method'] = "pay.unionpay.openandpay";
		$this->curl_create['params'] = [
				'acc_no' => $data['acc_no'],
				'phone_no' => $data['phone_no'],
				'certif_id' => $cert_no,
				'customer_nm' => $order_info['realname'],
				'user_id' => $order_info['user_id'],
				'out_no' => $trade_no,//交易流水号
				'amount' => $order_info['amount'],
				'fenqi' => $order_info['zuqi'],
				'front_url' => $data['front_url'],
				'back_url' => $this->pay_notify_url,
		];
		//curl调起请求
		$result = $this->send_curl();
		Debug::error(Location::L_Order,'银联支付请求',$result);
		if(!$result){
			$this->order->rollback();
			api_resopnse( $result, ApiStatus::CODE_60000,'支付接口异常');
			return;
		}
		if($result['code']!=0){
			$this->order->rollback();
			api_resopnse( $result, ApiStatus::CODE_50000,'支付接口异常');
			return;
		}
		$this->order->commit();
		api_resopnse( $result['data'], ApiStatus::CODE_0);
		return;
	}
	//开通银行卡结果查询接口
	public function get(){
		//接收请求参数
		$params = $this->params;
		//过滤参数
		$data = filter_array($params,[
				"acc_no"  => "required", //卡号
		]);
		if(empty($data['acc_no'])){
			api_resopnse( [], ApiStatus::CODE_20001,'acc_no必须');
			return;
		}
		//组装参数
		$this->curl_create['method'] = "pay.unionpay.polling";
		$this->curl_create['params'] = [
				'acc_no' => $data['acc_no'],
				'user_id' => $this->member['id'],
		];
		//curl调起请求
		$result = $this->send_curl();
		Debug::error(Location::L_Trade, "开通银行卡", $result);
		if($result['code']!=0){
			api_resopnse( [], ApiStatus::CODE_50000,'开通失败');
			return;
		}
		api_resopnse( [], ApiStatus::CODE_0);
		return;
	}
	//银联已开通银行卡列表查询接口
	public function cardlist(){
		//接收请求参数
		$params = $this->params;
		//过滤参数
		$data = filter_array($params,[
				"page"  => "required", //页码
		]);

		$page = $data['page']>1?$data['page']:1;
		$uid = $this->member['id'];
		//组装参数
		$this->curl_create['method'] = "pay.unionpay.bankcardlist";
		$this->curl_create['params'] = [
				'page' => $page,
				'user_id' => $uid,
		];
		//curl调起请求
		$result = $this->send_curl();
		if($result['code']!=0){
			api_resopnse( [], ApiStatus::CODE_50000,'请开通银行卡');
			return;
		}
		Debug::error(Location::L_Trade, "已开通银行卡列表", $result);
		api_resopnse( $result['data'], ApiStatus::CODE_0);
		return;
	}
	//银联短信验证码发送接口
	public function sendsms(){
		//接收请求参数
		$params = $this->params;
		//过滤参数
		$data = filter_array($params,[
				"bankcard_id"  => "required", //银行卡id
				"order_no"  => "required", //订单号
		]);
		//验证参数
		if(empty($data['bankcard_id'])){
			api_resopnse( [], ApiStatus::CODE_20001,'bankcard_id必须');
			return;
		}
		if(empty($data['order_no'])){
			api_resopnse( [], ApiStatus::CODE_20001,'order_no必须');
			return;
		}
		//查询订单信息
		$this->order = $this->load->service('order2/order');
		//开启事务
		$this->order->startTrans();
		//查询条件
		$where['order_no'] = $data['order_no'];
		$order_info = $this->order->get_order_info($where);
		//验证订单
		if(!$order_info){
			$this->order->rollback();
			api_resopnse( [], ApiStatus::CODE_50000,'订单不存在');
			return;
		}
		//支付单
		$this->payment_table = $this->load->table('order2/order2_payment');
		$payment_info = $this->payment_table->where(['order_no'=>$order_info['order_no']])->find();
		$payment_id = $payment_info['payment_id'];
		$trade_no = $payment_info['trade_no'];
		if(!$payment_id){
			//创建支付单
			$trade_no = \zuji\Business::create_business_no();
			$order_info['amount'] = 100*$order_info['amount'];
			$payment_data = [
					'payment_status' =>  zuji\order\PaymentStatus::PaymentPaying,// 直接支付中状态
					'business_key' => $order_info['business_key'],
					'order_id' => $order_info['order_id'],
					'order_no' => $order_info['order_no'],
					'trade_no' => $trade_no,
					'amount' => $order_info['amount'],    // 元转换成分
					'payment_channel_id' => $order_info['payment_type_id'],
					'payment_type_id' => $order_info['payment_type_id'],
			];
			$payment_id =$this->payment_table->create_data($payment_data);
			if(!$payment_id){
				$this->order->rollback();
				api_resopnse( [], ApiStatus::CODE_50000,'支付单创建失败');
				return;
			}
			$this->order_table = $this->load->table('order2/order2');
			$payment = ['order_id'=>$order_info['order_id'],"payment_id"=>$payment_id];
			$ret = $this->order_table->save($payment);
			if(!$ret){
				Debug::error(Location::L_Order,'更新订单支付id失败',$payment);
			}
		}

		//组装参数
		$this->curl_create['method'] = "pay.unionpay.smsconsume";
		$this->curl_create['params'] = [
				'user_id' => $order_info['user_id'],
				'bankcard_id' => $data['bankcard_id'],
				'out_no' => $trade_no,
				'amount' => $order_info['amount'],
				'fenqi' => $order_info['zuqi'],
				'back_url' => $this->pay_notify_url,
		];
		//curl调起请求
		$result = $this->send_curl();
		if($result['code']!=0){
			$this->order->rollback();
			Debug::error(Location::L_Trade, "银联短信发送失败", $result);
			api_resopnse( [], ApiStatus::CODE_50000,'短信发送失败');
			return;
		}
		$this->order->commit();
		api_resopnse( $result['data'], ApiStatus::CODE_0);
		return;
	}
	//银联支付消费接口(限已开通银联用户)
	public function consume(){
		//接收请求参数
		$params = $this->params;
		//过滤参数
		$data = filter_array($params,[
				"bankcard_id"  => "required", //银行卡号id
				"order_no"  => "required", // 订单号
				"sms_code"  => "required", // 短信验证码
		]);
		//验证参数
		if(empty($data['bankcard_id'])){
			api_resopnse( [], ApiStatus::CODE_20001,'bankcard_id必须');
			return;
		}
		if(empty($data['order_no'])){
			api_resopnse( [], ApiStatus::CODE_20001,'order_no必须');
			return;
		}
		if(empty($data['sms_code'])){
			api_resopnse( [], ApiStatus::CODE_20001,'sms_code必须');
			return;
		}

		//查询订单信息
		$this->order = $this->load->service('order2/order');
		//开启事务
		$this->order->startTrans();
		//查询条件
		$where['order_no'] = $data['order_no'];
		$order_info = $this->order->get_order_info($where,['lock'=>true]);
		//验证订单
		if(!$order_info){
			$this->order->rollback();
			api_resopnse( [], ApiStatus::CODE_50000,'订单不存在');
			return;
		}
		//验证订单已支付
		if($order_info["payment_status"] == zuji\order\PaymentStatus::PaymentSuccessful){
			$this->order->rollback();
			api_resopnse( [], ApiStatus::CODE_50000,'订单已支付');
			return;
		}
		//验证订单
		if($order_info['status']!=oms\state\State::OrderCreated && $order_info['status']!=oms\state\State::StoreConfirmed ){
			$this->order->rollback();
			api_resopnse( [], ApiStatus::CODE_50000,'订单异常');
			return;
		}
		//支付单
		$this->payment_table = $this->load->table('order2/order2_payment');
		$payment_info = $this->payment_table->where(['order_no'=>$order_info['order_no']])->find();
		$trade_no = $payment_info['trade_no'];
		if(!$trade_no){
			$this->order->rollback();
			api_resopnse( [], ApiStatus::CODE_50000,'支付单不存在');
			return;
		}
		//参数赋值
		$this->curl_create['method'] = "pay.unionpay.consume";
		$this->curl_create['params'] = [
				'user_id' => $order_info['user_id'],
				'bankcard_id' => $data['bankcard_id'],
				'out_no' => $trade_no,
				'amount' => $order_info['amount'],
				'fenqi' => $order_info['zuqi'],
				'sms_code' => $data['sms_code'],
		];
		//curl调起请求
		$result = $this->send_curl();
		if($result['code']!=0){
			$this->order->rollback();
			api_resopnse( $result, ApiStatus::CODE_50000,'支付接口异常');
			return;
		}
		$this->order->commit();
		api_resopnse( $result['data'], ApiStatus::CODE_0);
		return;
	}

	public function notify(){

	}

}