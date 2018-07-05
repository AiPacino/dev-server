<?php

namespace oms\state;

use oms\operation\CancelOperation;
use oms\operation\DeliveryOperation;
use oms\operation\CancelDeliveryOperation;
use oms\operation\FundsThawedOperation;
use oms\operation\QuxiaoDeliveryOperation;
use zuji\Business;
use zuji\Config;

/**
 * OrderConfirmed  线上确认订单
 *
 * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
 */
class OrderConfirmedState extends State {
    
    public function get_state(){
	return State::OrderConfirmed;
    }
    
    public function get_name(){
	return '已确认';
    }
    
    public function get_client_name(){
	return '待发货';
    }
    
    //是否允许发货
    public function allow_to_delivery(){
        return true;
    }
    //是否允许取消退货
    public function allow_to_cancel_delivery(){
        return true;
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
    //是否允许解除资金预授权
    public function allow_to_remove_authorize(){
        $payment_type_id =$this->Order->get_payment_type_id();
        if($payment_type_id == Config::WithhodingPay){
            return true;
        }else{
            return false;
        }
    }

    
    //后端操作列表
    public function get_operation_list(){

        //发货url
        if($this->Order->get_business_key() == Business::BUSINESS_ZUJI){
            $url = 'order2/delivery/prints';
        }else{
            $url = 'order2/delivery/send_alert';
        }

        if($this->Order->get_payment_type_id() == Config::WithhodingPay){
            $cancel_url ='order2/delivery/cancel_withhode_delivery';
        }elseif ($this->Order->get_payment_type_id() == Config::MiniAlipay){
            $cancel_url ='order2/delivery/cancel_minialipay_delivery';
        }else{
            $cancel_url ='order2/delivery/cancel_delivery';
        }

        return [
            [
                'mca'=>$url,
                'name'=>'发货',
                'params' => ['order_id' => $this->Order->get_order_id(),'delivery_id'=>$this->Order->get_delivery_id()],
                'iframe_width' => 350,
                'is_show' => false,
            ],
            [
                'mca'=>$cancel_url,
                'name'=>'取消发货',
                'params' => ['order_id' => $this->Order->get_order_id(),'delivery_id'=>$this->Order->get_delivery_id()],
                'iframe_width' => 300,
                'is_show' => true,
            ],
            [
                'mca' => 'order2/order/cancel_order',
                'name' => '取消订单',
                'params' => ['order_id' => $this->Order->get_order_id()],
                'iframe_width' => 300,
                'is_show' => false
            ]
        ];
    }
    
    /**
     * 发货操作
     * @param array $data
     * [
     *     'delivery_id' =>'',     //【必须】int 发货单ID
     *     'order_id'=>'',         //【必须】int 订单ID
     *     'wuliu_channel_id' =>"",//【必须】int 物流渠道ID
     *     'wuliu_no' =>"",        //【必须】string 物流编号
     *     'delivery_remark' =>"", //【必须】string 发货备注
     *     'imei1' =>"",           //【必须】string Imei
     *     'imei2' =>"",           //【可选】string Imei
     *     'imei3' =>"",           //【可选】string Imei
     *     'serial_number' =>"",   //【可选】序列号
     *     'admin_id'=>"",         //【可选】后台管理员ID
     *     'goods_id'=>'',         //【必须】商品ID
     * ]
     * @return boolean
     * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
     */
    public function delivery($data){
        // 操作前状态
        $old_state = $this->get_state();
        $delivery =new DeliveryOperation($data);
        $b = $delivery->update();
        if( $b == false ){
            return false;
        }
        // 更新 订单状态
        $State = new OrderDeliveryedState($this->Order);
        // 操作后状态值
        $new_state = $State->get_state();
        $this->Order->set_state_transition(new StateTransition('发货', $old_state, $new_state));
        return true;
    }

    /**
     * 取消发货 只取消发货单
     * @param array $data
     * [
     *      'order_id'=>'',     //【必须】订单ID
     *      'delivery_id'=>'',  //【必须】发货单ID
     * ]
     * @return boolean
     * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
     */
    public function quxiao_delivery($data){

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
            // 操作后状态值
            $new_state = $State->get_state();
            $this->Order->set_state_transition(new StateTransition('退款', $old_state, $new_state));
        }

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
     * 取消发货
     * @param array $data
     * [
     *      'order_id'=>'',     //【必须】订单ID
     *      'payment_id'=>'',   //【必须】支付单ID
     *      'should_amount'=>'',//【必须】应退金额
     *      'should_remark'=>'',//【必须】修改备注
     *      'delivery_id'=>'',  //【必须】发货单ID
     * ]
     * @return boolean
     * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
     */
    public function cancel_delivery($data){
        // 操作前状态
        $old_state = $this->get_state();

            $create_refund =new CancelDeliveryOperation($this->Order,$data);
            $b = $create_refund->update();
            if( $b == false ){
                return false;
            }
            // 更新 订单状态
            $State = new OrderRefundingState($this->Order);
            // 操作后状态值
            $new_state = $State->get_state();
            $this->Order->set_state_transition(new StateTransition('取消发货', $old_state, $new_state));

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
