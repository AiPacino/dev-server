<?php

/*
 *
 */

namespace zuji\debug;
/**
 *
 *
 * @author
 */
class Opreation{
    /**
     * @var int 修改子商品
     */
    const GOODS_STE_SKU_EDIT = 1;
    /**
     * @var int 修改或添加商品
     */
    const GOODS_STE_ADD = 2;
    /**
     * @var int 更改商品货号
     */
    const GOODS_STE_SN_EDIT = 3;
    /**
     * @var int 更改商品名称
     */
    const GOODS_STE_NAME_EDIT = 4;
    /**
     * @var int 批量恢复商品
     */
    const GOODS_STE_RECOVER = 5;
    /**
     * @var int 更改子商品名称
     */
    const GOODS_STE_SKU_NAME_EDIT = 6;
    /**
     * @var int 更改子商品货号
     */
    const GOODS_STE_SKU_SN_EDIT = 7;
    /**
     * @var int 修改商品上下架
     */
    const GOODS_STE_STATUS = 8;
    /**
     * @var int 更改商品是否在列表显示
     */
    const GOODS_STE_SHOW = 9;
    /**
     * @var int 更改商品属性
     */
    const GOODS_STE_ATTR = 10;
    /**
     * @var int 更改排序
     */
    const GOODS_STE_SORT = 11;
    /**
     * @var int 删除商品
     */
    const GOODS_STE_DEL = 12;
    /**
     * @var int 删除子商品
     */
    const GOODS_STE_SKU_DEL = 13;
    /**
     * @var int 更改商品标签状态
     */
    const GOODS_STE_STATUSSEXT_EDIT = 14;
    /**
     * @var int 上传商品图片
     */
    const GOODS_STE_UPLOAD = 15;
    /**
     * @var int 取消订单
     */
    const ORDER_STE_CANCEL = 16;
    /**
     * @var int 修改订单收货信息
     */
    const ORDER_STE_ADDRESS_EDIT = 17;
    /**
     * @var int 修改发货单模版
     */
    const ORDER_STE_TPL_PARCEL = 18;
    /**
     * @var int 确认付款
     */
    const ORDER_STE_PAY = 19;
    /**
     * @var int 确认订单
     */
    const ORDER_STE_CONFIRM = 20;
    /**
     * @var int 确认发货
     */
    const ORDER_STE_DELIVERY = 21;
    /**
     * @var int 修改物流信息
     */
    const ORDER_STE_DELIVERY_EDIT = 22;
    /**
     * @var int  确认完成订单
     */
    const ORDER_STE_FINISH = 23;
    /**
     * @var int  订单作废
     */
    const ORDER_STE_RECYCLE = 24;
    /**
     * @var int  删除订单
     */
    const ORDER_STE_DELETE = 25;
    /**
     * @var int  修改订单应付总额
     */
    const ORDER_STE_REAL_PRICE = 26;
    /**
     * @var int  确认配送
     */
    const ORDER_COMPLETE_PARCEL = 27;
    /**
     * @var int  删除发货单
     */
    const ORDER_DELETE_PARCEL = 28;
    /**
     * @var int  审核支付单
     */
    const ORDER_PAYMENT_CHECK = 29;
    /**
     * 列表
     * @return array    列表
     */
    public static function getOpreationList(){
        return [
            self::GOODS_STE_SKU_EDIT => '修改子商品',
            self::GOODS_STE_ADD => '修改或添加商品',
            self::GOODS_STE_SN_EDIT => '更改商品货号',
            self::GOODS_STE_NAME_EDIT => '更改商品名称',
            self::GOODS_STE_RECOVER => '批量恢复商品',
            self::GOODS_STE_SKU_NAME_EDIT => '更改子商品名称',
            self::GOODS_STE_SKU_SN_EDIT => '更改子商品货号',
            self::GOODS_STE_STATUS => '修改商品上下架',
            self::GOODS_STE_SHOW => '更改商品是否在列表显示',
            self::GOODS_STE_ATTR => '更改商品属性',
            self::GOODS_STE_SORT => '更改排序',
            self::GOODS_STE_DEL => '删除商品',
            self::GOODS_STE_SKU_DEL => '删除子商品',
            self::GOODS_STE_STATUSSEXT_EDIT => '更改商品标签状态',
            self::GOODS_STE_UPLOAD => '上传商品图片',
            self::ORDER_STE_CANCEL => '取消订单',
            self::ORDER_STE_ADDRESS_EDIT => '修改订单收货信息',
            self::ORDER_STE_TPL_PARCEL => '修改发货单模版',
            self::ORDER_STE_PAY => '确认付款',
            self::ORDER_STE_CONFIRM => '确认订单',
            self::ORDER_STE_DELIVERY => '确认发货',
            self::ORDER_STE_DELIVERY_EDIT => '修改物流信息',
            self::ORDER_STE_FINISH => '确认完成订单',
            self::ORDER_STE_RECYCLE => '订单作废',
            self::ORDER_STE_DELETE => '删除订单',
            self::ORDER_STE_REAL_PRICE => '修改订单应付总额',
            self::ORDER_COMPLETE_PARCEL => '确认配送',
            self::ORDER_DELETE_PARCEL => '删除发货单',
            self::ORDER_PAYMENT_CHECK => '审核支付单',
        ];
    }
    /**
     * 值 转换成 名称
     * @param int $status   值
     * @return string 名称
     */
    public static function getOpreationName($status){
        $list = self::getOpreationList();
        if( isset($list[$status]) ){
            return $list[$status];
        }
        return '';
    }

}
