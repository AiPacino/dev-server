<?php

use zuji\order\Order;
use zuji\debug\Debug;
use zuji\debug\Location;
use zuji\Config;
use zuji\Time;
use zuji\order\Service;
use zuji\email\EmailConfig;

/**
 * 订单控制器
 * @access public
 * @author limin <limin@huishoubao.com.cn>
 * @copyright (c) 2018, Huishoubao
 */
class notify_control extends control {

    public function _initialize() {
        parent::_initialize();
    }
	//支付通知
	public function pay(){
		//接收请求参数
		$jsonStr = file_get_contents("php://input");
		$params = json_decode($jsonStr,true);
		Debug::error(Location::L_Order,'支付回调参数',$params);
//		//过滤参数
		$data = filter_array($params,[
			"out_no"  => "required", //交易号
			"payment_no"  => "required", //支付系统交易号
		]);
		//验证参数
		if(empty($data['out_no'])){
			//out_no必须
			echo json_encode(["status"=>1,"msg"=>"out_no必须"]);
			die;
		}
		if(empty($data['payment_no'])){
			//payment_no必须
			echo json_encode(["status"=>1,"msg"=>"payment_no必须"]);
			die;
		}
		//支付交易号
		$trade_no = $data['out_no'];
		//查询支付单
		$this->payment_service = $this->load->service('order2/payment');
		$payment = $this->payment_service->get_info_orderid(['trade_no'=>$trade_no]);
		if(!$payment){
			Debug::error(Location::L_Order,'未找到支付单',$data);
			//未找到支付单
			echo json_encode(["status"=>1,"msg"=>"未找到支付单"]);
			die;
		}
		//查询订单信息
		$this->order_service = $this->load->service('order2/order');
		//开启事务
		$this->order_service->startTrans();

		$where['order_no'] = $payment['order_no'];
		$order_info = $this->order_service->get_order_info($where,['lock'=>true]);
		//验证订单
		if(!$order_info){
			//订单不存在
			echo json_encode(["status"=>1,"msg"=>"订单不存在"]);
			die;
		}
		//验证订单已支付
		if($order_info["payment_status"] == zuji\order\PaymentStatus::PaymentSuccessful){
			//订单已支付
			echo json_encode(["status"=>1,"msg"=>"订单已支付"]);
			die;
		}

		// 订单对象
		$Order = new oms\Order($order_info);

//		if(!$Order->allow_to_pay()){
//			//订单非法操作
//			echo json_encode(["status"=>1,"msg"=>"订单非法操作"]);
//			die;
//		}
		// 当前 操作员
		$admin = [
				'id' => $order_info['user_id'],
				'username' => $order_info['mobile'],
		];
		$Operator = new oms\operator\User( $admin['id'], $admin['username'] );

		// 订单 观察者主题
		$OrderObservable = $Order->get_observable();
		// 订单 观察者  日志
		$LogObserver = new oms\observer\LogObserver( $OrderObservable,'支付成功', '用户支付' );
		$LogObserver->set_operator($Operator);
		// 订单 观察者 状态流
		$FollowObserver = new oms\observer\FollowObserver( $OrderObservable );

		// 支付
		$b = $Order->pay( $order_info['payment_type_id'], ['trade_no'=>$trade_no,"payment_no"=>$data['payment_no'],"payment_id"=>$payment['payment_id'],"payment_amount"=>$payment['amount']],0);
		if(!$b){
			//事务回滚
			$this->order_service->rollback();
			//订单状态更新失败
			echo json_encode(["status"=>1,"msg"=>"订单状态更新失败"]);
			die;
		}
		//提交事务
		$this->order_service->commit();

		// 获取AppID类型，线上还是线下
		$appid_service = $this->load->service('channel/channel_appid');
		$appid_info = $appid_service->get_info( $order_info['appid'], 'channel' );
		\zuji\debug\Debug::error(\zuji\debug\Location::L_SMS, '支付回调', $appid_info);
		// 只有 appid 信息获取成功时，才发送短息
		if( $appid_info['appid']['type'] == 3 ){// 线下门店
			//发送提货短信。
			if( $appid_info['_channel']['name'] ){ // 判断门店名称
				$result = ['auth_token'=>  $this->auth_token,];
				$sms = new \zuji\sms\HsbSms();
				$b = $sms->send_sm($order_info['mobile'],'hsb_sms_c6adf',[
						'storeName' =>$appid_info['appid']['name'],    // 传递参数
						'orderNo' => $order_info['order_no'],    // 传递参数
						'serviceTel'=>Config::Customer_Service_Phone,
				],$order_info['order_no']);
				if (!$b) {
					Debug::error(Location::L_Trade,'线下支付成功短信',$b);
				}
			}
		}else{
			// 非线下门店
			//线上发送短信
			$sms_data =[
					'mobile' => $order_info['mobile'],
					'orderNo' => $order_info['order_no'],
					'realName' =>$order_info['realname'],
					'goodsName' =>$order_info['goods_name'],
			];
			\zuji\sms\SendSms::authorize_success($sms_data);
			//线上发送邮件
			//发送邮件 -----begin
			$data =[
					'subject'=>'用户已付款',
					'body'=>'订单编号：'.$order_info['order_no']."联系方式：".$order_info['mobile']." 请联系用户确认租用意向。",
					'address'=>[
							['address' => EmailConfig::Service_Username]
					],
			];

			$send =EmailConfig::system_send_email($data);
			if(!$send){
				Debug::error(Location::L_Trade, "发送邮件失败", $data);
			}
			//发送邮件------end
		}
		Debug::error(Location::L_Order,'支付成功回调',$data);
		//订单状态更新失败
		echo json_encode(["status"=>0]);
		die;
	}
	//退款通知
	public function refund()
    {
        //接收请求参数
		$jsonStr = file_get_contents("php://input");
		$params = json_decode($jsonStr,true);

        //过滤参数
        $data = filter_array($params, [
            "out_refund_no" => "required", //发送退款流水号
			"refund_no"  => "required", //返回退款流水号
        ]);
        //验证参数
        if (empty($data['out_refund_no'])) {
			//order_no必须
			echo json_encode(["status"=>1,"msg"=>"out_refund_no必须"]);
			die;
        }
		if (empty($data['refund_no'])) {
			//order_no必须
			echo json_encode(["status"=>1,"msg"=>"refund_no必须"]);
			die;
		}
		//查询退款单
		$this->refund_service = $this->load->service('order2/refund');
        //发送和返回 的流水号 要互换处理
		$refund = $this->refund_service->get_info_where(['refund_no'=>$data['out_refund_no'],'out_refund_no'=>$data['refund_no']]);
		if(!$refund){
			Debug::error(Location::L_Order,'未找到退款单',$data);
			//未找到支付单
			echo json_encode(["status"=>1,"msg"=>"未找到支付单"]);
			die;
		}
        //查询订单信息
        $this->order_service = $this->load->service('order2/order');
        $where['order_no'] = $refund['order_no'];
        $order_info = $this->order_service->get_order_info($where);
        //验证订单
        if (!$order_info) {
            Debug::error(Location::L_Order,'银联退款回调 订单不存在',$params);
			//订单不存在
			echo json_encode(["status"=>1,"msg"=>"订单不存在"]);
			die;
        }
        // 订单对象
        $Order = new oms\Order($order_info);
        if (!$Order->allow_to_refund_notify()) {
            Debug::error(Location::L_Order,'银联退款回调 订单不允许更改退款状态',$params);
			//订单不允许更改退款状态
			echo json_encode(["status"=>1,"msg"=>"订单不允许更改退款状态"]);
			die;
        }
        $this->order_service->startTrans();
        // 订单 观察者主题
        $OrderObservable = $Order->get_observable();
        // 订单 观察者 状态流
        $FollowObserver = new oms\observer\FollowObserver($OrderObservable);
        $b =$Order->refund_notify([]);
        if(!$b){
            $this->order_service->rollback();
            Debug::error(Location::L_Order,'退款回调失败',get_error());
			echo json_encode(["status"=>1,"msg"=>"退款回调失败"]);
			die;
        }
        $this->order_service->commit();
        Debug::error(Location::L_Order,'退款回调成功',$data);
		//ssuccess
		echo json_encode(["status"=>0]);
		die;
    }
}