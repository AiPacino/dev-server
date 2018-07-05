<?php
/**
 * Created by PhpStorm.
 * @author: <jinliangping@huishoubao.com.cn>
 * @author: <wangjinlin@huishoubao.com.cn>
 * Date: 2018/3/24 0024-上午 11:25
 * @copyright (c) 2017, Huishoubao
 */
use zuji\order\Order;
use zuji\debug\Debug;
use zuji\debug\Location;
use oms\state\State;
hd_core::load_class('api', 'api');
class mini_order_control extends api_control
{

    public function _initialize() {
        parent::_initialize();
        if( empty($this->member) || $this->member['id']<1 ){// 必须先授权
            $member = session('__THIRD_USER_INFO__');
            if(empty($member)){
                api_resopnse('',  ApiStatus::CODE_40001,'权限拒绝', ApiSubCode::User_Unauthorized, '请授权')->flush();
                exit;
            }
        }
    }

    /**
     * 创建临时订单
     * @author: <wangjinlin@huishoubao.com.cn>
     *
     *  参数
     *      sku_id      子商品ID
     */
    public function temporary_order(){
        // 验证参数
        $params = $this->params;
        $params = filter_array($params, [
            'sku_id' => 'required', //【必须】int；子商品ID
        ]);
        if( count($params)<1 ){
            api_resopnse( [], ApiStatus::CODE_20001,'', ApiSubCode::Params_Error,'请求参数错误');
            return;
        }

        $order_no = \zuji\Business::create_business_no();

        $data = [
            'order_no' => $order_no,
            'sku_id' => intval($params['sku_id'])
        ];

        // 创建订单需要的信息
        $temporary_table = \hd_load::getInstance()->table('mini/order2_temporary');
        $result = $temporary_table->add($data);
        if($result === false){
            api_resopnse( [], ApiStatus::CODE_50000,'', '失败','保存临时订单号失败');
            return;
        }

        //可能会返回
        //sku
        //价格 这些基本信息
        api_resopnse( ['order_no' => $order_no], ApiStatus::CODE_0);
        return;
    }

    /**
     * 订单确认
     */
    public function confirmation_query(){
        $load = \hd_load::getInstance();

        $appid = api_request()->getAppid();
        $params = $this->params;
        $params = filter_array($params, [
            'zm_order_no' => 'required',	//【必须】string；芝麻订单号
            'payment_type_id' => 'required', //【必须】int；支付方式
            'out_order_no' => 'required', //【必须】int；租机订单号
            'coupon_no' => 'required'
        ]);
        set_default_value($params['coupon_no'], '');
        if( count($params)!=4 ){
            api_resopnse( [], ApiStatus::CODE_20001,'', ApiSubCode::Params_Error,'请求参数错误');
            return;
        }

        if($params['payment_type_id'] != \zuji\Config::MiniAlipay){
            api_resopnse( [], ApiStatus::CODE_50000,'', ApiSubCode::Params_Error,'不支持的支付方式');
            return;
        }

        //调用芝麻查询订单信息接口
        $order_no = $params['zm_order_no'];
        $out_order_no = $params['out_order_no'];

        //获取请求交易号
        $redis = \zuji\cache\Redis::getInstans();
        $key = 'zhima:order:confirm:orderno:'.$order_no;
        $query_param = $redis->get($key);
        if($query_param){
            $query_param = json_decode($query_param, true);
            $trade_no = $query_param['transaction_id'];
        }else{
            try{
                $trade_no = \zuji\Business::create_business_no();
            }catch (\Exception $e){
                api_resopnse( [], ApiStatus::CODE_50000,'', '订单确认', '生成交易号失败');
                return;
            }
            $redis->set($key, json_encode(['order_no' => $order_no, 'transaction_id' => $trade_no]), 86400);
        }

        //查询芝麻订单确认结果
        $zhima = new \zuji\certification\ZhimaMini();
        $mini_app_id = config('ALIPAY_MINI_APP_ID');
        $data = $zhima->getOrderConfirmResult($mini_app_id, $out_order_no, $order_no, $trade_no);
		//Debug::error(Location::L_Order,'芝麻订单确认查询结果',$data);
        if($data === false){
            $msg = get_error();
            api_resopnse( [], ApiStatus::CODE_50000,'', '查询芝麻订单确认结果失败', $msg);
            return;
        }

        $member_service = $load->service('member2/member');
        $member_table = $load->table('member2/member');
        $credit_info = [
            'certified' => 1,
            'certified_platform' => 2,  //用2代表小程序的认证
            'face' => $data['zm_face'],
            'risk' => $data['zm_risk'],
            'cert_no' => $data['cert_no'],
            'realname' => $data['name'],
            'credit_time' => time()
        ];

        /**
         * 1) __THIRD_USER_INFO__
         * 2) 注册（查询）
         * 3）绑定
         */
        // 第三方登录绑定
        $third_user_info = session('__THIRD_USER_INFO__');
        //Debug::error(Location::L_Order,'第三方授权信息',$third_user_info);
        if( $third_user_info ){
            // 根据认证信息中的用户手机号，查询用户
            $member_info = $member_table->where(['mobile' => $data['mobile']])->find();
            if( empty($member_info) ){
                // 小程序用户注册
                $user_id = $member_service->mini_register(array_merge($credit_info, [
                    'mobile' => $data['mobile'],
                    'appid' => intval($appid)
                ]));
                if($user_id === false){
                    api_resopnse( [], ApiStatus::CODE_50000,'', '订单确认查询', '用户注册失败');
                    return;
                }
                //保存用户会话
                $member_info = array(
                    'id' => $user_id,
                    'username' => $data['mobile'],
                    'mobile' => $data['mobile'],
                    'certified' => 1,
                    'certified_platform' => 2,
                    'certified_platform_name' => '芝麻认证平台',
                    'credit_time' => '',
                    'session_cache_time' => $_SERVER['SESSION_GC_MAXLIFETIME']
                );
            }
            $this->set_user($member_info);
            $this->member = $this->get_user();

            $member_service->bind_third_member($third_user_info, $this->member['id']);
        }
		//Debug::error(Location::L_Order, '芝麻小程序确认查询订单--用户回话1', $this->member);
		//Debug::error(Location::L_Order, '芝麻小程序确认查询订单--用户回话2', $_SESSION);

        // 没有登录时
        if( empty($this->member) || $this->member['id'] < 1 ){
            api_resopnse([], ApiStatus::CODE_50003,'暂未登录', '','' );
            return;
        }

        // 更新认证信息
        $user_id = $this->member['id'];
        $member_table->where(['id'=>$user_id])->save($credit_info);

        //处理用户收货地址
        $address_data = [
            'mid' => $user_id,
            'name' => $data['name'],
            'mobile' => $data['mobile'],
            'address' => $data['house'],
        ];
        $member_address_table = $load->table('member/member_address');
        $member_address_service = $load->service('member/member_address');
        $address_id = $member_address_table->edit_address($address_data);
        $address_info = $member_address_service->user_address_default($user_id);
        $address_info['address_id'] = $address_id;

        //查询临时订单信息
        $order2_temp_table = $load->table('mini/order2_temporary');
        $order_info = $order2_temp_table->where(['order_no' => $out_order_no])->find();
        if(empty($order_info)){
            api_resopnse([], ApiStatus::CODE_50003,'订单错误', '获取临时订单失败' );
            return;
        }
        $sku_id = $order_info['sku_id'];
        
        //自动领取优惠券活动--临时
        $ret = zuji\coupon\Coupon::set_coupon_user(array("user_id"=>$this->member['id'],"only_id"=>"6ed56b337dfa92d61f4ffbcd0ae39d32"));
        if($ret['code'] == 1){
            $params['coupon_no'] = $ret['coupon_no'];
        }else{
            Debug::error(Location::L_Order,'优惠券',$ret);
        }
        try {
            $business_key = \zuji\Business::BUSINESS_ZUJI;// 此处的 业务类型 作为 确认订单的默认值（该接口只读，不记录订单，用任何业务类型都不影响）
            // 订单创建器
            $orderCreaterComponnet = new \oms\OrderCreater( $business_key );

            // 用户
            $UserComponnet = new \oms\order_creater\UserComponnet($orderCreaterComponnet,$user_id);
            $orderCreaterComponnet->set_user_componnet($UserComponnet);

            // 商品
            $SkuComponnet = new \oms\order_creater\SkuComponnet($orderCreaterComponnet,$sku_id,$params['payment_type_id']);
            $orderCreaterComponnet->set_sku_componnet($SkuComponnet);
            $orderCreaterComponnet->get_sku_componnet()->discrease_yajin($data['credit_amount']*100);

            // 装饰者 信用
            $orderCreaterComponnet = new \oms\order_creater\CreditComponnet($orderCreaterComponnet);

            // 装饰着 渠道
            $orderCreaterComponnet = new \oms\order_creater\ChannelComponnet($orderCreaterComponnet, $appid);

            //装饰着 优惠券
            if($params['coupon_no']){
                $orderCreaterComponnet = new \oms\order_creater\CouponComponnet($orderCreaterComponnet, $params['coupon_no']);
            }
            // 过滤
            $b = $orderCreaterComponnet->filter();
            if( !$b ){
                $this->order_remark($user_id,$orderCreaterComponnet->get_order_creater()->get_error());
            }
            // 元数据
            $schema_data = $orderCreaterComponnet->get_data_schema();

            $result = [
                'coupon_no'         => $params['coupon_no'],
                'certified'			=> $schema_data['credit']['certified']?'Y':'N',
                'certified_platform'=> zuji\certification\Certification::getPlatformName($schema_data['credit']['certified_platform']),
                'credit'			=> ''.$schema_data['credit']['credit'],

                'fenqi_amount'  => Order::priceFormat($schema_data['sku']['zujin']/100),
                'amount'			=> Order::priceFormat($schema_data['sku']['amount']/100),	// 订单金额
                'discount_amount'	=> Order::priceFormat($schema_data['sku']['discount_amount']/100),// 优惠金额
                'all_amount'		=> Order::priceFormat($schema_data['sku']['all_amount']/100),// 商品总金额
                'buyout_price'	    => Order::priceFormat($schema_data['sku']['buyout_price']/100),	// 买断价
                'yajin'				=> Order::priceFormat($schema_data['sku']['yajin']/100),
                'mianyajin'			=> $data['credit_amount'],
                'zujin'				=> Order::priceFormat($schema_data['sku']['zujin']/100),
                'yiwaixian'			=> Order::priceFormat($schema_data['sku']['yiwaixian']/100),
                'zuqi'				=> ''.$schema_data['sku']['zuqi'],
                'chengse'			=> ''.$schema_data['sku']['chengse'],
                // 支付方式
                'payment_type_id'			 => ''.$schema_data['sku']['payment_type_id'],

                'sku_info'			=> '',
                '_order_info' => $schema_data,
                'address_info' => $address_info,
                '$b' => $b,
                '_error' => $orderCreaterComponnet->get_order_creater()->get_error(),
            ];

            $result['first_amount'] = $result['fenqi_amount']+$result['yiwaixian'];
            //0首付
            if($schema_data['coupon']['coupon_type'] == zuji\coupon\CouponStatus::CouponTypeFirstMonthRentFree){
                $first = ($result['all_amount']-$result['yiwaixian'])/$result['zuqi']-$result['discount_amount']+$result['yiwaixian'];
                $first = $first>0?$first:0;
                $result['first_amount'] =sprintf("%.2f",$first);
                $result['fenqi_amount'] = sprintf("%.2f",($result['all_amount']-$result['yiwaixian'])/$result['zuqi']);
            }
            //固定金额
            elseif($schema_data['coupon']['coupon_type'] == zuji\coupon\CouponStatus::CouponTypeFixed){
                $price = $result['all_amount']-$result['yiwaixian']-$result['discount_amount'];
                $price = $price>0?$price:0;
                $result['fenqi_amount'] = sprintf("%.2f",$price/$result['zuqi']);
                $first = $result['fenqi_amount']+$result['yiwaixian'];
                $result['first_amount'] =sprintf("%.2f",$first);
            }
            //Debug::error(Location::L_Order,'订单确认查询',$result);
            api_resopnse( $result, ApiStatus::CODE_0);
            return;

        } catch (\oms\order_creater\ComponnetException $exc) {
            api_resopnse( [], ApiStatus::CODE_20001,'', ApiSubCode::Sku_Error_Sku_id,$exc->getMessage());
            return;
        }

    }

    /**
     * 小程序下单接口
     */
    public function create(){
		//Debug::error(Location::L_Order, '小程序下单接口', $this->params );
        $app_id = api_request()->getAppid();
        $params   = $this->params;
        $params = filter_array($params, [
            'order_no' => 'required',
            'payment_type_id' => 'required', //【必须】int；支付方式
            'address_id' => 'required|is_id',	//【必须】int；用户收货地址ID
            'coupon_no'=>'required',  //【可选】string;优惠券编号
        ]);

        if(empty($params['address_id']) ){
			Debug::error(Location::L_Order, '下单错误--支付方式',['msg'=>'收货地址错误'] );
            api_resopnse( [], ApiStatus::CODE_20001,'参数错误', ApiSubCode::Address_Error_Address_id,'收货地址错误');
            return;
        }
        $address_id = $params['address_id'];
        if( $params['payment_type_id'] != \zuji\Config::MiniAlipay ){
			Debug::error(Location::L_Order, '下单错误--支付方式',['msg'=>'不支持的支付方式'] );
            api_resopnse( [], ApiStatus::CODE_50000,'', ApiSubCode::Params_Error,'不支持的支付方式');
            return;
        }

        // ？这里的缓存做什么的？ 暂时不确定，先注释掉 liuhongxing
//        $redis_key = $this->member['id']."-".date("YmdHi");
//        $redis = \zuji\cache\Redis::getInstans();
//        $result = $redis->get($redis_key);
//        if($result){
//			Debug::error(Location::L_Order, '下单错误--redis缓存',['msg'=>$result] );
//            api_resopnse( [], ApiStatus::CODE_20001,'正在处理中');
//            return;
//        }
//        else{
//            $redis->set($redis_key,json_encode(['user_id'=>$this->member['id']]),60);
//        }

        // 订单编号
        $order_no = $params['order_no'];

        //查询临时订单信息
        $order2_temp_table = $this->load->table('mini/order2_temporary');
        $order_info = $order2_temp_table->where(['order_no' => $order_no])->find();
        if(empty($order_info)){
			Debug::error(Location::L_Order, '下单错误--临时订单',['msg'=>$order_info] );
            api_resopnse([], ApiStatus::CODE_50003,'订单错误', '获取临时订单失败' );
            return;
        }
        $sku_id = $order_info['sku_id'];
        $user_id = $this->member['id'];


        $order_service = $this->load->service('order2/order');
        $order2_info = $order_service->get_order_info(['order_no' => $order_no]);
        if($order2_info){
			Debug::error(Location::L_Order, '下单错误--订单号已使用',['msg'=>$order2_info] );
            api_resopnse([], ApiStatus::CODE_50003,'订单错误', '该订单号已存在！' );
            return;
        }

        $zhima_order = $this->load->service('order2/zhima_certification');
        $zhima_order_info = $zhima_order->field('credit_amount')->where(['out_order_no' => $order_no])->find();
        if($zhima_order_info){
			//Debug::error(Location::L_Order, '下单错误--订单号已确认',['order_no'=>$order_no, 'msg'=>$zhima_order_info] );
            //api_resopnse([], ApiStatus::CODE_50003,'订单错误', '该订单号已存在！' );
            //return;
        }

        //开启事务
        $b = $order_service->startTrans();
        if( !$b ){
			Debug::error(Location::L_Order, '事务失败','' );
            api_resopnse( [], ApiStatus::CODE_40003,'事务失败', '','服务器繁忙，请稍后重试...');
            return;
        }

        try {
            $business_key = \zuji\Business::BUSINESS_ZUJI;// 此处的 业务类型 作为 确认订单的默认值（该接口只读，不记录订单，用任何业务类型都不影响）
            // 订单创建器
            $orderCreaterComponnet = new \oms\OrderCreater( $business_key,$order_no );

            // 用户
            $UserComponnet = new \oms\order_creater\UserComponnet($orderCreaterComponnet,$user_id);
            $orderCreaterComponnet->set_user_componnet($UserComponnet);

            // 商品
            $SkuComponnet = new \oms\order_creater\SkuComponnet($orderCreaterComponnet,$sku_id,$params['payment_type_id']);
            $orderCreaterComponnet->set_sku_componnet($SkuComponnet);
            $orderCreaterComponnet->get_sku_componnet()->discrease_yajin($zhima_order_info['credit_amount']*100);

            // 装饰者 信用
            $orderCreaterComponnet = new \oms\order_creater\CreditComponnet($orderCreaterComponnet);

            // 装饰者 风险
            $orderCreaterComponnet = new \oms\order_creater\YidunComponnet($orderCreaterComponnet,$address_id);

            // 装饰者 收货地址
            $orderCreaterComponnet = new \oms\order_creater\AddressComponnet($orderCreaterComponnet,$address_id);

            // 装饰着 渠道
            $orderCreaterComponnet = new \oms\order_creater\ChannelComponnet($orderCreaterComponnet, $app_id);

            //装饰着 优惠券
            if($params['coupon_no']){
                $orderCreaterComponnet = new \oms\order_creater\CouponComponnet($orderCreaterComponnet, $params['coupon_no']);
            }

            $b = $orderCreaterComponnet->filter();
            if( !$b ){
                $order_service->rollback();
                Debug::error(Location::L_Order,'创建订单组件过滤失败',$orderCreaterComponnet->get_order_creater()->get_error());
                // 无法下单原因
                $this->order_remark($user_id,$orderCreaterComponnet->get_order_creater()->get_error());
//				var_dump( $orderCreaterComponnet->get_order_creater()->get_error() );
                api_resopnse( [], ApiStatus::CODE_50002,'', '', $orderCreaterComponnet->get_order_creater()->get_error());
                return;
            }

            // 元数据
            $schema_data = $orderCreaterComponnet->get_data_schema();
            $b = $orderCreaterComponnet->create();
            //创建成功组装数据返回结果
            if(!$b){
                $order_service->rollback();
                $error = $orderCreaterComponnet->get_order_creater()->get_error();
                // 无法下单原因
                $this->order_remark($user_id,$error);
                Debug::error(Location::L_Order, '下单失败', ['error'=>$error,'_data_schema'=>$schema_data]);
                api_resopnse( [], ApiStatus::CODE_50003, get_error(),  ApiSubCode::Order_Creation_Failed, '服务器繁忙，请稍后重试...');
                return;
            }
            $order_id = $orderCreaterComponnet->get_order_creater()->get_order_id();
            $order_no = $orderCreaterComponnet->get_order_creater()->get_order_no();

            // 记录操作日志
            $this->add_order_log($schema_data['user']['user_id'],$schema_data['user']['mobile'],$order_no,'创建订单','');

            //订单分期
            $this->instalment = $this->load->service("order2/instalment");
            $ret = $this->instalment->create(['order_id'=>$order_id]);
            if(!$ret){
                $order_service->rollback();
				Debug::error(Location::L_Order, '芝麻小程序下单失败','分期创建失败' );
                api_resopnse( [], ApiStatus::CODE_50003, '下单失败',  ApiSubCode::Order_Creation_Failed, '服务器繁忙，请稍后重试......');
                return;
            }
            $b = $order_service->commit();
            if( !$b ){
				Debug::error(Location::L_Order, '芝麻小程序下单失败','事务失败' );
                api_resopnse( [], ApiStatus::CODE_50003, '事务失败',  ApiSubCode::Order_Creation_Failed, '服务器繁忙，请稍后重试...');
                return;
            }
            // 清空 无法下单原因
            $this->order_remark($user_id,'');

            //创建订单后 发送支付短信。
            $result = ['auth_token'=>  $this->auth_token,];
            $sms = new \zuji\sms\HsbSms();
            // $b = $sms->send_sm($schema_data['user']['mobile'],'SMS_113450944',[
            //     'goodsName' => $schema_data['sku']['sku_name'],    // 传递参数
            // ],$order_no);

            $b = $sms->send_sm($schema_data['user']['mobile'],'hsb_sms_7eb75f',[
                'zidongQuxiao' => '半',    // 传递参数
            ],$order_no);
            if (!$b) {
                Debug::error(Location::L_Order,'线上下单短信',$b);
            }

            $result = [
                'order_id'			=> $order_id,
                'order_no'			=> $order_no,
                'certified'			=> $schema_data['credit']['certified']?'Y':'N',
                'certified_platform'=> zuji\certification\Certification::getPlatformName($schema_data['credit']['certified_platform']),
                'credit'			=> $schema_data['credit']['credit'],
                'credit_status'		=> $schema_data['sku']['yajin']==0?'Y':'N',  // 是否免押金
                'amount'			=> Order::priceFormat($schema_data['sku']['amount']/100),	// 订单金额
                'discount_amount'	=> Order::priceFormat($schema_data['sku']['discount_amount']/100),// 优惠金额
                'all_amount'		=> Order::priceFormat($schema_data['sku']['all_amount']/100),// 商品总金额
                'buyout_price'	    => Order::priceFormat($schema_data['sku']['buyout_price']/100),	// 买断价
                'yajin'				=> Order::priceFormat($schema_data['sku']['yajin']/100),
                'mianyajin'			=> Order::priceFormat($schema_data['sku']['mianyajin']/100),
                'zujin'				=> Order::priceFormat($schema_data['sku']['zujin']/100),
                'yiwaixian'			=> Order::priceFormat($schema_data['sku']['yiwaixian']/100),
                'zuqi'				=> $schema_data['sku']['zuqi'],
                'chengse'			=> $schema_data['sku']['chengse'],
                'payment_type_id'				=> $schema_data['sku']['payment_type_id'],
                'sku_info'			=> '',
                '_order_info' => $schema_data,
            ];
            //Debug::error(Location::L_Order,'创建订单成功返回参数',$result);
            api_resopnse( ['order_info'=>$result], ApiStatus::CODE_0);
            return;

        } catch (\oms\order_creater\ComponnetException $exc) {
            $order_service->rollback();
			Debug::error(Location::L_Order, '芝麻小程序下单失败',$exc->getMessage() );
            api_resopnse( [], ApiStatus::CODE_50003,'下单失败', ApiSubCode::Order_Creation_Failed,$exc->getMessage());
            return;
        } catch (\Exception $exc){
            $order_service->rollback();
			Debug::error(Location::L_Order, '芝麻小程序下单失败',$exc->getMessage() );
            api_resopnse( [], ApiStatus::CODE_50003, '下单失败',  ApiSubCode::Order_Creation_Failed, '服务器繁忙，请稍后重试...');
            return;
        }
    }

    /**
     * 前段确认订单同步通知接口
     */
    public function front_transition(){
        // 验证参数
        $params = $this->params;
        $params = filter_array($params, [
            'success' => 'required', //【必须】boolean；是否成功
            'out_order_no' => 'required', //【必须】string；商户订单号
            'order_status' => 'required', //【必须】string；下单状态 SUCCESS成功,FAIL失败,UNKOWN处理中
            'order_create_time' => 'required', //【必须】string；订单创建时间
        ]);
        if( count($params)<4 ){
            api_resopnse( [], ApiStatus::CODE_20001,'', ApiSubCode::Params_Error,'请求参数错误');
            return;
        }

        //订单加锁 如果状态已变更 不需要加锁
        $this->order = $this->load->service('order2/order');
        $where['order_no'] = $params['out_order_no'];
        $where['status'] = State::PaymentSuccess;
        //获取订单信息
        $order = $this->order->get_order_info($where);
        if(!$order){
		    \zuji\OrderLocker::lock($params['out_order_no'], \zuji\OrderLocker::ZMminiPaying);
        }

        // 验签 验证 通过 修改数据
        if($params['order_status'] == 'SUCCESS'){
            api_resopnse( [], ApiStatus::CODE_0);
            return;
        }elseif ($params['order_status'] == 'UNKOWN'){
            //处理中不做处理
            api_resopnse( [], ApiStatus::CODE_50003,'', '处理中','小程序处理中');
            return;
        }else{
            //处理中不做处理
            api_resopnse( [], ApiStatus::CODE_50003,'', '确认订单失败','小程序确认订单失败');
            return;
        }
    }

    /**
     * @param $user_id
     * @param $order_remark
     */
    protected function order_remark($user_id,$order_remark){
        // 更新（无法下单原因）
        $member_table = \hd_load::getInstance()->table('member/member');
        $member_table->where(['id'=> $user_id])->save(['order_remark'=>$order_remark]);
    }

    //订单取消接口
	//(暂时取消芝麻订单取消接口，请求映射到原 订单取消接口上 2018-04-01 liuhongxing)
//    public function zhima_cancel()
//    {
//        $params = $this->params;
//        //验证参数
//        $params = filter_array($params,[
//            'order_no' => 'required',
//            'reason_id' => 'required|is_id',
//        ]);
//        if (empty($params['order_no'])) {
//            api_resopnse( [], ApiStatus::CODE_20001,'订单编号必须',ApiSubCode::Order_Error_Order_no,'');
//            return;
//        }
//        /*****************依赖服务************/
//        $this->order = $this->load->service('order2/order');
//        $this->zhima_order = $this->load->service('order2/zhima_order_confirm');
//        $where['order_no'] = $params['order_no'];
//        //获取订单信息
//        $order = $this->order->get_order_info($where,['lock'=>true]);
//        if ($order ===false) {
//            api_resopnse( [], ApiStatus::CODE_50003,'当前订单不存在');
//            return;
//        }
//        if( !($order['status'] == State::OrderCreated || $order['status'] == State::PaymentSuccess) ){
//            api_resopnse( [], ApiStatus::CODE_50003,'该订单状态不支持取消订单');
//            return;
//        }
//
//        //订单取消判断是否在阻塞状态
//        $redis_result = \zuji\OrderLocker::isLocked($params['order_no']);
//        if($redis_result){
//            api_resopnse( [], ApiStatus::CODE_20001,'当前订单不可取消',ApiSubCode::Retrun_Error_Reason_id,'');
//            return;
//        }
//        //获取芝麻订单信息
//        $zhima_order_info = $this->zhima_order->get_zhima_order_one( ['out_order_no'=>$order['order_no']] );
//        if ($zhima_order_info === false) {
//            api_resopnse( [], ApiStatus::CODE_50003,'芝麻订单不存在');
//            return;
//        }
//		
//        //芝麻小程序取消订单
//        $arr['out_order_no'] = $zhima_order_info['out_order_no'];
//        $arr['zm_order_no'] = $zhima_order_info['order_no'];
//        $arr['out_trans_no'] = '';
//        //获取取消操作原因
//        if($params['reason_id'] ==0){
//            $beizhu= $params['reason_text'];
//        }else{
//            $beizhu=\zuji\order\Reason::$_ORDER_QUESTION[\zuji\order\Reason::ORDER_CANCEL][$params['reason_id']];
//        }
//        $arr['remark'] = $beizhu;
//		//
//        $Withhold = new \zhima\Withhold();
//        $b = $Withhold->OrderCancel( $arr );
//        //判断请求发送是否成功
//        if($b === false){
//            api_resopnse( [], ApiStatus::CODE_50003,'取消失败：'.$Withhold->getError());
//            return;
//        }
//        $orderObj = new oms\Order($order);
//        $this->log($orderObj,"取消订单",$params['remark']);
//        api_resopnse( [], ApiStatus::CODE_0);
//    }

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