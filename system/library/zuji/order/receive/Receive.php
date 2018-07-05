<?php

/*
 * 退货申请单
 * 因为 return 是关键字，所以+s使用复数形式
 */
namespace zuji\order\receive;

use zuji\Configurable;
use zuji\order\ReceiveStatus;

/**
 * 
 */
class Receive extends Configurable{
    
    
    // 业务类型
    protected $business_key = 0;
    
    protected $receive_id = 0;
    protected $order_id = 0;
    protected $order_no = null;
    protected $goods_id =0;
    protected $wuliu_channel_id=0;
    protected $wuliu_no=null;
    protected $bar_code=null;
    protected $create_time = 0;
    protected $update_time = 0;
    
    //-+------------------------------------------------------------------------
    // | 退货邮寄地址标识；北京，深圳
    //-+------------------------------------------------------------------------
    protected $address_id = 0;
    

    //-+------------------------------------------------------------------------
    // | 收货状态
    //-+------------------------------------------------------------------------
    protected $receive_status = 0;
    
    //-+------------------------------------------------------------------------
    // | 收货操作
    //-+------------------------------------------------------------------------
    protected $receive_time = 0;
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

    public function get_receive_id() {
        return $this->receive_id;
    }

    public function get_order_id() {
        return $this->order_id;
    }

    public function get_order_no() {
        return $this->order_no;
    }

    public function get_goods_id() {
        return $this->goods_id;
    }

    public function get_wuliu_channel_id() {
        return $this->wuliu_channel_id;
    }

    public function get_wuliu_no() {
        return $this->wuliu_no;
    }

    public function get_bar_code() {
        return $this->bar_code;
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

    public function get_receive_status() {
        return $this->receive_status;
    }

    public function get_receive_time() {
        return $this->receive_time;
    }

    public function get_admin_id() {
        return $this->admin_id;
    }

    public function set_business_key($business_key) {
        $this->business_key = $business_key;
    }

    public function set_receive_id($receive_id) {
        $this->receive_id = $receive_id;
    }

    public function set_order_id($order_id) {
        $this->order_id = $order_id;
    }

    public function set_order_no($order_no) {
        $this->order_no = $order_no;
    }

    public function set_goods_id($goods_id) {
        $this->goods_id = $goods_id;
    }

    public function set_wuliu_channel_id($wuliu_channel_id) {
        $this->wuliu_channel_id = $wuliu_channel_id;
    }

    public function set_wuliu_no($wuliu_no) {
        $this->wuliu_no = $wuliu_no;
    }

    public function set_bar_code($bar_code) {
        $this->bar_code = $bar_code;
    }

    public function set_create_time($create_time) {
        $this->create_time = $create_time;
    }

    public function set_update_time($update_time) {
        $this->update_time = $update_time;
    }

    public function set_address_id($address_id) {
        $this->address_id = $address_id;
    }

    public function set_receive_status($receive_status) {
        $this->receive_status = $receive_status;
    }

    public function set_receive_time($receive_time) {
        $this->receive_time = $receive_time;
    }

    public function set_admin_id($admin_id) {
        $this->admin_id = $admin_id;
    }

     

    /**
     * 创建收货单
     * @param type $data
     * @return Receive
     */
    public static function createReceive( $data ){
        if( empty($data) ){
            return new NullReceive();
        }
        return new Receive($data);
    }
    
    
    /**
     * 是否可以取消收货单
     */
    public function allow_cancel_receive(){
        if($this->wuliu_no =="" && $this->receive_status==ReceiveStatus::ReceiveWaiting){
            return true;
        }
        return false;
    }
    
    /**
     * 是否可以收货
     */
    public function allow_receive_confirmed(){
        if($this->receive_status==ReceiveStatus::ReceiveWaiting){
            return true;
            
        }
        return false;
    }
    
    /**
     * 是否可以生成检测单
     */
    public function allow_create_evaluation(){
        if($this->receive_status==ReceiveStatus::ReceiveConfirmed ||$this->receive_status==ReceiveStatus::ReceiveFinished){
            return true;
        }
        return false;
    }
  
}
