<?php
/**
 * 优惠券类型表
 * User: wangjinlin
 * Date: 2018/1/9
 * Time: 下午3:08
 */
class coupon_type_table extends table {
    protected $fields = [
        'id',
        'coupon_name',
        'coupon_type',
        'coupon_value',
        'range',
        'range_value',
        'mode',
        'describe',
        'use_restrictions',
        'only_id'
    ];
    protected $pk = 'id';
}