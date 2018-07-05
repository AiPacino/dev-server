<?php

use zuji\order\OrderStatus;
use zuji\debug\Location;
use zuji\order\EvaluationStatus;
use zuji\Business;
use zuji\debug\Debug;
use zuji\email\EmailConfig;
use oms\Order;

/**
 * 检测单 =》 控制器操作
 * 1）订单检测列表
 *   【搜索：订单编号、IMEI、检测状态（全部，待检测，检测合格，检测不合格）】
 *   【操作：待检测订单->检测结果确认：检测异常->不符合检测结果原因】
 *   【导出：列表】
 * 2）订单检测异常列表（全部，还机，换机，退货）
 *   【搜索：订单编号、手机号、检测原因、检测时间】
 *   【操作：处理->赔付入库/买断邮寄用户；检测报告详情】
 *   【导出：列表】
 */

hd_core::load_class('base', 'order2');

class evaluation_control extends base_control
{


    /**
     * @var array 关键字搜索类型列表
     */
    protected $keywords_type_list = [
        'order_no' => '订单编号',
    ];

    /**
     * @var array 检测合格分类列表
     */
    protected $level_list = [
        1 => '优品',
        2 => '良品',
        127 => '其他',
    ];

    /**
     * @var array 检测异常的备注原因列表
     */
    protected $unqualified_remark_list = [
        1 => '屏幕有裂痕',
        2 => '手机进水（或受潮）',
        3 => '手机触摸功能有问题',
    ];

    /**
     * @var array 检测异常业务类型列表【退货、换机、还机】
     */
    protected $system_key_list = [];

    /**
     * @var array 检测异常处理结果列表【入库，买断，付款一部分】
     */
    protected $deal_result_list = [];


    public function _initialize()
    {
        parent::_initialize();
        $this->evaluation_service = $this->load->service('order2/evaluation');
        $this->delivery_service = $this->load->service('order2/delivery');
        $this->order_service = $this->load->service('order2/order');
        //初始化检测异常业务类型列表
        $this->system_key_list = [
            zuji\Business::BUSINESS_ZUJI => zuji\Business::getName(zuji\Business::BUSINESS_ZUJI),
            zuji\Business::BUSINESS_GIVEBACK => zuji\Business::getName(zuji\Business::BUSINESS_GIVEBACK),
            zuji\Business::BUSINESS_EXCHANGE => zuji\Business::getName(zuji\Business::BUSINESS_EXCHANGE),
        ];
        //初始化检测异常处理结果列表
        $this->deal_result_list = [
//            zuji\order\EvaluationStatus::UnqualifiedAccepted => zuji\order\EvaluationStatus::getUnqualifiedName(zuji\order\EvaluationStatus::UnqualifiedAccepted),
            zuji\order\EvaluationStatus::UnqualifiedBuyout => zuji\order\EvaluationStatus::getUnqualifiedName(zuji\order\EvaluationStatus::UnqualifiedBuyout),
//            zuji\order\EvaluationStatus::UnqualifiedPayfor => zuji\order\EvaluationStatus::getUnqualifiedName(zuji\order\EvaluationStatus::UnqualifiedPayfor),
        ];
        //物流公司列表
        $this->logistics_list = [
            1 => '顺丰',
        ];
        
    }

    /**
     * 检测单列表
     * 支持搜索条件【订单编号、检测状态（全部，待检测，检测合格，检测不合格）】
     */
    public function index()
    {
        //-+--------------------------------------------------------------------
        // | 参数获取和验证过滤
        //-+--------------------------------------------------------------------

        //查询条件的获取以及验证过滤
        $where = $this->__parse_where();
        //查询附加条件分页的获取以及验证过滤
        $additional = $this->__parse_additional();

        //-+--------------------------------------------------------------------
        // | 获取数据、数据值转换
        //-+--------------------------------------------------------------------
        //检测单总数获取
        $evaluation_count = $this->evaluation_service->get_count($where);
        //检测列表获取
        $evaluation_list = $this->evaluation_service->get_list($where, $additional);
        $order_ids = array_column($evaluation_list, 'order_id');
        $order_list = $this->order_service->get_order_list(['order_id' => $order_ids], ['size' => count($order_ids)]);
        mixed_merge($evaluation_list, $order_list, 'order_id', 'order_info');
        /*
         * 验证列表按钮权限
         */
        //检测
        $auth['isqualified_alert'] = parent::check_promission_operate('order2', 'evaluation', 'isqualified_alert');
        //申请退款
        $auth['create_refund'] = parent::check_promission_operate('order2', 'refund', 'create_refund');
        //生成发货单
        $auth['alert_delivery'] = parent::check_promission_operate('order2', 'evaluation', 'alert_delivery');
        //解除资金预授权
        $auth['remove_authorize'] = parent::check_promission_operate('order2', 'order', 'remove_authorize');
        
        
        
        foreach ($evaluation_list as $key => &$value) {
            if(!isset($value['order_info'])){continue;}
            $specs = $value['goods_info']['specs'];
            $specs_value = array_column($specs, 'value');
            $value['bar_code'] = $value['receive_info']['bar_code'];
            $value['sku_name'] = $value['goods_info']['sku_name'] . ' ' . implode('&nbsp;', $specs_value);
            //$value['imei'] = $value['goods_info']['imei1'];
            $value['imei'] = [
                '序列号：' . $value['goods_info']['imei1'],
                'IMEI1：' . $value['goods_info']['imei1'],
            ];
            if ($value['goods_info']['imei2']) {
                $value['imei'][] = 'IMEI2：' . $value['goods_info']['imei2'];
            }
            if ($value['goods_info']['imei3']) {
                $value['imei'][] = 'IMEI3：' . $value['goods_info']['imei3'];
            }
            $value['imei'] = implode('<br/>', $value['imei']);

            $value['create_time'] = date('Y-m-d H:i:s', $value['create_time']);
            $value['evaluation_status_show'] = zuji\order\EvaluationStatus::getStatusName($value['evaluation_status']);
            $value['order_status'] = $value['order_info']['order_status'];
            $value['business'] = Business::getName($value['business_key']);
            $count = $this->delivery_service->get_count(['order_id' => $value['order_id'], 'evaluation_id' => $value['evaluation_id'], 'business_key' => Business::BUSINESS_HUANHUO]);
//           if(!is_array($value['order_info'])){
//               var_dump($value);die;
//           }

            $Order = new Order($value['order_info']);
            //是否可以生成发货单
            $value['allow_create_delivery']=false;
            if($auth['alert_delivery'] 
                && $count==0  
                && $value['business_key'] ==Business::BUSINESS_HUANHUO 
                && $value['evaluation_status'] == EvaluationStatus::EvaluationQualified
                && !$Order->order_islock()){
                $value['allow_create_delivery']=true;
            }

            //是否允许申请退款/解除资金预授权
            $value['allow_to_create_refund'] = false;
            $value['allow_to_remove_authorize'] = false;
            if($Order->get_payment_type_id() == \zuji\Config::WithhodingPay){
                if($Order->allow_to_remove_authorize() && $auth['remove_authorize'] && $value['business_key'] == Business::BUSINESS_ZUJI && $value['evaluation_status'] == EvaluationStatus::EvaluationQualified && !$Order->order_islock()){
                    $value['allow_to_remove_authorize'] = true;
                }
            }else{
                if($Order->allow_to_create_refund() && $auth['create_refund'] && $value['business_key'] == Business::BUSINESS_ZUJI && $value['evaluation_status'] == EvaluationStatus::EvaluationQualified && !$Order->order_islock()){
                    $value['allow_create_refund'] = true;
                }
            }

            //是否允许检测
            $value['allow_isqualified'] = false;
            if($value['evaluation_status'] == EvaluationStatus::EvaluationWaiting && $auth['isqualified_alert'] && $Order->allow_to_evaluation() && !$Order->order_islock()){
                $value['allow_isqualified'] = true;
            }
            
        }
        // 获取分页信息
        $pages = $this->admin_pages($evaluation_count, $additional['size']);

        //-+--------------------------------------------------------------------
        // | 页面模板赋值
        //-+--------------------------------------------------------------------

        //table信息
        $lists = array(
            //头部信息
            'th' => array(
                'business' => array('title' => '业务类型', 'length' => 10),
                'order_no' => array('title' => '订单编号', 'length' => 10),
                'bar_code' => array('title' => '条码', 'length' => 15),
                'sku_name' => array('title' => '商品信息', 'length' => 20),
                'imei' => array('title' => 'IMEI', 'length' => 15,),
                'evaluation_status_show' => array('title' => '处理状态', 'length' => 10),
                'create_time' => array('title' => '创建时间', 'length' => 10),
            ),
            //列表信息
            'lists' => $evaluation_list,
            //分页信息
            'pages' => $pages,
        );

        $status_list = array_merge(['0' => '全部'], EvaluationStatus::getStatusList());
        $tab_list = [];

        foreach ($status_list as $k => $name) {
            $css = '';
            if ($k == EvaluationStatus::EvaluationCreated) {
                continue;
            }
            if ($k == EvaluationStatus::EvaluationFinished) {
                continue;
            }
            if ($k == EvaluationStatus::EvaluationUnderway) {
                continue;
            }
            if ($_GET['evaluation_status'] == $k) {
                $css = 'current';
            }

            $url = url('order2/evaluation/index', array('evaluation_status' => $k));
            $tab_list[] = '<a class="' . $css . '" href="' . $url . '">' . $name . '</a>';

        }
       
        //赋值模板
        $this->load->librarys('View')
            ->assign('lists', $lists)
            ->assign('tab_list', $tab_list)
            ->assign('evaluation_wait', zuji\order\EvaluationStatus::EvaluationWaiting)//待检测
            ->assign('evaluation_qualified', zuji\order\EvaluationStatus::ResultQualified)//检测合格
            ->assign('evaluation_unqualified', zuji\order\EvaluationStatus::ResultUnqualified)//检测异常
            ->assign('keywords_type_list', $this->keywords_type_list)
            ->assign('auth', $auth)
            ->display('evaluation_index');
    }

    /**
     * 检测单详情。
     */
    public function detail()
    {
        // 是否内嵌
        $inner = boolval($_GET['inner']);
        //
        $evaluation_id = intval(trim($_GET['evaluation_id']));
        if ($evaluation_id < 1) {
            showmessage("参数错误", "null", 0);
        }
        $evaluation_info = $this->evaluation_service->get_info($evaluation_id);

        if (empty($evaluation_info)) {
            //检测单信息查询失败记录
            $debug = $this->debug_service->create([
                'location_id' => Location::L_Evaluation,
                'subject' => '检测单信息获取失败' . get_error(),
                'data' => '检测单ID->' . $evaluation_id . ' 错误',
            ]);
            showmessage("检测单不存在", "null", 0);
        }
        if (isset($evaluation_info['unqualified_remark']) && $evaluation_info['unqualified_remark']) {
            $evaluation_info['unqualified_remark_show'] = $this->unqualified_remark_list[$evaluation_info['unqualified_remark']];
        }
//        var_dump($evaluation_info);exit;
        $this->load->librarys('View')
            ->assign('inner', $inner)
            ->assign('evaluation_info', $evaluation_info)
            ->display('evaluation_detail');
    }

    /**
     * 检测是否合格=>弹框
     */
    public function isqualified_alert()
    {
        //-+--------------------------------------------------------------------
        // | 参数获取和验证过滤
        //-+--------------------------------------------------------------------
        $params = filter_array($_GET, [
            'order_id' => 'required|is_id',
            'evaluation_id' => 'required|is_id',
        ]);
        $order_id = isset($params['order_id']) ? $params['order_id'] : 0;
        $evaluation_id = isset($params['evaluation_id']) ? $params['evaluation_id'] : 0;

        $order_info = $this->order_service->get_order_info(['order_id' => $order_id]);
        // 检测单
        $evaluation_info = $this->evaluation_service->get_info($evaluation_id);
        //获取检测单是否已经检测过,已经检测完成的不允许再次检测
        if ($evaluation_info['evaluation_status'] == EvaluationStatus::EvaluationUnqualified || $evaluation_info['evaluation_status'] == EvaluationStatus::EvaluationQualified) {
            $this->order_service->rollback();
            showmessage('当前检测单已经检测过，请勿重复提交！','null',0);
        }
        $Orders = new Order($order_info);
        if(!$Orders->allow_to_evaluation()){
            $this->order_service->rollback();
            showmessage('该订单不允许检测！','null',0);
        }
        $goods_info = $this->order_service->get_goods_info($order_info['goods_id']);
        $specs = $goods_info['specs'];
        $specs_value = array_column($specs, 'value');
        $goods_info['goods_name'] = $goods_info['sku_name'] . ' ' . implode('&nbsp;', $specs_value);
        //-+--------------------------------------------------------------------
        // | 弹出框显示页面
        //-+--------------------------------------------------------------------
        $this->load->librarys('View')
            ->assign('msg', '是否符合检测结果？')
            ->assign('order_id', $order_id)
            ->assign('evaluation_id', $evaluation_id)
            ->assign('unqualified_remark_list', $this->unqualified_remark_list)
            ->assign('level_list', $this->level_list)
            ->assign('goods_info', $goods_info)
            ->display('evaluation_alert_isqualified');
    }

    /**
     * 检测确认是否合格
     */
    public function confirm_qualified()
    {
        //-+--------------------------------------------------------------------
        // | 参数获取和验证过滤
        //-+--------------------------------------------------------------------
        $params = filter_array($_GET, [
            'order_id' => 'required|is_id',
            'evaluation_id' => 'required|is_id',
        ]);
        if (isset($_POST['qualified'])) {
            $params['qualified'] = $_POST['qualified'];
        }

        $evaluation_id = intval($params['evaluation_id']);
        $order_id = intval($params['order_id']);
        $qualified = $params['qualified'];
        $remark = trim($_POST['evaluation_remark']);

        //检测结果入口需要的参数不全
        if (!isset($params['qualified']) || !isset($params['order_id']) || !isset($params['evaluation_id'])) {
            showmessage('参数错误','null',0);
        }
        //开启事务
        if(!$this->order_service->startTrans()){
            showmessage('服务器繁忙','null',0);
        }
        $options = ['lock' => true];

        // 订单
        $order_info = $this->order_service->get_order_info(['order_id' => $params['order_id']], $options);
        // 检测单
        $evaluation_info = $this->evaluation_service->get_info($evaluation_id, $options);
        //获取检测单是否已经检测过,已经检测完成的不允许再次检测
        if ($evaluation_info['evaluation_status'] == EvaluationStatus::EvaluationUnqualified || $evaluation_info['evaluation_status'] == EvaluationStatus::EvaluationQualified) {
            $this->order_service->rollback();
            showmessage('当前检测单已经检测过，请勿重复提交！','null',0);
        }
        
        $admin = [
            'id' =>$this->admin['id'],
            'username' =>$this->admin['username'],
        ];
        $Operator = new oms\operator\Admin( $admin['id'], $admin['username'] );
        $Orders = new Order($order_info);
        if(!$Orders->allow_to_evaluation()){
            $this->order_service->rollback();
            showmessage('该订单不允许检测！','null',0);
        }
        // 设置检测结果
        $data = [
            'order_id' => $order_id,
            'evaluation_id' => $evaluation_id,
            'qualified' => $qualified,
            'admin_id' => ADMIN_ID,
            'remark' => $remark,
        ];
        try{
            // 订单 观察者主题
            $OrderObservable = $Orders->get_observable();
            // 订单 观察者 状态流
            $FollowObserver = new oms\observer\FollowObserver( $OrderObservable );
            // 订单 观察者  日志
            $LogObserver = new oms\observer\LogObserver( $OrderObservable , "检测","备注：".$remark);
            $LogObserver->set_operator($Operator);
        
            $b =$Orders->evaluation($data);
            if(!$b){
                $this->order_service->rollback();
                showmessage('检测失败'.get_error(),'null',0);
            }
            if($order_info['payment_type_id'] == \zuji\Config::MiniAlipay && $qualified== 1 && $evaluation_info['business_key'] == Business::BUSINESS_ZUJI){

                $this->zhima_order_confrimed_table =$this->load->table('order2/zhima_order_confirmed');
                //获取订单的芝麻订单编号
                $zhima_order_info = $this->zhima_order_confrimed_table->where(['order_no'=>$order_info['order_no']])->find($options);
                if(!$zhima_order_info){
                    $this->order_service->rollback();
                    showmessage('该订单没有芝麻订单号！','null',0);
                }
                $zhima = new \zhima\Withhold();
                $b =$zhima->OrderCancel([
                    'out_order_no'=>$order_info['order_no'],//商户端订单号
                    'zm_order_no'=>$zhima_order_info['zm_order_no'],//芝麻订单号
                    'remark'=>$remark,//订单操作说明
                ]);
                if($b === false){
                    $this->order_service->rollback();
                    showmessage('操作失败','null',0);
                }
            }
            //发送邮件 -----begin
            $data =[
                'subject'=>'检测完成',
                'body'=>'订单编号：'.$order_info['order_no']." 已检测完成，请处理。",
                'address'=>[
                    ['address' => EmailConfig::Service_Username]
                ],
            ];
            $send =EmailConfig::system_send_email($data);
            if(!$send){
                Debug::error(Location::L_Delivery, "发送邮件失败", $data);
            }
            //发送邮件------end
            $this->order_service->commit();
            // 更新 订单状态
            if($qualified==0){
                //检测不合格发送短信
                \zuji\sms\SendSms::evaluation_unqualified([
                    'mobile' => $order_info['mobile'],
                    'orderNo' => $order_info['order_no'],
                    'realName' => $order_info['realname'],
                    'goodsName' => $order_info['goods_name'],
                ]);
            }else{
                //检测合格发送短信
                \zuji\sms\SendSms::evaluation_qualified([
                    'mobile' => $order_info['mobile'],
                    'orderNo' => $order_info['order_no'],
                    'realName' => $order_info['realname'],
                    'goodsName' => $order_info['goods_name'],
                ]);
            }
        
            showmessage('操作成功','null',1);
        }catch (\Exception $exc){
            $this->order_service->rollback();
            showmessage($exc->getMessage(),'null',0);
        }

    }

    /**
     * 检测异常单列表
     *
     */
    public function abnormal()
    {
        //-+--------------------------------------------------------------------
        // | 参数获取和验证过滤
        //-+--------------------------------------------------------------------

        //查询条件的获取以及验证过滤
        $where = $this->__parse_where();
        //拼接检测异常条件
        $where['evaluation_status'] = EvaluationStatus::EvaluationUnqualified;
        //查询附加条件分页的获取以及验证过滤
        $additional = $this->__parse_additional();

        //-+--------------------------------------------------------------------
        // | 
        //-+--------------------------------------------------------------------

        //检测单总数获取
        $evaluation_count = $this->evaluation_service->get_count($where);
        //检测列表获取
        $evaluation_list = $this->evaluation_service->get_list($where, $additional);
        // 获取分页信息
        $pages = $this->admin_pages($evaluation_count, $additional['size']);
        $order_ids = array_column($evaluation_list, 'order_id');

        /*
         * 验证列表按钮权限
         */
        //处理
        $auth['deal_result'] = parent::check_promission_operate('order2', 'evaluation', 'deal_result');
        //生成退款单
        $auth['create_refund'] = parent::check_promission_operate('order2', 'evaluation', 'create_refund');
        //解除资金预授权
        $auth['remove_authorize'] = parent::check_promission_operate('order2', 'order', 'remove_authorize');
        //生成发货单
        $auth['alert_delivery'] = parent::check_promission_operate('order2', 'evaluation', 'alert_delivery');
        
        // 订单列表
        $order_list = $this->order_service->get_order_list(['order_id' => $order_ids], ['size' => count($order_ids)]);
        mixed_merge($evaluation_list, $order_list, 'order_id', 'order_info');

        foreach ($evaluation_list as $key => &$value) {
            if(!isset($value['order_info'])){continue;}
            $specs = $value['goods_info']['specs'];
            $specs_value = array_column($specs, 'value');
            $value['imei'] = [
                '序列号：' . $value['goods_info']['imei1'],
                'IMEI1：' . $value['goods_info']['imei1'],
            ];
            if ($value['goods_info']['imei2']) {
                $value['imei'][] = 'IMEI2：' . $value['goods_info']['imei2'];
            }
            if ($value['goods_info']['imei3']) {
                $value['imei'][] = 'IMEI3：' . $value['goods_info']['imei3'];
            }
            $value['imei'] = implode('<br/>', $value['imei']);
            $value['business_name'] = Business::getName($value["business_key"]);
            $value['sku_name'] = $value['goods_info']['sku_name'] . ' ' . implode('&nbsp;', $specs_value);
            $value['realname'] = $value['order_info']['realname'];
            $value['mobile'] = $value['order_info']['mobile'];
            $value['status'] = $value['order_info']['status'];
            $value['unqualified_result_show'] = EvaluationStatus::getUnqualifiedName($value['unqualified_result']);
            //是否允许生成发货单
            $Orders = new Order($value['order_info']);
            $value['allow_create_delivery'] = false;
            if ($value['unqualified_result'] == EvaluationStatus::UnqualifiedGoUse && !$Orders->order_islock()) {
                $value['delivery_count'] = $this->delivery_service->get_count(['order_id' => $value['order_id'], 'evaluation_id' => $value['evaluation_id'], 'business_key' => Business::BUSINESS_HUIJI]);
                if ($value['delivery_count'] == 0 && $auth['alert_delivery']) {
                    $value['allow_create_delivery'] = true;
                }
            } else if ($value['unqualified_result'] == EvaluationStatus::UnqualifiedExchange && !$Orders->order_islock()) {
                $value['delivery_count'] = $this->delivery_service->get_count(['order_id' => $value['order_id'], 'evaluation_id' => $value['evaluation_id'], 'business_key' => Business::BUSINESS_HUANHUO]);
                if ($value['delivery_count'] == 0 && $auth['alert_delivery']) {
                    $value['allow_create_delivery'] = true;
                }
            }

            $value['allow_to_create_refund'] = false;
            $value['allow_to_remove_authorize'] = false;
            if ($Orders->allow_to_create_refund() && $value['order_info']['payment_type_id'] == \zuji\Config::FlowerStagePay && $value['unqualified_result'] == EvaluationStatus::UnqualifiedAccepted && $auth['create_refund'] && $value['order_info']['refund_id'] == 0) {
                $value['allow_to_create_refund'] = true;
            }
            if ($Orders->allow_to_remove_authorize() && $value['order_info']['payment_type_id'] == \zuji\Config::WithhodingPay && $value['unqualified_result'] == EvaluationStatus::UnqualifiedAccepted && $auth['remove_authorize']) {
                $value['allow_to_remove_authorize'] = true;
            }
            //是否允许异常处理
            $value['deal_result'] = false;
            if( $value['unqualified_result'] ==EvaluationStatus::UnqualifiedInvalid && $auth['deal_result'] && $Orders->allow_to_abnormal()){
                $value['deal_result'] = true;
            }

        }
        $lists = array(
            'th' => array(
                'business_name' => array('length' => 10, 'title' => '业务类型'),
                'order_no' => array('title' => '订单编号', 'length' => 10),
                'sku_name' => array('title' => '选购产品', 'length' => 10, 'style' => 'left_text'),
                'imei' => array('length' => 10, 'title' => 'IMEI'),
                'realname' => array('length' => 10, 'title' => '会员姓名'),
                'mobile' => array('length' => 10, 'title' => '会员电话'),
                'evaluation_remark' => array('length' => 10, 'title' => '异常问题'),
                'unqualified_remark' => array('length' => 10, 'title' => '异常备注'),
                'unqualified_result_show' => array('length' => 10, 'title' => '处理状态'),
            ),
            'lists' => $evaluation_list,
            'pages' => $pages,
        );

        $status_list = EvaluationStatus::getUnqualifiedList();

        if (!isset($_GET['unqualified_result']) && $_GET['unqualified_result'] == "all") {
            $css = 'current';
        }
        $url = url('order2/evaluation/abnormal', array('unqualified_result' => "all"));
        $tab_list[] = '<a class="' . $css . '" href="' . $url . '">q</a>';
        foreach ($status_list as $k => $name) {
            if ($k == EvaluationStatus::UnqualifiedPayfor) {
                continue;
            }
            if ($k == EvaluationStatus::UnqualifiedBuyout) {
                continue;
            }
            $css = '';
            if ($_GET['unqualified_result'] == $k) {
                $css = 'current';
            }
            $url = url('order2/evaluation/abnormal', array('unqualified_result' => $k));
            $tab_list[] = '<a class="' . $css . '" href="' . $url . '">' . $name . '</a>';

        }


        $this->load->librarys('View')
            ->assign('lists', $lists)
            ->assign('tab_list', $tab_list)
            ->assign('zuji', zuji\Business::BUSINESS_ZUJI)//租机业务
            ->assign('giveback', zuji\Business::BUSINESS_GIVEBACK)//还机业务
            ->assign('exchange', zuji\Business::BUSINESS_EXCHANGE)//换机业务
            ->assign('keywords_type_list', $this->keywords_type_list)
            ->assign('system_key_list', $this->system_key_list)
            ->assign('auth', $auth)
            ->display('evaluation_unqulified');
    }

    /**
     * 检测异常处理结果=>弹框
     */
    public function deal_result()
    {
        //-+--------------------------------------------------------------------
        // | 参数获取和验证过滤
        //-+--------------------------------------------------------------------
        $params = filter_array($_GET, [
            'order_id' => 'required|is_id',
            'evaluation_id' => 'required|is_id',
        ]);
        if (count($params) != 2) {
            showmessage('参数错误','null',0);
        }

        //判断订单是否有效
        $order_info = $this->order_service->get_order_info(['order_id' => $params['order_id']]);
        $Orders = new \oms\Order($order_info);
        if(!$Orders->allow_to_abnormal()){
            showmessage('该订单不允许检测','null');
        }

        $evaluation_info = $this->evaluation_service->get_info($params['evaluation_id']);
        if ($evaluation_info['unqualified_result'] != EvaluationStatus::UnqualifiedInvalid) {
             showmessage('当前异常检测单已经处理过，请勿重复处理','null',0);
        }
        $goods_info = $this->order_service->get_goods_info($order_info['goods_id']);
        $specs = $goods_info['specs'];
        $specs_value = array_column($specs, 'value');
        $goods_info['goods_name'] = $goods_info['sku_name'] . ' ' . implode('&nbsp;', $specs_value);

        $status_list = EvaluationStatus::getUnqualifiedList();
        $tab_list = [];
        foreach ($status_list as $k => $name) {
            if ($k == EvaluationStatus::UnqualifiedInvalid) {
                continue;
            }
            if ($k == EvaluationStatus::UnqualifiedPayfor) {
                continue;
            }
            if ($k == EvaluationStatus::UnqualifiedBuyout) {
                continue;
            }
            if($evaluation_info['business_key'] ==Business::BUSINESS_ZUJI){
                if ($k == EvaluationStatus::UnqualifiedExchange) {
                    continue;
                }
            }
            if($evaluation_info['business_key'] ==Business::BUSINESS_HUANHUO){
                if ($k == EvaluationStatus::UnqualifiedGoUse) {
                    continue;
                }
                if ($k == EvaluationStatus::UnqualifiedAccepted) {
                    continue;
                }
            }
            
            $tab_list[$k] = $name;
        }
        //-+--------------------------------------------------------------------
        // | 弹出框显示页面
        //-+--------------------------------------------------------------------
        $this->load->librarys('View')
            ->assign('msg', '请选择相关异常处理信息：')
            ->assign('order_id', $params['order_id'])
            ->assign('order_info',$order_info)
            ->assign('evaluation_id', $params['evaluation_id'])
            ->assign('deal_result_list', $tab_list)
            ->assign('goods_info', $goods_info)
            ->display('evaluation_alert_dealresult');
    }

    /**
     * 检测确认异常处理结果
     */
    public function confirm_deal_result()
    {
        //-+--------------------------------------------------------------------
        // | 参数获取和验证过滤
        //-+--------------------------------------------------------------------
        $params = filter_array($_POST, [
            'deal_result_type' => 'required|is_numeric',
            'order_id' => 'required|is_id',
            'evaluation_id' => 'required|is_id',
            'unqualified_remark' => 'required',
        ]);

        $admin = [
            'id' =>$this->admin['id'],
            'username' =>$this->admin['username'],
        ];

        $Operator = new oms\operator\Admin( $admin['id'], $admin['username'] );
        $trans = $this->order_service->startTrans();
        if(!$trans){
            showmessage("服务器繁忙 请稍候再试！","null",0);
        }

        $options = ['lock' => true];
        //判断订单是否有效
        $order_info = $this->order_service->get_order_info(['order_id' => $params['order_id']], $options);

        $Orders = new \oms\Order($order_info);
        if(!$Orders->allow_to_abnormal()){
            $this->order_service->rollback();
            showmessage('该订单不允许检测','null');
        }

        //检测结果入口需要的参数不全
        if (!isset($params['order_id']) || !isset($params['evaluation_id'])) {
            $this->order_service->rollback();
            showmessage('检测结果信息未获取成功','null',0);
        }
        //检测结果入口需要的参数不全
        if (!isset($params['deal_result_type'])) {
            $this->order_service->rollback();
            showmessage('请选择检测结果类型','null',0);
        }

        $evaluation_id = intval($params['evaluation_id']);
        $order_id = intval($params['order_id']);
        $deal_result_type = intval($params['deal_result_type']);
        $unqualified_remark = strval($params['unqualified_remark']);

        //-+--------------------------------------------------------------------
        // | 业务逻辑处理
        //-+--------------------------------------------------------------------
        //获取检测单是否已经检测过,已经检测完成的不允许再次检测
        $evaluation_info = $this->evaluation_service->get_info($evaluation_id, $options);
        if ($evaluation_info['unqualified_result'] != EvaluationStatus::UnqualifiedInvalid) {
            $this->order_service->rollback();
            showmessage('当前异常检测单已经处理过，请勿重复处理','null',0);
        }
        $data =[
            'admin_id'=>intval($this->admin['id']),
            'evaluation_id'=>$evaluation_id,
            'order_id'=>$order_id,
            'unqualified_remark'=>$unqualified_remark,
            'unqualified_result'=>$deal_result_type,
        ];
        // 订单 观察者主题
        $OrderObservable = $Orders->get_observable();
        try{

            // 订单 观察者 状态流
            if($Orders->get_payment_type_id() != \zuji\Config::WithhodingPay){
            $FollowObserver = new oms\observer\FollowObserver( $OrderObservable );
            }
            // 订单 观察者  日志
            $LogObserver = new oms\observer\LogObserver( $OrderObservable , "检测单异常处理","检测异常处理结果".EvaluationStatus::getUnqualifiedName($deal_result_type));
            $LogObserver->set_operator($Operator);
            //检测异常结果数据库操作结果验证
            $result = $Orders->abnormal($data);
            if (!$result) {
                $this->order_service->rollback();
                showmessage('异常处理失败'.get_error(),'null',0);   
            }
            if($deal_result_type == EvaluationStatus::UnqualifiedAccepted && $order_info['payment_type_id'] == \zuji\Config::MiniAlipay  && $evaluation_info['business_key'] == Business::BUSINESS_ZUJI){
                $this->zhima_order_confrimed_table =$this->load->table('order2/zhima_order_confirmed');
                //获取订单的芝麻订单编号
                $zhima_order_info = $this->zhima_order_confrimed_table->where(['order_no'=>$order_info['order_no']])->find($options);
                if(!$zhima_order_info){
                    $this->order_service->rollback();
                    showmessage('该订单没有芝麻订单号！','null',0);
                }
                $zhima = new \zhima\Withhold();
                $b =$zhima->OrderCancel([
                    'out_order_no'=>$order_info['order_no'],//商户端订单号
                    'zm_order_no'=>$zhima_order_info['zm_order_no'],//芝麻订单号
                    'remark'=>$unqualified_remark,//订单操作说明
                ]);
                if($b === false){
                    $this->order_service->rollback();
                    showmessage('操作失败','null',0);
                }
            }
            $this->order_service->commit();
            showmessage('检测异常处理成功','null',1);
        }catch (\Exception $exc){
            $this->order_service->rollback();
            Debug::error(Location::L_Evaluation, '异常处理失败:'.$exc->getMessage(), $params);
            showmessage($exc->getMessage(),'null',0);
        }

    }

    /*
     *  检测异常  寄回用户使用/换货
     */
    public function alert_delivery()
    {
        $order_id = intval($_GET['order_id']);
        $evaluation_id = intval($_GET['evaluation_id']);
        
        $evaluation_info =$this->evaluation_service->get_info($evaluation_id);
        
        if($evaluation_info['evaluation_status'] == EvaluationStatus::EvaluationUnqualified){
            if($evaluation_info['unqualified_result'] == EvaluationStatus::UnqualifiedGoUse){
                $count = $this->delivery_service->get_count(['order_id' => $order_id, 'evaluation_id' => $evaluation_id, 'business_key' => Business::BUSINESS_HUIJI]);
                if ($count > 0) {
                    showmessage('已经生成发货单了','null',0);
                }
            }
            
            if($evaluation_info['unqualified_result'] == EvaluationStatus::UnqualifiedExchange){
                $count = $this->delivery_service->get_count(['order_id' => $order_id, 'evaluation_id' => $evaluation_id, 'business_key' => Business::BUSINESS_HUANHUO]);
                if ($count > 0) {
                    showmessage('已经生成发货单了','null',0);
                }
            }
        }elseif($evaluation_info['evaluation_status'] == EvaluationStatus::EvaluationQualified){
            if($evaluation_info['business_key'] ==Business::BUSINESS_HUANHUO){
                $count = $this->delivery_service->get_count(['order_id' => $order_id, 'evaluation_id' => $evaluation_id, 'business_key' => Business::BUSINESS_HUANHUO]);
                if ($count > 0) {
                    showmessage('已经生成发货单了','null',0);
                } 
            }
        }else{
            showmessage('该检测未处理','null',0);
        }

        if (checksubmit('dosubmit')) {
            $order_id = intval($_POST['order_id']);
            $evaluation_id = intval($_POST['evaluation_id']);           
            //开启事务
            if(!$this->order_service->startTrans()){
                showmessage('服务器繁忙','null',0);
            }
            $options = ['lock' => true];

            $order_info = $this->order_service->get_order_info(['order_id' => $order_id], $options);
            $address_info = $this->order_service->get_address_info($order_info['address_id'], $options);

            $address_info['cids'] = [100000, $address_info['province_id'], $address_info['city_id'], $address_info['country_id']];
            $address_info['cid'] = $address_info['country_id'];

            $_POST['province_id'] = '';
            $_POST['city_id'] = '';
            $_POST['country_id'] = '';
            $list = $this->load->service('admin/district')->fetch_parents($_POST['district_id']);
            foreach ($list as $it) {
                if ($it['level'] == 3) {
                    $_POST['country_id'] = $it['id'];
                } elseif ($it['level'] == 2) {
                    $_POST['city_id'] = $it['id'];
                } elseif ($it['level'] == 1) {
                    $_POST['province_id'] = $it['id'];
                }
            }
            $name = $_POST['name'];
            $address = $_POST['address'];
            $mobile = $_POST['mobile'];
            $remark = $_POST['remark'];
            $province_id = intval($_POST['province_id']);
            $city_id = intval($_POST['city_id']);
            $country_id = intval($_POST['country_id']);

            $evaluation_info =$this->evaluation_service->get_info($evaluation_id,$options);
            if($evaluation_info['evaluation_status'] == EvaluationStatus::EvaluationQualified){
                   $business =intval($evaluation_info['business_key']);
            }else{
                if($evaluation_info['unqualified_result'] == EvaluationStatus::UnqualifiedGoUse){
                    $business =Business::BUSINESS_HUIJI;
                }else if($evaluation_info['unqualified_result'] == EvaluationStatus::UnqualifiedExchange){
                    $business =Business::BUSINESS_HUANHUO;
                }
            }

            //生成发货单
            $Operator = new oms\operator\Admin( $this->admin['id'], $this->admin['username'] );
           
            $Orders   = new \oms\Order($order_info);
            // if(!$Orders->allow_to_delivery()){
            //     $this->order_service->rollback();
            //     showmessage('订单不允许创建订单！','null',0);
            // }

            try{
                // 订单 观察者主题
                $OrderObservable = $Orders->get_observable();
                // 订单 观察者 状态流
                $FollowObserver = new oms\observer\FollowObserver( $OrderObservable );
              
                $data = [
                    'order_id' => $order_id,
                    'order_no' => $order_info['order_no'],
                    'business_key'  => $business,
                    'goods_id'      => $order_info['goods_id'],
                    'admin_id'      => $this->admin['id'],
                    'evaluation_id' => $evaluation_id,
                    'name' => $_POST['name'],
                    'mobile' => $_POST['mobile'],
                    'address' => $_POST['address'],
                    'remark' => $_POST['remark'],
                    'province_id' => $_POST['province_id'],
                    'city_id' => $_POST['city_id'],
                    'country_id' => $_POST['country_id'],
                ];
                
                if(!$Orders->allow_to_create_delivery()){
                    $this->order_service->rollback();
                    showmessage('创建发货单失败', 'null');
                }

                // 创建发货单
                $b = $Orders->create_delivery($data);
                if(!$b){
                    $this->order_service->rollback();
                    showmessage('创建发货单失败：'.get_error(),'null',0);
                }
                
                // 订单 观察者  日志
                $LogObserver = new oms\observer\LogObserver( $OrderObservable , "创建发货单", "创建发货单成功 备注：".$remark);
                $LogObserver->set_operator($Operator);
                

                $this->order_service->commit();
                showmessage('操作成功','null',1);
            }catch (\Exception $exc){
                $this->order_service->rollback();
                Debug::error(Location::L_Order, '取消订单失败:'.$exc->getMessage(), $data);
                showmessage($exc->getMessage(),'null',0);
            }


        } else {
            $order_info = $this->order_service->get_order_info(['order_id' => $order_id]);
            $address_info = $this->order_service->get_address_info($order_info['address_id']);

            $address_info['cids'] = [100000, $address_info['province_id'], $address_info['city_id'], $address_info['country_id']];
            $address_info['cid'] = $address_info['country_id'];

            //赋值模板
            $this->load->librarys('View')
                ->assign('order_id', $order_id)
                ->assign('evaluation_id', $evaluation_id)
                ->assign('address_info', $address_info)
                ->display('alert_evaluation_delivery');
        }

    }

    /**
     * 寄回详情
     */

    public function delivery_detail()
    {
        // 是否内嵌
        $inner = boolval($_GET['inner']);
        //
        $evaluation_id = intval(trim($_GET['evaluation_id']));
        if ($evaluation_id < 1) {
            dialog_result(0, "evaluation_id错误");
        }
        $order_id = intval(trim($_GET['order_id']));
        if ($order_id < 1) {
            dialog_result(0, "order_id错误");
        }
        $count = $this->delivery_service->get_count(['order_id' => $order_id, 'evaluation_id' => $evaluation_id]);
        if ($count == 0) {
            dialog_result(0, "暂时没有寄回详情");
            exit;
        }

        $delivery_info = $this->delivery_service->get_delivery_evaluation($evaluation_id);

        $address_info = $this->order_service->get_address_info($delivery_info['address_id']);
        
        $this->district_service = $this->load->service('admin/district');
        $province = $this->district_service->get_name($address_info['province_id']);
        $city = $this->district_service->get_name($address_info['city_id']);
        $country = $this->district_service->get_name($address_info['country_id']);
        $delivery_info['address'] = $province . ' ' . $city . ' ' . $country . ' ' . $address_info['address'];
        $delivery_info['address_remark'] = $address_info['remark'];
        $delivery_info['name'] = $address_info['name'];
        $delivery_info['mobile'] = $address_info['mobile'];
        $this->load->librarys('View')
            ->assign('inner', $inner)
            ->assign('delivery_info', $delivery_info)
            ->display('evaluation_delivery_detail');
    }

    /**
     * 解析请求中的参数生成搜索条件
     * @return array $where
     * $where = array(
     *      'evaluation_status' => '', 【可选】
     *      'unqualified_result' => '', 【可选】
     *      'business_key' => '', 【可选】
     *      'order_no' => string/array(), 【可选】
     *      'begin_time' => '', 【可选】
     *      'end_time' => '', 【可选】
     * )
     */
    private function __parse_where()
    {
        $where = [];//where条件初始化
        //-+--------------------------------------------------------------------
        // | 获取订单检测列表的检索条件
        //-+--------------------------------------------------------------------
        if ($_GET['start'] != '') {
            $_GET['begin_time'] = strtotime($_GET['start']);
        }
        if ($_GET['end'] != '') {
            $_GET['end_time'] = strtotime($_GET['end']);
        }
        $params = filter_array($_GET, [
            //验证订单检索状态类型（'':全部，1:待检测，2:检测中，3:检测完成）
            'evaluation_status' => 'required|is_numeric|zuji\order\EvaluationStatus::verifyStatus',
            //验证订单合格状态类型
            'qualified' => 'required',
            //验证检测处理结果类型（全部，0：未处理:1：入库:2：用户买断；3：用户赔付后入库）
            'unqualified_result' => 'required',
            //验证业务类型
            'business_key' => 'required|zuji\Business::verifyBusinessKey',
            //验证搜索key
            'kw_type' => 'required|is_string',
            //验证搜索value
            'keywords' => 'required',
            //开始时间
            'begin_time' => 'required|is_numeric',
            //验证是否是模糊搜索
            'end_time' => 'required|is_numeric',
        ]);
        //-+--------------------------------------------------------------------
        // | 拼接查询条件
        //-+--------------------------------------------------------------------
        // 状态存在
        if (isset($params['evaluation_status'])) {
            $where['evaluation_status'] = intval($params['evaluation_status']);
        }
        // 检测结果（合格，异常）
        if (isset($params['qualified'])) {
            $where['qualified'] = intval($params['qualified']);
        }
        // 检测异常处理结果
        if (isset($params['unqualified_result']) && $params['unqualified_result'] != "all") {
            $where['unqualified_result'] = intval($params['unqualified_result']);
        }
        // 业务类型
        if (isset($params['business_key'])) {
            $where['business_key'] = intval($params['business_key']);
        }
        // 搜索的key（字段名：目前支持order_no）
        if (isset($params['kw_type']) && $params['kw_type'] == 'order_no' && isset($params['keywords'])) {
            // 是否是模糊搜索（必须有搜索值才起作用）
            $where['order_no'] = array('LIKE', $params['keywords'] . '%');
        }
        if (isset($params['begin_time'])) {
            $where['begin_time'] = $params['begin_time'];
        }
        if (isset($params['end_time'])) {
            $where['end_time'] = $params['end_time'];
        }
        return $where;
    }

    /**
     * 解析请求中的参数生成的附加搜索条件【分页，排序】
     * @return array $where
     * $where = array(
     *      'page' => '', 【可选】int；页数(默认1页)
     *      'limit' => '', 【可选】int；每页大小（默认20条数据）
     * )
     */
    private function __parse_additional()
    {
        $additional = [];//附加条件初始化
        //-+--------------------------------------------------------------------
        // | 获取订单检测列表的检索条件
        //-+--------------------------------------------------------------------
//        var_dump($_GET);
        $params = filter_array($_GET, [
            //分页参数页码
            'page' => 'required|is_numeric',
            //分页参数每页大小
            'limit' => 'required|is_numeric',
        ]);
        if (!isset($params['page'])) {
            $params['page'] = 1;
        }
        if (!isset($params['size'])) {
            $params['limit'] = 20;
        }
        $additional = [
            'page' => intval($params['page']),
            'size' => intval($params['limit']),
        ];
//        var_dump($additional);exit;
        return $additional;
    }
}