<?php
use zuji\Business;

/**
 *	  内容控制器
 */
hd_core::load_class('api', 'api');
class sms_control extends api_control {

    public $position = array('index-lunbo'=>4,'index-hot'=>7,'index-brand'=>6);
    public $limit = 20;

    public function _initialize() {
	    parent::_initialize();
    }
    
    public static function is_country_code( $code ){
        return $code=='86';
    }
    
    public static function is_type( $type ){
        $arr = [
            'SM_LOGIN',
        ];
        return in_array($type,$arr);
    }


    /**
     * 发短信验证码接口
     */
    public function send() {
	$result = ['auth_token'=>  $this->auth_token,];
    	//-+--------------------------------------------------------------------
    	// | 接收请求
    	//-+--------------------------------------------------------------------
    	$params = $this->params;
        
        $params = filter_array($params,[
            'type' => 'required|sms_control::is_type',
            'mobile' => 'required|is_mobile',
            'country_code' => 'required|sms_control::is_country_code',
        ]);
        
        if( !isset($params['type'])){
    	   api_resopnse( $result, ApiStatus::CODE_20000,'', ApiSubCode::SMS_Error_Type,'' );
           return;
        }
        if( !isset($params['mobile'])){
    	   api_resopnse( $result, ApiStatus::CODE_20000,'', ApiSubCode::SMS_Error_Mobile,'' );
           return;
        }
        if( !isset($params['country_code'])){
    	   api_resopnse( $result, ApiStatus::CODE_20000,'', ApiSubCode::SMS_Error_Country_code,'' );
           return;
        }
        $mobile = $params['mobile'];
        $code = mt_rand(100000,999999);

        $time = time();
        $response_arr = null;
        if( $params['type'] == 'SM_LOGIN' ){
            // 1分钟内禁止重复发短息
            if( $_SESSION['_login_time_']+60 > $time ){
                api_resopnse($result, ApiStatus::CODE_50009,'短息错误', '','1分钟内禁止重发' );
                return ;
            }
            // 验证码
            $_SESSION['_login_mobile_'] = $mobile;
            $_SESSION['_login_sm_code_'] = $code;   //
            $_SESSION['_login_time_'] = $time;
            $_SESSION['_login_expiry_'] = $time+300;// 5分钟有效

            //测试账户
            if(config("Test_Mobile_On") == true){
                if($mobile == config("Test_Mobile")){
                    api_resopnse( $result, ApiStatus::CODE_0 );
                    return ;
                }
            }

            $sms = new \zuji\sms\HsbSms();
            $b = $sms->send_sm_code($mobile,'SMS_113450943',[
                'code' => $code,    // 冗余参数，验证码接口内部自己生成随机数
            ]);
            if (!$b) {
                $error = "返回格式非json";
                api_resopnse( $result, ApiStatus::CODE_60000,'短信接口错误' );
                return ;
            }

            // 空
            //$result['$request'] = $params;
            //$result['$_s'] = $_SESSION;
            api_resopnse( $result, ApiStatus::CODE_0 );
            return ;

            
        }elseif( $params['type'] == '其它' ){
            /*
            */
//            $response_arr = $this->send_sm($mobile,'SMS_109335197',[
//                        'store'     => "华为门店",
//                        'phoneType' => "iPhone 7",
//                    ]);
        }
        
    
    }
}