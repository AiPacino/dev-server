<?php
use zuji\Business;
use zuji\order\ReceiveStatus;
use zuji\debug\Location;
use zuji\order\OrderStatus;
use zuji\Config;
use zuji\order\receive\Receive;
use zuji\order\Order;
use zuji\debug\Debug;
/**
 * 		后台订单控制器
 */
hd_core::load_class('base','order2');
class receive_control extends base_control {
    /**
     * @var array 关键字搜索类型列表
     */
    protected $keywords_type_list = [
        'order_no' => '订单编号',
        'bar_code' => '条码',
        'wuliu_no' => '物流编号',
    ];
	public function _initialize() {
		parent::_initialize();
		// $this->order_service = $this->load->service('order2/order');
		// $this->receive_service = $this->load->service('order2/receive');
		// $this->logistics_service = $this->load->service('order2/logistics');
	
		//权限判断
		$promission_arr = [];
		$promission_arr['receive_confirmed'] = $this->check_promission_operate('order2','receive','receive_confirmed');
		//$promission_arr['create_evaluation'] = $this->check_promission_operate('order2','receive','create_evaluation');
		
		$this->promission_arr = $promission_arr;
	}
	
	/*
	 * 全部收货列表
	 */

    public function index(){
        $this->order_service = $this->load->service('order2/order');
        $this->receive_service = $this->load->service('order2/receive');
        $this->logistics_service = $this->load->service('order2/logistics');
        $where = [];
        if(isset($_GET['receive_status']) && $_GET['receive_status']>0){
            $where['receive_status'] = intval($_GET['receive_status']);
        }
        if(isset($_GET['time_type'])){
            $where['time_type']=$_GET['time_type'];
             if($_GET['begin_time']!='' ){
                $where['begin_time']=strtotime($_GET['begin_time']);
            }
            if($_GET['end_time']!='' ){
                $where['end_time']=strtotime($_GET['end_time']);
            }
        }
        if(intval($_GET['business_key'])>0 ){
            $where['business_key'] = intval($_GET['business_key']);
        }
        if($_GET['keywords']!=''){         
            if($_GET['kw_type']=='bar_code'){
                $where['bar_code'] = $_GET['keywords'];
            }
            elseif($_GET['kw_type']=='wuliu_no'){
                $where['wuliu_no'] = $_GET['keywords'];
            }
            else{
                $where['order_no'] = $_GET['keywords'];
            }
        }
        $size = (isset($_GET['limit']) && is_numeric($_GET['limit'])) ? $_GET['limit'] : 20;
        $count  = $this->receive_service->get_count($where);
        $pages  = $this->admin_pages($count,$size);

        $additional =[
            'page'=>intval($_GET['page']),
            'size'=>$size,
            'orderby'=>'',
        ];
         
        $receive_list = $this->receive_service->get_list($where,$additional);
        //var_dump($receive_list);die;
        $order_ids = array_column($receive_list, 'order_id');
        $order_ids = array_unique($order_ids);
         
        $order_list = $this->order_service->get_order_list(['order_id'=>$order_ids],['size'=>count($order_ids)]);
        mixed_merge($receive_list, $order_list, 'order_id','order_info');
        //var_dump($receive_list);die;
        foreach($receive_list as $k=>&$item)
        {
            if(!isset($item['order_info'])){continue;}
            $item['business_name']  = Business::getName($item["business_key"]);
            $item['goods_name'] = $item['order_info']['goods_name'];
            $item['mobile'] = $item['order_info']['mobile'];
            $item['status'] = ReceiveStatus::getStatusName($item["receive_status"]);
            $item['wuliu_channel'] = $this->logistics_service->get_name($item['wuliu_channel_id']);;
            $item['order_status'] =$item['order_info']['order_status'];
	    
    	    //$Order = Order::createOrder($item['order_info']);
    	    $Receive = Receive::createReceive($item);
    	    $Orders = new \oms\Order($item['order_info']);

            //是否允许审核
            $item['receive_confirmed'] =false;
            if($this->promission_arr['receive_confirmed'] && $Receive->allow_receive_confirmed() && !$Orders->order_islock()){
                $item['receive_confirmed'] =true;
            }
            //是否允许创建检测单
//             $item['create_evaluation']=false;
//             if($this->promission_arr['create_evaluation'] && $Receive->allow_create_evaluation() && $Order->is_open()){
//                 $item['create_evaluation'] =true;
//             }

        }
        
        $lists = array(
            'th' => array(
                'business_name' => array('length' => 8,'title' => '业务类型'),
                'order_no' => array('title' => '订单编号','length' => 10),
                'mobile' => array('length' => 10,'title' => '会员账号'),
                'wuliu_channel' => array('length' => 8,'title' => '物流名称'),
                'wuliu_no' => array('length' => 10,'title' => '物流单号','style'=>"double_click"),
                'bar_code' => array('length' => 10,'title' => '条码','style'=>"double_click"),
                'status' => array('length' => 10,'title' => '收货状态'),          
                'goods_name' => array('length' => 15,'title' => '退货商品'),
                'receive_time' => array('length' => 9,'title' => '收货时间','style'=>"date"),
            ),
            'lists' => $receive_list,
            'pages' => $pages,
        );
        
      	$status_list = array_merge(['0'=>'全部'],ReceiveStatus::getStatusList());
	    $tab_list = [];
	    foreach( $status_list as $k=>$name ){
	        if($k ==ReceiveStatus::ReceiveCreated){
	            continue;
	        }
    		$css = '';
    		if ($_GET['receive_status'] == $k){
    		    $css = 'current';
    		}
    		$url = url('order2/receive/index',array('receive_status'=>$k));
    		$tab_list[] = '<a class="'.$css.'" href="'.$url.'">'.$name.'</a>';
    	}
     

	    $this->load->librarys('View')
            ->assign('tab_list',$tab_list)
            ->assign('pay_channel_list',$this->pay_channel_list)
            ->assign('keywords_type_list',$this->keywords_type_list)
            ->assign('lists',$lists)->assign('pages',$pages)->display('receive_index');
    }
 
    /**
     *收货单详情
     */
    public function detail(){
        $this->receive_service = $this->load->service('order2/receive');
        $this->logistics_service = $this->load->service('order2/logistics');
        // 是否内嵌
        $inner = boolval($_GET['inner']);
        $receive_id = intval(trim($_GET['receive_id']));
        if ($receive_id < 1) showmessage('收货单ID错误','null');

        $receive_info = $this->receive_service->get_info($receive_id);
        $receive_info['wuliu_channel']=$this->logistics_service->get_name($receive_info['wuliu_channel_id']);
        $this->load->librarys('View')
        ->assign('inner', $inner)
        ->assign('receive_info', $receive_info)
        ->display('receive_detail');
    }
    /*
     * 点击收货
     */
    public function receive_confirmed() {
        $this->order_service = $this->load->service('order2/order');
        $this->receive_service = $this->load->service('order2/receive');

        if (checksubmit('dosubmit')) {
            $admin = [
                'id' =>$this->admin['id'],
                'username' =>$this->admin['username'],
            ];
            $Operator = new oms\operator\Admin( $admin['id'], $admin['username'] );
            $trans = $this->order_service->startTrans();
            if(!$trans){
                showmessage("服务器繁忙 请稍候再试！","null",0);
            }
            $additional =[
                'lock' =>true,
            ];
            
            $receive_id =intval(trim($_POST['receive_id']));    
            $receive_info =  $this->receive_service->get_info($receive_id,$additional);
            
            if($receive_info['receive_status'] == ReceiveStatus::ReceiveFinished){
                $this->order_service->rollback();
                showmessage('已经确认过收货了','null');
            }
            $order_info = $this->order_service->get_order_info(['order_id'=>$receive_info['order_id']]);
            $Orders = new \oms\Order($order_info);
            if(!$Orders->allow_to_confirm_received()){
                $this->order_service->rollback();
                showmessage('该订单不允许收货','null');
            }
            
            $data =[
                'order_id' =>intval($receive_info['order_id']),
                'order_no' =>$order_info['order_no'],
                'business_key' =>intval($receive_info['business_key']),
                'goods_id' =>intval($receive_info['goods_id']),
                'receive_id'=>$receive_id,
                'admin_id'=>intval($this->admin['id']),
            ];
            
            try{
                // 订单 观察者主题
                $OrderObservable = $Orders->get_observable();
                // 订单 观察者 状态流
                $FollowObserver = new oms\observer\FollowObserver( $OrderObservable );
                // 订单 观察者  日志
                $LogObserver = new oms\observer\LogObserver( $OrderObservable , "收货","平台确认收货");
                $LogObserver->set_operator($Operator);
            
                $b =$Orders->confirm_received($data);
                if(!$b){
                    $this->order_service->rollback();
                    showmessage('收货失败','null',0);
                }
                $this->order_service->commit();
                //确认收货发送短信
                \zuji\sms\SendSms::receive_confirmed([
                    'mobile' => $order_info['mobile'],
                    'orderNo' => $order_info['order_no'],
                    'realName' => $order_info['realname'],
                    'goodsName' => $order_info['goods_name'],
                ]);
                showmessage('操作成功','null',1);
            }catch (\Exception $exc){
                $this->order_service->rollback();
                Debug::error(Location::L_Order, '退款失败:'.$exc->getMessage(), $data);
                showmessage($exc->getMessage(),'null',0);
            }
        }
        
        $receive_id = intval(trim($_GET['receive_id']));
        if($receive_id < 1){
            showmessage('参数错误','null', 0);
        }
        $receive_info =  $this->receive_service->get_info($receive_id);
        if($receive_info['receive_status'] == ReceiveStatus::ReceiveFinished){
            showmessage('已经确认过收货了','null');
        }
        //判断订单是否有效
        $order_info = $this->order_service->get_order_info(['order_id'=>$receive_info['order_id']]);
        $Orders = new \oms\Order($order_info);
        if(!$Orders->allow_to_confirm_received()){
            showmessage('该订单不允许收货','null');
        }
        
	
	    $this->load->librarys('View')
	    ->assign('receive_id', $receive_id)
	    ->assign('bar_code', $receive_info['bar_code'])
	    ->assign('wuliu_no', $receive_info['wuliu_no'])
	    ->display('alert_shouhuo');
    }
    
    /**
     * 更改物流单号
     */
    public function edit_wuliu_no() {
        $this->receive_service = $this->load->service('order2/receive');

        $receive_id = intval($_GET['id']);
        $data['wuliu_no'] = $_GET['name'];
        $data['wuliu_channel_id']=1;
        $data['update_time'] = time();
        $receive_info =  $this->receive_service->get_info($receive_id);
        $this->receive_table = $this->load->table('order2/order2_receive');
        $result = $this->receive_table->where(['receive_id'=>$receive_id])->save($data);      
        if (!$result) {showmessage('修改失败','null',0);}
        	//生成日志开始
        	$operator = get_operator();
        	$log=[
        	    'order_no'=>$receive_info['order_no'],
        	    'action'=>"录入流单号",
        	    'operator_id'=>$operator['id'],
        	    'operator_name'=>$operator['username'],
        	    'operator_type'=>$operator['operator_type'],
        	    'msg'=>"流单号：".$data['wuliu_no'],
        	];
        	$add_log =$this->service_order_log->add($log);
        	if(!$add_log){
        	    showmessage('日志错误','null',0);
        	}
        	showmessage(lang('_operation_success_'),'null',1);
    }
    
    /**
     * 更改条码
     */
    public function edit_bar_code() {
        $this->receive_service = $this->load->service('order2/receive');

        $receive_id = intval($_GET['id']);
        $receive_info =  $this->receive_service->get_info($receive_id);
        $data['bar_code'] =$_GET['name'];
        $data['update_time'] = time();
        $this->receive_table = $this->load->table('order2/order2_receive');
        $result = $this->receive_table->where(['receive_id'=>$receive_id])->save($data);

        if (!$result) {showmessage('更新失败','null',0);}
    	//生成日志开始
    	$operator = get_operator();
    	$log=[
    	    'order_no'=>$receive_info['order_no'],
    	    'action'=>"录入条码",
    	    'operator_id'=>$operator['id'],
    	    'operator_name'=>$operator['username'],
    	    'operator_type'=>$operator['operator_type'],
    	    'msg'=>"条码：".$data['bar_code'],
    	];
    	$add_log =$this->service_order_log->add($log);
    	if(!$add_log){
    	    showmessage('日志错误','null',0);
    	}
	    showmessage(lang('_operation_success_'),'null',1);
    }

}