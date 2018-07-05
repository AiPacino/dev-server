<?php
namespace oms\state;
/**
 * OrderBuyOutingState  买断中
 *
 * @author maxiaoyu <maxiaoyu@huishoubao.com.cn>
 */
class OrderBuyOutingState extends State {
    
    public function get_state(){
	   return State::OrderBuyOuting;
    }
    
    public function get_name(){
	   return '买断中';
    }
    
    public function get_client_name(){
	   return '买断中';
    }
 	
    //判断是否允许买断
    public function allow_to_buyout(){
       return true;
    }
    
    /**
     * 已买断
     */
    public function buyout($data){
        echo "已买断";
    }
    
    //操作列表
    public function get_operation_list(){
        return [];
    }
    
}

