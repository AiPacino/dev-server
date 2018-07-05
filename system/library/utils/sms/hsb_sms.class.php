<?php
/**
 * 回收宝平台 短息接口
 * 
 * @author liuhongxing <liuhongxing@huishoubao.com>
 */
abstract class hsb_sms implements sms {
    
    protected $mobile = '15311371612';
    
    /**
     * 构造函数
     * @params	    string  $mobile	【必须】手机号，多个以','分割
     * @access public 
     * @author liuhongxing <liuhongxing@huishoubao.com.cn>
     */
    public function __construct($mobile='') {
	$this->mobile = $mobile;
    }
    
    /**
     * 设置手机号
     * @params	    string  $mobile	【必须】手机号，多个以','分割
     * @access public 
     * @author liuhongxing <liuhongxing@huishoubao.com.cn>
     */
    protected function set_mobiles( $mobile ){
	$this->mobile = $mobile;
    }
    
    /**
     * 获取短信模板ID
     * @return	    string  短信模板ID
     * @access public 
     * @author liuhongxing <liuhongxing@huishoubao.com.cn>
     */
    abstract protected function _get_template_code();
    /**
     * 获取短信模板参数
     * @return array	短信模板参数（非空数组时，必须为关联数组）
     * @access public 
     * @author liuhongxing <liuhongxing@huishoubao.com.cn>
     */
    abstract protected function _get_template_params();
    
    /**
     * 发送短信
     * @param array $data
     * @return mixed	false：失败；string：成功，短信业务流水号
     */
    private function send(  ){
	# 校验手机号
	if( $this->mobile==null || is_mobile($this->mobile)){
	    return false;
	}
	// 校验 短信模板ID
	$template_code = $this->_get_template_code();
	if( empty($template_code) ){
	    return false;
	}
	// 校验 短息模板参数
	$template_params = $this->_get_template_params();
	if( empty($template_params) ){
	    
	}
	$time = time();		// 当前时间
	$service_id = '110001';	// 服务ID（短信平台分配）
	$key = '2a04714403784e17a8c2e5fa3f7bb15f';  // key，用于加密（短信平台分配）
	$url = 'http://dev-psl-server.huanjixia.com/sms-interface'; // 接口url
	$data = [
	    '_head'  => [
		'_version'         => '0.01',	    // 接口版本
		'_msgType'         => 'request',    // 类型
		'_interface'       => 'smsSubmit',  // 接口名称
		'_remark'          => '123',	    // 备注
		'_invokeId'        => zuji_business::create_business_no('SM'), // 流水号
		'_callerServiceId' => $service_id,  // 
		'_groupNo'         => '1',	    // 服务组ID，固定值1
		'_timestamps'      => $time,	    // 当前时间戳
	    ],
	    '_param' => [
		'smsSign'       => '1',		    // 
		'phones'        => $this->mobile,   // 手机号
		'templateCode'  => $this->_get_template_code(), // 短息模板ID
		'templateParam' => $this->_get_template_params(),
	    ],
	];
	$json = json_encode($data);		// 参数序列化
	$signature = md5($json . '_' . $key);	// 参数签名

	# 模拟 HTTP POST 请求
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_POST, true);		    // 请求方式
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);   //
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);  // 不校验证书
	curl_setopt($curl, CURLOPT_TIMEOUT, 10);	    // 超时时长
	curl_setopt($curl, CURLOPT_URL, $url);		    // 请求地址
	curl_setopt($curl, CURLOPT_POSTFIELDS, $json);	    // 业务参数
	curl_setopt($curl, CURLOPT_HTTPHEADER, [	    // header 头
	    'HSB-OPENAPI-SIGNATURE:' . $signature,	    // 签名字符串
	    'HSB-OPENAPI-CALLERSERVICEID:' . $service_id	    // 服务ID
	]);
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);   // 支持重定向
	// 执行请求
	$response = curl_exec($curl);
	// curl请求失败
	if ($response === false) {
	    $error = curl_error($curl);
	    curl_close($curl);
	    echo $error; exit;
	}

	// json解析失败
	$response_arr = json_decode($response, true);

	if (empty($response_arr)) {
	    $error = "返回格式非json";
	    return FALSE;
	}
	return $response_arr;
    }
    
    
}
