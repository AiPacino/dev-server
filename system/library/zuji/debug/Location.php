<?php

/*
 *
 */

namespace zuji\debug;

/**
 * 
 *
 * @author 
 */
class Location {

    /**
     * @var int 支付
     */
    const L_Payment = 1;
    
    /**
     * @var int 第三方用户授权
     */
    const L_UserAuthorization = 2;
    
    /**
     * @var int 信用认证
     */
    const L_Certification = 3;
    /**
     * @var int 退款单
     */
    const L_Refund = 4;
    /**
     * @var int 资金授权
     */
    const L_FundAuth = 5;
    
    /**
     * @var int 交易处理
     */
    const L_Trade = 6;
    
    /**
     * @var int 短息
     */
    const L_SMS = 7;
    /**
     * @var int 退货单
     */
    const L_Return = 8;
    /**
     * @var int 发货单
     */
    const L_Delivery = 9;
    /**
     * @var int 收货单
     */
    const L_Receive = 10;
    /**
     * @var int 检测单
     */
    const L_Evaluation = 11;
    /**
     * @var int 订单
     */
    const L_Order = 12;
    /**
     * @var int 服务单
     */
    const L_Service = 13;
    /**
     * @var int 数据库
     */
    const L_DB = 14;
    /**
     * @var int 用户
     */
    const L_Member = 15;
    /*
     * @var int 用户
     */
    const L_Withholding = 16;
    /**
     * @var int 数据统计
     */
    const L_DataCount = 17;
    /**
     * @var int 支付宝小程序
     */
    const L_AlipayMini = 18;

    /**
     * @var int 支付平台
     */
    const L_Payment_product = 1;

    /**
     * 列表
     * @return array    列表
     */
    public static function getLocationList(){
        return [
            self::L_Payment => '支付',
            self::L_UserAuthorization => '用户授权',
            self::L_Certification => '信用认证',
            self::L_Refund => '退款单',
            self::L_FundAuth => '资金授权',
            self::L_Trade => '交易',
            self::L_SMS => '短信',
            self::L_Return => '退货单',
            self::L_Delivery => '发货单',
            self::L_Receive => '收货单',
            self::L_Order => '订单',
            self::L_Service => '服务单',
            self::L_DB => '数据库',
            self::L_Member => '用户',
            self::L_DataCount => '数据统计',
            self::L_AlipayMini => '支付宝小程序',

        ];
    }

    /**
     * 值 转换成 名称
     * @param int $status   值
     * @return string 名称
     */
    public static function getLocationName($status){
        $list = self::getStatusList();
        if( isset($list[$status]) ){
            return $list[$status];
        }
        return '';
    }
    
}
