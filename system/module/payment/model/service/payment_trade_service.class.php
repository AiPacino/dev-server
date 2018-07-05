<?php
use zuji\Config;
/**
 *  支付交易 服务层
 */

class payment_trade_service extends service {

    public function _initialize() {
	$this->payment_trade = $this->load->table('payment/payment_trade');
	$this->payment_trade_notify = $this->load->table('payment/payment_trade_notify');
	
    }

    
    /**
     * 创建交易记录
     * 注意：不需要事务，该服务接口，需要嵌入到其他业务的事务中
     * @param array $data
     * [
     *	    'order_id' => '',	    //【必须】int；订单ID
     *	    'order_no' => '',	    //【必须】string；订单编号
     *	    'trade_type' => '',	    //【必须】int；交易类型
     *	    'trade_channel' => '',  //【必须】string；交易渠道
     *	    'amount' => '',	    //【必须】price；交易金额
     *	    'subject' => '',	    //【必须】string；交易标题
     *	    'seller_id' => '',	    //【可选】int；收款方用户ID，接收到交易成功的通知时，会更新
     *	    'buyer_id' => '',	    //【可选】int；付款方用户ID
     * ]
     * @return mixed	false：创建失败；array：创建成功
     * [
     *	    'trad_id' => '',
     *	    'trad_no' => '',
     *	    'order_id' => '',
     *	    'order_no' => '',
     *	    'trade_type' => '',
     *	    'trade_channel' => '',
     *	    'amount' => '',
     *	    'seller_id' => '',	    
     *	    'buyer_id' => '',
     *	    'subject' => '',
     *	    'trade_no' => '',	    //【必须】string；租机交易编号
     *	    'create_time' => '',    //【必须】int；创建时间
     * ]
     */
    public function create( $params ){
	$data = filter_array($params, [
     	    'trade_no' => 'required',	    // 租机交易码
     	    'payment_id' => 'required|is_id',
     	    'order_id' => 'required|is_id',
     	    'order_no' => 'required',
     	    'trade_type' => 'required',
     	    'trade_channel' => 'required',
     	    'amount' => 'required',
     	    'subject' => 'required',
     	    'seller_id' => 'required',	    // 【可选】
     	    'buyer_id' => 'required',	    // 【可选】
	]);
	
	if( !isset($data['seller_id']) ){
	    $data['seller_id'] = '';
	}
	if( !isset($data['buyer_id']) ){
	    $data['buyer_id'] = '';
	}
	if( count($data) != 10 ){
	    set_error('创建交易记录失败，参数错误');
	    \zuji\debug\Debug::error(\zuji\debug\Location::L_Trade, '创建交易记录失败，参数错误', ['params'=>$params,'data'=>$data]);
	    return false;
	}
	// 创建时间
	$data['create_time'] = time();
	$data['trade_status'] = 1;  // 创建，默认状态为1
	
	$trade_id = $this->payment_trade->add($data);
	if( !$trade_id ){
	    set_error('创建交易记录失败，保存错误');
	    return false;
	}
	$data['trade_id'] = $trade_id;
	
	$this->payment_trade->commit();
	return $data;
    }
    
    /**
     * 根据交易码，获取交易记录
     * @param string $trade_no	交易码
     */
    public function get_info_by_trade_no( $trade_no, $additional=[] ){
	
	$info = $this->payment_trade->get_info_by_trade_no( $trade_no, $additional=[] );
	return $info;
    }
    
    /**
     * 交易通知
     * @param array $user_info	    用户基本信息
     * [
     *	    'user_id' => '',
     *	    'username' => '',
     *	    'mobile' => '',
     * ]
     * @param array $trade_info    交易记录信息
     * [
     *	    'trade_id' => '',	    // 交易记录ID
     *	    'trade_no' => '',	    // 交易码
     *	    'payment_id' => '',	    // 支付单ID
     *	    'order_id' => '',	    // 订单ID
     *	    'order_no' => '',	    // 订单号
     * ]
     * @param array $notify_data    交易异步通知信息
     * 注意： 
     * 支付宝返回的 out_trade_no 是租机交易号，trade_no是支付宝交易流水号
     * 在我们的数据库中，把这两个值翻转了一下： trade_no：租机交易号；out_trade_no：第三方交易号
     */
    public function trade_notify( $order_info, $trade_info, $notify_data ){
	$order_info = filter_array($order_info, [
	    'user_id' => 'required|is_id',
	    'mobile' => 'required|is_mobile',
	    'order_id' => 'required|is_id',
	    'order_no' => 'required',
	]);
	if( count($order_info)!=4 ){
	    set_error('交易通知保存错误，$user_info 参数错误');
	    return false;
	}
	$trade_info = filter_array($trade_info, [
	    'trade_id' => 'required|is_id',
	    'trade_no' => 'required',
	    'payment_id' => 'required|is_id',
	    'order_id' => 'required|is_id',
	    'order_no' => 'required',
	    'trade_status' => 'required',
	]);
	if( count($trade_info)!=6 ){
	    set_error('交易通知保存错误，$trade_info 参数错误');
	    return false;
	}
	$data = filter_array($notify_data, [
	    'trade_channel' => 'required',
	    'notify_time' => 'required',    // 通知的发送时间；YYYY-MM-DD HH:MM:SS
	    'notify_type' => 'required',    // 通知类型；固定值：fund_auth_freeze
	    'notify_id' => 'required',	    // 通知校验ID
	    'sign_type' => 'required',
	    'sign' => 'required',
	    //'notify_action_type' => 'required',
	    
	    'out_trade_no' => 'required',   // 支付宝交易流水号
	    'trade_no' => 'required',	    // 租机交易号
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
	    
	    'total_amount' => 'required',
	    'refund_fee' => 'required',
	]);
	// 交易关闭时间，默认值为0
	if( !isset($data['gmt_close']) ){
	    $data['gmt_close'] = 0;
	}
	
	if( count($data)!=19){
	    set_error('交易通知保存错误，$notify_data 参数错误');
	    return false;
	}
	
	$time = time();
	$data['create_time'] = $time;
	//更新 状态
	$trade_status = 0;
	if( $data['trade_status'] == 'WAIT_BUYER_PAY' ){
	    $trade_status = 2;
	}elseif( $data['trade_status'] == 'TRADE_PENDING' ){
	    $trade_status = 3;
	}elseif( $data['trade_status'] == 'TRADE_SUCCESS' ){
	    $trade_status = 4;
	}elseif( $data['trade_status'] == 'TRADE_CLOSED' ){
	    $trade_status = 5;
	}elseif( $data['trade_status'] == 'TRADE_FINISHED' ){
	    $trade_status = 6;
	}
	
	// 保存通知记录
	$data['trade_id'] = $trade_info['trade_id'];
	$notify_result = $this->payment_trade_notify->create( $data );
	if( !$notify_result ){
	    set_error('保存交易通知失败');
	    $this->payment_trade_notify->rollback();
	}
	
	// 通知支付单支付结果（当前交易记录状态在 TRADE_SUCCESS之前，都可以更新为 TRADE_SUCCESS）
	// 如果是 支付成功 的通知，同步到交易记录总，并且同步到支付单中
	// 其他状态通知，不同步到订单系统
	if( $trade_status == 4 && $trade_info['trade_status']<4){// 
	    $trade_data = [
		'trade_status' => $trade_status, 
		'payment_amount' => $data['total_amount'], // 单位：元
		'refund_amount' => $data['refund_fee'],	// 单位：元
		'payment_time' => $time, 
		'update_time' => $time, 
		'seller_id' => $data['seller_id'], 
		'seller_email' => $data['seller_email'], 
		'buyer_id' => $data['buyer_id'], 
		'buyer_email' => $data['buyer_email'], 
		'out_trade_no' => $data['out_trade_no'], 
	    ];
	    //\zuji\debug\Debug::error('1', '测试异步通知数据', $trade_data);
	    // 更新 交易信息
	    $b = $this->payment_trade->where(['trade_no'=>$data['trade_no']])->save( $trade_data );
	    if( !$b ){
		set_error('更新交易记录失败');
		$this->payment_trade_notify->rollback();
		return false;
	    }
	    
	    // 支付成功，同步数据到支付单
	    $payment_service = $this->load->service('order2/payment');
	    $payment_data = [
		'payment_amount' => 100*$data['total_amount'],
	    ];
	    $b = $payment_service->payment_successful( $trade_info['order_id'], $trade_info['payment_id'],$payment_data);
	    if( !$b ){
		set_error('支付单更新支付结果失败');
		$this->payment_trade_notify->rollback();
		return false;
	    }
	    //发送短信
        $sms_data =[
            'mobile' => $order_info['mobile'],
            'orderNo' => $order_info['order_no'],
            'realName' =>$order_info['realname'],
            'goodsName' =>$order_info['goods_name'],
        ];
        \zuji\sms\SendSms::authorize_success($sms_data);

	    // 支付成功 记录操作日志
	    $order_log = $this->load->service('order2/order_log');
	    $r = $order_log->add([
		'order_no' => $order_info['order_no'],
		'action' => '支付成功',
		'msg' => '支付成功；'.'交易号：'.$trade_info['trade_no'],
		'operator_id' => $order_info['user_id'],
		'operator_name' => $order_info['mobile'],
		'operator_type' => 2,
	    ]);
	}
	
	// 成功，提交事务
	$this->payment_trade_notify->commit();
	return $notify_result;
    
    }
    
   /**
    * 退款更新退款时间 退款金额，
    * @param string $trade_no      【必须】 租机交易码（唯一）
    * @param string $refund_amount 【必须】退款金额
    */
    
    public function update_refund($trade_no,$refund_amount){
        if(!isset($trade_no)){
            set_error("交易码错误");
            return false;
        }
        if(!is_price($refund_amount)){
           set_error("退款金额错误");
           return false;
        }
        
        $b = $this->payment_trade->update_refund($trade_no,$refund_amount);
	if( $b ){
	    return true;
	}
	return false;
    }

}
