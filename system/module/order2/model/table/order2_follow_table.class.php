<?php
/**
 * 		订单阶段 状态跟踪
 */
class order2_follow_table extends table {

    protected $fields =[
        'follow_id',
        'order_id',
        'follow_status',
        'order_status',
        'create_time',
        'old_status',
        'new_status',
        'admin_id',
    ];
    
    
    protected $pk ="follow_id";
    /**
     * 插入订单状态
     * @return mixed	false：创建失败；int:主键；创建成功
     */
    public function create_follow($data){
        $follow_id = $this->add($data);
        return $follow_id;
    } 
    /**
     * 根据订单ID获取
     * @param int $order_id	    订单ID
     * @return mixed	false：查询失败；array：订单状态信息
     */
    public function get_follow_by_order_id($order_id) {
	$rs = $this->field($this->fields)->where(['order_id'=>$order_id])->order('create_time ASC')->select();
	return $rs ? $rs : [];
    }
    /**
     * 查询记录数
     * @return int  符合查询条件的总数
     */
    public function get_count($where) {
        return $this->where($where)->count('follow_id');
    }
    /**
     * 根据状态来获取订单ID
     */
    public function get_order_id_by_new_status($new_status){
        $rs =$this->field('order_id')->where(['new_status'=>$new_status])->order('create_time ASC')->select();
        return $rs ? $rs : [];
    }

}