<?php
/**
 * 		会员登陆日志表
 */
class member_login_table extends table {

    protected $fields =[
        'login_id',
        'member_id',
        'login_time',
        'login_ip',
    ];
    
    
    protected $pk ="login_id";
    /**
     * 插入
     * @return mixed	false：创建失败；int:主键；创建成功
     */
    public function create_login($data){
        $login_id = $this->add($data);
        return $login_id;
    } 
    /**
     * 根据订单ID获取
     * @param int $order_id	    订单ID
     * @return mixed	false：查询失败；array：订单状态信息
     */
    public function get_list($where) {
	$rs = $this->field($this->fields)->where($where)->select();
	return $rs ? $rs : [];
    }

}