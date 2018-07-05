<?php

/**
 * 退款单状态
 * @access public 
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 * @copyright (c) 2017, Huishoubao
 * 
 */

namespace zuji\order;

/**
 * RefundStatus 退款单状态类
 *
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
class RefundStatus {
    
    /**
     * @var int 无效状态（为订单表的状态默认值设计）
     * 【注意：】绝对不允许出现出现状态为0的记录（要求程序控制）
     */
    const RefundInvalid = 0;
    
    /**
     * @var int 待退款已创建（生效状态）（检测合格或异常处理后允许退款的）【起点】
     */
    const RefundCreated = 1;
    
    /**
     * @var int 待退款（可以开始执行退款操作）【中间状态】
     */
    const RefundWaiting = 2;

    /**
     * @var int 退款中（支付平台完成了退款申请后，服务器接收到了退款成功通知）【终点】
     */
    const RefundPaying = 3;
    /**
     * @var int 退款成功（支付平台完成了退款申请后，服务器接收到了退款成功通知）【终点】
     */
    const RefundSuccessful = 4;
    
    /**
     * @var int 退款失败（支付平台完成了退款申请后，服务器接收到了退款失败的通知）【终点】
     */
    const RefundFailed = 5;
    
    
    public static function getStatusList(){
        return [
            self::RefundCreated => '待退款(已创建)',
            self::RefundWaiting => '待退款',
            self::RefundPaying => '退款中',
            self::RefundSuccessful => '退款成功',
            self::RefundFailed => '退款失败',
        ];
    }

    public static function getStatusName($status){
        $list = self::getStatusList();
        if( isset($list[$status]) ){
            return $list[$status];
        }
        return '';
    }
    
    //-+----------------------------------------------------------------------
    // | 退款类型（有系统根据支付渠道的设置，自动判断选择哪种类型的退款方式）
    //-+----------------------------------------------------------------------
    /**
     * @var int 原路返回（交易未关闭的前提下，资金原路返回到用户支付账户）
     */
    const TypeBacktrack = 1;
    /**
     * @var int 转账（交易关闭时）
     */
    const TypeTransfer = 2;
    
    public static function getTypeList(){
	return [
	    self::TypeBacktrack => '退款',
	    self::TypeTransfer => '转账',
	];
    }
    
    
    
}
