<!DOCTYPE html PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.0//EN" "http://www.wapforum.org/DTD/xhtml-mobile10.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
	<title>支付宝手机网页</title>
	<meta name="viewport"
	      content="width=device-width, initial-scale=1.0, user-scalable=yes" />
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    </head>
    <body>
	<a  target="_blank" href="/index.php?m=api&c=authorization&a=initialize">用户授权</a><br/>
	<a  target="_blank" href="/api.php?m=api&c=fund_auth&a=initialize&order_no=2017122600024&auth_channel=ALIPAY&return_url=<?php echo urlencode('http://dev-h5-zuji.huishoubao.com/confirmOrder');?>">资金授权</a><br/> 
	<a  target="_blank" href="/index.php?m=api&c=trade&a=initialize">测试支付</a><br/> 
	<?php $url = urlencode('http://dev-admin-zuji.huishoubao.com/zhima-return.php')?>
	<a  target="_blank" href="https://zmhatcher.zmxy.com.cn/creditlife/operatorEntrance.htm?productId=2017120101000222123430780086&channel=creditlife&callBackUrl=<?php echo $url?>">测试支付</a><br/> 
    </body>
</html>
