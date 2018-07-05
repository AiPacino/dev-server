<?php

hd_core::load_class('api', 'api');
use \zuji\OrderLocker;
/**
 * 订单控制器
 * @access public 
 * @author limin <limin@huishoubao.com.cn>
 * @copyright (c) 2017, Huishoubao
 */
class installment_control extends api_control {
    
    public function _initialize() {
        parent::_initialize();
    }
    //订单分期期数列表查询
    public function query(){
        $params   = $this->params;
        $data = filter_array($params, [
            'order_no' => 'required',	//【必须】string:订单号
        ]);
        if(empty($data['order_no'])){
            api_resopnse( ['sub_msg'=>'订单号必须'], ApiStatus::CODE_20001,'参数必须', ApiSubCode::Order_Error_Order_no,'订单号必须');
            return;
        }
        /**************依赖服务************/
        $this->order = $this->load->table('order2/order2');
        $this->service = $this->load->service("order2/instalment");
        $where = [
            'order_no'=>$data['order_no']
        ];
        $order_info = $this->order->get_info($where);
        if(!$order_info){
            api_resopnse( [], ApiStatus::CODE_50003,"订单不存在");
            return;
        }
        if($order_info['user_id']!=$this->member['id']){
            api_resopnse( [], ApiStatus::CODE_50001,"用户错误");
            return;
        }
        $result = $this->service->get_order_instalment($order_info['order_id']);
        $total_amount =0;
        $pay_amount =0;
        foreach($result as $key=>$item){
            $total_amount+=$item['amount'];
            $result[$key]['allow_pay'] =0;
            if($item['term']<=date('Ym') && ($item['status']==\zuji\payment\Instalment::UNPAID || $item['status']==\zuji\payment\Instalment::FAIL)){
                $result[$key]['allow_pay'] =1;
            }
            if($item['status']==\zuji\payment\Instalment::SUCCESS){
                $pay_amount+=$pay_amount;
            }
            $result[$key]['yiwaixian'] =0;
            $result[$key]['yiwaixian_amount'] =0;
            $result[$key]['fenqi_amount'] =\zuji\order\Order::priceFormat($result[$key]['amount'] /100);
            if($item['times']==1){
                $result[$key]['yiwaixian'] =1;
                $result[$key]['yiwaixian_amount'] =\zuji\order\Order::priceFormat($order_info['yiwaixian'] /100);
                $result[$key]['fenqi_amount'] =\zuji\order\Order::priceFormat(($result[$key]['amount'] -$order_info['yiwaixian'])/100);
            }
            $result[$key]['month'] =substr($item['item'], -2);
            $result[$key]['create_date'] =$item['item']."01";
            $result[$key]['instalment_id'] =$item['id'];


            $result[$key]['payment_time'] = $item['payment_time']?date("Y-m-d H:i:s",$item['payment_time']):"";
            $result[$key]['status'] = $item['status']==zuji\payment\Instalment::SUCCESS?"是":"否";
            $result[$key]['discount_amount'] /=100;
            $result[$key]['amount'] /=100;

            $result[$key]['realname']="*".mb_substr($order_info['realname'], 1, mb_strlen ( $order_info['realname'] )-1, 'utf-8');
            $result[$key]['mobile']=substr($order_info['mobile'], -4);

            //当前期数
            $result[$key]['times'] = $item['times'];
            //重写变量 获取最大的期数
            $total_times =$item['times'];
        }
        api_resopnse( $result, ApiStatus::CODE_0);
        return;
    }

    //提前还款请求
    public function prepayment(){
        if(config("Test_Mobile_On")==true){
            if($this->member['mobile']==config("Test_Mobile")){
                api_resopnse([], ApiStatus::CODE_20001,ApiSubCode::Order_Error_Order_no,'测试用户禁止支付');
                return ;
            }
        }
        $result = ['payment_url'=>'','payment_form'=>''];

        // 获取参数
        $params = filter_array($this->params, [
            'order_no' => 'required',
            'return_url'=>'required',
            'instalment_id'=>'required',
        ]);
        if (empty($params['order_no'])) {
            api_resopnse( ['sub_msg'=>'订单编号不能为空'], ApiStatus::CODE_20001,'订单编号不能为空',ApiSubCode::Order_Error_Order_no,'订单编号不能为空');
            return;
        }
        if (empty($params['return_url'])) {
            api_resopnse( ['sub_msg'=>'前端回跳地址不能为空'], ApiStatus::CODE_50004,'前端回跳地址不能为空',ApiSubCode::Order_Instalment_Error_prepayment,'前端回跳地址不能为空');
            return;
        }
        if (empty($params['instalment_id'])) {
            api_resopnse( ['sub_msg'=>'分期ID不能为空'], ApiStatus::CODE_50004,'分期ID不能为空',ApiSubCode::Order_Instalment_Error_prepayment,'分期ID不能为空');
            return;
        }
        //判断该订单是否有锁
        if(OrderLocker::isLocked($params['order_no'])){
            $lock_name =OrderLocker::getLockName(OrderLocker::getLock($params['order_no']));
            api_resopnse( ['sub_msg'=>'该订单有锁'], ApiStatus::CODE_50002,'支付失败', ApiSubCode::Trade_Url_Error,'支付[该订单有锁]失败:'.$lock_name);
            return ;
        }


        /*****************依赖服务************/
        $this->order = $this->load->service('order2/order');
        //开启事务
        $this->order->startTrans();
        $where['order_no'] = $params['order_no'];
        //获取订单信息
        $order_info = $this->order->get_order_info($where,['lock'=>true]);
        if ($order_info ===false) {
            //事务回滚
            $this->order->rollback();
            api_resopnse( ['sub_msg'=>'订单不存在'], ApiStatus::CODE_50003,'订单不存在','','订单不存在');
            return;
        }
        //判断该订单是否为该用户
        if($order_info['user_id']!=$this->member['id']){
            api_resopnse( ['sub_msg'=>'用户错误'], ApiStatus::CODE_50001,"用户错误",'','用户错误');
            return;
        }

        if( $order_info['status']!=\oms\state\State::OrderInService ){
            api_resopnse( ['sub_msg'=>'该订单不在服务中 不允许提前还款'], ApiStatus::CODE_50003,'订单错误', ApiSubCode::Order_Error_Status,'该订单不在服务中 不允许提前还款');
            return ;
        }
        //查询未扣款分期
        $this->instalment = $this->load->service('order2/instalment');
        $instalment_info =$this->instalment->get_info(['id'=>$params['instalment_id']],['lock'=>true]);

        if( $instalment_info['status']!=\zuji\payment\Instalment::UNPAID && $instalment_info['status']!=\zuji\payment\Instalment::FAIL){
            api_resopnse( ['sub_msg'=>'该分期不允许提前还款'], ApiStatus::CODE_40003,'该分期不允许提前还款',ApiSubCode::Order_Instalment_Error_prepayment,'该分期不允许提前还款');
            return;
        }

        //生成自助还款单
        $now_time =time();
        $trade_no =\zuji\Business::create_business_no();
        $this->prepayment_table = $this->load->table('payment/instalment_prepayment');
        $prepayment_data =[
            'order_id'=>$order_info['order_id'],
            'order_no'=>$order_info['order_no'],
            'trade_no'=>$trade_no,
            'instalment_id'=>$params['instalment_id'],
            'user_id'=>$order_info['user_id'],
            'mobile'=>$order_info['mobile'],
            'term'=>$instalment_info['term'],
            'create_time'=>$now_time,
            'update_time'=>$now_time,
            'payment_amount'=>$instalment_info['amount'],
        ];
        $b = $this->prepayment_table->add($prepayment_data);
        if(!$b){
            //事务回滚
            $this->order->rollback();
            api_resopnse( ['sub_msg'=>'生成自助还款单失败'], ApiStatus::CODE_40003,'生成自助还款单失败',ApiSubCode::Order_Instalment_Error_prepayment,'生成自助还款单失败');
            return;
        }


        //请求支付接口
        $data = [
            'fenqi_zuqi' => 0,// 分期数  提前还款 参数为0
            'fenqi_seller_percent' => 100,	// 商户承担分期收费比例，固定值 100
            'trade_no' => $trade_no,
            'amount' => $instalment_info['amount']/100,	// 支付价格 单位元
            'subject' => $order_info['goods_name'],
            'body' => '',// 产品描述，可选
            'return_url' => $params['return_url'],	// 前端回跳地址
            'notify_url'=>config('ALIPAY_Prepayment_Notify_Url'),// 后端支付成功回跳地址
        ];
        try {
            // 支付宝应用ID标识
            $appid = config('ALIPAY_APP_ID');
            $appid = $appid ? $appid : \zuji\Config::Alipay_App_Id;
            $WapPay = new \alipay\WapPay( $appid );
            $payment_form = $WapPay->wapPay($data,true,true);
            $payment_url = $WapPay->wapPay($data,true,false);
            if( !$payment_url ){
                $this->order->rollback();
                \zuji\debug\Debug::error( \zuji\debug\Location::L_Trade, '支付[创建支付url]失败',$data );
                api_resopnse( ['sub_msg'=>'创建支付url失败'], ApiStatus::CODE_50002,'支付失败'.get_error(), ApiSubCode::Trade_Url_Error,'创建支付url失败');
                return ;
            }

            //提前还款 发送短信
            $sms = new \zuji\sms\HsbSms();
            $b = $sms->send_sm($order_info['mobile'],'hsb_sms_a3b24b',[
                'realName' => $order_info['realname'],    // 传递参数
            ],$order_info['order_no']);
            if (!$b) {
                Debug::error(Location::L_Order,'线上下单短信',$b);
            }


            //提交事务
            $this->order->commit();

            // 接口访问
            if( IS_API ){
                api_resopnse( ['sub_msg'=>'成功','payment_url'=>$payment_url,'payment_form'=>$payment_form], ApiStatus::CODE_0,'');
                return ;
            }
            // url直接访问，返回form表单
            echo $payment_form;exit;
        } catch (\Exception $exc) {
            \zuji\debug\Debug::error(\zuji\debug\Location::L_Payment, '支付初始化失败', $exc->getMessage());
            api_resopnse( ['sub_msg'=>'支付初始化异常'], ApiStatus::CODE_50004,'支付错误',  ApiSubCode::Trade_Url_Error,'支付初始化异常');
            return ;
        }

    }
    //提前还款成功回调
    public function prepayment_notify(){
      //  \zuji\debug\Debug::error(\zuji\debug\Location::L_Trade,'[提前还款异步通知]数据',$_POST);
        $this->order_service   = $this->load->service('order2/order');
        $this->payment_service=$this->load->service('order2/payment');
        $this->instalment = $this->load->service('order2/instalment');
        $this->prepayment_service = $this->load->service('payment/prepayment');

//        $_POST =[
//            "gmt_create"=>"2018-03-22 14:08:45",
//          "charset"=>"UTF-8",
//          "seller_email"=>"shentiyang@huishoubao.com.cn",
//          "subject"=>"iPhone8 Plus",
//          "sign"=>"OJKIs4CU9p+YRSWRIINRwjaXBAhBEqt/Eh20HIJqLtXodKFs0br1RoFiRnvVORRIzV6f7Ts3a5zPAuJ25dLk7C1uceeiNULpDfm8CwcUMCzhmlhOrNpOKnNIOqFym/CvGCh+O8A1Gfv5CZdrUtTceOvuBUTdWfgmce/S1AmYsMqTNSNa1WMC8OzQWIc5L1Q42d5AuLToumoe9G10TQZiWHFvlvAamRnEKzrLcg5PnC+7PYhp4uUcdnx/MtllnE8vzzZLS4iNCCIpeV24lk2+6HChNqnaG+mPzby+rPX1uFsAYg/s23MHLKWe13NylbTMUMrJlz7likVVSPiq+uYLsw==",
//          "buyer_id"=>"2088702999441562",
//          "invoice_amount"=>"0.01",
//          "notify_id"=>"a207784ca61446ea03853f88850f6fckbp",
//          "fund_bill_list"=>"[{\"amount\":\"0.01\",\"fundChannel\":\"ALIPAYACCOUNT\"}]",
//          "notify_type"=>"trade_status_sync",
//          "trade_status"=>"TRADE_SUCCESS",
//          "receipt_amount"=>"0.01",
//          "buyer_pay_amount"=>"0.01",
//          "app_id"=>"2017101309291418",
//          "sign_type"=>"RSA2",
//          "seller_id"=>"2088821442906884",
//          "gmt_payment"=>"2018-03-22 14:08:46",
//          "notify_time"=>"2018-03-22 14:08:46",
//          "version"=>"1.0",
//          "out_trade_no"=>"Dev20180322000223",
//          "total_amount"=>"0.01",
//          "trade_no"=>"2018032221001004560547833511",
//          "auth_app_id"=>"2017101309291418",
//          "buyer_logon_id"=>"136****5804",
//          "point_amount"=>"0.00"
//        ];

        // 支付宝应用ID标识
        $appid = config('ALIPAY_APP_ID');
        $appid = $appid ? $appid : \zuji\Config::Alipay_App_Id;
        $WapPay = new \alipay\WapPay( $appid );

        $b =$WapPay->verify($_POST);
        if(!$b){
            \zuji\debug\Debug::error(\zuji\debug\Location::L_Payment,'[提前还款异步通知]签名验证失败', $_POST);
            echo '签名验证失败';
            exit;
        }

        $time =time();
        $_POST['trade_channel'] = $_GET['trade_channel'];
        // 商家在交易中实际收到的款项，单位为元
        set_default_value($_POST['receipt_amount'], 0);
        // 退款金额
        set_default_value($_POST['refund_fee'], 0);
        // 该笔交易结束时间
        set_default_value($_POST['gmt_close'], 0);

        //接收参数 过滤
        // * 注意：
        // * 支付宝返回的 out_trade_no 是租机交易号，trade_no是支付宝交易流水号
        // * 在我们的数据库中，把这两个值翻转了一下： trade_no：租机交易号；out_trade_no：第三方交易号
        $_no = $_POST['out_trade_no'];
        $_POST['out_trade_no'] = $_POST['trade_no'];
        $_POST['trade_no'] = $_no;
        $_POST['trade_channel'] ="ALIPAY";

        // 字段替换
        $_POST = replace_field($_POST, [
            'buyer_logon_id' => 'buyer_email',
        ]);
        //状态值转换
        $trade_status = 0;
        if( $_POST['trade_status'] == 'WAIT_BUYER_PAY' ){
            $trade_status = 2;
        }elseif( $_POST['trade_status'] == 'TRADE_PENDING' ){
            $trade_status = 3;
        }elseif( $_POST['trade_status'] == 'TRADE_SUCCESS' ){
            $trade_status = 4;
        }elseif( $_POST['trade_status'] == 'TRADE_CLOSED' ){
            $trade_status = 5;
        }elseif( $_POST['trade_status'] == 'TRADE_FINISHED' ){
            $trade_status = 6;
        }

        //-+--------------------------------------------------------------------
        // | 保存 支付异步通知
        //-+--------------------------------------------------------------------
        // 异步通知记录表
        $payment_trade_notify_table = $this->load->table('payment/payment_trade_notify');
        $_POST['create_time'] =$time;
        // 执行sql
        $trade_notify_id = $payment_trade_notify_table->create( $_POST );
        if( !$trade_notify_id ){
            $this->order_service->rollback();
            \zuji\debug\Debug::error(\zuji\debug\Location::L_Payment,'[提前还款异步通知]插入交易记录失败', $_POST);
            echo '插入交易记录失败';
            exit;
        }

        // 开启事务
        $n = $this->order_service->startTrans();
        if( !$n ){
            $this->order_service->rollback();
            \zuji\debug\Debug::error(\zuji\debug\Location::L_Payment,'[提前还款支付异步通知]事务开启失败', $_POST);
            echo 'db_transaction_error ';exit;
        }


        $prepayment_info =$this->prepayment_service->get_info(['trade_no'=>$_POST['trade_no']],['lock'=>true]);
        if(!$prepayment_info){
            $this->order_service->rollback();
            \zuji\debug\Debug::error(\zuji\debug\Location::L_Payment,'[提前还款异步通知]未找到提前还款支付交易记录', $_POST);
            echo '未找到提前还款支付交易记录';
            exit;
        }


        $instalment_info =$this->instalment->get_info(['id'=>$prepayment_info['instalment_id']],['lock'=>true]);
        if(!$instalment_info){
            $this->order_service->rollback();
            \zuji\debug\Debug::error(\zuji\debug\Location::L_Payment,'[提前还款异步通知]未找到分期支付交易记录', $_POST);
            echo '未找到分期支付交易记录';
            exit;
        }
        $order_info = $this->order_service->get_order_info(['order_id'=>$instalment_info['order_id']],['lock'=>true]);

        try {

            // 只同步 交易成功
            if( $trade_status==4 ){
                //更新分期状态
                $instalment_data =[
                    'id'=>$instalment_info['id'],
                    'trade_no'=>$_POST['trade_no'],
                    'payment_time'=>$time,
                    'status'=>\zuji\payment\Instalment::SUCCESS,
                    'update_time'=>$time,
                ];
                if($instalment_info['out_trade_no']==""){
                    $instalment_data['out_trade_no'] =$_POST['out_trade_no'];
                }
                $instalment_table = $this->load->table('order2/order2_instalment');
                $b =$instalment_table->save($instalment_data);
                if(!$b){
                    $this->order_service->rollback();
                    \zuji\debug\Debug::error(\zuji\debug\Location::L_Payment,'[提前还款异步通知]更新分期表失败', $instalment_data);
                    echo '更新分期表失败';
                    exit;
                }
                //更新提前还款单状态 和信息
                $prepayment_data =[
                    'update_time'=>$time,
                    'prepayment_time'=>$time,
                    'out_trade_no'=>$_POST['out_trade_no'],
                    'prepayment_status'=>1,
                    'payment_account'=>$_POST['buyer_email'],
                ];
                $prepayment_table = $this->load->table('payment/instalment_prepayment');
                $b =$prepayment_table->where(['trade_no'=>$_POST['trade_no']])->save($prepayment_data);
                if(!$b){
                    $this->order_service->rollback();
                    \zuji\debug\Debug::error(\zuji\debug\Location::L_Payment,'[提前还款异步通知]更新还款单状态失败', $prepayment_data);
                    echo '更新分期表失败';
                    exit;
                }

            }
        } catch (\Exception $exc) {
            $this->order_service->rollback();
            zuji\debug\Debug::error(\zuji\debug\Location::L_Trade, '[支付异步通知]支付异常', $_POST);
            var_dump($exc->getMessage());
            exit;
        }
        //业务处理完 解锁
        $b =OrderLocker::unlock($order_info['order_no']);
        //
        $this->order_service->commit();
        echo 'success';
        exit;

    }

}
