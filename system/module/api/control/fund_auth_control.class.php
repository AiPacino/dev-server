<?php

hd_core::load_class('api', 'api');
/**
 * 资金授权
 * @access public 
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 * @copyright (c) 2017, Huishoubao
 */
class fund_auth_control extends api_control {

    private $fund_auth;
    
    public function _initialize() {
	parent::_initialize();
		$this->fund_auth 		= $this->load->service('payment/fund_auth');
        $this->createpay_notify = $this->load->service('payment/createpay_notify');
        $this->createpay_notify_table = $this->load->table('payment/payment_createpay_notify');
		$this->order_service   	= $this->load->service('order2/order');
    }

    
    /**
     * 资金预授权
     */
    public function initialize() {
	
		$params = filter_array($this->params, [
		    'order_no' => 'required',
		    'return_url' => 'required|is_url',
		]);
		if( count($params) != 2 ){
		    api_resopnse([], ApiStatus::CODE_20001, '参数错误', ApiSubCode::Certivication_Param_Error, 'order_no,return_url必须');
			return;
		}
		
		// 默认值
		if( !isset($params['auth_channel']) || !\zuji\payment\FundAuth::verifyPlatform($params['auth_channel']) ){
		    // 默认选择支付宝渠道
		    $params['auth_channel'] = \alipay\fund\FundAuth::Platform;
		}
		
		$order_info = $this->order_service->get_order_info(['order_no'=>$params['order_no']]);
		if( !$order_info){
		   	api_resopnse([], ApiStatus::CODE_20001, '参数错误', ApiSubCode::Certivication_Param_Error, '订单不存在');
			return;
		}
		
		// 
		$order_id = $order_info['order_id'];
		$order_no = $order_info['order_no'];
		// 预授权资金 = 押金+租金
		$amount = $order_info['yajin'];
		
		if( $amount<=0 ){
		    $this->debug_error(\zuji\debug\Location::L_FundAuth, '金额错误', $amount);
		    api_resopnse([], ApiStatus::CODE_20001, '参数错误', ApiSubCode::Certivication_Param_Error, '授权金额不正确');
			return;
		}
		
		$fundauth_no = '';
		$request_no = '';
		// 查询订单，确认是否允许资金授权，然后再继续
		$auth_info = $this->fund_auth->get_info_by_order_no( $order_no );
		if( !$auth_info ){// 不存在，创建记录
			// fundauth_no 商户资金预授权唯一编码, request_no 请求流水唯一
			$fundauth_no = $request_no = \zuji\Business::create_business_no();
			$data = [
				'auth_channel' => $params['auth_channel'],
				'order_id' => $order_id,
				'order_no' => $order_no,
				'fundauth_no' => $request_no,
				'request_no' => $request_no,
				'amount' => $amount,
			];
			$auth_info = $this->fund_auth->create_auth( $data );
			if( !isset($auth_info['auth_id']) || !isset($auth_info['request_no']) ){
				$this->debug_error(\zuji\debug\Location::L_FundAuth, '授权记录创建失败', $data);
				api_resopnse([], ApiStatus::CODE_20001, '参数错误', ApiSubCode::Certivication_Param_Error, '授权记录创建失败');
				return;
			}
		}else{
			// 预授权记录状态（禁止重复预授权）
			if( $auth_info['auth_no'] || $auth_info['auth_status']!= \zuji\payment\FundAuth::CREATED ){
				api_resopnse([], ApiStatus::CODE_20001, '重复操作', 'Fund_Auth_Repetition', '重复授权');
				return;
			}
			// 同一笔预授权订单，必须使用一致的参数请求（后面请求必须保持和第一次去预授权时的参数一致）
			$fundauth_no = $auth_info['fundauth_no'];
			$request_no = $auth_info['request_no'];
//			// 更新预授权的 fundauth_no
//			$b = $this->fund_auth->set_fundauth_no($auth_info['auth_id'],$fundauth_no);
//			if( $b===false ){
//				$this->debug_error(\zuji\debug\Location::L_FundAuth, '授权记录fundauth_no更新失败', $data);
//				api_resopnse([], ApiStatus::CODE_50000, '操作失败', 'Fundauth_No_Fail', '资金授权编码错误');
//				return;
//			}
		}
		
		// 获取 预授权渠道 实例
		$auth = \zuji\payment\FundAuth::create( $params['auth_channel'] );
		if( !$auth ){
		    $this->debug_error(\zuji\debug\Location::L_FundAuth, '预授权渠道实例化错误', $params);
		    api_resopnse([], ApiStatus::CODE_20001, '参数错误', ApiSubCode::Certivication_Param_Error, '预授权渠道错误');
			return;
		}
		$data = [
		    'notify_url' => config('ALIPAY_FundAuth_Notify_Url'),
		    'return_url' => $params['return_url'],
		    'out_order_no' => ''.$fundauth_no,
		    'out_request_no' => ''.$request_no,
		    'order_title' => $order_info['goods_name'],
		    'amount' => $amount,
		];
	
		$url = $auth->freeseUrl($data);
		//$this->debug_error(\zuji\debug\Location::L_FundAuth, '[支付宝预授权]授权参数', $data);
		//file_put_contents('./data/fundauth-url-log.txt', $url."\n",FILE_APPEND);
    	api_resopnse(['url'=>$url], ApiStatus::CODE_0);
		return true;
    }

    /**
     * 资金授权 异步通知（区别与 资金解冻转支付）
     * 【注意：】支付平台会出现重复通知，需要做去重处理
     */
    public function notify() {
		//file_put_contents('./data/fundauth-log.txt', 'notify-POST:'.var_export($_POST,true),FILE_APPEND);
// 授权成功测试数据
			unset( $_POST['m'] );
			unset( $_POST['c'] );
			unset( $_POST['a'] );
			unset( $_POST['auth_channel'] );
			if( !is_array($_POST) || count($_POST)==0 ){
				echo '授权通知失败';
				exit;
			}
			// 默认值
			if( !isset($_GET['auth_channel']) || !\zuji\payment\FundAuth::verifyPlatform($_GET['auth_channel']) ){
				// 默认选择支付宝渠道
				$_GET['auth_channel'] = \alipay\fund\FundAuth::Platform;
			}

			// 校验
			$flag = '';
			$auth = \zuji\payment\FundAuth::create( $_GET['auth_channel'] );
			if( $auth ){
				$flag = $auth->signVerify($_POST,$_POST["sign"] );
				if( !$flag ){
					echo '签名错误';
					exit;
				}
			}else{
				echo '授权通知失败';
				exit;
			}

			$notify_info = filter_array($_POST, [
				'notify_time' => 'required',    // 通知的发送时间；YYYY-MM-DD HH:MM:SS
				'notify_type' => 'required',    // 通知类型；固定值：fund_auth_freeze
				'notify_id' => 'required',	    // 通知校验ID
				'sign_type' => 'required',
				'sign' => 'required',
				'auth_no' => 'required',
				'out_order_no' => 'required',	    // 商户授权金额订单号
				'total_freeze_amount' => 'required',    // 订单累计的冻结金额，单位：元（人民币）
				'total_unfreeze_amount' => 'required',  // 订单累计的解冻金额，单位：元（人民币）
				'total_pay_amount' => 'required',	    // 订单累计用于支付的金额，单位：元（人民币）
				'rest_amount' => 'required',	    // 订单总共剩余的冻结金额，单位：元（人民币）
				'order_status' => 'required',	    // 支付宝订单状态；INIT：初始；AUTHORIZED：已授权；FINISH：完成；CLOSED：关闭
				'operation_id' => 'required',	    // 支付宝资金操作流水号。
				'out_request_no' => 'required',	    // 商户本次资金操作的请求流水号
				'operation_type' => 'required',	    // 支付宝资金操作类型；固定值：FREEZE
				'amount' => 'required',		    // 本次操作冻结的金额：单位：元（人民币）
				'status' => 'required',		    // 资金操作流水的状态；INIT：初始；PROCESSING：处理中；SUCCESS：成功；FAIL：失败；CLOSED：关闭
				'gmt_create' => 'required',		    // 操作创建时间；YYYY-MM-DD HH:MM:SS
				'gmt_trans' => 'required',
				'payer_logon_id' => 'required',	    //【可选】付款方支付宝账号（Email 或手机号）
				'payer_user_id' => 'required',	    //【可选】付款方支付宝用户号
				'payee_logon_id' => 'required',	    //【可选】付款方支付宝账号（Email 或手机号）
				'payee_user_id' => 'required',	    //【可选】付款方支付宝用户号
			]);
					// 字段转换
			$notify_info = replace_field($notify_info, [
				'out_request_no' => 'request_no',
				'status' => 'request_status',
				'out_order_no' => 'fundauth_no',	// 商户资金授权号
			]);
			
			$time = time();
			$notify_info['create_time'] = $time;
			
			$fund_auth_notify_table = $this->load->table('payment/payment_fund_auth_notify');
			// 保存通知记录
			$fund_auth_notify_table->create( $notify_info );
			
			// 开启事务
			$this->order_service->startTrans();
			
			$b = $this->fund_auth->auth_notify( $notify_info );
			if( !$b ){
				$this->order_service->rollback();
                \zuji\debug\Debug::error(\zuji\debug\Location::L_FundAuth,'资金预授权',get_error());
				echo get_error();
				exit ;
			}
			$this->order_service->commit();
        //\zuji\debug\Debug::error(\zuji\debug\Location::L_FundAuth,'z资金预授权','success');
			echo 'success';
			exit;
			//api_resopnse( ['true'],ApiStatus::CODE_0  );
		//	file_put_contents('./log.log', 'notify-POST:'.var_export($_POST,true),FILE_APPEND);
		//	file_put_contents('./log.log', 'notify-info:'.var_export($notify_info,true),FILE_APPEND);
		//	file_put_contents('./log.log', 'notify-ID:'.var_export($notify_id,true),FILE_APPEND);
    }
    
    /**
     * 解冻转支付 异步通知
	 * 【开发使用，业务暂未使用】
     */
    public function createpay_notify_dev(){

	//	$_POST = array (
	//  'refund_fee' => '0.00',
	//  'trade_no' => '2017112821001004700573301963',
	//  'subject' => '预授权解冻转支付--测试',
	//  'buyer_email' => '153****1612',
	//  'gmt_create' => '2017-11-28 00:04:45',
	//  'notify_type' => 'trade_status_sync',
	//  'quantity' => '1',
	//  'out_trade_no' => '201711280002',
	//  'seller_id' => '2088821542502025',
	//  'notify_time' => '2017-11-28 00:08:08',
	//  'trade_status' => 'TRADE_FINISHED',
	//  'total_fee' => '0.01',
	//  'gmt_payment' => '2017-11-28 00:04:45',
	//  'seller_email' => 'zuji@huishoubao.com.cn',
	//  'gmt_close' => '2017-11-28 00:04:59',
	//  'notify_action_type' => 'finishFPAction',
	//  'price' => '0.01',
	//  'buyer_id' => '2088502596805705',
	//  'notify_id' => 'fff209e69286db3dc9c97497ef7113flel',
	//  'sign_type' => 'MD5',
	//  'sign' => 'cfaca36bfc478415c2d95888578a31b7',
	//);

		if( !is_array($_POST) || count($_POST)==0 ){
			api_resopnse([], ApiStatus::CODE_20001, '参数错误', ApiSubCode::Certivication_Param_Error, '参数错误');
			return;
		}
		// 校验
		$flag = '';
		$auth = \zuji\payment\FundAuth::create( $_GET['auth_channel'] );
		if( $auth ){
			$flag = $auth->signVerify($_POST,$_POST["sign"] );
			if( !$flag ){
				api_resopnse([], ApiStatus::CODE_10006, '签名错误', ApiSubCode::Certivication_Param_Error, '签名错误');
				return;
			}
		}else{
			api_resopnse([], ApiStatus::CODE_20001, '参数错误', ApiSubCode::Certivication_Param_Error, '渠道参数错误');
			return;
		}

		unset( $_POST['m'] );
		unset( $_POST['c'] );
		unset( $_POST['a'] );
		unset( $_POST['auth_channel'] );

		//file_put_contents('./fundauth-log.txt', 'notify-signVeryfy:'.$flag,FILE_APPEND);

		$notify_info = filter_array($_POST, [
			'notify_time' => 'required',    // 通知的发送时间；YYYY-MM-DD HH:MM:SS
			'notify_type' => 'required',    // 通知类型；固定值：fund_auth_freeze
			'notify_id' => 'required',	    // 通知校验ID
			'sign_type' => 'required',
			'sign' => 'required',
			'notify_action_type' => 'required',

			'out_trade_no' => 'required',   // 租机交易号
			'trade_no' => 'required',	    // 支付宝交易流水号
			// 交易目前所处的状态，
			// WAIT_BUYER_PAY：交易创建，等待买家付款。
			// TRADE_CLOSED：（1）在指定时间段内未支付时关闭的交易；（2）在交易完成全额退款成功时关闭的交易。
			// TRADE_SUCCESS：交易成功，且可对该交易做操作，如：多级分润、退款等。
			// TRADE_PENDING：等待卖家收款（买家付款后，如果卖家账号被冻结）。
			// TRADE_FINISHED：交易成功且结束，即不可再做任何操作
			'trade_status' => 'required',

			'subject' => 'required',
			'gmt_create' => 'required',		    // 操作创建时间；YYYY-MM-DD HH:MM:SS
			'gmt_payment' => 'required',
			'gmt_close' => 'required',

			'seller_email' => 'required',
			'seller_id' => 'required',
			'buyer_id' => 'required',
			'buyer_email' => 'required',

			'total_fee' => 'required',
			'price' => 'required',
			'quantity' => 'required',
			'refund_fee' => 'required',
		]);
		$notify_info['trade_channel'] = $_GET['auth_channel'];
		//
		// * 注意：
		// * 支付宝返回的 out_trade_no 是租机交易号，trade_no是支付宝交易流水号
		// * 在我们的数据库中，把这两个值翻转了一下： trade_no：租机交易号；out_trade_no：第三方交易号
		$_no = $notify_info['out_trade_no'];
		$notify_info['out_trade_no'] = $notify_info['trade_no'];
		$notify_info['trade_no'] = $_no;

		// 判断
		if(count($notify_info)!=22){
			\zuji\debug\Debug::error(\zuji\debug\Location::L_FundAuth, '解冻转支付通知参数错误', $notify_info);
			api_resopnse([], ApiStatus::CODE_20001, '参数错误', ApiSubCode::Certivication_Param_Error, '解冻转支付通知参数错误');
			return;
		}

		// 判断请求是否存在
		$auth_info = $this->fund_auth->get_info_by_trade_no( $notify_info['trade_no'] );
		if( !$auth_info ){
			\zuji\debug\Debug::error(\zuji\debug\Location::L_FundAuth, '解冻转支付交易码不存在', '交易码：'.$notify_info['trade_no']);
			api_resopnse([], ApiStatus::CODE_20001, '参数错误', ApiSubCode::Certivication_Param_Error, '解冻转支付交易码不存在');
			return;
		}

		if( $auth_info['auth_status']==5 ){
			\zuji\debug\Debug::error(\zuji\debug\Location::L_FundAuth, '资金预授权已关闭', '授权码：'.$auth_info['auth_no']);
			api_resopnse([], ApiStatus::CODE_20001, '参数错误', ApiSubCode::Certivication_Param_Error, '资金预授权已关闭');
			return;
		}

		// 创建通知记录
		$notify_b = $this->createpay_notify->create($notify_info);
		if(!$notify_b){
			api_resopnse([], ApiStatus::CODE_20001, '参数错误', ApiSubCode::Certivication_Param_Error, '创建通知记录失败');
			return;
		}

		// 字段转换
		$notify_info = replace_field($notify_info, [
			'total_fee' => 'total_amount',
		]);
		// 更新 资金预授权状态（解冻转支付操作成功）
		$_data = [
			'trade_no' => $notify_info['trade_no'],
			'amount' => $notify_info['total_amount'],
		] ;
		$b = $this->fund_auth->unfreeze_and_pay_success( $_data );
		// 更新失败时，需要特殊处理
		// 这个操作不在交易通知的事务中。
		if( !$b ){
			// 最好是发送错误通知，记录失败信息
			\zuji\debug\Debug::error(\zuji\debug\Location::L_FundAuth, '解冻转支付通知处理失败', get_error());
			api_resopnse([], ApiStatus::CODE_20001, '参数错误', ApiSubCode::Certivication_Param_Error, '解冻转支付通知处理失败');
			return;
		}

		api_resopnse( ['true'],ApiStatus::CODE_0  );
    }

    /**
     * 分期解冻 异步通知（区别与 资金解冻转支付）
     * 【注意：】支付平台会出现重复通知，需要做去重处理
     */
    public function fenqi_unfreeze_notify() {
     //   file_put_contents('./data/fenqi_unfreeze_notify-log.txt', 'notify-$_POST:'.var_export($_POST,true),FILE_APPEND);
        unset( $_POST['m'] );
        unset( $_POST['c'] );
        unset( $_POST['a'] );
        unset( $_POST['auth_channel'] );
        ob_start();
        if( !is_array($_POST) || count($_POST)==0 ){
            ob_clean();
            echo '解冻通知失败';
            exit;
        }
        // 默认值
        if( !isset($_GET['auth_channel']) || !\zuji\payment\FundAuth::verifyPlatform($_GET['auth_channel']) ){
            // 默认选择支付宝渠道
            $_GET['auth_channel'] = \alipay\fund\FundAuth::Platform;
        }

        // 校验
        $flag = '';
        $auth = \zuji\payment\FundAuth::create( $_GET['auth_channel'] );
        if( $auth ){
            $flag = $auth->signVerify($_POST,$_POST["sign"] );
            if( !$flag ){
                ob_clean();
                echo '签名错误';
                exit;
            }
        }else{
            ob_clean();
            echo '解冻通知失败';
            exit;
        }

        $notify_info = filter_array($_POST, [
            'notify_time' => 'required',    // 通知的发送时间；YYYY-MM-DD HH:MM:SS
            'notify_type' => 'required',    // 通知类型；固定值：fund_auth_freeze
            'notify_id' => 'required',	    // 通知校验ID
            'sign_type' => 'required',
            'sign' => 'required',
            'auth_no' => 'required',
            'out_order_no' => 'required',	    // 商户授权金额订单号
            'total_freeze_amount' => 'required',    // 订单累计的冻结金额，单位：元（人民币）
            'total_unfreeze_amount' => 'required',  // 订单累计的解冻金额，单位：元（人民币）
            'total_pay_amount' => 'required',	    // 订单累计用于支付的金额，单位：元（人民币）
            'rest_amount' => 'required',	    // 订单总共剩余的冻结金额，单位：元（人民币）
            'order_status' => 'required',	    // 支付宝订单状态；INIT：初始；AUTHORIZED：已授权；FINISH：完成；CLOSED：关闭
            'operation_id' => 'required',	    // 支付宝资金操作流水号。
            'out_request_no' => 'required',	    // 商户本次资金操作的请求流水号
            'operation_type' => 'required',	    // 支付宝资金操作类型；固定值：FREEZE
            'amount' => 'required',		    // 本次操作冻结的金额：单位：元（人民币）
            'status' => 'required',		    // 资金操作流水的状态；INIT：初始；PROCESSING：处理中；SUCCESS：成功；FAIL：失败；CLOSED：关闭
            'gmt_create' => 'required',		    // 操作创建时间；YYYY-MM-DD HH:MM:SS
            'gmt_trans' => 'required',
            'payer_logon_id' => 'required',	    //【可选】付款方支付宝账号（Email 或手机号）
            'payer_user_id' => 'required',	    //【可选】付款方支付宝用户号
            'payee_logon_id' => 'required',	    //【可选】付款方支付宝账号（Email 或手机号）
            'payee_user_id' => 'required',	    //【可选】付款方支付宝用户号
        ]);
        // 字段转换
        $notify_info = replace_field($notify_info, [
            'out_request_no' => 'request_no',
            'status' => 'request_status',
            'out_order_no' => 'fundauth_no',	// 商户资金授权号
        ]);

        $time = time();
        $notify_info['create_time'] = $time;

        $fund_auth_notify_table = $this->load->table('payment/payment_fund_auth_notify');
        // 保存通知记录
        $fund_auth_notify_table->add( $notify_info );

        // 开启事务
        $this->order_service->startTrans();

        $b = $this->fund_auth->unfreeze_notify( $notify_info );
        if( !$b ){
            $this->order_service->rollback();
            ob_clean();
            echo get_error();
            exit ;
        }
        $this->order_service->commit();
        ob_clean();
        echo 'success';
        exit;
    }
    
    /**
     * 分期解冻转支付 异步通知
     */
    public function fenqi_unfreeze_to_pay_notify(){
		
//        $_POST = array (
//            'trade_no' => '2018012921001004960260957687',
//            'subject' => '预授权解冻转支付',
//            'paytools_pay_amount' => '[{"PCREDIT":"0.01","PCC_PROD_ID":"9102"}]',
//            'buyer_email' => 'hai***@163.com',
//            'gmt_create' => '2018-01-29 10:34:15',
//            'notify_type' => 'trade_status_sync',
//            'quantity' => '1',
//            'out_trade_no' => '2018012900040',
//            'seller_id' => '2088821542502025',
//            'notify_time' => '2018-01-29 10:34:16',
//            'trade_status' => 'TRADE_SUCCESS',
//            'total_fee' => '0.01',
//            'gmt_payment' => '2018-01-29 10:34:16',
//            'seller_email' => 'zuji@huishoubao.com.cn',
//            'notify_action_type' => 'payByAccountAction',
//            'price' => '0.01',
//            'buyer_id' => '2088302825506960',
//            'notify_id' => '66bf15920a39e7d47fb57a1624c8e87neq',
//            'sign_type' => 'MD5',
//            'sign' => 'acaea228c506b2028b2927b6fe61d55d',
//        );
     //   file_put_contents('./data/fenqi_unfreeze_to_pay_notify-log.txt', 'notify-$_POST:'.var_export($_POST,true),FILE_APPEND);
        unset( $_POST['m'] );
        unset( $_POST['c'] );
        unset( $_POST['a'] );
        unset( $_POST['auth_channel'] );
        ob_start();
        if( !is_array($_POST) || count($_POST)==0 ){
            ob_clean();
            echo '分期解冻转支付-异步通知错误';
            exit;
        }
        // 校验
        $flag = '';
        $auth = \zuji\payment\FundAuth::create( $_GET['auth_channel'] );
        if( $auth ){
            $flag = $auth->signVerify($_POST,$_POST["sign"] );
            if( !$flag ){
                ob_clean();
                echo '签名错误';
                exit;
            }
        }else{
                ob_clean();
                echo '渠道参数错误';
                exit;
        }
		
		// 查询 转支付交易状态
		$query_info = $auth->query_createpay($_POST);
		if( $query_info === false ){// 失败
            ob_clean();
			echo get_error();
			exit;
		}
		
		// 参数提取
        $notify_info = filter_array($_POST, [
            'trade_no' => 'required',
            'subject' => 'required',
            'paytools_pay_amount' => 'required',
            'buyer_email' =>'required',
            'gmt_create' =>'required',
            'notify_type' =>'required',
            'quantity' =>'required',
            'out_trade_no' =>'required',
            'seller_id' =>'required',
            'notify_time' =>'required',
            'trade_status' =>'required',
            'total_fee' =>'required',
            'gmt_payment' =>'required',
            'seller_email' => 'required',
            'notify_action_type' => 'required',
            'price' => 'required',
            'buyer_id' =>'required',
            'notify_id' => 'required',
            'sign_type' => 'required',
            'sign' => 'required',
        ]);
        $notify_info['trade_channel'] = $_GET['auth_channel'];
        //
        // * 注意：
        // * 支付宝返回的 out_trade_no 是租机交易号，trade_no是支付宝交易流水号
        // * 在我们的数据库中，把这两个值翻转了一下： trade_no：租机交易号；out_trade_no：第三方交易号
        $_no = $notify_info['out_trade_no'];
        $notify_info['out_trade_no'] = $notify_info['trade_no'];
        $notify_info['trade_no'] = $_no;

        // 创建通知记录
        $notify_b = $this->createpay_notify_table->add($notify_info);
        if(!$notify_b){
            ob_clean();
            echo '创建通知记录失败';
			exit;
        }
		
        // 异步通知和支付宝查询到的订单当前状态一致，进行处理
        if( !in_array($query_info['status'], ['TRADE_SUCCESS','TRADE_FINISHED'])){
            ob_clean();
            echo 'success';
            exit;
		}
		// 字段转换
		$notify_info = replace_field($notify_info, [
			'total_fee' => 'total_amount',
		]);
		// 更新 资金预授权状态（解冻转支付操作成功）
		$_data = [
			'trade_no' => $notify_info['trade_no'],
			'amount' => $notify_info['total_amount'],
		] ;
		// 只处理交易成功的异步通知时，忽略其他状态通知
		if( $notify_info['trade_status'] === 'TRADE_SUCCESS' ){
			// 开启事务
			$this->order_service->startTrans();
			$b = $this->fund_auth->unfreeze_to_pay_notify( $notify_info );
			if( !$b ){
				$this->order_service->rollback();
				// 最好是发送错误通知，记录失败信息
				ob_clean();
				echo get_error();
				exit;
			}
			$this->order_service->commit();
		}
        ob_clean();
        echo 'success';
        exit;
    }

    /**
     * 押金解冻 异步通知（区别与 分期解冻）
     * 【注意：】支付平台会出现重复通知，需要做去重处理
     */
    public function yajin_unfreeze_notify() {
    //    file_put_contents('./data/yajin_unfreeze_notify-log.txt', 'notify-$_POST:'.var_export($_POST,true),FILE_APPEND);
        unset( $_POST['m'] );
        unset( $_POST['c'] );
        unset( $_POST['a'] );
        unset( $_POST['auth_channel'] );
        ob_start();
        if( !is_array($_POST) || count($_POST)==0 ){
            ob_clean();
            echo '解冻通知失败';
            exit;
        }
        // 默认值
        if( !isset($_GET['auth_channel']) || !\zuji\payment\FundAuth::verifyPlatform($_GET['auth_channel']) ){
            // 默认选择支付宝渠道
            $_GET['auth_channel'] = \alipay\fund\FundAuth::Platform;
        }

        // 校验
        $flag = '';
        $auth = \zuji\payment\FundAuth::create( $_GET['auth_channel'] );
        if( $auth ){
            $flag = $auth->signVerify($_POST,$_POST["sign"] );
            if( !$flag ){
                ob_clean();
                echo '签名错误';
                exit;
            }
        }else{
            ob_clean();
            echo '解冻通知失败';
            exit;
        }

        $notify_info = filter_array($_POST, [
            'notify_time' => 'required',    // 通知的发送时间；YYYY-MM-DD HH:MM:SS
            'notify_type' => 'required',    // 通知类型；固定值：fund_auth_freeze
            'notify_id' => 'required',	    // 通知校验ID
            'sign_type' => 'required',
            'sign' => 'required',
            'auth_no' => 'required',
            'out_order_no' => 'required',	    // 商户授权金额订单号
            'total_freeze_amount' => 'required',    // 订单累计的冻结金额，单位：元（人民币）
            'total_unfreeze_amount' => 'required',  // 订单累计的解冻金额，单位：元（人民币）
            'total_pay_amount' => 'required',	    // 订单累计用于支付的金额，单位：元（人民币）
            'rest_amount' => 'required',	    // 订单总共剩余的冻结金额，单位：元（人民币）
            'order_status' => 'required',	    // 支付宝订单状态；INIT：初始；AUTHORIZED：已授权；FINISH：完成；CLOSED：关闭
            'operation_id' => 'required',	    // 支付宝资金操作流水号。
            'out_request_no' => 'required',	    // 商户本次资金操作的请求流水号
            'operation_type' => 'required',	    // 支付宝资金操作类型；固定值：FREEZE
            'amount' => 'required',		    // 本次操作冻结的金额：单位：元（人民币）
            'status' => 'required',		    // 资金操作流水的状态；INIT：初始；PROCESSING：处理中；SUCCESS：成功；FAIL：失败；CLOSED：关闭
            'gmt_create' => 'required',		    // 操作创建时间；YYYY-MM-DD HH:MM:SS
            'gmt_trans' => 'required',
            'payer_logon_id' => 'required',	    //【可选】付款方支付宝账号（Email 或手机号）
            'payer_user_id' => 'required',	    //【可选】付款方支付宝用户号
            'payee_logon_id' => 'required',	    //【可选】付款方支付宝账号（Email 或手机号）
            'payee_user_id' => 'required',	    //【可选】付款方支付宝用户号
        ]);
        // 字段转换
        $notify_info = replace_field($notify_info, [
            'out_request_no' => 'request_no',
            'status' => 'request_status',
            'out_order_no' => 'fundauth_no',	// 商户资金授权号
        ]);

        $time = time();
        $notify_info['create_time'] = $time;

        $fund_auth_notify_table = $this->load->table('payment/payment_fund_auth_notify');
        // 保存通知记录
        $fund_auth_notify_table->add( $notify_info );

        // 开启事务
        $this->order_service->startTrans();

        $b = $this->fund_auth->yajin_unfreeze_notify( $notify_info );
        if( !$b ){
            $this->order_service->rollback();
            ob_clean();
            echo get_error();
            exit ;
        }
        $this->order_service->commit();
        ob_clean();
        echo 'success';
        exit;
    }

    /**
     * 押金解冻转支付 异步通知
     */
    public function yajin_unfreeze_to_pay_notify(){
       // file_put_contents('./data/yajin_unfreeze_to_pay_notify-log.txt', 'notify-$_POST:'.var_export($_POST,true),FILE_APPEND);
        unset( $_POST['m'] );
        unset( $_POST['c'] );
        unset( $_POST['a'] );
        unset( $_POST['auth_channel'] );

        ob_start();
        if( !is_array($_POST) || count($_POST)==0 ){
            ob_clean();
            echo '解冻通知失败';
            return;
        }
        // 校验
        $flag = '';
        $auth = \zuji\payment\FundAuth::create( $_GET['auth_channel'] );
        if( $auth ){
            $flag = $auth->signVerify($_POST,$_POST["sign"] );
            if( !$flag ){
                ob_clean();
                echo '签名错误';
                exit;
            }
        }else{
                ob_clean();
                echo '渠道参数错误';
                exit;
        }
		
		// 查询 转支付交易状态
		$query_info = $auth->query_createpay($_POST);
		if( $query_info === false ){// 失败
            ob_clean();
			echo get_error();
			exit;
		}
		
        $notify_info = filter_array($_POST, [
            'trade_no' => 'required',
            'subject' => 'required',
            'paytools_pay_amount' => 'required',
            'buyer_email' =>'required',
            'gmt_create' =>'required',
            'notify_type' =>'required',
            'quantity' =>'required',
            'out_trade_no' =>'required',
            'seller_id' =>'required',
            'notify_time' =>'required',
            'trade_status' =>'required',
            'total_fee' =>'required',
            'gmt_payment' =>'required',
            'seller_email' => 'required',
            'notify_action_type' => 'required',
            'price' => 'required',
            'buyer_id' =>'required',
            'notify_id' => 'required',
            'sign_type' => 'required',
            'sign' => 'required',
        ]);
        $notify_info['trade_channel'] = $_GET['auth_channel'];
        //
        // * 注意：
        // * 支付宝返回的 out_trade_no 是租机交易号，trade_no是支付宝交易流水号
        // * 在我们的数据库中，把这两个值翻转了一下： trade_no：租机交易号；out_trade_no：第三方交易号
        $_no = $notify_info['out_trade_no'];
        $notify_info['out_trade_no'] = $notify_info['trade_no'];
        $notify_info['trade_no'] = $_no;

        // 创建通知记录
        $notify_b = $this->createpay_notify_table->add($notify_info);
        if(!$notify_b){
            ob_clean();
            echo '创建通知记录失败';
            exit;
        }
        // 异步通知和支付宝查询到的订单当前状态一致，进行处理
        if( !in_array($query_info['status'], ['TRADE_SUCCESS','TRADE_FINISHED'])){
            ob_clean();
            echo 'success';
            exit;
		}
		// 字段转换
		$notify_info = replace_field($notify_info, [
			'total_fee' => 'total_amount',
		]);

		// 更新 资金预授权状态（解冻转支付操作成功）
		$_data = [
			'trade_no' => $notify_info['trade_no'],
			'amount' => $notify_info['total_amount'],
		] ;
		// 只处理交易成功的异步通知时，忽略其他状态通知
		if( $notify_info['trade_status'] === 'TRADE_SUCCESS' ){
			// 开启事务
			$this->order_service->startTrans();
			$b = $this->fund_auth->yajin_unfreeze_to_pay_notify( $notify_info );
			if( !$b ){
				$this->order_service->rollback();
				ob_clean();
				echo get_error();
				exit;
			}
			$this->order_service->commit();
		}
        ob_clean();
        echo 'success';
        exit;
    }

}
