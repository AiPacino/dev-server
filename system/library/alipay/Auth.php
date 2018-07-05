<?php

namespace alipay;


use zuji\debug\Debug;

require_once __DIR__ . '/aop/request/AlipaySystemOauthTokenRequest.php';
require_once __DIR__ . '/aop/request/AlipayUserInfoShareRequest.php';

class Auth extends BaseApi {

    public function __construct($appid) {
        parent::__construct($appid);
    }

    /**
     * 获取授权url
     * @param stirng	$redirect_uri	授权调整地址
     * @params string	$scope	授权级别，多个scope时用”,”分隔
     * auth_user：网站支付宝登录信息
     * auth_base：用户信息授权
     * auth_ecard：商户会员卡
     * auth_invoice_info：支付宝闪电开票
     * @return string	授权url
     */
    public function getUrl( $redirect_uri, $scope='auth_user' ){
        $config = $this->config;
        return 'https://openauth.alipay.com/oauth2/publicAppAuthorize.htm?app_id='.$config['app_id'].'&scope='.$scope.'&redirect_uri='.$redirect_uri;
    }

    /**
     * 根据授权，获取用户基本信息
     * @param string $auth_code	    授权码
     * @return mixed	false：失败；array：支付宝用户信息
     */
    public function getUserInfo($auth_code) {

        $token = $this->requestToken($auth_code);
        // 成功返回
        // 示例：array(
        // 'access_token' => 'publicpBfd7aa055c4c34120949e287f84eee84a',
        // 'expires_in' => 500,
        // 're_expires_in' => 300,
        // 'refresh_token' => 'publicpB343643c1f58b415ab9add66c0ea91fd3',
        // )
        if ($token) {
            $token_str = $token['access_token'];
            // echo $token_str;
            $user_info = $this->requestUserInfo($token_str);
            //var_dump($user_info);
            if ($user_info) {
                return $user_info;
            }
            set_error('获取用户授权信息失败'.  var_export($user_info,true));
            return false;
        }
        set_error('获取token失败'.  var_export($token,true));
        return false;
    }

    /**
     *
     * 根据token，获取用户基本信息
     * @param string $token	    token值
     * @return mixed	false：失败；array：支付宝用户信息
     */
    public function requestUserInfo($token) {
        $AlipayUserUserinfoShareRequest = new \AlipayUserInfoShareRequest ();

        $result = $this->execute($AlipayUserUserinfoShareRequest, $token);
        $result =  json_decode(json_encode($result), true);
        if(isset($result['alipay_user_info_share_response']) && $result['alipay_user_info_share_response']['code']==10000){
            return $result['alipay_user_info_share_response'];
        }
        return false;
    }

    /**
     *
     * 根据授权码，获取token值
     * @param string $auth_code	    授权码
     * @return mixed	false：失败；string：token值
     */
    public function requestToken($auth_code) {
        $AlipaySystemOauthTokenRequest = new \AlipaySystemOauthTokenRequest ();
        $AlipaySystemOauthTokenRequest->setCode($auth_code);
        $AlipaySystemOauthTokenRequest->setGrantType("authorization_code");

        $result = $this->execute($AlipaySystemOauthTokenRequest);
        $result =  json_decode(json_encode($result), true);
        if(isset($result['error_response'])){
            return false;
        }
        if(isset($result['alipay_system_oauth_token_response'])){
            return $result['alipay_system_oauth_token_response'];
        }

        return false;
    }

}

?>