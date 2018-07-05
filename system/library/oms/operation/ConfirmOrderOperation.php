<?php
/**
 * 创建发货单
 * Created by PhpStorm.
 * @author: <jinliangping@huishoubao.com.cn>
 * Date: 2018/1/6 0006-下午 6:34
 * @copyright (c) 2017, Huishoubao
 */

namespace oms\operation;

use oms\Order;
use oms\state\State;
use zuji\order\DeliveryStatus;
use zuji\order\PaymentStatus;
use zuji\order\OrderStatus;

class ConfirmOrderOperation implements OrderOperation
{

    private $Order = null;
    public function __construct( Order $order )
    {
        $this->Order = $order;
    }

    public function update()
    {
        //查询订单是否已支付
//        if ($this->Order->get_payment_status() != PaymentStatus::PaymentSuccessful) {
//            set_error("订单未支付");
//            return false;
//        }
        if($this->Order->get_delivery_id() != 0){
            set_error("该发货单已创建");
            return false;
        }

        //-+--------------------------------------------------------------------
        // | 系统类加载器
        //-+--------------------------------------------------------------------
        $load = \hd_load::getInstance();
        //发货单表
        $delivery_table =$load->table('order2/order2_delivery');
        // 订单表
        $order_table = $load->table('order2/order2');

        //生成发货单
        $data = [
            'order_id' => $this->Order->get_order_id(),
            'order_no' => $this->Order->get_order_no(),
            'goods_id' => $this->Order->get_goods_id(),
            'address_id' => $this->Order->get_address_id(),
            'business_key' => $this->Order->get_business_key(),
        ];

        if( count($data)<5 ){
            set_error('参数错误');
            return false;
        }
        $data['delivery_status'] = DeliveryStatus::DeliveryWaiting;
        $data['create_time'] = time();
        $data['update_time'] = time();
        

        $delivery_id = $delivery_table->add($data);
        if(!$delivery_id){
            set_error('发货单保存失败');
            return false;
        }
        //更新订单
        $order_data = [
            'delivery_status'=> DeliveryStatus::DeliveryWaiting,//订单待发货
            'delivery_id'=> $delivery_id,//发货单id
            'status' => State::OrderConfirmed,
            'update_time'=> time(),//更新时间
        ];
        
        $b =$order_table->where(['order_id'=>$this->Order->get_order_id()])->save($order_data);
        if(!$b){
            set_error("更新订单状态失败");
            return false;
        }
        return true;

    }
}