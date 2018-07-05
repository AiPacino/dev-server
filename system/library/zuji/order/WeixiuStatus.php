<?php
/**
 * 维修申请单状态
 * @access public
 * @author Chenchun <Chenchun@huishoubao.com.cn>
 * @copyright (c) 2017, Huishoubao
 *
 */
namespace zuji\order;
/**
 * WeixiuStatus 退货申请单状态类
 *
 * @author Chenchun <Chenchun@huishoubao.com.cn>
 */
class WeixiuStatus{

    const WeixiuInvalid = 0;
    /**
     * @var int 已申请（生成维修单，等待客服处理）
     */
    const WeixiuCreated = 1;
    /**
     * @var int 待发货（客服处理完毕，等待用户发货）
     */
    const WeixiuWaiting = 2;

    /**
     * @var int 收货检测中（客服已收货正在检测中）
     */

    const WeixiuDenied = 4;
    /**
     * @var int 维修有风险 （客服对货物检测后结果声明，等待用户确认是否修）
     */
    const WeixiuCanceled = 8;

    /**
     * @var int 退回中 （用户不想修理责将货物原路退回）
     */
    const WeixiuConfirmed= 16;
    /**
     * @var int 维修中 （用户确认修理者开始分配给修理人员修理）
     */
    const WeixiuBack= 32;
    /**
     * @var int 回寄中 （修理完毕将货物寄回已用户）
     */
    const WeixiuReceive= 64;
    /**
     * @var int 确认用户收货 （用户收到修理完货物）
     */
    const WeixiuComment= 128;
    /**
     * @var int 已评论 (用户对本次修理做出评价)
     */
    const WeixiuShut = 256;
    /**
     * @var int 已关闭 (未完成评价的单规定时间内关闭状态)
     */
    public static function getStatusList(){
        return [
            self::WeixiuInvalid => '已申请（维修申请）',
            self::WeixiuCreated => '待发货（维修申请）',
            self::WeixiuWaiting => '收货检测中（维修申请）',
            self::WeixiuDenied => '维修有风险（维修申请）',
            self::WeixiuCanceled => '退回中（维修申请）',
            self::WeixiuConfirmed => '维修中（维修申请）',
            self::WeixiuBack => '回寄中（维修申请）',
            self::WeixiuReceive => '确认用户收货（维修申请）',
            self::WeixiuComment => '已评论（维修申请）',
            self::WeixiuShut => '已关闭（维修申请）',
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