<?php
namespace alipay;
/*
 * 支付宝 退款 接口
 */


require_once __DIR__ . '/aop/request/AlipayTradeRefundRequest.php';
/**
 * 退款
 *
 * @author
 */
class Refund extends BaseApi {

	public function __construct($appid) {
		parent::__construct($appid);
	}

	/**
	 * 退款
	 */
	public function refund( $params ) {
		$params = filter_array($params, [
			'request_no' => 'required',		    // 【必须】租机请求序列号
			'trade_no' => 'required',		    // 【必须】租机交易号
			'out_trade_no' => 'required',	    // 【必须】支付宝交易码
			'refund_amount' => 'required|is_price', // 【必须】退款金额，元
			'refund_reason' => 'required',	    // 【可选】退款原因
		]);
		set_default_value($params['refund_reason'], '');

		if( count($params)!=5 ){
			set_error('退款失败，业务参数错误');
			return false;
		}


		//支付宝交易号，和商户订单号二选一
		$biz_content['trade_no'] = $params['out_trade_no'];	// 支付宝交易号
		$biz_content['out_trade_no'] = $params['trade_no'];	// 商户交易号
		$biz_content['refund_amount'] = $params['refund_amount'];  // 退款金额
		$biz_content['refund_reason'] = $params['refund_reason'];  // 退款原因
		$biz_content['out_request_no'] = $params['request_no'];	    // 请求序列号，标识一次退款请求，同一笔交易多次退款需要保证唯一，如需部分退款，则此参数必传。


		$request = new \AlipayTradeRefundRequest();
		$request->setBizContent (json_encode($biz_content) );
//	// 模拟请求结果
//	$result = [
//	    'alipay.trade.refund' => [
//		'code' => '10000',
//		'msg' => 'Success',
//		'buyer_logon_id' => 'hai***@163.com',
//		'buyer_user_id' => '2088302825506960',
//		'fund_change' => 'Y',
//		'gmt_refund_pay' => '2017-11-29 15:54:26',
//		'out_trade_no' => '2017112900034',
//		'refund_fee' => '0.01',
//		'send_back_fee' => '0.00',
//		'trade_no' => '2017112921001004960226218506',
//	    ],
//	];
		$response = $this->execute($request);
		$result = json_decode(json_encode($response),true);
		//var_dump( $result );
		$debug_data = [
			'request' => $biz_content,
			'response' => $response,
		];
		if(!isset($result['alipay_trade_refund_response'])){
			set_error('退款接口请求失败');
			\zuji\debug\Debug::error(\zuji\debug\Location::L_Refund, '退款接口，返回值错误', $debug_data);
			return false;
		}
		if( $result['alipay_trade_refund_response']['code']!=10000 ){
			$msg = $result['alipay_trade_refund_response']['sub_msg'];
			set_error($msg);
			\zuji\debug\Debug::error(\zuji\debug\Location::L_Refund, '退款接口：'.$msg, $debug_data);
			return false;
		}
		if( $result['alipay_trade_refund_response']['fund_change'] != 'Y' ){
			set_error('资金未发生变化');
			\zuji\debug\Debug::error(\zuji\debug\Location::L_Refund, '退款接口，资金未发生变化', $debug_data);
			return false;
		}
		return true;
	}

}
