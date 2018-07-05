<?php
namespace oms\state;

use oms\operation\CancelOperation;


/**
 * MiniAlipayCreatedState  小程序已下单
 *
 * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
 */
class MiniAlipayCreatedState extends State {
    
    public function get_state(){
	return State::OrderCreated;
    }
    
    public function get_name(){
		return '已下单';
    }
    
    public function get_client_name(){
	    return '待付款';
    }
    
    //后端操作列表
    public function get_operation_list(){
            return [
                [
                    'mca' => 'order2/order/cancel_order',
                    'name' => '取消订单',
                    'params' => ['order_id' => $this->Order->get_order_id()],
                    'iframe_width' => 300,
                    'is_show' => true
                ]
            ];
    }

    //判断是否允许 取消
    public function allow_to_cancel_order(){
        return true;
    }
    //判断是否允许订单支付
    public function allow_to_pay() {
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
     */
    public function cancel_order( $data ) {
    	// 操作前状态
    	$old_state = $this->get_state();
    	
    	$cancel =new CancelOperation($data);
    	$b = $cancel->update();
    	if( $b == false ){
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
     * 支付
     */
    public function pay(string $trade_channel, array $data, int $trade_id){
        // 操作前状态
        $old_state = $this->get_state();
         
        $operation = new \oms\operation\MiniPayOperation($this->Order);
        $b = $operation->update();
        if( $b == false ){
            // 取消失败
            return false;
        }
        // 更新 订单状态
        $State = new \oms\state\PaymentSuccessState($this->Order);
        // 操作后状态值
        $new_state = $State->get_state();
        $this->Order->set_state_transition(new StateTransition('支付订单', $old_state, $new_state));
        return true;
    }

    
}
