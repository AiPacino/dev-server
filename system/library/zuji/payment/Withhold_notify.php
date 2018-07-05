<?php
/**
 * 支付宝代扣协议
 *
 */
namespace zuji\payment;


/**
 * 支付宝代扣协议 接口
 *
 */
abstract class Withhold_notify {

    /**
     * @var string 正常
     */
    const NORMAL = "NORMAL";

    /**
     * @var string 未签约
     */
    const UNSIGN = "UNSIGN";

    /**
     * 获取代扣协议状态列表
     * @return array  空：无效,TEMP：暂存，协议未生效；NORMAL：正常；STOP：暂停',
     */
    public static function getStatusList(){
        return [
            self::NORMAL => '正常',
            self::UNSIGN => '未签约',
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


}
