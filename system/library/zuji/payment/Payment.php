<?php
/**
 * 支付接口文档
 * @access public 
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 * @copyright (c) 2017, Huishoubao
 * 
 */

namespace zuji\payment;

/**
 * 支付接口
 * @access public 
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
class Payment{
    //-+------------------------------------------------------------------------
    // | 支付渠道常量定义
    //-+------------------------------------------------------------------------
    /**
     * @var int 支付渠道 -- 支付宝
     */
    const Channel_ALIPAY = 1;
    /**
     * @var int 支付渠道 -- 银联
     */
    const Channel_UNIONPAY =2;
    // 后期追加其他支付渠道常量
    
    //-+------------------------------------------------------------------------
    // | 支付类型常量定义
    //-+------------------------------------------------------------------------
    /**
     * @var int 支付分类 -- 资金预授权 解冻转支付
     */
    const Type_Fund_Auth_Payment = 1;
    /**
     * @var int 支付分类 -- 订单支付
     */
    const Type_Order_Payment = 2;
    // 后期追加其他支付分类常量
    
    /**
     * 获取支付渠道列表
     * @access public
     * @author liuhongxing <liuhongxing@huishoubao.com.cn>
     * @return array	支付渠道列表，键：支付渠道常量值；值：支付渠道名称
     */
    public static function getChannelList(){
	return [
	    self::Channel_ALIPAY => '支付宝',
        self::Channel_UNIONPAY=>'银联',
	];
    }
    
    /**
     * 更具支付渠道常量值，获取支付渠道名称
     * @param int $channel_id 支付渠道常量值
     * @access public
     * @author liuhongxing <liuhongxing@huishoubao.com.cn>
     * @return string	支付渠道名称，如果传入的支付渠道常量值是未定义的，返回空字符串
     */
    public static function getChannelName($channel_id){
        $arr = self::getChannelList();
        if( !isset($arr[$channel_id]) ){
            return '';
        }
        return $arr[$channel_id];
    }
    
    /**
     * 获取支付链接地址
     */
    public function getPaymentUrl(  ){
    }
    /**
     * 获取支付form表单
     */
    public function getPaymentForm(  ){
    }
    
    /**
     * 处理支付结果异步通知
     */
    public function paymentNotify(){
    }
    
    /**
     * 退款
     */
    public function refund(){
	
    }
    
    /**
     * 转账
     */
    public function transfer(){
	
    }
    
    
}