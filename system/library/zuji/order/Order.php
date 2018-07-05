<?php
namespace zuji\order;

use oms\state\State;
use zuji\Configurable;
use zuji\Business;

/**
 * 订单基类
 * @abstract
 * @access public
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
abstract class Order extends Configurable{
    
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
    protected $status=0;
    
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
    protected $trade_no = null;	// 租机交易编号
    //-+------------------------------------------------------------------------
    // | 退款
    //-+------------------------------------------------------------------------
    protected $refund_amount = 0.00;
    protected $refund_time = 0;
    
    //-+------------------------------------------------------------------------
    // | 订单其他单据
    //-+------------------------------------------------------------------------
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
    // | 订单取消原因
    //-+------------------------------------------------------------------------
    protected $reason_id = 0;
    protected $reason_text = '';
    
    //-+------------------------------------------------------------------------
    // | 订单管理员ID
    //-+------------------------------------------------------------------------
    protected $admin_id = 0;
    
    /**
     * 构造函数
     * @param array $data
     */
    public function __construct( Array $data=[] ) {
	// $throwException=null 忽略未定义的属性
	$this->config($data,$throwException=null);
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
    public function get_status()
    {
        return $this->status;
    }

 /**
     * @param !CodeTemplates.settercomment.paramtagcontent!
     */
    public function set_status($status)
    {
        $this->status = $status;
    }

 //-+------------------------------------------------------------------------
    // | 方法
    //-+------------------------------------------------------------------------
    /**
     * 是否允许 取消订单
     * @return bool true：允许；false：不允许
     */
    abstract public function allow_to_cancel();
    /**
     * 是否允许创建发货单
     * @return bool true:允许 ;false:不允许
     */
    abstract public function allow_create_delivery();
    

    //-+------------------------------------------------------------------------
    // | 类静态方法
    //-+------------------------------------------------------------------------
    
    /**
     * 
     * @param type $data
     * @return Order
     * @throws \Exception
     */
    public static function createOrder( $data ){
	if( $data['business_key'] == Business::BUSINESS_ZUJI ){
	    return new ZujiOrder($data);
	}
	if($data['business_key'] == Business::BUSINESS_STORE){
	    return new StoreOrder($data);
	}
	return new NullOrder($data);
	//throw new \Exception('订单业务类型错误（#'.$data['business_key'].'）');
    }

    /**
     * 判断订单是否开启
     */
    public function is_open(){
        if($this->order_status == OrderStatus::OrderCreated){
            return true;
        }
        return false;
    }
    
    /**
     * 判断订单是否生成收货单
     */
    public function is_receive(){
        if($this->receive_id >0){
            return true;
        }
        return false;
    }
    /**
     * 判断是否生成检测单
     */
    public function is_evaluation(){
        if($this->evaluation_id >0){
            return true;
        }
        return false;
    }
    
    /**
     * 判断订单是否可以生成收货单
     */
    public function create_receive(){
        //判断订单是否生效
        if(!$this->is_open()){
            return false;
        }
        
        //判断是否已完成收货
        if($this->delivery_id == 0 || $this->delivery_status !=DeliveryStatus::DeliveryConfirmed){
            return false;
        }
        //判断是否生成收货单
        if($this->receive_id ==0){
            return true;
        }else{
            //判断收货单的业务是否已经完成
            if($this->receive_status ==ReceiveStatus::ReceiveCanceled 
                || $this->receive_status ==ReceiveStatus::ReceiveConfirmed 
                ||$this->receive_status ==ReceiveStatus::ReceiveFinished){
                
                return true;
            }
        }
        
        return false;
        
    }
    
    /**
     * 判断订单是否可以生成退款单
     */
    public function create_refund(){
        //判断订单是否生效
        if(!$this->is_open()){
            return false;
        }
        if($this->payment_status ==PaymentStatus::PaymentSuccessful && $this->refund_id ==0){
            return true;
        }
        return false;
    
    }

    
    /**
     * 校验订单编号格式
     * @param string $no
     * @return boolean
     */
    public static function verifyOrderNumber( $no ){
        //订单编号必须长度大于0
        if( strlen($no) == 0 ){
            return false;
        }
        return true;
    }

    /**
     * @param int $price    价格，单位：分
     * @return string   格式化价格，单位：元
     */
    public static function priceFormat($price){
        $price = max(0,$price);
        return sprintf('%0.2f',$price);
    }


    /**
     *
     * @param array $status_info    订单相关状态集合
     * [
     *      'order_status' => '',
     *      'payment_status' => '',
     *      'delivery_status' => '',
     *      'return_status' => '',
     *      'receive_status' => '',
     *      'evluation_status' => '',
     *      'refund_status' => '',
     * ]
     */
    public static function getStatusName(array $status_info){
        $status_info = filter_array($status_info,[
            'order_status' => 'required',
            'payment_status' => 'required',
            'delivery_status' => 'required',
            'return_status' => 'required',
            'receive_status' => 'required',
            'evaluation_status' => 'required',
            'refund_status' => 'required',
            'service_status'=>'required',
        ]);
        if(count($status_info)!=9){
            return '-状态异常-';
        }
        if( $status_info['order_status'] == OrderStatus::OrderCanceled ){
	    return OrderStatus::getStatusName($status_info['order_status']);
	}
        return '';

    }

    /**
     * 获取允许的租期列表
     * @return array 租期列表
     */
    public static function getZuqiList(){
	return [3,6,12];
    }
    /**
     * 验证租期值是否正确
     * @param int $zuqi	   待验证的租期值
     * @return bool
     */
    public static function verifyZuqi( $zuqi ){
	$zuqi_list = self::getZuqiList();
	return in_array($zuqi, $zuqi_list);
    }

    /**
     * 判断是否允许 取消订单
     *      支付所有状态都可以取消订单
     * @param array 状态码
     *      [
     *          'order_status'=>value,      //订单状态
     *          'delivery_status'=>value    //订单发货状态
     *      ]
     * @return boolean
     */
    public static function judgeCancelOrder($judge_arr){
        $judge_config = [
            'order_status' => [OrderStatus::OrderInitialize=>true,OrderStatus::OrderCreated=>true,OrderStatus::OrderTimeout=>true],
            'delivery_status' => [DeliveryStatus::DeliveryInvalid=>true,DeliveryStatus::DeliveryCreated=>true,DeliveryStatus::DeliveryWaiting=>true,DeliveryStatus::DeliveryProtocol=>true],
        ];
        foreach ($judge_config as $key=>$item) {
            if(!$item[$judge_arr[$key]]){
                return false;
            }
//            $item[$judge_arr[$key]] ?  : $code=false;
        }
        return true;

    }

    /**
     * 判断是否允许 录入维修记录
     *      生效状态    状态
     *      订单发货    阶段状态
     *      已确认收货   状态
     *
     *
     * @param array 状态码
     *      [
     *          'order_status'=>value,      //订单状态
     *          'payment_status'=>value,    //支付状态
     *          'delivery_status'=>value,    //订单发货状态
     *          'service_status'=>value,     //服务状态
     *      ]
     * @return boolean
     */
    public static function judgeRepairlLog($judge_arr,$time=0){
        $judge_config = [
            'order_status'=>[OrderStatus::OrderCreated=>1],
            'payment_status'=>[PaymentStatus::PaymentSuccessful=>1],
            'delivery_status'=>[DeliveryStatus::DeliveryConfirmed=>1],
            'service_status'=>[ServiceStatus::ServiceOpen=>1]
        ];
        foreach ($judge_config as $key=>$item) {
            if(!$item[$judge_arr[$key]]){
                return false;
            }
        }
        return true;

    }
    public static function get_order_root($order){
        $root = [
            //支付
            'payment' => false,
            //可取消
            'cancel' => false,
            //不可取消提示
            'cancel_msg' => false,
            //审核通过
            'passed' => false,
            //确认收货
            'confirm' => false,
            //退货
            'return' => false,
            //物流查询
            'logistics' => false,
            //服务结束
            'service' => false
        ];
        $orderObj = new \oms\Order($order);
        switch($order['status']){
            //已下单
            case State::OrderCreated:
                $root['cancel'] = $orderObj->allow_to_cancel_order();
                $root['payment'] = $orderObj->allow_to_pay();
                break;
            //已取消
            case State::OrderCanceled:
                break;
            //订单关闭
            case State::OrderClosed:
                break;
            //租用中
            case State::OrderInService:
                $root['return'] = $orderObj->allow_to_apply_for_return();
                break;
            //审核通过
            case State::StorePassed:
                $root['passed'] = true;
                break;
            //门店确认订单
            case State::StoreConfirmed:
                $root['cancel'] = $orderObj->allow_to_cancel_order();;
                $root['payment'] = $orderObj->allow_to_pay();
                break;
            //已支付
            case State::PaymentSuccess:
                $root['cancel_msg'] = true;
                break;
            //线上确认订单
            case State::OrderConfirmed:
                break;
            //退款中
            case State::OrderRefunding:
                break;
            //已退款
            case State::OrderRefunded:
                break;
            //已发货
            case State::OrderDeliveryed:
                $root['confirm'] = true;
                $root['confirm'] = $orderObj->allow_to_sign_delivery();
                break;
            //用户拒签
            case State::OrderRefused:
                $root['logistics'] = true;
                break;
            //退货审核中
            case State::OrderReturnChecking:
                $root['return'] = true;
                break;
            //退货中
            case State::OrderReturning:
                $root['return'] = true;
                break;
            //平台已收货
            case State::OrderReceived:
                $root['return'] = true;
                break;
            //检测合格
            case State::OrderEvaluationQualified:
                $root['return'] = true;
                break;
            //检测不合格
            case State::OrderEvaluationUnqualified:
                $root['return'] = true;
                break;
            //换货中
            case State::OrderHuanhuoing:
                $root['return'] = true;
                break;
            //回寄中
            case State::OrderHuijiing:
                $root['logistics'] = true;
                break;
            //买断中
            case State::OrderBuyOuting:

                break;
            //已买断
            case State::OrderBuyOuted:

                break;
            //订单超时
            case State::OrderTimeout:
                break;
        }
        return $root;
    }


}

