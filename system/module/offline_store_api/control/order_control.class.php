<?php
use zuji\order\Service;
use oms\Order;
use oms\state\State;
/**
 * 订单控制器
 * @access public 
 * @author limin <limin@huishoubao.com.cn>
 * @copyright (c) 2017, Huishoubao
 */
hd_core::load_class('user', 'offline_store_api');
class order_control extends user_control {
    
    public function _initialize() {
        parent::_initialize();
        $this->userId = $this->member['id'];
        $this->userId = $this->member['id'];
    }
    //记录状态流及记录操作日志
    public function log($orderObj,$title,$text=""){
        // 当前操作员
        $Operator = new oms\operator\Store( $this->member['id'], $this->member['username'] );
        // 订单 观察者主题
        $OrderObservable = $orderObj->get_observable();
        // 订单 观察者 状态流
        $FollowObserver = new oms\observer\FollowObserver( $OrderObservable );
        // 订单 观察者  日志
        $LogObserver = new oms\observer\LogObserver( $OrderObservable , $title, $text);
        //插入日志
        $LogObserver->set_operator($Operator);
    }

    //门店信息
    public function shop_info(){
        $this->channel = $this->load->service("channel/channel_appid");
        return $this->channel ->get_info($this->appid);
    }

    //订单列表
    public function query(){
        $params   = $this->params;
        //判断business_key当为10时为线下门店
        $where['business_key'] = zuji\Business::BUSINESS_STORE;

        $data = array(
            'order_list'    =>  '',
            'count'          =>  ''
        );

        /*****************依赖服务************/
        $this->order   = $this->load->service('order2/order');
        $this->delivery  = $this->load->service('order2/delivery');
        $this->service_serve  = $this->load->service('order2/service');
        $this->spu_serve    = $this->load->service('goods2/goods_spu');
        $request = api_request();
        $appid = (int)$request->getAppid();
        $where['appid'] = $appid ;
        //获取订单数
        $count = $this->order->get_order_count($where);
        if(!$count){
            api_resopnse( $data, ApiStatus::CODE_0 );
        }
        //选择性分页
        if($params['page']>0){
            $options['size']   = 10;
            $options['page'] = intval($params['page']);
        }
        else
        {
            $options['size']   = $count;
            $options['page']  = 1;
        }
        $data['count'] = $count;
        $options['goods_info'] = true;
        $total_page = ceil($data['count']/$options['size']);
        $data['total_page'] = $total_page;
        //获取订单数据列表
        $order_list = $this->order ->get_order_list($where,$options);
        $order_list = $this->arrayKey($order_list,"order_id");
        if(!$order_list){
            api_resopnse( [], ApiStatus::CODE_50003 ,'订单获取失败');
        }
        $order_ids = array_column($order_list,'order_id');
        asort($order_ids);
        $additional['size'] = count($order_ids);
        $additional['page']  = 1;
        //组装数据格式
        $order_new = array();
        //判断订单状态是否开启面签按钮
        $face_visa_btn = false;
        if( $order_list['status'] == State::StorePassed && $order_list['status'] == State::StorePassed ){
            $face_visa_btn = true;
        }
        foreach($order_list as $key=>$val){
            $Order = new Order($val);
            //获取前端订单状态
            $status_name =  $Order->get_client_name();
            $status_key =  $Order->get_status();
            if($status_key == oms\state\State::FundsAuthorized){
                $status_key = oms\state\State::PaymentSuccess;
            }
//            print_r($val);
//            print_r($status_name);
//            print_r($status_key);die;
            $order_new[$key] = array(
                'create_time'  =>  date("Y-m-d H:i:s",$val['create_time']),
                'face_visa_btn'  => $face_visa_btn,
                'status_name' => $status_name,
                'status_key' => $status_key,
                'order_id'      => $val['order_id'],
                'order_no'      => $val['order_no'],
                'amount'        => $val['amount'],
                'mobile'        => $val['mobile'],
                'yajin'          => $val['yajin'],
                'mianyajin'     => $val['mianyajin'],
                'zujin'          => $val['zujin'],
                'yiwaixian'      => $val['yiwaixian'],
                'zuqi'           => $val['zuqi'],
//                'return_status'   =>$val['return_status'],
                'user_array'       => array(
                    'username'         =>  $val['realname'],
                    'mobile'   =>  $val['mobile'],
                    'card'         =>  $val['cert_no'],
                ),
                'sku_info'       => array(
                    'sku_id'         =>  $val['goods_info']['sku_id'],
                    'sku_name'   =>  $val['goods_info']['sku_name'],
                )
            );
        }
        array_multisort($order_new,SORT_DESC);
        $data['order_list'] = $order_new;
        api_resopnse( $data, ApiStatus::CODE_0 );
        return;
    }
    //订单详情
    public function get(){
        $params   = $this->params;
        $params = filter_array($params,[
            'order_no'=>'required',
        ]);

        if(!isset($params['order_no']) && $params['order_no']<1){
            api_resopnse( [], ApiStatus::CODE_20001,'', ApiSubCode::Order_Error_Order_no,'');
            return;
        }
        $where['order_no'] = $params['order_no'];
        $additional['goods_info'] = true;
        $additional['address_info'] = true;
        //获取订单详情
        $data = $this->order_detail($where,$additional);
        if(!$data){
            api_resopnse( [], ApiStatus::CODE_50003 ,'订单获取失败');
            return;
        }
        api_resopnse( $data,ApiStatus::CODE_0 );
        return;
    }
    //取消订单
    public function cancel(){
        $params = $this->params;
        //验证参数
        $params = filter_array($params,[
            'order_no' => 'required',
        ]);
        if (!$params['order_no']) {
            api_resopnse( [], ApiStatus::CODE_20001,'订单编号必须',ApiSubCode::Order_Error_Order_no,'');
            return;
        }
        /*****************依赖服务************/
        $this->order   = $this->load->service('order2/order');
        //开启事务
        $this->order->startTrans();

        //获取订单信息
        $where['order_no'] = $params['order_no'];
        $order_info = $this->order->get_order_info($where,['lock'=>true]);

        if (!$order_info) {
            api_resopnse( [], ApiStatus::CODE_50003,'该订单不存在');
            return;
        }

        if ($order_info['payment_status'] == zuji\order\PaymentStatus::PaymentSuccessful) {
            $this->order->rollback();
            api_resopnse( [], ApiStatus::CODE_50003,'该订单已支付,请联系客服取消订单');
            return;
        }
        //取消订单操作
        $orderObj = new oms\Order($order_info);
        //验证订单取消权限
        if(!$orderObj->allow_to_cancel_order()){
            $this->order->rollback();
            api_resopnse( [], ApiStatus::CODE_50003,'不支持取消订单');
            return;
        }
        //记录状态流并插入日志
        $this->log($orderObj,"取消订单");
        //取消订单操作
        $ret = $orderObj->cancel_order($order_info);
        if(!$ret){
            //事务回滚
            $this->order->rollback();
            api_resopnse( [], ApiStatus::CODE_50000,'订单取消失败');
            return;
        }

        //提交事务
        $this->order->commit();
        api_resopnse( [], ApiStatus::CODE_0);
    }
    //订单确认
    public function confirm(){
        $params = $this->params;
        //验证参数
        $params = filter_array($params,[
            'order_no' => 'required',
        ]);
        if(!$params['order_no']){
            api_resopnse( [], ApiStatus::CODE_20001,'',ApiSubCode::Order_Error_Order_no,'');
            return;
        }
        /*****************依赖服务************/
        $this->order = $this->load->service('order2/order');
        //开启事务
        $this->order->startTrans();

        $where['order_no'] = $params['order_no'];
        $order_info = $this->order->get_order_info($where,['lock'=>true]);

        if(!$order_info){
            api_resopnse( [], ApiStatus::CODE_50003,'该订单不存在');
            return;
        }

        if($order_info['state'] == oms\state\State::StoreConfirmed){
            $this->order->rollback();
            api_resopnse( [], ApiStatus::CODE_50003,'该订单已确认');
            return;
        }

        $orderObj = new oms\Order($order_info);
        //验证订单确认权限
        if(!$orderObj->allow_to_store_confirm_order()){
            $this->order->rollback();
            api_resopnse( [], ApiStatus::CODE_50003,'不支持确认');
            return;
        }
        //记录状态流并插入日志
        $this->log($orderObj,"订单确认成功");
        //确认操作
        $ret = $orderObj->store_confirm_order($order_info);
        if(!$ret){
            //事务回滚
            $this->order->rollback();
            api_resopnse( [], ApiStatus::CODE_50000,'订单确认失败');
            return;
        }
        //提交事务
        $this->order->commit();
        api_resopnse( [], ApiStatus::CODE_0);
    }
    //订单协议提交
    public function submit(){
        $params = $this->params;
        //过滤参数
        $params = filter_array($params,[
            'order_no' => 'required',
            'imei' => 'required',
            'serial_number' => 'required',
            'card_hand' => 'required',
            'card_positive' => 'required',
            'card_negative' => 'required',
            'goods_delivery' => 'required',
        ]);
        //验证参数
        if(!$params['order_no']){
            api_resopnse( [], ApiStatus::CODE_20001,'',ApiSubCode::Order_Error_Order_no,'');
            return;
        }
        if(!$params['imei']){
            api_resopnse( [], ApiStatus::CODE_20001,'',ApiSubCode::Order_Error_Imei,'');
            return;
        }
        $shop = $this->shop_info();
        if($shop['appid']['is_upload_idcard']){
            if(!$params['card_hand']){
                api_resopnse( [], ApiStatus::CODE_20001,'',ApiSubCode::Order_Error_Card_hand,'');
                return;
            }
            if(!$params['card_positive']){
                api_resopnse( [], ApiStatus::CODE_20001,'',ApiSubCode::Order_Error_Card_positive,'');
                return;
            }
            if(!$params['card_negative']){
                api_resopnse( [], ApiStatus::CODE_20001,'',ApiSubCode::Order_Error_Card_negative,'');
                return;
            }
        }
        if(!$params['goods_delivery']){
            api_resopnse( [], ApiStatus::CODE_20001,'',ApiSubCode::Order_Error_Goods_delivery,'');
            return;
        }

        /*****************依赖服务************/
        $this->order = $this->load->service('order2/order');
        //开启事务
        $this->order->startTrans();

        $where['order_no'] = $params['order_no'];
        $order_info = $this->order->get_order_info($where,['lock'=>true]);

        if(!$order_info){
            api_resopnse( [], ApiStatus::CODE_50003,'该订单不存在');
            return;
        }

        //提交操作
        $orderObj = new oms\Order($order_info);
        //验证订单取消权限
        if(!$orderObj->allow_to_inservice()){
            $this->order->rollback();
            api_resopnse( [], ApiStatus::CODE_50003,'不支持协议提交操作');
            return;
        }

        $data['order_id']       = $order_info['order_id'];
        $data['imei1']          = $params['imei'];
        $data['serial_number']  = $params['serial_number'];
        $data['card_hand']      = $params['card_hand'];
        $data['card_positive']  = $params['card_positive'];
        $data['card_negative']  = $params['card_negative'];
        $data['goods_delivery'] = $params['goods_delivery'];

        //记录状态流并插入日志
        $this->log($orderObj,"提交协议成功");
        //提交协议
        $ret = $orderObj->inservice($data);
        if(!$ret){
            //事务回滚
            $this->order->rollback();
            api_resopnse( [], ApiStatus::CODE_50000,'提交协议失败');
            return;
        }

        $app_id = api_request()->getAppid();
        $appid_info = $this->load->table('channel/channel_appid')->get_info($app_id);
        if(empty($appid_info)){
            \zuji\debug\Debug::error(\zuji\debug\Location::L_Order,'线下提交协议获取appid信息失败',['appid:' => $app_id]);
        }

        $sms = new \zuji\sms\HsbSms();
        $b = $sms->send_sm($order_info['mobile'],'hsb_sms_bf0d8',[
            'storeName' => $appid_info['name'],    //店铺名称
            'orderNo' => $order_info['order_no'],    // 订单编号
            'serviceTel' =>  zuji\Config::Customer_Service_Phone //客服电话
        ],$order_info['order_no']);
        if(!$b){
            \zuji\debug\Debug::error(\zuji\debug\Location::L_Order,'线下提交协议短信',$b);
        }
        //提交事务
        $this->order->commit();
        api_resopnse( [], ApiStatus::CODE_0);
    }
    //重构数组键名
    function arrayKey($infos,$key){
        $retArr = array();
        if( $infos && count($infos) > 0 )
        {
            foreach( $infos as $info )
            {
                $retArr[ $info[ $key ] ] = $info;
            }
        }
        return $retArr;
    }
    //订单详情
    public function order_detail($where,$additional){
        /*****************依赖服务************/
        $this->channel = $this->load->service('channel/channel_appid');
        $this->order   = $this->load->service('order2/order');
        $this->delivery  = $this->load->service('order2/delivery');
        $this->service_serve  = $this->load->service('order2/service');
        $this->spu_serve     = $this->load->service('goods2/goods_spu');
        $this->image = $this->load->service('order2/order_image');


        //获取订单
        $order = $this->order->get_order_info($where,$additional);
        $Order = new Order($order);
        //判断订单是否
        if(!$order){
            return false;
        }
        //获取支付时间
        $payment_time = "";
        if($order['payment_status'] == zuji\order\PaymentStatus::PaymentSuccessful){
            $this->payment_serve = $this->load->service('order2/payment');
            $payment_info = $this->payment_serve->get_info_by_order_id($order['order_id']);
            if($payment_info){
                $payment_time = date("Y-m-d H:i:s",$payment_info['payment_time']);
            }
        }
        //获取前端订单状态
        $status_name =  $Order->get_client_name();
        $status_key =  $Order->get_status();
        $payment_status = false;
        if($status_key == State::PaymentSuccess){
            $payment_status = true;
        }
        if($status_key == oms\state\State::FundsAuthorized){
            $status_key = oms\state\State::PaymentSuccess;
        }
        $cancel =  $Order->allow_to_cancel_order();
//        //获取订单服务信息
//        $service =  $this->service_serve->get_info_by_order_id($order['order_id']);
//        //计算是否到期
//        $days = "";
//        $begin_time = "";
//        $end_time = "";
//        if(!empty($service)){
//            $begin_time = date("Y-m-d H:i:s",$service['begin_time']);
//            $end_time  = date("Y-m-d H:i:s",$service['end_time']);
//
//            $Service = Service::createService( $service );
//            $days = $Service->get_remaining_days();
//            if( $days<=7 && $days>0 ){
//                $status_name = "即将到期";
//                $status_key = "expire";
//            }elseif($days<=0){
//                $status_name = "已到期";
//                $status_key = "expired";
//            }
//        }
        $data = [];
        //订单基本信息
        $data['order_no'] = $order['order_no'];
        $data['create_time'] = date("Y-m-d H:i:s",$order['create_time']);
        $data['amount'] = $order['amount'];
        $data['yajin'] = $order['yajin'];
        $data['mianyajin'] = $order['mianyajin'];
        $data['zujin'] = $order['zujin'];
        $data['zuqi'] = $order['zuqi'];
        $data['yiwaixian'] = $order['yiwaixian'];
        $data['order_no'] = $order['order_no'];
        $data['order_id'] = $order['order_id'];
        $data['payment_time'] = $payment_time;
        $data['payment_status'] = $payment_status;
//        $data['begin_time'] = $begin_time;
//        $data['end_time'] = $end_time;
//        $data['day'] =$days;
//        $data['status_id'] = $status_id;
        $data['status_name'] = $status_name;
        $data['status_key'] = $status_key;
        $data['order_cancel'] = $cancel;
        //商品信息
        $sku_goods = $this->spu_serve->api_get_info($order['goods_info']['spu_id'],'thumb');
        $thumb = $sku_goods['thumb'];
        $specs_list = $order['goods_info']['specs'];
        $specs_value  = array_column($specs_list,"value");
        //获取用户信息
        $data['user_array']   =   array(
            'username'=>$order['realname'],
            'mobile'=>$order['mobile'],
            'card'=>$order['cert_no'],
        );
        //获取商品信息
        $data['sku_info']   =   array(
            'sku_id'=>$order['goods_info']['sku_id'],
            'sku_name'=>$order['goods_info']['sku_name'],
            'thumb'=>$thumb,
            'specs'=>implode("\n",$specs_value)

        );
        //查询门店信息
        $request = api_request();
        $appid = (int)$request->getAppid();
        $appid_info = $this->channel->get_info($appid);
        if($appid_info){
            $data['shop_array'] = [
                'shop_name' => $appid_info['appid']['name'],
                'shop_mobile' => $appid_info['appid']['mobile'],
            ];
        }
        //判断是否存在订单审批信息（判断状态是否为租用中）
//        if($order['status'] != State::OrderInService){
            //获取图片列表
            $price = $this->image->get_one($order['order_id']);
        if(!empty($price)) {
            if (!empty($price['card_hand'])) {
                $_tmp['card_hand'] = $price['card_hand'];
            }
            if (!empty($price['card_positive'])) {
                $_tmp['card_positive'] = $price['card_positive'];
            }
            if (!empty($price['card_negative'])) {
                $_tmp['card_negative'] = $price['card_negative'];
            }
            if (!empty($price['goods_delivery'])) {
                $_tmp['goods_delivery'] = $price['goods_delivery'];
            }
            $data['order_examine_array'] = [
                'order_imei' => $order['goods_info']['imei1'],
                'order_examine_picture' => $_tmp,
            ];
        }
//        }
        return $data;
    }

}
