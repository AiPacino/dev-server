<?php

use alipay\WapPay ;
use zuji\order\Order;
use zuji\debug\Debug;
use zuji\debug\Location;
use zuji\Config;
use zuji\order\OrderStatus;
use zuji\order\PaymentStatus;
use zuji\email\EmailConfig;

hd_core::load_class('api', 'api');
/**
 * 订单控制器
 * @access public
 * @author limin <limin@huishoubao.com.cn>
 * @copyright (c) 2017, Huishoubao
 */
class trade_control extends api_control {
    protected $member = array();
    
    private $order_service = null;
    private $trade_service = null;
    private $payment_service = null;
    
    public function _initialize() {
        parent::_initialize();
        $this->order_service   = $this->load->service('order2/order');
	    $this->payment_service=$this->load->service('order2/payment');
	    $this->sku_serve = $this->load->service('goods2/goods_sku');
    }

    
    /**
     * 支付交易初始化，获取支付链接地址或form表单
     * 1）校验支付操作（禁止重复支付）
     * 2）创建支付单（支付单关联交易单）
     * 3）校验下单时间 （两小时）和库存
     * 4）生成交易url
     */
    public function initialize(){
    if(config("Test_Mobile_On")==true){
       if($this->member['mobile']==config("Test_Mobile")){
           api_resopnse([], ApiStatus::CODE_20001,ApiSubCode::Order_Error_Order_no,'测试用户禁止支付');
           return ;
       }
    }
	$result = ['payment_url'=>'','payment_form'=>''];
	
	// 获取去参数
	$order_params = filter_array($this->params, [
	    'order_id' => 'required',
	    'order_no' => 'required',
	]);
	if( count($order_params)==0 ){
	    $msg = '订单ID和订单编号不能同时为空';
	    // 接口访问
	    if( IS_API ){
		api_resopnse( ['payment_url'=>'','payment_form'=>''], ApiStatus::CODE_20001,'支付失败', ApiSubCode::Order_Error_Order_id.'/'.ApiSubCode::Order_Error_Order_no,$msg);
		return ;
	    }
	    //
	    echo $msg;exit;
	}
	$params = filter_array($this->params, [
	    'type' => 'required',	    //【必须】string；类型；ORDER，订单
	    'channel_code' => 'required',   //【必须】string；支付渠道；ALIPAY：支付宝
	    'return_url' => 'required|is_url',//【必须】string；支付完成后回跳地址
	]);
	
	if( count($params)!=3 ){
	    $msg = 'type,channel_code,return_url必须';
	    // 接口访问
	    api_resopnse( $result, ApiStatus::CODE_20001,'支付失败', ApiSubCode::Order_Error_Order_id.'/'.ApiSubCode::Order_Error_Order_id,$msg);
	    return ;
	}
	
	// 交易渠
	$payment_channel_code_list = [
	    'ALIPAY' => 1,
	];
	// 交易渠道值
	if( !isset($payment_channel_code_list[$params['channel_code']]) ){
	    api_resopnse( [], ApiStatus::CODE_20001,'支付失败', ApiSubCode::Trade_Channel_Error,'交易渠道错误');
	    return ;
	}
	$payment_channel_id = $payment_channel_code_list[$params['channel_code']];
	
	// 开启事务
	$n = $this->order_service->startTrans();
	if( !$n ){
	    api_resopnse( $result, ApiStatus::CODE_50004,'支付失败', 'db_transaction_error ','系统繁忙，请稍候重试...');
	    return ;
	}
	
	// 订单
	$additional = [
	    'lock' => true,
	];
	$order_info = $this->order_service->get_order_info($order_params,$additional);
	
	if( $order_info['order_status']!=OrderStatus::OrderCreated ){
	    api_resopnse( $result, ApiStatus::CODE_50004,'支付失败', ApiSubCode::Trade_Url_Error,'订单已取消或关闭');
	    return ;
	}

	
	// 是否允许支付
	if( $order_info['payment_time']>0 ){
	    api_resopnse( $result, ApiStatus::CODE_50004,'支付失败', ApiSubCode::Order_Has_Traded,'订单已经交易');
	    return ;
	}
	
	//判断下单时间是否超过两小时
	$time = $order_info['create_time']+Config::Order_TimeOut_Hours*60*60;
	if(time() >$time ){
	    //超时 修改订单状态
	    $out_time=$this->order_service->order_timeout($order_info['order_id']);
	    if($out_time){
		$this->order_service->commit();
		api_resopnse( $result, ApiStatus::CODE_50004,'', ApiSubCode::Order_Timeout,'订单超时');
		return ;
	    }else{
		$this->order_service->rollback();
	        api_resopnse( $result, ApiStatus::CODE_50004,'', ApiSubCode::Order_Error_Status,'订单状态超时');
	        return ;
	    }
	}
	

	// 租期判断
	if( Order::verifyZuqi($order_info['zuqi'])==false ){
	    $this->order_service->rollback();
	    Debug::error(Location::L_Trade, '支付[订单租期]错误', $order_info);
	    api_resopnse( $result, ApiStatus::CODE_50003,'支付失败', ApiSubCode::Order_Zuqi_Error,'订单租期错误');
	    return ;
	}
	
	// 租金和订单金额判断
	if( $order_info['zujin']<=0 || $order_info['amount']<=0 ){
	    $this->order_service->rollback();
	    Debug::error(Location::L_Trade, '支付[订单金额]错误', $order_info);
	    api_resopnse( $result, ApiStatus::CODE_50003,'支付失败', ApiSubCode::Order_Zuqi_Error,'订单租期错误');
	    return ;
	}
	
	// 判断库存大于0
	$goods_info =$this->order_service->get_goods_info($order_info['goods_id']);
	if(!$goods_info){
	    Debug::error( Location::L_Trade, '支付[goods(#'.$goods_info['goods_id'].')]不存在',$order_info );
	    api_resopnse( [], ApiStatus::CODE_50002,'商品错误', ApiSubCode::Order_Error_Goods_id,'商品错误');
	    return ;
	}	
	$sku_info = $this->sku_serve->api_get_info($goods_info['sku_id'],"");
	if( !$sku_info ){
	    Debug::error( Location::L_Trade, '支付[sku(#'.$goods_info['sku_id'].')]不存在',$goods_info );
	    api_resopnse( [], ApiStatus::CODE_20001,'参数错误', ApiSubCode::Sku_Error_Sku_id,'服务器繁忙，请稍后重试...');
	    return;
	}
	//判断商品是否下架
	if($sku_info['status']!=1){
	    Debug::error( Location::L_Order, 'sku(#'.$goods_info['sku_id'].')[下架或删除]错误',$sku_info );
	    api_resopnse( [], ApiStatus::CODE_50002,'SKU错误', ApiSubCode::Sku_Not_Exists,'服务器繁忙，请稍后重试...');
	    return;
	}	
	// 判断库存大于0
	if( $sku_info['number'] < 1){
	    api_resopnse( [], ApiStatus::CODE_50002,'SKU错误', ApiSubCode::Sku_Not_Exists,'服务器繁忙，请稍后重试...');
	    return;
	}	
	
	// 支付单，如果不存在，则创建
	$pament_info = null;
	if( !$order_info['payment_id'] ){
	    $pament_info = [
		'business_key' => $order_info['business_key'],
		'order_id' => $order_info['order_id'],
		'order_no' => $order_info['order_no'],
		'amount' => $order_info['amount'],
		'goods_name' => $order_info['goods_name'],
		'payment_channel_id' => $payment_channel_id,
		'payment_type_id' => $order_info['payment_type_id'],
	    ];
	    $payment_result = $this->payment_service->create( $pament_info );
	    if( !$payment_result ){
		// 事务回滚
		$this->order_service->rollback();
		Debug::error( Location::L_Trade, '支付[创建支付单]失败',$pament_info );
		$msg = 'create order-payment error：'.  get_error();
		api_resopnse( [], ApiStatus::CODE_50004,'支付失败','','支付失败');
		return ;
	    }
	    $pament_info = array_merge($pament_info,$payment_result);
	    $order_info['payment_id'] = $pament_info['payment_id'];
	    
	}else{
	    // 支付单，加排它锁
	    $pament_info = $this->payment_service->get_info( $order_info['payment_id'], ['lock'=>true] );
	    if( !$pament_info ){
		Debug::error( Location::L_Trade, '支付[支付单]获取失败',[
		    'order_info' => $order_info
		] );
		api_resopnse( [], ApiStatus::CODE_50002,'支付失败', ApiSubCode::Order_Payment_Not_Exixts,'支付异常，请稍候重试...');
		return ;
	    }
	    // 支付渠道不存在 或者 支付渠道不是当前指定的支付渠道，需要更新
	    if( $pament_info['payment_channel_id'] == 0 || $pament_info['payment_channel_id'] == $payment_channel_id ){
		// 更新 支付单的 支付渠道
		$this->payment_service->update_payment_channel_id( $pament_info['payment_id'], $payment_channel_id );
	    }
	}
		
	$data = [
	    'fenqi_zuqi' => $order_info['zuqi'],// 分期数
	    'fenqi_seller_percent' => 100,	// 商户承担分期收费比例，固定值 100
	    'trade_no' => $pament_info['trade_no'],
	    'amount' => $order_info['amount'],	// 支付价格
	    'subject' => $order_info['goods_name'],
	    'body' => '',// 产品描述，可选
	    'return_url' => $params['return_url'],	// 回跳地址
	];
	try {
	    // 支付宝应用ID标识
	    $appid = config('ALIPAY_APP_ID');
	    $appid = $appid ? $appid : \zuji\Config::Alipay_App_Id;
	    $WapPay = new \alipay\WapPay( $appid );
	    $payment_form = $WapPay->wapPay($data,true,true);
	    $payment_url = $WapPay->wapPay($data,true,false);
	    if( !$payment_url ){
		Debug::error( Location::L_Trade, '支付[创建支付url]失败',$data );
		api_resopnse( [], ApiStatus::CODE_50002,'支付失败', ApiSubCode::Trade_Url_Error,'创建支付url失败');
		return ;
	    }
	    // 提交事务
	    $this->order_service->commit();
	    
	    // 接口访问
	    if( IS_API ){
		
    		api_resopnse( ['payment_url'=>$payment_url,'payment_form'=>$payment_form], ApiStatus::CODE_0,'');
    		return ;
	    }
	    // url直接访问，返回form表单
	    echo $payment_form;exit;
	} catch (\Exception $exc) {
	    $this->debug_error(\zuji\debug\Location::L_Payment, '支付初始化失败', $exc->getMessage());
	    api_resopnse( [], ApiStatus::CODE_50004,'支付错误',  ApiSubCode::Trade_Url_Error,'支付初始化异常');
	    return ;
	}

    }
    
	/**
	 * 支付
	 */
	public function notify(){
		Debug::error(Location::L_Trade, '[支付异步通知]数据', $_POST);
	//	file_put_contents('./data/alipay_notify-'.date('Y-m-d-h').'.log', var_export($_POST,true));
//	$_POST = array (
//  'gmt_create' => '2017-11-28 02:58:20',
//  'charset' => 'UTF-8',
//  'seller_email' => 'zuji@huishoubao.com.cn',
//  'subject' => '三星S8 9成新 黑色 6月 8GB ',
//  'sign' => 'Ut2V7w6Owry6kUr0PkeLpve90kjvwYbYVuwsPp4pYhh3wHDTFa+NfvYN1H4I9BMhorVYeQN0nlvcMoDcgvfAxm53gLyLzswKDJsQi+p9MoOofqHQYRYQdCnlY0DUfRonGbFHEmRFrMxBea69yVqOucbuQFppkaUGyd9OJdkn2slthZ73mrzJ9vkujeUA3ls9/S/KMQ/GTANgmTKqzlfBGLRZf34Iry/VXPZhHAAOHPrAHEgbQgQ/86zfDqhkLTjtQ/NjLUMXIHZ/IFr9dntc6oaLfu3pMU2I6aGlWLgIkkAN9yJnvND+TPBrkv644XCZAVhwSf3b5p9zo42tikSN8g==',
//  'buyer_id' => '2088502596805705',
//  'invoice_amount' => '0.01',
//  'notify_id' => '79c50febba8f92eeefb80922e283a74lel',
//  'fund_bill_list' => '[{"amount":"0.01","fundChannel":"ALIPAYACCOUNT"}]',
//  'notify_type' => 'trade_status_sync',
//  'trade_status' => 'TRADE_SUCCESS',
//  'receipt_amount' => '0.01',
//  'app_id' => '2017102309481957',
//  'buyer_pay_amount' => '0.01',
//  'sign_type' => 'RSA2',
//  'seller_id' => '2088821542502025',
//  'gmt_payment' => '2017-11-28 02:58:21',
//  'notify_time' => '2017-11-28 02:58:21',
//  'version' => '1.0',
//  'out_trade_no' => '2017112800014',
//  'total_amount' => '0.01',
//  'trade_no' => '2017112821001004700573432203',
//  'auth_app_id' => '2017102309481957',
//  'buyer_logon_id' => '153****1612',
//  'point_amount' => '0.00',
//);
//		if( $_GET['test'] ){
//
//			$_POST = array (
//				'gmt_create' => '2018-01-11 11:10:25',
//				'charset' => 'UTF-8',
//				'seller_email' => 'shentiyang@huishoubao.com.cn',
//				'subject' => '三星Galasy S8＋',
//				'sign' => 'Hu5Eddvz7G0DFQCiEq43RwfcMEFYhgHbX2i7LQd45VS8ULOZ+EBo+vahCDsQ4Y6X41yTTiwbdFImGchm6GzbG0FsBq7DgCkweCgpMVgkr0X/MRFsRWn0OTWVAYpEA4vXlKQ5qX+gvg9mI/qcoqlD6F/tsJ9y6elfS/xvEQgqT9hxS5r5km+AIqnPi9F/iB0PqQ1KzYtTlm20tcHvMzqMtjl2yiS/yDhbtGWlh0xC4ea1JbUBq9hseRPfM4o+NUEDRPmH008qUg8hxTFoBfPAYKO2bk0p8cnYx5/Xk8ZoFqeMHEMER2NBOH+TYrLOcQRcrvbrb3gK+bqAqebkEa4nJw==',
//				'buyer_id' => '2088622912907505',
//				'invoice_amount' => '0.01',
//				'notify_id' => '6ba9c6cfe4e691217bcd48fad59d9bbjuy',
//				'fund_bill_list' => '[{"amount":"0.01","fundChannel":"ALIPAYACCOUNT"}]',
//				'notify_type' => 'trade_status_sync',
//				'trade_status' => 'TRADE_SUCCESS',
//				'receipt_amount' => '0.01',
//				'buyer_pay_amount' => '0.01',
//				'app_id' => '2017101309291418',
//				'sign_type' => 'RSA2',
//				'seller_id' => '2088821442906884',
//				'gmt_payment' => '2018-01-11 11:10:26',
//				'notify_time' => '2018-01-11 12:34:05',
//				'version' => '1.0',
//				'out_trade_no' => '2018011100036',
//				'total_amount' => '0.01',
//				'trade_no' => '2018011121001004500288221916',
//				'auth_app_id' => '2017101309291418',
//				'buyer_logon_id' => '183****9759',
//				'point_amount' => '0.00',
//			  );
//		}

        // 支付宝应用ID标识
        $appid = config('ALIPAY_APP_ID');
        $appid = $appid ? $appid : \zuji\Config::Alipay_App_Id;
        $WapPay = new \alipay\WapPay( $appid );

        $b =$WapPay->verify($_POST);
        if(!$b){
            \zuji\debug\Debug::error(\zuji\debug\Location::L_Payment,'[支付回调]签名验证失败', $_POST);
            echo '签名验证失败';
            exit;
        }

        

		// 开启事务
		$n = $this->order_service->startTrans();
		if( !$n ){
			Debug::error(Location::L_Payment,'[支付异步通知]事务开启失败', $_POST);
			echo 'db_transaction_error ';exit;
		}
		$trade_channel = $_GET['trade_channel'];
		
		// 交易表
		$payment_trade_table = $this->load->table('payment/payment_trade');
		//-+--------------------------------------------------------------------
		// | 获取 租机交易信息
		//-+--------------------------------------------------------------------
		$trade_info = $payment_trade_table->get_info_by_trade_no($_POST['out_trade_no'],['lock'=>true]);
		if( !$trade_info ){
			$this->order_service->rollback();
			$debug['msg'] = get_error();
			Debug::error(Location::L_Payment,'[支付异步通知]未找到支付交易记录', $debug);
			// 未找到支付交易记录
			echo '未找到支付交易记录';
			exit;
		}

		try { 

			// 订单
			$order_id = $trade_info['order_id'];
			$order_info = $this->order_service->get_order_info(['order_id'=>$order_id],['lock'=>true]);

			// 订单对象
			$Order = new oms\Order($order_info);

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
			$b = $Order->pay( $trade_channel, $_POST, $trade_info['trade_id'] );
			if( !$b ){
				$this->order_service->rollback();
				zuji\debug\Debug::error(Location::L_Trade, '[支付异步通知]支付操作失败', [
					'error' => get_error(),
					'$trade_channel' => $trade_channel,
					'$_POST' => $_POST,
					'trade_id' => $trade_info['trade_id'],
				]);
				var_dump('失败：'.get_error());exit;
			}
		} catch (\Exception $exc) { 
				$this->order_service->rollback();
				zuji\debug\Debug::error(Location::L_Trade, '[支付异步通知]支付异常', [
					'error' => $exc->getMessage(),
					'$trade_channel' => $trade_channel,
					'$_POST' => $_POST,
					'trade_id' => $trade_info['trade_id'],
				]);
				var_dump($exc->getMessage());
				exit;
		}
		$this->order_service->commit();

        // 获取AppID类型，线上还是线下
        $appid_service = $this->load->service('channel/channel_appid');
        $appid_info = $appid_service->get_info( $order_info['appid'], 'channel' );
		\zuji\debug\Debug::error(\zuji\debug\Location::L_SMS, '支付回调', $appid_info);
        // 只有 appid 信息获取成功时，才发送短息
        if( $appid_info ){
            if( $appid_info['appid']['type'] == 3 ){// 线下门店
                //发送提货短信。
                //$Redis = \zuji\cache\Redis::getInstans();
                //$info = $Redis->hget('channel:appid', $order_info['appid']);
                //$info = json_decode($info,true);
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
            }else{ // 非线下门店
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

		}
		echo 'success';
		exit;
	}
    
    /**
     * 资金授权 异步通知
     * 【注意：】支付平台会出现重复通知，需要做去重处理
     */
//    public function notify_bak() {
////	$_POST = array (
////  'gmt_create' => '2017-11-28 02:58:20',
////  'charset' => 'UTF-8',
////  'seller_email' => 'zuji@huishoubao.com.cn',
////  'subject' => '三星S8 9成新 黑色 6月 8GB ',
////  'sign' => 'Ut2V7w6Owry6kUr0PkeLpve90kjvwYbYVuwsPp4pYhh3wHDTFa+NfvYN1H4I9BMhorVYeQN0nlvcMoDcgvfAxm53gLyLzswKDJsQi+p9MoOofqHQYRYQdCnlY0DUfRonGbFHEmRFrMxBea69yVqOucbuQFppkaUGyd9OJdkn2slthZ73mrzJ9vkujeUA3ls9/S/KMQ/GTANgmTKqzlfBGLRZf34Iry/VXPZhHAAOHPrAHEgbQgQ/86zfDqhkLTjtQ/NjLUMXIHZ/IFr9dntc6oaLfu3pMU2I6aGlWLgIkkAN9yJnvND+TPBrkv644XCZAVhwSf3b5p9zo42tikSN8g==',
////  'buyer_id' => '2088502596805705',
////  'invoice_amount' => '0.01',
////  'notify_id' => '79c50febba8f92eeefb80922e283a74lel',
////  'fund_bill_list' => '[{"amount":"0.01","fundChannel":"ALIPAYACCOUNT"}]',
////  'notify_type' => 'trade_status_sync',
////  'trade_status' => 'TRADE_SUCCESS',
////  'receipt_amount' => '0.01',
////  'app_id' => '2017102309481957',
////  'buyer_pay_amount' => '0.01',
////  'sign_type' => 'RSA2',
////  'seller_id' => '2088821542502025',
////  'gmt_payment' => '2017-11-28 02:58:21',
////  'notify_time' => '2017-11-28 02:58:21',
////  'version' => '1.0',
////  'out_trade_no' => '2017112800014',
////  'total_amount' => '0.01',
////  'trade_no' => '2017112821001004700573432203',
////  'auth_app_id' => '2017102309481957',
////  'buyer_logon_id' => '153****1612',
////  'point_amount' => '0.00',
////);
//	unset( $_POST['m'] );
//	unset( $_POST['c'] );
//	unset( $_POST['a'] );
//	unset( $_POST['trade_channel'] );
//	
//	if( !isset($_GET['trade_channel']) ){
//	    echo 'trade_channel error';exit;
//	}
//	
//	//file_put_contents('./alipay-trade-notify-log.txt', 'notify-POST:'.var_export($_POST,true)."\n",FILE_APPEND);
//	
//	if( !is_array($_POST) || count($_POST)==0 ){
//	    echo 'request error';exit;
//	}
//	
//	$debug = [
//	    'msg' => '正常',
//	    'POST' => $_POST,
//	];
//	
//	$app_id = $_POST['app_id'];
//	try {
//	    // 签名校验
//	    $WapPay = new \alipay\WapPay( $app_id );
//	    $b = $WapPay->verify($_POST);
//	    if( !$b ){
//		$this->debug_error('1', '支付通知校验错误', $debug);
//		echo 'verify error';exit;
//	    }
//	} catch (\Exception $exc) {
//	    $debug['msg'] = $exc->getMessage();
//	    $this->debug_error('1', '支付通知校验错误', $debug);
//	    echo 'verify error';exit;
//	}
//
//	// 校验
//	$notify_info = filter_array($_POST, [
//	    'notify_time' => 'required',    // 通知的发送时间；YYYY-MM-DD HH:MM:SS
//	    'notify_type' => 'required',    // 通知类型；固定值：fund_auth_freeze
//	    'notify_id' => 'required',	    // 通知校验ID
//	    'sign_type' => 'required',
//	    'sign' => 'required',
//	    'subject' => 'required',
//	    'trade_no' => 'required',		// 支付宝交易码
//	    'out_trade_no' => 'required',	// 原支付请求的商户订单号
//	    'out_biz_no' => 'required',		// 商户业务ID，主要是退款通知中返回退款申请的流水号
//	    'trade_status' => 'required',   // 交易目前所处的状态
//	    'total_amount' => 'required',   // 本次交易支付的订单金额，单位为人民币（元）
//	    'receipt_amount' => 'required', // 商家在交易中实际收到的款项，单位为元
//	    'buyer_pay_amount' => 'required',// 用户在交易中支付的金额
//	    'refund_fee' => 'required',	// 退款通知中，返回总退款金额，单位为元，支持两位小数
//	    'gmt_create' => 'required',	    // 该笔交易创建的时间。格式为yyyy-MM-dd HH:mm:ss
//	    'gmt_payment' => 'required',    // 该笔交易的买家付款时间。格式为yyyy-MM-dd HH:mm:ss
//	    'gmt_close' => 'required',		    // 该笔交易结束时间。格式为yyyy-MM-dd HH:mm:ss
//	    'buyer_logon_id' => 'required',	    //【可选】付款方支付宝账号（Email 或手机号）
//	    'buyer_id' => 'required',	    //【可选】付款方支付宝用户号
//	    'seller_email' => 'required',	    //【可选】付款方支付宝账号（Email 或手机号）
//	    'seller_id' => 'required',	    //【可选】付款方支付宝用户号
//	]);
//	// 退款金额
//	if( !isset($notify_info['refund_fee']) ){
//	    $notify_info['refund_fee'] = 0;
//	}
//	// 该笔交易结束时间
//	if( !isset($notify_info['gmt_close']) ){
//	    $notify_info['gmt_close'] = 0;
//	}
//	//$notify_info['price'] = 1;// 无用字段
//	// 支付渠道
//	$notify_info['trade_channel'] = $_GET['trade_channel'];
//	// * 注意： 
//	// * 支付宝返回的 out_trade_no 是租机交易号，trade_no是支付宝交易流水号
//	// * 在我们的数据库中，把这两个值翻转了一下： trade_no：租机交易号；out_trade_no：第三方交易号
//	$_no = $notify_info['out_trade_no'];
//	$notify_info['out_trade_no'] = $notify_info['trade_no'];
//	$notify_info['trade_no'] = $_no;
//	
//	// 字段替换
//	$notify_info = replace_field($notify_info, [
//	    'buyer_logon_id' => 'buyer_email',
//	]);
//
//	// 开启事务
//	$n = $this->order_service->startTrans();
//	if( !$n ){
//	    $this->debug_error(\zuji\debug\Location::L_Payment,'支付通知接收[事务异常]','租机交易码：'.$notify_info['trade_no'].'；数据库事务开启失败');
//	    // 未找到支付交易记录
//	    echo 'error: db_transaction_error';exit;
//	}
//	
//	// 查询本地交易记录
//	$trade_service = $this->load->service('payment/payment_trade');
//	$trade_info = $trade_service->get_info_by_trade_no($notify_info['trade_no'],['lock'=>true]);
//	if( !$trade_info ){
//	    $this->debug_error(\zuji\debug\Location::L_Payment,'未找到支付交易记录','租机交易码：'.$notify_info['trade_no'].'；error: '.get_error());
//	    // 未找到支付交易记录
//	    echo 'error: trade not exists';exit;
//	}
//	// 查询订单数据
//	$order_service = $this->load->service('order2/order');
//	$order_info = $order_service->get_order_info( ['order_no'=>$trade_info['order_no']] );
//	if( !$order_info ){
//	    $this->debug_error('1','未找到支付订单','订单编号：'.$trade_info['order_no'].'；error:'.get_error());
//	    echo 'error: order not exists';exit;
//	}
//	
//	// 保存异步通知数据
//	$notify_id = $trade_service->trade_notify( $order_info, $trade_info, $notify_info );
//	//var_dump( $notify_id );exit;
//	if( !$notify_id ){
//	    $this->debug_error('1','保存支付异步通知失败',[
//		'order_no' => $order_info['order_no'],
//		'trade_no' => $trade_info['trade_no'],
//		'out_trade_no' => $notify_info['out_trade_no'],
//		'error_msg' => get_error(),
//	    ]);
//	    echo 'error:'.get_error();exit;
//	}
//	
//	if($notify_info['trade_status'] == 'TRADE_SUCCESS'){
//    	
//	    $goods_info =$this->order_service->get_goods_info($order_info['goods_id']);	    
//	    if(!$goods_info){
//	        echo 'error:'.get_error();exit;
//	    }
//	    $this->sku_serve = $this->load->service('goods2/goods_sku');
//	    $sku_info =$this->sku_serve->api_get_info($goods_info['sku_id'],"");
//	    //sku库存 -1
//	    $sku_up = $this->sku_serve->minus_number($sku_info['spu_id'],$sku_info['sku_id']);
//	    if(!$sku_up){
//		  echo 'error:'.get_error();exit;
//		  $this->debug_error(Location::L_Trade,'库存减少错误',$sku_info);
//	    } 
//    	
//	}
//	//发送邮件 -----begin
//	$data =[
//	    'subject'=>'用户已付款',
//	    'body'=>'订单编号：'.$order_info['order_no']."联系方式：".$order_info['mobile']." 请联系用户确认租用意向。",
//	    'address'=>[
//	        ['address' => EmailConfig::Service_Username]
//	    ],
//	];
//     $send =EmailConfig::system_send_email($data);
//     if(!$send){
//         Debug::error(Location::L_Delivery, "发送邮件失败", $data);
//     }      
//	
//	//发送邮件------end
//	
//	//提交事务
//        $this->order_service->commit();
//	
//        // 异步通知接收成功
//	echo 'success';exit;
////	
////	//生成服务单
////	$end_time =strtotime("+".$order_info['zuqi']." month",$order_info['create_time']);
////	$service =[
////	    'order_id' =>intval($order_info['order_id']),	 //【必选】  int 订单ID
////	    'order_no'=> $order_info['order_no'],	 //【必选】  string 订单编号
////	    'mobile' => $order_info['mobile'],	     //【必选】  string 用户手机号
////	    'user_id '=> $order_info['user_id'],	     //【必选】 int 用户ID
////	    'business_key'=>$order_info['business_key'],	 //【必选】 int 业务类型ID
////	    'begin_time'=>$order_info['create_time'],	 //【必选】 int 服务开始时间戳
////	    'end_time'=>$end_time,      //【必选】 int 服务结束时间戳
////	];
////	$service_id=$this->service_service->create($service);
////	if(!$service_id){
////	    $this->debug_error('1','生成服务单失败',$service);
////	    echo '生成服务单失败';exit;
////	    return false;
////	}
////	//同步更新到订单表
////	$enter_service =$this->order_service->enter_service(intval($order_info['order_id']),$service_id);
////	if(!$enter_service){
////	    $this->debug_error('1','同步到订单状态失败',['order'=>$order_info['order_id'],'service_id'=>$service_id]);
////	    echo '生成服务单失败';exit;
////	    return false;
////	}
//        
//    }
}