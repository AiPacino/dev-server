<?php
/**
 * 收货单
 */
class order2_receive_table extends table {

    protected $fields = [
        'receive_id',
        'business_key',
        'order_id',
        'order_no',
        'goods_id',
        'address_id',
        'receive_status',
        'create_time',
        'update_time',
        'admin_id',
        'receive_time',
        'wuliu_channel_id',
        'wuliu_no',
        'bar_code',
        'wuliu_expense',
        'wuliu_user',
    ];
    
    protected $pk ="receive_id";

  /**
   * 通过ID 获取一条记录
   * @return array
   */
  public function get_by_id($where,$additional=[]) {
      return $this->field($this->fields)->where($where)->find($additional);
  }

    /**
     * 通过订单ID 获取一条记录
     * @return array
     */
    public function get_by_order_id($order_id, $additional=[]) {
        return $this->field($this->fields)->where(array("order_id"=>$order_id))->field($this->fields)->find($additional);
    }


    /**
     * 根据查询条件，查询发货单列表
     * @return mixed  false：查询失败；array：发货单信息
     *
     */
    public function  get_list($data, $additional)
    {
        return $list = $this->field($this->fields)->page($additional['page'])->limit($additional['size'])->where($data)->order($additional['orderby'])->select();
    }
    /**
     * 查询记录数
     * @return int  符合查询条件的总数
     */
    public function get_count($data) {
        $result = $this->where($data)->count($this->pk);
        if($result === false){
            return 0;
        }
        return $result;
    }


}