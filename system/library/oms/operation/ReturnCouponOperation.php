<?php

namespace oms\operation;

use zuji\coupon\Coupon;

/**
 * 返还优惠券
 * @author  liuhongxing <liuhongxing@huishoubao.com.cn> 
 *
 */
class ReturnCouponOperation implements OrderOperation {

	private $Order = Null;

	public function __construct(\oms\Order $Order) {
		$this->Order = $Order;
	}

	public function update() {
		$load = \hd_load::getInstance();
		//查询该订单是否有优惠券 有则返还
		$coupon_table = $load->table('order2/order2_coupon');
		$coupon_info = $coupon_table->where(['order_id' => $this->Order->get_order_id()])->find();
		if ($coupon_info) {
			$cancel_coupon = Coupon::cancel_coupon($coupon_info['coupon_id']);
			if (!$cancel_coupon) {
				set_error("返还优惠券失败");
				return false;
			}
		}

		return true;
	}

}
