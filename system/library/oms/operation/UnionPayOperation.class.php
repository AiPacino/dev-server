<?php
namespace oms\operation;

use zuji\order\OrderStatus;
use oms\state\State;
use zuji\debug\Debug;
use zuji\debug\Location;
use zuji\order\PaymentStatus;
use zuji\payment\Payment;

/**
 * 订单银联支付 操作
 * @author 
 *
 */
class UnionPayOperation implements OrderOperation
{

    private $Order = null;
    private $data = '';
    private $trade_id = '';
    
    public function __construct(\oms\Order $Order, $data,int $trade_id )
    {
        $this->Order = $Order;
        $this->data = $data;
        $this->trade_id = $trade_id;
    }
    
	/**
	 * 支付操作
	 * <p>3）更新订单状态</p>
	 * <p>注意：需要兼容线上订单</p>
	 * <p></p>
	 * @return boolean
	 */
    public function update(){
		
		//-+--------------------------------------------------------------------
		// | 系统类加载器
		//-+--------------------------------------------------------------------
		$load = \hd_load::getInstance();
		// 订单表
        $time = time();
		$order2_table = $load->table('order2/order2');
        $order2_payment_table = $load->table('order2/order2_payment');
        $payment_data =[
            'payment_id'=>$this->data['payment_id'],
            'trade_no'=>$this->data['trade_no'],
            'payment_status' =>  PaymentStatus::PaymentSuccessful,
            'payment_no'=>$this->data['payment_no'],
            'payment_amount'=>$this->data['payment_amount'],
            'payment_time'=>$time,
            'update_time'=>$time,
        ];
        $b= $order2_payment_table->save($payment_data);
        if(!$b){
            set_error("支付单更新失败");
            return false;
        }

        // 订单状态更新
        $b = $order2_table->where(['order_id'=> $this->Order->get_order_id()])->limit(1)->save([
            'payment_amount' => $this->data['payment_amount'],// 支付金额(单位：分)
            'status' => State::PaymentSuccess,	// 支付成功
            'payment_status'=>PaymentStatus::PaymentSuccessful,
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