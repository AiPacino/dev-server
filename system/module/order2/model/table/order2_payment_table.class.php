<?php
use zuji\order\PaymentStatus;
/**
 * 支付单表
 * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
 * @copyright (c) 2017, Huishoubao
 */
class order2_payment_table extends table {
    protected  $fields = [
        'payment_id',
        'business_key',
        'order_id',
        'order_no',
        'payment_channel_id',
        'amount',
        'payment_amount',
        'payment_time',
        'payment_status',
        'create_time',
        'update_time',
        'payment_text',
        'trade_no',
        'apply_status',
        'apply_time',
        'admin_id',
        'admin_remark',
        'payment_no',

    ]; 
    
    protected $pk ="payment_id";
    /**
     * 支付单申请退款中
     */
    public function update_apply($payment_id,$data){     
        $result =$this->limit(1)->where(['payment_id'=>$payment_id])->save($data);
        return $result;
    }

    /**
     * 生成支付单
     */
    public function create_data($data){
        $data['create_time']=time();
        $data['update_time']=0;
        $result =$this->add($data);
        return $result;
    }
    
    /**
     * 修改交易单号
     * 
     */
    public function edit_trade_no($payment_id, $trade_no){
        return $this->where(['payment_id'=>$payment_id])->save(['trade_no'=>$trade_no]);
    }
    /**
     * 支付成功
     * @param int $payment_id	    支付单ID
     * @param array $data	    支付数据
     * [
     *	    'payment_amount' => '',   // 支付金额；单位：分
     *	    'payment_status' => '', // 支付状态；
     *	    'payment_time' => '',   // 支付时间
     * ]
     * @return mixed  
     */
    public function payment_successful($payment_id,$data){
        $data['update_time']=time();
        return $this->where(['payment_id'=>$payment_id])->limit(1)->save($data);
    }
    /**
     * 支付失败
     */
    public function payment_failed($data){
        $data['update_time']=time();
        $data['payment_status']=PaymentStatus::PaymentFailed;
        return $this->save($data);
    }
    /**
     * 更新支付渠道
     * @param int $payment_id	支付单主键ID
     * @param int $payment_channel_id	支付渠道ID
     * @return boolean
     */
    public function update_payment_channel_id($payment_id, $payment_channel_id){
        $data['payment_channel_id']=$payment_channel_id;
        $data['update_time']=time();
        return $this->where(['payment_id'=>$payment_id])->limit(1)->save($data);
    }
    
    /**
     * 根据条件查询总条数
     */
    public function get_count($data=[]){
        return $this->field($this->fields)->where($data)->count();
    }
     public function get_info($where,$additional=[]){
         return $this->field($this->fields)->where($where)->find($additional);
     }
    /**
     * 根据订单ID 查询数据
     */
    public function get_info_by_order_id($order_id){
        return $this->where(array('order_id'=>$order_id))->field($this->fields)->find();
    }
    /**   
     * 列表，没有查询到时，返回空数组
     */
    public function get_list($data=[],$additional=[]) {
        return $this->field($this->fields)->page($additional['page'])->limit($additional['size'])->order($additional['orderby'])->where($data)->select();
    }

    /**
     * 查询一条记录
     */
    public function get_info_orderid($data){
        return $this->field($this->fields)->where($data)->find();
    }

}