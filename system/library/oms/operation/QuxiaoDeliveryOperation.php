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
class QuxiaoDeliveryOperation implements OrderOperation
{

    private $order_id=0;
    private $delivery_id =0;
    private $Order = null;

    public function __construct( Order $order,$data ){

        $this->order_id=$data['order_id'];
        $this->delivery_id =$data['delivery_id'];
        $this->Order =$order;
    }
    
    public function update(){  
        $delivery_id =$this->delivery_id;
        $order_id =$this->order_id;
        if($delivery_id <1 || $order_id <1){
            set_error("参数错误");
            return false;
        }
        $load = \hd_load::getInstance();
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
        if($this->Order->get_payment_type_id() == Config::MiniAlipay){
            // 订单表
            $order_table = $load->table('order2/order2');
            //更新订单
            $order_data = [
                'delivery_status'=> DeliveryStatus::DeliveryCanceled,//订单待发货
                'status' => State::OrderRefunding,
                'update_time'=> time(),//更新时间
            ];
            $b =$order_table->where(['order_id'=>$this->Order->get_order_id()])->save($order_data);
            if(!$b){
                set_error("更新订单状态失败");
                return false;
            }
        }

       return true;      
    }
}