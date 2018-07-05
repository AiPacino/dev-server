<?php
/**
 * Created by PhpStorm.
 * @author: <jinliangping@huishoubao.com.cn>
 * Date: 2018/1/19 0019-上午 11:20
 * @copyright (c) 2017, Huishoubao
 */

namespace oms\operator;


class OperatorList implements Operator
{

    public static function getOperatorList(){
        return [
            self::Type_User => '买家',
            self::Type_Admin=>'卖家',
            self::Type_System=>'系统',
            self::Type_Store=>'门店',
        ];
    }

    public static function getOperatorName($status){
        $list = self::getOperatorList();
        if( isset($list[$status]) ){
            return $list[$status];
        }
        return '';
    }

    /**
     * 操作员类型
     * @return int
     */
    public function get_type(){}

    /**
     * ID
     * @return int
     */
    public function get_id(){}

    /**
     * Username
     * @return int
     */
    public function get_username(){}
}