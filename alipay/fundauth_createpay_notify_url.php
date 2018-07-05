<?php
/**
 * 资金预授权 解冻转支付 异步通知入口
 */

file_put_contents('./data/fundauth_createpay_notify-'.date('Y-m-d').'.log',"\n".  var_export($_POST,true),FILE_APPEND);

// 资金解冻转支付
$_GET['m'] = 'api';
$_GET['c'] = 'fund_auth';
$_GET['a'] = 'createpay_notify';
$_GET['auth_channel'] = 'ALIPAY';

include '../index.php';

