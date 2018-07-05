<?php

/**
 * 订单状态
 * @access public
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 * @copyright (c) 2017, Huishoubao
 *
 */

namespace zuji\order;

/**
 * OrderStatus 订单状态类
 *
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
class OrderStatus {

    //--------------------------------------------------------------------------------------------
    //--+ 订单状态 start --------------------------------------------------------------------------
    //--------------------------------------------------------------------------------------------
    /**
     * @var int 订单初始化状态，不可使用（为自动审核阶段使用）
     */
    const OrderInitialize = 0;

    /**
     * @var int 生效状态（用户可用进行付款、取消操作）【订单操作的起点】
     */
    const OrderCreated = 1;

    /**
     * @var int 已取消状态（用户或管理员进行了订单取消操作）【终点】
     */
    const OrderCanceled = 2;

    /**
     * 这个值暂时不用 。。。
     * @var int 订单超时（用户在规定的时间内，没有完成支付操作）【终点】
     */
    const OrderTimeout = 3;

    /**
     * @var int 订单完成（已支付订单的最终状态，不考虑是否有退货退款）【终点】
     */
    const OrderFinished = 4;
    //--------------------------------------------------------------------------------------------
    //--+ 订单状态 end ----------------------------------------------------------------------------
    //--------------------------------------------------------------------------------------------
    /**
     * @var 门店业务待审核状态
     */
    const OrderStorePending =10;
    /**
     * @var 门店业务待确认状态
     */
    const OrderStoreConfirming =11;
    /**
     * @var 门店业务已确认
     */
    const OrderStoreConfirmed =12;
    /**
     * @var 门店业务待上传图片状态
     */
    const OrderStoreUploading =13;

    //--------------------------------------------------------------------------------------------
    //--+ 订单阶段状态 start -----------------------------------------------------------------------
    //--------------------------------------------------------------------------------------------
    /**
     * @var int 初始化阶段状态（用户生成订单表时产生）
     */
    const StepInitialize = 0;

    /**
     * @var int 订单生效阶段状态（预留给订单审核时使用）【中间状态】
     */
    const StepCreated = 1;
    /**
     * @var int 订单支付阶段状态（支付单操作阶段表时使用）【中间状态】
     */
    const StepPayment = 2;

    /**
     * @var int 订单发货阶段状态（发货表操作阶段表时使用）【中间状态】
     */
    const StepDelivery = 3;

    /**
     * @var int 订单退货申请阶段状态（用户确认收货时产生）【中间状态】
     */
    const StepReturn = 4;

    /**
     * @var int 订单收货阶段状态（用户退货、换机、还机时产生）【中间状态】
     */
    const StepReceive = 5;

    /**
     * @var int 订单检测阶段状态（检测阶段使用）【中间状态】
     */
    const StepEvaluation = 6;

    /**
     * @var int 订单退款阶段状态【退款阶段使用】【中间阶段】
     */
    const StepRefund = 7;

    /**
     * @var int 服务阶段
     */
    const StepService = 8;
    //--------------------------------------------------------------------------------------------
    //--+ 订单阶段状态 end -------------------------------------------------------------------------
    //--------------------------------------------------------------------------------------------

    /**
     * 订单状态列表
     * @return array    订单状态列表
     */
    public static function getStatusList(){
        return [
            self::OrderCreated => '开启',
            self::OrderCanceled => '已取消',
            self::OrderTimeout => '已超时',
            self::OrderFinished => '已完成',
            self::OrderStorePending=>'待审核',
            self::OrderStoreConfirming=>'待确认',
            self::OrderStoreConfirmed=>'已确认',
            self::OrderStoreUploading=>'待图片上传',
        ];
    }

    /**
     * 订单状态值 转换成 状态名称
     * @param int $status   订单状态值
     * @return string 订单状态名称
     */
    public static function getStatusName($status){
        $list = self::getStatusList();
        if( isset($list[$status]) ){
            return $list[$status];
        }
        return '';
    }


    /**
     * 订单阶段列表
     * @return array    订单状态列表
     */
    public static function getStepList(){
        return [
            self::StepCreated => '创建阶段',
            self::StepPayment => '支付阶段',
            self::StepDelivery => '发货阶段',
            self::StepReturn => '退货申请阶段',
            self::StepReceive => '收货阶段',
            self::StepEvaluation => '检测阶段',
            self::StepRefund => '退款阶段',
            self::StepService => '服务阶段',
        ];
    }
    /**
     * 订单阶段值 转换成 订单阶段名称
     * @param int $status   订单阶段值
     * @return string 订单阶段名称
     */
    public static function getStepName($status){
        $list = self::getStepList();
        if( isset($list[$status]) ){
            return $list[$status];
        }
        return '';
    }



}
