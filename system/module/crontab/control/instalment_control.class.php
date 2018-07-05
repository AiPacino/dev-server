<?php
/**
 * 订单定时任务
 * User: wangjinlin
 * Date: 2018/1/19
 * Time: 上午11:33
 */
//hd_core::load_class('base', 'order2');
use zuji;
use zuji\Config;
use zuji\order\Order;
use oms\state;
use oms\operator;
use zuji\debug\Debug;
use zuji\debug\Location;
use zuji\cache\Redis;

class instalment_control extends control
{
    public function _initialize()
    {
        parent::_initialize();
        //实例化 table
        $this->instalment_service = $this->load->service('order2/instalment');
        $this->order_service      = $this->load->service('order2/order');
        $this->member_service     = $this->load->service('member/member');
        $this->instalment_table     = $this->load->table('order2/order2_instalment');
    }

    /**
     * 扣款前发送短信
     * @author wuhaiyan<wuhaiyan@huishoubao.com.cn>
     */

    public function instalment_payment_sms(){
        $this->sms_service      = $this->load->service('order2/sms');
        $now_day = date("d");
        if($now_day ==12 || $now_day ==14){
            $now_date =date("Ym");
            $where['status'] = ['IN', [zuji\payment\Instalment::UNPAID,zuji\payment\Instalment::FAIL]];
            $where['term'] = $now_date;
            $instalment_list = $this->instalment_table->where($where)->select();
            foreach($instalment_list as $v) {
                // 订单信息
                $order_info = $this->order_service->get_order_info(['order_id' => $v['order_id']]);
                if (!$order_info) {
                    continue;
                }
                $data =[
                        'mobile'=>$order_info['mobile'],
                        'orderNo'=>$order_info['order_no'],
                        'realName'=>$order_info['realname'],
                        'goodsName'=>$order_info['goods_name'],
                        'zuJin'=>$v['amount'] / 100,
                        'createTime'=>$v['term']."15",
                ];
                $begin_time = strtotime(date("Y-m-d 00:00:00"));
                $end_time = strtotime(date("Y-m-d 23:59:59"));
                $where =[
                    'user_mobile'=>$order_info['mobile'],
                    'order_no'=>$order_info['order_no'],
                    'create_time'=>['between',[$begin_time, $end_time]],
                ];
                if($now_day ==12){
                    $where['sms_no']="hsb_sms_fe7c8";
                    $sms_count=$this->sms_service->get_count($where);
                    if($sms_count ==0){
                        zuji\sms\SendSms::instalment_three_day($data);
                    }
                }elseif($now_day ==14){
                    $where['sms_no']="hsb_sms_b5fd2";
                    $sms_count=$this->sms_service->get_count($where);
                    if($sms_count ==0){
                        zuji\sms\SendSms::instalment_one_day($data);
                    }
                }



            }
            Debug::error(Location::L_SMS,'扣款前发送短信',$now_date);
            echo "已执行";

        }
    }
    /*
     * 即将扣款
     */
    public function soon_debit(){


        $year   = date("Y");
        $month  = intval(date("m"));
        $day    = intval(date("d"));
        if($month < 10 ){
            $new_month = "0".$month;
        }
        $term   = $year.$new_month;


        if($month == 1){
            $year = $year-1;
            $before_term = $year.'12';
        }else{
            $month = $month-1;
            if($month < 10 ){
                $new_month = "0".$month;
            }
            $before_term = $year.$new_month;
        }


        if($day < 15){
            $where['term']      = ['IN', [$before_term,$term]];
        }else{
            $where['term']      = $term;
        }

        $where['status']    = ['IN', [1,3]];

        $load = \hd_load::getInstance();
        $instalment_table = $load->table('order2/order2_instalment');


        // 【机市】亲爱哒用户$
        // {userName}，您好！您在机市上的订单$
        // {orderNo}购买$
        // {goodsName}即将缴纳租金，租金$
        // {daikouSum}金额元，缴纳时间：
        // {koukuanTime}，请保持账户余额充足，以便清算，提高您的芝麻信用值。
        $instalment_list = $instalment_table->where($where)->select();

        foreach($instalment_list as $v){
            // 订单信息
            $order_info = $this->order_service->get_order_info(['order_id'=>$v['order_id']]);
            if(!$order_info){
                continue;
            }

            // 用户信息
            $user_id = $order_info['user_id'];
            $user_info = $this->member_service->fetch_by_id($user_id);

            $amount = $v['amount'] / 100;

            $sms = new \zuji\sms\HsbSms();
            $b = $sms->send_sm($user_info['mobile'],'hsb_sms_f3b1c',[
                'userName'      => $user_info['realname'],      // 用户名
                'orderNo'       => $order_info['order_no'],     // 订单号
                'goodsName'     => $order_info['goods_name'],   // 商品名称
                'daikouSum'     => $amount,                     // 代扣金额
                'koukuanTime'   => time(),                      // 时间
            ],$order_info['order_no']);
            if (!$b) {
                Debug::error(Location::L_Order,'代扣短信',$b);
            }
        }

    }
    /*
     * 提前扣款短信
     */
    public function advance_debit(){
        $sms_list = [
            'month',
            'advance_one',
            'advance_three',
        ];
        $type = $_GET['type'];

        if(empty($type) || !in_array($type, $sms_list)){
            echo "参数错误";
            Debug::error(Location::L_Order,'参数错误',[]);
            exit;
        }

        $trem               = date("Ym");
        $where['term']      = $trem;
        $where['status']    = ['IN', [zuji\payment\Instalment::UNPAID,zuji\payment\Instalment::FAIL]];

        $instalment_list    = $this->instalment_table->where($where)->select();

        //【机市】尊敬的用户{realName}，您的本月账单应付金额为{zuJin}元。您可以选择在机市：我的>全部订单>点击提前还款，进行提前还款。提前还款成功后将不再执行本月代扣。如您在使用中遇到问题或有其它疑问请联系客服电话：{serviceTel}。
        foreach($instalment_list as $v){

            // 订单信息
            $order_info = $this->order_service->get_order_info(['order_id'=>$v['order_id']]);
            if(!$order_info){
                continue;
            }

            // 用户信息
            $user_info  = $this->member_service->fetch_by_id($order_info['user_id']);
            $realName   = $user_info['realname'] ? $user_info['realname'] : "";

            // 租金
            $zuJin = $v['amount'] > 0 ? $v['amount'] / 100 : '0';

            //联系电话
            $serviceTel = "400-080-9966";

            $Withhold_time = date("Y-m-15",strtotime($v['term']));

            switch ($type){
                case 'month':
                    $model =  'hsb_sms_5b828';
                    $sms_data = [
                        'realName'      => $realName,       // 用户名
                        'zuJin'         => $zuJin,          // 租金
                        'serviceTel'    => $serviceTel,     // 客服电话
                    ];
                  break;
                case 'advance_one':
                    $model =  'hsb_sms_b5fd2';
                    $sms_data = [
                        'realName'      => $realName,                   // 用户名
                        'orderNo'       => $order_info['order_no'],     // 订单号
                        'goodsName'     => $order_info['goods_name'],   // 商品名
                        'zuJin'         => $zuJin,                      // 租金
                        'createTime'    => $Withhold_time,              // 时间
                        'serviceTel'    => $serviceTel,                 // 客服电话
                    ];
                  break;
                case 'advance_three':
                    $model =  'hsb_sms_fe7c8';
                    $sms_data = [
                        'realName'      => $realName,                   // 用户名
                        'orderNo'       => $order_info['order_no'],     // 订单号
                        'goodsName'     => $order_info['goods_name'],   // 商品名
                        'zuJin'         => $zuJin,                      // 租金
                        'createTime'    => $Withhold_time,              // 时间
                        'serviceTel'    => $serviceTel,                 // 客服电话
                    ];
                  break;
                default:
            }

            $sms_where = [
                'sms_no'    => $model,
                'mobile'    => $user_info['mobile'],
                'order_no'  => $order_info['order_no'] . '_' . $v['times'],
            ];

            // 判断是否发送过
            $this->sms_table = $this->load->table('sms');
            $result = $this->sms_table->where($sms_where)->find();

            if(!$result){
                $sms = new \zuji\sms\HsbSms();
                $b = $sms->send_sms($user_info['mobile'],$model,$sms_data);
                if (!$b) {
                    Debug::error(Location::L_Order,'代扣短信',$b);
                }
                //添加发送成功短信
                $data = [
                    'sms_no'        => $model,
                    'user_mobile'   => $user_info['mobile'],
                    'order_no'      => $order_info['order_no'] . '_' . $v['times'],
                    'json_data'     => json_encode($sms_data),
                    'create_time'   => time(),
                ];
                $this->sms_table->add($data);
            }
        }

    }

//    // 四月发送代扣短信
//    public function instalment_april(){
//        $this->instalment_april = $this->load->table('instalment_april');
//        $time = time();
//        $begintime = strtotime("2018-04-02 00:00:00");
//        $lasttime  = strtotime("2018-04-02 23:59:59");
//
//        // 4月1号 当天 发送
//        if($time > $begintime && $time <= $lasttime){
//
//            $where = [
//                'status'=>'0'
//            ];
//            $limit = 100;
//            $page  = (intval($_GET['page']) - 1) * $limit;
//
//            $result = $this->instalment_april->where($where)->order('id asc')->limit($page, $limit)->select();
//
//            foreach($result as $val){
//                $sms_data = [
//                    'realName'      => trim($val['name']),                  // 用户名
//                    'zuJin'         => Order::priceFormat($val['amount']),  // 租金
//                    'serviceTel'    => Config::Customer_Service_Phone,      // 客服电话
//                ];
//
//                //发送短信
//                $sms = new \zuji\sms\HsbSms();
//                $b = $sms->send_sm($val['mobile'], 'hsb_sms_5b828', $sms_data, $val['order_no']);
//                if($b){
//                    $this->instalment_april->where(['id'=>$val['id']])->save(['status'=>1]);
//                }
//            }
//
//        }else{
//            echo "时间错误！！！";
//        }
//
//    }


}