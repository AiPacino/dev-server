<?php


include 'order_creater/OrderCreaterComponnet.class.php';
include 'order_creater/UserComponnet.class.php';
include 'order_creater/CreditComponnet.class.php';
include 'order_creater/SkuComponnet.class.php';
include 'order_creater/CouponComponnet.class.php';

include 'OrderCreater.class.php';


$user_id = 1;
$sku_id = 1;
$coupon_no = 'C12345678';

// 订单创建器
$OrderCreater = new \oms\OrderCreater();

// 用户
$UserComponnet = new \oms\order_creater\UserComponnet($OrderCreater,$user_id);
$OrderCreater->set_user_componnet($UserComponnet);

// 商品
$SkuComponnet = new \oms\order_creater\SkuComponnet($OrderCreater,$sku_id);
$OrderCreater->set_sku_componnet($SkuComponnet);


// 装饰者 信用
$CreditComponnet = new \oms\order_creater\CreditComponnet($OrderCreater,$user_id);
//
// 
// 装饰者 优惠券
$CouponCommponnet = new \oms\order_creater\CouponComponnet($CreditComponnet,$user_id,$sku_id);


$b = $CouponCommponnet->filter();

//var_dump($OrderCreater);

var_dump( $b );
if( !$b ){
    var_dump( $OrderCreater->get_error() );
}


$b = $CouponCommponnet->create();
var_dump( $b );
if( !$b ){
    var_dump( $OrderCreater->get_error() );
}
