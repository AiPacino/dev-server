<?php

file_put_contents('./data/yajin_unfreeze_to_pay_notify-'.date('Y-m-d').'.log',"\n".  var_export($_POST,true),FILE_APPEND);

$_GET['m'] = 'api';
$_GET['c'] = 'fund_auth';
$_GET['a'] = 'yajin_unfreeze_to_pay_notify';
$_GET['auth_channel'] = 'ALIPAY';

if( $_GET['test']=='liuhongxing' ){

	$_POST = array (
	  'trade_no' => '2018021221001004560535968215',
	  'subject' => '预授权押金解冻转支付',
	  'paytools_pay_amount' => '[{"ALIPAYACCOUNT":"0.01"}]',
	  'buyer_email' => '136****5804',
	  'gmt_create' => '2018-02-12 17:51:24',
	  'notify_type' => 'trade_status_sync',
	  'quantity' => '1',
	  'out_trade_no' => '20180212000343',
	  'seller_id' => '2088821542502025',
	  'notify_time' => '2018-02-12 19:15:08',
	  'trade_status' => 'TRADE_SUCCESS',
	  'total_fee' => '0.01',
	  'gmt_payment' => '2018-02-12 17:51:24',
	  'seller_email' => 'zuji@huishoubao.com.cn',
	  'notify_action_type' => 'payByAccountAction',
	  'price' => '0.01',
	  'buyer_id' => '2088702999441562',
	  'notify_id' => '55862d204a7d4694e56771545a13395kbp',
	  'sign_type' => 'MD5',
	  'sign' => 'ea06353ca804752b3fc07248d3890116',
	)
	;
}

include '../index.php';

