<?php
namespace oms\observer;

use zuji\debug\Debug;
use zuji\debug\Location;
/**
 * FollowObserver  订单流
 *
 * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
 */
class FollowObserver extends OrderObserver {
    


    public function __construct(\oms\observer\OrderObservable $Observable ){
	parent::__construct($Observable);
    }

    public function get_id() {
	return 'order-follow-observer';
    }

    public function update() {

        $status = $this->Observable->get_status();
        if($status === false){
            return false;
        }
        $Order = $this->Observable->get_order();
        $StateTransition = $Order->get_state_transition();
        $name =$StateTransition->get_name();
        $old_status =$StateTransition->get_old_state();
        $new_status =$StateTransition->get_new_state();
        
        $load = \hd_load::getInstance();
        $order_table = $load->table('order2/order2_follow');
        
        $order_id =$Order->get_order_id(); 

        $data['create_time'] =time();
        $data['old_status'] =$old_status;
        $data['new_status'] =$new_status;
        $data['order_id']=$order_id;

        $follow_id = $order_table->add($data); 
        if(!$follow_id){
            Debug::error(Location::L_Order, '创建订单流失败', $data);
        }
        return $follow_id;
    }

    
}
