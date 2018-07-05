<?php

file_put_contents('./data/prepayment_notify_url-'.date('Y-m-d').'.log',"\n".  var_export($_POST,true),FILE_APPEND);

$_GET['m'] = 'api';
$_GET['c'] = 'installment';
$_GET['a'] = 'prepayment_notify';
$_GET['trade_channel'] = 'ALIPAY';// 交易渠道

include '../index.php';

?>

