<?php

namespace oms\state;
use oms\operation\SignDeliveryOperation;
use oms\operation\HuijiOperation;

/**
 * OrderHuijiingState  回寄中
 *
 * @author maxiaoyu <maxiaoyu@huishoubao.com.cn>
 */
class OrderHuijiingState extends State {

    public function get_state(){
	   return State::OrderHuijiing;
    }

    public function get_name(){
	   return '回寄中';
    }

    public function get_client_name(){
	   return '回寄中';
    }


    //后端操作列表
    public function get_operation_list(){
        return [
            [
                'mca'=>'order2/delivery/send_alert',
                'name'=>'发货',
                'params' => ['order_id' => $this->Order->get_order_id(),'delivery_id'=>$this->Order->get_delivery_id()],
                'iframe_width' => 350,
                'is_show' => false,
            ],
        ];
    }
    //检测是否允许回寄发货
    public function allow_to_delivery(){
       return true;
    }

   /**
     * 回寄发货
     * @param array $delivery_id    【必须】订单ID
     * @param array $order_id       【必须】地址ID
     * @param array $goods_id       【必须】订单ID
     * @param array $logistics_id   【必须】地址ID
     * @param array $admin_id       【必须】操作者
     * @param array $logistics_sn   【必须】物流单号
     * @param array $delivery_remark【必须】发货备注

     * @return boolean
     * @author maxiaoyu <maxiaoyu@huishoubao.com.cn>
     */
    public function delivery( $data )
    {
        // 操作前状态
        $old_state = $this->get_state();

        $operation = new HuijiOperation($data);

        $b = $operation->update();
        if ($b == false) {
            return false;
        }
        // 更新 订单状态
        $State = new OrderDeliveryedState($this->Order);
        // 操作后状态值
        $new_state = $State->get_state();
        $this->Order->set_state_transition(new StateTransition('回寄发货', $old_state, $new_state));
        return true;
    }

}

