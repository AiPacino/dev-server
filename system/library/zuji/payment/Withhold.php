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
abstract class Withhold {

    /**
     * @var int 无效记录
     */
    const Initialize = 0;
    /**
     * @var int 已签约
     */
    const SIGN = 1;
    /**
     * @var int 已解约
     */
    const UNSIGN = 2;


    /**
     * 获取代扣协议状态列表
     * @return array
     */
    public static function getStatusList(){
        return [
            self::SIGN => '已签约',
            self::UNSIGN => '已解约',
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
