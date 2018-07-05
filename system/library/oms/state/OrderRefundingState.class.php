<?php

namespace oms\state;

use oms\operation\MiniAlipayRefundOperation;
use oms\operation\RefundOperation;
use oms\operation\EditRefundOperation;
use oms\operation\UnionRefundNotifyOperation;
use oms\operation\UnionRefundOperation;
use zuji\Config;

/**
 * OrderRefunding  退款中
 *
 * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
 */
class OrderRefundingState extends State {
    
    public function get_state(){
	return State::OrderRefunding;
    }
    
    public function get_name(){
	return '退款中';
    }
    
    public function get_client_name(){
	return '退款中';
    }
    
    //后端操作列表
    public function get_operation_list(){
        return [
//            [
//                'mca'=>'order2/refund/refund_should',
//                'name'=>'修改退款金额',
//                'params' => ['refund_id' => $this->Order->get_refund_id()],
//                'iframe_width' => 300,
//                'is_show' => true
//            ],
            [
                'mca'=>'order2/refund/refund_confirm',
                'name'=>'退款',
                'params' => ['refund_id' => $this->Order->get_refund_id()],
                'iframe_width' => 300,
                'is_show' => false
            ]
        ];
    }
    
    //是否可以退款
    public function allow_to_refund(){
        return true;
    }
    
    //是否可以修改退款金额
    public function allow_to_edit_refund(){
        return true;
    }
    //是否可以允许退款回调
    public function allow_to_refund_notify(){
        $payment_type_id =$this->Order->get_payment_type_id();
        if($payment_type_id == Config::UnionPay){
            return true;
        }
        return false;
    }
    /**
     * 银联退款回调函数
     * @param array $data
     * [
     *      目前为空
     * ]
     * @return boolean
     * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
     */
    public function refund_notify($data){
        // 操作前状态
        $old_state = $this->get_state();
        $refund = new UnionRefundNotifyOperation($this->Order,$data);
        $b = $refund->update();
        if( $b == false ){
            return false;
        }
        // 更新 订单状态
        $State = new OrderRefundedState($this->Order);
        // 操作后状态值
        $new_state = $State->get_state();
        $this->Order->set_state_transition(new StateTransition('退款', $old_state, $new_state));
        return true;
    }

    /**
     * 退款操作
     * @param array $data
     * [
     *      'order_id'=>'',     // 【必须】订单ID
     *      'refund_id'=>'',    // 【必须】退款单ID
     *      'refund_remark'=>'',// 【必须】退款备注
     *      'admin_id'=>'',     // 【必须】操作员ID
     *      'business_key'=>'', // 【必须】业务类型
     *      'goods_id'=>'',     // 【必须】商品ID
     *      'trade_no'=>'',     //【可选】交易码
     * ]
     * @return boolean
     * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
     */
    public function refund($data){
        // 操作前状态
        $payment_type_id =$this->Order->get_payment_type_id();
        $old_state = $this->get_state();

        // 更新 订单状态
        $State = new OrderRefundedState($this->Order);

        if($payment_type_id == Config::FlowerStagePay){
            $refund =new RefundOperation($data);
        }elseif($payment_type_id == Config::UnionPay){
            $refund = new UnionRefundOperation($this->Order,$data);
        }elseif($payment_type_id == Config::MiniAlipay){
            $refund = new MiniAlipayRefundOperation($this->Order,$data);
            $State = new CanceledState($this->Order);
        }
        $b = $refund->update();
        if( $b == false ){
            return false;
        }

        // 操作后状态值
        $new_state = $State->get_state();
        $this->Order->set_state_transition(new StateTransition('退款', $old_state, $new_state));
        return true;
    }
    
    /**
     * 修改退款金额操作
     * @param array $data
     * [
     *      'refund_id'=>'',    // 【必须】退款单ID
     *      'admin_id'=>'',     // 【必须】操作员ID
     *      'should_amount'=>'',// 【必须】应退金额  -分
     *      'should_remark'=>'',// 【必须】修改备注
     * ]
     * @return boolean
     * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
     */
    public function edit_refund($data){
        // 操作前状态
        $refund =new EditRefundOperation($data);
        $b = $refund->update();
        if( $b == false ){
            return false;
        }
        return true;
        
    }
    
}

