<?php
namespace zuji\order;

use zuji\Configurable;
use zuji\Config;
use zuji\Time;

/**
 * 服务单
 *
 */
class Service extends Configurable{
    
    protected $service_id = 0;
    protected $order_id = 0;
    protected $order_no = null;
    protected $business_key = 0;
    protected $create_time = 0;
    protected $update_time = 0;
    
    // 状态
    protected $service_status = 0;
    
    // 租期
    // 开始时间戳，（某一天的0点0分0秒）
    protected $begin_time = 0;
    // 结束时间戳（不包含），（某一天的0点0分0秒）
    protected $end_time = 0;

    /**
     * 构造函数
     * @param array $data
     */
    public function __construct( Array $data=[] ) {
	// $throwException=null 忽略未定义的属性
	$this->config($data,$throwException=null);
    }
    
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
	$this->service_id = $service_id;
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

    public function set_business_key($business_key) {
	$this->business_key = $business_key;
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

    public function set_service_status($service_status) {
	$this->service_status = $service_status;
	return $this;
    }

    public function set_begin_time($begin_time) {
	$this->begin_time = $begin_time;
	return $this;
    }

    public function set_end_time($end_time) {
	$this->end_time = $end_time;
	return $this;
    }

    /**
     * 服务是否有效
     * @return bool true: 有效；false：无效
     */
    public function is_available(){
	// 服务未开启，则无效
	if( $this->service_status != ServiceStatus::ServiceOpen ){
	    return false;
	}
	// 当前时间在服务期内，则有效
	$time = time();
	if( $this->begin_time > $time && $this->end_time< $time ){
	    return true;
	}
	return false;
    }
    
    /**
     * 是否过期
     * @return bool true: 已过期；false：未过期
     */
    public function is_expired(){
	// 判断结束时间
	if( $this->end_time > time() ){
	    return true;
	}
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
	$Time = Time::getTime()->getDayBegin();
	$second = $this->end_time - $Time->toTimestamp();
	if( $second == 0 ){
	    return 0;
	}
	return intval($second/86400);
    }
    
    /**
     * 是否允许 退货申请
     * @return bool true：允许；false：不允许
     */
    public function allow_to_return() {
	// 无效服务，不允许退货申请
	if( !$this->is_available() ){
	    return false;
	}
	
	// 开始时间 7天内，允许退货申请
	if( $this->begin_time + Config::Order_Return_Days*86400 > time()  ){
	    return true;
	}
	return false;
    }
    
    /**
     * 创建 Service 对象
     * @param array $data
     * @return Service
     * @throws \Exception
     */
    public static function createService( $data ){
	if(is_array($data) && count($data) ){
	    return new Service($data);
	}
	return new NullService();
    }
    

}
