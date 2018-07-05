<?php

namespace oms\state;
use oms\operation\StoreConfirmedOperation;
use oms\operation\CancelOperation;
/**
 * PassedState  审核通过
 *
 * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
 */
class StorePassedState extends State {
    
    public function get_state(){
	return State::StorePassed;
    }
    
    public function get_name(){
	return '审核通过';
    }
    
    public function get_client_name(){
	return '待面签';
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
            ]
        ];
    }
    //判断是否允许取消订单
    public function allow_to_cancel_order(){
        return true;
    }
    //判断是否允许确认订单
    public function allow_to_store_confirm_order() {
        return true;
    }
    
    /**
     * 取消订单
     * @param array $data     【必须】取消保存数据
     * [
     *      'order_id' =>'',            【必须】int；订单ID
     *      'reason_id' => '',          【可选】int；取消原因ID
     *      'reason_text' => '',        【可选】string；附加原因描述，可以为空
     * ]
     * @return boolean
     * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
     */
    public function cancel_order( $data ) {
        // 操作前状态
        $old_state = $this->get_state();
         
        $cancel =new CancelOperation($data);
        $b = $cancel->update();
        if( $b === false ){
			set_error('取消订单失败');
            // 取消失败
            return false;
        }
		// 返还优惠券
		$operation = new \oms\operation\ReturnCouponOperation( $this->Order );
		$operation->update();
        if( $b === false ){
			set_error('返还优惠券失败');
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
     * 确认订单
     * @return boolean
     * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
     */
    public function store_confirm_order($data){
        // 操作前状态
        $old_state = $this->get_state();
        $check =new StoreConfirmedOperation($data);
        $b = $check->update();
        if( $b == false ){
            return false;
        }
        // 更新 订单状态
        $State = new StoreConfirmedState($this->Order);
        // 操作后状态值
        $new_state = $State->get_state();
        $this->Order->set_state_transition(new StateTransition('审核通过', $old_state, $new_state));
        return true;
    }
}

