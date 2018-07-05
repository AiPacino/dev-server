<?php

/**
 * 支付宝相关业务服务层
* @package pay
* @author limin <limin@huishoubao.com.cn>
* @copyright (c) 2017, Huishoubao
*/
class alipay_service extends service {
    const ALIPAY_URL         = 'https://openapi.alipay.com/gateway.do';
    const ALIPAY_APPID       = '2017101309291418';
    const ALIPAY_AUTO_KEY    = '79rt234fg12t30fegdsfg423';
    const ALIPAY_RSA_PRIVATE = '';

    /**
    * 支付宝授权链接
    * @return array
    */
    public function alipay_authorize($redirect_url){
        $url = 'http://openauth.alipay.com/oauth2/publicAppAuthorize.htm';
        $request = array(
            'app_id'         => self::ALIPAY_APPID,
            'scope'          => 'auth_user',
            'redirect_uri'   => urlencode($redirect_url)
        ); 
        
        foreach( $request as $k => $v )
		{
			$arr[] = $k . '=' . $v;
		}
        
		$str  = implode( '&', $arr );
        $str .= "&state=".strtolower( md5($str.'key='.self::ALIPAY_AUTO_KEY) );
        $url  = $url."?".$str;
        return $url;
    }
    /**
    * 支付宝获取授权token
    * @return array
    */
    public function alipay_token( $authcode )
    {
			$requestUrl = self::ALIPAY_URL;
            $req = array(
                'grant_type'    =>   'authorization_code',
                'code'          =>   $authcode,
            );
            $config = array(
                'app_id'        =>   self::ALIPAY_APPID,
                'version'       =>   '1.0',
                'format'        =>   'JSON',
                'sign_type'     =>   'RSA',
                'timestamp'     =>   date("Y-m-d H:i:s"),
                'method'        =>   'alipay.open.auth.token.app',
                'format'        =>   'json',
                'charset'       =>   'utf-8',
            );
            
            $data['biz_content']= json_encode($req);
            $signstr  = $this->getSignContent(array_merge($data,$config));
			$sign = $this->alipayNewRsaSign($signstr);
            $config['sign'] = $sign;
            $requestUrl .= $this->getSignContent($config,true);  
   
            $response   = Curl::post($requestUrl,$data);
            $respobject = json_decode($response,true);
            return $respobject;
    }
    /** 
    * 支付宝会员授权信息查询接口
    * 根据授权token，查询授权信息
    * @return array
    */
    public function alipay_share($token){
        $requestUrl = self::ALIPAY_URL;
            $req = array(
                'grant_type'    =>   'authorization_code',
                'code'          =>   $authcode,
            );
            $config = array(
                'app_id'        =>   self::ALIPAY_APPID,
                'version'       =>   '1.0',
                'format'        =>   'JSON',
                'sign_type'     =>   'RSA',
                'timestamp'     =>   date("Y-m-d H:i:s"),
                'method'        =>   'alipay.user.info.share',
                'format'        =>   'json',
                'charset'       =>   'utf-8',
            );
            
    }
    
    //转换成目标字符集
    protected function getSignContent($params,$urlencode=false){
        ksort( $params ); 
		$arr = array();
		foreach( $params as $k => $v )
		{
            $value = $urlencode?urlencode($v):$v;
			$arr[] = $k.'='.$value;
		}
		$str  = implode( '&', $arr );
        return $str;
    }
    //RSA加密
    public function alipayNewRsaSign( $data )
	{   
	    $rsa = @openssl_get_privatekey(self::ALIPAY_RSA_PRIVATE);
		@openssl_sign( $data, $sign, $rsa );
		@openssl_free_key( $rsa );
		return base64_encode( $sign );
	}
}
