<?php

use zuji\order\ServiceStatus;
use zuji\Business;
use zuji\Config;
use zuji\debug\Location;
use zuji\order\OrderStatus;
use oms\Order;
use zuji\debug\Debug;
/**
 * 服务单控制器
 *@author wuhaiyan<wuhaiyan@huishoubao.com.cn>
 */
// 加载 goods 模块中的 init_control
hd_core::load_class('base', 'order2');
class service_control extends base_control {

	/**
	 * @var array 关键字搜索类型列表
	 */
	protected $keywords_type_list = [
	    'order_no'=>'订单编号',
	    'mobile'=>'用户账号',
	];

	public function _initialize() {
	    parent::_initialize();

	    $this->service_service = $this->load->service('order2/service');
	    $this->order_service = $this->load->service('order2/order');
	}
	
    public function index() {     
        // 查询条件
        $where = [];
        //按照配置文件显示
        /* $daytime=Config::Order_To_close_day;
        $settime=time()+$daytime*86400; */
        

/*         if($_GET['begin_time']!='' ){
            $where['begin_time'] = strtotime($_GET['begin_time']);
        }
        if( $_GET['end_time']!='' ){
            $endtime=strtotime($_GET['end_time']);
            if($endtime>$settime){
                $where['end_time'] = $settime;
            }else if($endtime<time()){
                $where['end_time'] =time();
            }else{
                $where['end_time'] = $endtime;
            }
        }else{
            $where['end_time'] = $settime;
        }
        
          */
        if($_GET['begin_time']!='' ){
            $where['begin_time'] = strtotime($_GET['begin_time']);
        }
        if( $_GET['end_time']!='' ){          
            $where['end_time'] = strtotime($_GET['end_time']);
        }else{
            $where['end_time'] =time();
        }
       
        
        if(isset($_GET['service_status']) && $_GET['service_status']>0){
            $where['service_status'] = intval($_GET['service_status']);
            if($where['service_status']==ServiceStatus::ServiceTimeout){
                $where['end_time'] = time();   
                unset($where['service_status']);
            }
        }
        
        if(intval($_GET['business_key'])>0 ){
            $where['business_key'] = intval($_GET['business_key']);
        }

        if($_GET['keywords']!=''){
    	    if($_GET['kw_type']=='order_no'){
    		$where['order_no'] = $_GET['keywords'];
    	    }
    	    elseif($_GET['kw_type']=='mobile'){
    		$where['mobile'] = $_GET['keywords'];
    	    }
        }
        
        
        $size = (isset($_GET['limit']) && is_numeric($_GET['limit'])) ? $_GET['limit'] : 20;
        $count  = $this->service_service->get_count($where);
        $pages  = $this->admin_pages($count,$size);
        $additional =[
            'page'=>intval($_GET['page']),
            'size'=>intval($size),
            'orderby'=>'',
        ];
        $service_list=[];
        if($count > 0){
            $service_list = $this->service_service->get_list($where,$additional);
            $order_ids = array_column($service_list, 'order_id');
            $order_ids = array_unique($order_ids);
            $order_list = $this->order_service->get_order_list(['order_id'=>$order_ids],['size'=>count($order_ids)]);
            mixed_merge($service_list, $order_list, 'order_id','order_info');
            //var_dump($service_list);die;
            foreach($service_list as $k=>&$item)
            {
                $item['business_name']  = Business::getName($item["business_key"]);
                $item['status'] = ServiceStatus::getStatusName($item["service_status"]);
                $item['left'] =floor(($item['end_time']-time())/86400);
                if($item["service_status"] ==ServiceStatus::ServiceOpen){
                    if(time()>$item['end_time']){
                        $item['status'] = "服务超时";
                    }
                }
                $item['order_status'] =$item['order_info']['order_status'];
            
            }
            
        }
        $lists = array(
            'th' => array(
                'business_name' => array('length' => 10,'title' => '业务类型'),
                'order_no' => array('title' => '订单编号','length' => 10),
                'mobile' => array('length' => 10,'title' => '会员账号'),
                'left' => array('length' => 10,'title' => '剩余天数'),
                'begin_time' => array('length' => 10,'title' => '服务开始时间','style'=>'date'),
                'end_time' => array('length' => 10,'title' => '服务结束时间','style'=>'date'),
                'status' => array('length' => 10,'title' => '服务状态'),
                'create_time' => array('length' => 10,'title' => '创建时间','style'=>'date'),
            ),
            'lists' => $service_list,
            'pages' => $pages,
        );
        
        
        $status_list = array_merge(['0'=>'全部'],ServiceStatus::getStatusList());
	    $tab_list = [];
	    foreach( $status_list as $k=>$name ){
		$css = '';
		if ($_GET['service_status'] == $k){
		    $css = 'current';
		}
		$url = url('order2/service/index',array('service_status'=>$k));
		$tab_list[] = '<a class="'.$css.'" href="'.$url.'">'.$name.'</a>';
	    }

	    //权限判断
        $promission_arr = [];
        $promission_arr['close'] = $this->check_promission_operate('order2', 'service', 'close');
        $promission_arr['sendsms'] = $this->check_promission_operate('order2', 'service', 'sendsms');
        $promission_arr['order_detail'] = $this->check_promission_operate('order2', 'order', 'detail', 'service');

//	    var_dump($lists);die;
	    $this->load->librarys('View')
            ->assign('tab_list',$tab_list)
            ->assign('keywords_type_list',$this->keywords_type_list)
            ->assign('promission_arr', $promission_arr)
            ->assign('lists',$lists)->assign('pages',$pages)->display('service_index');
    }
    
    /**
     *服务详情
     */
    public function detail(){
        // 是否内嵌
        $inner = boolval($_GET['inner']);
        $service_id = intval(trim($_GET['service_id']));
        if ($service_id < 1){
            showmessage("参数错误", "null", 0);
        }
        $service_info = $this->service_service->get_info($service_id);
        $this->load->librarys('View')
        ->assign('inner', $inner)
        ->assign('service_info', $service_info)
        ->display('service_detail');
    }
    /*
     * 点击关闭
     */
    public function close() {

        if (checksubmit('dosubmit')) {
            
            // 当前 操作员
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
            
            $service_id =intval(trim($_POST['service_id']));
            $service_info =$this->service_service->get_info($service_id,$additional);
            $order_info = $this->order_service->get_order_info(['order_id'=>$service_info['order_id']],$additional);
            $Orders = new Order($order_info);
            if(!$Orders->allow_to_close_service()){
                $this->order_service->rollback();
                showmessage("不允许关闭该服务", "null", 0);
            }
            $remark =trim($_POST['remark']);
            $data =[
                'remark'=>$remark,
            ];
            
            try{
                if($order_info['payment_type_id'] == Config::MiniAlipay){
                    $this->zhima_order_confrimed_table =$this->load->table('order2/zhima_order_confirmed');
                    //获取订单的芝麻订单编号
                    $zhima_order_info = $this->zhima_order_confrimed_table->where(['order_no'=>$order_info['order_no']])->find($additional);
                    if(!$zhima_order_info){
                        $this->order_service->rollback();
                        showmessage('该订单没有芝麻订单号！','null',0);
                    }
                    $zhima = new \zhima\Withhold();
                    $b =$zhima->OrderClose([
                        'out_order_no'=>$order_info['order_no'],//商户端订单号
                        'zm_order_no'=>$zhima_order_info['zm_order_no'],//芝麻订单号
                        'remark'=>$remark,//订单操作说明
                    ]);
                    $this->order_service->commit();
                    if($b === false){
                        Debug::error(Location::L_Order,"小程序订单关闭",[
                            'out_order_no'=>$order_info['order_no'],//商户端订单号
                            'zm_order_no'=>$zhima_order_info['zm_order_no'],//芝麻订单号
                            'remark'=>$_POST['remark'],//订单操作说明
                        ]);
                        showmessage('操作失败','null',0);
                    }

                    showmessage('操作成功','null',1);

                }
                // 订单 观察者主题
                $OrderObservable = $Orders->get_observable();
                // 订单 观察者 状态流
                $FollowObserver = new oms\observer\FollowObserver( $OrderObservable );
                // 订单 观察者  日志
                $LogObserver = new oms\observer\LogObserver( $OrderObservable , "关闭服务"," 备注：".$remark);
                $LogObserver->set_operator($Operator);
            
                $b =$Orders->close_service($data);
                if(!$b){
                    $this->order_service->rollback();
                    showmessage('开启失败','null',0);
                }
                $this->order_service->commit();
                 
                showmessage('操作成功','null',1);
            }catch (\Exception $exc){
                $this->order_service->rollback();
                showmessage($exc->getMessage(),'null',0);
            }
        }else{
            
            $service_id = intval(trim($_GET['service_id']));
            if( $service_id < 1){showmessage("参数错误", "null", 0);}
            $service_info =$this->service_service->get_info($service_id);
            $order_info = $this->order_service->get_order_info(['order_id'=>$service_info['order_id']]);
            $Orders = new Order($order_info);
            if(!$Orders->allow_to_close_service()){
                showmessage("不允许关闭该服务", "null", 0);
            }

            
            $tishi="是否关闭该服务?";
            $title="关闭服务提醒";
            $url='close';
            if($order_info['payment_type_id'] == Config::MiniAlipay){
                $tishi.="该订单为小程序支付，如果有押金将进行关闭订单退款操作!";
                $title="取消服务提醒";
            }
            $this->load->librarys('View')
            ->assign('service_id', $service_id)
            ->assign('tishi', $tishi)
            ->assign('title', $title)
            ->assign('url', $url)
            ->display('alert_service');
        }
    }
    
    /*
     * 点击开启
     */
    public function open() {
        
        if (checksubmit('dosubmit')) {
            // 当前 操作员
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
    
            $service_id =intval(trim($_POST['service_id']));  
            $service_info =$this->service_service->get_info($service_id,$additional);
            $order_info = $this->order_service->get_order_info(['order_id'=>$service_info['order_id']],$additional);
            $Orders = new Order($order_info);
            if(!$Orders->allow_to_open_service()){
                $this->order_service->rollback();
                showmessage("不允许开启该服务", "null", 0);
            }
            $remark =trim($_POST['remark']);
            $data =[
                'remark'=>$remark,
            ];
            
            try{
                // 订单 观察者主题
                $OrderObservable = $Orders->get_observable();
                // 订单 观察者 状态流
                $FollowObserver = new oms\observer\FollowObserver( $OrderObservable );
                // 订单 观察者  日志
                $LogObserver = new oms\observer\LogObserver( $OrderObservable , "开启服务"," 备注：".$remark);
                $LogObserver->set_operator($Operator);
            
                $b =$Orders->open_service($data);
                if(!$b){
                    $this->order_service->rollback();
                    showmessage('开启失败','null',0);
                }
                $this->order_service->commit();
     
                showmessage('操作成功','null',1);
            }catch (\Exception $exc){
                $this->order_service->rollback();
                showmessage($exc->getMessage(),'null',0);
            }
        }else{
            
            $service_id = intval(trim($_GET['service_id']));
            if( $service_id < 1){showmessage("参数错误", "null", 0);}
            $service_info =$this->service_service->get_info($service_id);
            $order_info = $this->order_service->get_order_info(['order_id'=>$service_info['order_id']]);
            $Orders = new Order($order_info);
            if(!$Orders->allow_to_open_service()){
                showmessage("不允许开启该服务", "null", 0);
            }
            
            $tishi="是否开启该服务?";
            $title="开启服务提醒";
            $url='open';
            $this->load->librarys('View')
            ->assign('service_id', $service_id)
            ->assign('tishi', $tishi)
            ->assign('title', $title)
            ->assign('url', $url)
            ->display('alert_service');
        }
    }
    /*
     * 服务到期提醒短信
     */
    public function sendsms() {
    
        $service_id = intval(trim($_GET['service_id']));
        
        if( $service_id < 1)
            showmessage("参数错误", "null", 0);
        
        $service =$this->service_service->get_info($service_id);
        $order_info = $this->order_service->get_order_info(['order_id'=>$service['order_id']]);
        if($order_info['order_status']!=OrderStatus::OrderCreated){
             showmessage("订单未开启", "null", 0);
        }

        if (checksubmit('dosubmit')) {
            //发送短信。
            $result = ['auth_token' => $this->auth_token,];
            $sms = new \zuji\sms\HsbSms();
            $b = $sms->send_sm($order_info['mobile'], 'SMS_113461439', [
                'orderNo' => $order_info['order_no'],
                'serviceTel' => Config::Customer_Service_Phone,
            ],$order_info['order_no']);
            if (!$b) {
                 showmessage("发送短信失败", "null", 0);
            }
            //生成日志开始
            $operator = get_operator();
            $log = [
                'order_no' => $service['order_no'],
                'action' => "服务到期提醒",
                'operator_id' => $operator['id'],
                'operator_name' => $operator['username'],
                'operator_type' => $operator['operator_type'],
                'msg' => "发送短信成功",
            ];
            $add_log = $this->service_order_log->add($log);
            if (!$add_log) {
                showmessage("插入日志失败", "null", 0);
            }
             showmessage("发送成功", "null", 1);
        }else{
            $this->load->librarys('View')->assign('service_id', $service_id)->display('alert_service_remind');
        }
    }


}