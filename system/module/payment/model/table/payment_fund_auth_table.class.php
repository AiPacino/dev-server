<?php
/**
 * 资金授权 数据层
 */
class payment_fund_auth_table extends table {

    protected $fields = [
	'auth_id',
	'request_no',
	'order_id',
	'order_no',
	'fundauth_no',	// 商户资金授权唯一编号
	'auth_no',
	'auth_channel',
	'create_time',
	'update_time',
	'auth_status',
	'amount',
	'unfreeze_amount',
	'pay_amount',
	'payer_logon_id',
	'payer_user_id',
	'payee_logon_id',
	'payee_user_id',
	'trade_no',
	'topay',
    ];

    
    /**
     * 创建授权通知记录
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