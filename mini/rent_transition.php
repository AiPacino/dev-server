<?php
/**
 * 芝麻小程序 确认订单异步通知接口
 */

file_put_contents(__DIR__.'/rent_transition-'.date('Y-m-d').'.log',"\n".  var_export($_POST,true),FILE_APPEND);

$_GET['m'] = 'api';
$_GET['c'] = 'mini_back';
$_GET['a'] = 'rent_transition';
$_GET['trade_channel'] = 'ALIPAY';// 交易渠道

include '../index.php';

?>

