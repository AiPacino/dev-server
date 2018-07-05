<?php
use zuji\order\ServiceStatus;
/**
 * 服务单
 *@author wuhaiyan<wuhaiyan@huishoubao.com.cn>
 *@copyright (c) 2017, Huishoubao
 */
class service_service extends service {

	public function _initialize() {
		//实例化数据层
	    $this->service_table = $this->load->table('order2/order2_service');
	    $this->order_service =$this->load->service('order2/order');
	}
	
	/**
	 * 获取某用户是否有其他开启的服务单
	 * @param int $user_id 【必须】 用户ID
	 * @return boolean  Y:有开启的服务单(count大于0)；  N：没有开启的服务单； false：查询错误
	 * @author wuhaiyan<wuhaiyan@huishouhao.com.cn>
	 */
	public function has_open_service($user_id, $cert_no){
	    if(!isset($user_id) || $user_id <0){
	        set_error("参数错误");
	        return false;
	    }
	    return $this->service_table->has_open_service($user_id, $cert_no);
	}
	/**
	 * 计算结束时间
	 * @param int $begin_time
	 * @param int $month
	 * @return int 结束时间戳
	 */
	public function calculate_end_time($begin_time, $month){
	    //$info = getdate( $begin_time );
	    if($month ==12){
	        return strtotime("+365 day",$begin_time);
	    }else if($month ==6){
	        return strtotime("+180day",$begin_time);
	    }else{
	        return strtotime("+90day",$begin_time);
	    }

	    
	}


	/**
	 * 创建服务
	 * @param array   $data	      【必选】
	 * array(
	 *      'order_id' => '',	 //【必选】  int 订单ID
	 *      'order_no'=> '',	 //【必选】  string 订单编号
	 *      'mobile' => '',	     //【必选】  string 用户手机号
	 *      'user_id '=>''	     //【必选】 int 用户ID
	 *      'business_key'=>''	 //【必选】 int 业务类型ID
	 *      'begin_time'=>''	 //【必选】 int 服务开始时间戳
	 *      'zuqi'=>''           //【必选】 int 租期
	 *      )
	 * @author wuhaiyan<wuhaiyan@huishoubao.com.cn>
	 * @return mixed    false:创建失败；int：创建成功，返回服务单id：成功
	 *
	 */
	public function create( $data=array() )
    { 

		 $data = filter_array($data, [
            'order_id' => 'required|is_id',
            'order_no' =>'required',
		    'mobile' =>'required',
		    'user_id'=>'required|is_int',
            'business_key' =>'required|is_int',
		    'begin_time' =>'required|is_int',
		    'zuqi' =>'required|is_int',
        ]);

        if( count($data)!=7 ){ 
            set_error("参数错误");
            return false;
        }
        
        $data['end_time'] =$this->calculate_end_time($data['begin_time'], $data['zuqi']);
        unset($data['zuqi']);
        
        $service_id =$this->service_table->create($data);
        if(!service_id){
            set_error("创建服务单失败");
            return false;
        }
        return $service_id;
           
      
     }
     
     /**
      * 修改服务时间
      * @param  string  $order_no//【必须】 订单编号
      * @param  int $end_time //【必须】服务结束时间戳
      * @return boolean  true :插入成功  false:插入失败
      * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
      *
      */
     public function update_service_time($order_no,$end_time){
         if(!isset($order_no)){
             set_error("订单编号有错");
             return false;
         }
         if(!isset($end_time)){
             set_error("结束时间有误");
             return false;
         }
         $service =$this->service_table->update_time($order_no,$end_time);
         if(!service){
             set_error("服务单更新时间失败");
             return false;
         }
         return $service;
     }
     
     /**
      * 取消服务
      * @param int $order_id  //【必须】 订单ID
      * @return boolean  true :插入成功  false:插入失败
      * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
      *
      */
     public function service_cancel($order_id){
         if(!isset($order_id)){
             set_error("订单ID有错");
             return false;
         }
             $service =$this->service_table->cancel($order_id);
             if(!service){
                 set_error("服务单更新失败");
                 return false;
             }
             return true;   
     }
     
     /**
      * 关闭服务
      * @param int $service_id  //【必须】 服务单ID
      * @param int $order_id    //【必须】 订单ID
      * @param string $remark   //【可选】 备注
      * @return boolean  true :插入成功  false:插入失败
      * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
      *
      */
     public function service_close($service_id,$order_id,$remark=""){      
         if($service_id<1){
             set_error("服务单ID错误");
             return false;
         }
         if($order_id<1){
             set_error("订单ID错误");
             return false;
         }
         
         try{
             // 开启事务
             $data['update_time'] =time();
             $data['remark']=$remark;
             $data['service_status']=ServiceStatus::ServiceClose;
            
             $service =$this->service_table->where(['service_id'=>$service_id])->save($data);
             if(!service){
                 $this->service_table->rollback();
                 set_error("服务单更新失败");
                 return false;
             }
             
            //状态同步到订单表
            $notify_close =$this->order_service->notify_service_close($order_id);
            if(!$notify_close){
                $this->service_table->rollback();
                set_error("同步到订单状态失败");
                return false;
            } 
            
            //关闭订单
            $close_order =$this->order_service->close_order($order_id);
            if(!$close_order){
                $this->service_table->rollback();
                set_error("订单关闭失败");
                return false;
            }
            
            
         }catch (\Exception $exc) {
            $this->service_table->rollback();
            set_error('异常错误：'.$exc->getMessage());
            return false;
        }
        
        $this->service_table->commit();
        return true;

     }

    /**
     * 获取单个服务
     * @param array   $service_id	【必选】
     * @author wuhaiyan<wuhaiyan@huishoubao.com.cn>
     * @return  array 参考get_list参数
     */
    public function get_service_info($where=[])
    {
        $data = $this->service_table->get_service_info($where);
        return $data;

    }
	/**
	 * 获取单个服务
	 * @param array   $service_id	【必选】
     * @param array $additional
     * [
     *      'lock' =>'',【可选】bool 是否加锁
     * ]   
	 * @author wuhaiyan<wuhaiyan@huishoubao.com.cn>
	 * @return  array 参考get_list参数
	 */
	public function get_info($service_id,$additional=[])
	{
	    $where =[
	        'service_id' =>$service_id,
	    ];
	    
	    if(!isset($additional['lock'])){
	        $additional['lock']=false;
	    }
	    
		$service_info = $this->service_table->get_by_id($where,$additional);
		if( !$service_info ){
            return false;
        }
        // 格式化输出
        $this->_output_format($service_info);
        
        return $service_info;

	}
	private function _output_format(&$service_info){
	    $service_info['service_status_show'] =ServiceStatus::getStatusName($service_info['service_status']);
	    $service_info['create_time_show'] =$service_info['create_time']>0?date('Y-m-d H:i:s',$service_info['create_time']):'--';
	    $service_info['update_time_show'] =$service_info['update_time']>0?date('Y-m-d H:i:s',$service_info['update_time']):'--';
	    $service_info['begin_time_show'] = $service_info['begin_time']>0?date('Y-m-d H:i:s',$service_info['begin_time']):'--';
	    $service_info['end_time_show'] = $service_info['end_time']>0?date('Y-m-d H:i:s',$service_info['end_time']):'--';
	
	}
    /**
     * 根据订单Id获取单个服务
     * @param array   $service_id	【必选】
     * @author wuhaiyan<wuhaiyan@huishoubao.com.cn>
     * @return  array 参考get_list参数
     */
    public function get_info_by_order_id($order_id)
    {
        $data = $this->service_table->get_by_order_id($order_id);
        return $data;

    }

    /**
     * 根据查询条件 获取服务单列表
     * @param array     $where      【可选】搜索条件
     * [
     *      'service_id' => '',     //【可选】mixed；服务单ID，支持多个（string：多个用逗号分隔；array：ID集合）
     *      'order_no' => '',       //【可选】订单编号
     *      'mobile' => '',         //【可选】会员手机号
     *      'business_key' => '',   //【可选】业务类型
     *      'service_status' => '', //【可选】服务状态
     *      'begin_time' => '',     //【可选】int；服务开始时间戳
     *      'end_time' => '',       //【可选】int；服务结束时间戳
     * ]     
     * @param array $additional	    【可选】附加选项
     * array(
     *	    'page'	=> '',	           【可选】int 分页码，默认为1
     *	    'size'	=> '',	           【可选】int 每页大小，默认20
     *	    'orderby'	=> '',  【可选】string	排序；create_time：按创建时间排序 end_time asc 按服务快结束时间排序   默认end_time
     * )
     * @return 
     * [
     *      'service_id' => '',     // 服务单ID
     *      'order_id' => '',       // 订单ID
     *      'order_no' => '',       // 订单编号
     *      'mobile' => '',         // 会员手机号
     *      'user_id' => '',        // 会员ID
     *      'business_key' => '',   // 业务类型
     *      'service_status' => '', // 服务状态
     *      'begin_time' =>'',      // 服务开始时间
     *      'end_time' => '',       // 服务结束时间
     *      'create_time' => '',    // 服务创建时间
     *      'update_time' => '',    // 最后更新时间 
     * ] 
	 * @author wuhaiyan<wuhaiyan@huishoubao.com.cn>  
     */
    public function get_list($where=[],$additional=[]){

        $where = $this->_pars_where($where);
        if( $where === false ){
            return [];
        }
        

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
        $additional['size'] = min( $additional['size'], 20 );

        if( !isset($additional['orderby'])  || $additional['orderby'] =="" ){	// 排序默认值
            $additional['orderby']='end_time_ASC';
        }

        if( in_array($additional['orderby'],['end_time_ASC','end_time_DESC','begin_time_ASC','begin_time_DESC']) ){
            if( $additional['orderby'] == 'begin_time_ASC' ){
                $additional['orderby'] = 'begin_time ASC';
            }elseif( $additional['orderby'] == 'begin_time_DESC' ){
                $additional['orderby'] = 'begin_time DESC';
            }elseif( $additional['orderby'] == 'end_time_ASC' ){
                $additional['orderby'] = 'end_time ASC';
            }else{
                $additional['orderby'] = 'end_time DESC';
            }
        }

        $data = $this->service_table->get_list($where,$additional);
        if(!is_array($data)){
            return [];
        }
         
        return $data;
  
    
    }

    /**
     * 获取符合条件的服务单记录数
     * @param   array	$where  参考 get_list() 参数说明
     * @return int 查询总数
     */
    public function get_count($where=[]){
        // 参数过滤
        $where = $this->_pars_where($where);
        if( $where === false ){
            return 0;
        }
        return $this->service_table->get_count($where);
    }

    private function _pars_where($where){
        $where = filter_array($where,[
            'service_id' => 'required|is_id',
            'order_no' => 'required',
            'mobile'=>'required',
            'business_key' => 'required|is_int',
            'service_status' => 'required|is_int',
            'begin_time' => 'required|is_int',
            'end_time' => 'required|is_int',
        ]);

        if( isset($where['service_id']) ){
            
            if(!is_array($where['service_id']) && is_string($where['service_id']) ){
                $where['service_id'] = explode(',',$where['service_id']);
            }
            if( count($where['service_id'])==0 ){
                unset($where['service_id']);
            }else if(count($where['service_id'])==1 ){
                $where['service_id']= $where['service_id'][0];
            } 
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
        
/*         // 开始时间（可选）
        if( isset($where['begin_time'])){
            $where['begin_time'] = ['EGT',$where['begin_time']];
        } 
        // 结束时间（可选）
        if( isset($where['end_time'])){
            $where['end_time'] = ['ELT',$where['end_time']];
        }
 */
        
        if(isset($where['mobile'])){
            $where['mobile'] = ['LIKE', $where['mobile'] . '%'];
        }
        if(isset($where['order_no'])){
            $where['order_no'] = ['LIKE', $where['order_no'] . '%'];
        }


        return $where;

    }



    }