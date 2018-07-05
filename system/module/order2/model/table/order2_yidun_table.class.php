<?php
/**
 * 		订单yidun 记录表
 */
class order2_yidun_table extends table {

    protected $fields =[
        'id',
        'order_id',
        'verify_id',
        'verify_uri',
        'decision',
        'score',
        'strategies',
        'level',
        'yidun_id',
    ];
    
    
    protected $pk ="id";
    /**
     * 根据订单ID获取
     * @param int $order_id	    订单ID
     * @return mixed	false：查询失败；array：订单状态信息
     */
    public function get_yidun_by_order_id($order_id) {
	$rs = $this->field($this->fields)->where(['order_id'=>$order_id])->find();
	return $rs ? $rs : [];
    }


}