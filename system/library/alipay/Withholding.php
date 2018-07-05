<?php
namespace alipay;

require __DIR__.'/withholding/alipay_submit.class.php';
require __DIR__.'/withholding/alipay_notify.class.php';

class Withholding  {

    public function __construct() {
    }
    
    /**
     * 获取 代扣签约页面url地址
     * @return string	url
     */
    public function buildRequestForm( string $username, string $return_url ){
		
		require __DIR__.'/withholding/alipay.config.php';
		// 签约异步通知
		$notify_url = config('ALIPAY_WITHHOLDING_NOTIFY');
		/************************************************************/

		// 代扣签约页面接口参数
		$parameter = array(
			// 基本参数
			"service" => "alipay.dut.customer.agreement.page.sign",
			"partner" => trim($alipay_config['partner']),
			"_input_charset"	=> trim(strtolower($alipay_config['input_charset'])),
			"return_url"	=> $return_url,
			"notify_url"	=> $notify_url,
			// 业务参数
			"product_code"	=> 'GENERAL_WITHHOLDING_P',	// 代扣签约页面
			'access_info' => json_encode([
				'channel' => 'ALIPAYAPP',//用户接入渠道： WAP 访问；PC 端访问；ALIPAYAPP 支付宝钱包
			]), 
			// 商户下用户唯一标识（必须，是为了解决异步通知中用户无法识别的问题）
			'external_user_id' => $username,
			//'scene' => '', // 场景码，当传入商户签约号external_sign_no 时，本参数不能为空或默认值
			//'sign_validity_period' => '12m',// 签约有效期 未传入，默认为长期有效
		);

		//建立请求
		$alipaySubmit = new \AlipaySubmit($alipay_config);
		$html_text = $alipaySubmit->buildRequestForm($parameter);
		return $html_text;
    }
    
	/**
	 * 校验异步通知
	 * @param array $post	异步通知数据
	 * @return bool
	 */
	public function verifyNotify($post):bool {
		
		require __DIR__.'/withholding/alipay.config.php';
		//计算得出通知验证结果
		$alipayNotify = new \AlipayNotify($alipay_config);
		$verify_result = $alipayNotify->verifyNotify( $post );
		return $verify_result;
	}
	
	/**
	 * 签约查询接口
	 * @param string $alipay_user_id	用户支付宝标识
	 * @return mixed false：查询失败；Y：已签约；N：未签约
	 */
	public function query(  $alipay_user_id ){
		
		require __DIR__.'/withholding/alipay.config.php';
		// 签约异步通知
		$notify_url = config('ALIPAY_WITHHOLDING_NOTIFY');

		/************************************************************/

		// 代扣签约页面接口参数
		$parameter = array(
			// 基本参数
			"service" => "alipay.dut.customer.agreement.query",
			"partner" => trim($alipay_config['partner']),
			"_input_charset" => trim(strtolower($alipay_config['input_charset'])),
			"sign_type" => trim($alipay_config['sign_type']),

			// 业务参数
			"product_code"	=> 'GENERAL_WITHHOLDING_P',// 协议产品码

			//alipay_user_id 和 alipay_logon_id不能同时为空，若两个都填写，取alipay_user_id的值
			"alipay_user_id"	=> $alipay_user_id,
			//"alipay_logon_id"	=> "xxxxxxxx@qq.com",
		);

		//建立请求
		$alipaySubmit = new \AlipaySubmit($alipay_config);
		$xmlstr = $alipaySubmit->buildRequestHttp($parameter);
		
		//计算得出返回xml验证结果
		$alipayNotify = new \AlipayNotify($alipay_config);
		//解析XML
		$resParameter = $alipayNotify->getRspFromXML($xmlstr);
		//判断是否成功
		if( isset($resParameter['error']) ){
			set_error('支付宝接口返回失败：'.$resParameter['error']);
			return false;
		}
		return $resParameter['status']=='NORMAL' ? 'Y' : 'N';
	}
	
	/**
	 * 解约
	 * @param string $agreement_no		协议码
	 * @param string $alipay_user_id	用户支付宝标识
	 */
	public function unsign( $agreement_no, $alipay_user_id ){
		
		require __DIR__.'/withholding/alipay.config.php';
		// 签约异步通知
		$notify_url = config('ALIPAY_WITHHOLDING_NOTIFY');

		/************************************************************/

		// 代扣签约页面接口参数
		$parameter = array(
			// 基本参数
			"service" => "alipay.dut.customer.agreement.unsign",
			"partner" => trim($alipay_config['partner']),
			"_input_charset" => trim(strtolower($alipay_config['input_charset'])),
			"sign_type" => trim($alipay_config['sign_type']),
			'notify_url' => $notify_url,

			// 业务参数
			'agreement_no' => $agreement_no,
			"product_code"	=> 'GENERAL_WITHHOLDING_P',// 协议产品码

			//alipay_user_id 和 alipay_logon_id不能同时为空，若两个都填写，取alipay_user_id的值
			"alipay_user_id"	=> $alipay_user_id,
			//"alipay_logon_id"	=> "xxxxxxxx@qq.com",
		);
			//scene 和 external_sign_no可为空，如果填了external_sign_no，scene不能为空
			//"scene"	=> $scene,
			//"external_sign_no"	=> $external_sign_no

		//var_dump( $parameter ) ;exit;

		//建立请求
		$alipaySubmit = new \AlipaySubmit($alipay_config);
		$xmlstr = $alipaySubmit->buildRequestHttp($parameter);
		/* 测试数据
		$xmlstr = '<?xml version="1.0" encoding="utf-8"?>
<alipay><is_success>T</is_success><request><param name="sign">7c77f0db058e7294ab17d58ffaa009cc</param><param name="_input_charset">utf-8</param><param name="agreement_no">20180116451892748770</param><param name="product_code">GENERAL_WITHHOLDING_P</param><param name="sign_type">MD5</param><param name="notify_url">https://dev-admin-zuji.huishoubao.com/alipay/withholding_notify_url.php</param><param name="service">alipay.dut.customer.agreement.unsign</param><param name="alipay_user_id">2088502596805705</param><param name="partner">2088821542502025</param></request><response><result/></response></alipay>';
		 */
		//计算得出返回xml验证结果
		$alipayNotify = new \AlipayNotify($alipay_config);
		// 支付宝返回的接口中，只有<request>中有数据，<response>中没有值，没有办法校验
		// 所以业务处理需要依赖 解约异步通知 进行处理
		//解析XML
		$resParameter = $alipayNotify->getRspFromXML($xmlstr);
		//判断是否成功
		if($resParameter['is_success'] == "T" ){
			return true;
		}
		set_error('支付宝接口返回失败：');
		return false;
	}
	
	/**
	 * 扣款
	 */
	public function createPay(string $agreement_no,string $trade_no,string $subject, $amount){
		
		require __DIR__.'/withholding/alipay.config.php';
		// 签约异步通知
		$notify_url = config('ALIPAY_WITHHOLDING_CREATEPAY_NOTIFY');

		//构造要请求的参数数组，无需改动
		$parameter = array(
			"service" => "alipay.acquire.createandpay",
			"partner" => trim($alipay_config['partner']),
			"_input_charset" => trim(strtolower($alipay_config['input_charset'])),
			"product_code"	=> 'GENERAL_WITHHOLDING',
			"notify_url"	=> $notify_url,
			"out_trade_no"	=> $trade_no,
			"subject"	=> $subject,
			"total_fee"	=> $amount,
			"agreement_info"	=> "{\"agreement_no\":\"" .$agreement_no . "\"}",
		);

		//建立请求
		$alipaySubmit = new \AlipaySubmit($alipay_config);
		$xmlstr = $alipaySubmit->buildRequestHttp($parameter);

		//计算得出返回xml验证结果
		$alipayNotify = new \AlipayNotify($alipay_config);
		// 支付宝返回的接口中，只有<request>中有数据，<response>中没有值，没有办法校验
		// 所以业务处理需要依赖 解约异步通知 进行处理
		//解析XML
		$resParameter = $alipayNotify->getRspFromXML($xmlstr);
		//var_dump( $resParameter );

		//判断是否成功
		if($resParameter['is_success'] != "T" ){
			set_error('支付宝接口返回失败');
			return false;
		}

		//判断是否支付成功
		if( $resParameter['result_code'] == 'ORDER_SUCCESS_PAY_SUCCESS' ){
			return true;
		}
        //判断是否支付成功
        if( isset($resParameter['detail_error_code']) && $resParameter['detail_error_code']){
		    if($resParameter['detail_error_code']=="BUYER_BALANCE_NOT_ENOUGH"){
                set_error("BUYER_BALANCE_NOT_ENOUGH");
            }else if($resParameter['detail_error_code']=="BUYER_BANKCARD_BALANCE_NOT_ENOUGH"){
				set_error("BUYER_BANKCARD_BALANCE_NOT_ENOUGH");
			}else{
                set_error($resParameter['detail_error_des']);
            }

            return false;
        }
		
		set_error('支付失败:['.$resParameter['detail_error_code'].']'.$resParameter['display_message']);
		return false;
	}
	
}

?>