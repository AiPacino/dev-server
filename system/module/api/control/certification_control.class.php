<?php

hd_core::load_class('api', 'api');
/**
 * 认证
 * @access public 
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 * @copyright (c) 2017, Huishoubao
 */
hd_core::load_class('user', 'api');
use zuji\debug\Debug;
use zuji\debug\Location;

class certification_control extends user_control {

	public function _initialize() {
		parent::_initialize();
		$this->certification_alipay = $this->load->service('member2/certification_alipay');
	}

	/**
	 * 初始化
	 * 【注意：】必须通过接口形式访问
	 */
	public function initialize() {
		$params = $this->params;
		$params = filter_array($params, [
			'return_url' => 'required|is_url',
			'cert_channel' => 'required',
		]);
		if (count($params) != 2) {
			api_resopnse([], ApiStatus::CODE_20001, '参数错误', ApiSubCode::Certivication_Param_Error, 'return_url,cert_channel必须');
			return;
		}

		// 没有登录
		if (!$this->member) {
			api_resopnse([], ApiStatus::CODE_40001, '请登录');
			return;
		}
		$callBack = urlencode($params['return_url']);
		$url = 'https://zmhatcher.zmxy.com.cn/creditlife/operatorEntrance.htm?productId=2017120101000222123430780086&channel=creditlife&callBackUrl=' . $callBack;
		api_resopnse(['certification_url' => $url]);
		return;
	}

	/**
	 * 芝麻认证结果查询（一次性操作，）
	 * 【注意：】必须接口形式访问
	 */
	public function get() {
		//----------------------------------------------------------------------
		$params = $this->params;
		$params = filter_array($params, [
			'cert_channel' => 'required',
			'order_no' => 'required', // 是一个28位的字符串，例如：2017103100001001036008590096
		]);
		if (count($params) != 2) {
			api_resopnse([], ApiStatus::CODE_50005, '认证错误', ApiSubCode::Certivication_Param_Error, '参数错误');
			return;
		}
		$order_no = $params['order_no'];

		// 查询用户认证结果
		$zhima = new \zuji\certification\Zhima();
		$zhima_order_info = $zhima->getOrderInfo($order_no, $this->member['id']);
		
		//蚁盾接口调用风险验证(暂时不处理蚁盾结果)
		//2018-01-23 16:54 【蚁盾调取改为订单确认页获取，并进行押金验证；当前方法暂时弃用】
//		$yidun = new \zuji\yidun\Yidun($this->member['id']);
//		$yidun_result = $yidun->get_result();

		
		// 初始化认证标识
		$_SESSION['_cert_init_'] = false;
		if( $zhima_order_info ){
			$_SESSION['_cert_init_'] = true;
		}
		$cert_info = [
			'_id' => $zhima_order_info['id'],
			'cert_order_no' => $order_no, // 芝麻认证订单编号
			'cert_channel_name' => '芝麻认证', // 认证平台名称
			'cert_name' => $zhima_order_info['name'], // 真实姓名
			'cert_type' => 'IDENTITY_CARD', // 认证类型，固定值：身份证
			'cert_no' => $zhima_order_info['cert_no'], // 身份证号
			'credit' => $zhima_order_info['zm_score'], // 信用分
			'face' => $zhima_order_info['zm_face'], //人脸识别
		];
		api_resopnse(['cert_info' => $cert_info], ApiStatus::CODE_0, '认证成功');
		return;
		
	}

}
