<?php

namespace oms\state;

use oms\operation\CancelOperation;
use oms\operation\FundsAuthorizeOperation;
/**
 * 门店已确认订单
 *
 * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
 */
class StoreConfirmedWithhodeState extends State {
    
    public function get_state(){
	return State::StoreConfirmed;
    }
    
    public function get_name(){
	return '已确认';
    }
    
    public function get_client_name(){
	return '待付款';
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
    //判断是否允许资金授权
    public function allow_to_funds_authorize(){
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
		\zuji\debug\Debug::error(\zuji\debug\Location::L_Order, '取消订单-返还优惠券', [$b]);
        if( $b === false ){
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
     * 资金预授权
     * @return bool
     */
    public function funds_authorize(){
        // 操作前状态
        $old_state = $this->get_state();
        $operation = new FundsAuthorizeOperation($this->Order);
        $b =$operation->update();
        if($b == false){
            return false;
        }
        // 更新 订单状态（门店订单 资金预授权成功）
        $State = new StoreFundsAuthorizedState($this->Order);
        // 操作后状态值
        $new_state = $State->get_state();
        $this->Order->set_state_transition(new StateTransition('资金授权', $old_state, $new_state));
        return true;
    }
}

