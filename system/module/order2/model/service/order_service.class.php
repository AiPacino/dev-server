<?php
use zuji\order\OrderStatus;
use zuji\order\PaymentStatus;
use zuji\order\DeliveryStatus;
use zuji\order\ReturnStatus;
use zuji\order\ReceiveStatus;
use zuji\order\EvaluationStatus;
use zuji\order\RefundStatus;
use zuji\order\Order;
use zuji\debug\Location;
use zuji\debug\Debug;
use zuji\Business;
use oms\state\State;
use zuji\order\Status;

/**
 * 		订单服务层
 */
class order_service extends service {

    //支付渠道
    public $pay_type = [
        1=>'Alipay',
        2=>'WXPay'
    ];

    
    protected $where = array();

    public function _initialize() {
        /* 实例化数据层 */
        $this->order2_table = $this->load->table('order2/order2');
        $this->order2_goods_table = $this->load->table('order2/order2_goods');
        $this->order2_address_table = $this->load->table('order2/order2_address');
        $this->order2_number = $this->load->table('order2/order2_number');
        $this->order2_follow = $this->load->table('order2/order2_follow');
        $this->order2_yidun = $this->load->table('order2/order2_yidun');
        $this->service_order_log = $this->load->service('order2/order_log');
    }

    /**
     * 订单退款函数
     * 1,更新订单
     * 2,关闭服务（如果服务存在）
     * 3,租机业务恢复库存
     * 4, 退款次数判断（封锁用户账号）
     * @param data[
     *  'order_no'=>'',//必须 订单编号
     * ]
     * @return boolean
     */
    public function order_refund($data){
        $order_service =$this->load->service('order2/order');
        $order_table = $this->load->table('order2/order2');
        $data = filter_array($data, [
            'order_no'=>'required',
        ]);
        if( count($data)<1 ){
            set_error("参数错误");
            return false;
        }
        $order_info =$order_service->get_order_info(['order_no'=>$data['order_no']]);

        //更新订单
        $order_data = [
            'status' =>State::OrderRefunded,
            'update_time'=> time(),//更新时间
            'refund_status'=> RefundStatus::RefundSuccessful,
            'order_status'=>OrderStatus::OrderCanceled,
            'refund_amount' => $order_info['amount'],
            'refund_time' => time()
        ];
        $b =$order_table->where(['order_id'=>$order_info['order_id']])->save($order_data);
        if(!$b){
            set_error("更新订单状态失败");
            return false;
        }

        //修改服务单状态
        //退款成功时  如果有服务 取消服务
        $service_table =$this->load->table('order2/order2_service');
        $service_service=$this->load->service('order2/service');
        $service_info =$service_service->get_service_info(['order_id'=>$order_info['order_id']]);
        $service_data =[
            'update_time'=>time(),
            'service_status'=>\zuji\order\ServiceStatus::ServiceCancel,
        ];
        if($service_info){
            $b =$service_table->where(['order_id'=>$order_info['order_id']])->save($service_data);
            if(!$b){
                set_error("同步到服务单状态失败");
                return false;
            }
        }
        // 租机业务退款，则 恢复库存数量
        if($order_info['business_key'] == Business::BUSINESS_ZUJI){

            $goods_info =$order_service->get_goods_info($order_info['goods_id']);
            if(!$goods_info){
                set_error("商品信息不存在");
                return false;
            }
            $sku_service =$this->load->service('goods2/goods_sku');
            $sku_info =$sku_service->api_get_info($goods_info['sku_id'],"");
            //sku库存 +1
            $sku_table =$this->load->table('goods2/goods_sku');
            $spu_table=$this->load->table('goods2/goods_spu');

            $sku_data['sku_id'] =$goods_info['sku_id'];
            $sku_data['number'] = ['exp','number+1'];
            $add_sku =$sku_table->save($sku_data);
            if(!$add_sku){
                set_error("恢复商品库存失败");
                return false;
            }
            $spu_data['id'] =$sku_info['spu_id'];
            $spu_data['sku_total'] = ['exp','sku_total+1'];
            $add_spu =$spu_table->save($spu_data);
            if(!$add_spu){
                set_error("恢复总库存失败");
                return false;
            }
        }
        //查询 如果退款/已解冻 次数过多 封闭账号
        $status =State::OrderRefunded.",".State::FundsThawed;
        $where =[
            'user_id'=>$order_info['user_id'],
            'new_status'=>$status,
            'begin_time'=>mktime(0, 0 , 0,date("m"),date("d")-date("w")+1,date("Y")),
            'end_time'=>mktime(23,59,59,date("m"),date("d")-date("w")+7,date("Y")),
        ];
        $user_follow = $order_service->get_follow_user_list($where);

        if(count($user_follow) >= Config::Order_Refund_Num){
            $member_table = $this->load->table('member/member');
            $b =$member_table->where(array('id'=>$order_info['user_id']))->save(['block'=>1]);
            if(!$b){
                set_error("封锁账户失败");
                return false;
            }
        }
        return true;
    }

    
    
    /**
     * 检验订单条件 用户信息
     * @param array $user_info
     * @return boolean	true：允许；false：不允许
     */
    public function verifyUserCondition( $user_info ){
	
    }
    /**
     * 检验订单条件 用户信息
     * 非正常情况，debug记录
     * @param array $sku_info
     * []
     * @return boolean	true：允许；false：不允许
     */
    public function verifySkuCondition( $sku_params ){
	$sku_info = filter_array($sku_params, [
	    'spu_id' => 'required|is_id',
	    'sku_id' => 'required|is_id',
	    'sku_name' => 'required',
	    'brand_id' => 'required|is_id',
	    'category_id' => 'required|is_id',
	    'status' => 'required',
	    'number' => 'required',
	    'zujin' => 'required|is_price',
	    'yajin' => 'required|is_price',	// 押金
	    'chengse' => 'required',
	    'zuqi' => 'required',
	    'yiwaixian' => 'required|is_price',
	    'amount' => 'required|is_price',
	    'spec' => 'required',// 规格列表
	]);
	$_debug_data = [
	    'sku_params' => $sku_params,
	    'filter_result' => $sku_info,
	];
	
	// 必要参数个数判断
	if( count($sku_info)!=14 ){
	    set_error('服务器繁忙，请稍后重试...');
	    Debug::error( Location::L_Order, 'sku(#'.$sku_info['sku_id'].')[SKU参数]不完整',$_debug_data );
	    return false;
	}
	
	//判断商品是否下架
	if($sku_info['status']!=1){
	    set_error('商品已下架');
	    return;
	}
	// 判断库存大于0
	if( $sku_info['number'] < 1){
	    set_error('暂无库存');
	    Debug::error( Location::L_Order, 'sku(#'.$sku_info['sku_id'].')[库存]为0',$_debug_data );
	    return;
	}
	// sku 必须有 成色
	if( $sku_info['chengse']<1 ){
	    set_error('服务器繁忙，请稍后重试...');
	    Debug::error( Location::L_Order, 'sku(#'.$sku_info['sku_id'].')[成色]错误',$_debug_data );
	    return false;
	}
	// sku 必须有 租期
	if( $sku_info['zuqi']<1 ){
	    set_error('服务器繁忙，请稍后重试...');
	    Debug::error( Location::L_Order, 'sku(#'.$sku_info['sku_id'].')[租期]错误',$_debug_data );
	    return false;
	}
	// sku 必须有 月租金, 且不可低于系统设置的最低月租金
	$zujin_min_price = config('ZUJIN_MIN_PRICE');// 最低月租金
	if( $sku_info['zujin'] < $zujin_min_price ){
	    set_error('服务器繁忙，请稍后重试...');
	    $_debug_data['ZUJIN_MIN_PRICE'] = $zujin_min_price;
	    Debug::error( Location::L_Order, 'sku(#'.$sku_info['sku_id'].')月租金低于系统最小值',$_debug_data );
            api_resopnse( [], ApiStatus::CODE_50002,'SKU错误',ApiSubCode::Sku_Zujin_Error,'服务器繁忙，请稍后重试...');
	    return false;
	}
	// sku 必须有 押金
	if( $sku_info['yajin']<=0 ){
	    set_error('服务器繁忙，请稍后重试...');
	    Debug::error( Location::L_Order, 'sku(#'.$sku_info['sku_id'].')[押金]错误',$_debug_data );
	    return false;
	}
	// 规格
	$must_spec_id_list = zuji\Goods::getMustSpecIdList();
	$spec_ids = [];
	foreach( $sku_info['spec'] as $it ){
	    $spec_ids[] = $it['id'];
	}
	$spec_id_diff = array_diff($must_spec_id_list, $spec_ids);
	if( count($spec_id_diff)>0 ){
	    set_error('服务器繁忙，请稍后重试...');
	    $_debug_data['must_spec_id_list'] = $must_spec_id_list;
	    Debug::error( Location::L_Order, 'sku(#'.$sku_info['sku_id'].')[规格]错误',$_debug_data);
	    return false;
	}
	
	return true;
    }
    /**
     * 检验订单条件 收货地址
     * @param array $address_info
     * @return boolean	true：允许；false：不允许
     */
    public function verifyAddressCondition( $address_info ){
	
    }
    
    /**
     * 查询近七天匹配度查询
     * @param $data
     *      'order_id'=>'' //【必须】当前订单ID
     *      'user_id' =>'' //【必须】下单用户
     *      'create_time'=>''//【必须】下单时间
     *      'address'=>''//【必须】 下单地址 省 市 区 + 详细地址
	 */
	
    public function similar_order_address($data){
        //查询近7天 订单地址相似度匹配>70% 的
        $this->district_service = $this->load->service('admin/district');
        $this->order2_similar_address = $this->load->table('order2/order2_similar_address');
        $start = $data['create_time']-2*86400;
        $end   = $data['create_time'];
        $seven = array('BETWEEN',array($start ,$end));
        $order_list = $this->get_order_list(
                ['user_id_not_in'=>$data['user_id'],
                'create_time'=>$seven,
                'status' =>[State::OrderConfirmed,State::FundsAuthorized,State::OrderDeliveryed]],
                ['address_info'=>true,
                'size'=>'all']
        );
        $order =[];
        foreach( $order_list as &$item ){
            $seven_order = $this->district_service->get_address_detail($item['address_info']);
            similar_text($data['address'],$seven_order,$percent);
            if($percent >70){
                $save_data =[
                    'order_id'=>$item['order_id'],
                    'order_no'=>$item['order_no'],
                    'user_id'=>$data['user_id'],
                    'percent'=>$percent,
                ];
                $this->order2_similar_address->add($save_data);
                $order[$item['order_id']] =$item['order_no'];
            }
        }

        $this->order2_table->save(['order_id'=>$data['order_id'],'similar_status'=>1]);
        return $order;
    }
	
    /**
     *  创建订单状态流
     * @param $data array
     * [
     *      'order_id' => '',	      //【必须】  订单ID
     *      'step_status' =>'',       //【必须】  阶段值
     *      'follow_status' => '',	  //【必须】  跟踪阶段状态
     *      'order_status' => '',	  //【必须】  订单状态
     *      'admin_id' => '',	      //【必须】  操作员ID
     * ]
     * @return mixed	false：创建失败；int:主键；创建成功
     * @author wuhaiyan<wuhaiyan@huishouhao.com.cn>
     */
//
//    public function create_follow($data){
//        $data = filter_array($data, [
//            'order_id' => 'required|is_id',
//            'step_status' => 'required|is_int',
//            'follow_status' => 'required|is_int',
//            'order_status' => 'required|is_int',
//            'admin_id' => 'required|is_int',
//        ]);
//        if( count($data)<2 ){
//            set_error("参数错误");
//            return false;
//        }
//        $data['create_time'] =time();
//        $follow_id = $this->order2_follow->create_follow($data);
//
//        if(!$follow_id){
//            set_error("创建订单流失败");
//            Debug::error(Location::L_Order, '创建订单流失败', $data);
//            return false;
//        }
//        return $follow_id;
//    }
    /**
     * 根据订单ID获取订单状态流
     * @param int $order_id	    订单ID
     * @return mixed	false：查询失败；array：订单状态信息
     * [
     *      'follow_id'=>'',          //【必须】  主键ID
     *      'order_id' => '',	      //【必须】  订单ID
     *      'step_status' =>'',       //【必须】  阶段值
     *      'follow_status' => '',	  //【必须】   跟踪阶段状态
     *      'order_status' => '',	  //【必须】  订单状态
     *      'create_time' => '',      //【必须】  创建时间
     *      'admin_id' => '',	      //【必须】  操作员ID
     *      'old_status'=>'',         //【必须】   旧的状态值
     *      'new_status'=>'',         //【必须】   新的状态值
     * ]
     * @author wuhaiyan<wuhaiyan@huishouhao.com.cn>
     */
    public function get_follow_by_order_id($order_id) {
        if($order_id <1){
            set_error("订单ID错误");
            return false;
        }
        return $this->order2_follow->get_follow_by_order_id($order_id);
    }

    /**
     * 获取符合条件的状态流记录数
     * @param   array	$where
     * @return int 查询总数
     */
    public function get_follow_user_list($where=[]){
        // 参数过滤
        $where = filter_array($where, [
            'user_id'=>'required',
            'new_status' => 'required',
            'begin_time'=>'required',
            'end_time'=>'required',
        ]);
        if(count($where) != 4){
            set_error("参数错误");
            return false;
        }
        $sql ="select * from zuji_order2 t1 right join zuji_order2_follow t2 on t1.order_id = t2.order_id where t1.user_id=".$where['user_id']." and (t2.create_time between ".$where['begin_time']." and ".$where['end_time'].") and t2.new_status in (".$where['new_status'].")";
        return $this->order2_follow->query($sql);
    }

    /**
     * 设置订单超时
     * @param int $order_id 【必须】订单ID
     * @author wuhaiyan<wuhaiyan@huishouhao.com.cn>
     */
    public function order_timeout($order_id){
        if($order_id <1){
            set_error("订单ID错误");
            return false;
        }
        $data = [
            'update_time' =>time(),
            'order_status'=>OrderStatus::OrderTimeout,
        ]; 
        return $this->order2_table->order_timeout($order_id,$data);
    }

    /**
     * 获取某用户是否有其他有效订单 
     * @param int $user_id 【必须】 用户ID
     * @return boolean  Y:有开启的订单(count大于0)；  N：没有开启的订单； false：查询错误
     * @author wuhaiyan<wuhaiyan@huishouhao.com.cn>
     */
    public function has_open_order($user_id, $cert_no){
        if(!isset($user_id) || $user_id <0){
            set_error("参数错误");
            return false;
        }
        return $this->order2_table->has_open_order($user_id, $cert_no);
    }

    /**
     * 创建线下门店订单
     * @param array $data        【必须】
     * [
     *      'business_key'=>'',  【必须】业务类型值 business_key = BUSINESS_STORE
     *      'appid'=>'',         【必须】线下门店appID
     *      'pay_channel_id'=>'' 【可选】支付渠道ID
     * ]
     * @param array $user_info_data 【必须】用户ID+手机号
     * [
     *	    'user_id' => '',	【必须】int 用户ID
     *	    'mobile' => '',	          【必须】string 手机号
     *	    'certified_platform' => '',	  【必须】int 认证平台
     *	    'credit' => '',	          【必须】信用值（根据具体认证平台不同）
     *      'realname'=>''      【必须】真实姓名
     *      'cert_no'=>''       【必须 】身份证号
     * ]
     * @param array $sku_info_data	    【必须】
     * [
     *	    'sku_id' => '',	                 【必须】int SKU ID
     *	    'spu_id' => '',	                 【必须】int SPU ID
     *	    'brand_id' => '',	       【必须】int 品牌ID
     *	    'category_id' => '',   【必须】int 分类ID
     *	    'sku_name' => '',	       【必须】string  商品名称
     *	    'specs' => '',	                  【必须】string  商品规格描述
     *      'amount' => '',        【必须】int 应付金额单位元
     *	    'chengse' => '',	        【必须】string 商品成色
     *	    'zuqi' => '',	                   【必须】int 租期； 12:12期；6:6期；3:3期
     *	    'zujin' => '',	                   【必须】int 月租金额（单位：元）
     * 	    'yiwaixian' => '',	    【必须】int	意外保险金（单位：元）
     * 	    'yajin' => '',	               【必须】int	实际押金（单位：元）
     * 	    'mianyajin' => '',	    【必须】int	实际免押金额（单位：元）
     * ]
     * @return int  false：创建订单失败； array：订单创建成功，返回订单信息
     * @author wuhaiyan <wuhaiyan@huishouhao.com.cn>
     */
    public function store_create_order( $data,$user_info_data=[], $sku_info_data=[]){
        // 参数过滤
        $data = filter_array($data, [
            'business_key'=>'required|is_int',
            'appid'=>'required|is_int',
            'pay_channel_id'=>'required',
        ]);
        if(count($data)<2){
            set_error("参数错误");
            return false;
        }
        if($data['business_key']!=Business::BUSINESS_STORE){
            set_error("business_key 错误");
            return false;
        }
        
        $user_info = filter_array($user_info_data,[
            'user_id' => 'required|is_id',
            'mobile' => 'required|is_mobile',
            'certified_platform' => 'required',
            'credit' => 'required',
            'realname'=>'required',
            'cert_no' => 'required',
        ]);
        if( count($user_info)!=6 ){
            set_error('用户信息，参数错误');
    	    if( !isset($user_info['user_id']) ){
    		    Debug::error(Location::L_Order, '创建订单[用户ID]错误', $user_info_data);
    	    }
    	    if( !isset($user_info['mobile']) ){
    		    Debug::error(Location::L_Order, '创建订单[用户手机号]错误', $user_info_data);
    	    }
    	    if( !isset($user_info['certified_platform']) ){
    		    Debug::error(Location::L_Order, '创建订单[用户认证平台]错误', $user_info_data);
    	    }
    	    if( !isset($user_info['credit']) ){
    		    Debug::error(Location::L_Order, '创建订单[用户信用分]错误', $user_info_data);
    	    }
    	    if( !isset($user_info['realname']) ){
    	        Debug::error(Location::L_Order, '创建订单[用户真实姓名]错误', $user_info_data);
    	    }
    	    if( !isset($user_info['cert_no']) ){
    	        Debug::error(Location::L_Order, '创建订单[身份证号]错误', $user_info_data);
    	    }
            return false;
        }
	
        $sku_info = filter_array($sku_info_data,[
            'sku_id' => 'required|is_id',
            'spu_id' => 'required|is_id',
            'brand_id' => 'required|is_id',
            'category_id' => 'required|is_id',
            'sku_name' => 'required',
            'specs' => 'required',
            'amount' => 'required|is_price',
            'chengse' => 'required',
            'zuqi' => 'required',
            'zujin' => 'required|is_price',
            'yiwaixian' => 'required|is_price',
            'yajin' => 'required|is_price',
            'mianyajin' => 'required|is_price',
        ]);

        //格式化specs
        if(is_string($sku_info['specs'])){
            $specs = json_decode($sku_info['specs'], true);
            $sku_info['specs'] = \zuji\order\goods\Specifications::input_format($specs);
        }elseif (is_array($sku_info['specs'])){
            $sku_info['specs'] = \zuji\order\goods\Specifications::input_format($sku_info['specs']);
        }

        if( count($sku_info)!=13 ){
            set_error('商品信息，参数错误');
    	    if( !isset($sku_info['sku_id']) ){
    		Debug::error(Location::L_Order, '创建订单[skuID]错误', $sku_info_data);
    	    }
    	    if( !isset($sku_info['spu_id']) ){
    		Debug::error(Location::L_Order, '创建订单[spuID]错误', $sku_info_data);
    	    }
    	    if( !isset($sku_info['brand_id']) ){
    		Debug::error(Location::L_Order, '创建订单[品牌ID]错误', $sku_info_data);
    	    }
    	    if( !isset($sku_info['category_id']) ){
    		Debug::error(Location::L_Order, '创建订单[分类ID]错误', $sku_info_data);
    	    }
    	    if( !isset($sku_info['sku_name']) ){
    		Debug::error(Location::L_Order, '创建订单[spu名称]错误', $sku_info_data);
    	    }
    	    if( !isset($sku_info['specs']) ){
    		Debug::error(Location::L_Order, '创建订单[spu规格]错误', $sku_info_data);
    	    }
    	    if( !isset($sku_info['amount']) ){
    		Debug::error(Location::L_Order, '创建订单[商品总金额]错误', $sku_info_data);
    	    }
    	    if( !isset($sku_info['chengse']) ){
    		Debug::error(Location::L_Order, '创建订单[sku成色]错误', $sku_info_data);
    	    }
    	    if( !isset($sku_info['zuqi']) ){
    		Debug::error(Location::L_Order, '创建订单[sku成色]错误', $sku_info_data);
    	    }
    	    if( !isset($sku_info['zujin']) ){
    		Debug::error(Location::L_Order, '创建订单[sku成色]错误', $sku_info_data);
    	    }
    	    if( !isset($sku_info['yiwaixian']) ){
    		Debug::error(Location::L_Order, '创建订单[sku成色]错误', $sku_info_data);
    	    }
    	    if( !isset($sku_info['yajin']) ){
    		Debug::error(Location::L_Order, '创建订单[押金]错误', $sku_info_data);
    	    }
    	    if( !isset($sku_info['mianyajin']) ){
    		Debug::error(Location::L_Order, '创建订单[免押金额]错误', $sku_info_data);
    	    }
            // 失败
            return false;
        }

        try {
            // 保存
            $order_data = [
                'order_status' => zuji\order\OrderStatus::OrderCreated, // 订单已创建
                'step_status' => zuji\order\OrderStatus::StepCreated,   // 订单创建阶段
                'business_key' => Business::BUSINESS_STORE,        // 业务类型值
                'status'=>State::OrderCreated,
                'order_no' => \zuji\Business::create_business_no(),  // 编号
                'user_id' => $user_info['user_id'],
                'mobile' => $user_info['mobile'],
                'certified_platform' => $user_info['certified_platform'],
                'credit' => $user_info['credit'],
                'realname' =>$user_info['realname'],
                'cert_no' =>$user_info['cert_no'],
                'chengse' => $sku_info['chengse'],
                'zuqi' => $sku_info['zuqi'],
                'amount' => $sku_info['amount']*100,
                'zujin' => $sku_info['zujin']*100,
                'yiwaixian' => $sku_info['yiwaixian']*100,
                'yajin' => $sku_info['yajin']*100,
                'mianyajin' => $sku_info['mianyajin']*100,
                'create_time' => time(),
                'appid' => $data['appid'],
            ];
            $order_id = $this->order2_table->create($order_data);
            if( !$order_id ){
                 Debug::error(Location::L_Order, '生成订单失败', $order_data);
                 $this->order2_table->rollback();// 事务失败
                 return false;
            }
            // 记录订单流   ------begin
                $follow_data =[
                    'order_id' =>intval($order_id),
                    'step_status' =>intval($order_data['step_status']),
                    'order_status' =>intval($order_data['order_status']),
                    'old_status'=>0,
                    'new_status'=>1,
                ];
                $follow =$this->create_follow($follow_data);  
            //记录订单流   ------end
 
            $order_data['order_id'] = $order_id;

            // 保存 goods
            $goods_info = array_merge($sku_info,[
                'order_id'=>$order_id
            ]);
            $goods_id = $this->order2_goods_table->create($goods_info);
            if( !$goods_id ){
                Debug::error(Location::L_Order, '保存订单商品信息失败', $goods_info);
                $this->order2_table->rollback();// 事务失败
                return false;
            }
	        $goods_info['goods_id'] = $goods_id;

            // 更新订单关联的 goods_id
            $b = $this->order2_table->update_goods_id_and_name($order_id,$goods_id,$goods_info['sku_name']);
            if( !$b ){
                Debug::error(Location::L_Order, '订单关联商品失败', 'goods_id:'.$goods_id);
                set_error('订单关联商品失败');
                $this->order2_table->rollback();// 事务失败
                return false;
            }

            // 订单保存成功，提交事务
            $this->order2_table->commit();

            // 因为mysql不支持 事务嵌套，所以必须在当前事务之外，调用创建发货单的服务
            // 处理支付渠道（如果创建订单时就指定了 支付渠道，则直接创建支付单）
            if( $data['payment_channel_id']>0 ){
                // 加载 payment service
                $this->payment_service = $this->load->service('order2/payment');
                $this->payment_service->create([
                    'order_id' => $order_id,
                    'business_key' => $order_data['business_key'],
                    'amount' => $order_data['amount'],
                    'payment_channel_id' => $data['payment_channel_id'],
                    'order_no' =>$order_data['order_no'],
                    'goods_name'=>$order_data['goods_name'],
                ]);
            }
	    $order_data['goods_info'] = $goods_info;
	    // 格式化订单输出
	    $this->_output_format($order_data);
            return $order_data;
        } catch (\Exception $exc) {
            // 关闭事务
            $this->order2_table->rollback();
        }
        // 记录订单日志
        return false;
    }
    /**
     * 创建订单
     * @param int   $business_key   【必须】业务类型值
     * @param array $user_info	    【必须】用户ID+手机号
     * [
     *	    'user_id' => '',	    【必须】int	用户ID
     *	    'mobile' => '',	        【必须】string 手机号
     *	    'certified_platform' => '',	        【必须】int 认证平台
     *	    'credit' => '',	        【必须】信用值（根据具体认证平台不同）
     *      'realname'=>''    【必须】真实姓名
     *      'cert_no'           【必须】身份证号
     * ]
     * @param array $sku_info	    【必须】
     * [
     *	    'sku_id' => '',	        【必须】int SKU ID
     *	    'spu_id' => '',	        【必须】int SPU ID
     *	    'brand_id' => '',	    【必须】int 品牌ID
     *	    'category_id' => '',    【必须】int 分类ID
     *	    'sku_name' => '',	    【必须】string  商品名称
     *	    'specs' => '',	        【必须】string  商品规格描述
     *      'amount' => '',        【必须】int 应付金额单位元
     *	    'chengse' => '',		【必须】string 商品成色
     *	    'zuqi' => '',	        【必须】int	租期； 12:12期；6:6期；3:3期
     *	    'zujin' => '',	        【必须】int	月租金额（单位：元）
     * 	    'yiwaixian' => '',	    【必须】int	意外保险金（单位：元）
     * 	    'yajin' => '',	        【必须】int	实际押金（单位：元）
     * 	    'mianyajin' => '',	    【必须】int	实际免押金额（单位：元）
     *
     * ]
     * @param array $address_info   【必须】收货地址信息
     * [
     *	    'name' => '',	        【必须】string  收货人姓名
     *	    'mobile' => '',	        【必须】string  收货人手机号
     *	    'address' => '',	    【必须】string	详细地址信息
     * 	    'province_id' => '',    【必须】int	省份ID
     * 	    'city_id' => '',	    【必须】int	城市ID
     * 	    'country_id' => '',     【必须】int	区县ID
     *      'zipcode' => ''，
     * ]
     * @param int $pay_channel_id   【可选】支付渠道ID
     *
     * @return int  false：创建订单失败； array：订单创建成功，返回订单信息
     * @author liuhongxing <liuhongxing@huishouhao.com.cn>
     */
    public function create_order( $business_key, $user_info_data=[], $sku_info_data=[], $address_info_data=[], $pay_channel_id=0, $appid=0 ){
        // 参数过滤
        $user_info = filter_array($user_info_data,[
            'user_id' => 'required|is_id',
            'mobile' => 'required|is_mobile',
            'certified_platform' => 'required',
            'credit' => 'required',
            'realname'=>'required',
            'cert_no' => 'required',
        ]);
        if( count($user_info)!=6 ){
            set_error('用户信息，参数错误');
            if( !isset($user_info['user_id']) ){
                Debug::error(Location::L_Order, '创建订单[用户ID]错误', $user_info_data);
            }
            if( !isset($user_info['mobile']) ){
                Debug::error(Location::L_Order, '创建订单[用户手机号]错误', $user_info_data);
            }
            if( !isset($user_info['certified_platform']) ){
                Debug::error(Location::L_Order, '创建订单[用户认证平台]错误', $user_info_data);
            }
            if( !isset($user_info['credit']) ){
                Debug::error(Location::L_Order, '创建订单[用户信用分]错误', $user_info_data);
            }
            if( !isset($user_info['realname']) ){
                Debug::error(Location::L_Order, '创建订单[用户真实姓名]错误', $user_info_data);
            }
            if( !isset($user_info['cert_no']) ){
                Debug::error(Location::L_Order, '创建订单[身份证号]错误', $user_info_data);
            }
            // 失败
            return false;
        }
    
        $sku_info = filter_array($sku_info_data,[
            'sku_id' => 'required|is_id',
            'spu_id' => 'required|is_id',
            'brand_id' => 'required|is_id',
            'category_id' => 'required|is_id',
            'sku_name' => 'required',
            'specs' => 'required',
            'amount' => 'required|is_price',
            'chengse' => 'required',
            'zuqi' => 'required',
            'zujin' => 'required|is_price',
            'yiwaixian' => 'required|is_price',
            'yajin' => 'required|is_price',
            'mianyajin' => 'required|is_price',
        ]);
    
        //格式化specs
        if(is_string($sku_info['specs'])){
            $specs = json_decode($sku_info['specs'], true);
            $sku_info['specs'] = \zuji\order\goods\Specifications::input_format($specs);
        }elseif (is_array($sku_info['specs'])){
            $sku_info['specs'] = \zuji\order\goods\Specifications::input_format($sku_info['specs']);
        }
    
        if( count($sku_info)!=13 ){
            set_error('商品信息，参数错误');
            if( !isset($sku_info['sku_id']) ){
                Debug::error(Location::L_Order, '创建订单[skuID]错误', $sku_info_data);
            }
            if( !isset($sku_info['spu_id']) ){
                Debug::error(Location::L_Order, '创建订单[spuID]错误', $sku_info_data);
            }
            if( !isset($sku_info['brand_id']) ){
                Debug::error(Location::L_Order, '创建订单[品牌ID]错误', $sku_info_data);
            }
            if( !isset($sku_info['category_id']) ){
                Debug::error(Location::L_Order, '创建订单[分类ID]错误', $sku_info_data);
            }
            if( !isset($sku_info['sku_name']) ){
                Debug::error(Location::L_Order, '创建订单[spu名称]错误', $sku_info_data);
            }
            if( !isset($sku_info['specs']) ){
                Debug::error(Location::L_Order, '创建订单[spu规格]错误', $sku_info_data);
            }
            if( !isset($sku_info['amount']) ){
                Debug::error(Location::L_Order, '创建订单[商品总金额]错误', $sku_info_data);
            }
            if( !isset($sku_info['chengse']) ){
                Debug::error(Location::L_Order, '创建订单[sku成色]错误', $sku_info_data);
            }
            if( !isset($sku_info['zuqi']) ){
                Debug::error(Location::L_Order, '创建订单[sku成色]错误', $sku_info_data);
            }
            if( !isset($sku_info['zujin']) ){
                Debug::error(Location::L_Order, '创建订单[sku成色]错误', $sku_info_data);
            }
            if( !isset($sku_info['yiwaixian']) ){
                Debug::error(Location::L_Order, '创建订单[sku成色]错误', $sku_info_data);
            }
            if( !isset($sku_info['yajin']) ){
                Debug::error(Location::L_Order, '创建订单[押金]错误', $sku_info_data);
            }
            if( !isset($sku_info['mianyajin']) ){
                Debug::error(Location::L_Order, '创建订单[免押金额]错误', $sku_info_data);
            }
            // 失败
            return false;
        }
        $address_info = filter_array($address_info_data,[
            'name' => 'required',
            'mobile' => 'required|is_mobile',
            'address' => 'required',
            'province_id' => 'required|is_id',
            'city_id' => 'required|is_id',
            'country_id' => 'required|is_id',
            'zipcode' => 'required'
        ]);
        if( !isset($address_info['zipcode']) ){
            $address_info['zipcode'] = '';
        }
        if( count($address_info)!=7 ){
            set_error('收货人信息，参数错误');
            // 失败
            return false;
        }
    
        if(intval($appid) <= 0 ){
            set_error('appid参数错误');
            // 失败
            return false;
        }
         
        try {
            // 保存
            $order_data = [
                'status' => \oms\state\State::OrderCreated,	// 状态
                'order_status' => zuji\order\OrderStatus::OrderCreated, // 订单已创建
                'step_status' => zuji\order\OrderStatus::StepCreated,   // 订单创建阶段
                'business_key' => $business_key,        // 业务类型值
                'order_no' => \zuji\Business::create_business_no(),  // 编号
                'user_id' => $user_info['user_id'],
                'mobile' => $user_info['mobile'],
                'certified_platform' => $user_info['certified_platform'],
                'credit' => $user_info['credit'],
                'realname' =>$user_info['realname'],
                'cert_no' =>$user_info['cert_no'],
                'chengse' => $sku_info['chengse'],
                'zuqi' => $sku_info['zuqi'],
                'amount' => $sku_info['amount']*100,
                'zujin' => $sku_info['zujin']*100,
                'yiwaixian' => $sku_info['yiwaixian']*100,
                'yajin' => $sku_info['yajin']*100,
                'mianyajin' => $sku_info['mianyajin']*100,
                'create_time' => time(),
                'appid' => intval($appid)
            ];
            $order_id = $this->order2_table->create( $order_data );
            if( !$order_id ){
                Debug::error(Location::L_Order, '生成订单失败', $order_data);
                $this->order2_table->rollback();// 事务失败
                return false;
            }
            // 记录订单流   ------begin
            $follow_data =[
                'order_id' =>intval($order_id),
                'step_status' =>intval($order_data['step_status']),
                'order_status' =>intval($order_data['order_status']),
                'old_status'=>0,
                'new_status'=>1,
            ];
            $follow =$this->create_follow($follow_data);
            //记录订单流   ------end
    
    
            $order_data['order_id'] = $order_id;
    
            // 保存 goods
            $goods_info = array_merge($sku_info,[
                'order_id'=>$order_id
            ]);
            $goods_id = $this->order2_goods_table->create($goods_info);
            if( !$goods_id ){
                Debug::error(Location::L_Order, '保存订单商品信息失败', $goods_info);
                $this->order2_table->rollback();// 事务失败
                return false;
            }
            $goods_info['goods_id'] = $goods_id;
             
            // 更新订单关联的 goods_id
            $b = $this->order2_table->update_goods_id_and_name($order_id,$goods_id,$goods_info['sku_name']);
            if( !$b ){
                Debug::error(Location::L_Order, '订单关联商品失败', 'goods_id:'.$goods_id);
                set_error('订单关联商品失败');
                $this->order2_table->rollback();// 事务失败
                return false;
            }
    
            // 创建 address
            $address_info = array_merge($address_info,[
                'order_id'=>$order_id,
                'user_id'=>$user_info['user_id'],
            ]);
            $address_id = $this->order2_address_table->create($address_info);
            if( !$address_id ){
                set_error('保存订单收货地址信息失败');
                Debug::error(Location::L_Order, '保存订单收货地址信息失败',$address_info);
                $this->order2_table->rollback();// 事务失败
                return false;
            }
            $address_info['address_id'] = $address_id;
             
            // 更新 address_id 关联
            $b = $this->order2_table->update_address_id($order_id,$address_id);
            if( !$b ){
                set_error('保存订单收货地址ID失败');
                Debug::error(Location::L_Order, '保存订单收货地址ID失败',"address_id:".$address_id);
                $this->order2_table->rollback();// 事务失败
                return false;
            }
    
            // 订单保存成功，提交事务
            $this->order2_table->commit();
    
            // 因为mysql不支持 事务嵌套，所以必须在当前事务之外，调用创建发货单的服务
            // 处理支付渠道（如果创建订单时就指定了 支付渠道，则直接创建支付单）
            if( $pay_channel_id>0 ){
                // 加载 payment service
                $this->payment_service = $this->load->service('order2/payment');
                $this->payment_service->create([
                    'order_id' => $order_id,
                    'business_key' => $order_data['business_key'],
                    'amount' => $order_data['amount'],
                    'payment_channel_id' => $pay_channel_id,     
                    'order_no' =>$order_data['order_no'],
                    'goods_name'=>$order_data['goods_name'],
                ]);
            }
            $order_data['address_info'] = $address_info;
            $order_data['goods_info'] = $goods_info;
            // 格式化订单输出
            $this->_output_format($order_data);
            return $order_data;
        } catch (\Exception $exc) {
            // 关闭事务
            $this->order2_table->rollback();
        }
        // 记录订单日志
    
        return false;
    }
    
    
    /**
     * 获取一条商品信息
     */
    public function get_goods_info($goods_id){
        if(!isset($goods_id) || $goods_id <0){
            set_error("商品ID错误");
            return false;
        }
        $goods_info = $this->order2_goods_table->get_info($goods_id);
        $goods_info['specs'] = \zuji\order\goods\Specifications::output_format($goods_info['specs']);
        return $goods_info;
    }
    /**
     * 更改商品信息
     * @param int $goods_id 【必须】商品ID
     * @param $data
        'imei1' => '',      //【必须】 Imei1
        'imei2' => '',      //【可选】 Imei2
        'imei3' => '',      //【可选】 Imei3
        'serial_number'=>'',//【必须】 商品序列号
     * @author wuhaiyan
     */
    public function update_goods_serial($goods_id,$data=[]){
        if(!isset($goods_id) && $goods_id<1){
            set_error("商品ID错误");
            return false;
        }
        $data=filter_array($data,[
            'imei1' => 'required',
            'imei2' => 'required',
            'imei3' => 'required',
            'serial_number' => 'required',
        ]);
        if( count($data)<2 ){// 失败
            set_error('参数错误');
            return false;
        }
        return $this->order2_goods_table->update_serial($goods_id,$data);
    }

    /**
     * 计算订单支付金额（不包含押金，押金要走 预授权贷款）
     * @param array $data    【必须】价格参数
     * [
     *	    'zuqi' => '',	        【必须】int	租期； 12:12期；6:6期；3:3期
     *	    'zujin' => '',	        【必须】int	月租金额（单位：分）
     * 	    'yiwaixian' => '',	    【必须】int	意外保险金（单位：分）
     * 	    'yajin' => '',	        【必须】int	押金（单位：分）
     * ]
     * @param bool  $is_calculate_yajin 【可选】是否计算押金金额，默认：false；true：计算；false：不计算
     * @return mixed false：计算失败
     */
    public static function calculate_price( $data, $is_calculate_yajin=false ){
        $params = filter_array($data,[
            'zuqi' => 'required|is_int',
            'zujin' => 'required|is_int',
            'yiwaixian' => 'required|is_int',
            'yajin' => 'required|is_int',
        ]);
        if( count($params)!=4 ){// 失败
            set_error('计算价格时，参数错误');
            return false;
        }
        //
        $amount = $params['zuqi'] * $params['zujin'] + $params['yiwaixian'];
        if( $is_calculate_yajin ){
            $amount += $params['yajin'];// 算押金
        }
        return $amount;
    }

    /**
     * 订单数据 输入转换
     * @param array $order_info
     */
    private function input_format( array &$order_info ){
	// 输出，将价格单位，从 元 转换成 分
	$order_info['zujin']		= 100*Order::priceFormat($order_info['zujin']);
	$order_info['yajin']		= 100*Order::priceFormat($order_info['yajin']);
	$order_info['mianyajin']	= 100*Order::priceFormat($order_info['mianyajin']);
	$order_info['yiwaixian']	= 100*Order::priceFormat($order_info['yiwaixian']);
	$order_info['amount']		= 100*Order::priceFormat($order_info['amount']);
	$order_info['payment_amount']	= 100*Order::priceFormat($order_info['payment_amount']);
	$order_info['refund_amount']	= 100*Order::priceFormat($order_info['refund_amount']);
    }
    /**
     * 订单数据 输出转换
     * @param array $order_info
     */
    private function _output_format(array &$order_info ){
	
    $order_info['yidun'] = $this->order2_yidun->get_yidun_by_order_id($order_info['order_id']);
	if( empty($order_info['yidun']['decision']) ) {
		$order_info['yidun']['decision_text'] = isset($order_info['yidun']['score'])?\zuji\order\Lists::getYidunScoreLevelDesc($order_info['yidun']['score']):"未知";
		$order_info['yidun']['score_color'] = isset($order_info['yidun']['score'])?\zuji\order\Lists::getYidunScoreLevelColor($order_info['yidun']['score']):'';
	}else{		
		$order_info['yidun']['decision_text'] = isset($order_info['yidun']['decision'])?\zuji\order\Lists::getYidunDecisionLevelDesc($order_info['yidun']['decision']):"未知";
		$order_info['yidun']['score_color'] = isset($order_info['yidun']['decision'])?\zuji\order\Lists::getYidunDecisionLevelColor($order_info['yidun']['decision']):'';
	}
    $order_info['yidun']['score'] = isset($order_info['yidun']['score'])?$order_info['yidun']['score']:'0';
	//拼接用户命中蚁盾策略
	$strategies_arr = [];//初始化蚁盾策略数据
	if( isset($order_info['yidun']['strategies'])&& $order_info['yidun']['strategies']!='' ) {
		$strategies = json_decode($order_info['yidun']['strategies'],TRUE);
		foreach ($strategies as $key => $value) {
			$strategies_arr[] = implode('#', $value);
		}
	}
    $order_info['yidun']['strategies'] = implode(';', $strategies_arr);
	$order_info['certified_platform_name'] = \zuji\certification\Certification::getPlatformName($order_info['certified_platform']);

	//$order_info['order_status_show'] = OrderStatus::getStatusName($order_info['order_status']);
    $order_info['order_status_show'] = State::getStatusAllName($order_info['status']);
	$order_info['step_status_show']	= OrderStatus::getStepName($order_info['step_status']);


	// 输出，将价格单位，从 分 转换成 元
	$order_info['zujin']		= Order::priceFormat($order_info['zujin']/100);
	$order_info['zujin_total']	= Order::priceFormat(($order_info['zujin']*$order_info['zuqi']));// 总租金=月租金*租期
	$order_info['yajin']		= Order::priceFormat($order_info['yajin']/100);
	$order_info['mianyajin']	= Order::priceFormat($order_info['mianyajin']/100);
	$order_info['yiwaixian']	= Order::priceFormat($order_info['yiwaixian']/100);
	$order_info['all_amount']		= Order::priceFormat($order_info['all_amount']/100);
	$order_info['amount']		= Order::priceFormat($order_info['amount']/100);
    $order_info['buyout_price']		= Order::priceFormat($order_info['buyout_price']/100);

    $order_info['discount_amount']		= Order::priceFormat($order_info['discount_amount']/100);
	
	$order_info['payment_amount']	= Order::priceFormat($order_info['payment_amount']/100);
	$order_info['payment_amount_show'] = $order_info['payment_time']>0?$order_info['payment_amount']:'--';
	
	$order_info['refund_amount']	= Order::priceFormat($order_info['refund_amount']/100);
	$order_info['refund_amount_show'] = $order_info['refund_time']>0?$order_info['refund_amount']:'--';
	//支付方式
    $payment_style_model = model('payment/payment_style','service')->modelId($order_info['payment_type_id']);
    $order_info['payment_type'] = isset($payment_style_model['pay_name'])?$payment_style_model['pay_name']:'--';

	// 时间
	$order_info['create_time_show']= $order_info['create_time']>0?date('Y-m-d H:i:s',$order_info['create_time']):'--';
	$order_info['update_time_show']= $order_info['update_time']>0?date('Y-m-d H:i:s',$order_info['update_time']):'--';
	$order_info['payment_time_show']= $order_info['payment_time']>0?date('Y-m-d H:i:s',$order_info['payment_time']):'--';
	$order_info['refund_time_show']	= $order_info['refund_time']>0?date('Y-m-d H:i:s',$order_info['refund_time']):'--';
    }


    /**
     * 获取一条订单
     * @param array $where	【必须】查询条件    order_id或order_no二选一，都不存在时，返回false
     * [
     *      'order_id' => '',	//【可选】int；订单ID
     *      'order_no' => '',	//【可选】string；订单编号
     * ]
     * @param array $additional		【可选】附加条件
     * [
     *      'goods_info' => false,	//【可选】bool；是否查询商品
     *      'address_info' => false,	//【可选】bool；是否查询收货人信息
     *      'lock' =>true,          //【可选】bool;是否加排它锁
     * ]
     * @return mixed	    false：未找到； array：订单基本信息
     * [
     *      'business_key'              =>'',   //【必须】int；业务类型
     *      'order_id'              =>'',   //【必须】int；订单ID
     *      'order_no'    =>'',   //【必须】string；订单编号
     *      'goods_id'          =>'',   //【必须】int；sku ID
     *      'goods_name'        =>'',   //【必须】string；sku 名称
     *      'user_id'         =>'',   //【必须】int；用户ID
     *      'zujin'           =>'',   //【必须】int；租金（单位：分）
     *      'zuqi'            =>'',   //【必须】int；租期（单位：月）
     *      'yajin'           =>'',   //【必须】int；押金（单位：分）
     *      'mianyajin'       =>'',   //【必须】int；免押金（单位：分）
     *      'yiwaixian'         =>'',   //【必须】int；保险（单位：分）
     *      'amount'          =>'',   //【必须】int；应付金额（单位：分）
     *      'payment_amount'    =>'',   //【必须】int；实付金额（单位：分）
     *      'payment_time'    =>'',   //【必须】int；实付支付时间
     *      'refund_amount'    =>'',   //【必须】int；实际退款金额（单位：分）
     *      'refund_time'    =>'',   //【必须】int；实际退款时间
     *      'create_time'      =>'',     //【必须】int；下单时间
     *      'order_status'          =>'',   //【必须】int；订单状态
     *      'step_status'  =>'',   //【必须】int；订单阶段状态
     *      'payment_status'  =>'',   //【必须】int；支付状态
     *      'payment_id'  =>'',	      //【必须】int；支付状态
     *      'delivery_status'    =>'',//【必须】int；发货状态
     *      'delivery_id'    =>'',  //【必须】int；发货状态
     *      'return_status'    =>'',//【必须】int；退货状态
     *      'return_id'    =>'',    //【必须】int；退货单ID
     *      'refund_status'  =>'',  //【必须】int；退款状态
     *      'refund_id'  =>'',	    //【必须】int；退款状态
     *      'evaluation_status'   =>'',//【必须】int；检测状态
     *      'evaluation_id' => '',  //【必须】int；检测单ID
     *      'service_status'  =>'', //【必须】int；使用状态
     *      'goods_info'  =>[]',    //【可选】array；订单商品信息
     *      'address_info'  =>[]',    //【可选】array；订单收货人地址信息
     * ]
     * @author liuhongxing<liuhongxing@huishoubao.com.cn>
     */
    public function get_order_info($where=[], $additional=[]){
	// 参数过滤
	$where = filter_array($where, [
	    'order_id' => 'required|is_id',
	    'order_no' => 'required',
	]);
	
	// 都没有通过过滤器（都被过滤掉了）
	if( count($where)==0 ){
	    return false;
	}
	
	// 字段替换
	$where = replace_field( $where,[
	    'order_id' => 'order_id',
	    'order_no' => 'order_no',
	] );
	
	if($additional['lock']){
	    $lock =$additional['lock'];
	}else{
	    $lock =false;
	}
	
	$order_info = $this->order2_table->get_info($where,$lock);
	
	if( !$order_info ){
	    return false;
	}
	$this->_output_format($order_info);
	// 商品信息
	if( filter_array($additional,['goods_info' => 'required|is_true']) ){
	    $order_info['goods_info'] = $this->get_goods_info($order_info['goods_id']);
	}
	// 收货地址信息
	if( filter_array($additional,['address_info' => 'required|is_true']) ){
	    $order_info['address_info'] = $this->order2_address_table->get_info_by_order_id($order_info['order_id']);
	}
	
        return $order_info;
    }

    /**
     * 订单导出数据新方法
     * @param array $where	【可选】查询条件
     * [
     *      'order_id' => '',	//【可选】mixed 订单ID，string|array （string：多个','分割）（array：订单ID数组）多个只支持
     *      'order_no' => '',	//【可选】string；订单编号（支持前缀模糊查询）
     *      'order_id' => '',	//【可选】mixed 用户ID，string|array （string：多个','分割）（array：订单ID数组）多个只支持
     *      'mobile' => '',	//【可选】string；用户手机号
     *      'sku_name'=>''      //【可选】string；商品名称（支持前缀模糊查询）
     *      'step_status'=>''      //【可选】int；阶段
     *      'begin_time'=>''      //【可选】int；下单开始时间戳
     *      'end_time'=>''      //【可选】int；  下单截止时间戳
     *      'appid'=>''         //【可选】int; 根据APPID查询
     * ]
     *
     * @param array $options	    【可选】附加选项
     * [
     *	    'page'	=> '',	           【可选】int 分页码，默认为1
     *	    'size'	=> '',	           【可选】int 每页大小，默认20
     *	    'order'	=> '',             【可选】string 排序；默认 time_DESC：时间倒序；time_ASC：时间顺序
     *	    'goods_info' => false',	   【可选】string 查询商品信息
     *	    'address_info' => false',	   【可选】string 查询收货地址信息
     * ]
     * @return array	二维数组，键名参考 get_order_info
     * )
     * 支付单列表，没有查询到时，返回空数组
     * @author liuhongxing<liuhongxing@huishoubao.com.cn>
     */
    public function get_order_list_csv($where=[],$additional=[]){

        // 参数过滤
        $where = $this->_parse_order_where($where);
        if( $where===false ){
            return [];
        }

        $additional = filter_array($additional, [
            'page' => 'required|is_int',
            'size' => 'required',
            'orderby' => 'required',
            'goods_info' => 'required|is_true',
            'address_info' => 'required|is_true',
            'orderby' => 'required',
        ]);
        // 分页
        if( !isset($additional['page']) ){
            $additional['page'] = 1;
        }
        if( !isset($additional['size']) ){
            $additional['size'] = 20;
        }elseif($additional['size']=='all'){
            $additional['size']="all";
        }else{
            $additional['size'] = min( $additional['size'], 100 );
        }

        if( !isset($additional['orderby']) ){	// 排序默认值
            $additional['orderby']='time_DESC';
        }

        if( in_array($additional['orderby'],['time_DESC','time_ASC']) ){
            if( $additional['orderby'] == 'time_DESC' ){
                $additional['orderby'] = 'create_time DESC';
            }elseif( $additional['orderby'] == 'time_ASC' ){
                $additional['orderby'] = 'create_time ASC';
            }
        }
        // 订单信息
        $order_list = $this->order2_table->get_list_csv($where,$additional);
        foreach( $order_list as &$item ){
            $this->_output_format($item);
        }
        $n = count($order_list);
        if( $n ){
            $_additional = ['size'=>$n];
            // 商品
            if( filter_array($additional,['goods_info' => 'required|is_true']) ){
                $goods_ids = array_column($order_list, 'goods_id');
                $goods_list = $this->get_goods_list(['goods_id'=>$goods_ids],$_additional);
                if( is_array($goods_list) ){
                    mixed_merge($order_list, $goods_list, 'goods_id','goods_info');
                }
            }
            // 收货地址
            if( filter_array($additional,['address_info' => 'required|is_true']) ){

                $address_ids = array_column($order_list, 'address_id');
                $address_list = $this->get_address_list(['address_id'=>$address_ids],$_additional);
                if( is_array($address_list) ){
                    mixed_merge($order_list, $address_list, 'address_id','address_info');
                }
            }

        }
        foreach($order_list as $key=>$val){
            $address_info = $this->get_address_info($val['address_id']);
            $this->district_service = $this->load->service('admin/district');
            $province = $this->district_service->get_name($address_info['province_id']);
            $city = $this->district_service->get_name($address_info['city_id']);
            $country = $this->district_service->get_name($address_info['country_id']);
            $order_list[$key]['complete_address'] = $province . ' ' . $city . ' ' . $country . ' ' . $address_info['address'];
        }
        return $order_list;

    }

    /**
     * 查询订单列表(带分页)
     * @param array $where	【可选】查询条件    
     * [
     *      'order_id' => '',	//【可选】mixed 订单ID，string|array （string：多个','分割）（array：订单ID数组）多个只支持
     *      'order_no' => '',	//【可选】string；订单编号（支持前缀模糊查询）
     *      'order_id' => '',	//【可选】mixed 用户ID，string|array （string：多个','分割）（array：订单ID数组）多个只支持
     *      'mobile' => '',	//【可选】string；用户手机号
     *      'sku_name'=>''      //【可选】string；商品名称（支持前缀模糊查询）
     *      'step_status'=>''      //【可选】int；阶段
     *      'begin_time'=>''      //【可选】int；下单开始时间戳
     *      'end_time'=>''      //【可选】int；  下单截止时间戳
     *      'appid'=>''         //【可选】int; 根据APPID查询
     * ]
     *
     * @param array $options	    【可选】附加选项
     * [
     *	    'page'	=> '',	           【可选】int 分页码，默认为1
     *	    'size'	=> '',	           【可选】int 每页大小，默认20
     *	    'order'	=> '',             【可选】string 排序；默认 time_DESC：时间倒序；time_ASC：时间顺序
     *	    'goods_info' => false',	   【可选】string 查询商品信息
     *	    'address_info' => false',	   【可选】string 查询收货地址信息
     * ]
     * @return array	二维数组，键名参考 get_order_info
     * )
     * 支付单列表，没有查询到时，返回空数组
     * @author liuhongxing<liuhongxing@huishoubao.com.cn>
     */
    public function get_order_list($where=[],$additional=[]){

        // 参数过滤
        $where = $this->_parse_order_where($where);
        if( $where===false ){
            return [];
        }

        $additional = filter_array($additional, [
            'page' => 'required|is_int',
            'size' => 'required',
            'orderby' => 'required',
            'goods_info' => 'required|is_true',
            'address_info' => 'required|is_true',
            'orderby' => 'required',
        ]);
        // 分页
        if( !isset($additional['page']) ){
            $additional['page'] = 1;
        }
        if( !isset($additional['size']) ){
            $additional['size'] = 20;
        }elseif($additional['size']=='all'){
            $additional['size']="all";
        }else{
            $additional['size'] = min( $additional['size'], 100 );
        }

        if( !isset($additional['orderby']) ){	// 排序默认值
            $additional['orderby']='time_DESC';
        }
        
        if( in_array($additional['orderby'],['time_DESC','time_ASC']) ){
            if( $additional['orderby'] == 'time_DESC' ){
                $additional['orderby'] = 'create_time DESC';
            }elseif( $additional['orderby'] == 'time_ASC' ){
                $additional['orderby'] = 'create_time ASC';
            }
        }
	    // 订单信息
        $order_list = $this->order2_table->get_list($where,$additional);
	
        foreach( $order_list as &$item ){
            $this->_output_format($item);
        }
        $n = count($order_list);
        if( $n ){
            $_additional = ['size'=>$n];
            // 商品
            if( filter_array($additional,['goods_info' => 'required|is_true']) ){
                $goods_ids = array_column($order_list, 'goods_id');
                $goods_list = $this->get_goods_list(['goods_id'=>$goods_ids],$_additional);
		if( is_array($goods_list) ){
		    mixed_merge($order_list, $goods_list, 'goods_id','goods_info');   
		}
            }
            // 收货地址
            if( filter_array($additional,['address_info' => 'required|is_true']) ){
               
                $address_ids = array_column($order_list, 'address_id');
                $address_list = $this->get_address_list(['address_id'=>$address_ids],$_additional);
		if( is_array($address_list) ){
		    mixed_merge($order_list, $address_list, 'address_id','address_info');
		}
            }

        }
        return $order_list;

    }

    /**
     * 获取符合条件的订单记录数
     * @param   array	$where  参考 get_order_list() 参数说明
     * @return int 查询总数
     */
    public function get_order_count($where=[]){
	// 参数过滤
	$where = $this->_parse_order_where($where);
	if( $where===false ){
	    return 0;
	}
        return $this->order2_table->get_count($where);
    }
    /**
     * 获取一段日期内的下单总数量
	 * @param type $start_time
	 * @param type $end_time
     * @return int 查询总数
	 */
    public function get_order_pass($start_time,$end_time){
        return $this->order2_table->get_order_pass($start_time,$end_time);
    }

    private function _parse_order_where($where){

	// 参数过滤
	$where = filter_array($where, [
	    'business_key' => 'required|is_id',
	    'order_id' => 'required',
	    'order_no' => 'required|is_string',
	    'user_id' => 'required',
	    'mobile' => 'required',
	    'status'=>'required',
        'goods_id'=>'required',
	    'order_status'=>'required',
	    'step_status'=>'required',
	    'payment_status'=> 'required',
	    'delivery_status'=> 'required',
	    'return_status'=> 'required',
	    'receive_status'=> 'required',
	    'evaluation_status'=> 'required',
	    'refund_status'=> 'required',
	    'sku_name' => 'required',
	    'begin_time' => 'required',
	    'end_time' => 'required',
        'appid' => 'required',
	    'create_time'=>'required',
	    'appid'=>'required',
        'remark_id'=>'required',
        'user_id_not_in'=>'required',
	]);
//
	// 结束时间（可选），默认为为当前时间
	if( !isset($where['end_time']) ){
	    $where['end_time'] = time();
	}
	
	if(!isset($where['create_time'])){
	    if( isset($where['begin_time'])){
	        if( $where['begin_time']>$where['end_time'] ){
	            return false;
	        }
	        $where['create_time'] = ['between',[$where['begin_time'], $where['end_time']]];
	    }else{
	        $where['create_time'] = ['LT',$where['end_time']];
	    }
	}
	
	unset($where['begin_time']);
	unset($where['end_time']);

	// order_id 支持多个
	$b = $this->_parse_where_field_array('order_id',$where);
	if( !$b ){
	    return false;
	}
	if(isset($where['user_id_not_in'])){
        $where['user_id'] = ['NEQ',$where['user_id_not_in']];
    }else{
        // user_id 支持多个
        $b = $this->_parse_where_field_array('user_id',$where);
        if( !$b ){
            return false;
        }
    }// status 支持多个
        $b = $this->_parse_where_field_array('status',$where);
        if( !$b ){
            return false;
    }
    // goods_id 支持多个
    $b = $this->_parse_where_field_array('goods_id',$where);
    if( !$b ){
        return false;
    }
    // step_status 支持多个
    $b = $this->_parse_where_field_array('step_status',$where);
    if( !$b ){
        return false;
    }
	// payment_status 支持多个
	$b = $this->_parse_where_field_array('payment_status',$where);
	if( !$b ){
	    return false;
	}
	// delivery_status 支持多个
	$b = $this->_parse_where_field_array('delivery_status',$where);
	if( !$b ){
	    return false;
	}
	// return_status 支持多个
	$b = $this->_parse_where_field_array('return_status',$where);
	if( !$b ){
	    return false;
	}
	// receive_status 支持多个
	$b = $this->_parse_where_field_array('receive_status',$where);
	if( !$b ){
	    return false;
	}
	// evaluation_status 支持多个
	$b = $this->_parse_where_field_array('evaluation_status',$where);
	if( !$b ){
	    return false;
	}
	// refund_status 支持多个
	$b = $this->_parse_where_field_array('refund_status',$where);
	if( !$b ){
	    return false;
	}
	
	// sku_name 商品名称查询，使用前缀模糊查询
        if( isset($where['mobile']) ){
	    $where['mobile'] = ['LIKE', $where['mobile'] . '%'];
	}
	// order_no 订单编号查询，使用前缀模糊查询
    if(isset($where['order_no'])){
            $where['order_no'] = ['LIKE', $where['order_no'] . '%'];
        }
	// sku_name 商品名称查询，使用前缀模糊查询
        if( isset($where['sku_name']) ){
	    $where['sku_name'] = ['LIKE', $where['sku_name'] . '%'];
	}
	return $where;
    }


    /**
     * 查询商品列表
     * @param array $where  【必选】
     * [
     *	    'goods_id' => '',	//【必选】mixed 订单ID，string|array （string：多个','分割）（array：订单ID数组）多个只支持
     * ]
     * @param array $additional  【可选】
     * [
     *	    'size' => '20',	//【可选】int；查询记录大小
     * ]
     * @return array	商品信息列表（键名查考 get_goods_info()方法）
     */
    public function get_goods_list( $where=[], $additional=[] ){
        $where = filter_array($where, [
            'goods_id' => 'required'
        ]);
        if( count($where)==0 ){
            return [];
        }
        if( is_string($where['goods_id']) ){
            $where['goods_id'] = explode(',', $where['goods_id']);
        }

        if( count($where['goods_id'])==1 ){
            $where['goods_id'] = $where['goods_id'][0];
        }
        if( count($where['goods_id'])>1 ){
            $where['goods_id'] = ['IN',$where['goods_id']];
        }

        if( !isset($additional['size']) ){
            $additional['size'] = 20;
            $additional['size'] = min( $additional['size'], 20 );
        }

        $goods_list = $this->order2_goods_table->get_list($where,$additional);
        foreach ($goods_list as $k => &$item){
            $item['specs'] = \zuji\order\goods\Specifications::output_format($item['specs']);
        }
        //var_dump( $this->order2_goods_table->getLastSql() );exit;
        return $goods_list;
    }

    /**
     * 租机退回业务中 要添加新的地址

     * @param array $data
     * [
     *      'order_id' =>'',    //订单ID
     *      'user_id'=>'',      //用户ID
     *	    'name' => '',	// 姓名
     *	    'mobile' => '',	// 手机号
     *	    'address' => '',	// 详细地址
     *	    'remark' => '',	// 备注
     *      'province_id' => '',// 省ID
     *	    'city_id' => '',	// 市ID
     *	    'country_id' => '',	// 区县ID
     * ]
     */
    public function create_address($data ){
        $data = filter_array($data, [
            'order_id' => 'required|is_id',
            'user_id' => 'required|is_id',
            'province_id' => 'required|is_id',
            'city_id' => 'required|is_id',
            'country_id' => 'required|is_id',
            'name' => 'required',
            'mobile' => 'required|is_mobile',
            'address' => 'required',
            'remark' => 'required',
        ]);
        if( count($data)!=9 ){
            set_error('增加订单收货地址失败，参数错误');
            return false;
        }
        return $this->order2_address_table->create($data);
    }

    /**
     * 更新订单收货地址
     * @param int $address_id
     * @param array $data
     * [
     *	    'province_id' => '',// 省ID
     *	    'city_id' => '',	// 市ID
     *	    'country_id' => '',	// 区县ID
     *	    'name' => '',	// 姓名
     *	    'mobile' => '',	// 手机号
     *	    'address' => '',	// 详细地址
     *	    'remark' => '',	// 备注
     * ]
     */
    public function edit_address( $address_id,$data=[] ){
	$data = filter_array($data, [
	    'province_id' => 'required|is_id',
	    'city_id' => 'required|is_id',
	    'country_id' => 'required|is_id',
	    'name' => 'required',
	    'mobile' => 'required|is_mobile',
	    'address' => 'required',
	    'remark' => 'required',
	]);
	if( count($data)!=7 ){
	    set_error('更新订单收货地址失败，参数错误');
	    return false;
	}
	return $this->order2_address_table->edit($address_id,$data);
    }
    
    /**
     * 根据订单收货地址ID，查询收货地址信息
     * @author liuhongxing <liuhongxing@huishoubao.com.cn>
     * @param int   $address_id	    订单收货地址ID
     * @return boolean false：查询失败；array：收货地址信息
     * []
     */
    public function get_address_info($address_id, $additional=[]){
        return $this->order2_address_table->get_info($address_id, $additional);
    }
    /**
     * 根据订单ID查询收货地址，查询收货地址信息
     * @author liuhongxing <liuhongxing@huishoubao.com.cn>
     * @param int   $address_id	    订单收货地址ID
     * @return boolean false：查询失败；array：收货地址信息
     * []
     */
    public function get_address_info_by_order_id($order_id){
        return $this->order2_address_table->get_info_order_id($order_id);
    }
    /**
     * 根据查询条件，查询订单收货地址列表
     * @param array $where
     * [
     *	    'address_id' => '',	    【可选】
     * ]
     * @param array $additional
     * [
     *	    'page' => '1',
     *	    'size' => '20',
     *	    'orderby' => '',
     * ]
     */
    public function get_address_list($where=[],$additional=[]){
        $where = filter_array($where, [
            'address_id' => 'required',
            'order_id' => 'required'
        ]);
        if( count($where)==0 ){
            return [];
        }
        if( is_string($where['address_id']) ){
            $where['address_id'] = explode(',', $where['address_id']);
        }
        if( count($where['address_id'])==1 ){
            $where['address_id'] = $where['address_id'][0];
        }
        if( is_string($where['order_id']) ){
            $where['order_id'] = explode(',', $where['order_id']);
        }
        if( count($where['order_id'])==1 ){
            $where['order_id'] = $where['order_id'][0];
        }
        if( count($where['order_id'])==0 && count($where['address_id'])==0 ){
            return [];
        }elseif( $where['order_id'] && $where['address_id'] ){
            $where['order_id'] = ['IN',$where['order_id']];
            $where['address_id'] = ['IN',$where['address_id']];
        }elseif($where['order_id']){
            $where['order_id'] = ['IN',$where['order_id']];
        }else{
            $where['address_id'] = ['IN',$where['address_id']];
        }
        if( !isset($additional['size']) ){
            $additional['size'] = 20;
            $additional['size'] = min( $additional['size'], 20 );
        }
        
        return $this->order2_address_table->get_list($where,$additional);
    }


    /**
     * 判断订单是否允许支付
     * @param array $order_status_arr   订单状态集
     * [
     *      'order_status' => '',       //【必须】int；订单状态
     *      'step_status' => '',        //【必须】int；订单阶段
     *      'payment_status' => '',     //【必须】int；支付单状态
     * ]
     * @return bool     true：允许；false：禁止
     */
    public function is_allowed_to_pay( $order_status_arr=[] ){
        $data = filter_array($order_status_arr,[
            'order_status' => 'required',
            'step_status' => 'required',
            'payment_status' => 'required',
        ]);
        if( count($data)!=3 ){
            set_error('参数错误');
            return false;
        }
	if( $data['order_status']!=OrderStatus::OrderCreated ){
	    return false;
	}
        if($data['step_status']==OrderStatus::StepPayment
            && in_array($data['payment_status'],[PaymentStatus::PaymentWaiting,PaymentStatus::PaymentPaying,PaymentStatus::PaymentFailed]) ){
            return true;
        }
        return false;
    }

    /**
     * 判断是否允许 取消订单//租机业务
     * @param int $order_status
     * @return bool
     */
    public function is_allowed_to_cancel( $order_status ){
        if(!isset($order_status)){
            set_error("订单状态错误");
            return false;
        }
        if( $order_status==OrderStatus::OrderCreated){
            return true;
        }
        return false;
    }

    /**
     * 前端取消订单
     * @param int   $order_id 【必须】订单ID
     * @param string $order_no 【必须】 订单编号
     * @param array $data     【必须】取消保存数据
     * [
     *      'reason_id' => '',          【必须】int；取消原因ID
     *      'reason_text' => '',        【必须】string；附加原因描述，可以为空
     *      'user_id'=>'',              【必须】用户ID
     *      'username'=>'',             【必须】用户姓名
     * ]
     * @return bool     true：取消成功；false：取消失败
     */
    public function cancel_order( $order_id, $order_no,$data=[] ){
        $data = filter_array($data,[
            'reason_id' => 'required|is_id',
            'reason_text' => 'required',
            'user_id'=>'required|is_id',
            'username'=>'required',
        ]);
        // 附加原因描述默认为空字符串，如果reasion_id=0时，不允许为空
        if( !isset($data['reason_text']) ){
            $data['reason_text'] = '';
        }
        if($data['reason_id']==0 && $data['reason_text']==''){
            return false;
        }
        if($order_id <1){
            set_error("订单ID错误");
            return false;
        }
        if($order_no ==""){
            set_error("订单编号错误");
            return false;
        }
        
	    $data['order_status'] = OrderStatus::OrderCanceled;
        // 保存数据
        $b = $this->order2_table->cancel_order( $order_id , $data);
        if(!$b){
            set_error("取消订单失败");
            return false;
        }
        //生成日志开始
        $log = [
            'order_no' => $order_no,
            'action' => "用户取消订单",
            'operator_id' => $data['user_id'],
            'operator_name' => $data['username'],
            'operator_type' => 2,
            'msg' => "用户取消订单成功",
        ];
        $add_log = $this->service_order_log->add($log);
        if (!$add_log) {
            set_error("插入日志失败");
            return false;
         }
        return $b;
    }
    
    /**
     * 系统自动取消订单
     * @param int   $order_id 【必须】订单ID
     * @param string $order_no 【必须】 订单编号
     * @return bool     true：取消成功；false：取消失败
     */
    public function system_cancel_order( $order_id, $order_no ){

        if($order_id <1){
            set_error("订单ID错误");
        }
        if($order_no ==""){
            set_error("订单编号错误");
            return false;
        }
    
        $data['order_status'] = OrderStatus::OrderCanceled;
        $data['admin_id'] = 0;
        $data['reason_text'] = '系统自动取消订单';
        $data['reason_id']=0;
        // 保存数据
        $b = $this->order2_table->cancel_order( $order_id , $data);
        if(!$b){
            set_error("取消订单失败");
            return false;
        }
        //生成日志开始
        $log = [
            'order_no' => $order_no,
            'action' => "取消订单",
            'operator_id' => 0,
            'operator_name' =>"系统",
            'operator_type' => 3,
            'msg' => "系统自动取消订单成功",
        ];
        $add_log = $this->service_order_log->add($log);
        if (!$add_log) {
            set_error("插入日志失败");
            return false;
        }
        return $b;
    }
    /**
     * 后端取消订单
     * @param int   $order_id 【必须】订单ID
     * @param int   $admin_id 【必须】int；操作员ID
     * @return bool     true：取消成功；false：取消失败
     */
    public function admin_cancel_order( $order_id, $admin_id ){
        if( $order_id <1){
            set_error("订单ID错误");
            return false;
        }
        if( !isset($admin_id) ){
            $admin_id = 0;
        }
        $data['admin_id'] =$admin_id;
        $data['order_status'] = OrderStatus::OrderCanceled;
        // 保存数据
        $b = $this->order2_table->cancel_order( $order_id , $data);
        if(!$b){
            set_error("取消订单失败");
            return false;
        }
        return true;
    }
    
    

    //-+------------------------------------------------------------------------------
    // | 状态通知
    //-+------------------------------------------------------------------------------
    /**
     * 状态同步 -- 进入支付阶段
     * @params int  $order_id	订单ID
     * @params int  $payment_id	支付单ID
     * @return boolean	true：成功；false：失败
     */
    public function enter_payment( $order_id, $payment_id ){
	// 更新订单 step_status, payment_id,payment_status
        try {
            $data = array(
                'step_status'=> zuji\order\OrderStatus::StepPayment,//支付阶段
                'payment_id'=>$payment_id, //支付单id
                'payment_status'=> zuji\order\PaymentStatus::PaymentCreated,//待支付
            );
            //更新订单
            $order_result =$this->order2_table->enter_payment( $order_id, $data );
            //验证订单更新是否成功
            if( !$order_result ) {//业务处理不成功                
                return false;
            }
            return true;
        } catch (\Exception $exc) {
            return false;
        }
    }

    /**
     * 状态同步 -- 待支付
     * @params int  $order_id	订单ID
     * @return boolean	true：成功；false：失败
     */
    public function notify_payment_waiting( $order_id ){
	// 更新订单  payment_status
        try {
            $data = array(
                'step_status'=> zuji\order\OrderStatus::StepPayment,//支付阶段
                'payment_status'=> zuji\order\PaymentStatus::PaymentWaiting,//待支付
            );
            //更新订单
            $order_result = $this->order2_table->notify_payment_except_success( $order_id, $data );
            //验证订单更新是否成功
            if( !$order_result ) {//业务处理不成功
                return false;
            }
            return true;
        } catch (\Exception $exc) {
            return false;
        }
    }
    /**
     * 状态同步 -- 支付中
     * @params int  $order_id	订单ID
     * @return boolean	true：成功；false：失败
     */
    public function notify_payment_paying( $order_id ){
	// 更新订单  payment_status
        try {
            $data = array(
                'step_status'=> zuji\order\OrderStatus::StepPayment,//支付阶段
                'payment_status'=> zuji\order\PaymentStatus::PaymentPaying,//支付中
            );
            //更新订单
            $order_result = $this->order2_table->notify_payment_except_success( $order_id, $data );
            //验证订单更新是否成功
            if( !$order_result ) {//业务处理不成功
                return false;
            }
            return true;
        } catch (\Exception $exc) {
            return false;
        }
    }
    /**
     * 状态同步 -- 支付成功
     * @params int  $order_id	订单ID
     * @params int  $payment_time	实际支付时间戳
     * @params int  $payment_amount	实际支付金额（单位：分）
     * @return boolean	true：成功；false：失败
     */
    public function notify_payment_success( $order_id,$payment_time,$payment_amount ){
	// 更新订单  payment_status, payment_time, payment_amount
        try {
            $data = array(
                'order_status' => zuji\order\OrderStatus::OrderCreated,
                'step_status' => zuji\order\OrderStatus::StepPayment,//支付阶段
                'payment_status'=> zuji\order\PaymentStatus::PaymentSuccessful,//支付成功
                'payment_time' => $payment_time,//支付时间
                'payment_amount' => $payment_amount,//支付金额（分）
            );
            //更新订单
            $order_result = $this->order2_table->notify_payment_success( $order_id, $data );
            //验证订单更新是否成功
            if( !$order_result ) {//业务处理不成功
                return false;
            }
            // 记录订单流   ------begin
            $follow_data =[
                'order_id' =>$order_id,
                'step_status' =>intval($data['step_status']),
                'order_status' =>OrderStatus::OrderCreated,
                'follow_status'=>intval($data['payment_status']),
            ];
            $follow =$this->create_follow($follow_data);
            //记录订单流   ------end
            return true;
        } catch (\Exception $exc) {
            return false;
        }
    }
    
    /**
     * 状态同步 -- 门店支付成功
     * @params int  $order_id	订单ID
     * @params int  $payment_time	实际支付时间戳
     * @params int  $payment_amount	实际支付金额（单位：分）
     * @return boolean	true：成功；false：失败
     */
    public function notify_store_payment_success( $order_id,$payment_time,$payment_amount ){
        // 更新订单  payment_status, payment_time, payment_amount
        try {
            $data = array(
                'order_status' => zuji\order\OrderStatus::OrderStoreUploading,
                'payment_status'=> zuji\order\PaymentStatus::PaymentSuccessful,//支付成功
                'payment_time' => $payment_time,//支付时间
                'payment_amount' => $payment_amount,//支付金额（分）
            );
            //更新订单
            $order_result = $this->order2_table->notify_payment_success( $order_id, $data );
            //验证订单更新是否成功
            if( !$order_result ) {//业务处理不成功
                return false;
            }
            // 记录订单流   ------begin
            $follow_data =[
                'order_id' =>$order_id,
                'step_status' =>OrderStatus::StepPayment,
                'order_status' =>OrderStatus::OrderStoreUploading,
                'follow_status'=>intval($data['payment_status']),
            ];
            $follow =$this->create_follow($follow_data);
            //记录订单流   ------end
            return true;
        } catch (\Exception $exc) {
            return false;
        }
    }
    /**
     * 状态同步 -- 支付失败
     * @params int  $order_id	订单ID
     * @return boolean	true：成功；false：失败
     */
    public function notify_payment_failed( $order_id ){
	// 更新订单  payment_status
        try {
            $data = array(
                'step_status'=> zuji\order\OrderStatus::StepPayment,//支付阶段
                'payment_status'=> zuji\order\PaymentStatus::PaymentFailed,//支付失败
            );
            //更新订单
            $order_result = $this->order2_table->notify_payment_except_success( $order_id, $data );
            //验证订单更新是否成功
            if( !$order_result ) {//业务处理不成功
                return false;
            }
            // 记录订单流   ------begin
            $follow_data =[
                'order_id' =>$order_id,
                'step_status' =>intval($data['step_status']),
                'order_status' =>OrderStatus::OrderCreated,
                'follow_status'=>intval($data['payment_status']),
            ];
            $follow =$this->create_follow($follow_data);
            //记录订单流   ------end
            return true;
        } catch (\Exception $exc) {
            return false;
        }
    }


    /**
     * 状态同步 -- 进入发货阶段
     * @params int  $order_id	订单ID
     * @params int  $delivery_id	发货单ID
     * @return boolean	true：成功；false：失败
     */
    public function enter_delivery( $order_id, $delivery_id ,$delivery_status){
	// 更新订单 status，step_status, delivery_id, delivery_status
        try {
            $data = array(
                'delivery_status'=> $delivery_status,//订单待发货
                'step_status'=> zuji\order\OrderStatus::StepDelivery,//订单发货阶段
                'delivery_id'=> $delivery_id,//发货单id
            );
            //更新订单
            $order_result = $this->order2_table->enter_delivery( $order_id, $data );
            //验证订单更新是否成功
            if( !$order_result ) {//业务处理不成功
                return false;
            }
            // 记录订单流   ------begin
            $follow_data =[
                'order_id' =>$order_id,
                'step_status' =>intval($data['step_status']),
                'order_status' =>OrderStatus::OrderCreated,
                'follow_status'=>intval($data['delivery_status']),
            ];
            $follow =$this->create_follow($follow_data);
            //记录订单流   ------end
            return true;
        } catch (\Exception $exc) {
            return false;
        }
    }
    /**
     * 状态同步 -- 生成租机协议
     * @params int  $order_id	订单ID
     * @return boolean	true：成功；false：失败
     */
    public function notify_delivery_protocoled( $order_id, $protocol_no='' ){
	// 更新订单 delivery_status
        try {
            $data = array(
                'step_status'=> zuji\order\OrderStatus::StepDelivery,//订单发货阶段
                'delivery_status'=> zuji\order\DeliveryStatus::DeliveryProtocol,//生成租机协议
                'protocol_no' => $protocol_no,//协议编号
            );
            //更新订单
            $order_result = $this->order2_table->notify_delivery_protocoled( $order_id, $data );
            //验证订单更新是否成功
            if( !$order_result ) {//业务处理不成功
                return false;
            }
            return true;
        } catch (\Exception $exc) {
            return false;
        }
    }
    /**
     * 状态同步 -- 已发货
     * @params int  $order_id	订单ID
     * @return boolean	true：成功；false：失败
     */
    public function notify_delivery_send( $order_id ){
	// 更新订单 delivery_status
        try {
            $data = array(
                'step_status'=> zuji\order\OrderStatus::StepDelivery,//订单发货阶段
                'delivery_status'=> zuji\order\DeliveryStatus::DeliverySend,//订单已发货
            );
            //更新订单
            $order_result = $this->order2_table->notify_delivery( $order_id, $data );
            //验证订单更新是否成功
            if( !$order_result ) {//业务处理不成功
                return false;
            }
            // 记录订单流   ------begin
            $follow_data =[
                'order_id' =>$order_id,
                'step_status' =>intval($data['step_status']),
                'order_status' =>OrderStatus::OrderCreated,
                'follow_status'=>intval($data['delivery_status']),
            ];
            $follow =$this->create_follow($follow_data);
            //记录订单流   ------end
            return true;
        } catch (\Exception $exc) {
            return false;
        }
    }
    /**
     * 状态同步 -- 确认收货
     * @params int  $order_id	订单ID
     * @return boolean	true：成功；false：失败
     */
    public function notify_delivery_finished( $order_id ){
	// 更新订单 delivery_status
        try {
            $data = array(
                //'step_status'=> zuji\order\OrderStatus::StepService,//订单发货阶段
                //'delivery_status'=> zuji\order\DeliveryStatus::DeliveryConfirmed,//订单已确认收货
                'step_status'=> zuji\order\OrderStatus::StepService,//订单发货阶段
                'delivery_status'=> zuji\order\DeliveryStatus::DeliveryConfirmed,//订单已确认收货
                'service_status'=> zuji\order\ServiceStatus::ServiceOpen,
            );
            //更新订单
            $order_result = $this->order2_table->notify_delivery( $order_id, $data );
            //验证订单更新是否成功
            if( !$order_result ) {//业务处理不成功
		      set_error('同步订单[确认收货]状态失败');
                return false;
            }
            // 记录订单流   ------begin
            $follow_data =[
                'order_id' =>$order_id,
                'step_status' =>intval($data['step_status']),
                'order_status' =>OrderStatus::OrderCreated,
                'follow_status'=>intval($data['service_status']),
            ];
            $follow =$this->create_follow($follow_data);
            //记录订单流   ------end  
            return true;
        } catch (\Exception $exc) {
	    set_error( '同步订单[确认收货]状态异常' );
            return false;
        }
    }
    /**
     * 状态同步 -- 客户拒签
     * @params int  $order_id	订单ID
     * @return boolean	true：成功；false：失败
     */
//     public function notify_delivery_refuse( $order_id ){
// 	// 更新订单 delivery_status
//         try {
//             $data = array(
//                 'step_status'=> zuji\order\OrderStatus::StepDelivery,//订单发货阶段
//                 'delivery_status'=> zuji\order\DeliveryStatus::DeliveryRefuse,//订单拒签
//             );
//             //更新订单
//             $order_result = $this->order2_table->notify_delivery( $order_id, $data );
//             //验证订单更新是否成功
//             if( !$order_result ) {//业务处理不成功
// 		      set_error('同步订单[客户拒签]状态失败');
//                 return false;
//             }
//             // 记录订单流   ------begin
//             $follow_data =[
//                 'order_id' =>$order_id,
//                 'step_status' =>intval($data['step_status']),
//                 'order_status' =>OrderStatus::OrderCreated,
//                 'follow_status'=>intval($data['delivery_status']),
//             ];
//             $follow =$this->create_follow($follow_data);
//             if(!$follow){
//                 return false;
//             }
//             //记录订单流   ------end  
//             return true;
//         } catch (\Exception $exc) {
// 	    set_error( '同步订单[客户拒签]状态异常' );
//             return false;
//         }
//     }
    /**
     * 状态同步 -- 客户回寄拒签
     * @params int  $order_id	订单ID
     * @return boolean	true：成功；false：失败
     */
    public function notify_delivery_return_refuse( $order_id ){
	// 更新订单 delivery_status
        try {
            $data = array(
                'step_status'=> zuji\order\OrderStatus::StepService,//订单服务阶段
                'service_status'=> zuji\order\ServiceStatus::ServiceOpen,//租用中
            );
            //更新订单
            $order_result = $this->order2_table->notify_delivery_return_refuse( $order_id, $data );
            //验证订单更新是否成功
            if( !$order_result ) {//业务处理不成功
		      set_error('同步订单[客户回寄拒签]状态失败');
                return false;
            }
            // 记录订单流   ------begin
            $follow_data =[
                'order_id' =>$order_id,
                'step_status' =>intval($data['step_status']),
                'order_status' =>OrderStatus::OrderCreated,
                'follow_status'=>intval($data['service_status']),
            ];
            $follow =$this->create_follow($follow_data);
            //记录订单流   ------end  
            return true;
        } catch (\Exception $exc) {
	    set_error( '同步订单[客户回寄拒签]状态异常' );
            return false;
        }
    }
    
    /**
     * 状态同步 -- 取消发货状态
     * @params int  $order_id	订单ID
     * @return boolean	true：成功；false：失败
     */
    public function notify_delivery_canceled( $order_id ){
        // 更新订单 delivery_status
        try {
            $data = array(
                'step_status'=> zuji\order\OrderStatus::StepDelivery,//订单发货阶段
                'delivery_status'=> zuji\order\DeliveryStatus::DeliveryCanceled,//订单已确认收货
            );
            //更新订单
            $order_result = $this->order2_table->notify_delivery( $order_id, $data );
            //验证订单更新是否成功
            if( !$order_result ) {//业务处理不成功
                return false;
            }
            // 记录订单流   ------begin
            $follow_data =[
                'order_id' =>$order_id,
                'step_status' =>intval($data['step_status']),
                'order_status' =>OrderStatus::OrderCreated,
                'follow_status'=>intval($data['delivery_status']),
            ];
            $follow =$this->create_follow($follow_data);
            //记录订单流   ------end
            return true;
        } catch (\Exception $exc) {
            return false;
        }
    }


    /**
     * 状态同步 -- 进入退货申请阶段
     * @params int  $order_id	订单ID
     * @params int  $return_id	退货申请单ID
     * @return boolean	true：成功；false：失败
     */
    public function enter_return( $order_id, $return_id ){
	// 更新订单 status，step_status, return_id, return_status
        try {
            $data = array(
                'step_status'=> zuji\order\OrderStatus::StepReturn,//订单进入退货阶段
                'return_status'=> zuji\order\ReturnStatus::ReturnWaiting,//退货待审核
                'return_id'=> $return_id,//退货单id
            );
            //更新订单
            $order_result = $this->order2_table->enter_return( $order_id, $data );
            //验证订单更新是否成功
            if( !$order_result ) {//业务处理不成功
                return false;
            }
            
            // 记录订单流   ------begin
            $follow_data =[
                'order_id' =>$order_id,
                'step_status' =>intval($data['step_status']),
                'order_status' =>OrderStatus::OrderCreated,
                'follow_status'=>intval($data['return_status']),
            ];
            $follow =$this->create_follow($follow_data);
            //记录订单流   ------end
            return true;
        } catch (\Exception $exc) {
            return false;
        }
    }
    /**
     * 状态同步 -- 同意退货申请
     * @params int  $order_id	订单ID
     * @return boolean	true：成功；false：失败
     */
    public function notify_return_agreed( $order_id ){
	// 更新订单 return_status
        try {
            $data = array(
                'step_status'=> zuji\order\OrderStatus::StepReturn,//订单进入退货阶段
                'return_status'=> zuji\order\ReturnStatus::ReturnAgreed,//同意退货
            );
            //更新订单
            $order_result = $this->order2_table->notify_return( $order_id, $data );
            //验证订单更新是否成功
            if( !$order_result ) {//业务处理不成功
                return false;
            }
            // 记录订单流   ------begin
            $follow_data =[
                'order_id' =>$order_id,
                'step_status' =>intval($data['step_status']),
                'order_status' =>OrderStatus::OrderCreated,
                'follow_status'=>intval($data['return_status']),
            ];
            $follow =$this->create_follow($follow_data);
            //记录订单流   ------end
            return true;
        } catch (\Exception $exc) {
            return false;
        }
    }
    
    /**
     * 状态同步 -- 换货
     * @params int  $order_id	订单ID
     * @return boolean	true：成功；false：失败
     */
    public function notify_return_huanhuo( $order_id ){
        // 更新订单 return_status
        try {
            $data = array(
                'step_status'=> zuji\order\OrderStatus::StepReturn,//订单进入退货阶段
                'return_status'=> zuji\order\ReturnStatus::ReturnHuanhuo,//同意换货
            );
            //更新订单
            $order_result = $this->order2_table->notify_return( $order_id, $data );
            //验证订单更新是否成功
            if( !$order_result ) {//业务处理不成功
                return false;
            }
            // 记录订单流   ------begin
            $follow_data =[
                'order_id' =>$order_id,
                'step_status' =>intval($data['step_status']),
                'order_status' =>OrderStatus::OrderCreated,
                'follow_status'=>intval($data['return_status']),
            ];
            $follow =$this->create_follow($follow_data);
            //记录订单流   ------end
            return true;
        } catch (\Exception $exc) {
            return false;
        }
    }
    /**
     * 状态同步 -- 拒绝退货申请
     * @params int  $order_id	订单ID
     * @return boolean	true：成功；false：失败
     */
    public function notify_return_denied( $order_id ){
	// 更新订单 return_status
        try {
            $data = array(
                'step_status'=> zuji\order\OrderStatus::StepService,//订单进入退货阶段
                'return_status'=> zuji\order\ReturnStatus::ReturnDenied,//拒绝退货
            );
            //更新订单
            $order_result = $this->order2_table->notify_return( $order_id, $data );
            //验证订单更新是否成功
            if( !$order_result ) {//业务处理不成功
                return false;
            }
            // 记录订单流   ------begin
            $follow_data =[
                'order_id' =>$order_id,
                'step_status' =>intval($data['step_status']),
                'order_status' =>OrderStatus::OrderCreated,
                'follow_status'=>intval($data['return_status']),
            ];
            $follow =$this->create_follow($follow_data);
            //记录订单流   ------end
            return true;
        } catch (\Exception $exc) {
            return false;
        }
    }
    /**
     * 状态同步 -- 取消退货申请
     * @params int  $order_id	订单ID
     * @return boolean	true：成功；false：失败
     */
    public function notify_return_cancel( $order_id ){
        // 更新订单 return_status
        try {
            $data = array(
                'step_status'=> zuji\order\OrderStatus::StepService,//订单进入退货阶段
                'return_status'=> zuji\order\ReturnStatus::ReturnCanceled,//取消退货
            );
            //更新订单
            $order_result = $this->order2_table->notify_return( $order_id, $data );
            //验证订单更新是否成功
            if( !$order_result ) {//业务处理不成功
                return false;
            }
            // 记录订单流   ------begin
            $follow_data =[
                'order_id' =>$order_id,
                'step_status' =>intval($data['step_status']),
                'order_status' =>OrderStatus::OrderCreated,
                'follow_status'=>intval($data['return_status']),
            ];
            $follow =$this->create_follow($follow_data);
            //记录订单流   ------end
            return true;
        } catch (\Exception $exc) {
            return false;
        }
    }
    /**
     * 状态同步 -- 进入收货阶段
     * @params int  $order_id	订单ID
     * @params int  $receive_id	收货单ID
     * @return boolean	true：成功；false：失败
     */
    public function enter_receive( $order_id, $receive_id ){
	// 更新订单 status，step_status, receive_id, receive_status
        try {
            $data = array(
                'step_status'=> zuji\order\OrderStatus::StepReceive,//订单进入收货阶段
                'receive_status'=> zuji\order\ReceiveStatus::ReceiveCreated,//收货待审核状态
                'receive_id'=> $receive_id,//退货单id
            );
            //更新订单
            $order_result = $this->order2_table->enter_receive( $order_id, $data );
            //验证订单更新是否成功
            if( !$order_result ) {//业务处理不成功
                return false;
            }
            // 记录订单流   ------begin
            $follow_data =[
                'order_id' =>$order_id,
                'step_status' =>intval($data['step_status']),
                'order_status' =>OrderStatus::OrderCreated,
                'follow_status'=>intval($data['receive_status']),
            ];
            $follow =$this->create_follow($follow_data);
            //记录订单流   ------end
            return true;
        } catch (\Exception $exc) {
            return false;
        }
    }
    /**
     * 状态同步 -- 待收货
     * @params int  $order_id	订单ID
     * @return boolean	true：成功；false：失败
     */
    public function notify_receive_waiting( $order_id ){
	// 更新订单  receive_status
        try {
            $data = array(
                'step_status'=> zuji\order\OrderStatus::StepReceive,//订单进入收货阶段
                'receive_status'=> zuji\order\ReceiveStatus::ReceiveWaiting,//待收货状态
            );
            //更新订单
            $order_result = $this->order2_table->notify_receive( $order_id, $data );
            //验证订单更新是否成功
            if( !$order_result ) {//业务处理不成功
                return false;
            }
            // 记录订单流   ------begin
            $follow_data =[
                'order_id' =>$order_id,
                'step_status' =>intval($data['step_status']),
                'order_status' =>OrderStatus::OrderCreated,
                'follow_status'=>intval($data['receive_status']),
            ];
            $follow =$this->create_follow($follow_data);
            //记录订单流   ------end
            return true;
        } catch (\Exception $exc) {
            return false;
        }
    }
    /**
     * 状态同步 --确认收货
     * @params int  $order_id	订单ID
     * @return boolean	true：成功；false：失败
     */
    public function notify_receive_confirmed( $order_id ){
	// 更新订单  receive_status
        try {
            $data = array(
                'step_status'=> zuji\order\OrderStatus::StepReceive,//订单进入收货阶段
                'receive_status'=> zuji\order\ReceiveStatus::ReceiveConfirmed,//确认收货状态
            );
            //更新订单
            $order_result = $this->order2_table->notify_receive( $order_id, $data );
            //验证订单更新是否成功
            if( !$order_result ) {//业务处理不成功
                return false;
            }
            
            // 记录订单流   ------begin
            $follow_data =[
                'order_id' =>$order_id,
                'step_status' =>intval($data['step_status']),
                'order_status' =>OrderStatus::OrderCreated,
                'follow_status'=>intval($data['receive_status']),
            ];
            $follow =$this->create_follow($follow_data);
            //记录订单流   ------end
            return true;
        } catch (\Exception $exc) {
            return false;
        }
    }
    
    /**
     * 状态同步 --收货单取消
     * @params int  $order_id	订单ID
     * @return boolean	true：成功；false：失败
     */
    public function notify_receive_cancel( $order_id ){
        // 更新订单  receive_status
        try {
            $data = array(
                'step_status'=> zuji\order\OrderStatus::StepService,//订单进入收货阶段
                'receive_status'=> zuji\order\ReceiveStatus::ReceiveCanceled,//取消
            );
            //更新订单
            $order_result = $this->order2_table->notify_receive( $order_id, $data );
            //验证订单更新是否成功
            if( !$order_result ) {//业务处理不成功
                return false;
            }
            
            // 记录订单流   ------begin
            $follow_data =[
                'order_id' =>$order_id,
                'step_status' =>intval($data['step_status']),
                'order_status' =>OrderStatus::OrderCreated,
                'follow_status'=>intval($data['receive_status']),
            ];
            $follow =$this->create_follow($follow_data);
            //记录订单流   ------end
            return true;
        } catch (\Exception $exc) {
            return false;
        }
    }
    /**
     * 状态同步 --收货单结束
     * @params int  $order_id	订单ID
     * @return boolean	true：成功；false：失败
     */
    public function notify_receive_finished( $order_id ){
	// 更新订单  receive_status
        try {
            $data = array(
                'step_status'=> zuji\order\OrderStatus::StepReceive,//订单进入收货阶段
                'receive_status'=> zuji\order\ReceiveStatus::ReceiveFinished,//收货结束
            );
            //更新订单
            $order_result = $this->order2_table->notify_receive( $order_id, $data );
            //验证订单更新是否成功
            if( !$order_result ) {//业务处理不成功
                return false;
            }
            
            // 记录订单流   ------begin
            $follow_data =[
                'order_id' =>$order_id,
                'step_status' =>intval($data['step_status']),
                'order_status' =>OrderStatus::OrderCreated,
                'follow_status'=>intval($data['receive_status']),
            ];
            $follow =$this->create_follow($follow_data);
            //记录订单流   ------end
            return true;
        } catch (\Exception $exc) {
            return false;
        }
    }


    /**
     * 状态同步 -- 进入检测阶段
     * @params int  $order_id	订单ID
     * @params int  $evaluation_id	检测单ID
     * @return boolean	true：成功；false：失败
     */
    public function enter_evaluation( $order_id, $evaluation_id ){
	// 更新订单 status，step_status, evaluation_id, evaluation_status
        try {
            $data = array(
                'step_status'=> zuji\order\OrderStatus::StepEvaluation,//订单进入检测
                'evaluation_status'=> zuji\order\EvaluationStatus::EvaluationCreated,//待检测
                'evaluation_id'=> $evaluation_id,//检测单id
            );
            //更新订单
            $order_result = $this->order2_table->enter_evaluation( $order_id, $data );
            //验证订单更新是否成功
            if( !$order_result ) {//业务处理不成功
                return false;
            }
            // 记录订单流   ------begin
            $follow_data =[
                'order_id' =>$order_id,
                'order_status' =>OrderStatus::OrderCreated,
                'step_status' =>  OrderStatus::StepEvaluation,
                'follow_status'=>intval($data['evaluation_status']),
            ];
            $follow =$this->create_follow($follow_data);
            //记录订单流   ------end
            return true;
        } catch (\Exception $exc) {
            return false;
        }
    }
    /**
     * 状态同步 -- 检测中
     * @params int  $order_id	订单ID
     * @return boolean	true：成功；false：失败
     */
    public function notify_evaluation_waiting( $order_id ){
	// 更新订单 evaluation_status
        try {
            $data = array(
                'evaluation_status'=> zuji\order\EvaluationStatus::EvaluationWaiting,//检测中
            );
            //更新订单
            $order_result = $this->order2_table->notify_evaluation_except_finished( $order_id, $data );
            //验证订单更新是否成功
            if( !$order_result ) {//业务处理不成功
                return false;
            }
            // 记录订单流   ------begin
            $follow_data =[
                'order_id' =>$order_id,
                'order_status' =>OrderStatus::OrderCreated,
                'step_status' =>  OrderStatus::StepEvaluation,
                'follow_status'=>intval($data['evaluation_status']),
            ];
            $follow =$this->create_follow($follow_data);
            //记录订单流   ------end
            return true;
        } catch (\Exception $exc) {
            return false;
        }
    }
    /**
     * 状态同步 -- 检测完成（拿到了检测报告，并没有确定是否符合标准）
     * @params int  $order_id	订单ID
     * @return boolean	true：成功；false：失败
     */
    public function notify_evaluation_finished( $order_id ){
	// 更新订单 evaluation_status
        try {
            $data = array(
                'evaluation_status'=> zuji\order\EvaluationStatus::EvaluationFinished,//检测完成
            );
            //更新订单
            $order_result = $this->order2_table->notify_evaluation_finished( $order_id, $data );
            //验证订单更新是否成功
            if( !$order_result ) {//业务处理不成功
                return false;
            }
            // 记录订单流   ------begin
            $follow_data =[
                'order_id' =>$order_id,
                'order_status' =>OrderStatus::OrderCreated,
                'step_status' =>  OrderStatus::StepEvaluation,
                'follow_status'=>intval($data['evaluation_status']),
            ];
            $follow =$this->create_follow($follow_data);
            //记录订单流   ------end
            return true;
        } catch (\Exception $exc) {
            return false;
        }
    }
    /**
     * 状态同步 -- 检测合格或不合格
     * @params int  $order_id	订单ID
     * @params int  $evaluation_status	检测状态
     * @return boolean	true：成功；false：失败
     */
    public function notify_evaluation_status( $order_id, $evaluation_status ){
	// 更新订单 evaluation_status
        try {
            $data = array(
                'evaluation_status'=> $evaluation_status,
            );
            //更新订单
            $order_result = $this->order2_table->notify_evaluation_finished( $order_id, $data );
            if( !$order_result ) {
                return false;
            }
            // 记录订单流   ------begin
            $follow_data =[
                'order_id' =>$order_id,
                'order_status' =>OrderStatus::OrderCreated,
                'step_status' =>  OrderStatus::StepEvaluation,
                'follow_status'=>intval($data['evaluation_status']),
            ];
            $follow =$this->create_follow($follow_data);
            //记录订单流   ------end
            return true;
        } catch (\Exception $exc) {
            return false;
        }
    }
//    /**
//     * 状态同步 -- 检测不合格（改用 notify_evaluation_status ）
//     * @params int  $order_id	订单ID
//     * @return boolean	true：成功；false：失败
//     */
//    public function notify_evaluation_unqualified( $order_id ){
//	// 更新订单 evaluation_status
//        try {
//            $data = array(
//                'evaluation_status'=> zuji\order\EvaluationStatus::EvaluationUnQualified,//不合格
//            );
//            //更新订单
//            $order_result = $this->order2_table->notify_evaluation_finished( $order_id, $data );
//            if( !$order_result ) {
//                return false;
//            }
//            // 记录订单流   ------begin
//            $follow_data =[
//                'order_id' =>$order_id,
//                'order_status' =>OrderStatus::OrderCreated,
//                'step_status' =>  OrderStatus::StepEvaluation,
//                'follow_status'=>intval($data['evaluation_status']),
//            ];
//            $follow =$this->create_follow($follow_data);
//            if(!$follow){
//                return false;
//            }
//            //记录订单流   ------end
//            return true;
//        } catch (\Exception $exc) {
//            return false;
//        }
//    }
    /**
     * 状态同步 -- 进入服务阶段
     * @params int  $order_id	订单ID
     * @params int  $service_id	服务单ID
     * @return boolean	true：成功；false：失败
     */
    public function enter_service( $order_id, $service_id ){
        try {
            $data = array(
                'step_status'=> zuji\order\OrderStatus::StepService,// 服务阶段
                'service_status'=> zuji\order\ServiceStatus::ServiceOpen,//创建退款单
                'service_id'=> $service_id,//退款单id
            );
            //更新订单
            $order_result = $this->order2_table->enter_service( $order_id, $data );
            //验证订单更新是否成功
            if( !$order_result ) {//业务处理不成功
                return false;
            }
            // 记录订单流   ------begin
            $follow_data =[
                'order_id' =>$order_id,
                'step_status' =>intval($data['step_status']),
                'order_status' =>OrderStatus::OrderCreated,
                'follow_status'=>intval($data['service_status']),
            ];
            $follow =$this->create_follow($follow_data);
            //记录订单流   ------end
            return true;
        } catch (\Exception $exc) {
            return false;
        }
    }
    /**
     * 状态同步 -- 服务取消
     * @params int  $order_id	订单ID
     * @return boolean	true：成功；false：失败
     */
    public function notify_service_cancel( $order_id ){
        // 更新订单 refund_status
        try {
            $data = array(
                'step_status'=> zuji\order\OrderStatus::StepService,// 服务阶段
                'service_status'=> zuji\order\ServiceStatus::ServiceCancel,//待退款状态
            );
            //更新订单
            $order_result = $this->order2_table->notify_service( $order_id, $data );
            //验证订单更新是否成功
            if( !$order_result ) {//业务处理不成功
                return false;
            }
            
            // 记录订单流   ------begin
            $follow_data =[
                'order_id' =>$order_id,
                'step_status' =>intval($data['step_status']),
                'order_status' =>OrderStatus::OrderCreated,
                'follow_status'=>intval($data['service_status']),
            ];
            $follow =$this->create_follow($follow_data);
            //记录订单流   ------end
            
            
            return true;
        } catch (\Exception $exc) {
            return false;
        }
    }
    
    /**
     * 状态同步 -- 服务关闭
     * @params int  $order_id	订单ID
     * @return boolean	true：成功；false：失败
     */
    public function notify_service_close( $order_id ){
        // 更新订单 refund_status
        try {
            $data = array(
                'service_status'=> zuji\order\ServiceStatus::ServiceClose,//待退款状态
            );
            //更新订单
            $order_result = $this->order2_table->notify_service( $order_id, $data );
            //验证订单更新是否成功
            if( !$order_result ) {//业务处理不成功
                return false;
            }
            // 记录订单流   ------begin
            $follow_data =[
                'order_id' =>$order_id,
                'order_status' =>OrderStatus::OrderCreated,
                'follow_status'=>intval($data['service_status']),
            ];
            $follow =$this->create_follow($follow_data);
            //记录订单流   ------end
            return true;
        } catch (\Exception $exc) {
            return false;
        }
    }
    /**
     * 状态同步 -- 服务开启
     * @params int  $order_id	订单ID
     * @return boolean	true：成功；false：失败
     */
    public function notify_service_open( $order_id ){
        // 更新订单 refund_status
        try {
            $data = array(
                'service_status'=> zuji\order\ServiceStatus::ServiceOpen,
            );
            //更新订单
            $order_result = $this->order2_table->notify_service( $order_id, $data );
            //验证订单更新是否成功
            if( !$order_result ) {//业务处理不成功
                return false;
            }
            // 记录订单流   ------begin
            $follow_data =[
                'order_id' =>$order_id,
                'order_status' =>OrderStatus::OrderCreated,
                'follow_status'=>intval($data['service_status']),
            ];
            $follow =$this->create_follow($follow_data);
            //记录订单流   ------end
            return true;
        } catch (\Exception $exc) {
            return false;
        }
    }
    /**
     * 状态同步 -- 进入退款阶段
     * @params int  $order_id	订单ID
     * @params int  $refund_id	退款单ID
     * @return boolean	true：成功；false：失败
     */
    public function enter_refund( $order_id, $refund_id ){
	// 更新订单 status，step_status, refund_id, refund_status
        try {
            $data = array(
                'step_status'=> zuji\order\OrderStatus::StepRefund,//订单进入退款阶段
                'refund_status'=> zuji\order\RefundStatus::RefundCreated,//创建退款单
                'refund_id'=> $refund_id,//退款单id
            );
            //更新订单
            $order_result = $this->order2_table->enter_refund( $order_id, $data );
            //验证订单更新是否成功
            if( !$order_result ) {//业务处理不成功
                return false;
            }
            // 记录订单流   ------begin
            $follow_data =[
                'order_id' =>$order_id,
                'step_status' =>intval($data['step_status']),
                'order_status' =>OrderStatus::OrderCreated,
                'follow_status'=>intval($data['refund_status']),
            ];
            $follow =$this->create_follow($follow_data);
            //记录订单流   ------end
            return true;
        } catch (\Exception $exc) {
            return false;
        }
    }
    /**
     * 状态同步 -- 退款中
     * @params int  $order_id	订单ID
     * @return boolean	true：成功；false：失败
     */
    public function notify_refund_waiting( $order_id ){
	// 更新订单 refund_status
        try {
            $data = array(
                'step_status'=> zuji\order\OrderStatus::StepRefund,//订单进入退款阶段
                'refund_status'=> zuji\order\RefundStatus::RefundWaiting,//待退款状态
            );
            //更新订单
            $order_result = $this->order2_table->notify_refund_waiting( $order_id, $data );
            //验证订单更新是否成功
            if( !$order_result ) {//业务处理不成功
                return false;
            }
            // 记录订单流   ------begin
            $follow_data =[
                'order_id' =>$order_id,
                'step_status' =>intval($data['step_status']),
                'order_status' =>OrderStatus::OrderCreated,
                'follow_status'=>intval($data['refund_status']),
            ];
            $follow =$this->create_follow($follow_data);
            //记录订单流   ------end
            return true;
        } catch (\Exception $exc) {
            return false;
        }
    }
    /**
     * 状态同步 -- 退款成功
     * @params int  $order_id	订单ID
     * @params int  $refund_amount	实际退款金额（单位：分）
     * @return boolean	true：成功；false：失败
     */
    public function notify_refund_success( $order_id, $refund_amount ){
	// 更新订单 refund_status, refund_time, refund_amount
        try {
            $data = array(
                'step_status'=> zuji\order\OrderStatus::StepRefund,//订单进入退款阶段
                'refund_status'=> zuji\order\RefundStatus::RefundSuccessful,//退款成功
                'order_status'=>OrderStatus::OrderCanceled,
                'refund_time'=> time(),//退款时间
                'refund_amount'=> intval($refund_amount),//退款金额（分）
            );
            //更新订单
            $order_result = $this->order2_table->notify_refund_expect_success( $order_id, $data );
            //验证订单更新是否成功
            if( !$order_result ) {//业务处理不成功
                return false;
            }
            // 记录订单流   ------begin
            $follow_data =[
                'order_id' =>$order_id,
                'step_status' =>intval($data['step_status']),
                'order_status' =>OrderStatus::OrderCanceled,
                'follow_status'=>intval($data['refund_status']),
            ];
            $follow =$this->create_follow($follow_data);
            //记录订单流   ------end
            return true;
        } catch (\Exception $exc) {
            return false;
        }
    }
    /**
     * 状态同步 -- 退款失败
     * @params int  $order_id	订单ID
     * @return boolean	true：成功；false：失败
     */
    public function notify_refund_failed( $order_id ){
        try {
            // 更新订单 refund_status
            $data = array(
                'step_status'=> zuji\order\OrderStatus::StepRefund,//订单进入退款阶段
                'refund_status'=> zuji\order\RefundStatus::RefundFailed,//退款失败
            );
            //更新订单
            $order_result = $this->order2_table->notify_refund_expect_success( $order_id, $data );
            //验证订单更新是否成功
            if( !$order_result ) {//业务处理不成功
                return false;
            }
            // 记录订单流   ------begin
            $follow_data =[
                'order_id' =>$order_id,
                'step_status' =>intval($data['step_status']),
                'order_status' =>OrderStatus::OrderCreated,
                'follow_status'=>intval($data['refund_status']),
            ];
            $follow =$this->create_follow($follow_data);
            //记录订单流   ------end
            return true;
        } catch (\Exception $exc) {
            return false;
        }
    }
    /**
     * 状态同步 -- 退款成功 -- 修改订单状态   
     * @params int  $order_id	订单ID
     * @return boolean	true：成功；false：失败
     */
    public function close_order($order_id){
        try {
            $data = array(
                'order_status' =>OrderStatus::OrderCanceled,
            );
            //更新订单
            $order_result = $this->order2_table->close_order( $order_id, $data );
            //验证订单更新是否成功
            if( !$order_result ) {//业务处理不成功
                return false;
            }
            // 记录订单流   ------begin
            $follow_data =[
                'order_id' =>$order_id,
                'order_status' =>intval($data['order_status']),
            ];
            $follow =$this->create_follow($follow_data);
            //记录订单流   ------end
            return true;
        } catch (\Exception $exc) {
            return false;
        }
    }
    
    /**
     * 状态同步 -- 开启订单
     * @params int  $order_id	订单ID
     * @return boolean	true：成功；false：失败
     */
    public function open_order($order_id){
        try {
            $data = array(
                'order_status' =>OrderStatus::OrderCreated,
            );
            //更新订单
            $order_result = $this->order2_table->close_order( $order_id, $data );
            //验证订单更新是否成功
            if( !$order_result ) {//业务处理不成功
                return false;
            }
            // 记录订单流   ------begin
            $follow_data =[
                'order_id' =>$order_id,
                'order_status' =>intval($data['order_status']),
            ];
            $follow =$this->create_follow($follow_data);
            //记录订单流   ------end
            return true;
        } catch (\Exception $exc) {
            return false;
        }
    }
    
    /**
     * 状态同步 -- 订单完成。
     * @params int  $order_id	订单ID
     * @return boolean	true：成功；false：失败
     */
    public function order_finished($order_id){
        try {
            $data = array(
                'order_status' =>OrderStatus::OrderFinished,
            );
            //更新订单
            $order_result = $this->order2_table->close_order( $order_id, $data );
            //验证订单更新是否成功
            if( !$order_result ) {//业务处理不成功
                return false;
            }
            // 记录订单流   ------begin
            $follow_data =[
                'order_id' =>$order_id,
                'order_status' =>intval($data['order_status']),
            ];
            $follow =$this->create_follow($follow_data);
            //记录订单流   ------end
            return true;
        } catch (\Exception $exc) {
            return false;
        }
    }


    //-+------------------------------------------------------------------------
    // | 原来方法
    //-+------------------------------------------------------------------------
//
//    /**
//     * 计算转换钱
//     *      分转元
//     */
//    public function sub_money($n){
//        return $n*0.01;
//    }
//    /**
//     * 获取订单状态名称
//     * 如果 不存在 返回空
//     * @param int $status 【必须】 订单状态 id
//     * @return string 订单状态名称
//     * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
//     */
//    
//    /**
//     * 修改订单支付状态
//     * @param $where array
//     * [
//     *      id              =>'',   【必须】int 订单ID
//     *      payment_status  =>'',   【必须】int 支付状态
//     *      payment_id      =>'',   【必须】 int 支付单ID
//     * ]
//     * @return boolean
//     * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
//     */
//      public function edit_payment_status($where){
//          	// 参数过滤
//        	$where = filter_array($where, [
//        	    'id' => 'required|is_id',
//        	    'payment_status' => 'required|is_int',
//        	    'payment_id'=> 'required|is_id',
//        	]);
//        	if( count($where) <3 ){ return false;}	
//        	        //如果订单开启 并且阶段状态在初始化中 更改阶段状态 和支付状态
//        	        $data=array(
//        	            "id"=>$where['id'],
//        	            "step_status"=>OrderStatus::StepPayment,
//        	            "payment_status"=>$where['payment_status'],
//        	            "payment_id"=>$where['payment_id']        
//        	        );
//        	        return $this->table->edit_status($data);
//
//      } 
//      
//      /**
//       * 修改订单发货状态
//       * @param $where array
//       * [
//       *      id              =>'',    【必须】int 订单ID
//       *      delivery_status  =>'',   【必须】int 发货状态
//       *      delivery_id      =>'',   【必须】 int 发货单ID
//       * ]
//       * @return boolean
//       * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
//       */
//      public function edit_delivery_status($where){
//          // 参数过滤
//          $where = filter_array($where, [
//              'id' => 'required|is_id',
//              'delivery_status' => 'required|is_int',
//              'delivery_id'=> 'required|is_id',
//          ]);
//          if( count($where) <3 ){ return false;}
//          $data=array(
//              "id"=>$where['id'],
//              "step_status"=>OrderStatus::StepDelivery,
//              "delivery_status"=>$where['delivery_status'],
//              "delivery_id"=>$where['delivery_id']
//          );
//          return $this->table->edit_status($data);
//      }
//      
//      /**
//       * 修改订单退货申请状态
//       * @param $where array
//       * [
//       *      id              =>'',    【必须】int 订单ID
//       *      return_status  =>'',   【必须】int 发货状态
//       *      return_id      =>'',   【必须】 int 发货单ID
//       * ]
//       * @return boolean
//       * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
//       */
//      public function edit_return_status($where){
//          // 参数过滤
//          $where = filter_array($where, [
//              'id' => 'required|is_id',
//              'return_status' => 'required|is_int',
//              'return_id'=> 'required|is_id',
//          ]);
//          if( count($where) <3 ){ return false;}
//          $data=array(
//              "id"=>$where['id'],
//              "step_status"=>OrderStatus::StepReturn,
//              "return_status"=>$where['return_status'],
//              "return_id"=>$where['return_id']
//          );
//          return $this->table->edit_status($data);
//      }
//      
//      /**
//       *  根据支付状态来修改订单表
//       * @param $where array
//       * [
//       *      id              =>'',   【必须】int 订单ID
//       *      payment_status  =>'',   【必须】int 支付状态：0初始化1待支付，2支付成功，3支付超时，4支付失败 5,部分支付
//       *      payment_amount    =>''    【可选】 int 实际支付价格
//       *      
//       * ]
//       * @return boolean
//       * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
//       * */
//      
//      public function edit_status($where){
//          // 参数过滤
//          $where = filter_array($where, [
//              'id' => 'required|is_id',
//              'payment_status' => 'required|is_int',
//          ]);
//          if( count($where) <2 ){ return false;}  
//          return $this->table->edit_status($where);
//      }
//      
//      /**
//       * 把租机协议填入到订单表中
//       * [
//       *      id              =>'',   【必须】int 订单ID
//       *      protocol  =>'',         【必须】string 协议号
//       *      
//       * ]
//       * @return boolean
//       * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
//       * */
//      public function edit_protocol($where){
//          // 参数过滤
//          $where['id']=intval($where['id']);
//          $where = filter_array($where, [
//              'id' => 'required|is_id',
//              'protocol' => 'required',
//          ]);
//          if( count($where) <2 ){ return false;}
//          return $this->table->edit_protocol($where);
//      }
//       /**
//       * 根据ID 和其他单的状态  来判断 支付状态  是否可以使用 更改
//       * @param $where array
//       * [
//       *      id              =>'',   【必须】int 订单ID
//       *      payment_status  =>'',   【可选】int 支付状态
//       *      delivery_status =>'',   【可选】int 发货状态：0：初始化；1：待发货:；2：已发货；3：确认收货
//       *      return_status  =>'',    【可选】int 退货申请状态：0初始化，1待审核，2同意退货，3不同意退货
//       *      receive_status  =>'',   【可选】int 收货单状态：0初始化，1待收货，2已收货，3已拒签
//       *      evaluation_status  =>'',【可选】int 检测状态：0初始化，1待检测，2检测合格，3检测不合格
//       *      refund_status  =>'',    【可选】int 退款状态：0初始化，1待退款，2退款成功，3退款失败
//       *      service_status  =>'',   【可选】int（暂时忽略）使用状态：0初始化，1使用中，2使用结束
//       * ]
//       * @return boolean
//       * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
//       */
//      public function check_order_use($where){
//          // 参数过滤
//          $where = filter_array($where, [
//              'id' => 'required|is_id',
//              'payment_status' => 'required|is_int',
//              'delivery_status' => 'required|is_int',
//              'return_status' => 'required|is_int',
//              'receive_status' => 'required|is_int',
//              'evaluation_status' => 'required|is_int',
//              'refund_status' => 'required|is_int',
//              'service_status' => 'required|is_int',
//              
//          ]);
//
//          if( count($where) <1 ){ return false;}
//          
//          $order =$this->get_order_findid($where['id']);
//
//          if(!$order){return false;}
//          if($order['order_status']!=OrderStatus::OrderCreated){return false;}
//          
//          $jieduan =$order['step_status'];
//          $order_status =$order['order_status'];
//     
//          //传入支付单状态 
//          if(isset($where['payment_status'])){    
//              if($where['payment_status']==PaymentStatus::PaymentCreated){
//                  //生成支付单
//                  if($jieduan==OrderStatus::StepInitialize){return true;}
//              }else{
//                  if($jieduan==OrderStatus::StepPayment){return true; }
//              }
//              
//          }
//          
//          if(isset($where['delivery_status'])){
//              if($where['delivery_status']==DeliveryStatus::DeliveryCreated){
//                  //生成发货单
//                  if($jieduan==OrderStatus::StepPayment){return true;}
//              }else{
//                  if($jieduan==OrderStatus::StepDelivery){return true;}
//              }
//          }
//          if(isset($where['return_status'])){
//              if($where['return_status']==ReturnStatus::ReturnCreated){
//                  //生成退货单
//                  if($jieduan==OrderStatus::StepDelivery || $jieduan==OrderStatus::StepService){return true;}
//              }else{
//                  if($jieduan==OrderStatus::StepReturn){return true;}
//              }
//          }
//          if(isset($where['receive_status'])){
//              if($where['receive_status']==ReceiveStatus::ReceiveCreated){
//                  //生成收货单
//                  if($jieduan==OrderStatus::StepReturn || $jieduan==OrderStatus::StepDelivery){return true;}
//              }else{
//                  if($jieduan==OrderStatus::StepService){return true;}
//              } 
//          }
//          if(isset($where['evaluation_status'])){
//              if($where['evaluation_status']==EvaluationStatus::EvaluationCreated){
//                  //生成检测
//                  if($jieduan==OrderStatus::StepReturn){return true;}
//              }else{
//                  if($jieduan==OrderStatus::StepEvaluation){return true;}
//              }
//          }
//          if(isset($where['refund_status'])){
//              if($where['return_status']==RefundStatus::RefundCreated){
//                  //生成退款单
//                  if($jieduan==OrderStatus::StepEvaluation){return true;}
//              }else{
//                  if($jieduan==OrderStatus::StepRefund){return true;}
//              }
//          }
///*           if(isset($where['service_status'])){
//              if($where['service_status']==ReturnStatus::ReturnCreated){
//                  //生成服务单  -- 暂时不做
//                  if($jieduan==OrderStatus::StepDelivery){return true;}
//              }else{
//                  if($jieduan==OrderStatus::StepReturn){return true;}
//              }
//          } */
//            return false;
//          
//      }

//
//    /**
//     * 取消订单方法
//     *
//     * @param integer $order_id     订单ID
//     * @return boolean true|false   是否成功(true成功,false失败)
//     */
//    public function set_order_quxiao($data){
//        $order_id = $data['order_id'];
//        $data['order_status'] = zuji\order\OrderStatus::OrderCanceled;
//        $data['update_time'] = time();
//        $result = $this->order2_table->cancel($order_id,$data);
//        return $result;
//    }
//
//
//
//    /**
//     * 修改订单状态
//     *
//     * @param array
//     * [
//     *      id          =>'',   订单ID(必填)
//     *      status      =>'',   订单状态(必填)
//     * ]
//     * @return boolean
//     */
//    public function update_order_status($where)
//    {
//        if(!$where['id'] || !$where['order_status']){
//            return false;
//        }
//        return $this->table->update_order_status($where);
//    }
//
//    /**
//     * 支付成功修改订单支付状态
//     *
//     * @param array
//     * [
//     *      id              =>'',   订单ID(必填)
//     *      payment_status  =>'',   支付状态：0初始化1待支付，2已支付，3支付超时，4支付失败
//     * ]
//     * @return boolean
//     */
//    public function update_order_payment_status($where)
//    {
//        if(!$where['id'] || !$where['payment_status']){
//            return false;
//        }
//        $order_info = $this->table->get_order_findid($where['id']);
//        //验证支付状态(订单开启并且订单支付阶段并且支付状态不等于已支付)
//        if($order_info['order_status']==1 && $order_info['step_status']==1 && $order_info['payment_status']!=1){
//            //修改支付状态
//            return $this->table->update_order_status($where);
//        }
//        return false;
//    }
//
//    /**
//     * 已发货修改订单发货状态
//     *
//     * @param array
//     * [
//     *      id              =>'',   订单ID(必填)
//     *      delivery_status    =>'',   发货状态：0待发货，1已发货，2确认收货
//     * ]
//     * @return boolean
//     */
//    public function update_order_delivery_status($where)
//    {
//        if(!$where['id'] || !$where['delivery_status']){
//            return false;
//        }
//        $order_info = $this->table->get_order_findid($where['id']);
//        //判断要修改的状态
//        if($where['delivery_status']==1){
//            //验证支付状态(订单开启并且订单支付阶段并且支付状态等于已支付并且发货状态等于待发货)
//            if($order_info['order_status']==1 & $order_info['step_status']==1 & $order_info['payment_status']==1 & $order_info['delivery_status']==0){
//                //修改支付状态
//                return $this->table->update_order_status($where);
//            }
//        }elseif ($where['delivery_status']==1){
//            //验证支付状态(订单开启 并且 发货阶段 并且 发货状态等于已发货 )
//            if($order_info['order_status']==1 & $order_info['step_status']==2 & $order_info['delivery_status']==1){
//                /**
//                 * 生成服务单(退货的时候服务关闭还是暂停?)
//                 */
//                //修改支付状态
//                $where['order_status']=3;//订单完成
//                $where['service_status']=1;//使用状态改为使用中
//                return $this->table->update_order_status($where);
//            }
//        }
//
//        return false;
//    }
//
//    public function order_status($status){
//        if(!isset($status)){return "";}
//            switch ($status)
//            {
//                case 0:
//                    return "初始化状态";
//                    break;
//                case 1:
//                    return "订单开启";
//                    break;
//                case 2:
//                    return "订单取消";
//                    break;
//                case 3:
//                    echo "订单完成";
//                    break;
//                default:
//                    return "未知";
//                    break;
//            }
//    }
    
    /**
     * 根据日期生成唯一订单号
     * @param boolean $refresh 	是否刷新再生成
     * @return string
     */
    private function _build_order_no($refresh = FALSE) {
        if ($refresh == TRUE) {
            return date('Ymd') . substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 12);
        }
        return date('YmdHis') . substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 6);
    }

    /**
     * 开启事务
     */
    public function startTrans(){
        return $this->order2_table->startTrans();
    }
    public function rollback(){
        return $this->order2_table->rollback();
    }
    public function commit(){
        return $this->order2_table->commit();
    }
    

}
