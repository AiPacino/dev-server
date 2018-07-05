<?php
use zuji\order\ServiceStatus;
/**
 * 服务表
 */
class order2_image_table extends table {

    protected $fields = [
        'img_id',
        'order_id',
        'card_hand',
        'card_positive',
        'card_negative',
        'goods_delivery',
        'create_time'
    ];
    
    protected $pk ="img_id";

    
    public  function  create($data=array()){
      
       $data['create_time'] =time();
       $result =  $this->add($data);
       return $result;
    }

    /**
     * 通过ID 获取一条记录
     * @return array
     */
    public function get_img_info($where) {
        return $this->where($where)->field($this->fields)->limit(1)->find();
    }

    /**
     * 通过订单ID 获取一条记录
     * @return array
     */
    public function get_by_order_id($order_id) {
        return $this->where(array('order_id'=>$order_id))->field($this->fields)->limit(1)->find();
    }


    /**
     * 根据查询条件，查询发货单列表
     * @return mixed  false：查询失败；array：图片列表
     *
     */
    public function  get_list($order_id)
    {
        return $list = $this->field($this->fields)->where(['order_id'=>$order_id])->select();
    }

    /**
     * 查询记录数
     * @return int  符合查询条件的总数
     */
    public function get_count($data) {
       return $this->field($this->fields)->where($data)->count();
    }


}