<?php
use zuji\order\ReturnStatus;
use zuji\order\Address;
/**
 * 退货申请服务
 * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
 * @copyright (c) 2017, Huishoubao
 */
class return_service extends service {

    public function _initialize() {
        $this->return_table = $this->load->table('order2/order2_return');
        $this->order_service = $this->load->service('order2/order');
        $this->delivery_service = $this->load->service('order2/delivery');
        $this->receive_service = $this->load->service('order2/receive');
        $this->service_service = $this->load->service('order2/service');
    }
    
    /**
     * 根据订单编号，获取退货申请信息
     * @param type $return_id
     * @return mixed	false：查询失败；array：退货申请单信息参考get_list
     */
    public function get_info_by_order_no($order_no, $additional=[]){
        if(empty($order_no)){
            set_error("订单编号为空");
            return false;
        }
        $return_info = $this->return_table->get_info_by_order_no($order_no, $additional);
        return $return_info;
    }
    /**
     * 根据退货申请ID，获取退货申请信息
     * @param int $return_id
     * @return array	false：查询失败；array：退货申请单信息参考get_list
     */
    public function get_info($return_id, $additional=[]){
	    $return_info = $this->return_table->get_info($return_id, $additional);
	    
        if( !$return_info ){
            set_error("信息不存在");
            return false;
        }
        // 格式化输出
        $this->_output_format($return_info);
        
        return $return_info;
    }
    private function _output_format(&$return_info){
        $_admin = model('admin/admin_user')->find($return_info['admin_id']);
        $return_info['return_status_show'] =ReturnStatus::getStatusName($return_info['return_status']);
        $return_info['loss_type_show'] =ReturnStatus::getLostName($return_info['loss_type']);
        $return_info['reason']=$this->reason_list[$return_info['reason_id']];
        $return_info['admin_name'] =$_admin['username'];

        
        $this->return_address_service =$this->load->service('order2/return_address');
        $address = $this->return_address_service->get_info($return_info['address_id']);
        $return_info['address_show'] =$address['address'];
        
        $return_info['create_time_show'] =$return_info['create_time']>0?date('Y-m-d H:i:s',$return_info['create_time']):'--';
        $return_info['update_time_show'] =$return_info['update_time']>0?date('Y-m-d H:i:s',$return_info['update_time']):'--';
        $return_info['return_check_time_show'] = $return_info['return_check_time']>0?date('Y-m-d H:i:s',$return_info['return_check_time']):'--';
    
    }

    /** 查询订单列表(带分页)
     * @param array $where	【可选】查询条件
     * [
     *      'return_id' => '',	//【可选】mixed 退货申请单ID，string|array （string：多个','分割）（array：ID数组）多个只支持
     *      'case_id' => '',	//【可选】string；退货原因ID
     *      'return_status'=>'' //【可选】int；阶段
     *      'begin_time'=>''    //【可选】int；开始时间戳
     *      'end_time'=>''      //【可选】int；  截止时间戳
     * ]
     *
     * @param array $additional	    【可选】附加选项
     * [
     *	    'page'	=> '',	           【可选】int 分页码，默认为1
     *	    'size'	=> '',	           【可选】int 每页大小，默认20
     *	    'order'	=> '',       【可选】string 排序；默认 time_DESC：时间倒序；time_ASC：时间顺序
     * ]
     * @return array
     *          'return_id'=>'',            //【必须】intID
     *          'order_id'=>'',             //【必须】int订单ID
     *          'goods_id'=>'',             //【必须】int商品id
     *          'user_id'=>'',              //【必须】int退货人ID
     *          'reason_id'=>'',            //【必须】int原因标识ID
     *          'reason_text'=>'',          //【必须】string原因
     *          'wuliu_channel_id'=>'',     //【必须】int物流ID
     *          'wuliu_no'=>'',             //【必须】string物流编号
     *          'status'=>'',               //【必须】int退货单状态 退货状态：0初始化，1退货申请，2同意退货，3不同意退货，4退货成功
     *          'admin_id'=>'',             //【必须】int审核员ID
     *          'return_check_remark'=>'',           //【必须】string审核原因
     *          'return_check_time'=>'',           //【必须】int审核时间
     *          'create_time'=>'',          //【必须】int创建时间
     * )
     * 退货单列表，没有查询到时，返回空数组
     * @author limin<limin@huishoubao.com.cn>
     */
    public function get_list($where=[],$additional=[]){
        $where = $this->_parse_order_where($where);
        // 分页
        if( !isset($additional['page']) ){
            $additional['page'] = 1;
        }
        if( !isset($additional['size']) ){
            $additional['size'] = 20;
        }

        $additional['size'] = max( $additional['size'], 20 );

        if( !isset($additional['orderby'])  || $additional['orderby'] =="" ){	// 排序默认值
            $additional['orderby']='time_DESC';
        }

        if( in_array($additional['orderby'],['time_DESC','time_ASC']) ){
            if( $additional['orderby'] == 'time_DESC' ){
                $additional['orderby'] = 'create_time DESC';
            }elseif( $additional['orderby'] == 'time_ASC' ){
                $additional['orderby'] = 'create_time ASC';
            }
        }
       $data = $this->return_table->get_list($where,$additional);
        return $data;
    }
    /**
     * 获取符合条件的服务单记录数
     * @param   array	$where  参考 get_list() 参数说明
     * @return int 查询总数
     */
    public function get_count($where=[]){
        // 参数过滤
        $where = $this->_parse_order_where($where);
        if( $where === false ){
            return 0;
        }
	
        return $this->return_table->get_count($where);
    }
    /** 查询条件过滤
     * @param array $where	【可选】查询条件
     * [
     *      'return_id' => '',	//【可选】mixed 退货申请单ID，string|array （string：多个','分割）（array：ID数组）多个只支持
     *      'case_id' => '',	//【可选】string；退货原因ID
     *      'status'=>''      //【可选】int；阶段
     *      'begin_time'=>''      //【可选】int；开始时间戳
     *      'end_time'=>''      //【可选】int；  截止时间戳
     * ]
     * @return array	查询条件
     */
    public function _parse_order_where($where=[]){
        $where = filter_array($where, [
            'business_key' => 'required|is_id',
            'order_id' => 'required|is_id',
            'order_no' => 'required|is_string',
            'return_id'    => 'required|is_id',
            'case_id'       => 'required|is_string',
            'return_status' => 'required|is_int',
            'begin_time'  => 'required',
            'end_time'    => 'required',
            'user_id' => 'required|is_id',
        ]);
	// 结束时间（可选），默认为为当前时间
	if( !isset($where['end_time']) ){
	    $where['end_time'] = time();
	}
    if( isset($where['user_id']) ){
        $where['user_id'] = ['EQ',$where['user_id']];
    }
	// 开始时间（可选）
	if( isset($where['begin_time'])){
	    if( $where['begin_time']>$where['end_time'] ){
		return false;
	    }
	    $where['create_time'] = ['between',[$where['begin_time'], $where['end_time']]];
	}else{
	    $where['create_time'] = ['LT',$where['end_time']];
	}
	unset($where['begin_time']);
	unset($where['end_time']);
	
        // return_id 支持多个订单ID查询
        if(is_string($where['return_id']) ){
            $where['return_id'] = explode(',',$where['return_id']);
            if( !is_array($where['return_id']) ){
                return false;
            }
        }
	if(count($where['return_id'])==1 ){
	    $where['return_id'] = $where['return_id'][0];
	}
	if(count($where['return_id'])>1 ){
	    $where['return_id'] = ['IN',$where['return_id']];
	}
	// order_no 订单编号查询，使用前缀模糊查询
        if( isset($where['order_no']) ){
	    $where['order_no'] = ['LIKE', $where['order_no'] . '%'];
	}
        return $where;
    }
    
    /**
     * 生成退货申请单
     * @param array $data   【必选】退货申请单信息
     * array(
     *	   'business_key' => '',    //【必须】int;业务类型
     *     'user_id'=>''            //【必须】int 用户ID
     *	   'order_id' => '',	    //【必须】int;订单ID
     *	   'order_no' => '',	    //【必须】string;订单编号
     *	   'goods_id' => '',	    //【必须】int;商品ID
     *	   'reason_id' => '',	    //【必须】int;退货原因ID
     *	   'reason_text' => '',	    //【必须】int;退货附加原因描述
     *     'loss_type'=>'',         //【必须】int;损耗类型
     *     'address_id' =>'',       //【必须】int;退货地址    
     * )
     * @return mixed	false：失败；int：退货单ID
     * 当创建失败时返回false；当创建成功时返回退货单ID
     * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
     */
    public function create($data){
        $data = filter_array($data, [
            'order_id' => 'required|is_id',
            'order_no' => 'required',
            'user_id'=>'required|is_id',
            'goods_id' => 'required|is_id',
            'reason_id' =>'required|is_id',
            'reason_text'=>'required',
            'business_key'=>'required|is_id',
            'loss_type'=>'required|is_id',
            'address_id'=>'required|is_int',
        ]);
        if( !isset($data['reason_id']) ){
            $data['reason_id'] = 0;
        }
        if( !isset($data['reason_text']) ){
            $data['reason_text'] = '';
        }
        if($data['reason_id']==0 && $data['reason_text']==''){
            return false;
        }
       // var_dump($data);die;
        if( count($data)!=9){
	        set_error('参数错误');
            return false;
        }
        // 默认状态ID
//        var_dump($where);exit;
        try {
	        // 保存退货申请单
            $data['return_status'] = zuji\order\ReturnStatus::ReturnWaiting;
            $data['create_time']=time();
            $data['update_time']=time();
            
            $return_id = $this->return_table->add($data);
            if( !$return_id ){
                $this->return_table->rollback();
                set_error("生成退款单失败");
                return false;
            }
	        // 同步状态
            $enter_return =$this->order_service->enter_return($data['order_id'],$return_id);
            if(!$enter_return){
                set_error("同步订单状态失败");
                $this->return_table->rollback();
                return false;
            }
    
        } catch (\Exception $exc) {
            // 关闭事务
            $this->return_table->rollback();
            set_error("异常错误");
            return false;
        }     
        // 提交事务
        $this->return_table->commit();
        return true;
    }

    /**
     * 管理员审核 --同意
     * @param int $return_id 【必选】退货单ID
     * @param array $data   【必选】退货单审核信息
     * array(
     *	    'admin_id' => '',	    // 【必须】 审核员ID
     *      'order_id' =>'',        //【必须】订单ID
     *      'return_check_remark'=>'',         //【必须】审核备注
     *      'return_status'=>''         //【必须】审核状态
     *      
     * )
     * @param array $receive 创建收货单
     * 
     * @return boolean  true :插入成功  false:插入失败
     * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
     *
     */
    
    public function agree_return($return_id,$data){

        if($return_id <1){
            set_error('退货单ID错误');
            return false;
        }
        $data = filter_array($data, [
            'admin_id' => 'required|is_int',
            'order_id'=>'required|is_id',
            'return_check_remark' => 'required',
            'return_status'=>'required',
        ]);

        if( count($data)!=4 ){
	        set_error('参数错误');
            return false;
        }

        try {

            $data['return_check_time']=time();
            $data['update_time']=time();
            
            $return = $this->return_table->where(['return_id'=>$return_id])->save($data);
            if(!$return){
                $this->return_table->rollback();
                set_error("退货单更新失败");
                return false;
            }
            if($data['return_status'] == ReturnStatus::ReturnHuanhuo){
                $notify_huanhuo=$this->order_service->notify_return_huanhuo($data['order_id']);
                if(!$notify_huanhuo){
                    $this->return_table->rollback();
                    set_error("同步订单状态失败");
                    return false;
                }
            }else{
                $notify_agreed=$this->order_service->notify_return_agreed($data['order_id']);
                if(!$notify_agreed){
                    $this->return_table->rollback();
                    set_error("同步订单状态失败");
                    return false;
                }
            }
    
        } catch (\Exception $exc) {
            // 关闭事务
            $this->return_table->rollback();
            set_error("异常错误");
            return false;
        }  
        // 提交事务
        return true;
    }
    
    /**
     * 用户取消退货
     * @param int  $return_id 【必须】 退货单ID
     * @param int  $order_id 【必须】订单ID
     * @param int $receive_id 【必须】收货单ID
     * @return boolean  true :插入成功  false:插入失败
     * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
     *
     */
    
    public function return_cancel($return_id,$order_id,$receive_id){

        if( $return_id <1){
            set_error('退货单ID错误');
            return false;
        } 
        if($order_id <1){
            set_error('订单ID错误');
            return false;
        }

        
       try { 
           
           $data['update_time']=time();
           $data['return_status']=ReturnStatus::ReturnCanceled;          
           $return = $this->return_table->where(['return_id'=>$return_id])->save($data);
           
           if(!$return){
               $this->return_table->rollback();
               set_error("更新退货单失败");
               return false;
           }
           $order_ret = $this->order_service->notify_return_cancel($order_id);
           if (!$order_ret) {
                $this->return_table->rollback();
                set_error("更新订单失败");
                return false;
           }
           
           if($receive_id >0){
               $receive_cancel = $this->receive_service->receive_cancel($receive_id, $order_id);
               if (!$receive_cancel) {
                   $this->return_table->rollback();
                    set_error("更新收货单失败");
                    return false;
               }  
           }
           
       }catch (\Exception $exc) {
            // 关闭事务
            $this->return_table->rollback();
            set_error("异常错误");
            return false;
        }  
        // 提交事务
        $this->return_table->commit();
        return true;
    }
    
    /**
     * 确认用户收货
     * @param int $receive_id  //【必须】 收货单ID
     * @param array $data   【必选】退货单审核信息
     * array(
     *	    'return_id' => '',             // 【必须】 退货单ID
     *      'admin_id'=>                    //【可选】后台操作员ID
     *      'order_id'=>                    //【必须】订单ID
     * )
     * @return boolean  true :插入成功  false:插入失败
     * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
     *
     */
    
    public function return_confirm($return_id,$data){
        if($return_id <1){
            set_error("退货单ID错误");
            return false;
        }
        $data = filter_array($data, [
            'receive_id' => 'required|is_id',
            'admin_id' => 'required',
            'order_id' => 'required|is_id',
        ]);
        if(!isset($data['admin_id'])){
            $data['admin_id']=0;
        }
    
        if( count($data)!=3 ){
            set_error('参数错误');
            return false;
        }

            $data['return_status']=ReturnStatus::ReturnConfirmed;
            $data['update_time']=time();
            
            $return = $this->return_table->where(['return_id'=>$return_id])->save($data);
            if(!$return){
                $this->return_table->rollback();
                set_error("更新退货单失败");
                return false;
            }
    
            return true;
    
    }
    
    
    /**
     * 管理员审核 --不同意
     * @param int $return_id 【必选】退货单ID
     * @param array $data   【必选】退货单审核信息
     * array(
     *      'order_id' =>'',        // 【必须】订单ID
     *	    'admin_id' => '',	    // 【必须】 审核员ID
     *	    'return_check_remark' => ''，         // 【可选】 管理员审批内容
     * )
     * @return boolean  true :插入成功  false:插入失败
     * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
     *
     */

    public function deny_return($return_id,$data){  
        if($return_id<1){
            set_error("退货单ID错误");
            return false;
        }
        $data = filter_array($data, [
            'admin_id' => 'required|is_id',
            'return_check_remark' => 'required',
            'order_id'=>'required|is_id',
        ]);
        if( !isset($data['return_check_remark']) ){
            $data['return_check_remark'] = '';
        }
        if( count($data)!=3 ){
            set_error('参数错误');
            return false;
        }
        
        try {
            //退货单状态改为不同意
            $data['return_status']=ReturnStatus::ReturnDenied;
            $data['return_check_time']=time();
            $data['update_time']=time();
            
            $return = $this->return_table->where(['return_id'=>$return_id])->save($data);
            if(!$return){
                $this->return_table->rollback();
                set_error("更新退货单失败");
                return false;
            }
            
            $notify_denied=$this->order_service->notify_return_denied($data['order_id']);
            
            if(!$notify_denied){
                $this->return_table->rollback();
                set_error("同步订单状态失败");
                return false;
            }
            
        } catch (\Exception $exc) {
            // 关闭事务
            $this->return_table->rollback();
            set_error("异常错误");
            return false;
        }
        
        // 提交事务
        $this->return_table->commit();
        return true;
    }


}