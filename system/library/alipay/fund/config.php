<?php

$alipay_config = [];

//合作身份者id，以2088开头的16位纯数字
$alipay_config['partner']		= '2088821542502025';
//安全检验码，以数字和字母组成的32位字符
$alipay_config['key']			= '7h8zywbpe9sh00jqlexqwqw9akvuol8c';
//签名方式 不需修改
$alipay_config['sign_type']    = strtoupper('MD5');
//字符编码格式 目前支持 gbk 或 utf-8
$alipay_config['input_charset']= strtolower('utf-8');
//ca证书路径地址，用于curl中ssl校验
//请保证cacert.pem文件在当前文件夹目录中
$alipay_config['cacert']    = __DIR__.'/cacert.pem';

//访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
$alipay_config['transport']    = 'https';

// 若签约花呗渠道，需要使用，
// 则必须在请求参数中传入payee_logon_id（收款方支付宝账号）或者payee_user_id（收款方支付宝用户号），两者必传其一
// 请求参数中若传入payee_logon_id（收款方支付宝账号）或者payee_user_id（收款方支付宝用户号），在转支付时会校验收款方是否与此一致
$alipay_config['payee_logon_id']    = 'zuji@huishoubao.com.cn'; //'zuji@huishoubao.com.cn';
$alipay_config['payee_user_id']    = '2088821542502025'; //'';

// 支付宝网关地址
$alipay_config['alipay_gateway']    = 'https://mapi.alipay.com/gateway.do';


