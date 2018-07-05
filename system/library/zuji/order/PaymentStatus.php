<?php

/**
 * 支付单状态
 * @access public 
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 * @copyright (c) 2017, Huishoubao
 * 
 */

namespace zuji\order;

/**
 * PaymentStatus 支付单状态类
 *
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
class PaymentStatus {
    
    /**
     * @var int 无效状态（为订单表的状态默认值设计）
     * 【注意：】支付单中绝对不允许出现出现状态为0的记录（要求程序控制）
     */
    const PaymentInvalid = 0;
    
    /**
     * @var int 支付单创建（生效状态）（支付单创建过程）【支付单操作的起点】
     */
    const PaymentCreated = 1;
    
    /**
     * @var int 待支付（用户未点击支付按钮，等待用户去支付操作）【支付单操作的起点】
     */
    const PaymentWaiting = 2;

    /**
     * @var int 待支付（用户已经点击支付按钮，等待用户完成支付操作（支付平台异步通知尚未收到））【支付单操作的起点】
     */
    const PaymentPaying = 3;
    
    /**
     * @var int 支付失败（用户在第三方支付平台完成了支付，服务器接收到了支付失败的通知）【终点】
     */
    const PaymentFailed = 4;

    /**
     * @var int 支付成功（用户在第三方支付平台完成了支付，服务器接收到了支付成功通知）【终点】
     */
    const PaymentSuccessful = 5;




    /**
     * 退款申请状态  没有申请  默认状态
     */
    const PaymentApplyInvalid =0;
    /**
     * 退款申请状态  等待审核
     */
    const PaymentApplyWaiting =1;
    /**
     * 退款申请状态  审核通过
     */
    const PaymentApplySuccessful =2;
    /**
     * 退款申请状态  审核拒绝
     */
    const PaymentApplyFailed =3;
    
    
    /**
     * 获取申请退款状态
     */
    public static function getApplyList (){
        return [
            self::PaymentApplyInvalid => '初始化默认状态',
            self::PaymentApplyWaiting => '退款审核中',
            self::PaymentApplySuccessful => '退款同意',
            self::PaymentApplyFailed => '退款拒绝',
        ];
    }
    /**
     * @param int $apply_status 【必须】退款申请状态
     * @return string 状态名称
     */
    public static function getApplyName ( $apply_status ){
        $arr = self::getApplyList();
        if( !isset($arr[$apply_status]) ){
            return '';
        }
        return $arr[$apply_status];
    }
    
    /**
     * 获取业务类型列表
     */
    public static function get_list (){
        return [
            self::PaymentCreated => '创建支付单',
            self::PaymentWaiting => '待支付',
            self::PaymentPaying => '支付中',
            self::PaymentFailed => '支付取消',
            self::PaymentSuccessful => '支付成功',
        ];
    }
    public static function getStatusList (){
        return self::get_list();
    }
    
    /**
     * @param int $payment_status 【必须】支付单状态
     * @return string 状态名称
     */
    public static function get_name ( $payment_status ){
        $arr = self::get_list();
        if( !isset($arr[$payment_status]) ){
            return '';
        }
        return $arr[$payment_status];
    }
    public static function getStatusName ( $payment_status ){
        return self::get_name($payment_status);
    }

    

    
}
