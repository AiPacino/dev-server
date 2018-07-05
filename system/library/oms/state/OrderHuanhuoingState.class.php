<?php

namespace oms\state;

use oms\operation\HuanhuoDeliveryOperation;
/**
 * OrderHuanhuoingState  换货中
 *
 * @author maxiaoyu <maxiaoyu@huishoubao.com.cn>
 */
class OrderHuanhuoingState extends State {
    
    public function get_state(){
	   return State::OrderHuanhuoing;
    }
    
    public function get_name(){
	   return '换货中';
    }
    
    public function get_client_name(){
	   return '发货中';
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
    
    //是否允许换货发货
    public function allow_to_delivery(){
       return true;
    }

    /**
     * 换货发货操作
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
     * @author maxiaoyu <maxiaoyu@huishoubao.com.cn>
     */
    public function delivery($data){
        // 操作前状态
        $old_state = $this->get_state();
        $delivery  = new HuanhuoDeliveryOperation($data);
        $b = $delivery->update();
        if( $b == false ){
            return false;
        }
        // 更新 订单状态
        $State = new OrderDeliveryedState($this->Order);
        // 操作后状态值
        $new_state = $State->get_state();
        $this->Order->set_state_transition(new StateTransition('换货发货', $old_state, $new_state));
        return true;
    }
}

