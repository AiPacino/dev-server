<?php

use zuji\Business;
use zuji\order\RefundStatus;
use zuji\order\OrderStatus;
use zuji\payment\Payment;
use zuji\Config;
use zuji\debug\Location;
use zuji\order\Order;
use zuji\debug\Debug;
use zuji\order\refund\Refund;
use zuji\email\EmailConfig;
/**
 * 		退款单
 */

// 加载 goods 模块中的 init_control
hd_core::load_class('base','order2');

class refund_control extends base_control {

    /**
     * @var array 关键字搜索类型列表
     */
    protected $keywords_type_list = [
        'order_no' => '订单编号',
        'mobile' => '会员账户',
    ];

	public function _initialize() {
		parent::_initialize();
		// $this->order_service=$this->load->service('order2/order');
		// $this->refund_service =$this->load->service('order2/refund');
		// $this->goods_service = $this->load->service('order2/goods');
  //       $this->order2_table = $this->load->table('order2/order2');
		
		//权限判断
		$promission_arr = [];
		$promission_arr['refund_should'] = $this->check_promission_operate('order2','refund','refund_should');    //修改退款额
		$promission_arr['refund_confirm'] = $this->check_promission_operate('order2','refund','refund_confirm');    //退款
		$this->promission_arr = $promission_arr;
	}

    /**
     * 退款单列表
     *
     */
	public function index(){
        $this->order_service=$this->load->service('order2/order');
        $this->refund_service =$this->load->service('order2/refund');
	    $where = [];
	    if(isset($_GET['refund_status']) && $_GET['refund_status']>0){
	        $where['refund_status'] = intval($_GET['refund_status']);
	    }
	    if($_GET['begin_time']!=''){
	        $where['begin_time']=strtotime($_GET['begin_time']);
	    }
	    if($_GET['end_time']!=''){
	        $where['end_time']=strtotime($_GET['end_time']);
	    }
	    if(intval($_GET['business_key'])>0 ){
	        $where['business_key'] = intval($_GET['business_key']);
	    }
	    if($_GET['keywords']!=''){
	        if($_GET['kw_type']=='mobile'){
	            $where['mobile'] = $_GET['keywords'];
	        }else{
	            $where['order_no'] = $_GET['keywords'];
	        }
	    }
	    $size = (isset($_GET['limit']) && is_numeric($_GET['limit'])) ? $_GET['limit'] : 20;
	    $count  = $this->refund_service->get_count($where);
	    $pages  = $this->admin_pages($count,$size);
	    $additional =[
	        'page'=>$_GET['page'],
	        'size'=>$size,
	        'orderby'=>'',
	    ];
	    $refund_list = $this->refund_service->get_list($where,$additional);
	    $order_ids = array_column($refund_list, 'order_id');
	    $order_ids = array_unique($order_ids);	    
	    $order_list = $this->order_service->get_order_list(['order_id'=>$order_ids],['size'=>count($order_ids)]);
	    mixed_merge($refund_list, $order_list, 'order_id','order_info');
	    foreach($refund_list as $k=>&$item){
	        if(!isset($item['order_info'])){continue;}
	        $item['business_name']  = Business::getName($item["business_key"]);
	        $item['goods_name'] = $item['order_info']['goods_name'];
	        $item['status'] = RefundStatus::getStatusName($item["refund_status"]);
	        $item['order_status'] =OrderStatus::getStatusName($item['order_info']['order_status']); 
	        $item['payment_channel'] = Payment::getChannelName($item['payment_channel_id']);
	        $item['refund_amount'] = Order::priceFormat($item['refund_amount']/100);
	        $item['payment_amount'] = Order::priceFormat($item['payment_amount']/100);
	        $item['should_amount'] = Order::priceFormat($item['should_amount']/100);
	        $item['order_status'] =$item['order_info']['order_status'];

	        $Refund =Refund::createRefund($item);
	        $Orders = new \oms\Order($item['order_info']);

	        //是否允许修改
	        $item['allow_should'] =false;
	        if($this->promission_arr['refund_should'] && $Refund->allow_should_amount() && !$Orders->order_islock()){
	            $item['allow_should'] =true;
	        }
	        //是否允许退款
	        $item['allow_refund']=false;
	        if($this->promission_arr['refund_confirm'] && $Refund->allow_to_refund() && !$Orders->order_islock()){
	            $item['allow_refund']=true;
	        }

	    }
	    $lists = array(
	        'th' => array(
	            'business_name' => array('title' => '业务类型','length' => 5),
	            'order_no' => array('title' => '订单编号','length' => 10),
	            'payment_channel' => array('length' => 5,'title' => '支付渠道'),
	            'mobile' => array('length' => 10,'title' => '用户账号'),
	            'goods_name' => array('length' => 10,'title' => '退款商品'),
	            'payment_amount_show' => array('length' => 5,'title' => '支付金额'),
	            'should_amount_show' => array('length' => 5,'title' => '应退金额'),
	            'should_remark' => array('length' => 10,'title' => '应退备注'),
	            'refund_amount_show' => array('length' => 5,'title' => '退款金额'),
	            'refund_remark' => array('length' => 10,'title' => '退款备注'),
	            'refund_time' => array('length' => 10,'title' => '退款时间','style'=>"date"),
	            'status' => array('length' => 5,'title' => '退款状态'),
	        ),
	        'lists' => $refund_list,
	        'pages' => $pages,
	    );
	    
	    $status_list = array_merge(['0'=>'全部'],RefundStatus::getStatusList());
	    $tab_list = [];
	    foreach( $status_list as $k=>$name ){
	        if($k==RefundStatus::RefundCreated){
	            continue;
	        }
	        $css = '';
	        if ($_GET['refund_status'] == $k){
	            $css = 'current';
	        }
	        $url = url('order2/refund/index',array('refund_status'=>$k));
	        $tab_list[] = '<a class="'.$css.'" href="'.$url.'">'.$name.'</a>';
	    }
	    $this->load->librarys('View')
            ->assign('tab_list',$tab_list)
            ->assign('pay_channel_list',$this->pay_channel_list)
            ->assign('keywords_type_list',$this->keywords_type_list)
            ->assign('lists',$lists)
            ->assign('pages',$pages)
            ->display('refund_index');

    }
    /**
     *退款单详情
     */
    public function detail(){
        $this->refund_service =$this->load->service('order2/refund');
        // 是否内嵌
        $inner = boolval($_GET['inner']);
        $refund_id = intval(trim($_GET['refund_id']));
        if ($refund_id < 1){
            showmessage("退款单ID错误", "null", 0);
        }
        $refund_info = $this->refund_service->get_info($refund_id);

        $this->load->librarys('View')
        ->assign('inner', $inner)
        ->assign('refund_info', $refund_info)
        ->display('refund_detail');
    }
    /**
     * 申请退款
     */
    public function create_refund(){
        $this->order_service=$this->load->service('order2/order');
        // 表单提交处理
        if (checksubmit('dosubmit')) {
             // 当前 操作员
            $admin = [
                'id' =>$this->admin['id'],
                'username' =>$this->admin['username'],
            ];
            $Operator = new oms\operator\Admin( $admin['id'], $admin['username'] );
            
            if(!$this->order_service->startTrans()){
                showmessage('服务器繁忙', 'null', 0, '', 'json');
            }
            $options = ['lock' => true];
            $order_id =intval($_POST['order_id']);
            //查询订单
            $order_info = $this->order_service->get_order_info(['order_id' => $order_id], $options);
            if ($order_info['payment_status'] != \zuji\order\PaymentStatus::PaymentSuccessful) {
                $this->order_service->rollback();
                showmessage("订单未完成支付",'null',0);
            }
            if ($order_info['order_status'] != OrderStatus::OrderCreated) {
                $this->order_service->rollback();
                showmessage("订单未开启，禁止操作",'null',0);
            }
            
            $Orders = new \oms\Order($order_info);
            if(!$Orders->allow_to_create_refund()){
                $this->order_service->rollback();
                showmessage("该订单不允许申请退款",'null',0);
            }
            //支付单查询
            $payment_service = $this->load->service('order2/payment');
            $payment_info = $payment_service->get_info($order_info['payment_id'],$options);
            if (!$payment_info) {
                $this->order_service->rollback();
                showmessage("支付单查询失败",'null',0);
            }
            // 应退金额（元），不得大于 支付金额
            $should_amount = floatval($_POST['should_amount']);
            // 备注
            $should_remark = trim($_POST['should_remark']);
            if ($should_amount > $payment_info['payment_amount']) {
                $this->order_service->rollback();
                showmessage("退款金额超出限制",'null',0);
            }
            
            $data = [
                'order_id' => intval($order_info['order_id']),
                'payment_id' => intval($order_info['payment_id']),
                'should_amount' => $should_amount,  // 应退金额（单位：元）
                'should_remark' => $should_remark,// 备注
            ];
            
            try{
                // 订单 观察者主题
                $OrderObservable = $Orders->get_observable();
                // 订单 观察者 状态流
                $FollowObserver = new oms\observer\FollowObserver( $OrderObservable );
                // 订单 观察者  日志
                $LogObserver = new oms\observer\LogObserver( $OrderObservable , "申请退款", "应退金额：".$should_amount." 备注：".$should_remark);
                $LogObserver->set_operator($Operator);
            
                $b =$Orders->create_refund($data);
                if(!$b){
                    $this->order_service->rollback();
                    Debug::error(Location::L_Order,"失败:".get_error(), $data);
                    showmessage('申请退款失败','null',0);
                }
                $this->order_service->commit();
                //发送邮件 -----begin
                $data =[
                    'subject'=>'申请退款',
                    'body'=>'订单编号：'.$order_info['order_no']." 需要向用户退款，请处理。",
                    'address'=>[
                        ['address' => EmailConfig::Finance_Username]
                    ],
                ];
                $send =EmailConfig::system_send_email($data);
                if(!$send){
                    Debug::error(Location::L_Order, "发送邮件失败", $data);
                }
                //发送邮件------end
                showmessage('操作成功','null',1);
            }catch (\Exception $exc){
                $this->order_service->rollback();
                Debug::error(Location::L_Refund, '申请退款失败:'.$exc->getMessage(), $data);
                showmessage($exc->getMessage(),'null',0);
            }
        }else{
            $order_id =intval($_GET['order_id']);
            $order_info = $this->order_service->get_order_info(['order_id' => $order_id]);
            
            $Orders = new \oms\Order($order_info);
            if(!$Orders->allow_to_create_refund()){
                showmessage("该订单不允许申请退款",'null',0);
            }
            $payment_service = $this->load->service('order2/payment');
            $payment_info = $payment_service->get_info($order_info['payment_id']);
            if (!$payment_info) {
                showmessage("支付单查询失败",'null',0);
            }
            $this->load->librarys('View')
            ->assign('order_id', $order_id)
            ->assign('payment_info', $payment_info)
            ->display('alert_order_cancel');
        }

    }
    /*
     * 点击退款
     */
    public function refund_confirm() {
        $this->order_service=$this->load->service('order2/order');
        $this->refund_service =$this->load->service('order2/refund');

        if (checksubmit('dosubmit')) {
             // 当前 操作员
            $admin = [
                'id' =>$this->admin['id'],
                'username' =>$this->admin['username'],
            ];
            $Operator = new oms\operator\Admin( $admin['id'], $admin['username'] );
            
            $trans = $this->order_service->startTrans();
            if(!$trans){
                showmessage("服务器繁忙 请稍候再试！","null",0);
            }
            $additional =[
                'lock' =>true,
            ];
            $refund_id =intval($_POST['refund_id']);
            $refund_info =  $this->refund_service->get_info($refund_id,$additional);
            //判断订单是否有效
            $order_info = $this->order_service->get_order_info(['order_id'=>$refund_info['order_id']],$additional);
            $Orders = new \oms\Order($order_info);
            if(!$Orders->allow_to_refund()){
                $this->order_service->rollback();
                showmessage("不允许退款","null",0);
            }
            // 查询支付单信息
            $this->payment_service = $this->load->service('order2/payment');
            $payment_info = $this->payment_service->get_info( $refund_info['payment_id']);
            
            // 支付单支付成功状态
            if( $payment_info['payment_status'] != \zuji\order\PaymentStatus::PaymentSuccessful ){
                $this->order_service->rollback();
                showmessage("支付状态错误","null",0);
            }
            if($order_info['payment_type_id'] == Config::FlowerStagePay){
                // 查询交易信息
                $this->payment_trade_service = $this->load->service('payment/payment_trade');
                $trade_info = $this->payment_trade_service->get_info_by_trade_no( $payment_info['trade_no'] );

                if( !$trade_info['out_trade_no'] ){
                    $this->order_service->rollback();
                    showmessage("数据错误，没有查询到相关交易记录","null",0);
                }

            }
            $refund_remark =$_POST['refund_remark'];
            $refund_amount =trim($_POST['refund_amount']);
             
	    // 校验退款金额
            if($refund_amount!=$refund_info['should_amount']){
                showmessage("退款金额不正确","null",0);
            }
            $data =[
                'order_id'=>intval($order_info['order_id']),     // 【必须】订单ID
                'refund_id'=>$refund_id,    // 【必须】退款单ID
                'refund_remark'=>$refund_remark,// 【必须】退款备注
                'admin_id'=>intval($this->admin['id']),     // 【必须】操作员ID
                'business_key'=>intval($refund_info['business_key']), // 【必须】业务类型
                'goods_id'=>intval($refund_info['goods_id']),     // 【必须】商品ID
                'trade_no'=>$payment_info['trade_no'],//【可选】银联交易码
                'payment_no'=>$payment_info['payment_no'],//【可选】银联支付系统交易号
                'should_amount'=>$refund_amount*100,

            ];


            // 订单 观察者主题
            $OrderObservable = $Orders->get_observable();
            if($refund_info['refund_no']!="" && $order_info['payment_type_id'] == Config::UnionPay){
                     $check_data =$this->check_curl($order_info,$refund_info['refund_no']);
                     if($check_data['data']['refund_status']==0) {
                         showmessage("退款处理中,请您耐心等待",'null',0);
                     }elseif($check_data['data']['refund_status']==1){
                        // 订单 观察者 状态流
                        $FollowObserver = new oms\observer\FollowObserver($OrderObservable);
                        //查询订单信息
                        if (!$Orders->allow_to_refund_notify()) {
                            $this->order_service->rollback();
                            showmessage('订单不允许更改退款状态','null',0);
                        }
                        $b =$Orders->refund_notify([]);
                        if(!$b){
                            $this->order_service->rollback();
                            showmessage(get_error(),'null',0);
                        }
                         $this->order_service->commit();
                        showmessage("退款成功",'null',1);
                    }
            }
            try{
                // 订单 观察者  日志
                $LogObserver = new oms\observer\LogObserver( $OrderObservable , "退款","退款金额：".$refund_amount." 备注：".$refund_remark);
                $LogObserver->set_operator($Operator);
                if($order_info['payment_type_id'] == Config::FlowerStagePay){
                    // 订单 观察者 状态流
                    $FollowObserver = new oms\observer\FollowObserver( $OrderObservable );
                }
                $b =$Orders->refund($data);
                if(!$b){
                    $this->order_service->rollback();
                    Debug::error(Location::L_Refund,'退款失败', get_error());
                    showmessage('退款失败','null',0);
                }else{
                    $this->order_service->commit();
                    //发送短信。
                    $sms_data =[
                        'realName' => $order_info['realname'],
                        'orderNo' => $order_info['order_no'],
                        'goodsName' => $order_info['goods_name'],
                        'mobile' =>$order_info['mobile'],
                    ];
                 //   \zuji\sms\SendSms::remove_authorize($sms_data);
                    showmessage('操作成功','null',1);
                }
            }catch (\Exception $exc){
                $this->order_service->rollback();
                Debug::error(Location::L_Order, '退款失败:'.$exc->getMessage(), $data);
                showmessage($exc->getMessage(),'null',0);
            }
        }else{
            $refund_id = intval(trim($_GET['refund_id']));    
            if($refund_id < 1){
                showmessage("参数错误","null",0);
            }
            $refund_info =  $this->refund_service->get_info($refund_id);
            //判断订单是否有效
            $order_info = $this->order_service->get_order_info(['order_id'=>$refund_info['order_id']]);
            $Orders = new \oms\Order($order_info);
            if(!$Orders->allow_to_refund()){
                $this->order_service->rollback();
                showmessage("不允许退款","null",0);
            }
            // 查询支付单信息
            $this->payment_service = $this->load->service('order2/payment');
            $payment_info = $this->payment_service->get_info( $refund_info['payment_id']);
            
            // 支付单支付成功状态
            if( $payment_info['payment_status'] != \zuji\order\PaymentStatus::PaymentSuccessful ){
                showmessage("支付状态错误","null",0);
            }
            if($order_info['payment_type_id'] == Config::FlowerStagePay){
                // 查询交易信息
                $this->payment_trade_service = $this->load->service('payment/payment_trade');
                $trade_info = $this->payment_trade_service->get_info_by_trade_no( $payment_info['trade_no'] );

                if( !$trade_info['out_trade_no'] ){
                    showmessage("数据错误，没有查询到相关交易记录","null",0);
                }
            }
            $this->load->librarys('View')->assign('refund_id',$refund_id)->assign('should_amount',$refund_info['should_amount'])->display('alert_tuikuan');
        }
    }


    //查询状态
    public function check_curl($order_info,$refund_no){
        //初始化appid和时间戳
        $curl_create['appid'] = $order_info['appid'];
        $curl_create['timestamp'] = date("Y-m-d H:i:s",time());
        $curl_create['sign_type'] = "MD5";
        $curl_create['sign'] = "unionrefund";
        $curl_create['version'] = "1.0";
        $curl_create['auth_token'] = "unionrefund";
        $curl_create['params'] = [
            'refund_no'=>$refund_no,
        ];
        $curl_create['method'] = "pay.order.refundstatus";
        $result = \zuji\Curl::post(config('Interior_Pay_Url'),json_encode($curl_create));
        $data  = json_decode($result,true);
        if(!is_array($data)){
            set_error("返回数据错误");
            return false;
        }
        return $data;
    }


    public function refund_should() {
        $this->order_service=$this->load->service('order2/order');
        $this->refund_service =$this->load->service('order2/refund');
        
        if (checksubmit('dosubmit')) {
            // 当前 操作员
            $admin = [
                'id' =>$this->admin['id'],
                'username' =>$this->admin['username'],
            ];
            $Operator = new oms\operator\Admin( $admin['id'], $admin['username'] );
            
            $trans = $this->order_service->startTrans();
            if(!$trans){
                showmessage("服务器繁忙 请稍候再试！","null",0);
            }

            $refund_id = intval(trim($_POST['refund_id']));
            $should_remark =$_POST['should_remark'];
            $should_amount =trim($_POST['should_amount']);

            $additional =[
                'lock' =>true,
            ];
            $refund_info =  $this->refund_service->get_info($refund_id,$additional);
            $order_info = $this->order_service->get_order_info(['order_id'=>$refund_info['order_id']],$additional);
            $Orders = new \oms\Order($order_info);

            if(!$Orders->allow_to_edit_refund()){
                $this->order_service->rollback();
                showmessage('该订单不允许修改退款金额！','null',0);
            }
            // 查询支付单信息
            $this->payment_service = $this->load->service('order2/payment');
            $payment_info = $this->payment_service->get_info( $refund_info['payment_id'],$additional);
            if( $payment_info['payment_status'] != \zuji\order\PaymentStatus::PaymentSuccessful ){
                $this->order_service->rollback();
                showmessage("支付状态错误","null",0);
            }

            if($order_info['payment_type_id'] == Config::FlowerStagePay){
                // 查询交易信息
                $this->payment_trade_service = $this->load->service('payment/payment_trade');
                $trade_info = $this->payment_trade_service->get_info_by_trade_no( $payment_info['trade_no'] );

                if( !$trade_info['out_trade_no'] ){
                    $this->order_service->rollback();
                    showmessage("数据错误，没有查询到相关交易记录","null",0);
                }

            }
            $payment_amount =$payment_info['payment_amount'];
            if($payment_amount<$should_amount){
                $this->order_service->rollback();
                showmessage("修改的应退金额不能大于支付的金额","null",0);
            }
            $data=[
                'should_remark' =>$should_remark,
                'should_amount'=>intval(floatval($should_amount)*100),
                'should_admin_id'=>intval($this->admin['id']),
                'refund_id'=>$refund_id,
            ];   
            try{
                // 订单 观察者主题
                $OrderObservable = $Orders->get_observable();
                // 订单 观察者  日志
                $LogObserver = new oms\observer\LogObserver( $OrderObservable , "修改退款金额","应退款金额：".$should_amount." 修改备注：".$should_remark);
                $LogObserver->set_operator($Operator);
            
                $b =$Orders->edit_refund($data);
                if(!$b){
                    $this->order_service->rollback();
                    Debug::error(Location::L_Refund,get_error(), $data);
                    showmessage('修改退款金额失败','null',0);
                }
                $this->order_service->commit();
                showmessage('操作成功','null',1);
            }catch (\Exception $exc){
                $this->order_service->rollback();
                Debug::error(Location::L_Order, '修改退款金额失败:'.$exc->getMessage(), $data);
                showmessage($exc->getMessage(),'null',0);
            }

        }else{
            
            $refund_id = intval(trim($_GET['refund_id']));
            if($refund_id < 1){
                showmessage("参数错误","null",0);
            }
            $refund_info =  $this->refund_service->get_info($refund_id);
            $order_info = $this->order_service->get_order_info(['order_id'=>$refund_info['order_id']]);    
            $Orders = new \oms\Order($order_info);
            if(!$Orders->allow_to_edit_refund()){
                showmessage('该订单不允许修改退款金额！','null',0);
            }
            // 查询支付单信息
            $this->payment_service = $this->load->service('order2/payment');
            $payment_info = $this->payment_service->get_info( $refund_info['payment_id'] );
            if( $payment_info['payment_status'] != \zuji\order\PaymentStatus::PaymentSuccessful ){
                showmessage("支付状态错误","null",0);
            }
            if($order_info['payment_type_id'] == Config::FlowerStagePay){
                // 查询交易信息
                $this->payment_trade_service = $this->load->service('payment/payment_trade');
                $trade_info = $this->payment_trade_service->get_info_by_trade_no( $payment_info['trade_no'] );

                if( !$trade_info['out_trade_no'] ){
                    $this->order_service->rollback();
                    showmessage("数据错误，没有查询到相关交易记录","null",0);
                }

            }
            $this->load->librarys('View')->assign('refund_id',$refund_id)->assign('payment_info',$payment_info)->assign('refund_info', $refund_info)->display('alert_refund_should');
        }
    } 
}