<?php
/**
 * 优惠券奖池
 * User: wangjinlin
 * Date: 2018/1/9
 * Time: 下午3:09
 */
class coupon_table extends table {
    protected $fields = [
        'id',
        'coupon_type_id',
        'coupon_no',
        'status',
        'start_time',
        'end_time',
        'user_id',
    ];
    protected $pk = 'id';
}