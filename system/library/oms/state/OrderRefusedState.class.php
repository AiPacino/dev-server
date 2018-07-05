<?php

namespace oms\state;

/**
 * OrderRefused  用户拒签
 *
 * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
 */
class OrderRefusedState extends State {
    
    public function get_state(){
	return State::OrderRefused;
    }
    
    public function get_name(){
	return '用户拒签';
    }
    
    public function get_client_name(){
	return '租用中';
    }
    
    
    //后端操作列表
    public function get_operation_list(){
        return [];
    }
}

