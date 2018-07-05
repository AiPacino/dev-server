<?php
/**
 * 		发货单相关
 *      [Haidao] (C)2013-2099 Dmibox Science and technology co., LTD.
 *      This is NOT a freeware, use is subject to license terms
 *      @author wangqiang<wangqiang@huishoubao.com.cn>
 *      http://www.haidao.la
 *      tel:400-600-2042
 */
class order2_goods_table extends table {

    protected $_validate = array(
        /* array(验证字段1,验证规则,错误提示,[验证条件,附加规则,验证时间]), */
    );

    protected $_auto = array(
        // array(完成字段1,完成规则,[完成条件,附加规则]),
        array('systime','time',1,'function'),
    );
    
    protected $fields = [
        'goods_id',
        'order_id',
        'spu_id',
        'sku_id',
        'sku_name',
        'brand_id',
        'category_id',
        'specs',
        'thumb',
        'imei1',
        'imei2',
        'imei3',
        'serial_number',       
        'zuqi',   
        'zuqi_type',
        'zujin',
        'yajin',
        'mianyajin',
        'yiwaixian',
        'yiwaixian_cost',
        'chengse',
        'create_time',
        'update_time',
    ];
    
    protected $pk='goods_id';

    /**
     *根据订单id获取订单商品信息
     * @param int   $goods_id	订单商品ID
     * @return mixed	false：查询失败；array：商品信息（查看$fields）
     */
    public function  get_info($goods_id)
    {
        $result = $this->field($this->fields)->find($goods_id);
        return $result;
    }
    public function  get_list($where, $additionql)
    {
        $result = $this->where($where)->field($this->fields)->limit($additionql['size'])->select();
        return $result;
    }


    /**
     * 创建 订单商品记录
     * @param array $data
     * @return mixed  false：失败；int：主键ID
     */
    public  function  create($data){
        $result =  $this->add($data);
        return $result;
    }

    
    //-+------------------------------------------------------------------------

    /**
     * 
     * 发货时更新
     */
    public function update_serial($goods_id,$data){
        $data['update_time'] =time();
        $data['goods_id'] =$goods_id;
        $result =  $this->save($data);
        return $result;
    }

    //获取一条记录
    public function fetch_by_name($value,$field){
        $data = array();
        $data['name'] = $value;
        $data['enabled'] = 1;
        $result = $this->where($data)->find();
        if($field) return $result[$field];
        return $result;
    }

     
    /**
     *根据订单order_id，获取订单商品信息
     * @param int   $order_id	    订单ID
     * @return mixed	false：查询失败；array：商品信息（查看$fields）
     */
    public function  get_by_orderid($order_id)
   {
       $result = $this->where(array('order_id'=>$order_id))->find();
       return $result;
    }
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    /**
     *根据订单status获取订单全部商品信息
     */
    public function  getall_by_id($data,$page,$limit)
    {
        $result = $this->page($page)->limit($limit)->order('id ASC')->select();
        return $result;
    }

}