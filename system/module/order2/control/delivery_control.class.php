<?php

use zuji\order\DeliveryStatus;
use zuji\Business;
use zuji\debug\Location;
use zuji\order\OrderStatus;
use zuji\Config;
use zuji\order\delivery\Delivery;
use zuji\order\Order;
use zuji\debug\Debug;
use zuji\email\EmailConfig;

/**
 * 发货单控制器
 * 1）
 */

hd_core::load_class('base', 'order2');

class delivery_control extends base_control
{

    /**
     * @var array 关键字搜索类型列表
     */
    protected $keywords_type_list = [
        'order_no' => '订单编号',
        'wuliu_no' => '物流编号',
        'protocol_no' => '协议号',
    ];

    /**
     * 构造方法
     */
    public function _initialize()
    {
        parent::_initialize();
        // $this->delivery_service = $this->load->service('order2/delivery');
        // $this->order_service = $this->load->service('order2/order');
        // $this->payment_service = $this->load->service('order2/payment');
        // $this->refund_service = $this->load->service('order2/refund');
        // $this->member_service = $this->load->service('member2/member');
        // $this->goods_service = $this->load->service('order2/goods');
        // $this->address_service = $this->load->service('order2/order_address');
        // $this->logistics_service = $this->load->service('order2/logistics');
        // $this->service_tpl_parcel = $this->load->service('order/order_tpl_parcel');

        //权限判断
        $promission_arr = [];
        // 打印主机协议
        $promission_arr['allow_to_prints'] = $this->check_promission_operate('order2', 'delivery', 'prints', 'protocol');
        // 发货操作
        $promission_arr['allow_to_deliver'] = $this->check_promission_operate('order2', 'delivery', 'prints', 'delivery');
        // 修改发货单操作
        $promission_arr['allow_to_edit_deliver'] = $this->check_promission_operate('order2', 'delivery', 'edit_delivery');
        // 确认收货（后台代替用户确认）
        $promission_arr['allow_to_confirm_delivery'] = $this->check_promission_operate('order2', 'delivery', 'delivery_confirmed');
        // 客户拒签 （用户拒绝签收）
        $promission_arr['allow_to_refuse_sign'] = $this->check_promission_operate('order2', 'delivery', 'delivery_refuse');
        //取消订单

        $this->promission_arr = $promission_arr;
    }


    /**
     * 发货单列表
     * 支持搜索条件【订单编号、发货状态（全部，待发货，已发货，确认收货）】
     */
    public function index()
    {
        $this->delivery_service = $this->load->service('order2/delivery');
        $this->order_service = $this->load->service('order2/order');

        $where = $this->__parse_where();

        $delivery_count = $this->delivery_service->get_count($where);
        $delivery_list = [];
        if ($delivery_count > 0) {
            $size = (isset($_GET['limit']) && is_numeric($_GET['limit'])) ? $_GET['limit'] : 20;
            $additional = [
                'page' => intval($_GET['page']),
                'size' => $size,
                'orderby' => '',
            ];
            //检测列表获取
            $delivery_list = $this->delivery_service->get_list($where, $additional);
            // 发货商品列表
            $goods_ids = array_unique(array_column($delivery_list, 'goods_id'));
            $goods_list = $this->order_service->get_goods_list(['goods_id' => $goods_ids], ['size' => count($goods_ids)]);
            mixed_merge($delivery_list, $goods_list, 'goods_id', 'goods_info');
            // 发货地址列表
            $address_ids = array_unique(array_column($delivery_list, 'address_id'));
            $address_list = $this->order_service->get_address_list(['address_id' => $address_ids], ['size' => count($address_ids)]);
            mixed_merge($delivery_list, $address_list, 'address_id', 'address_info');
            // 订单信息
            $order_ids = array_column($delivery_list, 'order_id');
            $order_list = $this->order_service->get_order_list(['order_id' => $order_ids], ['size' => count($order_ids)]);
            mixed_merge($delivery_list, $order_list, 'order_id', 'order_info');
        }
        // 获取分页信息
        $pages = $this->admin_pages($delivery_count, $additional['size']);

        //数据遍历重置
        foreach ($delivery_list as $key => &$item) {
            if(!isset($item['order_info'])){continue;}
            $item['business_name'] = Business::getName($item["business_key"]);
            $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
            $item['delivery_status_name'] = DeliveryStatus::getStatusName($item['delivery_status']);
            $item['mobile'] = $item['address_info']['mobile'];
            $item['name'] = $item['address_info']['name'];
            //$item['address'] = $item['address_info']['address'];
            $item['sku_name'] = $item['goods_info']['sku_name'];
            //省, 市, 区县,具体地址,拼接
            $this->district_service = $this->load->service('admin/district');
            $province = $this->district_service->get_name($item['address_info']['province_id']);
            $city = $this->district_service->get_name($item['address_info']['city_id']);
            $country = $this->district_service->get_name($item['address_info']['country_id']);

            $item['address'] = $province . '-' . $city . '-' . $country . '<br>' . intercept_string($item['address_info']['address']);
            $item['order_status'] = $item['order_info']['order_status'];
            $item['protocol_no'] = $item['order_info']['protocol_no'];

            $Delivery = Delivery::createDelivery($item);
            $Orders = new \oms\Order($item['order_info']);
            // 是否允许 发货 操作
            $item['allow_to_deliver'] = false;
            if ($this->promission_arr['allow_to_deliver']
                && $Delivery->allow_to_deliver() && !$Orders->order_islock()) {
                $item['allow_to_deliver'] = true;
            }
            // 是否允许 修改发货单 操作
            $item['allow_to_edit_deliver'] = false;
            if ($this->promission_arr['allow_to_edit_deliver'] && ((time()-$item['delivery_time'] ) <= 14*86400) && $item['delivery_status'] >3){
                $item['allow_to_edit_deliver'] = true;
            }
            // 是否允许 确认收货 操作
            $item['allow_to_confirm_delivery'] = false;
            if ($this->promission_arr['allow_to_confirm_delivery']
                && $Delivery->allow_to_confirm_delivery() && !$Orders->order_islock()) {
                $item['allow_to_confirm_delivery'] = true;
            }
            // 是否允许 回寄拒签 操作
            $item['allow_to_refuse_sign'] = false;
            if ($this->promission_arr['allow_to_refuse_sign']
                && $Delivery->allow_to_refuse_sign() && !$Orders->order_islock()) {
                $item['allow_to_refuse_sign'] = true;
            }

            // 商品名称拼接规格
            $specs_arr = $item['goods_info']['specs'];
            $specs_value = array_column($specs_arr,'value');
            $item['sku_name'] .= ' '.implode(' ', $specs_value);

        }

        //-+--------------------------------------------------------------------
        // | 页面模板赋值
        //-+--------------------------------------------------------------------
        $lists = array(
            'th' => array(
                'business_name' => array('title' => '业务类型', 'length' => 5),
                'order_no' => array('title' => '订单编号', 'length' => 10),
                'name' => array('length' => 8, 'title' => '收货人'),
                'mobile' => array('title' => '收货人手机号', 'length' => 8, 'style' => 'left_text'),
                'address' => array('length' => 15, 'title' => '详细地址'),
                'sku_name' => array('length' => 10, 'title' => '商品信息'),
                'protocol_no' => array('length' => 15, 'title' => '协议号'),
                'create_time' => array('length' => 10, 'title' => '下单时间', 'style' => 'date'),
                'delivery_status_name' => array('length' => 8, 'title' => '发货状态', 'style' => 'delivery_status'),
            ),
            'lists' => $delivery_list,
            'pages' => $pages,
        );
        $status_list = array_merge(['0' => '全部'], DeliveryStatus::getStatusList());
        $tab_list = [];
        foreach ($status_list as $k => $name) {
            // 目前没有这个状态 隐藏
            if ($k == DeliveryStatus::DeliveryProtocol) {
                continue;
            }

            if ($k == DeliveryStatus::DeliveryCreated) {
                continue;
            }
            $css = '';
            if ($_GET['delivery_status'] == $k) {
                $css = 'current';
            }
            $url = url('order2/delivery/index', array('delivery_status' => $k));
            $tab_list[] = '<a class="' . $css . '" href="' . $url . '">' . $name . '</a>';
        }

        $this->load->librarys('View')
            ->assign('lists', $lists)//数据信息
            ->assign('tab_list', $tab_list)
            ->assign('delivery_waiting', zuji\order\DeliveryStatus::DeliveryWaiting)//待发货状态赋值
            ->assign('delivery_protocol', zuji\order\DeliveryStatus::DeliveryProtocol)//生成租机协议状态赋值
            ->assign('delivery_send', zuji\order\DeliveryStatus::DeliverySend)//发货状态赋值
            ->assign('delivery_confirmed', zuji\order\DeliveryStatus::DeliveryConfirmed)//确认收货状态赋值
            ->assign('keywords_type_list', $this->keywords_type_list)//搜索列表
            ->assign('promission_arr', $this->promission_arr)
            ->display('delivery_index');//模板展示
    }


    /*
     * 点击 确认收货
     */
    public function delivery_confirmed()
    {
        $this->delivery_service = $this->load->service('order2/delivery');
        $this->order_service = $this->load->service('order2/order');

        $delivery_id = intval($_GET['delivery_id']);
        if ($delivery_id < 1) {
            showmessage('参数错误', 'null');
        };

        if (checksubmit('dosubmit')){

            // 开启事务
            $trans =$this->order_service->startTrans();
            if(!$trans){
                showmessage('服务器繁忙', 'null', 0, '', 'json');
            }

            $options = ['lock' => true];
            $delivery_info = $this->delivery_service->get_info($delivery_id, $options);
            $order_info = $this->order_service->get_order_info(['order_id' => $delivery_info['order_id']], $options);

            // 当前 操作员
            $admin = [
                'id' =>$this->admin['id'],
                'username' =>$this->admin['username'],
            ];
            $Operator = new oms\operator\Admin( $admin['id'], $admin['username'] );

            // 订单对象
            $Order = new oms\Order($order_info);

            // 订单 观察者主题
            $OrderObservable = $Order->get_observable();
            // 订单 观察者  订单流
            $FollowObserver = new oms\observer\FollowObserver( $OrderObservable );
            // 订单 观察者  日志
            $LogObserver = new oms\observer\LogObserver( $OrderObservable,'确认收货(平台)', '平台待用户确认收货' );
            $LogObserver->set_operator($Operator);

            if(!$Order->allow_to_sign_delivery()){
                $this->order_service->rollback();
                showmessage('禁止操作', 'null');
            }

            try {
                $b = $Order->sign_delivery( array_merge($_POST, $admin) );
                if( !$b ){
                    $this->order_service->rollback();
                    showmessage('失败：'.get_error(), 'null');
                }
            } catch (\Exception $exc) {
                $this->order_service->rollback();
                showmessage($exc->getMessage(), 'null');
            }
            $this->order_service->commit();

            $this->service_service = $this->load->service('order2/service');
            $service_info =$this->service_service->get_info_by_order_id($order_info['order_id']);
            $this->instalment_table = $this->load->table('order2/order2_instalment');
            $instalment_info =$this->instalment_table->get_order_list(['order_id'=>$order_info['order_id']]);

            //确认收货发送短信
            \zuji\sms\SendSms::confirmed_delivery([
                'mobile' => $order_info['mobile'],
                'orderNo' => $order_info['order_no'],
                'realName' => $order_info['realname'],
                'goodsName' => $order_info['goods_name'],
                'zuQi' => $order_info['zuqi'],
                'beginTime' => date("Y-m-d H:i:s",$service_info['begin_time']),
                'endTime' => date("Y-m-d H:i:s",$service_info['end_time']),
                'zuJin' => $order_info['zujin'],
                'createTime' => $instalment_info[0]['term'],
            ]);
            showmessage('操作成功', 'null', 1, '', 'json');

        }else{

            $delivery_info = $this->delivery_service->get_info($delivery_id);
            $order_info = $this->order_service->get_order_info(['order_id' => $delivery_info['order_id']]);
            if ($order_info['order_status'] != OrderStatus::OrderCreated) {
                showmessage('订单已关闭或未生效', 'null');
            }

            // 发货单对象
            $Delivery = Delivery::createDelivery($delivery_info);
            if (!$Delivery->allow_to_confirm_delivery()) {
                showmessage('禁止操作', 'null');
            }
        }

        $this->load->librarys('View')
            ->assign('delivery_id', $delivery_id)
            ->display('alert_delivery_shouhuo');

    }

    /*
     * 点击客户拒签按钮
     */
    public function delivery_refuse()
    {
        $this->delivery_service = $this->load->service('order2/delivery');
        $this->order_service = $this->load->service('order2/order');

        $delivery_id = intval($_GET['delivery_id']);
        if ($delivery_id < 1) {
            showmessage('参数错误', 'null', 0, '', 'json');
        }

        if (checksubmit('dosubmit')) {
            // 开启事务
            if(!$this->order_service->startTrans()){
                showmessage('服务器繁忙', 'null', 0, '', 'json');
            }

            $options = ['lock' => true];
            $delivery_info = $this->delivery_service->get_info($delivery_id, $options);
            $order_info = $this->order_service->get_order_info(['order_id' => $delivery_info['order_id']], $options);

            // 当前 操作员
            $admin = [
                'id' =>$this->admin['id'],
                'username' =>$this->admin['username'],
            ];
            $Operator = new oms\operator\Admin( $admin['id'], $admin['username'] );

            // 订单对象
            $Order = new oms\Order($order_info);

            // 订单 观察者主题
            $OrderObservable = $Order->get_observable();
            // 订单 观察者  订单流
            $FollowObserver = new oms\observer\FollowObserver( $OrderObservable );
            // 订单 观察者  日志
            $LogObserver = new oms\observer\LogObserver( $OrderObservable,'确认收货(平台)', '平台待用户确认收货' );
            $LogObserver->set_operator($Operator);

            if(!$Order->allow_to_refused()){
                $this->order_service->rollback();
                showmessage('禁止操作', 'null');
            }

            try {
                $b = $Order->refused( array_merge($_POST, $admin) );
                if( !$b ){
                    $this->order_service->rollback();
                    showmessage('操作失败：'.get_error(), 'null', 0, '', 'json');
                }
            } catch (\Exception $exc) {
                $this->order_service->rollback();
                showmessage($exc->getMessage(), 'null', 0, '', 'json');
            }
            $this->order_service->commit();

            showmessage('操作成功', 'null', 1, '', 'json');
        }else{


            $delivery_info = $this->delivery_service->get_info($delivery_id);

            // 发货单对象
            $Delivery = Delivery::createDelivery($delivery_info);

            // 是否允许 客户拒签 操作
            if (!($this->promission_arr['allow_to_refuse_sign'] && $Delivery->allow_to_refuse_sign())) {
                showmessage('禁止操作', 'null', 0, '', 'json');
            }

            // 订单
            $order_info = $this->order_service->get_order_info(['order_id' => $delivery_info['order_id']]);
            if ($order_info['order_status'] != OrderStatus::OrderCreated) {
                showmessage('订单已关闭或取消', 'null', 0, '', 'json');
            }
        }

        $this->load->librarys('View')
            ->assign('delivery_id', $delivery_id)
            ->display('alert_delivery_refuse');

    }


    /**
     * 修改发货页面=》弹框
     */
    public function edit_delivery()
    {
        $this->delivery_service = $this->load->service('order2/delivery');
        $this->order_service = $this->load->service('order2/order');
        $this->logistics_service = $this->load->service('order2/logistics');

        $params = filter_array($_GET, [
            'order_id' => 'required|is_id',
            'delivery_id' => 'required|is_id',
        ]);
        //必要的参数进行验证
        if (!isset($params['order_id']) || !isset($params['delivery_id'])) {
            showmessage("参数错误","null");
        }
        $delivery_id = intval($params['delivery_id']);
        $order_id = intval($params['order_id']);
        //查询订单是否有效
        $order_info = $this->order_service->get_order_info(['order_id' => $order_id]);
        $Order =Order::createOrder($order_info);
        if(!$Order->is_open()){
            // showmessage("订单未生效","null");
        }
        // 获取发货单
        $delivery_info = $this->delivery_service->get_info($delivery_id);
        if (!$delivery_info) {
            showmessage('发货单查询失败',"null");
        }
        // 发货单对象
        $Delivery = Delivery::createDelivery($delivery_info);
        if ($Delivery->allow_to_create_protocol() && empty($order_info['protocol_no'])) {
            showmessage("请先生成租机协议","null");
        }
        $sqlmap = array();
        $sqlmap['enabled'] = 0;
        $logistics = $this->logistics_service->getField('id,name', $sqlmap);
        $goods_info = $this->order_service->get_goods_info($order_info['goods_id']);
        $goods_spu_service = $this->load->service('goods2/goods_spu');
        $spu_row = $goods_spu_service->api_get_info($goods_info['spu_id'], 'brand_id');
        $goods_name = $order_info['goods_name'] . "," . $order_info['chengse'] . "成新," . $order_info['specs'];

        $template = '';
        $goods_xinxi = "";
        if($delivery_info['business_key'] == Business::BUSINESS_ZUJI){
            $template = 'delivery_alert_send';
        }elseif ($delivery_info['business_key'] == Business::BUSINESS_HUIJI){
            $template = 'delivery_alert_send_huiji';
        }elseif ($delivery_info['business_key'] ==Business::BUSINESS_HUANHUO){
            $template = 'delivery_alert_send';
            $goods_xinxi="序列号：".$goods_info['serial_number']."<br> IMEI1:".$goods_info['imei1']."<br> IMEI2:".$goods_info['imei2']."<br> IMEI3:".$goods_info['imei3'];
        }
        $this->load->librarys('View')
            ->assign('logistics', $logistics)
            ->assign('goods_name', $goods_name)
            ->assign('delivery_id', $delivery_id)
            ->assign('delivery_info', $delivery_info)
            ->assign('order_id', $order_id)
            ->assign('goods_id', $order_info['goods_id'])
            ->assign('protocol_no', $goods_info['protocol_no'])
            ->assign('brand_id', $spu_row['brand_id'])
            ->assign('goods_info', $goods_info)
            ->assign('goods_xinxi', $goods_xinxi)
            ->assign('url','order2/delivery/send_edit')
            ->display($template);
    }
    /*
     * 修改发货=》完成操作
     */
    public function send_edit()
    {
        $this->delivery_service = $this->load->service('order2/delivery');
        $this->order_service = $this->load->service('order2/order');
        $delivery_table =$this->load->table('order2/order2_delivery');
        $goods_table =$this->load->table('order2/order2_goods');
        $this->service_order_log = $this->load->service('order2/order_log');

        $delivery_id = intval(trim($_POST['delivery_id']));
        $goods_id = intval(trim($_POST['goods_id']));

        // 开启事务
        $trans =$this->order_service->startTrans();
        if(!$trans){
            showmessage('服务器繁忙', 'null', 0, '', 'json');
        }
        $options = ['lock' => true];
        $delivery_info = $this->delivery_service->get_info($delivery_id, $options);
        $order_info = $this->order_service->get_order_info(['order_id' => $delivery_info['order_id']], $options);
        $delivery_data = array(
            'wuliu_channel_id' => intval($_POST['logistics_id']),//【必须】int 物流渠道ID
            'wuliu_no' => $_POST['logistics_sn'],
            'delivery_remark' => $_POST['delivery_remark'],
            'admin_id' => intval($this->admin['id']),         //【可选】后台管理员ID
            'update_time'=>time(),
        );
        $b =$delivery_table->where(['delivery_id'=>$delivery_id])->save($delivery_data);
        if(!$b){
            $this->order_service->rollback();
            showmessage('更新发货单失败',"null",0);
        }
        $goods_data = array(
            'imei1' => $_POST['imei1'],           //【必须】string Imei
            'imei2' => $_POST['imei2'],           //【必须】string Imei
            'imei3' => $_POST['imei3'],           //【必须】string Imei
            'serial_number' => $_POST['serial_number'],   //【可选】序列号
            'update_time'=>time(),
        );
        $b =$goods_table->where(['goods_id'=>$goods_id])->save($goods_data);
        if(!$b){
            $this->order_service->rollback();
            showmessage('更新商品信息失败',"null",0);
        }
        //生成日志开始
        $log = [
            'order_no' => $order_info['order_no'],
            'action' => "修改发货信息",
            'operator_id' => intval($this->admin['id']),         //【可选】后台管理员ID
            'operator_name' => intval($this->admin['username']),         //【可选】后台管理员ID
            'operator_type' => 1,
            'msg' => "修改发货信息",
        ];
        $add_log = $this->service_order_log->add($log);
        if (!$add_log) {
            $this->order_service->rollback();
            showmessage('插入日志失败',"null",0);
        }
        $this->order_service->commit();
        showmessage('修改成功',"null",1);
    }
    /**
     * 发货页面=》弹框
     */
    public function send_alert()
    {
        $this->delivery_service = $this->load->service('order2/delivery');
        $this->order_service = $this->load->service('order2/order');
        $this->logistics_service = $this->load->service('order2/logistics');

        $params = filter_array($_GET, [
            'order_id' => 'required|is_id',
            'delivery_id' => 'required|is_id',
        ]);
        //必要的参数进行验证
        if (!isset($params['order_id']) || !isset($params['delivery_id'])) {
            showmessage("参数错误","null");
        }
        $delivery_id = intval($params['delivery_id']);
        $order_id = intval($params['order_id']);
        //查询订单是否有效
        $order_info = $this->order_service->get_order_info(['order_id' => $order_id]);
        $Order =Order::createOrder($order_info);
        if(!$Order->is_open()){
            showmessage("订单未生效","null");
        }
        // 获取发货单
        $delivery_info = $this->delivery_service->get_info($delivery_id);
        if (!$delivery_info) {
            showmessage('发货单查询失败',"null");
        }
        // 发货单对象
        $Delivery = Delivery::createDelivery($delivery_info);
        if ($Delivery->allow_to_create_protocol() && empty($order_info['protocol_no'])) {
            showmessage("请先生成租机协议","null");
        }
        if (!$Delivery->allow_to_deliver()) {
            showmessage('已发货',"null");
        }

        $sqlmap = array();
        $sqlmap['enabled'] = 0;
        $logistics = $this->logistics_service->getField('id,name', $sqlmap);
        $goods_info = $this->order_service->get_goods_info($order_info['goods_id']);
        $goods_spu_service = $this->load->service('goods2/goods_spu');
        $spu_row = $goods_spu_service->api_get_info($goods_info['spu_id'], 'brand_id');
        $goods_name = $order_info['goods_name'] . "," . $order_info['chengse'] . "成新," . $order_info['specs'];

        $template = '';
        $goods_xinxi = "";
        if($delivery_info['business_key'] == Business::BUSINESS_ZUJI){
            $template = 'delivery_alert_send';
        }elseif ($delivery_info['business_key'] == Business::BUSINESS_HUIJI){
            $template = 'delivery_alert_send_huiji';
        }elseif ($delivery_info['business_key'] ==Business::BUSINESS_HUANHUO){
            $template = 'delivery_alert_send';
            $goods_xinxi="序列号：".$goods_info['serial_number']."<br> IMEI1:".$goods_info['imei1']."<br> IMEI2:".$goods_info['imei2']."<br> IMEI3:".$goods_info['imei3'];
        }
        $this->load->librarys('View')
            ->assign('logistics', $logistics)
            ->assign('goods_name', $goods_name)
            ->assign('delivery_id', $delivery_id)
            ->assign('delivery_info', $delivery_info)
            ->assign('order_id', $order_id)
            ->assign('goods_id', $order_info['goods_id'])
            ->assign('protocol_no', $goods_info['protocol_no'])
            ->assign('brand_id', $spu_row['brand_id'])
            ->assign('goods_info', $goods_info)
            ->assign('goods_xinxi', $goods_xinxi)
            ->assign('url','order2/delivery/send_complete')
            ->display($template);
    }

    /*
     * 发货=》完成操作
     */
    public function send_complete()
    {
        $this->delivery_service = $this->load->service('order2/delivery');
        $this->order_service = $this->load->service('order2/order');

        $delivery_id = intval(trim($_POST['delivery_id']));

        // 开启事务
        $trans =$this->order_service->startTrans();
        if(!$trans){
            showmessage('服务器繁忙', 'null', 0, '', 'json');
        }

        $options = ['lock' => true];
        $delivery_info = $this->delivery_service->get_info($delivery_id, $options);
        $order_info = $this->order_service->get_order_info(['order_id' => $delivery_info['order_id']],['goods_info' => true,'address_info'=>true], $options);

        // 当前 操作员
        $admin = [
            'id' =>$this->admin['id'],
            'username' =>$this->admin['username'],
        ];
        $Operator = new oms\operator\Admin( $admin['id'], $admin['username'] );

        // 订单对象
        $Order = new oms\Order($order_info);

        // 订单 观察者主题
        $OrderObservable = $Order->get_observable();
        // 订单 观察者  订单流
        $FollowObserver = new oms\observer\FollowObserver( $OrderObservable );
        // 订单 观察者  日志
        if($delivery_info['business_key'] ==Business::BUSINESS_ZUJI){
            $msg="租机发货";
        }else if($delivery_info['business_key'] ==Business::BUSINESS_HUIJI){
            $msg="回寄发货";
        }else if($delivery_info['business_key'] ==Business::BUSINESS_HUANHUO){
            $xinxi = "序列号：".$_POST['serial_number']."<br> IMEI1:".$_POST['imei1']."<br> IMEI2:".$_POST['imei2']."<br> IMEI3:".$_POST['imei3'];
            $msg="换货发货<br> 上次发货信息为：".$_POST['goods_xinxi']."<br>替换设备信息为：<br>".$xinxi;
        }else{
            $msg="发货成功";
        }
        $LogObserver = new oms\observer\LogObserver( $OrderObservable,'发货(平台)', $msg );
        $LogObserver->set_operator($Operator);


        if(!$Order->allow_to_delivery()){
            $this->order_service->rollback();
            showmessage('禁止操作', 'null');
        }
        if($delivery_info['business_key'] ==Business::BUSINESS_HUIJI){
            $data =[
                'delivery_id' =>$delivery_id,     //【必须】int 发货单ID
                'order_id'=>intval($order_info['order_id']),         //【必须】int 订单ID
                'logistics_id' =>intval($_POST['logistics_id']),//【必须】int 物流渠道ID
                'logistics_sn' =>$_POST['logistics_sn'],        //【必须】string 物流编号
                'delivery_remark' =>$_POST['delivery_remark'], //【必须】string 发货备注
                'admin_id'=>intval($this->admin['id']),         //【可选】后台管理员ID
                'goods_id'=>intval($_POST['goods_id']),         //【必须】商品ID
            ];
        }else{
            $data =[
                'delivery_id' =>$delivery_id,     //【必须】int 发货单ID
                'order_id'=>intval($order_info['order_id']),         //【必须】int 订单ID
                'wuliu_channel_id' =>intval($_POST['logistics_id']),//【必须】int 物流渠道ID
                'wuliu_no' =>$_POST['logistics_sn'],        //【必须】string 物流编号
                'delivery_remark' =>$_POST['delivery_remark'], //【必须】string 发货备注
                'imei1' =>$_POST['imei1'],           //【必须】string Imei
                'imei2' =>$_POST['imei2'],           //【可选】string Imei
                'imei3' =>$_POST['imei3'],           //【可选】string Imei
                'serial_number' =>$_POST['serial_number'],   //【可选】序列号
                'admin_id'=>intval($this->admin['id']),         //【可选】后台管理员ID
                'goods_id'=>intval($_POST['goods_id']),         //【必须】商品ID
            ];

            if($delivery_info['business_key'] ==Business::BUSINESS_ZUJI){
                //省, 市, 区县,具体地址,拼接
                $this->district_service = $this->load->service('admin/district');
                $order_info['address_info']['address'] = $this->district_service->get_address_detail($order_info['address_info']);

                $this->contract_service = $this->load->service('contract/contract');
                if ($order_info['goods_info']['chengse'] < 100) {
                    $chengse = '非成新';
                } else {
                    $chengse = '全新';
                }
                $contrach_data =[
                    'order_no'=>$order_info['order_no'],
                    'user_id'=>$order_info['user_id'],
                    'chengse'=>$chengse,
                    'machine_no'=>$order_info['goods_name'].' '.$order_info['goods_info']['spec_value_list'],
                    'imei'=>$_POST['imei1']. ' ' .$_POST['imei2']. ' ' .$_POST['imei3']. ' ' .$_POST['serial_number'],
                    'zuqi'=>$order_info['zuqi'],
                    'zujin'=>$order_info['amount']-$order_info['yiwaixian'],
                    'mianyajin'=>$order_info['yajin'],
                    'yiwaixian'=>$order_info['yiwaixian'],
                    'name' => $order_info["realname"],
                    'id_cards' => $order_info["cert_no"],
                    'mobile' => $order_info["mobile"],
                    'address'=>$order_info['address_info']['address'],
                    'delivery_time'=>date("Y-m-d H:i:s")
                ];

                $this->order_goods = $this->load->table("order2/order2_goods");
                $goods = $this->order_goods->where(['goods_id'=>$order_info['goods_id']])->field("spu_id,sku_id")->find();
                //获取市场价
                $this->sku = $this->load->table("goods2/goods_sku");
                $sku_info = $this->sku->where(['sku_id'=>$goods['sku_id']])->field("market_price")->find();
                $contrach_data['market_price'] = $sku_info['market_price'];
                //获取商品合同id
                $this->spu = $this->load->table("goods2/goods_spu");
                $spu_info = $this->spu->where(['id'=>$goods['spu_id']])->field("contract_id")->find();
                $this->contract_service->contract_sign($spu_info['contract_id'],$contrach_data);
            }

        }
        try {
            $b = $Order->delivery($data);
            if( !$b ){
                $this->order_service->rollback();
                showmessage('失败：'.get_error(), 'null');
            }
        } catch (\Exception $exc) {
            $this->order_service->rollback();
            showmessage($exc->getMessage(), 'null');
        }
        //发货成功后 发送短信。
        $result = ['auth_token' => $this->auth_token,];
        $sms = new \zuji\sms\HsbSms();
        $b = $sms->send_sm($order_info['mobile'], 'SMS_113460968', [
            'realName'=>$order_info['realname'],
            'orderNo' => $order_info['order_no'],
            'logisticsNo' => $_POST['logistics_sn'],
        ],$order_info['order_no']);
        if (!$b) {
            showmessage('短信接口错误',"null");
        }

        $this->order_service->commit();
        showmessage('发货成功',"null",1);
    }

    /**
     * 解析请求中的参数生成搜索条件
     * @return array $where
     * $where = array(
     *      'delivery_status' => '', 【可选】
     *      'order_no' => string/array(), 【可选】
     *      'begin_time' => '', 【可选】
     *      'end_time' => '', 【可选】
     * )
     */
    private function __parse_where()
    {
        $where = [];//where条件初始化

        if (isset($_GET['delivery_status']) && $_GET['delivery_status'] > 0) {
            $where['delivery_status'] = intval($_GET['delivery_status']);
        }
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
            if ($_GET['kw_type'] == 'protocol_no') {
                $where['protocol_no'] = $_GET['keywords'];
            } elseif ($_GET['kw_type'] == 'wuliu_no') {
                $where['wuliu_no'] = $_GET['keywords'];
            } else {
                $where['order_no'] = $_GET['keywords'];
            }
        }

        return $where;
    }

    /**
     *发货单详情
     */
    public function detail()
    {
        $this->delivery_service = $this->load->service('order2/delivery');
        $this->order_service = $this->load->service('order2/order');
        $this->logistics_service = $this->load->service('order2/logistics');

        // 是否内嵌
        $inner = boolval($_GET['inner']);
        //
        $delivery_id = intval(trim($_GET['delivery_id']));
        if ($delivery_id < 1) {
            showmessage(lang('_error_action_'), "", 0);
        }
        $delivery_info = $this->delivery_service->get_info($delivery_id);

        $address_info = $this->order_service->get_address_info($delivery_info['address_id']);

        $order_info = $this->order_service->get_order_info(['order_id' => $delivery_info['order_id']], ['goods_info' => true, 'address_info' => true]);

        if ($delivery_info['wuliu_channel_id'] > 0) {
            $delivery_info['wuliu_channel'] = $this->logistics_service->getField('name', ['id' => $delivery_info['wuliu_channel_id']]);
        }

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
            ->assign('order_info', $order_info)
            ->display('delivery_detail');
    }
    /*
     * 代扣预授权取消发货单
     */
    public function cancel_withhode_delivery()
    {
        $this->order_service = $this->load->service('order2/order');
        // 表单提交处理
        if (checksubmit('dosubmit')) {
            // 开启事务
            if(!$this->order_service->startTrans()){
                showmessage('服务器繁忙', 'null', 0, '', 'json');
            }
            $order_id =intval($_POST['order_id']);
            $delivery_id=intval($_POST['delivery_id']);
            $should_amount =$_POST['should_amount'];
            $should_remark =$_POST['should_remark'];
            //查询订单
            $options = ['lock' => true];
            $order_info = $this->order_service->get_order_info(['order_id' => $order_id], $options);

            $this->fund_auth_table = $this->load->table('payment/payment_fund_auth');
            $auth_info = $this->fund_auth_table->where([
                'order_id' => $order_id,
            ])->find(['lock'=>true]);
            $order_info['jiedong'] = \zuji\order\Order::priceFormat($auth_info['amount'] - $auth_info['unfreeze_amount'] - $auth_info['pay_amount']);
            if ($should_amount > $order_info['jiedong']) {
                $this->order_service->rollback();
                showmessage("解冻金额超出限制",'null',0);
            }
            // 当前 操作员
            $admin = [
                'id' =>$this->admin['id'],
                'username' =>$this->admin['username'],
            ];
            $Operator = new oms\operator\Admin( $admin['id'], $admin['username'] );
            // 订单对象
            $Order = new oms\Order($order_info);
            // 订单 观察者主题
            $OrderObservable = $Order->get_observable();
            $data=[
                'order_id'=>$order_id,
                'delivery_id'=>$delivery_id,
            ];
            if(!$Order->allow_to_remove_authorize()){
                $this->order_service->rollback();
                showmessage('该订单不允许解除资金预授权！','null',0);
            }
            if(!$Order->allow_to_cancel_order()){
                $this->order_service->rollback();
                showmessage('该订单不允许取消！','null',0);
            }
            try {
                // 订单 观察者  日志
                $LogObserver = new oms\observer\LogObserver( $OrderObservable,'取消发货', $should_remark );
                $LogObserver->set_operator($Operator);
                $b=$Order->quxiao_delivery($data);
                if(!$b){
                    $this->order_service->rollback();
                    showmessage('取消发货失败：'.get_error(), 'null');
                }

                // 订单 观察者 状态流
                $FollowObserver = new oms\observer\FollowObserver( $OrderObservable );
                // 订单 观察者  日志
                $LogObserver = new oms\observer\LogObserver( $OrderObservable , "解除资金预授权", $should_remark);
                $LogObserver->set_operator($Operator);
                $data =[
                    'should_amount'=>$should_amount*100,
                ];

                $b =$Order->remove_authorize($data);
                if(!$b){
                    $this->order_service->rollback();
                    showmessage('解除资金预授权失败:'.get_error(),'null',0);
                }

                // 取消订单
                // 订单 观察者  日志
                $LogObserver = new oms\observer\LogObserver( $OrderObservable , "取消订单", $should_remark);
                $LogObserver->set_operator($Operator);

                $b =$Order->cancel_order(['order_id'=>$order_id]);
                if(!$b){
                    $this->order_service->rollback();
                    showmessage('取消订单失败'.get_error(),'null',0);
                }

                $this->order_service->commit();
                showmessage('操作成功','null',1);

            } catch (\Exception $exc) {
                $this->order_service->rollback();
                showmessage($exc->getMessage(), 'null');
            }

        }
        $params = filter_array($_GET, [
            'order_id' => 'required',
            'delivery_id' => 'required',
        ]);
        $order_id = intval($_GET['order_id']);
        $delivery_id = intval($_GET['delivery_id']);
        // 参数判断
        if ($delivery_id <1 || $order_id <1) {
            showmessage("参数错误",'null',0);
        }
        $order_info = $this->order_service->get_order_info(['order_id' => $order_id]);
        $this->fund_auth_table = $this->load->table('payment/payment_fund_auth');
        $auth_info = $this->fund_auth_table->where([
            'order_id' => $order_id,
        ])->find();
        $jiedong =$auth_info['amount'] - $auth_info['unfreeze_amount'] - $auth_info['pay_amount'];
        $order_info['jiedong'] = Order::priceFormat($jiedong);
        $payment_info =[];
        $msg ='确认取消发货？解除代扣预授权取消订单？';
        $url ="order2/delivery/cancel_withhode_delivery";
        $this->load->librarys('View')
            ->assign('order_id', $params['order_id'])
            ->assign('delivery_id', $params['delivery_id'])
            ->assign('payment_info', $payment_info)
            ->assign('msg', $msg)
            ->assign('url',$url)
            ->assign('order_info',$order_info)
            ->display('alert_delivery_cancel');

    }
 /*
  * 支付宝小程序取消发货单
  */
    public function cancel_minialipay_delivery()
    {
        $this->order_service = $this->load->service('order2/order');
        $order_id = intval($_GET['order_id']);
        $delivery_id = intval($_GET['delivery_id']);
        // 参数判断
        if ($delivery_id <1 || $order_id <1) {
            showmessage("参数错误",'null',0);
        }

        // 表单提交处理
        if (checksubmit('dosubmit')) {
            // 开启事务
            if(!$this->order_service->startTrans()){
                showmessage('服务器繁忙', 'null', 0, '', 'json');
            }
            $order_id =intval($_POST['order_id']);
            $remark =$_POST['remark'];
            $delivery_id=intval($_POST['delivery_id']);
            //查询订单
            $options = ['lock' => true];
            $order_info = $this->order_service->get_order_info(['order_id' => $order_id], $options);

            // 当前 操作员
            $admin = [
                'id' =>$this->admin['id'],
                'username' =>$this->admin['username'],
            ];
            $Operator = new oms\operator\Admin( $admin['id'], $admin['username'] );

            // 订单对象
            $Order = new oms\Order($order_info);

            // 订单 观察者主题
            $OrderObservable = $Order->get_observable();
            // 订单 观察者  订单流
            $FollowObserver = new oms\observer\FollowObserver( $OrderObservable );
            // 订单 观察者  日志
            $LogObserver = new oms\observer\LogObserver( $OrderObservable,'取消发货', $remark );
            $LogObserver->set_operator($Operator);

            if(!$Order->allow_to_cancel_delivery()){
                $this->order_service->rollback();
                showmessage('禁止操作', 'null');
            }
            $data=[
                'order_id'=>$order_id,
                'delivery_id'=>$delivery_id,
            ];
            try {
                $b = $Order->quxiao_delivery($data);
                if( !$b ){
                    $this->order_service->rollback();
                    showmessage('失败：'.get_error(), 'null');
                }
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

            } catch (\Exception $exc) {
                $this->order_service->rollback();
                showmessage($exc->getMessage(), 'null');
            }

            $this->order_service->commit();
            showmessage("操作成功",'null',1);

        }

        //查询订单
        $order_info = $this->order_service->get_order_info(['order_id' => $order_id]);
        // 订单对象
        $Order = new oms\Order($order_info);
        if(!$Order->allow_to_cancel_delivery()){
            showmessage('不允许取消发货', 'null');
        }
        $msg ='确认取消发货？';
        $url="order2/delivery/cancel_minialipay_delivery";
        $this->load->librarys('View')
            ->assign('order_id', $order_id)
            ->assign('delivery_id', $delivery_id)
            ->assign('msg', $msg)
            ->assign('url',$url)
            ->display('alert_cancel');

    }

    /*
     * 取消发货单 并生成退款单
     */
    public function cancel_delivery()
    {
        $this->order_service = $this->load->service('order2/order');
        $params = filter_array($_GET, [
            'order_id' => 'required',
            'delivery_id' => 'required',
        ]);
        $order_id = intval($_GET['order_id']);
        $delivery_id = intval($_GET['delivery_id']);
        // 参数判断
        if ($delivery_id <1 || $order_id <1) {
            showmessage("参数错误",'null',0);
        }

        // 表单提交处理
        if (checksubmit('dosubmit')) {

            // 开启事务
            if(!$this->order_service->startTrans()){
                showmessage('服务器繁忙', 'null', 0, '', 'json');
            }
            $order_id =intval($_POST['order_id']);
            $should_amount =$_POST['should_amount'];
            $should_remark =$_POST['should_remark'];
            $delivery_id=intval($_POST['delivery_id']);

            //查询订单
            $options = ['lock' => true];
            $order_info = $this->order_service->get_order_info(['order_id' => $order_id], $options);
            $payment_service = $this->load->service('order2/payment');
            $payment_info = $payment_service->get_info($order_info['payment_id'],$options);

            // 当前 操作员
            $admin = [
                'id' =>$this->admin['id'],
                'username' =>$this->admin['username'],
            ];
            $Operator = new oms\operator\Admin( $admin['id'], $admin['username'] );

            // 订单对象
            $Order = new oms\Order($order_info);

            // 订单 观察者主题
            $OrderObservable = $Order->get_observable();
            // 订单 观察者  订单流
            $FollowObserver = new oms\observer\FollowObserver( $OrderObservable );
            // 订单 观察者  日志
            $LogObserver = new oms\observer\LogObserver( $OrderObservable,'申请退款', $should_remark );
            $LogObserver->set_operator($Operator);


            if(!$Order->allow_to_cancel_delivery()){
                $this->order_service->rollback();
                showmessage('禁止操作', 'null');
            }
            // 应退金额（元），不得大于 支付金额
            $should_amount = floatval($_POST['should_amount']);
            if ($should_amount > $payment_info['payment_amount']) {
                $this->order_service->rollback();
                showmessage("退款金额超出限制",'null',0);
            }

            $data=[
                'order_id'=>$order_id,
                'payment_id'=>intval($payment_info['payment_id']),
                'should_amount'=>$should_amount,
                'should_remark'=>$should_remark,
                'delivery_id'=>$delivery_id,
            ];
            try {
                $b = $Order->cancel_delivery($data);
                if( !$b ){
                    $this->order_service->rollback();
                    showmessage('失败：'.get_error(), 'null');
                }

                $this->order_service->commit();
            } catch (\Exception $exc) {
                $this->order_service->rollback();
                showmessage($exc->getMessage(), 'null');
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
            showmessage("操作成功",'null',1);

        }else{
            //查询订单
            $order_info = $this->order_service->get_order_info(['order_id' => $order_id]);
            if ($order_info['payment_status'] != \zuji\order\PaymentStatus::PaymentSuccessful) {
                showmessage("订单未完成支付",'null',0);
            }

            //支付单查询
            $payment_service = $this->load->service('order2/payment');
            $payment_info = $payment_service->get_info($order_info['payment_id']);
            if (!$payment_info) {
                showmessage("支付单查询失败",'null',0);
            }
        }

        $msg ='确认取消发货？请填写退款金额';
        $url="order2/delivery/cancel_delivery";
        $this->load->librarys('View')
            ->assign('order_id', $params['order_id'])
            ->assign('delivery_id', $params['delivery_id'])
            ->assign('payment_info', $payment_info)
            ->assign('msg', $msg)
            ->assign('url',$url)
            ->assign('order_info',$order_info)
            ->display('alert_delivery_cancel');

    }

    /*
     * 创建发货单=》并展示弹窗
     */
    public function create()
    {
        $this->order_service = $this->load->service('order2/order');
        // 表单提交处理
        if (checksubmit('dosubmit')) {
            //获取参数
            $order_id =intval($_POST['order_id']);
            $create_remark =$_POST['create_remark'];

            //开启事务
            $trans =$this->order_service->startTrans();
            if(!$trans){
                showmessage('服务器繁忙', 'null', 0, '', 'json');
            }

            //查询订单
            $options = ['lock' => true];
            $order_info = $this->order_service->get_order_info(['order_id' => $order_id], $options);

            // 当前 操作员
            $admin = [
                'id' =>$this->admin['id'],
                'username' =>$this->admin['username'],
            ];
            $Operator = new oms\operator\Admin( $admin['id'], $admin['username'] );

            // 订单对象
            $Order = new oms\Order($order_info);

            // 订单 观察者主题
            $OrderObservable = $Order->get_observable();
            // 订单 观察者  订单流
            $FollowObserver = new oms\observer\FollowObserver( $OrderObservable );
            // 订单 观察者  日志
            $LogObserver = new oms\observer\LogObserver( $OrderObservable,'确认订单', '创建发货单备注：'.$create_remark );
            $LogObserver->set_operator($Operator);


            if(!$Order->allow_to_confirm_order()){
                $this->order_service->rollback();
                showmessage('禁止操作', 'null');
            }
            try {
                $b = $Order->confirm_order();
                if( !$b ){
                    $this->order_service->rollback();
                    showmessage('失败：'.get_error(), 'null');
                }
            } catch (\Exception $exc) {
                $this->order_service->rollback();
                showmessage($exc->getMessage(), 'null');
            }
            //发送邮件 -----begin
            $data =[
                'subject'=>'确认订单',
                'body'=>'订单编号：'.$order_info['order_no']." 已确认租用意向，请发货。",
                'address'=>[
                    ['address' => EmailConfig::Goods_Username]
                ],
            ];
            $send =EmailConfig::system_send_email($data);
            if(!$send){
                Debug::error(Location::L_Delivery, "发送邮件失败", $data);
            }
            //发送邮件------end

            $this->order_service->commit();
            showmessage("确认成功，已邮件通知收发货人员","null",1);

        }else{
            $order_id =intval($_GET['order_id']);
            if($order_id <1) showmessage("参数错误","null");
            $order_info = $this->order_service->get_order_info(['order_id' => $order_id]);
            // 订单对象
            $Order = new oms\Order($order_info);

            if(!$Order->allow_to_confirm_order()){
                showmessage("该订单不允许确认订单","null");
            }

            $this->load->librarys('View')
                ->assign('order_id', $order_id)
                ->display('alert_delivery_create');
        }


    }

    /**
     * 打印租机协议
     */
    public function prints()
    {
        $this->delivery_service = $this->load->service('order2/delivery');
        $this->order_service = $this->load->service('order2/order');
        $this->service_tpl_parcel = $this->load->service('order/order_tpl_parcel');
        $this->member_service = $this->load->service('member2/member');
        //-+--------------------------------------------------------------------
        // | 参数获取和验证过滤
        //-+--------------------------------------------------------------------
        $params = filter_array($_GET, [
            'order_id' => 'required|is_id',
            'delivery_id' => 'required|is_id',
        ]);
        //必要的参数进行验证
        if (!isset($params['order_id']) || !isset($params['delivery_id'])) {
            showmessage('参数错误',"null");
        }
        $action = isset($_GET['action']) ? $_GET['action'] : '';
        $order_id = intval($params['order_id']);
        $delivery_id = intval($params['delivery_id']);
        $info = $this->service_tpl_parcel->get_tpl_parcel_by_id(2);

        $options = [];

        $protocol_no = '';

        if(empty($action)){
            //生成租机协议编号
            $protocol_no = \zuji\Business::create_business_no();
            // 开启事务
            if(!$this->order_service->startTrans()){
                showmessage('服务器繁忙', 'null', 0, '', 'json');
            }
            $options = ['lock' => true];
        }

        //订单信息
        $order_info = $this->order_service->get_order_info(['order_id' => $order_id], ['goods_info' => true, 'address_info' => true], $options);
        if (!$order_info) {
            showmessage("订单不存在","null");
        }

        //是否生成的租机协议
        if (empty($order_info['protocol_no']) && empty($action)) {
            // 获取发货单
            $delivery_info = $this->delivery_service->get_info($delivery_id, $options);
            if (!$delivery_info) {
                showmessage('发货单查询失败',"null");
            }

            // 发货单对象
            $Delivery = Delivery::createDelivery($delivery_info);
            //判断是否可生成协议
            if (!$Delivery->allow_to_create_protocol()) {
                showmessage('待发货和生成租机协议状态才可发货',"null");
            }

            //生成租机协议
            //写入数据库获取协议编号
            $msg = '协议编号生成失败，请重新尝试';
            $protocol_no = $this->delivery_service->set_protocol_status($delivery_info['delivery_id'], $delivery_info['order_id'], ADMIN_ID,$protocol_no);
            if ($protocol_no) {
                $msg = '生成完毕！用户协议编号：' . $protocol_no;
            }
            // 操作日志
            $operator = get_operator();
            $log = [
                'order_no' => $delivery_info['order_no'],
                'action' => "生成租机协议",
                'operator_id' => $operator['id'],
                'operator_name' => $operator['username'],
                'operator_type' => $operator['operator_type'],
                'msg' => $msg,
            ];
            $add_log = $this->service_order_log->add($log);
            if (!$add_log) {
                showmessage("插入日志失败","null");
            }
        }

        // 商品规格
        $specs = $order_info['goods_info']['specs'];
        $spec_value_list = [];
        foreach ($specs as $it) {
            $spec_value_list[] = $it['value'];
        }
        $order_info['goods_info']['spec_value_list'] = implode(' ', $spec_value_list);


        //省, 市, 区县,具体地址,拼接
        $this->district_service = $this->load->service('admin/district');
        $province = $this->district_service->get_name($order_info['address_info']['province_id']);
        $city = $this->district_service->get_name($order_info['address_info']['city_id']);
        $country = $this->district_service->get_name($order_info['address_info']['country_id']);
        $order_info['address_info']['address'] = $province . ' ' . $city . ' ' . $country . ' ' . $order_info['address_info']['address'];

        if ($order_info['goods_info']['chengse'] < 100) {
            $chengse = '非成新';
        } else {
            $chengse = '全新';
        }
        $contract = $this->load->service("contract/contract");

        //内容替换
        $info['content'] = str_replace('{user_name}', $order_info['realname'], $info['content']);
        $info['content'] = str_replace('{cert_no}', $order_info['cert_no'], $info['content']);
        $info['content'] = str_replace('{address}', $order_info['address_info']['address'], $info['content']);
        $info['content'] = str_replace('{mobile}', $order_info['mobile'], $info['content']);
        $info['content'] = str_replace('{chengse}', $chengse, $info['content']);
        $info['content'] = str_replace('{order_no}', $order_info['order_no'], $info['content']);

        $info['content'] = str_replace('{machine_no}', $order_info['goods_name'].' '.$order_info['goods_info']['spec_value_list'], $info['content']);
        $info['content'] = str_replace('{zuqi}', $order_info['zuqi'], $info['content']);
        $info['content'] = str_replace('{zujin}', $order_info['amount']-$order_info['yiwaixian'], $info['content']);
        $info['content'] = str_replace('{mianyajin}', $order_info['yajin'], $info['content']);
        $info['content'] = str_replace('{yiwaixian}', $order_info['yiwaixian'], $info['content']);
        $info['content'] = str_replace('{imei}', $order_info['goods_info']['imei1'] . ' ' . $order_info['goods_info']['imei2'] . ' ' . $order_info['goods_info']['imei3'] . ' ' . $order_info['goods_info']['serial_number'], $info['content']);

        $info['content'] = str_replace('{dx_zujin}', cny($order_info['zujin']*$order_info['zuqi']), $info['content']);
        $info['content'] = str_replace('{dx_mianyajin}', cny($order_info['yajin']), $info['content']);
        $info['content'] = str_replace('{protocol_no}', $order_info['protocol_no'], $info['content']);

        $this->load->librarys('View')
            ->assign('info', $info)
            ->assign('order_id', $order_id)
            ->assign('delivery_id', $delivery_id)
            ->assign('action', $action)
            ->display('prints_parcel');
    }
    public function delivery_order_export() {

        // 不限制超时时间
        set_time_limit(0);
        // 内存2M
        ini_set('memory_limit', 200*1024*1024);
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename='.'发货单统计'.time().'-'.rand(1000, 9999).'.csv');
        header('Cache-Control: max-age=0');
        $handle = fopen('php://output', 'a');
//
        $header_data = array('订单编号','用户名','用户姓名','下单时间','租期','月租金','信用分','选购产品','品牌','成色','颜色','容量','IMEI','发货地址','发货时间','用户协议号','订单状态','收货人姓名','收货人手机号');
        //输出头部数据
        $this->export_csv_wirter_row($handle, $header_data);

        //-+--------------------------------------------------------------------
        // | 查找列表数据
        //-+--------------------------------------------------------------------

        $this->delivery_service = $this->load->service('order2/delivery');
        $this->order_service = $this->load->service('order2/order');

        $where = $this->__parse_where();

        $delivery_count = $this->delivery_service->get_count($where);
        $delivery_list = [];

        $additional['page'] = 1;
        $additional['size'] = 100;
        //获取全部品牌
        $brand_service = $this->load->service('goods2/brand');
        $brand_list = $brand_service->api_get_list();
        $brand_map = [];
        foreach ($brand_list as $key => $value) {
            $brand_map[$value['id']] = $value['name'];
        }
        while ($delivery_count > 0) {
            //检测列表获取
            $delivery_list = $this->delivery_service->get_list($where, $additional);
            // 发货商品列表
            $goods_ids = array_unique(array_column($delivery_list, 'goods_id'));
            $goods_list = $this->order_service->get_goods_list(['goods_id' => $goods_ids], ['size' => count($goods_ids)]);
            mixed_merge($delivery_list, $goods_list, 'goods_id', 'goods_info');
            // 发货地址列表
            $address_ids = array_unique(array_column($delivery_list, 'address_id'));
            $address_list = $this->order_service->get_address_list(['address_id' => $address_ids], ['size' => count($address_ids)]);
            mixed_merge($delivery_list, $address_list, 'address_id', 'address_info');
            // 订单信息
            $order_ids = array_column($delivery_list, 'order_id');
            $order_list = $this->order_service->get_order_list(['order_id' => $order_ids], ['size' => count($order_ids)]);
            mixed_merge($delivery_list, $order_list, 'order_id', 'order_info');

            //数据遍历重置输出
            foreach ($delivery_list as $key => &$item) {
                $item['business_name'] = Business::getName($item["business_key"]);
                $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
                $item['delivery_status_name'] = DeliveryStatus::getStatusName($item['delivery_status']);
                $item['mobile'] = $item['address_info']['mobile'];
                $item['name'] = $item['address_info']['name'];
                //$item['address'] = $item['address_info']['address'];
                $item['sku_name'] = $item['goods_info']['sku_name'];
                //省, 市, 区县,具体地址,拼接
                $this->district_service = $this->load->service('admin/district');
                $province = $this->district_service->get_name($item['address_info']['province_id']);
                $city = $this->district_service->get_name($item['address_info']['city_id']);
                $country = $this->district_service->get_name($item['address_info']['country_id']);

                $item['address'] = $province . '-' . $city . '-' . $country . chr(13).chr(10) . $item['address_info']['address'];
                $item['order_status'] = $item['order_info']['order_status'];
                $item['protocol_no'] = $item['order_info']['protocol_no'];
                // 商品名称拼接规格
                $specs_arr = $item['goods_info']['specs'];
                $specs = array_column($specs_arr,'value');
                $item['sku_name'] .= ' '.implode(' ', $specs);
                $body_data = [
                    "\t" . $item['order_no'],//订单编号
                    "\t" . $item['mobile'],//用户名
                    "\t" . $item['name'],//用户姓名
                    "\t" . $item['order_info']['create_time_show'],//下单时间
                    "\t" . $item['order_info']['zuqi'],//租期
                    "\t" . zuji\order\Order::priceFormat($item['order_info']['zujin']),//月租金
                    "\t" . $item['order_info']['credit'],//信用分
                    "\t" . $item['order_info']['goods_name'],//选购产品
                    "\t" . $brand_map[$item['goods_info']['brand_id']],//品牌
                    "\t" . $specs[0],//成色
                    "\t" . $specs[1],//颜色

                    "\t" . $specs[3],//容量
                    "\t" . $item['goods_info']['imei1'],//IMEI
                    "\t" . $item['address'],//发货地址
                    "\t" . $item['delivery_time'] == 0 ? '--' : date('Y-m-d H:i:s',$item['delivery_time']),//发货时间
                    "\t" . $item['protocol_no'],//用户协议号
                    "\t" . $item['order_info']['order_status_show'],//订单状态
                    "\t" . $item['name'],//收货人姓名
                    "\t" . $item['mobile'],//收货人手机号
                ];
                $this->export_csv_wirter_row($handle, $body_data);
                unset($body_data);
            }

            $additional['page'] = $additional['page'] + 1;
            $delivery_count = $delivery_count - $additional['size'];
        }

    }
    private function export_csv_wirter_row( $handle, $row ){
        foreach ($row as $key => $value) {
            //$row[$key] = iconv('utf-8', 'gbk', $value);
            $row[$key] = mb_convert_encoding($value,'GBK');
        }
        fputcsv($handle, $row);
    }
}