<?php
namespace zuji\order;


use oms\state\State;
/**
 * 线下门店业务 订单
 *
 * @access public
 * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
 */
class StoreOrder extends Order {
    
    public function __construct(array $data=[]) {
	parent::__construct($data);
    }
    
    //判断管理员是否允许取消订单
    public function allow_to_cancel() {
         if($this->order_status ==OrderStatus::OrderCreated && $this->status == State::OrderCreated){
             return true;
         }
         return false;
    }
    //判断用户或者门店是否取消订单
    public function store_allow_to_cancel(){
        // 订单未支付之前可以取消
        if( $this->payment_status == PaymentStatus::PaymentSuccessful){
            return false;
        }
        return true;
    }
    
    //判断是否可以确认订单
    public function allow_to_confirmed(){
        if($this->order_status == OrderStatus::OrderStoreConfirming){
            return true;
        }
        return false;
    }
    
    //判断是否允许支付
    public function allow_to_pay(){
        if($this->payment_status !=PaymentStatus::PaymentSuccessful && $this->order_status ==OrderStatus::OrderStoreConfirmed){
            return true;
        }
        return false;
    }
    
    //判断是否可以上传图片
    public function allow_to_upload(){
        if($this->payment_status ==PaymentStatus::PaymentSuccessful && $this->order_status ==OrderStatus::OrderStoreUploading){
            return true;
        }
        return false;
    }
    
    
    //是否可以创建发货单
    public function allow_create_delivery(){
        return false;
    }
    
    
     
    
}
