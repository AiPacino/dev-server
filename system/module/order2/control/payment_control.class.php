<?php

/**
 * 支付单
 * 1）支付单列表（支持搜索条件）（支持按支付单状态查询：待支付；已支付；支付超时；支付失败；部分支付（异常））
 * 2）支付单详情
 */

use zuji\Business;
use zuji\order\PaymentStatus;
use zuji\payment\Payment;
use zuji\order\Order;
use zuji\order\OrderStatus;
use zuji\debug\Location;
use zuji\email\EmailConfig;
use zuji\debug\Debug;
use zuji\payment\Instalment;

hd_core::load_class('base', 'order2');

class payment_control extends base_control
{

    /**
     * @var array 关键字搜索类型列表
     */
    protected $keywords_type_list = [
        'order_no' => '订单编号',
        'trade_no' => '交易码',
    ];

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 支付订单列表
     */
    public function index()
    {   
        $this->payment_service = $this->load->service('order2/payment');
        $this->order_service = $this->load->service('order2/order');
        $where = array();
        // 查询条件
        if (isset($_GET['payment_status'])) {
            $where['payment_status'] = intval($_GET['payment_status']);
            if ($where['payment_status'] == 0) {
                unset($where['payment_status']);
            }
        }

        if (isset($_GET['apply_status'])) {
            $where['apply_status'] = intval($_GET['apply_status']);
            if ($where['apply_status'] == 0) {
                unset($where['apply_status']);
            }
        }


        if (isset($_GET['time_type'])) {
            $where['time_type'] = $_GET['time_type'];
            if ($_GET['begin_time'] != '') {
                $where['begin_time'] = strtotime($_GET['begin_time']);
            }
            if ($_GET['end_time'] != '') {
                $where['end_time'] = strtotime($_GET['end_time']);
            }
        }
        if (intval($_GET['business_key']) > 0) {
            $where['business_key'] = intval($_GET['business_key']);
        }
        if (intval($_GET['apply_status']) > 0) {
            $where['apply_status'] = intval($_GET['apply_status']);
        }
        if ($_GET['keywords'] != '') {
            if ($_GET['kw_type'] == 'trade_no') {
                $where['trade_no'] = $_GET['keywords'];
            } else {
                $where['order_no'] = $_GET['keywords'];
            }
        }
        
        //权限判断
        $promission_arr = [];
        $promission_arr['check'] = $this->check_promission_operate('order2', 'payment', 'check');
        $promission_arr['delivery_create'] = $this->check_promission_operate('order2', 'delivery', 'create');

        $size = (isset($_GET['limit']) && is_numeric($_GET['limit'])) ? $_GET['limit'] : 20;
        $count = $this->payment_service->get_count($where);
        $pages = $this->admin_pages($count, $size);
        $additional = [
            'page' => $_GET['page'],
            'size' => $size,
            'orderby' => '',
        ];
            $payment_list = $this->payment_service->get_list($where, $additional);
            $order_ids = array_column($payment_list, 'order_id');
            $order_list = $this->order_service->get_order_list(['order_id' => $order_ids], ['size' => count($order_ids)]);
            mixed_merge($payment_list, $order_list, 'order_id', 'order_info');
            foreach ($payment_list as $k => &$item) {

                // 支付单数据格式化
                $item['business_name'] = Business::getName($item["business_key"]);
                $item['payment_channel'] = Payment::getChannelName($item['payment_channel_id']);
                if ($item['order_info']['order_status'] == \zuji\order\OrderStatus::OrderCanceled) {
                    // 已经取消，显示订单本身状态
                    $item['payment_status_show'] = $item['order_info']['order_status_show'];
                } else {
                    $item['payment_status_show'] = PaymentStatus::getStatusName($item["payment_status"]);
                }
                
                $item['amount_show'] = Order::priceFormat($item['amount']) / 100;
                $item['payment_amount'] = Order::priceFormat($item['payment_amount']) / 100;
                $item['create_time_show'] = date('Y-m-d H:i:s', $item['create_time']);
                $item['apply_status_show'] = PaymentStatus::getApplyName($item['apply_status']);

                if ($item['payment_status'] >= PaymentStatus::PaymentSuccessful) {
                    $item['payment_amount_show'] = Order::priceFormat($item['payment_amount']);
                    $item['payment_time_show'] = date('Y-m-d H:i:s', $item['payment_time']);
                } else {
                    $item['payment_time_show'] = '--';
                    $item['payment_amount_show'] = '--';
                }
                
                $item['create_delivery'] =false;
                if($item['order_info']['delivery_id']==0 
                    && $item['payment_status'] == PaymentStatus::PaymentSuccessful 
                    && $promission_arr['delivery_create'] 
                    && $item['order_info']['refund_id']==0
                    && $item['order_info']['order_status']== OrderStatus::OrderCreated){
                    $item['create_delivery'] =true;
                }


                $item['order_status'] = $item['order_info']['order_status'];

            }

            $lists = array(
                'th' => array(
                    'business_name' => array('length' => 10, 'title' => '业务类型'),
                    'order_no' => array('title' => '订单编号', 'length' => 10),
                    'trade_no' => array('length' => 10, 'title' => '交易号'),
                    'payment_channel' => array('length' => 10, 'title' => '支付渠道'),
                    'amount_show' => array('length' => 7, 'title' => '应付金额'),
                    'payment_amount_show' => array('length' => 8, 'title' => '实付金额'),
                    'create_time_show' => array('length' => 10, 'title' => '创建时间'),
                    'payment_time_show' => array('length' => 10, 'title' => '交易时间'),
                    'payment_status_show' => array('length' => 10, 'title' => '支付状态'),
                ),
                'lists' => $payment_list,
                'pages' => $pages,
            );
            // 订单状态
            $status_list = array_merge(['0' => '全部'], PaymentStatus::getStatusList());
            $tab_list = [];
            foreach ($status_list as $k => $name) {
                if ($k == PaymentStatus::PaymentCreated) {
                    continue;
                }
                $css = '';
                if ($_GET['payment_status'] == $k) {
                    $css = 'current';
                }
                $url = url('order2/payment/index', array('payment_status' => $k));
                $tab_list[] = '<a class="' . $css . '" href="' . $url . '">' . $name . '</a>';
            }
            //退款状态
            $apply_list = PaymentStatus::getApplyList();
            foreach ($apply_list as $k => $name) {
                if ($k == PaymentStatus::PaymentApplyInvalid) {
                    continue;
                }
                $css = '';
                if ($_GET['apply_status'] == $k) {
                    $css = 'current';
                }
                $url = url('order2/payment/index', array('apply_status' => $k));
                $tab_list[] = '<a class="' . $css . '" href="' . $url . '">' . $name . '</a>';
            }



            $this->load->librarys('View')
                ->assign('tab_list', $tab_list)
                ->assign('apply_status', array_merge(['0' => '全部'], PaymentStatus::getapplyList()))
                ->assign('pay_channel_list', $this->pay_channel_list)
                ->assign('keywords_type_list', $this->keywords_type_list)
                ->assign('promission_arr', $promission_arr)
                ->assign('lists', $lists)->assign('pages', $pages)->display('payment_index');


    }

    /**
     * 支付单详情。
     */
    public function detail()
    {
        $this->payment_service = $this->load->service('order2/payment');
        // 是否内嵌
        $inner = boolval($_GET['inner']);
        //
        $payment_id = intval(trim($_GET['payment_id']));
        if ($payment_id < 1) {
            echo_div("参数错误");
//            showmessage(lang('_error_action_'), "", 0);
        }
        $payment_info = $this->payment_service->get_info($payment_id);

        $this->trade_service = $this->load->service('payment/payment_trade');
        $trade_info = $this->trade_service->get_info_by_trade_no($payment_info['trade_no']);

        $this->load->librarys('View')
            ->assign('inner', $inner)
            ->assign('payment_info', $payment_info)
            ->assign('trade_info', $trade_info)
            ->display('payment_detail');
    }


    /**
     * 支付单详情。
     */
    public function instalment_detail()
    {
        $this->instalment_service = $this->load->service('order2/instalment');
        // 是否内嵌
        $inner = boolval($_GET['inner']);

        $order_id = intval($_GET['order_id']);

        if ($order_id < 1) {
            echo_div("参数错误");
        }

        $instalment_list = $this->instalment_service->get_list(['order_id'=>$order_id]);

        if($instalment_list){
            foreach($instalment_list as &$item){
                $item['status_show'] = Instalment::getStatusName($item['status']);
				$item['amount'] = Order::priceFormat($item['amount']/100);
				$item['payment_time_show'] = $item['payment_time']>0 ? date("Y-m-d H:i:s",$item['payment_time']): '--';
				$item['update_time_show'] = $item['update_time']>0 ? date("Y-m-d H:i:s",$item['update_time']): '--';
            }
        }
        $this->load->librarys('View')
            ->assign('inner', $inner)
            ->assign('instalment_list', $instalment_list)
            ->display('instalment_list');
    }

    /*
     * 点击审核
     */
    public function check()
    {
        $this->payment_service = $this->load->service('order2/payment');
        $this->refund_service = $this->load->service('order2/refund');
        $this->order_service = $this->load->service('order2/order');
        $this->debug_service = $this->load->service('debug/debug');

        $payment_id = intval(trim($_GET['payment_id']));
        if ($payment_id < 1) {
            echo_div("参数错误");
//            showmessage(lang('_error_action_'), "", 0);
        }
        $payment_info = $this->payment_service->get_info($payment_id);
        //判断订单是否有效
        $order_info = $this->order_service->get_order_info(['order_id'=>$payment_info['order_id']]);
        if ($order_info['order_status'] != OrderStatus::OrderCreated) {
            echo_div("订单未生效");
        }

        if (checksubmit('dosubmit')) {
            $payment_id = intval(trim($_POST['payment_id']));
            $apply_status = intval(trim($_POST['apply_status']));
            $admin_remark = trim($_POST['admin_remark']);
            if (isset($this->admin['id']) && $this->admin['id'] > 0) {
                $admin_id = intval($this->admin['id']);
            } else {
                $admin_id = 0;
            }
            $data = [
                'admin_id' => $admin_id,
                'admin_remark' => $admin_remark,
            ];

            $refund_data = [
                'order_id' => intval($payment_info['order_id']),
                'order_no' => $payment_info['order_no'],
                'payment_amount' => intval($payment_info['payment_amount']),
                'user_id' => intval($order_info['user_id']),
                'mobile' => $order_info['mobile'],
                'goods_id' => intval($order_info['goods_id']),
                'payment_id' => $payment_id,
                'business_key' => intval($order_info['business_key']),
                'payment_channel_id' => intval($payment_info['payment_channel_id']),
            ];
            if ($apply_status == PaymentStatus::PaymentApplySuccessful) {
                $return_apply = $this->payment_service->payment_apply_successful($payment_id, $data);
                if (!$return_apply) {
                    $debug = $this->debug_service->create([
                        'location_id' => Location::L_Receive,
                        'subject' => '审核失败' . get_error(),
                        'data' => '支付单ID->' . $payment_id . '或 数据->' . json_encode($data) . ' 错误',
                    ]);
                    echo_div("审批失败");
                } else {
                    $result = $this->refund_service->create($refund_data);
                    if (!$result) {
                        $debug = $this->debug_service->create([
                            'location_id' => Location::L_Receive,
                            'subject' => '生成退款单失败' . get_error(),
                            'data' => $refund_data,
                        ]);
                    }
                    
                    //发送邮件 -----begin
                    $data =[
                        'subject'=>'申请退款',
                        'body'=>'订单编号：'.$order_info['order_no']." 需要向用户退款，请处理。",
                        'address'=>[
                            ['address' => EmailConfig::Finance_Username]
                        ],
                    ];
                    $send =EmailConfig::system_send_email($data);
                    if(!$send){
                        Debug::error(Location::L_Delivery, "发送邮件失败", $data);
                    }
                    
                    //发送邮件------end
                }
            } else {
                $result = $this->payment_service->payment_apply_failed($payment_id, $admin_id, $admin_remark);
                if (!$result) {
                    $debug = $this->debug_service->create([
                        'location_id' => Location::L_Receive,
                        'subject' => '审核拒绝失败' . get_error(),
                        'data' => '支付单ID->' . $payment_id . '或 管理员id->' . $admin_id . '或 备注->' . $admin_remark . ' 错误',
                    ]);
                }
            }

            //生成日志开始
            $operator = get_operator();
            $log = [
                'order_no' => $payment_info['order_no'],
                'action' => "支付单审核",
                'operator_id' => $operator['id'],
                'operator_name' => $operator['username'],
                'operator_type' => $operator['operator_type'],
                'msg' => "审核成功",
            ];
            $add_log = $this->service_order_log->add($log);
            if (!$add_log) {
                echo_div("插入日志失败");
            }
            //生成日志结束
            echo_json(1,lang('_operation_success_'));
            //生成操作日志
            //获取用户ID
            $userId = ADMIN_ID;
            //获取操作人的IP：
            $user_IP = ($_SERVER["HTTP_VIA"]) ? $_SERVER["HTTP_X_FORWARDED_FOR"] : $_SERVER["REMOTE_ADDR"];
            //操作功能ID
            $optionId = \zuji\debug\Opreation::ORDER_PAYMENT_CHECK;
            //操作备注
            $remark = '根据支付单ID' . $payment_id . '审核支付单';
            //操作时间
            $dateline = time();
            //操作连接
            $url = 'http://api.zuji.huishoubao.com/index.php?m=oder2&c=payment&a=check';
            $this->create_log($userId,$user_IP,$optionId,$remark,$dateline,$url);
        } else {

            $this->load->librarys('View')
                ->assign('payment_id', $payment_id)
                ->display('alert_payment_check');
        }
    }

    /*
     * 导出表格
     */

    public function daochu()
    {

    }

}
