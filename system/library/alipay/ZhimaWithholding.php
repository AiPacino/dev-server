<?php
namespace alipay;
/*
 * 芝麻扣款 取消订单 关闭订单 接口
 */


require_once __DIR__ . '/aop/request/ZhimaMerchantOrderCreditPayRequest.php';
/**
 * 芝麻扣款 取消订单 关闭订单 发送请求
 *
 * @author
 */
class ZhimaWithholding extends BaseApi {

	public function __construct($appid) {
		parent::__construct($appid);
	}

	private $result;

	private $error;

	//請求返回值
	public function getResult(){
		return $this->result;
	}

	//錯誤返回值
	public function getError(){
		return $this->error;
	}

	/**
	 * 芝麻扣款 取消订单 关闭订单
	 */
	public function withholdingCancelClose( $params ) {
		$params = filter_array($params, [
			'order_operate_type' => 'required',		    // 【必须】请求类型
			'out_order_no' => 'required',		    // 【必须】租机商户订单号
			'zm_order_no' => 'required',		    // 【必须】芝麻交易号
			'out_trans_no' => 'required',	    // 【必须】支付宝交易码
			'pay_amount' => 'required|is_price', // 【必须】支付金额，元
			'remark' => 'required',	    // 【可选】取消原因
		]);
		//支付宝交易号，和商户订单号二选一
		$biz_content['order_operate_type'] = $params['order_operate_type'];	// 请求类型
		$biz_content['out_order_no'] = $params['out_order_no'];	// 商户交易号
		$biz_content['zm_order_no'] = $params['zm_order_no'];	// 芝麻交易号
		if(isset($params['out_trans_no'])){
			$biz_content['out_trans_no'] = $params['out_trans_no'];
		}
		if(isset($params['pay_amount'])){
			$biz_content['pay_amount'] = $params['pay_amount'];
		}
		if(isset($params['remark'])){
			$biz_content['remark'] = $params['remark'];	    // 请求序列号，标识一次退款请求，同一笔交易多次退款需要保证唯一，如需部分退款，则此参数必传。
		}
		$request = new \ZhimaMerchantOrderCreditPayRequest();
		$request->setBizContent (json_encode($biz_content) );
		$response = $this->execute($request);
		$result = json_decode(json_encode($response),true);
		$debug_data = [
			'request' => $biz_content,
			'response' => $response,
		];
		//\zuji\debug\Debug::error(\zuji\debug\Location::L_Order, '芝麻接口', $debug_data);
		if( !isset($result['zhima_merchant_order_credit_pay_response']) ){
			$this->error = '芝麻扣款 取消订单 关闭订单 接口，返回值错误';
			\zuji\debug\Debug::error(\zuji\debug\Location::L_Order, '芝麻接口，返回值错误', $debug_data);
			return false;
		}
		if( $result['zhima_merchant_order_credit_pay_response']['code']!=10000 ){
			$msg = $result['zhima_merchant_order_credit_pay_response']['sub_msg'];
			$this->error = $result['zhima_merchant_order_credit_pay_response']['sub_code'].$result['zhima_merchant_order_credit_pay_response']['sub_msg'];
			\zuji\debug\Debug::error(\zuji\debug\Location::L_Order, '芝麻接口：'.$msg, $debug_data);
			return false;
		}
		$this->result = $result;
		return true;
	}

}
