<?php
namespace zuji\order;

use zuji\Configurable;
use zuji\Config;
use zuji\Time;

/**
 * 空 服务单
 *
 */
class NullService extends Service{
    
    public function get_service_id() {
	return $this->service_id;
    }

    public function get_order_id() {
	return $this->order_id;
    }

    public function get_order_no() {
	return $this->order_no;
    }

    public function get_business_key() {
	return $this->business_key;
    }

    public function get_create_time() {
	return $this->create_time;
    }

    public function get_update_time() {
	return $this->update_time;
    }

    public function get_service_status() {
	return $this->service_status;
    }

    public function get_begin_time() {
	return $this->begin_time;
    }

    public function get_end_time() {
	return $this->end_time;
    }

    public function set_service_id($service_id) {
	return $this;
    }

    public function set_order_id($order_id) {
	return $this;
    }

    public function set_order_no($order_no) {
	return $this;
    }

    public function set_business_key($business_key) {
	return $this;
    }

    public function set_create_time($create_time) {
	return $this;
    }

    public function set_update_time($update_time) {
	return $this;
    }

    public function set_service_status($service_status) {
	return $this;
    }

    public function set_begin_time($begin_time) {
	return $this;
    }

    public function set_end_time($end_time) {
	return $this;
    }

    /**
     * 服务是否有效
     * @return bool true: 有效；false：无效
     */
    public function is_available(){
	return false;
    }
    
    /**
     * 是否过期
     * @return bool true: 已过期；false：未过期
     */
    public function is_expired(){
	return false;
    }
    /**
     * 剩余天数
     * @return int 
     * <p>注意：允许返回负值</p>
     * <ul>
     * <li>负整数：已过期天数</li>
     * <li>0：到期</li>
     * <li>正整数：剩余天数</li>
     * </ul>
     */
    public function get_remaining_days(){
	return 0;
    }
    
    /**
     * 是否允许 退货申请
     * @return bool true：允许；false：不允许
     */
    public function allow_to_return() {
	return false;
    }
    

}
