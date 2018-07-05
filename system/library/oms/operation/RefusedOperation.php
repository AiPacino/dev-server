<?php
/**
 * 用户拒签操作
 * Created by PhpStorm.
 * @author: <jinliangping@huishoubao.com.cn>
 * Date: 2018/1/6 0006-上午 11:37
 * @copyright (c) 2017, Huishoubao
 */

namespace oms\operation;
use oms\state\State;
use zuji\order\DeliveryStatus;
use zuji\debug\Debug;
use zuji\debug\Location;
use zuji\order\OrderStatus;
use zuji\order\ServiceStatus;
use oms\Order;

class RefusedOperation implements OrderOperation
{

    private $refuse_remark="";
    private $admin_id=0;
    private $Order = null;

    public function __construct( $data, Order $order ){

        $this->refuse_remark=$data['refuse_remark'];
        $this->admin_id=$data['admin_id'];
        $this->Order = $order;

    }

    public function update()
    {
        //参数校验
        // 拒签备注
        $len = mb_strlen(trim($this->refuse_remark));
        if ($len < 5) {
            set_error("备注最少5个字符");
            return false;
        }

        //-+--------------------------------------------------------------------
        // | 系统类加载器
        //-+--------------------------------------------------------------------
        $load = \hd_load::getInstance();
        $order_table = $load->table('order2/order2');
        $delivery_table =$load->table('order2/order2_delivery');

        //更新发货单状态
        $delivery_data = [
            'delivery_id'=>$this->Order->get_delivery_id(),
            'delivery_status'=>DeliveryStatus::DeliveryRefuse,
            'admin_id'=>$this->admin_id,
            'refuse_remark'=>$this->refuse_remark,
        ];
        $delivery =$delivery_table->update($delivery_data);
        if(!$delivery){
            set_error("更新发货单信息失败");
            Debug::error(Location::L_Delivery, get_error(), $delivery_data);
            return false;
        }

        //更新订单状态（到租用中）
        $data = array(
            'service_status'=> ServiceStatus::ServiceOpen,//租用中
            'status' => State::OrderInService
        );
        //更新订单
        $order_result = $order_table->notify_delivery_return_refuse( $this->Order->get_order_id(), $data );
        //验证订单更新是否成功
        if( !$order_result ) {//业务处理不成功
            set_error('同步订单[客户回寄拒签]状态失败');
            return false;
        }

        return true;
    }
}