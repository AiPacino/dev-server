<?php
class member_alipay_table extends table
{
    protected $fields = [
	'id',
	'member_id',
	'user_id',
	'province',
	'city',
	'nick_name',
	'is_student_certified',
	'user_type',
	'user_status',
	'is_certified',
	'gender'
    ];


    //查询一条支付宝授权信息
    public function get_info($alipay_user_id) {
        $rs = $this->field($this->fields)
		->where(array('user_id'=>$alipay_user_id))
		->limit(1)
		->find();
        return $rs ? $rs : false;
    }
    

}