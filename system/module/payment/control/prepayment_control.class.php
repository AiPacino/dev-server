<?php

use zuji\payment\Instalment;
use zuji\order\Order;
use \zuji\OrderLocker;

hd_core::load_class('init', 'admin');
class prepayment_control extends init_control {

    /**
     * @var array 关键字搜索类型列表
     */
    protected $keywords_type_list = [
        'order_no' => '订单号',
        'order_id' => '订单ID',
        'mobile' => '手机号',
    ];

    /**
     * @var array 状态
     */
    protected $status_list = [
        '-1' => '全部',
        '0' => '未支付',
        '1' => '支付成功',
        '2' => '已退款',
    ];

    public function _initialize() {
        parent::_initialize();
        $this->prepayment_service   = $this->load->service('payment/prepayment');
    }

    /**
     * 列表
     */
    public function index(){
        $where = [];
        $additional = ['page'=>1,'size'=>20];

        // 查询条件
        if (isset($_GET['prepayment_status'])) {
            if(intval($_GET['prepayment_status']) == -1){
                unset($where['prepayment_status']);
            }else if($_GET['prepayment_status'] === '0'){
                $where['prepayment_status'] = $_GET['prepayment_status'];
            }else{
                $where['prepayment_status'] = intval($_GET['prepayment_status']);
            }
        }else{
            $_GET['prepayment_status'] = -1;
        }

        if($_GET['keywords']!=''){
            if($_GET['kw_type']=='order_no'){
                $where['order_no'] = $_GET['keywords'];
            }
            if($_GET['kw_type']=='order_id'){
                $where['order_id'] = trim(intval($_GET['keywords']));
            }
            if($_GET['kw_type']=='mobile'){
                $where['mobile'] = trim($_GET['keywords']);
            }
        }

        if(isset($_GET['begin_time'])){
            $where['begin_time'] = $_GET['begin_time'];
            if( !$where['begin_time'] ){
                unset($where['begin_time']);
            }
        }

        $limit = min(isset($_GET['limit']) && is_numeric($_GET['limit']) ? $_GET['limit'] : 20, 100);
        $additional['page'] = intval($_GET['page']);
        $additional['size'] = intval($limit);

        // 查询
        $count = $this->prepayment_service->get_count($where);

        $prepayment_list = [];
        if( $count>0 ){
            // 订单分期付款查询
            $prepayment_list = $this->prepayment_service->get_list($where,$additional);
            foreach ($prepayment_list as &$item){
                $item['alloe_refund'] = 0;
                $item['payment_amount'] 	= Order::priceFormat($item['payment_amount']/100);

                $item['prepayment_time'] 	= $item['prepayment_time']>0 ? date("Y-m-d H:i:s",$item['prepayment_time']): '--';
                $item['show_status'] 	    = $this->status_list[$item['prepayment_status']];

                $item['alloe_refund']       = $this->allow_refund($item['prepayment_id']);
            }
        }

        $data_table = array(
            'th' => array(
                'order_no' => array('length' => 10,'title' => '订单编号'),
                'realname' => array('length' => 8,'title' => '用户姓名'),
                'mobile' => array('length' => 10,'title' => '联系电话'),
                'term' => array('title' => '还款期数', 'length' => 8),
                'prepayment_time' => array('title' => '还款时间', 'length' => 10),
                'show_status' => array('title' => '状态', 'length' => 8),
                'payment_amount' => array('title' => '还款金额', 'length' => 8),
                'trade_no' => array('title' => '交易流水号', 'length' => 10),
                'out_trade_no' => array('title' => '第三方交易号', 'length' => 15),
            ),
            'record_list' => $prepayment_list,
            'pages' => $this->admin_pages($count, $additional['size']),
        );
        // 头部 tab 切换设置
        $tab_list = [];
        $status_list =  $this->status_list;
        foreach ($status_list as $k => $name) {
            $css = '';
            if ($_GET['prepayment_status'] == $k) {
                $css = 'current';
            }
            $url = self::current_url(array('prepayment_status' => $k));
            $tab_list[] = '<a class="' . $css . '" href="' . $url . '">' . $name . '</a>';
        }

        $this->load->librarys('View')
            ->assign('keywords_type_list',$this->keywords_type_list)
            ->assign('tab_list',$tab_list)
            ->assign('prepayment_status',$_GET['prepayment_status'])
            ->assign('data_table', $data_table)
            ->display('prepayment');
    }



    /**
     * 详情
     */
    public function allow_refund($prepayment_id){


        $allow = 0;

        if($prepayment_id < 1){
            $allow = 0;
        }
        $info = $this->prepayment_service->get_info(['prepayment_id'=>$prepayment_id]);
        if(!info){
            $allow = 0;
        }

        $this->instalment_service = $this->load->service('order2/instalment');
        $instalment_info = $this->instalment_service->get_info(['id'=>$info['instalment_id']]);
        // 交易号判断
        if($info['prepayment_status'] == 1 ){
            if($instalment_info['out_trade_no'] != "" && $info['out_trade_no'] != $instalment_info['out_trade_no']){
                $allow = 1;
            }
        }

//
//        $prepayment_time = $info['prepayment_time'];
//
//        $where = [
//            'instalment_id'=>$info['instalment_id'],
//            'prepayment_status'=>1,
//            'prepayment_time'=>$prepayment_time,
//        ];
//        $prepayment_count = $this->prepayment_service->get_count($where);
//        if($prepayment_count >= 1){
//            $allow =  1;
//        }
//
        return $allow;
    }




    /**
     * 退款
     */
    public function refund(){
        $this->order_service 		= $this->load->service('order2/order');
        $this->trade_service 		= $this->load->service('payment/payment_trade');

        $prepayment_id = trim($_REQUEST['prepayment_id']);
        if($prepayment_id < 1){
            showmessage('参数错误','null');
        }

        $info = $this->prepayment_service->get_info(['prepayment_id'=>$prepayment_id]);
        $info['payment_amount']  = Order::priceFormat($info['payment_amount']/100);
        $info['prepayment_time'] = $info['prepayment_time']>0 ? date("Y-m-d H:i:s",$info['prepayment_time']): '--';
        if(!$info){
            showmessage('尚未缴款', null);
        }

        //判断该订单是否有锁
        if(OrderLocker::isLocked($info['order_no'])){
            showmessage('该订单有锁', null);
        }

        if (checksubmit('dosubmit')){

            // 开启事务
            $b = $this->order_service->startTrans();
            if( !$b ){
                $this->order_service->rollback();
                showmessage('事务开启失败', 'null');
            }

            // 支付宝退款操作
            $appid = config('ALIPAY_APP_ID');
            $appid = $appid ? $appid : \zuji\Config::Alipay_App_Id;
            $Refund = new alipay\Refund($appid);

            //支付宝交易码
            $params = [
                'trade_no' => $info['trade_no'],                //租机交易号
                'out_trade_no' => $info['out_trade_no'],        //支付宝交易码
                'refund_amount' => $info['payment_amount'],     //金额
                'refund_reason' => '自主还款-退款',                       //备注
                'request_no' => \zuji\Business::create_business_no(),   //租机请求序列号
            ];

            $b = $Refund->refund( $params );
            if(!$b){
                $this->order_service->rollback();
                set_error("支付宝退款失败");
                showmessage(get_error(), 'null');
            }

            // 修改状态
            $data = [
                'prepayment_status'=>2,
                'refund_time'=>time(),
            ];

            $prepayment_b = $this->prepayment_service->save(['prepayment_id'=>$prepayment_id],$data);
            if(!$prepayment_b){
                $this->order_service->rollback();
                set_error("支付宝退款失败：修改状态失败");
                showmessage('支付宝退款失败', 'null');
            }

            // 修改分期状态
            // 有重复支付的账单 退款不修改状态 否则则还原状态
            $instalment_id = $info['instalment_id'];
            $prepayment_count = $this->prepayment_service->get_count(['instalment_id'=>$instalment_id,'prepayment_status'=>1]);

            if($prepayment_count < 1){
                $this->instalment_service = $this->load->service('order2/instalment');

                $instalment_info = $this->instalment_service->get_info(['id'=>$instalment_id]);

                // 交易号判断
                if($info['out_trade_no'] == $instalment_info['out_trade_no']){

                    $instalent_where = ['id'=>$instalment_id];
                    $instalment_b = $this->instalment_service->save($instalent_where,['status'=>\zuji\payment\Instalment::UNPAID]);
                    if(!$instalment_b){
                        $this->order_service->rollback();
                        set_error("支付宝退款失败：修改分期状态失败");
                        showmessage('支付宝退款失败：修改分期状态失败', 'null');
                    }
                }
            }

            // 提交事务
            $this->order_service->commit();

            showmessage('操作成功','null',1);
        }



        $this->load->librarys('View')
            ->assign('info', $info)
            ->assign('refund', $_GET['refund'])
            ->display('prepayment_refund');
    }


}