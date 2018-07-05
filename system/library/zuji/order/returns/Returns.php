<?php

/*
 * 退货申请单
 * 因为 return 是关键字，所以+s使用复数形式
 */

namespace zuji\order\returns;

use zuji\Configurable;
use zuji\order\ReturnStatus;
use zuji\order\returns\NullReturns;

/**
 * 
 */
class Returns extends Configurable{
    
    // 业务类型
    protected $business_key = 0;
    
    protected $return_id = 0;
    protected $order_id = 0;
    protected $order_no = null;
    protected $create_time = 0;
    protected $update_time = 0;
    
    //-+------------------------------------------------------------------------
    // | 退货邮寄地址标识；北京，深圳
    //-+------------------------------------------------------------------------
    protected $address_id = 0;
    
    //-+------------------------------------------------------------------------
    // | 退货原因
    //-+------------------------------------------------------------------------
    protected $reason_id = 0;
    protected $reason_text = null;
    protected $loss_type = 0;// 设备损耗

    //-+------------------------------------------------------------------------
    // | 退货状态
    //-+------------------------------------------------------------------------
    protected $return_status = 0;
    
    //-+------------------------------------------------------------------------
    // | 审核
    //-+------------------------------------------------------------------------
    protected $return_check_remark = null;
    protected $return_check_time = 0;
    protected $admin_id = 0;	// 审核人ID


    /**
     * 构造函数
     * @param array $data
     */
    public function __construct( Array $data=[] ) {
	// $throwException=null 忽略未定义的属性
	$this->config($data,$throwException=null);
    }
    
    //-+------------------------------------------------------------------------
    // | setter 和 getter 方法
    //-+------------------------------------------------------------------------
    public function get_business_key() {
	return $this->business_key;
    }

    public function get_return_id() {
	return $this->return_id;
    }

    public function get_order_id() {
	return $this->order_id;
    }

    public function get_order_no() {
	return $this->order_no;
    }

    public function get_create_time() {
	return $this->create_time;
    }

    public function get_update_time() {
	return $this->update_time;
    }

    public function get_address_id() {
	return $this->address_id;
    }

    public function get_reason_id() {
	return $this->reason_id;
    }

    public function get_reason_text() {
	return $this->reason_text;
    }

    public function get_loss_type() {
	return $this->loss_type;
    }

    public function get_return_status() {
	return $this->return_status;
    }

    public function get_return_check_remark() {
	return $this->return_check_remark;
    }

    public function get_return_check_time() {
	return $this->return_check_time;
    }

    public function get_admin_id() {
	return $this->admin_id;
    }

    public function set_business_key($business_key) {
	$this->business_key = $business_key;
	return $this;
    }

    public function set_return_id($return_id) {
	$this->return_id = $return_id;
	return $this;
    }

    public function set_order_id($order_id) {
	$this->order_id = $order_id;
	return $this;
    }

    public function set_order_no($order_no) {
	$this->order_no = $order_no;
	return $this;
    }

    public function set_create_time($create_time) {
	$this->create_time = $create_time;
	return $this;
    }

    public function set_update_time($update_time) {
	$this->update_time = $update_time;
	return $this;
    }

    public function set_address_id($address_id) {
	$this->address_id = $address_id;
	return $this;
    }

    public function set_reason_id($reason_id) {
	$this->reason_id = $reason_id;
	return $this;
    }

    public function set_reason_text($reason_text) {
	$this->reason_text = $reason_text;
	return $this;
    }

    public function set_loss_type($loss_type) {
	$this->loss_type = $loss_type;
	return $this;
    }

    public function set_return_status($return_status) {
	$this->return_status = $return_status;
	return $this;
    }

    public function set_return_check_remark($return_check_remark) {
	$this->return_check_remark = $return_check_remark;
	return $this;
    }

    public function set_return_check_time($return_check_time) {
	$this->return_check_time = $return_check_time;
	return $this;
    }

    public function set_admin_id($admin_id) {
	$this->admin_id = $admin_id;
	return $this;
    }

    /**
     * 创建退货单
     * @param type $data
     * @return Returns
     */
    public static function createReturn( $data ){
        if( empty($data) ){
            return new NullReturns();
        }
        return new Returns($data);
    }
    
    /**
     * 是否允许 审核
     * @return bool true：允许；false：不允许
     */
    public function allow_to_check(){
	// 待审核状态
	if($this->return_status == ReturnStatus::ReturnWaiting){
	    return true;
	}
	return false;
    }
    /**
     * 是否允许退货
     * return bool true：允许；false：不允许
     */
    public function allow_return(){
        //审核状态通过 
        if($this->return_status ==ReturnStatus::ReturnAgreed || $this->return_status == ReturnStatus::ReturnHuanhuo){
            return true;
        }
        return false;
    }
    /**
     * 是否可以创建收货单
     */
    public function allow_create_receive(){
        //审核状态通过 
        if($this->return_status ==ReturnStatus::ReturnAgreed || $this->return_status ==ReturnStatus::ReturnHuanhuo){
            return true;
        }
        return false;
    }
    
    /**
     * 是否取消退货
     */
    public function cancel_return(){
        if($this->return_status ==ReturnStatus::ReturnCanceled){
            return false;
        }
        return true;
    }

    
}
