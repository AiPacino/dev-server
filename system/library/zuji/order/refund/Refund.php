<?php

/*
 * 退款单
 */

namespace zuji\order\refund;

use zuji\Configurable;
use zuji\order\RefundStatus;

/**
 * 
 */
class Refund extends Configurable{
    
    // 业务类型
    protected $business_key = 0;
    
    protected $refund_id = 0;
    protected $order_id = 0;
    protected $order_no = null;
    protected $user_id =0;
    protected $goods_id=0;
    protected $payment_channel_id =0;
    protected $payment_id;
    protected $mobile=null;
    protected $create_time = 0;
    protected $update_time = 0;
    protected $payment_amount =0;
    
    //修改退款额   
    protected $should_amount=0;
    protected $should_remark=null;
    protected $should_admin_id=0;
    
    
    //退款
    protected $refund_amount =0;
    protected $reason_id =0;
    protected $refund_type=0;
    protected $account_name=null;
    protected $account_no=null;
    protected $really_name=null;
    protected $refund_time=0;
    protected $refund_remark=null;
    protected $admin_id=0;
    
    
    //状态
    protected $refund_status=0;



    /**
     * 构造函数
     * @param array $data
     */
    public function __construct( Array $data=[] ) {
	// $throwException=null 忽略未定义的属性
	$this->config($data,$throwException=null);
    }
    public function get_business_key() {
        return $this->business_key;
    }

    public function get_refund_id() {
        return $this->refund_id;
    }

    public function get_order_id() {
        return $this->order_id;
    }

    public function get_order_no() {
        return $this->order_no;
    }

    public function get_user_id() {
        return $this->user_id;
    }

    public function get_goods_id() {
        return $this->goods_id;
    }

    public function get_payment_channel_id() {
        return $this->payment_channel_id;
    }

    public function get_payment_id() {
        return $this->payment_id;
    }

    public function get_mobile() {
        return $this->mobile;
    }

    public function get_create_time() {
        return $this->create_time;
    }

    public function get_update_time() {
        return $this->update_time;
    }

    public function get_payment_amount() {
        return $this->payment_amount;
    }

    public function get_should_amount() {
        return $this->should_amount;
    }

    public function get_should_remark() {
        return $this->should_remark;
    }

    public function get_should_admin_id() {
        return $this->should_admin_id;
    }

    public function get_refund_amount() {
        return $this->refund_amount;
    }

    public function get_reason_id() {
        return $this->reason_id;
    }

    public function get_refund_type() {
        return $this->refund_type;
    }

    public function get_account_name() {
        return $this->account_name;
    }

    public function get_account_no() {
        return $this->account_no;
    }

    public function get_really_name() {
        return $this->really_name;
    }

    public function get_refund_time() {
        return $this->refund_time;
    }

    public function get_refund_remark() {
        return $this->refund_remark;
    }

    public function get_admin_id() {
        return $this->admin_id;
    }

    public function get_refund_status() {
        return $this->refund_status;
    }

    public function set_business_key($business_key) {
        $this->business_key = $business_key;
    }

    public function set_refund_id($refund_id) {
        $this->refund_id = $refund_id;
    }

    public function set_order_id($order_id) {
        $this->order_id = $order_id;
    }

    public function set_order_no($order_no) {
        $this->order_no = $order_no;
    }

    public function set_user_id($user_id) {
        $this->user_id = $user_id;
    }

    public function set_goods_id($goods_id) {
        $this->goods_id = $goods_id;
    }

    public function set_payment_channel_id($payment_channel_id) {
        $this->payment_channel_id = $payment_channel_id;
    }

    public function set_payment_id($payment_id) {
        $this->payment_id = $payment_id;
    }

    public function set_mobile($mobile) {
        $this->mobile = $mobile;
    }

    public function set_create_time($create_time) {
        $this->create_time = $create_time;
    }

    public function set_update_time($update_time) {
        $this->update_time = $update_time;
    }

    public function set_payment_amount($payment_amount) {
        $this->payment_amount = $payment_amount;
    }

    public function set_should_amount($should_amount) {
        $this->should_amount = $should_amount;
    }

    public function set_should_remark($should_remark) {
        $this->should_remark = $should_remark;
    }

    public function set_should_admin_id($should_admin_id) {
        $this->should_admin_id = $should_admin_id;
    }

    public function set_refund_amount($refund_amount) {
        $this->refund_amount = $refund_amount;
    }

    public function set_reason_id($reason_id) {
        $this->reason_id = $reason_id;
    }

    public function set_refund_type($refund_type) {
        $this->refund_type = $refund_type;
    }

    public function set_account_name($account_name) {
        $this->account_name = $account_name;
    }

    public function set_account_no($account_no) {
        $this->account_no = $account_no;
    }

    public function set_really_name($really_name) {
        $this->really_name = $really_name;
    }

    public function set_refund_time($refund_time) {
        $this->refund_time = $refund_time;
    }

    public function set_refund_remark($refund_remark) {
        $this->refund_remark = $refund_remark;
    }

    public function set_admin_id($admin_id) {
        $this->admin_id = $admin_id;
    }

    public function set_refund_status($refund_status) {
        $this->refund_status = $refund_status;
    }

    
 /**
     * 创建退款单
     * @param type $data
     * @return Refund
     */
    public static function createRefund( $data ){
        if( empty($data) ){
            return new NullRefund();
        }
        return new Refund($data);
    }
    
    /**
     * 是否允许 退款
     * @return bool true：允许；false：不允许
     */
    public function allow_to_refund(){
	// 待退款状态
    	if(($this->refund_status == RefundStatus::RefundWaiting && $this->should_remark !="") || ($this->refund_status == RefundStatus::RefundFailed) || ($this->refund_status ==RefundStatus::RefundPaying)){
    	    return true;
    	}
    	return false;
    }
    /**
     * 是否允许修改退款额
     */
    public function allow_should_amount(){
        if($this->refund_status !=RefundStatus::RefundSuccessful && $this->refund_status !=RefundStatus::RefundPaying){
            return true;
        }
        return false;
    }
    
    
}
