<?php

namespace oms\state;
use oms\operation\SignDeliveryOperation;
use oms\operation\RefusedOperation;
use zuji\Business;

/**
 * OrderDeliveryed  已发货
 *
 * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
 */
class OrderDeliveryedState extends State {
    
    public function get_state(){
	return State::OrderDeliveryed;
    }
    
    public function get_name(){
	return '已发货';
    }
    
    public function get_client_name(){
	return '待收货';
    }
    
    //后端操作列表
    public function get_operation_list(){
        $list = [
            [
                'mca'=>'order2/delivery/delivery_confirmed',
                'name'=>'确认收货',
                'params' => ['delivery_id'=>$this->Order->get_delivery_id()],
                'iframe_width' => 350,
                'is_show' => true,
            ]
        ];

        //判断发货单业务类型
        $delivery_id = $this->Order->get_delivery_id();
        $delivery_service = model('order2/delivery', 'service');
        $delivery_info = $delivery_service->get_info($delivery_id);
        if($delivery_info){
            $business_key = $delivery_info['business_key'];
            if(Business::BUSINESS_HUIJI == $business_key){
                $list[] = [
                    'mca'=>'order2/delivery/delivery_refuse',
                    'name'=>'用户拒签',
                    'params' => ['delivery_id'=>$this->Order->get_delivery_id()],
                    'iframe_width' => 350,
                    'is_show' => true,
                ];
            }
        }

        return $list;
    }
    
    //是否允许用户拒签
    public function allow_to_refused(){
        //判断业务类型
        return true;
    }
    
    //是否允许确认收货
    public function allow_to_sign_delivery(){
        return true;
    }
    
    //用户拒签
    public function refused( $data ){
        // 操作前状态
        $old_state = $this->get_state();
        $operation = new RefusedOperation($data, $this->Order);
        $b = $operation->update();
        if( $b == false ){
            return false;
        }
        // 更新 订单状态
        $State = new OrderInServiceState($this->Order);
        // 操作后状态值
        $new_state = $State->get_state();
        $this->Order->set_state_transition(new StateTransition('用户拒签', $old_state, $new_state));
        return true;
    }
    
    //确认收货

    /**
     * @param $data[
     *      'id' =>'' 【可选】管理员ID
     *      'confirm_remark'=>'' 【可选】 收货备注
     * ]
     * @return bool
     */
    public function sign_delivery( $data ){
        // 操作前状态
        $old_state = $this->get_state();
        $operation =new SignDeliveryOperation($data, $this->Order);
        $b = $operation->update();
        if( $b == false ){
            return false;
        }
        // 更新 订单状态
        $State = new OrderInServiceState($this->Order);
        // 操作后状态值
        $new_state = $State->get_state();
        $this->Order->set_state_transition(new StateTransition('确认收货', $old_state, $new_state));
        return true;
    }
    
}

