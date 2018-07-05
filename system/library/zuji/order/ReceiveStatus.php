<?php

/**
 * （平台）收货单状态
 * @access public 
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 * @copyright (c) 2017, Huishoubao
 * 
 */

namespace zuji\order;

/**
 * ReceiveStatus （平台）收货单状态
 *
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
class ReceiveStatus {
    
    /**
     * @var int 无效状态（为订单表的状态默认值设计）
     * 【注意：】绝对不允许出现出现状态为0的记录（要求程序控制）
     */
    const ReceiveInvalid = 0;
    
    /**
     * @var int 收货单已创建（生效状态）（退货申请审核通过了，等待用户上传物流信息）【起点】
     */
    const ReceiveCreated = 1;
    
    /**
     * @var int 待收货（用户或客服已经上传了物流信息，等待收货中）【中间状态】
     */
    const ReceiveWaiting = 2;
    
    /**
     * @var int 平台确认收货（收货员已经收货，录入和修改设备条码,并进行了确认收货操作）【中间状态】
     */
    const ReceiveConfirmed = 3;
    
    /**
     * @var int 收货完成（收货员发起检测申请操作，收货结束，收货信息禁止再修改）【终点】
     */
    const ReceiveFinished = 4;
    
    /**
     * @var int 取消收货单 【终点】
     */
    const ReceiveCanceled = 5;

    public static function getStatusList (){
        return [
            self::ReceiveCreated => '收货单已创建',
            self::ReceiveWaiting => '待收货',
            self::ReceiveConfirmed => '（平台）已确认收货',
            self::ReceiveFinished => '（平台）收货完成',
            self::ReceiveCanceled => '（平台）取消收货单',
        ];
    }

    public static function getStatusName($status){
        $list = self::getStatusList();
        if( isset($list[$status]) ){
            return $list[$status];
        }
        return '';
    }
}
