<?php

/**
 * 支付宝--资金预授权
 *
 */

namespace alipay\fund;

include_once __DIR__ . '/function.php';

/**
 * 支付宝--资金预授权 接口
 *
 */
class FundAuth extends \zuji\payment\FundAuth {

	/**
	 * HTTPS形式消息验证地址
	 */
	private $https_verify_url = 'https://mapi.alipay.com/gateway.do?service=notify_verify&';

	/**
	 * HTTP形式消息验证地址
	 */
	private $http_verify_url = 'http://notify.alipay.com/trade/notify_query.do?';

	/**
	 * @var sting  资金授权平台标识
	 */
	const Platform = 'ALIPAY';

	private $alipay_config = null;

	public function __construct() {

		// 引入配置文件
		include __DIR__ . '/config.php';
		$this->alipay_config = $alipay_config;
	}

	/**
	 * 获取 业务场景码
	 * 商户签约时由支付宝统一分配
	 * @return string	
	 */
	public static function getSceneCode() {
		return 'PRESALE';  // 终端服务
	}

	/**
	 * 默认 支付模式
	 * @return string	
	 */
	public static function getDefultPayMode() {
		return 'WIRELESS';
	}

	/**
	 * 校验 支付模式
	 * 取值范围： WIRELESS：需要在无线端完成支付；PC：支持在电脑上完成支付
	 * @param string	$model
	 * @return boolean
	 */
	public static function verifyPayMode($model) {
		return in_array($model, ['WIRELESS', 'PC']);
	}

	/**
	 * 生成签名
	 * @param type $params
	 * @return string	签名字符串
	 */
	public function sign($params) {

		//除去待签名参数数组中的空值和签名参数
		$para_filter = paraFilter($params);

		//对待签名参数数组排序
		$para_sort = argSort($para_filter);

		//把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
		$prestr = createLinkstring($para_sort);

		$mysign = "";
		switch ($this->alipay_config['sign_type']) {
			case "MD5" :
				$mysign = md5Sign($prestr, $this->alipay_config['key']);
				break;
			default :
				$mysign = "";
		}
		return $mysign;
	}

	/**
	 * 获取冻结请求的url地址
	 * @param array $data   (参考 freese() 方法)
	 * @return string	url地址
	 */
	public function freeseUrl($data) {
		$params = $this->freese($data);
		$url = $this->alipay_config['alipay_gateway'] . '?_input_charset=' . $this->alipay_config['input_charset'];
		while (list ($key, $val) = each($params)) {
			$url .= '&' . $key . '=' . $val;
		}
		return $url;
	}

	/**
	 * 获取冻结请求的 form表单
	 * @param array $data   (参考 freese() 方法)
	 * @return string	url地址
	 */
	public function freeseForm($data) {
		$params = $this->freese($data);
		// 生成 form表单
		$sHtml = "<form id='alipaysubmit' name='alipaysubmit' action='" . $this->alipay_config['alipay_gateway'] . "?_input_charset=" . $this->alipay_config['input_charset'] . "' method='GET'>";
		while (list ($key, $val) = each($params)) {
			$sHtml .= "<input type='hidden' name='" . $key . "' value='" . $val . "'/>";
		}
		//submit按钮控件请不要含有name属性
		$sHtml = $sHtml . "<input type='submit'  value='确认' style='display:none;'></form>";
		$sHtml = $sHtml . "<script>document.forms['alipaysubmit'].submit();</script>";

		return $sHtml;
	}

	/**
	 * 内部方法
	 * @param array $data	    【必须】
	 * [
	 * 	    'notify_url' => '',	    //【必须】string；服务器异步通知页面路径,不能加?id=123这类自定义参数
	 * 	    'return_url' => '',	    //【必须】string；页面跳转同步通知页面路径，不能加?id=123这类自定义参数，不能写成http://localhost/
	 * 	    'out_order_no' => '',   //【必须】string；商户授权资金订单号；同一商户不同的订单，商户授权资金订单号不能重复
	 * 	    'out_request_no' => '', //【必须】string；商户本次资金操作的请求流水号；同一商户每次不同的资金操作请求，商户请求流水号不能重复
	 * 	    'order_title' => '',    //【必须】string；业务订单的简单描述，如商品名称等
	 * 	    'amount' => '',	    //【必须】price；本次操作冻结的金额，单位为：元（人民币）；取值范围：[0.01,100000000.00]
	 * 	    'pay_mode' => '',	    //【可选】stirng；支付模式；取值范围： WIRELESS：需要在无线端完成支付；PC：支持在电脑上完成支付
	 * ]
	 * @return string
	 */
	private function freese($data) {

		$data = filter_array($data, [
			'notify_url' => 'required|is_url',
			'return_url' => 'required|is_url',
			'out_order_no' => 'required',
			'out_request_no' => 'required',
			'order_title' => 'required',
			'amount' => 'required|is_price',
			'pay_mode' => 'required|alipay\fund\FundAuth::verifyPayMode',
		]);
		if (!isset($data['notify_url'])) {
			set_error('notify_url');
			return false;
		}
		if (!isset($data['return_url'])) {
			set_error('return_url');
			return false;
		}
		if (!isset($data['out_order_no'])) {
			set_error('out_order_no');
			return false;
		}
		if (!isset($data['out_request_no'])) {
			set_error('out_request_no');
			return false;
		}
		if (!isset($data['order_title'])) {
			set_error('order_title');
			return false;
		}
		if (!isset($data['amount'])) {
			set_error('amount');
			return false;
		}
		// 这是默认 支付模式
		if (!isset($data['pay_mode'])) {
			$data['pay_mode'] = self::getDefultPayMode();
		}

		//构造要请求的参数数组，无需改动
		$params = array(
			'service' => 'alipay.fund.auth.create.freeze.apply',
			'payee_logon_id' => $this->alipay_config['payee_logon_id'],
			'payee_user_id' => $this->alipay_config['payee_user_id'],
			'partner' => $this->alipay_config['partner'],
			'notify_url' => $data['notify_url'], //
			'return_url' => $data['return_url'], //
			'out_order_no' => $data['out_order_no'], // 
			'out_request_no' => $data['out_request_no'], //
			'order_title' => $data['order_title'], //
			'amount' => $data['amount'], // 
			'pay_mode' => $data['pay_mode'], // 支付模式
			'product_code' => 'FUND_PRE_AUTH', // 业务产品码；固定值：FUND_PRE_AUTH
			'scene_code' => self::getSceneCode(), // 业务场景码；商户签约时由支付宝统一分配
			'_input_charset' => $this->alipay_config['input_charset'],
		);

		//签名结果与签名方式加入请求提交参数组中
		$params['sign'] = $this->sign($params);
		$params['sign_type'] = $this->alipay_config['sign_type'];
		return $params;
	}

	/**
	 * 获取返回时的签名验证结果
	 * @param $para_temp 通知返回来的参数数组
	 * @param $sign 返回的签名结果
	 * @return 签名验证结果
	 */
	public function signVerify($para_temp, $sign) {
		//除去待签名参数数组中的空值和签名参数
		$para_filter = paraFilter($para_temp);

		//对待签名参数数组排序
		$para_sort = argSort($para_filter);

		//把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
		$prestr = createLinkstring($para_sort);

		$isSign = false;
		switch (strtoupper(trim($this->alipay_config['sign_type']))) {
			case "MD5" :
				$isSign = md5Verify($prestr, $sign, $this->alipay_config['key']);
				break;
			default :
				$isSign = false;
		}
		//获取支付宝远程服务器ATN结果（验证是否是支付宝发来的消息）
		$responseTxt = 'false';
		if (!empty($para_temp["notify_id"])) {
			$responseTxt = $this->getResponse($para_temp["notify_id"]);
		}

		//写日志记录
		if ($isSign) {
			$isSignStr = 'true';
		} else {
			$isSignStr = 'false';
		}
		return $isSignStr;
		$log_text = "responseTxt=" . $responseTxt . "\n notify_url_log:isSign=" . $isSignStr . ",";
		//$log_text = $log_text.createLinkString($_POST);
		logResult($log_text);

		//验证
		//$responsetTxt的结果不是true，与服务器设置问题、合作身份者ID、notify_id一分钟失效有关
		//isSign的结果不是true，与安全校验码、请求时的参数格式（如：带自定义参数等）、编码格式有关

		if (preg_match("/true$/i", $responseTxt) && $isSign) {
			return true;
		}
		return false;
	}

	/**
	 * 解冻
	 * @param type $params
	 * [
	 * 	    'auth_no' => '',
	 * 	    'out_request_no' => '',
	 * 	    'amount' => '',
	 * 	    'remark' => '',
	 * 	    'notify_url' => '',
	 * ]
	 */
	public function unfreeze($data) {

		$data = filter_array($data, [
			'auth_no' => 'required',
			'out_request_no' => 'required',
			'amount' => 'required|is_price',
			'remark' => 'required',
			'notify_url' => 'required',
		]);
		if (!isset($data['notify_url'])) {
			set_error('notify_url 参数错误');
			return false;
		}
		if (!isset($data['out_request_no'])) {
			set_error('out_request_no 参数错误');
			return false;
		}
		if (!isset($data['amount'])) {
			set_error('amount 参数错误');
			return false;
		}

		//构造要请求的参数数组，无需改动
		$params = array(
			'service' => 'alipay.fund.auth.unfreeze',
			'partner' => $this->alipay_config['partner'],
			'notify_url' => $data['notify_url'], //
			'auth_no' => $data['auth_no'], // 
			'out_request_no' => $data['out_request_no'], //
			'amount' => $data['amount'], // 
			'remark' => $data['remark'], // 
			'_input_charset' => $this->alipay_config['input_charset'],
		);

		//签名结果与签名方式加入请求提交参数组中
		$params['sign'] = $this->sign($params);
		$params['sign_type'] = $this->alipay_config['sign_type'];
		// var_dump($params);die;

//	// 生成 form表单
//	$sHtml = "<form id='alipaysubmit' name='alipaysubmit' action='".$this->alipay_config['alipay_gateway']."?_input_charset=".$this->alipay_config['input_charset']."' method='GET'>";
//	while (list ($key, $val) = each ($params)) {
//            $sHtml.= "<input type='hidden' name='".$key."' value='".$val."'/>";
//        }
//	//submit按钮控件请不要含有name属性
//        $sHtml = $sHtml."<input type='submit'  value='确认' style='display:none;'></form>";
//	$sHtml = $sHtml."<script>document.forms['alipaysubmit'].submit();</script>";
//
//	return $sHtml;

		$url = $this->alipay_config['alipay_gateway'] . '?_input_charset=' . $this->alipay_config['input_charset'];
		while (list ($key, $val) = each($params)) {
			$url .= '&' . $key . '=' . urlencode($val);
		}
		$result = getHttpResponseGET($url, $this->alipay_config['cacert']);
		//file_put_contents('./data/fundauth-unfreeze.log',"\n".$result,FILE_APPEND);
		if (!$result) {
			set_error('支付宝接口请求失败');
			return false;
		}

		//解析XML
		$doc = new \DOMDocument();
		$doc->loadXML($result);
		if ($doc->getElementsByTagName("result_code")->item(0)->nodeValue == 'SUCCESS') {
			$code = $doc->getElementsByTagName("result_code")->item(0)->nodeValue;
			$msg = $doc->getElementsByTagName("result_message")->item(0)->nodeValue;
			return true;
		}
		if ($doc->getElementsByTagName("result_code")->item(0)->nodeValue) {
			$code = $doc->getElementsByTagName("result_code")->item(0)->nodeValue;
			$msg = $doc->getElementsByTagName("result_message")->item(0)->nodeValue;
			//echo '错误('.$code.')：'.$msg;exit;
			set_error('错误(' . $code . ')：' . $msg);
			return false;
		}
		return false;
	}

	/**
	 * 解冻转支付
	 * @param type $data
	 */
	public function unfreeze_and_pay($data) {
		if( !isset($data['notify_url']) || strlen($data['notify_url'])==0 ){
            set_error('notify_url 参数cuo');
			return false;
		}
		//构造要请求的参数数组，无需改动
		$params = array(
			'service' => 'alipay.acquire.createandpay', // 统一下单并支付接口(alipay.acquire.createandpay)将预授权订单转支付
			'product_code' => 'FUND_TRADE_FAST_PAY', // 业务类型：解冻转支付，固定值：FUND_TRADE_FAST_PAY
			'partner' => $this->alipay_config['partner'],
			// 若预授权冻结时传入payee_logon_id（收款方支付宝账号）或者payee_user_id（收款方支付宝用户号），则转支付请求参数seller_id（卖家支付宝用户号）或seller_email（卖家支付宝账号）必须与冻结传入的一致
			'seller_email' => $this->alipay_config['payee_logon_id'], // 商家支付宝账号
			'subject' => $data['subject'], // 标题
			'notify_url' => $data['notify_url'], // 异步通知地址
			'auth_no' => $data['auth_no'], // 授权编号
			// buyer_id（买家支付宝用户号）不可为空，为授权用户的支付宝账号uid
			'buyer_id' => $data['payer_logon_id'], // 用户支付宝用户ID
			'seller_id' => $data['payee_user_id'], // 商家支付宝用户ID
			'total_fee' => $data['amount'], // 支付金额
			// 记录out_trade_no（商户订单号）及trade_no（支付宝交易号），用于后续查单或者退款
			'out_trade_no' => $data['out_trade_no'],
			'_input_charset' => $this->alipay_config['input_charset'],
		);
		//var_dump( $params );exit;
		//签名结果与签名方式加入请求提交参数组中
		$params['sign'] = $this->sign($params);
		$params['sign_type'] = $this->alipay_config['sign_type'];
		
		$url = $this->alipay_config['alipay_gateway'] . '?_input_charset=' . $this->alipay_config['input_charset'];
		while (list ($key, $val) = each($params)) {
			$url .= '&' . $key . '=' . urlencode($val);
		}

        $result= getHttpResponseGET($url, $this->alipay_config['cacert']);
		
		//file_put_contents('./data/temp-'.date('Y-m-d').'.log',"\n".  $result."\n",FILE_APPEND);

        //解析XML
        $doc = new \DOMDocument();
        $doc->loadXML($result);
        $code = $doc->getElementsByTagName("result_code")->item(0)->nodeValue;
        $msg = $doc->getElementsByTagName("result_message")->item(0)->nodeValue;
        if($code =='ORDER_SUCCESS_PAY_SUCCESS'){
            return true;
        }else{
            set_error('错误(' . $code . ')：' . $msg);
            return false;
        }
//        if ($doc->getElementsByTagName("result_code")->item(0)->nodeValue == 'ORDER_SUCCESS_PAY_SUCCESS') {
//            $code = $doc->getElementsByTagName("result_code")->item(0)->nodeValue;
//            $msg = $doc->getElementsByTagName("result_message")->item(0)->nodeValue;
//            return true;
//        }else{
//            $code = $doc->getElementsByTagName("result_code")->item(0)->nodeValue;
//            $msg = $doc->getElementsByTagName("result_message")->item(0)->nodeValue;
//            set_error('错误(' . $code . ')：' . $msg);
//            return false;
//        }
        return false;


	}

	/**
	 * 授权查询接口
	 * @param array $data
	 * [
	 *		'auth_no' => '',// 支付宝授权码
	 * ]
	 * @return mixed  false：查询失败； array 查询成功
	 * [
	 *		'auth_no' => '',
	 *		'order_status' => '',
	 *		'total_freeze_amount' => '',
	 *		'total_unfreeze_amount' => '',
	 *		'total_pay_amount' => '',
	 * ]
	 */
	public function query_auth($data) {
		
		if( !isset($data['auth_no']) || strlen($data['auth_no'])==0 ){
			set_error('auth_no 参数错误');
			return false;
		}

		//构造要请求的参数数组，无需改动
		$params = array(
			'service' => 'alipay.fund.auth.query', // 
			'partner' => $this->alipay_config['partner'],
			'_input_charset' => $this->alipay_config['input_charset'],
			// 支付宝授权码
			'auth_no' => $data['auth_no'],
		);
		//签名结果与签名方式加入请求提交参数组中
		$params['sign'] = $this->sign($params);
		$params['sign_type'] = $this->alipay_config['sign_type'];

		$url = $this->alipay_config['alipay_gateway'] . '?_input_charset=' . $this->alipay_config['input_charset'];
		while (list ($key, $val) = each($params)) {
			$url .= '&' . $key . '=' . urlencode($val);
		}
		// 发送请求
        $result= getHttpResponseGET($url, $this->alipay_config['cacert']);
		
        //解析XML
        $doc = new \DOMDocument();
        $doc->loadXML($result);
        $is_success = $doc->getElementsByTagName("is_success")->item(0)->nodeValue;
		if( $is_success === 'T' ){ // 请求成功
			// 状态码
			$result_code = $doc->getElementsByTagName("result_code")->item(0)->nodeValue;
			// 业务处理成功
			if( $result_code === 'SUCCESS' ){//
				// 当前授权状态
				$order_status = $doc->getElementsByTagName("order_status")->item(0)->nodeValue;
				// 业务处理成功时订单的状态
				$status = ['INIT','AUTHORIZED','FINISH','CLOSED'];
				if(in_array($order_status, $status) ){
					$total_freeze_amount = $doc->getElementsByTagName("total_freeze_amount")->item(0)->nodeValue;
					$total_unfreeze_amount = $doc->getElementsByTagName("total_unfreeze_amount")->item(0)->nodeValue;
					$total_pay_amount = $doc->getElementsByTagName("total_pay_amount")->item(0)->nodeValue;
					return [
						'auth_no' => $data['auth_no'],
						'order_status' => $order_status,
						'total_freeze_amount' => $total_freeze_amount,// 累计冻结
						'total_unfreeze_amount' => $total_unfreeze_amount,// 累计解冻
						'total_pay_amount' => $total_pay_amount	// 累计支付
					];
				}
				set_error('支付宝订单状态值不合法');
				return false;
			}
			$error_code = $doc->getElementsByTagName("detail_error_code")->item(0)->nodeValue;
			$error_des = $doc->getElementsByTagName("detail_error_des")->item(0)->nodeValue;
			set_error('支付宝业务处理失败：'.$error_des.'('.$error_code.')');
			return false;
		}
		// is_success 不为 T，表示支付宝没有正确受理请求，直接返回false
		// 业务处理不成功 
		$msg = $doc->getElementsByTagName("error")->item(0)->nodeValue;
		set_error('支付宝请求处理失败：'.$msg);
		return false;
		
	}
	
	/**
	 * 收单查询接口
	 * @param array $data
	 * [
	 *		'out_trade_no' => '',// 租机交易码
	 *		'trade_no' => '',	// 支付宝交易码
	 * ]
	 */
	public function query_createpay($data) {

		//构造要请求的参数数组，无需改动
		$params = array(
			'service' => 'alipay.acquire.query', // 统一下单并支付接口(alipay.acquire.createandpay)将预授权订单转支付
			'partner' => $this->alipay_config['partner'],
			'_input_charset' => $this->alipay_config['input_charset'],
			// out_trade_no（商户订单号）优先级高于 trade_no
			'out_trade_no' => $data['out_trade_no'],
			// trade_no（支付宝交易号）
			'trade_no' => $data['trade_no'],
		);
		//签名结果与签名方式加入请求提交参数组中
		$params['sign'] = $this->sign($params);
		$params['sign_type'] = $this->alipay_config['sign_type'];

		$url = $this->alipay_config['alipay_gateway'] . '?_input_charset=' . $this->alipay_config['input_charset'];
		while (list ($key, $val) = each($params)) {
			$url .= '&' . $key . '=' . urlencode($val);
		}
		// 发送请求
        $result= getHttpResponseGET($url, $this->alipay_config['cacert']);
		
        //解析XML
        $doc = new \DOMDocument();
        $doc->loadXML($result);
        $is_success = $doc->getElementsByTagName("is_success")->item(0)->nodeValue;
		if( $is_success === 'T' ){ // 请求成功
			// 状态码
			$result_code = $doc->getElementsByTagName("result_code")->item(0)->nodeValue;
			// 业务处理成功
			if( $result_code === 'SUCCESS' ){//
				$trade_status = $doc->getElementsByTagName("trade_status")->item(0)->nodeValue;
				// 业务处理成功时订单的状态
				$status = ['WAIT_BUYER_PAY','TRADE_CLOSED','TRADE_SUCCESS','TRADE_PENDING','TRADE_FINISHED'];
				if(in_array($trade_status, $status) ){
					return [
						'out_trade_no' => $params['out_trade_no'],
						'trade_no' => $params['trade_no'],
						'status' => $trade_status,
					];
				}
				set_error('支付宝订单状态值不合法');
				return false;
			}
			$error_code = $doc->getElementsByTagName("detail_error_code")->item(0)->nodeValue;
			$error_des = $doc->getElementsByTagName("detail_error_des")->item(0)->nodeValue;
			set_error('支付宝业务处理失败：'.$error_des.'('.$error_code.')');
			return false;
		}
		// is_success 不为 T，表示支付宝没有正确受理请求，直接返回false
		// 业务处理不成功 
		$msg = $doc->getElementsByTagName("error")->item(0)->nodeValue;
		set_error('支付宝请求处理失败：'.$msg);
		return false;
		
	}
	/**
	 * 获取远程服务器ATN结果,验证返回URL
	 * @param $notify_id 通知校验ID
	 * @return 服务器ATN结果
	 * 验证结果集：
	 * invalid命令参数不对 出现这个错误，请检测返回处理中partner和key是否为空 
	 * true 返回正确信息
	 * false 请检查防火墙或者是服务器阻止端口问题以及验证时间是否超过一分钟
	 */
	private function getResponse($notify_id) {
		$transport = strtolower(trim($this->alipay_config['transport']));
		$partner = trim($this->alipay_config['partner']);
		$veryfy_url = '';
		if ($transport == 'https') {
			$veryfy_url = $this->https_verify_url;
		} else {
			$veryfy_url = $this->http_verify_url;
		}
		$veryfy_url = $veryfy_url . "partner=" . $partner . "&notify_id=" . $notify_id;
		$responseTxt = getHttpResponseGET($veryfy_url, $this->alipay_config['cacert']);

		return $responseTxt;
	}

}
