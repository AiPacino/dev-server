<?php
/**
 *        退货单
 */
use zuji\order\ReturnStatus;
use zuji\Business;
use zuji\debug\Location;
use zuji\Config;
use zuji;
use zuji\order\Reason;
use zuji\order\Order;
use zuji\order\returns\Returns;
use zuji\debug\Debug;
use zuji\order\receive\Receive;

hd_core::load_class('base', 'order2');

class return_control extends base_control
{

    /**
     * @var array 关键字搜索类型列表
     */
    protected $keywords_type_list = [
        'order_no' => '订单编号',
        'order_id' => '订单ID',
        'user_id' => '用户ID',
    ];
    protected $status_list = [
        '1' => '待审核',
        '2' => '未通过',
        '3' => '已通过',
    ];

    public function _initialize()
    {
        parent::_initialize();

        // $this->return_service = $this->load->service('order2/return');
        // $this->goods_service = $this->load->service('order2/goods');
        // $this->member_service = $this->load->service('member2/member');
        // $this->order_service = $this->load->service('order2/order');
        // $this->service_service = $this->load->service('order2/service');
        // $this->receive_service = $this->load->service('order2/receive');


        //权限判断
        $promission_arr = [];
        $promission_arr['check'] = $this->check_promission_operate('order2', 'return', 'check');
        $promission_arr['return_cancel'] = $this->check_promission_operate('order2', 'return', 'return_cancel');
        //$promission_arr['create_receive'] = $this->check_promission_operate('order2', 'return', 'create_receive');
        $this->promission_arr = $promission_arr;
    }

    /**
     * 退货单列表
     *
     */
    public function index()
    {
        $this->return_service = $this->load->service('order2/return');
        $this->order_service = $this->load->service('order2/order');
        $this->receive_service = $this->load->service('order2/receive');

        $where = [];
        if ($_GET['begin_time'] != '') {
            $where['begin_time'] = strtotime($_GET['begin_time']);
        }
        if ($_GET['end_time'] != '') {
            $where['end_time'] = strtotime($_GET['end_time']);
        }
        if (intval($_GET['business_key']) > 0) {
            $where['business_key'] = intval($_GET['business_key']);
        }
        if ($_GET['keywords'] != '') {
            if ($_GET['kw_type'] == 'order_id') {
                $where['order_id'] = $_GET['keywords'];
            } elseif ($_GET['kw_type'] == 'order_no') {
                $where['order_no'] = $_GET['keywords'];
            } elseif ($_GET['kw_type'] == 'user_id') {
                $where['user_id'] = $_GET['keywords'];
            }
        }
        if (isset($_GET['return_status']) && $_GET['return_status'] > 0) {
            $where['return_status'] = intval($_GET['return_status']);
        }
        $size = min(isset($_GET['limit']) && is_numeric($_GET['limit']) ? $_GET['limit'] : 20, 100);
        $count = $this->return_service->get_count($where);
        $pages = $this->admin_pages($count, $size);

        $additional = [
            'page' => intval($_GET['page']),
            'size' => intval($size),
            'orderby' => '',
        ];

        $additional['goods_info'] = true;
        $additional['address_info'] = true;
        // 查询退货申请单
        $return_list = $this->return_service->get_list($where, $additional);
        $order_ids = array_column($return_list, 'order_id');
        $order_ids = array_unique($order_ids);

        $order_list = $this->order_service->get_order_list(['order_id' => $order_ids], ['size' => count($order_ids)]);
        mixed_merge($return_list, $order_list, 'order_id', 'order_info');

        // 循环格式化
        foreach ($return_list as &$item) {
            if(!isset($item['order_info'])){continue;}
            $item['business_name'] = Business::getName($item["business_key"]);
            $item['create_time_show'] = date('Y-m-d H:i:s', $item['create_time']);
            $item['return_status_show'] = ReturnStatus::getStatusName($item['return_status']);
            if ($item['reason_id'] != 0) {
                $item['reason_name'] = Reason::$_ORDER_QUESTION[Reason::ORDER_RETURN][$item['reason_id']];
            } else {
                $item['reason_name'] = $item['reason_text'];
            }
            $item['goods_name'] = $item['order_info']['goods_name'];
            $item['mobile'] = $item['order_info']['mobile'];
            $item['order_status'] = $item['order_info']['order_status'];

            //$Order = Order::createOrder($item['order_info']);
            $Returns = Returns::createReturn($item);
            $Orders = new \oms\Order($item['order_info']);
            $receive_info = $this->receive_service->get_info_by_order_id($item['order_id']);
            $receive_cancel =true;
            if($receive_info){
                $Receive = Receive::createReceive($receive_info);
                $receive_cancel =$Receive->allow_cancel_receive();
            }
            //是否允许审核
            $item['allow_check'] = false;
            if ($this->promission_arr['check'] && $Returns->allow_to_check() && !$Orders->order_islock()) {
                $item['allow_check'] = true;
            }
            $item['return_cancel'] = false;
            if ($this->promission_arr['return_cancel'] && $Returns->cancel_return() && $receive_cancel && !$Orders->order_islock()) {
                $item['return_cancel'] = true;
            }
//             $item['create_receive'] = false;
//             if ($this->promission_arr['create_receive'] && $Returns->allow_create_receive() && $Order->is_open() && !$Order->is_receive()) {
//                 $item['create_receive'] = true;
//             }

        }
        $lists = array(
            'th' => array(
                'business_name' => array('length' => 10, 'title' => '业务类型'),
                'order_no' => array('title' => '订单编号', 'length' => 10),
                'mobile' => array('title' => '会员账号', 'length' => 10),
                'create_time_show' => array('title' => '申请时间', 'length' => 10),
                'reason_name' => array('title' => '申请理由', 'length' => 15),
                'reason' => array('title' => '退货描述', 'length' => 15),
                'return_status_show' => array('title' => '处理状态', 'length' => 10),
                'goods_name' => array('title' => '选购产品', 'length' => 10),
            ),
            'lists' => $return_list,
            'pages' => $pages,
        );
        // 订单阶段
        $status_list = array_merge(['0' => '全部'], ReturnStatus::getStatusList());
        $tab_list = [];
        foreach ($status_list as $k => $name) {
            if ($k == ReturnStatus::ReturnCreated) {
                continue;
            }
            $css = '';
            if ($_GET['return_status'] == $k) {
                $css = 'current';
            }
            $url = url('order2/return/index', array('return_status' => $k));
            $tab_list[] = '<a class="' . $css . '" href="' . $url . '">' . $name . '</a>';
        }


        $this->load->librarys('View')
            ->assign('tab_list', $tab_list)
            ->assign('keywords_type_list', $this->keywords_type_list)
            ->assign('lists', $lists)
            ->assign('pages', $pages)
            ->display('return_index');
    }

    /**
     *退货单详情
     */
    public function detail()
    {
        $this->return_service = $this->load->service('order2/return');
        // 是否内嵌
        $inner = boolval($_GET['inner']);
        //
        $return_id = intval(trim($_GET['return_id']));
        if ($return_id < 1) {
            showmessage(lang('_error_action_'), "null", 0);
//            echo_div("参数错误");
        }

        $return_info = $this->return_service->get_info($return_id);
        $arr = zuji\order\Reason::$_ORDER_QUESTION[zuji\order\Reason::ORDER_RETURN];
        $return_info['reason'] = $arr[$return_info['reason_id']];

        $this->load->librarys('View')
            ->assign('inner', $inner)
            ->assign('return_info', $return_info)
            ->display('return_detail');
    }

    /*
     * 点击审核
     */
    public function check(){
        $this->return_service = $this->load->service('order2/return');
        $this->order_service = $this->load->service('order2/order');
        
        if (checksubmit('dosubmit')) {
            $order_id = intval(trim($_POST['order_id']));
            $return_id = intval(trim($_POST['return_id']));
            $return_status = intval(trim($_POST['return_status']));
            $return_check_remark = trim($_POST['return_check_remark']);
            // 开启事务
            if (!$this->order_service->startTrans()) {
                showmessage('服务器繁忙', 'null', 0, '', 'json');
            }
            $options = ['lock' => true];
            // 查询退货申请单
            $return_info = $this->return_service->get_info($return_id, $options);
            $order_info = $this->order_service->get_order_info(['order_id' => $return_info['order_id']], $options);

            $Returns = Returns::createReturn($return_info);
            $Orders =new \oms\Order($order_info);
            if(!$Orders->allow_to_check_returns()){
                $this->order_service->rollback();
                showmessage("该订单不允许审核", "null");
            }
            if (!$Returns->allow_to_check()) {
                $this->order_service->rollback();
                showmessage("只有待审核状态才能进行审核操作", "null");
            }
            $business_key =intval($return_info['business_key']);
            if($return_status == ReturnStatus::ReturnHuanhuo){
                $business_key =Business::BUSINESS_HUANHUO;
                $shenhe = "用户换货";
            }elseif($return_status == ReturnStatus::ReturnAgreed){
                $shenhe ="同意";
            }else{
                $shenhe ="拒绝";
            }
            
            $admin = [
                'id' =>$this->admin['id'],
                'username' =>$this->admin['username'],
            ];
            $Operator = new oms\operator\Admin( $admin['id'], $admin['username'] );

            try{
                // 订单 观察者主题
                $OrderObservable = $Orders->get_observable();
                // 订单 观察者 状态流
                $FollowObserver = new oms\observer\FollowObserver( $OrderObservable );
                // 订单 观察者  日志
                $LogObserver = new oms\observer\LogObserver( $OrderObservable , "退货审核",$shenhe);
                $LogObserver->set_operator($Operator);
                if ($return_status == ReturnStatus::ReturnAgreed || $return_status == ReturnStatus::ReturnHuanhuo) {
                    $data = [
                        'return_id'=>$return_id,
                        'admin_id' =>intval($this->admin['id']),
                        'order_id' =>$order_id,
                        'return_check_remark'=>$return_check_remark,
                        'return_status'=>$return_status,
                        'business_key'=>$business_key,
                    ];
                    
                    $b =$Orders->agreed_returns($data);
                    if(!$b){           
                        $this->order_service->rollback();
                        showmessage('审核失败','null',0);
                    }

                    $this->return_address_service = $this->load->service('order2/return_address');
                    $address = $this->return_address_service->get_info(intval($return_info['address_id']));
                    //同意退换货发送短信
                    \zuji\sms\SendSms::agree_return([
                        'mobile' => $order_info['mobile'],
                        'orderNo' => $order_info['order_no'],
                        'realName' => $order_info['realname'],
                        'goodsName' => $order_info['goods_name'],
                        'shoujianrenName'=>$address['name'],
                        'returnAddress'=>$address['address'],
                    ]);

                }else{
                    $data =[
                        'order_id' =>$order_id, 
                        'admin_id' =>intval($this->admin['id']),
                        'return_check_remark' =>$return_check_remark,
                        'return_id'=>$return_id,
                    ];
                    $b =$Orders->denied_returns($data);
                    if(!$b){
                        $this->order_service->rollback();
                        showmessage('审核拒绝失败'.get_error(),'null',0);
                    }
                    //拒绝退货发送短信
                    \zuji\sms\SendSms::adenied_return([
                        'mobile' => $order_info['mobile'],
                        'orderNo' => $order_info['order_no'],
                        'realName' => $order_info['realname'],
                        'goodsName' => $order_info['goods_name'],
                    ]);
                }
                $this->order_service->commit();

                showmessage('操作成功','null',1);
            }catch (\Exception $exc){
                $this->order_service->rollback();
                Debug::error(Location::L_Return, '退货审核失败:'.$exc->getMessage(), $data);
                showmessage($exc->getMessage(),'null',0);
            }
        } else {
            
            $return_id = intval(trim($_GET['return_id']));
            // 查询退货申请单
            $return_info = $this->return_service->get_info($return_id);
            $Returns = Returns::createReturn($return_info);
            if (!$Returns->allow_to_check()) {
                showmessage("只有待审核状态才能进行审核操作", "null");
            }
            $this->load->librarys('View')
                ->assign('return_info', $return_info)
                ->assign('return_id', $return_id)
                ->display('alert_shenhe');
        }
    }

    /*
     * 点击用户取消退货
     */
    public function return_cancel(){
        $this->return_service = $this->load->service('order2/return');
        $this->order_service = $this->load->service('order2/order');
        $this->receive_service = $this->load->service('order2/receive');

        if (checksubmit('dosubmit')) {
            $order_id = intval(trim($_POST['order_id']));
            $return_id = intval(trim($_POST['return_id']));
            // 开启事务
            if (!$this->order_service->startTrans()) {
                showmessage('服务器繁忙', 'null', 0, '', 'json');
            }
            $options = ['lock' => true];

            $return_info = $this->return_service->get_info($return_id, $options);
            $order_info = $this->order_service->get_order_info(['order_id' => $order_id], $options);
            $receive_info = $this->receive_service->get_info_by_order_id($order_id, $options);
            $Orders = new \oms\Order($order_info);
            $Returns = Returns::createReturn($return_info);
            if (!$Returns->cancel_return()) {
                $this->order_service->rollback();
                showmessage("该退货已被取消", "null", 0);
            }
            if(!$Orders->allow_to_cancel_returns()){
                $this->order_service->rollback();
                showmessage("该订单不允许取消", "null", 0);
            }
            if ($receive_info) {
                $Receive = Receive::createReceive($receive_info);
                if (!$Receive->allow_cancel_receive()) {
                    $this->order_service->rollback();
                    showmessage('无法取消退货', 'null');
                }
            }
            $admin = [
                'id' =>$this->admin['id'],
                'username' =>$this->admin['username'],
            ];
            $Operator = new oms\operator\Admin( $admin['id'], $admin['username']);
            
            $data = [
                'return_id'=>$return_id,
                'order_id'=>$order_id, 
                'receive_id'=>$order_info['receive_id'],  
            ];
            
            try{
                // 订单 观察者主题
                $OrderObservable = $Orders->get_observable();
                // 订单 观察者 状态流
                $FollowObserver = new oms\observer\FollowObserver( $OrderObservable );
                // 订单 观察者  日志
                $LogObserver = new oms\observer\LogObserver( $OrderObservable , "取消退货","取消退货");
                $LogObserver->set_operator($Operator);            
                $b =$Orders->cancel_returns($data);
                if(!$b){
                    $this->order_service->rollback();
                    showmessage('申请退款失败','null',0);
                }
                $this->order_service->commit();
                showmessage('操作成功','null',1);
            }catch (\Exception $exc){
                $this->order_service->rollback();
                Debug::error(Location::L_Return, '取消退货失败:'.$exc->getMessage(), $data);
                showmessage('取消退货失败','null',0);
            }
        } else {
            $order_id = intval(trim($_GET['order_id']));
            $return_id = intval(trim($_GET['return_id']));
            if ($order_id < 1)
                showmessage('order_id错误:' . $order_id, 'null');
            if ($return_id < 1)
                showmessage('return_id错误:' . $return_id, 'null');
            
            $return_info = $this->return_service->get_info($return_id);
            $order_info = $this->order_service->get_order_info(['order_id' => $order_id]);
            $receive_info = $this->receive_service->get_info_by_order_id($order_id);
            $Orders = new \oms\Order($order_info);
            $Returns = Returns::createReturn($return_info);
            if (!$Returns->cancel_return()) {
                showmessage("该退货已被取消", "null", 0);
            }
            if(!$Orders->allow_to_cancel_returns()){
                showmessage("该订单不允许取消", "null", 0);
            }
            if ($receive_info) {
                $Receive = Receive::createReceive($receive_info);
                if (!$Receive->allow_cancel_receive()) {
                    showmessage('无法取消退货', 'null');
                }
            }
            $this->load->librarys('View')
                ->assign('order_id', $order_id)
                ->assign('return_id', $return_id)
                ->display('alert_return_cancel');
        }
    }

    /**
     * 退货单列表导出【参照后台页面：订单->退货申请列表（控制器：order2->return->index）】
     */
    public function return_order_export()
    {
        // 不限制超时时间
        set_time_limit(0);
        // 内存很大
        ini_set('memory_limit', 200*1024*1024);
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename='.'退货单列表'.time().'-'.rand(1000, 9999).'.csv');
        header('Cache-Control: max-age=0');
        $handle = fopen('php://output', 'a');
        $header_data = array('业务类型','订单编号','会员账号','申请时间','申请理由','退货描述','处理状态','选购产品',);
        //输出头部数据
        $this->export_csv_wirter_row($handle, $header_data);
		
		//-+--------------------------------------------------------------------
		// | 查找列表数据
		//-+--------------------------------------------------------------------
        $this->return_service = $this->load->service('order2/return');
        $this->order_service = $this->load->service('order2/order');
        $this->receive_service = $this->load->service('order2/receive');

        $where = [];
        if ($_GET['begin_time'] != '') {
            $where['begin_time'] = strtotime($_GET['begin_time']);
        }
        if ($_GET['end_time'] != '') {
            $where['end_time'] = strtotime($_GET['end_time']);
        }
        $return_count = $this->return_service->get_count($where);
		$return_list = [];//退货申请列表初始化

        $additional['goods_info'] = true;
        $additional['address_info'] = true;
        $additional['page'] = 1;
        $additional['size'] = 100;
		while ($return_count>0){
			// 查询退货申请单
			$return_list = $this->return_service->get_list($where, $additional);
			$order_ids = array_column($return_list, 'order_id');
			$order_ids = array_unique($order_ids);

			$order_list = $this->order_service->get_order_list(['order_id' => $order_ids], ['size' => count($order_ids)]);
			mixed_merge($return_list, $order_list, 'order_id', 'order_info');

			// 循环格式化
			foreach ($return_list as &$item) {
				$item['business_name'] = Business::getName($item["business_key"]);
				$item['create_time_show'] = date('Y-m-d H:i:s', $item['create_time']);
				$item['return_status_show'] = ReturnStatus::getStatusName($item['return_status']);
				if ($item['reason_id'] != 0) {
					$item['reason_name'] = Reason::$_ORDER_QUESTION[Reason::ORDER_RETURN][$item['reason_id']];
				} else {
					$item['reason_name'] = $item['reason_text'];
				}
				$item['goods_name'] = $item['order_info']['goods_name'];
				$item['mobile'] = $item['order_info']['mobile'];
				$item['order_status'] = $item['order_info']['order_status'];

                $body_data = [
                    "\t" . $item['business_name'],//业务类型
                    "\t" . $item['order_no'],//订单编号
                    "\t" . $item['mobile'],//会员账号
                    "\t" . $item['create_time_show'],//申请时间
                    "\t" . $item['reason_name'],//申请理由
                    "\t" . $item['reason'],//退货描述
                    "\t" . $item['return_status_show'],//处理状态
                    "\t" . $item['goods_name'],//选购产品
                ];
                $this->export_csv_wirter_row($handle, $body_data);
                unset($body_data);
			}
            $additional['page'] = $additional['page'] + 1;
            $return_count = $return_count - $additional['size'];
		}
    }
    private function export_csv_wirter_row( $handle, $row ){
        foreach ($row as $key => $value) {
            //$row[$key] = iconv('utf-8', 'gbk', $value);
            $row[$key] = mb_convert_encoding($value,'GBK');
        }
        fputcsv($handle, $row);
    }
	
    /*
     * 点击填写物流单号
     */

//     public function tui_wuliu()
//     {
//         $id = trim($_GET['id']);
//         if ((int)$id < 1)
//             echo_div("参数错误");
//         if (checksubmit('dosubmit')) {
//             echo_json(1, lang('_operation_success_'));
//         } else {
//             $sqlmap = $deliverys = array();
//             $sqlmap['enabled'] = 1;
//             $deliverys = $this->load->service('order/delivery')->getField('id,name', $sqlmap);
//             $this->load->librarys('View')->assign('deliverys', $deliverys)->display('alert_wuliu');
//         }
//     }


}
