<?php
/**
 * 		订单地址模型
 */
class order2_address_table extends table {

    protected $fields =[
        'address_id',
        'order_id',
        'user_id',
        'name',
        'mobile',
        'province_id',
        'city_id',
        'country_id',
        'address',
        'zipcode',
        'remark',
    ];


    /**
     * 插入订单地址表
     * @return mixed	false：创建失败；int:主键；创建成功
     */
    public function create($data){
        $address_id = $this->add($data);
        return $address_id;
    }
    
    
    public function edit($address_id,$data){
	return $this->where(['address_id'=>$address_id])->limit(1)->save($data);
    }
    
    /**
     * 获取一条订单的地址
     * @param int $id	    主键ID
     * @return mixed	false：查询失败或订单收地址不存在；array：收货地址信息
     * [
     *      'address_id' => '',		//【必须】  主键ID
     *      'order_id' => '',	//【必须】  订单ID
     *      'user_id' => '',	//【必须】  用户ID
     *      'mobile' => '',	//【必须】  手机号
     *      'province_id' => '',//【必须】  省份ID
     *      'city_id' => '',	//【必须】  城市ID
     *      'country_id' => '',	//【必须】  区县ID
     *      'address' => '',	//【必须】  详细地址
     *      'zipcode' => '',	//【必须】  邮政编码
     * ]
     */
    public function get_info($id, $additional=[]) {
	$rs = $this->where(['address_id'=>$id])->find($additional);
	return $rs ? $rs : false;
    }
    /**
     * 获取一条订单的地址
     * @param int $id	    订单ID
     * @return mixed	false：查询失败或订单收地址不存在；array：收货地址信息
     * [
     *      'address_id' => '',		//【必须】  主键ID
     *      'order_id' => '',	//【必须】  订单ID
     *      'user_id' => '',	//【必须】  用户ID
     *      'mobile' => '',	//【必须】  手机号
     *      'province_id' => '',//【必须】  省份ID
     *      'city_id' => '',	//【必须】  城市ID
     *      'country_id' => '',	//【必须】  区县ID
     *      'address' => '',	//【必须】  详细地址
     *      'zipcode' => '',	//【必须】  邮政编码
     * ]
     */
    public function get_info_by_order_id($order_id, $additional=[]) {
        $rs = $this->where(['order_id'=>$order_id])->find($additional);
        return $rs ? $rs : false;
    }
    /**
     * 根据查询条件，查询订单收货地址列表
     * @param array $where
     * [
     *	    'address_id' => '',	    【可选】
     * ]
     * @param array $additional
     * [
     *	    'page' => '1',
     *	    'size' => '20',
     *	    'orderby' => '',
     * ]
     * @return array	订单收货地址列表（键值参考 get_info() 方法）
     */
    public function get_list( $where, $additional ){
        return $list = $this->where($where)->page($additional['page'])->limit($additional['size'])->order($additional['orderby'])->select();
    
    }

}