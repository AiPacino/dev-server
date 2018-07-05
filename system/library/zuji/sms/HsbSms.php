<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace zuji\sms;

/**
 * Description of HsbSms
 *
 * @author Administrator
 */
class HsbSms {
    
    private $service_id = '110001';
    private $key = '2a04714403784e17a8c2e5fa3f7bb15f';
    //private $tongzhi_url = 'http://dev-psl-server.huanjixia.com/sms-interface';
    private $tongzhi_url = 'http://push.huanjixia.com/sms-interface';
    
    //private $code_url = 'http://dev-psl-server.huanjixia.com/service/captcha';
    private $code_url = 'http://push.huanjixia.com/service/captcha';
    
    public function send_sm( $mobile, $templateCode, $templateParam,$order_no){

        $time = time();		// 当前时间
        $data = [
            '_head'  => [
                '_version'         => '0.01',	    // 接口版本
                '_msgType'         => 'request',    // 类型
                '_interface'       => 'smsSubmit',  // 接口名称
                '_remark'          => '',	      // 备注
                '_invokeId'        => \zuji\Business::create_business_no(), // 流水号
                '_callerServiceId' => $this->service_id,  // 
                '_groupNo'         => '1',	    // 服务组ID，固定值1
                '_timestamps'      => $time,	    // 当前时间戳
            ],
            '_param' => [
                'smsSign'       => '3',		    // 
                'phones'        => $mobile,	    // 手机号
                'templateCode'  => $templateCode,   // 短息模板ID
                'templateParam' =>$templateParam,
            ],
        ];
        $json = json_encode($data);		// 参数序列化
        $signature = md5($json . '_' . $this->key);	// 参数签名

        // curl
        $response = \zuji\Curl::post($this->tongzhi_url,$json, [	    // header 头
            'HSB-OPENAPI-SIGNATURE:' . $signature,	    // 签名字符串
            'HSB-OPENAPI-CALLERSERVICEID:' . $this->service_id	    // 服务ID
        ] );

        // curl请求失败
        if ( empty($response) ) {
	    \zuji\debug\Debug::error(\zuji\debug\Location::L_SMS, '短息接口请求失败', \zuji\Curl::getError());
	    set_error(\zuji\Curl::getError());
            return false;
        }
        
        // json解析
        $response_arr = json_decode($response, true);
	// debug输出
	$debug = ['request'=>  json_decode($json,true), 'response'=>json_decode($response,true)];
	if( empty($response_arr) || !$response_arr ){
	    \zuji\debug\Debug::error(\zuji\debug\Location::L_SMS, '短息接口协议错误', $debug);
	    set_error('短息接口协议错误');
	    return false;
	}
	if( $response_arr['_data']['ret']!=0 ){
	    \zuji\debug\Debug::error(\zuji\debug\Location::L_SMS, '短息接口协议错误', $response);
	    set_error('发短信失败');
	    return false;
	}
	//保存到数据库
        Sms::save( $templateCode,$mobile, $templateParam,$order_no,$response);
        return true;
    }

    // 发送通知短信
    public function send_sms( $mobile, $templateCode, $templateParam){

        $time = time();		// 当前时间
        $data = [
            '_head'  => [
                '_version'         => '0.01',	    // 接口版本
                '_msgType'         => 'request',    // 类型
                '_interface'       => 'smsSubmit',  // 接口名称
                '_remark'          => '',	      // 备注
                '_invokeId'        => \zuji\Business::create_business_no(), // 流水号
                '_callerServiceId' => $this->service_id,  //
                '_groupNo'         => '1',	    // 服务组ID，固定值1
                '_timestamps'      => $time,	    // 当前时间戳
            ],
            '_param' => [
                'smsSign'       => '3',		    //
                'phones'        => $mobile,	    // 手机号
                'templateCode'  => $templateCode,   // 短息模板ID
                'templateParam' =>$templateParam,
            ],
        ];

        $json = json_encode($data);		// 参数序列化
        $signature = md5($json . '_' . $this->key);	// 参数签名

        // curl
        $response = \zuji\Curl::post($this->tongzhi_url,$json, [	    // header 头
            'HSB-OPENAPI-SIGNATURE:' . $signature,	    // 签名字符串
            'HSB-OPENAPI-CALLERSERVICEID:' . $this->service_id	    // 服务ID
        ] );

        // 记录debug
        //\zuji\debug\Debug::error(\zuji\debug\Location::L_SMS, '短息接口请求失败', $response);

        // curl请求失败
        if ( empty($response) ) {
            \zuji\debug\Debug::error(\zuji\debug\Location::L_SMS, '短息接口请求失败', \zuji\Curl::getError());
            set_error(\zuji\Curl::getError());
            return false;
        }

        // json解析
        $response_arr = json_decode($response, true);
        // debug输出
        $debug = ['request'=>  json_decode($json,true), 'response'=>json_decode($response,true)];
        if( empty($response_arr) || !$response_arr ){
            \zuji\debug\Debug::error(\zuji\debug\Location::L_SMS, '短息接口协议错误', $debug);
            set_error('短息接口协议错误');
            return false;
        }
        if( $response_arr['_data']['ret']!=0 ){
            \zuji\debug\Debug::error(\zuji\debug\Location::L_SMS, '短息接口协议错误', $response);
            set_error('发短信失败');
            return false;
        }
        return true;
    }

    
    /**
     * 验证码
     * @param type $mobile
     * @param type $templateCode
     * @param type $templateParam
     * @return boolean
     */
    public function send_sm_code( $mobile, $templateCode, $templateParam ){

        $time = time();		// 当前时间
        $data = [
            '_head'  => [
                '_version'         => '0.01',	    // 接口版本
                '_msgType'         => 'request',    // 类型
                '_interface'       => 'send',  // 接口名称
                '_remark'          => '',	      // 备注
                '_invokeId'        => \zuji\Business::create_business_no(), // 流水号
                '_callerServiceId' => $this->service_id,  // 
                '_groupNo'         => '1',	    // 服务组ID，固定值1
                '_timestamps'      => $time,	    // 当前时间戳
            ],
            '_param' => [
                'smsSign'       => '3',		    // 
                'phones'        => $mobile,	    // 手机号
                'templateCode'  => $templateCode,   // 短息模板ID
                'templateParam' =>$templateParam,
            ],
        ];
        $json = json_encode($data);		// 参数序列化
        $signature = md5($json . '_' . $this->key);	// 参数签名

		// debug输出
		$debug = ['url'=> $this->code_url,'request'=>  json_decode($json,true)];
		
        // curl
        $response = \zuji\Curl::post($this->code_url,$json, [	    // header 头
            'HSB-OPENAPI-SIGNATURE:' . $signature,	    // 签名字符串
            'HSB-OPENAPI-CALLERSERVICEID:' . $this->service_id	    // 服务ID
        ] );

        // curl请求失败
        if ( empty($response) ) {
	    \zuji\debug\Debug::error(\zuji\debug\Location::L_SMS, '短息接口请求失败', \zuji\Curl::getError());
	    set_error(\zuji\Curl::getError());
            return false;
        }
        
        // json解析
        $response_arr = json_decode($response, true);
		// 填充 请求结果
		$debug['response'] = json_decode($response,true);

		if( empty($response_arr) || !$response_arr ){
			\zuji\debug\Debug::error(\zuji\debug\Location::L_SMS, '短息接口协议错误', $debug);
			set_error('短息接口协议错误');
			return false;
		}
		if( $response_arr['_data']['_ret']!=0 ){
			\zuji\debug\Debug::error(\zuji\debug\Location::L_SMS, '短息接口协议错误', $debug);
			set_error('发短信失败');
			return false;
		}
        return true;
    }
    
    public function verify_sm_code( $mobile, $templateCode, $code ){

        $time = time();		// 当前时间
        $data = [
            '_head'  => [
                '_version'         => '0.01',	    // 接口版本
                '_msgType'         => 'request',    // 类型
                '_interface'       => 'verification',  // 接口名称
                '_remark'          => '',	      // 备注
                '_invokeId'        => \zuji\Business::create_business_no(), // 流水号
                '_callerServiceId' => $this->service_id,  // 
                '_groupNo'         => '1',	    // 服务组ID，固定值1
                '_timestamps'      => $time,	    // 当前时间戳
            ],
            '_param' => [
                'smsSign'       => '3',		    // 
                'phones'        => $mobile,	    // 手机号
                'templateCode'  => $templateCode,   // 短息模板ID
                'code' =>$code,
            ],
        ];
        $json = json_encode($data);		// 参数序列化
        $signature = md5($json . '_' . $this->key);	// 参数签名

        // curl
        $response = \zuji\Curl::post($this->code_url,$json, [	    // header 头
            'HSB-OPENAPI-SIGNATURE:' . $signature,	    // 签名字符串
            'HSB-OPENAPI-CALLERSERVICEID:' . $this->service_id	    // 服务ID
        ] );


        // curl请求失败
        if ( empty($response) ) {
	    \zuji\debug\Debug::error(\zuji\debug\Location::L_SMS, '短息接口请求失败', \zuji\Curl::getError());
	    set_error(\zuji\Curl::getError());
            return false;
        }
        
        // json解析
        $response_arr = json_decode($response, true);
	// debug输出
        
	$debug = ['request'=>  json_decode($json,true), 'response'=>json_decode($response,true)];
	//\zuji\debug\Debug::error(\zuji\debug\Location::L_SMS, '登录验证码请求日志', $debug);
	if( empty($response_arr) || !$response_arr ){
	    \zuji\debug\Debug::error(\zuji\debug\Location::L_SMS, '短息接口协议错误', $debug);
	    set_error('短息接口协议错误');
	    return false;
	}
	if( !isset($response_arr['_data']['_ret']) ){
	    \zuji\debug\Debug::error(\zuji\debug\Location::L_SMS, '短息接口协议错误(_ret)', $debug);
	    set_error('短息接口协议错误');
	    return false;
	}
	if( $response_arr['_data']['_ret']!=0 ){
	    \zuji\debug\Debug::error(\zuji\debug\Location::L_SMS, '短息接口协议错误', $debug);
	    set_error('短息接口协议错误');
	    return false;
	}
        return true;
    }


    // 发送验证码
    public function send_code( $mobile, $templateCode, $templateParam ){

        $time = time();		// 当前时间
        $data = [
            '_head'  => [
                '_version'         => '0.01',	    // 接口版本
                '_msgType'         => 'request',    // 类型
                '_interface'       => 'send',  // 接口名称
                '_remark'          => '',	      // 备注
                '_invokeId'        => \zuji\Business::create_business_no(), // 流水号
                '_callerServiceId' => $this->service_id,  //
                '_groupNo'         => '1',	    // 服务组ID，固定值1
                '_timestamps'      => $time,	    // 当前时间戳
            ],
            '_param' => [
                'smsSign'       => '3',		    //
                'phones'        => $mobile,	    // 手机号
                'templateCode'  => $templateCode,   // 短息模板ID
                'templateParam' =>$templateParam,
            ],
        ];
        $json = json_encode($data);		// 参数序列化
        $signature = md5($json . '_' . $this->key);	// 参数签名

        // debug输出
        $debug = ['url'=> $this->code_url,'request'=>  json_decode($json,true)];

        // curl
        $response = \zuji\Curl::post($this->code_url,$json, [	    // header 头
            'HSB-OPENAPI-SIGNATURE:' . $signature,	    // 签名字符串
            'HSB-OPENAPI-CALLERSERVICEID:' . $this->service_id	    // 服务ID
        ] );

        // curl请求失败
        if ( empty($response) ) {
            \zuji\debug\Debug::error(\zuji\debug\Location::L_SMS, '短息接口请求失败', \zuji\Curl::getError());
            set_error(\zuji\Curl::getError());
            return false;
        }

        // json解析
        $response_arr = json_decode($response, true);
        // 填充 请求结果
        $debug['response'] = json_decode($response,true);

        if( empty($response_arr) || !$response_arr ){
            \zuji\debug\Debug::error(\zuji\debug\Location::L_SMS, '短息接口协议错误', $debug);
            set_error('短息接口协议错误');
            return false;
        }
        if( $response_arr['_data']['_ret']!=0 ){
            \zuji\debug\Debug::error(\zuji\debug\Location::L_SMS, '短息接口协议错误', $debug);
            set_error('发短信失败');
            return false;
        }

        return true;
    }

    // 成功短信
    public function send_sm_success( $mobile, $templateCode, $templateParam = []){

        $time = time();		// 当前时间
        $data = [
            '_head'  => [
                '_version'         => '0.01',	    // 接口版本
                '_msgType'         => 'request',    // 类型
                '_interface'       => 'smsSubmit',  // 接口名称
                '_remark'          => '',	      // 备注
                '_invokeId'        => \zuji\Business::create_business_no(), // 流水号
                '_callerServiceId' => $this->service_id,  //
                '_groupNo'         => '1',	    // 服务组ID，固定值1
                '_timestamps'      => $time,	    // 当前时间戳
            ],
            '_param' => [
                'smsSign'       => '3',		    //
                'phones'        => $mobile,	    // 手机号
                'templateCode'  => $templateCode,   // 短息模板ID
                'templateParam' =>$templateParam,
            ],
        ];
        $json = json_encode($data);		// 参数序列化
        $signature = md5($json . '_' . $this->key);	// 参数签名

        // curl
        $response = \zuji\Curl::post($this->tongzhi_url,$json, [	    // header 头
            'HSB-OPENAPI-SIGNATURE:' . $signature,	    // 签名字符串
            'HSB-OPENAPI-CALLERSERVICEID:' . $this->service_id	    // 服务ID
        ] );

        // curl请求失败
        if ( empty($response) ) {
            \zuji\debug\Debug::error(\zuji\debug\Location::L_SMS, '短息接口请求失败', \zuji\Curl::getError());
            set_error(\zuji\Curl::getError());
            return false;
        }

        // json解析
        $response_arr = json_decode($response, true);
        // debug输出
        $debug = ['request'=>  json_decode($json,true), 'response'=>json_decode($response,true)];
        if( empty($response_arr) || !$response_arr ){
            \zuji\debug\Debug::error(\zuji\debug\Location::L_SMS, '短息接口协议错误', $debug);
            set_error('短息接口协议错误');
            return false;
        }
        if( $response_arr['_data']['ret']!=0 ){
            \zuji\debug\Debug::error(\zuji\debug\Location::L_SMS, '短息接口协议错误', $response);
            set_error('发短信失败');
            return false;
        }

        return true;
    }

}
