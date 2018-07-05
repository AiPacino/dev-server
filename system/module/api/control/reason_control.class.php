<?php


hd_core::load_class('api', 'api');
/**
 * 问题原因列表控制器
 * @access public 
 * @author limin <limin@huishoubao.com.cn>
 * @copyright (c) 2017, Huishoubao
 */
class reason_control extends api_control {

    protected $member = array();
    
    public function _initialize() {
        parent::_initialize();
        $this->service = $this->load->service('reason/reason');
    }

    /**
    * 获取问题
    * @return $data
    * @author limin
    */
    public function query() {
        
        $params = $this->params;
 
        if($params['type'] == 'ORDER_CANCEL'){
            $data = $this->service->get_order_cancel();
            api_resopnse( array('reason_list'=>$data), ApiStatus::CODE_0 );
        }
        else if($params['type'] == 'ORDER_RETURN'){
            $data = $this->service->get_order_return();
            api_resopnse( array('reason_list'=>$data), ApiStatus::CODE_0 );
        }
        else
        {
            api_resopnse( [], ApiStatus::CODE_20001,'type参数必须，或者值错误');
        }
        return;
        
    }
    
}
