<?php
namespace oms\state;
use oms\operation\CreateRefundOperation;
use oms\operation\ConfirmOrderOperation;
use oms\operation\CancelOperation;
use oms\operation\MiniAlipayCancelOperation;
use oms\operation\MiniAlipayRefundOperation;
use zuji\Config;

/**
 * PaymentSuccessState  线上用户支付成功
 *
 * @author maxiaoyu <maxioayu@huishoubao.com.cn>
 */
class PaymentSuccessState extends State {
    
    public function get_state(){
	   return State::PaymentSuccess;
    }
    
    public function get_name(){
	   return '已支付';
    }
    
    public function get_client_name(){
	   return '待发货';
    }
    
    //后端操作列表
    public function get_operation_list(){
        if ($this->Order->get_payment_type_id() == Config::MiniAlipay){
            $cancel_url ='order2/order/cancel_order';
            $name ="取消订单";
        }else{
            $cancel_url ='order2/refund/create_refund';
            $name ="申请退款";
        }

        return [
            [
                'mca'=>$cancel_url,
                'name'=>$name,
                'params' => ['order_id' => $this->Order->get_order_id()],
                'iframe_width' => 300,
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
	//是否允许申请退款
	public function allow_to_create_refund(){
	    return true;
	}
    //判断是否允许 取消
    public function allow_to_cancel_order(){
        if ($this->Order->get_payment_type_id() == Config::MiniAlipay){
            return true;
        }
        return false;
    }
    /**
     * 支付成功取消订单
     * @param array $data
     * [
     *      'order_id'=>'',     //【必须】订单ID
     *      'delivery_id'=>'',  //【必须】发货单ID
     * ]
     * @return boolean
     * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
     */
    public function refund($data){
        // 操作前状态
        $old_state = $this->get_state();
        // 操作前状态
        $Operation =new QuxiaoDeliveryOperation($this->Order,$data);
        $b = $Operation->update();
        if( $b == false ){
            return false;
        }
        if($this->Order->get_payment_type_id() == Config::MiniAlipay){
            $State = new OrderRefundingState($this->Order);
        }
        // 操作后状态值
        $new_state = $State->get_state();
        $this->Order->set_state_transition(new StateTransition('退款', $old_state, $new_state));
        return true;

    }
    public function cancel_order( $data ) {
        if ($this->Order->get_payment_type_id() == Config::MiniAlipay){
			// 操作前状态
			$old_state = $this->get_state();

			$cancel =new MiniAlipayCancelOperation($this->Order);
			$b = $cancel->update();
			if( $b == false ){
				// 取消失败
				return false;
			}
			// 更新 订单状态
            $State = new OrderRefundingState($this->Order);
			// 操作后状态值
			$new_state = $State->get_state();
			$this->Order->set_state_transition(new StateTransition('取消订单', $old_state, $new_state));
			return true;
        }
		return parent::cancel_order($data);
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
    
}
