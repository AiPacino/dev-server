<?php

namespace alipay;
use alipay\aop\AopClient;

/**
 * Description of BaseApi
 *
 * @author Administrator
 */
include __DIR__ . '/AopSDk.php';
include __DIR__ . '/function.inc.php';
class BaseApi {
    
    protected $appid = 'default';
    protected $config = [];
    
    /**
     *
     * @var \alipay\aop\AopClient
     */
    protected $Aop = NULL;


    public function __construct( $appid ){
	
	$config_file = __DIR__.'/'.$appid.'-config.php';
	if( !file_exists($config_file) && !is_readable($config_file) ){
	    throw new \Exception('支付宝应用配置未找到:'.$config_file);
	}
	$config = include $config_file;
	$aop = new AopClient ();
	$aop->gatewayUrl = $config ['gatewayUrl'];
	$aop->appId = $config ['app_id'];
	$aop->rsaPrivateKey=$config['merchant_private_key'];
	$aop->alipayrsaPublicKey=$config['alipay_public_key'];
	$aop->signType=$config['sign_type'];
	$aop->apiVersion = "1.0";
	// 开启页面信息输出
	$aop->debugInfo=true;
	
	$this->Aop = $aop;
	$this->config = $config;

    }
    
    public function pageExecute($request, $ispage=false,$type="POST"){
	if($ispage)
	{
	    return $this->Aop->pageExecute($request,$type);
	}
	return $this->Aop->execute($request);
    }
    
    public function execute( $request,$token='' ){
	return $this->Aop->execute($request,$token);
    }
    
    /**
     * 签名校验
     * @param type $params
     * @param type $sign
     * @return boolean
     */
    public function verify( $params ){
	$this->Aop->alipayrsaPublicKey = $this->config['alipay_public_key'];
	$result = $this->Aop->rsaCheckV1($params, $this->config['alipay_public_key'], $this->config['sign_type']);
	return $result;
    }
    
}
