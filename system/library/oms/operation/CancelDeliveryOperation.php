<?php
namespace oms\operation;

use oms\Order;
use zuji\debug\Debug;
use zuji\debug\Location;
use zuji\order\RefundStatus;
use zuji\Business;
use zuji\order\ServiceStatus;
use zuji\Config;
use oms\state\State;
use zuji\order\DeliveryStatus;
use zuji\order\OrderStatus;

/**
 * 取消发货操作
 * @author wuhaiyan<wuhaiyan@huishoubao.com.cn>
 *
 */
class CancelDeliveryOperation implements OrderOperation
{

    private $order_id=0;
    private $payment_id=0;
    private $should_remark="";
    private $should_amount=0;
    private $delivery_id =0;
    private $Order = null;

    public function __construct( Order $order,$data ){
       
        $this->should_amount =$data['should_amount'];
        $this->should_remark=$data['should_remark'];
        $this->order_id=$data['order_id'];
        $this->payment_id=$data['payment_id'];
        $this->delivery_id =$data['delivery_id'];
        $this->Order =$order;
    }
    
    public function update(){  
        $delivery_id =$this->delivery_id;
        if($delivery_id <1){
            set_error("发货单ID错误");
            return false;
        } 
        $data=[
            'payment_id'=>$this->payment_id,
            'order_id'=>$this->order_id,
            'should_amount'=>$this->should_amount,
            'should_remark' =>$this->should_remark,
        ];
        
        $data = filter_array($data, [
            'payment_id'=>'required|is_id',
            'order_id'=>'required|is_id',
            'should_amount' =>'required',
            'should_remark' =>'required',
        ]);
        if( count($data)<4 ){
            set_error("参数错误");
            return false;
        }
        
        $payment_id=$this->payment_id;
        $order_id=$this->order_id;
        $should_amount=$this->should_amount;
        $should_remark=$this->should_remark;
        
        $load = \hd_load::getInstance();
        $payment_service = $load->service('order2/payment');
        $order_service =$load->service('order2/order');
        $order_table = $load->table('order2/order2');
        $delivery_table =$load->table('order2/order2_delivery');
        
        $delivery_data =[
            'update_time' =>time(),
            'delivery_status'=>DeliveryStatus::DeliveryCanceled
        ];

        $b = $delivery_table->where(['delivery_id'=>$delivery_id])->save($delivery_data);
        if(!$b){
            set_error("更新发货单失败");
            return false;
        }

            $payment_info = $payment_service->get_info($payment_id);
            $order_info = $order_service->get_order_info(['order_id' => $order_id]);

            //创建退款单
            $refundData = [
                'order_id' => intval($order_info['order_id']),
                'order_no' => $order_info['order_no'],
                'payment_amount' => $order_info['payment_amount'] * 100,
                'user_id' => intval($order_info['user_id']),
                'mobile' => $order_info['mobile'],
                'goods_id' => intval($order_info['goods_id']),
                'payment_id' => intval($order_info['payment_id']),
                'business_key' => intval($order_info['business_key']),
                'payment_channel_id' => intval($payment_info['payment_channel_id']),
                'should_amount' => $should_amount * 100,  // 应退金额（单位：元->分）
                'should_remark' => $should_remark,// 备注
                'update_time' => time(),
                'create_time' => time(),
                'refund_status' => RefundStatus::RefundWaiting,
            ];
            $refund_table = $load->table('order2/order2_refund');
            $refund_id = $refund_table->add($refundData);
            if (!$refund_id) {
                set_error("创建[退款单]失败");
                return false;
            }
            //更新订单
            $order_data = [
                'status' =>State::OrderRefunding,
                'update_time'=> time(),//更新时间
                'refund_id'=>$refund_id,
                'refund_status'=>RefundStatus::RefundWaiting,//订单已发货
            ];

            $b =$order_table->where(['order_id'=>$this->order_id])->save($order_data);
            if(!$b){
                set_error("更新订单状态失败");
                return false;
            }
        
       return true;      
    }
}