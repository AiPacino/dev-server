<?php

file_put_contents('./data/fenqi_unfreeze_to_pay_notify-'.date('Y-m-d').'.log',"\n".  var_export($_POST,true),FILE_APPEND);

$_GET['m'] = 'api';
$_GET['c'] = 'fund_auth';
$_GET['a'] = 'fenqi_unfreeze_to_pay_notify';
$_GET['auth_channel'] = 'ALIPAY';

include '../index.php';

