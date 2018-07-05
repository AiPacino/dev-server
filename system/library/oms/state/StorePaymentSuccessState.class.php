<?php
namespace oms\state;
use oms\operation\StoreUploadImgOperation;
use oms\operation\CreateRefundOperation;

/**
 * StorePaymentSuccessState  门店用户支付成功
 *
 * @author maxiaoyu <maxioayu@huishoubao.com.cn>
 */
class StorePaymentSuccessState extends State {
    
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
        return [];
    }
    
    // 判断是否允许 租用中
    public function allow_to_inservice(){
       return true;
    }
    
    //判断是否可以申请退款
    public function allow_to_create_refund(){
        return true;
    }

     /**
     * 租用中
     * @param array $data
     * [
     *      'order_id'=>'',     // 【必须】订单ID
     *      'imei'=>'',         // 【必须】IMEI号
     *      'serial_number'=>'',// 【必须】序列号
     *      'card_hand'         // 【可选】手持身份证相片
     *      'card_positive'     // 【可选】身份证正面照片
     *      'card_negative'     // 【可选】身份证反面相片
     *      'goods_delivery'    // 【必须】商品交付相片
     * ]
     * @author maixaoyu <maxiaoyu@huishoubao.com.cn>
     */
    public function inservice($data = array()){

         // 操作前状态
        $old_state = $this->get_state();
        $create_refund = new StoreUploadImgOperation($data);
        $b = $create_refund->update();
        if( $b == false ){
            return false;
        }
        // 更新 订单状态
        $State = new OrderInServiceState($this->Order);
        // 操作后状态值
        $new_state = $State->get_state();
        $this->Order->set_state_transition(new StateTransition('租用中', $old_state, $new_state));
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
