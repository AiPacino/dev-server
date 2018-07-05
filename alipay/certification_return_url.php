<?php

file_put_contents('./data/certification-'.date('Y-m-d').'.log',"\n".  var_export($_POST,true),FILE_APPEND);
// 
$_GET['m'] = 'api';
$_GET['c'] = 'certification';
$_GET['a'] = 'zhima_return_page';

include '../index.php';