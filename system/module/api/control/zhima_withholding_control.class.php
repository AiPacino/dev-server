<?php
/**
 * 代扣
 */
use zuji\debug\Debug;
use zuji\debug\Location;

hd_core::load_class('api', 'api');


/**
 * 支付宝小程序 芝麻 代扣控制器 （接收回调）
 * @access public
 * @author limin <zhangjinhui@huishoubao.com.cn>
 * @copyright (c) 2017, Huishoubao
 */
class zhima_withholding_control extends api_control {

    //取消
    private $CANCEL = 'ZM_RENT_ORDER_CANCEL';
    //完结
    private $FINISH = 'ZM_RENT_ORDER_FINISH';
//    //分期扣款
//    private $INSTALLMENT = 'ZM_RENT_ORDER_INSTALLMENT';
    //确认订单
    private $CREATE = 'ZM_RENT_ORDER_CREATE';
    //返回数组
    private $data = [];

    public function _initialize() {
        parent::_initialize();
    }

    /*
     * 芝麻支付宝小程序 代扣接口(订单关闭 订单取消)异步回调
     */
    public function withholdingCloseCancelNotify(){
        $ZhimaWithholding = new \alipay\ZhimaWithholding(\zuji\Config::ZHIMA_MINI_APP_ID);
        $b = $ZhimaWithholding->verify( $_POST );
        if(!$b){
            Debug::error(Location::L_AlipayMini,'扣款回调验签','签名验证失败fail');
            echo '签名验证失败fail';exit;
        }
        $this->data = $_POST;
        if($this->data['notify_type'] == $this->CANCEL){
            $this->OrderCancelNotify();
        } if($this->data['notify_type'] == $this->FINISH){
            //查询redis锁 判断为 分期扣款回调 还是 关闭订单回调
            $redis_order = \zuji\OrderLocker::getLock($this->data['out_order_no']);
			// 有交易，必然有 这两个参数
			if( isset($this->data['alipay_fund_order_no'])){ //  && isset($this->data['out_trans_no '])
                $this->withholdingNotify();
				echo 'success';
				exit;
			}
            $this->orderCloseNotify();
        }else if($this->data['notify_type'] == $this->CREATE){
            $this->rentTransition();
        }
    }

    /*
     * 芝麻支付宝小程序 订单取消接口异步回调
     */
    private function OrderCancelNotify(){
        //Debug::error(Location::L_Order,'取消订单回调',$this->data);
        $data = $this->data;
        //验证参数
        $data = filter_array($data,[
            'out_order_no' => 'required',
        ]);
        $params['order_no'] = $data['out_order_no'];
        if (empty($params['order_no'])) {
            echo '订单编号必须';
            return;
        }
        /*****************依赖服务************/
        $this->order = $this->load->service('order2/order');
        //开启事务
        $this->order->startTrans();
        $where['order_no'] = $params['order_no'];
        //获取订单信息
        $order = $this->order->get_order_info($where,['lock'=>true]);
        if ( $order === false ){
            echo '订单不存在';
            return;
        }
        //判断订单是否已经取消
        if( $order['status'] == \oms\state\State::OrderCanceled ){
			//去掉订单锁处理订单
			\zuji\OrderLocker::unlock($params['order_no']);
            echo 'success';
            return;
        }
        //退款接口处理操作
        $Orders = new \oms\Order($order);
        // 订单 观察者主题
        $OrderObservable = $Orders->get_observable();
        // 订单 观察者 状态流
        $FollowObserver = new oms\observer\FollowObserver($OrderObservable);
        $b =$Orders->refund([]);
        if(!$b){
            //事务回滚
            $this->order->rollback();
            echo '订单取消失败';
            return;
        }
        //提交事务
        $this->order->commit();
		//去掉订单锁处理订单
		\zuji\OrderLocker::unlock($order['order_no']);
        //取消订单发送短信
        \zuji\sms\SendSms::cancel_order([
            'mobile' => $order['mobile'],
            'orderNo' => $order['order_no'],
            'realName' => $order['realname'],
            'goodsName' => $order['goods_name'],
        ]);
        Debug::error(Location::L_Order,'取消订单发送短信',$this->data);
        echo 'success';
        exit;
    }

    /*
     * 芝麻支付宝小程序 订单关闭接口异步回调
     */
    private function orderCloseNotify(){
        //Debug::error(Location::L_Order,'关闭订单回调',$this->data);
        $data = $this->data;
        //验证参数
        $data = filter_array($data,[
            'out_order_no' => 'required',
        ]);
        $params['order_no'] = $data['out_order_no'];
        if (empty($params['order_no'])) {
            echo '订单编号必须';
            return;
        }
        /*****************依赖服务************/
        $this->order = $this->load->service('order2/order');
        //开启事务
        $this->order->startTrans();
        $where['order_no'] = $params['order_no'];
        //获取订单信息
        $order = $this->order->get_order_info($where,['lock'=>true]);
        if ( $order === false ){
            echo '订单不存在';
            return;
        }
        //判断订单是否已经关闭
        if( $order['status'] == \oms\state\State::OrderClosed ){
            //去掉订单锁处理订单
            \zuji\OrderLocker::unlock($params['order_no']);
            echo 'success';
            return;
        }
        //退款接口处理操作
        $Orders = new \oms\Order($order);
        // 订单 观察者主题
        $OrderObservable = $Orders->get_observable();
        // 订单 观察者 状态流
        $FollowObserver = new oms\observer\FollowObserver($OrderObservable);
        // 当前 操作员
        $Operator = new oms\operator\System( 0,'system' );
        // 订单 观察者  日志
        $LogObserver = new oms\observer\LogObserver( $OrderObservable , "订单关闭","小程序回调操作");
        $LogObserver->set_operator($Operator);
        $b =$Orders->refund([]);
        if(!$b){
            //事务回滚
            $this->order->rollback();
            echo '订单关闭失败';
            return;
        }
        //提交事务
        $this->order->commit();
        //去掉订单锁处理订单
        \zuji\OrderLocker::unlock($order['order_no']);
        echo 'success';
        exit;
    }

    /*
     * 芝麻支付宝小程序 订单扣款接口异步回调
     */
    private function  withholdingNotify(){
        Debug::error(Location::L_AlipayMini,'扣款回调原始数据',$this->data);
        $data = $this->data;
        // * 注意：
        // * 支付宝返回的 out_trade_no 是租机交易号，trade_no是支付宝交易流水号
        // * 在我们的数据库中，把这两个值翻转了一下： trade_no：租机交易号；out_trade_no：第三方交易号
        $notify_info['out_trade_no'] = $data['alipay_fund_order_no'];
        $notify_info['order_no'] = $data['out_order_no'];
        $notify_info['trade_no'] = $data['out_trans_no'];
        $notify_info['pay_amount'] = $data['pay_amount'];
        $notify_info['pay_status'] = $data['pay_status'];
        //-+--------------------------------------------------------------------
        // | 更新交易表状态
        //-+--------------------------------------------------------------------
        $time = time();
        // 订单
        $this->order_service = $this->load->service('order2/order');
        $order_info = $this->order_service->get_order_info(['order_no'=>$notify_info['order_no']]);
        //扣款要发送的短信
        $data_sms =[
            'mobile'=>$order_info['mobile'],
            'orderNo'=>$order_info['order_no'],
            'realName'=>$order_info['realname'],
            'goodsName'=>$order_info['goods_name'],
            'zuJin'=>$notify_info['pay_amount'],
        ];
        // 更新 分期表
        $instalment_table = $this->load->table('order2/order2_instalment');
        //开启事务
        $b = $instalment_table->startTrans();
        if( !$b ){
            Debug::error(Location::L_AlipayMini, '[扣款异步通知]事务开启失败', $notify_info);
            echo '事务开启失败';
            exit;
        }
        //查询分期表是否已经扣款成功
        $instalment_info = $instalment_table->get_info(['trade_no'=>$notify_info['trade_no']]);
        if( $instalment_info['status']  == 2 ){
            //去掉订单锁处理订单
            \zuji\OrderLocker::unlock( $order_info['order_no'] );
            Debug::error(Location::L_AlipayMini, '订单已扣款', $notify_info);
            echo 'success';
            exit;
        }
        //状态值转换
        $trade_status = 0;
        if( $notify_info['pay_status'] == 'PAY_SUCCESS' ){
            //成功
            $trade_status = 2;
            \zuji\sms\SendSms::instalment_pay($data_sms);
        }elseif( $notify_info['pay_status'] == 'PAY_FAILED' ){
            //失败
            $trade_status = 3;
//            \zuji\sms\SendSms::instalment_pay_failed($data_sms);
        }elseif( $notify_info['pay_status'] == 'PAY_INPROGRESS' ){
            //扣款中
            $trade_status = 5;
        }
        Debug::error(Location::L_AlipayMini, '扣款发送短信成功', $notify_info);
        $n = $instalment_table->where([
            'trade_no' => $notify_info['trade_no'],
        ])->limit(1)->save([
            'status' => $trade_status,
            'out_trade_no' => $notify_info['out_trade_no'],
            'payment_time' => $time,
            'update_time' => $time,
        ]);
        if( $n===false ){
            Debug::error(Location::L_AlipayMini, '[扣款异步通知]更新分期扣款状态失败', $notify_info);
            echo '更新分期状态失败';
            exit;
        }
        // 提交事务
        $b = $instalment_table->commit();
        if( !$b ){
            Debug::error(Location::L_AlipayMini, '[扣款异步通知]事务提交失败', $notify_info);
            echo '事务提交失败';
            exit;
        }
        Debug::error(Location::L_AlipayMini, '扣款成功转换数据', $notify_info);
        //去掉扣款订单锁处理订单
        \zuji\OrderLocker::unlock( $order_info['order_no'] );
        echo 'success';
        exit;
    }

    /**
     * 确认订单异步通知接口
     *      订单创建成功异步通知
     */
    public function rentTransition(){
        // 验证参数
        $params = $this->data;
        $params = filter_array($params, [
            'zm_order_no' => 'required', //【必须】string；芝麻订单号
            'out_order_no' => 'required', //【必须】string；商户订单号
            'notify_type'=>'required', //【必须】string；通知类型
            'credit_privilege_amount'=>'required', //【必须】string；用户信用权益金额
            'fund_type'=>'required', //【必须】string；预授权，代扣免押，两者都支持
            'channel'=>'required', //【不必须】string；订单来源
            'order_create_time'=>'required', //【必须】string；订单创建时间
            'notify_app_id'=>'required', //【必须】string；通知目标APP_ID
        ]);
//        if(count($params)<7){
//            echo '参数有误';exit;
//        }

        //记录小程序订单确认信息
        $this->set_confirmed($params);
		
        $this->zhima_certification_service =$this->load->service('order2/zhima_certification');
		$zhima_order_info = $this->zhima_certification_service->where(['out_order_no'=>$params['out_order_no']])->find();
		if(!$zhima_order_info){ // 不存在则创建
			$this->zhima_certification_service->update([
				'out_order_no' => $params['out_order_no'] ,// 租机订单号
				'order_no' => $params['zm_order_no'] ,// 芝麻订单号
				'create_time' => time() ,
			]);
		}
        $this->order = $this->load->service('order2/order');
        $where['order_no'] = $params['out_order_no'];
        //获取订单信息
        $order = $this->order->get_order_info($where,['lock'=>true]);
		if( !$order ){
			echo '订单['.$where['order_no'].']不存在';exit;
		}
		
		// 订单对象
        $orderObj = new oms\Order($order);
				
		// 已创建时，进行操作
		if( $orderObj->get_status() != \oms\state\State::OrderCreated ){
			echo '订单['.$where['order_no'].']当前状态下，不支持支付操作';exit;
		}

        //下单成功,修改订单状态到准发发货
        $this->update($params['out_order_no']);

        //订单流
        $this->set_follow($params['out_order_no']);

        //日志
        $this->set_log($params['out_order_no']);

        //订单解锁
		\zuji\OrderLocker::unlock($params['out_order_no']);

        echo 'success';
        exit;

    }

    /**
     * 记录小程序订单确认信息
     */
    private function set_confirmed($params){
        $row = [
            'notify_type'=>$params['notify_type'],
            'zm_order_no'=>$params['zm_order_no'],
            'order_no'=>$params['out_order_no'],
            'credit_privilege_amount'=>$params['credit_privilege_amount'],
            'fund_type'=>$params['fund_type'],
            'channel'=>$params['channel'],
            'order_create_time'=>$params['order_create_time'],
            'notify_app_id'=>$params['notify_app_id'],
            'data_text'=>json_encode($params)
        ];
        $zhima_order_confirmed =$this->load->table('order2/zhima_order_confirmed');
        $zhima_order_confirmed->update($row);
    }

    /**
     * 确认订单流
     */
    private function set_follow($order_no){
        $load = \hd_load::getInstance();
        $order_table = $load->table('order2/order2_follow');
        $order2_table = $load->table('order2/order2');
        $order2_info = $order2_table->where(['order_no'=>$order_no])->find();

        $data['create_time'] =time();
        $data['old_status'] =\oms\state\State::OrderCreated;
        $data['new_status'] =\oms\state\State::PaymentSuccess;
        $data['order_id']=$order2_info['order_id'];

        $follow_id = $order_table->add($data);
        if(!$follow_id){
            Debug::error(Location::L_Order, '创建订单流失败', $data);
        }
    }
    /**
     * 支付日志
     */
    private function set_log($order_no){
        $load = \hd_load::getInstance();
        $order_table = $load->table('order2/order2_log');
        $log = [
            'order_no'      => $order_no,
            'action'        => "支付成功",
            'operator_id'   => 0,
            'operator_name' => 'system',
            'operator_type' => \oms\operator\System::Type_System,
            'msg'           => "芝麻小程序用户确认订单",
            'system_time'   => time()
        ];
        $add_log = $order_table->add($log);

        if(!$add_log){
            \zuji\debug\Debug::error(\zuji\debug\Location::L_Order, '写入日志失败', $log);
        }
    }
    /**
     * 修改数据库
     */
    private function update($order_no){
        $load = \hd_load::getInstance();
        $order_table = $load->table('order2/order2');

        //更新订单
        $order_data = [
            'status' =>\oms\state\State::PaymentSuccess,
            'order_status'=>\zuji\order\OrderStatus::OrderCreated,
            'update_time'=> time(),//更新时间
        ];

        $b =$order_table->where(['order_no'=>$order_no])->save($order_data);
        if(!$b){
            set_error("更新订单状态失败");
            return false;
        }
        return true;
    }

    //记录状态流及记录操作日志
    public function log($orderObj,$title,$text=""){
        // 当前 操作员
        $Operator = new oms\operator\System(\oms\operator\System::Type_System,'system');
        // 订单 观察者主题
        $OrderObservable = $orderObj->get_observable();
        // 订单 观察者 状态流
        $FollowObserver = new oms\observer\FollowObserver( $OrderObservable );
        // 订单 观察者  日志
        $LogObserver = new oms\observer\LogObserver( $OrderObservable , $title, $text);
        //插入日志
        $LogObserver->set_operator($Operator);
    }
}