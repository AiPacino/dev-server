<?php
/**
 * 订单定时任务
 * User: wangjinlin
 * Date: 2018/1/19
 * Time: 上午11:33
 */
//hd_core::load_class('base', 'order2');
use zuji;
use oms\state;
use oms\operator;
use zuji\debug\Debug;
use zuji\debug\Location;
class order_control extends control
{
    public function _initialize()
    {
        parent::_initialize();
        /*
         * 白名单验证
         */
        //get_client_ip();
        //实例化 table
        $this->order_table = $this->load->table('order2/order2');
    }

    public function index(){}

    /*
     * 2小时取消未支付订单
     */
    public function cancel_order(){
        // 当前 操作员
        $Operator = new oms\operator\System(operator\System::Type_System,'system');

        $order_info = $this->order_table
            ->field($this->order_table->fields)
            ->where(['status'=>oms\state\State::OrderCreated,'create_time'=>['LT',time()-7200]])
            ->select();

        foreach ($order_info as $key=>$value){
            $data =[
                'order_id'=>$value['order_id'],
                'reason_id'=>0,
                'reason_text'=>"",
            ];
            try{
                $trans =$this->order_table->startTrans();
                if(!$trans){
                    Debug::error(Location::L_Order, '系统自动取消失败', '服务器繁忙');
                    continue;
                }
                $Orders = new \oms\Order($value);
                // 订单 观察者主题
                $OrderObservable = $Orders->get_observable();
                // 订单 观察者 状态流
                $FollowObserver = new oms\observer\FollowObserver( $OrderObservable );
                // 订单 观察者  日志
                $LogObserver = new oms\observer\LogObserver( $OrderObservable , "取消订单", "系统默认取消");
                $LogObserver->set_operator($Operator);
                $b =$Orders->cancel_order($data);
                if(!$b){
                    $this->order_table->rollback();
                    Debug::error(Location::L_Order, '系统默认取消订单失败:'.get_error(), $data);
                    continue;
                }
                $this->order_table->commit();
//                echo '操作成功';
            }catch (\Exception $exc){
                $this->order_table->rollback();
                Debug::error(Location::L_Order, '系统默认取消订单失败:'.$exc->getMessage(), $data);
                continue;
            }
        }
    }

    /*
     * 半小时取消芝麻未支付订单
     */
    public function zhima_cancel_order(){
        // 当前 操作员
        $Operator = new oms\operator\System(operator\System::Type_System,'system');
        //查询订单表（芝麻订单号）
        $order_info = $this->order_table
            ->field($this->order_table->fields)
            ->where(['status'=>oms\state\State::OrderCreated,'payment_type_id'=>\zuji\Config::MiniAlipay ,'create_time'=>['LT',time()-1800]])
            ->select();

        foreach ($order_info as $key=>$value){
            $data =[
                'order_id'=>$value['order_id'],
                'reason_id'=>0,
                'reason_text'=>"",
            ];
            try{
                $trans =$this->order_table->startTrans();
                if(!$trans){
                    Debug::error(Location::L_Order, '系统自动取消失败', '服务器繁忙');
                    continue;
                }
                $Orders = new \oms\Order($value);
                // 订单 观察者主题
                $OrderObservable = $Orders->get_observable();
                // 订单 观察者 状态流
                $FollowObserver = new oms\observer\FollowObserver( $OrderObservable );
                // 订单 观察者  日志
                $LogObserver = new oms\observer\LogObserver( $OrderObservable , "取消订单", "系统默认取消");
                $LogObserver->set_operator($Operator);
                $b =$Orders->cancel_order($data);
                if(!$b){
                    $this->order_table->rollback();
                    Debug::error(Location::L_Order, '系统默认取消订单失败:'.get_error(), $data);
                    continue;
                }
                $this->order_table->commit();
//                echo '操作成功';
            }catch (\Exception $exc){
                $this->order_table->rollback();
                Debug::error(Location::L_Order, '系统默认取消订单失败:'.$exc->getMessage(), $data);
                continue;
            }
        }
    }

    /*
     * 7天默认收货
     */
    public function defaults_receive(){
        $order2_delivery_table = $this->load->table('order2/order2_delivery');

        $this->service_service = $this->load->service('order2/service');
        $this->instalment_table = $this->load->table('order2/order2_instalment');

        // 当前 操作员
        $Operator = new oms\operator\System(operator\System::Type_System,'system');

        $order_list = $this->order_table
            ->field($this->order_table->fields)
            ->where(['status'=>oms\state\State::OrderDeliveryed])
            ->select();

        $order_info = [];
        foreach ($order_list as $key=>$value){
            $delivery_row = $order2_delivery_table->field('delivery_id')
                        ->where(['order_id'=>$value['order_id'],'delivery_status'=>zuji\order\DeliveryStatus::DeliverySend,'delivery_time'=>['LT',time()-604800]])
                        ->find();
            if($delivery_row){
                $order_info[$value['order_id']]=$value;
                $order_info[$value['order_id']]['delivery_id']=$delivery_row['delivery_id'];
            }

        }

        foreach ($order_info as $key=>$value){
            $data =[
                'order_id'=>$value['order_id'],
                'confirm_remark'=>'系统默认收货'
            ];
            try{
                $trans =$this->order_table->startTrans();
                if(!$trans){
                    Debug::error(Location::L_Order, '系统默认收货失败', '服务器繁忙');
                    exit;
                }
                $Orders = new \oms\Order($value);
                // 订单 观察者主题
                $OrderObservable = $Orders->get_observable();
                // 订单 观察者 状态流
                $FollowObserver = new oms\observer\FollowObserver( $OrderObservable );
                // 订单 观察者  日志
                $LogObserver = new oms\observer\LogObserver( $OrderObservable , "确认收货", "系统默认收货");
                $LogObserver->set_operator($Operator);
                $b =$Orders->sign_delivery($data);
                if(!$b){
                    $this->order_table->rollback();
                    Debug::error(Location::L_Order, '系统默认收货失败:'.get_error(), $data);
                }
                $this->order_table->commit();

                $service_info =$this->service_service->get_info_by_order_id($value['order_id']);
                $instalment_info =$this->instalment_table->get_order_list(['order_id'=>$value['order_id']]);

                //确认收货发送短信
                \zuji\sms\SendSms::confirmed_delivery([
                    'mobile' => $value['mobile'],
                    'orderNo' => $value['order_no'],
                    'realName' => $value['realname'],
                    'goodsName' => $value['goods_name'],
                    'zuQi' => $value['zuqi'],
                    'beginTime' => date("Y-m-d H:i:s",$service_info['begin_time']),
                    'endTime' => date("Y-m-d H:i:s",$service_info['end_time']),
                    'zuJin' => $value['zujin']/100,
                    'createTime' => $instalment_info[0]['term'],
                ]);
//                echo '操作成功';
            }catch (\Exception $exc){
                $this->order_table->rollback();
                Debug::error(Location::L_Order, '系统默认收货失败:'.$exc->getMessage(), $data);
            }
        }
    }
}