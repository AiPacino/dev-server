<?php
use zuji\order\RefundStatus;
use zuji\order\Order;
use zuji\Business;
/**
 * 退款服务
 * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
 * @copyright (c) 2017, Huishoubao
 */
class refund_service extends service {
    

    public function _initialize() {
        //实例化数据层
        $this->refund_table = $this->load->table('order2/order2_refund');
        $this->order_service = $this->load->service('order2/order');
        $this->service_service = $this->load->service('order2/service');
    }
    
    
    /**
     * 生成退款单
     * @param array $data   【必选】 退款单信息
     * array(
     *	    'order_id' => '',            // 【必须】int；要检测的订单ID 必须大于0
     *      'order_no' =>''              // 【必须】string 订单编号
     *	    'payment_amount' => '',	     // 【必须】price；实际支付金额（单位：元）
     *	    'user_id' => '',	         // 【必须】int；用户ID 必须大于0
     *      'mobile' => '',	             // 【必须】sting 用户账号 手机号
     *	    'goods_id' => '',	         // 【必须】int；商品ID 必须大于0
     *      'payment_id' => '',	         // 【必须】int；支付单 ID必须大于0
     *      'business_key' => '',	     // 【必须】int 业务类型 必须大于0
     *      'payment_channel_id'=>''     // 【必须】int 支付渠道ID
     *      'should_amount'=>''     // 【必须】price 应退金额（单位：元）
     *      'should_remark'=>''     // 【必须】string $should_remark
     * )
     * @return mixed	false：失败；int：退款单ID
     * 当创建失败时返回false；当创建成功时返回退款单ID
     * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
     * 
     */
    public function create($data=[]){
        // 校验
         $data = filter_array($data, [
	    'order_id' => 'required|is_id',  
	    'order_no' =>'required',
             'payment_amount' =>'required|is_price',
             'user_id' =>'required|is_id',
             'mobile' =>'required',
             'goods_id' =>'required|is_id',
             'payment_id' =>'required|is_id',
             'business_key' =>'required|is_int',
             'payment_channel_id' =>'required|is_int',
             'should_amount' =>'required|is_price',
             'should_remark' =>'required',
		    
        ]);
        if($data['payment_amount']<=0){
            set_error('支付金额不能为0');
            return false;
        }
        if( count($data)!=11 ){ 
	    set_error('创建[退款单]参数错误');
	    return false;
	}
	
	// 金额单位转换
	$data['payment_amount'] = 100 * $data['payment_amount'];
	$data['should_amount'] = 100 * $data['should_amount'];


        
        try {
            // 开启事务        
            $this->refund_table->startTrans();
            $data['update_time'] =time();
            $data['create_time'] =time();
            $data['refund_status']=RefundStatus::RefundWaiting;
            
            $refund_id = $this->refund_table->add($data);    
            if(!$refund_id){
                set_error("创建[退款单]失败");
                $this->refund_table->rollback();
                return false;
            }
            $enter_refund =$this->order_service->enter_refund($data['order_id'],$refund_id);
            if(!$enter_refund){
                set_error("[退款单][创建状态]同步失败");
                $this->refund_table->rollback();
                return false;
            }      
            $notify_waiting=$this->order_service->notify_refund_waiting($data['order_id']);
            if(!$notify_waiting){
                set_error("[退款单][待退款状态]同步失败");
                $this->refund_table->rollback();
                return false;
            }       
        } catch (\Exception $exc) {
            // 关闭事务
            $this->refund_table->rollback();
            return false;
        }
        
        // 提交事务
        $this->refund_table->commit();
        return $refund_id;
        
    }
    /**
     *客服人员修改应退金额  和 修改备注
     *@param int $refund_id 【必须】 退款单ID
     *@param array $data[
     *      'should_amount'=>'',//【必须】应退金额
     *      'should_remark'=>'',//【必须】修改应退金额备注
     *      'should_admin_id'=>'',//【必须】修改金额的人员ID
     *
     *]
     *@author wuhaiyan <wuhaiyan@huishoubao.com.cn>
     *@return boolean  true :成功  false:失败
     */
    public function update_should_amount($refund_id,$data=[]){
        if($refund_id <1){
            set_error("退款单ID错误");
            return false;
        }
        $data = filter_array($data, [
            'should_amount' =>'required|is_int',
            'should_remark' =>'required',
            'should_admin_id'=>'required|is_int',
        ]);
       
        if(count($data)!=3){
            set_error("参数错误");
            return false;
        }
        $data['update_time'] =time();
        return $this->refund_table->where(['refund_id'=>$refund_id])->save($data);
    }
 
    /**
     * 点击退款 退款成功
     * @param int $business_key //【必须】业务类型
     * @param array $data  
     * array(
     *	    'refund_id' => '',           // 【必须】int；业务ID  必须大于0
     *	    'return_type' => '',	     // 【必须】int；0初始化 1.原路返回 2.其他
     *      'refund_remark'=>''          // 【可选】string：退款备注
     *      'refund_amount'=>''          // 【必选】退款金额（单位：元）
     *      'order_id'=>''               // 【必选】订单ID
     *      'admin_id'=>'',              // 【必选】退款人
     * )
     * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
     * @return boolean  true :成功  false:失败
     */
    public function refund_successful($business_key,$data=[]){  
    // 校验
         if($business_key <0){
             set_error("业务类型ID错误");
             return false;
         }
         $data = filter_array($data, [
	    'refund_id' => 'required|is_id',  
	    'return_type' =>'required|is_int',
	    'refund_remark' =>'required',
	    'refund_amount' =>'required|is_price',
	    'order_id'=>'required|is_id',
	    'admin_id'=>'required|is_int',
        ]);
        if( count($data)!=6 ){ 
            set_error('参数错误');
            return false;
        }
	
	// 金额单位转换 元转换成分
	$data['refund_amount'] = 100 * $data['refund_amount'];
	
        try {
            // 开启事务
            $data['update_time']=time();
            $data['refund_time']=time();
            $data['refund_status']=RefundStatus::RefundSuccessful;
            
            $refund = $this->refund_table->where(['refund_id'=>$data['refund_id']])->save($data);
            if(!$refund){
                set_error("更新退款单失败");
                $this->refund_table->rollback();
                return false;
            }        
            //修改服务单状态
            //退款成功时  取消服务  --- 如果是租机业务 才能取消。
            if($business_key == Business::BUSINESS_ZUJI){
                $service_cancel =$this->service_service->service_cancel($data['order_id']);
                if(!$service_cancel){
                    set_error("同步到服务单状态失败");
                    $this->refund_table->rollback();
                    return false;
                }
                
                //状态同步到订单表
                $notify_cancel =$this->order_service->notify_service_cancel($data['order_id']);
                if(!$notify_cancel){
                    $this->refund_table->rollback();
                    set_error("同步到订单状态失败");
                    return false;
                } 
                
                //关闭订单
                $close_order =$this->order_service->close_order($data['order_id']);
                if(!$close_order){
                    $this->refund_table->rollback();
                    set_error("订单关闭失败");
                    return false;
                }
                
            }
            
            $notify_success =$this->order_service->notify_refund_success($data['order_id'],$data['refund_amount']);
            if(!$notify_success){
                set_error("同步到订单状态失败");
                $this->refund_table->rollback();
                return false;
            }
        
        } catch (\Exception $exc) {
            // 关闭事务
            $this->refund_table->rollback();
            return false;
        }
        
        // 提交事务
        $this->refund_table->commit();
        return true;
    	    
    }
    /**
     * 退款中.
     * @param int $refund_id 【必须】int；业务ID  必须大于0
     * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
     * @return boolean  true :成功  false:失败
     */
    public function refund_paying($refund_id){
        // 校验
        if($refund_id<1){
            set_error('退款单ID错误');
            return false;
        }
        
        $data['update_time']=time();
        $data['refund_status']=RefundStatus::RefundPaying;
        return $this->refund_table->where(['refund_id'=>$refund_id])->save($data);
    } 
    /**
     * 退款失败
     * @param array $data
     * array(
     *	    'refund_id' => '',           //【必须】int；业务ID  必须大于0
     *      'order_id'=>''               //【必须】int 订单ID
     * )
     * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
     * @return boolean  true :成功  false:失败
     */
    public function refund_failed($data=[]){
        // 校验
        $data = filter_array($data, [
            'refund_id' => 'required|is_id',
            'order_id' => 'required|is_id',
        ]);
        if( count($data)!=2 ){ return false;}
        try {
            // 开启事务
            $this->refund_table->startTrans();
            $data['update_time']=time();
            $data['refund_status']=RefundStatus::RefundFailed;
            $refund = $this->refund_table->where(['refund_id'=>$data['refund_id']])->save($data);
            if(!$refund){
                $this->refund_table->rollback();
                return false;
            }
            $notify_failed =$this->order_service->notify_refund_failed($data['order_id']);
            if(!$notify_failed){
                $this->refund_table->rollback();
                return false;
            }
            
        } catch (\Exception $exc) {
            // 关闭事务
            $this->refund_table->rollback();
            return false;
        }
        // 提交事务
        $this->refund_table->commit();
        return true;
        
        	
    }
    
    /**
     * 查询退款列表
     * @param array    $where	【可选】 
     * array(
     *      'refund_id' => '',      // 【可选】  支付单ID，string|array （string：多个','分割）（array：支付单ID数组）
     *      'order_id' => '',       // 【可选】  订单ID，string|array （string：多个','分割）（array：订单ID数组）
     *      'status'=>''            // 【可选】int 退款状态：0初始化，1成功   2失败
     *      'refund_type'=>''       // 【可选】int退款方式：0初始化 1.原路返回 2.其他
     *      'begin_time'=>''        // 【可选】int 支付开始时间
     *      'end_time'=>''          // 【可选】int 支付结束时间 
     *      'refund_time'=>''       // 【可选】int 直接传过来BETWEEN sql 数组  
     * )
     * @param array $additional	    【可选】附加选项
     * array(
     *	    'page'	=> '',	           【可选】int 分页码，默认为1
     *	    'size'	=> '',	           【可选】int 每页大小，默认20
     *	    'orderby'	=> '',  【可选】string	排序；create_time_DESC：时间倒序；create_time_ASC：时间顺序；默认 refund_id_DESC
     * )
     * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
     * @return array(
     *      array(
     *          'refund_id'=>'',        //【必须】int退款单id
     *          'order_id'=>'',         //【必须】int订单ID
     *          'order_no'=>'',         //【必须】sting 订单编号
     *          'user_id'=>'',          //【必须】int用户id
     *          'mobile'=>'',           //【必须】sting用户账号
     *          'refund_amount'=>'',    //【必须】int退款金额
     *          'goods_id'=>'',         //【必须】int商品ID
     *          'payment_id'=>'',       //【必须】int支付单ID
     *          'payment_amount'=>'',     //【必须】int支付价格
     *          'refund_status'=>'',    //【必须】int退款状态
     *          'business_key'=>'',     //【必须】int业务类型id
     *          'retfund_type'=>'',     //【必须】int退款方式
     *          'account_name'=>'',     //【必须】string账户名称
     *          'account_no'=>'',       //【必须】string账户号
     *          'really_name'=>'',      //【必须】string收款人真实姓名
     *          'create_time'=>'',      //【必须】string退款单生成时间
     *          'update_time'=>'',      //【必须】string退款单更新时间
     *          'refund_time'=>'',      //【必须】string退款时间
     *          'refund_remark'=>'',    //【必须】string退款备注
     *      )
     * )     
     * // 列表，没有查询到时，返回空数组
     */
    public function get_list($where=[],$additional=[]){
      
        $where = $this->_pars_where($where);
        if( $where === false ){
            return [];
        }
        if(!isset($additional['page'])){$additional['page']=1;}
        if(!isset($additional['size'])){$additional['size']=20;}
        
        if(!isset($additional['orderby'])  || $additional['orderby'] ==""){$additional['orderby']='create_time_DESC';}
        
        if( in_array($additional['orderby'],['refund_id_DESC','create_time_DESC','create_time_ASC']) ){
            if( $additional['orderby'] == 'refund_id_DESC' ){
                $additional['orderby'] = 'refund_id DESC';
            }elseif( $additional['orderby'] == 'create_time_DESC' ){
                $additional['orderby'] = 'create_time DESC';
            }elseif( $additional['orderby'] == 'create_time_ASC' ){
                $additional['orderby'] = 'create_time ASC';
            }
        }else{
            $additional['orderby']="refund_id DESC";
        }
        
        $result =$this->refund_table->get_list($where,$additional);
	foreach( $result as &$item){
	    // 格式化输出
	    $this->_output_format($item);
	}
        return $result; 
    }

    /**
     * 获取单个
     * @param int  $refund_id	【必选】
     * @param array $additional
     * [
     *      'lock' =>'',【可选】bool 是否加锁
     * ]    
     * @author wuhaiyan<wuhaiyan@huishoubao.com.cn>
     * @return  array 参考get_list
     */
    public function get_info($refund_id,$additional=[])
    {
        if($refund_id <1){
            set_error("退款单ID错误");
            return false;
        }
        $where =[
            'refund_id' =>$refund_id,
        ];
        
        if(!isset($additional['lock'])){
            $additional['lock']=false;
        }
        
        $refund_info = $this->refund_table->get_by_id($where,$additional);       
        if( !$refund_info ){
            return false;
        }
        // 格式化输出
        $this->_output_format($refund_info);
        
        return $refund_info;
    
    }

    private function _output_format(&$refund_info){
	$refund_info['admin_name'] = '';
	if( $refund_info['admin_id']>0 ){
	    $_admin = model('admin/admin_user')->field('username')->find($refund_info['admin_id']);
	    $refund_info['admin_name'] =$_admin['username'];
	}
        
	$refund_info['should_admin_name'] = '';
	if( $refund_info['should_admin_id']>0 ){
	    $_should_admin = model('admin/admin_user')->field('username')->find($refund_info['should_admin_id']);
	    $refund_info['should_admin_name'] =$_should_admin['username'];
	}
        
        $refund_info['refund_status_show'] =RefundStatus::getStatusName($refund_info['refund_status']);
	
	
	$refund_info['should_amount']	= Order::priceFormat($refund_info['should_amount']/100);
        $refund_info['should_amount_show']	= $refund_info['should_amount']>0?$refund_info['should_amount']:'--';
	
	$refund_info['refund_amount']	= Order::priceFormat($refund_info['refund_amount']/100);
        $refund_info['refund_amount_show']	= $refund_info['refund_amount']>0?$refund_info['refund_amount']:'--';
	
	$refund_info['payment_amount']	= Order::priceFormat($refund_info['payment_amount']/100);
        $refund_info['payment_amount_show']	= $refund_info['payment_amount']>0?$refund_info['payment_amount']:'--';
	
	
        $refund_info['create_time_show'] =$refund_info['create_time']>0?date('Y-m-d H:i:s',$refund_info['create_time']):'--';
        $refund_info['update_time_show'] =$refund_info['update_time']>0?date('Y-m-d H:i:s',$refund_info['update_time']):'--';
        $refund_info['refund_time_show'] = $refund_info['refund_time']>0?date('Y-m-d H:i:s',$refund_info['refund_time']):'--';
    
    }

    /**
     * 根据条件，查询总条数
     * @param array $where		查看 get_list() 定义
     * @return int  符合条件的记录总数
     */
    public function get_count($where=[]){
        $where = $this->_pars_where($where);
        return $result=$this->refund_table->get_count($where);
    }
    
    private function _pars_where($where){
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
        if( isset($where['refund_id']) ){
            if(!is_array($where['refund_id']) && is_string($where['refund_id']) ){
                $where['refund_id'] = explode(',',$where['refund_id']);
            }
            if( count($where['refund_id'])==0 ){
                unset($where['refund_id']);
            } else if(count($where['refund_id'])==1 ){
                $where['refund_id']= $where['refund_id'][0];
            }
        }
        
        if(!isset($where['refund_time'])){
            if(isset($where['begin_time'])) {$time[] = array('GT',$where['begin_time']);}
            if(isset($where['end_time'])) {$time[] = array('LT',$where['end_time']);}
            if($time){$where['refund_time'] = $time;}
        }
        unset($where['begin_time']);
        unset($where['end_time']);
        if(isset($where['mobile'])){
            // wuliu_no 物流单号，使用前缀模糊查询
            $where['mobile'] = ['LIKE', $where['mobile'] . '%'];
        }
        if(isset($where['order_no'])){
            // wuliu_no 物流单号，使用前缀模糊查询
            $where['order_no'] = ['LIKE', $where['order_no'] . '%'];
        }
        return $where;
    
    }

    /**
     * 查询一条记录
     * @param array
     * [
     *      key     =>  value
     * ]
     * @return array
     */
    public function get_info_where($where=[],$additional=[]){
        if($additional['lock']){
            $lock =$additional['lock'];
        }else{
            $lock =false;
        }
        $refund_info = $this->refund_table->get_info($where,$lock);
        if( !$refund_info ){
            return false;
        }
        // 格式化输出
        $this->_output_format($refund_info);

        return $refund_info;
    }
    
    
}