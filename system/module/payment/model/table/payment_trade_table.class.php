<?php
/**
 * 支付 交易流水表
 */
class payment_trade_table extends table {

    protected $fields = [
	'trade_id',
	'trade_no',
	'payment_id',
	'order_id',
	'order_no',
	'trade_type',
	'trade_channel',
	'trade_status',
	'payment_time',
	'refund_time',
	'amount',
	'payment_amount',
	'refund_amount',
	'seller_id',
	'seller_email',
	'buyer_id',
	'buyer_email',
	'subject',
	'create_time',
	'update_time',
	'out_trade_no',	// 第三方交易码
    ];

    /**
     * 根据交易码，获取交易记录
     * @param string $trade_no	交易码
     */
    public function get_info_by_trade_no( $trade_no, $additional=[] ){
	$options = [];
	if( isset($additional['lock']) ){
	    $options['lock'] = $additional['lock'];
	}
	return $this->where(['trade_no'=>$trade_no])->limit(1)->find($options);
    }
    
    public function update_refund($trade_no,$refund_amount){
        $data=[
            'refund_amount'=>$refund_amount,
            'update_time'=>time(),
            'refund_time'=>time(),
        ];
        return $this->where(['trade_no'=>$trade_no])->save($data);
    }
	
}