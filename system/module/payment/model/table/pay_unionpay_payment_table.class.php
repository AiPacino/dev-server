<?php
/**
 *  银联支付单
 */
class pay_unionpay_payment_table extends table {

    protected $fields = [
	'id',
	'order_id',
	'order_no',
	'user_id',
	'token',
	'pay_time',
	'amount',
	'merid',
	'goods_name',
	'sku_id',
	'spu_id',
	'status',
	'payment_no',
    ];

    /**
     * 根据订单ID，获取交易记录
     * @param int  $order_id	订单ID
     */
    public function get_info_by_order_id( $order_id, $additional=[] ){
	$options = [];
	if( isset($additional['lock']) ){
	    $options['lock'] = $additional['lock'];
	}
	return $this->where(['order_id'=>$order_id])->limit(1)->find($options);
    }

	
}