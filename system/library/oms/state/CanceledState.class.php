<?php

namespace oms\state;
use oms\operation\FundsAuthorizeOperation;


/**
 * CanceledState  已取消
 *
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
class CanceledState extends State {
    
    public function get_state(){
	return State::OrderCanceled;
    }
    
    public function get_name(){
	return '已取消';
    }
    
    public function get_client_name(){
	return '已取消';
    }
    
    //操作列表
    public function get_operation_list(){
        return [];
    }
    
    //判断是否允许订单支付
    public function allow_to_pay() {
        return true;
    }
    
    //判断是否允许资金授权
    public function allow_to_funds_authorize(){
        return true;
    }
	
    /**
     * 支付
     */
    public function pay(string $trade_channel, array $data, int $trade_id){
        // 操作前状态
        $old_state = $this->get_state();
         
        $operation = new \oms\operation\PayOperation($this->Order,$trade_channel,$data, $trade_id);
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
        // 更新 订单状态
        $State = new FundsAuthorizedState($this->Order);
        // 操作后状态值
        $new_state = $State->get_state();
        $this->Order->set_state_transition(new StateTransition('资金授权', $old_state, $new_state));
        return true;
    }

}