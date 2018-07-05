<?php
use zuji\order\RefundStatus;
use zuji\order\ReturnStatus;
/**
 * 退款单表
 * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
 * @copyright (c) 2017, Huishoubao
 */
class order2_refund_table extends table {
   
    protected $fields = [
        'refund_id',
        'order_id',
        'order_no',
        'refund_no',
        'out_refund_no',
        'refund_amount',
        'user_id',
        'mobile',
        'payment_amount',
        'should_amount',
        'should_remark',
        'should_admin_id',
        'goods_id',
        'payment_id',
        'refund_status',
        'business_key',
        'refund_type',
        'account_name',
        'account_no',
        'really_name',
        'create_time',
        'update_time',
        'refund_time',
        'refund_remark',
        'payment_channel_id',
    ];
    
    protected $pk ="refund_id";

    /**
     * 查询列表
     * @return array 
     */
    public function get_list($data=[],$additional=[]) {
        $result = $this->field($this->fields)->page($additional['page'])->limit($additional['size'])->order($additional['orderby'])->where($data)->select();
        if($result){
            return $result;
        }       
        return [];
    }
    
    /**
     * 通过ID 获取一条记录
     * @return array
     */
    public function get_by_id($where,$additional=[]) {
        return $this->field($this->fields)->where($where)->find($additional);
    }

    public function get_info($where,$lock=false) {
        return $this->field($this->fields)->where($where)->find(['lock'=>$lock]);
    }
    

    /**
     * 
     * 根据条件查询总条数
     */
    public function get_count($data=[]){
        $result = $this->where($data)->count($this->pk);
        if($result === false){
            return 0;
        }
        return $result;
    }
}