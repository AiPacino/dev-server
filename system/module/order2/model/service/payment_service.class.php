<?php
use zuji\order\PaymentStatus;
use zuji\order\Order;
use zuji\Business;
/**
 * 支付单服务
 * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
 * @copyright (c) 2017, Huishoubao
 */
class payment_service extends service {
    


    public function _initialize() {
        //实例化数据层
        $this->payment_table = $this->load->table('order2/order2_payment');
        $this->order_service = $this->load->service('order2/order');
        $this->delivery_service = $this->load->service('order2/delivery'); 
    }

    
    /**
     * 生成支付单
     * 修改：事务在 控制器层处理
     * @param array $where   【必选】 支付单信息
     * array(
     *	    'business_key' => '',       // 【必须】int；支付业务类型  不能为0
     *	    'order_id' => '',	        // 【必须】int；订单ID 必须大于0
     *	    'amount' => '',	        // 【必须】price；待支付金额（单位：元）
     *	    'payment_channel_id' => '',	// 【可选】int；支付渠道ID
     *      'order_no' =>''             // 【必选】string;订单编号
     * )
     * @return mixed	false：失败；array：成功
     * [
     *	    'payment_id' => '',	// 支付单ID
     *	    'trade_no' => '',	// 交易码
     * ]
     * 当创建失败时返回false；当创建成功时返回支付单ID
     * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
     * 
     */
    public function create($data){
        //校验
        $data = filter_array($data, [
            'order_id' => 'required|is_id',
            'order_no'=>'required',
            'business_key' => 'required|is_id',
            'amount' => 'required|is_price',
            'goods_name' => 'required',
            'payment_channel_id' => 'required|is_id',
        ]);
    	if( !isset($data['payment_channel_id']) ){
    	    $data['payment_channel_id'] = 0;
    	}
        if( count($data)!=6 ){
    	    set_error('创建支付单时，业务参数错误');
            return false;
        }
	
        try {
	    
	    // 创建支付单
	    // 支付单的交易码
	    $trade_no = \zuji\Business::create_business_no();
	    $payment_data = [
		'payment_status' =>  PaymentStatus::PaymentPaying,// 直接支付中状态
		'business_key' => $data['business_key'],
		'order_id' => $data['order_id'],
		'order_no' => $data['order_no'],
		'trade_no' => $trade_no,
		'amount' => 100*$data['amount'],    // 元转换成分
		'payment_channel_id' => $data['payment_channel_id'],
	    ];
            $payment_id =$this->payment_table->create_data($payment_data);
            if(!$payment_id){
		set_error('创建支付单失败');
                return false;
            } 
	    
	    // 创建交易码
	    $trade_data = [
		'order_id' => $data['order_id'],
		'order_no' => $data['order_no'],
		'trade_type' => \zuji\payment\Payment::Type_Order_Payment,// 订单支付
		'trade_channel' => $data['payment_channel_id'],
		'amount' => $data['amount'],    // 金额，单位：元
		'subject' => $data['goods_name'],// 商品名称
	    ];
	    $trade_service = $this->load->service('payment/payment_trade');
	    $trade_data['trade_no'] = $trade_no;    // 交易码
	    $trade_data['payment_id'] = $payment_id;	    // 支付单
	    $trade_info = $trade_service->create( $trade_data );
	    if( !$trade_info ){
		return false;
	    }
	    // 订单进入支付阶段
            $enter_payment =$this->order_service->enter_payment($data['order_id'],$payment_id);
            if(!$enter_payment){
		set_error('订单进入支付阶段失败');
                return false;
            }
       // 订单支付状态修改为 支付中
            $payment_waiting=$this->order_service->notify_payment_waiting($data['order_id']);
            if(!$payment_waiting){
                set_error('支付状态更新失败');
                return false;
            }
	    // 订单支付状态修改为 支付中
            $payment_paying=$this->order_service->notify_payment_paying($data['order_id']);
           if(!$payment_paying){
		       set_error('支付状态更新失败');
               return false;
           }
	   
	    
	    // 记录订单日志       
	    return ['payment_id'=>$payment_id,'trade_no'=>$trade_no];  
            
        } catch (\Exception $exc) {
	    set_error($exc->getMessage());
            // 关闭事务
            return false;
        }
    }
    
    /**
     * 支付成功（用户在第三方支付平台完成了支付，服务器接收到了支付成功通知）
     * 【注意：】不需要加事务，该接口会嵌入到其他业务的事务中
     * @param int $order_id	【必选】 订单ID
     * @param int $payment_id	【必选】 支付单ID
     * @param array $data   【必选】 支付信息
     * [
     *	    'payment_amount' => '',	 // 【必须】price；实际支付金额;单位：分； 必须大于0
     * ]
     * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
     * @return boolean  true :插入成功  false:插入失败
     */
    public function payment_successful($order_id,$payment_id,$data){
        $data = filter_array($data, [
            'payment_amount' => 'required|is_numeric',
        ]);
        $data['payment_amount'] = intval($data['payment_amount']);
    	if( $order_id<1 ){
    	    set_error('$order_id 参数错误');
    	    return false;
    	}
    	if( $payment_id<1 ){
    	    set_error('$payment_id 参数错误');
    	    return false;
    	}
        if( count($data)!=1 ){
	    set_error('payment_amount 参数错误');
            return false;
        }
        try {
    
            $payment =$this->payment_table->payment_successful($payment_id,[
    		'payment_amount' => $data['payment_amount'],// 支付金额
    		'payment_status'=>PaymentStatus::PaymentSuccessful, // 支付成功
    		'payment_time' => time(),// 支付时间
    	    ]);
            if(!$payment){
		      set_error('更新支付单失败');
                return false;
            }
            $payment_info =$this->get_info($payment_id);
            if($payment_info['business_key'] == Business::BUSINESS_STORE){
                $notify_success =$this->order_service->notify_store_payment_success($order_id,time(),$data['payment_amount']);
            }else{
                $notify_success =$this->order_service->notify_payment_success($order_id,time(),$data['payment_amount']);     
            }
            if(!$notify_success){
                set_error('通知订单支付成功失败');
                return false;
            }
   
            return true;
             
        } catch (\Exception $exc) {
            // 关闭事务
            return false;
        }
    
    }
    
    /**
     * 支付成功--申请退款 
     * @param int $payment_id   【必选】 支付单ID
     * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
     * @return boolean  true :插入成功  false:插入失败
     */
    public function payment_apply_going($payment_id){  
        if(!isset($payment_id) || $payment_id<0){
            set_error("参数错误");
            return false;
        }
        $data =[
            'apply_status'=>PaymentStatus::PaymentApplyWaiting,
            'apply_time'=>time(),
            'update_time'=>time(),
        ];
        return $this->payment_table->update_apply($payment_id,$data);
     
    }
    /**
     * 退款审核  -- 同意
     * @param int $payment_id   【必选】 支付单ID
     * @param array $data
     * [
     *  'admin_id'=>'',     【可选】后台操作员ID
     *  'admin_remark'=>''  【可选】审核备注
     * ]
     * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
     * @return boolean  true :插入成功  false:插入失败
     */
    public function payment_apply_successful($payment_id,$data=[]){
        if(!isset($payment_id) || $payment_id<0){
            set_error("参数错误");
            return false;
        }
       // var_dump($refund_data);die;
        //校验
        $data = filter_array($data, [
            'admin_id' => 'required|is_id',
            'admin_remark'=>'required',
        ]);
        if( !isset($data['admin_id']) ){
            $data['admin_id'] = 0;
        }
        if(!isset($data['admin_remark'])){
            $data['admin_remark']="";
        }

        try {
            $data['apply_status']=PaymentStatus::PaymentApplySuccessful;
            $data['update_time']=time();
            $apply =$this->payment_table->update_apply($payment_id,$data);
            return $apply;
        }catch (\Exception $exc) {
            set_error("异常错误");
            return false;
        }

    }
    /**
     * 退款审核  -- 拒绝
     * @param int $payment_id   【必选】 支付单ID
     * @param int $admin_id     【可选】后台操作员ID
     * @param int $admin_remark 【可选】审核备注
     * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
     * @return boolean  true :插入成功  false:插入失败
     */
    public function payment_apply_failed($payment_id,$admin_id=0,$admin_remark=""){
        if(!isset($payment_id) || $payment_id<0){
            set_error("参数错误");
            return false;
        }
        $data =[
            'apply_status'=>PaymentStatus::PaymentApplyFailed,
            'update_time'=>time(),
            'admin_id'=>$admin_id,
            'admin_remark'=>$admin_remark,
        ];
    
        return $this->payment_table->update_apply($payment_id,$data);
    }
    /**
     * 支付失败（用户在第三方支付平台完成了支付，服务器接收到了支付失败的通知）
     * @param array $where   【必选】 支付单信息
     * array(
     *	    'payment_id' => '',          // 【必须】int；支付业务ID  不能为0
     *      'order_id'=>''               // 【必须】int; 订单ID
     *	    'amount_payed' => '',	     // 【可选】int；实际支付金额 必须大于0
     *	    'payment_channel_id' => '',	 // 【必须】int；支付渠道
     *	    'payment_text' => '',	     // 【必须】 string:第三方支付返回的结果
     * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
     * @return boolean  true :插入成功  false:插入失败
     */
    public function payment_failed($where){
        $where = filter_array($where, [
            'payment_id' => 'required|is_id',
            'order_id' => 'required|is_id',
            'amount_payed' => 'required|is_int',
            'payment_channel_id' => 'required|is_id',
            'payment_text' => 'required',
        ]);
        if( isset($where['amount_payed']) ){
    	    $where['amount_payed'] = 0;
    	}
        if( count($where)<5 ){
            set_error("参数错误");
            return false;
        }
        try {
            // 开启事务
            $this->payment_table->startTrans();
            $payment =$this->payment_table->payment_failed($where);
            if(!$payment){
                $this->payment_table->rollback();
                set_error("更新支付单错误");
                return false;
            }
            $notify_failed =$this->order_service->notify_payment_failed($where['order_id']);
            if(!$notify_failed){
                $this->payment_table->rollback();
                set_error("同步到订单状态错误");
                return false;
            }
            
             
        } catch (\Exception $exc) {
            // 关闭事务
            $this->payment_table->rollback();
            set_error("异常错误");
            return false;
        }
        // 提交事务
        $this->payment_table->commit();
        return true;
        
    }
  
    /**
     * 更新支付渠道
     * @param int $payment_id	支付单主键ID
     * @param int $payment_channel_id	支付渠道ID
     * @return boolean
     */
    public function update_payment_channel_id( $payment_id, $payment_channel_id ){
	
        try {
            // 开启事务
            $this->payment_table->startTrans();
            $payment =$this->payment_table->payment_failed($payment_id, $payment_channel_id);
            if(!$payment){
                $this->payment_table->rollback();
                set_error("支付方式设置错误");
                return false;
            }
	    // 提交事务
	    $this->payment_table->commit();
	    return true;
        } catch (\Exception $exc) {
            // 关闭事务
            $this->payment_table->rollback();
            set_error("更新支付方式异常");
            return false;
        }
        
    }

    /**
     * 查询支付单列表
     * @param array    $where	【可选】 
     * array(
     *      'payment_id' => '',      //【可选】int 支付单ID，string|array （string：多个','分割）（array：支付单ID数组）
     *      'order_id' => '',        //【可选】int 订单ID，string|array （string：多个','分割）（array：订单ID数组）多个只支持：=；单个：like%
     *      'payment_status'=>''     //【可选】int 支付状态
     *      'business_key'=>''       //【可选】int 业务类型ID
     *      'payment_channel_id'=>'' //【可选】int 支付方式ID
     *      'apply_status'=>''       //【可选】int 退款申请状态
     *      'begin_time'=>''         //【可选】int 支付开始时间
     *      'end_time'=>''           //【可选】int 支付结束时间   
     *      'time_type'=>''          //【可选】string 时间类型 create_time  payment_time
     * )
     * 
     * @param array $additional	    【可选】附加选项
     * array(
     *	    'page'	=> '',	           【可选】int 分页码，默认为1
     *	    'size'	=> '',	           【可选】int 每页大小，默认20
     *	    'orderby'	=> '',  【可选】string	排序；payment_time_DESC：时间倒序；payment_time_ASC：时间顺序；默认 create_time_DESC
     * )
     * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
     * @return array(
     *      array(
     *          'payment_id'=>'',           //【必须】int 支付单ID
     *          'order_id'=>'',             //【必须】int 订单ID
     *          'payment_channel_id'=>'',   //【必须】int 支付方式id
     *          'amount'=>'',               //【必须】int 应付金额
     *          'amount_payed'=>'',         //【必须】int 实际支付金额
     *          'payment_time'=>'',         //【必须】int 支付时间
     *          'payment_status'=>'',       //【必须】int 支付状态
     *          'create_time'=>'',          //【必须】int 下单时间
     *          'update_time'=>'',          //【必须】int 最后更新时间
     *          'payment_text'=>'',         //【必须】string 第三方支付返回的结果
     *          'business_key'=>'',         //【必须】int 业务类型ID
     *          'apply_status'=>'',         //【必须】int 申请退款状态ID
     *          'apply_time'=>'',           //【必须】int 申请时间
     *          'admin_id'=>'',             //【必须】int 后台操作员ID
     *      )
     * )     
     * 支付单列表，没有查询到时，返回空数组
     */
    public function get_list($where=[],$additional=[]){
       
        // 参数过滤
        $where = $this->_pars_where($where);
        if( $where === false ){
            return 0;
        }
      

        if(!isset($additional['page'])){$additional['page']=1;}
        if(!isset($additional['size'])){$additional['size']=20;}
        
        if(!isset($additional['orderby']) || $additional['orderby'] ==""){$additional['orderby']='create_time_DESC';}
  
        if( in_array($additional['orderby'],['payment_time_DESC','payment_time_ASC','create_time_DESC','create_time_ASC']) ){
            if( $additional['orderby'] == 'payment_time_DESC' ){
                $additional['orderby'] = 'payment_time DESC';
            }elseif( $additional['orderby'] == 'payment_time_ASC' ){
                $additional['orderby'] = 'payment_time ASC';
            }elseif( $additional['orderby'] == 'create_time_DESC' ){
                $additional['orderby'] = 'create_time DESC';
            }elseif( $additional['orderby'] == 'create_time_ASC' ){
                $additional['orderby'] = 'create_time ASC';
            }
        }
     
        $result =$this->payment_table->get_list($where,$additional);
        if(count($result)>0){
            return $result;
        }else{
            return [];
        }
       
    }
    

    
    /**
     * 根据条件，查询总条数
     * @param array $where		查看 get_list() 定义
     * @return int  符合条件的记录总数  
     */
    public function get_count($where=[]){
        // 参数过滤
        $where = $this->_pars_where($where);
        if( $where === false ){
            return 0;
        }
        
        return $result=$this->payment_table->get_count($where);
    }
    
    private function _pars_where($where=[]){
        // 如果有多个订单 要进行订单拆分
        if( isset($where['order_id']) ){
        
            if(!is_array($where['order_id']) && is_string($where['order_id']) ){
                $where['order_id'] = explode(',',$where['order_id']);
            }
            if( count($where['order_id'])==0 ){
                unset($where['order_id']);
            }else if(count($where['order_id'])==1 ){
                $where['order_id']= $where['order_id'][0];
            }
        }
        if( isset($where['payment_id']) ){
        
            if(!is_array($where['payment_id']) && is_string($where['payment_id']) ){
                $where['payment_id'] = explode(',',$where['payment_id']);
            }
            if( count($where['payment_id'])==0 ){
                unset($where['payment_id']);
            }else if(count($where['payment_id'])==1 ){
                $where['payment_id']= $where['payment_id'][0];
            }
        }
        // 时间查询条件，先根据 time_type 获取查询哪个时间，
        $time_key = '';
        if( isset($where['time_type']) ){
            if( $where['time_type']=='create_time' ){
                $time_key = 'create_time';
            }elseif( $where['time_type']=='payment_time' ){
                $time_key = 'payment_time';
            }else{
                return false;// 时间类型参数错误
            }
            unset($where['time_type']);
        }
        unset($where['time_type']);
        // 找到要查询的时间，然后判断 结束时戳间和开始时间戳
        if( $time_key ){
            // 结束时间（可选），默认为为当前时间
            if( !isset($where['end_time']) ){
                $where['end_time'] = time();
            }
            // 开始时间（可选）
            if( isset($where['begin_time'])){
                if( $where['begin_time']>$where['end_time'] ){
                    return false;// 查询参数错误
                }
                $where[$time_key] = ['between',[$where['begin_time'], $where['end_time']]];
            }else{
                $where[$time_key] = ['LT',$where['end_time']];
            }
        }
        
        unset($where['begin_time']);
        unset($where['end_time']);
        
        if(isset($where['trade_no'])){
            $where['trade_no'] = ['LIKE', $where['trade_no'] . '%'];
        }
        if(isset($where['order_no'])){
            $where['order_no'] = ['LIKE', $where['order_no'] . '%'];
        }
        

        return $where;
    
    }
    
    /**
     * 根据ID 查询数据
     * @param int id 主键
     * @param array $additional
     * [
     *      'lock' =>'',【可选】bool 是否加锁
     * ]    
     * return array
     */
    public function get_info($id,$additional=[]){
        if( !isset($id) || intval($id)<1 ){return false;}
        
        $where =[
            'payment_id' =>$id,
        ];
        
        if(!isset($additional['lock'])){
            $additional['lock']=false;
        }
        
        $payment_info = $this->payment_table->get_info($where,$additional);
	if( !$payment_info ){
	    return false;
	}
	// 格式化输出
	$this->_output_format($payment_info);
	
        return $payment_info;
    }
    /**
     * 根据订单ID 查询数据
     * @param int id 主键
     * return array
     */
    public function get_info_by_order_id($order_id){
        if( !isset($order_id)){return false;}

        $payment_info = $this->payment_table->get_info_by_order_id($order_id);
        if( !$payment_info ){
            return false;
        }
        // 格式化输出
        $this->_output_format($payment_info);

        return $payment_info;
    }
    
    private function _output_format(&$payment_info){
	
	// 输出，将价格单位，从 分 转换成 元
	$payment_info['amount']		= Order::priceFormat($payment_info['amount']/100);
	$payment_info['payment_amount']	= Order::priceFormat($payment_info['payment_amount']/100);
	$payment_info['payment_amount_show']	= $payment_info['payment_amount']>0?Order::priceFormat($payment_info['payment_amount']/100):'--';
	
	$payment_info['create_time_show'] = $payment_info['create_time']>0?date('Y-m-d H:i:s',$payment_info['create_time']):'--';
	$payment_info['update_time_show'] = $payment_info['update_time']>0?date('Y-m-d H:i:s',$payment_info['update_time']):'--';
	
	$payment_info['payment_channel_show'] = zuji\payment\Payment::getChannelName($payment_info['payment_channel_id']);
	$payment_info['payment_status_show'] = PaymentStatus::getStatusName($payment_info['payment_status']);
	$payment_info['payment_time_show'] = $payment_info['payment_time']>0?date('Y-m-d H:i:s',$payment_info['payment_time']):'--';
	
	$payment_info['apply_status_show'] = PaymentStatus::getApplyName($payment_info['apply_status']);
	
	$payment_info['apply_time_show'] = $payment_info['apply_time']>0?date('Y-m-d H:i:s',$payment_info['apply_time']):'--';
	
	
	
	
    }
    
    /**
     * 查询一条记录
     * @param array
     * [
     *      key     =>  value
     * ]
     * @return array
     */
    public function get_info_orderid($where=[]){
        return $this->payment_table->get_info_orderid($where);
    }
  
}