<?php
/**
 * 支付 交易-异步通知表
 */
class payment_trade_notify_table extends table {

    protected $fields = [
	'id',
	'trade_channel',
	'notify_id',
	'notify_type',
	'notify_time',
	'notify_action_type',
	'trade_id',
	'trade_no',
	'out_trade_no',
	'trade_status',
	'gmt_create',
	'gmt_payment',
	'gmt_close',
	'total_amount',
	'price',
	'quantity',
	'refund_fee',
	'seller_id',
	'seller_email',
	'buyer_id',
	'buyer_email',
	'subject',
	'create_time',
    ];
    
    /**
     * 创建通知记录
     * @param type $data
     */
    public function create($data){
	return $this->add($data);
    }


}