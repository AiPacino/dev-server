<?php
namespace oms\state;
use oms\operation\CancelOperation;
use oms\operation\FundsThawedOperation;
use oms\operation\StoreReturnOperation;
use oms\operation\CreateRefundOperation;
use oms\operation\CloseServiceOperation;
use zuji\Config;

/**
 * StoreInServiceState  门店租用中
 *
 * @author maxiaoyu <maxiaoyu@huishoubao.com.cn>
 */
class StoreInServiceState extends State {
    
    public function get_state(){
       return State::OrderInService;
    }
    
    public function get_name(){
       return '租用中';
    }
    
    public function get_client_name(){
       return '租用中';
    }
    
    //后端操作列表
    public function get_operation_list(){
        $payment_type_id =$this->Order->get_payment_type_id();
        if($payment_type_id == Config::FlowerStagePay){
            return [
                [
                    'mca'=>'order2/refund/create_refund',
                    'name'=>'申请退款',
                    'params' => ['order_id' => $this->Order->get_order_id()],
                    'iframe_width' => 300,
                    'is_show' => true
                ],
            ];
        }else if($payment_type_id == Config::WithhodingPay){
            return [
                [
                    'mca' => 'order2/order/remove_authorize',
                    'name' => '解除资金预授权',
                    'params' => ['order_id' => $this->Order->get_order_id()],
                    'iframe_width' => 350,
                    'is_show' => true
                ]
            ];
        }
    }

    //判断门店是否允许换货
    public function allow_to_store_return(){
        return true;
    }
    
    //判断是否可以申请退款
    public function allow_to_create_refund(){
        if($this->Order->get_payment_type_id() ==Config::FlowerStagePay){
            return true;
        }else{
            return false;
        }
    }
    //判断是否允许 取消
    public function allow_to_cancel_order(){
        $payment_type_id =$this->Order->get_payment_type_id();
        if($payment_type_id == Config::WithhodingPay){
            return true;
        }else{
            return false;
        }
    }
    
    //是否允许关闭 订单/服务
    public function allow_to_close_service(){

        return true;
    }

    /*
     * 是否允许解除资金预授权
     */
    public function allow_to_remove_authorize(){
        if($this->Order->get_payment_type_id() ==Config::WithhodingPay){
            return true;
        }else{
            return false;
        }
    }
    
    /**
     * 换货
     * @param array $data
     * [
     *      'order_id'=>'',     // 【必须】订单ID
     *      'imei'=>'',         // 【必须】IMEI号
     * ]
     * @return boolean
     * @author maxiaoyu <maxiaoyu@huishoubao.com.cn>
     */
    public function store_return($data){
        // 操作前状态
        $old_state = $this->get_state();
        $cancel =new StoreReturnOperation($data);
        $b = $cancel->update();
        if( $b == false ){
            return false;
        }
        // 更新 订单状态
        $State = new OrderInServiceState($this->Order);
        // 操作后状态值
        $new_state = $State->get_state();
        $this->Order->set_state_transition(new StateTransition('门店换货', $old_state, $new_state));
        return true;
    }
    
    /**
     * 申请退款
     * @param array $data
     * [
     *      'order_id'=>'',     //【必须】订单ID
     *      'payment_id'=>'',   //【必须】支付单ID
     *      'should_amount'=>'',//【必须】应退金额
     *      'should_remark'=>'',//【必须】修改备注
     * ]
     * @return boolean
     * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
     */
    public function create_refund($data){
        // 操作前状态
        $old_state = $this->get_state();
        $create_refund =new CreateRefundOperation($data);
        $b = $create_refund->update();
        if( $b == false ){
            return false;
        }
        // 更新 订单状态
        $State = new OrderRefundingState($this->Order);
        // 操作后状态值
        $new_state = $State->get_state();
        $this->Order->set_state_transition(new StateTransition('申请退款', $old_state, $new_state));
        return true;
    
    }
    /**
     * 关闭服务
     * @param array $data
     * [
     *      'remark'=>'',//【必须】 修改备注
     * ]
     */
    public function close_service($data){
        // 操作前状态
        $new_data =[
            'remark'=>$data['remark'],
            'order_id'=>$this->Order->get_order_id(),
            'service_id'=>$this->Order->get_service_id(),
        ];
        $old_state = $this->get_state();
        $operation =new CloseServiceOperation($new_data,$this->Order);
        $b = $operation->update();
        if( $b == false ){
            return false;
        }
        // 更新 订单状态
        $State = new ClosedState($this->Order);
        // 操作后状态值
        $new_state = $State->get_state();
        $this->Order->set_state_transition(new StateTransition('关闭服务', $old_state, $new_state));
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
