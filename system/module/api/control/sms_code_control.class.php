<?php


use zuji\debug\Debug;
use zuji\debug\Location;
hd_core::load_class('api', 'api');
/**
 * 新机发布预租活动接口 API
 * @access public
 * @author maxiaoyu <maxiaoyu@huishoubao.com.cn>
 * @copyright (c) 2018-03-07, Huishoubao
 */
class sms_code_control extends api_control {


    public function _initialize() {
        parent::_initialize();

        $this->service = $this->load->service('order2/sms_code');

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
            'mobile' => 'required|is_mobile',
        ]);

        if( !isset($params['mobile'])){
            api_resopnse( $result, ApiStatus::CODE_20000,'', ApiSubCode::SMS_Error_Mobile,'' );
            return;
        }

        $mobile = $params['mobile'];


        $time = time();
        $response_arr = null;

        // 1分钟内禁止重复发短息
        if( $_SESSION['_sms_code_time_']+60 > $time ){
            api_resopnse($result, ApiStatus::CODE_50009, '请过一分钟后再操作' );
            return ;
        }
        // 验证码
        $_SESSION['_login_mobile_']     = $mobile;
        $_SESSION['_sms_code_time_']    = $time;
        $_SESSION['_login_expiry_']     = $time + 300;// 5分钟有效

        //测试账户
        if(config("Test_Mobile_On") == true){
            if($mobile == config("Test_Mobile")){
                api_resopnse( $result, ApiStatus::CODE_0 );
                return ;
            }
        }

        $sms = new \zuji\sms\SendSms();
        $b = $sms->send_code([
            'mobile' => $mobile,
        ]);
        if (!$b) {
            api_resopnse( $result, ApiStatus::CODE_60000, '短信接口错误' );
            return ;
        }

        api_resopnse( $result, ApiStatus::CODE_0 );
        return ;

    }

    /**
     * 验证短信验证码接口
     *
     */
    public function verification() {
        $result = ['auth_token'=>  $this->auth_token,];
        //-+--------------------------------------------------------------------
        // | 接收请求
        //-+--------------------------------------------------------------------
        $params = $this->params;

        $params = filter_array($params,[
            'mobile' => 'required|is_mobile',
            'code' => 'required',
        ]);

        if( !isset($params['mobile'])){
            api_resopnse( $result, ApiStatus::CODE_20000,'', ApiSubCode::SMS_Error_Mobile,'' );
            return;
        }

        if( !isset($params['code'])){
            api_resopnse( $result, ApiStatus::CODE_20000, '验证码错误' );
            return;
        }

        // 接口校验验证码
        $sms = new \zuji\sms\HsbSms();
        $b = $sms->verify_sm_code($params['mobile'],'SMS_113450943',$params['code']);

        if( !$b ){
            api_resopnse( $result, ApiStatus::CODE_40004,'验证码错误');
            return;
        }

        // 查询
        $where = [
            'mobile'=>$params['mobile']
        ];

        $info = $this->service->find($where);
        if($info){
            api_resopnse( $result, ApiStatus::CODE_50011, '您已经参加新机预租活动' );
            return;
        }
        
        //保存到数据库
        $code_data = [
            'mobile' => $params['mobile'],
            'create_time' => time(),
        ];
        $create_b = $this->service->create($code_data);
        if(!$create_b){
            api_resopnse( $result, ApiStatus::CODE_20000, '新机预租活动失败' );
            return false;
        }

        // 预约成功短信
        $sms->send_sm_success($params['mobile'],'hsb_sms_bbb41',['mobile'=>$params['mobile']]);

        api_resopnse( $result, ApiStatus::CODE_0 ,'成功');
        return ;

    }


}
