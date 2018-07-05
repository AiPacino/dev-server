<?php
/**
 * 支付宝代扣 异步通知入口
 */

file_put_contents(__DIR__.'/data/withholding_notify_url-'.date('Y-m-d').'.log',"\n".  var_export($_POST,true),FILE_APPEND);

$_GET['m'] = 'api';
$_GET['c'] = 'withholding';
$_GET['a'] = 'notify';
$_GET['channel_code'] = 'ALIPAY';// 渠道

include '../index.php';

?>

