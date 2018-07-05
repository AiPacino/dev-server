<?php


file_put_contents(__DIR__.'/data/zhima_withholdingCloseCancelNotify-'.date('Y-m-d').'.log',"\n".  var_export($_POST,true),FILE_APPEND);

$_GET['m'] = 'api';
$_GET['c'] = 'zhima_withholding';
$_GET['a'] = 'withholdingCloseCancelNotify';
$_GET['trade_channel'] = 'ZHIMA';// 交易渠道

include '../index.php';

?>

