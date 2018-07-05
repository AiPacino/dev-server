<?php
/**
 * 资金授权 数据层
 */
class payment_fund_auth_notify_table extends table {

    protected $fields = [
	'id',
	'request_no',
	'request_status',
	'order_id',
	'order_no',
	'auth_no',
	'order_status',
	'notify_time',
	'notify_id',
	'notify_type',
	'total_pay_amount',
	'total_freeze_amount',
	'total_unfreeze_amount',
	'reset_amount',
	'operation_type',
	'operation_id',
	'gmt_create',
	'gmt_trans',
	'amount',
	'payer_logon_id',
	'payer_user_id',
	'payee_logon_id',
	'payee_user_id',
	'sign_type',
	'sign',
	'create_time',
	'update_time',
    ];

    /**
     * 创建授权通知记录
     * @param type $data
     */
    public function create($data){
	return $this->add($data);
    }

}