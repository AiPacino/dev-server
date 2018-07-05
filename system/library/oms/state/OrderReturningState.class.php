<?php

namespace oms\state;

use oms\operation\CancelReturnsOperation;
use oms\operation\ConfirmReceivedOperation;
use oms\operation\UploadWuliuOperation;
/**
 * OrderReturning  退货中
 *
  * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
 */
class OrderReturningState extends State {
    
    public function get_state(){
	return State::OrderReturning;
    }
    
    public function get_name(){
	return '退换货中';
    }
    
    public function get_client_name(){
	return '退货中';
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
                'mca'=>'order2/receive/receive_confirmed',
                'name'=>'收货',
                'params' => ['receive_id' => $this->Order->get_receive_id()],
                'iframe_width' => 300,
                'is_show' => false
            ]
    
        ];
    }
    
    //是否允许取消退货
    public function allow_to_cancel_returns(){
        return true;
    }
   
    //是否允许平台收货
    public function allow_to_confirm_received(){
        return true;
    }
    
    //是否允许上传物流单号
    public function allow_to_upload_wuliu(){
        return true;
    }
    /**
     * 上传物流单号
     * @param array $data
     * [
     *      'order_id'=>''          //【必须】订单ID
     *      'wuliu_channel_id'=>'' //【必须】物流渠道ID
     *      'wuliu_no'=>'',        //【必须】物流单号 
     * ]
     */
    public function upload_wuliu($data){
        $operation =new UploadWuliuOperation($data);
        $b = $operation->update();
        if( $b == false ){
            return false;
        }
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
        $operation =new CancelReturnsOperation($data);
        $b = $operation->update();
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
     * 平台收货操作 -创建检测单
     * @param array $data
     *[
     *	    'order_id' => '',//【必须】int;订单id
     *	    'order_no' => '',//【必须】int;订单编号
     *      'business_key' => '', //【必须】zuji\Business::BUSINESS_前缀常量 
     *	    'goods_id' => '',//【必须】int;商品id
     *      'receive_id'=>'',//【必须】int 检测单ID
     *      'admin_id'=>''//【必须】int 操作员ID
     * ]
     * @return boolean
     * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
     */
    public function confirm_received($data){
        // 操作前状态
        $old_state = $this->get_state();
        $operation =new ConfirmReceivedOperation($data);
        $b = $operation->update();
        if( $b == false ){
            return false;
        }
        // 更新 订单状态
        $State = new OrderReceivedState($this->Order);
        // 操作后状态值
        $new_state = $State->get_state();
        $this->Order->set_state_transition(new StateTransition('平台收货', $old_state, $new_state));
        return true;
    }
}

