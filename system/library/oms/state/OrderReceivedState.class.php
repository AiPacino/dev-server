<?php

namespace oms\state;

use oms\operation\QualifiedOperation;
use oms\operation\EvaluationOperation;
use zuji\Config;

/**
 * OrderReceivedState  平台已收货
 *
 * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
 */
class OrderReceivedState extends State {
    
    public function get_state(){
	   return State::OrderReceived;
    }
    
    public function get_name(){
	   return '平台已收货';
    }
    
    public function get_client_name(){
	   return '退货中';
    }
    
    //后端操作列表
    public function get_operation_list(){
        return [
            [
                'mca'=>'order2/evaluation/isqualified_alert',
                'name'=>'检测',
                'params' => ['order_id' => $this->Order->get_order_id(),'evaluation_id'=>$this->Order->get_evaluation_id()],
                'iframe_width' => 350,
                'is_show' => false,
            ]
        ];
    }
    
    //是否允许检测
    public function allow_to_evaluation(){
        return true;
    }
   
    /**
     * 检测操作
     * @param array $data
     * [
     *      'evaluation_id'=>'',    // 【必须】检测单ID
     *      'order_id'=>'',     // 【必须】订单ID
     *      'admin_id'=>'',   // 【必须】操作员ID
     *      'remark'=>'',   //【必须】审核备注
     *      'qualified'=>'', //【必须】审核结果
     * ]
     * @return boolean
     * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
     */
    public function evaluation($data){
	   // 操作前状态
    	$old_state = $this->get_state();
    	
    	$operation =new EvaluationOperation($data,$this->Order);
    	$b = $operation->update();
    	if( $b == false ){
    	    return false;
    	}
    	// 更新 订单状态
    	if($data['qualified']==0){
    	    $State = new OrderEvaluationUnqualifiedState($this->Order);
    	}else{
    	    if($this->Order->get_payment_type_id() == Config::MiniAlipay){
                $State = new OrderRefundingState($this->Order);
            }else{
                $State = new OrderEvaluationQualifiedState($this->Order);
            }

    	}
    	// 操作后状态值
    	$new_state = $State->get_state();
    	$this->Order->set_state_transition(new StateTransition('检测', $old_state, $new_state));
    	return true;
    }  
}

