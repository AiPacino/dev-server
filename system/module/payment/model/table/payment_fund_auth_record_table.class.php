<?php
/**
 * 资金授权表 解冻转支付记录表
 */
class payment_fund_auth_record_table extends table {

    protected $fields = [
	'id',
	'auth_id',
	'type',
	'amount',
	'order_id',
	'instalment_id',
	'create_time',
    'trade_no',
    'out_trade_no',
    'status',
    'update_time',
    ];

    /**
     * 查询记录数
     * @return int  符合查询条件的总数
     */
    public function get_count($where) {
        return $this->where($where)->count('id');
    }
    
    public function get_list($where,$options) {
        $order_list = $this->field($this->fields)->page($options['page'])->limit($options['size'])->where($where)->order($options['orderby'])->select();
        if(!is_array($order_list)){
            return [];
        }
        return $order_list;
    }

}