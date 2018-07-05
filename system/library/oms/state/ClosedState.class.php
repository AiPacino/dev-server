<?php

namespace oms\state;

use oms\operation\OpenServiceOperation;
/**
 * ClosedState  已关闭
 *
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
class ClosedState extends State {
    
    public function get_state(){
	return State::OrderClosed;
    }
    
    public function get_name(){
	return '已关闭';
    }
    
    public function get_client_name(){
	return '已关闭';
    }
    
    //是否允许开启 订单/服务
    
    public function allow_to_open_service(){
        return true;
    }
    
    //操作列表
    public function get_operation_list(){
        return [];
    }
    /**
     * 开启服务
     * @param array $data
     * [
     *      'remark'=>'',//【必须】 修改备注
     * ]
     */
    public function open_service($data){      
        // 操作前状态
        $new_data =[
            'remark'=>$data['remark'],
            'order_id'=>$this->Order->get_order_id(),
            'service_id'=>$this->Order->get_service_id(),
        ]; 
        $old_state = $this->get_state(); 
        $operation =new OpenServiceOperation($new_data);
        $b = $operation->update();
        if( $b == false ){
            return false;
        }
        // 更新 订单状态
        $State = new OrderInServiceState($this->Order);
        // 操作后状态值
        $new_state = $State->get_state();
        $this->Order->set_state_transition(new StateTransition('开启服务', $old_state, $new_state));
        return true; 
    }
}

