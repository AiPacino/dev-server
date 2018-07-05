<?php

namespace oms\state;

/**
 * OrderRefunded  已退款
 *
 * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
 */
class OrderRefundedState extends State {
    
    public function get_state(){
	return State::OrderRefunded;
    }
    
    public function get_name(){
	return '已退款';
    }
    
    public function get_client_name(){
	return '已退款';
    }
    
    //操作列表
    public function get_operation_list(){
        return [];
    }
    
}

