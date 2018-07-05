<?php

namespace oms\state;

use oms\operation\CancelReturnsOperation;
use oms\operation\DeniedReturnsOperation;
use oms\operation\AgreedReturnsOperation;
/**
 * OrderReturnChecking  退货审核中
 *
 * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
 */
class OrderReturnCheckingState extends State {
    
    public function get_state(){
	return State::OrderReturnChecking;
    }
    
    public function get_name(){
	return '退货审核中';
    }
    
    public function get_client_name(){
	return '退货审批中';
    }
    
    //后端操作列表
    public function get_operation_list(){
        return [
            [
                'mca'=>'order2/return/return_cancel',
                'name'=>'取消退货',
                'params' => ['order_id' => $this->Order->get_order_id(),'return_id'=>$this->Order->get_return_id()],
                'iframe_width' => 300,
                'is_show' => true
            ],
            [
                'mca'=>'order2/return/check',
                'name'=>'退货审核',
                'params' => ['return_id'=>$this->Order->get_return_id()],
                'iframe_width' => 300,
                'is_show' => true
            ]
        ];
    }
    
    
    //是否允许取消退货
    public function allow_to_cancel_returns(){
        return true;
    }
    //是否允许审核
    public function allow_to_check_returns(){
        return true;
    }
     /**
     * 取消退货操作
     * @param array $data
     * [
     *      'return_id'=>'',    // 【必须】退货单ID
     *      'order_id'=>'',     // 【必须】订单ID
     *      'receive_id'=>'',   // 【可选】收货单ID 审核完成后才能有收货单 审核之后取消要必选
     * ]
     * @return boolean
     * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
     */
    public function cancel_returns($data){
        // 操作前状态
        $old_state = $this->get_state();
        $cancel =new CancelReturnsOperation($data);
        $b = $cancel->update();
        if( $b == false ){
            return false;
        }
        // 更新 订单状态
        $State = new OrderInServiceState($this->Order);
        // 操作后状态值
        $new_state = $State->get_state();
        $this->Order->set_state_transition(new StateTransition('取消退货', $old_state, $new_state));
        return true;
    }
    
     /**
     * 管理员审核 --通过
     * @param array $data   【必选】退货单审核信息
     * [
     *      'return_id'=>''         //【必选】退货单ID
     *	    'admin_id' => '',	    // 【必须】 审核员ID
     *      'order_id' =>'',        //【必须】订单ID
     *      'return_check_remark'=>'',         //【必须】审核备注
     *      'return_status'=>''         //【必须】审核状态
     *      'business_key'=>''      //【必须】业务类型 
     * ]
     * 
     * @return boolean  true :插入成功  false:插入失败
     * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
     *
     */
    public function agreed_returns($data){
         // 操作前状态
        $old_state = $this->get_state();
        $agreed =new AgreedReturnsOperation($data);
        $b = $agreed->update();
        if( $b == false ){
            return false;
        }
        // 更新 订单状态
         $State = new OrderReturningState($this->Order);
        // 操作后状态值
        $new_state = $State->get_state();
        $this->Order->set_state_transition(new StateTransition('审核通过', $old_state, $new_state));
        return true;
    }
     /**
     * 审核拒绝操作
     * @param array $data
     * [
     *      'order_id' =>'',        // 【必须】订单ID
     *	    'admin_id' => '',	    // 【必须】 审核员ID
     *	    'return_check_remark' => ''，         // 【可选】 管理员审批内容
     *      'return_id'=>''
     * ]
     * @return boolean
     * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
     */
    public function denied_returns($data){
         // 操作前状态
        $old_state = $this->get_state();
        $cancel =new DeniedReturnsOperation($data);
        $b = $cancel->update();
        if( $b == false ){
            return false;
        }
        // 更新 订单状态
        $State = new OrderInServiceState($this->Order);
        // 操作后状态值
        $new_state = $State->get_state();
        $this->Order->set_state_transition(new StateTransition('审核拒绝', $old_state, $new_state));
        return true;
    }
    
    
    
}

