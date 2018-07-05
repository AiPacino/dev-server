<?php
namespace oms\state;

use oms\operation\CancelOperation;
use oms\operation\CheckOperation;
use oms\operation\StoreCheckOperation;
use zuji\Config;


/**
 * StoreCreatedState  门店已下单
 *
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
class StoreCreatedState extends State {
    
    public function get_state(){
	return State::OrderCreated;
    }
    
    public function get_name(){
		return '已下单';
    }
    
    public function get_client_name(){
        return '待审批';
    }

    //判断是否允许 取消
    public function allow_to_cancel_order(){
        return true;
    }
    //判断是否允许 审核
    public function allow_to_store_check_order() {
        return true;
    }
    
    //后端操作列表
    public function get_operation_list(){
        return [
            [
                'mca'=>'order2/order/cancel_order',
                'name'=>'取消订单',
                'params' => ['order_id' => $this->Order->get_order_id()],
                'iframe_width' => 300,
                'is_show' => true
            ],
            [
                'mca'=>'order2/order/store_check_order',
                'name'=>'审核订单',
                'params' => ['order_id' => $this->Order->get_order_id()],
                'iframe_width' => 300,
                'is_show' => false
            ]
    
        ];
    }
    
    /**
     * 取消订单
     * @param array $data     【必须】取消保存数据
     * [
     *      'order_id' =>'',            【必须】int；订单ID
     *      'reason_id' => '',          【可选】int；取消原因ID
     *      'reason_text' => '',        【可选】string；附加原因描述，可以为空
     * ]
     */
    public function cancel_order( $data ) {
    	// 操作前状态
    	$old_state = $this->get_state();
		$cancel =new CancelOperation($data);
    	$b = $cancel->update();
    	if( $b === false ){
    	    // 取消失败
    	    return false;
    	}
    	// 更新 订单状态
    	$State = new CanceledState($this->Order);
    	// 操作后状态值
    	$new_state = $State->get_state();
    	$this->Order->set_state_transition(new StateTransition('取消订单', $old_state, $new_state));
    	return true;
    }
    /**
     * 订单审核
     */
    
    public function store_check_order($data=[]){
       // 操作前状态
    	$old_state = $this->get_state();
    	$order_id =$this->Order->get_order_id();    	
    	$check =new StoreCheckOperation($order_id);
    	$b = $check->update();
    	if( $b == false ){
    	    return false;
    	}
    	// 更新 订单状态
    	$State = new StorePassedState($this->Order);
    	// 操作后状态值
    	$new_state = $State->get_state();

    	$this->Order->set_state_transition(new StateTransition('审核通过', $old_state, $new_state));
    	return true;
    }

    
}
