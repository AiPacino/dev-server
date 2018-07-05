<?php

namespace oms\operation;

use oms\Order;
use oms\state\State;
use zuji\Business;
use zuji\Config;
use zuji\coupon\Coupon;
use zuji\debug\Debug;
use zuji\debug\Location;
use oms\operator\Operator;
use zuji\order\OrderStatus;

/**
 * 小程序取消订单操作
 * @author wuhaiyan<wuhaiyan@huishoubao.com.cn> 
 *
 */
class MiniAlipayCancelOperation implements OrderOperation {

	private $Order = null;

	public function __construct(Order $order) {
		$this->Order =$order;
	}

	public function update() {
		$load = \hd_load::getInstance();
		$order_table = $load->table('order2/order2');
        $order_service =$load->service('order2/order');

		$data = [
			'status' => State::OrderRefunding,
			'update_time' => time(), //更新时间
		];

		$b = $order_table->where(['order_id' => $this->Order->get_order_id()])->save($data);
		if ($b===false) {
            set_error("小程序订单取消失败");
            return false;
        }

		return true;
	}

}
