<?php use zuji\order\ReturnStatus;
include template('header', 'admin'); ?>

<script type="text/javascript" src="<?php echo __ROOT__ ?>statics/js/admin/order2_action.js"></script>

<body>
    <?php if(!$inner){?>
    <div class="fixed-nav layout">
	<ul>
	    <li class="first">支付单详情</li>
	    <li class="spacer-gray"></li>
	</ul>
	<div class="hr-gray"></div>
    </div>
    <?php }?>
    <div class="content <?php if(!$inner){echo 'padding-big have-fixed-nav';}?>">
	<!--支付详情-->
	<table cellpadding="0" cellspacing="0" class="border bg-white layout margin-top">
	    <tbody>
		<tr class="bg-gray-white line-height-40 border-bottom">
		    <th class="text-left padding-big-left">支付详情</th>
		</tr>
		<tr class="border">
		    <td class="padding-big-left padding-big-right">
			<table cellpadding="0" cellspacing="0" class="layout">
			    <tbody>
				<tr class="line-height-40">
				    <th class="text-left">支付状态：<?php echo $payment_info['payment_status_show']; ?></th>
				    <th class="text-left">交易流水：<?php echo $payment_info['trade_no']; ?></th>
				</tr>
				<tr class="line-height-40">
				    <td class="text-left">应付金额：<?php echo $payment_info['amount']; ?></td>
				    <td class="text-left">实付金额：<?php echo $payment_info['payment_amount']; ?></td>
				</tr>
				<tr class="line-height-40">
				    <td class="text-left">创建时间：<?php echo $payment_info['create_time_show']; ?></td>
				    <td class="text-left">支付时间：<?php echo $payment_info['payment_time_show']; ?></td>
				</tr>
			    </tbody>
			</table>
		    </td>
		</tr>
		<tr class="border">
		    <td class="padding-big-left padding-big-right">
			<table cellpadding="0" cellspacing="0" class="layout">
			    <tbody>
				<tr class="line-height-40">
				    <th class="text-left">支付平台：<?php echo $payment_info['payment_channel_show']; ?></th>
				    <th class="text-left">支付宝交易码：<?php echo $trade_info['out_trade_no'];?></th>
				</tr>
				<tr class="line-height-40">
				    <td class="text-left">收款账户：<?php echo $trade_info['seller_email'];?></td>
				    <td class="text-left">付款账户：<?php echo $trade_info['buyer_email'];?></td>
				</tr>
			    </tbody>
			</table>
		    </td>
		</tr>
		<?php if($payment_info['apply_status']>0){?>
		<tr class="border">
		    <td class="padding-big-left padding-big-right">
			<table cellpadding="0" cellspacing="0" class="layout">
			    <tbody>
				<tr class="line-height-40">
				    <th class="text-left" colspan="1">申请退款状态：<?php echo $payment_info['apply_status_show']; ?></th>
				    <th class="text-left" colspan="1">申请时间：<?php echo $payment_info['apply_time_show'];?></th>
				    <th class="text-left" colspan="1">审核时间：</th>
				</tr>
				<tr class="line-height-40">
				    <td class="text-left" colspan="3">申请理由：<?php echo $payment_info['apply_status_name']; ?></td>
				</tr>
				<tr class="line-height-40">
				    <td class="text-left" colspan="3">客服备注：<?php echo $payment_info['admin_remark'];?></td>
				</tr>
			    </tbody>
			</table>
		    </td>
		</tr>
		<?php }?>


	    </tbody>
	</table>

    </div>
<?php include template('footer', 'admin'); ?>
    <script>
	$('.table').resizableColumns();
    </script>
