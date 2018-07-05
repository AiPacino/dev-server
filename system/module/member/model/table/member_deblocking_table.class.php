<?php
/**
 * 	解封操作日志
 */
class member_deblocking_table extends table {

    protected $fields =[
        'block_id',
        'member_id',
        'deblocking_time',
        'admin_id',
        'admin_remark',
    ];
    
    
    protected $pk ="block_id";
    /**
     * 插入订单状态
     * @return mixed	false：创建失败；int:主键；创建成功
     */
    public function create_block($data){
        $block_id = $this->add($data);
        return $block_id;
    } 
    /**
     * 根据会员ID获取
     * @param int $member_id	    会员ID
     * @return mixed	false：查询失败；array：订单状态信息
     */
    public function get_by_member_id($member_id,$additional=[]) {
	$rs = $this->page($additional['page'])->limit($additional['size'])->field($this->fields)->order("deblocking_time DESC")->where(['member_id'=>$member_id])->select();
	return $rs ? $rs : [];
    }

}