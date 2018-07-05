<?php
// 资金预授权 分期解冻异步通知
file_put_contents('./data/fenqi_unfreeze_notify-'.date('Y-m-d').'.log',"\n".  var_export($_POST,true),FILE_APPEND);

$_GET['m'] = 'api';
$_GET['c'] = 'fund_auth';
$_GET['a'] = 'fenqi_unfreeze_notify';
$_GET['auth_channel'] = 'ALIPAY';

include '../index.php';

