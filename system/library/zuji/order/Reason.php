<?php
namespace zuji\order;

//订单问题原因
class Reason{

    //订单取消原因
    const ORDER_CANCEL = 1;
    //订单退货原因
    const ORDER_RETURN =2;

    //订单问题所有原因
    static public $_ORDER_QUESTION = array(
        self::ORDER_CANCEL => array(
            '1'  => '额度不够',
            '2'  => '价格不划算',
            '3'  => '选错机型,重新下单',
            '4'  => '随便试试',
            '5'  => '不想租了',
            '6'  => '已经买了',
        ),
        self::ORDER_RETURN => array(
            '1'  => '寄错手机型号',
            '2'  => '不想用了',
            '3'  => '收到时已经拆封',
            '4'  => '手机无法正常使用',
            '5'  => '未收到手机',
            '6'  => '换货',

        )
    );
}

