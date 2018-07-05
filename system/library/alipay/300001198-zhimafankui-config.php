<?php
// 芝麻反馈配置参数
return array(
    //应用ID,您的APPID。
    'app_id' => "300001198", // 正式app_id
    //商户私钥文件
    'merchant_private_key_file' => __DIR__."/keys/zhima/rsa_private_key.pem",
    //商户应用公钥
    'merchant_public_key_file' => __DIR__."/keys/zhima/rsa_public_key.pem",
    //芝麻公钥
    //'alipay_public_key' => "MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDVPLvtDAdC5DURIQFs6CLt3akdxnBdPVbs4sxrCem+R1s6v2nCKbBEobA61ktHpGKPDL2SOV3xF/Oy45U7LoqUixfHsMrjhvE4rk5gTZjqsdwT59unvyF/eita9txyuALDDQtTKpF1y+YHkfDH6YLep66lVB38MitkfTJErwyl8wIDAQAB",
    'alipay_public_key_file' =>  __DIR__."/keys/zhima/zhima_public_key.pem",
    //（手机网站支付）编码格式
    'charset' => "UTF-8",
    //支付宝网关
    'gatewayUrl' => "https://zmopenapi.zmxy.com.cn/openapi.do",
    //测试
    'type_id_test' => '300000249-default-test',
    //正式
    'type_id' => '300000249-default-order',
    //开关
    'switch' => true,
);
