<?php


hd_core::load_class('api', 'api');
/**
 * 回寄地址列表控制器
 * @access public 
 * @author limin <limin@huishoubao.com.cn>
 * @copyright (c) 2017, Huishoubao
 */
class service_address_control extends api_control {

    protected $member = array();
    
    public function _initialize() {
        parent::_initialize();
    }

    /**
    * 获取回寄地址列表
    * @return $data
    * @author limin
    */
    public function query() {
        $this->service = $this->load->service('order2/return_address');
        $data = $this->service->get_info(zuji\order\Address::AddressOne);
        if(!$data){
            api_resopnse( [], ApiStatus::CODE_50003,'没有找到退货地址' );
        }
        $result['address_list'] = [$data];
        api_resopnse($result, ApiStatus::CODE_0 );
        return;
        
    }
    
}
