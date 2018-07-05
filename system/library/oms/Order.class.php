<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace oms;
use zuji\Business;
use zuji\Configurable;
use zuji\OrderLocker;

/**
 * 订单类
 *
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
class Order extends Configurable {

	//-+------------------------------------------------------------------------
	// | 订单基本信息
	//-+------------------------------------------------------------------------
	/**
	 * 订单业务类型
	 * @var int	
	 */
	protected $business_key = 0;

	/**
	 * 订单ID
	 * @var int	
	 */
	protected $order_id = 0;

	/**
	 * 订单编号
	 * @var int	
	 */
	protected $order_no = null;

	/**
	 * 创建时间戳
	 * @var int	
	 */
	protected $create_time = 0;

	/**
	 * 最后更新时间戳
	 * @var int
	 */
	protected $update_time = 0;

	/**
	 * 订单应付金额
	 * @var price 
	 */
	protected $amount = 0.00;

	/**
	 * 租期
	 * @var int 
	 */
	protected $zuqi = 0;
	
	/**
	 * 租期类型【1：天；2：月】
	 * @var int 
	 */
	protected $zuqi_type = 0;

	/**
	 * 月租金（单位：元）
	 * @var int
	 */
	protected $zujin = 0.00;

	/**
	 * 实际押金（单位：元）
	 * @var price 
	 */
	protected $yajin = 0.00;

	/**
	 * 实际免押金额（单位：元）
	 * @var price
	 */
	protected $mianyajin = 0.00;

	/**
	 * 意外险（单位：元）
	 * @var price
	 */
	protected $yiwaixian = 0.00;

	/**
	 * 订单状态
	 * @var int
	 */
	protected $order_status = 0;
	protected $status = 0;

	//-+------------------------------------------------------------------------
	// | 用户相关
	//-+------------------------------------------------------------------------
	/**
	 * 用户ID
	 * @var int
	 */
	protected $user_id = 0;

	/**
	 * 用户名或手机号
	 * @var string
	 */
	protected $mobile = null;

	/**
	 * 用户认证平台
	 * @var int
	 */
	protected $certified_platform = 0;

	/**
	 * 认证信用分值
	 * @var int
	 */
	protected $credit = 0;

	/**
	 * 认证真实姓名
	 * @var string
	 */
	protected $realname = null;

	/**
	 * 认证身份证号
	 * @var string
	 */
	protected $cert_no = null;
	//-+------------------------------------------------------------------------
	// | 收货地址（与用户无关）
	//-+------------------------------------------------------------------------
	/**
	 * 收货地址ID
	 * @var int
	 */
	protected $address_id = 0;
	//-+------------------------------------------------------------------------
	// | 商品相关属性
	//-+------------------------------------------------------------------------
	/**
	 * 订单商品ID
	 * @var int
	 */
	protected $goods_id = 0;

	/**
	 * 商品名称
	 * @var string
	 */
	protected $goods_name = '';

	/**
	 * 商品 成色
	 * @var int
	 */
	protected $chengse = '';
	//-+------------------------------------------------------------------------
	// | 支付
	//-+------------------------------------------------------------------------
	/**
	 * 实际支付金额
	 * @var price 
	 */
	protected $payment_amount = 0.00;
	protected $payment_time = 0;
	protected $trade_no = null; // 租机交易编号
	//-+------------------------------------------------------------------------
	// | 退款
	//-+------------------------------------------------------------------------
	protected $refund_amount = 0.00;
	protected $refund_time = 0;
	//-+------------------------------------------------------------------------
	// | 订单其他单据
	//-+------------------------------------------------------------------------
    protected $appid=0;
	protected $payment_status = 0;
	protected $payment_id = 0;
	protected $delivery_status = 0;
	protected $delivery_id = 0;
	protected $return_status = 0;
	protected $return_id = 0;
	protected $receive_status = 0;
	protected $receive_id = 0;
	protected $evaluation_status = 0;
	protected $evaluation_id = 0;
	protected $refund_status = 0;
	protected $refund_id = 0;
	protected $service_status = 0;
	protected $service_id = 0;
    //-+------------------------------------------------------------------------
    // | 支付方式
    //-+------------------------------------------------------------------------
    protected $payment_type_id = 0;
	//-+------------------------------------------------------------------------
	// | 订单取消原因
	//-+------------------------------------------------------------------------
	protected $reason_id = 0;
	protected $reason_text = '';
	//-+------------------------------------------------------------------------
	// | 订单管理员ID
	//-+------------------------------------------------------------------------
	protected $admin_id = 0;

	/**
	 * 订单状态对象
	 * @var \oms\state\State
	 */
	private $State = null;

	/**
	 * 观察者主题对象
	 * @var \oms\observer\OrderObservable
	 */
	private $OrderObservable = null;

	/**
	 * 状态转换
	 * @var \oms\state\StateTransition
	 */
	private $StateTransition = null;

	/**
	 * 构造函数
	 * @param array $data
	 */
	public function __construct(array $data) {
		// $throwException=null 忽略未定义的属性
		$this->config($data, $throwException = null);

		// 根据订单状态值，赋值 状态对象
		$this->State = \oms\state\State::createState($this);
	}
    public function set_appid($appid) {
        $this->appid = $appid;
        return $this;
    }
	public function set_business_key($business_key) {
		$this->business_key = $business_key;
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

	public function set_amount(price $amount) {
		$this->amount = $amount;
		return $this;
	}

	public function set_zuqi($zuqi) {
		$this->zuqi = $zuqi;
		return $this;
	}
	
	public function set_zuqi_type($zuqi_type) {
		$this->zuqi_type = $zuqi_type;
		return $this;
	}

	public function set_zujin($zujin) {
		$this->zujin = $zujin;
		return $this;
	}

	public function set_yajin(price $yajin) {
		$this->yajin = $yajin;
		return $this;
	}

	public function set_mianyajin(price $mianyajin) {
		$this->mianyajin = $mianyajin;
		return $this;
	}

	public function set_yiwaixian(price $yiwaixian) {
		$this->yiwaixian = $yiwaixian;
		return $this;
	}

	public function set_order_status($order_status) {
		$this->order_status = $order_status;
		return $this;
	}

	public function set_user_id($user_id) {
		$this->user_id = $user_id;
		return $this;
	}

	public function set_mobile($mobile) {
		$this->mobile = $mobile;
		return $this;
	}

	public function set_certified_platform($certified_platform) {
		$this->certified_platform = $certified_platform;
		return $this;
	}

	public function set_credit($credit) {
		$this->credit = $credit;
		return $this;
	}

	public function set_realname($realname) {
		$this->realname = $realname;
		return $this;
	}

	public function set_cert_no($cert_no) {
		$this->cert_no = $cert_no;
		return $this;
	}

	public function set_address_id($address_id) {
		$this->address_id = $address_id;
		return $this;
	}

	public function set_goods_id($goods_id) {
		$this->goods_id = $goods_id;
		return $this;
	}

	public function set_goods_name($goods_name) {
		$this->goods_name = $goods_name;
		return $this;
	}

	public function set_chengse($chengse) {
		$this->chengse = $chengse;
		return $this;
	}

	public function set_payment_amount(price $payment_amount) {
		$this->payment_amount = $payment_amount;
		return $this;
	}

	public function set_payment_time($payment_time) {
		$this->payment_time = $payment_time;
		return $this;
	}

	public function set_trade_no($trade_no) {
		$this->trade_no = $trade_no;
		return $this;
	}

	public function set_refund_amount($refund_amount) {
		$this->refund_amount = $refund_amount;
		return $this;
	}

	public function set_refund_time($refund_time) {
		$this->refund_time = $refund_time;
		return $this;
	}

	public function set_payment_status($payment_status) {
		$this->payment_status = $payment_status;
		return $this;
	}

	public function set_payment_id($payment_id) {
		$this->payment_id = $payment_id;
		return $this;
	}

	public function set_delivery_status($delivery_status) {
		$this->delivery_status = $delivery_status;
		return $this;
	}

	public function set_delivery_id($delivery_id) {
		$this->delivery_id = $delivery_id;
		return $this;
	}

	public function set_return_status($return_status) {
		$this->return_status = $return_status;
		return $this;
	}

	public function set_return_id($return_id) {
		$this->return_id = $return_id;
		return $this;
	}

	public function set_receive_status($receive_status) {
		$this->receive_status = $receive_status;
		return $this;
	}

	public function set_receive_id($receive_id) {
		$this->receive_id = $receive_id;
		return $this;
	}

	public function set_evaluation_status($evaluation_status) {
		$this->evaluation_status = $evaluation_status;
		return $this;
	}

	public function set_evaluation_id($evaluation_id) {
		$this->evaluation_id = $evaluation_id;
		return $this;
	}

	public function set_refund_status($refund_status) {
		$this->refund_status = $refund_status;
		return $this;
	}

	public function set_refund_id($refund_id) {
		$this->refund_id = $refund_id;
		return $this;
	}

	public function set_service_status($service_status) {
		$this->service_status = $service_status;
		return $this;
	}

	public function set_service_id($service_id) {
		$this->service_id = $service_id;
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

    public function set_payment_type_id($payment_type_id) {
        $this->payment_type_id = $payment_type_id;
        return $this;
    }
    public function get_payment_type_id(){
	    return $this->payment_type_id;
    }

	public function get_business_key() {
		return $this->business_key;
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

	public function get_amount() {
		return $this->amount;
	}

	public function get_zuqi() {
		return $this->zuqi;
	}
	public function get_zuqi_type() {
		return $this->zuqi_type;
	}

	public function get_zujin() {
		return $this->zujin;
	}

	public function get_yajin() {
		return $this->yajin;
	}

	public function get_mianyajin() {
		return $this->mianyajin;
	}

	public function get_yiwaixian() {
		return $this->yiwaixian;
	}
    public function get_appid() {
        return $this->appid;
    }

	public function get_order_status() {
		return $this->order_status;
	}

	public function get_user_id() {
		return $this->user_id;
	}

	public function get_mobile() {
		return $this->mobile;
	}

	public function get_certified_platform() {
		return $this->certified_platform;
	}

	public function get_credit() {
		return $this->credit;
	}

	public function get_realname() {
		return $this->realname;
	}

	public function get_cert_no() {
		return $this->cert_no;
	}

	public function get_address_id() {
		return $this->address_id;
	}

	public function get_goods_id() {
		return $this->goods_id;
	}

	public function get_goods_name() {
		return $this->goods_name;
	}

	public function get_chengse() {
		return $this->chengse;
	}

	public function get_payment_amount() {
		return $this->payment_amount;
	}

	public function get_payment_time() {
		return $this->payment_time;
	}

	public function get_trade_no() {
		return $this->trade_no;
	}

	public function get_refund_amount() {
		return $this->refund_amount;
	}

	public function get_refund_time() {
		return $this->refund_time;
	}

	public function get_payment_status() {
		return $this->payment_status;
	}

	public function get_payment_id() {
		return $this->payment_id;
	}

	public function get_delivery_status() {
		return $this->delivery_status;
	}

	public function get_delivery_id() {
		return $this->delivery_id;
	}

	public function get_return_status() {
		return $this->return_status;
	}

	public function get_return_id() {
		return $this->return_id;
	}

	public function get_receive_status() {
		return $this->receive_status;
	}

	public function get_receive_id() {
		return $this->receive_id;
	}

	public function get_evaluation_status() {
		return $this->evaluation_status;
	}

	public function get_evaluation_id() {
		return $this->evaluation_id;
	}

	public function get_refund_status() {
		return $this->refund_status;
	}

	public function get_refund_id() {
		return $this->refund_id;
	}

	public function get_service_status() {
		return $this->service_status;
	}

	public function get_service_id() {
		return $this->service_id;
	}

	public function get_reason_id() {
		return $this->reason_id;
	}

	public function get_reason_text() {
		return $this->reason_text;
	}

	/**
	 * @return $status
	 */
	public function get_status() {
		return $this->status;
	}

	/**
	 * @param !CodeTemplates.settercomment.paramtagcontent!
	 */
	public function set_status($status) {
		$this->status = $status;
	}

	/**
	 * 获取 订单状态
	 * @return \oms\state\State
	 */
	public function get_state(){
		return $this->State;
	}

	/**
	 * 
	 * @param \oms\state\State $state
	 * @return \oms\Order
	 */
	public function set_state(\oms\state\State $state) {
		$this->State = $state;
		return $this;
	}
	
	public function get_name(){
	    return $this->State->get_name();
	}
	public function get_client_name(){
	    return $this->State->get_client_name();
	}
	public function get_operation_list(){
	    return $this->State->get_operation_list();
	}

	/**
	 * 设置 观察者主题对象
	 * @param \oms\observer\OrderObservable $OrderObservable
	 * @return \oms\Order
	 */
	public function set_observable(\oms\observer\OrderObservable $OrderObservable) {
		$this->OrderObservable = $OrderObservable;
		return $this;
	}

	/**
	 * 获取 观察者主题对象
	 * @return \oms\observer\OrderObservable
	 */
	public function get_observable() {
		if ($this->OrderObservable == null) {
			new \oms\observer\OrderObservable($this);
		}
		return $this->OrderObservable;
	}

	/**
	 * 设置 状态转换
	 * @param \oms\state\StateTransition $stateTransition
	 */
	public function set_state_transition(\oms\state\StateTransition $stateTransition) {
		$this->StateTransition = $stateTransition;
	}

	/**
	 * 获取状态转换
	 * @return \oms\state\StateTransition
	 */
	public function get_state_transition() {
		return $this->StateTransition;
	}

	//-+------------------------------------------------------------------------
	// | 订单操作
	//-+------------------------------------------------------------------------

    //判断订单是否有缓存锁 判断按钮是否显示
    public function order_islock(){
        if( OrderLocker::isLocked($this->order_no) ){
            return true;
        }
        return false;
    }

	//上传物流单号
	public function allow_to_upload_wuliu(){
        if( OrderLocker::isLocked($this->order_no) ){
            return false;
        }
	    return $this->State->allow_to_upload_wuliu();
	}
	public function upload_wuliu($data){
	    $b = $this->State->upload_wuliu($data);
		$this->get_observable()->set_status($b);
		$this->get_observable()->notify();
		return $b;
	}
	//开启服务
	public function allow_to_open_service(){
        if( OrderLocker::isLocked($this->order_no) ){
            return false;
        }
	    return $this->State->allow_to_open_service();
	}
	public function open_service($data){
	    $b = $this->State->open_service($data);
		$this->get_observable()->set_status($b);
		$this->get_observable()->notify();
		return $b;
	}
	
	//关闭服务
	public function allow_to_close_service(){
        if( OrderLocker::isLocked($this->order_no) ){
            return false;
        }
	    return $this->State->allow_to_close_service();
	}
	public function close_service($data){
	    $b = $this->State->close_service($data);
		$this->get_observable()->set_status($b);
		$this->get_observable()->notify();
		return $b;
	}
	
	/**
	 * 判断订单是否允许取消
	 * @return boolean
	 */
	public function allow_to_cancel_order() {
        if( OrderLocker::isLocked($this->order_no) ){
            return false;
        }
		return $this->State->allow_to_cancel_order();
	}

	/**
	 * 取消订单
	 * @return boolean
	 * @throws \Exception
	 */
	public function cancel_order($data) {
		$b = $this->State->cancel_order($data);
		$this->get_observable()->set_status($b);
		$this->get_observable()->notify();
		return $b;
	}
	
	/**
	 * 判断订单是否允许取消发货
	 * @return boolean
	 */
	public function allow_to_cancel_delivery() {
        if( OrderLocker::isLocked($this->order_no) ){
            return false;
        }
	    return $this->State->allow_to_cancel_delivery();
	}
	
	/**
	 * 取消发货
	 * @return boolean
	 * @throws \Exception
	 */
	public function cancel_delivery($data) {
	    $b = $this->State->cancel_delivery($data);
	    $this->get_observable()->set_status($b);
	    $this->get_observable()->notify();
	    return $b;
	}

	/**
	 * 判断是否允许 租用中
	 * @return boolean
	 */
	public function allow_to_inservice() {
        if( OrderLocker::isLocked($this->order_no) ){
            return false;
        }
		return $this->State->allow_to_inservice();
	}

	/**
	 * 租用中
	 * @return boolean
	 * @throws \Exception
	 */
	public function inservice($data) {
		$b = $this->State->inservice($data);
		$this->get_observable()->set_status($b);
		$this->get_observable()->notify();
		return $b;
	}

	/**
	 * 判断门店订单是否允许 审核
	 * @return boolean
	 */
	public function allow_to_store_check_order() {
        if( OrderLocker::isLocked($this->order_no) ){
            return false;
        }
		return $this->State->allow_to_store_check_order();
	}

	/**
	 * 订单 自动审核
	 * @return boolean
	 * @throws \Exception
	 */
	public function store_check_order($data) {
		$b = $this->State->store_check_order($data);
		$this->get_observable()->set_status($b);
		$this->get_observable()->notify();
		return $b;
	}
	
	//买断申请
	public function allow_to_apply_for_buyout(){
        if( OrderLocker::isLocked($this->order_no) ){
            return false;
        }
	    return $this->State->allow_to_apply_for_buyout();
	}
	public function apply_for_buyout($data){
	    $b = $this->State->apply_for_buyout($data);
		$this->get_observable()->set_status($b);
		$this->get_observable()->notify();
		return $b;
	}

	/**
	 * 判断是否确认门店订单
	 * @return boolean
	 */
	public function allow_to_store_confirm_order() {
        if( OrderLocker::isLocked($this->order_no) ){
            return false;
        }
		return $this->State->allow_to_store_confirm_order();
	}

	/**
	 * 门店订单确认
	 * @return boolean
	 * @throws \Exception
	 */
	public function store_confirm_order($data) {
		$b = $this->State->store_confirm_order($data);
		$this->get_observable()->set_status($b);
		$this->get_observable()->notify();
		return $b;
	}
	/**
	 * 判断是否 门店换货
	 * @return boolean
	 */
	public function allow_to_store_huanhuo() {
        if( OrderLocker::isLocked($this->order_no) ){
            return false;
        }
		return $this->State->allow_to_store_return();
	}

	/**
	 * 门店换货
	 * @return boolean
	 * @throws \Exception
	 */
	public function store_huanhuo($data) {
		$b = $this->State->store_return($data);
		$this->get_observable()->set_status($b);
		$this->get_observable()->notify();
		return $b;
	}
	
	
	// 回寄/换货
	public function allow_to_create_delivery() {
        if( OrderLocker::isLocked($this->order_no) ){
            return false;
        }
	    return $this->State->allow_to_create_delivery();
	}
	
	public function create_delivery($data) {
	    $b = $this->State->create_delivery($data);
	    $this->get_observable()->set_status($b);
	    $this->get_observable()->notify();
	    return $b;
	}


	//申请退款
	public function allow_to_create_refund() {
        if( OrderLocker::isLocked($this->order_no) ){
            return false;
        }
		return $this->State->allow_to_create_refund();
	}

	public function create_refund($data) {
		$b = $this->State->create_refund($data);
		$this->get_observable()->set_status($b);
		$this->get_observable()->notify();
		return $b;
	}
	
	//退款
	public function allow_to_refund(){
        if( OrderLocker::isLocked($this->order_no) ){
            return false;
        }
	   return $this->State->allow_to_refund();
	}
	public function refund($data){
	    $b = $this->State->refund($data);
		$this->get_observable()->set_status($b);
		$this->get_observable()->notify();
		return $b;
	}
    //银联退款回调方法
    public function allow_to_refund_notify(){
        return $this->State->allow_to_refund_notify();
    }
    public function refund_notify($data){
        $b = $this->State->refund_notify($data);
        $this->get_observable()->set_status($b);
        $this->get_observable()->notify();
        return $b;
    }
	
	//修改退款金额
	public function allow_to_edit_refund(){
        if( OrderLocker::isLocked($this->order_no) ){
            return false;
        }
	    return $this->State->allow_to_edit_refund();
	}
	public function edit_refund($data){
	    $b = $this->State->edit_refund($data);
		$this->get_observable()->set_status($b);
		$this->get_observable()->notify();
		return $b;
	}

	// 签收
	public function allow_to_sign_delivery() {
        if( OrderLocker::isLocked($this->order_no) ){
            return false;
        }
		return $this->State->allow_to_sign_delivery();
	}

	public function sign_delivery($data) {
		$b = $this->State->sign_delivery($data);
		$this->get_observable()->set_status($b);
		$this->get_observable()->notify();
		return $b;
	}

	//用户拒签
	public function allow_to_refused() {
        if( OrderLocker::isLocked($this->order_no) ){
            return false;
        }
		return $this->State->allow_to_refused();
	}

	public function refused($data) {
		$b = $this->State->refused($data);
		$this->get_observable()->set_status($b);
		$this->get_observable()->notify();
		return $b;
	}

	//退货审核
	public function allow_to_check_returns() {
        if( OrderLocker::isLocked($this->order_no) ){
            return false;
        }
		return $this->State->allow_to_check_returns();
	}

	public function agreed_returns($data) {
		$b = $this->State->agreed_returns($data);
		$this->get_observable()->set_status($b);
		$this->get_observable()->notify();
		return $b;
	}

	public function denied_returns($data) {
		$b = $this->State->denied_returns($data);
		$this->get_observable()->set_status($b);
		$this->get_observable()->notify();
		return $b;
	}

	//取消退货
	public function allow_to_cancel_returns() {
        if( OrderLocker::isLocked($this->order_no) ){
            return false;
        }
		return $this->State->allow_to_cancel_returns();
	}

	public function cancel_returns($data) {
		$b = $this->State->cancel_returns($data);
		$this->get_observable()->set_status($b);
		$this->get_observable()->notify();
		return $b;
	}

	//平台收货
	public function allow_to_confirm_received() {
        if( OrderLocker::isLocked($this->order_no) ){
            return false;
        }
		return $this->State->allow_to_confirm_received();
	}

	public function confirm_received($data) {
		$b = $this->State->confirm_received($data);
		$this->get_observable()->set_status($b);
		$this->get_observable()->notify();
		return $b;
	}

	/**
	 * 判断是否线上确认订单
	 * @return boolean
	 */
	public function allow_to_confirm_order() {
        if( OrderLocker::isLocked($this->order_no) ){
            return false;
        }
		return $this->State->allow_to_confirm_order();
	}

	/**
	 * 线上订单确认
	 * @return boolean
	 * @throws \Exception
	 */
	public function confirm_order() {
		$b = $this->State->confirm_order();
		$this->get_observable()->set_status($b);
		$this->get_observable()->notify();
		return $b;
	}


	public function allow_to_pay() {
        if( OrderLocker::isLocked($this->order_no) ){
            return false;
        }
		return $this->State->allow_to_pay();
	}
	/**
	 * 订单支付
	 * @return boolean
	 * @throws \Exception
	 */
	public function pay(string $trade_channel, array $data, int $trade_id) {
		$b = $this->State->pay($trade_channel,$data, $trade_id);
		$this->get_observable()->set_status($b);
		$this->get_observable()->notify();
		return $b;
	}
	

	/**
	 * 判断门店订单是否允许 提货
	 * @return boolean
	 */
	public function allow_to_store_pickup() {
        if( OrderLocker::isLocked($this->order_no) ){
            return false;
        }
		return $this->State->allow_to_store_pickup();
	}

	/**
	 * 门店订单提货
	 * @return boolean
	 * @throws \Exception
	 */
	public function store_pickup($data) {
		$b = $this->State->store_pickup($data);
		$this->get_observable()->set_status($b);
		$this->get_observable()->notify();
		return $b;
	}

	/**
	 * 判断平台是否收货
	 * @return boolean
	 */
	public function allow_to_goods_receipt() {
        if( OrderLocker::isLocked($this->order_no) ){
            return false;
        }
		return $this->State->allow_to_goods_receipt();
	}

	/**
	 * 平台收货
	 * @return boolean
	 * @throws \Exception
	 */
	public function goods_receipt($data) {
		$b = $this->State->goods_receipt($data);
		$this->get_observable()->set_status($b);
		$this->get_observable()->notify();
		return $b;
	}

	/**
	 * 判断是否允许检测
	 * @return boolean
	 */
	public function allow_to_evaluation() {
        if( OrderLocker::isLocked($this->order_no) ){
            return false;
        }
		return $this->State->allow_to_evaluation();
	}

	/**
	 * 检测
	 * @return boolean
	 * @throws \Exception
	 */
	public function evaluation($data) {
		$b = $this->State->evaluation($data);
		$this->get_observable()->set_status($b);
		$this->get_observable()->notify();
		return $b;
	}


	/**
	 * 检测异常
	 * @return boolean
	 */
	public function allow_to_abnormal() {
        if( OrderLocker::isLocked($this->order_no) ){
            return false;
        }
		return $this->State->allow_to_abnormal();
	}

	/**
	 * 检测
	 * @return boolean
	 * @throws \Exception
	 */
	public function abnormal($data) {
		$b = $this->State->abnormal($data);
		$this->get_observable()->set_status($b);
		$this->get_observable()->notify();
		return $b;
	}

	/**
	 * 判断是否允许换货
	 * @return boolean
	 */
	public function allow_to_huanhuo() {
        if( OrderLocker::isLocked($this->order_no) ){
            return false;
        }
		return $this->State->allow_to_huanhuo();
	}

	/**
	 * 换货
	 * @return boolean
	 * @throws \Exception
	 */
	public function huanhuo($data) {
		$b = $this->State->huanhuo($data);
		$this->get_observable()->set_status($b);
		$this->get_observable()->notify();
		return $b;
	}
	
	/**
	 * 判断是否允许退货
	 * @return boolean
	 */
	public function allow_to_apply_for_return() {
        if( OrderLocker::isLocked($this->order_no) ){
            return false;
        }
	    return $this->State->allow_to_apply_for_return();
	}
	
	/**
	 * 退货
	 * @return boolean
	 * @throws \Exception
	 */
	public function apply_for_return($data) {
	    $b = $this->State->apply_for_return($data);
	    $this->get_observable()->set_status($b);
	    $this->get_observable()->notify();
	    return $b;
	}

	// 回寄发货
	public function allow_to_delivery() {
        if( OrderLocker::isLocked($this->order_no) ){
            return false;
        }
		return $this->State->allow_to_delivery();
	}

	public function delivery($data) {
		$b = $this->State->delivery($data);
		$this->get_observable()->set_status($b);
		$this->get_observable()->notify();
		return $b;
	}

	/**
	 * 判断是否允许买断
	 * @return boolean
	 */
	public function allow_to_buyout_goods() {
        if( OrderLocker::isLocked($this->order_no) ){
            return false;
        }
		return $this->State->allow_to_buyout_goods();
	}

	/**
	 * 买断中
	 * @return boolean
	 * @throws \Exception
	 */
	public function buyout_goods($data) {
		$b = $this->State->buyout_goods($data);
		$this->get_observable()->set_status($b);
		$this->get_observable()->notify();
		return $b;
	}

	/**
	 * 判断是否买断
	 * @return boolean
	 */
	public function allow_to_buyout() {
        if( OrderLocker::isLocked($this->order_no) ){
            return false;
        }
		return $this->State->allow_to_buyout();
	}

	/**
	 * 已买断
	 * @return boolean
	 * @throws \Exception
	 */
	public function buyout($data) {
		$b = $this->State->buyout($data);
		$this->get_observable()->set_status($b);
		$this->get_observable()->notify();
		return $b;
	}

    // 资金授权
    public function allow_to_funds_authorize() {
        if( OrderLocker::isLocked($this->order_no) ){
            return false;
        }
        return $this->State->allow_to_funds_authorize();
    }

    public function funds_authorize() {
        $b = $this->State->funds_authorize();
        $this->get_observable()->set_status($b);
        $this->get_observable()->notify();
        return $b;
    }
    // 解除资金授权
    public function allow_to_remove_authorize() {
        if( OrderLocker::isLocked($this->order_no) ){
            return false;
        }
        return $this->State->allow_to_remove_authorize();
    }

    public function remove_authorize($data) {
        $b = $this->State->remove_authorize($data);
        $this->get_observable()->set_status($b);
        $this->get_observable()->notify();
        return $b;
    }
    //取消发货单
    public function quxiao_delivery($data) {
        $b = $this->State->quxiao_delivery($data);
        $this->get_observable()->set_status($b);
        $this->get_observable()->notify();
        return $b;
    }

	
	/**
	 * 获取当前状态下 客户端 操作状态列表
	 * @param array $order
	 * @return array
	 */
	public function get_client_operations(array $order): array{
		return [
			'return'	=> $this->allow_to_apply_for_return(),//退货操作
			'cancel' => $this->allow_to_cancel_order(),	// 取消订单操作
			'payment' => $this->allow_to_pay(),	// 支付操作
			'fundauth' => $this->allow_to_funds_authorize(),	// 资金预授权操作
			'delivery' => $this->allow_to_sign_delivery(),	// 签收发货单操作
		];
	}
}
