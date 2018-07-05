<?php
namespace zuji\order;


/**
 * 租机业务 订单
 *
 * @access public
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
class ZujiOrder extends Order {
    
    public function __construct(array $data=[]) {
	parent::__construct($data);
    }
    
    public function allow_to_cancel() {
	
	// 订单未开启状态，禁止任何操作
	if( $this->order_status == OrderStatus::OrderCreated && $this->delivery_id ==0 && $this->refund_id ==0){

	    return true;
	}
	return false;
	
    }
    
    
    //是否可以创建发货单
    public function allow_create_delivery(){
        if($this->step_status ==OrderStatus::StepPayment 
            && $this->delivery_id ==0 
            && $this->payment_status ==PaymentStatus::PaymentSuccessful
            && $this->order_status == OrderStatus::OrderCreated){
            return true;
        }
        return false;
    }
     
    
}
