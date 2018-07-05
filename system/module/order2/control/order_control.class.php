<?php
/**
 * 后台订单控制器
 * 1）订单列表（支持搜索功能）（点击不同状态tab标签，进入对应的 xx单 列表）
 * 2）订单详情（输出订单基本信息：用户，商品，收货地址，时间，租期，金额相关），其他数据，做ajax异步加载
 * 3）订单取消功能
 */

use zuji\order\OrderStatus;
use zuji\Business;
use zuji\order\Order;
use zuji\order\PaymentStatus;
use zuji\order\DeliveryStatus;
use zuji\order\ReturnStatus;
use zuji\order\ReceiveStatus;
use zuji\order\EvaluationStatus;
use zuji\order\RefundStatus;
use zuji\debug\Location;
use zuji\email\EmailConfig;
use zuji\debug\Debug;
use oms\state\State;

/* *
 */
// 加载 goods 模块中的 init_control
hd_core::load_class('base', 'order2');
class order_control extends base_control {

    /**
     * @var array 关键字搜索类型列表
     */
    protected $keywords_type_list = [
        'order_no'=>'订单编号',
        'order_id'=>'订单ID',
        'user_id'=>'用户ID',
        'mobile'=>'手机号',
    ];

    public function _initialize() {
        parent::_initialize();
        // $this->spu_service = $this->load->service('goods/goods_spu');
        // /* 服务层 */
        // $this->order_service = $this->load->service('order2/order');
        // $this->member_service = $this->load->service('member2/member');
        // $this->payment_service = $this->load->service('order2/payment');
        // $this->service_order_log = $this->load->service('order/order_log');
        // $this->return_service = $this->load->service('order2/return');
        // $this->evaluation_service = $this->load->service('order2/evaluation');
        // $this->order2_table = $this->load->table('order2/order2');
        // $this->order2_follow_table = $this->load->table('order2/order2_follow');
        //    $this->coupon_service = $this->load->service('order2/coupon');

        // $this->order_image_service = $this->load->service('order2/order_image');
        //    $this->channel_service = $this->load->service('channel/channel');
    }



    /**
     * 订单列表
     *      查询订单 显示订单号,会员账号,收货人,收货电话,下单时间,商品名,订单金额,状态,操作(查看)
     *      搜索 订单号or收货人姓名or收货人手机号or会员账号
     */
    public function index() {
        $this->order_service = $this->load->service('order2/order');
        $this->member_service = $this->load->service('member2/member');
        // 查询条件
        $where = [];
        if($_GET['begin_time']!='' ){
            $where['begin_time'] = strtotime($_GET['begin_time']);
        }
        if( $_GET['end_time']!='' ){
            $where['end_time'] = strtotime($_GET['end_time']);
        }
        if($_GET['business_key']>'0' ){
            $where['business_key'] = intval($_GET['business_key']);
        }
        if($_GET['appid']>'0' ){
            $where['appid'] = intval($_GET['appid']);
        }
        if($_GET['remark_id'] > 0){
            $where['remark_id'] = intval($_GET['remark_id']);
        }
        if($_GET['keywords']!=''){
            if($_GET['kw_type']=='order_id'){
                $where['order_id'] = $_GET['keywords'];
            }
            elseif($_GET['kw_type']=='order_no'){
                $where['order_no'] = $_GET['keywords'];
            }
            elseif($_GET['kw_type']=='user_id'){
                $where['user_id'] = $_GET['keywords'];
            }
            elseif($_GET['kw_type']=='mobile'){
                $where['mobile'] = $_GET['keywords'];
            }
        }

        if($_GET['status']!=0){
            $where['status'] =$_GET['status'];
        }
        $limit = min(isset($_GET['limit']) && is_numeric($_GET['limit']) ? $_GET['limit'] : 20, 100);
        $additional['page'] = intval($_GET['page']);
        $additional['size'] = intval($limit);
        $additional['goods_info'] = true;
        $additional['address_info'] = true;

        $count = $this->order_service->get_order_count($where,$additional);
        $order_list = $this->order_service->get_order_list($where,$additional);

        $pages  = $this->admin_pages($count, $limit);

        if( $count>0 ){
            $user_ids = array_column($order_list,'user_id');
            $user_list = $this->member_service->get_list(['user_id'=>$user_ids]);
            mixed_merge($order_list,$user_list,'user_id','user_info');
        }

        foreach( $order_list as &$item ){
			if( $item['zuqi_type'] == 1 ) {
				$item['zuqi'] = $item['zuqi'].'(天)';
				$item['zujin'] = $item['zujin'].'/天';
			}elseif( $item['zuqi_type'] == 2 ){
				$item['zuqi'] = $item['zuqi'].'(月)';
				$item['zujin'] = $item['zujin'].'/月';
			}
			
            $item['business_name']  = Business::getName($item['business_key']);
            $item['create_time_show'] = date('Y-m-d H:i:s',$item['create_time']);

            // 判断是否能够取消订单
            $judge_arr = [
                'order_status' => $item['order_status'],
                'delivery_status' => $item['delivery_status'],
            ];
            $item['allowed_cancel'] = Order::judgeCancelOrder( $judge_arr );
            $judge_arr['payment_status'] = $item['payment_status'];
            $judge_arr['service_status'] = $item['service_status'];
            $item['allowed_repairl'] = Order::judgeRepairlLog( $judge_arr );
            unset($judge_arr);

            $Orders =new \oms\Order($item);

            // 商品规格
            $specs_arr  = $item['goods_info']['specs'];
            $specs = array_column($specs_arr, 'value');
            $item['goods_name'] .= ' '.implode(' ',$specs);

            // 应用类型转换
            $appid_info = $this->load->service('channel/channel_appid')->get_info($item['appid']);
            $item['appid'] = !empty($appid_info) ? $appid_info['appid']['name'] : '其他';
            $item['status_show'] = $Orders->get_name();

            //获取操作列表
            $operation_list = $Orders->get_operation_list();

            if(!empty($operation_list)){
                foreach ($operation_list as $k => $operation){
                    if($operation['is_show'] === false){
                        unset($operation_list[$k]);
                    }
                    $mca_arr = explode('/', $operation['mca']);
                    if($mca_arr[2]=="cancel_withhode_delivery"){
                        $mca_arr[2]=substr_replace("cancel_withhode_delivery","cancel_delivery",0);
                    }
                    $promission = $this->check_promission_operate($mca_arr[0], $mca_arr[1], $mca_arr[2]);
                    if($promission === false){
                        unset($operation_list[$k]);
                    }
                }
            }

            if($Orders->order_islock()){
                $item['lock_show'] ="不可操作";
                $item['operation_list'] = [];
            }else{
                $item['lock_show'] ="--";
                $item['operation_list'] = $operation_list;

            }
            $payment_style_model = model('payment/payment_style','service')->modelId($item['payment_type_id']);
            $item['payment_type'] = isset($payment_style_model['pay_name'])?$payment_style_model['pay_name']:'--';


        }

        $lists = array(
            'th' => array(
                'business_name' => array('length' => 5,'title' => '业务类型'),
                'order_no' => array('title' => '订单编号','length' => 7),
                'mobile' => array('title' => '用户名','length' => 7),
                'create_time_show' => array('length' => 6,'title' => '下单时间','style'=>'date'),
                'zuqi' => array('title' => '租期','length' => 4),
                'zujin' => array('title' => '租金','length' => 7),
                'goods_name' => array('title' => '选购产品','length' => 8),
                'amount' => array('title' => '订单金额','length' => 5),
                'yajin' => array('title' => '实押金','length' =>5),
                'mianyajin' => array('title' => '免押金','length' =>5),
                'payment_type' => array('title' => '支付方式','length' => 7),
                'payment_amount' => array('title' => '支付金额','length' => 5),
                //'payment_time_show' => array('title' => '支付时间','length' => 6),
                'appid' => array('title' => '入口','length' => 5),
                'discount_amount' => array('title' => '优惠金额','length' => 5),
                'status_show' => array('title' => '订单状态','style'=>'_status','length' => 6),
                'lock_show' => array('title' => '是否可操作','style'=>'_status','length' => 6),
            ),
            'lists' => $order_list,
            'pages' => $pages,
        );

        // 订单状态
        $status_list = State::getStatusList();
        $tab_list = [];
        $css = '';
        if ($_GET['status'] == 0) {
            $css = 'current';
        }
        $url = url('order2/order/index', array('status' => 0));
        $tab_list[] = '<a class="' . $css . '" href="' . $url . '">全部</a>';
        foreach ($status_list as $k => $name) {
            $css = '';
            if ($_GET['status'] == $k) {
                $css = 'current';
            }
            $url = url('order2/order/index', array('status' => $k));
            $tab_list[] = '<a class="' . $css . '" href="' . $url . '">' . $name . '</a>';
        }

        $appid_list = $this->load->service('channel/channel_appid')->get_list(['status' => 1]);
        $appid_arr[0] = '全部';
        foreach ($appid_list as $appid){
            $appid_arr[$appid['id']] = $appid['name'];
        }
        $beizhu_list[0] = '全部';
        $beizhu_list =array_merge($beizhu_list,\zuji\order\Lists::getOrderBeizhuList());

        $this->load->librarys('View')
            ->assign('tab_list',$tab_list)
            ->assign('pay_channel_list',$this->pay_channel_list)
            ->assign('keywords_type_list',$this->keywords_type_list)
            ->assign('promission_arr', $this->promission_arr)
            ->assign('appid_list', $appid_arr)
            ->assign('beizhu_list', $beizhu_list)
            ->assign('lists',$lists)->assign('pages',$pages)->display('index');
    }
    /**
     * 取消订单 并解除资金预授权
     */
    public function remove_authorize(){
        $this->order_service = $this->load->service('order2/order');
        if(checksubmit('dosubmit')){
            // 当前 操作员
            $admin = [
                'id' =>$this->admin['id'],
                'username' =>$this->admin['username'],
            ];
            $Operator = new oms\operator\Admin( $admin['id'], $admin['username'] );

            $trans =$this->order_service->startTrans();
            if(!$trans){
                showmessage("服务器繁忙 请稍候再试！","null",0);
            }
            $additional =[
                'lock' =>true,
            ];
            $order_id =intval($_POST['order_id']);
            $should_amount =$_POST['should_amount'];
            $should_remark =$_POST['should_remark'];
            //查询订单是否已支付
            $order_info = $this->order_service->get_order_info(['order_id'=>$order_id],$additional);
            if(!$order_info){
                $this->order_service->rollback();
                showmessage('订单查询失败','null',0);
            }

            $this->fund_auth_table = $this->load->table('payment/payment_fund_auth');
            $auth_info = $this->fund_auth_table->where([
                'order_id' => $order_id,
            ])->find(['lock'=>true]);

            $jiedong =$auth_info['amount'] - $auth_info['unfreeze_amount'] - $auth_info['pay_amount'];
            $order_info['jiedong'] = Order::priceFormat($jiedong);

            if ($should_amount > $order_info['jiedong']) {
                $this->order_service->rollback();
                showmessage("解冻金额超出限制",'null',0);
            }

            $Orders = new \oms\Order($order_info);
            if(!$Orders->allow_to_remove_authorize()){
                $this->order_service->rollback();
                showmessage('该订单不允许解除资金预授权！','null',0);
            }
            try{
                // 订单 观察者主题
                $OrderObservable = $Orders->get_observable();
                // 订单 观察者 状态流
                $FollowObserver = new oms\observer\FollowObserver( $OrderObservable );
                // 订单 观察者  日志
                $LogObserver = new oms\observer\LogObserver( $OrderObservable , "解除资金预授权", $should_remark);
                $LogObserver->set_operator($Operator);
                $data =[
                    'should_amount'=>$should_amount*100,
                ];

                $b =$Orders->remove_authorize($data);
                if(!$b){
                    $this->order_service->rollback();
                    showmessage('解除资金预授权失败:'.get_error(),'null',0);
                }

                // 取消订单
                // 订单 观察者  日志
                $LogObserver = new oms\observer\LogObserver( $OrderObservable , "取消订单", "取消订单");
                $LogObserver->set_operator($Operator);

                $b =$Orders->cancel_order(['order_id'=>$order_id]);
                if(!$b){
                    $this->order_service->rollback();
                    showmessage('取消订单失败'.get_error(),'null',0);
                }

                $this->order_service->commit();

                //解除资金预授权发送短信
                $sms_data =[
                    'realName' => $order_info['realname'],
                    'orderNo' => $order_info['order_no'],
                    'goodsName' => $order_info['goods_name'],
                    'mobile' =>$order_info['mobile'],
                ];
//                \zuji\sms\SendSms::remove_authorize($sms_data);
                //取消订单发送短信
                \zuji\sms\SendSms::cancel_order($sms_data);

                showmessage('操作成功','null',1);
            }catch (\Exception $exc){
                $this->order_service->rollback();
                Debug::error(Location::L_Order, '取消订单失败:'.$exc->getMessage(), []);
                showmessage($exc->getMessage(),'null',0);
            }

        }

        $order_id =intval($_GET['order_id']);
        //查询订单是否已支付
        $order_info = $this->order_service->get_order_info(['order_id'=>$order_id]);
        if(!$order_info){
            showmessage('订单查询失败','null',0);
        }


        $this->fund_auth_table = $this->load->table('payment/payment_fund_auth');
        $auth_info = $this->fund_auth_table->where([
            'order_id' => $order_id,
        ])->find();
        $jiedong =$auth_info['amount'] - $auth_info['unfreeze_amount'] - $auth_info['pay_amount'];
        $order_info['jiedong'] = Order::priceFormat($jiedong);



        $Orders = new \oms\Order($order_info);
        if(!$Orders->allow_to_remove_authorize()){
            showmessage('该订单不允许解除资金预授权！','null',0);
        }
        $msg="确认取消该订单么？并且取消资金预授权";

        $this->load->librarys('View')
            ->assign('msg',$msg)
            ->assign('url','order2/order/remove_authorize')
            ->assign('order_id',$order_id)
            ->assign('order_info',$order_info)
            ->display('alert_remove_authorize');

    }
    /**
     * 取消订单
     */
    public function cancel_order(){
        $this->order_service = $this->load->service('order2/order');
        $this->zhima_certification_service =$this->load->service('order2/zhima_certification');
        if(checksubmit('dosubmit')){
            // 当前 操作员
            $admin = [
                'id' =>$this->admin['id'],
                'username' =>$this->admin['username'],
            ];
            $Operator = new oms\operator\Admin( $admin['id'], $admin['username'] );

            $trans =$this->order_service->startTrans();
            if(!$trans){
                showmessage("服务器繁忙 请稍候再试！","null",0);
            }
            $additional =[
                'lock' =>true,
            ];
            $order_id =intval($_POST['order_id']);
            //查询订单是否已支付
            $order_info = $this->order_service->get_order_info(['order_id'=>$order_id],$additional);
            if(!$order_info){
                $this->order_service->rollback();
                showmessage('订单查询失败','null',0);
            }
            $Orders = new \oms\Order($order_info);
            if(!$Orders->allow_to_cancel_order()){
                $this->order_service->rollback();
                showmessage('该订单不允许取消！','null',0);
            }
            $data =[
                'order_id'=>$order_id,
                'reason_id'=>0,
                'reason_text'=>$_POST['remark'],
            ];
            // 订单 观察者主题
            $OrderObservable = $Orders->get_observable();
            try{
                if($order_info['payment_type_id'] == \zuji\Config::MiniAlipay && $order_info['status']!=State::OrderCreated){
                    // 订单 观察者 状态流
                    $FollowObserver = new oms\observer\FollowObserver( $OrderObservable );
                    // 订单 观察者  日志
                    $LogObserver = new oms\observer\LogObserver( $OrderObservable , "取消订单退款", $_POST['remark']);
                    $LogObserver->set_operator($Operator);

                    $b =$Orders->cancel_order($data);
                    if(!$b){
                        $this->order_service->rollback();
                        showmessage('取消订单失败'.get_error(),'null',0);
                    }

                    $this->zhima_order_confrimed_table =$this->load->table('order2/zhima_order_confirmed');
                    //获取订单的芝麻订单编号
                    $zhima_order_info = $this->zhima_order_confrimed_table->where(['order_no'=>$order_info['order_no']])->find($additional);
                    if(!$zhima_order_info){
                        $this->order_service->rollback();
                        showmessage('该订单没有芝麻订单号！','null',0);
                    }
                    $zhima = new \zhima\Withhold();
                    $b =$zhima->OrderCancel([
                        'out_order_no'=>$order_info['order_no'],//商户端订单号
                        'zm_order_no'=>$zhima_order_info['zm_order_no'],//芝麻订单号
                        'remark'=>$_POST['remark'],//订单操作说明
                    ]);
                    $this->order_service->commit();
                    if($b === false){
                        Debug::error(Location::L_Order,"小程序订单取消",[
                            'out_order_no'=>$order_info['order_no'],//商户端订单号
                            'zm_order_no'=>$zhima_order_info['zm_order_no'],//芝麻订单号
                            'remark'=>$_POST['remark'],//订单操作说明
                        ]);
                        showmessage('操作失败','null',0);
                    }
                    showmessage('操作成功','null',1);

                }else{

                    // 订单 观察者 状态流
                    $FollowObserver = new oms\observer\FollowObserver( $OrderObservable );
                    // 订单 观察者  日志
                    $LogObserver = new oms\observer\LogObserver( $OrderObservable , "取消订单", $_POST['remark']);
                    $LogObserver->set_operator($Operator);

                    $b =$Orders->cancel_order($data);
                    if(!$b){
                        $this->order_service->rollback();
                        showmessage('取消订单失败'.get_error(),'null',0);
                    }

                    //取消订单发送短信
                    \zuji\sms\SendSms::cancel_order([
                        'mobile' => $order_info['mobile'],
                        'orderNo' => $order_info['order_no'],
                        'realName' => $order_info['realname'],
                        'goodsName' => $order_info['goods_name'],
                    ]);
                    $this->order_service->commit();
                    showmessage('操作成功','null',1);
                }

            }catch (\Exception $exc){
                $this->order_service->rollback();
                Debug::error(Location::L_Order, '取消订单失败:'.$exc->getMessage(), $data);
                showmessage($exc->getMessage(),'null',0);
            }

        }

        $order_id =intval($_GET['order_id']);
        //查询订单是否已支付
        $order_info = $this->order_service->get_order_info(['order_id'=>$order_id]);
        if(!$order_info){
            showmessage('订单查询失败','null',0);
        }
        $Orders = new \oms\Order($order_info);
        if(!$Orders->allow_to_cancel_order()){
            showmessage('该订单不允许取消！','null',0);
        }
        $msg="确认取消该订单么？";

        $this->load->librarys('View')
            ->assign('msg',$msg)
            ->assign('url','order2/order/cancel_order')
            ->assign('order_id',$order_id)
            ->display('alert_cancel');

    }

    /**
     * 订单详情
     */
    public function detail() {
        $this->order_service = $this->load->service('order2/order');
        $this->return_service = $this->load->service('order2/return');
        $this->member_service = $this->load->service('member2/member');
        $this->spu_service = $this->load->service('goods/goods_spu');
        $this->payment_service = $this->load->service('order2/payment');
        $this->delivery_service = $this->load->service('order2/delivery');
        $this->order_image_service = $this->load->service('order2/order_image');
        $this->coupon_service = $this->load->service('order2/coupon');
        $this->contract_table = $this->load->table('order2/order2_contract');

        $order_id = intval( $_GET['order_id'] );
        $return_id = intval( $_GET['return_id'] );

        if($return_id){
            $return_info =  $this->return_service->get_info($return_id);
        }
        $order_info = $this->order_service->get_order_info( ['order_id'=>$order_id],['goods_info'=>true,'address_info'=>true] );
        if (!$order_info){
            echo_div('该订单不存在');
        }
        $Orders = new \oms\Order($order_info);
        $order_info['status_name'] = $Orders->get_name();

        $create_delivery =false;
        if($Orders->allow_to_confirm_order() &&
            $this->promission_arr['create_delivery']){
            $create_delivery=true;
        }


        // 商品规格
        $specs  = $order_info['goods_info']['specs'];
        $spec_value_list = [];
        foreach( $specs as $it ){
            $spec_value_list[] = $it['value'];
        }
        $order_info['goods_info']['spec_value_list'] = implode(' ',$spec_value_list);
        $chengse = array('100'=>'全新','99'=>'99成新','95'=>'95成新','90'=>'9成新','80'=>'8成新','70'=>'7成新',);
        $order_info['chengse'] = $chengse[$order_info['chengse']];


        //省, 市, 区县,具体地址,拼接
        $this->district_service = $this->load->service('admin/district');
        $order_info['address_info']['address'] = $this->district_service->get_address_detail($order_info['address_info']);

        //查询近7天 订单地址相似度匹配>70% 的
        $order =[];
        if($order_info['similar_status'] == 1){
            $this->order2_similar_address = $this->load->table('order2/order2_similar_address');
            $order_list = $this->order2_similar_address->get_similar_by_user_id($order_info['user_id']);
            foreach( $order_list as &$item ){
                $order[$item['order_id']] =$item['order_no'];
            }
        }else{
            $order =$this->order_service->similar_order_address(['order_id'=>$order_id,'user_id'=>$order_info['user_id'],'create_time'=>$order_info['create_time'],'address'=>$order_info['address_info']['address']]);
        }

        $order_info['similar'] =$order;
        // 用户信息
        $order_info['member'] = $this->member_service->get_info(['id' => $order_info['user_id']]);

        // 产品图片
        $spu_info = $this->spu_service->get_query_one( $order_info['goods_info']['spu_id'] );
        $thumb = $spu_info['spu_info']['thumb'];
        $order_info['goods_info']['thumb'] = $thumb;
        $order_info['order_status'] = zuji\order\Order::getStatusName($order_info);
        //获取支付信息
        $payment_row = $this->payment_service->get_info_orderid(['order_id'=>$order_info['id']]);
        $order_info['payment_row'] = $payment_row;

        // 应用类型转换
        $appid_info = $this->load->service('channel/channel_appid')->get_info($order_info['appid']);
        $order_info['appid'] = !empty($appid_info) ? $appid_info['appid']['name'] : '其他';

        //获取检测退回信息
        $this->delivery_service = $this->load->service('order2/delivery');

        $delivery_count =$this->delivery_service->get_count(['order_id'=>$order_id,'evaluation_id'=>$order_info['evaluation_id'],'business_key'=>Business::BUSINESS_HUIJI]);


        // 线下渠道 证件相片
        if($order_info['business_key'] == Business::BUSINESS_STORE){
            $order_image =  $this->order_image_service->get_one($order_id);
        }
        $order_info['order_image'] = !empty($order_image) ? $order_image : "" ;

//        // 优惠券信息
        $coupon = $this->coupon_service->get_info($order_id);
        if($coupon){
            $coupon['coupon_type_show']=\zuji\coupon\CouponStatus::get_coupon_type_name(intval($coupon['coupon_type']));
        }
        $order_info['coupon'] = $coupon ;

        //获取订单回访备注
        $order_info['order_beizhu'] = \zuji\order\Lists::getOrderBeizhuName($order_info['remark_id'])." ".$order_info['remark'];
		
		//订单租期租金
		if( $order_info['zuqi_type'] == 1 ) {
			$order_info['zuqi'] = $order_info['zuqi'].'(天)';
			$order_info['zujin'] = $order_info['zujin'].'/天';
		}elseif( $order_info['zuqi_type'] == 2 ){
			$order_info['zuqi'] = $order_info['zuqi'].'(月)';
			$order_info['zujin'] = $order_info['zujin'].'/月';
		}

        // 日志


        $order_logs = $this->service_order_log->get_by_order_no($order_info['order_no'],'id DESC');

        //维修记录
        $weixiu_service = $this->load->service('weixiu/weixiu');
        $repair_record = $weixiu_service->get_info_all(['order_id' => $order_id]);

        //蚁盾风险查看
//        $fond_color = array('accept'=>'green','reject'=>'red','validate'=>'yellow');
//        $order_info['yidun']['color'] = $fond_color[$order_info['yidun']['decision']];

        //电子合同
        $contract_info = $this->contract_table->where(['order_no'=>$order_info['order_no']])->find();

        $this->load->librarys('View')
            ->assign('order',$order_info)
            ->assign('delivery_count',$delivery_count)
            ->assign('return_info',$return_info)
            ->assign('order_logs',$order_logs)
            ->assign('create_delivery',$create_delivery)
            ->assign('repair_record', $repair_record)
            ->assign('contract_info', $contract_info)
            ->display('detail');
    }

    /**
     *
     */
    public function order_log(){

        // 日志
        $order_logs = $this->load->service('order2/order_log')->get_by_order_no($_GET['order_no'],'id DESC');
        $this->load->librarys('View')
            ->assign('order_logs',$order_logs)
            ->display('order_log');
    }

    public function order_follow(){

        // 日志
        $order_follows = $this->load->service('order2/order')->get_follow_by_order_id($_GET['order_id']);
        $this->load->librarys('View')
            ->assign('order_follows',$order_follows)
            ->display('order_follow');
    }

    /*
     * 还机列表详情
     **/
    public function send_back()
    {
        $order = $this->service_sub->find(array('sub_sn' => $_GET['sub_sn']));
        if (!$order) showmessage(lang('order_not_exist','order/language'));
        // $order['_member'] = $this->load->service('member/member')->find($order['buyer_id']);
        $order['_member'] = $this->service->member_data($order['buyer_id']);
        $order['_main'] = $this->service->find(array('sn' => $order['order_no']));
        foreach ($order['_skus'] as $key => $value) {
            if($key > 0){
                $status = 1;
            }
        }
        // 日志
        $order_logs = $this->service_order_log->get_by_order_no($order['sub_sn'],'id DESC');
        $this->load->librarys('View')->assign('order',$order)->assign('order_logs',$order_logs)->assign('order_status',$status)->display('send_back');


    }
    /**
     * 修改订单备注和回访等标识
     */
    public function order_beizhu_edit(){
        $this->order_service = $this->load->service('order2/order');
        $this->order2_table = $this->load->table('order2/order2');

        if(checksubmit('dosubmit')){
            $order_id =$_POST['order_id'];
            $remark_id =$_POST['remark_id'];
            $remark =$_POST['remark'];
            $data=[
                'order_id'=>$order_id,
                'remark_id'=>$remark_id,
                'remark'=>$remark,
            ];
            $b = $this->order2_table->save($data);
            if($b===false){
                showmessage('编辑备注失败','null',0);
            }
            $order_info = $this->order_service->get_order_info(['order_id'=>$order_id]);
            // 操作日志
            $operator = get_operator();
            $log=[
                'order_no'=>$order_info['order_no'],
                'action'=>"修改回访备注",
                'operator_id'=>$operator['id'],
                'operator_name'=>$operator['username'],
                'operator_type'=>$operator['operator_type'],
                'msg'=>\zuji\order\Lists::getOrderBeizhuName($remark_id)." ".$remark,
            ];
            $this->service_order_log->add($log);
            showmessage(lang('修改订单收货信息成功'),'null',1);
        }else{

            $order_id = $_GET['order_id'];
            $order_info = $this->order_service->get_order_info(['order_id'=>$order_id]);
            $beizhu_list =\zuji\order\Lists::getOrderBeizhuList();

            $this->load->librarys('View')
                ->assign('order_info',$order_info)
                ->assign('beizhu_list',$beizhu_list)
                ->display('alert_order_beizhu');
        }
    }
    /**
     * 修改订单收货信息(GET请求为修改表单页，POST为修改表单提交)
     */
    public function address_edit(){
        $this->order_service = $this->load->service('order2/order');
        $address_id = $_GET['address_id'];
        $address_info = $this->order_service->get_address_info($address_id);
        if( !$address_info ){
            showmessage(lang('参数错误'),'',0,'json');
        }
        $order_info = $this->order_service->get_order_info( $address_info );
        if( !$order_info ){
            showmessage(lang('参数错误'),'',0,'json');
        }

        if(checksubmit('dosubmit')){
            $_GET['province_id'] = '';
            $_GET['city_id'] = '';
            $_GET['country_id'] = '';
            $list = $this->load->service('admin/district')->fetch_parents( $_GET['district_id'] );
            foreach( $list as $it){
                if($it['level']==3){
                    $_GET['country_id'] = $it['id'];
                }elseif($it['level']==2){
                    $_GET['city_id'] = $it['id'];
                }elseif($it['level']==1){
                    $_GET['province_id'] = $it['id'];
                }
            }
            // 修改收货地址
            $result = $this->order_service->edit_address($_GET['address_id'],$_GET);
            if($result===false){
                showmessage('修改订单收货信息失败:'.  get_error());
            }
            // 操作日志
            $operator = get_operator();
            $log=[
                'order_no'=>$order_info['order_no'],
                'action'=>"修改收货地址",
                'operator_id'=>$operator['id'],
                'operator_name'=>$operator['username'],
                'operator_type'=>$operator['operator_type'],
                'msg'=>$_GET['remark'],
            ];
            $this->service_order_log->add($log);
            //获取用户ID
            $userId = ADMIN_ID;
            //获取操作人的IP：
            $user_IP = ($_SERVER["HTTP_VIA"]) ? $_SERVER["HTTP_X_FORWARDED_FOR"] : $_SERVER["REMOTE_ADDR"];
            //操作功能ID
            $optionId = \zuji\debug\Opreation::ORDER_STE_ADDRESS_EDIT;
            //操作备注
            $remark = '根据收货地址ID为'.$_GET['address_id'].'修改收货地址信息';
            //操作时间
            $dateline = time();
            //操作连接
            $url = 'http://api.zuji.huishoubao.com/index.php?m=oder2&c=order&a=address_edit';
            $this->create_log($userId,$user_IP,$optionId,$remark,$dateline,$url);
            showmessage(lang('修改订单收货信息成功'),'',1,'json');
        }else{
            $address_info['cids']=[100000,$address_info['province_id'],$address_info['city_id'],$address_info['country_id']];
            $address_info['cid']=$address_info['country_id'];
            $this->load->librarys('View')
                ->assign('address_info',$address_info)
                ->display('address');
        }
    }



    /**
     * 修改物流信息
     */
    public function delivery_edit(){
        if (checksubmit('dosubmit')) {
            $params = array();
            $o_delivery_id = remove_xss($_GET['o_delivery_id']);
            $old_delivery = $this->service_p_delivery->order_delivery_find(array('id'=>$o_delivery_id),'delivery_name,delivery_sn');
            $delivery_status = $this->service_order_sku->getField('delivery_status',array('delivery_id'=>$o_delivery_id));
            if ($delivery_status == 2){
                showmessage('用户已收货，不能修改物流信息！');
            }
            $this->service_p_delivery->delete_delivery($o_delivery_id);
            $params['is_choise']     = 1;
            $params['delivery_id']   = remove_xss($_GET['delivery_id']);
            $params['delivery_sn']   = remove_xss($_GET['delivery_sn']);
            $params['sub_sn']   	 = remove_xss($_GET['sub_sn']);
            $params['o_sku_ids']	 = remove_xss($_GET['o_sku_ids']);
            $skuids = explode(',',$params['o_sku_ids']);
            $skus = $this->service_order_sku->getField('id,sku_name',array('id'=>array('IN',$skuids)));
            $str = '';
            foreach ($skus as $key => $value) {
                $str .= (count($skus) > 1) ? $value.'，' : $value;
            }
            $new_delivery = $this->service_p_delivery->get_by_id($params['delivery_id']);
            $params['msg']			 = $str.' 物流信息由： '.$old_delivery['delivery_name'].'【'.$old_delivery['delivery_sn'].'】 修改为： '.$new_delivery['name'].'【'.$params['delivery_sn'].'】';
            $params['is_edit']		 = '修改物流信息';
            $result = $this->service_sub->set_order($_GET['sub_sn'] ,'delivery' ,$_GET['order_status'],$params);
            if (!$result) showmessage($this->service_sub->error);
            //获取用户ID
            $userId = ADMIN_ID;
            //获取操作人的IP：
            $user_IP = ($_SERVER["HTTP_VIA"]) ? $_SERVER["HTTP_X_FORWARDED_FOR"] : $_SERVER["REMOTE_ADDR"];
            //操作功能ID
            $optionId = \zuji\debug\Opreation::ORDER_STE_DELIVERY_EDIT;
            //操作备注
            $remark = '根据子订单号'.$_GET['sub_sn'].'修改物流信息';
            //操作时间
            $dateline = time();
            //操作连接
            $url = 'http://api.zuji.huishoubao.com/index.php?m=oder2&c=order&a=delivery_edit';
            $this->create_log($userId,$user_IP,$optionId,$remark,$dateline,$url);
            showmessage(lang('修改物流信息成功'),'',1,'json');
        } else {
            // 获取已开启的物流
            $sqlmap = $deliverys = array();
            $sqlmap['enabled'] = 1;
            $deliverys = $this->service_p_delivery->getField('id,name' ,$sqlmap);
            // 获取子订单下的skus
            $o_skus = $this->service_sub->sub_delivery_skus($_GET['sub_sn']);
            foreach ($o_skus as $key => $value) {
                if($value['delivery_id'] != $_GET['delivery_id']){
                    unset($o_skus[$key]);
                    continue;
                }
            }
            if (!$o_skus) {
                showmessage($this->service_sub->error);
            }
            $order_deliverys = $this->service_p_delivery->order_delivery_find(array('id'=>$_GET['delivery_id']) ,"*");
            $this->load->librarys('View')->assign('deliverys',$deliverys)->assign('o_skus',$o_skus)->assign('order_deliverys',$order_deliverys)->display('alert_delivery_edit');
        }
    }

    /**
     * 确认完成订单
     */
    public function finish() {
        if (checksubmit('dosubmit')) {
            $result = $this->service_sub->set_order($_GET['sub_sn'] ,'finish' ,'',array('msg'=>$_GET['msg']));
            if (!$result) showmessage($this->service_sub->error);
            //获取用户ID
            $userId = ADMIN_ID;
            //获取操作人的IP：
            $user_IP = ($_SERVER["HTTP_VIA"]) ? $_SERVER["HTTP_X_FORWARDED_FOR"] : $_SERVER["REMOTE_ADDR"];
            //操作功能ID
            $optionId = \zuji\debug\Opreation::ORDER_STE_FINISH;
            //操作备注
            $remark = '根据子订单号'.$_GET['sub_sn'].'确认完成订单';
            //操作时间
            $dateline = time();
            //操作连接
            $url = 'http://api.zuji.huishoubao.com/index.php?m=oder2&c=order&a=finish';
            $this->create_log($userId,$user_IP,$optionId,$remark,$dateline,$url);
            showmessage(lang('确认完成成功'),'',1,'json');
        } else {
            $this->load->librarys('View')->display('alert_finish');
        }
    }


    /**
     * 修改订单应付总额
     */
    public function update_real_price() {
        if (checksubmit('dosubmit')) {
            $result = $this->service->update_real_price($_GET['sub_sn'] ,$_GET['real_price']);
            if (!$result) {
                showmessage($this->service->error);
            }
            //获取用户ID
            $userId = ADMIN_ID;
            //获取操作人的IP：
            $user_IP = ($_SERVER["HTTP_VIA"]) ? $_SERVER["HTTP_X_FORWARDED_FOR"] : $_SERVER["REMOTE_ADDR"];
            //操作功能ID
            $optionId = \zuji\debug\Opreation::ORDER_STE_REAL_PRICE;
            //操作备注
            $remark = '根据子订单号'.$_GET['sub_sn'].'修改订单应付总额';
            //操作时间
            $dateline = time();
            //操作连接
            $url = 'http://api.zuji.huishoubao.com/index.php?m=oder2&c=order&a=update_real_price';
            $this->create_log($userId,$user_IP,$optionId,$remark,$dateline,$url);
            showmessage(lang('修改订单应付总额成功'),'',1,'json');
        } else {
            $order = $this->service_sub->find(array('sub_sn' => $_GET['sub_sn']));
            $this->load->librarys('View')->assign('order',$order)->display('alert_update_real_price');
        }
    }

    /**
     * 打印发货单
     */
    public function prints(){
        if((int)$_GET['id'] < 1) showmessage(lang('_error_action_'));
        $info = $this->service_tpl_parcel->get_tpl_parcel_by_id(1);
        //订单信息
        $sub_sn = $this->load->service('order/order_parcel')->fetch_by_id($_GET['id'],'sub_sn');
        $order_no = $this->load->service('order/order_parcel')->fetch_by_id($_GET['id'],'order_no');
        //收货人信息
        $userinfo = $this->load->service('order/order_parcel')->find(array('sub_sn'=>$sub_sn));
        //商品信息
        $goods = $this->load->service('order/order_sku')->fetch(array('sub_sn'=>$sub_sn));
        $info['content'] = str_replace('{order_no}',$order_no,$info['content']);
        $info['content'] = str_replace('{address}',$userinfo['address_detail'],$info['content']);
        $info['content'] = str_replace('{print_time}',date('Y-m-d H:i:s',time()),$info['content']);
        $info['content'] = str_replace('{accept_name}',$userinfo['address_name'],$info['content']);
        $info['content'] = str_replace('{mobile}',$userinfo['address_mobile'],$info['content']);
        $info['content'] = str_replace('{delivery_txt}',$userinfo['delivery_name'],$info['content']);
        $field_start = substr($info['content'],strpos($info['content'],'<tr id="goodslist">'));
        $field_end = substr($info['content'],strpos($info['content'],'<tr id="goodslist">'),strpos($field_start, '<tr>'));
        $total_num = 0;
        $total_price = 0;
        $total_price = 0;
        foreach($goods as $k => $v){
            $str = str_replace('{sort_id}',$k+1,$field_end);
            $sku = $this->load->service('goods/goods_sku')->fetch_by_id($v['sku_id']);
            $str = str_replace('{products_sn}',$sku['sn'],$str);
            $str = str_replace('{goods_name}',$v['sku_name'],$str);
            $str = str_replace('{goods_spec}',$v['_sku_spec'],$str);
            $str = str_replace('{shop_price}',$v['sku_price'],$str);
            $str = str_replace('{number}',$v['buy_nums'],$str);
            $str = str_replace('{total_goods_price}',$v['real_price'],$str);
            $goods[$k] = $str;
            $total_num = $total_num + $v['buy_nums'];
            $total_price =  $total_price + $v['real_price'];
        }
        $goods=implode('', $goods);
        $info['content']=str_replace($field_end, $goods, $info['content']);
        $info['content']=str_replace('{total_num}', $total_num, $info['content']);
        $info['content']=str_replace('{total_price}', number_format($total_price,2), $info['content']);
        $this->load->librarys('View')->assign('info',$info)->assign('sub_sn',$sub_sn)->display('prints_parcel');
    }


    /* 打印快递单 */
    public function print_kd() {
        if (checksubmit('dosubmit')) {
            /* 标记快递单为已打印 */
            $o_id = (int) $_GET['o_id'];
            $this->service->order_delivery_update(array('print_time' => time()), array('id' => $_GET['o_id']));
            return TRUE;
        } else {
            $o_delivery = $this->service->order_delivery_find(array('id' => $_GET['o_id']));
            $sub_order = $this->load->service('order/order_sub')->sub_detail($o_delivery['sub_sn']);
            $main_order = $this->service->find(array('sn' => $sub_order['order_no']));
            $setting = $this->load->service('admin/setting')->get();
            $_delivery = $this->load->service('order/delivery')->find(array('id' => $o_delivery['delivery_id']));
            // 替换值
            if ($_delivery['tpl']) {
                $_delivery['tpl'] = json_decode($_delivery['tpl'] ,TRUE);
                $str = '';
                foreach ($_delivery['tpl']['list'] as $k => $v) {
                    $str = str_replace('left','x',json_encode($v));
                    $str = str_replace('{address_name}',$main_order['address_name'],$v);
                    $str = str_replace('{address_mobile}',$main_order['address_mobile'],$str);
                    $str = str_replace('{address_detail}',$main_order['address_detail'],$str);
                    $str = str_replace('{sender_name}',$setting['sender_name'],$str);
                    $str = str_replace('{sender_mobile}',$setting['sender_mobile'],$str);
                    $str = str_replace('{sender_address}',$setting['sender_address'],$str);
                    $str = str_replace('{real_amount}',$main_order['real_amount'],$str);
                    $str = str_replace('{paid_amount}',$main_order['paid_amount'],$str);
                    $str = str_replace('{remark}',$sub_order['remark'],$str);
                    $str = str_replace('{dateline}',date('Y-m-d H:i:s' , time()),$str);
                    $_delivery['tpl']['list'][$k] = $str;
                }
            }
            $this->load->librarys('View')->assign('o_delivery',$o_delivery)->assign('_delivery',$_delivery)->display('print_kd');
        }
    }

    /**
     * 订单管理导出
     */
    public function diff_order_export() {
        $this->order_service = $this->load->service('order2/order');
        $this->channel_service = $this->load->service('channel/channel');
        $this->channel_appid_service = $this->load->service('channel/channel_appid');
        // 不限制超时时间
        set_time_limit(0);
        // 内存2M
        ini_set('memory_limit', 200*1024*1024);
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename='.'订单新增统计'.time().'-'.rand(1000, 9999).'.csv');
        header('Cache-Control: max-age=0');
        $handle = fopen('php://output', 'a');

        $header_data = array('业务类型','订单编号','用户名','用户姓名','性别','身份证号','蚁盾分数','蚁盾描述','蚁盾策略','下单时间','租期','月租金','信用分','选购产品','成色','颜色','容量','网络制式','订单金额','碎屏意外险','碎屏意外险成本','碎屏意外险利润','实押金','免押金','支付方式','支付金额','支付时间','渠道','渠道appid','优惠金额','订单状态','取消原因','相似订单编号');
        //输出头部数据
        $this->export_csv_wirter_row($handle, $header_data);
        $where = [];
        if($_REQUEST['begin_time'] != '' ){
            $where['begin_time'] = strtotime($_REQUEST['begin_time']);
        }
        if( $_REQUEST['end_time'] != '' ){
            $where['end_time'] = strtotime($_REQUEST['end_time']);
        }

        if($_REQUEST['status'] != 0){
            $where['status'] = $_REQUEST['status'];
        }
        // 渠道列表
        $channel_list_info = $this->channel_service->get_list();
        $channel_list = [];
        foreach ($channel_list_info as $key => $value) {
            $channel_list[$value['id']] = $value;
        }
        // 渠道appid列表
        $channel_appid_list = $this->channel_appid_service->get_list();
        //appid和父级渠道集合列表
        $channel_lists = [];
        foreach ($channel_appid_list as $value) {
            $channel_lists[$value['id']] = [
                'name' => $value['name'],
                'channel_name' => isset($channel_list[$value['channel_id']]) ? $channel_list[$value['channel_id']]['name'] : '其他'
            ];
        }


        // 支付方式
        $options['order'] = 'id desc';
        $payment_style = $this->load->service('payment/payment_style')->arrListByPage(0, 0, [], $options);
        $payment_style_list = $payment_style['rows'];

        $additional['page'] = 1;
        $additional['size'] = 100;
        $additional['goods_info'] = true;
        $additional['address_info'] = true;

        $order_count = $this->order_service->get_order_count($where);
        while ($order_count>0){
            $order_list = $this->order_service->get_order_list($where,$additional);

            foreach($order_list as $val){
                $Orders =new \oms\Order($val);

                // 支付方式
                $payment_type = isset($payment_style_list[$val['payment_type_id']])?$payment_style_list[$val['payment_type_id']]['pay_name']:'--';

                // 渠道
                $channel_name = isset($channel_lists[$val['appid']]) ? $channel_lists[$val['appid']]['channel_name'] : '其他';
                $channel_appid_name = isset($channel_lists[$val['appid']]) ? $channel_lists[$val['appid']]['name'] : '其他';

                // 订单状态
                $status_show = $Orders->get_name();

                // 商品规格
                $specs_arr  = $val['goods_info']['specs'];
                $specs = array_column($specs_arr, 'value');
                $specs= array_values($specs);

                $create_time = $val['create_time'] != '0' ? date('Y-m-d H:i:s',$val['create_time']) : "--";
                $payment_time = $val['payment_time'] != '0' ? date('Y-m-d H:i:s',$val['payment_time']) : "--";
                //取消原因
                $reason_list = zuji\order\Reason::$_ORDER_QUESTION;
                $cancel_reason_list = $reason_list[zuji\order\Reason::ORDER_CANCEL];
                $cancel_reason = $val['reason_id'] ? $cancel_reason_list[$val['reason_id']] : $val['reason_text'];

                //获取相似订单编号
                $similar_order_no = [];
                if($val['similar_status'] == 1){
                    $this->order2_similar_address = $this->load->table('order2/order2_similar_address');
                    $similar_order_list = $this->order2_similar_address->get_similar_by_user_id($val['user_id']);
                    foreach( $similar_order_list as &$item ){
                        $similar_order_no[$item['order_id']] =$item['order_no'];
                    }
                }else{
                    $similar_order_no =$this->order_service->similar_order_address(['order_id'=>$val['order_id'],'user_id'=>$val['user_id'],'create_time'=>$val['create_time'],'address'=>$val['address_info']['address']]);
                }

                //身份证号区分男女
                $man_arr = [1,3,5,7,9];
                $sex_name = isset($val['cert_no'])?(in_array(substr($val['cert_no'], -2,-1),$man_arr)?'男':'女'):'--';

                $body_data = [
                    ''.Business::getName($val['business_key']),
                    "\t" . $val['order_no'],
                    "\t" . $val['mobile'],
                    ''.$val['realname'],
                    ''.$sex_name,
                    "\t".$val['cert_no'],
                    ''.$val['yidun']['score'],
                    ''.$val['yidun']['decision_text'],
                    ''.$val['yidun']['strategies'],
                    "\t" . $create_time,

                    ''.$val['zuqi'],
                    ''.zuji\order\Order::priceFormat($val['zujin']),
                    ''.$val['credit'],
                    ''.$val['goods_name'],

                    ''.$specs[0],
                    ''.$specs[1],
                    ''.$specs[3],
                    ''.$specs[4],
                    ''.$val['amount'],
                    ''.zuji\order\Order::priceFormat($val['yiwaixian']),
                    ''.zuji\order\Order::priceFormat($val['goods_info']['yiwaixian_cost']/100),
                    ''.zuji\order\Order::priceFormat($val['yiwaixian']-$val['goods_info']['yiwaixian_cost']/100),
                    ''.$val['yajin'],
                    ''.$val['mianyajin'],
                    ''.$payment_type,
                    ''.zuji\order\Order::priceFormat($val['payment_amount']),
                    "\t" . $payment_time,
                    ''.$channel_name,
                    ''.$channel_appid_name,
                    ''.zuji\order\Order::priceFormat($val['discount_amount']),
                    ''.$status_show,
                    ''.$cancel_reason,
                    ''.implode('#', $similar_order_no),
                ];
                $this->export_csv_wirter_row($handle, $body_data);
                unset($body_data);
            }
            $additional['page'] = $additional['page'] + 1;
            $order_count = $order_count - $additional['size'];
        }
        fclose($handle);
    }

    /**
     * 订单管理快速导出
     */
    public function diff_order_export_speed() {
        $this->order_service = $this->load->service('order2/order');
        $this->channel_service = $this->load->service('channel/channel');
        $this->channel_appid_service = $this->load->service('channel/channel_appid');
        // 不限制超时时间
        set_time_limit(0);
        // 内存2M
        ini_set('memory_limit', 200*1024*1024);
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename='.'订单新增统计'.time().'-'.rand(1000, 9999).'.csv');
        header('Cache-Control: max-age=0');
        $handle = fopen('php://output', 'a');

        $header_data = array('业务类型','订单编号','用户名','用户姓名','性别','身份证号','蚁盾分数','蚁盾描述','蚁盾策略','下单时间','租期','月租金','信用分','选购产品','成色','颜色','容量','网络制式','订单金额','碎屏意外险','碎屏意外险成本','碎屏意外险利润','实押金','免押金','支付方式','支付金额','支付时间','渠道','渠道appid','优惠金额','订单状态','取消原因','相似订单编号');
        //输出头部数据
        $this->export_csv_wirter_row($handle, $header_data);
        $where = [];
        if($_REQUEST['begin_time'] != '' ){
            $where['begin_time'] = strtotime($_REQUEST['begin_time']);
        }
        if( $_REQUEST['end_time'] != '' ){
            $where['end_time'] = strtotime($_REQUEST['end_time']);
        }

        if($_REQUEST['status'] != 0){
            $where['status'] = $_REQUEST['status'];
        }
        // 渠道列表
        $channel_list_info = $this->channel_service->get_list();
        $channel_list = [];
        foreach ($channel_list_info as $key => $value) {
            $channel_list[$value['id']] = $value;
        }
        // 渠道appid列表
        $channel_appid_list = $this->channel_appid_service->get_list();
        //appid和父级渠道集合列表
        $channel_lists = [];
        foreach ($channel_appid_list as $value) {
            $channel_lists[$value['id']] = [
                'name' => $value['name'],
                'channel_name' => isset($channel_list[$value['channel_id']]) ? $channel_list[$value['channel_id']]['name'] : '其他'
            ];
        }

        // 支付方式
        $options['order'] = 'id desc';
        $payment_style = $this->load->service('payment/payment_style')->arrListByPage(0, 0, [], $options);
        $payment_style_list = $payment_style['rows'];

        $additional['page'] = 1;
        $additional['size'] = 100;
        $additional['goods_info'] = true;
        $additional['address_info'] = true;

        $order_count = $this->order_service->get_order_count($where);
        while ($order_count>0){
            $order_list = $this->order_service->get_order_list($where,$additional);

            foreach($order_list as $val){
                $Orders =new \oms\Order($val);

                // 支付方式
                $payment_type = isset($payment_style_list[$val['payment_type_id']])?$payment_style_list[$val['payment_type_id']]['pay_name']:'--';

                // 渠道
                $channel_name = isset($channel_lists[$val['appid']]) ? $channel_lists[$val['appid']]['channel_name'] : '其他';
                $channel_appid_name = isset($channel_lists[$val['appid']]) ? $channel_lists[$val['appid']]['name'] : '其他';
                // 订单状态
                $status_show = $Orders->get_name();

                // 商品规格
                $specs_arr  = $val['goods_info']['specs'];
                $specs = array_column($specs_arr, 'value');
                $specs= array_values($specs);

                $create_time = $val['create_time'] != '0' ? date('Y-m-d H:i:s',$val['create_time']) : "--";
                $payment_time = $val['payment_time'] != '0' ? date('Y-m-d H:i:s',$val['payment_time']) : "--";
                //取消原因
                $reason_list = zuji\order\Reason::$_ORDER_QUESTION;
                $cancel_reason_list = $reason_list[zuji\order\Reason::ORDER_CANCEL];
                $cancel_reason = $val['reason_id'] ? $cancel_reason_list[$val['reason_id']] : $val['reason_text'];

                //身份证号区分男女
                $man_arr = [1,3,5,7,9];
                $sex_name = isset($val['cert_no'])?(in_array(substr($val['cert_no'], -2,-1),$man_arr)?'男':'女'):'--';
                $body_data = [
                    Business::getName($val['business_key']),
                    "\t" . $val['order_no'],
                    "\t" . $val['mobile'],
                    ''.$val['realname'],
                    ''.$sex_name,
                    "\t".$val['cert_no'],
                    ''.$val['yidun']['score'],
                    ''.$val['yidun']['decision_text'],
                    ''.$val['yidun']['strategies'],
                    "\t" . $create_time,

                    ''.$val['zuqi'],
                    ''.zuji\order\Order::priceFormat($val['zujin']),
                    ''.$val['credit'],
                    ''.$val['goods_name'],
                    ''.$specs[0],
                    ''.$specs[1],
                    ''.$specs[3],
                    ''.$specs[4],
                    ''.$val['amount'],
                    ''.zuji\order\Order::priceFormat($val['yiwaixian']),
                    ''.zuji\order\Order::priceFormat($val['goods_info']['yiwaixian_cost']/100),
                    ''.zuji\order\Order::priceFormat($val['yiwaixian']-$val['goods_info']['yiwaixian_cost']/100),
                    ''.$val['yajin'],
                    ''.$val['mianyajin'],
                    ''.$payment_type,
                    ''.zuji\order\Order::priceFormat($val['payment_amount']),
                    "\t" . $payment_time,
                    ''.$channel_name,
                    ''.$channel_appid_name,
                    ''.zuji\order\Order::priceFormat($val['discount_amount']),
                    ''.$status_show,
                    ''.$cancel_reason,
                ];
                $this->export_csv_wirter_row($handle, $body_data);
                unset($body_data);
            }
            $additional['page'] = $additional['page'] + 1;
            $order_count = $order_count - $additional['size'];
        }
        fclose($handle);
    }
    private function export_csv_wirter_row( $handle, $row ){
        foreach ($row as $key => $value) {
            //$row[$key] = iconv('utf-8', 'gbk', $value);
            $row[$key] = mb_convert_encoding($value,'GBK');
        }
        fputcsv($handle, $row);
    }

}