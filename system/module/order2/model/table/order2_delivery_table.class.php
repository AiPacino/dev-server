<?php
/**
 * 		发货单相关
 */
use zuji\order\DeliveryStatus;
class order2_delivery_table extends table {

    /**
     * 发货单表的数据库字段
     */
    protected $fields = [
        'delivery_id',
        'business_key',
        'order_id',
        'order_no',
        'goods_id',
        'address_id',
        'delivery_status',
        'pause',
        'wuliu_channel_id',
        'wuliu_no',
        'protocol_no',
        'admin_id',
        'delivery_time',
        'create_time',
        'update_time',
        'delivery_remark',
        'confirm_time',
        'confirm_remark',
        'confirm_admin_id',
        'evaluation_id',
        'refuse_remark',
    ];
    /**
     * 主键id
     */
    protected $pk = 'delivery_id';

    /**
     * 根据检测单ID，获取发货单信息
     * @param int $id   检测 ID
     * @return mixed  false：查询失败；array：发货单信息
     * [
     *      '' => '',
     * ]
     */
    public function  get_delivery_evaluation($evaluation_id, $additional=[])
    {
        return $this->field($this->fields)->where(['evaluation_id' => $evaluation_id])->find($additional);
    }
    
    /**
     * 根据发货单ID，获取发货单信息
     * @param int $id   发货订单ID
     * @return mixed  false：查询失败；array：发货单信息
     * [
     *      '' => '',
     * ]
     */
    public function  get_info($delivery_id, $additional=[])
    {
        return $this->field($this->fields)->where(['delivery_id' => $delivery_id])->find($additional);
    }
    /**
     * 根据订单ID，获取发货单信息
     * @param int $id   订单ID
     * @return mixed  false：查询失败；array：发货单信息
     * [
     *      '' => '',
     * ]
     */
    public function  get_info_by_order_id($order_id,$business_key,$evaluation_id =0, $additional=[])
    {
        $where = [
            'order_id'=>$order_id,
            'business_key'=>$business_key,
            'evaluation_id'=>$evaluation_id,
        ];
        return $this->where($where)->field($this->fields)->find($additional);
    }
    /**
     *创建发货单
     *@author wuhaiyan <wuhaiyan@huishoubao.com.cn>
     */
    public function create_delivery($where){
     $where['create_time'] = time();
     $where['update_time'] = 0;
     $result =  $this->add($where);
     return $result;
  }
  /**
   *点击发货
   *@author wuhaiyan <wuhaiyan@huishoubao.com.cn>
   */
  public function send($where){

      $where['update_time'] = time();
      $where['delivery_time'] = time();
      $where['delivery_status'] = DeliveryStatus::DeliverySend;
      $result =  $this->save($where);
      return $result;
  }
  /**
   *点击收货
   *@author wuhaiyan <wuhaiyan@huishoubao.com.cn>
   */
  public function update_confirmed($delivery_id,$data){
  
      $result =  $this->limit(1)->where(['delivery_id'=>$delivery_id])->save($data);
      return $result;
  }

    /**
     * 查询列表
     * @return array 
     */
    public function get_list($where=[],$additional=[]) {
        $delivery_list = $this->page($additional['page'])->limit($additional['size'])->order($additional['orderby'])->field($this->fields)->where($where)->select();
        if($delivery_list){
            return $delivery_list;
        }       
        return [];
    }
  
    /**
     * 根据条件查询总条数
     */
    public function get_count($where=[]){
        $evaluation_count = $this->where($where)->count();
        if($evaluation_count === false){
            return 0;
        }
        return $evaluation_count;
    }

    /**
     * 检测表根据主键更新
     * @param array $data                                      【至少两个参数】
     * array(
     *	    'delivery_id' => '',   //int                                【必须】
     *	    'admin_id' => '',//int 管理员ID                             【可选】
     *	    'user_id' => '',//int 用户id                                【可选】
     *	    'address_id' => '',//int 地址id                             【可选】 
     *	    'delivery_status' => '',//int 发货状态                      【可选】 
     *	    'pause' => '',//int 暂停状态                                【可选】 
     *	    'wuliu_channel_id' => '',//物流渠道id                       【可选】
     *	    'wuliu_no' => '',//物流单号                                 【可选】
     *	    'delivery_time' => '',//发货时间戳	                       【可选】 
     *	    'refuse_remark' => '',//string 拒签备注                     【可选】 
     * )
     * @return boolean  true :成功  false:失败
     */
    public function update( $data ){
        //更新数据过滤
        $data = filter_array($data, [
            'delivery_id' => 'required|is_id',
            'admin_id' => 'required|is_int',
            'user_id' => 'required|is_int',
            'address_id' => 'required|is_int',
            'delivery_status' => 'required|is_int',
            'pause' => 'required|is_int',
            'wuliu_channel_id' => 'required|is_int',
            'wuliu_no' => 'required|is_int',
            'protocol_no' => 'required|is_numeric',
            'delivery_time' => 'required|is_int',
            'refuse_remark' => 'required',
        ]);
        //至少包含id和另外一个参数才能更新
        if( !isset($data['delivery_id']) || count($data) < 2 ) {
            set_error('检测表更新数据有误');
            return false;
        }
        //拼接更新时间
        $data['update_time'] = time();
        return $this->save($data);
    }
}