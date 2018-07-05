<?php
use zuji\order\ReturnStatus;
/**
 * 退货表
 * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
 * @copyright (c) 2017, Huishoubao
 */
class order2_return_table extends table {

    protected $fields = [
        'return_id',
    	'business_key',
    	'order_id',
    	'order_no',
        'user_id',
    	'goods_id',
        'address_id',
    	'reason_id',
    	'reason_text',
        'loss_type',
    	'return_status',
	    'admin_id',
        'return_check_remark',
    	'return_check_time',
    	'create_time',
    	'update_time',
    ];
    
    protected $pk ="return_id";

    /**
     * 通过ID 获取一条记录
     * @return array
     */
    public function get_info($return_id, $additional=[]) {
        return $this->field($this->fields)->where(['return_id' => $return_id])->limit(1)->find($additional);
    }
    /**
     * 通过订单编号 获取一条记录
     * @return array
     */
    public function get_info_by_order_no($order_no, $additional=[]) {
        return $this->field($this->fields)->limit(1)->where(['order_no'=>$order_no])->find($additional);
    }

    /**
     * 根据退货列表
     * @return array   列表，没有查询到时，返回空数组
     */
    public function get_list($where=[],$additional=[]) {
        $parcels = $this->field($this->fields)->page($additional['page'])->limit($additional['size'])->order($additional['orderby'])->where($where)->select();
        if($parcels){
            return $parcels;
        }
        return [];
    }
    /**
     * 根据条件查询总条数    
     */
    public function get_count($where=[]){
        $result = $this->where($where)->count($this->pk);
        if($result === false){
            return 0;
        }
        return $result;
    }
     
}