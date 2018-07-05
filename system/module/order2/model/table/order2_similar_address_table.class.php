<?php
/**
 * 		订单地址相似 记录表
 */
class order2_similar_address_table extends table {

    protected $fields =[
        'id',
        'order_id',
        'user_id',
        'order_no',
        'percent',
    ];
    
    
    protected $pk ="id";
    /**
     * 根据userID获取
     * @param int $user_id	    用户ID
     * @return mixed	false：查询失败；array：订单状态信息
     */
    public function get_similar_by_user_id($user_id) {
	$rs = $this->field($this->fields)->where(['user_id'=>$user_id])->select();
	return $rs ? $rs : [];
    }


}