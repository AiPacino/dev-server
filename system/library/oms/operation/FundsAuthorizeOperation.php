<?php
namespace oms\operation;
use oms\state\State;
use zuji\order\ReceiveStatus;
use zuji\order\OrderStatus;

/**
 * 资金授权操作
 * @author wuhaiyan<wuhaiyan@huishoubao.com.cn>
 *
 */
class FundsAuthorizeOperation implements OrderOperation
{
    private $Order =null;
    public function __construct(\oms\Order $Order){
        $this->Order = $Order;
    }
    
    public function update(){
        $order_id =$this->Order->get_order_id();
        $zujin =$this->Order->get_zujin();
        $zuqi =$this->Order->get_zuqi();
        $yajin =$this->Order->get_yajin();

        $load = \hd_load::getInstance();
        $order_table = $load->table('order2/order2');
        //更新订单
        $order_data = [
            'authorized_yajin'=>$yajin,
            'authorized_zujin'=>$zujin*$zuqi,
            'status' =>State::FundsAuthorized,
            'order_status'=>OrderStatus::OrderCreated,
            'update_time'=> time(),//更新时间
        ];
        $b =$order_table->where(['order_id'=>$order_id])->save($order_data);
        if(!$b){
            set_error("更新订单状态失败");
            return false;
        }
        return true;
    }
}