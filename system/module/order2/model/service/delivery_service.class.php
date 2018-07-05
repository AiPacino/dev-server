<?php
use zuji\order\DeliveryStatus;
use zuji\Business;
/**
 * 		物流服务层
 *      @author wangqiang<wangqiang@huishoubao.com.cn>
 */
class delivery_service extends service {

	public function _initialize() {
            $this->order_service = $this->load->service('order2/order');
            $this->logistics = $this->load->table('order2/logistics');
            $this->delivery_table = $this->load->table('order2/order2_delivery');
            $this->goods_service= $this->load->service('order2/goods');
            $this->service_service= $this->load->service('order2/service');
	}
    /**
     * 创建发货单 （入口方法名称）
     * @param array   $data	【可选】
     * array(
     *      'order_id' => '',     // 【必选】  int 订单ID
     *      'order_no' => '',     // 【必选】  string 订单编号
     *      'goods_id' => '',     // 【必选】  int 商品ID
     *      'address_id '=>''     // 【必选】  int 订单收货地址ID
     *      'business_key'=>''    // 【必选】  int 业务类型
     *      'evaluation_id'=>''     //【可选】 int 检测回寄时 加上检测单ID
     *      
     * )
     * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
     * @return mixed  false：失败；int（插入的主键id）:成功
     */
    public function create($data){
        $data = filter_array($data, [
            'order_id' => 'required|is_id',
            'order_no' => 'required',
            'goods_id' =>'required|is_id',
            'address_id' =>'required|is_id',
            'business_key' =>'required|is_id',
            'evaluation_id'=>'required|is_id',
        ]);
        if( count($data)<5 ){ 
            set_error('参数错误');
            return false;
        }
        $data['delivery_status'] = DeliveryStatus::DeliveryWaiting;
        try {
            $delivery_id = $this->delivery_table->create_delivery($data);
            if(!$delivery_id){
                set_error('发货单保存失败');
                $this->delivery_table->rollback();
                return false;
            }
         //   if($data['business_key'] == Business::BUSINESS_ZUJI){
                $enter_delivery =$this->order_service->enter_delivery($data['order_id'],$delivery_id,$data['delivery_status']);
                if(!$enter_delivery){
                    set_error('创建发货单状态同步失败');
                    $this->delivery_table->rollback();
                    return false;
                }
        //    }
            
        } catch (\Exception $exc) {
            set_error('创建发货单异常：'.$exc->getMessage());
            // 关闭事务
            $this->delivery_table->rollback();
            return false;
        }
        // 提交事务
        $this->delivery_table->commit();
        return $delivery_id;
     }
     
     /**
      * 查询支付单列表
      * @param array    $where	【可选】
      * array(
      *      'delivery_id' => '',      //【可选】int 支付单ID，string|array （string：多个','分割）（array：支付单ID数组）
      *      'order_id' => '',        //【可选】int 订单ID，string|array （string：多个','分割）（array：订单ID数组）多个只支持：=；单个：like%
      *      'delivery_status'=>''    //【可选】int 发货状态
      *      'business_key'=>''       //【可选】int 业务类型ID
      *      'order_no'=>''           //【可选】string 业务类型ID
      *      'protocol_no'=>''       //【可选】string 协议号
      *      'begin_time'=>''         //【可选】int 发货开始时间
      *      'end_time'=>''           //【可选】int 发货结束时间
      * )
      * @param array $additional	    【可选】附加选项
      * array(
      *	    'page'	=> '',	           【可选】int 分页码，默认为1
      *	    'size'	=> '',	           【可选】int 每页大小，默认20
      *	    'orderby'	=> '',  【可选】string	排序；delivery_time_DESC：时间倒序；delivery_time_ASC：时间顺序；默认 id_DESC
      * )
      * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
      * @return array(
      *      array(
      *          'delivery_id'=>'',                   //【必须】int 支付单ID
      *          'order_id'=>'',             //【必须】int 订单ID
      *          'goods_id'=>'',             //【必须】int 商品id
      *          'admin_id'=>'',             //【必须】int 管理员id
      *          'member_id'=>'',            //【必须】int 用户ID
      *          'address_id'=>'',           //【必须】int 收货地址id
      *          'status'=>'',               //【必须】int 发货状态
      *          'wuliu_channel_id'=>'',     //【必须】int 物流渠道ID
      *          'wuliu_no'=>'',             //【必须】int 物流编号
      *          'business_key'=>'',         //【必须】int 业务类型
      *          'create_time'=>'',          //【必须】int 创建时间
      *          'update_time'=>'',          //【必须】int 最后更新时间
      *          'delivery_time'=>'',        //【必须】int 发货时间
      *      )
      * )
      * 支付单列表，没有查询到时，返回空数组
      */
     public function get_list($where=[],$additional=[]){
        //过滤查询条件
        $where = $this->__parse_where($where);
        //过滤附加查询条件
        $additional = $this->__parse_additional($additional);
        //获取检测单列表
        $delivery_list = $this->delivery_table->get_list($where,$additional);
       /*  //获取当前列表内的商品id数组
        $goods_ids = array_column($delivery_list, 'goods_id');
        //获取当前列表内的地址id数组
        $address_ids = array_column($delivery_list, 'address_id');
        //获取所有商品信息
        $goods_lists = $this->order_service->get_goods_list(['goods_id'=>$goods_ids]);
        //获取当前列表所有地址信息
        $address_lists = $this->order_service->get_address_list(['address_id'=>$address_ids]);
        //合并数据，让检测列表的时间覆盖商品表时间
        if(!mixed_merge($goods_lists, $delivery_list, 'goods_id')){
            return [];
        }
        //合并数据，让检测列表的时间覆盖地址表时间
        if( !mixed_merge( $address_lists, $goods_lists, 'address_id') ) {
            return [];
        } */
        return $delivery_list;
    }
    /**
     * 过滤where条件
     * @param array $where 查看 get_list()定义
     */
    private function __parse_where( $where=[] ) {
        //过滤查询条件
        
        $where = filter_array($where, [
            'delivery_id' => 'required',
            'order_id' => 'required',
            'business_key' => 'required|is_int',
            'order_no' => 'required',
            'protocol_no' => 'required',
            'delivery_status' => 'required',
            'begin_time' => 'required|is_int',
            'end_time' => 'required|is_int',
            'evaluation_id'=>'required|is_int',
        ]);
   
        //当订单编号存在时，先查找订单id，然后在进行检测列表获取

        if(isset($where['begin_time'])) {$time[] = array('GT',$where['begin_time']);}
        if(isset($where['end_time'])) {$time[] = array('LT',$where['end_time']);}
        if($time){$where['delivery_time'] = $time;}
        unset($where['begin_time']);
        unset($where['end_time']);
        //只查找未暂停状态
        $where['pause'] = zuji\order\DeliveryStatus::PauseNo;
        // order_id
        if(isset($where['order_id'])){
        $this->_parse_where_field_array('order_id',$where);
        }
        if(isset($where['delivery_id'])){
        $this->_parse_where_field_array('delivery_id',$where);
        }
        if(isset($where['delivery_status'])){
        $this->_parse_where_field_array('delivery_status',$where);
        }
        
        if(isset($where['wuliu_no'])){
            // wuliu_no 物流单号，使用前缀模糊查询
            $where['wuliu_no'] = ['LIKE', $where['wuliu_no'] . '%'];
        }
        if(isset($where['protocol_no'])){
            $where['protocol_no'] = ['LIKE', $where['protocol_no'] . '%'];
        }
        if(isset($where['order_no'])){
            $where['order_no'] = ['LIKE', $where['order_no'] . '%'];
        }
        
      
        
        return $where;
    }
    /**
     * @param array $additional	    【可选】附加选项
     * array(
     *	    'page'	=> '',	           【可选】int 分页码，默认为1
     *	    'size'	=> '',	           【可选】int 每页大小，默认20
     * )
     */
    private function __parse_additional( $additional=[] ) {
        // 附加条件
        $additional = filter_array($additional, [
            'page' => 'required|is_int',
            'size' => 'required|is_int',
            'orderby' => 'required',
        ]);
        // 分页
        if( !isset($additional['page']) ){
            $additional['page'] = 1;
        }
        if( !isset($additional['size']) ){
            $additional['size'] = 20;
        }
        $additional['size'] = max( $additional['size'], 20 );
        
        if( !isset($additional['orderby']) ||$additional['orderby'] ==""){	// 排序默认值
            $additional['orderby']='create_DESC';
        }
        
        if( in_array($additional['orderby'],['create_DESC','delivery_DESC']) ){
            if( $additional['orderby'] == 'create_DESC' ){
                $additional['orderby'] = 'create_time DESC';
            }elseif( $additional['orderby'] == 'delivery_DESC' ){
                $additional['orderby'] = 'delivery_time DESC';
            }
        }

        return $additional;
    }
     

     /**
      * 根据条件，查询总条数
      * @param array $where		查看 get_list() 定义
      * @return int  符合条件的记录总数
      * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
      */
     public function get_count($where=[]){
        //过滤查询条件
        $where = $this->__parse_where($where);
        return $result=$this->delivery_table->get_count($where);
     }

     /**
      * @param  string $field 获取的字段
      * @param  array  $where sql条件
      * @return [type]
      * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
      */
     public function getField($field = '',$sqlmap = array()) {
         return $this->delivery_table->getFields($field = '',$sqlmap = array());
     }
     
    /**
     * 状态更新=>生成租机协议
     * @param int $delivery_id 发货单主键
     * @param int $order_id 订单id
     * @param int $admin_id 后台操作员id
     * @author yaodongxu <yaodongxu@huishoubao.com.cn>
     */
    public function set_protocol_status( $delivery_id,$order_id, $admin_id, $protocol_no ){
        //拼接更新条件
        $data = [
            'delivery_id' => $delivery_id,
            'admin_id' => $admin_id,
            'protocol_no' => $protocol_no,
            'delivery_status' => \zuji\order\DeliveryStatus::DeliveryProtocol,
        ];
        //过滤查询条件
         $datas = filter_array($data, [
             'delivery_id' => 'required|is_id',
             'admin_id' =>'required|is_id',
             'protocol_no' =>'required|is_string',
             'delivery_status' =>'required|zuji\order\DeliveryStatus::verifyStatus',
         ]);
        try {
            $delivery_result =$this->delivery_table->update($datas);
            
            if( !$delivery_result ) {
                $this->delivery_table->rollback();
                set_error("更新发货单信息失败");
                return false;
            }
            // 更新订单 delivery_status:生成租机协议
            $order_result = $this->order_service->notify_delivery_protocoled($order_id, $protocol_no);
            //验证订单生成租机协议通知是否成功
            if( !$order_result ) {//业务逻辑处理不成功
                $this->delivery_table->rollback();
                set_error("同步到订单状态失败");
                return false;
            }
            
        } catch (\Exception $exc) {
            // 事务回滚
            $this->delivery_table->rollback();
            set_error('异常错误：'.$exc->getMessage());
            return false;
        }
        // 事务提交
        $this->delivery_table->commit();
        return $protocol_no;
    }
     /**
      * 状态更新=>已发货

      * @param array $where
      * [
      *   'delivery_id' =>'',     //【必须】int 发货单ID
          'order_id'=>'',         //【必须】int 订单ID
          'wuliu_channel_id' =>"",//【必须】int 物流渠道ID
          'wuliu_no' =>"",        //【必须】string 物流编号
          'delivery_remark' =>"", //【必须】string 发货备注
          'imei1' =>"",           //【必须】string Imei
          'imei2' =>"",           //【可选】string Imei
          'imei3' =>"",           //【可选】string Imei
          'serial_number' =>"",   //【可选】序列号
          'admin_id'=>"",         //【可选】后台管理员ID
          'goods_id'=>'',         //【必须】商品ID
      * ]
      * @return boolean
      * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
      */
     public function set_send_status($where){
         $where = filter_array($where, [
             'delivery_id' =>'required|is_id',
             'order_id'=>'required|is_id',
             'wuliu_channel_id' =>'required|is_id',
             'wuliu_no' =>'required',
             'delivery_remark' =>'required',
             'imei1' =>'required',
             'imei2' =>'required',
             'imei3' =>'required',
             'serial_number' =>'required',
             'admin_id'=>'required',
             'goods_id'=>'required|is_int',
         ]);   
         if( count($where)<7 ){ 
             set_error('参数错误');
             return false;
         }
         try {

             //更新order2 订单表状态：已发货
             $order_result = $this->order_service->notify_delivery_send($where['order_id']);            
             if(!$order_result){
                 $this->delivery_table->rollback();
                 set_error('同步到订单状态失败');
                 return false;
             }
             $delivery_where=[
                 'delivery_id' => $where['delivery_id'],
                 'wuliu_channel_id' =>$where['wuliu_channel_id'],
                 'wuliu_no' => $where['wuliu_no'],
                 'delivery_remark' => $where['delivery_remark'],
                 'admin_id' => $where['admin_id'],        
             ];
   
             $delivery_result = $this->delivery_table->send($delivery_where);
             
             if(!$delivery_result){
                 $this->delivery_table->rollback();
                 set_error('修改发货单信息失败');
                 return false;
             }
             
             $goods_where=[
                 'imei1' => $where['imei1'],
                 'imei2' => $where['imei2'],
                 'imei3' => $where['imei3'],
                 'serial_number'=>$where['serial_number'],
             ];

           
             $goods_result = $this->order_service->update_goods_serial($where['goods_id'],$goods_where);
             if(!$goods_result){
                 $this->delivery_table->rollback();
                 set_error('修改商品信息失败');
                 return false;
             }
             
            
        } catch (\Exception $exc) {
            // 关闭事务
            $this->delivery_table->rollback();
            set_error('异常错误：'.$exc->getMessage());
            return false;
        }
        // 事务提交
        $this->delivery_table->commit();
        return true;
    }

    /**
     * 状态更新=>回寄-发货
     * @param array $where
     * [
     *   'delivery_id' =>'',     //【必须】int 发货单ID
        'order_id'=>'',         //【必须】int 订单ID
        'wuliu_channel_id' =>"",//【必须】int 物流渠道ID
        'wuliu_no' =>"",        //【必须】string 物流编号
        'delivery_remark' =>"", //【必须】string 发货备注
        'admin_id'=>"",         //【可选】后台管理员ID
        'goods_id'=>'',         //【必须】商品ID
     * ]
     * @return boolean
     * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
     */
    public function huiji_set_send_status($where){
        $where = filter_array($where, [
            'delivery_id' =>'required|is_id',
            'order_id'=>'required|is_id',
            'wuliu_channel_id' =>'required|is_id',
            'wuliu_no' =>'required',
            'delivery_remark' =>'required',
            'admin_id'=>'required',
            'goods_id'=>'required|is_int',
        ]);
        if( count($where)<7 ){
            set_error('参数错误');
            return false;
        }
        try {
            //更新order2 订单表状态：已发货
            $order_result = $this->order_service->notify_delivery_send($where['order_id']);
            if(!$order_result){
                $this->delivery_table->rollback();
                set_error('同步到订单状态失败');
                return false;
            }
            $delivery_where=[
                'delivery_id' => $where['delivery_id'],
                'wuliu_channel_id' =>$where['wuliu_channel_id'],
                'wuliu_no' => $where['wuliu_no'],
                'delivery_remark' => $where['delivery_remark'],
                'admin_id' => $where['admin_id'],
            ];

            $delivery_result = $this->delivery_table->send($delivery_where);

            if(!$delivery_result){
                $this->delivery_table->rollback();
                set_error('修改发货单信息失败');
                return false;
            }

        } catch (\Exception $exc) {
            // 关闭事务
            $this->delivery_table->rollback();
            set_error('异常错误：'.$exc->getMessage());
            return false;
        }
        // 事务提交
        $this->delivery_table->commit();
        return true;
    }


    /**
     * 状态更新=>确认收货
     * @param int $delivery_id 发货单主键
     * @param array   $data	      【必选】
     * array(
     *      'order_id' =>'',             //【必选】  string 订单ID
     *      'confirm_remark' => '',	 //【可选】  string 收货备注
     *      'confirm_admin_id'=>''      //【可选】int 默认0 前端 其他是后端管理员
     *      'confirm_time'=>''         //【可选】 后台确认收货时间 可手动填入   
     *      )
     * @param array $service	    租机业务收货时必须
      array(
	 *      'order_id' => '',,	//【必选】  int 订单ID
	 *      'order_no'=> '',,	//【必选】  string 订单编号
	 *      'mobile' => '',,	//【必选】  string 用户手机号
	 *      'user_id '=>'',		//【必选】 int 用户ID
	 *      'business_key'=>'',	//【必选】 int 业务类型ID
	 *      'zuqi'=>'',		//【必选】 int 租期
	 *      'begin_time'=>'',	//【必选】 int 租期
	 *      )  
     * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
     */
    public function delivery_confirmed($delivery_id,$data,$service){
        if($delivery_id <1){
            set_error("发货单ID错误");
            return false;
        }
        $data = filter_array($data, [
            'order_id' => 'required|is_id',
            'confirm_remark' => 'required',
            'confirm_admin_id'=>'required',
            'confirm_time'=>'required',
        ]);
	set_default_value($data['confirm_remark'], '');

	if(!isset($data['confirm_admin_id'])){
	      $data['confirm_admin_id']=0;
	}
	if(!isset($data['confirm_time'])){
	    $data['confirm_time'] =time();
	}
        if(count($data)<4){
            set_error("参数错误");
            return false;
        }
	$service_data = $service;
	if($service_data['business_key'] == Business::BUSINESS_ZUJI){
	    // 服务参数过滤
	    $service_data = filter_array($service, [
		'order_id' => 'required|is_id',
		'order_no' => 'required',
		'mobile' => 'required|is_mobile',
		'user_id' => 'required|is_id',
		'business_key' => 'required|is_id',
		'zuqi' => 'required',
		'begin_time' => 'required',
	    ]);
	    if( count( $service_data ) != 7 ){
		set_error("参数错误");
		zuji\debug\Debug::error(\zuji\debug\Location::L_Delivery, '客户端[确认收货]服务参数错误', [
		    'params' => $service,
		    'filter_params' => $service_data,
		]);
		return false;
	    }
	}
	
	
        try {
            $data=[
                'order_id'=>$data['order_id'],
                'confirm_remark'=>$data['confirm_remark'],
                'update_time'=>time(),
                'confirm_time'=>$data['confirm_time'],
                'delivery_status'=>DeliveryStatus::DeliveryConfirmed,
                'confirm_admin_id'=>$data['confirm_admin_id'],
            ];
            
            $delivery =$this->delivery_table->update_confirmed($delivery_id,$data);
            if(!$delivery){
                $this->delivery_table->rollback();
                set_error("更新发货单信息失败");
                return false;
            }
            $order_result = $this->order_service->notify_delivery_finished($data['order_id']);
            if( !$order_result ) {
                $this->delivery_table->rollback();
                return false;
            }
            //租机服务  生成服务单  同步到订单
            if($service_data['business_key'] == Business::BUSINESS_ZUJI){
                $service_id =$this->service_service->create($service_data);
                if(!$service_id){                    
                    $this->delivery_table->rollback();
                    zuji\debug\Debug::error(\zuji\debug\Location::L_Delivery, '[确认收货]生成服务单失败', [
                        'params' => $service,
                        'filter_params' => $service_data,
                    ]);
                    set_error("生成服务单失败");
                    return false;
                }
                //同步更新到订单表
                $enter_service =$this->order_service->enter_service($data['order_id'],$service_id);
                if(!$enter_service){
                    $this->delivery_table->rollback();
                    set_error("同步到服务单失败");
                    return false;
                }                   
            }
            
            //如果是买断服务 确认收货时  订单关闭
            if($service_data['business_key'] == Business::BUSINESS_BUYOUT){
                //关闭订单
                $close_order =$this->order_service->order_finished($data['order_id']);
                if(!$close_order){
                    $this->delivery_table->rollback();
                    set_error("订单关闭失败");
                    return false;
                }
            }
        } catch (\Exception $exc) {
            $this->delivery_table->rollback();
            set_error('异常错误：'.$exc->getMessage());
            return false;
        }
        $this->delivery_table->commit();
        return true;
    
    }
        
    /**
     * 状态更新=>客户回寄拒签(修改发货单为客户拒签，填写拒签原因，更改订单状态为租用中)
     * @param int $delivery_id 发货单主键
     * @param int $order_id 订单主键
     * @param int $admin_id 操作人
     * @param string $refuse_remark 拒签备注
     * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
     */
    public function delivery_return_refuse($delivery_id,$order_id,$admin_id,$refuse_remark){
        if($delivery_id <1 || $order_id< 1){
            set_error("发货单ID或者订单ID错误");
            return false;
        }
        if( !$admin_id ) {
            set_error("未检测到登录后台操作人员id");
            return false;
        }
        try {

            $data=[
                'delivery_id'=>$delivery_id,
                'delivery_status'=>DeliveryStatus::DeliveryRefuse,
                'admin_id'=>$admin_id,
                'refuse_remark'=>$refuse_remark,
            ];
            //更新发货单状态
            $delivery =$this->delivery_table->update($data);
            if(!$delivery){
                $this->delivery_table->rollback();
                set_error("更新发货单信息失败");
                return false;
            }
            //更新订单状态（到租用中）
            $order_result = $this->order_service->notify_delivery_return_refuse($order_id);
            if( !$order_result ) {
                $this->delivery_table->rollback();
                set_error("订单信息更新失败");
                return false;
            }
        } catch (\Exception $exc) {
            $this->delivery_table->rollback();
            set_error('异常错误：'.$exc->getMessage());
            return false;
        }
        $this->delivery_table->commit();
        return true;
    } 
    /**
     * 状态更新=>客户拒签(首次拒签：改为线下处理，不用做系统处理：隐藏页面的拒签按钮)
     * @param int $delivery_id 发货单主键
     * @param int $order_id 订单主键
     * @param int $admin_id 操作人
     * @param string $refuse_remark 拒签备注
     * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
     */
//     public function delivery_refuse($delivery_id,$order_id,$admin_id,$refuse_remark){
//         if($delivery_id <1 || $order_id< 1){
//             set_error("发货单ID或者订单ID错误");
//             return false;
//         }
//         if( !$admin_id ) {
//             set_error("未检测到登录后台操作人员id");
//             return false;
//         }
//         try {
//             // 开启事务
//             $this->delivery_table->startTrans();
//             $data=[
//                 'delivery_id'=>$delivery_id,
//                 'delivery_status'=>DeliveryStatus::DeliveryRefuse,
//                 'admin_id'=>$admin_id,
//                 'refuse_remark'=>$refuse_remark,
//             ];
//             //更新发货单状态
//             $delivery =$this->delivery_table->update($data);
//             if(!$delivery){
//                 $this->delivery_table->rollback();
//                 set_error("更新发货单信息失败");
//                 return false;
//             }
//             //更新订单状态
//             $order_result = $this->order_service->notify_delivery_return_refuse($order_id);
//             if( !$order_result ) {
//                 $this->delivery_table->rollback();
//                 set_error("订单信息更新失败");
//                 return false;
//             }
//             $delivery_info = $this->get_info($delivery_id);
//             //创建收货单
//         } catch (\Exception $exc) {
//             $this->delivery_table->rollback();
//             set_error('异常错误：'.$exc->getMessage());
//             return false;
//         }
//         $this->delivery_table->commit();
//         return true;
//     }
    /**
     * 状态更新=>取消发货
     * @param int $delivery_id 发货单主键
     * @param int $order_id 订单id
     * @author yaodongxu <yaodongxu@huishoubao.com.cn>
     */
    public function set_canceled_status( $delivery_id,$order_id ){
        //拼接更新条件
        $data = [
            'delivery_id' => $delivery_id,
            'delivery_status' => zuji\order\DeliveryStatus::DeliveryCanceled,
        ];
        //过滤查询条件
         $datas = filter_array($data, [
             'delivery_id' => 'required|is_id',
             'delivery_status' =>'required|zuji\order\DeliveryStatus::verifyStatus',
         ]);
        try {
            $delivery_result =$this->delivery_table->update($datas);
            if( !$delivery_result ) {//业务逻辑处理不成功
                $this->delivery_table->rollback();
                set_error("更新发货单状态失败");
                return false;
            }
            $order_result =$this->order_service->notify_delivery_canceled($order_id);
             if( !$order_result ) {//业务逻辑处理不成功
                $this->delivery_table->rollback();
                return false;
            }
            
        } catch (\Exception $exc) {
            // 事务回滚
            $this->delivery_table->rollback();
            set_error('异常错误：'.$exc->getMessage());
            return false;
        }
        // 事务提交
        $this->delivery_table->commit();
        return true;
    }
    /**
     * 发货单暂停
     * @param int $delivery_id 发货单主键
     * @author yaodongxu <yaodongxu@huishoubao.com.cn>
     */
    public function set_pauseyes_status( $delivery_id ){
        //拼接更新条件
        $data = [
            'delivery_id' => $delivery_id,
            'pause' => zuji\order\DeliveryStatus::PauseYes,
        ];
        //过滤查询条件
         $datas = filter_array($data, [
             'delivery_id' => 'required|is_id',
             'pause' =>'required|zuji\order\DeliveryStatus::verifyPause',
         ]);
        try {
            $delivery_result =$this->delivery_table->update($datas);
            if( !$delivery_result ) {//业务逻辑处理不成功
                set_error("更新状态失败");
                return false;
            }
            return true;
        } catch (\Exception $exc) {
            set_error('异常错误：'.$exc->getMessage());
            return false;
        }
    }
    /**
     * 发货单继续
     * @param int $delivery_id 发货单主键
     * @author yaodongxu <yaodongxu@huishoubao.com.cn>
     */
    public function set_pauseno_status( $delivery_id ){
        //拼接更新条件
        $data = [
            'delivery_id' => $delivery_id,
            'pause' => zuji\order\DeliveryStatus::PauseNo,
        ];
        //过滤查询条件
         $datas = filter_array($data, [
             'delivery_id' => 'required|is_id',
             'pause' =>'required|zuji\order\DeliveryStatus::verifyPause',
         ]);
        try {
            $delivery_result =$this->delivery_table->update($datas);
            if( !$delivery_result ) {//业务逻辑处理不成功
                set_error("更新状态失败");
                return false;
            }
            return true;
        } catch (\Exception $exc) {
            set_error('异常错误：'.$exc->getMessage());
            return false;
        }
    }
    /**
     * 上传物流单号
     * @param array   $data	【必选】
     * @author limin<limin@huishoubao.com.cn>
     * @return  array 参考get_list参数
     */
    public function set_wuliu_no($data=[])
    {
        $data = filter_array($data,[
             'delivery_id'=>'required',
             'wuliu_channel_id'=>'required',
             'wuliu_no'=>'required'
        ]);
       $ret = $this->deliver_table->update($data);
        return $ret;
    }
    /**
     * 通过检测单 查找发货单
     * @param int $evaluation_id 检测单ID
     * @param array $additional附加条件
     * @return array 参考get_list 参数
     */
    public function get_delivery_evaluation($evaluation_id, $additional=[]){
        if($evaluation_id <1){
            set_error("不是检测生成的发货单");
            return false;
        }
        
        $delivery_info = $this->delivery_table->get_delivery_evaluation($evaluation_id, $additional);
        
        if( !$delivery_info ){
            return false;
        }
        // 格式化输出
        $this->_output_format($delivery_info);
        
        return $delivery_info;
    }
    /**
     * 获取单个发货单
     * @param array   $delivery_id	【必选】
     * @author wuhaiyan<wuhaiyan@huishoubao.com.cn>
     * @return  array 参考get_list参数
     */
    public function get_info($delivery_id, $additional=[])
    {
        $delivery_info = $this->delivery_table->get_info($delivery_id, $additional);

        if( !$delivery_info ){
            return false;
        }
        // 格式化输出
        $this->_output_format($delivery_info);
        
        return $delivery_info;
    }

    /**
     * 获取单个发货单
     * @param int  $order_id	【必选】
     * @param $evaluation_id 【可选】
     * @author limin<limin@huishoubao.com.cn>
     * @return  array 参考get_list参数
     */
    public function get_info_by_order_id($order_id,$business_key,$evaluation_id=0)
    {
        if(!$order_id){
            return false;
        }
        if(!$business_key){
            $business_key = zuji\Business::BUSINESS_ZUJI;
        }
        $delivery_info = $this->delivery_table->get_info_by_order_id(intval($order_id),$business_key,$evaluation_id);

        if( !$delivery_info ){
            return false;
        }
        // 格式化输出
        $this->_output_format($delivery_info);

        return $delivery_info;
    }
        private function _output_format(&$delivery_info){
            $_admin = model('admin/admin_user')->find($delivery_info['admin_id']);
            $delivery_info['delivery_status_show'] =DeliveryStatus::getStatusName($delivery_info['delivery_status']);
            $delivery_info['admin_name'] =$_admin['username'];
            $delivery_info['business'] =Business::getName($delivery_info['business_key']);
            $delivery_info['create_time_show'] =$delivery_info['create_time']>0?date('Y-m-d H:i:s',$delivery_info['create_time']):'--';
            $delivery_info['update_time_show'] =$delivery_info['update_time']>0?date('Y-m-d H:i:s',$delivery_info['update_time']):'--';
            $delivery_info['delivery_time_show'] = $delivery_info['delivery_time']>0?date('Y-m-d H:i:s',$delivery_info['delivery_time']):'--';
            $delivery_info['confirm_time_show'] = $delivery_info['confirm_time']>0?date('Y-m-d H:i:s',$delivery_info['confirm_time']):'--';
        }
}