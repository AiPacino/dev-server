<?php

namespace oms\state;

/**
 * OrderBuyOutedState  已买断
 *
 * @author maxiaoyu <maxiaoyu@huishoubao.com.cn>
 */
class OrderBuyOutedState extends State {
    
    public function get_state(){
	   return State::OrderBuyOuted;
    }
    
    public function get_name(){
	   return '已买断';
    }
    
    public function get_client_name(){
	   return '已买断';
    }
    //后端操作列表
    public function get_operation_list(){
        return [];
    }
}

