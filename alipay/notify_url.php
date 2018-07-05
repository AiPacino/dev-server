<?php


file_put_contents(__DIR__.'/notify_url-'.date('Y-m-d').'.log',"\n".  var_export($_POST,true),FILE_APPEND);

$_GET['m'] = 'api';
$_GET['c'] = 'trade';
$_GET['a'] = 'notify';
$_GET['trade_channel'] = 'ALIPAY';// 交易渠道

include '../index.php';

?>

