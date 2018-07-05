<?php
/**
 * 确认收货
 * Created by PhpStorm.
 * @author: <jinliangping@huishoubao.com.cn>
 * Date: 2018/1/6 0006-下午 2:16
 * @copyright (c) 2017, Huishoubao
 */

namespace oms\operation;


use oms\Order;
use oms\state\State;
use zuji\Config;
use zuji\debug\Debug;
use zuji\debug\Location;
use zuji\order\DeliveryStatus;
use zuji\order\OrderStatus;
use zuji\order\ServiceStatus;
use zuji\Time;

class SignDeliveryOperation implements OrderOperation
{

    private $confirm_remark="";
    private $Order = null;
    private $admin_id = 0;


    public function __construct( $data, Order $order ){

        $this->confirm_remark = isset($data['confirm_remark'])?$data['confirm_remark']:'';
        $this->Order = $order;
        $this->admin_id = isset($data['id'])?intval($data['id']):0;
    }

    public function update()
    {

        // 判断 签收时间有效期（小于当前时间，大于前3天时间）
//         $Time = Time::getTime();
//         $BTime = $Time->getOtherDayBegin(-3);
//         if ($this->confirm_time < $BTime->toTimestamp()) {
//             set_error('确认收货时间必须大于' . Time::format($BTime));
//             return false;
//         }

        //-+--------------------------------------------------------------------
        // | 系统类加载器
        //-+--------------------------------------------------------------------
        $load = \hd_load::getInstance();
        //发货单表
        $delivery_table =$load->table('order2/order2_delivery');
        // 订单表
        $order2_table = $load->table('order2/order2');
        //订单服务表
        $service_table = $load->table('order2/order2_service');
        // 更新发货单状态
        $data=[
            'order_id'=>$this->Order->get_order_id(),
            'confirm_remark'=>$this->confirm_remark,
            'update_time'=>time(),
            'confirm_time'=>time(),
            'delivery_status'=>DeliveryStatus::DeliveryConfirmed,
            'confirm_admin_id'=>$this->admin_id,
        ];
        $delivery = $delivery_table->where(['delivery_id'=>$this->Order->get_delivery_id()])->save($data);
        if(!$delivery){
            set_error("更新发货单信息失败");
            Debug::error(Location::L_Delivery, get_error(), $data);
            return false;
        }

        if($this->Order->get_service_id()==0){
  
        //生成服务单  同步到订单
        $service_data = [
            'order_id' => $this->Order->get_order_id(),
            'order_no' => $this->Order->get_order_no(),
            'mobile' => $this->Order->get_mobile(),
            'user_id' => $this->Order->get_user_id(),
            'business_key' => $this->Order->get_business_key(),
            // 签收时间的次日0晨开始
            'begin_time' => Time::getTime($this->confirm_time)->getOtherDayBegin(1)->toTimestamp(),
            'zuqi' => $this->Order->get_zuqi(),
            
        ];
        $service_data = filter_array($service_data, [
            'order_id' => 'required|is_id',
            'order_no' =>'required',
            'mobile' =>'required',
            'user_id'=>'required',
            'business_key' =>'required',
            'begin_time' =>'required|is_int',
            'zuqi' =>'required',
        ]);
        
        if( count($service_data)<7 ){
            set_error("参数错误");
            return false;
        }
        $service_data['update_time'] =time();
        $service_data['create_time'] =time();
        $service_data['service_status']=ServiceStatus::ServiceOpen;
        $service_data['end_time'] =$this->calculate_end_time($service_data['begin_time'], $service_data['zuqi'],$this->Order->get_zuqi_type());
        unset($service_data['zuqi']);

        $service_id = $service_table->add($service_data);
        if(!$service_id){
            set_error("生成服务单失败");
            return false;
        }
//        //判断支付方式  如果是代扣预授权 则创建订单分期  -- 改成下单时候调用
//        $payment_type_id =$this->Order->get_payment_type_id();
//        if($payment_type_id ==Config::WithhodingPay){
//            //调用代扣分期的方法
//            $instalment =$load->service("order2/instalment");
//            $b = $instalment->create(['order_id'=>$this->Order->get_order_id()]);
//            if(!$b){
//                set_error("生成代扣分期失败".get_error());
//                return false;
//            }
//
//        }

        //更新订单状态
        $order_data = array(
            'delivery_status'=> DeliveryStatus::DeliveryConfirmed,//订单已确认收货
            'service_status'=> ServiceStatus::ServiceOpen,
            'status' => State::OrderInService,
            'service_id'=> $service_id,//服务单id
        );
        }else{
            //更新订单状态
            $order_data = array(
                'delivery_status'=> DeliveryStatus::DeliveryConfirmed,//订单已确认收货
                'status' => State::OrderInService,
            );
        }
        //更新订单
        $order_result = $order2_table->notify_delivery( $this->Order->get_order_id(), $order_data );
        //验证订单更新是否成功
        if( !$order_result ) {//业务处理不成功
            set_error('同步订单[确认收货]状态失败');
            return false;
        }

        return true;
    }

    private function calculate_end_time($begin_time, $zuqi, $zuqi_type){
        $day = 0;
		if( $zuqi_type == 1 ){
			$day = $zuqi;
		}elseif( $zuqi_type == 2 ){
			if($zuqi ==12){
				$day = 365;
			}else if($zuqi ==6){
				$day = 180;
			}else{
				$day = 90;
			}
		}
        return Time::getTime($begin_time)->getOtherDayBegin($day)->toTimestamp();
    }
}