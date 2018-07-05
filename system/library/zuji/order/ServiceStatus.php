<?php

/**
 * （平台）服务单状态
 * @access public 
 * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
 * @copyright (c) 2017, Huishoubao
 * 
 */

namespace zuji\order;

/**
 * ReceiveStatus （平台）收货单状态
 *
 * @author wuhaiyan <wuhaiyan@huishoubao.com.cn>
 */
class ServiceStatus {
    
    /**
     * @var int 无效状态（为服务表的状态默认值设计）
     * 【注意：】绝对不允许出现出现状态为0的记录（要求程序控制）
     */
    const ServiceProhibit = 0;
    /**
     * @var int 初始化状态（服务表生成时）
     */
    const ServiceInvalid = 1;
    /**
     * @var int 订单开启（服务中。）
     */
    const ServiceOpen = 2;
    /**
     * @var int 服务取消（终点）
     */
    const ServiceCancel = 3;
    /**
     * @var int 服务关闭（终点）
     */
    const ServiceClose = 4;
    /**
     * @var int 服务超时  用于列表判断/不存入数据库
     */
    const ServiceTimeout = 5;

    

    public static function getStatusList (){
        return [
            self::ServiceInvalid => '',
            self::ServiceOpen => '服务开启',
            self::ServiceCancel => '服务取消',
            self::ServiceClose => '服务关闭',
            self::ServiceTimeout=>'服务超时',
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
