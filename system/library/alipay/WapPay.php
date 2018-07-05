<?php
namespace alipay;


require_once __DIR__ . '/aop/request/AlipayTradeWapPayRequest.php';


class WapPay extends BaseApi {

    public function __construct($appid) {
	parent::__construct($appid);
    }
    
    /**
     * 手机网站支付
     * @param array $params
     * [
     *	    'fenqi_zuqi' => '',
     *	    'fenqi_seller_percent' => '',
     *	    'trade_no' => '',
     *	    'amount' => '',
     *	    'subject' => '',
     *	    'body' => '',
     *	    'return_url' => '',
     *      'notify_url' =>'',
     * ]
     * @return string	url
     */
    public function wapPay( $params,$ispage=true,$post=true){
	
	$config = $this->config;
	$params = filter_array($params, [
	    'fenqi_zuqi' => 'required',
	    'fenqi_seller_percent' => 'required',
	    'trade_no' => 'required',
	    'amount' => 'required',
	    'subject' => 'required',
	    'body' => 'required',
	    'return_url' => 'required',
        'notify_url' =>'required',
	]);
	// 默认 支付回跳地址
	if( !isset($params['return_url']) ){
	    $params['return_url'] = $config['return_url'];
	}
	//默认 异步通知回跳地址
    if( !isset($params['notify_url']) ){
        $params['notify_url'] = $config['notify_url'];
    }
	if( !isset($params['body']) ){
	    $params['body'] = '';
	}
	if( count($params)!=8 ){
	    set_error( '创建支付url失败，参数错误' );
	    return false;
	}

	//超时时间
	$timeout_express="1m";
	
		
        $biz_content['productCode'] = "QUICK_WAP_PAY";
        
        // goods_type 商品主类型：0—虚拟类商品，1—实物类商品注：虚拟类商品不支持使用花呗渠道
        $biz_content['goods_type'] = '1';
        
        // 支付方式
        $biz_content['enable_pay_channels'] = $config['enable_pay_channels'];
	
        $biz_content['out_trade_no'] = $params['trade_no'];
        $biz_content['total_amount'] = $params['amount'];
        $biz_content['subject'] = $params['subject'];
        $biz_content['body'] = $params['body'];
        $biz_content['timeout_express'] = $timeout_express;
	// 花呗分期
	if( $params['fenqi_zuqi']>0 ){
	    $biz_content['extend_params'] = array(
		// 系统商编号，该参数作为系统商返佣数据提取的依据，请填写系统商签约协议的PID。注：若不属于支付宝业务经理提供签约服务的商户，暂不对外提供该功能，该参数使用无效。
		'sys_service_provider_id' => '',
		'needBuyerRealnamed' => 'T',    // 是否发起实名校验T：发起F：不发起
		// 账务备注：该字段显示在离线账单的账务备注中
		'TRANS_MEMO' => '花呗分期',
		'hb_fq_num' => $params['fenqi_zuqi'],
		'hb_fq_seller_percent' => $params['fenqi_seller_percent'],   // 卖家承担收费比例，商家承担手续费传入100，用户承担手续费传入0，仅支持传入100、0两种
	    );
	}
	
	$request = new \AlipayTradeWapPayRequest();
	$request->setNotifyUrl($params['notify_url']);
	$request->setReturnUrl($params['return_url']);
	// json 格式字符串
	$request->setBizContent ( json_encode($biz_content) );

	$result = $this->pageExecute($request,$ispage,$post?'POST':'GET');
	
	return $result;
    }
    
}

?>