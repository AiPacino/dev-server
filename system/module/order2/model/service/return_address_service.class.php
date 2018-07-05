<?php

/**
 * 	   退货地址
 *   @author limin <limin@huishoubao.com.cn>
 */
class return_address_service extends service {

	public function _initialize() {
        $this->redis = zuji\cache\Redis::getInstans();
        $this->service = $this->load->service('admin/setting');
	}
    /**
     * 根据地址ID获取单条退货地址
     * @param string $address_id    索引
     * @return mixed	false：失败；array：数据
     * [
     *	    'id' => '',	           【必须】int  地址ID
     *	    'seller_address' => '',	   【必须】string  地址信息
     *	    'seller_name' => '',	   【必须】string	  收货人
     * 	    'seller_mobile' => '',    【必须】string	  收货人手机号码
     * ]
     */
    public function get_info($address_id){
        $result = [];
        $result = $this->redis->hget("return_address:get",$address_id);
        if($result){
            return json_decode($result,true);
        }
        else{
            $data = $this->service->get();
            if(!$data){
                return;
            }
            $address_info = [
                'id' => zuji\order\Address::AddressOne,
                'address' => $data['seller_address'],
                'name'   => $data['seller_name'],
                'mobile'  => $data['seller_mobile'],
            ];
            $this->redis->hset("return_address:get",$address_id,json_encode($address_info));
            return $address_info;
        }

    }
    //获取多条退货地址
    public function get_list(){
        $data = $this->service->get();
        $address_list[] = [
            'id' => zuji\order\Address::AddressOne,
            'address' => $data['seller_address'],
            'name'   => $data['seller_name'],
            'mobile'  => $data['seller_mobile'],
        ];
        return $address_list;
    }
}