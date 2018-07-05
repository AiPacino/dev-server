<?php
/**
 * 		订单优惠券模型
 */
class order2_coupon_table extends table {

    protected $fields =[
        'id',
        'coupon_no',
        'coupon_id',
        'discount_amount',
        'coupon_type',
        'coupon_name',
        'order_id',
    ];

    public function get_info($order_id, $additional=[]) {
	$rs = $this->where(['order_id'=>$order_id])->find($additional);
	return $rs ? $rs : false;
    }

}