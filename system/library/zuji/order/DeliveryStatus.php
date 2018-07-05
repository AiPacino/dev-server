<?php

/**
 * 发货单状态
 * @access public 
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 * @copyright (c) 2017, Huishoubao
 * 
 */

namespace zuji\order;

/**
 * DeliveryStatus 发货单状态类
 *
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
class DeliveryStatus {
    
    /**
     * @var int 无效状态（为订单表的状态默认值设计）
     * 【注意：】发货单中绝对不允许出现出现状态为0的记录（要求程序控制）
     */
    const DeliveryInvalid = 0;
    
    /**
     * @var int 已创建（创建状态） 【预留状态：暂时未使用】
     */
    const DeliveryCreated = 1;
    
    /**
     * @var int 待发货（生效状态）（进入发货流程，等待发货员进行发货操作）【订单操作的起点】
     */
    const DeliveryWaiting = 2;
    
    /**
     * @var int 生成租机协议（为用户创建租机协议操作）【中间状态】
     */
    const DeliveryProtocol = 3;
    
    /**
     * @var int 已发货（发货员进行发货操作，填写了设备信息和物流信息）【中间状态】
     */
    const DeliverySend = 4;
    
    /**
     * @var int 已确认收货（用户或客服进行了确认收货操作）【终点】
     */
    const DeliveryConfirmed = 5;
    
    /**
     * @var int 取消发货 【终点】
     */
    const DeliveryCanceled = 6;
    /**
     * @var int 拒绝签收（用户拒绝签收快递）【终点】
     */
    const DeliveryRefuse = 7;

    public static function getStatusList (){
        return [
            self::DeliveryCreated => '发货单创建',
            self::DeliveryWaiting => '待发货',
            self::DeliveryProtocol => '生成租机协议',
            self::DeliverySend => '已发货',
            self::DeliveryConfirmed => '已确认收货',
            self::DeliveryCanceled => '取消发货',
            self::DeliveryRefuse => '客户拒签',
        ];
    }
    /**
     * 校验状态值是否正确
     * @param int   $status
     * @return boolean
     */
    public static function verifyStatus( $status ){
        return array_key_exists($status,self::getStatusList());
    }

    public static function getStatusName($status){
        $list = self::getStatusList();
        if( isset($list[$status]) ){
            return $list[$status];
        }
        return '';
    }
    
    //-+----------------------------------------------------------------------
    // | 暂停的状态
    //-+----------------------------------------------------------------------
    /**
     * @var int 未暂停 
     */
    const PauseNo = 0;
    /**
     * @var int 已暂停
     */
    const PauseYes = 1;
    /**
     * 获取发货暂停状态列表
     * @return array【状态key=》状态名】
     */
    public static function getPauseList (){
        return [
            self::PauseNo => '进行中',
            self::PauseYes => '已暂停',
        ];
    }
    /**
     * 暂停状态值是否正确
     * @param int   $pause
     * @return boolean
     */
    public static function verifyPause( $pause ){
        return array_key_exists($pause,self::getPauseList());
    }

    /**
     * 获取暂停状态名称
     * @param int $pause 状态key
     * @return string/null
     */
    public static function getPauseName($pause){
        $list = self::getPauseList();
        if( isset($list[$pause]) ){
            return $list[$pause];
        }
        return '';
    }
}
