<?php
namespace oms\state;
use oms\operation\StoreUploadImgOperation;
use oms\operation\CreateRefundOperation;

use oms\operation\CancelOperation;
use oms\operation\ConfirmOrderOperation;
use oms\operation\FundsThawedOperation;
use oms\operation\RemoveAuthorizeOperation;
/**
 * StoreFundsAuthorizedState  门店用户 资金预授权成功
 *
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>

 */
class StoreFundsAuthorizedState extends State {
    
    public function get_state(){
	return State::FundsAuthorized;
    }
    
    public function get_name(){
		return '资金已授权';
    }
    
    public function get_client_name(){
	   return '待发货';
    }
    
    //后端操作列表
    public function get_operation_list(){
		return [
//			[
//				'mca' => 'order2/order/remove_authorize',
//				'name' => '解除资金预授权',
//				'params' => ['order_id' => $this->Order->get_order_id()],
//				'iframe_width' => 300,
//				'is_show' => true
//			]
		];
    }
    
    // 判断是否允许 租用中
    public function allow_to_inservice(){
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

//    /*
//     * 是否允许解除资金预授权
//     */
//    public function allow_to_remove_authorize(){
//        return true;
//    }
//    /**
//     * 解除资金预授权
//     * @return boolean
//     * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
//     */
//    public function remove_authorize(){
//        // 操作前状态
//        $old_state = $this->get_state();
//        $Operation =new FundsThawedOperation($this->Order);
//        $b = $Operation->update();
//        if( $b == false ){
//            return false;
//        }
//        // 更新 订单状态
//        $State = new FundsThawedState($this->Order);
//        // 操作后状态值
//        $new_state = $State->get_state();
//        $this->Order->set_state_transition(new StateTransition('解冻资金预授权', $old_state, $new_state));
//        return true;
//    }
    
}
