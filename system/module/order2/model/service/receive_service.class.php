<?php
use zuji\order\ReceiveStatus;
/**
 * 收货单
 *@author wuhaiyan<wuhaiyan@huishoubao.com.cn>
 *@copyright (c) 2017, Huishoubao
 */
class receive_service extends service {

	public function _initialize() {
		//实例化数据层
		$this->receive_table = $this->load->table('order2/order2_receive');
		$this->order_service = $this->load->service('order2/order');
		$this->evaluation_service = $this->load->service('order2/evaluation');
	}
	
	/**
	 * 取消收货单
	 * 注意：这个方法是嵌套别的事务中 不要加事务
     * @param int $receive_id  //【必须】 收货单ID
     * @param int $order_id  //【必须】订单ID
     * @return boolean  true :插入成功  false:插入失败
     * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
     *
	 */
	public function receive_cancel($receive_id,$order_id){
	    if(!isset($receive_id) || $receive_id<1){
	        set_error("参数错误");
	        return false;
	    }
	    if(!isset($order_id) || $order_id<1){
	        set_error("参数错误");
	        return false;
	    }
	    try {
	        $data=[
	            'update_time'=>time(),
	            'receive_status'=>ReceiveStatus::ReceiveCanceled,
	        ];	        
	        $receive=$this->receive_table->where(['receive_id'=>$receive_id])->save($data);
	        if(!$receive){
	            set_error("修改收货单失败");
	            return false;
	        }
	        $notify_receive_cancel =$this->order_service->notify_receive_cancel($order_id);
	        if(!$notify_receive_cancel){
	            set_error("同步订单状态失败");
	            return false;
	        }  	    
	    } catch (\Exception $exc) {
	        set_error("异常错误");
	        return false;
	    }
	    return true;

	}
	
	
	/**
	 * 更新收货单物流单号 
	 * @param int $receive_id 【必选】收货单id
	 * @param array   $data	【必选】
	 * array(
	 *      'wuliu_no'=>''   //物流单号
	 *      'wuliu_channel_id'=>''   //物流渠道
	 * )
	 * @author wuhaiyan<wuhaiyan@huishoubao.com.cn>
	 * @return  false  :更新失败   其余为主键id：成功
	 *
	 */
	public function edit_received_goodsFlow($receive_id,$data=array())
	{

	    $data = filter_array($data, [
	        'wuliu_channel_id' => 'required|is_int',
	        'wuliu_no' =>'required',
	    ]);
	    if( count($data)<2 ){ 
	        set_error("参数错误");
	        return false;
	    }
	    $data['update_time'] = time();
	    $data = $this->receive_table->where(['receive_id'=>$receive_id])->save($data);
	    return $data;
	}
	
	/**
	 * 更新收货单条码  （入口方法名称）
	 * @param int $receive_id 收货ID
	 * @param int $bar_code 条码
	 * @author wuhaiyan<wuhaiyan@huishoubao.com.cn>
	 * @return  false  :更新失败   其余为主键id：成功
	 *
	 */
	public function edit_received_barCode($receive_id,$bar_code)
	{

	    if(!isset($receive_id) || $receive_id <0){
	        set_error("参数错误");
	        return false;
	    }
	    if(!isset($bar_code)){
	        set_error("参数错误");
	        return false;
	    }
	    $data['bar_code'] =$bar_code;
	    $data['update_time'] = time();
	    $data = $this->receive_table->where(['receive_id'=>$receive_id])->save($data);
	    return $data;
	}



	/**
	 * 创建收货单
	 * @param array   $data	     【必选】
	 * array(
	 *      'order_id' => '',	//【必选】   int 订单ID
	 *      'order_no'= '',	    //【必选】   int 订单编号
	 *      'goods_id' => '',	//【必选】  int 商品ID
	 *      'address_id '=>''	//【必选】 int 地址ID
	 *      'business_key'=>''	//【必选】 业务类型ID
	 *      )
	 * @author wuhaiyan<wuhaiyan@huishoubao.com.cn>
	 * @return mixed    false:创建失败；int：创建成功，返回收货单ID
	 *
	 */
	public function create( $data )
    {
		 $data = filter_array($data, [
            'order_id' => 'required|is_id',
            'goods_id' =>'required|is_id',
		    'order_no' =>'required',
            'address_id' =>'required',
            'business_key' =>'required|is_id',
        ]);
		 if(!isset($data['address_id'])){
		     $data['address_id']=1;
		 }
        //zuji\debug\Debug::error(\zuji\debug\Location::L_Receive, '平台收货创建参数', $data);
        if( count($data)!=5 ){ 
            set_error("参数错误");
            return false;
        }
		try {
		    // 开启事务		    
		    $data['update_time'] =time();
		    $data['create_time'] =time();
		    $data['receive_status']=ReceiveStatus::ReceiveWaiting;
		    $data['wuliu_channel_id']=1;
		    $receive_id = $this->receive_table->add($data);
		    if(!$receive_id){
		        $this->receive_table->rollback();
		        set_error("创建收货单失败");
		        return false;
		    }
		    
		    $enter_receive =$this->order_service->enter_receive($data['order_id'],$receive_id);
            if(!$enter_receive){
                $this->receive_table->rollback();
                set_error("同步到订单状态失败");
                return false;
            }
            $notify_waiting=$this->order_service->notify_receive_waiting($data['order_id']);
            
            if(!$notify_waiting){
                $this->receive_table->rollback();
                set_error("同步到订单状态失败");
                return false;
            }
            
            
            
		} catch (\Exception $exc) {
		    // 关闭事务
		    $this->receive_table->rollback();
		    set_error("异常错误");
		    return false;
		}
		
		// 提交事务
		$this->receive_table->commit();
		return $receive_id;
     }
     


     /**
      * 确认收货
      * @param int $receive_id 【必选】 收货单ID
      * @param array   $data	【必选】
      * array(
      *         'order_id'=>''          【必须】订单ID
      *         'admin_id'=>'',         【可选】  int 后台操作员 ID
      * )
      * @author wuhaiyan<wuhaiyan@huishoubao.com.cn>
      * @return  false  :更新失败   true:成功
      *
      */
     public function edit_confirmed($receive_id,$data)
     {
        
         $data = filter_array($data,[
             'admin_id'=>'required',
             'order_id' => 'required',
         ]);
         if( !isset($receive_id) || $receive_id<0){
             set_error('参数错误');
             return false;
         }
        
         if( count($data)!=2 ){
             set_error('确认收货失败');
             return false;
         }
         try {
             // 开启事务
             $this->receive_table->startTrans();
             // 保存退货申请单
             $data['update_time'] =time();
             $data['receive_time'] =time();
             $data['receive_status'] =ReceiveStatus::ReceiveFinished;
             
             $receive =$this->receive_table->where(['receive_id'=>$receive_id])->save($data);
  
             if( !$receive ){
                 $this->receive_table->rollback();
                 set_error("更改收货单信息失败");
                 return false;
             }

             // 同步状态
             $receive_confirmed =$this->order_service->notify_receive_confirmed($data['order_id']);
             if(!$receive_confirmed){
                 $this->return_table->rollback();
                 set_error("同步到订单状态失败");
                 return false;
             }
              
             
             $notify_finished=$this->order_service->notify_receive_finished($data['order_id']);
             if(!$notify_finished){
                 $this->receive_table->rollback();
                 set_error("同步到订单状态失败");
                 return false;
             }
         
             
              
              
         } catch (\Exception $exc) {
             // 关闭事务
             $this->receive_table->rollback();
             set_error("异常错误");
             return false;
         }
         // 提交事务
         $this->receive_table->commit();
         return true;
     
     }

	/**
	 * 获取单个收货单
	 * @param array   $receive_id	【必选】
     * @param array $additional
     * [
     *      'lock' =>'',【可选】bool 是否加锁
     * ]  
	 * @author wuhaiyan<wuhaiyan@huishoubao.com.cn>
	 * @return  array 参考get_list参数
	 */
	public function get_info($receive_id,$additional=[])
	{
	    if($receive_id <0){
	        set_error("收货单ID");
	        return false;
	    }
	    $where =[
	        'receive_id' =>$receive_id,
	    ];
	    
	    if(!isset($additional['lock'])){
	        $additional['lock']=false;
	    }
	    
		$receive_info = $this->receive_table->get_by_id($where,$additional);
		
        if( !$receive_info ){
            return false;
        }
        // 格式化输出
        $this->_output_format($receive_info);
        
        return $receive_info;

	}
    /**
     * 根据订单id获取单个收货单
     * @param array   $order_id	【必选】
     * @author wuhaiyan<wuhaiyan@huishoubao.com.cn>
     * @return  array 参考get_list参数
     */
    public function get_info_by_order_id($order_id, $additional=[])
    {
        $receive_info = $this->receive_table->get_by_order_id($order_id, $additional);

        if( !$receive_info ){
            return false;
        }
        // 格式化输出
        $this->_output_format($receive_info);

        return $receive_info;

    }
    /**
     * 上传物流单号
     * @param array   $data	【必选】
     * @author limin<limin@huishoubao.com.cn>
     * @return  array 参考get_list参数
     */
    public function set_wuliu_no($data)
    {
        $data = filter_array($data,[
            'receive_id'=>'required',
            'wuliu_channel_id'=>'required',
            'wuliu_no'=>'required'
        ]);

        $data['update_time'] = time();
        $ret = $this->receive_table->where(['receive_id'=>$data['receive_id']])->save($data);
        return $ret;
    }
	private function _output_format(&$receive_info){
	    $_admin = model('admin/admin_user')->find($receive_info['admin_id']);
	    $receive_info['receive_status_show'] =ReceiveStatus::getStatusName($receive_info['receive_status']);
	    $receive_info['admin_name'] =$_admin['username'];
	    $this->return_address_service =$this->load->service('order2/return_address');
	    $address = $this->return_address_service->get_info($receive_info['address_id']);	    
	    $receive_info['address_show'] =$address['address'];
	    $receive_info['create_time_show'] =$receive_info['create_time']>0?date('Y-m-d H:i:s',$receive_info['create_time']):'--';
	    $receive_info['update_time_show'] =$receive_info['update_time']>0?date('Y-m-d H:i:s',$receive_info['update_time']):'--';
	    $receive_info['receive_time_show'] = $receive_info['receive_time']>0?date('Y-m-d H:i:s',$receive_info['receive_time']):'--';
	
	}


    /**
     * 根据订单状态获取全部收货单
     * @param array     $where      【可选】搜索条件
     * [
     *      'receive_id' => '',     //【可选】mixed；收货单ID，支持多个（string：多个用逗号分隔；array：ID集合）
     *      'goods_id' => '',       //【可选】mixed；商品ID，支持多个（string：多个用逗号分隔；array：ID集合）
     *      'business_key' => '',   //【可选】int；业务类型
     *      'receive_status' => '', //【可选】int；状态
     *      'time_type' => '',      //【可选】string；时间类型：create_time：创建时间；receive_time：收货时间
     *      'begin_time' => '',     //【可选】int；开始时间戳
     *      'end_time' => '',       //【可选】int；结束时间戳
     *      'wuliu_channel_id' =>'',//【可选】int；物流渠道ID
     *      'wuliu_no' => '',       //【可选】string；物流单号（支持前缀模糊搜索）
     *      'bar_code' => '',       //【可选】string；收货条码（支持前缀模糊搜索）
     *      'order_no' => '',       //【可选】string；订单编号（支持前缀模糊搜索） 
     * ]     
     * @param array $additional	    【可选】附加选项
     * array(
     *	    'page'	=> '',	           【可选】int 分页码，默认为1
     *	    'size'	=> '',	           【可选】int 每页大小，默认20
     *	    'orderby'	=> '',  【可选】string	排序；create_DESC：按创建时间排序 receive_DESC 按确认收货时间排序  默认receive_DESC
     * )

     * @return  array
     *
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
        $additional['size'] = max( $additional['size'], 20 );

        if( !isset($additional['orderby'])  || $additional['orderby'] ==""){	// 排序默认值
            $additional['orderby']='create_DESC';
        }

        if( in_array($additional['orderby'],['create_DESC','receive_DESC']) ){
            if( $additional['orderby'] == 'create_DESC' ){
                $additional['orderby'] = 'create_time DESC';
            }elseif( $additional['orderby'] == 'receive_DESC' ){
                $additional['orderby'] = 'receive_time DESC';
            }
        }
        $data = $this->receive_table->get_list($where,$additional);
        if(!is_array($data)){
            return [];
        }
         
        return $data;
  
    
    }

    /**
     * 获取符合条件的收货单记录数
     * @param   array	$where  参考 get_list() 参数说明
     * @return int 查询总数
     */
    public function get_count($where=[]){
        // 参数过滤
        $where = $this->_pars_where($where);
        if( $where === false ){
            return 0;
        }
        return $this->receive_table->get_count($where);
    }

    private function _pars_where($where=[]){
        $where = filter_array($where,[
            'receive_id' => 'required',
            'goods_id'=>'required',
            'business_key' => 'required|is_int',
            'receive_status' => 'required|is_int',
            'time_type' => 'required|is_string',
            'begin_time' => 'required|is_int',
            'end_time' => 'required|is_int',
            'wuliu_channel_id' => 'required|is_int',
            'wuliu_no' => 'required',
            'bar_code' => 'required',
            'order_id' => 'required',
            'order_no' => 'required',
        ]);
        if( isset($where['receive_id']) ){
            
            if(!is_array($where['receive_id']) && is_string($where['receive_id']) ){
                $where['receive_id'] = explode(',',$where['receive_id']);
            }
            if( count($where['receive_id'])==0 ){
                unset($where['receive_id']);
            }else if(count($where['receive_id'])==1 ){
                $where['receive_id']= $where['receive_id'][0];
            }
        }
        // 查询条件
        // goods_id
            if( isset($where['goods_id']) ){
            
            if(!is_array($where['goods_id']) && is_string($where['goods_id']) ){
                $where['goods_id'] = explode(',',$where['goods_id']);
            }
            if( count($where['goods_id'])==0 ){
                unset($where['goods_id']);
            }else if(count($where['goods_id'])==1 ){
                $where['goods_id']= $where['goods_id'][0];
            }else {
                $where['goods_id'] = ['in',$where['goods_id']];
            }
        }
        // 时间查询条件，先根据 time_type 获取查询哪个时间，
        $time_key = '';
        // time_type (支持 create_time和receive_time)
        if( isset($where['time_type']) ){
            if( $where['time_type']=='create_time' ){
                $time_key = 'create_time';
            }elseif( $where['time_type']=='receive_time' ){
                $time_key = 'receive_time';
            }else{
                return false;// 时间类型参数错误
            }
            unset($where['time_type']);
        }
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
        if(isset($where['wuliu_no'])){
        // wuliu_no 物流单号，使用前缀模糊查询
            $where['wuliu_no'] = ['LIKE', $where['wuliu_no'] . '%'];
        }
        if(isset($where['bar_code'])){
            $where['bar_code'] = ['LIKE', $where['bar_code'] . '%'];
        }
        if(isset($where['order_no'])){
            $where['order_no'] = ['LIKE', $where['order_no'] . '%'];
        }
        
        unset($where['begin_time']);
        unset($where['end_time']);

        return $where;

    }



    }