<?php
/**
 * 解冻转支付 异步通知
 */
class payment_createpay_notify_table extends table {

    protected $fields = [
        'id',
        'notify_time',    // 通知的发送时间；YYYY-MM-DD HH:MM:SS
        'notify_type',    // 通知类型；固定值：fund_auth_freeze
        'notify_id',	    // 通知校验ID
        'sign_type',
        'sign',
        'notify_action_type',
        'out_trade_no',   // 租机交易号
        'trade_no',	    // 支付宝交易流水号
        'trade_status',
        'subject',
        'gmt_create',		    // 操作创建时间；YYYY-MM-DD HH:MM:SS
        'gmt_payment',
        'gmt_close',
        'seller_email',
        'seller_id',
        'buyer_id',
        'buyer_email',
        'total_fee',
        'price',
        'quantity',
        'refund_fee',
        'paytools_pay_amount',
        'trade_channel',
    ];


    /**
     * 创建
     * @param type $data
     */
    public function create($data){
        return $this->add($data);
    }

    /**
     * 查询记录数
     * @return int  符合查询条件的总数
     */
    public function get_count($where) {
        return $this->where($where)->count('auth_id');
    }

    public function get_list($where,$options) {
        $order_list = $this->field($this->fields)->page($options['page'])->limit($options['size'])->where($where)->order($options['orderby'])->select();
        if(!is_array($order_list)){
            return [];
        }
        return $order_list;
    }

}