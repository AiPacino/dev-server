<?php
/**
 * 资金预授权
 *
 */
namespace zuji\payment;


/**
 * 资金预授权 接口
 *
 */
abstract class FundAuth {
    
    /**
     * @var int 无效
     */
    const OrderInitialize = 0;
    /**
     * @var int 创建记录
     */
    const CREATED = 1;
    /**
     * @var int 初始化
     */
    const INIT = 2;
    /**
     * @var int 已授权
     */
    const AUTHORIZED = 3;
    /**
     * @var int 完成
     */
    const FINISH = 4;
    /**
     * @var int 关闭
     */
    const CLOSED = 5;


    /**
     * 资金授权交易类型
     */
    //分期解冻
    const FenqiToUnfreeze=1;
    //分期转支付
    const FenqiToPay =2;
    //押金解冻
    const YajinToUnfreeze=3;
    //押金转支付
    const YajinToPay=4;
    //预授权解冻
    const FundAuthToUnfreeze =5;


    /**
     * 获取资金授权交易类型列表
     * @return array
     */
    public static function getAuthList(){
        return [
            self::FenqiToUnfreeze => '分期解冻',
            self::FenqiToPay => '分期转支付',
            self::YajinToUnfreeze => '押金解冻',
            self::YajinToPay => '押金转支付',
            self::FundAuthToUnfreeze => '授权解冻',
        ];
    }

    /**
     *
     * @param int $status   状态值
     * @return string 状态名称
     */
    public static function getAuthName($status){
        $list = self::getAuthList();
        if( isset($list[$status]) ){
            return $list[$status];
        }
        return '';
    }
    /**
     * 获取资金授权状态列表
     * @return array
     */
    public static function getStatusList(){
	return [
	    self::CREATED => '已创建',
	    self::INIT => '初始化',
	    self::AUTHORIZED => '已授权',
	    self::FINISH => '已完成',
	    self::CLOSED => '关闭',
	];
    }
    
    /**
     * 状态值 转换成 状态名称
     * @param int $status   状态值
     * @return string 状态名称
     */
    public static function getStatusName($status){
        $list = self::getStatusList();
        if( isset($list[$status]) ){
            return $list[$status];
        }
        return '';
    }
    
    public static function verifyPlatform( $platform ){
	return in_array($platform, [
	   \alipay\fund\FundAuth::Platform,	// 支付宝 
	]);
    }
    
    /**
     * 获取 资金授权接口 实例对象
     * @param FundAuth	    资金授权接口实例对象
     */
    public static function create( $platform ){
	if( $platform == \alipay\fund\FundAuth::Platform ){
	    return new \alipay\fund\FundAuth();
	}
	return false;
    }
    
    
    /**
     * 生成签名
     * @param type $params
     * @return string	签名字符串
     */
    abstract public function sign( $params );
    
    /**
     * 获取冻结请求的url地址
     * @param array $data	    【必须】
     * [
     *	    'notify_url' => '',	    //【必须】string；服务器异步通知页面路径,不能加?id=123这类自定义参数
     *	    'return_url' => '',	    //【必须】string；页面跳转同步通知页面路径，不能加?id=123这类自定义参数，不能写成http://localhost/
     *	    'out_order_no' => '',   //【必须】string；商户授权资金订单号；同一商户不同的订单，商户授权资金订单号不能重复
     *	    'out_request_no' => '', //【必须】string；商户本次资金操作的请求流水号；同一商户每次不同的资金操作请求，商户请求流水号不能重复
     *	    'order_title' => '',    //【必须】string；业务订单的简单描述，如商品名称等
     *	    'amount' => '',	    //【必须】price；本次操作冻结的金额，单位为：元（人民币）；取值范围：[0.01,100000000.00]
     *	    'pay_mode' => '',	    //【可选】stirng；支付模式；取值范围： WIRELESS：需要在无线端完成支付；PC：支持在电脑上完成支付
     * ]
     * @return string	url地址
     */
    abstract public function freeseUrl($data);
    /**
     * 获取冻结请求的 form表单
     * @param array $data	【必须】   参考 freseUrl()
     * @return string	 form表单字符串
     */
    abstract public function freeseForm($data);
    
    
    /**
     * 获取返回时的签名验证结果
     * @param $params 通知返回来的参数数组
     * @param $sign 返回的签名结果
     * @return bool 签名验证结果    true：成功；false：失败
     */
    abstract public function signVerify($params,$sign);
    
    
    abstract public function unfreeze($params);
    
}
