<?php
use zuji\order\ServiceStatus;
/**
 * 服务表
 */
class order2_service_table extends table {

    protected $fields = [
        'service_id',
        'order_id',
        'order_no',
        'mobile',
        'user_id',
        'business_key',
        'service_status',
        'begin_time',
        'end_time',
        'create_time',
        'update_time',
        'remark',
    ];
    
    protected $pk ="service_id";

    /**
     * 获取某用户是否有其他有效订单
     */
    public function has_open_service($user_id, $cert_no) {
	$table = $this->getTableName();
        $where = $table.'.`service_status`='.ServiceStatus::ServiceOpen.' AND (O.`user_id`='.$user_id.' OR O.`cert_no`="'.$cert_no.'") AND `mianyajin`>0';
        $result =$this->join(config("DB_PREFIX").'order2 AS O ON '.$table.'.order_id=O.order_id')->where($where)->count($table.'.`service_id`');
        if($result >0)
            return "Y";
        else
            return "N";
    }
   public  function  create($data=array())
   {
     $data['update_time'] =time();
     $data['create_time'] =time();
     $data['service_status']=ServiceStatus::ServiceOpen;
     $result =  $this->add($data);
     return $result;


  }
  /*
   * 修改服务时间
   */
  public  function  update_time($order_no,$end_time)
  {
      $data['update_time'] =time();
      $data['end_time'] =$end_time;
      $result =  $this->limit(1)->where(['order_no'=>$order_no])->save($data);
      return $result;
  
  
  }
  /**
   * 取消订单
   * @param int $order_no
   */
  public  function  cancel($order_id)
  {
      $data['update_time'] =time();
      $data['service_status']=ServiceStatus::ServiceCancel;
      $result =  $this->where(['order_id'=>$order_id])->save($data);
      return $result;
  
  
  }

 
  /**
   * 通过ID 获取一条记录
   * @return array
   */
  public function get_by_id($where,$additional=[]) {
      return $this->field($this->fields)->where($where)->limit(1)->find($additional);
  }
    /**
     * 通过ID 获取一条记录
     * @return array
     */
    public function get_service_info($where) {
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
       return $this->field($this->fields)->where($data)->count();
    }


}