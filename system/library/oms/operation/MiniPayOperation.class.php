<?php
namespace oms\operation;

use zuji\order\OrderStatus;
use oms\state\State;
use zuji\debug\Debug;
use zuji\debug\Location;

/**
 * 订单支付 操作
 * @author 
 *
 */
class MiniPayOperation implements OrderOperation
{

    private $Order = null;

    public function __construct(\oms\Order $Order ){
		$this->Order = $Order;
    }
    
	/**
	 * 支付操作
	 * @return boolean
	 */
    public function update(){
		
		//-+--------------------------------------------------------------------
		// | 系统类加载器
		//-+--------------------------------------------------------------------
		$load = \hd_load::getInstance();
		// 订单表
		$order2_table = $load->table('order2/order2');
		$time = time();
        // 订单单状态更新
        $b = $order2_table->where(['order_id'=> $this->Order->get_order_id()])->limit(1)->save([
            'status' => State::PaymentSuccess,	// 支付成功
            'payment_time' => $time,// 支付时间
            'update_time' => $time,// 更新时间
        ]);
        if( !$b ){
            set_error('订单更新失败');
            return false;
        }

       return true;
    }
}