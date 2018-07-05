<?php
namespace oms\state;
use zuji\Config;
use oms\operation\CancelOperation;
use oms\operation\ConfirmOrderOperation;
use oms\operation\FundsThawedOperation;
use oms\operation\RemoveAuthorizeOperation;


/**
 * FundsAuthorizedState  资金已授权
 *
 * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
 */
class FundsAuthorizedState extends State {
    
    public function get_state(){
	return State::FundsAuthorized;
    }
    
    public function get_name(){
		return '资金已授权';
    }
    
    public function get_client_name(){
	    return '已支付';
    }
    
    //后端操作列表
    public function get_operation_list(){
            return [
                [
                    'mca' => 'order2/order/remove_authorize',
                    'name' => '解除资金预授权',
                    'params' => ['order_id' => $this->Order->get_order_id()],
                    'iframe_width' => 350,
                    'is_show' => true
                ],
                [
                    'mca'=>'order2/delivery/create',
                    'name'=>'确认订单',
                    'params' => ['order_id' => $this->Order->get_order_id()],
                    'iframe_width' => 300,
                    'is_show' => true
                ]
            ];
    }

    public function allow_to_confirm_order() {
        return true;
    }
    /*
     * 是否允许解除资金预授权
     */
    public function allow_to_remove_authorize(){
        return true;
    }


    /**
     * 线上订单确认
     * <p>确认订单后，创建发货单，进入发货流程</p>
     */
    public function confirm_order() {
        // 操作前状态
        $old_state = $this->get_state();
        $operation =new ConfirmOrderOperation( $this->Order );
        $b = $operation->update();
        if( $b == false ){
            return false;
        }
        // 更新 订单状态
        $State = new OrderConfirmedState($this->Order);
        // 操作后状态值
        $new_state = $State->get_state();
        $this->Order->set_state_transition(new StateTransition('订单确认', $old_state, $new_state));
        return true;
    }
    /**
     * 解除资金预授权
     * @return boolean
     * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
     */
    public function remove_authorize($data){
        // 操作前状态
        $old_state = $this->get_state();
        $Operation =new FundsThawedOperation($this->Order,$data);
        $b = $Operation->update();
        if( $b == false ){
            return false;
        }
        // 更新 订单状态
        $State = new FundsThawedState($this->Order);
        // 操作后状态值
        $new_state = $State->get_state();
        $this->Order->set_state_transition(new StateTransition('解冻资金预授权', $old_state, $new_state));

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
}
