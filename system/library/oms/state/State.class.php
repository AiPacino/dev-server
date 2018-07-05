<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace oms\state;
use oms\Order;
use zuji\Business;
use zuji\Config;

/**
 * Description of State
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
abstract class State{   
    //--------------------------------------------------------------------------------------------
    //--status状态--------------------------------------------------------------------------
    //--------------------------------------------------------------------------------------------
    
    /**
     * @var 已下单
     */
    const OrderCreated = 1;
    /**
     * @var 已取消
     */
    const OrderCanceled = 2;
    /**
     * @var 订单关闭
     */
    const OrderClosed =3;
    /**
     * @var 租用中
     */
    const OrderInService = 4;
    /**
     * @var 审核通过
     */
    const StorePassed = 5;
    /**
     * @var 门店确认订单
     */
    const StoreConfirmed = 6;
    /**
     * @var 已支付
     */
    const PaymentSuccess = 7;
    /**
     * @var 线上确认订单
     */
    const OrderConfirmed =8;
    /**
     * @var 退款中
     */
    const OrderRefunding =9;
    /**
     * @var 已退款
     */
    const OrderRefunded =10;
    /**
     * @var 已发货
     */
    const OrderDeliveryed =11;
    /**
     * @var 用户拒签
     */
    const OrderRefused =12;
    /**
     * @var 退货审核中 
     */
    
    const OrderReturnChecking =13;
    /**
     * @var 退货中
     */
    const OrderReturning =14;
    /**
     * @var 平台已收货
     */
    const OrderReceived =15;
    /**
     * @var 检测合格
     */
    const OrderEvaluationQualified =16;
    /**
     * @var 检测不合格
     */
    const OrderEvaluationUnqualified =17;
    /**
     * @var 换货中
     */
    const OrderHuanhuoing =18;
    /**
     * @var 回寄中
     */
    const OrderHuijiing =19;
    /**
     * @var 买断中
     */
    const OrderBuyOuting =20;
    /**
     * @var 已买断
     */
    const OrderBuyOuted=21;
    /**
     * @var 资金已授权
     */
    const FundsAuthorized =22;
    /**
     * @var 资金已解冻
     */
    const FundsThawed =23;
    
    /**
     * 订单对象
     * @var \oms\Order
     */
    protected $Order = null;


    public function __construct( Order $Order ) {
		$this->Order = $Order;
		$this->Order->set_state($this);
    }
    
    abstract public function get_state();
    
    abstract public function get_name();
    
    abstract public function get_client_name();
    
	public function get_operation_list(){
		return [];
	}

    /**
     *
     * @param type $data
     * @return Order
     * @throws \Exception
     */
    public static function createState( Order $Order ){
        $status =$Order->get_status();
        $business_key=$Order->get_business_key();
        $payment_type_id =$Order->get_payment_type_id();
		
        if( $status == State::OrderCreated ){
            if($business_key ==Business::BUSINESS_STORE){ // 门店订单
                return new StoreCreatedState($Order);
            }else{// 线上订单
                if($payment_type_id==Config::WithhodingPay){// 代扣+预授权 订单
                    return new FundAuthCreateState( $Order );
				}elseif($payment_type_id ==Config::UnionPay){
                    return new UnionPayCreatedState($Order);
                }elseif($payment_type_id == Config::MiniAlipay){
                    return new MiniAlipayCreatedState($Order);
                }else{
					return new CreatedState($Order);
				}
            }
        }elseif( $status == State::OrderCanceled){
            return new CanceledState($Order);
        }elseif( $status == State::StorePassed){
            return new StorePassedState($Order);
        }elseif( $status == State::StoreConfirmed){
            if($payment_type_id==Config::WithhodingPay){// 代扣+预授权 订单
                return new StoreConfirmedWithhodeState($Order);
            }else{
                return new StoreConfirmedState($Order);
            }
        }elseif( $status == State::PaymentSuccess){
            if($business_key ==Business::BUSINESS_STORE){// 门店
				if($payment_type_id==Config::WithhodingPay){
					return new StoreFundsAuthorizedState($Order);//门店订单 资金授权成功
				}else{
					return new StorePaymentSuccessState($Order);// 门店订单 支付成功
				}
            }else{
                return new PaymentSuccessState($Order);
            }
        }elseif( $status == State::OrderInService){
            if($business_key ==Business::BUSINESS_STORE){
                return new StoreInServiceState($Order);
            }else{
                return new OrderInServiceState($Order);
            }      
        }elseif( $status == State::OrderConfirmed){
            return new OrderConfirmedState($Order);
        }elseif( $status == State::OrderRefunding){
            return new OrderRefundingState($Order);
        }elseif( $status == State::OrderRefunded){
            return new OrderRefundedState($Order);
        }elseif( $status == State::OrderDeliveryed){
            return new OrderDeliveryedState($Order);
        }elseif( $status == State::OrderRefused){
            return new OrderRefusedState($Order);
        }elseif( $status == State::OrderReturnChecking){
            return new OrderReturnCheckingState($Order);
        }elseif( $status == State::OrderReturning){
            return new OrderReturningState($Order);
        }elseif( $status == State::OrderReceived){
            return new OrderReceivedState($Order);
        }elseif( $status == State::OrderEvaluationQualified){
            return new OrderEvaluationQualifiedState($Order);
        }elseif( $status == State::OrderEvaluationUnqualified){
            return new OrderEvaluationUnqualifiedState($Order);
        }elseif( $status == State::OrderHuanhuoing){
            return new OrderHuanhuoingState($Order);
        }elseif( $status == State::OrderHuijiing){
            return new OrderHuijiingState($Order);
        }elseif( $status == State::OrderBuyOuting){
            return new OrderBuyOutingState($Order);
        }elseif( $status == State::OrderBuyOuted){
            return new OrderBuyOutedState($Order);
        }elseif( $status == State::FundsAuthorized){
            if($business_key ==Business::BUSINESS_STORE){
                return new StoreFundsAuthorizedState($Order);
            }else{
                return new FundsAuthorizedState($Order);
            }
        }elseif( $status == State::FundsThawed){
            return new FundsThawedState($Order);
        }
        
        return new ClosedState($Order);

    }
    public static function getClientStatusList(){
        return [
            self::OrderCreated => '已下单',
            self::OrderCanceled=>'已取消',
            self::OrderClosed=>'已关闭',
            self::OrderInService=>'租用中',
            self::StorePassed=>'待面签',
            self::StoreConfirmed=>'待支付',
            self::PaymentSuccess=>'发货中',
            self::OrderConfirmed=>'确认收货',
            self::OrderRefunding=>'退款中',
            self::OrderRefunded=>'已退款',
            self::OrderDeliveryed=>'待收货',
            self::OrderRefused=>'租用中',
            self::OrderReturnChecking=>'退货审批中',
            self::OrderReturning=>'退货中',
            self::OrderReceived=>'平台已收货',
            self::OrderEvaluationQualified=>'检测合格',
            self::OrderEvaluationUnqualified=>'检测不合格',
            self::OrderHuanhuoing=>'发货中',
            self::OrderHuijiing=>'回寄中',
            self::FundsAuthorized=>'资金已授权',
            self::FundsThawed=>'资金已解冻',
            
        ];
    }
    
    public static function getClientStatusName($status){
        $list = self::getClientStatusList();
        if( isset($list[$status]) ){
            return $list[$status];
        }
        return '';
    }

    public static function getStatusAllList(){
        return [
            self::OrderCreated => '已下单',
            self::OrderCanceled=>'已取消',
            self::OrderClosed=>'已关闭',
            self::OrderInService=>'租用中',
            self::StorePassed=>'审核通过',
            self::StoreConfirmed=>'门店确认订单',
            self::PaymentSuccess=>'已支付',
            self::OrderConfirmed=>'确认订单',
            self::OrderRefunding=>'退款中',
            self::OrderRefunded=>'已退款',
            self::OrderDeliveryed=>'已发货',
            self::OrderRefused=>'用户拒签',
            self::OrderReturnChecking=>'退货审批中',
            self::OrderReturning=>'退货中',
            self::OrderReceived=>'平台已收货',
            self::OrderEvaluationQualified=>'检测合格',
            self::OrderEvaluationUnqualified=>'检测不合格',
            self::OrderHuanhuoing=>'换货中',
            self::OrderHuijiing=>'回寄中',
            self::FundsAuthorized=>'资金已授权',
            self::FundsThawed=>'资金已解冻',

        ];
    }

    public static function getStatusAllName($status){
        $list = self::getStatusAllList();
        if( isset($list[$status]) ){
            return $list[$status];
        }
        return '';
    }
    
    public static function getStatusList(){
        return [
            self::OrderCreated => '已下单',
            self::OrderCanceled=>'已取消',
            self::OrderClosed=>'已关闭',
            self::OrderInService=>'租用中',
            self::PaymentSuccess=>'已支付',
            self::FundsAuthorized=>'资金已授权',
            self::OrderConfirmed=>'已确认',
            self::OrderRefunded=>'已退款',
            self::OrderDeliveryed=>'已发货',     
        ];
    }
    
    public static function getStatusName($status){
        $list = self::getStatusList();
        if( isset($list[$status]) ){
            return $list[$status];
        }
        return '';
    }
    
    
    //上传物流单号
    public function allow_to_upload_wuliu(){
        return false;
    }
    public function upload_wuliu($data){
        throw new \Exception('禁止上传物流单号');
    }
    
    /**
     * 判断是否允许 线上订单确认
     * @return boolean
     */
    
    public function allow_to_confirm_order(){
        return false;
    }
    /**
     * 线上订单确认
	 * <p>1）订单状态转变为 已确认</p>
	 * <p>2）创建发货单</p>
     * @return boolean
     * @throws \Exception
     */

    public function confirm_order(){
        throw new \Exception('禁止确认订单');
    }
    
    
    /**
     * 判断是否允许 门店订单确认
     * @return boolean
     */
    
    public function allow_to_store_confirm_order(){
	return false;
    }
    /**
     * 门店订单确认
     * @return boolean
     * @throws \Exception
     */
    public function store_confirm_order($data){
	throw new \Exception('禁止确认订单');
    }
    
    /**
     * 判断是否允许 取消订单
     * @return boolean
     */
    public function allow_to_cancel_order(){
	return false;
    }
    
    /**
     * 取消订单
     * <p>直接结束订单</p>
     * @return boolean
     * @throws \Exception
     */
    public function cancel_order($data){
        throw new \Exception('禁止取消');
    }
    
    /**
     * 取消发货
     * @return boolean
     * @throws \Exception
     */
    public function cancel_delivery($data){
        throw new \Exception('禁止取消发货');
    }
    
    /**
     * 判断是否允许 取消发货
     * @return boolean
     */
    public function allow_to_cancel_delivery(){
        return false;
    }
    /**
     * 判断是否 门店换货
     * @return boolean
     */
    public function allow_to_store_huanhuo() {
        return false;
    }
    /**
     * 门店换货
     * @return boolean
     * @throws \Exception
     */
    public function store_huanhuo($data) {
        throw new \Exception('禁止门店换货');
    }
     /**
     * 判断是否允许 租用中
     * @return boolean
     */
    public function allow_to_inservice() {
        return false;
    }
    /**
     * 租用中
     * @return boolean
     * @throws \Exception
     */
    public function inservice($data) {
       throw new \Exception('禁止租用中');
    }
    
    /**
     * 判断是否允许审核
     * @return boolean
     */
    public function allow_to_store_check_order(){
        return false;
    }
    /**
     * 审核订单
     * @return boolean
     * @throws \Exception
     */
    public function store_check_order($data){
        throw new \Exception('禁止审核');
    }
    
    // 支付
    public function allow_to_pay(){
	return false;
    }
    public function pay(string $trade_channel, array $data, int $trade_id){
    throw new \Exception('禁止支付');
    }
    
    // 发货
    public function allow_to_delivery(){
	return false;
    }
    public function delivery($data){
	throw new \Exception('禁止发货');
    }
    
    // 创建发货单
    public function allow_to_create_delivery(){
        return false;
    }
    public function create_delivery($data){
        throw new \Exception('禁止创建发货单');
    }
    
    //开启服务
    public function allow_to_open_service(){
        return false;
    }
    public function open_service($data){
        throw new \Exception('禁止开启服务');
    }
    
    //关闭服务
    public function allow_to_close_service(){
        return false;
    }
    public function close_service($data){
        throw new \Exception('禁止关闭服务');
    }

    
    //确认退款
    public function allow_to_create_refund(){
        return false;
    }
    public function create_refund($data){
        throw new \Exception('禁止申请退款');
    }
    //退款
    public function allow_to_refund(){
        return false;
    }
    public function refund($data){
        throw new \Exception('禁止退款');
    }
    //银联退款回调方法
    public function allow_to_refund_notify(){
        return false;
    }
    public function refund_notify($data){
        throw new \Exception('禁止退款回调');
    }
    
    //修改退款金额
    public function allow_to_edit_refund(){
        return false;
    }
    public function edit_refund($data){
        throw new \Exception('禁止修改退款金额');
    }
    
    // 签收
    public function allow_to_sign_delivery(){
	return false;
    }
    public function sign_delivery($data){
	throw new \Exception('禁止确认收货');
    }

    // // 签收
    // public function allow_to_create_delivery(){
    // return false;
    // }
    // public function create_delivery($data){
    // throw new \Exception('禁止创建发货单');
    // }
    
    //用户拒签
    public function allow_to_refused(){
        return false;
    }
    public function refused($data){
        throw new \Exception('禁止拒签');
    }
    
    // 退货申请
    public function allow_to_apply_for_return(){
	return false;
    }
    public function apply_for_return($data){
	throw new \Exception('禁止退货');
    }
    
    //退货审核
    public function allow_to_check_returns(){
	return false;
    }
    public function agreed_returns($data){
    throw new \Exception('禁止退货审核同意');
    }

    public function denied_returns($data){
        throw new \Exception('禁止退货审核拒绝');
    }
    
    //取消退货
    public function allow_to_cancel_returns(){
        return false;
    }
    public function cancel_returns($data){
       throw new \Exception('禁止取消退货');
    }
    
     
    //平台收货
    public function allow_to_confirm_received(){
        return false;
    }
    public function confirm_received($data){
        throw new \Exception('禁止平台确认收货');
    }
    
    //检测
    public function allow_to_evaluation() {
        return false;
    }
    public function evaluation($data) {
        throw new \Exception('禁止检测');
    }
    
    //检测异常
    public function allow_to_abnormal() {
        return false;
    }
    public function abnormal($data) {
        throw new \Exception('禁止检测异常');
    }

    //换货
    public function allow_to_huanhuo(){
        return false;
    }
    public function huanhuo($data){
        throw new \Exception('禁止换货');
    }

    //回寄
    public function allow_to_huiji(){
        return false;
    }
    public function huiji($data){
        throw new \Exception('禁止回寄');
    }

    //买断申请
    public function allow_to_apply_for_buyout(){
        return false;
    }
    public function apply_for_buyout($data){
        throw new \Exception('禁止买断申请');
    }

    //买断
    public function allow_to_buyout(){
        return false;
    }
    public function buyout($data){
        throw new \Exception('禁止买断中');
    }
    // 资金授权
    public function allow_to_funds_authorize() {
        return false;
    }

    public function funds_authorize() {
        throw new \Exception('禁止资金授权');
    }

    // 解除资金授权
    public function allow_to_remove_authorize() {
        return false;
    }

    public function remove_authorize($data) {
        throw new \Exception('禁止解除资金授权');
    }
    //取消发货单
    public function quxiao_delivery($data) {
        throw new \Exception('禁止取消发货单');
    }
    
}
