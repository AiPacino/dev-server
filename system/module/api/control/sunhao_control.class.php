<?php


hd_core::load_class('api', 'api');
/**
 * 设备损耗列表控制器
 * @access public 
 * @author limin <limin@huishoubao.com.cn>
 * @copyright (c) 2017, Huishoubao
 */
class sunhao_control extends api_control {

    protected $member = array();
    
    public function _initialize() {
        parent::_initialize();
        $this->service = $this->load->service('sunhao/sunhao');
    }

    /**
    * 获取损耗列表
    * @return $data
    * @author limin
    */
    public function query() {

        $data = $this->service->get_lists();
        api_resopnse( array('sunhao_list'=>$data), ApiStatus::CODE_0 );
        return;
        
    }
    
}
